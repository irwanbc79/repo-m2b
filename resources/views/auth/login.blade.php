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

    <div class="w-full max-w-5xl bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col md:flex-row border border-gray-150">
        <!-- Left Side: Login Form -->
        <div class="w-full md:w-3/4 p-6 sm:p-10 flex flex-col justify-center">
            <div class="bg-white text-center mb-6">
                <img src="{{ asset('images/m2b-logo.png') }}" alt="M2B Portal" class="h-24 mx-auto w-auto">
            </div>

            <h2 class="text-2xl font-bold text-m2b-primary text-center mb-6">PT. MORA MULTI BERKAH</h2>

            @if ($errors->any())
                <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-4">
                    <p class="text-red-700 text-sm">{{ $errors->first() }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                
                <div class="mb-5">
                    <label class="block text-m2b-primary text-sm font-bold mb-2 ml-1">Email Address</label>
                    <input class="w-full px-4 py-3 bg-gray-50 border border-blue-200 rounded-lg focus:outline-none focus:border-m2b-primary focus:ring-2 focus:ring-m2b-primary/20 transition duration-200" 
                           id="email" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="name@company.com">
                </div>

                <div class="mb-8">
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

            <div class="mt-8 text-center space-y-2 border-t pt-6 border-gray-100">
                <p class="text-sm text-gray-600">
                    Belum punya akun? 
                    <a href="{{ route('register') }}" class="text-m2b-primary font-bold hover:underline text-base">Daftar Sekarang</a>
                </p>
                <p class="text-xs text-gray-400">
                    Butuh bantuan? <a href="mailto:support@m2b.co.id" class="hover:text-m2b-accent">Hubungi Support</a>
                </p>
            </div>
        </div>

        <!-- Right Side: Ecosystem Panel (Dark theme / Gradient) -->
        <div class="w-full md:w-1/4 bg-slate-950 text-white p-5 sm:p-6 flex flex-col justify-between relative overflow-hidden border-t border-gray-200 md:border-t-0 md:border-l md:border-gray-150">
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
                            <span class="text-xs font-bold text-white truncate">Mora Bangun</span>
                        </div>
                        <span class="self-start text-[9px] text-cyan-400 bg-cyan-400/10 px-2 py-0.5 rounded border border-cyan-400/20 font-semibold uppercase tracking-wider">IT PARTNER</span>
                    </div>
                    <p class="text-[10px] text-slate-400 leading-relaxed font-light">
                        Sistem Enterprise (ERP, CRM) & integrasi AI terpercaya.
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