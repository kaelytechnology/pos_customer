<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->enum('type', ['billing', 'shipping']);
            $table->string('street', 255);
            $table->string('street_number', 20)->nullable();
            $table->string('interior', 20)->nullable();
            $table->string('neighborhood', 100)->nullable();
            $table->string('city', 100);
            $table->string('state', 100);
            $table->string('postal_code', 10);
            $table->string('country', 3)->default('MX');
            $table->string('phone', 20)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_default')->default(false);
            
            // Campos auditables
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index(['customer_id']);
            $table->index(['type']);
            $table->index(['is_default']);
            $table->index(['city']);
            $table->index(['state']);
            $table->index(['postal_code']);
            $table->index(['country']);
            
            // Índices compuestos
            $table->index(['customer_id', 'type']);
            $table->index(['customer_id', 'is_default']);
            $table->index(['type', 'is_default']);
            
            // Índice único para dirección por defecto por cliente y tipo
            $table->unique(['customer_id', 'type', 'is_default'], 'unique_default_address');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
}; 