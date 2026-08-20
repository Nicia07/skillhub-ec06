<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('inscriptions', 'last_activity_at')) {
                $table->dateTime('last_activity_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('inscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('inscriptions', 'last_activity_at')) {
                $table->dropColumn('last_activity_at');
            }
        });
    }
};
