<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('lawsuit_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->cascadeOnDelete();
            $table->text('description');
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->decimal('hourly_rate', 8, 2);
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->boolean('is_billable')->default(true);
            $table->boolean('is_billed')->default(false);
            $table->date('date');
            $table->timestamps();
            
            $table->index(['user_id', 'date']);
            $table->index(['lawsuit_id', 'is_billable']);
            $table->index(['is_billable', 'is_billed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};