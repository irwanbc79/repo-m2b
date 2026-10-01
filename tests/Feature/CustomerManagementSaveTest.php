<?php

namespace Tests\Feature;

use App\Livewire\Admin\CustomerManagement;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerManagementSaveTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_create_modal_and_see_accurate_customer_code(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Customer::create([
            'user_id' => $admin->id,
            'customer_code' => 'CUST-000108',
            'company_name' => 'Existing Company',
        ]);

        Livewire::actingAs($admin)
            ->test(CustomerManagement::class)
            ->call('create')
            ->assertSet('isModalOpen', true)
            ->assertSet('customerId', null)
            ->assertSet('customer_code', 'CUST-000109');
    }

    public function test_admin_can_create_customer_via_save(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(CustomerManagement::class)
            ->call('create')
            ->set('company_name', 'PT. NORIN ENERGY SOLUTIONS')
            ->set('npwp', '0123456789012345')
            ->set('name', 'KATRINA')
            ->set('phone', '+62 851-9888-9229')
            ->set('email', 'norinenergysolutions@gmail.com')
            ->set('password', 'secret123')
            ->set('city', 'PATUMBAK')
            ->set('credit_limit', 0)
            ->set('address', 'JL. TANGKAHAN BATU SIGARA-GARA, PATUMBAK')
            ->set('warehouse_address', 'JL. TANGKAHAN BATU SIGARA-GARA, PATUMBAK')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('isModalOpen', false);

        $this->assertDatabaseHas('users', [
            'email' => 'norinenergysolutions@gmail.com',
            'name' => 'KATRINA',
            'role' => 'customer',
        ]);

        $customer = Customer::where('company_name', 'PT. NORIN ENERGY SOLUTIONS')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('CUST-000001', $customer->customer_code);
        $this->assertTrue($customer->users()->where('email', 'norinenergysolutions@gmail.com')->exists());
    }

    public function test_editing_resets_properly_when_creating_new_customer(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'customer']);
        $customer = Customer::create([
            'user_id' => $user->id,
            'customer_code' => 'CUST-000050',
            'company_name' => 'Old Customer',
        ]);

        $component = Livewire::actingAs($admin)
            ->test(CustomerManagement::class)
            ->call('edit', $customer->id)
            ->assertSet('customerId', $customer->id)
            ->assertSet('isEditing', true);

        // Setelah klik Add Customer, customerId harus kembali null
        $component->call('create')
            ->assertSet('customerId', null)
            ->assertSet('isEditing', false)
            ->assertSet('customer_code', 'CUST-000051');
    }
}
