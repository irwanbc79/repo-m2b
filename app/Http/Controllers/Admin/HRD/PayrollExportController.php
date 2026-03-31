<?php

namespace App\Http\Controllers\Admin\HRD;

use App\Http\Controllers\Controller;
use App\Models\PayrollPeriod;
use App\Models\PayrollSlip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Barryvdh\DomPDF\Facade\Pdf;

class PayrollExportController extends Controller
{
    private function authorizeExport(): void
    {
        abort_unless(
            Auth::user()->hasRole(['admin', 'super_admin', 'finance', 'director']),
            403,
            'Anda tidak memiliki akses export.'
        );
    }

    /**
     * Export rekap penggajian bulanan sebagai Excel.
     */
    public function exportExcel(int $periodId)
    {
        $this->authorizeExport();

        $period = PayrollPeriod::findOrFail($periodId);
        $slips  = PayrollSlip::with('employee.jabatan')
            ->where('period_id', $periodId)
            ->orderBy('created_at')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Gaji');

        // ---- Company header ----
        $sheet->mergeCells('A1:M1');
        $sheet->setCellValue('A1', 'PT. MORA MULTI BERKAH');
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->mergeCells('A2:M2');
        $sheet->setCellValue('A2', 'REKAP GAJI KARYAWAN — ' . strtoupper($period->period_label));
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 11],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // ---- Column headers (row 4) ----
        $headers = [
            'A' => 'No',
            'B' => 'NIK',
            'C' => 'Nama Karyawan',
            'D' => 'Jabatan',
            'E' => 'Gaji Pokok',
            'F' => 'T. Transport',
            'G' => 'T. Jabatan',
            'H' => 'T. Lainnya',
            'I' => 'Lembur',
            'J' => 'Pot. BPJS Kes',
            'K' => 'Pot. BPJS TK',
            'L' => 'Pot. Lainnya',
            'M' => 'Total Gaji',
        ];

        foreach ($headers as $col => $label) {
            $sheet->setCellValue("{$col}4", $label);
        }

        $headerStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F2C59']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];
        $sheet->getStyle('A4:M4')->applyFromArray($headerStyle);

        // ---- Data rows ----
        $row      = 5;
        $grandTotal = 0;

        foreach ($slips as $i => $slip) {
            $total = $slip->total_gaji;
            $grandTotal += $total;

            $sheet->setCellValue("A{$row}", $i + 1);
            $sheet->setCellValue("B{$row}", $slip->employee->nik);
            $sheet->setCellValue("C{$row}", $slip->employee->nama);
            $sheet->setCellValue("D{$row}", $slip->employee->jabatan->nama_jabatan ?? '-');
            $sheet->setCellValue("E{$row}", (float) $slip->gaji_pokok);
            $sheet->setCellValue("F{$row}", (float) $slip->tunjangan_transport);
            $sheet->setCellValue("G{$row}", (float) $slip->tunjangan_jabatan);
            $sheet->setCellValue("H{$row}", (float) $slip->tunjangan_lainnya);
            $sheet->setCellValue("I{$row}", (float) $slip->lembur_nominal);
            $sheet->setCellValue("J{$row}", (float) $slip->potongan_bpjs_kes);
            $sheet->setCellValue("K{$row}", (float) $slip->potongan_bpjs_tk);
            $sheet->setCellValue("L{$row}", (float) $slip->potongan_lainnya);
            $sheet->setCellValue("M{$row}", $total);

            // Number format for currency columns
            $currencyCols = ['E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M'];
            foreach ($currencyCols as $col) {
                $sheet->getStyle("{$col}{$row}")
                    ->getNumberFormat()
                    ->setFormatCode('#,##0');
            }

            // Alternate row colour
            if ($i % 2 === 0) {
                $sheet->getStyle("A{$row}:M{$row}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
                ]);
            }

            $sheet->getStyle("A{$row}:M{$row}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
            ]);

            $row++;
        }

        // ---- Grand total row ----
        $sheet->mergeCells("A{$row}:D{$row}");
        $sheet->setCellValue("A{$row}", 'TOTAL');
        $sheet->setCellValue("M{$row}", $grandTotal);
        $sheet->getStyle("M{$row}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("A{$row}:M{$row}")->applyFromArray([
            'font'    => ['bold' => true],
            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DBEAFE']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        // ---- Column widths ----
        $widths = ['A' => 5, 'B' => 14, 'C' => 28, 'D' => 20,
                   'E' => 15, 'F' => 14, 'G' => 14, 'H' => 14,
                   'I' => 13, 'J' => 15, 'K' => 14, 'L' => 15, 'M' => 16];
        foreach ($widths as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        // ---- Output ----
        $filename = 'Rekap_Gaji_' . $period->period_label . '.xlsx';
        $writer   = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Export PDF slip gaji per karyawan.
     */
    public function exportPdfSlip(int $slipId)
    {
        $this->authorizeExport();

        $slip = PayrollSlip::with(['employee.jabatan', 'period', 'deductionItems'])->findOrFail($slipId);

        $pdf = Pdf::loadView('admin.hrd.pdf-slip', compact('slip'))
            ->setPaper('a4', 'portrait')
            ->setOptions(['dpi' => 150, 'defaultFont' => 'DejaVu Sans']);

        $filename = 'SlipGaji_' . str_replace(' ', '_', $slip->employee->nama)
            . '_' . $slip->period->period_label . '.pdf';

        return $pdf->download($filename);
    }
}
