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
            Schema::create('work_order_items', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('work_order_id')->constrained('work_orders')->onDelete('cascade');
                    $table->enum('item_type', ['tech_req', 'task', 'material', 'method', 'machine'])->nullable();
                    $table->text('item_description')->nullable();
                    $table->string('unit')->nullable();

                    $table->timestamps();
                    $table->softDeletes();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_order_items');
    }
};
