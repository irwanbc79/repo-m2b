<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;

class ManageVendors extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public $search = '';
    public $isModalOpen = false;
    public $isEditing = false;
    public $vendorId = null;

    // Vendor Main Form (code hanya DISPLAY, bukan sumber data)
    public $code, $name, $category, $address, $npwp, $website, $bank_details;

    // Vendor Contacts
    public $contacts = [];

    /* =========================
     |  VALIDATION RULES
     |  (TIDAK ADA code di sini)
     ========================= */
    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'address' => 'nullable|string',
            'npwp' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'bank_details' => 'nullable|string',

            'contacts' => 'required|array|min:1',
            'contacts.*.pic_name' => 'required|string|max:100',
            'contacts.*.email' => 'required|email|max:100',
            'contacts.*.phone' => 'required|string|max:30',
            'contacts.*.role' => 'nullable|string|max:50',
        ];
    }

    /* =========================
     |  LIFECYCLE
     ========================= */
    public function mount()
    {
        $this->resetForm();
    }

    public function render()
    {
        $vendors = Vendor::with('contacts')
            ->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('category', 'like', '%' . $this->search . '%')
                  ->orWhere('code', 'like', '%' . $this->search . '%');
            })
            ->latest()
            ->paginate(10);

        return view('livewire.admin.vendor-form', compact('vendors'))
            ->layout('layouts.admin');
    }

    /* =========================
     |  ACTIONS
     ========================= */
    public function create()
    {
        $this->resetForm();

        // CODE hanya untuk ditampilkan di UI
        $this->code = $this->generateVendorCode();

        $this->isEditing = false;
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        $vendor = Vendor::with('contacts')->findOrFail($id);

        $this->vendorId     = $vendor->id;
        $this->code         = $vendor->code; // immutable
        $this->name         = $vendor->name;
        $this->category     = $vendor->category;
        $this->address      = $vendor->address;
        $this->npwp         = $vendor->npwp;
        $this->website      = $vendor->website;
        $this->bank_details = $vendor->bank_details;

        $this->contacts = $vendor->contacts->toArray();
        if (empty($this->contacts)) {
            $this->addContact();
        }

        $this->isEditing = true;
        $this->isModalOpen = true;
    }

    public function save()
    {
        $this->validate();

        try {
            DB::transaction(function () {

                if ($this->isEditing) {
                    // ===== EDIT MODE (CODE TIDAK DIUBAH) =====
                    $vendor = Vendor::findOrFail($this->vendorId);

                    $vendor->update([
                        'name'         => $this->name,
                        'category'     => $this->category,
                        'address'      => $this->address,
                        'npwp'         => $this->npwp,
                        'website'      => $this->website,
                        'bank_details' => $this->bank_details,
                    ]);

                    $vendor->contacts()->delete();

                } else {
                    // ===== CREATE MODE (CODE DIBUAT DI SERVER) =====
                    $vendor = Vendor::create([
                        'code'         => $this->generateVendorCode(), // 🔥 KUNCI UTAMA
                        'name'         => $this->name,
                        'category'     => $this->category,
                        'address'      => $this->address,
                        'npwp'         => $this->npwp,
                        'website'      => $this->website,
                        'bank_details' => $this->bank_details,
                    ]);
                }

                foreach ($this->contacts as $i => $contact) {
                    $vendor->contacts()->create([
                        'pic_name'   => $contact['pic_name'],
                        'email'      => $contact['email'],
                        'phone'      => $contact['phone'],
                        'role'       => $contact['role'] ?? null,
                        'is_primary' => $i === 0,
                    ]);
                }
            });

            session()->flash('success', 'Vendor berhasil disimpan.');
            $this->closeModal();

        } catch (\Throwable $e) {
            report($e);
            session()->flash('error', 'Gagal menyimpan vendor. Silakan coba ulang.');
        }
    }

    public function delete($id)
    {
        $vendor = Vendor::find($id);
        if ($vendor) {
            $vendor->contacts()->delete();
            $vendor->delete();
            session()->flash('success', 'Vendor berhasil dihapus.');
        }
    }

    /* =========================
     |  UTILITIES
     ========================= */
    protected function generateVendorCode(): string
    {
        // Aman untuk multi-user
        $last = Vendor::withTrashed()
            ->orderBy('id', 'desc')
            ->lockForUpdate()
            ->first();

        $number = $last
            ? intval(substr($last->code, 4)) + 1
            : 1;

        return 'VEN-' . str_pad($number, 3, '0', STR_PAD_LEFT);
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->reset([
            'vendorId',
            'code',
            'name',
            'category',
            'address',
            'npwp',
            'website',
            'bank_details',
            'contacts',
            'isEditing',
        ]);

        $this->addContact();
    }

    /* =========================
     |  CONTACT HANDLER
     ========================= */
    public function addContact()
    {
        $this->contacts[] = [
            'pic_name' => '',
            'phone'    => '',
            'email'    => '',
            'role'     => '',
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
