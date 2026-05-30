@extends('layouts.app', ['title' => 'Setting'])

@section('content')
<div class="mb-6">
    <p class="text-sm font-bold uppercase tracking-[0.2em] text-teal-700">Konfigurasi</p>
    <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">Setting Aplikasi</h1>
</div>
<div class="max-w-3xl rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
    <form method="post" action="{{ route('settings.store') }}" class="grid gap-4">
        @csrf
        <label class="grid gap-1.5 text-xs font-black uppercase tracking-wide text-slate-500">API Sync Data Siswa Sistem Ujian
            <input type="url" name="exam_sync_api_url" value="{{ old('exam_sync_api_url', $settings['exam_sync_api_url'] ?? '') }}" placeholder="https://..." class="min-h-12 rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold normal-case tracking-normal text-slate-900 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">
        </label>
        <label class="grid gap-1.5 text-xs font-black uppercase tracking-wide text-slate-500">API Set Status Rekomendasi Sistem Ujian
            <input type="url" name="exam_rekom_api_url" value="{{ old('exam_rekom_api_url', $settings['exam_rekom_api_url'] ?? '') }}" placeholder="https://..." class="min-h-12 rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold normal-case tracking-normal text-slate-900 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">
        </label>
        <label class="grid gap-1.5 text-xs font-black uppercase tracking-wide text-slate-500">Base URL API Pembayaran
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
        </label>
        <label class="grid gap-1.5 text-xs font-black uppercase tracking-wide text-slate-500">Teks Isi 2
            <textarea name="surat_teks_2" class="min-h-28 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold normal-case tracking-normal text-slate-900 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">{{ old('surat_teks_2', $settings['surat_teks_2'] ?? 'Adapun pembayaran yang telah saya lakukan sebesar Rp {nominal_rekom}, dari Rp {total_tagihan} akan saya lunasi paling lambat pada {tanggal_batas} sesuai dengan ketentuan pembayaran Net {batas_hari} dari tanggal pembuatan surat ini.') }}</textarea>
            <span class="text-xs font-semibold normal-case tracking-normal text-slate-400">Placeholder: {nominal_rekom}, {total_tagihan}, {tanggal_batas}, {batas_hari}</span>
        </label>
        <label class="grid gap-1.5 text-xs font-black uppercase tracking-wide text-slate-500">Teks Isi 3
            <textarea name="surat_teks_3" class="min-h-24 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold normal-case tracking-normal text-slate-900 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">{{ old('surat_teks_3', $settings['surat_teks_3'] ?? 'Demikian pernyataan ini saya buat dengan sebenar-benarnya dan dapat dipergunakan sebagaimana mestinya.') }}</textarea>
        </label>
        <button class="mt-2 w-fit rounded-2xl bg-teal-600 px-5 py-3 text-sm font-black text-white shadow-sm shadow-teal-600/20 transition hover:bg-teal-700">Simpan Setting</button>
    </form>
</div>
@endsection
