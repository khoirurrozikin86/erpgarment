<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoice_items', function (Blueprint $table) {
            $table->decimal('returned_quantity', 18, 4)->default(0)->after('quantity');
        });

        Schema::create('customer_returns', function (Blueprint $table) {
            $table->id();
            $table->string('number', 100)->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_invoice_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('cash_bank_account_id')->constrained()->restrictOnDelete();
            $table->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('returned_at');
            $table->text('reason');
            $table->text('notes')->nullable();
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->timestamps();
            $table->index(['company_id', 'returned_at']);
        });

        Schema::create('customer_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_invoice_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 18, 4);
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('tax_rate', 8, 2)->default(0);
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('line_total', 18, 2)->default(0);
            $table->decimal('cost_amount', 18, 2)->default(0);
            $table->timestamps();
            $table->index('sales_invoice_item_id');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('customer_return_id')->nullable()->after('purchase_return_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_return_item_id')->nullable()->after('customer_return_id')->constrained()->restrictOnDelete();
        });

        Schema::table('cash_bank_transactions', function (Blueprint $table) {
            $table->foreignId('customer_return_id')->nullable()->unique()->after('supplier_payment_id')->constrained()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cash_bank_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_return_id');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_return_item_id');
            $table->dropConstrainedForeignId('customer_return_id');
        });

        Schema::dropIfExists('customer_return_items');
        Schema::dropIfExists('customer_returns');

        Schema::table('sales_invoice_items', function (Blueprint $table) {
            $table->dropColumn('returned_quantity');
        });
    }
};
