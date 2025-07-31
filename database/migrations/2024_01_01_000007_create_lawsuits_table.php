<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lawsuits', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('responsible_lawyer_id')->constrained('users');
            $table->foreignId('court_id')->constrained();
            $table->string('subject');
            $table->text('description');
            $table->decimal('value', 15, 2)->nullable();
            $table->enum('status', ['active', 'suspended', 'finished', 'archived'])->default('active');
            $table->enum('phase', ['knowledge', 'execution', 'appeal', 'other'])->default('knowledge');
            $table->enum('instance', ['first', 'second', 'superior', 'supreme'])->default('first');
            $table->date('distribution_date');
            $table->date('estimated_end_date')->nullable();
            $table->date('actual_end_date')->nullable();
            $table->timestamps();
            
            $table->index(['client_id', 'status']);
            $table->index(['responsible_lawyer_id', 'status']);
            $table->index('status');
            $table->index('distribution_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lawsuits');
    }
};