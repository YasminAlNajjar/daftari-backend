<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {

            $table->softDeletes();

            $table->text('description')
                ->nullable()
                ->change();

             // Index أنسب لعمليات Ownership
            $table->index([
                'user_id',
                'customer_id',
            ]);

            // حذف الـ index القديم
            $table->dropIndex([
                'user_id',
                'type',
            ]);

        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {

            $table->dropIndex([
                'user_id',
                'customer_id',
            ]);

            $table->index([
                'user_id',
                'type',
            ]);

            $table->string('description')
                ->nullable()
                ->change();

            $table->dropSoftDeletes();
        });
    }
};