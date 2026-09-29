# ROADMAP — Pusmendik

> **Peta besar project.** Status detail & kerjaan harian ada di **Kanban Hermes**:
> `hermes kanban --board pusmendik list`
>
> File ini **jarang berubah** — hanya untuk konteks AI & arah jangka panjang.
> Jangan salin status dari Kanban ke sini; cukup tulis nomor task-nya.

- **Board Kanban:** `pusmendik`
- **Repo:** `https://github.com/hakibro/pusmendik.git`
- **Project Hermes:** `pusmendik`

<!-- kanban-board:begin -->
> Status baris di atas dari sudut **Hermes**: board `pusmendik` sudah dibuat, project Hermes belum (tidak ada profil per project).
<!-- kanban-board:end -->

---

## Arah Project

<1–3 paragraf: project ini untuk apa, siapa penggunanya, tujuan akhirnya.>

---

## Fitur / Milestone


### Selesai

- [x] **Data pembayaran siswa dari `apiakademik`** (2026-09-29) — halaman detail siswa, rekomendasi, dan cek pembayaran kini membaca `/siswa/{idperson}/pembayaran` dari API akademik bersama, menggantikan gateway `api.daruttaqwa.or.id/sisda/v1`. Klien: `App\Services\ApiAkademikService` + `App\Services\PembayaranViewService` (memetakan bentuk datar apiakademik ke `periods → categories → items` yang dipakai blade). Total dihitung sama dengan bantubayar/apiakademik: tagihan = total kredit, dibayar = total debet, sisa = tunggakan jurnal (item jatuh tempo belum lunas).
- [x] **Perbaikan halaman `/siswa`** (2026-09-29) — (1) `/panduan` kini menghormati `?group=`/`?role=` saat memilih panduan default (sebelumnya selalu panduan siswa); (2) memulihkan `PusmendikController::paymentSummaryByLevel()` yang terhapus saat refactor pembayaran sehingga `/siswa` error 500; (3) kartu Total Tagihan/Dibayar/Sisa di halaman detail siswa aman saat API akademik gagal (menampilkan pesan error, bukan error undefined array key).
- [x] **Perbaikan live search siswa di beranda** (2026-09-29) — tombol "Cek Status Pembayaran" sebelumnya `disabled` permanen tanpa JS yang mengaktifkan, sehingga Enter tidak mengirim form dan pengguna hanya mengandalkan dropdown. Kini: tombol aktif otomatis saat kata kunci ≥2 karakter (dengan gaya disabled/enabled), Enter di input mengirim form (pola sama dengan halaman Rekom), dan dropdown hasil di layar <lg tampil in-flow (bukan absolute di bawah lipatan layar) agar hasil bisa di-scroll/klik di ponsel.
- [x] **Perbaikan akses via Cloudflare Tunnel (trusted proxies)** (2026-09-29) — diakses lewat tunnel halaman "tidak bisa" (live search mati), padahal lokal normal. Penyebab: aplikasi tidak memercayai reverse proxy, jadi request HTTPS dari tunnel dikenali sebagai HTTP → semua URL/aset dirender `http://` → diblokir browser sebagai mixed content (CSS/JS gagal dimuat). Diperbaiki di `bootstrap/app.php` dengan `$middleware->trustProxies()` (loopback + rentang privat IPv4/IPv6, termasuk `fd00::/8` jaringan podman Lerd); bisa dioverride lewat env `TRUSTED_PROXIES`. Header `X-Forwarded-*` dari alamat tak tepercaya tetap diabaikan (anti host/proto spoofing). Catatan: `X-Forwarded-Port` **sengaja tidak dipercaya** karena cloudflared/lerd mengirim port internal (`80`) sehingga URL jadi `https://host:80/...` dan aset gagal dimuat (halaman tanpa styling) — port dibiarkan mengikuti skema.

- [x] **Tampilan pembayaran lebih jelas (tunggakan aktif vs belum jatuh tempo)** (2026-09-29) — sumber kerancuan: kartu "Sisa Tunggakan" hanya menampilkan satu angka sementara ada tagihan yang belum jatuh tempo. Kini (di detail siswa `/siswa/{id}` dan halaman publik `/cek-pembayaran`): (1) kartu Sisa Tunggakan menampilkan rasio mis. "11% dari total sisa Rp 6.460.000"; (2) blok **Komposisi Sisa Tagihan** memecah tunggakan aktif (jatuh tempo) vs belum jatuh tempo, dengan bar porsi + jumlah item; (3) tiap item diberi badge **Tunggakan Aktif** / **Belum Jatuh Tempo** / **Lunas**; (4) blok **Fokus Ujian** menampilkan pos wajib ujian beserta **persen ujian** (`persen_ujian`), status terpenuhi, dan kekurangan nominal. Percabangan ini butuh `?ujian=1` ke apiakademik (kini dipakai `PusmendikController::payment()`). Catatan: aset di-rebuild (`npm run build`) karena beberapa kelas Tailwind baru.
- [x] **Sisa Tunggakan diambil dari syarat ujian, bukan tunggakan jatuh tempo** (2026-09-29) — nilai utama "Sisa Tunggakan" kini dihitung dari **kekurangan syarat ujian** per pos (`wajib_bayar = tagihan × persen/100 - dibayar`), persis seperti gerbang ujian apiakademik, bukan dari `ringkasan.total_tunggakan` (jurnal jatuh tempo). `PembayaranViewService` menambah `sisa_tunggakan`, `sisa_tunggakan_sumber` (`syarat_ujian`|`jatuh_tempo`), `sisa_tunggakan_syarat_ujian`, `sisa_tunggakan_jatuh_tempo`, dan `fokus_ujian`/`fokus_ujian_belum` (digabung per `ipsmain` agar persen tidak terhitung berulang). Bila tidak ada kebijakan ujian, otomatis fallback ke tunggakan jatuh tempo. Ini penting karena ada siswa yang **tunggakan jatuh tempo Rp 0 tetapi kurang syarat ujian Rp 200.000** (mis. `220119`), dan sebaliknya — angka jurnal menyesatkan.

