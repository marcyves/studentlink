<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('deliverable_type')->default('none')->after('description');
        });

        Schema::table('submissions', function (Blueprint $table) {
            $table->string('url', 2048)->nullable()->after('status');
            $table->string('file_path')->nullable()->after('url');
            $table->string('original_name')->nullable()->after('file_path');
        });
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropColumn(['url', 'file_path', 'original_name']);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('deliverable_type');
        });
    }
};
