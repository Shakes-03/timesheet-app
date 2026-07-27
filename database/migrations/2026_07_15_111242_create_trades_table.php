<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., Boilermaker, Planner
            $table->string('rate_type'); // 'industrial' or 'flat'
            

            $table->decimal('normal_rate_to_man', 10, 2);

            $table->decimal('flat_overtime_override', 10, 2)->nullable(); 
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};