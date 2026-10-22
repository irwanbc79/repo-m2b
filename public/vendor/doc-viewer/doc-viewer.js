/**
 * M2B Portal - Client-Side Multi-Format Document Viewer
 * Supports: DOCX (docx-preview + jszip), XLSX/XLS/CSV (SheetJS), Images, Text
 */
(function() {
    window.__m2bLoadedScripts = window.__m2bLoadedScripts || {};

    window.ensureScriptLoaded = function(src, globalVarName) {
        return new Promise((resolve, reject) => {
            if (globalVarName && window[globalVarName]) {
                return resolve(window[globalVarName]);
            }
            if (window.__m2bLoadedScripts[src]) {
                return window.__m2bLoadedScripts[src].then(() => resolve(window[globalVarName])).catch(reject);
            }
            window.__m2bLoadedScripts[src] = new Promise((res, rej) => {
                const existing = document.querySelector(`script[src="${src}"]`);
                if (existing) {
                    if (globalVarName && window[globalVarName]) {
                        res(window[globalVarName]);
                    } else {
                        existing.addEventListener('load', () => res(window[globalVarName]));
                        existing.addEventListener('error', rej);
                    }
                    return;
                }
                const script = document.createElement('script');
                script.src = src;
                script.async = true;
                script.onload = () => res(window[globalVarName]);
                script.onerror = () => rej(new Error('Gagal memuat script pendukung: ' + src));
                document.head.appendChild(script);
            });

            return window.__m2bLoadedScripts[src].then(() => resolve(window[globalVarName])).catch(reject);
        });
    };

    window.docxViewerComponent = function(docUrl, targetId, assetsBase) {
        return {
            loading: true,
            error: false,
            errorMsg: '',
            async load() {
                this.loading = true;
                this.error = false;
                this.errorMsg = '';
                try {
                    const jszipUrl = assetsBase + '/jszip.min.js';
                    const docxUrl = assetsBase + '/docx-preview.min.js';
                    await window.ensureScriptLoaded(jszipUrl, 'JSZip');
                    await window.ensureScriptLoaded(docxUrl, 'docx');

                    const response = await fetch(docUrl, { credentials: 'same-origin' });
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: Gagal mengunduh dokumen dari server.`);
                    }
                    const blob = await response.blob();
                    const container = document.getElementById(targetId);
                    if (!container) return;
                    container.innerHTML = '';

                    await window.docx.renderAsync(blob, container, null, {
                        className: 'docx',
                        inWrapper: true,
                        ignoreWidth: false,
                        ignoreHeight: false,
                        breakPages: true,
                        ignoreLastRenderedPageBreak: false,
                        experimental: true,
                        renderHeaders: true,
                        renderFooters: true
                    });
                    this.loading = false;
                } catch (err) {
                    console.error('Docx preview error:', err);
                    this.error = true;
                    this.errorMsg = err.message || 'Format dokumen Word tidak valid atau file rusak.';
                    this.loading = false;
                }
            }
        };
    };

    window.xlsxViewerComponent = function(docUrl, assetsBase) {
        return {
            loading: true,
            error: false,
            errorMsg: '',
            workbook: null,
            sheetNames: [],
            activeSheet: '',
            sheetData: [],
            maxCols: 0,
            searchQuery: '',
            async load() {
                this.loading = true;
                this.error = false;
                this.errorMsg = '';
                try {
                    const xlsxUrl = assetsBase + '/xlsx.full.min.js';
                    await window.ensureScriptLoaded(xlsxUrl, 'XLSX');

                    const response = await fetch(docUrl, { credentials: 'same-origin' });
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: Gagal mengunduh file spreadsheet dari server.`);
                    }
                    const buffer = await response.arrayBuffer();
                    this.workbook = window.XLSX.read(buffer, { type: 'array', cellDates: true });
                    this.sheetNames = this.workbook.SheetNames || [];
                    if (this.sheetNames.length > 0) {
                        this.selectSheet(this.sheetNames[0]);
                    } else {
                        throw new Error('Tidak ada lembar kerja (worksheet) ditemukan di dalam file Excel.');
                    }
                    this.loading = false;
                } catch (err) {
                    console.error('Xlsx preview error:', err);
                    this.error = true;
                    this.errorMsg = err.message || 'File Excel tidak dapat dibaca.';
                    this.loading = false;
                }
            },
            selectSheet(name) {
                this.activeSheet = name;
                this.searchQuery = '';
                const sheet = this.workbook.Sheets[name];
                if (!sheet) {
                    this.sheetData = [];
                    this.maxCols = 0;
                    return;
                }
                const raw = window.XLSX.utils.sheet_to_json(sheet, { header: 1, defval: '' });
                this.sheetData = raw;
                const calculatedMax = Math.max(...raw.map(r => (Array.isArray(r) ? r.length : 0)), 1);
                this.maxCols = Math.min(calculatedMax, 60);
            },
            get filteredRows() {
                if (!this.searchQuery || !this.searchQuery.trim()) {
                    return this.sheetData;
                }
                const q = this.searchQuery.trim().toLowerCase();
                return this.sheetData.filter(row => {
                    if (!Array.isArray(row)) return false;
                    return row.some(cell => String(cell ?? '').toLowerCase().includes(q));
                });
            },
            getColHeader(idx) {
                let letter = '';
                let num = idx;
                while (num >= 0) {
                    letter = String.fromCharCode((num % 26) + 65) + letter;
                    num = Math.floor(num / 26) - 1;
                }
                return letter;
            }
        };
    };

    window.txtViewerComponent = function(docUrl) {
        return {
            loading: true,
            error: false,
            errorMsg: '',
            content: '',
            async load() {
                this.loading = true;
                this.error = false;
                try {
                    const response = await fetch(docUrl, { credentials: 'same-origin' });
                    if (!response.ok) throw new Error('HTTP ' + response.status);
                    this.content = await response.text();
                    this.loading = false;
                } catch (e) {
                    this.error = true;
                    this.errorMsg = e.message || 'Gagal memuat teks.';
                    this.loading = false;
                }
            }
        };
    };
})();
