<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->string('job_title')->nullable();
            $table->string('website')->nullable();
            $table->string('source')->nullable();
            $table->string('status')->default('new');
            $table->string('priority')->default('medium');
            $table->decimal('expected_value', 12, 2)->nullable();
            $table->date('follow_up')->nullable();
            $table->json('tags')->nullable();
            $table->string('street')->nullable();
            $table->string('zip')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->longText('notes')->nullable();
            $table->string('attachment_path')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('pipeline')->default('default');
            $table->string('stage')->default('lead');
            $table->string('visibility')->default('team');
            $table->timestamp('converted_to_customer_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'owner_id']);
            $table->index('follow_up');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
