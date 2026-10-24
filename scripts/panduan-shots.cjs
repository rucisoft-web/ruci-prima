// Membuat gambar layar panduan (public/img/panduan/*.png, lalu dikonversi ke .webp).
// Pakai: jalankan aplikasi dengan data contoh (php artisan serve), lalu
//   node scripts/panduan-shots.cjs http://127.0.0.1:8000 <id-kendaraan> <id-work-order> <token-qr>
const { chromium } = require('playwright');
const [base, vehicle, workOrder, qr] = process.argv.slice(2);
const out = 'public/img/panduan';
const password = process.env.DEMO_PASSWORD || 'Demo#2026aset';
const app = base + '/app/sahid';

const plan = [
  ['direktur@sahid.demo', [
    ['eksekutif-2', '/dasbor-pimpinan'],
    ['eksekutif-3', '/dasbor-pimpinan', 'text=Menunggu keputusan Anda'],
    ['eksekutif-4', '/capex-proposals'],
    ['eksekutif-5', '/asset-risks'],
  ]],
  ['manajer@sahid.demo', [
    ['manajer-1', ''], ['manajer-2', '/room-bookings'], ['manajer-3', '/supply-requests'], ['manajer-4', '/maintenance-requests'],
    ['manajer-5', '/preventive-plans'], ['manajer-6', '/asset-risks'], ['manajer-7', '/locations'],
  ]],
  ['umum@sahid.demo', [
    ['umum-1', '/jadwal-ruang-rapat'], ['umum-2', '/vehicle-bookings'], ['umum-3', `/vehicles/${vehicle}`], ['umum-4', '/supply-items'], ['umum-5', '/legal-documents'],
  ]],
  ['teknisi@sahid.demo', [
    ['teknisi-1', '/work-orders'], ['teknisi-2', `/work-orders/${workOrder}/edit`], ['teknisi-3', '/inspections/create'], ['teknisi-4', '/verifikasi-aset'], ['teknisi-5', '/assets'],
  ]],
];

async function login(browser, email) {
  const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const page = await ctx.newPage();
  await page.goto(base + '/app/login');
  await page.fill('input[type=email]', email);
  await page.fill('input[type=password]', password);
  await page.click('button[type=submit]');
  await page.waitForURL(u => !u.toString().includes('/login'), { timeout: 30000 });
  return { ctx, page };
}

async function shot(page, name, url, anchor) {
  await page.goto(url);
  await page.waitForLoadState('networkidle').catch(() => {});
  await page.waitForTimeout(1500);
  if (anchor) {
    await page.locator(anchor).first().evaluate(el => window.scrollTo(0, el.getBoundingClientRect().top + window.scrollY - 90));
    await page.waitForTimeout(400);
  }
  await page.screenshot({ path: `${out}/${name}.png` });
  console.log(name, page.url());
}

(async () => {
  const browser = await chromium.launch();

  // Direksi grup: pemilih perusahaan terbuka.
  {
    const { ctx, page } = await login(browser, 'pimpinan@prima.demo');
    await page.goto(app + '/dasbor-pimpinan');
    await page.waitForTimeout(2500);
    await page.locator('.fi-tenant-menu-trigger, .fi-tenant-menu button').first().click().catch(e => console.log('tenant menu', e.message));
    await page.waitForTimeout(600);
    await page.screenshot({ path: `${out}/eksekutif-1.png` });
    await ctx.close();
  }

  for (const [email, pages] of plan) {
    const { ctx, page } = await login(browser, email);
    for (const [name, path, anchor] of pages) await shot(page, name, app + path, anchor);
    await ctx.close();
  }

  // Tamu: halaman masuk, pita perusahaan, halaman kenapa perlu.
  {
    const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const page = await ctx.newPage();
    await page.goto(base + '/app/login');
    await page.waitForTimeout(800);
    await page.screenshot({ path: `${out}/tamu-1.png` });
    await page.click('.rac-guest-button');
    await page.waitForURL(u => u.toString().includes('dasbor-pimpinan'), { timeout: 30000 });
    await shot(page, 'tamu-2', base + '/app/bluebird/dasbor-pimpinan');
    await shot(page, 'tamu-3', base + '/app/bluebird/kenapa-perlu-ruci-prima');
    await ctx.close();
  }

  // Pindai QR dari ponsel.
  {
    const ctx = await browser.newContext({ viewport: { width: 390, height: 844 } });
    const page = await ctx.newPage();
    await shot(page, 'teknisi-6', `${base}/qr/${qr}`);
    await ctx.close();
  }

  await browser.close();
})();
