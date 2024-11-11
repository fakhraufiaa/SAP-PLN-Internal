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
        Schema::create('procurement_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_id')->constrained()->onDelete('restrict');
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->decimal('price', 10, 2)->nullable();
            $table->integer('quantity')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procurement_products');
    }
};
