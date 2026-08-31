<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scan_reports', function (Blueprint $table) {
            if (! $this->hasIndex('scan_reports', 'scan_reports_created_at_index')) {
                $table->index('created_at');
            }
        });

        Schema::table('admin_activity_logs', function (Blueprint $table) {
            if (! $this->hasIndex('admin_activity_logs', 'admin_activity_logs_created_at_index')) {
                $table->index('created_at');
            }
        });

        Schema::table('pdf_download_logs', function (Blueprint $table) {
            if (! $this->hasIndex('pdf_download_logs', 'pdf_download_logs_created_at_index')) {
                $table->index('created_at');
            }
        });

        Schema::table('cve_findings', function (Blueprint $table) {
            if (! $this->hasIndex('cve_findings', 'cve_findings_severity_index')) {
                $table->index('severity');
            }
            if (! $this->hasIndex('cve_findings', 'cve_findings_service_name_index')) {
                $table->index('service_name');
            }
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            if (Schema::hasTable('activity_logs') && ! $this->hasIndex('activity_logs', 'activity_logs_created_at_index')) {
                $table->index('created_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('scan_reports', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
        Schema::table('admin_activity_logs', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
        Schema::table('pdf_download_logs', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
        Schema::table('cve_findings', function (Blueprint $table) {
            $table->dropIndex(['severity']);
            $table->dropIndex(['service_name']);
        });
        if (Schema::hasTable('activity_logs')) {
            Schema::table('activity_logs', function (Blueprint $table) {
                $table->dropIndex(['created_at']);
            });
        }
    }

    private function hasIndex(string $table, string $index): bool
    {
        $indexes = Schema::getIndexes($table);

        foreach ($indexes as $meta) {
            if (($meta['name'] ?? '') === $index) {
                return true;
            }
        }

        return false;
    }
};
