@extends('layouts.app', ['title' => 'Setting'])

@section('content')
<div class="mb-6">
    <p class="text-sm font-bold uppercase tracking-[0.2em] text-teal-700">Konfigurasi</p>
    <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">Setting Aplikasi</h1>
</div>
<div class="mb-5 max-w-3xl rounded-3xl border border-teal-200 bg-teal-50 p-5 shadow-sm">
    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
        <div>
            <h2 class="text-xl font-black text-slate-950">Editor Panduan</h2>
            <p class="mt-1 text-sm leading-6 text-teal-800">Tulis panduan berbasis Markdown, upload screenshot, dan tambahkan lampiran dokumen impor.</p>
        </div>
        <a href="{{ route('settings.guides.index') }}" class="w-fit rounded-2xl bg-teal-600 px-5 py-3 text-sm font-black text-white shadow-sm shadow-teal-600/20 transition hover:bg-teal-700">Buka Editor</a>
    </div>
</div>

<div class="mb-5 max-w-3xl rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
        <div>
            <h2 class="text-xl font-black text-slate-950">Aplikasi Android (APK)</h2>
            <p class="mt-1 text-sm leading-6 text-slate-500">Unggah file <code class="rounded bg-slate-100 px-1">.apk</code> untuk siswa. Tombol <strong>Download APK</strong> akan muncul otomatis di beranda, dengan nama aplikasi diambil dari nama file yang diunggah.</p>
        </div>
    </div>

    @if ($apk)
        <div class="mt-4 flex flex-col gap-3 rounded-2xl border border-teal-200 bg-teal-50 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <span class="text-lg">📱</span>
                    <span class="truncate text-sm font-black text-slate-950">{{ $apk['app_name'] }}</span>
                </div>
                <div class="mt-1 break-all text-xs font-semibold text-teal-800">{{ $apk['file_name'] }} &middot; {{ $apk['size_label'] }}@if ($apk['uploaded_at']) &middot; diunggah {{ \Illuminate\Support\Carbon::parse($apk['uploaded_at'])->translatedFormat('d M Y H:i') }}@endif</div>
            </div>
            <form method="post" action="{{ route('settings.apk.delete') }}" class="w-fit shrink-0" onsubmit="return confirm('Hapus aplikasi ini? Tombol download di beranda akan hilang.')">
                @csrf
                @method('delete')
                <button class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-black text-rose-700 transition hover:bg-rose-100">Hapus APK</button>
            </form>
        </div>
    @else
        <div class="mt-4 rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-4 text-sm font-semibold text-slate-500">Belum ada aplikasi yang diunggah.</div>
    @endif

    <form method="post" action="{{ route('settings.apk.store') }}" enctype="multipart/form-data" class="mt-4 grid gap-3">
        @csrf
        <label class="grid gap-1.5 text-xs font-black uppercase tracking-wide text-slate-500">{{ $apk ? 'Ganti File APK' : 'File APK' }}
            <input type="file" name="apk" accept=".apk,application/vnd.android.package-archive" required class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700 outline-none transition file:mr-3 file:rounded-xl file:border-0 file:bg-teal-600 file:px-4 file:py-2 file:text-xs file:font-black file:text-white focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">
        </label>
        @error('apk')
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">{{ $message }}</div>
        @enderror
        <div class="text-xs font-semibold text-amber-600">Maksimal 500 MB. Mengunggah file baru akan menggantikan file lama.</div>
        <button class="w-fit rounded-2xl bg-teal-600 px-5 py-3 text-sm font-black text-white shadow-sm shadow-teal-600/20 transition hover:bg-teal-700">{{ $apk ? 'Simpan APK Baru' : 'Unggah APK' }}</button>
    </form>
