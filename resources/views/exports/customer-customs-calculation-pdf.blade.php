<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Proforma Estimasi Pabean & Pajak Impor - PT. MORA MULTI BERKAH</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10px; color: #1e293b; background: #ffffff; padding: 25px 30px; line-height: 1.4; }
        
        .header-table { width: 100%; border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 15px; }
        .company-name { font-size: 16px; font-weight: bold; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px; }
        .company-sub { font-size: 8.5px; color: #475569; margin-top: 2px; }
        .doc-title { text-align: right; }
        .doc-title h2 { font-size: 14px; font-weight: bold; color: #2563eb; text-transform: uppercase; letter-spacing: 0.5px; }
        .doc-title p { font-size: 8.5px; color: #64748b; margin-top: 2px; }

        .client-box { width: 100%; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 12px; margin-bottom: 12px; }
        .client-table { width: 100%; }
        .client-table td { font-size: 9.5px; padding: 2px 4px; }
        .client-label { color: #64748b; font-weight: bold; width: 18%; }
        .client-val { color: #0f172a; font-weight: bold; width: 32%; }

        .meta-box { width: 100%; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px 14px; margin-bottom: 14px; }
        .meta-table { width: 100%; }
        .meta-table td { padding: 3px 6px; font-size: 9.5px; }
        .meta-label { color: #64748b; font-weight: bold; width: 22%; }
        .meta-val { color: #0f172a; font-weight: bold; width: 28%; }

        .section-title { font-size: 11px; font-weight: bold; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; padding-bottom: 3px; border-bottom: 1.5px solid #3b82f6; }

        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.data-table th { background: #0f172a; color: #ffffff; font-size: 9px; text-transform: uppercase; padding: 6px 10px; text-align: left; letter-spacing: 0.5px; }
        table.data-table td { padding: 6px 10px; border-bottom: 1px solid #e2e8f0; font-size: 9.5px; }
        table.data-table tr:nth-child(even) td { background: #f8fafc; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: 'Courier New', Courier, monospace; }
        .font-bold { font-weight: bold; }

        .total-box { background: #eff6ff; border: 1.5px solid #bfdbfe; border-radius: 6px; padding: 10px 14px; margin-bottom: 14px; }
        .total-row { display: table; width: 100%; }
        .total-left { display: table-cell; vertical-align: middle; }
        .total-right { display: table-cell; text-align: right; vertical-align: middle; }
        .total-amount { font-size: 18px; font-weight: bold; color: #1d4ed8; }

        .cta-box { background: #fefce8; border: 1px solid #fef08a; border-radius: 6px; padding: 10px 14px; margin-bottom: 14px; }
        .cta-title { font-size: 10px; font-weight: bold; color: #854d0e; text-transform: uppercase; margin-bottom: 4px; }

        .footer { margin-top: 18px; padding-top: 8px; border-top: 1px solid #e2e8f0; font-size: 8px; color: #64748b; line-height: 1.35; }
        .badge { display: inline-block; padding: 2px 6px; font-size: 8px; font-weight: bold; border-radius: 4px; text-transform: uppercase; }
        .badge-blue { background: #dbeafe; color: #1e40af; }
        .badge-green { background: #dcfce7; color: #166534; }
    </style>
</head>
<body>

    {{-- Header Perusahaan & Dokumen --}}
    <table class="header-table">
        <tr>
            <td style="width: 58%; vertical-align: middle;">
                <div class="company-name">PT. MORA MULTI BERKAH</div>
                <div class="company-sub">International Freight Forwarding, PPJK & Customs Clearance</div>
                <div class="company-sub">Website: https://portal.m2b.co.id &bull; Email: sales@m2b.co.id &bull; Telp: 061-44020012</div>
            </td>
            <td class="doc-title" style="width: 42%; vertical-align: middle;">
                <h2>Estimasi Pungutan Impor</h2>
                <p>Simulasi Estimasi Tagihan Pabean Klien</p>
                <p>Tanggal Cetak: {{ $tanggal }}</p>
            </td>
        </tr>
    </table>

    {{-- Info Klien / Pemohon --}}
    <div class="client-box">
        <table class="client-table">
            <tr>
                <td class="client-label">Nama Klien / Perusahaan:</td>
                <td class="client-val">{{ $customer->company_name ?? ($user->name ?? 'Klien Terdaftar') }}</td>
                <td class="client-label">PIC / Kontak:</td>
                <td class="client-val">{{ $user->email ?? '-' }}</td>
            </tr>
            <tr>
                <td class="client-label">Status Importir:</td>
                <td class="client-val">
                    @if($status_importir === 'api')
                        <span class="badge badge-green">Importir Ber-API (PPh 2.5%)</span>
                    @elseif($status_importir === 'non_api')
                        <span class="badge badge-blue">Non-API / Umum (PPh 7.5%)</span>
                    @else
                        <span class="badge badge-blue">Non-NPWP (PPh 15.0%)</span>
                    @endif
                </td>
                <td class="client-label">Skema Perdagangan / Fasilitas:</td>
                <td class="client-val">
                    @if($skema_fasilitas === 'form_e') Form E (ACFTA China - BM 0%)
                    @elseif($skema_fasilitas === 'form_d') Form D (ATIGA ASEAN - BM 0%)
                    @elseif($skema_fasilitas === 'form_ak') Form AK (AKFTA Korea - BM 0%)
                    @elseif($skema_fasilitas === 'kite') KITE / Fasilitas Berikat
                    @else Standar / Umum (MFN)
                    @endif
                </td>
            </tr>
        </table>
    </div>

    {{-- Parameter Simulasi Transaksi --}}
    <div class="section-title">01. Dasar Penghitungan Nilai Pabean (CIF)</div>
    <div class="meta-box">
        <table class="meta-table">
            @if(($cifMethod ?? 'direct') === 'breakdown')
            <tr>
                <td class="meta-label">Nilai FOB (Invoice Barang):</td>
                <td class="meta-val font-mono">{{ $mata_uang }} {{ number_format($nilai_fob, 2, ',', '.') }}</td>
                <td class="meta-label">Ongkos Angkut (Freight):</td>
                <td class="meta-val font-mono">{{ $mata_uang }} {{ number_format($nilai_freight, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="meta-label">Asuransi (Insurance):</td>
                <td class="meta-val font-mono">{{ $mata_uang }} {{ number_format($nilai_insurance, 2, ',', '.') }} ({{ $insurance_type === 'pmk_dn' ? 'Rumus DN' : 'Manual Polis' }})</td>
                <td class="meta-label">Total Nilai CIF:</td>
                <td class="meta-val font-mono font-bold text-blue-700">{{ $mata_uang }} {{ number_format($nilai_barang, 2, ',', '.') }}</td>
            </tr>
            @else
            <tr>
                <td class="meta-label">Nilai Transaksi (CIF):</td>
                <td class="meta-val font-mono font-bold">{{ $mata_uang }} {{ number_format($nilai_barang, 2, ',', '.') }}</td>
                <td class="meta-label">Kategori Barang:</td>
                <td class="meta-val">{{ $selectedPreset }}</td>
            </tr>
            @endif
            <tr>
                <td class="meta-label">Kurs Pajak Kemenkeu:</td>
                <td class="meta-val font-mono">Rp {{ number_format($kurs, 2, ',', '.') }} / {{ $mata_uang }}</td>
                <td class="meta-label">Nilai Pabean (IDR):</td>
                <td class="meta-val font-mono font-bold" style="color: #1e3a8a;">Rp {{ number_format($nilai_pabean, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    {{-- Tabel Rincian Pungutan Pabean --}}
    <div class="section-title">02. Rincian Estimasi Pungutan Negara (Billing DJBC)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 35%;">Jenis Pungutan / Pajak</th>
                <th style="width: 25%;">Dasar Pengenaan Pajak (DPP)</th>
                <th style="width: 12%;" class="text-center">Tarif</th>
                <th style="width: 23%;" class="text-right">Estimasi Tagihan (IDR)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center">1</td>
                <td><strong>Bea Masuk (BM)</strong></td>
                <td class="font-mono">Rp {{ number_format($nilai_pabean, 0, ',', '.') }}</td>
                <td class="text-center font-bold">{{ $tarif_bm }}%</td>
                <td class="text-right font-mono font-bold">Rp {{ number_format($bayar_bm, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="text-center">2</td>
                <td><strong>PPN Impor</strong></td>
                <td class="font-mono">Rp {{ number_format($nilai_impor, 0, ',', '.') }}</td>
                <td class="text-center font-bold">{{ $tarif_ppn }}%</td>
                <td class="text-right font-mono font-bold">Rp {{ number_format($bayar_ppn, 0, ',', '.') }}</td>
            </tr>
            @if($tarif_ppnbm > 0)
            <tr>
                <td class="text-center">3</td>
                <td><strong>PPnBM (Barang Mewah)</strong></td>
                <td class="font-mono">Rp {{ number_format($nilai_impor, 0, ',', '.') }}</td>
                <td class="text-center font-bold">{{ $tarif_ppnbm }}%</td>
                <td class="text-right font-mono font-bold">Rp {{ number_format($bayar_ppnbm, 0, ',', '.') }}</td>
            </tr>
            @endif
            <tr>
                <td class="text-center">{{ $tarif_ppnbm > 0 ? 4 : 3 }}</td>
                <td><strong>PPh Pasal 22 Impor</strong></td>
                <td class="font-mono">Rp {{ number_format($nilai_impor, 0, ',', '.') }}</td>
                <td class="text-center font-bold">{{ $tarif_pph }}%</td>
                <td class="text-right font-mono font-bold">Rp {{ number_format($bayar_pph, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Total Setoran --}}
    <div class="total-box">
        <div class="total-row">
            <div class="total-left">
                <div style="font-size: 11px; font-weight: bold; color: #1e3a8a; text-transform: uppercase;">Total Estimasi Setoran Kas Negara</div>
                <div style="font-size: 8.5px; color: #475569;">Pungutan resmi atas nama importir yang disetorkan via Kode Billing SIMPONI / DJBC</div>
            </div>
            <div class="total-right">
                <div class="total-amount font-mono">Rp {{ number_format($total_pungutan, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    {{-- CTA & Layanan M2B --}}
    <div class="cta-box">
        <div class="cta-title">🚢 Siap Mengirimkan Barang Ini? Tim Ahli M2B Siap Membantu Anda!</div>
        <p style="font-size: 8.5px; color: #713f12; line-height: 1.4;">
            M2B melayani pengurusan izin impor (PI/Lartas), pengurusan sertifikat FTA (Form E / Form D agar Bea Masuk 0%), pengapalan laut/udara internasional (FCL/LCL), hingga custom clearance door-to-door. 
            Hubungi Account Manager M2B Anda atau buat booking langsung di portal kami: <strong>https://portal.m2b.co.id/customer/shipments/create</strong>.
        </p>
    </div>

    {{-- Catatan & Disclaimer --}}
    <div class="footer">
        <p><strong>Catatan & Ketentuan:</strong></p>
        <p>1. Perhitungan ini merupakan proforma simulasi berdasarkan Kurs Pajak Kemenkeu yang berlaku saat dokumen ini diterbitkan. Nilai ketetapan resmi yang mengikat diterbitkan oleh Direktorat Jenderal Bea dan Cukai (DJBC) pada lembar Billing PIB / SPPB.</p>
        <p>2. Simulasi belum mencakup biaya operasional pelabuhan (THC, Lift On/Off, Storage/Demurrage, Trucking) dan jasa PPJK M2B. Silakan minta penawaran lengkap (All-In / Door-to-Door) kepada tim sales M2B.</p>
        <p>3. Dokumen ini sah dicetak otomatis melalui sistem Portal M2B dan tidak memerlukan tanda tangan basah.</p>
    </div>

</body>
</html>
