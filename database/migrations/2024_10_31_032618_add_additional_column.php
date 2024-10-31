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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('user');
            $table->string('status')->default('active');
            $table->date('birth_date')->nullable();
            $table->string('birth_location')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'status', 'birth_date', 'birth_location', 'phone', 'address']);
            $table->dropSoftDeletes();
        });
    }
};
