<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('source_platform')->nullable()->after('source');
            $table->string('source_channel')->nullable()->after('source_platform');
            $table->string('external_lead_id')->nullable()->after('source_channel');
            $table->string('external_form_id')->nullable()->after('external_lead_id');
            $table->string('external_campaign_id')->nullable()->after('external_form_id');
            $table->string('external_ad_id')->nullable()->after('external_campaign_id');
            $table->json('raw_payload')->nullable()->after('attachment_path');
            $table->timestamp('received_at')->nullable()->after('closed_at');

            $table->index(['source_platform', 'external_lead_id']);
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['source_platform', 'external_lead_id']);
            $table->dropColumn([
                'source_platform',
                'source_channel',
                'external_lead_id',
                'external_form_id',
                'external_campaign_id',
                'external_ad_id',
                'raw_payload',
                'received_at',
            ]);
        });
    }
};
