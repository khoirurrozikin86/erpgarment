<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pos_sales')) {
            Schema::create('pos_sales', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->string('number', 100);
                $table->foreignId('pos_session_id')->constrained()->restrictOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained()->restrictOnDelete();
                $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('cash_bank_account_id')->constrained()->restrictOnDelete();
                $table->dateTime('sold_at');
                $table->decimal('subtotal', 18, 2)->default(0);
                $table->decimal('discount_rate', 6, 2)->default(0);
                $table->decimal('discount_amount', 18, 2)->default(0);
                $table->decimal('tax_amount', 18, 2)->default(0);
                $table->decimal('total_amount', 18, 2)->default(0);
                $table->enum('payment_method', ['cash', 'bank_transfer', 'card']);
                $table->decimal('paid_amount', 18, 2)->default(0);
                $table->decimal('change_amount', 18, 2)->default(0);
                $table->enum('status', ['completed', 'returned'])->default('completed')->index();
                $table->timestamps();
                $table->unique(['company_id', 'number']);
                $table->index(['company_id', 'sold_at']);
            });
        }

        if (!Schema::hasTable('pos_sale_items')) {
            Schema::create('pos_sale_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pos_sale_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->restrictOnDelete();
                $table->decimal('quantity', 18, 4);
                $table->decimal('unit_price', 18, 2);
                $table->decimal('discount_amount', 18, 2)->default(0);
                $table->decimal('tax_rate', 8, 2)->default(0);
                $table->decimal('tax_amount', 18, 2)->default(0);
                $table->decimal('line_total', 18, 2);
                $table->decimal('cost_amount', 18, 2)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasColumn('cash_bank_transactions', 'pos_sale_id')) {
            Schema::table('cash_bank_transactions', function (Blueprint $table) {
                $table->foreignId('pos_sale_id')->nullable()->unique()->after('pos_session_id')->constrained('pos_sales')->restrictOnDelete();
            });
        }
        if (!Schema::hasColumn('stock_movements', 'pos_sale_id')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->foreignId('pos_sale_id')->nullable()->after('delivery_id')->constrained('pos_sales')->restrictOnDelete();
                $table->foreignId('pos_sale_item_id')->nullable()->after('pos_sale_id')->constrained('pos_sale_items')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('stock_movements', 'pos_sale_item_id')) {
            Schema::table('stock_movements', fn (Blueprint $table) => $table->dropConstrainedForeignId('pos_sale_item_id'));
        }
        if (Schema::hasColumn('stock_movements', 'pos_sale_id')) {
            Schema::table('stock_movements', fn (Blueprint $table) => $table->dropConstrainedForeignId('pos_sale_id'));
        }
        if (Schema::hasColumn('cash_bank_transactions', 'pos_sale_id')) {
            Schema::table('cash_bank_transactions', fn (Blueprint $table) => $table->dropConstrainedForeignId('pos_sale_id'));
        }
        Schema::dropIfExists('pos_sale_items');
        Schema::dropIfExists('pos_sales');
    }
};
