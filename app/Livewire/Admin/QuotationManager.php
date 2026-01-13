<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Quotation;
use App\Models\Customer;
use App\Models\QuotationItem;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Mail\QuotationMail;

class QuotationManager extends Component
{
    use WithPagination;

    public $search = '';
    public $isModalOpen = false;
    public $isEditing = false;
    public $editingId = null;
    
    public $isSendModalOpen = false;
    public $sendToEmail = '';
    public $sendingId = null;
    
    public $is_new_customer = false;
    public $customer_id;
    public $manual_company, $manual_pic, $manual_email, $manual_phone;
    
    public $quotation_date;
    public $valid_until;
    public $origin;
    public $destination;
    public $service_type = 'import';
    public $notes = ''; 
    public $terbilang_lang = 'id';
    
    public $ppn_rate = 11;
    public $pph_rate = 0;
    public $items = [];
    public $serviceTotal = 0;
    public $reimbursementTotal = 0;
    public $ppn = 0;
    public $pph = 0;
    public $grandTotal = 0;
    public $filterStatus = "";
    public $filterDateFrom = "";
    public $filterDateTo = "";


    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterStatus() { $this->resetPage(); }

    public function getStats()
    {
        return [
            "total" => \App\Models\Quotation::count(),
            "draft" => \App\Models\Quotation::where("status", "draft")->count(),
            "sent" => \App\Models\Quotation::where("status", "sent")->count(),
            "accepted" => \App\Models\Quotation::where("status", "accepted")->count(),
            "expired" => \App\Models\Quotation::where("valid_until", "<", now())->count(),
            "expiring_soon" => \App\Models\Quotation::where("valid_until", "<=", now()->addDays(7))->where("valid_until", ">=", now())->count(),
            "total_value" => \App\Models\Quotation::sum("grand_total"),
        ];
    }


    public function mount()
    {
        $this->quotation_date = date('Y-m-d');
        $this->valid_until = date('Y-m-d', strtotime('+14 days'));
        $this->items = [['item_type' => 'service', 'description' => 'Jasa Freight', 'qty' => 1, 'price' => 0]];
    }

    public function changeServiceType($value) { 
        $this->service_type = $value;
        $this->setDefaultNotes($value); 
        // Paksa update tampilan editor
        $this->dispatch('set-editor-content', content: $this->notes);
    }

