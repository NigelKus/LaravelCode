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
            $table->timestamps(); // Adds 'created_at' and 'updated_at' columns
            $table->softDeletes(); // Adds 'deleted_at' column for soft deletes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mstr_customer', function (Blueprint $table) {
            $table->dropSoftDeletes(); // Drops 'deleted_at' column
            $table->dropTimestamps(); // Drops 'created_at' and 'updated_at' columns
        });
    }
};
