<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone');
            $table->enum('level_interested', ['QT', 'CSEE', 'ASCEE', 'English Course', 'ECDE', 'Pre Form One']);
            $table->enum('source', ['phone_call', 'walk_in', 'whatsapp', 'facebook', 'email', 'website']);
            $table->enum('status', ['new', 'contacted', 'followed_up', 'converted', 'lost'])->default('new');
            $table->text('message')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('status');
            $table->index('source');
            $table->index('level_interested');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_enquiries');
    }
};
