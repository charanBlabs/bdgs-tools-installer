<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encrypted_tools', function (Blueprint $table) {
            $table->id();
            $table->string('tool_slug')->unique();
            $table->longText('encrypted_payload');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encrypted_tools');
    }
};
