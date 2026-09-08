import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

import dotenv from 'dotenv';
import { expect, test, type Page } from '@playwright/test';

// Kredensial & base URL dimuat dari .env.e2e (gitignored). Tidak ada nilai
// yang di-hardcode di file ini.
const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ENV_PATH = path.resolve(__dirname, '../.env.e2e');

/**
 * dotenv memperlakukan '#' di dalam nilai TANPA kutip sebagai awal komentar
 * inline, sehingga password seperti `Rahasia#2026` terbaca jadi `Rahasia` dan
 * login gagal tanpa pesan yang jelas. Nilai unquoted karena itu dibungkus kutip
 * dulu sebelum diserahkan ke dotenv.parse().
 * Perbaikan permanennya: kutip langsung nilainya di .env.e2e (VAR='...').
 */
function loadEnvFile(file: string): Record<string, string> {
    if (!fs.existsSync(file)) {
        return {};
    }

    const quoted = fs
        .readFileSync(file, 'utf8')
        .replace(
            /^(\s*(?:export\s+)?[\w.-]+\s*=[ \t]*)([^'"\r\n][^\r\n]*?)[ \t]*$/gm,
            (_match, assignment: string, value: string) =>
                value.includes("'")
                    ? `${assignment}"${value.replace(/\\/g, '\\\\').replace(/"/g, '\\"')}"`
                    : `${assignment}'${value}'`,
        );

    return dotenv.parse(quoted);
}

const fileEnv = loadEnvFile(ENV_PATH);

// process.env menang atas isi file, supaya nilai bisa di-override di CI.
function requireEnv(key: string): string {
    const value = process.env[key] || fileEnv[key];

    if (!value) {
        throw new Error(
            `Variabel ${key} tidak ditemukan. Isi ${path.basename(ENV_PATH)} di root repo (lihat contoh di file tersebut) sebelum menjalankan e2e.`,
        );
    }

    return value;
}

const BASE_URL = requireEnv('E2E_BASE_URL').replace(/\/+$/, '');
const ADMIN_EMAIL = requireEnv('E2E_ADMIN_EMAIL');
const ADMIN_PASSWORD = requireEnv('E2E_ADMIN_PASSWORD');
const FINANCE_EMAIL = requireEnv('E2E_FINANCE_EMAIL');
const FINANCE_PASSWORD = requireEnv('E2E_FINANCE_PASSWORD');

// Route khusus admin: AuditLogManager::render() memanggil abort(403) untuk user
// yang bukan admin level (>= 60) dan tidak punya permission audit_log.view.
const ADMIN_ONLY_PATH = '/admin/audit-logs';

/**
 * Halaman login memuat script pihak ketiga (CDN Tailwind, script afiliasi).
 * Diblokir agar test tidak bergantung pada jaringan luar.
 */
test.beforeEach(async ({ page }) => {
    await page.route('**/*', (route) => {
        const url = route.request().url();

        return url.startsWith(BASE_URL) ? route.continue() : route.abort();
    });
});

async function submitLogin(page: Page, email: string, password: string): Promise<void> {
    await page.goto(`${BASE_URL}/login`, { waitUntil: 'domcontentloaded' });

    await page.fill('#email', email);
    await page.fill('#password', password);

    await Promise.all([
        page.waitForLoadState('domcontentloaded'),
        page.getByRole('button', { name: 'SIGN IN' }).click(),
    ]);
}

test.describe('Autentikasi portal', () => {
    test('admin berhasil login dan masuk dashboard admin', async ({ page }) => {
        await submitLogin(page, ADMIN_EMAIL, ADMIN_PASSWORD);

        await expect(page).toHaveURL(`${BASE_URL}/admin/dashboard`);
        await expect(page).toHaveTitle('M2B Admin Panel');
    });

    test('password salah menampilkan error dan tetap di /login', async ({ page }) => {
        await submitLogin(page, ADMIN_EMAIL, `${ADMIN_PASSWORD}-salah`);

        await expect(page).toHaveURL(`${BASE_URL}/login`);
        await expect(page.getByText('Email atau password salah.')).toBeVisible();

        // Pastikan benar-benar belum terautentikasi: sesi anonim di / akan
        // dilempar balik ke /login, bukan ke dashboard.
        await page.goto(`${BASE_URL}/`, { waitUntil: 'domcontentloaded' });
        await expect(page).toHaveURL(`${BASE_URL}/login`);
    });

    test('user finance ditolak saat mengakses route khusus admin', async ({ page }) => {
        await submitLogin(page, FINANCE_EMAIL, FINANCE_PASSWORD);
        await expect(page).not.toHaveURL(`${BASE_URL}/login`);

        const response = await page.goto(`${BASE_URL}${ADMIN_ONLY_PATH}`, {
            waitUntil: 'domcontentloaded',
        });

        const status = response?.status();
        const landedPath = new URL(page.url()).pathname;

        const forbidden = status === 403;
        const redirectedAway = landedPath !== ADMIN_ONLY_PATH;

        expect(
            forbidden || redirectedAway,
            `Diharapkan 403 atau redirect untuk role finance di ${ADMIN_ONLY_PATH}, ` +
                `tetapi dapat status ${status} di ${landedPath}.`,
        ).toBeTruthy();

        if (forbidden) {
            await expect(page.locator('body')).not.toContainText('Audit Log');
        }
    });

    test('user finance berhak mengakses modul penggajian', async ({ page }) => {
        await submitLogin(page, FINANCE_EMAIL, FINANCE_PASSWORD);
        await expect(page).not.toHaveURL(`${BASE_URL}/login`);

        const response = await page.goto(`${BASE_URL}/admin/hrd/penggajian`, {
            waitUntil: 'domcontentloaded',
        });

        expect(response?.status()).toBe(200);
        await expect(page.locator('body')).toContainText('Periode Penggajian');
    });

    test('route debugging /test-inbox sudah tidak ada (404)', async ({ page }) => {
        await submitLogin(page, ADMIN_EMAIL, ADMIN_PASSWORD);
        const response = await page.goto(`${BASE_URL}/test-inbox`, {
            waitUntil: 'domcontentloaded',
        });

        expect(response?.status()).toBe(404);
    });
});
