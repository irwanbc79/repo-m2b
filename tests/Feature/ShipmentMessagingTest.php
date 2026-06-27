<?php

namespace Tests\Feature;

use App\Livewire\Admin\CustomerMessages;
use App\Livewire\Customer\ShipmentDetail;
use App\Models\Customer;
use App\Models\Shipment;
use App\Models\ShipmentMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ShipmentMessagingTest extends TestCase
{
    use RefreshDatabase;

    private function makeCustomerWithShipment(): array
    {
        $user = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $customer = Customer::create([
            'user_id' => $user->id,
            'customer_code' => Customer::generateCustomerCode(),
            'company_name' => 'PT Kirim Jaya',
        ]);
        $shipment = Shipment::create([
            'customer_id' => $customer->id,
            'awb_number' => 'AWB-' . uniqid(),
            'shipment_type' => 'sea',
            'service_type' => 'import',
            'origin' => 'Shanghai',
            'destination' => 'Surabaya',
            'status' => 'pending',
        ]);

        return [$user, $customer, $shipment];
    }

    /** Customer dapat mengirim pesan di shipment miliknya. */
    public function test_customer_can_send_message(): void
    {
        [$user, $customer, $shipment] = $this->makeCustomerWithShipment();

        Livewire::actingAs($user)
            ->test(ShipmentDetail::class, ['id' => $shipment->id])
            ->set('newMessage', 'Kapan perkiraan tiba?')
            ->call('sendMessage')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('shipment_messages', [
            'shipment_id' => $shipment->id,
            'sender_type' => 'customer',
            'body' => 'Kapan perkiraan tiba?',
        ]);
    }

    /** Membuka detail menandai pesan admin sebagai sudah dibaca customer. */
    public function test_opening_detail_marks_admin_messages_read(): void
    {
        [$user, $customer, $shipment] = $this->makeCustomerWithShipment();
        $adminMsg = ShipmentMessage::create([
            'shipment_id' => $shipment->id,
            'customer_id' => $customer->id,
            'sender_type' => 'admin',
            'body' => 'Halo, ada yang bisa dibantu?',
        ]);

        $this->assertNull($adminMsg->read_at);

        Livewire::actingAs($user)->test(ShipmentDetail::class, ['id' => $shipment->id]);

        $this->assertNotNull($adminMsg->fresh()->read_at);
    }

    /** Customer tidak bisa mengakses shipment milik customer lain. */
    public function test_customer_cannot_open_others_shipment(): void
    {
        [, , $shipment] = $this->makeCustomerWithShipment();
        $intruder = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        Customer::create([
            'user_id' => $intruder->id,
            'customer_code' => Customer::generateCustomerCode(),
            'company_name' => 'PT Penyusup',
        ]);

        $this->expectException(ModelNotFoundException::class);
        Livewire::actingAs($intruder)->test(ShipmentDetail::class, ['id' => $shipment->id]);
    }

    /** Admin membalas + thread menandai pesan customer terbaca. */
    public function test_admin_can_reply_and_marks_read(): void
    {
        [$user, $customer, $shipment] = $this->makeCustomerWithShipment();
        $custMsg = ShipmentMessage::create([
            'shipment_id' => $shipment->id,
            'customer_id' => $customer->id,
            'sender_type' => 'customer',
            'sender_id' => $user->id,
            'body' => 'Dokumen sudah lengkap?',
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(CustomerMessages::class)
            ->call('selectThread', $shipment->id)
            ->set('reply', 'Sudah, tinggal proses bea cukai.')
            ->call('sendReply')
            ->assertHasNoErrors();

        $this->assertNotNull($custMsg->fresh()->read_at, 'Pesan customer harus tertandai dibaca.');
        $this->assertDatabaseHas('shipment_messages', [
            'shipment_id' => $shipment->id,
            'sender_type' => 'admin',
            'body' => 'Sudah, tinggal proses bea cukai.',
        ]);
    }

    /** Scope unread membedakan arah pesan dengan benar. */
    public function test_unread_scopes(): void
    {
        [$user, $customer, $shipment] = $this->makeCustomerWithShipment();
        ShipmentMessage::create([
            'shipment_id' => $shipment->id, 'customer_id' => $customer->id,
            'sender_type' => 'customer', 'body' => 'tanya',
        ]);
        ShipmentMessage::create([
            'shipment_id' => $shipment->id, 'customer_id' => $customer->id,
            'sender_type' => 'admin', 'body' => 'jawab',
        ]);

        $this->assertEquals(1, ShipmentMessage::unreadForAdmin()->count());
        $this->assertEquals(1, ShipmentMessage::unreadForCustomer()->count());
    }
}
