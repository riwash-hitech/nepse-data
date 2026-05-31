<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('floorsheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('contract_id', 30)->nullable();
            $table->string('buyer_broker', 10);
            $table->string('seller_broker', 10);
            $table->bigInteger('quantity');
            $table->decimal('rate', 12, 2);
            $table->decimal('amount', 18, 2);
            $table->timestamps();

            $table->index(['stock_id', 'date']);
            $table->index(['date', 'buyer_broker']);
            $table->index(['date', 'seller_broker']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('floorsheets');
    }
};
