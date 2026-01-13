@once
    {{-- CSS disuntikkan ke dalam head sekali saja (Livewire Safe) --}}
    <style>
        /* FIX KRITIS: Memastikan class 'hidden' berfungsi, karena tidak selalu dimuat global oleh Tailwind CDN */
        .hidden {
            display: none !important;
        }

        /* CSS KHUSUS INTEGRASI (Netralisasi Root Livewire) */
        .cashier-wrapper {
            display: block;
            width: 100%;
            margin: 0; 
            background-color: transparent; 
            min-height: 80vh; 
            position: relative; 
        }
        .cashier-container {
            /* Menerapkan padding dan background di sini */
            padding: 1.5rem; /* p-6 equivalent */
            background-color: #f3f4f6; /* Latar belakang aplikasi */
            min-height: 80vh;
        }

        /* Overlay - Diubah menjadi ABSOLUTE agar terikat pada .cashier-wrapper */
        .cashier-loading-overlay {
            position: absolute; 
            inset: 0;
            z-index: 50; 
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
            background: rgba(0,0,0,0.45);
        }

        .cashier-loading-box {
            padding: 24px;
            background: white;
            border-radius: 12px;
            border: 1px solid #ddd;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }

        /* CSS Aplikasi Utama */
        .print-input-header {
            border: 1px solid #d1d5db;
            padding: 8px 12px;
            border-radius: 6px;
            width: 100%;
        }
        .print-input-header:focus, .print-input-journal:focus {
            outline: 2px solid #3b82f6;
            outline-offset: 1px;
            border-color: #3b82f6 !important;
        }
        .print-input-journal {
            border: 1px solid transparent;
            padding: 4px 8px;
            width: 100%;
            background-color: transparent;
        }
        
        /* Print styles */
        @media print {
            body { background-color: white; padding: 0 !important; }
            .no-print { display: none !important; }
            .print-area { box-shadow: none !important; border: none !important; border-radius: 0 !important; }
            .print-input-header {
                border: none !important; border-bottom: 1px solid #000 !important;
                padding: 2px 0 !important; background-color: white !important;
                box-shadow: none !important; border-radius: 0 !important;
                font-size: 14px; font-weight: 600;
            }
            .print-input-journal { border: none !important; background-color: white !important; padding: 4px 8px !important; }
            #journalBody tr td, #journalTotalFooter td, #journalBody tr th { border: 1px solid #000 !important; }
            .print-only { display: block !important; }
            .h-16 { height: 60px; }
            .border-b { border: none !important; }
        }
    </style>
    
    {{-- SCRIPT MODULE (Livewire Safe) --}}
    <script type="module">
        // --- Firebase SDK Imports ---
        import { initializeApp } from "https://www.gstatic.com/firebasejs/11.6.1/firebase-app.js";
        import { getAuth, signInAnonymously, signInWithCustomToken } from "https://www.gstatic.com/firebasejs/11.6.1/firebase-auth.js";
        import { getFirestore, addDoc, collection, query, onSnapshot, orderBy } from "https://www.gstatic.com/firebasejs/11.6.1/firebase-firestore.js";
        
        // --- GLOBAL VARIABLES (MANDATORY) ---
        const appId = typeof __app_id !== 'undefined' ? __app_id : 'default-app-id';
        const firebaseConfig = JSON.parse(typeof __firebase_config !== 'undefined' ? __firebase_config : '{}');
        
        // FIX 1: Perbaiki Typo variabel agar token terambil dengan benar
        const initialAuthToken = typeof __initial_auth_token !== 'undefined'
            ? __initial_auth_token
            : null; 

        let db, auth, userId = null;
        let transactionsRef;

        // FIX 2: FailSafe Global - Fungsi untuk paksa hilangkan loading
        function forceHideLoading() {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) overlay.classList.add('hidden');
        }

        // --- AUTH & INIT ---
        const initFirebase = async () => {
            const overlay = document.getElementById('loadingOverlay');
            overlay?.classList.remove('hidden'); // Pastikan loading muncul saat init

            // === FIX KRITIS: JANGAN BLOK UI JIKA FIREBASE TIDAK ADA ===
            if (Object.keys(firebaseConfig).length === 0) {
                console.warn('Firebase config tidak ditemukan. Aplikasi berjalan TANPA sync.');
                
                // Set dummy userId agar aplikasi tetap hidup
                userId = 'local-admin';
                window.appState.currentView = 'form';
                renderApp();          // ⬅️ KUNCI: Render UI segera
                forceHideLoading();
                return;
            }

            try {
                const app = initializeApp(firebaseConfig);
                db = getFirestore(app);
                auth = getAuth(app);
                
                if (initialAuthToken) {
                    await signInWithCustomToken(auth, initialAuthToken);
                } else {
                    await signInAnonymously(auth);
                }
                userId = auth.currentUser?.uid || crypto.randomUUID();
                
                transactionsRef = collection(
                    db,
                    `artifacts/${appId}/users/${userId}/finance_transactions`
                );

                loadReportsListener();
                changeView('form'); // Ini akan memanggil renderApp() jika sukses

            } catch (error) {
                console.error("Firebase init error:", error);
                showError("Gagal koneksi Firebase, aplikasi tetap bisa digunakan (Data tidak tersimpan).");
                renderApp(); // ⬅️ TETAP RENDER UI, meskipun gagal koneksi
            } finally {
                // Failsafe hiding di sini sudah tidak diperlukan karena ada di renderApp dan setTimeout
            }
        };

        // --- GLOBAL STATE ---
        window.appState = {
            currentView: 'form', // 'form', 'browse', 'report'
            currentFormType: 'Pemasukan',
            coaList: [
                '1101 - Kas Kecil', '1102 - Kas Besar', '1103 - Bank Mandiri', '1104 - Bank BCA',
                '2101 - Utang Dagang (Supplier)', '2102 - Utang Pengiriman (Customer)',
                '4101 - Pendapatan Penjualan', '4201 - Pendapatan Jasa',
                '5101 - Beban Telepon', '5102 - Beban Listrik', '5201 - Beban Transportasi'
            ],
            coaSelections: [], 
            proofFile: null,
            transactions: []
        };
        
        // --- DATA BINDING AND RENDER ---
        window.loadReportsListener = () => {
            if (!transactionsRef) return;

            onSnapshot(query(transactionsRef, orderBy("transactionDate", "desc")), (snapshot) => {
                window.appState.transactions = snapshot.docs.map(doc => ({
                    id: doc.id,
                    ...doc.data()
                }));
                
                if (window.appState.currentView === 'browse') {
                    renderBrowseView();
                }
                if (window.appState.currentView === 'report') {
                    window.filterReports(); 
                }
            }, (error) => {
                showError("Gagal memuat data transaksi.");
            });
        };

        window.renderInternalNav = () => {
             const viewName = window.appState.currentView;
             const displayUserId = userId ? userId.substring(0, 8) + '...' : 'Loading...';
             
             // Konten navigasi internal, di-render di awal setiap view
             return `
                <div class="bg-white p-4 rounded-xl shadow-md border border-gray-100 mb-6 flex justify-between items-center flex-wrap">
                    <div class="text-xl font-black text-gray-800 tracking-wide mr-4">APLIKASI KASIR</div>
                    <div class="flex space-x-3 text-sm font-semibold">
                        <button onclick="changeView('form')" id="nav-form" class="py-2 px-4 rounded-lg transition">📝 Input Transaksi</button>
                        <button onclick="changeView('browse')" id="nav-browse" class="py-2 px-4 rounded-lg transition">📂 Browse Data</button>
                        <button onclick="changeView('report')" id="nav-report" class="py-2 px-4 rounded-lg transition">📊 Laporan Bulanan</button>
                    </div>
                    <div class="text-xs text-gray-500 mt-2 sm:mt-0">
                        User ID: <span class="font-mono">${displayUserId}</span>
                    </div>
                </div>
            `;
        }

        window.renderApp = () => {
            const container = document.getElementById('app-container-content');
            if (!container) return;

            const views = {
                'form': renderFormView,
                'browse': renderBrowseView,
                'report': renderReportView
            };
            
            // RENDER: Navigasi Internal + Konten Utama
            container.innerHTML = renderInternalNav() + views[window.appState.currentView]();
            
            // Logic Fix: Sembunyikan Overlay setelah render konten utama selesai
            document.getElementById('loadingOverlay')?.classList.add('hidden');

            // Re-initialization logic
            const viewName = window.appState.currentView;
            if (viewName === 'form') {
                initFormView(window.appState.currentFormType);
            } else if (viewName === 'report') {
                window.filterReports();
            }

            // Update Navbar Styles (dilakukan setelah elemen dibuat di renderInternalNav)
            const navForm = document.getElementById('nav-form');
            if (navForm) {
                const navBrowse = document.getElementById('nav-browse');
                const navReport = document.getElementById('nav-report');

                [navForm, navBrowse, navReport].forEach(el => {
                    if (el) {
                        el.classList.remove('bg-blue-600', 'text-white', 'shadow-md', 'text-gray-600', 'hover:bg-gray-100');
                        el.classList.add('text-gray-600', 'hover:bg-gray-100');
                    }
                });

                const activeNav = document.getElementById(`nav-${viewName}`);
                if (activeNav) {
                    activeNav.classList.add('bg-blue-600', 'text-white', 'shadow-md');
                    activeNav.classList.remove('text-gray-600', 'hover:bg-gray-100');
                }
            }
        };

        window.changeView = (viewName) => {
            window.appState.currentView = viewName;
            window.renderApp();
        };

        window.changeFormType = (type) => {
            window.appState.currentFormType = type;
            window.renderApp(); // Re-render untuk mengupdate view
        };
        
        // --- FORM VIEW LOGIC (Create/Entry) ---
        function initFormView(type) {
            const isPemasukan = type === 'Pemasukan';
            
            const formTitleEl = document.getElementById('formTitle');
            if (formTitleEl) formTitleEl.textContent = isPemasukan ? 'Bukti Pemasukan Kas/Bank (BKM)' : 'Bukti Pengeluaran Kas/Bank (BKK)';
            
            document.getElementById('btnPemasukan').className = isPemasukan 
                ? 'px-4 py-2 rounded-lg text-sm font-semibold bg-blue-600 text-white shadow-lg'
                : 'px-4 py-2 rounded-lg text-sm font-semibold bg-gray-200 text-gray-700 hover:bg-gray-300';
            
            document.getElementById('btnPengeluaran').className = isPemasukan
                ? 'px-4 py-2 rounded-lg text-sm font-semibold bg-gray-200 text-gray-700 hover:bg-gray-300'
                : 'px-4 py-2 rounded-lg text-sm font-semibold bg-red-600 text-white shadow-lg';
            

            window.appState.coaSelections = [];
            window.appState.proofFile = null;
            
            const form = document.getElementById('transactionForm');
            if(form) form.reset();
            
            // 🔥 FIX 1: Pastikan renderTable dipanggil di akhir init
            window.appState.coaSelections.push({
                coa: window.appState.coaList[0],
                debit: 0,
                kredit: 0,
                isCashBank: false,
                note: ''
            });
            window.appState.coaSelections.push({
                coa: '1101 - Kas Kecil',
                debit: 0,
                kredit: 0,
                isCashBank: true,
                note: ''
            });
            
            // Call render methods here
            renderJournalTable();
            updateTerbilang(); // Dipanggil di sini untuk memastikan Terbilang "Nol Rupiah"
            
            const proofEl = document.getElementById('proofFilename');
            if (proofEl) proofEl.textContent = 'Belum ada file dipilih.';
        }

        function addDetailRow(isCashBank = false) {
             window.appState.coaSelections.push({
                coa: window.appState.coaList[0],
                debit: 0,
                kredit: 0,
                isCashBank: false,
                note: ''
            });

            renderJournalTable();
        }

        function renderJournalTable() {
            const body = document.getElementById('journalBody');
            const totalDebitEl = document.getElementById('totalDebit');
            const totalKreditEl = document.getElementById('totalKredit');

            if (!body || !totalDebitEl || !totalKreditEl) return;

            body.innerHTML = '';
            let totalDebit = 0;
            let totalKredit = 0;
            const isPemasukan = window.appState.currentFormType === 'Pemasukan';

            window.appState.coaSelections.forEach((item, index) => {
                const isCashBankRow = item.isCashBank;
                
                // Ambil nilai amountInput dari input DOM, BUKAN dari state coaSelections yang lain.
                const amountInput = parseFloat(document.getElementById('amountInput')?.value) || 0;
                
                if (isCashBankRow) {
                    // Baris Kas/Bank: Nilai total otomatis dari input Jumlah Uang
                    item.debit = isPemasukan ? amountInput : 0;
                    item.kredit = isPemasukan ? 0 : amountInput;
                }

                totalDebit += item.debit;
                totalKredit += item.kredit;

                const row = document.createElement('tr');
                row.className = `border-b ${isCashBankRow ? 'bg-yellow-50 font-bold' : 'hover:bg-gray-50'}`;
                
                let coaSelect = `<select data-index="${index}" onchange="updateJournalCoa(this)" class="print-input-journal w-full ${isCashBankRow ? 'font-bold bg-yellow-100' : ''}" ${isCashBankRow ? '' : 'required'}>`;
                
                const options = window.appState.coaList.filter(coa => coa.startsWith('110')); // Hanya COA Kas/Bank untuk baris ini
                
                options.forEach(coa => {
                    coaSelect += `<option value="${coa}" ${item.coa === coa ? 'selected' : ''}>${coa}</option>`;
                });
                coaSelect += '</select>';

                let actualDebitInput;
                let actualKreditInput;

                if (!isCashBankRow) {
                    if (isPemasukan) {
                        actualDebitInput = `<input type="number" data-index="${index}" data-type="debit" oninput="updateJournalAmount(this)" class="print-input-journal text-right bg-gray-200" value="${item.debit.toFixed(0)}" placeholder="0" readonly>`;
                        actualKreditInput = `<input type="number" data-index="${index}" data-type="kredit" oninput="updateJournalAmount(this)" class="print-input-journal text-right bg-white" value="${item.kredit.toFixed(0)}" placeholder="0">`;
                    } else {
                        actualDebitInput = `<input type="number" data-index="${index}" data-type="debit" oninput="updateJournalAmount(this)" class="print-input-journal text-right bg-white" value="${item.debit.toFixed(0)}" placeholder="0">`;
                        actualKreditInput = `<input type="number" data-index="${index}" data-type="kredit" oninput="updateJournalAmount(this)" class="print-input-journal text-right bg-gray-200" value="${item.kredit.toFixed(0)}" placeholder="0" readonly>`;
                    }
                } else {
                    // Kas/Bank Row - Readonly for both
                    actualDebitInput = `<input type="number" data-index="${index}" data-type="debit" class="print-input-journal text-right bg-yellow-100 font-bold" value="${item.debit.toFixed(0)}" placeholder="0" readonly>`;
                    actualKreditInput = `<input type="number" data-index="${index}" data-type="kredit" class="print-input-journal text-right bg-yellow-100 font-bold" value="${item.kredit.toFixed(0)}" placeholder="0" readonly>`;
                }
                
                const noteInput = `<input type="text" data-index="${index}" oninput="updateJournalNote(this)" class="print-input-journal text-sm bg-white border-b border-gray-200" value="${item.note}" placeholder="Keterangan baris (opsional)">`;

                row.innerHTML = `
                    <td class="p-2 text-center text-xs border">${index + 1}</td>
                    <td class="p-2 text-sm border">${coaSelect}</td>
                    <td class="p-2 text-xs border">${noteInput}</td>
                    <td class="p-2 border">${actualDebitInput}</td>
                    <td class="p-2 border">${actualKreditInput}</td>
                    <td class="p-2 text-center no-print border">
                        ${!isCashBankRow ? `<button onclick="removeDetailRow(${index})" class="text-red-500 hover:text-red-700">&times;</button>` : ''}
                    </td>
                `;
                body.appendChild(row);
            });

            totalDebitEl.textContent = formatRupiah(totalDebit);
            totalKreditEl.textContent = formatRupiah(totalKredit);
            
            const isBalanced = totalDebit === totalKredit && totalDebit > 0;
            const totalFooter = document.getElementById('journalTotalFooter');
            totalFooter.className = `font-bold text-sm ${isBalanced ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`;
        }

        window.handleAmountInput = (el) => {
            const value = parseFloat(el.value) || 0;
            const isPemasukan = window.appState.currentFormType === 'Pemasukan';
        
            // Update BARIS KAS/BANK SAJA di state
            window.appState.coaSelections.forEach(item => {
                if (item.isCashBank) {
                    item.debit  = isPemasukan ? value : 0;
                    item.kredit = isPemasukan ? 0 : value;
                }
            });

            updateTerbilang();
        };

        window.updateJournalCoa = (el) => {
            const index = parseInt(el.dataset.index);
            window.appState.coaSelections[index].coa = el.value;
            const isCashBankRow = window.appState.coaSelections[index].isCashBank;
            if (isCashBankRow && !el.value.startsWith('110')) {
                 el.value = window.appState.coaSelections[index].coa;
            }
        };

        window.updateJournalNote = (el) => {
            const index = parseInt(el.dataset.index);
            window.appState.coaSelections[index].note = el.value;
        };

        window.updateJournalAmount = (el) => {
             const index = parseInt(el.dataset.index);
             const type = el.dataset.type;
             let value = parseFloat(el.value) || 0;
             value = Math.max(0, value);

             // Ensure only one column has value (if manual entry)
             if (type === 'debit') {
                window.appState.coaSelections[index].debit = value;
                window.appState.coaSelections[index].kredit = 0;
             } else {
                window.appState.coaSelections[index].kredit = value;
                window.appState.coaSelections[index].debit = 0;
             }
             
             // Setelah Akun Lawan diisi, update total transaksi
             updateTotalJournal();
        };

        window.updateTotalJournal = () => {
             const cashBankIndex = window.appState.coaSelections.findIndex(item => item.isCashBank);
             let totalDetailAmount = 0;
             const isPemasukan = window.appState.currentFormType === 'Pemasukan';

             window.appState.coaSelections.forEach((item, index) => {
                if (index !== cashBankIndex) {
                    // Sum the active side (Kredit for Pemasukan, Debit for Pengeluaran)
                    totalDetailAmount += (isPemasukan ? item.kredit : item.debit);
                }
             });

             // Update input amount dan terbilang
             const amountInputEl = document.getElementById('amountInput');
             if (amountInputEl) {
                 amountInputEl.value = totalDetailAmount.toFixed(0);
             }
             
             updateTerbilang(); // Rerender terbilang dan tabel
        };
        
        window.removeDetailRow = (index) => {
            if (window.appState.coaSelections[index].isCashBank) return;
            
            window.appState.coaSelections.splice(index, 1);
            
            updateTotalJournal();
        };

        // --- UTILS ---
        function terbilang(amount) {
            const num = parseInt(amount) || 0;
            if (num === 0) return 'Nol Rupiah';
            if (num > 1000000000000) return 'Jumlah Terlalu Besar'; 
            
            const units = ['', 'Ribu', 'Juta', 'Miliar', 'Triliun'];
            let result = '';
            let unitIndex = 0;
            let temp = num;

            while (temp > 0) {
                const chunk = temp % 1000;
                if (chunk > 0) {
                    let text = chunkToWords(chunk);
                    result = text + ' ' + units[unitIndex] + ' ' + result;
                }
                temp = Math.floor(temp / 1000);
                unitIndex++;
            }
            // Fix double spaces and trailing 'Rupiah'
            let finalResult = result.trim().replace(/\s+/g, ' ');
            if (finalResult.toLowerCase().startsWith('satu ribu')) {
                 finalResult = 'Seribu' + finalResult.substring(9);
            }
            return finalResult + ' Rupiah';
        }

        function chunkToWords(n) {
            const ones = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan'];
            const tens = ['', '', 'Dua Puluh', 'Tiga Puluh', 'Empat Puluh', 'Lima Puluh', 'Enam Puluh', 'Tujuh Puluh', 'Delapan Puluh', 'Sembilan Puluh'];
            const teens = ['Sepuluh', 'Sebelas', 'Dua Belas', 'Tiga Belas', 'Empat Belas', 'Lima Belas', 'Enam Belas', 'Tujuh Belas', 'Delapan Belas', 'Sembilan Belas'];

            let s = '';
            let h = Math.floor(n / 100);
            let t = n % 100;
            
            if (h > 0) {
                s += ones[h] + ' Ratus ';
            }
            if (t >= 20) {
                s += tens[Math.floor(t / 10)] + ' ';
                t %= 10;
            } else if (t >= 10) {
                s += teens[t - 10] + ' ';
                t = 0;
            }
            if (t > 0) {
                s += ones[t] + ' ';
            }
            
            s = s.replace('Satu Ratus', 'Seratus');
            
            return s.trim();
        }

        function formatRupiah(amount) {
            const number = parseFloat(amount) || 0;
            return 'Rp ' + number.toLocaleString('id-ID', { minimumFractionDigits: 0 });
        }
        
        function formatDate(date) {
             if (!(date instanceof Date)) {
                // Check if it's a Firestore Timestamp object
                if (date && typeof date.toDate === 'function') {
                    date = date.toDate();
                } else {
                    return 'N/A';
                }
            }
            return date.toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric' });
        }

        // --- UI/UX FEEDBACK ---
        function showLoading(show, message = 'Loading...') {
            const loader = document.getElementById('loadingOverlay');
            if (loader) {
                loader.classList.toggle('hidden', !show);
                loader.querySelector('span').textContent = message;
            }
        }
        
        function showSuccess(message) {
            const msgEl = document.getElementById('statusMessage');
            msgEl.textContent = message;
            msgEl.className = 'p-3 bg-green-100 text-green-700 rounded-lg shadow-md mb-4';
            setTimeout(() => {
                const currentMsgEl = document.getElementById('statusMessage');
                if (currentMsgEl && currentMsgEl.textContent === message) {
                    currentMsgEl.textContent = '';
                }
            }, 5000);
        }

        function showError(message) {
            const msgEl = document.getElementById('statusMessage');
            msgEl.textContent = message;
            msgEl.className = 'p-3 bg-red-100 text-red-700 rounded-lg shadow-md mb-4';
            setTimeout(() => {
                const currentMsgEl = document.getElementById('statusMessage');
                if (currentMsgEl && currentMsgEl.textContent === message) {
                    currentMsgEl.textContent = '';
                }
            }, 8000);
        }

        // --- VIEW RENDERING (CONTENT VIEWS) ---
        
        function renderFormView() { 
            const isPemasukan = window.appState.currentFormType === 'Pemasukan';
            const color = isPemasukan ? 'blue' : 'red';
            const textColor = isPemasukan ? 'text-blue-700' : 'text-red-700';

            // Konten Murni Form
            return `
                <div class="pb-10">
                    <div class="w-full max-w-5xl mx-auto">
                        <div class="flex justify-between items-center mb-6 no-print">
                            <h1 id="formTitle" class="text-3xl font-bold text-gray-800"></h1>
                            <div class="flex gap-2">
                                <button onclick="changeFormType('Pemasukan')" id="btnPemasukan" class="px-4 py-2 rounded-lg text-sm font-semibold"></button>
                                <button onclick="changeFormType('Pengeluaran')" id="btnPengeluaran" class="px-4 py-2 rounded-lg text-sm font-semibold"></button>
                            </div>
                        </div>
                        <div id="statusMessage" class="min-h-[40px] text-center"></div>

                        <div class="bg-white rounded-xl shadow-2xl border border-gray-100 overflow-hidden print-area">
                            <form id="transactionForm" class="p-8 space-y-8">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 border-b pb-6 border-${color}-100">
                                    <div class="md:col-span-1">
                                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">NO. BUKTI</label>
                                        <input type="text" class="print-input-header" placeholder="Contoh: BKM-2025/001" value="AUTO-DRAFT" required>
                                    </div>
                                    <div class="md:col-span-1">
                                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">TANGGAL TRANSAKSI</label>
                                        <input type="date" class="print-input-header" value="${new Date().toISOString().substring(0, 10)}" required>
                                    </div>
                                    <div class="md:col-span-1">
                                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">${isPemasukan ? 'DITERIMA DARI' : 'DIBAYAR KEPADA'}</label>
                                        <input type="text" class="print-input-header" placeholder="Nama Perusahaan/Individu" required>
                                    </div>
                                    <div class="md:col-span-3">
                                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">KETERANGAN / URAIAN</label>
                                        <textarea rows="1" class="print-input-header" placeholder="Contoh: Pembayaran hutang pengiriman IMP-251212-236" required></textarea>
                                    </div>
                                </div>

                                <div class="grid grid-cols-5 gap-6">
                                    <div class="col-span-5 md:col-span-3">
                                        <h3 class="text-sm font-bold text-gray-800 mb-3 border-b pb-1">DETAIL JURNAL <span class="text-xs text-red-500">(Pastikan Debit = Kredit)</span></h3>
                                        <table class="w-full text-sm border-collapse border">
                                            <thead>
                                                <tr class="bg-gray-100 font-bold text-xs uppercase text-gray-600">
                                                    <th class="p-2 w-8 text-center border">No</th>
                                                    <th class="p-2 w-48 text-left border">Kode Akun (COA)</th>
                                                    <th class="p-2 w-48 text-left border">Keterangan Baris</th>
                                                    <th class="p-2 w-32 text-right border">Debit</th>
                                                    <th class="p-2 w-32 text-right border">Kredit</th>
                                                    <th class="p-2 w-10 text-center no-print border"></th>
                                                </tr>
                                            </thead>
                                            <tbody id="journalBody" class="divide-y divide-gray-200">
                                                <!-- Rows rendered by renderJournalTable() -->
                                            </tbody>
                                            <tfoot>
                                                <tr id="journalTotalFooter" class="font-bold text-sm">
                                                    <td colspan="3" class="p-2 text-right uppercase border">Total Jurnal</td>
                                                    <td id="totalDebit" class="p-2 text-right border">Rp 0</td>
                                                    <td id="totalKredit" class="p-2 text-right border">Rp 0</td>
                                                    <td class="p-2 no-print border"></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                        <div class="flex justify-between items-center mt-3 no-print">
                                            <button type="button" onclick="addDetailRow(false)" class="text-xs text-gray-600 hover:text-blue-600 flex items-center gap-1 font-semibold">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg> Tambah Akun Lawan
                                            </button>
                                            <span class="text-xs text-red-500 italic">Akun Kas/Bank terisi otomatis dari Jumlah Transaksi.</span>
                                        </div>
                                    </div>
                                    
                                    <div class="col-span-5 md:col-span-2 space-y-4">
                                        <div class="border border-${color}-300 bg-${color}-50 p-4 rounded-xl shadow-inner">
                                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">TOTAL TRANSAKSI (ANGKA)</label>
                                            <input type="number" id="amountInput" oninput="handleAmountInput(this)" class="print-input-header text-3xl font-extrabold ${textColor} bg-transparent p-0 border-none" placeholder="0" required>
                                            <div class="mt-3 text-sm font-bold text-gray-600 border-t pt-2 border-${color}-200">Terbilang:</div>
                                            <div id="terbilangDisplay" class="bg-white p-2 text-sm italic font-semibold rounded-md min-h-[40px] border border-gray-300">Nol Rupiah</div>
                                        </div>

                                        <div class="p-4 bg-white rounded-xl border border-gray-200 space-y-2 shadow-sm no-print">
                                            <h4 class="text-sm font-bold text-gray-800">Bukti Transaksi (Attachment)</h4>
                                            <label class="block">
                                                <span class="sr-only">Choose Proof File</span>
                                                <input type="file" onchange="handleProofUpload(event)" accept="image/*, application/pdf" class="block w-full text-sm text-gray-500
                                                    file:mr-4 file:py-2 file:px-4
                                                    file:rounded-full file:border-0
                                                    file:text-sm file:font-semibold
                                                    file:bg-blue-50 file:text-blue-700
                                                    hover:file:bg-blue-100
                                                "/>
                                            </label>
                                            <p id="proofFilename" class="text-xs text-gray-500 italic mt-1">Maks. 5MB. Gambar akan dikompres otomatis (JPEG 800x600). PDF tidak dikompres.</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-4 gap-4 pt-8 text-center text-xs print-only print-signs">
                                    <div>
                                        <p class="font-bold">Dibuat Oleh</p>
                                        <div class="h-16 border-b border-dashed border-gray-400 mt-2"></div>
                                        <p class="text-gray-500 mt-1">(Staf Finance/Kasir)</p>
                                    </div>
                                    <div>
                                        <p class="font-bold">Diperiksa Oleh</p>
                                        <div class="h-16 border-b border-dashed border-gray-400 mt-2"></div>
                                        <p class="text-gray-500 mt-1">(Supervisor)</p>
                                    </div>
                                    <div>
                                        <p class="font-bold">Disetujui Oleh</p>
                                        <div class="h-16 border-b border-dashed border-gray-400 mt-2"></div>
                                        <p class="text-gray-500 mt-1">(Manajer/Direktur)</p>
                                    </div>
                                    <div>
                                        <p class="font-bold ${textColor}">${isPemasukan ? 'Penyetor / Diterima' : 'Penerima / Dibayar'}</p>
                                        <div class="h-16 border-b border-dashed border-gray-400 mt-2"></div>
                                        <p class="text-gray-500 mt-1">(${isPemasukan ? 'Nama Penyetor' : 'Nama Penerima'})</p>
                                    </div>
                                </div>
                            </form>
                            
                            <div class="bg-gray-50 px-8 py-5 border-t flex justify-between items-center no-print">
                                <button onclick="window.print()" class="px-4 py-2 bg-green-500 text-white rounded-lg font-bold hover:bg-green-600 transition shadow-md flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-5a2 2 0 00-2-2H5a2 2 0 00-2 2v5a2 2 0 002 2h2m0 0v-4a2 2 0 012-2h6a2 2 0 012 2v4m-8-5v5m-1-5h2"></path></svg>
                                    Cetak Bukti (A4)
                                </button>
                                <button onclick="saveTransaction()" class="px-6 py-3 bg-blue-600 text-white rounded-xl text-lg font-bold hover:bg-blue-700 transition shadow-xl transform hover:scale-[1.02]">
                                    Simpan Transaksi & Buat Jurnal 🚀
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
        
        function renderBrowseView() { 
            const transactions = window.appState.transactions;
            const tableRows = transactions.map((t, index) => {
                const isPemasukan = t.type === 'Pemasukan';
                const typeClass = isPemasukan ? 'text-blue-600 bg-blue-100' : 'text-red-600 bg-red-100';
                
                return `
                    <tr class="border-b hover:bg-gray-50 transition">
                        <td class="p-4 text-center text-xs">${index + 1}</td>
                        <td class="p-4 text-sm font-mono text-gray-700">${t.receiptNo}</td>
                        <td class="p-4 text-sm">${formatDate(t.transactionDate)}</td>
                        <td class="p-4 text-sm font-semibold">${t.paidToFrom}</td>
                        <td class="p-4 text-center text-xs font-bold ${typeClass} rounded-full">${t.type}</td>
                        <td class="p-4 text-right font-bold text-gray-800">${formatRupiah(t.amount)}</td>
                        <td class="p-4 text-center whitespace-nowrap">
                            <button onclick="viewJournal('${t.id}')" class="p-2 text-blue-600 hover:bg-blue-100 rounded-full" title="Lihat Jurnal">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            </button>
                            ${t.proofBase64 ? `<a href="${t.proofBase64}" target="_blank" class="p-2 text-purple-600 hover:bg-purple-100 rounded-full" title="Lihat Bukti Upload">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </a>` : `<span class="p-2 text-gray-400" title="Tidak ada bukti">&mdash;</span>`}
                        </td>
                    </tr>
                `;
            }).join('');
            
            return `
                <div class="pt-8 pb-10">
                    <div class="w-full max-w-7xl mx-auto">
                        <h1 class="text-3xl font-bold text-gray-800 mb-6">📂 Daftar Transaksi Kas/Bank</h1>
                        
                        <div id="statusMessage" class="mb-4"></div>

                        <div class="bg-white rounded-xl shadow-2xl border border-gray-100 overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="p-4 text-xs font-bold text-gray-600 uppercase w-10">No</th>
                                        <th class="p-4 text-xs font-bold text-gray-600 uppercase text-left">No. Bukti</th>
                                        <th class="p-4 text-xs font-bold text-gray-600 uppercase text-left">Tanggal</th>
                                        <th class="p-4 text-xs font-bold text-gray-600 uppercase text-left">Diterima/Dibayar Kepada</th>
                                        <th class="p-4 text-xs font-bold text-gray-600 uppercase text-center w-28">Tipe</th>
                                        <th class="p-4 text-xs font-bold text-gray-600 uppercase text-right w-40">Jumlah</th>
                                        <th class="p-4 text-xs font-bold text-gray-600 uppercase text-center w-32">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${transactions.length > 0 ? tableRows : '<tr><td colspan="7" class="p-8 text-center text-gray-500 italic">Belum ada data transaksi tersimpan.</td></tr>'}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        }
        function renderReportView(filteredTransactions = window.appState.transactions, selectedMonth = null, selectedYear = null) {
            const years = Array.from({length: 5}, (_, i) => new Date().getFullYear() - i);
            const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            
            const defaultMonth = new Date().getMonth() + 1;
            const defaultYear = new Date().getFullYear();

            return `
                <div class="pt-8 pb-10">
                    <div class="w-full max-w-4xl mx-auto">
                        <h1 class="text-3xl font-bold text-gray-800 mb-6">📊 Laporan Rekapitulasi Jurnal (Bulanan)</h1>
                        
                        <div class="bg-white p-6 rounded-xl shadow-md mb-6 flex space-x-4 items-end no-print">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Bulan</label>
                                <select id="reportMonth" onchange="filterReports()" class="p-2 border rounded-lg text-sm w-32">
                                    <option value="">-- Semua Bulan --</option>
                                    ${months.map((m, i) => `<option value="${i + 1}" ${i + 1 == defaultMonth ? 'selected' : ''}>${m}</option>`).join('')}
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Tahun</label>
                                <select id="reportYear" onchange="filterReports()" class="p-2 border rounded-lg text-sm w-24">
                                    ${years.map(y => `<option value="${y}" ${y == defaultYear ? 'selected' : ''}>${y}</option>`).join('')}
                                </select>
                            </div>
                            <button onclick="downloadReportCSV()" class="px-4 py-2 bg-orange-600 text-white rounded-lg font-bold hover:bg-orange-700 transition flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                Download CSV
                            </button>
                            <button onclick="window.print()" class="px-4 py-2 bg-green-600 text-white rounded-lg font-bold hover:bg-green-700 transition flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-5a2 2 0 00-2-2H5a2 2 0 00-2 2v5a2 2 0 002 2h2m0 0v-4a2 2 0 012-2h6a2 2 0 012 2v4m-8-5v5m-1-5h2"></path></svg>
                                Cetak Laporan
                            </button>
                        </div>

                        <div id="reportContainer" class="bg-white p-6 rounded-xl shadow-2xl border border-gray-100 overflow-hidden">
                            <!-- Report content rendered by filterReports -->
                        </div>
                    </div>
                </div>
            `;
        }
        
        function renderReportContent(transactions, selectedMonth, selectedYear) {
             const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

             let reportData = {};
             let globalTotalDebit = 0;
             let globalTotalKredit = 0;

             transactions.forEach(t => {
                 if (Array.isArray(t.journal)) {
                     t.journal.forEach(j => {
                         if (!reportData[j.coa]) {
                             reportData[j.coa] = { debit: 0, kredit: 0 };
                         }
                         reportData[j.coa].debit += j.debit;
                         reportData[j.coa].kredit += j.kredit;
                         
                         globalTotalDebit += j.debit;
                         globalTotalKredit += j.kredit;
                     });
                 }
             });

             const reportRows = Object.entries(reportData).sort(([coaA], [coaB]) => coaA.localeCompare(coaB)).map(([coa, data], index) => `
                 <tr class="border-b hover:bg-gray-50">
                     <td class="p-3 text-sm font-bold text-gray-800">${coa}</td>
                     <td class="p-3 text-right text-green-700 font-semibold">${formatRupiah(data.debit)}</td>
                     <td class="p-3 text-right text-red-700 font-semibold">${formatRupiah(data.kredit)}</td>
                 </tr>
             `).join('');

             const periodTitle = (selectedMonth && selectedYear) 
                ? `${months[selectedMonth - 1].toUpperCase()} ${selectedYear}`
                : (selectedYear ? `TAHUN ${selectedYear}` : 'KESELURUHAN');

             return `
                 <h2 class="text-xl font-black text-gray-800 mb-4">REKAP TRANSAKSI ${periodTitle}</h2>
                 <table class="min-w-full divide-y divide-gray-200">
                     <thead class="bg-gray-100">
                         <tr>
                             <th class="p-3 text-xs font-bold text-gray-600 uppercase text-left">Kode Akun (COA)</th>
                             <th class="p-3 text-xs font-bold text-gray-600 uppercase text-right w-40">Total Debit</th>
                             <th class="p-3 text-xs font-bold text-gray-600 uppercase text-right w-40">Total Kredit</th>
                         </tr>
                     </thead>
                     <tbody>
                         ${reportRows.length > 0 ? reportRows : '<tr><td colspan="3" class="p-8 text-center text-gray-500 italic">Tidak ada data untuk periode ini.</td></tr>'}
                     </tbody>
                     <tfoot class="bg-gray-200">
                         <tr class="font-bold">
                             <td class="p-3 text-sm text-right">TOTAL KESELURUHAN</td>
                             <td class="p-3 text-right">${formatRupiah(globalTotalDebit)}</td>
                             <td class="p-3 text-right">${formatRupiah(globalTotalKredit)}</td>
                         </tr>
                     </tfoot>
                 </table>
             `;
        }

        window.viewJournal = (id) => {
            const transaction = window.appState.transactions.find(t => t.id === id);
            if (!transaction) return;
            
            const journalRows = (Array.isArray(transaction.journal) ? transaction.journal : []).map((j, index) => `
                <tr class="${j.isCashBank ? 'bg-yellow-50 font-bold' : 'bg-white'} border-b">
                    <td class="p-3 text-sm">${j.coa}</td>
                    <td class="p-3 text-sm text-gray-500">${j.note || '-'}</td>
                    <td class="p-3 text-right text-green-700">${formatRupiah(j.debit)}</td>
                    <td class="p-3 text-right text-red-700">${formatRupiah(j.kredit)}</td>
                </tr>
            `).join('');

            const totalDebit = (Array.isArray(transaction.journal) ? transaction.journal : []).reduce((sum, j) => sum + j.debit, 0);
            const totalKredit = (Array.isArray(transaction.journal) ? transaction.journal : []).reduce((sum, j) => sum + j.kredit, 0);

            const modalHtml = `
                <div id="journalModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
                    <div class="bg-white rounded-xl w-full max-w-xl shadow-2xl animate-fade-in-up border-t-8 border-blue-600 overflow-hidden">
                        <div class="p-5 border-b flex justify-between items-center">
                            <h3 class="text-xl font-bold text-gray-800">${transaction.type}: ${transaction.receiptNo}</h3>
                            <button onclick="document.getElementById('journalModal').remove()" class="text-gray-400 hover:text-red-500">&times;</button>
                        </div>
                        <div class="p-5 space-y-4">
                            <div class="text-xs text-gray-600 grid grid-cols-2 gap-2 border-b pb-3">
                                <div><span class="font-bold">Tanggal:</span> ${formatDate(transaction.transactionDate)}</div>
                                <div><span class="font-bold">Total:</span> <span class="text-lg font-extrabold text-blue-800">${formatRupiah(transaction.amount)}</span></div>
                                <div class="col-span-2"><span class="font-bold">Diterima/Dibayar Kepada:</span> ${transaction.paidToFrom}</div>
                                <div class="col-span-2"><span class="font-bold">Uraian Transaksi:</span> ${transaction.description}</div>
                            </div>
                            
                            <h4 class="font-bold text-sm text-gray-700 border-b pb-1">Jurnal Entries (Debit vs Kredit)</h4>
                            <table class="min-w-full">
                                <thead>
                                    <tr class="bg-gray-100 text-xs uppercase font-bold text-gray-600">
                                        <th class="p-2 text-left">Akun</th>
                                        <th class="p-2 text-left">Note</th>
                                        <th class="p-2 text-right w-1/5">Debit</th>
                                        <th class="p-2 text-right w-1/5">Kredit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${journalRows}
                                </tbody>
                                <tfoot>
                                    <tr class="font-bold bg-green-100 text-green-700">
                                        <td class="p-3 text-sm text-right" colspan="2">TOTAL SEIMBANG</td>
                                        <td class="p-3 text-right">${formatRupiah(totalDebit)}</td>
                                        <td class="p-3 text-right">${formatRupiah(totalKredit)}</td>
                                    </tr>
                                </tfoot>
                            </table>

                            ${transaction.proofBase64 ? `
                                <div class="pt-2">
                                    <h4 class="font-bold text-sm text-gray-700 border-b pb-1">Bukti Terlampir</h4>
                                    <a href="${transaction.proofBase64}" target="_blank" class="text-blue-600 hover:underline text-sm flex items-center gap-1 mt-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m-6-6L10 14"></path></svg>
                                        Klik untuk melihat Bukti
                                    </a>
                                </div>
                            ` : ''}

                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHtml);
        };


        // --- ENTRY POINT ---
        document.addEventListener('DOMContentLoaded', () => {
            initFirebase();
            // FIX 3: Failsafe Hiding (Opsional, tapi aman)
            setTimeout(forceHideLoading, 3000); 
        });
    </script>
@endonce

{{-- INI ADALAH SATU-SATUNYA ELEMEN ROOT YANG DIKEMBALIKAN OLEH KOMPONEN LIVEWIRE --}}
<div id="cashier-livewire-root" class="cashier-wrapper">

    {{-- 2. Main Content Area (Dynamic Content) --}}
    <div id="app-container-content" class="cashier-container">
        <!-- Content will be dynamically rendered by JavaScript -->
        <div class="text-center py-20 text-gray-500">Memuat aplikasi kasir...</div>
    </div>

    {{-- 3. Loading Overlay (Diubah menjadi absolute agar terikat pada wrapper) --}}
    <div id="loadingOverlay" class="cashier-loading-overlay hidden">
        <div class="cashier-loading-box">
            <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-sm font-semibold text-gray-700">Loading...</span>
        </div>
    </div>

</div>