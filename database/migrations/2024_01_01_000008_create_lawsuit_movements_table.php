<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lawsuit_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lawsuit_id')->constrained()->cascadeOnDelete();
            $table->date('movement_date');
            $table->text('description');
            $table->string('type')->nullable();
            $table->enum('source', ['manual', 'pje', 'eproc', 'saj'])->default('manual');
            $table->string('external_id')->nullable();
            $table->timestamps();
            
            $table->index(['lawsuit_id', 'movement_date']);
            $table->index('source');
            $table->unique(['lawsuit_id', 'external_id', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lawsuit_movements');
    }
};