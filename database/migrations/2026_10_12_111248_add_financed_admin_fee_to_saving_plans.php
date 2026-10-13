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
        Schema::table('saving_plans', function (Blueprint $table) {
            $table->boolean('financed_admin_fee')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('saving_plans', function (Blueprint $table) {
            $table->dropColumn('financed_admin_fee');
        });
    }
};
