<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\StockMovementType;
use App\Enums\SupplyRequestState;
use App\Enums\VehicleBookingState;
use App\Filament\App\Resources\RoomBookings\Pages\ManageRoomBookings;
use App\Filament\App\Resources\SupplyItems\Pages\ManageSupplyItems;
use App\Filament\App\Resources\SupplyRequests\Pages\CreateSupplyRequest;
use App\Filament\App\Resources\SupplyRequests\Pages\ViewSupplyRequest;
use App\Filament\App\Resources\VehicleBookings\Pages\ManageVehicleBookings;
use App\Models\Company;
use App\Models\MeetingRoom;
use App\Models\RoomBooking;
use App\Models\SupplyItem;
use App\Models\SupplyRequest;
use App\Models\Vehicle;
use App\Models\VehicleBooking;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Alur layar bagian umum lewat komponen Filament (form, aksi tabel, modal). */
class GeneralAffairsUiTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = $this->company();
        $this->actingAs($this->member($this->company, Role::Manajer));
        Filament::setCurrentPanel('app');
        Filament::setTenant($this->company);
        $this->useCompany($this->company);
    }

    public function test_room_booking_form_reports_conflict_and_capacity(): void
    {
        $room = MeetingRoom::create(['name' => 'Ruang A', 'capacity' => 8]);
        RoomBooking::create(['meeting_room_id' => $room->id, 'title' => 'Rapat pagi', 'starts_at' => today()->addDay()->setTime(9, 0), 'ends_at' => today()->addDay()->setTime(10, 0)]);

        Livewire::test(ManageRoomBookings::class)
            ->callAction('create', data: [
                'title' => 'Bentrok', 'meeting_room_id' => $room->id, 'attendees' => 12,
                'starts_at' => today()->addDay()->setTime(9, 30)->format('Y-m-d H:i:s'), 'ends_at' => today()->addDay()->setTime(11, 0)->format('Y-m-d H:i:s'),
            ])
            ->assertHasActionErrors(['ends_at', 'attendees']);

        Livewire::test(ManageRoomBookings::class)
            ->callAction('create', data: [
                'title' => 'Rapat siang', 'meeting_room_id' => $room->id, 'attendees' => 6,
                'starts_at' => today()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s'), 'ends_at' => today()->addDay()->setTime(11, 0)->format('Y-m-d H:i:s'),
            ])
            ->assertHasNoActionErrors();

        $this->assertSame(2, RoomBooking::count());
    }

    public function test_vehicle_booking_approve_and_return_from_table(): void
    {
        $vehicle = Vehicle::create(['plate_number' => 'D 1 AB', 'name' => 'Avanza', 'odometer' => 500]);
        $trip = VehicleBooking::create(['purpose' => 'Ke bank', 'destination' => 'Dago', 'starts_at' => now()->addHour(), 'ends_at' => now()->addHours(2)]);

        Livewire::test(ManageVehicleBookings::class)
            ->callTableAction('approve', $trip, data: ['vehicle_id' => $vehicle->id, 'driver_name' => 'Pak Ujang'])
            ->assertHasNoTableActionErrors()
            ->callTableAction('depart', $trip, data: ['odometer_start' => 500])
            ->callTableAction('return', $trip, data: ['odometer_end' => 562]);

        $trip->refresh();
        $this->assertSame(VehicleBookingState::Returned, $trip->state);
        $this->assertSame(62, $trip->distance_km);
        $this->assertSame(562, $vehicle->fresh()->odometer);
    }

    public function test_supply_item_created_with_opening_stock_and_restocked(): void
    {
        Livewire::test(ManageSupplyItems::class)
            ->callAction('create', data: ['code' => 'ATK-9', 'name' => 'Map biru', 'category' => 'stationery', 'unit' => 'pcs', 'min_stock' => 5, 'unit_cost' => 3000, 'is_active' => true, 'opening_stock' => 20])
            ->assertHasNoActionErrors();

        $item = SupplyItem::where('code', 'ATK-9')->firstOrFail();
        $this->assertEquals(20, $item->stock);

        Livewire::test(ManageSupplyItems::class)
            ->callTableAction('stock_in', $item, data: ['quantity' => 10, 'unit_cost' => 3500, 'moved_at' => today()->toDateString()])
            ->callTableAction('stock_out', $item, data: ['quantity' => 4, 'moved_at' => today()->toDateString(), 'reference' => 'Front Office'])
            ->callTableAction('adjust', $item, data: ['counted' => 25, 'note' => 'Opname akhir bulan']);

        $item->refresh();
        $this->assertEquals(25, $item->stock);
        $this->assertEquals(3500, (float) $item->unit_cost);
        $this->assertSame(4, $item->movements()->count());
    }

    public function test_supply_request_created_with_lines_and_issued(): void
    {
        $item = SupplyItem::create(['code' => 'ATK-1', 'name' => 'Kertas A4', 'unit' => 'rim']);
        $item->move(StockMovementType::In, 10);

        Livewire::test(CreateSupplyRequest::class)
            ->fillForm(['purpose' => 'Kebutuhan rapat', 'lines' => [['supply_item_id' => $item->id, 'quantity' => 3]]])
            ->call('create')
            ->assertHasNoFormErrors();

        $request = SupplyRequest::with('lines')->firstOrFail();
        $this->assertCount(1, $request->lines);

        Livewire::test(ViewSupplyRequest::class, ['record' => $request->getRouteKey()])
            ->callAction('issue', data: ['quantities' => [$request->lines[0]->id => 2]])
            ->assertHasNoActionErrors();

        $this->assertSame(SupplyRequestState::Issued, $request->fresh()->state);
        $this->assertEquals(8, $item->fresh()->stock);
    }
}
