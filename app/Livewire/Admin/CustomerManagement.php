<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Customer;
use App\Models\Shipment;
use App\Models\Invoice;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CustomerManagement extends Component
{
    use WithPagination;

    // Search & Filter
    public $search = '';
    public $filterCity = '';
    public $filterStatus = '';
    public $filterTag = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $perPage = 10;

    // Modal States
    public $isModalOpen = false;
    public $isEditing = false;
    public $customerId = null;
    public $showQuickView = false;
    public $quickViewCustomer = null;
    public $showDeleteConfirm = false;
    public $deleteId = null;

    // Bulk Actions
    public $selectedCustomers = [];
    public $selectAll = false;

    // Form Data
    public $email, $password, $name;
    public $role = 'customer';
    public $customer_code, $company_name, $phone, $address, $city;
    public $npwp, $warehouse_address, $business_type, $credit_limit, $payment_terms;
    public $customer_tag = '';
    public $is_active = true;

    // Available Tags
    public $availableTags = ['VIP', 'Regular', 'New', 'Priority', 'Inactive'];

    protected $queryString = ['search', 'filterCity', 'filterStatus', 'sortField', 'sortDirection'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterCity()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedCustomers = $this->getCustomersQuery()->pluck('id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selectedCustomers = [];
        }
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function getStats()
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();

        return [
            'total' => Customer::count(),
            'new_this_month' => Customer::where('created_at', '>=', $startOfMonth)->count(),
            'with_shipments' => Customer::whereHas('shipments')->count(),
            'cities' => Customer::distinct('city')->whereNotNull('city')->count(),
            'total_credit_limit' => Customer::sum('credit_limit'),
            'vip_count' => Customer::where('business_type', 'VIP')->count(),
        ];
    }

    public function getCities()
    {
        return Customer::distinct()
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->orderBy('city')
            ->pluck('city');
    }

    public function create()
    {
        $this->resetValidation();
        $this->resetForm();
        $this->isEditing = false;
        $this->isModalOpen = true;
        $this->customer_code = $this->generateSmartCode($this->role);
    }

    public function updatedRole($value)
    {
        if ($this->isEditing) return;
        $this->customer_code = $this->generateSmartCode($value);
    }

    public function openModal()
    {
        $this->create();
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
    }

    public function resetForm()
    {
        $this->email = '';
        $this->password = '';
        $this->name = '';
        $this->role = 'customer';
        $this->company_name = '';
        $this->phone = '';
        $this->address = '';
        $this->city = '';
        $this->npwp = '';
        $this->warehouse_address = '';
        $this->business_type = '';
        $this->credit_limit = 0;
        $this->payment_terms = 30;
        $this->customer_tag = '';
        $this->is_active = true;
        $this->customer_code = $this->generateSmartCode('customer');
    }

    public function generateSmartCode($roleType)
    {
        $count = Customer::count() + 1;
        $number = str_pad($count, 6, '0', STR_PAD_LEFT);
        if (in_array($roleType, ['admin', 'manager', 'staff', 'finance', 'shipment'])) {
            return 'M2B-' . $number;
        }
        return 'CUST-' . $number;
    }

    public function save()
    {
        $rules = [
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . ($this->customerId ? Customer::find($this->customerId)->user_id : ''),
            'role' => 'required|in:admin,manager,staff,finance,shipment,customer',
            'company_name' => 'required',
        ];

        if (!$this->isEditing) {
            $rules['password'] = 'required|min:6';
        }

        $this->validate($rules);

        DB::transaction(function () {
            if ($this->isEditing) {
                $customer = Customer::find($this->customerId);
                $userData = ['name' => $this->name, 'role' => $this->role, 'email' => $this->email];
                if (!empty($this->password)) {
                    $userData['password'] = Hash::make($this->password);
                }

                $customer->user->update($userData);

                $customer->update([
                    'company_name' => $this->company_name,
                    'phone' => $this->phone,
                    'npwp' => $this->npwp,
                    'address' => $this->address,
                    'warehouse_address' => $this->warehouse_address,
                    'city' => $this->city,
                    'business_type' => $this->customer_tag ?: $this->business_type,
                    'credit_limit' => $this->credit_limit ?? 0,
                    'payment_terms' => $this->payment_terms ?? 30,
                ]);

                session()->flash('message', 'Data customer berhasil diperbarui!');
            } else {
                $user = User::create([
                    'name' => $this->name,
                    'email' => $this->email,
                    'password' => Hash::make($this->password),
                    'role' => $this->role,
                ]);

                $customerCode = Customer::generateCustomerCode();

                Customer::create([
                    'user_id' => $user->id,
                    'customer_code' => $customerCode,
                    'company_name' => $this->company_name,
                    'phone' => $this->phone,
                    'npwp' => $this->npwp,
                    'address' => $this->address,
                    'warehouse_address' => $this->warehouse_address,
                    'city' => $this->city,
                    'business_type' => $this->customer_tag ?: 'Regular',
                    'credit_limit' => $this->credit_limit ?? 0,
                    'payment_terms' => $this->payment_terms ?? 30,
                ]);

                session()->flash('message', 'Customer berhasil ditambahkan! Code: ' . $customerCode);
            }
        });

        $this->closeModal();
    }

    public function store()
    {
        $this->save();
    }

    public function update()
    {
        $this->save();
    }

    public function edit($id)
    {
        $customer = Customer::with('user')->find($id);
        if ($customer) {
            $this->customerId = $id;
            $this->name = $customer->user->name ?? '';
            $this->email = $customer->user->email ?? '';
            $this->role = $customer->user->role ?? 'customer';

            $this->customer_code = $customer->customer_code;
            $this->company_name = $customer->company_name;
            $this->phone = $customer->phone;
            $this->npwp = $customer->npwp;
            $this->address = $customer->address;
            $this->warehouse_address = $customer->warehouse_address;
            $this->city = $customer->city;
            $this->business_type = $customer->business_type;
            $this->customer_tag = $customer->business_type;
            $this->credit_limit = $customer->credit_limit;
            $this->payment_terms = $customer->payment_terms;

            $this->isEditing = true;
            $this->isModalOpen = true;
        }
    }

    public function quickView($id)
    {
        $this->quickViewCustomer = Customer::with(['user', 'shipments' => function ($q) {
            $q->latest()->limit(5);
        }])->withCount(['shipments', 'invoices'])->find($id);
        
        if ($this->quickViewCustomer) {
            $this->quickViewCustomer->total_revenue = Invoice::where('customer_id', $id)
                ->where('status', 'paid')
                ->sum('grand_total');
            $this->quickViewCustomer->last_order = Shipment::where('customer_id', $id)
                ->latest()
                ->first();
        }
        
        $this->showQuickView = true;
    }

    public function closeQuickView()
    {
        $this->showQuickView = false;
        $this->quickViewCustomer = null;
    }

    public function confirmDelete($id)
    {
        $this->deleteId = $id;
        $this->showDeleteConfirm = true;
    }

    public function cancelDelete()
    {
        $this->showDeleteConfirm = false;
        $this->deleteId = null;
    }

    public function delete($id = null)
    {
        $targetId = $id ?? $this->deleteId;
        
        try {
            $customer = Customer::find($targetId);
            if (!$customer) {
                session()->flash('error', 'Customer tidak ditemukan.');
                return;
            }

            $hasInvoices = DB::table('invoices')->where('customer_id', $targetId)->exists();
            $hasShipments = DB::table('shipments')->where('customer_id', $targetId)->exists();

            if ($hasInvoices || $hasShipments) {
                session()->flash('error', 'GAGAL: Customer ini memiliki riwayat transaksi (Invoice/Shipment) dan tidak dapat dihapus.');
                $this->cancelDelete();
                return;
            }

            DB::transaction(function () use ($customer) {
                $user = $customer->user;
                $customer->delete();
                if ($user) {
                    $user->delete();
                }
            });

            session()->flash('message', 'Customer berhasil dihapus permanen.');
            $this->cancelDelete();
        } catch (\Exception $e) {
            session()->flash('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }

    public function bulkDelete()
    {
        if (empty($this->selectedCustomers)) {
            session()->flash('error', 'Pilih customer yang akan dihapus.');
            return;
        }

        $deleted = 0;
        $failed = 0;

        foreach ($this->selectedCustomers as $id) {
            $customer = Customer::find($id);
            if (!$customer) continue;

            $hasInvoices = DB::table('invoices')->where('customer_id', $id)->exists();
            $hasShipments = DB::table('shipments')->where('customer_id', $id)->exists();

            if ($hasInvoices || $hasShipments) {
                $failed++;
                continue;
            }

            DB::transaction(function () use ($customer) {
                $user = $customer->user;
                $customer->delete();
                if ($user) {
                    $user->delete();
                }
            });
            $deleted++;
        }

        $this->selectedCustomers = [];
        $this->selectAll = false;

        if ($deleted > 0) {
            session()->flash('message', "{$deleted} customer berhasil dihapus.");
        }
        if ($failed > 0) {
            session()->flash('error', "{$failed} customer tidak dapat dihapus karena memiliki transaksi.");
        }
    }

    public function exportExcel()
    {
        $customers = $this->getCustomersQuery()->get();

        $csvContent = "Kode,Perusahaan,NPWP,Kontak,HP,Email,Kota,Tag,Credit Limit,Terdaftar\n";

        foreach ($customers as $c) {
            $csvContent .= implode(',', [
                $c->customer_code,
                '"' . str_replace('"', '""', $c->company_name) . '"',
                $c->npwp ?? '-',
                '"' . str_replace('"', '""', $c->user->name ?? '-') . '"',
                $c->phone ?? '-',
                $c->user->email ?? '-',
                $c->city ?? '-',
                $c->business_type ?? 'Regular',
                $c->credit_limit ?? 0,
                $c->created_at?->format('Y-m-d') ?? '-',
            ]) . "\n";
        }

        $filename = 'customers_' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($csvContent) {
            echo "\xEF\xBB\xBF" . $csvContent;
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function updateTag($customerId, $tag)
    {
        $customer = Customer::find($customerId);
        if ($customer) {
            $customer->update(['business_type' => $tag]);
            session()->flash('message', 'Tag customer berhasil diupdate!');
        }
    }

    private function getCustomersQuery()
    {
        return Customer::with('user')
            ->withCount(['shipments', 'invoices'])
            ->when($this->search, function ($query) {
                $term = '%' . $this->search . '%';
                $query->where(function ($q) use ($term) {
                    $q->where('company_name', 'like', $term)
                        ->orWhere('customer_code', 'like', $term)
                        ->orWhere('phone', 'like', $term)
                        ->orWhere('npwp', 'like', $term)
                        ->orWhere('city', 'like', $term)
                        ->orWhereHas('user', function ($u) use ($term) {
                            $u->where('email', 'like', $term)
                                ->orWhere('name', 'like', $term);
                        });
                });
            })
            ->when($this->filterCity, function ($query) {
                $query->where('city', $this->filterCity);
            })
            ->when($this->filterTag, function ($query) {
                $query->where('business_type', $this->filterTag);
            })
            ->orderBy($this->sortField, $this->sortDirection);
    }

    public function render()
    {
        $customers = $this->getCustomersQuery()->paginate($this->perPage);
        $stats = $this->getStats();
        $cities = $this->getCities();

        return view('livewire.admin.customer-management', [
            'customers' => $customers,
            'stats' => $stats,
            'cities' => $cities,
        ])->layout('layouts.admin');
    }
}
