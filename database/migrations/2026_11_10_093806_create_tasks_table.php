<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->foreignId('theme_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('exercise_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quiz_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->date('starts_at');
            $table->dateTime('due_at')->nullable();
            $table->string('priority', 20)->default('normal');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['due_at', 'priority']);
            $table->index('subject_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
