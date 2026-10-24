<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Enums\RoomBookingState;
use App\Enums\StockMovementType;
use App\Enums\SupplyRequestState;
use App\Enums\VehicleBookingState;
use App\Models\LegalDocument;
use App\Models\MeetingRoom;
use App\Models\RoomBooking;
use App\Models\StockMovement;
use App\Models\SupplyItem;
use App\Models\SupplyRequest;
use App\Models\Vehicle;
use App\Models\VehicleBooking;
use App\Models\VehicleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class GeneralAffairsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useCompany($this->company());
    }

    public function test_room_booking_rejects_overlapping_slot_but_allows_adjacent(): void
    {
        $room = MeetingRoom::create(['name' => 'Ruang A', 'capacity' => 10]);
        $booking = RoomBooking::create(['meeting_room_id' => $room->id, 'title' => 'Rapat 1', 'starts_at' => today()->setTime(9, 0), 'ends_at' => today()->setTime(10, 0)]);

        $this->assertStringStartsWith('RR/', $booking->number);
        $this->assertSame(RoomBookingState::Requested, $booking->state);

        RoomBooking::create(['meeting_room_id' => $room->id, 'title' => 'Rapat 2', 'starts_at' => today()->setTime(10, 0), 'ends_at' => today()->setTime(11, 0)]);

        $this->expectException(LogicException::class);
        RoomBooking::create(['meeting_room_id' => $room->id, 'title' => 'Bentrok', 'starts_at' => today()->setTime(9, 30), 'ends_at' => today()->setTime(10, 30)]);
    }

    public function test_cancelled_booking_frees_the_room(): void
    {
        $room = MeetingRoom::create(['name' => 'Ruang A']);
        $first = RoomBooking::create(['meeting_room_id' => $room->id, 'title' => 'Rapat 1', 'starts_at' => today()->setTime(9, 0), 'ends_at' => today()->setTime(10, 0)]);
        $first->approve();
        $first->cancel();

        $second = RoomBooking::create(['meeting_room_id' => $room->id, 'title' => 'Rapat 2', 'starts_at' => today()->setTime(9, 0), 'ends_at' => today()->setTime(10, 0)]);
        $this->assertSame(RoomBookingState::Requested, $second->state);
        $this->assertSame(RoomBookingState::Cancelled, $first->fresh()->state);
    }

    public function test_vehicle_booking_flow_updates_odometer_and_blocks_double_booking(): void
    {
        $vehicle = Vehicle::create(['plate_number' => 'D 1 AB', 'name' => 'Avanza', 'odometer' => 1000, 'default_driver' => 'Pak Ujang']);
        $trip = VehicleBooking::create(['purpose' => 'Ke bank', 'destination' => 'Dago', 'starts_at' => now()->addHour(), 'ends_at' => now()->addHours(3)]);

        $trip->approve($vehicle->id);
        $this->assertSame('Pak Ujang', $trip->driver_name);

        $other = VehicleBooking::create(['purpose' => 'Ke pasar', 'destination' => 'Caringin', 'starts_at' => now()->addHours(2), 'ends_at' => now()->addHours(4)]);
        try {
            $other->approve($vehicle->id);
            $this->fail('Kendaraan yang sama tidak boleh dipakai dua pemesanan sekaligus.');
        } catch (LogicException) {
            $this->assertSame(VehicleBookingState::Requested, $other->fresh()->state);
        }

        $trip->depart();
        $trip->complete(1085);

        $this->assertSame(VehicleBookingState::Returned, $trip->state);
        $this->assertSame(85, $trip->distance_km);
        $this->assertSame(1085, $vehicle->fresh()->odometer);
    }

    public function test_vehicle_service_moves_next_service_schedule(): void
    {
        $vehicle = Vehicle::create(['plate_number' => 'D 2 AB', 'name' => 'Hiace', 'odometer' => 50000, 'next_service_date' => today()->subDays(3)]);
        $this->assertSame(1, Vehicle::query()->dueBefore(today()->addDays(30))->count());

        VehicleService::create(['vehicle_id' => $vehicle->id, 'service_date' => today(), 'odometer' => 50200, 'cost' => 950000,
            'next_service_date' => today()->addMonths(6), 'next_service_km' => 60000]);

        $vehicle->refresh();
        $this->assertSame(today()->addMonths(6)->toDateString(), $vehicle->next_service_date->toDateString());
        $this->assertSame(50200, $vehicle->odometer);
        $this->assertSame(0, Vehicle::query()->dueBefore(today()->addDays(30))->count());
    }

    public function test_stock_movements_keep_balance_and_refuse_negative_stock(): void
    {
        $item = SupplyItem::create(['code' => 'ATK-1', 'name' => 'Kertas A4', 'unit' => 'rim', 'min_stock' => 5]);
        $item->move(StockMovementType::In, 10, ['unit_cost' => 52000]);
        $item->move(StockMovementType::Out, 6);

        $item->refresh();
        $this->assertEquals(4, $item->stock);
        $this->assertEquals(52000, (float) $item->unit_cost);
        $this->assertTrue($item->isLow());
        $this->assertSame(1, SupplyItem::query()->lowStock()->count());

        $item->move(StockMovementType::Adjust, -1, ['note' => 'Stok opname']);
        $this->assertEquals(3, $item->fresh()->stock);
        $this->assertEquals(3, StockMovement::latest('id')->first()->stock_after);

        $this->expectException(LogicException::class);
        $item->move(StockMovementType::Out, 4);
    }

    public function test_supply_request_issue_deducts_stock_all_or_nothing(): void
    {
        $paper = SupplyItem::create(['code' => 'ATK-1', 'name' => 'Kertas A4', 'unit' => 'rim']);
        $pen = SupplyItem::create(['code' => 'ATK-2', 'name' => 'Pulpen', 'unit' => 'pcs']);
        $paper->move(StockMovementType::In, 10);
        $pen->move(StockMovementType::In, 2);

        $request = SupplyRequest::create(['purpose' => 'Kebutuhan FO']);
        $request->lines()->create(['supply_item_id' => $paper->id, 'quantity' => 3]);
        $line = $request->lines()->create(['supply_item_id' => $pen->id, 'quantity' => 5]);
        $this->assertStringStartsWith('PB/', $request->number);

        try {
            $request->issue();
            $this->fail('Stok pulpen tidak cukup.');
        } catch (LogicException) {
            $this->assertEquals(10, $paper->fresh()->stock);
            $this->assertSame(SupplyRequestState::Submitted, $request->fresh()->state);
        }

        $request->issue([$line->id => 2]);
        $this->assertEquals(7, $paper->fresh()->stock);
        $this->assertEquals(0, $pen->fresh()->stock);
        $this->assertSame(SupplyRequestState::Issued, $request->fresh()->state);
        $this->assertEquals(2, $line->fresh()->issued_quantity);
    }

    public function test_document_status_follows_expiry_and_reminder_window(): void
    {
        $make = fn (?int $days, int $reminder = 60) => LegalDocument::create(['name' => 'Dok', 'expiry_date' => $days === null ? null : today()->addDays($days), 'reminder_days' => $reminder]);

        $this->assertSame(DocumentStatus::Permanent, $make(null)->status);
        $this->assertSame(DocumentStatus::Expired, $make(-1)->status);
        $this->assertSame(DocumentStatus::Expiring, $make(30)->status);
        $this->assertSame(DocumentStatus::Valid, $make(30, 14)->status);
        $this->assertSame(DocumentStatus::Valid, $make(200)->status);

        $this->assertSame(2, LegalDocument::query()->needsAction()->count());
        $this->assertSame(2, LegalDocument::query()->withStatus(DocumentStatus::Valid)->count());
        $this->assertSame(1, LegalDocument::query()->withStatus(DocumentStatus::Expiring)->count());
    }

    public function test_request_line_cannot_use_item_from_another_company(): void
    {
        $request = SupplyRequest::create(['purpose' => 'Uji']);
        $this->useCompany($this->company('PT Lain'));
        $foreign = SupplyItem::create(['code' => 'X-1', 'name' => 'Barang lain']);

        $this->expectException(LogicException::class);
        $request->lines()->create(['supply_item_id' => $foreign->id, 'quantity' => 1]);
    }
}
