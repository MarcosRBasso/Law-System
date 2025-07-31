<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('start_date');
            $table->timestamp('end_date');
            $table->boolean('all_day')->default(false);
            $table->enum('type', ['hearing', 'deadline', 'appointment', 'reminder']);
            $table->foreignId('lawsuit_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->string('location')->nullable();
            $table->enum('status', ['scheduled', 'completed', 'cancelled', 'postponed'])->default('scheduled');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            $table->index(['start_date', 'status']);
            $table->index(['assigned_to', 'start_date']);
            $table->index('lawsuit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};