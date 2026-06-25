<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->json('allowed_email_domains')->nullable()->after('description');
        });

        Schema::create('professor_access_requests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('institution')->nullable();
            $table->text('message')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professor_access_requests');

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('allowed_email_domains');
        });
    }
};
