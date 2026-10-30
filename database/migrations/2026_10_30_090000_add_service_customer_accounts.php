<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('fiscal_code', 50)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'name']);
        });

        Schema::create('service_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_customer_id')->constrained('service_customers')->restrictOnDelete();
            $table->string('number', 100)->unique();
            $table->decimal('amount', 12, 2);
            $table->decimal('orders_total', 12, 2);
            $table->date('issued_on')->index();
            $table->timestamp('recorded_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_fiscal_code', 50)->nullable();
            $table->timestamps();
        });

        Schema::table('service_orders', function (Blueprint $table) {
            $table->foreignId('service_customer_id')->nullable()->constrained('service_customers')->restrictOnDelete();
            $table->foreignId('service_invoice_id')->nullable()->constrained('service_invoices')->restrictOnDelete();
            $table->index(['payment_method', 'status', 'service_invoice_id'], 'service_orders_uninvoiced_index');
            $table->index('completed_at');
        });

        Schema::table('service_orders', function (Blueprint $table) {
            $table->enum('payment_method', ['cash', 'card', 'mixed', 'protocol', 'client_account'])->nullable()->change();
        });
    }

    public function down(): void
    {
        // Never silently discard operational receivables or recorded invoices.
        if (DB::table('service_orders')->where('payment_method', 'client_account')->exists()
            || DB::table('service_invoices')->exists()
            || DB::table('service_customers')->exists()) {
            throw new RuntimeException('Rollback oprit: există clienți, fișe pe cont sau facturi. Păstrați evidența și folosiți o migrare corectivă.');
        }

        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropIndex('service_orders_uninvoiced_index');
            $table->dropIndex(['completed_at']);
            $table->dropConstrainedForeignId('service_invoice_id');
            $table->dropConstrainedForeignId('service_customer_id');
            $table->enum('payment_method', ['cash', 'card', 'mixed', 'protocol'])->nullable()->change();
        });
        Schema::dropIfExists('service_invoices');
        Schema::dropIfExists('service_customers');
    }
};