</div>
<div class="max-w-3xl rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
    <form method="post" action="{{ route('settings.store') }}" class="grid gap-4">
        @csrf
        <label class="grid gap-1.5 text-xs font-black uppercase tracking-wide text-slate-500">Base URL API Akademik
            <input type="url" name="payment_api_base_url" value="{{ old('payment_api_base_url', $settings['payment_api_base_url'] ?? env('PAYMENT_API_BASE_URL')) }}" class="min-h-12 rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold normal-case tracking-normal text-slate-900 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">
        </label>
        <div class="mt-4 border-t border-slate-200 pt-5">
            <h2 class="text-xl font-black text-slate-950">Setting Surat Rekomendasi</h2>
            <p class="mt-1 text-sm text-slate-500">Dipakai untuk kop surat, isi surat, batas pembayaran, dan lokasi penanggalan.</p>
        </div>
        <label class="grid gap-1.5 text-xs font-black uppercase tracking-wide text-slate-500">Logo Kop Surat
            <input type="text" name="surat_logo" value="{{ old('surat_logo', $settings['surat_logo'] ?? '') }}" placeholder="/logo.png atau https://..." class="min-h-12 rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold normal-case tracking-normal text-slate-900 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">
        </label>
        <label class="grid gap-1.5 text-xs font-black uppercase tracking-wide text-slate-500">Kop Baris 1
            <textarea name="surat_kop_baris_1" class="min-h-28 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold normal-case tracking-normal text-slate-900 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">{{ old('surat_kop_baris_1', $settings['surat_kop_baris_1'] ?? "YAYASAN DARUT TAQWA SENGONAGUNG\nSEKOLAH MENENGAH KEJURUAN (SMK) DARUT TAQWA\nSENGONAGUNG PURWOSARI PASURUAN") }}</textarea>
        </label>
        <label class="grid gap-1.5 text-xs font-black uppercase tracking-wide text-slate-500">Kop Baris 2
            <textarea name="surat_kop_baris_2" class="min-h-20 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold normal-case tracking-normal text-slate-900 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">{{ old('surat_kop_baris_2', $settings['surat_kop_baris_2'] ?? 'Jl. Pesantren Ngalah No. 16 Pandean, Sengonagung Pruwosari Pasuruan Jawa Timur - 67162, Telp. (0343) 61206') }}</textarea>
        </label>
        <div class="grid gap-4 sm:grid-cols-2">
            <label class="grid gap-1.5 text-xs font-black uppercase tracking-wide text-slate-500">Lokasi Penanggalan
                <input type="text" name="surat_lokasi" value="{{ old('surat_lokasi', $settings['surat_lokasi'] ?? 'Sengonagung') }}" class="min-h-12 rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold normal-case tracking-normal text-slate-900 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">
            </label>
            <label class="grid gap-1.5 text-xs font-black uppercase tracking-wide text-slate-500">Batas Pembayaran (hari)
                <input type="number" name="surat_batas_pembayaran_hari" min="1" value="{{ old('surat_batas_pembayaran_hari', $settings['surat_batas_pembayaran_hari'] ?? 7) }}" class="min-h-12 rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold normal-case tracking-normal text-slate-900 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">
            </label>
        </div>
        <label class="grid gap-1.5 text-xs font-black uppercase tracking-wide text-slate-500">Teks Isi 1
            <textarea name="surat_teks_1" class="min-h-24 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold normal-case tracking-normal text-slate-900 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">{{ old('surat_teks_1', $settings['surat_teks_1'] ?? 'Dengan ini, saya mengajukan pembayaran terkait administrasi agar anak saya dapat mengikuti Ujian Asesmen Sumatif Akhir Semester (ASAS) Semester Genap.') }}</textarea>
            <span class="text-xs font-semibold normal-case tracking-normal text-slate-400">Format: **tebal**, *miring*, ==highlight==.</span>
        </label>
        <label class="grid gap-1.5 text-xs font-black uppercase tracking-wide text-slate-500">Teks Isi 2
            <textarea name="surat_teks_2" class="min-h-28 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold normal-case tracking-normal text-slate-900 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">{{ old('surat_teks_2', $settings['surat_teks_2'] ?? 'Adapun pembayaran yang telah saya lakukan sebesar Rp {nominal_rekom}, dari Rp {total_tagihan} akan saya lunasi paling lambat pada {tanggal_batas} sesuai dengan ketentuan pembayaran Net {batas_hari} dari tanggal pembuatan surat ini.') }}</textarea>
            <span class="text-xs font-semibold normal-case tracking-normal text-slate-400">Placeholder: {nominal_rekom}, {total_tagihan}, {total_tunggakan}, {tanggal_batas}, {batas_hari}. Format: **tebal**, *miring*, ==highlight==.</span>
        </label>
        <label class="grid gap-1.5 text-xs font-black uppercase tracking-wide text-slate-500">Teks Isi 3
            <textarea name="surat_teks_3" class="min-h-24 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold normal-case tracking-normal text-slate-900 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">{{ old('surat_teks_3', $settings['surat_teks_3'] ?? 'Demikian pernyataan ini saya buat dengan sebenar-benarnya dan dapat dipergunakan sebagaimana mestinya.') }}</textarea>
            <span class="text-xs font-semibold normal-case tracking-normal text-slate-400">Format: **tebal**, *miring*, ==highlight==.</span>
        </label>
        <button class="mt-2 w-fit rounded-2xl bg-teal-600 px-5 py-3 text-sm font-black text-white shadow-sm shadow-teal-600/20 transition hover:bg-teal-700">Simpan Setting</button>
    </form>
</div>
@endsection
