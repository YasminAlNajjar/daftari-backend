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
            /*
             * حذف الـ unique القديم:
             * user_id + phone
             *
             * لأننا سنستخدم Soft Delete،
             * ونريد السماح بإعادة استخدام الرقم
             * إذا كان السجل السابق محذوفًا.
             */
            $table->dropUnique([
                'user_id',
                'phone',
            ]);

            $table->softDeletes();

            /*
             * لتحسين البحث عن رقم هاتف
             * داخل زبائن التاجر.
             */
            $table->index([
                'user_id',
                'phone',
            ]);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('customers', function (Blueprint $table) {

            $table->dropIndex([
                'user_id',
                'phone',
            ]);

            $table->dropSoftDeletes();

            $table->unique([
                'user_id',
                'phone',
            ]);
        });
    
    }
};
