<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();
            $table->string('store_name')->default('My Store');
            $table->string('address')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('currency', 8)->default('IDR');
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->string('invoice_prefix', 16)->default('INV');
            $table->string('receipt_footer')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_settings');
    }
};
