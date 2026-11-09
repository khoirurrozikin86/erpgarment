<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            /*
             * Identitas barang
             */
            $table->string('code', 50)->unique();
            $table->string('barcode', 100)->nullable()->unique();
            $table->string('name', 150);

            /*
             * Relasi Master Data
             *
             * Untuk sementara nullable karena
             * Category dan Unit belum kita buat.
             */
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('unit_id')->nullable();

            /*
             * stock     = barang yang mempunyai persediaan
             * service   = jasa / non-stock
             */
            $table->enum('product_type', [
                'stock',
                'service',
            ])->default('stock');

            /*
             * Harga default.
             *
             * Harga transaksi nantinya tidak selalu
             * mengambil langsung dari field ini.
             */
            $table->decimal('purchase_price', 18, 2)
                ->default(0);

            $table->decimal('sales_price', 18, 2)
                ->default(0);

            /*
             * Minimum / Maximum Stock
             */
            $table->decimal('min_stock', 18, 4)
                ->default(0);

            $table->decimal('max_stock', 18, 4)
                ->default(0);

            /*
             * Pajak
             */
            $table->boolean('taxable')
                ->default(false);

            /*
             * Keterangan
             */
            $table->text('description')->nullable();

            /*
             * Status
             */
            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();

            /*
             * Index untuk pencarian
             */
            $table->index('name');
            $table->index('category_id');
            $table->index('unit_id');
            $table->index('product_type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
