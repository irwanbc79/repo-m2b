<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Shipment;
use App\Models\ShipmentStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

class ShipmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = Customer::all();
        $adminUser = User::where('role', 'admin')->first();

        $shipmentData = [
            [
                'awb_number' => 'AWB' . date('Y') . '001',
                'shipment_type' => 'air',
                'service_type' => 'import',
                'origin' => 'Singapore',
                'destination' => 'Jakarta',
                'status' => 'in_progress',
                'weight' => 150.50,
                'pieces' => 5,
            ],
            [
                'awb_number' => 'AWB' . date('Y') . '002',
                'shipment_type' => 'sea',
                'service_type' => 'export',
                'origin' => 'Jakarta',
                'destination' => 'Shanghai',
                'status' => 'pending',
                'weight' => 5000.00,
                'pieces' => 20,
            ],
            [
                'awb_number' => 'AWB' . date('Y') . '003',
                'shipment_type' => 'air',
                'service_type' => 'import',
                'origin' => 'Hong Kong',
                'destination' => 'Surabaya',
                'status' => 'completed',
                'weight' => 200.00,
                'pieces' => 8,
            ],
        ];

        foreach ($customers as $index => $customer) {
            if (isset($shipmentData[$index])) {
                $data = $shipmentData[$index];
                
                $shipment = Shipment::create([
                    'customer_id' => $customer->id,
                    'awb_number' => $data['awb_number'],
                    'shipment_type' => $data['shipment_type'],
                    'service_type' => $data['service_type'],
                    'origin' => $data['origin'],
                    'destination' => $data['destination'],
                    'status' => $data['status'],
                    'weight' => $data['weight'],
                    'pieces' => $data['pieces'],
                    'commodity' => 'General Cargo',
                    'estimated_departure' => now()->addDays(2),
                    'estimated_arrival' => now()->addDays(7),
                ]);

                // Create initial status
                ShipmentStatus::create([
                    'shipment_id' => $shipment->id,
                    'status' => 'Shipment Created',
                    'location' => $data['origin'],
                    'notes' => 'Shipment has been created in the system',
                    'updated_by' => $adminUser->id,
                ]);

                // Add more status updates for in_progress shipments
                if ($data['status'] === 'in_progress') {
                    ShipmentStatus::create([
                        'shipment_id' => $shipment->id,
                        'status' => 'In Transit',
                        'location' => 'Port of ' . $data['origin'],
                        'notes' => 'Cargo departed from origin',
                        'updated_by' => $adminUser->id,
                        'created_at' => now()->subDays(1),
                    ]);
                }

                // Add completion status for completed shipments
                if ($data['status'] === 'completed') {
                    ShipmentStatus::create([
                        'shipment_id' => $shipment->id,
                        'status' => 'Completed',
                        'location' => $data['destination'],
                        'notes' => 'Shipment has been delivered successfully',
                        'updated_by' => $adminUser->id,
                        'created_at' => now()->subDays(2),
                    ]);
                }
            }
        }
    }
}
