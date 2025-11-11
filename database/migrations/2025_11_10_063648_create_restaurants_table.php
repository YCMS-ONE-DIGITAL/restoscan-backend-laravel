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
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // Restaurant name
            $table->string('phone')->nullable();             // Contact number
            $table->string('email')->nullable();             // Optional: restaurant contact email
            $table->string('address')->nullable();           // Full address
            $table->string('logo')->nullable();              // Image/logo path
            $table->string('qr_code')->nullable();           // QR code image/path
            $table->boolean('is_active')->default(true);     // In case you want to deactivate temporarily
            
            // Relation: one user = one restaurant
            $table->unsignedBigInteger('user_id')->unique(); // unique ensures one-to-one
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');                     // Delete restaurant if user deleted

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};
