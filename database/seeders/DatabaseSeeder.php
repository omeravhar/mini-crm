<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'CRM Admin',
                'role' => 'admin',
                'password' => Hash::make('password'),
            ]
        );

        $sales = User::updateOrCreate(
            ['email' => 'sales@example.com'],
            [
                'name' => 'Sales Rep',
                'role' => 'editor',
                'password' => Hash::make('password'),
            ]
        );

        $lead = Lead::updateOrCreate(
            ['email' => 'lead@example.com'],
            [
                'first_name' => 'Dana',
                'last_name' => 'Levi',
                'phone' => '+972-50-111-2233',
                'company' => 'Northwind',
                'job_title' => 'Operations Manager',
                'source' => 'website',
                'status' => 'contacted',
                'priority' => 'high',
                'expected_value' => 12000,
                'owner_id' => $sales->id,
                'created_by' => $admin->id,
                'pipeline' => 'default',
                'stage' => 'mql',
                'visibility' => 'team',
            ]
        );

        Customer::updateOrCreate(
            ['email' => 'customer@example.com'],
            [
                'lead_id' => $lead->id,
                'owner_id' => $sales->id,
                'first_name' => 'Yoav',
                'last_name' => 'Cohen',
                'phone' => '+972-50-987-6543',
                'company' => 'Acme Ltd',
                'job_title' => 'CEO',
                'website' => 'https://example.com',
                'city' => 'Tel Aviv',
                'country' => 'Israel',
                'notes' => 'Imported sample customer',
            ]
        );
    }
}
