<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('number', 40)->nullable();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->dateTime('inspected_at');
            $table->foreignId('inspector_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('result', 20)->default('pass');
            $table->unsignedTinyInteger('condition_score')->default(80);
            $table->text('finding')->nullable();
            $table->text('recommendation')->nullable();
            $table->decimal('meter_reading', 14, 2)->nullable();
            $table->string('meter_unit', 20)->nullable();
            $table->string('photo')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'inspected_at']);
        });

        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('number', 40)->nullable();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->dateTime('requested_at');
            $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reporter_name')->nullable();
            $table->string('reporter_contact')->nullable();
            $table->boolean('via_qr')->default(false);
            $table->string('request_type', 20)->default('corrective');
            $table->string('priority', 20)->default('medium');
            $table->text('description');
            $table->string('photo_before')->nullable();
            $table->string('state', 20)->default('new');
            $table->unsignedBigInteger('work_order_id')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'state']);
        });

        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('number', 40)->nullable();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('maintenance_request_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('preventive_plan_id')->nullable();
            $table->string('work_type', 20)->default('corrective');
            $table->string('priority', 20)->default('medium');
            $table->string('state', 20)->default('draft');
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('planned_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->text('description')->nullable();
            $table->text('root_cause')->nullable();
            $table->text('action_taken')->nullable();
            $table->decimal('labor_hours', 8, 2)->default(0);
            $table->decimal('sparepart_cost', 18, 2)->default(0);
            $table->decimal('vendor_cost', 18, 2)->default(0);
            $table->decimal('other_cost', 18, 2)->default(0);
            $table->decimal('total_cost', 18, 2)->default(0);
            $table->decimal('downtime_hours', 8, 2)->default(0);
            $table->string('photo_after')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'state']);
            $table->index(['company_id', 'planned_at']);
            $table->index(['company_id', 'completed_at']);
        });

        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->foreign('work_order_id')->references('id')->on('work_orders')->nullOnDelete();
        });

        Schema::create('preventive_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->string('frequency', 20)->default('monthly');
            $table->date('next_due_date');
            $table->foreignId('responsible_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->text('checklist')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'is_active', 'next_due_date']);
        });

        Schema::table('work_orders', function (Blueprint $table) {
            $table->foreign('preventive_plan_id')->references('id')->on('preventive_plans')->nullOnDelete();
        });

        Schema::create('asset_risks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('probability')->default(2);
            $table->unsignedTinyInteger('consequence')->default(2);
            $table->unsignedTinyInteger('risk_score')->default(4);
            $table->string('level', 20)->default('low');
            $table->text('mitigation')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('target_date')->nullable();
            $table->string('state', 20)->default('open');
            $table->timestamps();
        });

        Schema::create('capex_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('asset_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category', 20)->default('mandatory');
            $table->unsignedSmallInteger('target_year');
            $table->decimal('estimated_cost', 18, 2)->default(0);
            $table->unsignedTinyInteger('guest_impact')->default(3);
            $table->unsignedTinyInteger('safety_impact')->default(3);
            $table->unsignedTinyInteger('revenue_impact')->default(3);
            $table->unsignedTinyInteger('urgency')->default(3);
            $table->decimal('priority_score', 5, 1)->default(60);
            $table->text('justification')->nullable();
            $table->string('recommendation', 20)->default('replace');
            $table->string('state', 20)->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capex_proposals');
        Schema::dropIfExists('asset_risks');
        Schema::table('work_orders', fn (Blueprint $table) => $table->dropForeign(['preventive_plan_id']));
        Schema::dropIfExists('preventive_plans');
        Schema::table('maintenance_requests', fn (Blueprint $table) => $table->dropForeign(['work_order_id']));
        Schema::dropIfExists('work_orders');
        Schema::dropIfExists('maintenance_requests');
        Schema::dropIfExists('inspections');
    }
};
