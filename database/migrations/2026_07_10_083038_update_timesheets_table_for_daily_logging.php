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
        $table->dropColumn(['week_ending_date', 'regular_hours', 'overtime_hours']);

        $table->date('date');
        $table->decimal('normal_time_hours', 5, 2)->default(0);
        $table->decimal('overtime_1_3_3_hours', 5, 2)->default(0);
        $table->decimal('overtime_1_5_hours', 5, 2)->default(0);
        $table->decimal('overtime_2_0_hours', 5, 2)->default(0);
        $table->decimal('overtime_2_5_hours', 5, 2)->default(0);
        $table->decimal('LOA_QTY', 5, 2)->default(0);
        $table->decimal('pph_normal_time_hours', 5, 2)->default(0);
        $table->decimal('travelling_allowance', 8, 2)->default(0);
        $table->unique(['employee_id', 'date']);
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('timesheets', function (Blueprint $table) {
        $table->dropUnique(['employee_id', 'date']);
        $table->dropColumn([
            'date', 'normal_time_hours', 'overtime_1_3_3_hours', 'overtime_1_5_hours', 'overtime_2_0_hours', 'overtime_2_5_hours', 'LOA_QTY',
            'pph_normal_time_hours', 'travelling_allowance'
        ]);
        
        $table->string('week_ending_date')->nullable();
        $table->decimal('regular_hours', 5, 2)->default(0);
        $table->decimal('overtime_hours', 5, 2)->default(0);
    }); 
    }
};
