<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_annotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_file_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 30)->default('overlay');
            $table->json('data')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index('submission_file_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_annotations');
    }
};
