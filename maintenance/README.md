# Halaman Maintenance / Landing Page

Landing page "sedang pemeliharaan" yang siap pakai. Tampilannya ramah (mobile friendly),
kreatif, dan profesional — semua dalam satu file mandiri tanpa proses build.

## Isi

- `index.html` — landing page lengkap (HTML + CSS + JS inline).

## Fitur

- **Mobile friendly** — layout responsif dari layar kecil sampai desktop, aman untuk notch (`viewport-fit=cover`).
- **Kata-kata menyenangkan** — sapaan hangat dan nada positif, menyampaikan bahwa layanan kembali **besok**.
- **Kreatif & profesional** — gradien lembut, blob mengambang, ikon animasi, badge status berdenyut, progress bar, dan efek kaca (glassmorphism).
- **Tanpa dependensi build** — cukup buka di browser. Font dimuat dari Google Fonts.
- **Aksesibel** — atribut ARIA pada progress bar, tanda `aria-hidden` pada dekorasi, dan menghormati `prefers-reduced-motion`.

## Cara Pakai

### 1. Pratinjau langsung
Buka file berikut di browser:

```
maintenance/index.html
```

### 2. Sajikan sebagai situs statis (opsional)
```bash
cd maintenance
python3 -m http.server 8080
# lalu buka http://localhost:8080
```

### 3. Jadikan halaman maintenance Laravel (opsional)
Agar seluruh situs menampilkan halaman ini saat pemeliharaan:

1. Salin `index.html` menjadi view Blade, contoh `resources/views/maintenance.blade.php`.
2. Aktifkan mode maintenance Laravel dengan secret agar tim internal tetap bisa masuk:
   ```bash
   php artisan down --secret="rahasia-tim" --render=maintenance
   ```
   Akses kembali dengan membuka `https://domain-anda/rahasia-tim`.
3. Untuk mengaktifkan kembali:
   ```bash
   php artisan up
   ```

## Kustomisasi Cepat

| Ingin mengubah | Cari di `index.html` |
| --- | --- |
| Nama / brand | `brand__name`, `brand__sub`, `<title>` |
| Kata-kata sapaan | bagian `<h1>` dan `.lead` |
| Warna tema | variabel di `:root` (`--teal-*`, `--amber-*`) |
| Email kontak | `<a href="mailto:...">` |
| Persentase progres | `.bar__fill` (`width`) dan `aria-valuenow` |
