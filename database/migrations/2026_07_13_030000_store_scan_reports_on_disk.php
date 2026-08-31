<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scan_reports', function (Blueprint $table) {
            if (! Schema::hasColumn('scan_reports', 'storage_path')) {
                $table->string('storage_path')->nullable()->after('content_hmac');
            }
        });

        // max_allowed_packet hatasını önlemek: blob artık zorunlu değil
        DB::statement('ALTER TABLE scan_reports MODIFY content_encrypted LONGTEXT NULL');
    }

    public function down(): void
    {
        Schema::table('scan_reports', function (Blueprint $table) {
            if (Schema::hasColumn('scan_reports', 'storage_path')) {
                $table->dropColumn('storage_path');
            }
        });

        DB::statement('ALTER TABLE scan_reports MODIFY content_encrypted LONGTEXT NOT NULL');
    }
};
