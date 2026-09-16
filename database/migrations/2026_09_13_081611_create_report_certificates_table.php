<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_certificates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('report_export_id')
                ->unique()
                ->constrained('report_exports')
                ->cascadeOnDelete();

            $table->char(
                'verification_token_hash',
                64
            )->unique();

            $table->char(
                'file_hash',
                64
            )->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_certificates');
    }
};