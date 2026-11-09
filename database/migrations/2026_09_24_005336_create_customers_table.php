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

            $table->string('code', 50)->unique();
            $table->string('name', 150);

            $table->string('customer_type', 30)
                ->default('company');

            $table->string('contact_person', 100)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('website', 150)->nullable();

            $table->string('tax_number', 100)->nullable();

            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('postal_code', 20)->nullable();

            $table->string('payment_term', 50)
                ->default('COD');

            $table->decimal('credit_limit', 18, 2)
                ->default(0);

            $table->text('notes')->nullable();

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();

            $table->index('name');
            $table->index('customer_type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
