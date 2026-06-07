<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - M2B Portal</title>
    <link rel="icon" href="{{ asset('images/m2b-logo.png') }}" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        m2b: {
                            primary: '#0F2C59', // Warna Biru Tua M2B
                            accent: '#B91C1C',  // Warna Merah Aksen M2B
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center font-sans p-4 sm:p-6 md:p-8">

    <div class="w-full max-w-6xl bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col md:flex-row border border-gray-150">

        <!-- Left Side: Partner Production Panel (Dark theme / Gradient) -->
        <div class="w-full md:w-1/4 order-2 md:order-1 bg-slate-950 text-white p-5 sm:p-6 flex flex-col justify-between relative overflow-hidden border-t border-gray-200 md:border-t-0 md:border-r md:border-gray-150">
            <!-- Decorative gradients -->
            <div class="absolute -left-20 -top-20 w-48 h-48 rounded-full bg-emerald-600/10 blur-[80px] pointer-events-none"></div>
            <div class="absolute -right-20 -bottom-20 w-48 h-48 rounded-full bg-blue-600/15 blur-[80px] pointer-events-none"></div>

            <div class="relative z-10 space-y-5">
                <div>
                    <span class="inline-flex items-center gap-1.5 text-[10px] font-bold text-emerald-400 uppercase tracking-widest bg-emerald-400/10 px-2.5 py-1 rounded-full border border-emerald-400/20">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        Partner Production
                    </span>
                    <h3 class="text-base font-bold text-white mt-3.5">Mitra Operasional</h3>
                    <p class="text-[10px] text-slate-400 mt-1">Akses cepat layanan kepabeanan &amp; perdagangan yang kami gunakan.</p>
                </div>

                <!-- Partner 1: INSW -->
                <a href="https://insw.go.id" target="_blank" rel="noopener noreferrer"
                   class="group block bg-slate-900/60 border border-slate-800/80 rounded-xl p-3.5 space-y-2 hover:border-blue-500/40 transition duration-300">
                    <div class="flex flex-col gap-1.5">
                        <div class="flex items-center gap-1.5">
                            <div class="w-5 h-5 rounded bg-white flex items-center justify-center shrink-0 overflow-hidden">
                                <img src="{{ asset('images/partners/insw.png') }}" alt="INSW" class="w-4 h-4 object-contain" loading="lazy">
                            </div>
                            <span class="text-xs font-bold text-white truncate">INSW</span>
                        </div>
                        <span class="self-start text-[9px] text-blue-400 bg-blue-400/10 px-2 py-0.5 rounded border border-blue-400/20 font-semibold uppercase tracking-wider">HS Code</span>
                    </div>
                    <p class="text-[10px] text-slate-400 leading-relaxed font-light">
                        Rujukan klasifikasi HS Code &amp; cek Lartas (larangan/pembatasan).
                    </p>
                    <div class="pt-0.5">
                        <span class="inline-flex items-center text-[10px] font-bold text-blue-400 group-hover:text-blue-300 transition gap-1">
                            Buka INSW &rarr;
                        </span>
                    </div>
                </a>

                <!-- Partner 2: Bea Cukai (CEISA) -->
                <a href="https://portal.beacukai.go.id/portal/login" target="_blank" rel="noopener noreferrer"
                   class="group block bg-slate-900/60 border border-slate-800/80 rounded-xl p-3.5 space-y-2 hover:border-amber-500/40 transition duration-300">
                    <div class="flex flex-col gap-1.5">
                        <div class="flex items-center gap-1.5">
                            <div class="w-5 h-5 rounded bg-white flex items-center justify-center shrink-0 overflow-hidden">
                                <img src="{{ asset('images/partners/beacukai.ico') }}" alt="Bea Cukai" class="w-4 h-4 object-contain" loading="lazy">
                            </div>
                            <span class="text-xs font-bold text-white truncate">Bea Cukai</span>
                        </div>
                        <span class="self-start text-[9px] text-amber-400 bg-amber-400/10 px-2 py-0.5 rounded border border-amber-400/20 font-semibold uppercase tracking-wider">Portal CEISA</span>
                    </div>
                    <p class="text-[10px] text-slate-400 leading-relaxed font-light">
                        Submit dokumen impor/ekspor (PIB/PEB) lewat portal resmi.
                    </p>
                    <div class="pt-0.5">
                        <span class="inline-flex items-center text-[10px] font-bold text-amber-400 group-hover:text-amber-300 transition gap-1">
                            Login CEISA &rarr;
                        </span>
                    </div>
                </a>

                <!-- Partner 3: Dira -->
                <a href="https://dira.co.id" target="_blank" rel="noopener noreferrer"
                   class="group block bg-slate-900/60 border border-slate-800/80 rounded-xl p-3.5 space-y-2 hover:border-emerald-500/40 transition duration-300">
                    <div class="flex flex-col gap-1.5">
                        <div class="flex items-center gap-1.5">
                            <div class="w-5 h-5 rounded bg-white flex items-center justify-center shrink-0 overflow-hidden">
                                <img src="{{ asset('images/partners/dira.jpg') }}" alt="Dira" class="w-4 h-4 object-contain" loading="lazy">
                            </div>
                            <span class="text-xs font-bold text-white truncate">Dira</span>
                        </div>
                        <span class="self-start text-[9px] text-emerald-400 bg-emerald-400/10 px-2 py-0.5 rounded border border-emerald-400/20 font-semibold uppercase tracking-wider">Undername</span>
                    </div>
                    <p class="text-[10px] text-slate-400 leading-relaxed font-light">
                        Mitra jasa undername, general trading, impor &amp; ekspor.
                    </p>
                    <div class="pt-0.5">
                        <span class="inline-flex items-center text-[10px] font-bold text-emerald-400 group-hover:text-emerald-300 transition gap-1">
                            Kunjungi Web &rarr;
                        </span>
                    </div>
                </a>
            </div>

            <!-- Footer text in left panel -->
            <div class="relative z-10 pt-4 text-[9px] text-slate-500 flex items-center gap-1.5 border-t border-slate-900/80 mt-4">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                <span>Tautan resmi mitra &amp; instansi</span>
            </div>
        </div>

        <!-- Center: Login Form -->
        <div class="w-full md:w-2/4 order-1 md:order-2 p-8 sm:p-12 flex flex-col justify-center items-center">
            <div class="w-full max-w-md space-y-6">
                <div class="bg-white text-center">
                    <img src="{{ asset('images/m2b-logo.png') }}" alt="M2B Portal" class="h-24 mx-auto w-auto">
                </div>

                <h2 class="text-2xl font-bold text-m2b-primary text-center">PT. MORA MULTI BERKAH</h2>

                @if ($errors->any())
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg">
                        <p class="text-red-700 text-sm">{{ $errors->first() }}</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-m2b-primary text-sm font-bold mb-2 ml-1">Email Address</label>
                        <input class="w-full px-4 py-3 bg-gray-50 border border-blue-200 rounded-lg focus:outline-none focus:border-m2b-primary focus:ring-2 focus:ring-m2b-primary/20 transition duration-200"
                               id="email" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="name@company.com">
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-2 ml-1">
                            <label class="block text-m2b-primary text-sm font-bold">Password</label>
                            <a href="{{ route('password.request') }}" class="text-xs text-m2b-accent font-semibold hover:underline">Forgot Password?</a>
                        </div>
                        <input class="w-full px-4 py-3 bg-gray-50 border border-blue-200 rounded-lg focus:outline-none focus:border-m2b-primary focus:ring-2 focus:ring-m2b-primary/20 transition duration-200"
                               id="password" type="password" name="password" required placeholder="••••••••">
                    </div>

                    <button class="w-full bg-m2b-primary hover:bg-blue-900 text-white font-bold py-3.5 px-4 rounded-lg shadow-lg hover:shadow-xl transition duration-200 transform hover:-translate-y-0.5" type="submit">
                        SIGN IN
                    </button>
                </form>

                <!-- Google OAuth -->
                <div class="relative flex py-2 items-center">
                    <div class="flex-grow border-t border-gray-200"></div>
                    <span class="flex-shrink mx-4 text-gray-400 text-xs uppercase tracking-wider">atau</span>
                    <div class="flex-grow border-t border-gray-200"></div>
                </div>

                <a href="{{ route('login.google') }}" class="w-full inline-flex items-center justify-center gap-3 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 font-bold py-3 px-4 rounded-lg shadow-sm hover:shadow transition duration-200 transform hover:-translate-y-0.5">
                    <svg class="w-5 h-5" viewBox="0 0 24 24">
                        <path fill="#EA4335" d="M12 5.04c1.66 0 3.2.57 4.38 1.69l3.27-3.27C17.68 1.54 14.98 1 12 1 7.24 1 3.2 3.73 1.24 7.74l3.86 3C6.01 7.73 8.78 5.04 12 5.04z"/>
                        <path fill="#4285F4" d="M23.49 12.27c0-.81-.07-1.59-.2-2.36H12v4.51h6.43c-.28 1.44-1.1 2.67-2.33 3.5l3.61 2.8c2.11-1.95 3.33-4.82 3.33-8.45z"/>
                        <path fill="#FBBC05" d="M5.1 14.76c-.23-.69-.36-1.43-.36-2.2s.13-1.51.36-2.2l-3.86-3C.43 9.07 0 10.49 0 12s.43 2.93 1.24 4.64l3.86-3.24z"/>
                        <path fill="#34A853" d="M12 23c3.24 0 5.97-1.07 7.96-2.91l-3.61-2.8c-1.1.74-2.51 1.18-4.35 1.18-3.22 0-5.99-2.69-6.9-5.7l-3.86 3C3.2 20.27 7.24 23 12 23z"/>
                    </svg>
                    <span class="text-sm">Masuk dengan Google</span>
                </a>

                <div class="text-center space-y-2 border-t pt-4 border-gray-100">
                    <p class="text-sm text-gray-600">
                        Belum punya akun?
                        <a href="{{ route('register') }}" class="text-m2b-primary font-bold hover:underline text-base">Daftar Sekarang</a>
                    </p>
                    <p class="text-xs text-gray-400">
                        Butuh bantuan? <a href="mailto:support@m2b.co.id" class="hover:text-m2b-accent">Hubungi Support</a>
                    </p>
                </div>
            </div>
        </div>

        <!-- Right Side: Ecosystem Panel (Dark theme / Gradient) -->
        <div class="w-full md:w-1/4 order-3 bg-slate-950 text-white p-5 sm:p-6 flex flex-col justify-between relative overflow-hidden border-t border-gray-200 md:border-t-0 md:border-l md:border-gray-150">
            <!-- Decorative gradients -->
            <div class="absolute -right-20 -top-20 w-48 h-48 rounded-full bg-blue-600/10 blur-[80px] pointer-events-none"></div>
            <div class="absolute -left-20 -bottom-20 w-48 h-48 rounded-full bg-violet-600/15 blur-[80px] pointer-events-none"></div>

            <div class="relative z-10 space-y-5">
                <div>
                    <span class="inline-flex items-center gap-1.5 text-[10px] font-bold text-blue-400 uppercase tracking-widest bg-blue-400/10 px-2.5 py-1 rounded-full border border-blue-400/20">
                        PARTNER ECOSYSTEM
                    </span>
                    <h3 class="text-base font-bold text-white mt-3.5">Rekomendasi Partner</h3>
                    <p class="text-[10px] text-slate-400 mt-1">Solusi teknologi pendukung akselerasi bisnis Anda.</p>
                </div>

                <!-- Partner 1: Hostinger -->
                <div class="bg-slate-900/60 border border-slate-800/80 rounded-xl p-3.5 space-y-2 hover:border-violet-500/40 transition duration-300">
                    <div class="flex flex-col gap-1.5">
                        <div class="flex items-center gap-1.5">
                            <div class="w-5 h-5 rounded bg-violet-600 flex items-center justify-center text-[10px] font-bold text-white shrink-0">H</div>
                            <span class="text-xs font-bold text-white truncate">Hostinger Hosting</span>
                        </div>
                        <span class="self-start text-[9px] text-emerald-400 bg-emerald-400/10 px-2 py-0.5 rounded border border-emerald-400/20 font-semibold uppercase tracking-wider">20% OFF</span>
                    </div>
                    <p class="text-[10px] text-slate-400 leading-relaxed font-light">
                        Infrastruktur cloud ultra-cepat dengan server lokal Jakarta.
                    </p>
                    <div class="pt-0.5">
                        <a href="https://www.hostinger.com/id?REFERRALCODE=7YYIRWANB72L" target="_blank" rel="noopener noreferrer"
                           class="inline-flex items-center text-[10px] font-bold text-violet-400 hover:text-violet-300 transition gap-1">
                            Klaim Diskon &rarr;
                        </a>
                    </div>
                </div>

                <!-- Partner 2: Emergent AI -->
                <div class="bg-slate-900/60 border border-slate-800/80 rounded-xl p-3.5 space-y-2 hover:border-blue-500/40 transition duration-300">
                    <div class="flex flex-col gap-1.5">
                        <div class="flex items-center gap-1.5">
                            <div class="w-5 h-5 rounded bg-blue-600 flex items-center justify-center text-[10px] font-bold text-white shrink-0">E</div>
                            <span class="text-xs font-bold text-white truncate">Emergent AI</span>
                        </div>
                        <span class="self-start text-[9px] text-blue-400 bg-blue-400/10 px-2 py-0.5 rounded border border-blue-400/20 font-semibold uppercase tracking-wider">FREE ACCESS</span>
                    </div>
                    <p class="text-[10px] text-slate-400 leading-relaxed font-light">
                        Platform automasi alur kerja (workflow) berbasis AI.
                    </p>
                    <div class="pt-0.5">
                        <a href="https://app.emergent.sh/register?ref=irwa212175" target="_blank" rel="noopener noreferrer"
                           class="inline-flex items-center text-[10px] font-bold text-blue-400 hover:text-blue-300 transition gap-1">
                            Daftar Akun &rarr;
                        </a>
                    </div>
                </div>

                <!-- Partner 3: Mora Bangun Solutions -->
                <div class="bg-slate-900/60 border border-slate-800/80 rounded-xl p-3.5 space-y-2 hover:border-cyan-500/40 transition duration-300">
                    <div class="flex flex-col gap-1.5">
                        <div class="flex items-center gap-1.5">
                            <div class="w-5 h-5 rounded bg-cyan-600 flex items-center justify-center text-[10px] font-bold text-slate-950 shrink-0">M</div>
                            <span class="text-xs font-bold text-white truncate">Mora Bangun Solutions</span>
                        </div>
                        <span class="self-start text-[9px] text-cyan-400 bg-cyan-400/10 px-2 py-0.5 rounded border border-cyan-400/20 font-semibold uppercase tracking-wider">IT PARTNER</span>
                    </div>
                    <p class="text-[10px] text-slate-400 leading-relaxed font-light">
                        Sistem Enterprise (ERP, CRM) &amp; integrasi AI terpercaya.
                    </p>
                    <div class="pt-0.5">
                        <a href="https://morabangun.com" target="_blank" rel="noopener noreferrer"
                           class="inline-flex items-center text-[10px] font-bold text-cyan-400 hover:text-cyan-300 transition gap-1">
                            Kunjungi Web &rarr;
                        </a>
                    </div>
                </div>
            </div>

            <!-- Footer text in right panel -->
            <div class="relative z-10 pt-4 text-[9px] text-slate-500 flex justify-between items-center border-t border-slate-900/80 mt-4">
                <span>&copy; {{ date('Y') }} M2B</span>
                <span>v1.2.0</span>
            </div>
        </div>

    </div>

</body>
</html>
