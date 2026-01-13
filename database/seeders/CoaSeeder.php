<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;

class CoaSeeder extends Seeder
{
    public function run()
    {
        $accounts = [
            // ASET LANCAR (1-1000)
            ['code' => '1101', 'name' => 'Kas Besar', 'type' => 'kas_bank'],
            ['code' => '1102', 'name' => 'Kas Kecil (Petty Cash)', 'type' => 'kas_bank'],
            ['code' => '1103', 'name' => 'Bank Mandiri IDR', 'type' => 'kas_bank'],
            ['code' => '1104', 'name' => 'Bank BCA IDR', 'type' => 'kas_bank'],
            ['code' => '1201', 'name' => 'Piutang Usaha (IDR)', 'type' => 'piutang'],
            ['code' => '1202', 'name' => 'Piutang Karyawan', 'type' => 'aset_lancar_lain'],
            ['code' => '1301', 'name' => 'PPN Masukan', 'type' => 'aset_lancar_lain'],
            ['code' => '1302', 'name' => 'PPH 23 Dibayar Dimuka', 'type' => 'aset_lancar_lain'],

            // ASET TETAP (1-2000)
            ['code' => '1501', 'name' => 'Kendaraan', 'type' => 'aset_tetap'],
            ['code' => '1502', 'name' => 'Akum. Penyusutan Kendaraan', 'type' => 'aset_tetap'],
            ['code' => '1503', 'name' => 'Peralatan Kantor', 'type' => 'aset_tetap'],

            // KEWAJIBAN (2-0000)
            ['code' => '2101', 'name' => 'Hutang Usaha (Vendor)', 'type' => 'hutang_lancar'],
            ['code' => '2102', 'name' => 'PPN Keluaran', 'type' => 'hutang_lancar'],
            ['code' => '2103', 'name' => 'Hutang PPH 21', 'type' => 'hutang_lancar'],
            ['code' => '2104', 'name' => 'Hutang Gaji', 'type' => 'hutang_lancar'],

            // MODAL (3-0000)
            ['code' => '3101', 'name' => 'Modal Disetor', 'type' => 'modal'],
            ['code' => '3201', 'name' => 'Laba Ditahan', 'type' => 'modal'],
            ['code' => '3301', 'name' => 'Laba Tahun Berjalan', 'type' => 'modal'],

            // PENDAPATAN (4-0000)
            ['code' => '4101', 'name' => 'Pendapatan Jasa Freight', 'type' => 'pendapatan'],
            ['code' => '4102', 'name' => 'Pendapatan Jasa Custom', 'type' => 'pendapatan'],
            ['code' => '4103', 'name' => 'Pendapatan Trucking', 'type' => 'pendapatan'],
            ['code' => '4201', 'name' => 'Pendapatan Lain-lain', 'type' => 'pendapatan'],

            // BEBAN POKOK (5-0000)
            ['code' => '5101', 'name' => 'Biaya Freight (Ocean/Air)', 'type' => 'beban_pokok'],
            ['code' => '5102', 'name' => 'Biaya Trucking (Vendor)', 'type' => 'beban_pokok'],
            ['code' => '5103', 'name' => 'Biaya THC & Doc Fee', 'type' => 'beban_pokok'],

            // BEBAN OPERASIONAL (6-0000)
            ['code' => '6101', 'name' => 'Gaji & Tunjangan', 'type' => 'beban_operasional'],
            ['code' => '6102', 'name' => 'Biaya Listrik, Air, Internet', 'type' => 'beban_operasional'],
            ['code' => '6103', 'name' => 'Biaya Sewa Kantor', 'type' => 'beban_operasional'],
            ['code' => '6104', 'name' => 'Biaya ATK & Cetak', 'type' => 'beban_operasional'],
            ['code' => '6105', 'name' => 'Biaya Marketing & Entertaiment', 'type' => 'beban_operasional'],
            ['code' => '6106', 'name' => 'Biaya Maintenance', 'type' => 'beban_operasional'],
        ];

        foreach ($accounts as $acc) {
            Account::updateOrCreate(['code' => $acc['code']], $acc);
        }
    }
}