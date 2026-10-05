<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('identifier');
            $table->string('ip_address', 45)->nullable();
            $table->string('country', 120)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('outcome', 20);
            $table->timestamps();

            $table->index(['outcome', 'created_at']);
        });

        Schema::create('course_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('action', 40);
            $table->string('subject')->nullable();
            $table->timestamps();

            $table->index(['course_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_activities');
        Schema::dropIfExists('login_attempts');
    }
};
