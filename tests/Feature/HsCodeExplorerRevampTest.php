<?php

namespace Tests\Feature;

use App\Livewire\Customer\HsCodeExplorer as CustomerHsCodeExplorer;
use App\Livewire\HsCode\Explorer as AdminHsCodeExplorer;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class HsCodeExplorerRevampTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $customerUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'roles' => ['admin'],
        ]);

        $this->customerUser = User::factory()->create([
            'role' => 'customer',
            'roles' => ['customer'],
        ]);

        Customer::create([
            'user_id' => $this->customerUser->id,
            'customer_code' => 'CUST-001',
            'company_name' => 'PT Test Customer',
            'address' => 'Jakarta',
        ]);

        // Seed test section and chapter
        DB::table('hs_sections')->insert([
            'id' => 16,
            'section_number' => 'XVI',
            'title_id' => 'Mesin dan Peralatan Mekanis',
            'title_en' => 'Machinery and Mechanical Appliances',
            'display_order' => 16,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('hs_chapters')->insert([
            'chapter_number' => '84',
            'title_id' => 'Reaktor nuklir, ketel, mesin dan peralatan mekanis',
            'title_en' => 'Nuclear reactors, boilers, machinery and mechanical appliances',
            'section_id' => 16,
            'display_order' => 84,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Seed sample HS codes with duties
        $rows = [
            [
                'hs_code' => '84.71',
                'hs_level' => 4,
                'parent_code' => '84',
                'description_id' => 'Mesin pengolah data otomatis dan unitnya',
                'description_en' => 'Automatic data processing machines and units thereof',
                'chapter_number' => '84',
                'import_duty' => null,
                'export_duty' => null,
                'ppn' => null,
                'pph_api' => null,
                'pph_non_api' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'hs_code' => '8471.30',
                'hs_level' => 6,
                'parent_code' => '84.71',
                'description_id' => 'Mesin pengolah data otomatis portabel, dengan berat tidak lebih dari 10 kg',
                'description_en' => 'Portable automatic data processing machines, weighing not more than 10 kg',
                'chapter_number' => '84',
                'import_duty' => null,
                'export_duty' => null,
                'ppn' => null,
                'pph_api' => null,
                'pph_non_api' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'hs_code' => '8471.30.20',
                'hs_level' => 8,
                'parent_code' => '8471.30',
                'description_id' => 'Laptop termasuk notebook dan subnotebook',
                'description_en' => 'Laptops including notebooks and subnotebooks',
                'chapter_number' => '84',
                'import_duty' => '0',
                'export_duty' => '-',
                'ppn' => '11',
                'pph_api' => '2.5',
                'pph_non_api' => '7.5',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        foreach ($rows as $r) {
            DB::table('hs_codes')->insert($r);
        }
    }

    public function test_admin_hs_explorer_renders_with_tariffs()
    {
        Livewire::actingAs($this->admin)
            ->test(AdminHsCodeExplorer::class)
            ->assertStatus(200)
            ->assertSee('8471.30.20')
            ->assertSee('Laptop termasuk notebook dan subnotebook')
            ->assertSee('0% (Bebas)')
            ->assertSee('PPN: 11%');
    }

    public function test_admin_search_normalizes_dotless_digits()
    {
        Livewire::actingAs($this->admin)
            ->test(AdminHsCodeExplorer::class)
            ->set('search', '84713020')
            ->assertSee('8471.30.20')
            ->assertSee('Laptop termasuk notebook');
    }

    public function test_admin_level_and_chapter_filter()
    {
        Livewire::actingAs($this->admin)
            ->test(AdminHsCodeExplorer::class)
            ->set('selectedLevel', '8')
            ->set('selectedChapter', '84')
            ->assertSee('8471.30.20')
            ->assertDontSee('84.71');
    }

    public function test_admin_hierarchy_builder()
    {
        Livewire::actingAs($this->admin)
            ->test(AdminHsCodeExplorer::class)
            ->call('showHierarchy', '8471.30.20')
            ->assertSet('selectedCode', '8471.30.20')
            ->assertSee('Struktur Hierarki Klasifikasi')
            ->assertSee('Bab 84');
    }

    public function test_customer_hs_explorer_renders_and_filters()
    {
        Livewire::actingAs($this->customerUser)
            ->test(CustomerHsCodeExplorer::class)
            ->assertStatus(200)
            ->assertSee('8471.30.20')
            ->set('search', 'laptop')
            ->assertSee('8471.30.20');
    }
}