    private function setDefaultNotes($type)
    {
        $signature = "<br><br>Disetujui Oleh / Approved By:<br><br><br><br>( ...................................................... )<br>Nama & Stempel Perusahaan";

        if ($type == 'import') {
            $this->notes = "<div><strong>SYARAT & KETENTUAN (TERMS & CONDITIONS):</strong></div><ol><li>Biaya yang akan ditagih ke Customer adalah sbb:<ul><li>Biaya tersebut diatas belum termasuk PPN 11%, Pajak Impor, Biaya D/O, Biaya LOLO, Sewa gudang (Penumpukan), dan Behandle.</li><li>Pungutan Impor akan dibayar langsung oleh pemilik barang sesuai Billing yang diterbitkan Bea Cukai.</li><li>Biaya Nota Pembetulan / Notul (jika ada) dari Bea Cukai yang timbul menjadi tanggung jawab pihak pemilik barang.</li><li>Harga diatas diluar Biaya Muat dan Biaya Asuransi Barang.</li><li>Pembayaran DP minimal 50% dari harga tagihan.</li><li>Biaya-Biaya lainnya akan ditagihkan sesuai tagihan / invoice.</li><li>Biaya resmi yang dibayarkan kepada Pihak Ketiga, bersifat Reimbursement tidak boleh dipotong PPH.</li></ul></li><li>Masa pembayaran tagihan harus dilunasi setelah barang SPPB (Barang sudah bisa keluar dari Gudang Penumpukan).</li><li>Pemilik barang bersedia datang ke kantor Bea Cukai jika diperlukan dalam hal pejabat Bea Cukai membutuhkan keterangan langsung dari pihak pemilik barang.</li></ol>" . $signature;
        } elseif ($type == 'export') {
            $this->notes = "<div><strong>SYARAT & KETENTUAN (TERMS & CONDITIONS):</strong></div><ol><li>Biaya yang akan ditagih ke eksportir sbb:<ul><li>Biaya Trucking akan ditagih sesuai tujuan gudang/ warehouse eksportir.</li><li>Biaya D/O Pelayaran sesuai tagihan/ invoice.</li><li>Final Dokumen harus kami terima minimal 5 hari sebelum kapal berangkat atau kami anggap sebagai keterlambatan dokumen.</li><li>Bea keluar dan Dana Pungutan Sawit akan dibayar langsung oleh eksportir sesuai Billing yang diterbitkan oleh Bea Cukai.</li><li>Biaya Nota Pembetulan/ Notul (jika ada) dari Bea Cukai yang timbul menjadi tanggung jawab pihak eksportir.</li><li>Biaya Lift On Lift Off (LOLO) dalam hal pengembalian empty container ke depo sesuai tagihan/ invoice.</li><li>Biaya THC (Terminal Handling Charge) jika dalam kondisi FOB dibebankan ke pihak eksportir.</li></ul></li><li>Masa pembayaran tagihan harus dilunasi setelah barang NPE (Nota Persetujuan Ekspor).</li><li>Pembayaran di depan sebesar 50% dari estimasi biaya clearance/ total tagihan yang sudah disepakati.</li></ol>" . $signature;
        } else { 
            $this->notes = "<div><strong>SYARAT & KETENTUAN DOMESTIK:</strong></div><ol><li>Biaya yang akan ditagih ke Customer adalah sbb:<ul><li>Harga di atas BELUM TERMASUK (Exclude) PPN 11% dan Asuransi Pengiriman Barang (Barang tidak diasuransikan kecuali ada permintaan tertulis).</li><li>Belum termasuk biaya Tenaga Kerja Bongkar/Muat (Kuli/Labor) di lokasi pengirim/penerima.</li><li>Belum termasuk biaya Inap Truk (Overnight) jika proses bongkar/muat melebihi batas waktu yang ditentukan.</li><li>Biaya resmi lainnya akan ditagihkan sesuai bukti tagihan (at cost).</li></ul></li><li>Pengiriman bersifat Door to Door (Trucking / Kargo).</li><li>Klaim kerusakan/kehilangan hanya dapat diproses jika ada Berita Acara Serah Terima (BAST) yang ditandatangani kedua belah pihak.</li><li>Estimasi waktu pengiriman (Lead Time) tergantung kondisi lalu lintas dan jadwal penyeberangan.</li><li>Pembayaran DP minimal 50% dari total tagihan saat konfirmasi order.</li></ol>" . $signature;
        }
    }

    public function create()
    {
        $this->resetInput();
        $this->isEditing = false;
        $this->is_new_customer = false;
        $this->quotation_date = now()->format('Y-m-d');
        $this->valid_until = now()->addDays(14)->format('Y-m-d');
        $this->items = [['item_type' => 'service', 'description' => 'Jasa Freight', 'qty' => 1, 'price' => 0]];
        $this->setDefaultNotes('import');
        $this->calculate();
        $this->isModalOpen = true;
        $this->dispatch('set-editor-content', content: $this->notes);
    }

