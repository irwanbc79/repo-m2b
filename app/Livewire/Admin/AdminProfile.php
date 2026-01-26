<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminProfile extends Component
{
    public $name;
    public $email;
    public $phone;
    public $role;
    public $roles;
    
    // Password change
    public $current_password;
    public $new_password;
    public $new_password_confirmation;
    
    public function mount()
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone ?? '';
        $this->role = $user->role;
        $this->roles = $user->roles ?? [$user->role];
    }
    
    public function updateProfile()
    {
        $this->validate([
            'name' => 'required|min:2',
            'phone' => 'nullable|string|max:20',
        ]);
        
        $user = Auth::user();
        $user->update([
            'name' => $this->name,
            'phone' => $this->phone,
        ]);
        
        session()->flash('message', '✅ Profil berhasil diperbarui!');
    }
    
    public function updatePassword()
    {
        $this->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);
        
        $user = Auth::user();
        
        if (!Hash::check($this->current_password, $user->password)) {
            session()->flash('error', '❌ Password saat ini tidak cocok!');
            return;
        }
        
        $user->update([
            'password' => Hash::make($this->new_password),
        ]);
        
        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
        session()->flash('message', '✅ Password berhasil diubah!');
    }
    
    public function getRoleBadgeColor($role)
    {
        return match($role) {
            'super_admin' => 'bg-purple-100 text-purple-800',
            'director' => 'bg-blue-100 text-blue-800',
            'manager' => 'bg-green-100 text-green-800',
            'supervisor' => 'bg-yellow-100 text-yellow-800',
            'admin' => 'bg-indigo-100 text-indigo-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
    
    public function render()
    {
        return view('livewire.admin.admin-profile')
            ->layout('layouts.admin');
    }
}
