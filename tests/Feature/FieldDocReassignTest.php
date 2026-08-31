<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\FieldPhoto;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FieldDocReassignTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::factory()->create([
            'name' => 'Admin Lapangan',
            'email' => 'admin.field@m2b.co.id',
            'role' => 'admin',
        ]);
    }

    public function test_can_reassign_photos_to_another_shipment()
    {
        $admin = $this->createAdmin();

        $customerUser = User::factory()->create();
        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'company_name' => 'CV BRYNA BALAKOSA ABADI',
            'customer_code' => 'BRY-001',
            'phone' => '0812345678',
            'address' => 'Jakarta',
        ]);

        $oldShipment = Shipment::create([
            'customer_id' => $customer->id,
            'shipment_type' => 'export',
            'service_type' => 'air',
            'awb_number' => 'EXP-260227-431',
            'status' => 'delivered',
            'origin' => 'Jakarta',
            'destination' => 'Singapore',
        ]);

        $newShipment = Shipment::create([
            'customer_id' => $customer->id,
            'shipment_type' => 'export',
            'service_type' => 'air',
            'awb_number' => 'EXP-260831-999',
            'status' => 'in_transit',
            'origin' => 'Jakarta',
            'destination' => 'Singapore',
        ]);

        $photo1 = FieldPhoto::create([
            'shipment_id' => $oldShipment->id,
            'user_id' => $admin->id,
            'original_filename' => 'foto1.jpg',
            'file_path' => 'field-photos/foto1.jpg',
            'file_size' => 1024,
            'mime_type' => 'image/jpeg',
            'status' => 'approved',
        ]);

        $photo2 = FieldPhoto::create([
            'shipment_id' => $oldShipment->id,
            'user_id' => $admin->id,
            'original_filename' => 'foto2.jpg',
            'file_path' => 'field-photos/foto2.jpg',
            'file_size' => 1024,
            'mime_type' => 'image/jpeg',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($admin)
            ->postJson(route('admin.field-docs.reassign-photos'), [
                'photo_ids' => [$photo1->id, $photo2->id],
                'target_shipment_id' => $newShipment->id,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $photo1->refresh();
        $photo2->refresh();

        $this->assertEquals($newShipment->id, $photo1->shipment_id);
        $this->assertEquals($newShipment->id, $photo2->shipment_id);
    }

    public function test_search_shipments_api_prioritizes_active_shipments()
    {
        $admin = $this->createAdmin();

        $customerUser = User::factory()->create();
        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'company_name' => 'CV BRYNA BALAKOSA ABADI',
            'customer_code' => 'BRY-002',
            'phone' => '0812345678',
            'address' => 'Jakarta',
        ]);

        $oldShipment = Shipment::create([
            'customer_id' => $customer->id,
            'shipment_type' => 'export',
            'service_type' => 'air',
            'awb_number' => 'EXP-260227-431',
            'status' => 'delivered',
            'origin' => 'Jakarta',
            'destination' => 'Singapore',
            'created_at' => now()->subMonths(6),
        ]);

        $newShipment = Shipment::create([
            'customer_id' => $customer->id,
            'shipment_type' => 'export',
            'service_type' => 'air',
            'awb_number' => 'EXP-260831-999',
            'status' => 'in_transit',
            'origin' => 'Jakarta',
            'destination' => 'Singapore',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->getJson(route('admin.field-docs.search-shipments', ['q' => 'BRYNA']));

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertNotEmpty($data);
        $this->assertEquals('EXP-260831-999', $data[0]['awb_number']);
        $this->assertTrue($data[0]['is_active']);
    }
}
