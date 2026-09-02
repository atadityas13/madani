# MADANI

**Management Academic Data Native Integration** — master data akademik MTsN 11 Majalengka. Identitas siswa selaras tab EMIS 4.0, disiapkan sebagai sumber data untuk PPDB, REDIK, CBT, PRISMA, dan SIPASTI.

Bukan pengganti EMIS Kemenag. Ekspor template operator menyusul; tidak ada push ke emispendis.

## Stack

- Laravel 13 (PHP 8.3.33+, dikunci di `composer.json` `config.platform` agar lock file cocok dengan hosting)
- Bootstrap 5 + Bootstrap Icons
- Tailwind CSS v4 dengan prefix `tw-` (preflight dimatikan)
- Livewire 4
- Laravel Sanctum
- Spatie Permission

## Instalasi lokal

```bash
git clone https://github.com/atadityas13/madani.git
cd madani
composer install
copy .env.example .env
php artisan key:generate
```

Buat database MySQL `madani`, lalu sesuaikan `DB_*` di `.env`.

```bash
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Akun awal:

- Username: `admin`
- Password: `madani-admin`

Ganti sandi segera setelah login pertama.

## Pull di hosting

**Penyebab “Index of /”:** document root subdomain mengarah ke folder proyek, bukan `public/`. Laravel hanya boleh dilayani dari `public/index.php`.

Cara yang benar (cPanel → Subdomains → document root):

`/home/mtsnmaja/madani.mtsn11majalengka.sch.id/public`

Jika document root tidak bisa diubah, buat file `.htaccess` di **akar proyek** (file ini tidak di-git, agar handler PHP cPanel tidak tertimpa). Gabungkan config PHP hosting dengan rewrite ke `public/` — lihat contoh di catatan deploy. Setelah itu refresh browser.

```bash
cd /path/ke/madani
git pull origin main
composer install --no-dev --optimize-autoloader
cp .env.example .env   # hanya sekali; jangan menimpa .env produksi
php artisan key:generate   # hanya sekali
php artisan migrate --force
php artisan db:seed --force   # hanya sekali di instalasi baru
npm ci && npm run build     # lewati jika Node tidak ada; unggah public/build dari lokal
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
```

Pastikan folder `storage/` dan `bootstrap/cache/` dapat ditulis web server.

## API

`GET /api/v1/siswa?nisn=xxxxxxxxxx`  
Header: `Authorization: Bearer {token Sanctum}`

Token dibuat dari user yang sudah login, misalnya via `php artisan tinker`:

```php
User::where('username', 'admin')->first()->createToken('prisma')->plainTextToken;
```

## Fase berikutnya

1. Form lengkap 6 tab EMIS + audit ubah identitas
2. Impor siswa PPDB yang diterima dan daftar ulang
3. Endpoint intake/rombel untuk CBT dan PRISMA
4. Mutasi, naik kelas, alumni
