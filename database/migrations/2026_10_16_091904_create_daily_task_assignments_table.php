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
        Schema::create('daily_task_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            // 1 = 課題A, 2 = 課題B
            $table->unsignedTinyInteger('slot');
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->text('memo')->nullable();
            $table->timestamps();

            // 同じ日の同じスロットには1つしか課題を入れられない
            $table->unique(['user_id', 'date', 'slot']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_task_assignments');
    }
};
