Ah, I see! Your current timesheet migration is designed around a weekly setup (using columns like week_ending_date, regular_hours, and overtime_hours), but your Filament form is trying to submit a daily timesheet record with specific overtime tiers (overtime_1_3_3_hours, overtime_1_5_hours, etc.) and allowances (LOA_QTY, travelling_allowance).

If you want your app to support this daily format and accommodate all the fields your form is currently sending, we need to update this migration to match.

The Updated Migration File
Replace your current migration with this schema to align with all the inputs from your form:

PHP
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
        Schema::create('timesheets', function (Blueprint $table) {
            $table->id();
            
            //links the timesheet directly to a specific employee
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete(); 
            
            // Daily timesheet date (replaces week_ending_date)
            $table->date('date');
            
            // Hours tracking
            $table->decimal('normal_time_hours', 5, 2)->default(0.00);
            $table->decimal('overtime_1_3_3_hours', 5, 2)->default(0.00);
            $table->decimal('overtime_1_5_hours', 5, 2)->default(0.00);
            $table->decimal('overtime_2_0_hours', 5, 2)->default(0.00);
            $table->decimal('overtime_2_5_hours', 5, 2)->default(0.00);
            $table->integer('LOA_QTY')->default(0); 
            $table->decimal('travelling_allowance', 8, 2)->default(0.00);
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timesheets');
    }
};