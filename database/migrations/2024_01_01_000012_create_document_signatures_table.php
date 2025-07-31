<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('signer_name');
            $table->string('signer_email');
            $table->string('signer_document');
            $table->timestamp('signature_date')->nullable();
            $table->json('certificate_info')->nullable();
            $table->enum('signature_status', ['pending', 'signed', 'rejected'])->default('pending');
            $table->timestamps();
            
            $table->index('document_id');
            $table->index('signature_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_signatures');
    }
};