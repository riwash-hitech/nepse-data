<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_holdings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(0);
            $table->decimal('avg_cost', 12, 4)->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'stock_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_holdings');
    }
};
