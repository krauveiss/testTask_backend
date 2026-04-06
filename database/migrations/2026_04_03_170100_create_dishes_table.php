<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dishes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('search_name')->index();
            $table->json('photos')->nullable();
            $table->decimal('calories', 8, 2);
            $table->decimal('proteins', 8, 2);
            $table->decimal('fats', 8, 2);
            $table->decimal('carbohydrates', 8, 2);
            $table->decimal('portion_size', 8, 2);
            $table->string('category', 32);
            $table->boolean('is_vegan')->default(false);
            $table->boolean('is_gluten_free')->default(false);
            $table->boolean('is_sugar_free')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dishes');
    }
};
