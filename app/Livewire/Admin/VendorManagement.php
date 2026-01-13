<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Vendor;
use App\Models\VendorContact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str; 
use Illuminate\Validation\Rule; 
use Illuminate\Database\QueryException;

class VendorManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $filterCategory = '';
    public $isModalOpen = false;
    public $isEditing = false;
    public $editingId = null; 

    // Form Fields Vendor Utama
    public $code, $name, $category = 'Trucking', $customCategory = '';
    public $address, $bank_details, $npwp, $website; 
    
    // Properti Kontak Lama (dibiarkan untuk reset)
    public $pic_name_old, $phone_old, $email_old; 
    
    // Kontak Dinamis (Array untuk PIC baru)
    public $contacts = [];

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'category' => 'required|string',
            'npwp' => 'nullable|string|max:20', 
            'address' => 'nullable|string',
            'bank_details' => 'nullable|string',
            'website' => 'nullable|url|max:255',
            
            // FIX: Validasi kode unik (penting untuk error 1062)
            'code' => [
                'required',
                'string',
                Rule::unique('vendors', 'code')->ignore($this->editingId),
            ],
            
            // Validation for dynamic contact array
            'contacts.*.pic_name' => 'required|string|max:100',
            'contacts.*.email' => 'nullable|email|max:100', 
            'contacts.*.phone' => 'required|string|max:30',
            'contacts.*.role' => 'nullable|string|max:50',
        ];
    }
    
    public function mount()
    {
        $this->addContact(); 
    }

    // --- STATS METHOD ---
    public function getStats()
    {
        return [
            'total' => Vendor::count(),
            'trucking' => Vendor::where('category', 'Trucking')->count(),
            'shipping' => Vendor::where('category', 'Shipping Line')->count(),
            'tps' => Vendor::where('category', 'TPS')->count(),
            'depo' => Vendor::where('category', 'Depo')->count(),
            'ppjk' => Vendor::where('category', 'PPJK')->count(),
            'warehouse' => Vendor::where('category', 'Warehouse')->count(),
            'airline' => Vendor::where('category', 'Airline')->count(),
            'ground_handling' => Vendor::where('category', 'Ground Handling')->count(),
            'operator_pelabuhan' => Vendor::where('category', 'Operator Pelabuhan')->count(),
            'lainnya' => Vendor::whereNotIn('category', ['Trucking', 'Shipping Line', 'PPJK', 'Warehouse', 'Airline', 'Ground Handling', 'TPS', 'Operator Pelabuhan', 'Depo'])->count(),
        ];
    }

    // --- RENDER ---
    public function render()
    {
        $query = Vendor::with('contacts')
            ->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('code', 'like', '%' . $this->search . '%');
            });
        
        // Apply category filter
        if ($this->filterCategory) {
            $query->where('category', $this->filterCategory);
        }
        
        $vendors = $query->orderBy('created_at', 'desc')->paginate(10);
        $stats = $this->getStats();

        return view('livewire.admin.vendor-management', compact('vendors', 'stats'))->layout('layouts.admin');
    }

    public function create()
    {
        $this->resetInputFields();
        $this->addContact();
        $this->isEditing = false;
        $this->isModalOpen = true;
        // FIX: Panggil generator saat create
        $this->code = $this->generateCode(); 
    }

    // FIX LOGIC: Generator yang lebih stabil
    public function generateCode()
    {
        // Cari ID tertinggi yang digunakan (termasuk yang terhapus lunak)
        $maxUsedId = Vendor::withTrashed()->max('id') ?? 0;
        $nextId = $maxUsedId + 1;
        
        $code = 'VEN-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);

        // Safety check: Pastikan kode ini belum ada (penting untuk menghindari race condition)
        while (Vendor::where('code', $code)->exists()) {
             $nextId++;
             $code = 'VEN-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
        }
        
        return $code;
    }

    public function edit($id)
    {
        $vendor = Vendor::with('contacts')->findOrFail($id);
        
        $this->editingId = $id; 
        
        $this->code = $vendor->code;
        $this->name = $vendor->name;
        $this->category = $vendor->category;
        $this->address = $vendor->address;
        $this->bank_details = $vendor->bank_details;
        $this->npwp = $vendor->npwp; 
        $this->website = $vendor->website ?? null; 

        $this->pic_name_old = null; 
        $this->phone_old = null;
        $this->email_old = null;

        $this->contacts = $vendor->contacts->toArray();
        
        if (empty($this->contacts)) {
            $this->addContact(); 
        }

        $this->isEditing = true;
        $this->isModalOpen = true;
    }

    public function save()
    {
        // LIVEWIRE FIX 1: Pastikan minimal ada 1 baris kontak untuk divalidasi
        if (empty($this->contacts)) {
            $this->addContact(); 
        }
        
        // 1. Melakukan validasi. Jika gagal, eksekusi berhenti di sini.
        $this->validate(); 

        try {
            DB::transaction(function () {
                
                $code = $this->code;
                
                // FIX: Jika ini CREATE, pastikan kode yang digunakan adalah unik terakhir
                if (!$this->isEditing) {
                    $code = $this->generateCode(); 
                }
                
                $vendorData = [
                    'code' => $code, // Menggunakan kode yang terjamin unik
                    'name' => $this->name,
                    'category' => ($this->category === 'custom' && $this->customCategory) ? $this->customCategory : $this->category,
                    'address' => $this->address,
                    'bank_details' => $this->bank_details,
                    'npwp' => $this->npwp, 
                    'website' => $this->website,
                ];

                if ($this->isEditing) {
                    $vendor = Vendor::find($this->editingId);
                    $vendor->update($vendorData);
                    
                    $vendor->contacts()->delete();
                    
                } else {
                    $vendor = Vendor::create($vendorData); // Baris 126
                }

                // --- FIX: Simpan Kontak Baru menggunakan Relasi HasMany ---
                foreach ($this->contacts as $index => $contactData) {
                    
                    $filteredContactData = array_filter($contactData, fn($value) => !is_null($value) && $value !== '');

                    $filteredContactData['is_primary'] = ($index === 0);
                    
                    $vendor->contacts()->create($filteredContactData);
                }
                // --------------------------------------------------------

            });

            session()->flash('message', 'Data Vendor berhasil disimpan.');
            $this->closeModal();
            
        } catch (QueryException $e) {
            // Memberi pesan error yang lebih jelas
            if (Str::contains($e->getMessage(), '1048 Column')) {
                 session()->flash('error', 'GAGAL SAVE: Ada kolom yang wajib diisi (NOT NULL) di database yang tidak terkirim dari formulir. Detil: ' . $e->getMessage());
            } else if (Str::contains($e->getMessage(), '1062 Duplicate entry')) {
                 // FIX ERROR 1062
                 session()->flash('error', 'GAGAL SAVE: Kode Vendor (' . $this->code . ') sudah ada. Silakan buat vendor baru.');
                 // Re-generate kode baru agar modal siap diisi lagi
                 $this->code = $this->generateCode();
            } else {
                 session()->flash('error', 'Terjadi kesalahan database: ' . $e->getMessage());
            }

        } catch (\Exception $e) {
            session()->flash('error', 'Terjadi kesalahan sistem Livewire: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            DB::transaction(function () use ($id) {
                 $vendor = Vendor::with('jobCosts', 'contacts')->find($id);

                 if (!$vendor) {
                     session()->flash('error', 'Vendor tidak ditemukan.');
                     return;
                 }

                 if ($vendor->jobCosts()->exists()) {
                     session()->flash('error', 'GAGAL: Vendor ini tidak bisa dihapus karena sudah memiliki riwayat transaksi Job Costing.');
                     return;
                 }
                 
                 $vendor->contacts()->delete();
                 $vendor->delete();
                 session()->flash('message', 'Vendor berhasil dihapus.');
            });

        } catch (\Exception $e) {
            session()->flash('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }

    // --- UTILS ---
    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->resetInputFields();
        $this->resetValidation();
    }

    private function resetInputFields()
    {
        $this->code = ''; $this->name = ''; $this->category = 'Trucking'; $this->customCategory = '';
        $this->address = ''; $this->bank_details = '';
        $this->editingId = null;
        $this->npwp = null; 
        $this->website = null;
        $this->reset(['contacts', 'pic_name_old', 'phone_old', 'email_old']);
        $this->addContact();
    }
    
    // --- LOGIC KONTAK PIC DINAMIS ---

    public function addContact()
    {
        $this->contacts[] = [
            'pic_name' => '',
            'phone' => '',
            'email' => '',
            'role' => '',
            'is_primary' => false,
        ];
    }

    public function removeContact($index)
    {
        unset($this->contacts[$index]);
        $this->contacts = array_values($this->contacts); 

        if (empty($this->contacts)) {
            $this->addContact();
        }
    }
}