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
            $table->unsignedInteger('current_streak')->default(0)
                ->comment('The current streak of consecutive days the user has completed a daily task.');
            $table->unsignedInteger('longest_streak')->default(0)
                ->comment('The longest streak of consecutive days the user has completed a daily task.');
            $table->timestamp('last_studied_date')->nullable()->default(null)
                ->comment('The final date of the last time the user completed a daily task.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('current_streak');
            $table->dropColumn('longest_streak');
            $table->dropColumn('last_studied_date');
        });
    }
};
