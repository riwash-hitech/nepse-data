<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 20)->unique();
            $table->string('name');
            $table->foreignId('sector_id')->nullable()->constrained()->nullOnDelete();
            $table->string('isin', 20)->nullable();
            $table->bigInteger('listed_shares')->default(0);
            $table->decimal('face_value', 12, 2)->default(100);
            $table->decimal('paid_up_capital', 18, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_bonus_share')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['symbol', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
