<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_points_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->enum('type', ['earned', 'redeemed', 'expired', 'adjusted']);
            $table->integer('points');
            $table->decimal('amount', 10, 2)->nullable(); // Monto de la transacción que generó los puntos
            $table->string('currency', 3)->default('MXN');
            $table->string('description');
            $table->string('reference_type')->nullable(); // 'sale', 'manual', 'expiration'
            $table->unsignedBigInteger('reference_id')->nullable(); // ID de la venta o referencia
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_expired')->default(false);
            
            // Campos auditables
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Índices
            $table->index(['customer_id']);
            $table->index(['type']);
            $table->index(['expires_at']);
            $table->index(['is_expired']);
            $table->index(['reference_type', 'reference_id']);
            
            // Índices compuestos
            $table->index(['customer_id', 'type']);
            $table->index(['customer_id', 'is_expired']);
            $table->index(['type', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_points_history');
    }
}; 