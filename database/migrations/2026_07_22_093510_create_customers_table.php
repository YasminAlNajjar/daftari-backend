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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
             $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');

            $table->string('phone', 20);

            $table->text('notes')
                ->nullable();

            $table->decimal('credit_limit', 15, 2)
                ->nullable();

            $table->boolean('is_active')
                ->default(true);


            $table->index(['user_id', 'name']);
            $table->index(['user_id', 'phone']);


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
