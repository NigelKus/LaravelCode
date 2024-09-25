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
        Schema::table('mstr_customer', function (Blueprint $table) {
            $table->timestamp('timestamp')->nullable(); // Add a nullable timestamp column
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mstr_customer', function (Blueprint $table) {
            $table->dropColumn('timestamp');
        });
    }
};
