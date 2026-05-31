<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->decimal('open', 12, 2)->default(0);
            $table->decimal('high', 12, 2)->default(0);
            $table->decimal('low', 12, 2)->default(0);
            $table->decimal('close', 12, 2)->default(0);
            $table->decimal('previous_close', 12, 2)->default(0);
            $table->bigInteger('volume')->default(0);
            $table->decimal('turnover', 18, 2)->default(0);
            $table->integer('transactions')->default(0);
            $table->decimal('change', 10, 2)->default(0);
            $table->decimal('change_percent', 8, 4)->default(0);
            $table->decimal('vwap', 12, 2)->nullable()->comment('Volume Weighted Average Price');
            $table->timestamps();

            $table->unique(['stock_id', 'date']);
            $table->index(['stock_id', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_prices');
    }
};
