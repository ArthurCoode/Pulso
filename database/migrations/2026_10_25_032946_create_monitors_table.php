<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url');
            $table->string('method', 10)->default('GET');
            $table->unsignedSmallInteger('expected_status')->default(200);
            $table->unsignedSmallInteger('timeout')->default(10);
            $table->unsignedSmallInteger('interval_minutes')->default(1);
            $table->string('keyword')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('status', 10)->default('unknown');
            $table->unsignedSmallInteger('consecutive_failures')->default(0);
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('next_check_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitors');
    }
};