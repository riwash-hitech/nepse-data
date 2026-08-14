<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['buy', 'sell']);
            $table->unsignedInteger('quantity');
            $table->decimal('rate', 12, 4);
            $table->date('txn_date');
            $table->decimal('realized_gain', 12, 2)->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'stock_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_transactions');
    }
};
