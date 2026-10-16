<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracking_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attendance_record_id')->nullable()->constrained('attendance_records')->nullOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->enum('status', ['active', 'paused', 'completed'])->default('active');
            $table->decimal('total_distance_km', 8, 2)->default(0.00);
            $table->timestamps();
        });

        Schema::create('employee_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('tracking_sessions')->nullOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('accuracy', 6, 2)->nullable(); // meters
            $table->decimal('speed', 6, 2)->nullable(); // m/s
            $table->integer('battery_level')->nullable(); // percentage
            $table->boolean('is_mock')->default(false);
            $table->timestamp('recorded_at')->index();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_locations');
        Schema::dropIfExists('tracking_sessions');
    }
};
