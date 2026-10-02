# README: Poliklinik Al-Huda - Sistem Pengurusan Klinik

**Kod Dokumen:** KLINIK-README-PR2026-01-panduan-setup-operasi
**Dicipta:** 2 Oktober 2026
**Penulis:** AI Assistant
**Dikemaskini:** 2 Oktober 2026

---

## 1. Ringkasan Eksekutif

### 1.1 Gambaran Keseluruhan

Sistem pengurusan klinik (EMR) untuk Poliklinik Al-Huda yang meliputi pendaftaran pesakit, temujanji, giliran, konsultasi EMR, farmasi, inventori, bil, kewangan dan laporan. Akses dikawal mengikut peranan, dan seorang pengguna boleh mempunyai lebih daripada satu peranan.

### 1.2 Metadata

- **Nama Sistem**: Poliklinik Al-Huda - Sistem Pengurusan Klinik
- **Repo**: `ahmadsobri89/alhuda`
- **Persekitaran Production**: `/var/www/alhuda` (deploy automatik dari branch `main`)
- **Bahasa Antara Muka**: Bahasa Melayu (default), English

### 1.3 Teknologi Stack

| Lapisan | Teknologi |
|---|---|
| Backend | Laravel 13, PHP 8.4 |
| Frontend | Inertia.js 2 + Vue 3, Tailwind CSS 4, Vite |
| Pangkalan Data | MySQL / MariaDB |
| Real-time | Laravel Reverb (WebSocket) |
| Audit | `audit_logs` (dalaman) + `spatie/laravel-activitylog` |
| Log Masuk | Kata laluan + Google (`laravel/socialite`) |
| Impersonate | `lab404/laravel-impersonate` |
| Lain-lain | `simplesoftwareio/simple-qrcode` (QR pengesahan dokumen) |

---

## 2. Senarai Modul

### 2.1 Modul & Akses Peranan

Rujukan: `config/access.php`. Peranan `admin` sentiasa boleh mengakses semua modul.

| Modul | Peranan Dibenarkan |
|---|---|
| Dashboard, Giliran, Profil | Semua pengguna |
| Daftar Pesakit | Resepsionis |
| Pesakit | Resepsionis, Doktor, Jururawat, Farmasi |
| Temujanji | Resepsionis, Doktor, Jururawat |
| EMR / Konsultasi (MC, Surat Rujukan, Slip Masa, Kuarantin, Keputusan Ujian, Memo) | Doktor, Jururawat |
| Farmasi, Inventori | Farmasi |
| Perkhidmatan | Resepsionis, Kewangan |
| Bil & Invois | Resepsionis |
| Kewangan | Kewangan |
| Laporan | Doktor |
| Tetapan, Log Audit | Admin sahaja |

### 2.2 Dokumen PRD

| No. | Dokumen |
|---|---|
| 01 | [Tetapan & Keselamatan - Kawalan Sistem](01-KLINIK-Tetapan-PR2026-01-kawalan-sistem-keselamatan.md) |
| 02 | [Sumber Manusia - Pengurusan Kakitangan](02-KLINIK-HR-PR2026-01-pengurusan-kakitangan.md) |
| 03 | [Pendaftaran Pesakit - Pengurusan Maklumat Pesakit](03-KLINIK-PendaftaranPesakit-PR2026-01-pengurusan-maklumat-pesakit.md) |
| 04 | [Temujanji Pesakit - Pengurusan Temujanji](04-KLINIK-TemujanjiPesakit-PR2026-01-pengurusan-temujanji.md) |
| 05 | [Queue - Pengurusan Giliran](05-KLINIK-Queue-PR2026-01-pengurusan-giliran.md) |
| 06 | [Konsultasi EMR - Rekod Rawatan Pesakit](06-KLINIK-KonsultasiEMR-PR2026-01-rekod-rawatan-pesakit.md) |
| 07 | [AI - Triage & EMR Sokongan Klinikal](07-KLINIK-AI-PR2026-01-triage-emr-sokongan-klinikal.md) |
| 08 | [Farmasi - Pengurusan Ubat & Stok](08-KLINIK-Farmasi-PR2026-01-pengurusan-ubat-stok.md) |
| 09 | [Billing - Caj & Kutipan Bayaran](09-KLINIK-Billing-PR2026-01-caj-kutipan-bayaran.md) |
| 10 | [Panel - Pengurusan Pesakit Panel](10-KLINIK-Panel-PR2026-01-pengurusan-pesakit-panel.md) |
| 11 | [Laporan - Analisis Prestasi & KPI](11-KLINIK-Laporan-PR2026-01-analisis-prestasi-kpi.md) |

---

## 3. Keperluan Sistem

