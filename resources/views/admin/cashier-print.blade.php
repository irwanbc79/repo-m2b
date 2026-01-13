<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bukti Kas {{ strtoupper($cash->type) }}</title>

    <style>
        @page {
            size: 21.59cm 35.56cm; /* LEGAL / F4 */
            margin: 2cm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #000;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .header h2 {
            margin: 0;
            font-size: 18px;
        }

        .header p {
            margin: 2px 0;
            font-size: 11px;
        }

        .title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin: 20px 0;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        table td {
            padding: 6px;
            vertical-align: top;
        }

        .label {
            width: 25%;
            font-weight: bold;
        }

        .value {
            width: 75%;
        }

        .amount {
            font-size: 16px;
            font-weight: bold;
        }

        .box {
            border: 1px solid #000;
            padding: 8px;
        }

        .signatures {
            margin-top: 60px;
            width: 100%;
        }

        .signatures td {
            text-align: center;
            height: 80px;
        }

        .sign-name {
            margin-top: 60px;
            font-weight: bold;
            text-decoration: underline;
        }

        .footer {
            position: fixed;
            bottom: 1.5cm;
            width: 100%;
            text-align: right;
            font-size: 10px;
        }
    </style>
</head>
<body>

{{-- HEADER PERUSAHAAN --}}
<div class="header">
    <h2>PT. MORA MULTI BERKAH</h2>
    <p>Jl. Kapten Sumaryono, Komplek Graha Metropolitan Blok G No.14</p>
    <p>Medan Helvetia – Indonesia</p>
</div>

{{-- JUDUL --}}
<div class="title">
    Bukti Kas {{ $cash->type === 'in' ? 'Masuk' : 'Keluar' }}
</div>

{{-- DATA TRANSAKSI --}}
<table>
    <tr>
        <td class="label">Tanggal</td>
        <td class="value">: {{ \Carbon\Carbon::parse($cash->transaction_date)->format('d/m/Y') }}</td>
    </tr>
    <tr>
        <td class="label">Jenis Transaksi</td>
        <td class="value">: {{ strtoupper($cash->type) }}</td>
    </tr>
    <tr>
        <td class="label">Akun Kas / Bank</td>
        <td class="value">: {{ $cash->account->code ?? '' }} – {{ $cash->account->name ?? '' }}</td>
    </tr>
    <tr>
        <td class="label">Akun Lawan</td>
        <td class="value">: {{ $cash->counterAccount->code ?? '' }} – {{ $cash->counterAccount->name ?? '' }}</td>
    </tr>
</table>

{{-- NOMINAL --}}
<table>
    <tr>
        <td class="label">Nominal</td>
        <td class="value amount">
            : Rp {{ number_format($cash->amount, 0, ',', '.') }}
        </td>
    </tr>
    <tr>
        <td class="label">Terbilang</td>
        <td class="value box">
            {{ ucwords($terbilang) }}
        </td>
    </tr>
</table>

{{-- KETERANGAN --}}
<table>
    <tr>
        <td class="label">Keterangan</td>
        <td class="value box">
            {{ $cash->description }}
        </td>
    </tr>
</table>

{{-- PENANDA TANGAN --}}
<table class="signatures">
    <tr>
        <td>Dibuat oleh</td>
        <td>Diperiksa oleh</td>
        <td>Disetujui oleh</td>
    </tr>
    <tr>
        <td class="sign-name">
            {{ $signatories['dibuat_oleh'] ?? '______________' }}
        </td>
        <td class="sign-name">
            {{ $signatories['diperiksa_oleh'] ?? '______________' }}
        </td>
        <td class="sign-name">
            {{ $signatories['disetujui_oleh'] ?? '______________' }}
        </td>
    </tr>
</table>

{{-- FOOTER --}}
<div class="footer">
    Dicetak pada {{ now()->format('d/m/Y H:i') }}
</div>

</body>
</html>
