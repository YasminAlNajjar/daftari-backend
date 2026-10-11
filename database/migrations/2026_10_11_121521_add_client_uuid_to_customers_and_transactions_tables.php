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
        Schema::table('customers', function (Blueprint $table) {
            $table->uuid('client_uuid')
                ->nullable()
                ->after('user_id');

            $table->unique(
                ['user_id', 'client_uuid'],
                'customers_user_client_uuid_unique'
            );
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->uuid('client_uuid')
                ->nullable()
                ->after('user_id');

            $table->unique(
                ['user_id', 'client_uuid'],
                'transactions_user_client_uuid_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique('transactions_user_client_uuid_unique');
            $table->dropColumn('client_uuid');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique('customers_user_client_uuid_unique');
            $table->dropColumn('client_uuid');
        });
    }
};
