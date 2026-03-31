<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->timestamp('closed_at')->nullable()->after('converted_to_customer_at');
        });

        DB::table('leads')
            ->whereIn('status', ['won', 'lost'])
            ->whereNull('closed_at')
            ->update([
                'closed_at' => DB::raw('updated_at'),
            ]);
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('closed_at');
        });
    }
};
