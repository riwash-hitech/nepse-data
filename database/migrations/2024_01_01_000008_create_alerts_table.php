<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_id')->constrained()->cascadeOnDelete();
            $table->enum('condition_type', ['price_above', 'price_below', 'rsi_above', 'rsi_below', 'signal_buy', 'signal_sell', 'volume_spike']);
            $table->decimal('condition_value', 12, 4)->nullable();
            $table->json('notification_channels')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_triggered')->default(false);
            $table->timestamp('triggered_at')->nullable();
            $table->string('telegram_chat_id')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->index(['stock_id', 'condition_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
