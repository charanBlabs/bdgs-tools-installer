<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tool_server_assets', function (Blueprint $table) {
            $table->id();
            $table->string('tool_slug', 64)->index();
            $table->string('file_name', 255)->comment('Logical name e.g. faq-render.html; use __base_url__ for custom_base_url row');
            $table->string('storage_path', 500)->nullable()->comment('Path under storage/app or disk');
            $table->string('custom_base_url', 500)->nullable()->comment('Only set when file_name = __base_url__');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tool_server_assets');
    }
};
