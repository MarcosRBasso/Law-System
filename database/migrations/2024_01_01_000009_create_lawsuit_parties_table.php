<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lawsuit_parties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lawsuit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained();
            $table->enum('type', ['plaintiff', 'defendant', 'third_party']);
            $table->timestamps();
            
            $table->index('lawsuit_id');
            $table->unique(['lawsuit_id', 'client_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lawsuit_parties');
    }
};