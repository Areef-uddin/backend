<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('joining_requests', function (Blueprint $table) {
            $table->string('referral_source')->nullable()->default('Direct / Walk-in')->after('photo_url');
            $table->string('referred_by_name')->nullable()->after('referral_source');
            $table->string('referral_employee_code')->nullable()->after('referred_by_name');
            $table->string('referral_phone')->nullable()->after('referral_employee_code');
            $table->text('referral_notes')->nullable()->after('referral_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('joining_requests', function (Blueprint $table) {
            $table->dropColumn([
                'referral_source',
                'referred_by_name',
                'referral_employee_code',
                'referral_phone',
                'referral_notes',
            ]);
        });
    }
};
