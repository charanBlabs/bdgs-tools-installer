<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tool_documentation', function (Blueprint $table) {
            $table->id();
            $table->string('tool_slug', 64)->unique()->comment('Matches config tools.registry key');
            $table->string('image_url', 500)->nullable()->comment('Main hero image');
            $table->json('screenshots')->nullable()->comment('Array of screenshot URLs for carousel');
            $table->string('installation_type', 32)->default('quick_install')->comment('quick_install | installer_plus_manual | manual_only');
            $table->text('installation_steps')->nullable()->comment('Step-by-step install guide (markdown or plain)');
            $table->text('manual_steps')->nullable()->comment('Post-install manual steps if installer_plus_manual');
            $table->json('features')->nullable()->comment('List of feature descriptions');
            $table->text('ui_description')->nullable()->comment('UI overview for developers');
            $table->text('feature_notes')->nullable()->comment('Future feature ideas / notes for new versions');
            $table->json('files_used')->nullable()->comment('List of files (widget assets, etc.)');
            $table->text('version_notes')->nullable()->comment('Version history / changelog notes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tool_documentation');
    }
};
