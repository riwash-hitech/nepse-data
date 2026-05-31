<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_summaries', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->decimal('nepse_index', 10, 2)->default(0);
            $table->decimal('nepse_change', 10, 2)->default(0);
            $table->decimal('nepse_change_pct', 8, 4)->default(0);
            $table->decimal('sensitive_index', 10, 2)->nullable();
            $table->decimal('float_index', 10, 2)->nullable();
            $table->decimal('total_turnover', 18, 2)->default(0);
            $table->bigInteger('total_volume')->default(0);
            $table->integer('total_transactions')->default(0);
            $table->integer('scrip_traded')->default(0);
            $table->integer('positive_count')->default(0);
            $table->integer('negative_count')->default(0);
            $table->integer('unchanged_count')->default(0);
            $table->json('top_gainers')->nullable();
            $table->json('top_losers')->nullable();
            $table->json('most_active')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_summaries');
    }
};
