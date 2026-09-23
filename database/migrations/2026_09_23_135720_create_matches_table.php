<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->dateTime('played_at')->nullable();
            $table->string('mode')->nullable();
            $table->string('rule')->nullable();
            $table->string('stage')->nullable();
            $table->string('weapon')->nullable();
            $table->boolean('is_win')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'played_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
