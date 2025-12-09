<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {

            // 1️⃣ ADD COLUMN FIRST
            if (!Schema::hasColumn('orders', 'staff_id')) {
                $table->unsignedBigInteger('staff_id')->nullable()->after('customer_id');
            }
        });

        // 2️⃣ NOW that the column exists, clean staff_id values
        DB::statement('UPDATE orders SET staff_id = NULL');

        // 3️⃣ Add Foreign Key
        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('staff_id')
                  ->references('id')
                  ->on('staffs')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['staff_id']);
            $table->dropColumn('staff_id');
        });
    }
};
