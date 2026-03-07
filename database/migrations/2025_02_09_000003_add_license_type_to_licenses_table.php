<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->enum('license_type', ['subscription', 'lifetime'])
                ->default('subscription')
                ->after('tool_slug')
                ->comment('Type of license: subscription (time-limited) or lifetime (no expiration)');
        });
    }

    public function down(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->dropColumn('license_type');
        });
    }
};
