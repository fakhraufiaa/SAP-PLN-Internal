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
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('amp_id');
            $table->string('no_wo')->unique();
            $table->string('no_surat')->nullable();
            $table->string('no_wbs')->nullable();
            $table->string('nama_penugasan');
            $table->string('kategori');
            $table->decimal('nilai_penugasan', 20, 2)->nullable();
            $table->date('tgl_penugasan')->nullable();
            $table->date('tgl_bts_penugasan')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
