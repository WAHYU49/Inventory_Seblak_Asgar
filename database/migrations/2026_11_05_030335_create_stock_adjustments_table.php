<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();

            $table->string('adjustment_number')->unique();

            $table->foreignId('ingredient_id')
                ->constrained('ingredients')
                ->restrictOnDelete();

            $table->string('type');
            // increase atau decrease

            $table->decimal('quantity', 12, 3);

            $table->decimal('stock_before', 12, 3);

            $table->decimal('stock_after', 12, 3);

            $table->string('reason');

            $table->text('notes')->nullable();

            $table->date('adjustment_date');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
