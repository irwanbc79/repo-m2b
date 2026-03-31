<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Slip Gaji - {{ $slip->employee->nama }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; color: #1a202c; }
        .container { width: 100%; max-width: 680px; margin: 0 auto; padding: 24px; }

        /* Header */
        .header { border-bottom: 3px solid #0F2C59; padding-bottom: 12px; margin-bottom: 16px; }
        .company-name { font-size: 18px; font-weight: bold; color: #0F2C59; }
        .company-sub { font-size: 10px; color: #4a5568; margin-top: 2px; }
        .slip-title { font-size: 13px; font-weight: bold; text-align: center; margin: 12px 0; color: #0F2C59; letter-spacing: 1px; text-transform: uppercase; }

        /* Employee info */
        .info-grid { display: table; width: 100%; margin-bottom: 16px; border: 1px solid #e2e8f0; border-radius: 4px; overflow: hidden; }
        .info-row { display: table-row; }
        .info-label { display: table-cell; padding: 6px 10px; background: #f7fafc; color: #718096; width: 130px; font-size: 10px; border-bottom: 1px solid #e2e8f0; }
        .info-value { display: table-cell; padding: 6px 10px; color: #2d3748; font-weight: 600; border-bottom: 1px solid #e2e8f0; }

        /* Salary table */
        .section-title { font-size: 10px; font-weight: bold; color: #ffffff; background: #0F2C59; padding: 5px 10px; margin-bottom: 0; }
        table.salary { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.salary th { background: #ebf4ff; padding: 6px 10px; text-align: left; font-size: 10px; color: #2c5282; border: 1px solid #bee3f8; }
        table.salary td { padding: 6px 10px; border: 1px solid #e2e8f0; font-size: 11px; }
        table.salary td.amount { text-align: right; font-family: 'DejaVu Sans Mono', monospace; }
        table.salary tr:nth-child(even) td { background: #f7fafc; }
        .deduction td { color: #c53030; }

        /* Total */
        .total-box { background: #0F2C59; color: #ffffff; padding: 12px 14px; border-radius: 4px; margin-top: 4px; display: table; width: 100%; }
        .total-label { display: table-cell; font-size: 12px; font-weight: bold; }
        .total-amount { display: table-cell; text-align: right; font-size: 16px; font-weight: bold; }

        /* Footer */
        .footer { margin-top: 32px; display: table; width: 100%; }
        .sign-box { display: table-cell; width: 50%; text-align: center; }
        .sign-line { border-top: 1px solid #718096; margin-top: 60px; padding-top: 6px; font-size: 10px; color: #4a5568; }
        .note { font-size: 9px; color: #a0aec0; text-align: center; margin-top: 20px; border-top: 1px dashed #e2e8f0; padding-top: 8px; }

        @media print { body { -webkit-print-color-adjust: exact; } }
    </style>
</head>
<body>
<div class="container">

    {{-- Company Header --}}
    <div class="header">
        <div class="company-name">PT. MORA MULTI BERKAH</div>
        <div class="company-sub">Jl. Contoh No. 1, Jakarta | HRD Department</div>
    </div>

    <div class="slip-title">Slip Gaji Karyawan &mdash; {{ $slip->period->period_label }}</div>

    {{-- Employee Info --}}
    <div class="info-grid">
        <div class="info-row">
            <div class="info-label">Nama</div>
            <div class="info-value">{{ $slip->employee->nama }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">NIK</div>
            <div class="info-value">{{ $slip->employee->nik }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Jabatan</div>
            <div class="info-value">{{ $slip->employee->jabatan->nama_jabatan ?? '-' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Jenis Karyawan</div>
            <div class="info-value">
                {{ $slip->employee->employment_type === 'permanent' ? 'Karyawan Tetap (PKWTT)' : 'Karyawan Kontrak (PKWT)' }}
            </div>
        </div>
        <div class="info-row">
            <div class="info-label">Periode</div>
            <div class="info-value">{{ $slip->period->period_label }}</div>
        </div>
    </div>

    {{-- Pendapatan --}}
    <div class="section-title">Pendapatan</div>
    <table class="salary">
        <tr>
            <td>Gaji Pokok</td>
            <td class="amount">Rp {{ number_format($slip->gaji_pokok, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Tunjangan Transport</td>
            <td class="amount">Rp {{ number_format($slip->tunjangan_transport, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Tunjangan Jabatan</td>
            <td class="amount">Rp {{ number_format($slip->tunjangan_jabatan, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Tunjangan Lainnya</td>
            <td class="amount">Rp {{ number_format($slip->tunjangan_lainnya, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Lembur ({{ number_format($slip->lembur_jam, 1) }} jam)</td>
            <td class="amount">Rp {{ number_format($slip->lembur_nominal, 0, ',', '.') }}</td>
        </tr>
    </table>

    {{-- Potongan --}}
    <div class="section-title">Potongan</div>
    <table class="salary">
        <tr class="deduction">
            <td>BPJS Kesehatan</td>
            <td class="amount">Rp {{ number_format($slip->potongan_bpjs_kes, 0, ',', '.') }}</td>
        </tr>
        <tr class="deduction">
            <td>BPJS Ketenagakerjaan</td>
            <td class="amount">Rp {{ number_format($slip->potongan_bpjs_tk, 0, ',', '.') }}</td>
        </tr>
        @if($slip->potongan_lainnya > 0)
        <tr class="deduction">
            <td>Potongan Lainnya</td>
            <td class="amount">Rp {{ number_format($slip->potongan_lainnya, 0, ',', '.') }}</td>
        </tr>
        @endif
        {{-- Dynamic deduction items (kasbon, absen, dll) --}}
        @foreach($slip->deductionItems as $item)
        <tr class="deduction">
            <td>
                {{ $item->nama_potongan }}
                @if($item->catatan)
                <span style="font-size:9px; color:#a0aec0;"> — {{ $item->catatan }}</span>
                @endif
            </td>
            <td class="amount">Rp {{ number_format($item->nominal, 0, ',', '.') }}</td>
        </tr>
        @endforeach
    </table>

    {{-- Catatan --}}
    @if($slip->catatan)
    <p style="font-size:10px; color:#718096; margin-bottom:10px;">
        <strong>Catatan:</strong> {{ $slip->catatan }}
    </p>
    @endif

    {{-- Total --}}
    <div class="total-box">
        <div class="total-label">TAKE HOME PAY</div>
        <div class="total-amount">Rp {{ number_format($slip->total_gaji, 0, ',', '.') }}</div>
    </div>

    {{-- Signatures --}}
    <div class="footer">
        <div class="sign-box">
            <div class="sign-line">
                Karyawan,<br><br>{{ $slip->employee->nama }}
            </div>
        </div>
        <div class="sign-box">
            <div class="sign-line">
                HRD / Finance,<br><br>PT. Mora Multi Berkah
            </div>
        </div>
    </div>

    <div class="note">
        Dokumen ini digenerate secara otomatis oleh sistem. Dicetak pada: {{ now()->format('d M Y H:i') }}
    </div>

</div>
</body>
</html>
