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
        Schema::table('employees', function (Blueprint $table) {
        // Drop the old department column safely if it exists
        if (Schema::hasColumn('employees', 'department')) {
            $table->dropColumn('department');
            }

            $table->string('trade_occupation')->nullable();
        $table->string('id_number', 13)->nullable();
        $table->date('start_date')->nullable();
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
      Schema::table('employees', function (Blueprint $table) {
        $table->dropColumn(['trade_occupation', 'id_number', 'start_date']);
        $table->string('department')->nullable();
    });  
    }
};
