<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_tokens', function (Blueprint $table) {
            $table->string('installation_id', 191)
                ->nullable()
                ->after('platform');

            $table->unique(
                ['user_id', 'installation_id'],
                'device_tokens_user_installation_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('device_tokens', function (Blueprint $table) {
            $table->dropUnique('device_tokens_user_installation_unique');
            $table->dropColumn('installation_id');
        });
    }
};
