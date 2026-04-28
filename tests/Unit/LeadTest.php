<?php

namespace Tests\Unit;

use App\Models\Lead;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LeadTest extends TestCase
{
    public function test_formatted_entry_at_uses_jerusalem_timezone(): void
    {
        $lead = new Lead([
            'received_at' => Carbon::parse('2026-04-15 08:30:00', 'UTC'),
        ]);

        $this->assertSame('2026-04-15 11:30', $lead->formatted_entry_at);
    }
}
