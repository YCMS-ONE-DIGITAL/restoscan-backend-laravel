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
        Schema::create('staffs', function (Blueprint $table) {
            $table->id();   
              $table->unsignedBigInteger('restaurant_id');

        $table->string('name');
        $table->string('email')->unique();
        $table->string('phone')->nullable();

        $table->string('role'); // waiter, chef, manager
        $table->text('password'); // encrypted password

        $table->boolean('is_logged_in')->default(false); // SINGLE LOGIN
        $table->string('login_device')->nullable(); // device info

        $table->timestamps();

        $table->foreign('restaurant_id')
              ->references('id')->on('restaurants')
              ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staffs');
    }
};
