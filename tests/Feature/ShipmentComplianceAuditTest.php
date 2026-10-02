<?php

namespace Tests\Feature;

use App\Livewire\Admin\ShipmentCompliancePanel;
use App\Models\Customer;
use App\Models\Shipment;
use App\Models\ShipmentComplianceAudit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ShipmentComplianceAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function createShipment(): Shipment
    {
        $user = User::factory()->create(['role' => 'admin', 'roles' => ['admin']]);
        $customer = Customer::create([
            'user_id' => $user->id,
            'company_name' => 'PT Sumber Makmur Impor',
            'customer_code' => 'CUST-00999',
            'phone' => '08123456789',
        ]);

        return Shipment::create([
            'customer_id' => $customer->id,
            'awb_number' => 'BL-SML-202610-001',
            'shipment_type' => 'sea',
            'service_type' => 'import',
            'commodity' => 'Hydraulic Valves',
            'hs_code' => '8481.20.00',
            'origin' => 'SHANGHAI, CHINA',
            'destination' => 'TANJUNG PRIOK, INDONESIA',
            'status' => 'document_collection',
        ]);
    }

    public function test_audit_model_can_record_findings_and_compute_effective_status(): void
    {
        $shipment = $this->createShipment();
        $user = User::factory()->create(['role' => 'admin']);

        $audit = ShipmentComplianceAudit::create([
            'shipment_id' => $shipment->id,
            'audited_by' => $user->id,
            'provider' => 'gemini',
            'model' => 'gemini-2.5-flash',
            'overall_status' => 'CRITICAL',
            'compliance_score' => 65,
            'summary' => 'Ditemukan selisih koli antara B/L dan Packing List.',
            'findings' => [
                [
                    'id' => 'f_1',
                    'document' => 'B/L vs PL',
                    'severity' => 'CRITICAL',
                    'title' => 'Selisih Jumlah Kemasan',
                    'description' => 'B/L mencatat 1200 cartons sedangkan PL mencatat 1205 cartons.',
                    'recommendation' => 'Klarifikasi ke pihak pelayaran sebelum aju PIB.',
                    'status' => 'OPEN',
                ],
            ],
            'disclaimer' => 'Disusun oleh Tim M2B.',
        ]);

        $this->assertEquals('CRITICAL', $audit->effective_status);

        // Resolusi manual oleh staf
        $audit->resolveFinding(0, 'Sudah konfirmasi ke pelayaran, 5 koli adalah sample bonus.', $user);

        $fresh = $audit->fresh();
        $this->assertEquals('RESOLVED', $fresh->findings[0]['status']);
        $this->assertEquals('Sudah konfirmasi ke pelayaran, 5 koli adalah sample bonus.', $fresh->findings[0]['resolution_note']);
        $this->assertEquals('COMPLIANT', $fresh->effective_status);
        $this->assertGreaterThanOrEqual(95, $fresh->compliance_score);
    }

    public function test_whatsapp_message_is_formatted_professionally_with_tim_m2b(): void
    {
        $shipment = $this->createShipment();
        $user = User::factory()->create(['role' => 'admin']);

        $audit = ShipmentComplianceAudit::create([
            'shipment_id' => $shipment->id,
            'audited_by' => $user->id,
            'overall_status' => 'ATTENTION',
            'compliance_score' => 85,
            'summary' => 'Perlu konfirmasi Form E Box 13.',
            'findings' => [
                [
                    'id' => 'f_1',
                    'document' => 'Form E',
                    'severity' => 'MEDIUM',
                    'title' => 'Third Party Invoicing',
                    'description' => 'Invoice dari Hong Kong tapi Box 13 belum tercentang.',
                    'recommendation' => 'Mintakan revisi Form E ke supplier.',
                    'status' => 'OPEN',
                ],
            ],
            'disclaimer' => 'Disusun oleh Tim M2B.',
        ]);

        $waText = $audit->buildWhatsAppText();

        $this->assertStringContainsString('UPDATE PRA-VERIFIKASI DOKUMEN PABEAN — M2B LOGISTICS', $waText);
        $this->assertStringContainsString('PT Sumber Makmur Impor', $waText);
        $this->assertStringContainsString('BL-SML-202610-001', $waText);
        $this->assertStringContainsString('Third Party Invoicing', $waText);
        $this->assertStringContainsString('Tim M2B', $waText);
        $this->assertStringNotContainsString('Ahli Kepabeanan M2B', $waText);
    }

    public function test_livewire_compliance_panel_renders_and_handles_resolution(): void
    {
        $shipment = $this->createShipment();
        $user = User::factory()->create(['role' => 'admin']);

        $audit = ShipmentComplianceAudit::create([
            'shipment_id' => $shipment->id,
            'audited_by' => $user->id,
            'overall_status' => 'ATTENTION',
            'compliance_score' => 80,
            'summary' => 'Uraian barang perlu diselaraskan.',
            'findings' => [
                [
                    'id' => 'f_1',
                    'document' => 'Invoice',
                    'severity' => 'MEDIUM',
                    'title' => 'Uraian Komersial',
                    'description' => 'Uraian barang terlalu umum.',
                    'recommendation' => 'Tambahkan spesifikasi material dan tipe.',
                    'status' => 'OPEN',
                ],
            ],
            'disclaimer' => 'Disusun oleh Tim M2B.',
        ]);

        $this->actingAs($user);

        Livewire::test(ShipmentCompliancePanel::class, ['shipmentId' => $shipment->id])
            ->assertSee('Pra-Audit Kepatuhan Berkas')
            ->assertSee('Pre-Clearance Diligence')
            ->assertSee('Uraian Komersial')
            ->call('openResolveModal', 0)
            ->set('resolutionNote', 'Uraian pabean resmi sudah dilengkapi di draft PIB.')
            ->call('saveResolution')
            ->assertHasNoErrors()
            ->assertSee('Telah Dikonfirmasi Tim M2B');

        $this->assertEquals('RESOLVED', $audit->fresh()->findings[0]['status']);
    }
}
