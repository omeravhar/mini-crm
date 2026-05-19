<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('lead_statuses')
            ->where('slug', 'lost')
            ->where('is_system', true)
            ->update([
                'name' => 'לא רלוונטי',
            ]);
    }

    public function down(): void
    {
        DB::table('lead_statuses')
            ->where('slug', 'lost')
            ->where('is_system', true)
            ->update([
                'name' => 'אבוד',
            ]);
    }
};
