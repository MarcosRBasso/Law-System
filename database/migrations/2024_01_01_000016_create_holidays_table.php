<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('date');
            $table->boolean('is_national')->default(false);
            $table->string('state', 2)->nullable();
            $table->string('city')->nullable();
            $table->timestamps();
            
            $table->index(['date', 'is_national']);
            $table->index(['state', 'date']);
            $table->unique(['date', 'state', 'city']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};