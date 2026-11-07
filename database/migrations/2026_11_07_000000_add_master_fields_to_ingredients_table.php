<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->string('category')->nullable();
            $table->decimal('package_quantity', 12, 3)->nullable();
            $table->decimal('het_price', 12, 2)->nullable();
            $table->decimal('hpp_price', 12, 2)->nullable();
            $table->string('supplier')->nullable();
            $table->string('allocation')->nullable();
            $table->string('purchase_unit')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropColumn([
                'category',
                'package_quantity',
                'het_price',
                'hpp_price',
                'supplier',
                'allocation',
                'purchase_unit',
            ]);
        });
    }
};
