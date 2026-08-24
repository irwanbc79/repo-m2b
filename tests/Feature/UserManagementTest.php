<?php

namespace Tests\Feature;

use App\Livewire\Admin\UserManagement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'roles' => ['admin'],
        ]);
    }

    public function test_admin_can_assign_konsultan_pajak_role_to_user(): void
    {
        $this->actingAs($this->admin);

        $targetUser = User::factory()->create([
            'name' => 'Target User',
            'email' => 'target@example.com',
            'role' => 'staff',
            'roles' => ['staff'],
        ]);

        Livewire::test(UserManagement::class)
            ->call('edit', $targetUser->id)
            ->set('name', 'KONSULTAN PAJAK')
            ->set('selectedRoles', ['konsultan_pajak'])
            ->call('save')
            ->assertHasNoErrors();

        $targetUser->refresh();
        $this->assertEquals('KONSULTAN PAJAK', $targetUser->name);
        $this->assertEquals('konsultan_pajak', $targetUser->role);
        $this->assertEquals(['konsultan_pajak'], $targetUser->roles);
        $this->assertTrue($targetUser->hasRole('konsultan_pajak'));
    }

    public function test_admin_can_create_new_user_with_konsultan_pajak_role(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(UserManagement::class)
            ->call('create')
            ->set('name', 'Konsultan Pajak Baru')
            ->set('email', 'pajak.baru@m2b.co.id')
            ->set('password', 'password123')
            ->set('selectedRoles', ['konsultan_pajak'])
            ->call('save')
            ->assertHasNoErrors();

        $newUser = User::where('email', 'pajak.baru@m2b.co.id')->first();
        $this->assertNotNull($newUser);
        $this->assertEquals('konsultan_pajak', $newUser->role);
        $this->assertEquals(['konsultan_pajak'], $newUser->roles);
    }
}