- [x] **Status administrasi ujian memakai definisi tunggal gerbang ujian** (2026-09-29) — kolom status di halaman **Rekom** (`/siswa`), kartu ringkasan per tingkat, modal kelas, **live search** beranda & Rekom, header **detail siswa**, laman publik **`/cek-pembayaran`**, dan **surat cetak** kini dihitung dari gerbang pembayaran ujian apiakademik (`PembayaranViewService::statusUjianMap()`, flag `terpenuhi` → Lunas/Belum), bukan dari kolom `status_pembayaran` di DB yang bisa basi. Label "Pembayaran" diganti **"Administrasi Ujian"** agar selaras dengan "Sisa Tunggakan Ujian". Peta gerbang (1 permintaan, ~335 KB) di-cache 5 menit; bila gerbang mati, halaman jatuh ke nilai DB tanpa error.
- [x] **Istilah "(jurnal)" diganti "(Ngalah Mobile)"** (2026-09-29) — label sisa tunggakan jatuh tempo kini memakai sebutan yang dikenal pengguna. Di UI detail siswa & `/cek-pembayaran`: kartu **Sisa Tunggakan Jatuh Tempo (Ngalah Mobile)**, blok Komposisi memakai "(Ngalah Mobile)", dan label **Tunggakan Aktif (Ngalah Mobile)**. Di surat: baris **Sisa Tunggakan Jatuh Tempo (Ngalah Mobile)**. Field mentah `tgl_jurnal` yang tampil di rincian item diganti label **tanggal**, dan label baris item "Jurnal:" → **"Tanggal:"** — sehingga kata "jurnal" tak lagi muncul di halaman maupun surat.
- [x] **Sisa Tunggakan Ujian di surat cetak rekomendasi** (2026-09-29) — surat (`/siswa/{id}/rekomendasi/cetak`) kini punya baris **Sisa Tunggakan Ujian** (kekurangan syarat per pos) + baris pembanding tunggakan jatuh tempo bila berbeda, tabel **Rincian Syarat Ujian per Pos** (persen, tagihan, wajib, dibayar, kurang, status terpenuhi), dan label status **Administrasi Ujian**. Placeholder teks surat baru: `{sisa_ujian}`.

### Sedang dikerjakan

### Siap dikerjakan (Ready)

### Direncanakan (Bahas / Todo)

### Ide (Inbox / Triage)

---

## Hubungan dengan Project Lain

> Ini yang bikin project saling tertaut. Saat satu fitur butuh/menyentuh project lain.

| Project | Hubungan | Detail |
|---|---|---|
| `apiakademik` | **Menkonsumsi** (2026-09-29) | Sumber data pembayaran siswa (`/siswa/{idperson}/pembayaran`). `idyayasan` = `idperson`. Base URL di setting `payment_api_base_url`. |
| `skadaexam` | **Berbagi data** | Membaca DB ujian `skadaexam` lewat koneksi `exam` untuk jadwal, ruangan, hasil, siswa. Tidak saling memanggil API. |

---

## Catatan Teknis Penting

- **Sumber data pembayaran = `apiakademik`**, bukan lagi `api.daruttaqwa.or.id/sisda/v1`. Base URL di setting aplikasi `app_settings.payment_api_base_url` (menang), fallback `.env` `PAYMENT_API_BASE_URL` lalu `API_AKADEMIK_BASE_URL`.
- Klien: `ApiAkademikService` (HTTP) + `PembayaranViewService` (bentuk tampilan `periods/categories/items`). `idyayasan` siswa = `idperson` apiakademik.
- Arti kolom item: `amount` = tagihan (jml kredit), `paid` = dibayar (jml debet), `remaining` = sisa per pos, dan `total_remaining` = **tunggakan jurnal** (item jatuh tempo yang belum lunas).
- **Nilai utama "Sisa Tunggakan" yang ditampilkan = kekurangan syarat ujian** (`sisa_tunggakan`, per pos `tagihan × persen/100 - dibayar`), bukan tunggakan jurnal — karena izin ujian ditentukan syarat ujian. Fallback ke tunggakan jatuh tempo bila tidak ada kebijakan ujian. Rincian jurnal tetap ditampilkan di blok "Komposisi Sisa Tagihan".
- **Status "Lunas/Belum" = definisi tunggal gerbang ujian** (`PembayaranViewService::statusUjianMap()`, `GET /{idunit}/ujian/pembayaran`, flag `terpenuhi`). Dipakai konsisten di daftar/Rekom, rekap per tingkat, live search, header detail, `/cek-pembayaran`, dan surat cetak. Peta di-cache 5 menit; gagal API → jatuh ke `status_pembayaran` DB (degradasi rapi). Unit lembaga dari `API_AKADEMIK_IDUNIT` (default `02`).
- Bila apiakademik diproteksi token, isi `API_AKADEMIK_TOKEN` (bearer) di `.env`.

- <stack, konvensi, gotcha, yang penting diingat AI>

---

## Changelog Roadmap

- <YYYY-MM-DD> — dibuat pertama kali.
