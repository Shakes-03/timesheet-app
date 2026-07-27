To fix the error where your database is missing the description column, replace the content of your migration file with the code below. This will add both the description column and ensure your name column is set to be nullable, resolving your previous errors.

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
        Schema::table('trades', function (Blueprint $table) {
            $table->text('description')->nullable();
            $table->string('name')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trades', function (Blueprint $table) {
            $table->dropColumn('description');
            $table->string('name')->nullable(false)->change();
        });
    }
};
