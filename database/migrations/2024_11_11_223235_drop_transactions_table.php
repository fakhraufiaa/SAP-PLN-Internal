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
        Schema::dropIfExists('transactions');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained();
            $table->enum('transaction_type', ['in', 'out']);
            $table->integer('quantity');
            $table->timestamp('transaction_date');
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
