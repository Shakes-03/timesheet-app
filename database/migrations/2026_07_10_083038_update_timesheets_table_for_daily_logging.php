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
            if (Schema::hasColumn('timesheets', 'week_ending_date')) {
                $table->dropColumn('week_ending_date');
            }
            if (Schema::hasColumn('timesheets', 'regular_hours')) {
                $table->dropColumn('regular_hours');
            }
            if (Schema::hasColumn('timesheets', 'overtime_hours')) {
                $table->dropColumn('overtime_hours');
            }

            // 2. Safely add new logging columns if they don't exist yet
            if (!Schema::hasColumn('timesheets', 'date')) {
                $table->date('date')->nullable(); 
            }
            if (!Schema::hasColumn('timesheets', 'normal_time_hours')) {
                $table->decimal('normal_time_hours', 5, 2)->default(0);
            }
            if (!Schema::hasColumn('timesheets', 'overtime_1_3_3_hours')) {
                $table->decimal('overtime_1_3_3_hours', 5, 2)->default(0);
            }
            if (!Schema::hasColumn('timesheets', 'overtime_1_5_hours')) {
                $table->decimal('overtime_1_5_hours', 5, 2)->default(0);
            }
            if (!Schema::hasColumn('timesheets', 'overtime_2_0_hours')) {
                $table->decimal('overtime_2_0_hours', 5, 2)->default(0);
            }
            if (!Schema::hasColumn('timesheets', 'overtime_2_5_hours')) {
                $table->decimal('overtime_2_5_hours', 5, 2)->default(0);
            }
            if (!Schema::hasColumn('timesheets', 'LOA_QTY')) {
                $table->decimal('LOA_QTY', 5, 2)->default(0);
            }
            if (!Schema::hasColumn('timesheets', 'pph_normal_time_hours')) {
                $table->decimal('pph_normal_time_hours', 5, 2)->default(0);
            }
            if (!Schema::hasColumn('timesheets', 'travelling_allowance')) {
                $table->decimal('travelling_allowance', 8, 2)->default(0);
            }

            // 3. Prevent duplicate logs
            if (!Schema::hasIndex('timesheets', ['employee_id', 'date'], 'unique')) {
                $table->unique(['employee_id', 'date']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timesheets', function (Blueprint $table) {
            if (Schema::hasIndex('timesheets', ['employee_id', 'date'], 'unique')) {
                $table->dropUnique(['employee_id', 'date']);
            }
            
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
