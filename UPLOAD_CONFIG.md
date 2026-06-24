# Konfigurasi Upload File Besar (500MB)

## Konfigurasi PHP

Untuk mendukung upload file hingga 500MB, Anda perlu mengubah beberapa pengaturan PHP.

### 1. Edit php.ini

Lokasi file php.ini di Laragon:
```
C:\laragon\bin\php\php-8.x.x\php.ini
```

Ubah nilai berikut:

```ini
upload_max_filesize = 500M
post_max_size = 510M
max_execution_time = 300
max_input_time = 300
memory_limit = 512M
```

**Penjelasan:**
- `upload_max_filesize`: Ukuran maksimal file yang dapat diupload (500MB)
- `post_max_size`: Ukuran maksimal POST data, harus lebih besar dari upload_max_filesize (510MB)
- `max_execution_time`: Waktu maksimal eksekusi script (300 detik = 5 menit)
- `max_input_time`: Waktu maksimal parsing input data (300 detik)
- `memory_limit`: Memory limit untuk PHP (512MB)

### 2. Restart Apache/Nginx

Setelah mengubah php.ini, restart web server Anda:

**Di Laragon:**
- Klik menu Laragon
- Pilih "Stop All"
- Kemudian "Start All"

### 3. Verifikasi Konfigurasi

Buat file `phpinfo.php` di folder public:

```php
<?php
phpinfo();
```

Akses `http://localhost/phpinfo.php` dan cari nilai:
- `upload_max_filesize`
- `post_max_size`
- `max_execution_time`

Pastikan nilainya sesuai dengan yang Anda set.

## Konfigurasi Web Server (Opsional)

### Apache (.htaccess)

Jika menggunakan Apache, tambahkan di `.htaccess`:

```apache
php_value upload_max_filesize 500M
php_value post_max_size 510M
php_value max_execution_time 300
php_value max_input_time 300
```

### Nginx

Jika menggunakan Nginx, tambahkan di konfigurasi server block:

```nginx
client_max_body_size 510M;
```

## Catatan Penting

⚠️ **Pertimbangan Keamanan:**
- Upload file besar dapat menyebabkan server overload
- Pertimbangkan untuk membatasi tipe file yang diperbolehkan
- Validasi file yang diupload untuk menghindari malware
- Pertimbangkan menggunakan cloud storage (S3, Google Cloud Storage) untuk file besar

⚠️ **Pertimbangan Performa:**
- Upload file 500MB membutuhkan bandwidth dan waktu
- Pastikan koneksi internet stabil
- Pertimbangkan menggunakan progress bar untuk UX yang lebih baik
- File besar akan memakan disk space server

## Testing

Setelah konfigurasi:
1. Coba upload file kecil terlebih dahulu (1-10MB)
2. Kemudian coba file menengah (50-100MB)
3. Terakhir coba file mendekati 500MB
4. Perhatikan error log jika ada masalah

## Troubleshooting

### Error: "The uploaded file exceeds the upload_max_filesize directive"
- Periksa nilai `upload_max_filesize` di php.ini
- Restart web server setelah mengubah php.ini

### Error: "Maximum execution time exceeded"
- Tingkatkan nilai `max_execution_time`
- Atau gunakan `set_time_limit(0)` di controller

### Error: "Allowed memory size exhausted"
- Tingkatkan nilai `memory_limit`
- Untuk file sangat besar, pertimbangkan chunked upload

### Upload Timeout di Browser
- Tingkatkan `max_input_time` dan `max_execution_time`
- Periksa timeout di web server (Apache/Nginx)
