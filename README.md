<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Setup

### Keperluan

| Komponen | Versi |
|---|---|
| PHP | 8.4 (composer.lock memerlukan `>= 8.4`) |
| Node.js | 23.3.0 (lihat `.nvmrc`) |
| Pangkalan data | MySQL / MariaDB |
| Lain-lain | Composer 2, ekstensi PHP `gd` (favicon), Laravel Reverb (WebSocket) |

### Local (development)

1. Clone dan pasang dependensi:
   ```bash
   git clone git@github.com:ahmadsobri89/alhuda.git && cd alhuda
   composer install
   nvm use && npm install
   ```
2. Sediakan `.env`:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Kemudian ubah sekurang-kurangnya:
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
   Untuk log masuk Google, isi juga `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET`.
3. Cipta pangkalan data, jalankan migration dan seed:
   ```bash
   mysql -u root -e "CREATE DATABASE poliklinikalhuda CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
   php artisan migrate
   php artisan db:seed                                        # data demo (lookup, staf contoh, pesakit)
   php artisan db:seed --class=SuperAdminSeeder               # akaun super admin
   php artisan storage:link                                   # logo klinik, gambar tips kesihatan
   ```
   Staf demo dari `DatabaseSeeder` menggunakan kata laluan `password`. Kebanyakannya ada MFA aktif. Untuk ujian cepat, guna akaun yang tiada MFA (cth `salina@alhuda.my`).
4. Jalankan semua proses (server, queue, log, Vite, Reverb) serentak:
   ```bash
   composer dev
   ```
   Aplikasi di <http://127.0.0.1:8000>.

   > Tanpa `composer dev` / `npm run dev` (tiada `public/hot`), perubahan pada fail Vue hanya kelihatan selepas `npm run build`.
5. (Pilihan) Jadikan diri anda impersonator untuk menguji:
   ```bash
   php artisan user:impersonator --all
   php artisan user:impersonator {id}
   ```

> **Ujian:** `phpunit.xml` menggunakan SQLite `:memory:`, tetapi migration projek ini belum boleh dijalankan dari kosong di SQLite. Jadi `php artisan test` dengan `RefreshDatabase` akan gagal buat masa ini.

### Server (production)

Production berada di `/var/www/alhuda` dan **deploy automatik** melalui GitHub Actions (`.github/workflows/deploy-production.yml`) setiap kali ada push ke `main`. Workflow itu menjalankan:

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

**Setup server kali pertama** (sekali sahaja, sebelum deploy automatik berfungsi):

1. Pasang PHP 8.4 (+ `php8.4-fpm`, `mysql`, `mbstring`, `xml`, `curl`, `zip`, `gd`, `bcmath`), Composer, Node 23, MySQL dan Nginx.
2. Clone ke `/var/www/alhuda`, kemudian:
   ```bash
   composer install --no-dev --optimize-autoloader
   cp .env.example .env && php artisan key:generate
   ```
   Isi `.env` seperti di bahagian local, tetapi dengan:
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
4. Kebenaran fail:
   ```bash
   sudo chown -R www-data:www-data storage bootstrap/cache
   ```
5. Nginx: root ke `/var/www/alhuda/public`, hantar `*.php` ke PHP-FPM, dan proxy WebSocket `/app` serta `/apps` ke Reverb (`127.0.0.1:8080`).
6. Proses latar belakang:
   - **Queue worker** (systemd atau supervisor): `php artisan queue:work --tries=1`
   - **Reverb** sebagai servis systemd bernama `reverb`: `php artisan reverb:start`. Nama ini mesti sama, kerana workflow memanggil `systemctl restart reverb`.
   - Benarkan `SSH_PROD_USER` menjalankan `sudo systemctl restart reverb` tanpa kata laluan (sudoers).

**Selepas deploy ciri impersonate:** migration `can_impersonate` berjalan secara automatik dalam workflow. Selepas itu, SSH ke server dan beri impersonator pertama:

```bash
cd /var/www/alhuda
php artisan user:impersonator --all
php artisan user:impersonator {id}
```

