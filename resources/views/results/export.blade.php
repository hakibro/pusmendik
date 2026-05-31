<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
</head>
<body>
<table border="1">
    <thead>
    <tr>
        <th>Nama</th>
        <th>ID Yayasan</th>
        <th>Tingkat</th>
        <th>Kelas</th>
        <th>Ujian</th>
        <th>Mapel</th>
        <th>Tanggal</th>
        <th>Jumlah Soal</th>
        <th>Dijawab</th>
        <th>Benar</th>
        <th>Salah</th>
        <th>Tidak Dijawab</th>
        <th>Skor</th>
        <th>Nilai</th>
        <th>Lulus</th>
        <th>Status</th>
        <th>Pelanggaran</th>
        <th>Waktu Mulai</th>
        <th>Waktu Selesai</th>
    </tr>
    </thead>
    <tbody>
    @foreach($items as $item)
        <tr>
            <td>{{ $item->nama }}</td>
            <td>{{ $item->idyayasan }}</td>
            <td>{{ $item->tingkat }}</td>
            <td>{{ $item->nama_kelas }}</td>
            <td>{{ $item->judul }}</td>
            <td>{{ $item->nama_mapel }}</td>
            <td>{{ $item->tanggal }}</td>
            <td>{{ $item->jumlah_soal }}</td>
            <td>{{ $item->jumlah_dijawab }}</td>
            <td>{{ $item->jumlah_benar }}</td>
            <td>{{ $item->jumlah_salah }}</td>
            <td>{{ $item->jumlah_tidak_dijawab }}</td>
            <td>{{ $item->skor }}</td>
            <td>{{ $item->nilai }}</td>
            <td>{{ (int) $item->lulus === 1 ? 'Ya' : 'Tidak' }}</td>
            <td>{{ $item->status }}</td>
            <td>{{ $item->violations_count }}</td>
            <td>{{ $item->waktu_mulai }}</td>
            <td>{{ $item->waktu_selesai }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>
