<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'DejaVu Sans', sans-serif; font-size: 8.5pt; color: #1a1a1a; }

  /* ── Header perusahaan ── */
  .company-header { text-align: center; margin-bottom: 6px; }
  .company-name { font-size: 13pt; font-weight: bold; letter-spacing: 0.5px; }
  .doc-title { font-size: 11pt; font-weight: bold; margin-top: 2px; }
  .doc-sub { font-size: 8pt; margin-top: 2px; color: #444; }
  .divider { border-top: 2px solid #C0392B; margin: 6px 0 4px; }
  .divider-thin { border-top: 1px solid #ccc; margin: 4px 0; }

  /* ── Meta info ── */
  .meta-row { display: flex; justify-content: space-between; font-size: 7.5pt; color: #555; margin-bottom: 6px; }

  /* ── Tabel ── */
  table { width: 100%; border-collapse: collapse; }
  thead tr th {
    background-color: #C0392B;
    color: #fff;
    font-weight: bold;
    padding: 5px 4px;
    text-align: center;
    font-size: 8pt;
    border: 1px solid #a93226;
  }
  tbody tr td {
    padding: 3px 4px;
    border: 1px solid #ddd;
    font-size: 7.8pt;
    vertical-align: top;
  }
  tbody tr:nth-child(even) td { background-color: #fdf6f6; }
  tbody tr.saldo-awal td { background-color: #f0f0f0; font-weight: bold; font-style: italic; }
  tbody tr.total-row td { background-color: #fadbd8; font-weight: bold; border-top: 2px solid #C0392B; }
  tbody tr.saldo-akhir td { background-color: #eafbea; font-weight: bold; }

  .text-right { text-align: right; }
  .text-center { text-align: center; }
  .text-left { text-align: left; }

  .badge-reconciled { color: #1e8449; font-weight: bold; font-size: 7pt; }
  .badge-pending { color: #ca6f1e; font-weight: bold; font-size: 7pt; }

  .debit-val { color: #922b21; }
  .kredit-val { color: #1a5276; }

  /* ── Tanda tangan ── */
  .sign-section { margin-top: 24px; }
  .sign-date { text-align: right; font-size: 8pt; margin-bottom: 12px; }
  .sign-table { width: 100%; border-collapse: collapse; }
  .sign-table td {
    width: 20%;
    text-align: center;
    padding: 4px 6px;
    vertical-align: top;
  }
  .sign-label { font-weight: bold; font-size: 7.5pt; border-bottom: 1px solid #ccc; padding-bottom: 2px; }
  .sign-space { height: 40px; }
  .sign-name { font-weight: bold; font-size: 8pt; text-decoration: underline; margin-top: 2px; }
  .sign-empty { font-size: 7pt; color: #aaa; font-style: italic; }

  /* ── Page break ── */
  @page { margin: 10mm 8mm 10mm 8mm; size: A4 landscape; }
</style>
</head>
<body>

{{-- ── Header Perusahaan ── --}}
<div class="company-header">
  <div class="company-name">PT. MORA MULTI BERKAH</div>
  <div class="doc-title">REKONSILIASI BANK PERIODE {{ strtoupper($meta['period']) }}</div>
  <div class="doc-sub">NO REKENING : {{ $meta['account_number'] }}</div>
</div>
<div class="divider"></div>

{{-- ── Meta Info ── --}}
<div class="meta-row">
  <span>
    @if($meta['bank']) Bank: <b>{{ $meta['bank'] }}</b> &nbsp;|&nbsp; @endif
    Periode: <b>{{ $meta['date_from'] }} s/d {{ $meta['date_to'] }}</b>
    @if($meta['status']) &nbsp;|&nbsp; Status: <b>{{ $meta['status'] }}</b> @endif
  </span>
  <span>Total: <b>{{ $meta['total'] }}</b> transaksi &nbsp;|&nbsp; Dicetak: {{ now()->format('d/m/Y H:i') }}</span>
</div>

{{-- ── Tabel Transaksi ── --}}
<table>
  <thead>
    <tr>
      <th style="width:7%">TANGGAL</th>
      <th style="width:46%">KETERANGAN</th>
      <th style="width:10%">KATEGORI</th>
      <th style="width:11%" class="text-right">DEBIT</th>
      <th style="width:11%" class="text-right">KREDIT</th>
      <th style="width:11%" class="text-right">SALDO</th>
      <th style="width:4%">STATUS</th>
    </tr>
  </thead>
  <tbody>
    {{-- Saldo Awal --}}
    @if($meta['opening_balance'] !== null)
    <tr class="saldo-awal">
      <td></td>
      <td>SALDO AWAL</td>
      <td></td>
      <td></td>
      <td></td>
      <td class="text-right">{{ number_format($meta['opening_balance'], 0, ',', '.') }}</td>
      <td></td>
    </tr>
    @endif

    @php
      $totalDebit  = 0;
      $totalKredit = 0;
    @endphp

    @forelse($transactions as $trx)
      @php
        $totalDebit  += (float) $trx->debit_amount;
        $totalKredit += (float) $trx->credit_amount;
      @endphp
      <tr>
        <td class="text-center">{{ $trx->transaction_date->format('d/m/Y') }}</td>
        <td>{{ $trx->description }}</td>
        <td class="text-center" style="font-size:7pt;">
          {{ \App\Models\BankTransaction::CATEGORIES[$trx->category] ?? $trx->category }}
        </td>
        <td class="text-right debit-val">
          @if($trx->debit_amount > 0)
            {{ number_format($trx->debit_amount, 0, ',', '.') }}
          @else
            <span style="color:#ccc;">—</span>
          @endif
        </td>
        <td class="text-right kredit-val">
          @if($trx->credit_amount > 0)
            {{ number_format($trx->credit_amount, 0, ',', '.') }}
          @else
            <span style="color:#ccc;">—</span>
          @endif
        </td>
        <td class="text-right">{{ number_format($trx->balance, 0, ',', '.') }}</td>
        <td class="text-center">
          @if($trx->is_reconciled)
            <span class="badge-reconciled">✔</span>
          @else
            <span class="badge-pending">⏳</span>
          @endif
        </td>
      </tr>
    @empty
      <tr>
        <td colspan="7" class="text-center" style="padding:16px;color:#888;">
          Tidak ada data transaksi untuk filter yang dipilih.
        </td>
      </tr>
    @endforelse

    {{-- Baris Total --}}
    <tr class="total-row">
      <td></td>
      <td><b>TOTAL</b></td>
      <td></td>
      <td class="text-right debit-val"><b>{{ number_format($totalDebit, 0, ',', '.') }}</b></td>
      <td class="text-right kredit-val"><b>{{ number_format($totalKredit, 0, ',', '.') }}</b></td>
      <td></td>
      <td></td>
    </tr>

    {{-- Saldo Akhir --}}
    @if($transactions->last())
    <tr class="saldo-akhir">
      <td></td>
      <td colspan="4"><b>SALDO AKHIR</b></td>
      <td class="text-right"><b>{{ number_format($transactions->last()->balance, 0, ',', '.') }}</b></td>
      <td></td>
    </tr>
    @endif
  </tbody>
</table>

{{-- ── Area Tanda Tangan ── --}}
<div class="sign-section">
  <div class="sign-date">Medan, {{ now()->translatedFormat('d F Y') }}</div>
  <table class="sign-table">
    <tr>
      <td>
        <div class="sign-label">{{ $signers['disetujui_label'] }}</div>
        <div class="sign-space"></div>
        @if($signers['disetujui_nama'])
          <div class="sign-name">{{ $signers['disetujui_nama'] }}</div>
        @else
          <div class="sign-empty">( _________________ )</div>
        @endif
      </td>
      <td>
        <div class="sign-label">{{ $signers['diketahui_label'] }}</div>
        <div class="sign-space"></div>
        @if($signers['diketahui_nama'])
          <div class="sign-name">{{ $signers['diketahui_nama'] }}</div>
        @else
          <div class="sign-empty">( _________________ )</div>
        @endif
      </td>
      <td>
        <div class="sign-label">{{ $signers['diperiksa1_label'] }}</div>
        <div class="sign-space"></div>
        @if($signers['diperiksa1_nama'])
          <div class="sign-name">{{ $signers['diperiksa1_nama'] }}</div>
        @else
          <div class="sign-empty">( _________________ )</div>
        @endif
      </td>
      <td>
        <div class="sign-label">{{ $signers['diperiksa2_label'] }}</div>
        <div class="sign-space"></div>
        @if($signers['diperiksa2_nama'])
          <div class="sign-name">{{ $signers['diperiksa2_nama'] }}</div>
        @else
          <div class="sign-empty">( _________________ )</div>
        @endif
      </td>
      <td>
        <div class="sign-label">{{ $signers['dibuat_label'] }}</div>
        <div class="sign-space"></div>
        @if($signers['dibuat_nama'])
          <div class="sign-name">{{ $signers['dibuat_nama'] }}</div>
        @else
          <div class="sign-empty">( _________________ )</div>
        @endif
      </td>
    </tr>
  </table>
</div>

</body>
</html>
