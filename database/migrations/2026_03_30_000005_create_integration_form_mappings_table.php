<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_form_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integration_id')->constrained()->cascadeOnDelete();
            $table->string('external_form_id');
            $table->string('external_form_name')->nullable();
            $table->foreignId('default_owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->json('field_map')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['integration_id', 'external_form_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_form_mappings');
    }
};
