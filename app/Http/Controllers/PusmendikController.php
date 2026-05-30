<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PusmendikController extends Controller
{
    private function exam()
    {
        return DB::connection('exam');
    }

    private function setting(string $key, ?string $default = null): ?string
    {
        return DB::table('app_settings')->where('key', $key)->value('value') ?? $default;
    }

    public function dashboard()
    {
        $exam = $this->exam();

        return view('dashboard', [
            'stats' => [
                'siswa' => $exam->table('siswa')->whereNull('deleted_at')->count(),
                'lunas' => $exam->table('siswa')->whereNull('deleted_at')->where('status_pembayaran', 'Lunas')->count(),
                'rekom' => $exam->table('siswa')->whereNull('deleted_at')->where('rekomendasi', 'ya')->count(),
                'jadwal' => $exam->table('jadwal_ujian')->count(),
            ],
            'jadwalHariIni' => $exam->table('jadwal_ujian')
                ->leftJoin('mapel', 'mapel.id', '=', 'jadwal_ujian.mapel_id')
                ->select('jadwal_ujian.*', 'mapel.nama_mapel')
                ->whereDate('tanggal', now('Asia/Jakarta')->toDateString())
                ->orderBy('judul')
                ->get(),
        ]);
    }

    public function students(Request $request)
    {
        $localDatabase = DB::connection()->getDatabaseName();
        $handlerTable = DB::raw("`{$localDatabase}`.`recommendation_handlers`");
        $handlerJoinTable = DB::raw("`{$localDatabase}`.`recommendation_handlers` as recommendation_handlers");
        $latestHandlers = $this->exam()->table($handlerTable)
            ->select('exam_siswa_id', DB::raw('MAX(id) as latest_id'))
            ->groupBy('exam_siswa_id');

        $query = $this->studentQuery(['recommendation_handlers.nominal_rekom', 'recommendation_handlers.handled_by_name'])
            ->leftJoinSub($latestHandlers, 'latest_handlers', fn ($join) => $join->on('latest_handlers.exam_siswa_id', '=', 'siswa.id'))
            ->leftJoin($handlerJoinTable, 'recommendation_handlers.id', '=', 'latest_handlers.latest_id')
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = $request->string('q');
                $query->where(fn($inner) => $inner
                    ->where('siswa.nama', 'like', "%{$q}%")
                    ->orWhere('siswa.idyayasan', 'like', "%{$q}%")
                    ->orWhere('siswa.nis', 'like', "%{$q}%"));
            })
            ->when($request->filled('kelas'), fn($query) => $query->where('kelas.nama_kelas', $request->kelas))
            ->when($request->filled('status_pembayaran'), fn($query) => $query->where('siswa.status_pembayaran', $request->status_pembayaran))
            ->when($request->filled('rekomendasi'), fn($query) => $query->where('siswa.rekomendasi', $request->rekomendasi))
            ->when($request->filled('petugas'), fn($query) => $query->where('recommendation_handlers.handled_by_name', $request->petugas));

        return view('students.index', [
            'students' => $query->orderBy('kelas.tingkat')->orderBy('kelas.nama_kelas')->orderBy('siswa.nama')->paginate(25)->withQueryString(),
            'kelas' => $this->exam()->table('kelas')->orderBy('tingkat')->orderBy('nama_kelas')->pluck('nama_kelas'),
            'petugas' => DB::table('recommendation_handlers')
                ->whereNotNull('handled_by_name')
                ->distinct()
                ->orderBy('handled_by_name')
                ->pluck('handled_by_name'),
            'filters' => $request->all(),
        ]);
    }

    public function studentDetail(int $id)
    {
        $student = $this->studentQuery()->where('siswa.id', $id)->firstOrFail();
        $summary = $this->payment($student->idyayasan, true);
        $payments = $this->payment($student->idyayasan, false);

        return view('students.show', [
            'student' => $student,
            'summary' => $summary,
            'payments' => $payments,
            'paymentView' => $this->paymentViewModel($summary, $payments),
            'handler' => DB::table('recommendation_handlers')->where('exam_siswa_id', $id)->latest()->first(),
        ]);
    }

    public function saveRecommendation(Request $request, int $id)
    {
        $data = $request->validate([
            'nominal_rekom' => ['required', 'numeric', 'min:1'],
            'catatan' => ['nullable', 'string'],
        ]);

        $student = $this->studentQuery()->where('siswa.id', $id)->firstOrFail();
        $user = $request->session()->get('data_user');
        $tunggakan = $this->payment($student->idyayasan, false);
        $catatanRekomendasi = 'wali membayar Rp. ' . number_format((float) $data['nominal_rekom'], 0, ',', '.');

        DB::table('recommendation_handlers')->insert([
            'exam_siswa_id' => $student->id,
            'idyayasan' => $student->idyayasan,
            'nama' => $student->nama,
            'nominal_rekom' => $data['nominal_rekom'],
            'catatan' => $catatanRekomendasi,
            'tunggakan' => json_encode($tunggakan, JSON_UNESCAPED_UNICODE),
            'handled_by' => $user['id'] ?? null,
            'handled_by_name' => $user['name'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->exam()->table('siswa')->where('id', $id)->update([
            'rekomendasi' => 'ya',
            'catatan_rekomendasi' => $catatanRekomendasi,
            'updated_at' => now(),
        ]);

        if ($url = $this->setting('exam_rekom_api_url')) {
            Http::timeout(10)->asJson()->post($url, [
                'siswa_id' => $student->id,
                'idyayasan' => $student->idyayasan,
                'rekomendasi' => 'ya',
                'nominal_rekom' => $data['nominal_rekom'],
            ]);
        }

        return back()->with('success', 'Nominal rekom disimpan dan status rekomendasi diset ya.');
    }

    public function printRecommendation(int $id)
    {
        $student = $this->studentQuery()->where('siswa.id', $id)->firstOrFail();
        $handler = DB::table('recommendation_handlers')->where('exam_siswa_id', $id)->latest()->first();
        $storedTunggakan = $handler?->tunggakan ? json_decode($handler->tunggakan, true) : [];
        $summary = $this->payment($student->idyayasan, true);
        $payments = $storedTunggakan ?: $this->payment($student->idyayasan, false);
        $paymentView = $this->paymentViewModel($summary, $payments);
        $settings = DB::table('app_settings')->pluck('value', 'key');
        $paymentDeadlineDays = (int) ($settings['surat_batas_pembayaran_hari'] ?? 7);
        $createdAt = $handler?->created_at ? Carbon::parse($handler->created_at, 'Asia/Jakarta') : now('Asia/Jakarta');
        $letter = [
            'logo' => $settings['surat_logo'] ?? '',
            'line_1' => $settings['surat_kop_baris_1'] ?? "YAYASAN DARUT TAQWA SENGONAGUNG\nSEKOLAH MENENGAH KEJURUAN (SMK) DARUT TAQWA\nSENGONAGUNG PURWOSARI PASURUAN",
            'line_2' => $settings['surat_kop_baris_2'] ?? 'Jl. Pesantren Ngalah No. 16 Pandean, Sengonagung Pruwosari Pasuruan Jawa Timur - 67162, Telp. (0343) 61206',
            'location' => $settings['surat_lokasi'] ?? 'Sengonagung',
            'number' => sprintf('%03d/PUSMENDIK/ASAS/%s/%s', $handler->id ?? $student->id, $this->romanMonth((int) now('Asia/Jakarta')->format('n')), now('Asia/Jakarta')->format('Y')),
            'text_1' => $settings['surat_teks_1'] ?? 'Dengan ini, saya mengajukan pembayaran terkait administrasi agar anak saya dapat mengikuti Ujian Asesmen Sumatif Akhir Semester (ASAS) Semester Genap.',
            'text_2' => $settings['surat_teks_2'] ?? 'Adapun pembayaran yang telah saya lakukan sebesar Rp {nominal_rekom}, dari Rp {total_tagihan} akan saya lunasi paling lambat pada {tanggal_batas} sesuai dengan ketentuan pembayaran Net {batas_hari} dari tanggal pembuatan surat ini.',
            'text_3' => $settings['surat_teks_3'] ?? 'Demikian pernyataan ini saya buat dengan sebenar-benarnya dan dapat dipergunakan sebagaimana mestinya.',
            'deadline_days' => $paymentDeadlineDays,
            'deadline_date' => $createdAt->copy()->addDays($paymentDeadlineDays),
        ];

        return view('students.print', compact('student', 'handler', 'paymentView', 'letter'));
    }

    public function syncStudents()
    {
        $url = $this->setting('exam_sync_api_url');

        if (!$url) {
            return back()->with('error', 'Link API sinkronisasi belum diatur di Setting.');
        }

        try {
            $response = Http::timeout(30)->post($url);
            $response->throw();
        } catch (RequestException $exception) {
            return back()->with('error', 'Sinkronisasi gagal: ' . $exception->getMessage());
        }

        return back()->with('success', 'Trigger sinkronisasi terkirim. Respons: ' . Str::limit($response->body(), 160));
    }

    public function paymentStatus(Request $request)
    {
        $student = null;
        $summary = null;

        if ($request->filled('q')) {
            $student = $this->studentQuery()
                ->where(fn($query) => $query->where('siswa.idyayasan', $request->q)->orWhere('siswa.nama', 'like', '%' . $request->q . '%'))
                ->first();
            $summary = $student ? $this->payment($student->idyayasan, true) : null;
        }

        return view('payments.status', compact('student', 'summary'));
    }

    public function studentSearch(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        return response()->json(
            $this->studentQuery()
                ->where(fn ($query) => $query
                    ->where('siswa.nama', 'like', "%{$q}%")
                    ->orWhere('siswa.idyayasan', 'like', "%{$q}%")
                    ->orWhere('siswa.nis', 'like', "%{$q}%"))
                ->orderBy('siswa.nama')
                ->limit(8)
                ->get()
                ->map(fn ($student) => [
                    'id' => $student->id,
                    'idyayasan' => $student->idyayasan,
                    'nama' => $student->nama,
                    'kelas' => $student->nama_kelas,
                    'status_pembayaran' => $student->status_pembayaran,
                    'url' => route('payments.status', ['q' => $student->idyayasan]),
                ])
        );
    }

    public function schedules(Request $request)
    {
        $items = $this->exam()->table('jadwal_ujian')
            ->leftJoin('mapel', 'mapel.id', '=', 'jadwal_ujian.mapel_id')
            ->select('jadwal_ujian.*', 'mapel.nama_mapel')
            ->when($request->filled('tanggal'), fn($q) => $q->whereDate('tanggal', $request->tanggal))
            ->when($request->filled('q'), fn($q) => $q->where('judul', 'like', '%' . $request->q . '%'))
            ->orderByDesc('tanggal')
            ->paginate(30)
            ->withQueryString();

        return view('simple-table', [
            'title' => 'Jadwal Ujian',
            'items' => $items,
            'columns' => ['tanggal' => 'Tanggal', 'judul' => 'Nama Ujian', 'nama_mapel' => 'Mapel', 'durasi_menit' => 'Durasi', 'status' => 'Status'],
            'filters' => ['q' => 'Cari ujian', 'tanggal' => 'Tanggal'],
            'filterOptions' => [],
        ]);
    }

    public function rooms(Request $request)
    {
        $items = $this->exam()->table('sesi_ruangan_siswa as srs')
            ->join('siswa', 'siswa.id', '=', 'srs.siswa_id')
            ->leftJoin('kelas', 'kelas.id', '=', 'siswa.kelas_id')
            ->join('sesi_ruangan as sr', 'sr.id', '=', 'srs.sesi_ruangan_id')
            ->join('ruangan', 'ruangan.id', '=', 'sr.ruangan_id')
            ->select('siswa.idyayasan', 'siswa.nama', 'kelas.tingkat', 'kelas.nama_kelas', 'sr.nama_sesi', 'sr.waktu_mulai', 'sr.waktu_selesai', 'ruangan.nama_ruangan', 'srs.status_kehadiran')
            ->whereNull('siswa.deleted_at')
            ->where('sr.sumber', 'sumber')
            ->when($request->filled('q'), fn($q) => $q->where(fn($i) => $i->where('siswa.nama', 'like', '%' . $request->q . '%')->orWhere('siswa.idyayasan', 'like', '%' . $request->q . '%')))
            ->when($request->filled('tingkat'), fn($q) => $q->where('kelas.tingkat', $request->tingkat))
            ->when($request->filled('kelas'), fn($q) => $q->where('kelas.nama_kelas', $request->kelas))
            ->when($request->filled('ruangan'), fn($q) => $q->where('ruangan.nama_ruangan', $request->ruangan))
            ->when($request->filled('sesi'), fn($q) => $q->where('sr.nama_sesi', $request->sesi))
            ->orderBy('ruangan.nama_ruangan')
            ->orderBy('sr.waktu_mulai')
            ->orderBy('siswa.nama')
            ->paginate(30)
            ->withQueryString();

        return view('simple-table', [
            'title' => 'Ruangan dan Sesi Siswa',
            'items' => $items,
            'columns' => ['idyayasan' => 'ID Yayasan', 'nama' => 'Nama', 'tingkat' => 'Tingkat', 'nama_kelas' => 'Kelas', 'nama_sesi' => 'Sesi', 'nama_ruangan' => 'Ruangan', 'status_kehadiran' => 'Kehadiran'],
            'filters' => ['q' => 'Nama / ID Yayasan', 'tingkat' => 'Tingkat', 'kelas' => 'Kelas', 'ruangan' => 'Ruangan', 'sesi' => 'Sesi'],
            'filterOptions' => $this->roomFilterOptions(),
        ]);
    }

    public function supervisors(Request $request)
    {
        $items = $this->exam()->table('jadwal_ujian_sesi_ruangan as jsr')
            ->join('jadwal_ujian as ju', 'ju.id', '=', 'jsr.jadwal_ujian_id')
            ->join('sesi_ruangan as sr', 'sr.id', '=', 'jsr.sesi_ruangan_id')
            ->join('ruangan', 'ruangan.id', '=', 'sr.ruangan_id')
            ->leftJoin('users as pengawas', 'pengawas.id', '=', 'jsr.pengawas_id')
            ->select('ju.tanggal', 'ju.judul', 'sr.nama_sesi', 'ruangan.nama_ruangan', 'pengawas.name as pengawas')
            ->when($request->filled('tanggal'), fn($q) => $q->whereDate('ju.tanggal', $request->tanggal))
            ->orderByDesc('ju.tanggal')
            ->orderBy('ruangan.nama_ruangan')
            ->paginate(30)
            ->withQueryString();


        return view('simple-table', [
            'title' => 'Pengawas',
            'items' => $items,
            'columns' => ['tanggal' => 'Tanggal', 'judul' => 'Ujian', 'nama_sesi' => 'Sesi', 'nama_ruangan' => 'Ruangan', 'pengawas' => 'Pengawas'],
            'filters' => ['tanggal' => 'Tanggal Ujian'],
            'filterOptions' => [],
        ]);
    }

    public function liveExam()
    {
        $now = Carbon::now('Asia/Jakarta')->format('H:i:s');
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        $sessions = $this->exam()->table('hasil_ujian as h')
            ->join('siswa', 'siswa.id', '=', 'h.siswa_id')
            ->join('jadwal_ujian as ju', 'ju.id', '=', 'h.jadwal_ujian_id')
            ->join('sesi_ruangan as sr', 'sr.id', '=', 'h.sesi_ruangan_id')
            ->join('ruangan', 'ruangan.id', '=', 'sr.ruangan_id')
            ->select('ju.judul', 'sr.nama_sesi', 'ruangan.nama_ruangan', DB::raw('COUNT(*) peserta'), DB::raw('SUM(h.jumlah_dijawab) dijawab'), DB::raw('SUM(h.jumlah_tidak_dijawab) belum'))
            ->whereNull('siswa.deleted_at')
            ->whereDate('ju.tanggal', $today)
            ->where('sr.waktu_mulai', '<=', $now)
            ->where('sr.waktu_selesai', '>=', $now)
            ->groupBy('ju.judul', 'sr.nama_sesi', 'ruangan.nama_ruangan')
            ->orderBy('ruangan.nama_ruangan')
            ->get();

        $details = $this->exam()->table('hasil_ujian as h')
            ->join('siswa', 'siswa.id', '=', 'h.siswa_id')
            ->join('jadwal_ujian as ju', 'ju.id', '=', 'h.jadwal_ujian_id')
            ->join('sesi_ruangan as sr', 'sr.id', '=', 'h.sesi_ruangan_id')
            ->join('ruangan', 'ruangan.id', '=', 'sr.ruangan_id')
            ->select('siswa.nama', 'siswa.idyayasan', 'ju.judul', 'sr.nama_sesi', 'ruangan.nama_ruangan', 'h.jumlah_soal', 'h.jumlah_dijawab', 'h.jumlah_tidak_dijawab', 'h.status')
            ->whereNull('siswa.deleted_at')
            ->whereDate('ju.tanggal', $today)
            ->where('sr.waktu_mulai', '<=', $now)
            ->where('sr.waktu_selesai', '>=', $now)
            ->orderBy('ruangan.nama_ruangan')
            ->orderBy('siswa.nama')
            ->get();

        return view('live.index', compact('sessions', 'details'));
    }

    public function users()
    {
        $users = $this->exam()->table('users')
            ->join('model_has_roles', function ($join) {
                $join->on('model_has_roles.model_id', '=', 'users.id')
                    ->where('model_has_roles.model_type', 'like', '%User');
            })
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.name', 'data')
            ->select('users.id', 'users.name', 'users.email', 'roles.name as role')
            ->orderBy('users.name')
            ->paginate(30);

        return view('users.index', compact('users'));
    }

    public function attendance(Request $request)
    {
        $items = $this->exam()->table('enrollment_ujian as e')
            ->join('siswa', 'siswa.id', '=', 'e.siswa_id')
            ->leftJoin('kelas', 'kelas.id', '=', 'siswa.kelas_id')
            ->leftJoin('jadwal_ujian as ju', 'ju.id', '=', 'e.jadwal_ujian_id')
            ->leftJoin('sesi_ruangan as sr', 'sr.id', '=', 'e.sesi_ruangan_id')
            ->leftJoin('ruangan', 'ruangan.id', '=', 'sr.ruangan_id')
            ->leftJoin('sesi_ruangan_siswa as srs', function ($join) {
                $join->on('srs.siswa_id', '=', 'siswa.id')->on('srs.sesi_ruangan_id', '=', 'sr.id');
            })
            ->select('siswa.id as siswa_id', 'siswa.idyayasan', 'siswa.nama', 'kelas.tingkat', 'kelas.nama_kelas', 'ju.tanggal', 'ju.judul', 'sr.nama_sesi', 'ruangan.nama_ruangan', 'srs.status_kehadiran', 'e.status_enrollment', 'e.waktu_mulai_ujian', 'e.waktu_selesai_ujian')
            ->whereNull('siswa.deleted_at')
            ->when($request->filled('q'), fn($q) => $q->where(fn($i) => $i->where('siswa.nama', 'like', '%' . $request->q . '%')->orWhere('siswa.idyayasan', 'like', '%' . $request->q . '%')))
            ->when($request->filled('tingkat'), fn($q) => $q->where('kelas.tingkat', $request->tingkat))
            ->when($request->filled('kelas'), fn($q) => $q->where('kelas.nama_kelas', $request->kelas))
            ->when($request->filled('sesi'), fn($q) => $q->where('sr.nama_sesi', $request->sesi))
            ->when($request->filled('ruangan'), fn($q) => $q->where('ruangan.nama_ruangan', $request->ruangan))
            ->when($request->filled('status_kehadiran'), fn($q) => $q->where('srs.status_kehadiran', $request->status_kehadiran))
            ->when($request->filled('tanggal_awal'), fn($q) => $q->whereDate('ju.tanggal', '>=', $request->tanggal_awal))
            ->when($request->filled('tanggal_akhir'), fn($q) => $q->whereDate('ju.tanggal', '<=', $request->tanggal_akhir))
            ->orderByDesc('ju.tanggal')
            ->orderBy('siswa.nama')
            ->paginate(30)
            ->withQueryString();

        return view('attendance.index', [
            'title' => 'Kehadiran dan Status Pengerjaan',
            'items' => $items,
            'filters' => ['q' => 'Nama / ID Yayasan', 'tingkat' => 'Tingkat', 'kelas' => 'Kelas', 'sesi' => 'Sesi', 'ruangan' => 'Ruangan', 'status_kehadiran' => 'Status Kehadiran', 'tanggal_awal' => 'Tanggal Awal', 'tanggal_akhir' => 'Tanggal Akhir'],
            'filterOptions' => $this->attendanceFilterOptions(),
        ]);
    }

    public function settings()
    {
        return view('settings.index', [
            'settings' => DB::table('app_settings')->pluck('value', 'key'),
        ]);
    }

    public function saveSettings(Request $request)
    {
        $data = $request->validate([
            'exam_sync_api_url' => ['nullable', 'url'],
            'exam_rekom_api_url' => ['nullable', 'url'],
            'payment_api_base_url' => ['nullable', 'url'],
            'surat_logo' => ['nullable', 'string'],
            'surat_kop_baris_1' => ['nullable', 'string'],
            'surat_kop_baris_2' => ['nullable', 'string'],
            'surat_lokasi' => ['nullable', 'string'],
            'surat_batas_pembayaran_hari' => ['nullable', 'integer', 'min:1'],
            'surat_teks_1' => ['nullable', 'string'],
            'surat_teks_2' => ['nullable', 'string'],
            'surat_teks_3' => ['nullable', 'string'],
        ]);

        foreach ($data as $key => $value) {
            DB::table('app_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now(), 'created_at' => now()]
            );
        }

        return back()->with('success', 'Setting tersimpan.');
    }

    private function studentQuery(array $extraSelects = [])
    {
        return $this->exam()->table('siswa')
            ->leftJoin('kelas', 'kelas.id', '=', 'siswa.kelas_id')
            ->whereNull('siswa.deleted_at')
            ->select(array_merge(['siswa.*', 'kelas.nama_kelas', 'kelas.tingkat', 'kelas.jurusan'], $extraSelects));
    }

    private function roomFilterOptions(): array
    {
        $exam = $this->exam();

        return [
            'tingkat' => $exam->table('kelas')->whereNotNull('tingkat')->distinct()->orderBy('tingkat')->pluck('tingkat'),
            'kelas' => $exam->table('kelas')->distinct()->orderBy('nama_kelas')->pluck('nama_kelas'),
            'ruangan' => $exam->table('ruangan')->distinct()->orderBy('nama_ruangan')->pluck('nama_ruangan'),
            'sesi' => $exam->table('sesi_ruangan')->where('sumber', 'sumber')->distinct()->orderBy('nama_sesi')->pluck('nama_sesi'),
        ];
    }

    private function attendanceFilterOptions(): array
    {
        $exam = $this->exam();

        return [
            'tingkat' => $exam->table('kelas')->whereNotNull('tingkat')->distinct()->orderBy('tingkat')->pluck('tingkat'),
            'kelas' => $exam->table('kelas')->distinct()->orderBy('nama_kelas')->pluck('nama_kelas'),
            'sesi' => $exam->table('sesi_ruangan')->distinct()->orderBy('nama_sesi')->pluck('nama_sesi'),
            'ruangan' => $exam->table('ruangan')->distinct()->orderBy('nama_ruangan')->pluck('nama_ruangan'),
            'status_kehadiran' => collect(['hadir', 'tidak_hadir', 'sakit', 'izin']),
            'tanggal_awal' => $exam->table('jadwal_ujian')->distinct()->orderByDesc('tanggal')->pluck('tanggal'),
            'tanggal_akhir' => $exam->table('jadwal_ujian')->distinct()->orderByDesc('tanggal')->pluck('tanggal'),
        ];
    }

    private function payment(string $idyayasan, bool $summary): array
    {
        $base = rtrim($this->setting('payment_api_base_url', env('PAYMENT_API_BASE_URL', 'https://api.daruttaqwa.or.id/sisda/v1')), '/');
        $url = $base . '/payments/' . $idyayasan . ($summary ? '/summary' : '');

        try {
            return Http::timeout(12)->acceptJson()->get($url)->json() ?? [];
        } catch (\Throwable $exception) {
            return ['error' => $exception->getMessage()];
        }
    }

    private function paymentViewModel(array $summary, array $payments): array
    {
        $periods = $this->extractPeriodGroups($payments);
        $billRows = collect($periods)->flatMap(fn ($period) => $period['items'])->values()->all();

        $totalRemaining = $this->firstNumericByKeys($summary, ['total_remaining', 'remaining', 'total_tunggakan', 'tunggakan'])
            ?? collect($periods)->sum('total_remaining');

        $totalBill = $this->firstNumericByKeys($summary, ['total_billed', 'total_bill', 'total_tagihan', 'amount', 'total'])
            ?? collect($periods)->sum('total_billed');

        $totalPaid = $this->firstNumericByKeys($summary, ['total_paid', 'paid', 'total_bayar'])
            ?? collect($periods)->sum('total_paid');

        return [
            'total_remaining' => (float) $totalRemaining,
            'total_bill' => (float) $totalBill,
            'total_paid' => (float) $totalPaid,
            'periods' => $periods,
            'bills' => $billRows,
            'raw_summary' => $summary,
            'raw_payments' => $payments,
        ];
    }

    private function extractPeriodGroups(array $payload): array
    {
        $students = $payload['data'] ?? $payload;
        if (! is_array($students)) {
            return [];
        }

        if (! array_is_list($students)) {
            $students = [$students];
        }

        $periods = [];

        foreach ($students as $student) {
            foreach (($student['periods'] ?? []) as $period) {
                $periodRows = [];
                $categoryGroups = [];

                foreach (($period['categories'] ?? []) as $category) {
                    $categoryName = $category['category_name'] ?? 'Tagihan';
                    $categoryItems = [];

                    foreach (($category['items'] ?? []) as $item) {
                        $remaining = (float) ($item['remaining_balance'] ?? $item['total_remaining'] ?? 0);
                        $row = [
                            'name' => $categoryName,
                            'period' => (string) ($period['period_id'] ?? '-'),
                            'kelas_info' => $period['kelas_info'] ?? '-',
                            'unit' => $item['unit_name'] ?? $item['unit_id'] ?? '-',
                            'amount' => (float) ($item['amount_billed'] ?? 0),
                            'paid' => (float) ($item['amount_paid'] ?? 0),
                            'remaining' => $remaining,
                            'journal_date' => $item['journal_date'] ?? '-',
                            'last_updated' => $item['last_updated'] ?? '-',
                            'payment_status' => $item['payment_status'] ?? '-',
                            'raw' => $item,
                        ];

                        $categoryItems[] = $row;

                        if ($remaining > 0) {
                            $periodRows[] = $row;
                        }
                    }

                    $categoryRemaining = (float) ($category['summary']['total_remaining'] ?? 0);
                    if ($categoryRemaining > 0 && $categoryItems === []) {
                        $periodRows[] = [
                            'name' => $categoryName,
                            'period' => (string) ($period['period_id'] ?? '-'),
                            'kelas_info' => $period['kelas_info'] ?? '-',
                            'unit' => '-',
                            'amount' => (float) ($category['summary']['total_billed'] ?? 0),
                            'paid' => (float) ($category['summary']['total_paid'] ?? 0),
                            'remaining' => $categoryRemaining,
                            'journal_date' => '-',
                            'last_updated' => '-',
                            'payment_status' => '-',
                            'raw' => [],
                        ];
                    }

                    $categoryGroups[] = [
                        'category_name' => $categoryName,
                        'summary' => $category['summary'] ?? [],
                        'items' => $categoryItems,
                        'raw' => $category,
                    ];
                }

                $periodRemaining = (float) ($period['summary']['total_remaining'] ?? collect($periodRows)->sum('remaining'));
                $periodBilled = (float) ($period['summary']['total_billed'] ?? collect($periodRows)->sum('amount'));
                $periodPaid = (float) ($period['summary']['total_paid'] ?? collect($periodRows)->sum('paid'));

                if ($periodRemaining <= 0 && count($periodRows) === 0) {
                    continue;
                }

                $periods[] = [
                    'period_id' => (string) ($period['period_id'] ?? '-'),
                    'kelas_info' => $period['kelas_info'] ?? '-',
                    'summary' => $period['summary'] ?? [],
                    'total_billed' => $periodBilled,
                    'total_paid' => $periodPaid,
                    'total_remaining' => $periodRemaining,
                    'categories' => $categoryGroups,
                    'items' => $periodRows,
                    'raw' => $period,
                ];
            }
        }

        if ($periods !== []) {
            return $periods;
        }

        $fallbackRows = $this->extractBillRows($payload);
        if ($fallbackRows === []) {
            return [];
        }

        return collect($fallbackRows)
            ->groupBy('period')
            ->map(fn ($rows, $period) => [
                'period_id' => (string) $period,
                'kelas_info' => '-',
                'total_billed' => $rows->sum('amount'),
                'total_paid' => $rows->sum('paid'),
                'total_remaining' => $rows->sum('remaining'),
                'items' => $rows->values()->all(),
            ])
            ->values()
            ->all();
    }

    private function extractBillRows(array $payload): array
    {
        $rows = [];
        $this->walkPayload($payload, function (array $item) use (&$rows) {
            $remaining = $this->firstNumericByKeys($item, ['total_remaining', 'remaining_balance', 'remaining', 'sisa', 'sisa_tagihan', 'nominal_sisa']);
            $amount = $this->firstNumericByKeys($item, ['total_billed', 'amount_billed', 'total_bill', 'total_tagihan', 'bill', 'amount', 'nominal', 'tagihan']);
            $paid = $this->firstNumericByKeys($item, ['total_paid', 'amount_paid', 'paid', 'total_bayar']);

            if ($remaining === null && $amount === null) {
                return;
            }

            $rows[] = [
                'name' => $this->firstStringByKeys($item, ['name', 'nama', 'description', 'keterangan', 'jenis', 'title']) ?? 'Tagihan',
                'period' => $this->firstStringByKeys($item, ['period', 'periode', 'bulan', 'tahun']) ?? '-',
                'amount' => (float) ($amount ?? 0),
                'paid' => (float) ($paid ?? 0),
                'remaining' => (float) ($remaining ?? 0),
                'unit' => $this->firstStringByKeys($item, ['unit_name', 'unit_id']) ?? '-',
                'kelas_info' => '-',
                'journal_date' => $this->firstStringByKeys($item, ['journal_date', 'tanggal']) ?? '-',
            ];
        });

        return collect($rows)
            ->filter(fn ($row) => $row['remaining'] > 0 || $row['amount'] > 0)
            ->unique(fn ($row) => $row['name'].'|'.$row['period'].'|'.$row['amount'].'|'.$row['remaining'])
            ->values()
            ->all();
    }

    private function walkPayload(mixed $payload, callable $callback): void
    {
        if (! is_array($payload)) {
            return;
        }

        if (array_is_list($payload)) {
            foreach ($payload as $item) {
                $this->walkPayload($item, $callback);
            }

            return;
        }

        $callback($payload);

        foreach ($payload as $value) {
            $this->walkPayload($value, $callback);
        }
    }

    private function firstNumericByKeys(array $payload, array $keys): ?float
    {
        foreach ($keys as $key) {
            if (isset($payload[$key]) && is_numeric($payload[$key])) {
                return (float) $payload[$key];
            }
        }

        return null;
    }

    private function firstStringByKeys(array $payload, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (! empty($payload[$key]) && is_scalar($payload[$key])) {
                return (string) $payload[$key];
            }
        }

        return null;
    }

    private function romanMonth(int $month): string
    {
        return [1 => 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][$month] ?? 'I';
    }
}
