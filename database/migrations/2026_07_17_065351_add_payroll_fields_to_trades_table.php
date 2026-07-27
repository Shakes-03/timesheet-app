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
        Schema::table('trades', function (Blueprint $table) {
            $table->decimal('leave_rate', 10, 2)->default(0);
            $table->decimal('enhancement_bonus', 10, 2)->default(0);
            $table->decimal('total_cc', 10, 2)->default(0);
            $table->decimal('ctc', 10, 2)->default(0);
            $table->decimal('admin_fee', 10, 2)->default(0);
            
            // Add the rate multipliers we discussed earlier
            $table->decimal('rate_1_33', 10, 2)->default(0);
            $table->decimal('rate_1_5', 10, 2)->default(0);
            $table->decimal('rate_2_0', 10, 2)->default(0);
            $table->decimal('rate_2_5', 10, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trades', function (Blueprint $table) {
            $table->dropColumn([
                'leave_rate',
                'enhancement_bonus',
                'total_cc',
                'ctc',
                'admin_fee',
                'rate_1_33',
                'rate_1_5',
                'rate_2_0',
                'rate_2_5',
            ]);
        });
    }
};