    public function edit($id)
    {
        $q = Quotation::with('items')->find($id);
        if($q) {
            $this->isEditing = true;
            $this->editingId = $id;
            $this->quotation_date = $q->quotation_date->format('Y-m-d');
            $this->valid_until = $q->valid_until->format('Y-m-d');
            $this->origin = $q->origin;
            $this->destination = $q->destination;
            $this->service_type = $q->service_type;
            
            // Pastikan Notes diambil sebagai string
            $this->notes = (string) $q->notes; 
            $this->terbilang_lang = $q->terbilang_lang ?? 'id';

            if ($q->customer_id) {
                $this->is_new_customer = false;
                $this->customer_id = $q->customer_id;
            } else {
                $this->is_new_customer = true;
                $this->manual_company = $q->manual_company;
                $this->manual_pic = $q->manual_pic;
                $this->manual_email = $q->manual_email;
                $this->manual_phone = $q->manual_phone;
            }
            
            $this->items = [];
            foreach($q->items as $item) {
                $this->items[] = [
                    'item_type' => $item->item_type ?? 'service',
                    'description' => $item->description,
                    'qty' => $item->qty,
                    'price' => $item->price
                ];
            }
            $this->calculate();
            $this->isModalOpen = true;
            
            $this->dispatch('set-editor-content', content: $this->notes);
        }
    }

    public function save()
    {
        // 1. Validasi Input
        $rules = [
            'quotation_date' => 'required|date',
            'origin' => 'required|string',
            'destination' => 'required|string',
            'items' => 'required|array|min:1',
            // Hapus validasi array dalam item dulu agar tidak crash di sini
        ];

        // Logika Customer
        $cleanCustomerId = null;
        $cleanManualCompany = null; $cleanManualPic = null; $cleanManualEmail = null; $cleanManualPhone = null;

        if ($this->is_new_customer) {
            $this->validate([
                'manual_company' => 'required|string',
                'manual_pic' => 'required|string',
                'manual_email' => 'required|email',
            ]);
            $cleanManualCompany = (string) $this->manual_company;
            $cleanManualPic = (string) $this->manual_pic;
            $cleanManualEmail = (string) $this->manual_email;
            $cleanManualPhone = (string) $this->manual_phone;
        } else {
            $this->validate(['customer_id' => 'required']);
            // Paksa Ambil ID Integer
            if (is_array($this->customer_id)) {
                $cleanCustomerId = (int) ($this->customer_id['id'] ?? 0);
            } else {
                $cleanCustomerId = (int) $this->customer_id;
            }
        }

        $this->validate($rules);
        $this->calculate(); // Pastikan total dihitung ulang

        // 2. DATA UTAMA (Clean Data)
        $data = [
            'customer_id'       => $cleanCustomerId,
            'manual_company'    => $cleanManualCompany,
            'manual_pic'        => $cleanManualPic,
            'manual_email'      => $cleanManualEmail,
            'manual_phone'      => $cleanManualPhone,
            
            'quotation_date'    => (string)$this->quotation_date,
            'valid_until'       => (string)$this->valid_until,
            'origin'            => (string)$this->origin,
            'destination'       => (string)$this->destination,
            'service_type'      => (string)$this->service_type,
            
            'service_total'     => (float)$this->serviceTotal,
            'reimbursement_total' => (float)$this->reimbursementTotal,
            'subtotal'          => (float)($this->serviceTotal + $this->reimbursementTotal),
            'tax_amount'        => (float)$this->ppn,
            'pph_amount'        => (float)$this->pph,
            'grand_total'       => (float)$this->grandTotal,
            
            // Pastikan Notes String
            'notes'             => is_string($this->notes) ? $this->notes : (string)$this->notes,
            'terbilang_lang'    => $this->terbilang_lang,
        ];

        // 3. PROSES DATABASE UTAMA
        if ($this->isEditing) {
            $q = Quotation::find($this->editingId);
            $q->update($data);
            $q->items()->delete(); // Hapus item lama
            $qId = $q->id;
            $msg = 'Quotation Updated!';
        } else {
            // Generator Nomor Anti-Duplikat
            $lastQ = Quotation::whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->orderBy('id', 'desc')
                ->first();
            
            $lastNum = 0;
            if($lastQ) {
                $parts = explode('.', $lastQ->quotation_number);
                $lastNum = (int)end($parts);
            }
            $data['quotation_number'] = 'QT.' . now()->format('Y.m') . '.' . str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
            $data['status'] = 'sent';
            
            $q = Quotation::create($data);
            $qId = $q->id;
            $msg = 'Quotation Created: ' . $data['quotation_number'];
        }

        // 4. PROSES ITEM (INI YANG SERING ERROR)
        foreach ($this->items as $item) {
            // Paksa casting tipe data satu per satu
            QuotationItem::create([
                'quotation_id' => $qId,
                'item_type'    => (string) ($item['item_type'] ?? 'service'),
                'description'  => (string) ($item['description'] ?? '-'),
                'qty'          => (float) ($item['qty'] ?? 0),
                'price'        => (float) ($item['price'] ?? 0),
                'total'        => (float) (($item['qty']??0) * ($item['price']??0)),
            ]);
        }

        session()->flash('message', $msg);
        $this->closeModal();
    }

