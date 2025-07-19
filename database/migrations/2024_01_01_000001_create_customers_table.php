<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('persons')->onDelete('cascade');
            $table->string('rfc', 13)->nullable()->unique();
            $table->string('tax_id', 50)->nullable();
            $table->string('customer_group', 50)->default('general');
            $table->decimal('credit_limit', 10, 2)->default(0.00);
            $table->integer('points_balance')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_purchase_at')->nullable();
            $table->decimal('total_purchases', 12, 2)->default(0.00);
            $table->integer('total_orders')->default(0);
            
            // Campos auditables
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index(['rfc']);
            $table->index(['tax_id']);
            $table->index(['customer_group']);
            $table->index(['is_active']);
            $table->index(['last_purchase_at']);
            $table->index(['points_balance']);
            $table->index(['credit_limit']);
            
            // Índices compuestos
            $table->index(['customer_group', 'is_active']);
            $table->index(['is_active', 'last_purchase_at']);
            $table->index(['points_balance', 'is_active']);
            
            // Índice de búsqueda de texto
            $table->fullText(['rfc', 'tax_id'], 'customers_search');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
}; 