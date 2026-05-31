<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            // Moving Averages
            $table->decimal('sma_20', 12, 4)->nullable();
            $table->decimal('sma_50', 12, 4)->nullable();
            $table->decimal('sma_200', 12, 4)->nullable();
            $table->decimal('ema_12', 12, 4)->nullable();
            $table->decimal('ema_26', 12, 4)->nullable();
            $table->decimal('ema_50', 12, 4)->nullable();
            // RSI
            $table->decimal('rsi_14', 8, 4)->nullable();
            // MACD
            $table->decimal('macd', 12, 4)->nullable();
            $table->decimal('macd_signal', 12, 4)->nullable();
            $table->decimal('macd_histogram', 12, 4)->nullable();
            // Bollinger Bands
            $table->decimal('bb_upper', 12, 4)->nullable();
            $table->decimal('bb_middle', 12, 4)->nullable();
            $table->decimal('bb_lower', 12, 4)->nullable();
            // ATR & Volatility
            $table->decimal('atr_14', 12, 4)->nullable();
            // Support & Resistance
            $table->decimal('support_1', 12, 2)->nullable();
            $table->decimal('support_2', 12, 2)->nullable();
            $table->decimal('resistance_1', 12, 2)->nullable();
            $table->decimal('resistance_2', 12, 2)->nullable();
            // Pivot Points
            $table->decimal('pivot', 12, 2)->nullable();
            $table->timestamps();

            $table->unique(['stock_id', 'date']);
            $table->index(['stock_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicators');
    }
};
