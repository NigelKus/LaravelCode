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
        Schema::table('salesorder_detail', function (Blueprint $table) {
            $table->timestamps(); // Adds created_at and updated_at columns
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salesorder_detail', function (Blueprint $table) {
            $table->dropSoftDeletes(); // Removes deleted_at column
            $table->dropTimestamps();
        });
    }
};
