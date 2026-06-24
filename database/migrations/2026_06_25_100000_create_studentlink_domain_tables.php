<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professor_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('code', 32)->unique();
            $table->string('join_code', 12)->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('course_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['course_id', 'user_id']);
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });

        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('invite_code', 10)->unique();
            $table->timestamps();
        });

        Schema::create('group_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_leader')->default(false);
            $table->timestamps();

            $table->unique(['group_id', 'user_id']);
        });

        Schema::create('rubrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('rubric_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rubric_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->unsignedTinyInteger('weight')->default(1);
            $table->unsignedTinyInteger('max_score')->default(5);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique('group_id');
        });

        Schema::create('peer_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->string('type');
            $table->foreignId('reviewee_group_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->foreignId('reviewee_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('peer_evaluation_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peer_evaluation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rubric_criterion_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('score');
            $table->timestamps();

            $table->unique(['peer_evaluation_id', 'rubric_criterion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peer_evaluation_scores');
        Schema::dropIfExists('peer_evaluations');
        Schema::dropIfExists('submissions');
        Schema::dropIfExists('rubric_criteria');
        Schema::dropIfExists('rubrics');
        Schema::dropIfExists('group_user');
        Schema::dropIfExists('groups');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('course_user');
        Schema::dropIfExists('courses');
    }
};
