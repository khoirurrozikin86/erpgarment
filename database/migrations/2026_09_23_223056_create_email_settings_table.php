<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_settings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->unique()
                ->constrained('companies')
                ->cascadeOnDelete();

            $table->string('mail_mailer', 50)->default('smtp');
            $table->string('mail_host', 150)->nullable();
            $table->unsignedSmallInteger('mail_port')->default(587);
            $table->string('mail_username', 150)->nullable();
            $table->text('mail_password')->nullable();
            $table->string('mail_encryption', 20)->nullable();

            $table->string('from_name', 150)->nullable();
            $table->string('from_email', 150)->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_settings');
    }
};
