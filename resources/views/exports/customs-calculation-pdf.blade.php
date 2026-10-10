<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Estimasi Perhitungan Pabean & Pajak Impor - M2B</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10.5px; color: #1e293b; background: #ffffff; padding: 25px 30px; line-height: 1.4; }
        
        .header-table { width: 100%; border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 15px; }
        .company-name { font-size: 16px; font-weight: bold; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px; }
        .company-sub { font-size: 9px; color: #475569; margin-top: 2px; }
        .doc-title { text-align: right; }
        .doc-title h2 { font-size: 14px; font-weight: bold; color: #2563eb; text-transform: uppercase; letter-spacing: 0.5px; }
        .doc-title p { font-size: 8.5px; color: #64748b; margin-top: 2px; }

        .meta-box { width: 100%; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 14px; margin-bottom: 15px; }
        .meta-table { width: 100%; }
        .meta-table td { padding: 3px 6px; font-size: 10px; }
        .meta-label { color: #64748b; font-weight: bold; width: 18%; }
        .meta-val { color: #0f172a; font-weight: bold; width: 32%; }

        .section-title { font-size: 11px; font-weight: bold; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; padding-bottom: 3px; border-bottom: 1.5px solid #3b82f6; }

        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table.data-table th { background: #0f172a; color: #ffffff; font-size: 9px; text-transform: uppercase; padding: 6px 10px; text-align: left; letter-spacing: 0.5px; }
        table.data-table td { padding: 6px 10px; border-bottom: 1px solid #e2e8f0; font-size: 10px; }
        table.data-table tr:nth-child(even) td { background: #f8fafc; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: 'Courier New', Courier, monospace; }
        .font-bold { font-weight: bold; }

        .total-box { background: #eff6ff; border: 1.5px solid #bfdbfe; border-radius: 6px; padding: 10px 14px; margin-bottom: 15px; }
        .total-row { display: table; width: 100%; }
        .total-left { display: table-cell; vertical-align: middle; }
        .total-right { display: table-cell; text-align: right; vertical-align: middle; }
        .total-amount { font-size: 18px; font-weight: bold; color: #1d4ed8; }

        .tax-analysis-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 10px 14px; margin-bottom: 15px; }
        .tax-analysis-title { font-size: 10.5px; font-weight: bold; color: #166534; text-transform: uppercase; margin-bottom: 6px; }
        
        .footer { margin-top: 20px; padding-top: 10px; border-top: 1px solid #e2e8f0; font-size: 8px; color: #64748b; line-height: 1.3; }
        .badge { display: inline-block; padding: 2px 6px; font-size: 8px; font-weight: bold; border-radius: 4px; text-transform: uppercase; }
        .badge-blue { background: #dbeafe; color: #1e40af; }
        .badge-green { background: #dcfce7; color: #166534; }
    </style>
</head>
<body>

    {{-- Header Perusahaan & Dokumen --}}
    <table class="header-table">
        <tr>
            <td style="width: 55%; vertical-align: middle;">
                <div class="company-name">PT. MORA MULTI BERKAH</div>
                <div class="company-sub">Customs Brokerage, Freight Forwarding & Logistics Solutions</div>
                <div class="company-sub">Portal M2B Control Center &bull; https://portal.m2b.co.id &bull; Telp: 061-44020012</div>
            </td>
            <td class="doc-title" style="width: 45%; vertical-align: middle;">
                <h2>Proforma Estimasi Pabean</h2>
                <p>Simulasi Pungutan Impor & Analisis Pajak</p>
                <p>Dibuat: {{ $tanggal }}</p>
            </td>
        </tr>
    </table>

    {{-- Meta Parameter Simulasi --}}
    <div class="meta-box">
        <table class="meta-table">
            <tr>
                <td class="meta-label">Mata Uang:</td>
                <td class="meta-val">{{ $mata_uang }}</td>
                <td class="meta-label">Kurs Pajak (Kemenkeu):</td>
                <td class="meta-val font-mono">Rp {{ number_format($kurs, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="meta-label">Status Importir:</td>
                <td class="meta-val">
                    @if($status_importir === 'api')
                        <span class="badge badge-green">Memiliki API (PPh 2.5%)</span>
                    @elseif($status_importir === 'non_api')
                        <span class="badge badge-blue">Non-API (PPh 7.5%)</span>
                    @else
                        <span class="badge badge-blue">Non-NPWP (PPh 15.0%)</span>
                    @endif
                </td>
                <td class="meta-label">Skema Fasilitas:</td>
                <td class="meta-val">
                    @if($skema_fasilitas === 'form_e') Form E (ACFTA Preferensi 0%)
                    @elseif($skema_fasilitas === 'form_d') Form D (ATIGA Preferensi 0%)
                    @elseif($skema_fasilitas === 'form_ak') Form AK (AKFTA Preferensi 0%)
                    @elseif($skema_fasilitas === 'kite') KITE / Pembebasan Fasilitas
                    @else Standar / Umum (MFN)
                    @endif
                </td>
            </tr>
            <tr>
                <td class="meta-label">Kategori Komoditas:</td>
                <td class="meta-val">{{ $selectedPreset }}</td>
                <td class="meta-label">Petugas Simulasi:</td>
                <td class="meta-val">{{ $generated_by }}</td>
            </tr>
        </table>
    </div>

    {{-- Nilai Pabean (CIF) Breakdown --}}
    <div class="section-title">1. Dasar Pengenaan Pungutan (Nilai Pabean CIF)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Komponen Nilai</th>
                <th class="text-right">Valuta Asing ({{ $mata_uang }})</th>
                <th class="text-right">Rupiah Equivalent (IDR)</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @if($cifMethod === 'breakdown')
            <tr>
                <td>Nilai FOB (Free on Board)</td>
                <td class="text-right font-mono">{{ number_format($nilai_fob, 2, ',', '.') }}</td>
                <td class="text-right font-mono">Rp {{ number_format(floor($nilai_fob * $kurs), 0, ',', '.') }}</td>
                <td>Harga faktur barang supplier luar negeri</td>
            </tr>
            <tr>
                <td>Biaya Angkut (Freight)</td>
                <td class="text-right font-mono">{{ number_format($nilai_freight, 2, ',', '.') }}</td>
                <td class="text-right font-mono">Rp {{ number_format(floor($nilai_freight * $kurs), 0, ',', '.') }}</td>
                <td>Ongkos angkut laut / udara (B/L / AWB)</td>
            </tr>
            <tr>
                <td>Biaya Asuransi (Insurance)</td>
                <td class="text-right font-mono">{{ number_format($nilai_insurance, 2, ',', '.') }}</td>
                <td class="text-right font-mono">Rp {{ number_format(floor($nilai_insurance * $kurs), 0, ',', '.') }}</td>
                <td>Premi asuransi pengangkutan barang</td>
            </tr>
            @endif
            <tr style="background: #f1f5f9; font-weight: bold;">
                <td>TOTAL NILAI PABEAN (CIF)</td>
                <td class="text-right font-mono" style="color: #2563eb;">{{ number_format($nilai_barang, 2, ',', '.') }}</td>
                <td class="text-right font-mono" style="color: #2563eb;">Rp {{ number_format($nilai_pabean, 0, ',', '.') }}</td>
                <td>Dasar perhitungan Bea Masuk</td>
            </tr>
        </tbody>
    </table>

    {{-- Rincian Pungutan Pabean & PDRI --}}
    <div class="section-title">2. Rincian Pungutan Impor Kas Negara (Billing SIMPONI / SSPCP)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Jenis Pungutan</th>
                <th class="text-center">Tarif (%)</th>
                <th class="text-right">Dasar Pengenaan Pajak (DPP)</th>
                <th class="text-right">Jumlah Bayar (IDR)</th>
                <th>Sifat Pungutan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="font-bold">Bea Masuk (BM)</td>
                <td class="text-center font-mono">{{ $tarif_bm }}%</td>
                <td class="text-right font-mono">Rp {{ number_format($nilai_pabean, 0, ',', '.') }}</td>
                <td class="text-right font-mono font-bold" style="color: #1e40af;">Rp {{ number_format($bayar_bm, 0, ',', '.') }}</td>
                <td>Biaya / Beban Pokok (HPP)</td>
            </tr>
            <tr style="background: #f8fafc; font-style: italic; color: #475569;">
                <td colspan="2">Nilai Impor (CIF + BM)</td>
                <td class="text-right font-mono font-bold" colspan="2">Rp {{ number_format($nilai_impor, 0, ',', '.') }}</td>
                <td>DPP untuk PPN, PPnBM & PPh</td>
            </tr>
            <tr>
                <td class="font-bold">PPN Impor</td>
                <td class="text-center font-mono">{{ $tarif_ppn }}%</td>
                <td class="text-right font-mono">Rp {{ number_format($nilai_impor, 0, ',', '.') }}</td>
                <td class="text-right font-mono font-bold" style="color: #166534;">Rp {{ number_format($bayar_ppn, 0, ',', '.') }}</td>
                <td>Pajak Masukan (Dapat Dikreditkan)</td>
            </tr>
            @if($tarif_ppnbm > 0)
            <tr>
                <td class="font-bold">PPnBM</td>
                <td class="text-center font-mono">{{ $tarif_ppnbm }}%</td>
                <td class="text-right font-mono">Rp {{ number_format($nilai_impor, 0, ',', '.') }}</td>
                <td class="text-right font-mono font-bold" style="color: #b91c1c;">Rp {{ number_format($bayar_ppnbm, 0, ',', '.') }}</td>
                <td>Beban Barang Mewah (Non-Kredit)</td>
            </tr>
            @endif
            <tr>
                <td class="font-bold">PPh Pasal 22 Impor</td>
                <td class="text-center font-mono">{{ $tarif_pph }}%</td>
                <td class="text-right font-mono">Rp {{ number_format($nilai_impor, 0, ',', '.') }}</td>
                <td class="text-right font-mono font-bold" style="color: #166534;">Rp {{ number_format($bayar_pph, 0, ',', '.') }}</td>
                <td>Kredit Pajak PPh Badan (Tahunan)</td>
            </tr>
        </tbody>
    </table>

    {{-- Total Billing Box --}}
    <div class="total-box">
        <div class="total-row">
            <div class="total-left">
                <div style="font-size: 11px; font-weight: bold; color: #1e3a8a; text-transform: uppercase;">Total Billing Setoran Kas Negara (SSPCP)</div>
                <div style="font-size: 9px; color: #64748b;">Jumlah yang dibayar via Bank Persepsi / Pos Indonesia / Penerimaan Negara Bukan Pajak</div>
            </div>
            <div class="total-right">
                <div class="total-amount font-mono">Rp {{ number_format($total_pungutan, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    {{-- Analisis Perpajakan & Cash Flow --}}
    <div class="tax-analysis-box">
        <div class="tax-analysis-title">3. Analisis Perpajakan & Dampak Pembukuan (Tax & Accounting Summary)</div>
        <table style="width: 100%; font-size: 9.5px;">
            <tr>
                <td style="width: 50%; vertical-align: top; padding-right: 10px;">
                    <div style="font-weight: bold; color: #1e293b; margin-bottom: 4px;">A. Komponen Menjadi Beban / HPP Barang:</div>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 2px 0;">&bull; Nilai Pabean (CIF IDR):</td>
                            <td class="text-right font-mono">Rp {{ number_format($nilai_pabean, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 2px 0;">&bull; Bea Masuk (BM):</td>
                            <td class="text-right font-mono">Rp {{ number_format($bayar_bm, 0, ',', '.') }}</td>
                        </tr>
                        @if($bayar_ppnbm > 0)
                        <tr>
                            <td style="padding: 2px 0;">&bull; PPnBM:</td>
                            <td class="text-right font-mono">Rp {{ number_format($bayar_ppnbm, 0, ',', '.') }}</td>
                        </tr>
                        @endif
                        <tr style="border-top: 1px solid #cbd5e1; font-weight: bold;">
                            <td style="padding: 4px 0;">Total Nilai Landed Cost / Persediaan:</td>
                            <td class="text-right font-mono" style="color: #0f172a;">Rp {{ number_format($beban_pokok, 0, ',', '.') }}</td>
                        </tr>
                    </table>
                </td>
                <td style="width: 50%; vertical-align: top; padding-left: 10px; border-left: 1px dashed #cbd5e1;">
                    <div style="font-weight: bold; color: #166534; margin-bottom: 4px;">B. Komponen Menjadi Kredit Pajak (Dapat Diklaim):</div>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 2px 0;">&bull; PPN Masukan (SPT Masa 1111):</td>
                            <td class="text-right font-mono" style="color: #166534;">Rp {{ number_format($kredit_pajak_ppn, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 2px 0;">&bull; Kredit PPh 22 (SPT Badan 1771):</td>
                            <td class="text-right font-mono" style="color: #166534;">Rp {{ number_format($kredit_pajak_pph, 0, ',', '.') }}</td>
                        </tr>
                        <tr style="border-top: 1px solid #cbd5e1; font-weight: bold;">
                            <td style="padding: 4px 0; color: #166534;">Total Kredit Pajak (Dapat Diklaim):</td>
                            <td class="text-right font-mono" style="color: #166534;">Rp {{ number_format($total_kredit_pajak, 0, ',', '.') }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    {{-- Footer & Disclaimer Hukum --}}
    <div class="footer">
        <strong>PEMBERITAHUAN & DISCLAIMER KEPABEANAN:</strong><br>
        1. Dokumen ini merupakan estimasi simulasi perhitungan pabean berdasarkan parameter tarif dan kurs pajak Kemenkeu yang berlaku saat simulasi dibuat.<br>
        2. Besaran pungutan pabean resmi yang mengikat secara hukum akan ditetapkan oleh Pejabat Bea dan Cukai pada saat penerbitan dokumen PIB/SPPB.<br>
        3. Tarif referensi dapat berubah sewaktu-waktu sesuai ketentuan Peraturan Menteri Keuangan (PMK) dan Sistem Indonesia National Single Window (INSW).<br>
        <em>Generated automatically by M2B Portal Engine &bull; Dokumen sah internal PT. MORA MULTI BERKAH</em>
    </div>

</body>
</html>
