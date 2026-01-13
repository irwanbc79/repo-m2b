<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customerUsers = User::where('role', 'customer')->get();

        $companies = [
            [
                'company_name' => 'PT. Maju Jaya Trading',
                'business_type' => 'Trading',
                'address' => 'Jl. Sudirman No. 123',
                'city' => 'Jakarta',
            ],
            [
                'company_name' => 'CV. Sukses Bersama Import',
                'business_type' => 'Import/Export',
                'address' => 'Jl. Thamrin No. 456',
                'city' => 'Surabaya',
            ],
            [
                'company_name' => 'UD. Sejahtera Abadi Logistics',
                'business_type' => 'Logistics',
                'address' => 'Jl. Gatot Subroto No. 789',
                'city' => 'Bandung',
            ],
        ];

        foreach ($customerUsers as $index => $user) {
            if (isset($companies[$index])) {
                Customer::create([
                    'user_id' => $user->id,
                    'customer_code' => Customer::generateCustomerCode(),
                    'company_name' => $companies[$index]['company_name'],
                    'business_type' => $companies[$index]['business_type'],
                    'address' => $companies[$index]['address'],
                    'city' => $companies[$index]['city'],
                    'country' => 'Indonesia',
                    'postal_code' => '12' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                    'credit_limit' => 50000000 + ($index * 10000000),
                    'payment_terms' => 30,
                ]);
            }
        }
    }
}
