<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installation_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->nullable()->constrained('licenses')->nullOnDelete();
            $table->string('tool_slug')->index();
            $table->string('install_domain')->nullable();
            $table->string('bd_base_url', 500)->nullable();
            $table->string('source', 20)->default('web')->comment('web or api');
            $table->boolean('success')->default(true);
            $table->json('details')->nullable()->comment('Widget results and other install details');
            $table->string('order_id')->nullable()->comment('Reserved for future order reference');
            $table->string('customer_id')->nullable()->comment('Reserved for future customer reference');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installation_histories');
    }
};
