
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('joining_requests') && !Schema::hasColumn('joining_requests', 'photo_url')) {
            Schema::table('joining_requests', function (Blueprint $table) {
                $table->text('photo_url')->nullable()->after('documents_submitted');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('joining_requests') && Schema::hasColumn('joining_requests', 'photo_url')) {
            Schema::table('joining_requests', function (Blueprint $table) {
                $table->dropColumn('photo_url');
            });
        }
    }
};
