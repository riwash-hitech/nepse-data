<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->enum('signal_type', ['BUY', 'SELL', 'HOLD']);
            $table->unsignedTinyInteger('confidence')->default(0)->comment('0-100');
            $table->json('reasons')->nullable()->comment('Array of reason strings');
            // Entry/Exit Generator
            $table->decimal('entry_min', 12, 2)->nullable();
            $table->decimal('entry_max', 12, 2)->nullable();
            $table->decimal('stop_loss', 12, 2)->nullable();
            $table->decimal('target_1', 12, 2)->nullable();
            $table->decimal('target_2', 12, 2)->nullable();
            $table->decimal('risk_reward', 8, 4)->nullable();
            // Snapshot of key indicators
            $table->decimal('price_at_signal', 12, 2)->nullable();
            $table->decimal('rsi_value', 8, 4)->nullable();
            $table->decimal('macd_value', 12, 4)->nullable();
            $table->bigInteger('volume_at_signal')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['stock_id', 'date', 'signal_type']);
            $table->index(['signal_type', 'confidence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signals');
    }
};
