@extends('layouts.app', ['title' => $title])

@section('content')
@php($groups = $items->getCollection()->groupBy('siswa_id'))
<div class="mb-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
    <div>
        <p class="text-sm font-bold uppercase tracking-[0.2em] text-teal-700">Monitoring</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">{{ $title }}</h1>
        <p class="mt-2 text-sm text-slate-500">Siswa tampil sebagai ringkasan, daftar ujian yang diikuti tersedia di accordion.</p>
    </div>
    <button type="button" data-attendance-export-open class="inline-flex shrink-0 items-center justify-center rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white shadow-lg shadow-slate-900/10 transition hover:bg-teal-700">Download Excel</button>
</div>

<form method="get" class="mb-6 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($filters as $name => $label)
            <label class="grid gap-1.5 text-xs font-black uppercase tracking-wide text-slate-500">
                {{ $label }}
                @if(isset($filterOptions[$name]))
                    <select name="{{ $name }}" class="min-h-11 rounded-2xl border border-slate-200 bg-slate-50 px-3 text-sm font-semibold normal-case tracking-normal text-slate-800 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">
                        <option value="">Semua</option>
                        @foreach($filterOptions[$name] as $option)
                            <option value="{{ $option }}" @selected((string) request($name) === (string) $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                @else
                    <input type="text" name="{{ $name }}" value="{{ request($name) }}" class="min-h-11 rounded-2xl border border-slate-200 bg-slate-50 px-3 text-sm font-semibold normal-case tracking-normal text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">
                @endif
            </label>
        @endforeach
    </div>
    <div class="mt-4 flex flex-wrap gap-2">
        <button class="rounded-2xl bg-teal-600 px-5 py-2.5 text-sm font-black text-white shadow-sm shadow-teal-600/20 transition hover:bg-teal-700">Filter</button>
        <a class="rounded-2xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-black text-slate-700 transition hover:bg-slate-50" href="{{ url()->current() }}">Reset</a>
    </div>
</form>

<div class="grid gap-3">
    @forelse($groups as $studentItems)
        @php($first = $studentItems->first())
        <details class="group rounded-3xl border border-slate-200 bg-white p-4 shadow-sm open:border-teal-200 open:shadow-lg open:shadow-teal-900/5">
            <summary class="flex cursor-pointer list-none flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="font-black text-slate-950">{{ $first->nama }}</div>
                    <div class="mt-1 text-sm text-slate-500">{{ $first->idyayasan }} / {{ $first->tingkat }} / {{ $first->nama_kelas }}</div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-700">{{ $studentItems->count() }} ujian</span>
                    <span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-black text-teal-700">{{ $first->status_kehadiran ?? 'belum diisi' }}</span>
                    <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-black text-amber-700">{{ $first->status_enrollment ?? '-' }}</span>
                </div>
            </summary>
            <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200">
                <div class="bg-slate-50 px-4 py-3 text-xs font-black uppercase tracking-wide text-slate-500">Daftar ujian yang diikuti</div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-white"><tr><th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Tanggal</th><th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Ujian</th><th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Sesi</th><th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Ruangan</th><th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Kehadiran</th><th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Pengerjaan</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                        @foreach($studentItems as $item)
                            <tr>
                                <td class="whitespace-nowrap px-4 py-3">{{ $item->tanggal ?? '-' }}</td>
                                <td class="min-w-56 px-4 py-3 font-bold">{{ $item->judul ?? '-' }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $item->nama_sesi ?? '-' }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $item->nama_ruangan ?? '-' }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $item->status_kehadiran ?? '-' }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $item->status_enrollment ?? '-' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </details>
    @empty
        <div class="rounded-3xl border border-slate-200 bg-white p-10 text-center text-slate-500 shadow-sm">Data tidak ditemukan.</div>
    @endforelse
</div>

<div class="mt-5">{{ $items->links() }}</div>

<div id="attendance-export-modal" data-attendance-export-modal
    class="fixed inset-0 z-[90] hidden items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm">
    <div class="w-full max-w-md rounded-3xl bg-white p-5 shadow-2xl">
        <div class="mb-4 flex items-start justify-between gap-3">
            <div>
                <div class="text-xs font-black uppercase tracking-wide text-teal-700">Export Excel</div>
                <h2 class="mt-1 text-xl font-black text-slate-950">Unduh Kehadiran per Hari</h2>
            </div>
            <button type="button" data-attendance-export-close
                class="shrink-0 rounded-2xl bg-slate-100 px-4 py-2 text-sm font-black text-slate-700">Tutup</button>
        </div>

        @if ($tanggalExportDefault === null)
            <p class="rounded-2xl bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">
                Belum ada jadwal ujian pada tahun ajaran / paket ujian aktif, jadi belum ada tanggal yang bisa diunduh.
            </p>
        @else
            <form method="get" action="{{ route('attendance.download') }}" class="grid gap-3">
                <label class="grid gap-1.5 text-xs font-black uppercase tracking-wide text-slate-500">
                    Tanggal Ujian
                    {{-- data-no-auto-submit: layout auto-submit semua <select>, modal ini tidak boleh ikut. --}}
                    <select name="tanggal" required data-no-auto-submit
                        class="min-h-11 rounded-2xl border border-slate-200 bg-slate-50 px-3 text-sm font-semibold normal-case tracking-normal text-slate-800 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-100">
                        @foreach ($filterOptions['tanggal_awal'] as $tanggal)
                            <option value="{{ $tanggal }}" @selected((string) $tanggal === (string) $tanggalExportDefault)>{{ $tanggal }}</option>
                        @endforeach
                    </select>
                </label>
                <p class="text-xs font-semibold text-slate-500">
                    Berkas berisi seluruh siswa yang ujian pada tanggal tersebut, tanpa mengikuti filter di halaman.
                </p>
                <button type="submit"
                    class="rounded-2xl bg-teal-600 px-5 py-2.5 text-sm font-black text-white shadow-sm shadow-teal-600/20 transition hover:bg-teal-700">Download Excel</button>
            </form>
        @endif
    </div>
</div>

<script>
    document.querySelectorAll('[data-attendance-export-open]').forEach((button) => {
        button.addEventListener('click', () => {
            const modal = document.getElementById('attendance-export-modal');
            modal?.classList.remove('hidden');
            modal?.classList.add('flex');
        });
    });
    document.querySelectorAll('[data-attendance-export-close]').forEach((button) => {
        button.addEventListener('click', () => {
            const modal = button.closest('.fixed');
            modal?.classList.add('hidden');
            modal?.classList.remove('flex');
        });
    });
    document.querySelectorAll('[data-attendance-export-modal]').forEach((modal) => {
        modal.addEventListener('click', (event) => {
            if (event.target !== modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        });
    });
</script>
@endsection
