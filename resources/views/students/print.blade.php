<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Surat Rekomendasi {{ $student->nama }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #111827;
            margin: 30px 42px;
            font-size: 13px;
            line-height: 1.45;
        }

        .print-btn {
            position: fixed;
            top: 14px;
            right: 14px;
            border: 0;
            border-radius: 8px;
            background: #0f766e;
            color: white;
            padding: 10px 14px;
            font-weight: 700;
            cursor: pointer;
        }

        .kop {
            display: grid;
            grid-template-columns: 80px 1fr;
            gap: 14px;
            align-items: center;
            border-bottom: 3px solid #111827;
            padding-bottom: 10px;
        }

        .logo {
            width: 72px;
            height: 72px;
            object-fit: contain;
        }

        .kop h1 {
            margin: 0;
            font-size: 17px;
            line-height: 1.25;
            text-align: center;
            white-space: pre-line;
        }

        .kop p {
            margin: 4px 0 0;
            text-align: center;
            font-size: 11px;
        }

        .title {
            text-align: center;
            margin: 18px 0 10px;
        }

        .title h2 {
            font-size: 16px;
            text-decoration: underline;
            margin: 0;
        }

        .title div {
            margin-top: 4px;
        }

        .student {
            display: grid;
            grid-template-columns: 150px 1fr;
            column-gap: 10px;
            row-gap: 2px;
            margin: 14px 0;
        }

        p {
            text-align: justify;
            margin: 10px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #334155;
            padding: 6px 7px;
            vertical-align: top;
        }

        th {
            background: #f1f5f9;
            text-align: left;
        }

        .right {
            text-align: right;
        }

        .date {
            display: flex;
            justify-content: flex-end;
            margin-top: 18px;
        }

        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 70px;
            margin-top: 8px;
        }

        .signature {
            text-align: center;
            min-height: 115px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .name-line {
            border-top: 1px solid #111827;
            padding-top: 4px;
            min-height: 22px;
        }

        .summary-table th,
        .summary-table td {
            font-size: 14px;
        }

        .page-break {
            page-break-before: always;
            break-before: page;
        }

        mark {
            background: #fef08a;
            padding: 0 2px;
        }

        @media print {
            .print-btn {
                display: none;
            }

            body {
                margin: 22px 36px;
            }
        }
    </style>
</head>

<body>
    @php
        $nominalRekom = (float) ($handler->nominal_rekom ?? 0);
        $manualNominal = number_format($nominalRekom, 0, ',', '.');
        $printPeriods = $paymentView['unpaid_periods'] ?? [];
        $text2 = str_replace(
            ['{nominal_rekom}', '{total_tagihan}', '{total_tunggakan}', '{tanggal_batas}', '{batas_hari}'],
            [
                $manualNominal,
                number_format($paymentView['total_bill'], 0, ',', '.'),
                number_format($paymentView['total_remaining'], 0, ',', '.'),
                $letter['deadline_date']->translatedFormat('d F Y'),
                $letter['deadline_days'] . ' hari',
            ],
            $letter['text_2_html'],
        );
    @endphp
    <button class="print-btn" onclick="window.print()">Cetak</button>

    <div class="kop">
        <div>
            @if ($letter['logo'])
                <img class="logo" src="{{ $letter['logo'] }}" alt="Logo">
            @endif
        </div>
        <div>
            <h1>{{ $letter['line_1'] }}</h1>
            <p>{{ $letter['line_2'] }}</p>
        </div>
    </div>

    <div class="title">
        <h2>SURAT PERNYATAAN PEMBAYARAN ADMINISTRASI UJIAN</h2>
        <div>Nomor: {{ $letter['number'] }}</div>
    </div>

    <div class="student">
        <div>Nama Siswa</div>
        <div>: <strong>{{ $student->nama }}</strong></div>
        <div>ID Yayasan</div>
        <div>: {{ $student->idyayasan }}</div>
        <div>Kelas</div>
        <div>: {{ $student->nama_kelas }}</div>
        <div>Status Pembayaran</div>
        <div>: {{ $student->status_pembayaran }}</div>
        <div>Nominal Dibayar</div>
        <div>: Rp {{ $manualNominal }}</div>
    </div>

    <p>{!! $letter['text_1_html'] !!}</p>
    <p>{!! $text2 !!}</p>

    <table class="summary-table">
        <thead>
            <tr>
                <th>Uraian</th>
                <th class="right">Nominal</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Total Tagihan</td>
                <td class="right">Rp {{ number_format($paymentView['total_bill'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Total Dibayar</td>
                <td class="right">Rp {{ number_format($paymentView['total_paid'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Total Sisa Tunggakan</td>
                <td class="right">Rp {{ number_format($paymentView['total_remaining'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <th>Nominal Dibayar pada Surat Ini</th>
                <th class="right">Rp {{ $manualNominal }}</th>
            </tr>
        </tbody>
    </table>

    <p>{!! $letter['text_3_html'] !!}</p>

    <div class="date">{{ $letter['location'] }}, {{ now('Asia/Jakarta')->translatedFormat('d F Y') }}</div>
    <div class="signatures">
        <div class="signature">
            <div>Petugas yang menangani,</div>
            <div class="name-line"><strong>{{ $handler->handled_by_name ?? 'Petugas Data' }}</strong></div>
        </div>
        <div class="signature">
            <div>Wali Siswa,</div>
            <div class="name-line">&nbsp;</div>
        </div>
    </div>

    <div class="page-break"></div>
    <h2 style="text-align:center; margin:24px 0;">DETAIL TUNGGAKAN</h2>
    @forelse($printPeriods as $period)
        <table>
            <thead>
                <tr>
                    <th colspan="6">Periode {{ $period['period_id'] }} - {{ $period['kelas_info'] }}</th>
                </tr>
                <tr>
                    <th style="width:36px">No</th>
                    <th>Kategori</th>
                    <th>Unit</th>
                    <th class="right">Tagihan</th>
                    <th class="right">Dibayar</th>
                    <th class="right">Sisa</th>
                </tr>
            </thead>
            <tbody>
                @php($rowNumber = 1)
                @forelse(($period['categories'] ?? []) as $category)
                    @if (($category['items'] ?? []) === [])
                        <tr>
                            <td>{{ $rowNumber++ }}</td>
                            <td>{{ $category['category_name'] }}</td>
                            <td>-</td>
                            <td class="right">Rp
                                {{ number_format((float) ($category['summary']['total_paid'] ?? 0), 0, ',', '.') }}
                            </td>
                            <td class="right">Rp
                                {{ number_format((float) ($category['summary']['total_billed'] ?? 0), 0, ',', '.') }}
                            </td>
                            <td class="right">Rp
                                {{ number_format(abs((float) ($category['summary']['total_remaining'] ?? 0)), 0, ',', '.') }}
                            </td>
                        </tr>
                    @else
                        @foreach ($category['items'] as $bill)
                            <tr>
                                <td>{{ $rowNumber++ }}</td>
                                <td>{{ $category['category_name'] }}</td>
                                <td>{{ $bill['unit'] ?? '-' }}</td>
                                <td class="right">Rp {{ number_format($bill['amount'], 0, ',', '.') }}</td>
                                <td class="right">Rp {{ number_format($bill['paid'] ?? 0, 0, ',', '.') }}</td>
                                <td class="right">Rp {{ number_format($bill['remaining'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    @endif
                @empty
                    @forelse($period['items'] as $bill)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $bill['name'] }}</td>
                            <td>{{ $bill['unit'] ?? '-' }}</td>
                            <td class="right">Rp {{ number_format($bill['amount'], 0, ',', '.') }}</td>
                            <td class="right">Rp {{ number_format($bill['paid'] ?? 0, 0, ',', '.') }}</td>
                            <td class="right">Rp {{ number_format($bill['remaining'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center">Detail item tunggakan tidak tersedia.</td>
                        </tr>
                    @endforelse
                @endforelse
                <tr>
                    <th colspan="3" class="right">Subtotal Periode</th>
                    <th class="right">Rp {{ number_format($period['total_billed'], 0, ',', '.') }}</th>
                    <th class="right">Rp {{ number_format($period['total_paid'], 0, ',', '.') }}</th>
                    <th class="right">Rp {{ number_format($period['total_remaining'], 0, ',', '.') }}</th>
                </tr>
            </tbody>
        </table>
        @empty
            <table>
                <thead>
                    <tr>
                        <th>Keterangan</th>
                        <th class="right">Sisa Tunggakan</th>
                    </tr>
                </thead>
                <tbody>
                    @if ((float) ($paymentView['total_remaining'] ?? 0) > 0)
                        <tr>
                            <td>Total tunggakan berdasarkan total_remaining dari API</td>
                            <td class="right">Rp {{ number_format((float) $paymentView['total_remaining'], 0, ',', '.') }}
                            </td>
                        </tr>
                    @else
                        <tr>
                            <td colspan="2" style="text-align:center">Tidak ada pembayaran dengan total_remaining lebih
                                dari 0.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        @endforelse
    </body>

    </html>
