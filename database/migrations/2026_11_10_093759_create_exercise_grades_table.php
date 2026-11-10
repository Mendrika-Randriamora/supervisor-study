<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercise_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('exercise_submissions')->cascadeOnDelete();
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('score', 5, 2);
            $table->decimal('max_points', 5, 2);
            $table->text('comment')->nullable();
            $table->timestamp('graded_at');
            $table->timestamps();

            $table->index(['submission_id', 'graded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercise_grades');
    }
};
