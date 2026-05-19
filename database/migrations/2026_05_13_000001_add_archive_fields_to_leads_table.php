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
            $table->timestamp('archived_at')->nullable()->after('closed_at');
            $table->foreignId('archived_by')->nullable()->after('archived_at')->constrained('users')->nullOnDelete();
            $table->text('archive_reason')->nullable()->after('archived_by');
            $table->index('archived_at');
        });

        DB::table('leads')
            ->whereNull('archived_at')
            ->where(function ($query) {
                $query
                    ->whereNotNull('closed_at')
                    ->orWhereIn('status', ['won', 'lost']);
            })
            ->update([
                'archived_at' => DB::raw('COALESCE(closed_at, updated_at)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['archived_at']);
            $table->dropConstrainedForeignId('archived_by');
            $table->dropColumn(['archived_at', 'archive_reason']);
        });
    }
};
