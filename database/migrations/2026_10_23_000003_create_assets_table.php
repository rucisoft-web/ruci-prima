<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 40)->nullable();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->foreignId('asset_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_no')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('acquisition_cost', 18, 2)->default(0);
            $table->decimal('replacement_cost', 18, 2)->default(0);
            $table->decimal('expected_life_years', 5, 1)->default(5);

            // Komponen skor kesehatan (0–100).
            $table->unsignedTinyInteger('physical_condition')->default(80);
            $table->unsignedTinyInteger('maintenance_compliance')->default(80);
            $table->unsignedTinyInteger('breakdown_reliability')->default(80);
            $table->unsignedTinyInteger('remaining_life_score')->default(80);
            $table->unsignedTinyInteger('safety_condition')->default(80);
            $table->unsignedTinyInteger('operational_reliability')->default(80);
            $table->unsignedTinyInteger('documentation_score')->default(80);
            $table->decimal('health_score', 5, 1)->default(80);
            $table->string('condition', 20)->default('good');

            // Komponen kekritisan (1–5).
            $table->unsignedTinyInteger('safety_impact')->default(2);
            $table->unsignedTinyInteger('guest_impact')->default(2);
            $table->unsignedTinyInteger('revenue_impact')->default(2);
            $table->unsignedTinyInteger('operational_impact')->default(2);
            $table->unsignedTinyInteger('replacement_difficulty')->default(2);
            $table->decimal('criticality_score', 4, 2)->default(2);
            $table->string('criticality', 20)->default('low');

            $table->decimal('risk_score', 5, 1)->default(0);
            $table->string('priority', 20)->default('low');
            $table->string('status', 20)->default('operational');
            $table->text('note')->nullable();

            // Identifikasi awal / verifikasi lapangan.
            $table->string('verification_status', 20)->default('needs_id');
            $table->string('identification_source', 20)->default('manual');
            $table->foreignId('asset_survey_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('verified_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('verified_at')->nullable();
            $table->text('verification_note')->nullable();

            $table->string('qr_token', 64)->unique();
            $table->timestamps();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'is_active', 'risk_score']);
            $table->index(['company_id', 'location_id']);
            $table->index(['company_id', 'verification_status']);
        });

        Schema::create('asset_survey_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_survey_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->string('team_name');
            $table->foreignId('asset_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('state', 20)->default('draft');
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('asset_survey_assignment_user', function (Blueprint $table) {
            $table->foreignId('asset_survey_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['asset_survey_assignment_id', 'user_id'], 'survey_assignment_user_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_survey_assignment_user');
        Schema::dropIfExists('asset_survey_assignments');
        Schema::dropIfExists('assets');
    }
};
