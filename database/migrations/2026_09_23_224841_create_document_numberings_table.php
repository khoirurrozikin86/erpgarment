<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_numberings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            $table->string('document_type', 50);
            $table->string('prefix', 20);

            $table->string('format', 100)
                ->default('{PREFIX}/{YYYY}/{MM}/{NUMBER}');

            $table->unsignedInteger('next_number')
                ->default(1);

            $table->unsignedTinyInteger('number_length')
                ->default(5);



            $table->enum('reset_period', [
                'never',
                'yearly',
                'monthly',
            ])->default('monthly');

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();

            $table->index('document_type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_numberings');
    }
};
