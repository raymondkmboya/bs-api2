<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->date('follow_up_date');
            $table->enum('medium_used', ['phone', 'email', 'sms', 'whatsapp', 'in_person', 'social_media']);
            $table->text('message_content')->nullable();
            $table->date('next_follow_up_date')->nullable();
            $table->enum('status', ['pending', 'contacted', 'interested', 'not_interested', 'enrolled', 'stop_follow_up']);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['student_id', 'status']);
            $table->index('next_follow_up_date');
            $table->index('follow_up_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_follow_ups');
    }
};
