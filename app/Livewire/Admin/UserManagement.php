<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $isModalOpen = false;
    public $isEditing = false;
    public $editingId = null;

    // Form Fields
    public $name, $email, $password;
    public $selectedRoles = []; // GANTI $role JADI ARRAY

    // Daftar Role Internal M2B
    public $rolesList = [
        // === MANAGEMENT LEVEL ===
        'super_admin' => 'Super Admin',
        'director' => 'Director/Owner',
        'manager' => 'Manager',
        'supervisor' => 'Supervisor',
        
        // === AUDITOR & KONSULTAN ===
        'auditor' => 'Auditor',
        'konsultan_pajak' => 'Konsultan Pajak',

        // === OPERATIONAL LEVEL ===
        'staff_accounting' => 'Staff Accounting',
        'staff_operations' => 'Staff Operations',
        'staff_sales' => 'Staff Sales/Marketing',
        'staff_ppjk' => 'Staff PPJK/Customs',
        'staff_documentation' => 'Staff Documentation',
        'cashier' => 'Kasir',
        
        // === FIELD OFFICER ===
        'field_uploader' => 'Petugas Lapangan (Freelance)',

        // === LEGACY (for backward compatibility) ===
        'admin' => 'Admin (Legacy)',
        'staff' => 'Staff (Legacy)',
        'finance' => 'Finance (Legacy)',
        'shipment' => 'Shipment (Legacy)',
        'staf_accounting' => 'Accounting (Legacy)',
    ];

    public function updatingSearch() { $this->resetPage(); }

    public function render()
    {
        // Tampilkan user yang BUKAN customer saja
        // Kita filter manual menggunakan whereJsonDoesntContain (MySQL 5.7+) atau like
        $users = User::where('email', 'not like', '%@example.com%') 
            ->where(function($q) {
                // Logic filter: Cari user yang roles-nya TIDAK mengandung "customer"
                // Karena kolom roles adalah JSON array string
                $q->where('roles', 'not like', '%"customer"%');
            })
            ->where(function($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                  ->orWhere('email', 'like', '%'.$this->search.'%');
            })
            ->latest()
            ->paginate(25);

        return view('livewire.admin.user-management', [
            'users' => $users
        ])->layout('layouts.admin');
    }

    public function create()
    {
        $this->resetInput();
        $this->isEditing = false;
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        $user = User::find($id);
        if ($user) {
            $this->editingId = $id;
            $this->name = $user->name;
            $this->email = $user->email;
            
            // Ambil data roles dari DB (Array)
            $this->selectedRoles = $user->roles ?? []; 
            
            $this->isEditing = true;
            $this->isModalOpen = true;
        }
    }

    public function save()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->editingId,
            'selectedRoles' => 'required|array|min:1', // Wajib pilih minimal 1 role
        ];

        if (!$this->isEditing) {
            $rules['password'] = 'required|min:6';
        }

        $this->validate($rules);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'roles' => $this->selectedRoles,
            'role'  => $this->selectedRoles[0] ?? null, // sync kolom legacy
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->isEditing) {
            $user = User::find($this->editingId);
            $oldRoles = $user->roles ?? [$user->role];
            $user->update($data);
            
            // Hapus data customer jika user ini dulunya customer (bersih-bersih)
            if($user->customer) { $user->customer->delete(); }
            
            $rolesChanged = ($oldRoles != $this->selectedRoles);
            if ($rolesChanged) {
                \App\Models\ActivityLog::record('UserManagement', 'UPDATE_ROLE', $user->email, "Ubah hak akses staf {$user->name}: " . implode(', ', (array)$oldRoles) . " → " . implode(', ', (array)$this->selectedRoles), ['roles' => $oldRoles], ['roles' => $this->selectedRoles]);
            } else {
                \App\Models\ActivityLog::record('UserManagement', 'UPDATE_USER', $user->email, "Perbarui profil staf {$user->name}");
            }

            session()->flash('message', 'Data Staf (Multi-Role) berhasil diperbarui.');
        } else {
            $newUser = User::create($data);
            \App\Models\ActivityLog::record('UserManagement', 'CREATE_USER', $newUser->email, "Membuat akun staf baru: {$newUser->name} (" . implode(', ', (array)$this->selectedRoles) . ")");
            session()->flash('message', 'Staf M2B baru berhasil ditambahkan.');
        }

        $this->closeModal();
    }

    public function delete($id)
    {
        if ($id == auth()->id()) {
            session()->flash('error', 'Tidak bisa menghapus diri sendiri!');
            return;
        }
        $user = User::find($id);
        if ($user) {
            \App\Models\ActivityLog::record('UserManagement', 'DELETE_USER', $user->email, "Menghapus akun staf: {$user->name} ({$user->email})");
            $user->delete();
            session()->flash('message', 'User berhasil dihapus.');
        }
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->resetInput();
    }

    private function resetInput()
    {
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->selectedRoles = []; // Reset Array
        $this->editingId = null;
    }
}