## Impersonate (log masuk sebagai pengguna lain)

Menggunakan [`lab404/laravel-impersonate`](https://github.com/404labfr/laravel-impersonate) melalui route POST sendiri (`impersonate.start` / `impersonate.stop`) — route GET terbina package tidak digunakan kerana tiada perlindungan CSRF.

### Pemasangan

Package `lab404/laravel-impersonate` sudah ada dalam `composer.json` / `composer.lock`. Tiada `vendor:publish` atau config tambahan diperlukan, kerana nilai default package digunakan.

**Local** (selepas `git pull`):

```bash
composer install            # pasang lab404/laravel-impersonate
php artisan migrate         # tambah lajur users.can_impersonate
npm run build               # banner & butang Impersonate (atau composer dev)
php artisan user:impersonator --all
php artisan user:impersonator {id}
```

**Production:** push ke `main`. GitHub Actions akan menjalankan `composer install`, `migrate --force` dan `npm run build` secara automatik. Selepas deploy selesai, SSH ke server dan beri impersonator pertama:

```bash
cd /var/www/alhuda
php artisan user:impersonator --all
php artisan user:impersonator {id}
```

Untuk pasang package ini dalam projek **baharu** dari kosong: `composer require lab404/laravel-impersonate`, kemudian tambah trait `Lab404\Impersonate\Models\Impersonate` pada model `User` (lihat `app/Models/User.php`).

### Siapa boleh impersonate

Kebenaran ialah bendera `users.can_impersonate` (default `false`), **bukan** peranan. Peranan `admin` / Super Admin sahaja **tidak** memadai.

| Pengguna | Kebenaran |
|---|---|
| Tanpa bendera (termasuk Super Admin) | Tidak boleh impersonate, dan tidak boleh memberi akses kepada sesiapa — termasuk diri sendiri |
| Impersonator | Boleh impersonate, serta beri / tarik balik akses untuk pengguna **lain** di Settings → Pengguna → Edit → *Akses Impersonate* |
| Impersonator (diri sendiri) | Tidak boleh mengubah bendera sendiri melalui UI |

Peraturan tambahan:

- Tidak boleh impersonate diri sendiri, pengguna lain yang juga impersonator, atau secara bertingkat.
- Pengguna `inactive` tidak boleh impersonate atau diberi akses.
- Semasa menyamar, tindakan berikut disekat: tukar kata laluan, kemaskini / padam profil, dan urus pengguna (termasuk memberi akses impersonate).
- Banner oren dipaparkan di atas setiap halaman semasa menyamar, dengan butang *Kembali ke akaun saya*.

### Bootstrap pertama (production)

Impersonator pertama **hanya** boleh diberi melalui CLI (perlu akses pelayan). Pengguna dicari mengikut ID (`users.id`).

1. Jalankan migration (menambah lajur `users.can_impersonate`):
   ```bash
   php artisan migrate
   ```
2. Cari **ID pengguna** (lajur `ID` = `users.id`):
   ```bash
   php artisan user:impersonator --all
   ```
   Contoh output:
   ```
   | ID | Nama              | E-mel                     | Peranan | Status | Impersonator |
   | 1  | Ahmad Sobri Haris | ahmadsobriharis@gmail.com | admin   | active | -            |
   | 8  | MOHD AFIEZ        | mohdafiez7@gmail.com      | admin   | active | -            |
   ```
3. Beri kebenaran menggunakan ID tersebut (gantikan `{id}`, cth `1` untuk Ahmad Sobri Haris):
   ```bash
   php artisan user:impersonator {id}            # beri       → cth: php artisan user:impersonator 1
   php artisan user:impersonator {id} --revoke   # tarik balik → cth: php artisan user:impersonator 1 --revoke
   php artisan user:impersonator --list          # senarai impersonator semasa
   ```
4. Selepas itu, impersonator tersebut boleh memberi akses kepada pengguna lain melalui sistem.

### Patah balik (rollback)

Pilih tahap mengikut keperluan. Mulakan dengan tahap paling ringan.

#### Tahap 1: Matikan serta-merta (tanpa deploy, kod kekal)

Sesuai jika ada isu keselamatan atau anda mahu berhenti guna buat sementara waktu.

```bash
cd /var/www/alhuda
php artisan user:impersonator --revoke-all   # tarik balik akses SEMUA impersonator
php artisan user:impersonator --list         # sahkan: "Tiada pengguna dijumpai."
```

Kesannya:
- Tiada sesiapa boleh mula impersonate.
- Butang *Impersonate* hilang dari UI.
- Akses boleh diaktifkan semula bila-bila masa dengan `user:impersonator {id}`.

Sesi impersonate yang **sedang berjalan** masih kekal sehingga pengguna itu menekan *Kembali ke akaun saya* atau log keluar. Untuk menamatkannya serta-merta, padam sesi yang mengandungi penanda impersonate (`SESSION_DRIVER=database`):

```bash
php artisan tinker --execute="echo DB::table('sessions')->get()->filter(fn (\$s) => str_contains(base64_decode(\$s->payload), 'impersonated_by'))->each(fn (\$s) => DB::table('sessions')->where('id', \$s->id)->delete())->count().' sesi dipadam';"
```

Pengguna yang terlibat akan dilog keluar dan perlu log masuk semula.

#### Tahap 2: Buang kod impersonate (revert commit)

Sesuai jika anda memutuskan untuk tidak lagi menggunakan ciri ini.

1. Tamatkan semua sesi impersonate dahulu (lihat Tahap 1). Jika tidak, sesi tersebut kekal log masuk sebagai pengguna sasaran tanpa banner atau butang *Kembali*.
2. Di local, revert commit impersonate dan push:
   ```bash
   git log --oneline --grep="impersonate" -i    # cari hash commit
   git revert <hash>
   git push origin main
   ```
   GitHub Actions akan deploy secara automatik:
   - `composer install` membuang `lab404/laravel-impersonate` (mengikut `composer.lock` yang telah di-revert).
   - `npm run build` membuang banner dan butang dari UI.

Lajur `users.can_impersonate` **kekal** dalam pangkalan data. Ini selamat: lajur itu ada nilai default `0`, dan kod lain tidak menggunakannya.

#### Tahap 3 (pilihan): Buang lajur `can_impersonate` dari pangkalan data

Hanya perlu jika anda mahu pangkalan data kembali sama seperti sebelum ciri ini. Lakukan **selepas** Tahap 2.

```bash
cd /var/www/alhuda
php artisan tinker --execute="Schema::dropColumns('users', ['can_impersonate']); DB::table('migrations')->where('migration', '2026_10_02_120000_add_can_impersonate_to_users_table')->delete(); echo 'OK';"
```

> Jangan bergantung pada `php artisan migrate:rollback --path=...`. Arahan itu hanya berfungsi jika migration tersebut berada dalam **batch terakhir**. Jika ada migration lain yang dijalankan selepasnya, ia tidak melakukan apa-apa.

#### Pasang semula selepas patah balik

- Selepas Tahap 1: `php artisan user:impersonator {id}`.
- Selepas Tahap 2 / 3: `git revert <hash-commit-revert>` dan push. Migration akan menambah semula lajur jika ia telah dibuang. Kemudian `php artisan user:impersonator {id}`.

### Jejak audit

| Tindakan | Direkod di |
|---|---|
| `impersonate.start`, `impersonate.stop`, `impersonate.denied` | `audit_logs` (atas nama impersonator) |
| `impersonate.grant`, `impersonate.revoke` melalui UI | `audit_logs` |
| `impersonate.grant`, `impersonate.revoke` melalui CLI | `activity_log` (`via: artisan`) |
| Sebarang `AuditLog::record()` semasa menyamar | `audit_logs` atas nama pengguna sasaran, dengan `meta.impersonator_id` |

> Nota: Spatie activitylog (log perubahan model) mencatat pengguna sasaran sebagai *causer* semasa menyamar — `impersonator_id` hanya ada dalam `audit_logs`.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
