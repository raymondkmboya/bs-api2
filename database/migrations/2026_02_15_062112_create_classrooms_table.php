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
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('room_number')->unique();
            $table->integer('capacity')->default(30);
            $table->string('building')->nullable();
            $table->string('floor')->nullable();
            $table->enum('classroom_type', ['lecture', 'lab', 'computer', 'art', 'music', 'general'])->default('general');
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            $table->text('description')->nullable();
            $table->json('facilities')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classrooms');
    }
};
