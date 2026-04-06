<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankTransaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class BankReconciliationExportController extends Controller
{
    /**
     * Build filtered query dari request params.
     */
    private function buildQuery(Request $request)
    {
        $query = BankTransaction::query()->orderBy('transaction_date')->orderBy('id');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('description', 'like', "%{$request->search}%")
                  ->orWhere('reference_number', 'like', "%{$request->search}%");
            });
        }

        if ($request->filterBank) {
            $query->where('bank_name', $request->filterBank);
        }

        if ($request->filterStatus === 'reconciled') {
            $query->where('is_reconciled', true);
        } elseif ($request->filterStatus === 'unreconciled') {
            $query->where('is_reconciled', false);
        }

        if ($request->filterCategory) {
            $query->where('category', $request->filterCategory);
        }

        if ($request->filterDateFrom) {
            $query->whereDate('transaction_date', '>=', $request->filterDateFrom);
        }

        if ($request->filterDateTo) {
            $query->whereDate('transaction_date', '<=', $request->filterDateTo);
        }

        return $query;
    }

    /**
     * Export PDF — format rekening koran resmi.
     */
    public function exportPdf(Request $request)
    {
        $transactions = $this->buildQuery($request)->get();
        $signers      = $this->parseSigners($request);
        $meta         = $this->buildMeta($request, $transactions);

        $pdf = Pdf::loadView('admin.bank-reconciliation.export-pdf', compact('transactions', 'signers', 'meta'))
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'defaultFont'  => 'DejaVu Sans',
                'isRemoteEnabled' => false,
                'dpi'          => 150,
            ]);

        $filename = 'rekonsiliasi-bank-' . now()->format('Ymd-His') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export Excel — format rekening koran.
     */
    public function exportExcel(Request $request)
    {
        $transactions = $this->buildQuery($request)->get();
        $signers      = $this->parseSigners($request);
        $meta         = $this->buildMeta($request, $transactions);

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekonsiliasi Bank');

        // ── Lebar kolom ─────────────────────────────────────────────────────
        $sheet->getColumnDimension('A')->setWidth(14);  // Tanggal
        $sheet->getColumnDimension('B')->setWidth(60);  // Keterangan
        $sheet->getColumnDimension('C')->setWidth(20);  // Kategori
        $sheet->getColumnDimension('D')->setWidth(18);  // Debit
        $sheet->getColumnDimension('E')->setWidth(18);  // Kredit
        $sheet->getColumnDimension('F')->setWidth(20);  // Saldo
        $sheet->getColumnDimension('G')->setWidth(14);  // Status

        // ── Header perusahaan ────────────────────────────────────────────────
        $row = 1;
        $sheet->mergeCells("A{$row}:G{$row}");
        $sheet->setCellValue("A{$row}", 'PT. MORA MULTI BERKAH');
        $sheet->getStyle("A{$row}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $row++;
        $sheet->mergeCells("A{$row}:G{$row}");
        $sheet->setCellValue("A{$row}", 'REKONSILIASI BANK PERIODE ' . strtoupper($meta['period']));
        $sheet->getStyle("A{$row}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $row++;
        $sheet->mergeCells("A{$row}:G{$row}");
        $sheet->setCellValue("A{$row}", 'NO REKENING : ' . ($meta['account_number'] ?: '106-00-5598889-6'));
        $sheet->getStyle("A{$row}")->applyFromArray([
            'font'      => ['size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // ── Filter info ──────────────────────────────────────────────────────
        $row++;
        $sheet->mergeCells("A{$row}:G{$row}");
        $filterInfo = [];
        if ($meta['bank']) {
            $filterInfo[] = 'Bank: ' . strtoupper($meta['bank']);
        }
        if ($meta['status']) {
            $filterInfo[] = 'Status: ' . $meta['status'];
        }
        $filterInfo[] = 'Periode: ' . $meta['date_from'] . ' s/d ' . $meta['date_to'];
        $sheet->setCellValue("A{$row}", implode('  |  ', $filterInfo));
        $sheet->getStyle("A{$row}")->applyFromArray([
            'font'      => ['size' => 9, 'italic' => true, 'color' => ['rgb' => '666666']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $row++;

        // ── Header tabel ─────────────────────────────────────────────────────
        $headerRow = $row;
        $headers   = ['TANGGAL', 'KETERANGAN', 'KATEGORI', 'DEBIT', 'KREDIT', 'SALDO', 'STATUS'];
        foreach ($headers as $i => $h) {
            $col = chr(65 + $i);
            $sheet->setCellValue("{$col}{$row}", $h);
        }

        $sheet->getStyle("A{$headerRow}:G{$headerRow}")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C0392B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(20);

        // ── Saldo awal ────────────────────────────────────────────────────────
        if ($meta['opening_balance'] !== null) {
            $row++;
            $sheet->setCellValue("A{$row}", '');
            $sheet->setCellValue("B{$row}", 'SALDO AWAL');
            $sheet->setCellValue("C{$row}", '');
            $sheet->setCellValue("D{$row}", '');
            $sheet->setCellValue("E{$row}", '');
            $sheet->setCellValue("F{$row}", $meta['opening_balance']);
            $sheet->setCellValue("G{$row}", '');
            $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
                'font' => ['bold' => true, 'italic' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5F5F5']],
            ]);
            $this->applyNumberFormat($sheet, $row);
        }

        // ── Data baris ────────────────────────────────────────────────────────
        $totalDebit  = 0;
        $totalKredit = 0;
        foreach ($transactions as $idx => $trx) {
            $row++;
            $bgColor = $idx % 2 === 0 ? 'FFFFFF' : 'FDF9F9';

            $sheet->setCellValue("A{$row}", $trx->transaction_date->format('d/m/Y'));
            $sheet->setCellValue("B{$row}", $trx->description);
            $sheet->setCellValue("C{$row}", BankTransaction::CATEGORIES[$trx->category] ?? $trx->category);
            $sheet->setCellValue("D{$row}", $trx->debit_amount  > 0 ? (float) $trx->debit_amount  : '');
            $sheet->setCellValue("E{$row}", $trx->credit_amount > 0 ? (float) $trx->credit_amount : '');
            $sheet->setCellValue("F{$row}", (float) $trx->balance);
            $sheet->setCellValue("G{$row}", $trx->is_reconciled ? 'Rekonsiliasi' : 'Pending');

            $totalDebit  += (float) $trx->debit_amount;
            $totalKredit += (float) $trx->credit_amount;

            $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
                'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E0E0E0']]],
            ]);
            $sheet->getStyle("B{$row}")->getAlignment()->setWrapText(true);
            $this->applyNumberFormat($sheet, $row);

            // Warna status
            $statusColor = $trx->is_reconciled ? '27AE60' : 'E67E22';
            $sheet->getStyle("G{$row}")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color($statusColor));
        }

        // ── Baris total ───────────────────────────────────────────────────────
        $row++;
        $sheet->setCellValue("A{$row}", '');
        $sheet->mergeCells("A{$row}:C{$row}");
        $sheet->setCellValue("A{$row}", 'TOTAL');
        $sheet->setCellValue("D{$row}", $totalDebit);
        $sheet->setCellValue("E{$row}", $totalKredit);
        $sheet->setCellValue("F{$row}", $transactions->last()?->balance ?? 0);
        $sheet->setCellValue("G{$row}", '');

        $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
            'font'    => ['bold' => true],
            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FADBD8']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM]],
        ]);
        $this->applyNumberFormat($sheet, $row);

        // ── Saldo akhir ───────────────────────────────────────────────────────
        $row++;
        $sheet->mergeCells("A{$row}:E{$row}");
        $sheet->setCellValue("A{$row}", 'SALDO AKHIR');
        $sheet->setCellValue("F{$row}", $transactions->last()?->balance ?? 0);
        $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
            'font'    => ['bold' => true, 'size' => 11],
            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F8E8']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM]],
        ]);
        $this->applyNumberFormat($sheet, $row);

        // ── Area tanda tangan ─────────────────────────────────────────────────
        $row += 2;
        $sheet->mergeCells("A{$row}:G{$row}");
        $sheet->setCellValue("A{$row}", 'Medan, ' . now()->translatedFormat('d F Y'));
        $sheet->getStyle("A{$row}")->applyFromArray([
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        ]);

        $row += 2;
        // 5 kolom tanda tangan — merge ke 7 kolom
        $signCols = [
            ['A', 'A', $signers['disetujui_label'],  $signers['disetujui_nama']],
            ['B', 'B', $signers['diketahui_label'],  $signers['diketahui_nama']],
            ['C', 'C', $signers['diperiksa1_label'], $signers['diperiksa1_nama']],
            ['E', 'F', $signers['diperiksa2_label'], $signers['diperiksa2_nama']],
            ['G', 'G', $signers['dibuat_label'],     $signers['dibuat_nama']],
        ];

        $labelRow = $row;
        $nameRow  = $row + 5;

        foreach ($signCols as [$colS, $colE, $label, $name]) {
            if ($colS !== $colE) {
                $sheet->mergeCells("{$colS}{$labelRow}:{$colE}{$labelRow}");
                $sheet->mergeCells("{$colS}{$nameRow}:{$colE}{$nameRow}");
            }
            $sheet->setCellValue("{$colS}{$labelRow}", $label);
            $sheet->setCellValue("{$colS}{$nameRow}", $name);
            $sheet->getStyle("{$colS}{$labelRow}")->applyFromArray([
                'font'      => ['bold' => true, 'size' => 9],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            $sheet->getStyle("{$colS}{$nameRow}")->applyFromArray([
                'font'      => ['bold' => true, 'size' => 9, 'underline' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }

        // ── Cetak ─────────────────────────────────────────────────────────────
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getHeaderFooter()->setOddHeader('&C&B PT. MORA MULTI BERKAH — Rekonsiliasi Bank');
        $sheet->getHeaderFooter()->setOddFooter('&L&D &T&C&P dari &N');

        $writer   = new Xlsx($spreadsheet);
        $filename = 'rekonsiliasi-bank-' . now()->format('Ymd-His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    /**
     * Print voucher Bukti Penerimaan atau Bukti Pengeluaran per transaksi.
     */
    public function printVoucher(Request $request, int $id)
    {
        $transaction = BankTransaction::findOrFail($id);
        $isDebit     = (float) $transaction->debit_amount > 0;
        $isKas       = false; // default: BANK

        // Nomor voucher: BP/TRX/YYYYMMDD/ID atau BK/TRX/YYYYMMDD/ID
        $prefix    = $isDebit ? 'BPK' : 'BPM';
        $noVoucher = $prefix . '/' . $transaction->transaction_date->format('Ymd') . '/' . str_pad($id, 4, '0', STR_PAD_LEFT);

        $signers = [
            'disetujui_nama'  => $request->input('disetujui_nama', ''),
            'diketahui_nama'  => $request->input('diketahui_nama', ''),
            'diperiksa_nama'  => $request->input('diperiksa_nama', ''),
            'kasir_nama'      => $request->input('kasir_nama', ''),
            'dibayar_nama'    => $request->input('dibayar_nama', ''),
            'penerima_nama'   => $request->input('penerima_nama', ''),
        ];

        // Logo as base64 — bypass DomPDF file-type detection issues
        $logoPath   = public_path('images/m2b-logo.png');
        $logoBase64 = file_exists($logoPath)
            ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath))
            : null;

        // Extract payer/beneficiary name from description
        $desc        = $transaction->description;
        $beneficiary = $this->extractNameFromDescription($desc, $isDebit);
        $sender      = $beneficiary; // alias untuk penerimaan

        if ($isDebit) {
            $terbilang = $this->terbilang((float) $transaction->debit_amount) . ' Rupiah';
            $pdf = Pdf::loadView('admin.bank-reconciliation.bukti-pengeluaran',
                compact('transaction', 'isKas', 'noVoucher', 'signers', 'terbilang', 'logoBase64', 'beneficiary'))
                ->setPaper('a5', 'portrait')
                ->setOptions(['defaultFont' => 'Arial', 'isRemoteEnabled' => false, 'dpi' => 150]);
            $filename = 'bukti-pengeluaran-' . $id . '.pdf';
        } else {
            $terbilang = $this->terbilang((float) $transaction->credit_amount) . ' Rupiah';
            $pdf = Pdf::loadView('admin.bank-reconciliation.bukti-penerimaan',
                compact('transaction', 'isKas', 'noVoucher', 'signers', 'logoBase64', 'sender', 'terbilang'))
                ->setPaper('a5', 'portrait')
                ->setOptions(['defaultFont' => 'Arial', 'isRemoteEnabled' => false, 'dpi' => 150]);
            $filename = 'bukti-penerimaan-' . $id . '.pdf';
        }

        return $pdf->stream($filename);
    }

    // ─── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Ekstrak nama pengirim / penerima dari deskripsi transaksi bank.
     * Mendukung format Mandiri e-banking dan transfer umum.
     */
    private function extractNameFromDescription(string $desc, bool $isDebit): string
    {
        if ($isDebit) {
            // Pengeluaran — "KE [NAME] Transfer Fee" atau "TRSF ... KE [NAME]"
            if (preg_match('/\bKE\s+([A-Z][A-Z0-9\s\.\-]+?)(?:\s+Transfer Fee|\s+\d{8,}|\s+Ref\s*:|$)/i', $desc, $m)) {
                return trim($m[1]);
            }
        } else {
            // Penerimaan — Mandiri: "TRSF E-BANKING CR MMDD/TYPE/CHAN/REFNUM NAME"
            if (preg_match('/\d{4}\/\w+\/\w+\/\d+\s+([A-Z][A-Z0-9\s\.\-]+?)(?:\s{2,}|\s+\d{8,}|$)/i', $desc, $m)) {
                return trim($m[1]);
            }
            // "DARI [NAME]"
            if (preg_match('/\bDARI\s+([A-Z][A-Z0-9\s\.\-]+?)(?:\s{2,}|\s+Ref|\s+\d{8,}|$)/i', $desc, $m)) {
                return trim($m[1]);
            }
            // "INKASO MASUK / [NAME]"
            if (preg_match('/INKASO\s+MASUK\s*[\/\-]\s*([A-Z][A-Z0-9\s\.\-]+?)(?:\s*[\/\-]|$)/i', $desc, $m)) {
                return trim($m[1]);
            }
        }
        return $desc;
    }

    private function terbilang(float $angka): string
    {
        $angka  = (int) round($angka);
        $satuan = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan',
                   'sepuluh', 'sebelas', 'dua belas', 'tiga belas', 'empat belas', 'lima belas',
                   'enam belas', 'tujuh belas', 'delapan belas', 'sembilan belas'];

        if ($angka < 20) return $satuan[$angka];
        if ($angka < 100) {
            $puluhan = (int) ($angka / 10);
            $sisa    = $angka % 10;
            return $satuan[$puluhan] . ' puluh' . ($sisa ? ' ' . $satuan[$sisa] : '');
        }
        if ($angka < 200) return 'seratus' . ($angka - 100 ? ' ' . $this->terbilang($angka - 100) : '');
        if ($angka < 1000) {
            $ratus = (int) ($angka / 100);
            $sisa  = $angka % 100;
            return $satuan[$ratus] . ' ratus' . ($sisa ? ' ' . $this->terbilang($sisa) : '');
        }
        if ($angka < 2000) return 'seribu' . ($angka - 1000 ? ' ' . $this->terbilang($angka - 1000) : '');
        if ($angka < 1000000) {
            $ribu = (int) ($angka / 1000);
            $sisa = $angka % 1000;
            return $this->terbilang($ribu) . ' ribu' . ($sisa ? ' ' . $this->terbilang($sisa) : '');
        }
        if ($angka < 1000000000) {
            $juta = (int) ($angka / 1000000);
            $sisa = $angka % 1000000;
            return $this->terbilang($juta) . ' juta' . ($sisa ? ' ' . $this->terbilang($sisa) : '');
        }
        $milyar = (int) ($angka / 1000000000);
        $sisa   = $angka % 1000000000;
        return $this->terbilang($milyar) . ' milyar' . ($sisa ? ' ' . $this->terbilang($sisa) : '');
    }

    private function applyNumberFormat($sheet, int $row): void
    {
        foreach (['D', 'E', 'F'] as $col) {
            $sheet->getStyle("{$col}{$row}")
                ->getNumberFormat()
                ->setFormatCode('#,##0');
            $sheet->getStyle("{$col}{$row}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }
    }

    private function parseSigners(Request $request): array
    {
        return [
            'disetujui_label'  => $request->input('disetujui_label',  'DISETUJUI OLEH'),
            'disetujui_nama'   => $request->input('disetujui_nama',   ''),
            'diketahui_label'  => $request->input('diketahui_label',  'DIKETAHUI OLEH'),
            'diketahui_nama'   => $request->input('diketahui_nama',   ''),
            'diperiksa1_label' => $request->input('diperiksa1_label', 'DIPERIKSA OLEH'),
            'diperiksa1_nama'  => $request->input('diperiksa1_nama',  ''),
            'diperiksa2_label' => $request->input('diperiksa2_label', 'DIPERIKSA OLEH'),
            'diperiksa2_nama'  => $request->input('diperiksa2_nama',  ''),
            'dibuat_label'     => $request->input('dibuat_label',     'DIBUAT OLEH'),
            'dibuat_nama'      => $request->input('dibuat_nama',      ''),
        ];
    }

    private function buildMeta(Request $request, $transactions): array
    {
        $bankMap = [
            'mandiri' => 'Bank Mandiri',
            'bca'     => 'BCA',
        ];

        $statusMap = [
            'reconciled'   => 'Sudah Rekonsiliasi',
            'unreconciled' => 'Belum Rekonsiliasi',
        ];

        $firstTrx = $transactions->first();

        return [
            'period'           => $request->filterDateFrom
                ? \Carbon\Carbon::parse($request->filterDateFrom)->translatedFormat('F Y')
                : now()->translatedFormat('F Y'),
            'date_from'        => $request->filterDateFrom
                ? \Carbon\Carbon::parse($request->filterDateFrom)->format('d/m/Y')
                : '-',
            'date_to'          => $request->filterDateTo
                ? \Carbon\Carbon::parse($request->filterDateTo)->format('d/m/Y')
                : '-',
            'bank'             => $bankMap[$request->filterBank] ?? $request->filterBank ?? '',
            'status'           => $statusMap[$request->filterStatus] ?? '',
            'account_number'   => $firstTrx?->account_number ?? '106-00-5598889-6',
            'total'            => $transactions->count(),
            'opening_balance'  => null, // bisa diisi dari saldo awal jika ada
        ];
    }
}
