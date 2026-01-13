<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class Profile extends Component
{
    // Data Akun Login
    public $name;
    public $email;
    
    // Data Perusahaan (Customer Only)
    public $company_name;
    public $customer_code;
    public $npwp;
    public $phone;
    public $address;
    public $city;
    public $warehouse_address;

    // Password
    public $current_password;
    public $new_password;
    public $new_password_confirmation;

    public function mount()
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
        
        // Load Data Customer jika ada
        if ($user->customer) {
            $this->company_name = $user->customer->company_name;
            $this->customer_code = $user->customer->customer_code;
            $this->npwp = $user->customer->npwp;
            $this->phone = $user->customer->phone;
            $this->address = $user->customer->address;
            $this->city = $user->customer->city;
            $this->warehouse_address = $user->customer->warehouse_address;
        }
    }

    public function updateProfile()
    {
        $user = Auth::user();

        // 1. Validasi Dasar
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
        ]);

        // 2. Update User Login
        $user->update([
            'name' => $this->name,
            'email' => $this->email,
        ]);

        // 3. Update Data Perusahaan (Jika User adalah Customer)
        if ($user->customer) {
            $this->validate([
                'company_name' => 'required|string',
                'phone' => 'nullable|string',
                'address' => 'nullable|string',
                'npwp' => 'nullable|string',
            ]);

            $user->customer->update([
                'company_name' => $this->company_name,
                'npwp' => $this->npwp,
                'phone' => $this->phone,
                'address' => $this->address,
                'city' => $this->city,
                'warehouse_address' => $this->warehouse_address,
            ]);
        }

        session()->flash('message', 'Profil dan Data Perusahaan berhasil diperbarui!');
    }

    public function updatePassword()
    {
        $this->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($this->current_password, $user->password)) {
            $this->addError('current_password', 'Password lama salah.');
            return;
        }

        $user->update(['password' => Hash::make($this->new_password)]);
        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
        session()->flash('password_message', 'Password berhasil diubah!');
    }

    public function render()
    {
        // Pilih layout sesuai role
        $layout = in_array(Auth::user()->role, ['admin', 'manager', 'staff']) ? 'layouts.admin' : 'layouts.customer';
        
        // Cek apakah user ini Admin/Internal atau Customer
        $isInternal = in_array(Auth::user()->role, ['admin', 'manager', 'staff']);

        // Jika Admin membuka profile ini, tampilkan view admin (yang simpel)
        if ($isInternal) {
            return view('livewire.admin.profile')->layout($layout);
        }
        
        // Jika Customer, tampilkan view customer (yang lengkap dengan data perusahaan)
        return view('livewire.customer.profile')->layout($layout);
    }
}