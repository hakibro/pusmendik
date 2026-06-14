<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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

    private function activeAcademicYearId(): ?int
    {
        return $this->exam()->table('tahun_ajaran')
            ->where(fn($query) => $query->where('is_active', 1)->orWhere('status', 'aktif'))
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->value('id');
    }

    private function activeExamPackageId(?int $tahunAjaranId = null): ?int
    {
        return $this->exam()->table('paket_ujian')
            ->when($tahunAjaranId, fn($query) => $query->where('tahun_ajaran_id', $tahunAjaranId))
            ->where('status', 'aktif')
            ->orderByDesc('id')
            ->value('id');
    }

    public function dashboard()
    {
        $exam = $this->exam();
        $students = $this->studentQuery();
        $activeAcademicYearId = $this->activeAcademicYearId();
        $activeExamPackageId = $this->activeExamPackageId($activeAcademicYearId);

        return view('dashboard', [
            'stats' => [
                'siswa' => (clone $students)->count(),
                'lunas' => (clone $students)->where('sta.status_pembayaran', 'Lunas')->count(),
                'rekom' => (clone $students)->where('sta.rekomendasi', 'ya')->count(),
                'jadwal' => $exam->table('jadwal_ujian')
                    ->when($activeAcademicYearId, fn($query) => $query->where('tahun_ajaran_id', $activeAcademicYearId))
                    ->when($activeExamPackageId, fn($query) => $query->where('paket_ujian_id', $activeExamPackageId))
                    ->count(),
            ],
            'jadwalHariIni' => $exam->table('jadwal_ujian')
                ->leftJoin('mapel', 'mapel.id', '=', 'jadwal_ujian.mapel_id')
                ->leftJoin('paket_ujian', 'paket_ujian.id', '=', 'jadwal_ujian.paket_ujian_id')
                ->select('jadwal_ujian.*', 'mapel.nama_mapel', 'paket_ujian.nama as paket_ujian_nama')
                ->when($activeAcademicYearId, fn($query) => $query->where('jadwal_ujian.tahun_ajaran_id', $activeAcademicYearId))
                ->when($activeExamPackageId, fn($query) => $query->where('jadwal_ujian.paket_ujian_id', $activeExamPackageId))
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
            ->leftJoinSub($latestHandlers, 'latest_handlers', fn($join) => $join->on('latest_handlers.exam_siswa_id', '=', 'siswa.id'))
            ->leftJoin($handlerJoinTable, 'recommendation_handlers.id', '=', 'latest_handlers.latest_id')
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = $request->string('q');
                $query->where(fn($inner) => $inner
                    ->where('siswa.nama', 'like', "%{$q}%")
                    ->orWhere('siswa.idyayasan', 'like', "%{$q}%")
                    ->orWhere('siswa.nis', 'like', "%{$q}%"));
            })
            ->when($request->filled('kelas'), fn($query) => $query->where('kelas.nama_kelas', $request->kelas))
            ->when($request->filled('status_pembayaran'), fn($query) => $query->where('sta.status_pembayaran', $request->status_pembayaran))
            ->when($request->filled('rekomendasi'), fn($query) => $query->where(DB::raw("COALESCE(sta.rekomendasi, 'tidak')"), $request->rekomendasi))
            ->when($request->filled('petugas'), fn($query) => $query->where('recommendation_handlers.handled_by_name', $request->petugas));

        return view('students.index', [
            'students' => $query->orderBy('kelas.tingkat')->orderBy('kelas.nama_kelas')->orderBy('siswa.nama')->paginate(25)->withQueryString(),
            'kelas' => $this->exam()->table('kelas')
                ->when($this->activeAcademicYearId(), fn($query, $tahunAjaranId) => $query->where('tahun_ajaran_id', $tahunAjaranId))
                ->orderBy('tingkat')
                ->orderBy('nama_kelas')
                ->pluck('nama_kelas'),
            'petugas' => DB::table('recommendation_handlers')
                ->whereNotNull('handled_by_name')
                ->distinct()
                ->orderBy('handled_by_name')
                ->pluck('handled_by_name'),
            'paymentSummary' => $this->paymentSummaryByLevel(),
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
            'examInfo' => $this->studentExamInfo($student->id),
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


        if ($student->tahun_ajaran_id) {
            $this->exam()->table('siswa_tahun_ajaran')
                ->where('siswa_id', $id)
                ->where('tahun_ajaran_id', $student->tahun_ajaran_id)
                ->update([
                    'rekomendasi' => 'ya',
                    'catatan' => $catatanRekomendasi,
                    'updated_at' => now(),
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

        if ($storedTunggakan && $paymentView['unpaid_periods'] === []) {
            $freshPayments = $this->payment($student->idyayasan, false);
            $freshPaymentView = $this->paymentViewModel($summary, $freshPayments);

            if ($freshPaymentView['unpaid_periods'] !== []) {
                $payments = $freshPayments;
                $paymentView = $freshPaymentView;
            }
        }

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
            'text_1_html' => $this->formatLetterText($settings['surat_teks_1'] ?? 'Dengan ini, saya mengajukan pembayaran terkait administrasi agar anak saya dapat mengikuti Ujian Asesmen Sumatif Akhir Semester (ASAS) Semester Genap.'),
            'text_3_html' => $this->formatLetterText($settings['surat_teks_3'] ?? 'Demikian pernyataan ini saya buat dengan sebenar-benarnya dan dapat dipergunakan sebagaimana mestinya.'),
            'deadline_days' => $paymentDeadlineDays,
            'deadline_date' => $createdAt->copy()->addDays($paymentDeadlineDays),
        ];
        $letter['text_2_html'] = $this->formatLetterText($letter['text_2']);

        return view('students.print', compact('student', 'handler', 'paymentView', 'letter'));
    }

    public function paymentStatus(Request $request)
    {
        $student = null;
        $summary = null;
        $paymentView = null;

        if ($request->filled('q')) {
            $student = $this->studentQuery()
                ->where(fn($query) => $query->where('siswa.idyayasan', $request->q)->orWhere('siswa.nama', 'like', '%' . $request->q . '%'))
                ->first();
            if ($student) {
                $summary = $this->payment($student->idyayasan, true);
                $payments = $this->payment($student->idyayasan, false);
                $paymentView = $this->paymentViewModel($summary, $payments);
            }
        }

        return view('payments.status', compact('student', 'summary', 'paymentView'));
    }

    public function studentSearch(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        return response()->json(
            $this->studentQuery()
                ->where(fn($query) => $query
                    ->where('siswa.nama', 'like', "%{$q}%")
                    ->orWhere('siswa.idyayasan', 'like', "%{$q}%")
                    ->orWhere('siswa.nis', 'like', "%{$q}%"))
                ->orderBy('siswa.nama')
                ->limit(8)
                ->get()
                ->map(fn($student) => [
                    'id' => $student->id,
                    'idyayasan' => $student->idyayasan,
                    'nama' => $student->nama,
                    'kelas' => $student->nama_kelas,
                    'status_pembayaran' => $student->status_pembayaran,
                    'url' => route('payments.status', ['q' => $student->idyayasan]),
                ])
        );
    }

    public function rekomStudentSearch(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $localDatabase = DB::connection()->getDatabaseName();
        $handlerTable = DB::raw("`{$localDatabase}`.`recommendation_handlers`");
        $handlerJoinTable = DB::raw("`{$localDatabase}`.`recommendation_handlers` as recommendation_handlers");
        $latestHandlers = $this->exam()->table($handlerTable)
            ->select('exam_siswa_id', DB::raw('MAX(id) as latest_id'))
            ->groupBy('exam_siswa_id');

        $query = $this->studentQuery(['recommendation_handlers.nominal_rekom', 'recommendation_handlers.handled_by_name'])
            ->leftJoinSub($latestHandlers, 'latest_handlers', fn($join) => $join->on('latest_handlers.exam_siswa_id', '=', 'siswa.id'))
            ->leftJoin($handlerJoinTable, 'recommendation_handlers.id', '=', 'latest_handlers.latest_id');

        if (mb_strlen($q) >= 2) {
            $query->where(fn($inner) => $inner
                ->where('siswa.nama', 'like', "%{$q}%")
                ->orWhere('siswa.idyayasan', 'like', "%{$q}%")
                ->orWhere('siswa.nis', 'like', "%{$q}%"));
        }

        if ($request->filled('kelas')) {
            $query->where('kelas.nama_kelas', $request->kelas);
        }

        if ($request->filled('status_pembayaran')) {
            $query->where('sta.status_pembayaran', $request->status_pembayaran);
        }

        if ($request->filled('rekomendasi')) {
            $query->where(DB::raw("COALESCE(sta.rekomendasi, 'tidak')"), $request->rekomendasi);
        }

        if ($request->filled('petugas')) {
            $query->where('recommendation_handlers.handled_by_name', $request->petugas);
        }

        return response()->json(
            $query
                ->orderBy('kelas.tingkat')
                ->orderBy('kelas.nama_kelas')
                ->orderBy('siswa.nama')
                ->limit(50)
                ->get()
                ->map(fn($student) => [
                    'id' => $student->id,
                    'idyayasan' => $student->idyayasan,
                    'nama' => $student->nama,
                    'kelas' => $student->nama_kelas,
                    'status_pembayaran' => $student->status_pembayaran,
                    'rekomendasi' => $student->rekomendasi ?: 'tidak',
                    'nominal_rekom' => $student->nominal_rekom,
                    'handled_by_name' => $student->handled_by_name,
                    'url' => route('students.show', $student->id),
                ])
        );
    }

    public function schedules(Request $request)
    {
        $activeAcademicYearId = $this->activeAcademicYearId();
        $activeExamPackageId = $this->activeExamPackageId($activeAcademicYearId);

        $items = $this->exam()->table('jadwal_ujian')
            ->leftJoin('mapel', 'mapel.id', '=', 'jadwal_ujian.mapel_id')
            ->leftJoin('tahun_ajaran', 'tahun_ajaran.id', '=', 'jadwal_ujian.tahun_ajaran_id')
            ->leftJoin('paket_ujian', 'paket_ujian.id', '=', 'jadwal_ujian.paket_ujian_id')
            ->select('jadwal_ujian.*', 'mapel.nama_mapel', 'tahun_ajaran.nama as tahun_ajaran_nama', 'paket_ujian.nama as paket_ujian_nama')
            ->when($request->filled('tanggal'), fn($q) => $q->whereDate('tanggal', $request->tanggal))
            ->when($activeAcademicYearId, fn($q) => $q->where('jadwal_ujian.tahun_ajaran_id', $activeAcademicYearId))
            ->when($activeExamPackageId, fn($q) => $q->where('jadwal_ujian.paket_ujian_id', $activeExamPackageId))
            ->orderBy('tanggal')
            ->orderBy('judul')
            ->get();

        return view('schedules.index', [
            'title' => 'Jadwal Ujian',
            'items' => $items,
            'tanggalOptions' => $this->exam()->table('jadwal_ujian')
                ->select('tanggal')
                ->distinct()
                ->when($activeAcademicYearId, fn($q) => $q->where('tahun_ajaran_id', $activeAcademicYearId))
                ->when($activeExamPackageId, fn($q) => $q->where('paket_ujian_id', $activeExamPackageId))
                ->orderBy('tanggal')
                ->pluck('tanggal'),
        ]);
    }

    public function rooms(Request $request)
    {
        $activeAcademicYearId = $this->activeAcademicYearId();
        $activeExamPackageId = $this->activeExamPackageId($activeAcademicYearId);

        $items = $this->exam()->table('sesi_ruangan_siswa as srs')
            ->join('siswa', 'siswa.id', '=', 'srs.siswa_id')
            ->join('sesi_ruangan as sr', 'sr.id', '=', 'srs.sesi_ruangan_id')
            ->leftJoin('siswa_tahun_ajaran as sta', function ($join) use ($activeAcademicYearId) {
                $join->on('sta.siswa_id', '=', 'siswa.id');
                if ($activeAcademicYearId) {
                    $join->where('sta.tahun_ajaran_id', $activeAcademicYearId);
                }
            })
            ->leftJoin('kelas', 'kelas.id', '=', 'sta.kelas_id')
            ->join('ruangan', 'ruangan.id', '=', 'sr.ruangan_id')
            ->select('siswa.idyayasan', 'siswa.nama', 'kelas.tingkat', 'kelas.nama_kelas', 'sr.nama_sesi', 'sr.waktu_mulai', 'sr.waktu_selesai', 'ruangan.nama_ruangan', 'srs.status_kehadiran')
            ->whereNull('siswa.deleted_at')
            ->where('sr.sumber', 'sumber')->where('sr.paket_ujian_id', $activeExamPackageId)
            ->when($activeAcademicYearId, fn($q) => $q->where('sr.tahun_ajaran_id', $activeAcademicYearId))
            ->when($request->filled('q'), fn($q) => $q->where(fn($i) => $i->where('siswa.nama', 'like', '%' . $request->q . '%')->orWhere('siswa.idyayasan', 'like', '%' . $request->q . '%')))
            ->when($request->filled('tingkat'), fn($q) => $q->where('kelas.tingkat', $request->tingkat))
            ->when($request->filled('kelas'), fn($q) => $q->where('kelas.nama_kelas', $request->kelas))
            ->when($request->filled('ruangan'), fn($q) => $q->where('ruangan.nama_ruangan', $request->ruangan))
            ->when($request->filled('sesi'), fn($q) => $q->where('sr.nama_sesi', $request->sesi))
            ->orderBy('ruangan.nama_ruangan')
            ->orderBy('sr.waktu_mulai')
            ->orderBy('siswa.nama')
            ->get();

        return view('rooms.index', [
            'title' => 'Ruangan dan Sesi Siswa',
            'items' => $items,
            'filters' => ['q' => 'Nama / ID Yayasan', 'tingkat' => 'Tingkat', 'kelas' => 'Kelas', 'ruangan' => 'Ruangan', 'sesi' => 'Sesi'],
            'filterOptions' => $this->roomFilterOptions(),
        ]);
    }

    public function roomStudentSearch(Request $request)
    {
        $activeAcademicYearId = $this->activeAcademicYearId();
        $activeExamPackageId = $this->activeExamPackageId($activeAcademicYearId);
        $q = trim((string) $request->query('q', ''));

        $query = $this->exam()->table('sesi_ruangan_siswa as srs')
            ->join('siswa', 'siswa.id', '=', 'srs.siswa_id')
            ->join('sesi_ruangan as sr', 'sr.id', '=', 'srs.sesi_ruangan_id')
            ->leftJoin('siswa_tahun_ajaran as sta', function ($join) use ($activeAcademicYearId) {
                $join->on('sta.siswa_id', '=', 'siswa.id');
                if ($activeAcademicYearId) {
                    $join->where('sta.tahun_ajaran_id', $activeAcademicYearId);
                }
            })
            ->leftJoin('kelas', 'kelas.id', '=', 'sta.kelas_id')
            ->join('ruangan', 'ruangan.id', '=', 'sr.ruangan_id')
            ->select('siswa.idyayasan', 'siswa.nama', 'kelas.tingkat', 'kelas.nama_kelas', 'sr.nama_sesi', 'sr.waktu_mulai', 'sr.waktu_selesai', 'ruangan.nama_ruangan', 'srs.status_kehadiran')
            ->whereNull('siswa.deleted_at')
            ->where('sr.sumber', 'sumber')
            ->when($activeAcademicYearId, fn($q) => $q->where('sr.tahun_ajaran_id', $activeAcademicYearId));

        if (mb_strlen($q) >= 2) {
            $query->where(fn($inner) => $inner
                ->where('siswa.nama', 'like', "%{$q}%")
                ->orWhere('siswa.idyayasan', 'like', "%{$q}%")
                ->orWhere('siswa.nis', 'like', "%{$q}%"));
        }

        if ($request->filled('ruangan')) {
            $query->where('ruangan.nama_ruangan', $request->ruangan);
        }

        if ($request->filled('tingkat')) {
            $query->where('kelas.tingkat', $request->tingkat);
        }

        if ($request->filled('kelas')) {
            $query->where('kelas.nama_kelas', $request->kelas);
        }

        if ($request->filled('sesi')) {
            $query->where('sr.nama_sesi', $request->sesi);
        }

        return response()->json(
            $query
                ->orderBy('ruangan.nama_ruangan')
                ->orderBy('sr.waktu_mulai')
                ->orderBy('siswa.nama')
                ->limit(100)
                ->get()
                ->groupBy(fn($item) => ($item->nama_ruangan ?? '-') . '|' . ($item->nama_sesi ?? '-'))
                ->map(fn($rows, $key) => [
                    'key' => $key,
                    'ruangan' => explode('|', $key)[0],
                    'sesi' => explode('|', $key)[1],
                    'waktu_mulai' => $rows->first()->waktu_mulai ?? '-',
                    'waktu_selesai' => $rows->first()->waktu_selesai ?? '-',
                    'count' => $rows->count(),
                    'students' => $rows->map(fn($student) => [
                        'nama' => $student->nama,
                        'idyayasan' => $student->idyayasan,
                        'nama_kelas' => $student->nama_kelas,
                    ])->values(),
                ])
                ->values()
        );
    }

    public function supervisors(Request $request)
    {
        $activeAcademicYearId = $this->activeAcademicYearId();
        $activeExamPackageId = $this->activeExamPackageId($activeAcademicYearId);

        $supervisorAssignments = $this->exam()->table('jadwal_ujian_sesi_ruangan as jsr2')
            ->join('jadwal_ujian as ju2', 'ju2.id', '=', 'jsr2.jadwal_ujian_id')
            ->select('ju2.tanggal', 'jsr2.sesi_ruangan_id', DB::raw('MAX(jsr2.pengawas_id) as pengawas_id'))
            ->whereNotNull('jsr2.pengawas_id')
            ->when($activeAcademicYearId, fn($q) => $q->where('ju2.tahun_ajaran_id', $activeAcademicYearId))
            ->when($activeExamPackageId, fn($q) => $q->where('ju2.paket_ujian_id', $activeExamPackageId))
            ->groupBy('ju2.tanggal', 'jsr2.sesi_ruangan_id');

        $items = $this->exam()->table('jadwal_ujian_sesi_ruangan as jsr')
            ->join('jadwal_ujian as ju', 'ju.id', '=', 'jsr.jadwal_ujian_id')
            ->join('sesi_ruangan as sr', 'sr.id', '=', 'jsr.sesi_ruangan_id')
            ->join('ruangan', 'ruangan.id', '=', 'sr.ruangan_id')
            ->leftJoinSub($supervisorAssignments, 'supervisor_assignments', function ($join) {
                $join->on('supervisor_assignments.tanggal', '=', 'ju.tanggal')
                    ->on('supervisor_assignments.sesi_ruangan_id', '=', 'jsr.sesi_ruangan_id');
            })
            ->leftJoin('guru as pengawas', 'pengawas.id', '=', DB::raw('COALESCE(jsr.pengawas_id, supervisor_assignments.pengawas_id)'))
            ->leftJoin('tahun_ajaran', 'tahun_ajaran.id', '=', 'ju.tahun_ajaran_id')
            ->leftJoin('paket_ujian', 'paket_ujian.id', '=', 'ju.paket_ujian_id')
            ->select('ju.tanggal', 'ju.judul', 'sr.nama_sesi', 'ruangan.nama_ruangan', 'pengawas.nama as pengawas', 'tahun_ajaran.nama as tahun_ajaran_nama', 'paket_ujian.nama as paket_ujian_nama')
            ->when($request->filled('tanggal'), fn($q) => $q->whereDate('ju.tanggal', $request->tanggal))
            ->when($activeAcademicYearId, fn($q) => $q->where('ju.tahun_ajaran_id', $activeAcademicYearId))
            ->when($activeExamPackageId, fn($q) => $q->where('ju.paket_ujian_id', $activeExamPackageId))
            ->orderBy('ruangan.nama_ruangan')
            ->orderBy('ju.tanggal')
            ->orderBy('sr.nama_sesi')
            ->get();


        return view('supervisors.index', [
            'title' => 'Pengawas',
            'items' => $items,
            'filters' => ['tanggal' => 'Tanggal Ujian'],
            'tanggalOptions' => $this->exam()->table('jadwal_ujian')
                ->select('tanggal')
                ->distinct()
                ->when($activeAcademicYearId, fn($q) => $q->where('tahun_ajaran_id', $activeAcademicYearId))
                ->when($activeExamPackageId, fn($q) => $q->where('paket_ujian_id', $activeExamPackageId))
                ->orderBy('tanggal')
                ->limit(20)
                ->pluck('tanggal'),
            'filterOptions' => [],
        ]);
    }

    public function liveExam()
    {
        $now = Carbon::now('Asia/Jakarta')->format('H:i:s');
        $today = Carbon::now('Asia/Jakarta')->toDateString();
        $activeAcademicYearId = $this->activeAcademicYearId();
        $activeExamPackageId = $this->activeExamPackageId($activeAcademicYearId);

        $sessions = $this->exam()->table('hasil_ujian as h')
            ->join('siswa', 'siswa.id', '=', 'h.siswa_id')
            ->join('jadwal_ujian as ju', 'ju.id', '=', 'h.jadwal_ujian_id')
            ->join('sesi_ruangan as sr', 'sr.id', '=', 'h.sesi_ruangan_id')
            ->join('ruangan', 'ruangan.id', '=', 'sr.ruangan_id')
            ->select('ju.judul', 'sr.nama_sesi', 'ruangan.nama_ruangan', DB::raw('COUNT(*) peserta'), DB::raw('SUM(h.jumlah_dijawab) dijawab'), DB::raw('SUM(h.jumlah_tidak_dijawab) belum'))
            ->whereNull('siswa.deleted_at')
            ->whereDate('ju.tanggal', $today)
            ->when($activeAcademicYearId, fn($q) => $q->where('ju.tahun_ajaran_id', $activeAcademicYearId))
            ->when($activeExamPackageId, fn($q) => $q->where('ju.paket_ujian_id', $activeExamPackageId))
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
            ->when($activeAcademicYearId, fn($q) => $q->where('ju.tahun_ajaran_id', $activeAcademicYearId))
            ->when($activeExamPackageId, fn($q) => $q->where('ju.paket_ujian_id', $activeExamPackageId))
            ->where('sr.waktu_mulai', '<=', $now)
            ->where('sr.waktu_selesai', '>=', $now)
            ->orderBy('ruangan.nama_ruangan')
            ->orderBy('siswa.nama')
            ->get();

        return view('live.index', compact('sessions', 'details'));
    }

    public function examResults(Request $request)
    {
        $query = $this->examResultsQuery($request);
        $analysisRows = (clone $query)->get();
        $total = $analysisRows->count();
        $finished = $analysisRows->whereIn('status', ['selesai', 'finished', 'final'])->count();
        $average = $total ? round($analysisRows->avg('nilai'), 2) : 0;
        $highest = $total ? round($analysisRows->max('nilai'), 2) : 0;
        $lowest = $total ? round($analysisRows->min('nilai'), 2) : 0;
        $passed = $analysisRows->where('lulus', 1)->count();

        return view('results.index', [
            'items' => $query->orderByDesc('ju.tanggal')->orderBy('siswa.nama')->paginate(30)->withQueryString(),
            'analysis' => [
                'total' => $total,
                'finished' => $finished,
                'average' => $average,
                'highest' => $highest,
                'lowest' => $lowest,
                'passed' => $passed,
                'pass_rate' => $total ? round(($passed / $total) * 100, 1) : 0,
            ],
            'filterOptions' => $this->examResultFilterOptions(),
        ]);
    }

    public function downloadExamResults(Request $request)
    {
        $items = $this->examResultsQuery($request)
            ->orderByDesc('ju.tanggal')
            ->orderBy('siswa.nama')
            ->get();

        $filename = 'hasil-ujian-' . now('Asia/Jakarta')->format('Ymd-His') . '.xls';

        return response()
            ->view('results.export', compact('items'))
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
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
        $activeAcademicYearId = $this->activeAcademicYearId();
        $activeExamPackageId = $this->activeExamPackageId($activeAcademicYearId);

        $items = $this->exam()->table('enrollment_ujian as e')
            ->join('siswa', 'siswa.id', '=', 'e.siswa_id')
            ->leftJoin('siswa_tahun_ajaran as sta', function ($join) use ($activeAcademicYearId) {
                $join->on('sta.siswa_id', '=', 'siswa.id');
                if ($activeAcademicYearId) {
                    $join->where('sta.tahun_ajaran_id', $activeAcademicYearId);
                }
            })
            ->leftJoin('kelas', 'kelas.id', '=', 'sta.kelas_id')
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
            ->when($activeAcademicYearId, fn($q) => $q->where('ju.tahun_ajaran_id', $activeAcademicYearId))
            ->when($activeExamPackageId, fn($q) => $q->where('ju.paket_ujian_id', $activeExamPackageId))
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

    public function guides(Request $request)
    {
        $guides = DB::table('guides')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        $selectedGuideId = (int) $request->query('guide', 0);

        $attachments = DB::table('guide_attachments')
            ->whereIn('guide_id', $guides->pluck('id'))
            ->orderBy('title')
            ->get()
            ->groupBy('guide_id');

        $allGuidesWithToc = $guides->map(function ($guide) use ($attachments) {
            $rendered = $this->renderGuideMarkdown((string) $guide->content_md);

            return (object) [
                ...get_object_vars($guide),
                'content_html' => $rendered['html'],
                'toc' => $rendered['toc'],
                'attachments' => $attachments->get($guide->id, collect())->map(function ($attachment) {
                    $attachment->url = $this->publicStorageUrl($attachment->file_path);
                    $attachment->size_label = $this->formatFileSize((int) $attachment->file_size);
                    return $attachment;
                }),
            ];
        });

        $studentGuides = $allGuidesWithToc->where('group', 'siswa')->values();
        $committeeGuides = $allGuidesWithToc->where('group', 'panitia')->values();

        if ($selectedGuideId === 0) {
            $selectedGuideId = ($studentGuides->first() ?? $committeeGuides->first())->id ?? 0;
        }

        $selectedGuide = $allGuidesWithToc->firstWhere('id', $selectedGuideId);

        return view('guides.index', [
            'studentGuides' => $studentGuides,
            'committeeGuides' => $committeeGuides,
            'selectedGuide' => $selectedGuide,
            'selectedGuideId' => $selectedGuideId,
            'meta' => [
                'updated_at' => $guides->max('updated_at'),
                'app_version' => null,
                'error' => null,
            ],
        ]);
    }

    public function getGuideAjax(int $guide)
    {
        $guideData = DB::table('guides')
            ->where('id', $guide)
            ->where('is_active', true)
            ->first();

        if (!$guideData) {
            return response()->json(['error' => 'Panduan tidak ditemukan'], 404);
        }

        $attachments = DB::table('guide_attachments')
            ->where('guide_id', $guide)
            ->orderBy('title')
            ->get()
            ->map(function ($attachment) {
                $attachment->url = $this->publicStorageUrl($attachment->file_path);
                $attachment->size_label = $this->formatFileSize((int) $attachment->file_size);
                return $attachment;
            });

        $rendered = $this->renderGuideMarkdown((string) $guideData->content_md);

        return response()->json([
            'id' => $guideData->id,
            'title' => $guideData->title,
            'group' => $guideData->group,
            'content_html' => $rendered['html'],
            'toc' => $rendered['toc'],
            'attachments' => $attachments,
        ]);
    }

    public function guideEditor(Request $request)
    {
        $guides = DB::table('guides')->orderBy('group')->orderBy('sort_order')->orderBy('title')->get();
        $selectedGuide = $guides->firstWhere('id', (int) $request->query('guide')) ?? $guides->first();
        $attachments = collect();

        if ($selectedGuide) {
            $attachments = DB::table('guide_attachments')
                ->where('guide_id', $selectedGuide->id)
                ->orderBy('title')
                ->get()
                ->map(function ($attachment) {
                    $attachment->url = $this->publicStorageUrl($attachment->file_path);
                    $attachment->markdown = '[' . $attachment->title . '](' . $attachment->url . ')';
                    $attachment->size_label = $this->formatFileSize((int) $attachment->file_size);

                    return $attachment;
                });
        }

        return view('settings.guides.index', [
            'guides' => $guides,
            'selectedGuide' => $selectedGuide,
            'attachments' => $attachments,
            'renderedPreview' => $selectedGuide ? $this->renderGuideMarkdown((string) $selectedGuide->content_md)['html'] : '',
            'suggestedContent' => $this->defaultGuideContent('Panduan Baru'),
        ]);
    }

    public function storeGuide(Request $request)
    {
        $data = $request->validate($this->guideRules());

        $id = DB::table('guides')->insertGetId([
            ...$data,
            'is_active' => $request->boolean('is_active'),
            'content_md' => ($data['content_md'] ?? '') ?: $this->defaultGuideContent($data['title']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('settings.guides.index', ['guide' => $id])->with('success', 'Panduan berhasil ditambahkan.');
    }

    public function updateGuide(Request $request, int $guide)
    {
        $data = $request->validate($this->guideRules($guide));

        DB::table('guides')->where('id', $guide)->update([
            ...$data,
            'is_active' => $request->boolean('is_active'),
            'updated_at' => now(),
        ]);

        return redirect()->route('settings.guides.index', ['guide' => $guide])->with('success', 'Panduan berhasil diperbarui.');
    }

    public function deleteGuide(int $guide)
    {
        $attachments = DB::table('guide_attachments')->where('guide_id', $guide)->get();
        foreach ($attachments as $attachment) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        DB::table('guides')->where('id', $guide)->delete();

        return redirect()->route('settings.guides.index')->with('success', 'Panduan berhasil dihapus.');
    }

    public function uploadGuideImage(Request $request)
    {
        $data = $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'alt' => ['nullable', 'string', 'max:120'],
        ]);

        $path = $data['image']->store('guides/images', 'public');
        $url = $this->publicStorageUrl($path);
        $alt = $data['alt'] ?: pathinfo($data['image']->getClientOriginalName(), PATHINFO_FILENAME);

        return back()->with('success', 'Gambar berhasil diupload.')->with('uploaded_image_markdown', '![' . $alt . '](' . $url . ')');
    }

    public function uploadGuideImageAjax(Request $request)
    {
        $data = $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'alt' => ['nullable', 'string', 'max:120'],
        ]);

        $path = $data['image']->store('guides/images', 'public');
        $url = $this->publicStorageUrl($path);
        $alt = $data['alt'] ?: pathinfo($data['image']->getClientOriginalName(), PATHINFO_FILENAME);
        $markdown = '![' . $alt . '](' . $url . ')';

        return response()->json([
            'success' => true,
            'markdown' => $markdown,
            'url' => $url,
            'alt' => $alt,
        ]);
    }

    public function previewGuideMarkdown(Request $request)
    {
        $data = $request->validate([
            'content_md' => ['nullable', 'string'],
        ]);

        return response()->json($this->renderGuideMarkdown((string) ($data['content_md'] ?? '')));
    }

    public function storeGuideAttachment(Request $request, int $guide)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'attachment' => ['required', 'file', 'mimes:doc,docx,xls,xlsx,pdf', 'max:10240'],
        ]);

        abort_unless(DB::table('guides')->where('id', $guide)->exists(), 404);

        $file = $data['attachment'];
        $path = $file->store('guides/attachments', 'public');

        DB::table('guide_attachments')->insert([
            'guide_id' => $guide,
            'title' => $data['title'],
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize() ?: 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('settings.guides.index', ['guide' => $guide])->with('success', 'Lampiran berhasil ditambahkan.');
    }

    public function deleteGuideAttachment(int $attachment)
    {
        $item = DB::table('guide_attachments')->where('id', $attachment)->firstOrFail();
        Storage::disk('public')->delete($item->file_path);
        DB::table('guide_attachments')->where('id', $attachment)->delete();

        return redirect()->route('settings.guides.index', ['guide' => $item->guide_id])->with('success', 'Lampiran berhasil dihapus.');
    }

    private function guideRules(?int $guideId = null): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:80', 'alpha_dash', Rule::unique('guides', 'slug')->ignore($guideId)],
            'group' => ['required', Rule::in(['siswa', 'panitia'])],
            'sort_order' => ['required', 'integer', 'min:0'],
            'content_md' => ['nullable', 'string'],
        ];
    }

    private function renderGuideMarkdown(string $markdown): array
    {
        $markdown = $this->normalizeGuideStorageUrls($markdown);
        $toc = $this->guideTableOfContents($markdown);
        $index = 0;
        $html = Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        $html = preg_replace_callback('/<h([23])>(.*?)<\/h\1>/s', function ($matches) use ($toc, &$index) {
            $item = $toc[$index] ?? null;
            $index++;

            if (!$item) {
                return $matches[0];
            }

            return '<h' . $matches[1] . ' id="' . e($item['id']) . '">' . $matches[2] . '</h' . $matches[1] . '>';
        }, $html);

        return ['html' => $html, 'toc' => $toc];
    }

    private function normalizeGuideStorageUrls(string $markdown): string
    {
        $publicUrl = rtrim((string) config('filesystems.disks.public.url'), '/');
        $appUrl = rtrim((string) config('app.url'), '/');

        foreach (array_filter([$publicUrl, $appUrl . '/storage', 'http://localhost/storage']) as $baseUrl) {
            $markdown = str_replace($baseUrl . '/', '/storage/', $markdown);
        }

        return $markdown;
    }

    private function publicStorageUrl(string $path): string
    {
        return '/storage/' . ltrim(str_replace('\\', '/', $path), '/');
    }

    private function guideTableOfContents(string $markdown): array
    {
        preg_match_all('/^(#{2,3})\s+(.+)$/m', $markdown, $matches, PREG_SET_ORDER);
        $used = [];

        return collect($matches)->map(function ($match) use (&$used) {
            $title = trim(preg_replace('/[`*_~\[\]\(\)#>]/', '', $match[2]));
            $base = Str::slug($title) ?: 'bagian';
            $slug = $base;
            $counter = 2;

            while (in_array($slug, $used, true)) {
                $slug = $base . '-' . $counter;
                $counter++;
            }

            $used[] = $slug;

            return [
                'id' => $slug,
                'title' => $title,
                'level' => strlen($match[1]),
            ];
        })->values()->all();
    }

    private function defaultGuideContent(string $title): string
    {
        return <<<MD
# {$title}

Tulis ringkasan singkat tentang tujuan panduan ini.

## 1. Unduh Template

Gunakan lampiran di bawah panduan ini, atau tautkan langsung:

[Download Template Import](/storage/guides/attachments/template.xlsx)

## 2. Isi Template

| Kolom | Wajib | Keterangan |
| --- | --- | --- |
| contoh | Ya | Isi keterangan di sini |

## 3. Import File

![Halaman Import](/storage/guides/images/import.png)

Langkah:
1. Buka menu import.
2. Pilih file.
3. Klik tombol import.
4. Periksa hasil validasi.

> Catatan: jangan mengubah nama kolom template.
MD;
    }

    private function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1, ',', '.') . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1, ',', '.') . ' KB';
        }

        return $bytes . ' B';
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
        $activeAcademicYearId = $this->activeAcademicYearId();

        return $this->exam()->table('siswa')
            ->leftJoin('siswa_tahun_ajaran as sta', function ($join) use ($activeAcademicYearId) {
                $join->on('sta.siswa_id', '=', 'siswa.id');

                if ($activeAcademicYearId) {
                    $join->where('sta.tahun_ajaran_id', $activeAcademicYearId);
                }
            })
            ->leftJoin('kelas', 'kelas.id', '=', 'sta.kelas_id')
            ->whereNull('siswa.deleted_at')
            ->select(array_merge([
                'siswa.id',
                'siswa.nis',
                'siswa.idyayasan',
                'siswa.nama',
                'siswa.email',
                'siswa.email_verified_at',
                'siswa.password',
                'siswa.remember_token',
                'siswa.created_at',
                'siswa.updated_at',
                'siswa.deleted_at',
                DB::raw('sta.status_pembayaran as status_pembayaran'),
                DB::raw("COALESCE(sta.rekomendasi, 'tidak') as rekomendasi"),
                DB::raw('sta.catatan as catatan_rekomendasi'),
                'kelas.nama_kelas',
                'kelas.tingkat',
                'kelas.jurusan',
                'sta.tahun_ajaran_id',
                'sta.kelas_id',
            ], $extraSelects));
    }

    private function studentExamInfo(int $studentId): array
    {
        $exam = $this->exam();
        $activeAcademicYearId = $this->activeAcademicYearId();
        $activeExamPackageId = $this->activeExamPackageId($activeAcademicYearId);

        $academicYear = $activeAcademicYearId
            ? $exam->table('tahun_ajaran')->where('id', $activeAcademicYearId)->first(['id', 'nama'])
            : null;
        $examPackage = $activeExamPackageId
            ? $exam->table('paket_ujian')->where('id', $activeExamPackageId)->first(['id', 'nama'])
            : null;

        $roomSessions = $exam->table('sesi_ruangan_siswa as srs')
            ->join('sesi_ruangan as sr', 'sr.id', '=', 'srs.sesi_ruangan_id')
            ->join('ruangan', 'ruangan.id', '=', 'sr.ruangan_id')
            ->select(
                'srs.status_kehadiran',
                'sr.id as sesi_ruangan_id',
                'sr.nama_sesi',
                'sr.waktu_mulai',
                'sr.waktu_selesai',
                'ruangan.nama_ruangan'
            )
            ->where('srs.siswa_id', $studentId)
            ->when($activeAcademicYearId, fn($query) => $query->where('sr.tahun_ajaran_id', $activeAcademicYearId))
            ->when($activeExamPackageId, function ($query) use ($activeAcademicYearId, $activeExamPackageId) {
                $query->whereExists(function ($exists) use ($activeAcademicYearId, $activeExamPackageId) {
                    $exists->select(DB::raw(1))
                        ->from('jadwal_ujian_sesi_ruangan as jsr')
                        ->join('jadwal_ujian as ju', 'ju.id', '=', 'jsr.jadwal_ujian_id')
                        ->whereColumn('jsr.sesi_ruangan_id', 'sr.id')
                        ->where('ju.paket_ujian_id', $activeExamPackageId)
                        ->when($activeAcademicYearId, fn($q) => $q->where('ju.tahun_ajaran_id', $activeAcademicYearId));
                });
            })
            ->orderBy('ruangan.nama_ruangan')
            ->orderBy('sr.waktu_mulai')
            ->get();

        $attendance = $exam->table('enrollment_ujian as e')
            ->join('jadwal_ujian as ju', 'ju.id', '=', 'e.jadwal_ujian_id')
            ->leftJoin('mapel', 'mapel.id', '=', 'ju.mapel_id')
            ->leftJoin('sesi_ruangan as sr', 'sr.id', '=', 'e.sesi_ruangan_id')
            ->leftJoin('ruangan', 'ruangan.id', '=', 'sr.ruangan_id')
            ->leftJoin('sesi_ruangan_siswa as srs', function ($join) use ($studentId) {
                $join->on('srs.sesi_ruangan_id', '=', 'e.sesi_ruangan_id')
                    ->where('srs.siswa_id', $studentId);
            })
            ->select(
                'ju.id as jadwal_ujian_id',
                'ju.tanggal',
                'ju.judul',
                'mapel.nama_mapel',
                'sr.nama_sesi',
                'ruangan.nama_ruangan',
                'srs.status_kehadiran',
                'e.status_enrollment',
                'e.waktu_mulai_ujian',
                'e.waktu_selesai_ujian'
            )
            ->where('e.siswa_id', $studentId)
            ->when($activeAcademicYearId, fn($query) => $query->where('ju.tahun_ajaran_id', $activeAcademicYearId))
            ->when($activeExamPackageId, fn($query) => $query->where('ju.paket_ujian_id', $activeExamPackageId))
            ->orderBy('ju.tanggal')
            ->orderBy('ju.judul')
            ->get();

        $results = $exam->table('hasil_ujian as h')
            ->join('jadwal_ujian as ju', 'ju.id', '=', 'h.jadwal_ujian_id')
            ->leftJoin('mapel', 'mapel.id', '=', 'ju.mapel_id')
            ->leftJoin('sesi_ruangan as sr', 'sr.id', '=', 'h.sesi_ruangan_id')
            ->leftJoin('ruangan', 'ruangan.id', '=', 'sr.ruangan_id')
            ->select(
                'ju.tanggal',
                'ju.judul',
                'mapel.nama_mapel',
                'sr.nama_sesi',
                'ruangan.nama_ruangan',
                'h.jumlah_soal',
                'h.jumlah_dijawab',
                'h.jumlah_benar',
                'h.jumlah_salah',
                'h.jumlah_tidak_dijawab',
                'h.skor',
                'h.nilai',
                'h.lulus',
                'h.status',
                'h.waktu_mulai',
                'h.waktu_selesai'
            )
            ->where('h.siswa_id', $studentId)
            ->when($activeAcademicYearId, fn($query) => $query->where('ju.tahun_ajaran_id', $activeAcademicYearId))
            ->when($activeExamPackageId, fn($query) => $query->where('ju.paket_ujian_id', $activeExamPackageId))
            ->orderByDesc('ju.tanggal')
            ->orderBy('ju.judul')
            ->get();

        return [
            'academicYear' => $academicYear,
            'examPackage' => $examPackage,
            'roomSessions' => $roomSessions,
            'attendance' => $attendance,
            'results' => $results,
        ];
    }

    private function roomFilterOptions(): array
    {
        $exam = $this->exam();
        $activeAcademicYearId = $this->activeAcademicYearId();

        return [
            'ruangan' => $exam->table('sesi_ruangan as sr')
                ->join('ruangan', 'ruangan.id', '=', 'sr.ruangan_id')
                ->where('sr.sumber', 'sumber')
                ->when($activeAcademicYearId, fn($q) => $q->where('sr.tahun_ajaran_id', $activeAcademicYearId))
                ->distinct()
                ->orderBy('ruangan.nama_ruangan')
                ->pluck('ruangan.nama_ruangan'),
        ];
    }

    private function attendanceFilterOptions(): array
    {
        $exam = $this->exam();
        $activeAcademicYearId = $this->activeAcademicYearId();
        $activeExamPackageId = $this->activeExamPackageId($activeAcademicYearId);

        return [
            'tingkat' => $exam->table('kelas')->when($activeAcademicYearId, fn($q) => $q->where('tahun_ajaran_id', $activeAcademicYearId))->whereNotNull('tingkat')->distinct()->orderBy('tingkat')->pluck('tingkat'),
            'kelas' => $exam->table('kelas')->when($activeAcademicYearId, fn($q) => $q->where('tahun_ajaran_id', $activeAcademicYearId))->distinct()->orderBy('nama_kelas')->pluck('nama_kelas'),
            'sesi' => $exam->table('sesi_ruangan')->when($activeAcademicYearId, fn($q) => $q->where('tahun_ajaran_id', $activeAcademicYearId))->distinct()->orderBy('nama_sesi')->pluck('nama_sesi'),
            'ruangan' => $exam->table('ruangan')->distinct()->orderBy('nama_ruangan')->pluck('nama_ruangan'),
            'status_kehadiran' => collect(['hadir', 'tidak_hadir', 'sakit', 'izin']),
            'tanggal_awal' => $exam->table('jadwal_ujian')->when($activeAcademicYearId, fn($q) => $q->where('tahun_ajaran_id', $activeAcademicYearId))->when($activeExamPackageId, fn($q) => $q->where('paket_ujian_id', $activeExamPackageId))->distinct()->orderByDesc('tanggal')->pluck('tanggal'),
            'tanggal_akhir' => $exam->table('jadwal_ujian')->when($activeAcademicYearId, fn($q) => $q->where('tahun_ajaran_id', $activeAcademicYearId))->when($activeExamPackageId, fn($q) => $q->where('paket_ujian_id', $activeExamPackageId))->distinct()->orderByDesc('tanggal')->pluck('tanggal'),
        ];
    }

    private function examResultsQuery(Request $request)
    {
        $activeAcademicYearId = $this->activeAcademicYearId();
        $activeExamPackageId = $this->activeExamPackageId($activeAcademicYearId);

        return $this->exam()->table('hasil_ujian as h')
            ->join('siswa', 'siswa.id', '=', 'h.siswa_id')
            ->leftJoin('siswa_tahun_ajaran as sta', function ($join) use ($activeAcademicYearId) {
                $join->on('sta.siswa_id', '=', 'siswa.id');
                if ($activeAcademicYearId) {
                    $join->where('sta.tahun_ajaran_id', $activeAcademicYearId);
                }
            })
            ->leftJoin('kelas', 'kelas.id', '=', 'sta.kelas_id')
            ->join('jadwal_ujian as ju', 'ju.id', '=', 'h.jadwal_ujian_id')
            ->leftJoin('mapel', 'mapel.id', '=', 'ju.mapel_id')
            ->leftJoin('tahun_ajaran', 'tahun_ajaran.id', '=', 'ju.tahun_ajaran_id')
            ->leftJoin('paket_ujian', 'paket_ujian.id', '=', 'ju.paket_ujian_id')
            ->select(
                'h.id',
                'siswa.idyayasan',
                'siswa.nama',
                'kelas.tingkat',
                'kelas.nama_kelas',
                'ju.id as ujian_id',
                'ju.judul',
                'ju.tanggal',
                'tahun_ajaran.nama as tahun_ajaran_nama',
                'paket_ujian.nama as paket_ujian_nama',
                'mapel.nama_mapel',
                'h.jumlah_soal',
                'h.jumlah_dijawab',
                'h.jumlah_benar',
                'h.jumlah_salah',
                'h.jumlah_tidak_dijawab',
                'h.skor',
                'h.nilai',
                'h.lulus',
                'h.status',
                'h.violations_count',
                'h.waktu_mulai',
                'h.waktu_selesai'
            )
            ->whereNull('siswa.deleted_at')
            ->when($request->filled('q'), fn($query) => $query->where(fn($inner) => $inner
                ->where('siswa.nama', 'like', '%' . $request->q . '%')
                ->orWhere('siswa.idyayasan', 'like', '%' . $request->q . '%')))
            ->when($request->filled('tingkat'), fn($query) => $query->where('kelas.tingkat', $request->tingkat))
            ->when($request->filled('kelas'), fn($query) => $query->where('kelas.nama_kelas', $request->kelas))
            ->when($request->filled('ujian'), fn($query) => $query->where('ju.id', $request->ujian))
            ->when($activeAcademicYearId, fn($query) => $query->where('ju.tahun_ajaran_id', $activeAcademicYearId))
            ->when($activeExamPackageId, fn($query) => $query->where('ju.paket_ujian_id', $activeExamPackageId));
    }

    private function examResultFilterOptions(): array
    {
        $exam = $this->exam();
        $activeAcademicYearId = $this->activeAcademicYearId();
        $activeExamPackageId = $this->activeExamPackageId($activeAcademicYearId);

        return [
            'tingkat' => $exam->table('kelas')->when($activeAcademicYearId, fn($q) => $q->where('tahun_ajaran_id', $activeAcademicYearId))->whereNotNull('tingkat')->distinct()->orderBy('tingkat')->pluck('tingkat'),
            'kelas' => $exam->table('kelas')->when($activeAcademicYearId, fn($q) => $q->where('tahun_ajaran_id', $activeAcademicYearId))->distinct()->orderBy('nama_kelas')->pluck('nama_kelas'),
            'ujian' => $exam->table('jadwal_ujian')->when($activeAcademicYearId, fn($q) => $q->where('tahun_ajaran_id', $activeAcademicYearId))->when($activeExamPackageId, fn($q) => $q->where('paket_ujian_id', $activeExamPackageId))->orderByDesc('tanggal')->orderBy('judul')->pluck('judul', 'id'),
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
        $billRows = collect($periods)->flatMap(fn($period) => $period['items'])->values()->all();

        $periodTotalRemaining = collect($periods)->sum('total_remaining');
        $periodTotalBill = collect($periods)->sum('total_billed');
        $periodTotalPaid = collect($periods)->sum('total_paid');

        $totalRemaining = $periods !== []
            ? $periodTotalRemaining
            : ($this->sumDebtByKeyDeep($summary, 'total_remaining') ?: abs($this->firstNumericByKeysDeep($summary, ['remaining', 'total_tunggakan', 'tunggakan']) ?? 0));

        $totalBill = $periodTotalBill > 0
            ? $periodTotalBill
            : ($this->firstNumericByKeysDeep($summary, ['total_paid', 'paid', 'total_tagihan', 'amount', 'total']) ?? 0);

        $totalPaid = $periodTotalPaid > 0
            ? $periodTotalPaid
            : ($this->firstNumericByKeysDeep($summary, ['total_billed', 'billed', 'total_bayar']) ?? max(0, $totalBill - $totalRemaining));

        return [
            'total_remaining' => (float) $totalRemaining,
            'total_bill' => (float) $totalBill,
            'total_paid' => (float) $totalPaid,
            'periods' => $periods,
            'unpaid_periods' => $this->unpaidPeriods($periods),
            'bills' => $billRows,
            'raw_summary' => $summary,
            'raw_payments' => $payments,
        ];
    }

    private function extractPeriodGroups(array $payload): array
    {
        $students = $payload['data'] ?? $payload;
        if (!is_array($students)) {
            return [];
        }

        if (!array_is_list($students)) {
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
                        $remaining = abs((float) ($item['remaining_balance'] ?? $item['total_remaining'] ?? 0));
                        $row = [
                            'name' => $categoryName,
                            'period' => (string) ($period['period_id'] ?? '-'),
                            'kelas_info' => $period['kelas_info'] ?? '-',
                            'unit' => $item['unit_name'] ?? $item['unit_id'] ?? '-',
                            'amount' => (float) ($item['amount_paid'] ?? 0),
                            'paid' => (float) ($item['amount_billed'] ?? 0),
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

                    $categoryRemaining = $this->summaryDebt($category['summary'] ?? [], 'total_remaining', collect($categoryItems)->sum('remaining'));
                    if ($categoryRemaining > 0 && collect($categoryItems)->sum('remaining') <= 0) {
                        $periodRows[] = [
                            'name' => $categoryName,
                            'period' => (string) ($period['period_id'] ?? '-'),
                            'kelas_info' => $period['kelas_info'] ?? '-',
                            'unit' => '-',
                            'amount' => (float) ($category['summary']['total_paid'] ?? 0),
                            'paid' => (float) ($category['summary']['total_billed'] ?? 0),
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
                        'total_remaining' => $categoryRemaining,
                        'items' => $categoryItems,
                        'raw' => $category,
                    ];
                }

                $periodRemaining = $this->summaryDebt($period['summary'] ?? [], 'total_remaining', collect($categoryGroups)->sum('total_remaining'));
                $periodBilled = (float) ($period['summary']['total_paid'] ?? collect($periodRows)->sum('amount'));
                $periodPaid = (float) ($period['summary']['total_billed'] ?? collect($periodRows)->sum('paid'));

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
            ->map(fn($rows, $period) => [
                'period_id' => (string) $period,
                'kelas_info' => '-',
                'summary' => [
                    'total_billed' => $rows->sum('amount'),
                    'total_paid' => $rows->sum('paid'),
                    'total_remaining' => $rows->sum('remaining'),
                ],
                'total_billed' => $rows->sum('amount'),
                'total_paid' => $rows->sum('paid'),
                'total_remaining' => $rows->sum('remaining'),
                'categories' => [],
                'items' => $rows->values()->all(),
                'raw' => [],
            ])
            ->values()
            ->all();
    }

    private function unpaidPeriods(array $periods): array
    {
        return collect($periods)
            ->filter(fn($period) => (float) ($period['total_remaining'] ?? 0) > 0)
            ->map(function ($period) {
                $period['categories'] = collect($period['categories'] ?? [])
                    ->filter(fn($category) => abs((float) ($category['summary']['total_remaining'] ?? collect($category['items'] ?? [])->sum('remaining'))) > 0)
                    ->map(function ($category) {
                        $category['items'] = collect($category['items'] ?? [])
                            ->filter(fn($item) => (float) ($item['remaining'] ?? 0) > 0)
                            ->values()
                            ->all();

                        return $category;
                    })
                    ->values()
                    ->all();

                $period['items'] = collect($period['items'] ?? [])
                    ->filter(fn($item) => (float) ($item['remaining'] ?? 0) > 0)
                    ->values()
                    ->all();

                return $period;
            })
            ->values()
            ->all();
    }

    private function paymentSummaryByLevel(): array
    {
        return $this->studentQuery()
            ->select('siswa.id', 'siswa.idyayasan', 'siswa.nama', DB::raw('sta.status_pembayaran as status_pembayaran'), 'kelas.tingkat', 'kelas.nama_kelas')
            ->orderBy('kelas.tingkat')
            ->orderBy('kelas.nama_kelas')
            ->orderBy('siswa.nama')
            ->get()
            ->groupBy(fn($student) => $student->tingkat ?: 'Tanpa Tingkat')
            ->map(function ($levelStudents, $level) {
                return [
                    'tingkat' => $level,
                    'total' => $levelStudents->count(),
                    'lunas' => $levelStudents->where('status_pembayaran', 'Lunas')->count(),
                    'belum' => $levelStudents->where('status_pembayaran', '!=', 'Lunas')->count(),
                    'classes' => $levelStudents
                        ->groupBy(fn($student) => $student->nama_kelas ?: 'Tanpa Kelas')
                        ->map(fn($classStudents, $className) => [
                            'kelas' => $className,
                            'total' => $classStudents->count(),
                            'lunas' => $classStudents->where('status_pembayaran', 'Lunas')->count(),
                            'belum' => $classStudents->where('status_pembayaran', '!=', 'Lunas')->count(),
                            'students' => $classStudents->values(),
                        ])
                        ->values(),
                ];
            })
            ->values()
            ->all();
    }

    private function extractBillRows(array $payload): array
    {
        $rows = [];
        $this->walkPayload($payload, function (array $item) use (&$rows) {
            $remaining = $this->firstNumericByKeys($item, ['total_remaining', 'remaining_balance', 'remaining', 'sisa', 'sisa_tagihan', 'nominal_sisa']);
            $amount = $this->firstNumericByKeys($item, ['total_paid', 'amount_paid', 'paid', 'total_bill', 'total_tagihan', 'bill', 'amount', 'nominal', 'tagihan']);
            $paid = $this->firstNumericByKeys($item, ['total_billed', 'amount_billed', 'billed', 'total_bayar']);

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
            ->filter(fn($row) => $row['remaining'] > 0 || $row['amount'] > 0)
            ->unique(fn($row) => $row['name'] . '|' . $row['period'] . '|' . $row['amount'] . '|' . $row['remaining'])
            ->values()
            ->all();
    }

    private function walkPayload(mixed $payload, callable $callback): void
    {
        if (!is_array($payload)) {
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

    private function firstNumericByKeysDeep(array $payload, array $keys): ?float
    {
        $found = null;
        $this->walkPayload($payload, function (array $item) use ($keys, &$found) {
            if ($found !== null) {
                return;
            }

            $found = $this->firstNumericByKeys($item, $keys);
        });

        return $found;
    }

    private function sumDebtByKeyDeep(array $payload, string $key): float
    {
        $sum = 0.0;
        $this->walkPayload($payload, function (array $item) use ($key, &$sum) {
            if (isset($item[$key]) && is_numeric($item[$key])) {
                $sum += abs((float) $item[$key]);
            }
        });

        return $sum;
    }

    private function summaryDebt(array $summary, string $key, float|int $fallback = 0): float
    {
        return array_key_exists($key, $summary) && is_numeric($summary[$key])
            ? abs((float) $summary[$key])
            : (float) $fallback;
    }

    private function firstStringByKeys(array $payload, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (!empty($payload[$key]) && is_scalar($payload[$key])) {
                return (string) $payload[$key];
            }
        }

        return null;
    }

    private function romanMonth(int $month): string
    {
        return [1 => 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][$month] ?? 'I';
    }

    private function formatLetterText(string $text): string
    {
        $escaped = e($text);
        $escaped = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $escaped);
        $escaped = preg_replace('/\*(.*?)\*/s', '<em>$1</em>', $escaped);
        $escaped = preg_replace('/==(.*?)==/s', '<mark>$1</mark>', $escaped);

        return nl2br($escaped);
    }
}
