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
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
    $table->foreignId('category_id')
          ->constrained('categories')
          ->onDelete('cascade');
    $table->foreignId('restaurant_id')
                  ->constrained('restaurants')
                  ->onDelete('cascade');
    $table->string('name'); // Dish name
    $table->text('description')->nullable();
    $table->decimal('price', 8, 2);
    $table->string('image')->nullable();

    // Veg / Non-Veg / Egg
    $table->enum('type', ['veg', 'non_veg', 'egg'])->default('veg');

    $table->boolean('is_available')->default(true);
    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_itmes');
    }
};
