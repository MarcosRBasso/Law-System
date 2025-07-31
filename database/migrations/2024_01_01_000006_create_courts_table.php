<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->enum('type', ['federal', 'state', 'labor', 'electoral']);
            $table->string('city');
            $table->string('state', 2);
            $table->string('api_endpoint')->nullable();
            $table->timestamps();
            
            $table->index(['type', 'state']);
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courts');
    }
};