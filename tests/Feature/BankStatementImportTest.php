<?php

namespace Tests\Feature;

use App\Livewire\Admin\BankReconciliation;
use App\Models\BankTransaction;
use App\Models\User;
use App\Services\BankStatementImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BankStatementImportTest extends TestCase
{
    use RefreshDatabase;

    private function userAdmin(): User
    {
        return User::factory()->create(['name' => 'Kinan', 'role' => 'admin', 'roles' => ['admin']]);
    }

    public function test_import_mandiri_csv_statement_berhasil(): void
    {
        $csvContent = "AccountNo;Ccy;PostDate;Remarks;AdditionalDesc;Credit Amount;Debit Amount;Close Balance\n"
            . "1060055988896;IDR;03 August 2026 11:27:10;GAJI CS M2B (LINA);MCM InhouseTrf KE EKA MAYANG SARI HARAHAP;0.00;750000.00;13838932.64\n"
            . "1060055988896;IDR;07 August 2026 10:16:54;PT SARANA CEPAT LOGISTIN;Trf Inw CN BANK CENTRAL ASIA;1000000.00;0.00;14838932.64\n";

        $tempPath = tempnam(sys_get_temp_dir(), 'mandiri_') . '.csv';
        file_put_contents($tempPath, $csvContent);

        $service = new BankStatementImportService();
        $result = $service->import($tempPath, 'mandiri');

        unlink($tempPath);

        $this->assertTrue($result['success']);
        $this->assertSame(2, $result['imported']);
        $this->assertSame(0, $result['duplicates']);
        $this->assertCount(2, BankTransaction::all());

        $t1 = BankTransaction::where('credit_amount', 1000000)->first();
        $this->assertNotNull($t1);
        $this->assertSame('mandiri', $t1->bank_name);
        $this->assertSame('1060055988896', $t1->account_number);
        $this->assertSame('PT SARANA CEPAT LOGISTIN', $t1->description);
        $this->assertSame('Trf Inw CN BANK CENTRAL ASIA', $t1->additional_description);
    }

    public function test_import_mandiri_dengan_bulan_indonesia_berhasil(): void
    {
        $csvContent = "AccountNo;Ccy;PostDate;Remarks;AdditionalDesc;Credit Amount;Debit Amount;Close Balance\n"
            . "1060055988896;IDR;15 Agustus 2026 10:29:08;TRANSFER DARI PT DIRABARAKAMULIA;BSMDIDJA/PTDIRABARAKAMULIA;8000000.00;0.00;15357051.64\n";

        $tempPath = tempnam(sys_get_temp_dir(), 'mandiri_') . '.csv';
        file_put_contents($tempPath, $csvContent);

        $service = new BankStatementImportService();
        $result = $service->import($tempPath, 'mandiri');

        unlink($tempPath);

        $this->assertTrue($result['success']);
        $this->assertSame(1, $result['imported']);
        $this->assertDatabaseHas('bank_transactions', [
            'credit_amount' => 8000000,
            'transaction_date' => '2026-08-15',
        ]);
    }

    public function test_import_csv_lewat_livewire_bank_reconciliation(): void
    {
        $admin = $this->userAdmin();

        $csvContent = "AccountNo;Ccy;PostDate;Remarks;AdditionalDesc;Credit Amount;Debit Amount;Close Balance\n"
            . "1060055988896;IDR;07 August 2026 10:16:54;PT SARANA CEPAT LOGISTIN;Trf Inw CN BANK CENTRAL ASIA;1000000.00;0.00;14838932.64\n";

        $file = UploadedFile::fake()->createWithContent('Acc_Statement_1060055988896.csv', $csvContent);

        Livewire::actingAs($admin)
            ->test(BankReconciliation::class)
            ->call('openImportModal')
            ->set('selectedBank', 'mandiri')
            ->set('csvFile', $file)
            ->call('importCsv')
            ->assertHasNoErrors()
            ->assertSet('importResult.success', true)
            ->assertSet('importResult.imported', 1);

        $this->assertSame(1, BankTransaction::count());
    }

    public function test_transaksi_duplikat_tidak_diinsert_ganda(): void
    {
        $csvContent = "AccountNo;Ccy;PostDate;Remarks;AdditionalDesc;Credit Amount;Debit Amount;Close Balance\n"
            . "1060055988896;IDR;03 August 2026 11:27:10;GAJI CS M2B (LINA);MCM InhouseTrf;0.00;750000.00;13838932.64\n";

        $tempPath = tempnam(sys_get_temp_dir(), 'mandiri_') . '.csv';
        file_put_contents($tempPath, $csvContent);

        $service = new BankStatementImportService();
        $res1 = $service->import($tempPath, 'mandiri');
        $this->assertSame(1, $res1['imported']);

        // Import kedua kalinya
        $res2 = $service->import($tempPath, 'mandiri');
        unlink($tempPath);

        $this->assertTrue($res2['success']);
        $this->assertSame(0, $res2['imported']);
        $this->assertSame(1, $res2['duplicates']);
        $this->assertSame(1, BankTransaction::count());
    }
}
