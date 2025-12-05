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
        Schema::create('public_otp_verifications', function (Blueprint $table) {
          $table->id();
    $table->foreignId('restaurant_id')->constrained()->onDelete('cascade');
    $table->string('phone');
    $table->string('otp');
    $table->boolean('is_verified')->default(false);

    // ⭐ OTP expire time
    $table->timestamp('otp_expires_at')->nullable();

    // ⭐ Verification valid till (EXPIRES)
    $table->timestamp('verified_expires_at')->nullable();

    $table->timestamps();

    $table->index(['phone', 'restaurant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('public_otp_verifications');
    }
};
