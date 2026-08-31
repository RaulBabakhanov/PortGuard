<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scan_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scan_id')->constrained()->cascadeOnDelete();
            $table->string('download_token', 64)->unique();
            $table->string('filename');
            $table->string('mime_type', 80)->default('application/pdf');
            $table->unsignedBigInteger('byte_size');
            $table->string('content_sha256', 64);
            $table->string('content_hmac', 64);
            $table->longText('content_encrypted');
            $table->foreignId('created_by_admin_id')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamps();

            $table->unique('scan_id');
            $table->index('content_sha256');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_reports');
    }
};
