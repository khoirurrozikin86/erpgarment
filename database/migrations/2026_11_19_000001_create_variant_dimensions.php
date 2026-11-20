<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variant_dimensions', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 8);
            $table->string('code', 80);
            $table->string('name', 120)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignId('canonical_id')->nullable()->constrained('variant_dimensions')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['kind', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variant_dimensions');
    }
};
