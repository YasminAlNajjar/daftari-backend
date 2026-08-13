<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
       Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();

            $table->string('phone', 20)->index();

            $table->string('code_hash');

            $table->unsignedTinyInteger('attempts')
                ->default(0);

            $table->unsignedTinyInteger('send_count')
                ->default(1);

            $table->dateTime('send_window_started_at')
                ->nullable();

            $table->dateTime('blocked_until')
                ->nullable();

            $table->dateTime('expires_at');

            $table->dateTime('last_sent_at')
                ->nullable();

             $table->dateTime('verified_at')
                ->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
    }
};