    // --- UTILS ---
    public function resetInput() { $this->reset(['customer_id', 'manual_company', 'manual_pic', 'manual_email', 'manual_phone', 'items', 'origin', 'destination', 'service_type', 'notes', 'terbilang_lang', 'editingId', 'isEditing', 'isSendModalOpen']); }
    public function closeModal() { $this->isModalOpen = false; }
    public function addItem() { $this->items[] = ['item_type' => 'service', 'description' => '', 'qty' => 1, 'price' => 0]; }
    public function removeItem($index) { unset($this->items[$index]); $this->items = array_values($this->items); $this->calculate(); }
    public function recalculate() { $this->calculate(); }
    public function calculate() {
        $this->serviceTotal = 0; $this->reimbursementTotal = 0;
        foreach ($this->items as $item) {
            $amount = (float)$item['qty'] * (float)$item['price'];
            if (($item['item_type'] ?? 'service') == 'service') $this->serviceTotal += $amount; else $this->reimbursementTotal += $amount;
        }
        $this->ppn = $this->serviceTotal * ($this->ppn_rate / 100); $this->pph = $this->serviceTotal * ($this->pph_rate / 100);
        $this->grandTotal = ($this->serviceTotal + $this->ppn - $this->pph) + $this->reimbursementTotal;
    }
    public function delete($id) { if(Quotation::find($id)->delete()) session()->flash('message', 'Deleted.'); }
    
    public function convertToShipment($id) { 
        $q = Quotation::find($id); if (!$q) return;
        $customerId = $q->customer_id;
        if (!$customerId) { 
             $email = $q->manual_email; $existingUser = null;
             if($email) { $existingUser = User::where('email', $email)->first(); }
             if ($existingUser) {
                $existingCustomer = Customer::where('user_id', $existingUser->id)->first();
                $customerId = $existingCustomer ? $existingCustomer->id : Customer::create(['user_id'=>$existingUser->id, 'customer_code'=>Customer::generateCustomerCode(), 'company_name'=>$q->manual_company, 'phone'=>$q->manual_phone, 'address'=>'-', 'city'=>'Indonesia'])->id;
             } else {
                $newUser = User::create(['name'=>$q->manual_pic??'New','email'=>$email?:'cl'.time().'@m.co','password'=>Hash::make('12345678'),'role'=>'customer']);
                $customerId = Customer::create(['user_id'=>$newUser->id, 'customer_code'=>Customer::generateCustomerCode(), 'company_name'=>$q->manual_company, 'phone'=>$q->manual_phone, 'address'=>'-', 'city'=>'Indonesia'])->id;
             }
             $q->update(['customer_id'=>$customerId, 'manual_company'=>null, 'manual_pic'=>null, 'manual_email'=>null, 'manual_phone'=>null]);
        }
        $prefix = match($q->service_type) { 'import'=>'IMP', 'export'=>'EXP', 'domestic'=>'DOM', default=>'JOB' };
        $newRef = $prefix . '-' . date('ymd') . '-' . rand(100,999);
        Shipment::create(['customer_id'=>$customerId, 'quotation_id'=>$q->id, 'awb_number'=>$newRef, 'origin'=>$q->origin, 'destination'=>$q->destination, 'service_type'=>$q->service_type, 'shipment_type'=>'sea', 'status'=>'pending', 'weight'=>0, 'pieces'=>0, 'notes'=>'Generated from QT']);
        $q->update(['status'=>'accepted']);
        return redirect()->route('admin.shipments.index');
    }
    public function openSendModal($id) { $q = Quotation::find($id); if($q){ $this->sendingId = $id; $this->sendToEmail = $q->customer ? ($q->customer->user->email ?? $q->customer->email) : $q->manual_email; $this->isSendModalOpen = true; } }
    public function closeSendModal() { $this->isSendModalOpen = false; }
    public function sendQuotation() { $q = Quotation::find($this->sendingId); if($q) Mail::to($this->sendToEmail)->send(new QuotationMail($q)); $this->closeSendModal(); }
    public function render() {
        $query = Quotation::with("customer");
        
        // Search
        if ($this->search) {
            $query->where(function($q) {
                $q->where("quotation_number", "like", "%".$this->search."%")
                  ->orWhere("manual_company", "like", "%".$this->search."%")
                  ->orWhereHas("customer", function($c) {
                      $c->where("company_name", "like", "%".$this->search."%");
                  });
            });
        }
        
        // Filter by status
        if ($this->filterStatus) {
            if ($this->filterStatus === "expired") {
                $query->where("valid_until", "<", now());
            } else {
                $query->where("status", $this->filterStatus);
            }
        }
        
        $quotations = $query->latest()->paginate(10);
        $customers = Customer::orderBy("company_name")->get();
        $stats = $this->getStats();
        
        return view("livewire.admin.quotation-manager", [
            "quotations" => $quotations,
            "customers" => $customers,
            "stats" => $stats
        ])->layout("layouts.admin");
    }