| Komponen | Versi / Keterangan |
|---|---|
| PHP | 8.4 (`composer.lock` memerlukan `>= 8.4`) |
| Node.js | 23.3.0 (lihat `.nvmrc`) |
| Pangkalan Data | MySQL / MariaDB |
| Composer | 2.x |
| Ekstensi PHP | `mbstring`, `xml`, `curl`, `zip`, `gd` (favicon), `bcmath`, `mysql` |
| Production | Nginx + PHP-FPM, systemd / supervisor |

---

## 4. Setup Local (Development)

### 4.1 Clone & Pasang Dependensi

```bash
git clone git@github.com:ahmadsobri89/alhuda.git && cd alhuda
composer install
nvm use && npm install
```

### 4.2 Konfigurasi `.env`

```bash
cp .env.example .env
php artisan key:generate
```

`.env.example` masih menggunakan SQLite. Ubah sekurang-kurangnya nilai berikut:

```dotenv
APP_URL=http://127.0.0.1:8000
APP_LOCALE=ms

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=poliklinikalhuda
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_CONNECTION=reverb
REVERB_APP_ID=...
REVERB_APP_KEY=...
REVERB_APP_SECRET=...
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

Untuk log masuk Google, isi juga `GOOGLE_CLIENT_ID` dan `GOOGLE_CLIENT_SECRET`.

### 4.3 Pangkalan Data

```bash
mysql -u root -e "CREATE DATABASE poliklinikalhuda CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
php artisan migrate
php artisan db:seed                              # data demo (lookup, staf contoh, pesakit)
php artisan db:seed --class=SuperAdminSeeder     # akaun super admin
php artisan storage:link                         # logo klinik, gambar tips kesihatan
```

Staf demo dari `DatabaseSeeder` menggunakan kata laluan `password`. Kebanyakannya ada MFA aktif. Untuk ujian cepat, guna akaun tanpa MFA (cth `salina@alhuda.my`).

### 4.4 Jalankan Aplikasi

```bash
composer dev
```

Arahan ini menjalankan server, queue, log, Vite dan Reverb serentak. Aplikasi boleh diakses di <http://127.0.0.1:8000>.

> Tanpa `composer dev` / `npm run dev` (tiada `public/hot`), perubahan pada fail Vue hanya kelihatan selepas `npm run build`.

---

## 5. Setup Server (Production)

### 5.1 Deploy Automatik

Setiap push ke `main` akan men-deploy secara automatik melalui GitHub Actions (`.github/workflows/deploy-production.yml`):

```bash
cd /var/www/alhuda
git pull origin main
composer install --optimize-autoloader
php artisan migrate --force
npm ci && npm run build
php artisan queue:restart
sudo systemctl restart reverb
php artisan optimize:clear
```

Secret GitHub yang diperlukan: `SSH_PROD_HOST`, `SSH_PROD_USER`, `SSH_PROD_KEY`.

### 5.2 Setup Kali Pertama

Dilakukan sekali sahaja, sebelum deploy automatik berfungsi.

1. Pasang PHP 8.4 (berserta `php8.4-fpm` dan ekstensi di Bahagian 3), Composer, Node 23, MySQL dan Nginx.
2. Clone ke `/var/www/alhuda`, kemudian:
   ```bash
   composer install --no-dev --optimize-autoloader
   cp .env.example .env && php artisan key:generate
   ```
   Isi `.env` seperti Bahagian 4.2, tetapi dengan:
   ```dotenv
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://domain-klinik.my
   REVERB_SCHEME=https
   ```
3. Jalankan migration, seeder asas dan storage link:
   ```bash
   php artisan migrate --force
   php artisan db:seed --class=LookupSeeder --force
   php artisan db:seed --class=SuperAdminSeeder --force
   php artisan storage:link
   npm ci && npm run build
   ```
   **Jangan** jalankan `php artisan db:seed` tanpa `--class` di production, kerana ia memasukkan staf dan pesakit demo.
4. Tetapkan kebenaran fail:
   ```bash
   sudo chown -R www-data:www-data storage bootstrap/cache
   ```
5. Konfigurasi Nginx: root ke `/var/www/alhuda/public`, hantar `*.php` ke PHP-FPM, dan proxy WebSocket `/app` serta `/apps` ke Reverb (`127.0.0.1:8080`).

### 5.3 Proses Latar Belakang

| Proses | Arahan | Catatan |
|---|---|---|
| Queue worker | `php artisan queue:work --tries=1` | systemd atau supervisor |
| Reverb | `php artisan reverb:start` | Servis systemd **mesti** bernama `reverb`, kerana workflow memanggil `systemctl restart reverb` |

Benarkan `SSH_PROD_USER` menjalankan `sudo systemctl restart reverb` tanpa kata laluan (sudoers).

---

## 6. Impersonate (Log Masuk Sebagai Pengguna Lain)

### 6.1 Gambaran Keseluruhan

Membolehkan pengguna yang diberi kebenaran untuk log masuk sebagai pengguna lain, bagi tujuan sokongan dan penyiasatan isu. Menggunakan `lab404/laravel-impersonate` melalui route POST sendiri (`impersonate.start` / `impersonate.stop`). Route GET terbina dalam package tidak digunakan kerana tiada perlindungan CSRF.

### 6.2 Kawalan Akses

Kebenaran ialah bendera `users.can_impersonate` (default `false`), **bukan** peranan. Peranan `admin` atau Super Admin sahaja **tidak** memadai.

| Pengguna | Kebenaran |
|---|---|
| Tanpa bendera (termasuk Super Admin) | Tidak boleh impersonate, dan tidak boleh memberi akses kepada sesiapa, termasuk diri sendiri |
| Impersonator | Boleh impersonate, serta beri / tarik balik akses untuk pengguna **lain** di Tetapan → Pengguna → Edit → *Akses Impersonate* |
| Impersonator (diri sendiri) | Tidak boleh mengubah bendera sendiri melalui UI |

Peraturan tambahan:

- Tidak boleh impersonate diri sendiri, impersonator lain, atau secara bertingkat.
- Pengguna `inactive` tidak boleh impersonate atau diberi akses.
- Semasa menyamar, tindakan berikut disekat: tukar kata laluan, kemaskini / padam profil, dan urus pengguna (termasuk memberi akses impersonate).
- Banner oren dipaparkan di atas setiap halaman semasa menyamar, bersama butang *Kembali ke akaun saya*.

### 6.3 Pemasangan

Package sudah ada dalam `composer.json` / `composer.lock`. Tiada `vendor:publish` atau konfigurasi tambahan diperlukan.

**Local** (selepas `git pull`):

```bash
composer install            # pasang lab404/laravel-impersonate
php artisan migrate         # tambah lajur users.can_impersonate
npm run build               # banner & butang Impersonate (atau composer dev)
```

**Production:** push ke `main`. Deploy automatik (Bahagian 5.1) akan menjalankan `composer install`, `migrate --force` dan `npm run build`.

Untuk projek **baharu** dari kosong: jalankan `composer require lab404/laravel-impersonate`, kemudian tambah trait `Lab404\Impersonate\Models\Impersonate` pada model `User` (lihat `app/Models/User.php`).

### 6.4 Bootstrap Impersonator Pertama

Impersonator pertama **hanya** boleh diberi melalui CLI, kerana ia memerlukan akses ke server. Pengguna dicari mengikut ID (`users.id`).

1. Cari **ID pengguna**:
   ```bash
   cd /var/www/alhuda
   php artisan user:impersonator --all
   ```
   Contoh output:
   ```
   | ID | Nama              | E-mel                     | Peranan | Status | Impersonator |
   | 1  | Ahmad Sobri Haris | ahmadsobriharis@gmail.com | admin   | active | -            |
   | 8  | MOHD AFIEZ        | mohdafiez7@gmail.com      | admin   | active | -            |
   ```
2. Beri kebenaran menggunakan ID tersebut. Gantikan `{id}` dengan nombor dari lajur `ID`, cth `1` untuk Ahmad Sobri Haris:
   ```bash
   php artisan user:impersonator {id}            # beri        → cth: php artisan user:impersonator 1
   php artisan user:impersonator {id} --revoke   # tarik balik → cth: php artisan user:impersonator 1 --revoke
   php artisan user:impersonator --list          # senarai impersonator semasa
   php artisan user:impersonator --revoke-all    # tarik balik SEMUA (kill switch)
   ```
3. Selepas itu, impersonator tersebut boleh memberi akses kepada pengguna lain melalui sistem.

> ID di production mungkin berbeza daripada local. Sentiasa jalankan `--all` di server terlebih dahulu.

### 6.5 Patah Balik (Rollback)

Pilih tahap mengikut keperluan. Mulakan dengan tahap paling ringan.

| Tahap | Bila Digunakan | Perlu Deploy? |
|---|---|---|
| 1. Matikan serta-merta | Ada isu, atau berhenti guna buat sementara | Tidak |
| 2. Revert kod | Tidak lagi mahu menggunakan ciri ini | Ya (push ke `main`) |
| 3. Buang lajur DB | Pilihan, jika mahu DB sama seperti sebelum ciri ini | Tidak (selepas Tahap 2) |

#### 6.5.1 Tahap 1: Matikan Serta-merta

```bash
cd /var/www/alhuda
php artisan user:impersonator --revoke-all   # tarik balik akses SEMUA impersonator
php artisan user:impersonator --list         # sahkan: "Tiada pengguna dijumpai."
```

Kesannya:
- Tiada sesiapa boleh mula impersonate.
- Butang *Impersonate* hilang dari UI.
- Akses boleh diaktifkan semula bila-bila masa dengan `user:impersonator {id}`.

Sesi impersonate yang **sedang berjalan** kekal sehingga pengguna menekan *Kembali ke akaun saya* atau log keluar. Untuk menamatkannya serta-merta (`SESSION_DRIVER=database`):

```bash
php artisan tinker --execute="echo DB::table('sessions')->get()->filter(fn (\$s) => str_contains(base64_decode(\$s->payload), 'impersonated_by'))->each(fn (\$s) => DB::table('sessions')->where('id', \$s->id)->delete())->count().' sesi dipadam';"
```

Pengguna yang terlibat akan dilog keluar dan perlu log masuk semula.

#### 6.5.2 Tahap 2: Revert Kod

1. Tamatkan semua sesi impersonate dahulu (Tahap 1). Jika tidak, sesi tersebut kekal log masuk sebagai pengguna sasaran tanpa banner atau butang *Kembali*.
2. Di local, revert commit impersonate dan push:
   ```bash
   git log --oneline --grep="impersonate" -i    # cari hash commit
   git revert <hash>
   git push origin main
   ```
   Deploy automatik akan:
   - membuang `lab404/laravel-impersonate` melalui `composer install` (mengikut `composer.lock` yang telah di-revert);
   - membuang banner dan butang dari UI melalui `npm run build`.

Lajur `users.can_impersonate` **kekal** dalam pangkalan data. Ini selamat kerana lajur itu ada nilai default `0` dan tidak digunakan oleh kod lain.

#### 6.5.3 Tahap 3: Buang Lajur `can_impersonate` (Pilihan)

Lakukan **selepas** Tahap 2:

```bash
cd /var/www/alhuda
php artisan tinker --execute="Schema::dropColumns('users', ['can_impersonate']); DB::table('migrations')->where('migration', '2026_10_02_120000_add_can_impersonate_to_users_table')->delete(); echo 'OK';"
```

> Jangan bergantung pada `php artisan migrate:rollback --path=...`. Arahan itu hanya berfungsi jika migration tersebut berada dalam **batch terakhir**. Jika ada migration lain dijalankan selepasnya, ia tidak melakukan apa-apa.

#### 6.5.4 Pasang Semula Selepas Patah Balik

- Selepas Tahap 1: `php artisan user:impersonator {id}`.
- Selepas Tahap 2 / 3: `git revert <hash-commit-revert>` dan push. Migration akan menambah semula lajur jika ia telah dibuang. Kemudian jalankan `php artisan user:impersonator {id}`.

### 6.6 Audit Trail

| Tindakan | Direkod Di |
|---|---|
| `impersonate.start`, `impersonate.stop`, `impersonate.denied` | `audit_logs` (atas nama impersonator) |
| `impersonate.grant`, `impersonate.revoke` melalui UI | `audit_logs` |
| `impersonate.grant`, `impersonate.revoke` melalui CLI | `activity_log` (`via: artisan`) |
| Sebarang `AuditLog::record()` semasa menyamar | `audit_logs` atas nama pengguna sasaran, dengan `meta.impersonator_id` |

> Spatie activitylog (log perubahan model) mencatat pengguna sasaran sebagai *causer* semasa menyamar. `impersonator_id` hanya ada dalam `audit_logs`.

### 6.7 Fail Berkaitan

| Fail | Fungsi |
|---|---|
| `app/Http/Controllers/ImpersonationController.php` | Mula / tamat impersonate |
| `app/Http/Controllers/SettingsController.php` (`updateImpersonator`) | Beri / tarik balik akses melalui UI |
| `app/Console/Commands/ManageImpersonator.php` | Command `user:impersonator` |
| `app/Models/User.php` | `canImpersonate()`, `canBeImpersonated()`, `canManageImpersonatorOf()` |
| `database/migrations/2026_10_02_120000_add_can_impersonate_to_users_table.php` | Lajur `users.can_impersonate` |
| `resources/js/Layouts/KlinikLayout.vue` | Banner impersonate |
| `resources/js/Pages/Settings.vue` | Butang Impersonate & *Akses Impersonate* |

---

## 7. Isu Diketahui

| Isu | Keterangan |
|---|---|
| Test suite | `phpunit.xml` menggunakan SQLite `:memory:`, tetapi migration projek ini belum boleh dijalankan dari kosong di SQLite. `php artisan test` dengan `RefreshDatabase` gagal buat masa ini. |
| Kata laluan dalam seeder | `database/seeders/SuperAdminSeeder.php` mengandungi kata laluan sebenar dalam kod dan telah di-commit ke repo. Disyorkan untuk membaca kata laluan dari `.env` dan menukar kata laluan akaun tersebut di production. |

---

**END OF README**

---

## Appendix: Change Log

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2026-10-02 | AI Assistant | Ganti README boilerplate Laravel: setup local & production, senarai modul, dokumentasi impersonate dan rollback |
