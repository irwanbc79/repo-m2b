<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Exception;

class GoogleAuthController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Obtain the user information from Google.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Exception $e) {
            return redirect()->route('login')->withErrors(['email' => 'Gagal masuk menggunakan Google. Harap coba lagi.']);
        }

        // Check if a user already exists with this Google ID
        $user = User::where('google_id', $googleUser->getId())->first();

        if ($user) {
            // Update token if changed
            $user->update([
                'google_token' => $googleUser->token,
                'google_refresh_token' => $googleUser->refreshToken ?? $user->google_refresh_token,
            ]);

            return $this->loginOrBlock($user);
        }

        // Check if user exists with the same email address
        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            // Link Google Account to existing user
            $user->update([
                'google_id' => $googleUser->getId(),
                'google_token' => $googleUser->token,
                'google_refresh_token' => $googleUser->refreshToken ?? null,
            ]);

            return $this->loginOrBlock($user);
        }

        // Pengguna baru: JANGAN langsung buat akun. Simpan identitas Google
        // di session, lalu minta calon customer melengkapi data valid dulu.
        $request->session()->put('google_register', [
            'google_id' => $googleUser->getId(),
            'name' => $googleUser->getName(),
            'email' => $googleUser->getEmail(),
            'google_token' => $googleUser->token,
            'google_refresh_token' => $googleUser->refreshToken ?? null,
        ]);

        return redirect()->route('register.complete');
    }

    /**
     * Tampilkan form lengkapi data untuk pendaftar Google baru.
     */
    public function showCompleteProfile(Request $request)
    {
        $data = $request->session()->get('google_register');

        if (! $data) {
            return redirect()->route('login');
        }

        return view('auth.complete-profile', [
            'name' => $data['name'],
            'email' => $data['email'],
        ]);
    }

    /**
     * Simpan data valid pendaftar Google -> buat akun PENDING + Customer.
     */
    public function storeCompleteProfile(Request $request)
    {
        $data = $request->session()->get('google_register');

        if (! $data) {
            return redirect()->route('login');
        }

        // Pastikan email Google belum dipakai (cegah duplikat / race).
        if (User::where('email', $data['email'])->orWhere('google_id', $data['google_id'])->exists()) {
            $request->session()->forget('google_register');

            return redirect()->route('login')->withErrors([
                'email' => 'Akun dengan email ini sudah terdaftar. Silakan login.',
            ]);
        }

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:200'],
            'position'     => ['required', 'string', 'max:100'],
            'phone'        => ['required', 'string', 'regex:/^(\+62|62|0)8[1-9][0-9]{6,11}$/'],
            'address'      => ['required', 'string', 'min:10', 'max:500'],
            'city'         => ['required', 'string', 'max:100'],
            'npwp'         => ['nullable', 'string', 'regex:/^[0-9]{15,16}$/'],
            'trade_type'   => ['required', 'in:import,export,both,domestic'],
            'trade_plan'   => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'position.required' => 'Mohon isi jabatan Anda sebagai perwakilan perusahaan.',
            'phone.regex' => 'Nomor HP tidak valid. Gunakan format Indonesia, contoh 08xxxxxxxxxx.',
            'address.min' => 'Alamat terlalu pendek, mohon isi alamat lengkap.',
            'npwp.regex'  => 'NPWP harus 15 atau 16 digit angka.',
            'trade_type.required' => 'Pilih kebutuhan layanan Anda (Impor / Ekspor / Keduanya / Domestik).',
            'trade_plan.required' => 'Mohon jelaskan rencana pengiriman / komoditas Anda.',
            'trade_plan.min' => 'Penjelasan rencana terlalu singkat.',
        ]);

        $nameParts = explode(' ', $data['name'], 2);

        DB::transaction(function () use ($data, $validated, $nameParts) {
            $newUser = User::create([
                'name' => $data['name'],
                'first_name' => $nameParts[0] ?? 'Google',
                'last_name' => $nameParts[1] ?? 'User',
                'email' => $data['email'],
                'google_id' => $data['google_id'],
                'google_token' => $data['google_token'],
                'google_refresh_token' => $data['google_refresh_token'],
                'role' => 'customer',
                'is_active' => false, // menunggu persetujuan admin
                'password' => Hash::make(Str::random(16)), // Dummy password
            ]);

            Customer::create([
                'user_id' => $newUser->id,
                'customer_code' => Customer::generateCustomerCode(),
                'company_name' => $validated['company_name'],
                'position' => $validated['position'],
                'phone' => $validated['phone'],
                'address' => $validated['address'],
                'city' => $validated['city'],
                'npwp' => $validated['npwp'] ?? null,
                'business_type' => 'Regular',
                'trade_type' => $validated['trade_type'],
                'trade_plan' => $validated['trade_plan'],
                'credit_limit' => 0,
                'payment_terms' => 30,
                'preferred_language' => 'id',
            ]);
        });

        $request->session()->forget('google_register');

        // Jangan auto-login: akun masih menunggu persetujuan admin.
        return redirect()->route('login')->with('status', 'Pendaftaran berhasil! Data Anda sedang ditinjau admin. Anda dapat masuk setelah akun diaktifkan.');
    }

    /**
     * Login user yang sudah ada, atau tolak bila akun belum aktif
     * (menunggu persetujuan / dinonaktifkan admin).
     */
    private function loginOrBlock(User $user)
    {
        if (! $user->is_active) {
            return redirect()->route('login')->withErrors([
                'email' => 'Akun Anda belum aktif / masih menunggu persetujuan admin.',
            ]);
        }

        Auth::login($user);
        $primaryRole = $user->getPrimaryRole();

        return $primaryRole === 'customer'
            ? redirect()->intended(route('customer.dashboard'))
            : redirect()->intended(route('admin.dashboard'));
    }
}
