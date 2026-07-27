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
        Schema::table('timesheets', function (Blueprint $table) {
            if (!Schema::hasColumn('timesheets', 'travelling_allowance')) {
                $table->decimal('travelling_allowance', 8, 2)->default(0);
            }

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timesheets', function (Blueprint $table) {
            if (Schema::hasColumn('timesheets', 'travelling_allowance')) {
                $table->dropColumn('travelling_allowance');
            }
        });
    }
};