<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       
        Schema::table('otp_codes', function (Blueprint $table) {
            $table->renameColumn('phone', 'email');
        });

        Schema::table('otp_codes', function (Blueprint $table) {
            $table->string('email', 150)->change();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('otp_codes', function (Blueprint $table) {
            $table->renameColumn('email', 'phone');
        });

        Schema::table('otp_codes', function (Blueprint $table) {
            $table->string('phone', 15)->change();
        });
    }
};
