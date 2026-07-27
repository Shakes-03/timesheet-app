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
            if (Schema::hasColumn('employees', 'department')) {
                $table->dropColumn('department');
            }

            if (Schema::hasColumn('employees', 'trade_occupation')) {
                $table->dropColumn('trade_occupation');
            }
            if (!Schema::hasColumn('employees', 'trade_id')) {
                $table->foreignId('trade_id')
                    ->nullable()
                    ->constrained('trades')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('employees', 'id_number')) {
                $table->string('id_number', 13)->nullable();
            }

            if (!Schema::hasColumn('employees', 'start_date')) {
                $table->date('start_date')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'trade_id')) {
                $table->dropForeign(['trade_id']);
                $table->dropColumn('trade_id');
            }
            $table->dropColumn(['id_number', 'start_date']);
            $table->string('department')->nullable();
        });  
    }
};