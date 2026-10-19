<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Alter users.avatar to text to support longer URLs and avoid truncation
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'avatar')) {
            Schema::table('users', function (Blueprint $table) {
                $table->text('avatar')->nullable()->change();
            });
        }

        // 2. Alter joining_requests fields to text to support detailed educational qualifications
        if (Schema::hasTable('joining_requests')) {
            Schema::table('joining_requests', function (Blueprint $table) {
                if (Schema::hasColumn('joining_requests', 'institution_board')) {
                    $table->text('institution_board')->nullable()->change();
                }
                if (Schema::hasColumn('joining_requests', 'qualification')) {
                    $table->text('qualification')->nullable()->change();
                }
            });
        }

        // 3. Alter employee_documents.status to string(50) so all status strings are accepted without truncation warnings
        if (Schema::hasTable('employee_documents') && Schema::hasColumn('employee_documents', 'status')) {
            Schema::table('employee_documents', function (Blueprint $table) {
                $table->string('status', 50)->default('approved')->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'avatar')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('avatar', 255)->nullable()->change();
            });
        }
    }
};
