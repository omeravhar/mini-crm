<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('platform')->index();
            $table->string('status')->default('draft')->index();
            $table->uuid('webhook_key')->unique();
            $table->string('external_account_id')->nullable();
            $table->string('external_page_id')->nullable();
            $table->longText('access_token')->nullable();
            $table->longText('refresh_token')->nullable();
            $table->string('verify_token')->nullable();
            $table->longText('webhook_secret')->nullable();
            $table->json('config')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('last_webhook_at')->nullable();
            $table->timestamp('last_error_at')->nullable();
            $table->text('last_error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integrations');
    }
};
