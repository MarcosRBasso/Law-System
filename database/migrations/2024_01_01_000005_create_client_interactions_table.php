<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->enum('type', ['call', 'email', 'meeting', 'other']);
            $table->string('subject');
            $table->text('description');
            $table->timestamp('interaction_date');
            $table->integer('duration_minutes')->nullable();
            $table->timestamps();
            
            $table->index(['client_id', 'interaction_date']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_interactions');
    }
};