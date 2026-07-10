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
        // This links the timesheet directly to a specific employee
        $table->foreignId('employee_id')->constrained()->cascadeOnDelete(); 
        $table->date('week_ending_date');
        $table->decimal('regular_hours', 5, 2)->default(0.00);
        $table->decimal('overtime_hours', 5, 2)->default(0.00);
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
