<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 40)->unique();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('allowed_networks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('cidr', 50);
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active']);
        });

        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('ip', 45);
            $table->string('asset_type', 40)->default('server');
            $table->string('criticality', 20)->default('medium');
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('owner_name')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['ip', 'is_active']);
            $table->index(['criticality']);
        });

        Schema::create('municipality_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('kvkk_retention_days')->default(365);
            $table->unsignedInteger('approval_host_threshold')->default(16);
            $table->boolean('enforce_allowed_networks')->default(true);
            $table->boolean('require_approval_for_critical')->default(true);
            $table->timestamps();
        });

        Schema::create('admin_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_user_id')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->string('action', 80);
            $table->string('description');
            $table->nullableMorphs('subject');
            $table->json('properties')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['action', 'created_at']);
        });

        Schema::create('pdf_download_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scan_report_id')->nullable()->constrained('scan_reports')->nullOnDelete();
            $table->string('actor_type', 20);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_email')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('content_sha256', 64)->nullable();
            $table->timestamps();

            $table->index(['scan_id', 'created_at']);
            $table->index(['actor_type', 'actor_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('email')->constrained()->nullOnDelete();
        });

        Schema::table('scans', function (Blueprint $table) {
            $table->string('approval_status', 20)->default('none')->after('status');
            $table->foreignId('approved_by_admin_id')->nullable()->after('approval_status')->constrained('admin_users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by_admin_id');
            $table->timestamp('rejected_at')->nullable()->after('approved_at');
            $table->string('rejection_reason')->nullable()->after('rejected_at');
            $table->boolean('requires_approval')->default(false)->after('rejection_reason');
            $table->string('approval_reason')->nullable()->after('requires_approval');
        });
    }

    public function down(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by_admin_id');
            $table->dropColumn([
                'approval_status', 'approved_at', 'rejected_at', 'rejection_reason',
                'requires_approval', 'approval_reason',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
        });

        Schema::dropIfExists('pdf_download_logs');
        Schema::dropIfExists('admin_activity_logs');
        Schema::dropIfExists('municipality_settings');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('allowed_networks');
        Schema::dropIfExists('departments');
    }
};
