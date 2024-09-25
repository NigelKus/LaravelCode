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
        Schema::table('invoice_sales', function (Blueprint $table) {
            $table->timestamps(); // Adds created_at and updated_at columns
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_sales', function (Blueprint $table) {
            $table->dropTimestamps(); // Drops created_at and updated_at columns
            $table->dropSoftDeletes();
        });
    }
};
