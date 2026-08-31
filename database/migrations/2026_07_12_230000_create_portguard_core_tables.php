<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 100);
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('description');
            $table->json('properties')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create('targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 20);
            $table->string('ip', 45)->nullable();
            $table->string('cidr', 50)->nullable();
            $table->string('start_ip', 45)->nullable();
            $table->string('end_ip', 45)->nullable();
            $table->string('ports')->default('22,80,443,3306');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('target_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('type', 20);
            $table->string('ip', 45)->nullable();
            $table->string('cidr', 50)->nullable();
            $table->string('start_ip', 45)->nullable();
            $table->string('end_ip', 45)->nullable();
            $table->string('ports')->default('22,80,443,3306');
            $table->string('status', 20)->default('pending');
            $table->unsignedInteger('total_hosts')->default(0);
            $table->unsignedInteger('active_hosts')->default(0);
            $table->unsignedInteger('service_count')->default(0);
            $table->unsignedInteger('cve_count')->default(0);
            $table->longText('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('scan_hosts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scan_id')->constrained()->cascadeOnDelete();
            $table->string('ip', 45);
            $table->boolean('is_up')->default(false);
            $table->string('hostname')->nullable();
            $table->longText('raw_output')->nullable();
            $table->timestamps();

            $table->index(['scan_id', 'is_up']);
        });

        Schema::create('scan_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scan_host_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scan_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('product')->nullable();
            $table->string('version')->nullable();
            $table->unsignedInteger('port')->nullable();
            $table->string('protocol', 10)->nullable();
            $table->string('raw_line')->nullable();
            $table->timestamps();

            $table->index(['scan_id', 'name']);
        });

        Schema::create('cve_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scan_service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('service_name');
            $table->string('cve_id', 32);
            $table->text('description')->nullable();
            $table->string('severity', 20)->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'cve_id']);
            $table->index(['scan_id', 'service_name']);
        });

        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50);
            $table->string('title');
            $table->text('body')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
        });

        Schema::create('scheduled_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('target_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('frequency', 20);
            $table->string('ports')->default('22,80,443,3306');
            $table->boolean('is_active')->default(true);
            $table->timestamp('next_run_at')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->index(['next_run_at', 'is_active']);
        });

        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('default_ports')->default('22,80,443,3306');
            $table->boolean('notify_on_scan_complete')->default(true);
            $table->boolean('notify_on_cve_found')->default(true);
            $table->unsignedInteger('max_hosts_per_scan')->default(64);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_settings');
        Schema::dropIfExists('scheduled_scans');
        Schema::dropIfExists('user_notifications');
        Schema::dropIfExists('cve_findings');
        Schema::dropIfExists('scan_services');
        Schema::dropIfExists('scan_hosts');
        Schema::dropIfExists('scans');
        Schema::dropIfExists('targets');
        Schema::dropIfExists('activity_logs');
    }
};