    // ========== MASTER PRODUCT INTEGRATION ==========
    public $productSuggestions = [];
    public $activeProductIndex = null;
    
    public function searchProducts($index, $query)
    {
        $this->activeProductIndex = $index;
        
        if (strlen($query) < 2) {
            $this->productSuggestions = [];
            return;
        }
        
        $this->productSuggestions = \App\Models\Product::where('is_active', true)
            ->where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('code', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->orderBy('sort_order')
            ->limit(8)
            ->get(['id', 'code', 'name', 'category', 'default_price', 'description'])
            ->toArray();
    }
    
    public function selectProduct($index, $productId)
    {
        $product = \App\Models\Product::find($productId);
        
        if ($product) {
            $this->items[$index]['description'] = $product->name;
            $this->items[$index]['price'] = $product->default_price;
            $this->items[$index]['product_id'] = $product->id;
            $this->items[$index]['product_code'] = $product->code;
        }
        
        $this->productSuggestions = [];
        $this->activeProductIndex = null;
        $this->calculate();
    }
    
    public function clearSuggestions()
    {
        $this->productSuggestions = [];
        $this->activeProductIndex = null;
    }
    
    public function getPopularProducts()
    {
        return \App\Models\Product::where('is_active', true)
            ->orderBy('sort_order')
            ->limit(6)
            ->get(['id', 'code', 'name', 'default_price']);
    }
    // ========== END MASTER PRODUCT INTEGRATION ==========

    // ========== QUICK SELECT PRODUCT PANEL ==========
    public $showProductPanel = true;
    
    public function getProductsByCategory()
    {
        return \App\Models\Product::where('is_active', true)
            ->orderBy('category')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('category');
    }
    
    public function quickAddProduct($productId)
    {
        $product = \App\Models\Product::find($productId);
        
        if ($product) {
            $this->items[] = [
                'item_type' => 'service',
                'description' => $product->name,
                'qty' => 1,
                'price' => $product->default_price,
                'product_id' => $product->id,
                'product_code' => $product->code,
            ];
            $this->calculate();
        }
    }
    
    public function toggleProductPanel()
    {
        $this->showProductPanel = !$this->showProductPanel;
    }
    // ========== END QUICK SELECT PRODUCT PANEL ==========
}