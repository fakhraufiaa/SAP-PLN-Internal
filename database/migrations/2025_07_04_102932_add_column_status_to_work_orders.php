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
        Schema::table('work_orders', function (Blueprint $table) {
            $table->string('status')->default('draft')->after('tgl_bts_penugasan');
            $table->timestamp('status_at')->nullable()->after('status');
            $table->index(['status', 'status_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropIndex(['status', 'status_at']);
            $table->dropColumn(['status', 'status_at']);
        });
    }
};
