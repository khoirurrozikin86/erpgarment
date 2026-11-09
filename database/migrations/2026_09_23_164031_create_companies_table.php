<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string('code', 50)->unique();
            $table->string('name', 150);

            // Branding
            $table->string('logo')->nullable();

            // Contact
            $table->string('email', 150)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('website', 255)->nullable();

            // Legal / Tax
            $table->string('tax_number', 100)->nullable();

            // Address
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('postal_code', 20)->nullable();

            // ERP Configuration
            $table->string('currency', 10)->default('IDR');
            $table->string('timezone', 50)->default('Asia/Jakarta');
            $table->string('date_format', 30)->default('d/m/Y');

            // Invoice Configuration
            $table->text('invoice_header')->nullable();
            $table->text('invoice_footer')->nullable();

            // Receipt Configuration
            $table->text('receipt_header')->nullable();
            $table->text('receipt_footer')->nullable();

            // Status
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Index
            $table->index('name');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
