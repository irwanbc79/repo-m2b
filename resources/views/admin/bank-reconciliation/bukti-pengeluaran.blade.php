<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
  @page { size: {{ ($half ?? false) ? 'A4' : 'A5' }} portrait; margin: {{ ($half ?? false) ? '6mm' : '8mm' }} 10mm; }
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: Arial, sans-serif; font-size: 8.5pt; color: #1a1a1a; }

  /* ── Outer wrapper ── */
  .voucher { border: 1.5px solid #0F2C59; width: 100%; }

  /* ── Top red banner ── */
  .banner {
    background: #0F2C59;
    color: #fff;
    text-align: center;
    padding: 5px 8px;
    font-size: 12pt;
    font-weight: bold;
    letter-spacing: 1px;
  }

  /* ── Header row ── */
  .hdr-table { width: 100%; border-collapse: collapse; border-bottom: 2px solid #0F2C59; }
  .hdr-table td { padding: 5px 7px; vertical-align: middle; }

  .company-name { font-size: 9.5pt; font-weight: bold; color: #0F2C59; }
  .company-sub  { font-size: 7.5pt; color: #555; margin-top: 1px; }

  .kas-bank-row { margin-top: 4px; font-size: 8pt; }
  .cb     { display: inline-block; width: 9px; height: 9px; border: 1.5px solid #888; margin-right: 2px; vertical-align: middle; }
  .cb-on  { background: #0F2C59; border-color: #0F2C59; }

  .meta-tbl { border-collapse: collapse; font-size: 7.8pt; }
  .meta-tbl td { padding: 1px 3px; }
  .meta-label { color: #777; white-space: nowrap; }
  .meta-val   { font-weight: bold; border-bottom: 1px solid #ddd; min-width: 70px; }

  /* ── Thin red divider ── */
  .red-line { border-bottom: 1px solid #b8ccdf; margin: 0; }

  /* ── Dibayarkan Kepada ── */
  .to-table { width: 100%; border-collapse: collapse; background: #f0f5fb; }
  .to-table td { padding: 4px 7px; font-size: 8pt; }
  .to-label { color: #0F2C59; font-weight: bold; white-space: nowrap; width: 115px; }
  .to-value { }

  /* ── Data table ── */
  table.data-tbl { width: 100%; border-collapse: collapse; }
  table.data-tbl thead tr th {
    background: #0F2C59; color: #fff;
    font-weight: bold; font-size: 8pt;
    padding: 5px 5px; border: 1px solid #0a2040;
    text-align: center;
  }
  table.data-tbl tbody tr td {
    border: 1px solid #e0e0e0; padding: 2.5px 5px;
    font-size: 8pt; height: 14px;
  }
  table.data-tbl tbody tr:nth-child(even) td { background: #f0f5fb; }
  .col-kode { width: 14%; text-align: center; }
  .col-ket  { width: 60%; }
  .col-jml  { width: 26%; text-align: right; }

  /* ── Total & Saldo ── */
  .total-row td {
    font-weight: bold; background: #dce8f5 !important;
    border-top: 2px solid #0F2C59 !important;
  }

  /* ── Terbilang ── */
  .terbilang-box {
    margin: 0;
    padding: 4px 7px;
    background: #f0f5fb;
    border-left: 3px solid #0F2C59;
    font-size: 8pt;
    font-style: italic;
  }
  .terbilang-label { font-weight: bold; font-style: normal; color: #0F2C59; }

  /* ── Signature ── */
  table.sign-tbl { width: 100%; border-collapse: collapse; border-top: 2px solid #0F2C59; }
  table.sign-tbl th {
    background: #0F2C59; color: #fff;
    border: 1px solid #0a2040;
    padding: 4px 3px; text-align: center;
    font-size: 7pt; font-weight: bold;
  }
  table.sign-tbl td {
    border: 1px solid #e0e0e0;
    text-align: center; font-size: 7pt;
    height: 80px; vertical-align: bottom;
    padding: 2px 3px 6px 3px;
  }
  .sign-name { font-weight: bold; text-decoration: underline; font-size: 7.5pt; color: #1a1a1a; }
  .sign-role { font-weight: bold; font-size: 8pt; color: #0F2C59; }

  /* ── Footer strip ── */
  .footer-strip {
    background: #0F2C59;
    color: #fff;
    font-size: 6.5pt;
    text-align: center;
    padding: 2px;
    letter-spacing: 0.5px;
  }
</style>
</head>
<body>
<div class="voucher">

  {{-- ── Red Banner ── --}}
  <div class="banner">BUKTI PENGELUARAN</div>

  {{-- ── Header ── --}}
  <table class="hdr-table">
    <tr>
      {{-- Logo --}}
      <td style="width:58px; padding:5px 5px 5px 7px;">
        @if($logoBase64)
          <img src="{{ $logoBase64 }}" style="width:50px; height:auto;" alt="M2B">
        @endif
      </td>

      {{-- Company ── --}}
      <td style="padding:5px 6px;">
        <div class="company-name">PT. MORA MULTI BERKAH</div>
        <div class="company-sub">Jl. Kapten Sumarsono, Komplek Graha Metropolitan Blok G No. 14 Medan Helvetia</div>
        <div class="kas-bank-row">
          <span class="cb {{ $isKas ? 'cb-on' : '' }}"></span> KAS &nbsp;
          <span class="cb {{ !$isKas ? 'cb-on' : '' }}"></span> BANK
        </div>
      </td>

      {{-- No / Giro / Tanggal --}}
      <td style="text-align:right; padding:5px 7px; vertical-align:top;">
        <table class="meta-tbl" style="margin-left:auto;">
          <tr>
            <td class="meta-label">No. Voucher</td>
            <td style="padding:1px 3px;">:</td>
            <td class="meta-val">{{ $noVoucher }}</td>
          </tr>
          <tr>
            <td class="meta-label">Giro/Cek No.</td>
            <td style="padding:1px 3px;">:</td>
            <td class="meta-val">{{ $transaction->reference_number ?? '—' }}</td>
          </tr>
          <tr>
            <td class="meta-label">Tanggal</td>
            <td style="padding:1px 3px;">:</td>
            <td class="meta-val" style="color:#0F2C59;">{{ $transaction->transaction_date->format('d/m/Y') }}</td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

  {{-- ── Dibayarkan Kepada ── --}}
  <table class="to-table">
    <tr>
      <td class="to-label">Dibayarkan Kepada</td>
      <td style="width:6px; color:#0F2C59; font-weight:bold; padding:4px 2px;">:</td>
      <td class="to-value">{{ $beneficiary }}</td>
    </tr>
  </table>
  <div class="red-line"></div>

  {{-- ── Data Table ── --}}
  <table class="data-tbl">
    <thead>
      <tr>
        <th class="col-kode">Kode<br>Perkiraan</th>
        <th class="col-ket">KETERANGAN</th>
        <th class="col-jml">JUMLAH (Rp)</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td class="col-kode" style="color:#888; font-size:7pt;"></td>
        <td class="col-ket">
          <span style="font-weight:bold;">{{ \App\Models\BankTransaction::CATEGORIES[$transaction->category] ?? 'Lainnya' }}</span>
          <br><span style="font-size:7pt; color:#555;">{{ $keterangan ?? $transaction->description }}</span>
        </td>
        <td class="col-jml">{{ number_format($transaction->debit_amount, 0, ',', '.') }}</td>
      </tr>
      @for($i = 0; $i < 2; $i++)
      <tr style="height:18px;">
        <td class="col-kode">&nbsp;</td>
        <td class="col-ket">&nbsp;</td>
        <td class="col-jml">&nbsp;</td>
      </tr>
      @endfor
      <tr class="total-row">
        <td colspan="2" style="text-align:right; padding-right:8px; font-size:8.5pt;">TOTAL</td>
        <td class="col-jml" style="font-size:9pt;">{{ number_format($transaction->debit_amount, 0, ',', '.') }}</td>
      </tr>
    </tbody>
  </table>

  {{-- ── Terbilang ── --}}
  <div class="terbilang-box">
    <span class="terbilang-label">Terbilang : </span>{{ $terbilang }}
  </div>
  <div class="red-line"></div>

  {{-- ── Tanda Tangan ── --}}
  <table class="sign-tbl">
    <tr>
      <th style="width:20%;">Disetujui Oleh</th>
      <th style="width:20%;">Diketahui Oleh</th>
      <th style="width:20%;">Diperiksa Oleh</th>
      <th style="width:20%;">Dibayar Oleh</th>
      <th style="width:20%;">Yang Menerima</th>
    </tr>
    <tr>
      <td>
        <span class="sign-name">{{ !empty($signers['disetujui_nama']) ? $signers['disetujui_nama'] : 'Ir. Benny Tarigan' }}</span>
      </td>
      <td>
        <span class="sign-name">{{ !empty($signers['diketahui_nama']) ? $signers['diketahui_nama'] : 'Eka Mayang Sari Harahap, S.E.' }}</span>
      </td>
      <td>
        @if(!empty($signers['diperiksa_nama']))
          <span class="sign-name">{{ $signers['diperiksa_nama'] }}</span>
        @else
          &nbsp;
        @endif
      </td>
      <td>
        @if(!empty($signers['dibayar_nama']))
          <span class="sign-name">{{ $signers['dibayar_nama'] }}</span>
        @else
          <span class="sign-role">KASIR</span>
        @endif
      </td>
      <td>
        @if(!empty($signers['penerima_nama']))
          <span class="sign-name">{{ $signers['penerima_nama'] }}</span>
        @else
          &nbsp;
        @endif
      </td>
    </tr>
  </table>

  {{-- ── Footer Strip ── --}}
  <div class="footer-strip">PT. MORA MULTI BERKAH · Dokumen Internal · Dicetak {{ now()->format('d/m/Y H:i') }}</div>

</div>

@if(($half ?? false) || ($twoUp ?? false))
<div style="margin-top:8mm; border-top:1.5px dashed #aaa; text-align:center; padding-top:3px;">
  <span style="font-size:7pt; color:#bbb; letter-spacing:1px;">✂ &nbsp; POTONG DI SINI &nbsp; ✂</span>
</div>
@endif

@if($twoUp ?? false)
{{-- ── Salinan ke-2 ── --}}
<div style="margin-top:6mm;">
<div class="voucher">
  <div class="banner">BUKTI PENGELUARAN</div>
  <table class="hdr-table">
    <tr>
      <td style="width:58px; padding:5px 5px 5px 7px;">
        @if($logoBase64)
          <img src="{{ $logoBase64 }}" style="width:50px; height:auto;" alt="M2B">
        @endif
      </td>
      <td style="padding:5px 6px;">
        <div class="company-name">PT. MORA MULTI BERKAH</div>
        <div class="company-sub">Jl. Kapten Sumarsono, Komplek Graha Metropolitan Blok G No. 14 Medan Helvetia</div>
        <div class="kas-bank-row">
          <span class="cb {{ $isKas ? 'cb-on' : '' }}"></span> KAS &nbsp;
          <span class="cb {{ !$isKas ? 'cb-on' : '' }}"></span> BANK
        </div>
      </td>
      <td style="text-align:right; padding:5px 7px; vertical-align:top;">
        <table class="meta-tbl" style="margin-left:auto;">
          <tr><td class="meta-label">No. Voucher</td><td style="padding:1px 3px;">:</td><td class="meta-val">{{ $noVoucher }}</td></tr>
          <tr><td class="meta-label">Giro/Cek No.</td><td style="padding:1px 3px;">:</td><td class="meta-val">{{ $transaction->reference_number ?? '—' }}</td></tr>
          <tr><td class="meta-label">Tanggal</td><td style="padding:1px 3px;">:</td><td class="meta-val" style="color:#0F2C59;">{{ $transaction->transaction_date->format('d/m/Y') }}</td></tr>
        </table>
      </td>
    </tr>
  </table>
  <table class="to-table">
    <tr>
      <td class="to-label">Dibayarkan Kepada</td>
      <td style="width:6px; color:#0F2C59; font-weight:bold; padding:4px 2px;">:</td>
      <td class="to-value">{{ $beneficiary }}</td>
    </tr>
  </table>
  <div class="red-line"></div>
  <table class="data-tbl">
    <thead>
      <tr>
        <th class="col-kode">Kode<br>Perkiraan</th>
        <th class="col-ket">KETERANGAN</th>
        <th class="col-jml">JUMLAH (Rp)</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td class="col-kode" style="color:#888; font-size:7pt;"></td>
        <td class="col-ket">
          <span style="font-weight:bold;">{{ \App\Models\BankTransaction::CATEGORIES[$transaction->category] ?? 'Lainnya' }}</span>
          <br><span style="font-size:7pt; color:#555;">{{ $keterangan ?? $transaction->description }}</span>
        </td>
        <td class="col-jml">{{ number_format($transaction->debit_amount, 0, ',', '.') }}</td>
      </tr>
      @for($i = 0; $i < 2; $i++)
      <tr style="height:18px;"><td class="col-kode">&nbsp;</td><td class="col-ket">&nbsp;</td><td class="col-jml">&nbsp;</td></tr>
      @endfor
      <tr class="total-row">
        <td colspan="2" style="text-align:right; padding-right:8px; font-size:8.5pt;">TOTAL</td>
        <td class="col-jml" style="font-size:9pt;">{{ number_format($transaction->debit_amount, 0, ',', '.') }}</td>
      </tr>
    </tbody>
  </table>
  <div class="terbilang-box"><span class="terbilang-label">Terbilang : </span>{{ $terbilang }}</div>
  <div class="red-line"></div>
  <table class="sign-tbl">
    <tr>
      <th style="width:20%;">Disetujui Oleh</th>
      <th style="width:20%;">Diketahui Oleh</th>
      <th style="width:20%;">Diperiksa Oleh</th>
      <th style="width:20%;">Dibayar Oleh</th>
      <th style="width:20%;">Yang Menerima</th>
    </tr>
    <tr>
      <td><span class="sign-name">{{ !empty($signers['disetujui_nama']) ? $signers['disetujui_nama'] : 'Ir. Benny Tarigan' }}</span></td>
      <td><span class="sign-name">{{ !empty($signers['diketahui_nama']) ? $signers['diketahui_nama'] : 'Eka Mayang Sari Harahap, S.E.' }}</span></td>
      <td>@if(!empty($signers['diperiksa_nama']))<span class="sign-name">{{ $signers['diperiksa_nama'] }}</span>@else &nbsp; @endif</td>
      <td>@if(!empty($signers['dibayar_nama']))<span class="sign-name">{{ $signers['dibayar_nama'] }}</span>@else <span class="sign-role">KASIR</span>@endif</td>
      <td>@if(!empty($signers['penerima_nama']))<span class="sign-name">{{ $signers['penerima_nama'] }}</span>@else &nbsp; @endif</td>
    </tr>
  </table>
  <div class="footer-strip">PT. MORA MULTI BERKAH · Dokumen Internal · Dicetak {{ now()->format('d/m/Y H:i') }}</div>
</div>
</div>
@endif

</body>
</html>
