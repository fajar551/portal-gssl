# Portal GSSL

Portal GSSL adalah aplikasi berbasis **Cloud Billing Management System (CBMS)** untuk pengelolaan billing dan layanan terkait.

## Persyaratan

- PHP minimal 7.4
- Composer
- Database (MySQL/MariaDB sesuai konfigurasi Laravel)

## Instalasi produksi

1. Clone repositori ini.
2. Jalankan `composer install`.
3. Salin `.env.example` menjadi `.env` dan sesuaikan konfigurasi (termasuk `APP_URL`, database, dan email).
4. Jalankan perintah berikut:

   ```bash
   php artisan migrate
   php artisan db:seed
   php artisan adminpermissions:generate
   php artisan apipermissions:generate
   ```

5. Pastikan ada file `modules_statuses.json` di root proyek (cek atau buat sesuai kebutuhan modul).
6. Sesuaikan template email di area admin (sintaks Blade) bila diperlukan.
7. Konfigurasi `.htaccess` di server bila diperlukan.

## Virtualizor (cron)

Contoh cron untuk runner Virtualizor:

```bash
* * * * * php /path/to/projek/artisan virtualizor:run
```

## Git

Untuk update ke production, ikuti alur branch tim Anda (misalnya merge request dari branch fitur ke `dev`).

---

**Portal GSSL** — proyek ini dikelola di repositori Git sesuai kebijakan deployment internal.
