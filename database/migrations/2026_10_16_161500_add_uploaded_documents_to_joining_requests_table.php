<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('joining_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('joining_requests', 'uploaded_documents')) {
                $table->json('uploaded_documents')->nullable()->after('documents_submitted');
            }
        });
    }

    public function down(): void
    {
        Schema::table('joining_requests', function (Blueprint $table) {
            if (Schema::hasColumn('joining_requests', 'uploaded_documents')) {
                $table->dropColumn('uploaded_documents');
            }
        });
    }
};
