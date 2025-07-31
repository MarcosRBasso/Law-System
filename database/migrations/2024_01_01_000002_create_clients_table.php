<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['individual', 'company']);
            $table->string('name');
            $table->string('document')->unique(); // CPF/CNPJ
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            $table->string('zip_code', 10)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('profession')->nullable();
            $table->string('marital_status')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            $table->index(['type', 'is_active']);
            $table->index('document');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};