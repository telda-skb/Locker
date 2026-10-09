@extends('layouts.app')
@section('title', 'Surat Masuk')
@section('content')
@php
    use Illuminate\Support\Carbon;
    /*
    |--------------------------------------------------------------------------
    | USER
    |--------------------------------------------------------------------------
    */
    $user = auth()->user();
    $userRole = strtolower(
        trim(
            (string) (
                $user->role ??
                $user->jabatan ??
                ''
            )
        )
    );
    if ($userRole === 'staf') {
        $userRole = 'staff';
    }
    /*
    |--------------------------------------------------------------------------
    | HAK AKSES
    |--------------------------------------------------------------------------
    */
    $canManage = in_array(
        $userRole,
        [
            'admin',
            'pimpinan',
        ],
        true
    );
    /*
    |--------------------------------------------------------------------------
    | STATUS
    |--------------------------------------------------------------------------
    */
    $statusOptions = [
        'baru' => 'Baru',
        'diproses' => 'Diproses',
        'didisposisikan' => 'Didisposisikan',
        'selesai' => 'Selesai',
        'diarsipkan' => 'Diarsipkan',
    ];
    /*
    |--------------------------------------------------------------------------
    | SCORECARD
    |--------------------------------------------------------------------------
    |
    | Controller mengirim $statusCounts yang dihitung dari seluruh data
    | yang boleh dilihat user, bukan dari data pagination.
    |
    | Struktur:
    |
    | [
    |     'total' => 4,
    |     'baru' => 4,
    |     'diproses' => 0,
    |     'didisposisikan' => 0,
    |     'selesai' => 0,
    |     'diarsipkan' => 0,
    | ]
    |
    */
    $statusCounts = is_array($statusCounts ?? null)
        ? $statusCounts
        : [];
    $totalSuratMasuk = (int) (
        $statusCounts['total']
        ?? 0
    );
    $suratBaru = (int) (
        $statusCounts['baru']
        ?? 0
    );
    $suratDiproses = (int) (
        $statusCounts['diproses']
        ?? 0
    );
    $suratDidisposisikan = (int) (
        $statusCounts['didisposisikan']
        ?? 0
    );
    $suratSelesai = (int) (
        $statusCounts['selesai']
        ?? 0
    );
    $suratDiarsipkan = (int) (
        $statusCounts['diarsipkan']
        ?? 0
    );
    /*
    |--------------------------------------------------------------------------
    | FALLBACK
    |--------------------------------------------------------------------------
    |
    | Hanya digunakan apabila Controller belum mengirim $statusCounts.
    | Untuk data sedikit, halaman tetap bisa tampil.
    |
    */
    if (
        !isset($statusCounts['total']) &&
        isset($suratMasuks)
    ) {
        if (
            method_exists(
                $suratMasuks,
                'total'
            )
        ) {
            $totalSuratMasuk =
                (int) $suratMasuks->total();
        } else {
            $totalSuratMasuk =
                (int) $suratMasuks->count();
        }
        if (
            method_exists(
                $suratMasuks,
                'getCollection'
            )
        ) {
            $scorecardCollection =
                collect(
                    $suratMasuks->getCollection()
                );
        } else {
            $scorecardCollection =
                collect(
                    $suratMasuks
                );
        }
        $suratBaru =
            $scorecardCollection
                ->filter(
                    fn ($item) =>
                        strtolower(
                            trim(
                                (string) (
                                    $item->status ??
                                    ''
                                )
                            )
                        ) === 'baru'
                )
                ->count();
        $suratDidisposisikan =
            $scorecardCollection
                ->filter(
                    fn ($item) =>
                        strtolower(
                            trim(
                                (string) (
                                    $item->status ??
                                    ''
                                )
                            )
                        ) === 'didisposisikan'
                )
                ->count();
        $suratDiproses =
            $scorecardCollection
                ->filter(
                    fn ($item) =>
                        strtolower(
                            trim(
                                (string) (
                                    $item->status ??
                                    ''
                                )
                            )
                        ) === 'diproses'
                )
                ->count();
        $suratSelesai =
            $scorecardCollection
                ->filter(
                    fn ($item) =>
                        strtolower(
                            trim(
                                (string) (
                                    $item->status ??
                                    ''
                                )
                            )
                        ) === 'selesai'
                )
                ->count();
        $suratDiarsipkan =
            $scorecardCollection
                ->filter(
                    fn ($item) =>
                        strtolower(
                            trim(
                                (string) (
                                    $item->status ??
                                    ''
                                )
                            )
                        ) === 'diarsipkan'
                )
                ->count();
    }
    /*
    |--------------------------------------------------------------------------
    | KATEGORI TERPILIH
    |--------------------------------------------------------------------------
    */
    $rawKategori = request(
        'kategori_id',
        request(
            'kategori_surat_id',
            []
        )
    );
    if (
        is_scalar($rawKategori) &&
        trim((string) $rawKategori) !== ''
    ) {
        $rawKategori = [
            $rawKategori
        ];
    }
    if (!is_array($rawKategori)) {
        $rawKategori = [];
    }
    $selectedKategori =
        collect(
            $rawKategori
        )
        ->flatten()
        ->filter(
            fn ($id) =>
                is_scalar($id) &&
                is_numeric($id) &&
                (int) $id > 0
        )
        ->map(
            fn ($id) =>
                (string) (
                    (int) $id
                )
        )
        ->unique()
        ->values()
        ->all();
    /*
    |--------------------------------------------------------------------------
    | STATUS TERPILIH
    |--------------------------------------------------------------------------
    */
    $rawStatus =
        request(
            'status',
            []
        );
    if (
        is_scalar($rawStatus) &&
        trim((string) $rawStatus) !== ''
    ) {
        $rawStatus = [
            $rawStatus
        ];
    }
    if (!is_array($rawStatus)) {
        $rawStatus = [];
    }
    $selectedStatus =
        collect(
            $rawStatus
        )
        ->filter(
            fn ($status) =>
                is_scalar($status)
        )
        ->map(
            fn ($status) =>
                strtolower(
                    trim(
                        (string) $status
                    )
                )
        )
        ->filter(
            fn ($status) =>
                array_key_exists(
                    $status,
                    $statusOptions
                )
        )
        ->unique()
        ->values()
        ->all();
    /*
    |--------------------------------------------------------------------------
    | RENTANG TANGGAL
    |--------------------------------------------------------------------------
    */
    $dariTanggal =
        request(
            'dari_tanggal'
        );
    $sampaiTanggal =
        request(
            'sampai_tanggal'
        );
    $visibleDateRange = '';
    if (
        $dariTanggal &&
        $sampaiTanggal
    ) {
        try {
            $start =
                Carbon::createFromFormat(
                    'Y-m-d',
                    $dariTanggal
                );
            $end =
                Carbon::createFromFormat(
                    'Y-m-d',
                    $sampaiTanggal
                );
            $visibleDateRange =
                $start->format('d/m/Y') .
                ' - ' .
                $end->format('d/m/Y');
        } catch (\Throwable $e) {
            $visibleDateRange = '';
        }
    } elseif ($dariTanggal) {
        try {
            $visibleDateRange =
                Carbon::createFromFormat(
                    'Y-m-d',
                    $dariTanggal
                )->format('d/m/Y');
        } catch (\Throwable $e) {
            $visibleDateRange = '';
        }
    } elseif ($sampaiTanggal) {
        try {
            $visibleDateRange =
                Carbon::createFromFormat(
                    'Y-m-d',
                    $sampaiTanggal
                )->format('d/m/Y');
        } catch (\Throwable $e) {
            $visibleDateRange = '';
        }
    }
    /*
    |--------------------------------------------------------------------------
    | FILTER AKTIF
    |--------------------------------------------------------------------------
    */
    $hasFilters =
        request()->filled('search') ||
        !empty($selectedKategori) ||
        !empty($selectedStatus) ||
        request()->filled('dari_tanggal') ||
        request()->filled('sampai_tanggal');
    /*
    |--------------------------------------------------------------------------
    | DATA TABEL
    |--------------------------------------------------------------------------
    */
    $hasVisibleData =
        isset($suratMasuks) &&
        method_exists(
            $suratMasuks,
            'count'
        ) &&
        $suratMasuks->count() > 0;
    /*
    |--------------------------------------------------------------------------
    | EXPORT QUERY
    |--------------------------------------------------------------------------
    */
    $exportQuery =
        request()->query();
    /*
    |--------------------------------------------------------------------------
    | URUTAN DATA
    |--------------------------------------------------------------------------
    | desc = terbaru ke terlama
    | asc  = terlama ke terbaru
    */
    $sortOrder = strtolower(
        trim(
            (string) request()->input('sort', 'desc')
        )
    );
    if (!in_array($sortOrder, ['asc', 'desc'], true)) {
        $sortOrder = 'desc';
    }
    $sortLabel =
        $sortOrder === 'desc'
            ? 'Terbaru'
            : 'Terlama';
    $nextSortOrder =
        $sortOrder === 'desc'
            ? 'asc'
            : 'desc';
    $sortUrl = route(
        'surat-masuk.index',
        array_merge(
            request()->except(['sort', 'page']),
            ['sort' => $nextSortOrder]
        )
    );
    $sortTitle =
        $sortOrder === 'desc'
            ? 'Klik untuk menampilkan dari terlama ke terbaru'
            : 'Klik untuk menampilkan dari terbaru ke terlama';
    /*
    |--------------------------------------------------------------------------
    | RINGKASAN STATUS & KATEGORI
    |--------------------------------------------------------------------------
    */
    $chartData = [
        'Baru' => [
            'value' => $suratBaru,
            'color' => '#2563eb',
        ],
        'Diproses' => [
            'value' => $suratDiproses,
            'color' => '#d97706',
        ],
        'Didisposisikan' => [
            'value' => $suratDidisposisikan,
            'color' => '#7c3aed',
        ],
        'Selesai' => [
            'value' => $suratSelesai,
            'color' => '#059669',
        ],
        'Diarsipkan' => [
            'value' => $suratDiarsipkan,
            'color' => '#64748b',
        ],
    ];
    $chartTotal = array_sum(
        array_column($chartData, 'value')
    );
    $chartGradient = '#e2e8f0';
    if ($chartTotal > 0) {
        $parts = [];
        $cursor = 0;
        foreach ($chartData as $item) {
            if ($item['value'] <= 0) {
                continue;
            }
            $start = $cursor;
            $cursor += (
                $item['value'] /
                $chartTotal
            ) * 360;
            $parts[] =
                $item['color'] .
                ' ' .
                round($start, 2) .
                'deg ' .
                round($cursor, 2) .
                'deg';
        }
        $chartGradient =
            'conic-gradient(' .
            implode(', ', $parts) .
            ')';
    }
    $categorySummary = collect();
    /*
    |--------------------------------------------------------------------------
    | RINGKASAN KATEGORI
    |--------------------------------------------------------------------------
    |
    | Utamakan data kategori dari controller karena query tersebut mengikuti
    | hak akses user dan tidak terpengaruh pagination/filter tabel.
    |
    | Jika controller belum mengirim $categoryCounts, gunakan data pada
    | halaman aktif sebagai fallback agar Blade tetap kompatibel.
    |
    */
    if (isset($categoryCounts)) {
        $categorySummary =
            collect($categoryCounts)
                ->mapWithKeys(
                    function ($item) {
                        $name =
                            trim(
                                (string) (
                                    $item
                                        ->kategori
                                        ?->nama_kategori
                                    ?? 'Tanpa Kategori'
                                )
                            );
                        if ($name === '') {
                            $name = 'Tanpa Kategori';
                        }
                        return [
                            $name =>
                                (int) (
                                    $item->total
                                    ?? 0
                                ),
                        ];
                    }
                )
                ->filter(
                    fn ($jumlah) =>
                        (int) $jumlah > 0
                )
                ->sortDesc()
                ->take(5);
    }
    if (
        $categorySummary->isEmpty() &&
        isset($suratMasuks)
    ) {
        $categoryCollection =
            method_exists(
                $suratMasuks,
                'getCollection'
            )
                ? collect(
                    $suratMasuks
                        ->getCollection()
                )
                : collect(
                    $suratMasuks
                );
        $categorySummary =
            $categoryCollection
                ->map(
                    fn ($item) =>
                        trim(
                            (string) (
                                $item
                                    ->kategori
                                    ?->nama_kategori
                                ?? 'Tanpa Kategori'
                            )
                        )
                )
                ->map(
                    fn ($name) =>
                        $name !== ''
                            ? $name
                            : 'Tanpa Kategori'
                )
                ->countBy()
                ->sortDesc()
                ->take(5);
    }
@endphp
@push('styles')
<style>
/* ==========================================================================
   PAGE
   ========================================================================== */
.surat-page{
    min-width:0;
}
/* ==========================================================================
   HEADER
   ========================================================================== */
.surat-page-header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:24px;
    margin-bottom:16px;
}
.surat-page-header-left{
    display:flex;
    align-items:center;
    gap:14px;
    min-width:0;
}
.surat-page-icon{
    display:flex;
    align-items:center;
    justify-content:center;
    width:54px;
    height:54px;
    flex:0 0 54px;
    border-radius:16px;
    background:linear-gradient(135deg,#dbeafe,#eff6ff);
    color:#2563eb;
    box-shadow:inset 0 0 0 1px rgba(37,99,235,.05);
}
.surat-page-title{
    margin:0;
    color:#172554;
    font-size:28px;
    font-weight:800;
    line-height:1.1;
    letter-spacing:-.025em;
}
.surat-page-description{
    margin-top:4px;
    color:#64748b;
    font-size:13px;
    line-height:1.5;
}
.surat-header-actions{
    display:flex;
    align-items:center;
    justify-content:flex-end;
    gap:8px;
    flex-wrap:wrap;
}
.surat-export-button,
.surat-create-button{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:7px;
    min-height:40px;
    padding:0 13px;
    border-radius:11px;
    font-size:11px;
    font-weight:700;
    line-height:1;
    text-decoration:none;
    transition:
        background .15s ease,
        border-color .15s ease,
        color .15s ease,
        transform .15s ease,
        box-shadow .15s ease;
}
.surat-export-button{
    border:1px solid #dbe4f0;
    background:#fff;
    color:#475569;
    box-shadow:0 2px 8px rgba(15,23,42,.035);
}
.surat-export-button:hover{
    transform:translateY(-1px);
}
.surat-export-button.excel:hover{
    border-color:#a7f3d0;
    background:#ecfdf5;
    color:#047857;
}
.surat-export-button.pdf:hover{
    border-color:#fecdd3;
    background:#fff1f2;
    color:#be123c;
}
.surat-create-button{
    border:1px solid #2563eb;
    background:#2563eb;
    color:#fff;
    box-shadow:0 8px 20px rgba(37,99,235,.18);
}
.surat-create-button:hover{
    border-color:#1d4ed8;
    background:#1d4ed8;
    transform:translateY(-1px);
    box-shadow:0 12px 24px rgba(37,99,235,.23);
}
/* ==========================================================================
   COMPACT SUMMARY
   ========================================================================== */
.surat-summary{
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    margin-bottom:16px;
    overflow:hidden;
    border:1px solid #dbe4f0;
    border-radius:14px;
    background:#fff;
    box-shadow:0 2px 10px rgba(15,23,42,.035);
}
.surat-summary-item{
    position:relative;
    display:flex;
    align-items:center;
    gap:11px;
    min-width:0;
    padding:14px 16px;
}
.surat-summary-item + .surat-summary-item{
    border-left:1px solid #edf2f7;
}
.surat-summary-icon{
    display:flex;
    align-items:center;
    justify-content:center;
    width:34px;
    height:34px;
    flex:0 0 34px;
    border-radius:10px;
}
.surat-summary-icon.blue{background:#eff6ff;color:#2563eb;}
.surat-summary-icon.red{background:#fff1f2;color:#e11d48;}
.surat-summary-icon.purple{background:#f5f3ff;color:#7c3aed;}
.surat-summary-icon.green{background:#ecfdf5;color:#059669;}
.surat-summary-text{
    min-width:0;
}
.surat-summary-label{
    color:#94a3b8;
    font-size:10px;
    font-weight:700;
}
.surat-summary-value{
    margin-top:2px;
    color:#172033;
    font-size:20px;
    font-weight:800;
    line-height:1;
}
.surat-summary-meta{
    margin-top:3px;
    color:#cbd5e1;
    font-size:9px;
    white-space:nowrap;
}
/* ==========================================================================
   WORKSPACE
   ========================================================================== */
.surat-workspace{
    display:grid;
    grid-template-columns:minmax(0,1fr) 286px;
    gap:16px;
    align-items:start;
}
.surat-main-card{
    min-width:0;
    overflow:hidden;
    border:1px solid #dbe4f0;
    border-radius:16px;
    background:#fff;
    box-shadow:0 3px 14px rgba(15,23,42,.04);
}
.surat-main-card-header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:16px;
    padding:17px 20px 14px;
    border-bottom:1px solid #edf2f7;
}
.surat-main-card-title-wrap{
    display:flex;
    align-items:center;
    gap:11px;
    min-width:0;
}
.surat-main-card-icon{
    display:flex;
    align-items:center;
    justify-content:center;
    width:38px;
    height:38px;
    flex:0 0 38px;
    border-radius:11px;
    background:#eff6ff;
    color:#2563eb;
}
.surat-main-card-title{
    margin:0;
    color:#172033;
    font-size:14px;
    font-weight:800;
}
.surat-main-card-subtitle{
    margin-top:2px;
    color:#94a3b8;
    font-size:10px;
    line-height:1.4;
}
.surat-sort-badge{
    display:inline-flex;
    align-items:center;
    gap:6px;
    min-height:32px;
    padding:0 10px;
    border:1px solid #dbe4f0;
    border-radius:9px;
    background:#fff;
    color:#475569;
    font-size:10px;
    font-weight:700;
    text-decoration:none;
    white-space:nowrap;
    cursor:pointer;
    transition:background .15s ease,border-color .15s ease,color .15s ease,transform .15s ease,box-shadow .15s ease;
}
.surat-sort-badge:hover{
    border-color:#bfdbfe;
    background:#eff6ff;
    color:#2563eb;
    transform:translateY(-1px);
    box-shadow:0 5px 14px rgba(37,99,235,.10);
}
/* ==========================================================================
   FILTER
   ========================================================================== */
.surat-main-card .surat-filter-card{
    margin:0;
    padding:15px 20px 16px;
    border:0;
    border-bottom:1px solid #edf2f7;
    border-radius:0;
    box-shadow:none;
}
.surat-filter-main{
    display:grid;
    grid-template-columns:minmax(0,1fr) 250px 108px;
    gap:10px;
    align-items:center;
}
.surat-search-wrapper{
    position:relative;
    min-width:0;
}
.surat-search-icon{
    position:absolute;
    top:50%;
    left:13px;
    z-index:2;
    display:flex;
    align-items:center;
    justify-content:center;
    width:17px;
    height:17px;
    color:#64748b;
    transform:translateY(-50%);
    pointer-events:none;
}
.surat-search-input{
    width:100%;
    height:40px;
    padding:0 13px 0 40px;
    border:1px solid #d6e0ec;
    border-radius:10px;
    outline:none;
    background:#fff;
    color:#334155;
    font-size:11px;
    transition:border-color .15s ease,box-shadow .15s ease;
}
.surat-search-input::placeholder{
    color:#94a3b8;
}
.surat-search-input:hover,
.surat-date-input:hover{
    border-color:#b8c5d6;
}
.surat-search-input:focus,
.surat-date-input:focus{
    border-color:#3b82f6;
    box-shadow:0 0 0 3px rgba(59,130,246,.10);
}
.surat-filter-submit{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:6px;
    height:40px;
    min-width:96px;
    padding:0 14px;
    border:1px solid #d6e0ec;
    border-radius:10px;
    background:#fff;
    color:#475569;
    font-size:11px;
    font-weight:700;
    cursor:pointer;
    transition:
        background .15s ease,
        border-color .15s ease,
        color .15s ease;
}
.surat-filter-submit:hover{
    border-color:#94a3b8;
    background:#f8fafc;
    color:#1e293b;
}
.surat-filter-submit.has-filter{
    border-color:#bfdbfe;
    background:#eff6ff;
    color:#2563eb;
}
.surat-date-wrapper{
    position:relative;
}
.surat-date-input{
    width:100%;
    height:40px;
    padding:0 36px 0 39px;
    border:1px solid #d6e0ec;
    border-radius:10px;
    outline:none;
    background:#fff;
    color:#475569;
    font-size:11px;
    font-weight:600;
    cursor:pointer;
    transition:border-color .15s ease,box-shadow .15s ease;
}
.surat-date-left-icon{
    position:absolute;
    top:50%;
    left:13px;
    z-index:2;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#64748b;
    transform:translateY(-50%);
    pointer-events:none;
}
.surat-date-clear{
    position:absolute;
    top:50%;
    right:4px;
    display:none;
    align-items:center;
    justify-content:center;
    width:31px;
    height:31px;
    border:0;
    border-radius:8px;
    background:transparent;
    color:#94a3b8;
    cursor:pointer;
    transform:translateY(-50%);
}
.surat-date-clear:hover{
    background:#fff1f2;
    color:#e11d48;
}
.surat-filter-secondary{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:8px;
    margin-top:8px;
}
.surat-filter-trigger{
    min-height:44px;
}
.surat-filter-menu{
    border-radius:12px;
}
/* ==========================================================================
   WORKSPACE SIDEBAR
   ========================================================================== */
.surat-sidebar{
    display:flex;
    flex-direction:column;
    gap:12px;
    min-width:0;
}
.surat-side-card{
    overflow:hidden;
    border:1px solid #dbe4f0;
    border-radius:16px;
    background:#fff;
    box-shadow:0 3px 14px rgba(15,23,42,.04);
}
.surat-side-card-header{
    display:flex;
    align-items:center;
    gap:9px;
    padding:14px 15px;
    border-bottom:1px solid #edf2f7;
}
.surat-side-card-icon{
    display:flex;
    align-items:center;
    justify-content:center;
    width:30px;
    height:30px;
    flex:0 0 30px;
    border-radius:9px;
    background:#eff6ff;
    color:#2563eb;
}
.surat-side-card-title{
    color:#172033;
    font-size:11px;
    font-weight:800;
}
.surat-side-card-content{
    padding:15px;
}
.surat-donut{
    position:relative;
    display:flex;
    align-items:center;
    justify-content:center;
    width:142px;
    height:142px;
    margin:2px auto 14px;
    border-radius:999px;
    background:var(--donut);
}
.surat-donut::after{
    content:'';
    position:absolute;
    inset:23px;
    border-radius:999px;
    background:#fff;
    box-shadow:0 0 0 1px rgba(226,232,240,.8);
}
.surat-donut-center{
    position:relative;
    z-index:2;
    text-align:center;
}
.surat-donut-number{
    color:#172033;
    font-size:25px;
    font-weight:800;
    line-height:1;
}
.surat-donut-label{
    margin-top:4px;
    color:#94a3b8;
    font-size:8px;
    font-weight:800;
    letter-spacing:.06em;
    text-transform:uppercase;
}
.surat-legend{
    display:flex;
    flex-direction:column;
    gap:8px;
}
.surat-legend-row{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
}
.surat-legend-name{
    display:flex;
    align-items:center;
    gap:8px;
    min-width:0;
    color:#64748b;
    font-size:9px;
    font-weight:600;
}
.surat-legend-dot{
    width:8px;
    height:8px;
    flex:0 0 8px;
    border-radius:999px;
}
.surat-legend-value{
    color:#334155;
    font-size:10px;
    font-weight:800;
}
.surat-category-list{
    display:flex;
    flex-direction:column;
}
.surat-category-row{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    padding:9px 0;
    border-bottom:1px solid #f1f5f9;
}
.surat-category-row:last-child{
    border-bottom:0;
    padding-bottom:0;
}
.surat-category-row:first-child{
    padding-top:0;
}
.surat-category-name{
    min-width:0;
    overflow:hidden;
    color:#475569;
    font-size:9px;
    font-weight:600;
    text-overflow:ellipsis;
    white-space:nowrap;
}
.surat-category-count{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-width:23px;
    height:22px;
    padding:0 7px;
    border-radius:999px;
    background:#eff6ff;
    color:#2563eb;
    font-size:9px;
    font-weight:800;
}
.surat-side-empty{
    padding:8px 0 2px;
    color:#94a3b8;
    font-size:9px;
    line-height:1.5;
}
/* ==========================================================================
   TABLE
   ========================================================================== */
.archive-table-wrapper{
    overflow:hidden;
    border:0;
    border-radius:0;
    background:#fff;
    box-shadow:none;
}
.archive-table-scroll{
    overflow-x:auto;
}
.archive-table{
    width:100%;
    min-width:930px;
    border-collapse:collapse;
    border-spacing:0;
    background:#fff;
}
.archive-table thead{
    background:#f8fafc;
}
.archive-table thead tr{
    border-bottom:1px solid #e2e8f0;
}
.archive-table thead th{
    padding:12px 13px;
    color:#64748b;
    font-size:8.5px;
    font-weight:800;
    letter-spacing:.04em;
    line-height:1.3;
    text-align:left;
    text-transform:uppercase;
    white-space:nowrap;
}
.archive-table thead th:first-child{
    padding-left:20px;
}
.archive-table thead th:last-child{
    text-align:center;
}
.archive-table tbody tr{
    background:#fff;
    transition:background-color .15s ease;
}
.archive-table tbody tr:hover{
    background:#f8fbff;
}
.archive-table tbody td{
    padding:13px;
    border-bottom:1px solid #edf2f7;
    color:#475569;
    font-size:10px;
    line-height:1.4;
    vertical-align:middle;
}
.archive-table tbody td:first-child{
    padding-left:20px;
}
.archive-table tbody tr:last-child td{
    border-bottom:0;
}
.archive-table .cell-date{
    color:#334155;
    font-weight:700;
    white-space:nowrap;
}
.archive-table .cell-sender{
    color:#334155;
}
.archive-table .cell-subject{
    color:#1e293b;
    font-weight:700;
}
.archive-table .cell-category{
    color:#64748b;
}
.archive-table .sender-name{
    display:block;
    max-width:190px;
    overflow:hidden;
    color:#334155;
    font-weight:700;
    text-overflow:ellipsis;
    white-space:nowrap;
}
.archive-table .sender-email{
    display:block;
    max-width:190px;
    margin-top:2px;
    overflow:hidden;
    color:#94a3b8;
    font-size:8.5px;
    text-overflow:ellipsis;
    white-space:nowrap;
}
/* ==========================================================================
   BADGES
   ========================================================================== */
.category-badge{
    display:inline-flex;
    align-items:center;
    gap:5px;
    min-height:25px;
    padding:0 8px;
    border-radius:999px;
    background:#eff6ff;
    color:#2563eb;
    font-size:8.5px;
    font-weight:700;
    white-space:nowrap;
    border:1px solid transparent;
}
.category-badge.category-normal{
    background:#eff6ff;
    color:#2563eb;
    border-color:#dbeafe;
}
.category-badge.category-important{
    background:#fff7ed;
    color:#c2410c;
    border-color:#fed7aa;
}
.category-badge.category-secret{
    background:#f5f3ff;
    color:#7c3aed;
    border-color:#ddd6fe;
}
.category-badge.category-urgent{
    background:#fff1f2;
    color:#e11d48;
    border-color:#fecdd3;
}
.status-badge{
    display:inline-flex;
    align-items:center;
    gap:5px;
    min-height:25px;
    padding:0 8px;
    border-radius:999px;
    font-size:8.5px;
    font-weight:800;
    white-space:nowrap;
}
.status-dot{
    width:6px;
    height:6px;
    border-radius:999px;
    background:currentColor;
}
.status-baru{
    background:#eaf2ff;
    color:#2563eb;
}
.status-diproses{
    background:#fff4dc;
    color:#d97706;
}
.status-didisposisikan{
    background:#f0e9ff;
    color:#7c3aed;
}
.status-selesai{
    background:#dcf8ee;
    color:#059669;
}
.status-diarsipkan{
    background:#f1f5f9;
    color:#64748b;
}
/* ==========================================================================
   ACTIONS
   ========================================================================== */
.action-cell{
    width:118px;
    text-align:center!important;
    white-space:nowrap;
}
.action-buttons{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:4px;
}
.action-button{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    width:34px;
    height:34px;
    border:1px solid #e2e8f0;
    border-radius:9px;
    background:#fff;
    color:#64748b;
    transition:
        background .15s ease,
        color .15s ease,
        border-color .15s ease;
}
.action-button:hover{
    border-color:#bfdbfe;
    background:#eff6ff;
    color:#2563eb;
}
.action-button.edit:hover{
    border-color:#fde68a;
    background:#fffbeb;
    color:#d97706;
}
.action-button.delete:hover{
    border-color:#fecdd3;
    background:#fff1f2;
    color:#e11d48;
}
/* ==========================================================================
   EMPTY
   ========================================================================== */
.archive-table-empty{
    padding:48px 20px!important;
    text-align:center;
}
.archive-empty-icon{
    display:flex;
    align-items:center;
    justify-content:center;
    width:54px;
    height:54px;
    margin:0 auto 12px;
    border-radius:16px;
    background:#f1f5f9;
    color:#94a3b8;
}
/* ==========================================================================
   PAGINATION
   ========================================================================== */
.archive-pagination{
    padding:11px 20px 13px;
    border-top:1px solid #edf2f7;
}
/* ==========================================================================
   DATE PICKER
   ========================================================================== */
.custom-date-picker,
.custom-picker-panel{
    border:1px solid #cbd5e1;
    background:#fff;
    box-shadow:
        0 24px 70px rgba(15,23,42,.18),
        0 8px 25px rgba(15,23,42,.08);
}
.custom-date-picker{
    position:fixed;
    z-index:999999;
    width:720px;
    max-width:calc(100vw - 20px);
    overflow:hidden;
    border-radius:14px;
}
.custom-date-picker.hidden,
.custom-picker-panel.hidden{
    display:none!important;
}
.custom-date-picker-header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:11px 14px;
    border-bottom:1px solid #e2e8f0;
}
.custom-date-picker-title{
    color:#334155;
    font-size:12px;
    font-weight:800;
}
.custom-date-picker-close,
.custom-picker-panel-close{
    display:flex;
    align-items:center;
    justify-content:center;
    border:0;
    background:#f8fafc;
    color:#64748b;
    cursor:pointer;
}
.custom-date-picker-close{
    width:30px;
    height:30px;
    border-radius:8px;
    font-size:18px;
}
.custom-date-picker-close:hover,
.custom-picker-panel-close:hover{
    background:#f1f5f9;
    color:#ef4444;
}
.custom-date-picker-calendars{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
}
.custom-calendar{
    padding:13px 15px 11px;
}
.custom-calendar+.custom-calendar{
    border-left:1px solid #e2e8f0;
}
.custom-calendar-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:5px;
    min-height:38px;
    margin-bottom:5px;
}
.custom-calendar-month-buttons{
    display:flex;
    align-items:center;
    justify-content:center;
    flex:1;
    gap:4px;
}
.custom-calendar-nav{
    display:flex;
    align-items:center;
    justify-content:center;
    width:32px;
    height:32px;
    flex:0 0 32px;
    border:0;
    border-radius:8px;
    background:transparent;
    color:#64748b;
    font-size:21px;
    cursor:pointer;
}
.custom-calendar-nav:hover{
    background:#eff6ff;
    color:#2563eb;
}
.custom-calendar-month,
.custom-calendar-year{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:5px;
    min-height:32px;
    border:1px solid #dbe3ed;
    border-radius:8px;
    padding:6px 9px;
    background:#f8fafc;
    color:#334155;
    font-family:inherit;
    font-size:11px;
    font-weight:800;
    cursor:pointer;
}
.custom-calendar-month:hover,
.custom-calendar-year:hover{
    border-color:#93c5fd;
    background:#eff6ff;
    color:#2563eb;
}
.custom-calendar-month::after,
.custom-calendar-year::after{
    content:'';
    width:6px;
    height:6px;
    margin-top:-3px;
    border-right:1.5px solid currentColor;
    border-bottom:1.5px solid currentColor;
    transform:rotate(45deg);
}
.custom-calendar-weekdays,
.custom-calendar-days{
    display:grid;
    grid-template-columns:repeat(7,minmax(0,1fr));
    gap:2px;
}
.custom-calendar-weekdays{
    margin-bottom:3px;
}
.custom-calendar-weekday{
    display:flex;
    align-items:center;
    justify-content:center;
    height:26px;
    color:#94a3b8;
    font-size:9px;
    font-weight:800;
}
.custom-calendar-day{
    display:flex;
    align-items:center;
    justify-content:center;
    height:34px;
    border:0;
    border-radius:7px;
    background:transparent;
    color:#475569;
    font-family:inherit;
    font-size:10px;
    font-weight:600;
    cursor:pointer;
}
.custom-calendar-day:hover{
    background:#eff6ff;
    color:#2563eb;
}
.custom-calendar-day.other-month{
    color:#cbd5e1;
}
.custom-calendar-day.today{
    box-shadow:inset 0 0 0 1px #93c5fd;
    color:#2563eb;
}
.custom-calendar-day.in-range{
    border-radius:0;
    background:#eff6ff;
    color:#2563eb;
}
.custom-calendar-day.range-start{
    border-radius:999px 0 0 999px;
    background:#2563eb;
    color:#fff;
}
.custom-calendar-day.range-end{
    border-radius:0 999px 999px 0;
    background:#2563eb;
    color:#fff;
}
.custom-calendar-day.range-start.range-end{
    border-radius:999px;
}
.custom-date-picker-footer{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    padding:10px 12px;
    border-top:1px solid #e2e8f0;
}
.custom-date-picker-selected{
    min-width:0;
    color:#64748b;
    font-size:10px;
    font-weight:700;
}
.custom-date-picker-actions{
    display:flex;
    align-items:center;
    gap:6px;
}
.custom-date-picker-button{
    height:34px;
    border:1px solid #dbe3ed;
    border-radius:8px;
    padding:0 12px;
    background:#f8fafc;
    color:#475569;
    font-family:inherit;
    font-size:10px;
    font-weight:700;
    cursor:pointer;
}
.custom-date-picker-button:hover{
    background:#f1f5f9;
}
.custom-date-picker-button.apply{
    border-color:#2563eb;
    background:#2563eb;
    color:#fff;
}
.custom-date-picker-button.apply:hover{
    background:#1d4ed8;
}
.custom-picker-panel{
    position:fixed;
    z-index:1000000;
    width:310px;
    max-width:calc(100vw - 20px);
    padding:12px;
    border-radius:12px;
}
.custom-picker-panel-header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:8px;
    margin-bottom:10px;
    padding-bottom:9px;
    border-bottom:1px solid #e2e8f0;
}
.custom-picker-panel-title{
    flex:1;
    color:#334155;
    font-size:12px;
    font-weight:800;
    text-align:center;
}
.custom-picker-panel-close{
    width:28px;
    height:28px;
    border-radius:7px;
    font-size:17px;
}
.custom-picker-month-grid,
.custom-picker-year-grid{
    display:grid;
    gap:7px;
}
.custom-picker-month-grid{
    grid-template-columns:repeat(3,1fr);
}
.custom-picker-year-grid{
    grid-template-columns:repeat(4,1fr);
}
.custom-picker-option{
    display:flex;
    align-items:center;
    justify-content:center;
    min-height:40px;
    border:1px solid #dbe3ed;
    border-radius:9px;
    background:#fff;
    color:#475569;
    font-family:inherit;
    font-size:10px;
    font-weight:700;
    cursor:pointer;
}
.custom-picker-option:hover{
    border-color:#93c5fd;
    background:#eff6ff;
    color:#2563eb;
}
.custom-picker-option.active{
    border-color:#2563eb;
    background:#2563eb;
    color:#fff;
}
.custom-picker-option.current{
    box-shadow:inset 0 0 0 1px #93c5fd;
}
.custom-picker-option.active.current{
    box-shadow:none;
}
.custom-picker-year-navigation{
    display:flex;
    align-items:center;
    gap:4px;
}
.custom-picker-year-nav{
    display:flex;
    align-items:center;
    justify-content:center;
    width:29px;
    height:29px;
    border:0;
    border-radius:7px;
    background:#f8fafc;
    color:#64748b;
    cursor:pointer;
}
.custom-picker-year-nav:hover{
    background:#eff6ff;
    color:#2563eb;
}
/* ==========================================================================
   RESPONSIVE
   ========================================================================== */
@media(max-width:1100px){
    .surat-workspace{
        grid-template-columns:minmax(0,1fr);
    }
    .surat-sidebar{
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
    }
    .surat-filter-main{
        grid-template-columns:minmax(0,1fr) auto;
    }
    .surat-date-wrapper{
        grid-column:1/-1;
    }
    .surat-summary{
        grid-template-columns:repeat(2,minmax(0,1fr));
    }
    .surat-summary-item:nth-child(3){
        border-left:0;
        border-top:1px solid #edf2f7;
    }
    .surat-summary-item:nth-child(4){
        border-top:1px solid #edf2f7;
    }
}
@media(max-width:767px){
    .surat-page-header{
        align-items:flex-start;
        flex-direction:column;
        gap:12px;
    }
    .surat-page-header-left{
        width:100%;
    }
    .surat-header-actions{
        width:100%;
    }
    .surat-header-actions .surat-export-button,
    .surat-header-actions .surat-create-button{
        flex:1 1 0;
        min-width:0;
        padding:0 9px;
        font-size:10px;
    }
    .surat-page-title{
        font-size:24px;
    }
    .surat-page-description{
        font-size:11px;
    }
    .surat-summary{
        grid-template-columns:1fr 1fr;
    }
    .surat-summary-item{
        padding:12px;
    }
    .surat-summary-item + .surat-summary-item{
        border-left:1px solid #edf2f7;
    }
    .surat-summary-item:nth-child(odd){
        border-left:0;
    }
    .surat-summary-item:nth-child(n+3){
        border-top:1px solid #edf2f7;
    }
    .surat-summary-icon{
        width:31px;
        height:31px;
        flex-basis:31px;
    }
    .surat-summary-value{
        font-size:18px;
    }
    .surat-summary-meta{
        display:none;
    }
    .surat-main-card-header{
        padding:14px;
    }
    .surat-main-card-title{
        font-size:13px;
    }
    .surat-main-card-subtitle{
        font-size:9px;
    }
    .surat-sort-badge{
        display:inline-flex;
        min-height:32px;
        font-size:9px;
    }
    .surat-main-card .surat-filter-card{
        padding:12px;
    }
    .surat-filter-main{
        grid-template-columns:1fr;
    }
    .surat-filter-submit{
        width:100%;
    }
    .surat-filter-secondary{
        grid-template-columns:1fr;
    }
    .surat-filter-menu{
        position:fixed;
        top:50%;
        right:auto!important;
        left:50%;
        width:calc(100vw - 24px);
        min-width:0;
        max-width:430px;
        transform:translate(-50%,-50%);
    }
    .surat-filter-options{
        max-height:55vh;
    }
    .archive-table-scroll{
        overflow:visible;
    }
    .archive-table{
        min-width:0;
        width:100%;
    }
    .archive-table thead{
        display:none;
    }
    .archive-table,
    .archive-table tbody,
    .archive-table tr,
    .archive-table td{
        display:block;
        width:100%;
    }
    .archive-table tbody tr{
        margin:0;
        padding:10px 12px;
        border-bottom:1px solid #edf2f7;
        background:#fff;
    }
    .archive-table tbody tr:last-child{
        border-bottom:0;
    }
    .archive-table tbody td,
    .archive-table tbody td:first-child{
        display:grid;
        grid-template-columns:88px minmax(0,1fr);
        gap:10px;
        align-items:center;
        padding:6px 0;
        border-bottom:0;
        font-size:10px;
    }
    .archive-table tbody td::before{
        content:attr(data-label);
        color:#94a3b8;
        font-size:8px;
        font-weight:800;
        letter-spacing:.03em;
        text-transform:uppercase;
    }
    .archive-table tbody td.action-cell{
        display:flex;
        justify-content:space-between;
        align-items:center;
        width:100%;
        padding-top:8px;
        margin-top:4px;
        border-top:1px solid #f1f5f9;
    }
    .archive-table tbody td.action-cell::before{
        content:attr(data-label);
    }
    .action-buttons{
        margin-left:auto;
    }
    .archive-table .sender-name,
    .archive-table .sender-email{
        max-width:none;
    }
    .cell-subject{
        max-width:none!important;
        white-space:normal!important;
    }
    .surat-sidebar{
        display:flex;
        flex-direction:column;
    }
    .surat-side-card{
        border-radius:14px;
    }
    .surat-donut{
        width:130px;
        height:130px;
    }
    .custom-date-picker{
        top:50%;
        left:50%;
        width:calc(100vw - 16px);
        max-height:calc(100vh - 16px);
        overflow-y:auto;
        transform:translate(-50%,-50%);
    }
    .custom-date-picker-calendars{
        grid-template-columns:1fr;
    }
    .custom-calendar+.custom-calendar{
        border-top:1px solid #e2e8f0;
        border-left:0;
    }
    .custom-calendar-day{
        height:38px;
    }
    .custom-picker-panel{
        top:50%!important;
        left:50%!important;
        width:calc(100vw - 24px);
        transform:translate(-50%,-50%);
    }
    .custom-date-picker-footer{
        position:sticky;
        bottom:0;
        background:#fff;
    }
    body.date-picker-lock{
        overflow:hidden;
    }
}
@media(max-width:480px){
    .surat-page-icon{
        width:46px;
        height:46px;
        flex-basis:46px;
    }
    .surat-header-actions{
        display:grid;
        grid-template-columns:1fr 1fr;
    }
    .surat-summary{
        grid-template-columns:1fr;
    }
    .surat-summary-item,
    .surat-summary-item + .surat-summary-item{
        border-left:0;
    }
    .surat-summary-item + .surat-summary-item{
        border-top:1px solid #edf2f7;
    }
    .surat-main-card-title-wrap{
        align-items:flex-start;
    }
    .surat-main-card-icon{
        width:34px;
        height:34px;
        flex-basis:34px;
    }
    .surat-main-card-subtitle{
        display:none;
    }
}
/* ==========================================================================
   FINAL LAYOUT REFINEMENT
   ========================================================================== */
/* SCORECARD: lebih lega, terpisah, dan mudah dipindai */
.surat-summary{
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:12px;
    margin-bottom:18px;
    overflow:visible;
    border:0;
    border-radius:0;
    background:transparent;
    box-shadow:none;
}
.surat-summary-item{
    min-height:88px;
    padding:17px 18px;
    gap:13px;
    border:1px solid #dbe4f0;
    border-radius:14px;
    background:#fff;
    box-shadow:0 3px 14px rgba(15,23,42,.045);
}
.surat-summary-item + .surat-summary-item{
    border-left:1px solid #dbe4f0;
}
.surat-summary-icon{
    width:40px;
    height:40px;
    flex:0 0 40px;
    border-radius:11px;
}
.surat-summary-label{
    font-size:10px;
    letter-spacing:.01em;
}
.surat-summary-value{
    margin-top:4px;
    font-size:23px;
}
.surat-summary-meta{
    margin-top:4px;
    font-size:9px;
}
/* WORKSPACE: sidebar sedikit lebih lebar agar tabel mempunyai ruang yang cukup */
.surat-workspace{
    grid-template-columns:minmax(0,1fr) 300px;
    gap:18px;
}
/* SIDEBAR mengikuti tinggi alami dan dimulai sejajar dengan tabel */
.surat-sidebar{
    gap:14px;
    align-self:start;
}
.surat-side-card{
    border-radius:15px;
}
/* FILTER lebih lapang dan konsisten */
.surat-main-card .surat-filter-card{
    padding:17px 18px 18px;
}
.surat-filter-main{
    grid-template-columns:minmax(0,1fr) 104px 230px;
    gap:10px;
}
.surat-filter-secondary{
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:10px;
    margin-top:10px;
}
.surat-filter-trigger{
    min-height:46px;
    padding:8px 12px;
}
.surat-filter-menu{
    min-width:300px;
}
.surat-filter-options{
    gap:8px;
}
/* TABEL DESKTOP: paksa penuh mengikuti lebar kartu,
   tanpa scrollbar horizontal pada layar normal */
.archive-table-wrapper{
    width:100%;
    max-width:100%;
}
.archive-table-scroll{
    width:100%;
    max-width:100%;
    overflow-x:hidden;
}
.archive-table{
    width:100%;
    max-width:100%;
    min-width:0;
    table-layout:fixed;
}
.archive-table th,
.archive-table td{
    box-sizing:border-box;
}
.archive-table thead th{
    padding:12px 10px;
}
.archive-table tbody td{
    padding:13px 10px;
}
.archive-table thead th:first-child,
.archive-table tbody td:first-child{
    padding-left:16px;
}
.archive-table th:nth-child(1),
.archive-table td:nth-child(1){
    width:98px;
}
.archive-table th:nth-child(2),
.archive-table td:nth-child(2){
    width:176px;
}
.archive-table th:nth-child(3),
.archive-table td:nth-child(3){
    width:auto;
    min-width:0;
}
.archive-table th:nth-child(4),
.archive-table td:nth-child(4){
    width:132px;
}
.archive-table th:nth-child(5),
.archive-table td:nth-child(5){
    width:108px;
}
.archive-table th:nth-child(6),
.archive-table td:nth-child(6){
    width:126px;
}
.archive-table tbody td{
    overflow:hidden;
}
.archive-table .cell-subject,
.archive-table .cell-sender,
.archive-table .cell-category{
    min-width:0;
}
.archive-table .cell-subject{
    display:block;
    width:100%;
    max-width:100%;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
}
.archive-table .sender-name,
.archive-table .sender-email{
    max-width:100%;
}
.category-badge,
.status-badge{
    max-width:100%;
}
.category-badge{
    overflow:hidden;
    text-overflow:ellipsis;
}
.action-cell{
    width:126px;
}
.action-buttons{
    gap:5px;
    width:100%;
}
.action-button{
    width:32px;
    height:32px;
    flex:0 0 32px;
}
/* Beri ruang visual di antara header tabel dan isi */
.archive-table tbody tr{
    min-height:56px;
}
@media(max-width:1100px){
    .surat-summary{
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:12px;
    }
    .surat-summary-item + .surat-summary-item{
        border-left:1px solid #dbe4f0;
    }
    .surat-workspace{
        grid-template-columns:minmax(0,1fr);
        gap:14px;
    }
    .surat-sidebar{
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:14px;
    }
    .surat-filter-main{
        grid-template-columns:minmax(0,1fr) 104px;
    }
    .surat-date-wrapper{
        grid-column:1/-1;
    }
    .archive-table-scroll{
        overflow-x:auto;
    }
}
@media(max-width:767px){
    .surat-summary{
        grid-template-columns:1fr 1fr;
        gap:10px;
        margin-bottom:14px;
    }
    .surat-summary-item{
        min-height:82px;
        padding:14px;
        gap:10px;
    }
    .surat-summary-icon{
        width:34px;
        height:34px;
        flex-basis:34px;
    }
    .surat-summary-value{
        font-size:20px;
    }
    .surat-filter-main{
        grid-template-columns:1fr;
        gap:9px;
    }
    .surat-filter-secondary{
        grid-template-columns:1fr;
        gap:9px;
    }
    .surat-filter-submit{
        width:100%;
    }
    .surat-sidebar{
        display:flex;
        flex-direction:column;
        gap:12px;
    }
    .archive-table-scroll{
        overflow:visible;
    }
    .archive-table{
        min-width:0;
        width:100%;
        table-layout:auto;
    }
    .archive-table th:nth-child(1),
    .archive-table td:nth-child(1),
    .archive-table th:nth-child(2),
    .archive-table td:nth-child(2),
    .archive-table th:nth-child(3),
    .archive-table td:nth-child(3),
    .archive-table th:nth-child(4),
    .archive-table td:nth-child(4),
    .archive-table th:nth-child(5),
    .archive-table td:nth-child(5),
    .archive-table th:nth-child(6),
    .archive-table td:nth-child(6){
        width:auto;
    }
    .action-cell{
        width:100%;
    }
    .action-button{
        width:34px;
        height:34px;
        flex-basis:34px;
    }
}
@media(max-width:480px){
    .surat-summary{
        grid-template-columns:1fr;
    }
}
/* ==========================================================================
   POLISHED UI REFINEMENT
   ========================================================================== */
/* PAGE FLOW */
.surat-page{
    padding-bottom:20px;
}
/* SCORECARD */
.surat-summary{
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:14px;
    margin:2px 0 20px;
}
.surat-summary-item{
    position:relative;
    min-height:104px;
    padding:18px 18px 17px;
    gap:14px;
    overflow:hidden;
    border:1px solid #e2e8f0;
    border-radius:17px;
    background:#fff;
    box-shadow:0 8px 24px rgba(15,23,42,.055);
    transition:
        transform .18s ease,
        box-shadow .18s ease,
        border-color .18s ease;
}
.surat-summary-item::before{
    content:'';
    position:absolute;
    inset:0 auto 0 0;
    width:4px;
    border-radius:17px 0 0 17px;
}
.surat-summary-item:nth-child(1)::before{background:#2563eb;}
.surat-summary-item:nth-child(2)::before{background:#e11d48;}
.surat-summary-item:nth-child(3)::before{background:#7c3aed;}
.surat-summary-item:nth-child(4)::before{background:#059669;}
.surat-summary-item:hover{
    transform:translateY(-2px);
    border-color:#cbd5e1;
    box-shadow:0 12px 28px rgba(15,23,42,.08);
}
.surat-summary-item + .surat-summary-item{
    border-left:1px solid #e2e8f0;
}
.surat-summary-icon{
    width:44px;
    height:44px;
    flex:0 0 44px;
    border-radius:13px;
    box-shadow:inset 0 0 0 1px rgba(255,255,255,.7);
}
.surat-summary-label{
    color:#64748b;
    font-size:10px;
    font-weight:800;
    letter-spacing:.015em;
}
.surat-summary-value{
    margin-top:5px;
    color:#0f172a;
    font-size:25px;
    font-weight:850;
    letter-spacing:-.025em;
    line-height:1;
}
.surat-summary-meta{
    margin-top:5px;
    color:#94a3b8;
    font-size:9.5px;
    font-weight:600;
}
/* WORKSPACE */
.surat-workspace{
    grid-template-columns:minmax(0,1fr) 308px;
    gap:20px;
}
.surat-main-card,
.surat-side-card{
    border-color:#e2e8f0;
    border-radius:18px;
    box-shadow:0 8px 26px rgba(15,23,42,.045);
}
/* MAIN CARD HEADER */
.surat-main-card-header{
    padding:18px 20px 16px;
    background:linear-gradient(180deg,#ffffff 0%,#fbfdff 100%);
}
.surat-main-card-icon{
    width:42px;
    height:42px;
    flex-basis:42px;
    border-radius:12px;
    background:#eff6ff;
    color:#2563eb;
}
.surat-main-card-title{
    font-size:15px;
    letter-spacing:-.01em;
}
.surat-main-card-subtitle{
    margin-top:4px;
    font-size:10px;
    color:#94a3b8;
}
.surat-sort-badge{
    min-height:34px;
    padding:0 11px;
    border-radius:10px;
    background:#f8fafc;
    color:#475569;
    font-size:10px;
}
/* FILTER PANEL */
.surat-main-card .surat-filter-card{
    margin:0;
    padding:16px 18px 18px;
    border-bottom:1px solid #eef2f7;
    background:#f8fafc;
}
.surat-filter-main{
    grid-template-columns:minmax(0,1fr) 104px 236px;
    gap:10px;
}
.surat-search-wrapper,
.surat-date-wrapper,
.surat-filter-dropdown{
    position:relative;
    min-width:0;
}
.surat-filter-dropdown{
    position:relative;
}
.surat-search-input,
.surat-date-input{
    height:44px;
    border-color:#d8e1eb;
    border-radius:12px;
    background:#fff;
    font-size:11px;
    box-shadow:0 1px 2px rgba(15,23,42,.02);
}
.surat-search-input{
    padding-left:42px;
}
.surat-search-input:hover,
.surat-date-input:hover{
    border-color:#c4d0dc;
}
.surat-search-input:focus,
.surat-date-input:focus{
    border-color:#60a5fa;
    box-shadow:0 0 0 4px rgba(59,130,246,.10);
}
.surat-filter-submit{
    height:44px;
    min-width:104px;
    border:1px solid #2563eb;
    border-radius:12px;
    background:#2563eb;
    color:#fff;
    box-shadow:0 6px 14px rgba(37,99,235,.16);
}
.surat-filter-submit:hover{
    border-color:#1d4ed8;
    background:#1d4ed8;
    color:#fff;
    box-shadow:0 8px 18px rgba(37,99,235,.20);
}
.surat-filter-submit.has-filter{
    border-color:#2563eb;
    background:#2563eb;
    color:#fff;
}
.surat-filter-secondary{
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:10px;
    margin-top:10px;
}
.surat-filter-trigger{
    display:flex;
    align-items:center;
    min-height:50px;
    width:100%;
    padding:8px 12px;
    border:1px solid #d8e1eb;
    border-radius:12px;
    background:#fff;
    box-shadow:0 1px 2px rgba(15,23,42,.02);
    cursor:pointer;
    transition:
        border-color .16s ease,
        box-shadow .16s ease,
        background .16s ease;
}
.surat-filter-trigger:hover,
.surat-filter-trigger[aria-expanded="true"]{
    border-color:#93c5fd;
    background:#fff;
    box-shadow:0 0 0 3px rgba(59,130,246,.07);
}
.surat-filter-icon{
    display:flex;
    align-items:center;
    justify-content:center;
    width:32px;
    height:32px;
    flex:0 0 32px;
    margin-right:10px;
    border-radius:9px;
    background:#eff6ff;
    color:#2563eb;
}
.surat-filter-icon.status{
    background:#f5f3ff;
    color:#7c3aed;
}
.surat-filter-trigger-content{
    min-width:0;
    text-align:left;
}
.surat-filter-trigger-title{
    display:block;
    color:#1e293b;
    font-size:10.5px;
    font-weight:800;
    line-height:1.2;
}
.surat-filter-trigger-subtitle{
    display:block;
    margin-top:3px;
    overflow:hidden;
    color:#94a3b8;
    font-size:8.5px;
    font-weight:600;
    line-height:1.2;
    text-overflow:ellipsis;
    white-space:nowrap;
}
.surat-filter-count{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-width:52px;
    height:24px;
    margin-left:auto;
    padding:0 8px;
    border-radius:999px;
    background:#eff6ff;
    color:#2563eb;
    font-size:8.5px;
    font-weight:800;
    white-space:nowrap;
}
.surat-filter-count.status{
    background:#f5f3ff;
    color:#7c3aed;
}
.surat-filter-chevron{
    display:flex;
    align-items:center;
    justify-content:center;
    width:22px;
    height:22px;
    margin-left:6px;
    color:#94a3b8;
    transition:transform .16s ease,color .16s ease;
}
.surat-filter-trigger[aria-expanded="true"] .surat-filter-chevron{
    color:#2563eb;
    transform:rotate(180deg);
}
.surat-filter-trigger.is-active{
    border-color:#93c5fd;
    background:#f8fbff;
}
.surat-filter-menu{
    position:absolute;
    top:calc(100% + 8px);
    left:0;
    z-index:100;
    width:min(360px, calc(100vw - 32px));
    min-width:300px;
    max-width:calc(100vw - 32px);
    overflow:hidden;
    border:1px solid #dbe4ee;
    border-radius:14px;
    background:#fff;
    box-shadow:0 18px 44px rgba(15,23,42,.14),0 4px 12px rgba(15,23,42,.06);
}
.surat-filter-dropdown[data-filter-dropdown="status"] .surat-filter-menu{
    right:0;
    left:auto;
}
.surat-filter-menu-header{
    padding:13px 14px;
    background:#f8fafc;
    border-bottom:1px solid #e8eef5;
}
.surat-filter-menu-title{
    color:#1e293b;
    font-size:11px;
    font-weight:800;
}
.surat-filter-menu-description{
    margin-top:3px;
    color:#94a3b8;
    font-size:8.5px;
}
.surat-filter-options{
    gap:7px;
    max-height:260px;
    padding:10px;
}
.surat-filter-option{
    display:flex;
    align-items:center;
    gap:9px;
    min-height:40px;
    padding:7px 9px;
    border:1px solid #e2e8f0;
    border-radius:10px;
    background:#fff;
    color:#475569;
    cursor:pointer;
    user-select:none;
    transition:
        border-color .15s ease,
        background .15s ease,
        color .15s ease;
}
.surat-filter-option input[type="checkbox"]{
    width:16px;
    height:16px;
    flex:0 0 16px;
    margin:0;
    accent-color:#2563eb;
    cursor:pointer;
}
.surat-filter-option-text{
    min-width:0;
    overflow:hidden;
    color:#475569;
    font-size:10px;
    font-weight:650;
    line-height:1.35;
    text-overflow:ellipsis;
    white-space:nowrap;
}
.surat-filter-option:hover,
.surat-filter-option.is-selected{
    border-color:#bfdbfe;
    background:#f8fbff;
}
.surat-filter-option.is-selected .surat-filter-option-text{
    color:#1d4ed8;
    font-weight:750;
}
.surat-filter-menu-footer{
    padding:9px 12px;
    border-top:1px solid #e8eef5;
    background:#fff;
}
/* SIDEBAR */
.surat-sidebar{
    gap:14px;
}
.surat-side-card-header{
    padding:15px 16px;
    background:linear-gradient(180deg,#ffffff 0%,#fbfdff 100%);
}
.surat-side-card-icon{
    width:32px;
    height:32px;
    flex-basis:32px;
    border-radius:10px;
}
.surat-side-card-title{
    font-size:11.5px;
}
.surat-side-card-content{
    padding:16px;
}
/* TABLE */
.archive-table-wrapper{
    background:#fff;
}
.archive-table{
    table-layout:fixed;
}
.archive-table thead{
    background:#f8fafc;
}
.archive-table thead tr{
    border-bottom:1px solid #e6edf5;
}
.archive-table thead th{
    padding:12px 11px;
    color:#64748b;
    font-size:8.5px;
    letter-spacing:.055em;
}
.archive-table tbody tr{
    transition:
        background-color .14s ease,
        box-shadow .14s ease;
}
.archive-table tbody tr:hover{
    background:#fbfdff;
}
.archive-table tbody td{
    padding:15px 11px;
    border-bottom:1px solid #edf2f7;
    color:#475569;
    font-size:10px;
}
.archive-table .cell-date{
    color:#334155;
    font-size:9.5px;
    font-weight:800;
}
.archive-table .sender-name,
.archive-table .cell-subject{
    color:#1e293b;
    font-weight:700;
}
.archive-table .sender-email{
    color:#94a3b8;
}
.category-badge,
.status-badge{
    min-height:27px;
    padding:0 9px;
    font-size:8.5px;
    border:1px solid transparent;
}
.category-badge{
    background:#eff6ff;
    color:#2563eb;
}
.status-badge{
    background:#eff6ff;
    color:#2563eb;
}
.action-cell{
    width:126px;
}
.action-buttons{
    gap:6px;
}
.action-button{
    width:33px;
    height:33px;
    flex-basis:33px;
    border-color:#dfe7f0;
    border-radius:10px;
    background:#fff;
}
.action-button:hover{
    border-color:#bfdbfe;
    background:#eff6ff;
    color:#2563eb;
}
/* PAGINATION */
.archive-pagination{
    padding:12px 18px 14px;
    background:#fff;
}
/* RESPONSIVE */
@media(max-width:1200px){
    .surat-workspace{
        grid-template-columns:minmax(0,1fr) 286px;
        gap:16px;
    }
    .surat-summary{
        gap:12px;
    }
}
@media(max-width:1100px){
    .surat-summary{
        grid-template-columns:repeat(2,minmax(0,1fr));
    }
    .surat-workspace{
        grid-template-columns:1fr;
    }
    .surat-sidebar{
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:14px;
    }
    .surat-filter-main{
        grid-template-columns:minmax(0,1fr) 104px;
    }
    .surat-date-wrapper{
        grid-column:1/-1;
    }
}
@media(max-width:767px){
    .surat-summary{
        grid-template-columns:1fr 1fr;
        gap:10px;
        margin-bottom:14px;
    }
    .surat-summary-item{
        min-height:88px;
        padding:14px;
        gap:10px;
        border-radius:14px;
    }
    .surat-summary-item::before{
        width:3px;
    }
    .surat-summary-icon{
        width:36px;
        height:36px;
        flex-basis:36px;
    }
    .surat-summary-value{
        font-size:21px;
    }
    .surat-filter-main,
    .surat-filter-secondary{
        grid-template-columns:1fr;
    }
    .surat-filter-submit{
        width:100%;
    }
    .surat-sidebar{
        display:flex;
        flex-direction:column;
    }
}
@media(max-width:480px){
    .surat-summary{
        grid-template-columns:1fr;
    }
    .surat-summary-item,
    .surat-summary-item + .surat-summary-item{
        border-left:1px solid #e2e8f0;
    }
    .surat-main-card-header{
        padding:15px 14px 14px;
    }
    .surat-main-card .surat-filter-card{
        padding:12px;
    }
    .surat-filter-trigger{
        min-height:48px;
    }
}
/* ==========================================================================
   FILTER DROPDOWN - FINAL LAYOUT
   ========================================================================== */
.surat-filter-main{
    display:grid!important;
    grid-template-columns:minmax(0,1fr) 250px 108px!important;
    gap:10px!important;
    align-items:center!important;
}
.surat-filter-secondary{
    display:grid!important;
    grid-template-columns:repeat(2,minmax(0,1fr))!important;
    gap:10px!important;
    margin-top:10px!important;
}
.surat-filter-dropdown{
    position:relative!important;
    min-width:0!important;
}
.surat-filter-trigger{
    display:flex!important;
    align-items:center!important;
    width:100%!important;
    min-height:48px!important;
    padding:7px 11px!important;
    border:1px solid #d8e1eb!important;
    border-radius:11px!important;
    background:#fff!important;
    color:#334155!important;
    box-shadow:0 1px 2px rgba(15,23,42,.025)!important;
    cursor:pointer!important;
}
.surat-filter-trigger:hover,
.surat-filter-trigger[aria-expanded="true"],
.surat-filter-trigger.is-active{
    border-color:#93c5fd!important;
    background:#f8fbff!important;
    box-shadow:0 0 0 3px rgba(59,130,246,.07)!important;
}
.surat-filter-icon{
    display:flex!important;
    align-items:center!important;
    justify-content:center!important;
    width:31px!important;
    height:31px!important;
    flex:0 0 31px!important;
    margin-right:9px!important;
    border-radius:9px!important;
    background:#eff6ff!important;
    color:#2563eb!important;
}
.surat-filter-icon.status{
    background:#f5f3ff!important;
    color:#7c3aed!important;
}
.surat-filter-trigger-content{
    display:flex!important;
    flex:1 1 auto!important;
    flex-direction:column!important;
    align-items:flex-start!important;
    min-width:0!important;
    text-align:left!important;
}
.surat-filter-trigger-label{
    color:#334155!important;
    font-size:10px!important;
    font-weight:800!important;
    line-height:1.2!important;
}
.surat-filter-trigger-description{
    display:block!important;
    max-width:100%!important;
    margin-top:3px!important;
    overflow:hidden!important;
    color:#94a3b8!important;
    font-size:9px!important;
    line-height:1.25!important;
    text-overflow:ellipsis!important;
    white-space:nowrap!important;
}
.surat-filter-count{
    display:inline-flex!important;
    align-items:center!important;
    justify-content:center!important;
    min-width:24px!important;
    height:22px!important;
    margin-left:8px!important;
    padding:0 7px!important;
    border-radius:999px!important;
    background:#eff6ff!important;
    color:#2563eb!important;
    font-size:9px!important;
    font-weight:800!important;
    white-space:nowrap!important;
}
.surat-filter-chevron{
    display:flex!important;
    align-items:center!important;
    justify-content:center!important;
    margin-left:7px!important;
    color:#94a3b8!important;
    transition:transform .16s ease,color .16s ease!important;
}
.surat-filter-trigger[aria-expanded="true"] .surat-filter-chevron{
    color:#2563eb!important;
    transform:rotate(180deg)!important;
}
.surat-filter-menu{
    position:absolute!important;
    top:calc(100% + 8px)!important;
    left:0!important;
    right:auto!important;
    z-index:120!important;
    width:360px!important;
    max-width:min(360px,calc(100vw - 32px))!important;
    overflow:hidden!important;
    border:1px solid #dbe4ee!important;
    border-radius:14px!important;
    background:#fff!important;
    box-shadow:0 20px 45px rgba(15,23,42,.15),0 5px 14px rgba(15,23,42,.06)!important;
}
.surat-filter-dropdown[data-filter-dropdown="status"] .surat-filter-menu{
    right:0!important;
    left:auto!important;
}
.surat-filter-menu-header{
    display:flex!important;
    align-items:flex-start!important;
    justify-content:space-between!important;
    gap:12px!important;
    padding:13px 14px 11px!important;
    border-bottom:1px solid #eef2f7!important;
}
.surat-filter-menu-title{
    color:#172033!important;
    font-size:11px!important;
    font-weight:800!important;
}
.surat-filter-menu-description{
    margin-top:3px!important;
    color:#94a3b8!important;
    font-size:9px!important;
    line-height:1.35!important;
}
.surat-filter-menu-actions{
    display:flex!important;
    align-items:center!important;
    gap:5px!important;
    flex:0 0 auto!important;
}
.surat-filter-action{
    min-height:28px!important;
    padding:0 8px!important;
    border:1px solid #e2e8f0!important;
    border-radius:7px!important;
    background:#fff!important;
    color:#64748b!important;
    font-size:9px!important;
    font-weight:750!important;
    cursor:pointer!important;
}
.surat-filter-options{
    display:grid!important;
    grid-template-columns:1fr!important;
    gap:6px!important;
    max-height:280px!important;
    padding:10px!important;
    overflow-y:auto!important;
}
.surat-filter-option{
    display:flex!important;
    align-items:center!important;
    gap:9px!important;
    min-height:38px!important;
    padding:7px 9px!important;
    border:1px solid #e2e8f0!important;
    border-radius:9px!important;
    background:#fff!important;
    cursor:pointer!important;
}
.surat-filter-option:hover,
.surat-filter-option.is-selected{
    border-color:#bfdbfe!important;
    background:#f8fbff!important;
}
.surat-filter-option input[type="checkbox"]{
    width:16px!important;
    height:16px!important;
    flex:0 0 16px!important;
    margin:0!important;
    accent-color:#2563eb!important;
    cursor:pointer!important;
}
.surat-filter-option-text{
    min-width:0!important;
    overflow:hidden!important;
    color:#475569!important;
    font-size:10px!important;
    font-weight:650!important;
    line-height:1.35!important;
    text-overflow:ellipsis!important;
    white-space:nowrap!important;
}
.surat-filter-menu-footer{
    display:flex!important;
    align-items:center!important;
    justify-content:space-between!important;
    gap:10px!important;
    min-height:39px!important;
    padding:8px 12px!important;
    border-top:1px solid #eef2f7!important;
    background:#f8fafc!important;
}
@media (max-width: 900px){
    .surat-filter-main{
        grid-template-columns:minmax(0,1fr) 220px 100px!important;
    }
}
@media (max-width: 720px){
    .surat-filter-main{
        grid-template-columns:1fr!important;
    }
    .surat-filter-secondary{
        grid-template-columns:1fr!important;
    }
    .surat-filter-menu,
    .surat-filter-dropdown[data-filter-dropdown="status"] .surat-filter-menu{
        position:fixed!important;
        top:50%!important;
        right:auto!important;
        left:50%!important;
        width:calc(100vw - 24px)!important;
        min-width:0!important;
        max-width:430px!important;
        transform:translate(-50%,-50%)!important;
    }
}
@media (max-width: 480px){
    .surat-main-card .surat-filter-card{
        padding:12px!important;
    }
    .surat-filter-menu-header{
        padding:12px!important;
    }
    .surat-filter-options{
        max-height:calc(100vh - 190px)!important;
    }
}
/* ==========================================================================
   FILTER DROPDOWN - COMPACT FINAL
   ========================================================================== */
/* Tombol Filter dibuat compact */
.surat-filter-submit{
    min-width:82px!important;
    width:82px!important;
    height:38px!important;
    padding:0 10px!important;
    border-radius:9px!important;
    font-size:10px!important;
    box-shadow:0 4px 10px rgba(37,99,235,.12)!important;
}
.surat-filter-submit svg{
    width:14px!important;
    height:14px!important;
}
/* Menu dropdown tidak memanjang ke bawah */
.surat-filter-menu{
    width:420px!important;
    max-width:min(420px,calc(100vw - 32px))!important;
}
/* Kategori dan status otomatis 3 kolom.
   Jika jumlah item:
   6 => 3 + 3
   5 => 3 + 2
   4 => 3 + 1
   3 => 3
   2 => 2
   1 => 1
*/
.surat-filter-options{
    display:grid!important;
    grid-template-columns:repeat(3,minmax(0,1fr))!important;
    gap:7px!important;
    max-height:none!important;
    overflow-y:visible!important;
    align-items:stretch!important;
}
.surat-filter-option{
    min-width:0!important;
    min-height:40px!important;
    width:100%!important;
    padding:7px 8px!important;
}
.surat-filter-option-text{
    overflow:hidden!important;
    text-overflow:ellipsis!important;
    white-space:nowrap!important;
}
/* Jika hanya sedikit pilihan, jangan dipaksa melebar secara visual */
.surat-filter-options:has(.surat-filter-option:nth-child(1):last-child){
    grid-template-columns:1fr!important;
}
.surat-filter-options:has(.surat-filter-option:nth-child(2):last-child){
    grid-template-columns:repeat(2,minmax(0,1fr))!important;
}
.surat-filter-options:has(.surat-filter-option:nth-child(3):last-child){
    grid-template-columns:repeat(3,minmax(0,1fr))!important;
}
/* Dropdown tidak terlalu tinggi walaupun kategori/status bertambah banyak.
   Setelah 9 item, tetap 3 kolom dan area pilihan melakukan scroll. */
.surat-filter-options:has(.surat-filter-option:nth-child(10)){
    max-height:230px!important;
    overflow-y:auto!important;
}
/* Scrollbar kecil */
.surat-filter-options::-webkit-scrollbar{
    width:5px;
}
.surat-filter-options::-webkit-scrollbar-track{
    background:#f8fafc;
}
.surat-filter-options::-webkit-scrollbar-thumb{
    border-radius:999px;
    background:#cbd5e1;
}
@media (max-width:900px){
    .surat-filter-submit{
        width:82px!important;
        min-width:82px!important;
    }
    .surat-filter-menu{
        width:390px!important;
        max-width:calc(100vw - 24px)!important;
    }
}
@media (max-width:720px){
    .surat-filter-submit{
        width:100%!important;
        min-width:0!important;
    }
    .surat-filter-options{
        grid-template-columns:repeat(2,minmax(0,1fr))!important;
        max-height:280px!important;
        overflow-y:auto!important;
    }
    .surat-filter-options:has(.surat-filter-option:nth-child(1):last-child){
        grid-template-columns:1fr!important;
    }
    .surat-filter-options:has(.surat-filter-option:nth-child(2):last-child){
        grid-template-columns:repeat(2,minmax(0,1fr))!important;
    }
}
/* FINAL UI OVERRIDE */
.surat-page{min-width:0;padding-bottom:24px}
.surat-page-header{gap:18px;margin-bottom:16px}
.surat-page-title{font-size:27px}
.surat-page-description{font-size:12px}
.surat-header-actions{gap:7px}
.surat-export-button,.surat-create-button{min-height:39px;padding:0 11px;border-radius:10px;font-size:10px}
.surat-summary{grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin:0 0 18px}
.surat-summary-item{min-height:92px;padding:15px 16px;gap:12px;border-radius:15px}
.surat-summary-icon{width:41px;height:41px;flex-basis:41px}
.surat-summary-value{font-size:23px}
.surat-workspace{grid-template-columns:minmax(0,1fr) 300px;gap:17px}
.surat-main-card,.surat-side-card{border-radius:16px;box-shadow:0 7px 22px rgba(15,23,42,.045)}
.surat-main-card-header{padding:16px 18px 14px}
.surat-main-card-icon{width:40px;height:40px;flex-basis:40px}
.surat-main-card-title{font-size:14px}
.surat-main-card-subtitle{font-size:9.5px}
.surat-sort-badge{min-height:32px;padding:0 9px;font-size:9.5px}
.surat-main-card .surat-filter-card{padding:14px 17px 16px;background:linear-gradient(180deg,#fbfdff 0%,#f8fafc 100%)}
/* Search + date + compact Filter button. Date remains the widest field. */
.surat-filter-main{grid-template-columns:minmax(0,1fr) minmax(300px,1.15fr) 80px!important;gap:7px!important;align-items:center}
.surat-search-input,.surat-date-input{height:41px;border-radius:10px;font-size:10.5px}
.surat-filter-submit{width:80px!important;min-width:80px!important;height:37px!important;padding:0 9px!important;border-radius:9px!important;font-size:10px!important}
.surat-date-wrapper{min-width:0}
.surat-date-wrapper>.relative{width:100%}
/* Category + status stay side by side and their menu options use 3 columns: 3+3 / 3+2 / etc. */
.surat-filter-secondary{grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:8px!important;margin-top:8px!important}
.surat-filter-trigger{min-height:46px!important;padding:6px 10px!important;border-radius:10px!important}
.surat-filter-icon{width:29px!important;height:29px!important;flex-basis:29px!important;margin-right:8px!important}
.surat-filter-trigger-title{font-size:9.5px!important}
.surat-filter-trigger-subtitle{font-size:8px!important}
.surat-filter-count{min-width:23px!important;height:21px!important;font-size:8.5px!important}
.surat-filter-menu{width:420px!important;max-width:min(420px,calc(100vw - 28px))!important;min-width:290px!important}
.surat-filter-menu-header{padding:10px 11px!important}
.surat-filter-menu-title{font-size:10.5px!important}
.surat-filter-menu-description{font-size:8.5px!important}
.surat-filter-menu-actions{gap:4px!important}
.surat-filter-action{height:26px!important;padding:0 7px!important;font-size:8px!important}
.surat-filter-options{display:grid!important;grid-template-columns:repeat(3,minmax(0,1fr))!important;gap:6px!important;padding:8px!important;max-height:230px!important;overflow-y:auto!important}
.surat-filter-option{min-width:0!important;min-height:37px!important;padding:6px 7px!important;border-radius:8px!important}
.surat-filter-option-text{font-size:9px!important}
.surat-filter-options:has(.surat-filter-option:nth-child(1):last-child){grid-template-columns:1fr!important}
.surat-filter-options:has(.surat-filter-option:nth-child(2):last-child){grid-template-columns:repeat(2,minmax(0,1fr))!important}
.surat-filter-options:has(.surat-filter-option:nth-child(3):last-child){grid-template-columns:repeat(3,minmax(0,1fr))!important}
.surat-filter-options::-webkit-scrollbar{width:5px}
.surat-filter-options::-webkit-scrollbar-thumb{border-radius:999px;background:#cbd5e1}
.surat-side-card-header{padding:13px 14px}
.surat-side-card-content{padding:14px}
.surat-donut{width:136px;height:136px}
.archive-table-scroll{width:100%;overflow-x:auto}
.archive-table{width:100%;min-width:790px;table-layout:fixed}
.archive-table thead th{padding:11px 10px;font-size:8.5px}
.archive-table tbody td{padding:12px 10px;font-size:10px}
.archive-table .cell-subject{display:block;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.action-cell{width:118px}
.action-button{width:32px;height:32px;flex-basis:32px}
@media(max-width:1200px){
    .surat-workspace{grid-template-columns:minmax(0,1fr) 280px}
    .surat-filter-main{grid-template-columns:minmax(0,1fr) minmax(270px,1fr) 80px!important}
}
@media(max-width:1050px){
    .surat-summary{grid-template-columns:repeat(2,minmax(0,1fr))}
    .surat-workspace{grid-template-columns:1fr}
    .surat-sidebar{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
    .surat-filter-main{grid-template-columns:minmax(0,1fr) 80px!important}
    .surat-date-wrapper{grid-column:1/-1}
}
@media(max-width:767px){
    .surat-page-header{align-items:flex-start;flex-direction:column;gap:11px}
    .surat-page-header-left{width:100%}
    .surat-header-actions{width:100%;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:6px}
    .surat-header-actions .surat-export-button,.surat-header-actions .surat-create-button{width:100%;min-width:0;padding:0 7px;font-size:9px}
    .surat-page-title{font-size:23px}
    .surat-page-description{font-size:10px}
    .surat-summary{grid-template-columns:repeat(2,minmax(0,1fr));gap:9px;margin-bottom:13px}
    .surat-summary-item{min-height:80px;padding:12px;border-radius:13px}
    .surat-summary-icon{width:34px;height:34px;flex-basis:34px}
    .surat-summary-value{font-size:20px}
    .surat-summary-meta{display:none}
    .surat-main-card-header{padding:13px}
    .surat-main-card .surat-filter-card{padding:11px}
    .surat-filter-main{grid-template-columns:minmax(0,1fr) 76px!important;gap:7px!important}
    .surat-filter-submit{width:76px!important;min-width:76px!important}
    .surat-date-wrapper{grid-column:1/-1}
    .surat-filter-secondary{grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:7px!important}
    .surat-filter-trigger{min-height:44px!important;padding:6px 8px!important}
    .surat-filter-count{min-width:21px!important;height:20px!important;font-size:8px!important}
    .surat-filter-menu,.surat-filter-dropdown[data-filter-dropdown="status"] .surat-filter-menu{position:fixed!important;top:50%!important;right:auto!important;left:50%!important;width:calc(100vw - 20px)!important;min-width:0!important;max-width:430px!important;transform:translate(-50%,-50%)!important}
    .surat-filter-options{grid-template-columns:repeat(2,minmax(0,1fr))!important;max-height:55vh!important}
    .surat-sidebar{display:flex;flex-direction:column;gap:11px}
    .archive-table-scroll{overflow:visible}
    .archive-table{min-width:0;width:100%;table-layout:auto}
    .archive-table thead{display:none}
    .archive-table,.archive-table tbody,.archive-table tr,.archive-table td{display:block;width:100%}
    .archive-table tbody tr{padding:9px 11px;border-bottom:1px solid #edf2f7}
    .archive-table tbody td,.archive-table tbody td:first-child{display:grid;grid-template-columns:80px minmax(0,1fr);gap:9px;align-items:center;padding:6px 0;border:0;font-size:10px}
    .archive-table tbody td::before{content:attr(data-label);color:#94a3b8;font-size:8px;font-weight:800;letter-spacing:.03em;text-transform:uppercase}
    .archive-table tbody td.action-cell{display:flex;align-items:center;justify-content:space-between;width:100%;padding-top:8px;margin-top:4px;border-top:1px solid #f1f5f9}
    .archive-table tbody td.action-cell::before{content:attr(data-label)}
    .action-buttons{margin-left:auto}
    .archive-table .sender-name,.archive-table .sender-email{max-width:none}
    .archive-table .cell-subject{max-width:none!important;white-space:normal!important}
    .action-cell{width:100%}
    .surat-donut{width:126px;height:126px}
    .custom-date-picker{top:50%;left:50%;width:calc(100vw - 14px);max-height:calc(100vh - 14px);overflow-y:auto;transform:translate(-50%,-50%)}
    .custom-date-picker-calendars{grid-template-columns:1fr}
    .custom-calendar+.custom-calendar{border-top:1px solid #e2e8f0;border-left:0}
    .custom-calendar-day{height:37px}
    .custom-picker-panel{top:50%!important;left:50%!important;width:calc(100vw - 20px);transform:translate(-50%,-50%)}
    .custom-date-picker-footer{position:sticky;bottom:0}
}
@media(max-width:540px){
    .surat-header-actions{grid-template-columns:1fr 1fr}
    .surat-header-actions .surat-create-button{grid-column:1/-1}
    .surat-summary{grid-template-columns:1fr}
    .surat-filter-secondary{grid-template-columns:1fr!important}
    .surat-filter-options{grid-template-columns:repeat(2,minmax(0,1fr))!important}
    .surat-filter-menu-footer{flex-direction:column;align-items:flex-start}
    .custom-date-picker-footer{align-items:stretch;flex-direction:column}
    .custom-date-picker-actions{justify-content:flex-end}
}
@media(max-width:390px){
    .surat-filter-main{grid-template-columns:minmax(0,1fr) 72px!important;gap:6px!important}
    .surat-filter-submit{width:72px!important;min-width:72px!important;padding:0 7px!important}
    .surat-filter-count{display:none}
    .archive-table tbody td,.archive-table tbody td:first-child{grid-template-columns:72px minmax(0,1fr)}
}

/* FINAL FILTER LAYOUT */
.surat-filter-main{display:grid!important;grid-template-columns:minmax(0,1fr) 82px minmax(320px,.8fr)!important;gap:8px!important;align-items:center!important}
.surat-filter-submit{width:82px!important;min-width:82px!important;height:40px!important;padding:0 9px!important;border-radius:10px!important;font-size:10px!important;gap:5px!important}
.surat-date-wrapper{grid-column:auto!important;min-width:0!important}
.surat-date-wrapper>.relative{width:100%!important}
.surat-date-input{width:100%!important;height:40px!important;padding:0 36px 0 39px!important;border-radius:10px!important;font-size:10.5px!important}
.surat-filter-secondary{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:8px!important;margin-top:8px!important}
.surat-filter-trigger{min-height:44px!important;padding:6px 10px!important;border-radius:10px!important}
.surat-filter-menu{width:430px!important;min-width:320px!important;max-width:min(430px,calc(100vw - 24px))!important}
.surat-filter-options{display:grid!important;grid-template-columns:repeat(3,minmax(0,1fr))!important;gap:6px!important;padding:8px!important;max-height:230px!important;overflow-y:auto!important;align-items:stretch!important}
.surat-filter-options:has(.surat-filter-option:nth-child(1):last-child){grid-template-columns:1fr!important}
.surat-filter-options:has(.surat-filter-option:nth-child(2):last-child){grid-template-columns:repeat(2,minmax(0,1fr))!important}
.surat-filter-options:has(.surat-filter-option:nth-child(4):last-child){grid-template-columns:repeat(2,minmax(0,1fr))!important}
.surat-filter-option{min-width:0!important;min-height:37px!important;width:100%!important;padding:6px 7px!important;border-radius:8px!important}
.surat-filter-option input[type="checkbox"]{width:15px!important;height:15px!important;flex:0 0 15px!important}
.surat-filter-option-text{min-width:0!important;overflow:hidden!important;font-size:9px!important;line-height:1.25!important;text-overflow:ellipsis!important;white-space:nowrap!important}
.surat-filter-menu-header{padding:10px 11px!important;gap:9px!important}
.surat-filter-menu-title{font-size:10.5px!important}
.surat-filter-menu-description{margin-top:2px!important;font-size:8px!important}
.surat-filter-menu-actions{gap:4px!important}
.surat-filter-action{min-height:26px!important;padding:0 7px!important;border-radius:7px!important;font-size:8px!important}
.surat-filter-menu-footer{display:flex!important;align-items:center!important;justify-content:space-between!important;gap:7px!important;min-height:34px!important;padding:7px 10px!important;line-height:1.2!important}
.surat-filter-menu-footer [data-footer-count]{min-width:0!important;font-size:8.5px!important;font-weight:700!important;white-space:nowrap!important}
.surat-filter-footer-hint{min-width:0!important;overflow:hidden!important;color:#94a3b8!important;font-size:8px!important;font-weight:600!important;text-overflow:ellipsis!important;white-space:nowrap!important}
@media(max-width:1100px){
    .surat-filter-main{grid-template-columns:minmax(0,1fr) 82px minmax(280px,.78fr)!important}
}
@media(max-width:900px){
    .surat-filter-main{grid-template-columns:minmax(0,1fr) 80px minmax(260px,.72fr)!important}
}
@media(max-width:720px){
    .surat-filter-main{grid-template-columns:minmax(0,1fr) 80px!important;gap:8px!important}
    .surat-date-wrapper{grid-column:1/-1!important}
    .surat-filter-submit{width:80px!important;min-width:80px!important}
    .surat-filter-secondary{grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:8px!important}
    .surat-filter-menu{position:fixed!important;top:50%!important;left:50%!important;right:auto!important;width:calc(100vw - 24px)!important;min-width:0!important;max-width:430px!important;transform:translate(-50%,-50%)!important}
    .surat-filter-options{grid-template-columns:repeat(3,minmax(0,1fr))!important;max-height:55vh!important}
}
@media(max-width:540px){
    .surat-filter-secondary{grid-template-columns:1fr!important}
    .surat-filter-options{grid-template-columns:repeat(2,minmax(0,1fr))!important}
    .surat-filter-menu-footer{align-items:center!important}
}
@media(max-width:390px){
    .surat-filter-main{grid-template-columns:minmax(0,1fr) 76px!important;gap:7px!important}
    .surat-filter-submit{width:76px!important;min-width:76px!important;padding:0 7px!important}
}
</style>
@endpush
<div class="surat-page">
    {{-- =====================================================================
         HEADER
    ====================================================================== --}}
    <div class="surat-page-header">
        <div class="surat-page-header-left">
            <div class="surat-page-icon">
                <svg
                    class="h-7 w-7"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M3 8l9 6 9-6"
                    />
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                    />
                </svg>
            </div>
            <div class="min-w-0">
                <h1 class="surat-page-title">
                    Surat Masuk
                </h1>
                <p class="surat-page-description">
                    Kelola dan pantau seluruh arsip surat masuk organisasi Anda.
                </p>
            </div>
        </div>
        <div class="surat-header-actions">
            <a
                href="{{ route('export.surat-masuk.excel', $exportQuery) }}"
                class="surat-export-button excel"
                title="Export Excel"
                aria-label="Export Excel"
            >
                <svg
                    class="h-4 w-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M4 4h16v16H4zM8 8l8 8m0-8l-8 8"
                    />
                </svg>
                Excel
            </a>
            <a
                href="{{ route('export.surat-masuk.pdf', $exportQuery) }}"
                target="_blank"
                rel="noopener noreferrer"
                class="surat-export-button pdf"
                title="Export PDF"
                aria-label="Export PDF"
            >
                <svg
                    class="h-4 w-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M7 3h7l4 4v14H7a2 2 0 01-2-2V5a2 2 0 012-2zm7 0v5h5"
                    />
                </svg>
                PDF
            </a>
            @if($canManage)
                <a
                    href="{{ route('surat-masuk.create') }}"
                    class="surat-create-button"
                    title="Tambah Surat Masuk"
                    aria-label="Tambah Surat Masuk"
                >
                    <svg
                        class="h-4 w-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 4v16m8-8H4"
                        />
                    </svg>
                    Surat Masuk
                </a>
            @endif
        </div>
    </div>
    <div class="surat-summary">
        <div class="surat-summary-item">
            <div class="surat-summary-icon blue">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 4h10a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 8h6M9 12h6M9 16h4"/>
                </svg>
            </div>
            <div class="surat-summary-text">
                <div class="surat-summary-label">Total Surat Masuk</div>
                <div class="surat-summary-value">{{ number_format($totalSuratMasuk) }}</div>
                <div class="surat-summary-meta">Semua arsip</div>
            </div>
        </div>
        <div class="surat-summary-item">
            <div class="surat-summary-icon red">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l9 6 9-6"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <div class="surat-summary-text">
                <div class="surat-summary-label">Surat Baru</div>
                <div class="surat-summary-value">{{ number_format($suratBaru) }}</div>
                <div class="surat-summary-meta">Perlu dicek</div>
            </div>
        </div>
        <div class="surat-summary-item">
            <div class="surat-summary-icon purple">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zm8-1a3 3 0 100-6m3 17v-2a4 4 0 00-3-3.87"/>
                </svg>
            </div>
            <div class="surat-summary-text">
                <div class="surat-summary-label">Didisposisikan</div>
                <div class="surat-summary-value">{{ number_format($suratDidisposisikan) }}</div>
                <div class="surat-summary-meta">Sudah diteruskan</div>
            </div>
        </div>
        <div class="surat-summary-item">
            <div class="surat-summary-icon green">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="12" cy="12" r="8.5" fill="none" stroke="currentColor" stroke-width="1.8"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8.5 12.5l2.4 2.4 4.7-5"/>
                </svg>
            </div>
            <div class="surat-summary-text">
                <div class="surat-summary-label">Selesai</div>
                <div class="surat-summary-value">{{ number_format($suratSelesai) }}</div>
                <div class="surat-summary-meta">Telah ditindaklanjuti</div>
            </div>
        </div>
    </div>
<div class="surat-workspace">
    <div class="surat-main-card">
        <div class="surat-main-card-header">
            <div class="surat-main-card-title-wrap">
                <div class="surat-main-card-icon">
                    <svg
                        class="h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M6 4h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2z"
                        />
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M8 9h8M8 13h8M8 17h5"
                        />
                    </svg>
                </div>
                <div class="min-w-0">
                    <h2 class="surat-main-card-title">
                        Daftar Surat Masuk
                    </h2>
                    <p class="surat-main-card-subtitle">
                        Menampilkan surat masuk sesuai filter yang dipilih.
                    </p>
                </div>
            </div>
            <a
                href="{{ $sortUrl }}"
                class="surat-sort-badge"
                title="{{ $sortTitle }}"
                aria-label="{{ $sortTitle }}"
            >
                <svg
                    class="h-3.5 w-3.5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.8"
                        d="M8 6h12M8 12h8M8 18h5"
                    />
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.8"
                        d="M4 6v12m0 0l-2-2m2 2l2-2"
                    />
                </svg>
                {{ $sortLabel }}
                <svg
                    class="h-3.5 w-3.5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.8"
                        d="M8 9l4-4 4 4M16 15l-4 4-4-4"
                    />
                </svg>
            </a>
        </div>
            <div class="surat-filter-card">
        <form
            id="filterForm"
            method="GET"
            action="{{ route('surat-masuk.index') }}"
        >
            <input
                type="hidden"
                name="sort"
                value="{{ $sortOrder }}"
            >
            <div class="surat-filter-main">
                {{-- SEARCH --}}
                <div class="surat-search-wrapper">
                    <div class="surat-search-icon">
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0a7 7 0 0114 0z"
                            />
                        </svg>
                    </div>
                    <input
                        type="search"
                        id="search"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Cari perihal, nomor surat, atau pengirim..."
                        autocomplete="off"
                        class="surat-search-input"
                    >
                </div>
                {{-- FILTER --}}
                <button
                    type="submit"
                    class="surat-filter-submit {{ $hasFilters ? 'has-filter' : '' }}"
                    title="Terapkan filter"
                >
                    <svg
                        class="h-4 w-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707v3.414L9 14V9.707a1 1 0 00-.293-.707L3.293 6.293A1 1 0 013 5.586V4z"
                        />
                    </svg>
                    Filter
                </button>
                {{-- RENTANG TANGGAL --}}
                <div class="surat-date-wrapper">
                    <div class="relative">
                        <div class="surat-date-left-icon">
                            <svg
                                class="h-4 w-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5v12a2 2 0 002 2z"
                                />
                            </svg>
                        </div>
                        <input
                            type="text"
                            id="date-range"
                            value="{{ $visibleDateRange }}"
                            readonly
                            autocomplete="off"
                            placeholder="Rentang tanggal"
                            class="surat-date-input"
                            aria-label="Pilih rentang tanggal"
                        >
                        <button
                            type="button"
                            id="clearDateRange"
                            class="surat-date-clear"
                            title="Hapus rentang tanggal"
                            aria-label="Hapus rentang tanggal"
                        >
                            <svg
                                class="h-4 w-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                    <input
                        type="hidden"
                        name="dari_tanggal"
                        id="dari_tanggal"
                        value="{{ $dariTanggal }}"
                    >
                    <input
                        type="hidden"
                        name="sampai_tanggal"
                        id="sampai_tanggal"
                        value="{{ $sampaiTanggal }}"
                    >
                </div>
            </div>
            {{-- KATEGORI + STATUS --}}
            <div class="surat-filter-secondary">
                {{-- KATEGORI --}}
                <div
                    class="surat-filter-dropdown"
                    data-filter-dropdown="kategori"
                >
                    <button
                        type="button"
                        class="surat-filter-trigger"
                        data-dropdown-trigger
                        aria-expanded="false"
                        aria-haspopup="true"
                    >
                        <span class="surat-filter-icon category">
                            <svg
                                class="h-4 w-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h10"
                                />
                            </svg>
                        </span>
                        <span class="surat-filter-trigger-content">
                            <span class="surat-filter-trigger-title">
                                Kategori Surat
                            </span>
                            <span class="surat-filter-trigger-subtitle">
                                Pilih satu atau beberapa kategori
                            </span>
                        </span>
                        <span
                            class="surat-filter-count category"
                            data-filter-count
                        >
                            {{ count($selectedKategori) }} dipilih
                        </span>
                        <span class="surat-filter-chevron">
                            <svg
                                class="h-4 w-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 9l6 6l6-6"
                                />
                            </svg>
                        </span>
                    </button>
                    <div
                        class="surat-filter-menu hidden"
                        data-dropdown-menu
                    >
                        <div class="surat-filter-menu-header">
                            <div>
                                <div class="surat-filter-menu-title">
                                    Pilih Kategori
                                </div>
                                <div class="surat-filter-menu-description">
                                    Checkbox dapat dipilih lebih dari satu
                                </div>
                            </div>
                            <div class="surat-filter-menu-actions">
                                <button
                                    type="button"
                                    class="surat-filter-action select"
                                    data-action="select-all"
                                >
                                    Pilih Semua
                                </button>
                                <button
                                    type="button"
                                    class="surat-filter-action clear"
                                    data-action="clear-all"
                                >
                                    Batalkan
                                </button>
                            </div>
                        </div>
                        <div class="surat-filter-options">
                            @forelse(
                                ($kategoris ?? collect())
                                as $kategori
                            )
                                <label
                                    class="surat-filter-option"
                                >
                                    <input
                                        type="checkbox"
                                        name="kategori_id[]"
                                        value="{{ $kategori->id }}"
                                        class="kategori-checkbox"
                                        @checked(
                                            in_array(
                                                (string) $kategori->id,
                                                $selectedKategori,
                                                true
                                            )
                                        )
                                    >
                                    <span
                                        class="surat-filter-option-text"
                                        title="{{ $kategori->nama_kategori }}"
                                    >
                                        {{ $kategori->nama_kategori }}
                                    </span>
                                </label>
                            @empty
                                <div class="col-span-full px-3 py-6 text-center text-xs text-slate-400">
                                    Belum ada kategori surat.
                                </div>
                            @endforelse
                        </div>
                        <div class="surat-filter-menu-footer">
                            <span
                                class="surat-filter-footer-count"
                                data-footer-count
                            >
                                {{ count($selectedKategori) }}
                                kategori dipilih
                            </span>
                            <span class="surat-filter-footer-hint">
                                Klik Filter untuk menerapkan
                            </span>
                        </div>
                    </div>
                </div>
                {{-- STATUS --}}
                <div
                    class="surat-filter-dropdown"
                    data-filter-dropdown="status"
                >
                    <button
                        type="button"
                        class="surat-filter-trigger"
                        data-dropdown-trigger
                        aria-expanded="false"
                        aria-haspopup="true"
                    >
                        <span class="surat-filter-icon status">
                            <svg
                                class="h-4 w-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12l2 2l4-4m6 2a9 9 0 11-18 0a9 9 0 0118 0z"
                                />
                            </svg>
                        </span>
                        <span class="surat-filter-trigger-content">
                            <span class="surat-filter-trigger-title">
                                Status Surat
                            </span>
                            <span class="surat-filter-trigger-subtitle">
                                Pilih satu atau beberapa status
                            </span>
                        </span>
                        <span
                            class="surat-filter-count status"
                            data-filter-count
                        >
                            {{ count($selectedStatus) }} dipilih
                        </span>
                        <span class="surat-filter-chevron">
                            <svg
                                class="h-4 w-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 9l6 6l6-6"
                                />
                            </svg>
                        </span>
                    </button>
                    <div
                        class="surat-filter-menu hidden"
                        data-dropdown-menu
                    >
                        <div class="surat-filter-menu-header">
                            <div>
                                <div class="surat-filter-menu-title">
                                    Pilih Status
                                </div>
                                <div class="surat-filter-menu-description">
                                    Checkbox dapat dipilih lebih dari satu
                                </div>
                            </div>
                            <div class="surat-filter-menu-actions">
                                <button
                                    type="button"
                                    class="surat-filter-action select"
                                    data-action="select-all"
                                >
                                    Pilih Semua
                                </button>
                                <button
                                    type="button"
                                    class="surat-filter-action clear"
                                    data-action="clear-all"
                                >
                                    Batalkan
                                </button>
                            </div>
                        </div>
                        <div class="surat-filter-options">
                            @foreach(
                                $statusOptions
                                as $value => $label
                            )
                                <label
                                    class="surat-filter-option"
                                >
                                    <input
                                        type="checkbox"
                                        name="status[]"
                                        value="{{ $value }}"
                                        class="status-checkbox"
                                        @checked(
                                            in_array(
                                                $value,
                                                $selectedStatus,
                                                true
                                            )
                                        )
                                    >
                                    <span
                                        class="surat-filter-option-text"
                                    >
                                        {{ $label }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <div class="surat-filter-menu-footer">
                            <span
                                class="surat-filter-footer-count"
                                data-footer-count
                            >
                                {{ count($selectedStatus) }}
                                status dipilih
                            </span>
                            <span class="surat-filter-footer-hint">
                                Klik Filter untuk menerapkan
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
            {{-- =====================================================================
         TABLE
    ====================================================================== --}}
    <div class="archive-table-wrapper">
        <div class="archive-table-scroll">
            <table class="archive-table">
                <thead>
                    <tr>
                        <th>
                            Tanggal
                        </th>
                        <th>
                            Pengirim
                        </th>
                        <th>
                            Perihal
                        </th>
                        <th>
                            Kategori
                        </th>
                        <th>
                            Status
                        </th>
                        <th class="action-cell">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(
                        ($suratMasuks ?? collect())
                        as $surat
                    )
                        @php
                            /*
                            |--------------------------------------------------------------------------
                            | STATUS
                            |--------------------------------------------------------------------------
                            */
                            $status =
                                strtolower(
                                    trim(
                                        (string) (
                                            $surat->status ??
                                            'baru'
                                        )
                                    )
                                );
                            $statusLabel =
                                $statusOptions[
                                    $status
                                ]
                                ??
                                ucfirst(
                                    $status
                                );
                            /*
                            |--------------------------------------------------------------------------
                            | TANGGAL
                            |--------------------------------------------------------------------------
                            */
                            $tanggalTerima =
                                '-';
                            if (
                                $surat->tanggal_terima
                            ) {
                                try {
                                    $tanggalTerima =
                                        Carbon::parse(
                                            $surat->tanggal_terima
                                        )->format(
                                            'd M Y'
                                        );
                                } catch (
                                    \Throwable $e
                                ) {
                                    $tanggalTerima =
                                        '-';
                                }
                            }
                            /*
                            |--------------------------------------------------------------------------
                            | KATEGORI STYLE
                            |--------------------------------------------------------------------------
                            */
                            $kategoriSifat = strtolower(
                                trim(
                                    (string) (
                                        $surat
                                            ->kategori
                                            ?->sifat
                                        ?? ''
                                    )
                                )
                            );

                            $categoryClass = match ($kategoriSifat) {
                                'penting' => 'category-important',
                                'rahasia' => 'category-secret',
                                'segera'  => 'category-urgent',
                                default   => 'category-normal',
                            };
                        @endphp
                        <tr>
                            {{-- TANGGAL --}}
                            <td class="cell-date" data-label="Tanggal">
                                <div>
                                    {{ $tanggalTerima }}
                                </div>
                            </td>
                            {{-- PENGIRIM --}}
                            <td class="cell-sender" data-label="Pengirim">
                                <span
                                    class="sender-name"
                                    title="{{ $surat->pengirim ?? '-' }}"
                                >
                                    {{ $surat->pengirim ?? '-' }}
                                </span>
                                @if(
                                    !empty(
                                        $surat
                                            ->email_pengirim
                                    )
                                )
                                    <span
                                        class="sender-email"
                                        title="{{ $surat->email_pengirim }}"
                                    >
                                        {{ $surat->email_pengirim }}
                                    </span>
                                @endif
                            </td>
                            {{-- PERIHAL --}}
                            <td data-label="Perihal">
                                <div
                                    class="cell-subject max-w-[280px] truncate"
                                    title="{{ $surat->perihal ?? '-' }}"
                                >
                                    {{ $surat->perihal ?? '-' }}
                                </div>
                            </td>
                            {{-- KATEGORI --}}
                            <td class="cell-category" data-label="Kategori">
                                <span
                                    class="category-badge {{ $categoryClass }}"
                                >
                                    <svg
                                        class="h-3.5 w-3.5"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                        aria-hidden="true"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M7 7h10M7 12h10M7 17h6"
                                        />
                                    </svg>
                                    {{
                                        $surat
                                            ->kategori
                                            ?->nama_kategori
                                        ?? '-'
                                    }}
                                </span>
                            </td>
                            {{-- STATUS --}}
                            <td data-label="Status">
                                <span
                                    class="
                                        status-badge
                                        status-{{ $status }}
                                    "
                                >
                                    <span
                                        class="status-dot"
                                    ></span>
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            {{-- AKSI --}}
                            <td class="action-cell" data-label="Aksi">
                                <div class="action-buttons">
                                    {{-- DETAIL --}}
                                    <a
                                        href="{{
                                            route(
                                                'surat-masuk.show',
                                                $surat
                                            )
                                        }}"
                                        class="action-button"
                                        title="Lihat Detail & Disposisi"
                                        aria-label="Lihat detail surat"
                                    >
                                        <svg
                                            class="h-4 w-4"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                            aria-hidden="true"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0a3 3 0 006 0z"
                                            />
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7z"
                                            />
                                        </svg>
                                    </a>
                                    @if($canManage)
                                        {{-- EDIT --}}
                                        <a
                                            href="{{
                                                route(
                                                    'surat-masuk.edit',
                                                    $surat
                                                )
                                            }}"
                                            class="action-button edit"
                                            title="Ubah Data"
                                            aria-label="Ubah data surat"
                                        >
                                            <svg
                                                class="h-4 w-4"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                                aria-hidden="true"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5"
                                                />
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M18.5 2.5a2.121 2.121 0 013 3L11.828 15H9v-2.828L18.5 2.5z"
                                                />
                                            </svg>
                                        </a>
                                        {{-- DELETE --}}
                                        <form
                                            action="{{
                                                route(
                                                    'surat-masuk.destroy',
                                                    $surat
                                                )
                                            }}"
                                            method="POST"
                                            class="delete-form inline"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="button"
                                                class="action-button delete delete-btn"
                                                title="Hapus Surat"
                                                aria-label="Hapus surat"
                                            >
                                                <svg
                                                    class="h-4 w-4"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                    aria-hidden="true"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7"
                                                    />
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M10 11v6m4-6v6"
                                                    />
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M4 7h16m-5-3H9a1 1 0 00-1 1v2h8V5a1 1 0 00-1-1z"
                                                    />
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="6"
                                class="archive-table-empty"
                            >
                                <div>
                                    <div
                                        class="archive-empty-icon"
                                    >
                                        <svg
                                            class="h-7 w-7"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                            aria-hidden="true"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-1.414 0l-2.414-2.414A1 1 0 006.586 13H4"
                                            />
                                        </svg>
                                    </div>
                                    <p
                                        class="text-sm font-bold text-slate-700 sm:text-base"
                                    >
                                        Belum ada data surat masuk
                                    </p>
                                    <p
                                        class="mx-auto mt-1 max-w-md text-xs text-slate-400"
                                    >
                                        @if($hasFilters)
                                            Tidak ada surat yang sesuai dengan filter yang digunakan.
                                        @elseif(
                                            $userRole === 'staff'
                                        )
                                            Belum ada surat masuk yang didisposisikan kepada Anda.
                                        @else
                                            Belum ada data surat masuk yang tersimpan.
                                        @endif
                                    </p>
                                    @if($hasFilters)
                                        <a
                                            href="{{
                                                route(
                                                    'surat-masuk.index'
                                                )
                                            }}"
                                            class="mt-4 inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-xs font-semibold text-white transition hover:bg-slate-800"
                                        >
                                            Reset Filter
                                        </a>
                                    @elseif($canManage)
                                        <a
                                            href="{{
                                                route(
                                                    'surat-masuk.create'
                                                )
                                            }}"
                                            class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-blue-700"
                                        >
                                            <svg
                                                class="h-4 w-4"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                                aria-hidden="true"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M12 4v16m8-8H4"
                                                />
                                            </svg>
                                            Tambah Surat Masuk
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{-- PAGINATION --}}
        @if(
            isset($suratMasuks) &&
            method_exists(
                $suratMasuks,
                'hasPages'
            ) &&
            $suratMasuks->hasPages()
        )
            <div class="archive-pagination">
                {{
                    $suratMasuks
                        ->withQueryString()
                        ->links()
                }}
            </div>
        @endif
    </div>
    </div>
    <aside class="surat-sidebar">
        <section class="surat-side-card">
            <div class="surat-side-card-header">
                <div class="surat-side-card-icon">
                    <svg
                        class="h-4 w-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M5 6h14M5 12h14M5 18h9"
                        />
                    </svg>
                </div>
                <span class="surat-side-card-title">
                    Kategori Surat
                </span>
            </div>
            <div class="surat-side-card-content">
                @if($categorySummary->count())
                    <div class="surat-category-list">
                        @foreach($categorySummary as $namaKategori => $jumlah)
                            <div class="surat-category-row">
                                <span
                                    class="surat-category-name"
                                    title="{{ $namaKategori }}"
                                >
                                    {{ $namaKategori }}
                                </span>
                                <span class="surat-category-count">
                                    {{ number_format($jumlah) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                    <p class="mt-3 text-[8px] text-slate-400">
                        Berdasarkan data yang dapat Anda lihat.
                    </p>
                @else
                    <div class="surat-side-empty">
                        Belum ada kategori yang dapat diringkas.
                    </div>
                @endif
            </div>
        </section>
        <section class="surat-side-card">
            <div class="surat-side-card-header">
                <div class="surat-side-card-icon">
                    <svg
                        class="h-4 w-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M4 19V5M4 19h16"
                        />
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M7 15l3-3 3 2 4-5"
                        />
                    </svg>
                </div>
                <span class="surat-side-card-title">
                    Ringkasan Status
                </span>
            </div>
            <div class="surat-side-card-content">
                <div
                    class="surat-donut"
                    style="--donut: {{ $chartGradient }};"
                    aria-label="Ringkasan status surat masuk"
                >
                    <div class="surat-donut-center">
                        <div class="surat-donut-number">
                            {{ number_format($chartTotal) }}
                        </div>
                        <div class="surat-donut-label">
                            Surat
                        </div>
                    </div>
                </div>
                <div class="surat-legend">
                    @foreach($chartData as $label => $item)
                        <div class="surat-legend-row">
                            <div class="surat-legend-name">
                                <span
                                    class="surat-legend-dot"
                                    style="background: {{ $item['color'] }};"
                                ></span>
                                <span>{{ $label }}</span>
                            </div>
                            <span class="surat-legend-value">
                                {{ number_format($item['value']) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    </aside>
</div>
</div>
{{-- ==========================================================================
     DATE PICKER
     ========================================================================== --}}
<div
    id="customDatePicker"
    class="custom-date-picker hidden"
>
    <div class="custom-date-picker-header">
        <div class="custom-date-picker-title">
            Pilih Rentang Tanggal
        </div>
        <button
            type="button"
            id="datePickerClose"
            class="custom-date-picker-close"
            aria-label="Tutup"
        >
            &times;
        </button>
    </div>
    <div
        id="customDateCalendars"
        class="custom-date-picker-calendars"
    ></div>
    <div class="custom-date-picker-footer">
        <div
            id="datePickerSelected"
            class="custom-date-picker-selected"
        >
            Pilih tanggal awal
        </div>
        <div class="custom-date-picker-actions">
            <button
                type="button"
                id="datePickerClear"
                class="custom-date-picker-button"
            >
                Bersihkan
            </button>
            <button
                type="button"
                id="datePickerApply"
                class="custom-date-picker-button apply"
            >
                Terapkan
            </button>
        </div>
    </div>
</div>
<div
    id="customPickerPanel"
    class="custom-picker-panel hidden"
></div>
@push('scripts')
<script>
(function(){
    'use strict';
    /*
    |--------------------------------------------------------------------------
    | CONFIG
    |--------------------------------------------------------------------------
    */
    const MONTHS = [
        'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    ];
    const WEEKDAYS = [
        'Sn',
        'Sl',
        'Rb',
        'Km',
        'Jm',
        'Sb',
        'Mg'
    ];
    const MIN_YEAR = 2000;
    const MAX_YEAR =
        new Date()
            .getFullYear() +
        20;
    /*
    |--------------------------------------------------------------------------
    | ELEMENT
    |--------------------------------------------------------------------------
    */
    const dateInput =
        document.getElementById(
            'date-range'
        );
    const dariInput =
        document.getElementById(
            'dari_tanggal'
        );
    const sampaiInput =
        document.getElementById(
            'sampai_tanggal'
        );
    const clearButton =
        document.getElementById(
            'clearDateRange'
        );
    const picker =
        document.getElementById(
            'customDatePicker'
        );
    const calendars =
        document.getElementById(
            'customDateCalendars'
        );
    const panel =
        document.getElementById(
            'customPickerPanel'
        );
    const closePickerButton =
        document.getElementById(
            'datePickerClose'
        );
    const clearPickerButton =
        document.getElementById(
            'datePickerClear'
        );
    const applyPickerButton =
        document.getElementById(
            'datePickerApply'
        );
    const selectedLabel =
        document.getElementById(
            'datePickerSelected'
        );
    /*
    |--------------------------------------------------------------------------
    | STATE
    |--------------------------------------------------------------------------
    */
    let selectedStart =
        parseDate(
            dariInput?.value || ''
        );
    let selectedEnd =
        parseDate(
            sampaiInput?.value || ''
        );
    let tempStart =
        selectedStart
            ? cloneDate(
                selectedStart
            )
            : null;
    let tempEnd =
        selectedEnd
            ? cloneDate(
                selectedEnd
            )
            : null;
    let viewLeft =
        selectedStart
            ? new Date(
                selectedStart
                    .getFullYear(),
                selectedStart
                    .getMonth(),
                1
            )
            : new Date();
    let panelSide =
        'left';
    viewLeft.setDate(1);
    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */
    function cloneDate(date){
        return new Date(
            date.getFullYear(),
            date.getMonth(),
            date.getDate()
        );
    }
    function parseDate(value){
        if(!value){
            return null;
        }
        const match =
            String(value).match(
                /^(\d{4})-(\d{2})-(\d{2})$/
            );
        if(!match){
            return null;
        }
        const year =
            Number(match[1]);
        const month =
            Number(match[2]) - 1;
        const day =
            Number(match[3]);
        const date =
            new Date(
                year,
                month,
                day
            );
        if(
            date.getFullYear() !== year ||
            date.getMonth() !== month ||
            date.getDate() !== day
        ){
            return null;
        }
        return date;
    }
    function pad(value){
        return String(value)
            .padStart(
                2,
                '0'
            );
    }
    function toISO(date){
        if(!date){
            return '';
        }
        return [
            date.getFullYear(),
            pad(
                date.getMonth() + 1
            ),
            pad(
                date.getDate()
            )
        ].join('-');
    }
    function formatDate(date){
        if(!date){
            return '';
        }
        return [
            pad(
                date.getDate()
            ),
            pad(
                date.getMonth() + 1
            ),
            date.getFullYear()
        ].join('/');
    }
    function sameDate(
        a,
        b
    ){
        return !!(
            a &&
            b &&
            a.getFullYear() ===
                b.getFullYear() &&
            a.getMonth() ===
                b.getMonth() &&
            a.getDate() ===
                b.getDate()
        );
    }
    function addMonths(
        date,
        amount
    ){
        return new Date(
            date.getFullYear(),
            date.getMonth() +
                amount,
            1
        );
    }
    function isBetween(
        date,
        start,
        end
    ){
        if(
            !date ||
            !start ||
            !end
        ){
            return false;
        }
        const value =
            toISO(date);
        return (
            value >
                toISO(start) &&
            value <
                toISO(end)
        );
    }
    /*
    |--------------------------------------------------------------------------
    | RENDER CALENDAR
    |--------------------------------------------------------------------------
    */
    function renderCalendars(){
        if(!calendars){
            return;
        }
        calendars.innerHTML =
            '';
        const leftDate =
            new Date(
                viewLeft.getFullYear(),
                viewLeft.getMonth(),
                1
            );
        const rightDate =
            addMonths(
                leftDate,
                1
            );
        calendars.appendChild(
            createCalendar(
                leftDate,
                'left'
            )
        );
        calendars.appendChild(
            createCalendar(
                rightDate,
                'right'
            )
        );
        updateSelectedLabel();
        requestAnimationFrame(
            positionPicker
        );
    }
    function createCalendar(
        date,
        side
    ){
        const calendar =
            document.createElement(
                'div'
            );
        calendar.className =
            'custom-calendar';
        const header =
            document.createElement(
                'div'
            );
        header.className =
            'custom-calendar-head';
        const previous =
            document.createElement(
                'button'
            );
        previous.type =
            'button';
        previous.className =
            'custom-calendar-nav';
        previous.innerHTML =
            '&#8249;';
        previous.setAttribute(
            'aria-label',
            'Bulan sebelumnya'
        );
        const next =
            document.createElement(
                'button'
            );
        next.type =
            'button';
        next.className =
            'custom-calendar-nav';
        next.innerHTML =
            '&#8250;';
        next.setAttribute(
            'aria-label',
            'Bulan berikutnya'
        );
        const monthButtons =
            document.createElement(
                'div'
            );
        monthButtons.className =
            'custom-calendar-month-buttons';
        const monthButton =
            document.createElement(
                'button'
            );
        monthButton.type =
            'button';
        monthButton.className =
            'custom-calendar-month';
        monthButton.textContent =
            MONTHS[
                date.getMonth()
            ];
        const yearButton =
            document.createElement(
                'button'
            );
        yearButton.type =
            'button';
        yearButton.className =
            'custom-calendar-year';
        yearButton.textContent =
            String(
                date.getFullYear()
            );
        monthButtons.append(
            monthButton,
            yearButton
        );
        header.append(
            previous,
            monthButtons,
            next
        );
        monthButton.addEventListener(
            'click',
            function(event){
                event.preventDefault();
                event.stopPropagation();
                showMonthPanel(
                    date,
                    monthButton,
                    side
                );
            }
        );
        yearButton.addEventListener(
            'click',
            function(event){
                event.preventDefault();
                event.stopPropagation();
                showYearPanel(
                    date,
                    yearButton,
                    side
                );
            }
        );
        previous.addEventListener(
            'click',
            function(event){
                event.preventDefault();
                event.stopPropagation();
                viewLeft =
                    addMonths(
                        viewLeft,
                        -1
                    );
                renderCalendars();
            }
        );
        next.addEventListener(
            'click',
            function(event){
                event.preventDefault();
                event.stopPropagation();
                viewLeft =
                    addMonths(
                        viewLeft,
                        1
                    );
                renderCalendars();
            }
        );
        calendar.appendChild(
            header
        );
        const weekdays =
            document.createElement(
                'div'
            );
        weekdays.className =
            'custom-calendar-weekdays';
        WEEKDAYS.forEach(
            function(day){
                const element =
                    document.createElement(
                        'div'
                    );
                element.className =
                    'custom-calendar-weekday';
                element.textContent =
                    day;
                weekdays.appendChild(
                    element
                );
            }
        );
        calendar.appendChild(
            weekdays
        );
        const days =
            document.createElement(
                'div'
            );
        days.className =
            'custom-calendar-days';
        const year =
            date.getFullYear();
        const month =
            date.getMonth();
        const firstDay =
            new Date(
                year,
                month,
                1
            ).getDay();
        const mondayOffset =
            firstDay === 0
                ? 6
                : firstDay - 1;
        const daysInMonth =
            new Date(
                year,
                month + 1,
                0
            ).getDate();
        const daysInPreviousMonth =
            new Date(
                year,
                month,
                0
            ).getDate();
        for(
            let index = 0;
            index < 42;
            index++
        ){
            let dayNumber;
            let cellDate;
            let otherMonth =
                false;
            if(
                index <
                mondayOffset
            ){
                dayNumber =
                    daysInPreviousMonth -
                    mondayOffset +
                    index +
                    1;
                cellDate =
                    new Date(
                        year,
                        month - 1,
                        dayNumber
                    );
                otherMonth =
                    true;
            } else if(
                index >=
                mondayOffset +
                daysInMonth
            ){
                dayNumber =
                    index -
                    mondayOffset -
                    daysInMonth +
                    1;
                cellDate =
                    new Date(
                        year,
                        month + 1,
                        dayNumber
                    );
                otherMonth =
                    true;
            } else {
                dayNumber =
                    index -
                    mondayOffset +
                    1;
                cellDate =
                    new Date(
                        year,
                        month,
                        dayNumber
                    );
            }
            const button =
                document.createElement(
                    'button'
                );
            button.type =
                'button';
            button.className =
                'custom-calendar-day';
            button.textContent =
                String(
                    dayNumber
                );
            if(
                otherMonth
            ){
                button.classList.add(
                    'other-month'
                );
            }
            if(
                sameDate(
                    cellDate,
                    new Date()
                )
            ){
                button.classList.add(
                    'today'
                );
            }
            if(
                tempStart &&
                tempEnd &&
                isBetween(
                    cellDate,
                    tempStart,
                    tempEnd
                )
            ){
                button.classList.add(
                    'in-range'
                );
            }
            if(
                tempStart &&
                sameDate(
                    cellDate,
                    tempStart
                )
            ){
                button.classList.add(
                    'range-start'
                );
            }
            if(
                tempEnd &&
                sameDate(
                    cellDate,
                    tempEnd
                )
            ){
                button.classList.add(
                    'range-end'
                );
            }
            button.addEventListener(
                'click',
                function(event){
                    event.preventDefault();
                    event.stopPropagation();
                    selectDate(
                        cellDate
                    );
                }
            );
            days.appendChild(
                button
            );
        }
        calendar.appendChild(
            days
        );
        return calendar;
    }
    /*
    |--------------------------------------------------------------------------
    | SELECT DATE
    |--------------------------------------------------------------------------
    */
    function selectDate(
        date
    ){
        const chosen =
            cloneDate(
                date
            );
        if(
            !tempStart ||
            tempEnd
        ){
            tempStart =
                chosen;
            tempEnd =
                null;
        } else if(
            toISO(chosen) <
            toISO(tempStart)
        ){
            tempEnd =
                cloneDate(
                    tempStart
                );
            tempStart =
                chosen;
        } else {
            tempEnd =
                chosen;
        }
        renderCalendars();
    }
    /*
    |--------------------------------------------------------------------------
    | LABEL
    |--------------------------------------------------------------------------
    */
    function updateSelectedLabel(){
        if(!selectedLabel){
            return;
        }
        if(
            tempStart &&
            tempEnd
        ){
            selectedLabel.textContent =
                formatDate(
                    tempStart
                ) +
                ' - ' +
                formatDate(
                    tempEnd
                );
            return;
        }
        if(tempStart){
            selectedLabel.textContent =
                formatDate(
                    tempStart
                ) +
                ' - pilih tanggal akhir';
            return;
        }
        selectedLabel.textContent =
            'Pilih tanggal awal';
    }
    /*
    |--------------------------------------------------------------------------
    | POSITION
    |--------------------------------------------------------------------------
    */
    function positionPicker(){
        if(
            !picker ||
            !dateInput ||
            window.innerWidth <= 767
        ){
            return;
        }
        const rect =
            dateInput.getBoundingClientRect();
        const width =
            picker.offsetWidth ||
            720;
        const height =
            picker.offsetHeight ||
            500;
        let left =
            rect.left +
            rect.width / 2 -
            width / 2;
        let top =
            rect.bottom +
            8;
        if(
            left + width >
            window.innerWidth - 10
        ){
            left =
                window.innerWidth -
                width -
                10;
        }
        if(left < 10){
            left =
                10;
        }
        if(
            top + height >
            window.innerHeight - 10
        ){
            top =
                rect.top -
                height -
                8;
        }
        if(top < 10){
            top =
                10;
        }
        picker.style.left =
            left + 'px';
        picker.style.top =
            top + 'px';
    }
    /*
    |--------------------------------------------------------------------------
    | SHOW / HIDE
    |--------------------------------------------------------------------------
    */
    function showPicker(){
        if(!picker){
            return;
        }
        closePanel();
        tempStart =
            selectedStart
                ? cloneDate(
                    selectedStart
                )
                : null;
        tempEnd =
            selectedEnd
                ? cloneDate(
                    selectedEnd
                )
                : null;
        if(tempStart){
            viewLeft =
                new Date(
                    tempStart
                        .getFullYear(),
                    tempStart
                        .getMonth(),
                    1
                );
        }
        renderCalendars();
        picker.classList.remove(
            'hidden'
        );
        document.body.classList.add(
            'date-picker-lock'
        );
        requestAnimationFrame(
            positionPicker
        );
    }
    function hidePicker(){
        if(!picker){
            return;
        }
        picker.classList.add(
            'hidden'
        );
        closePanel();
        document.body.classList.remove(
            'date-picker-lock'
        );
    }
    /*
    |--------------------------------------------------------------------------
    | MONTH PANEL
    |--------------------------------------------------------------------------
    */
    function showMonthPanel(
        calendarDate,
        anchor,
        side
    ){
        if(!panel){
            return;
        }
        panelSide =
            side;
        panel.innerHTML =
            '';
        panel.classList.remove(
            'hidden'
        );
        const header =
            document.createElement(
                'div'
            );
        header.className =
            'custom-picker-panel-header';
        const title =
            document.createElement(
                'div'
            );
        title.className =
            'custom-picker-panel-title';
        title.textContent =
            'Pilih Bulan ' +
            calendarDate.getFullYear();
        const close =
            document.createElement(
                'button'
            );
        close.type =
            'button';
        close.className =
            'custom-picker-panel-close';
        close.innerHTML =
            '&times;';
        close.addEventListener(
            'click',
            closePanel
        );
        header.append(
            title,
            close
        );
        panel.appendChild(
            header
        );
        const grid =
            document.createElement(
                'div'
            );
        grid.className =
            'custom-picker-month-grid';
        MONTHS.forEach(
            function(
                monthName,
                monthIndex
            ){
                const button =
                    document.createElement(
                        'button'
                    );
                button.type =
                    'button';
                button.className =
                    'custom-picker-option';
                button.textContent =
                    monthName;
                if(
                    monthIndex ===
                    calendarDate.getMonth()
                ){
                    button.classList.add(
                        'active'
                    );
                }
                button.addEventListener(
                    'click',
                    function(event){
                        event.preventDefault();
                        event.stopPropagation();
                        const newDate =
                            new Date(
                                calendarDate
                                    .getFullYear(),
                                monthIndex,
                                1
                            );
                        viewLeft =
                            panelSide ===
                            'left'
                                ? newDate
                                : addMonths(
                                    newDate,
                                    -1
                                );
                        closePanel();
                        renderCalendars();
                    }
                );
                grid.appendChild(
                    button
                );
            }
        );
        panel.appendChild(
            grid
        );
        positionPanel(
            panel,
            anchor
        );
    }
    /*
    |--------------------------------------------------------------------------
    | YEAR PANEL
    |--------------------------------------------------------------------------
    */
    function showYearPanel(
        calendarDate,
        anchor,
        side
    ){
        if(!panel){
            return;
        }
        panelSide =
            side;
        const startYear =
            Math.floor(
                calendarDate
                    .getFullYear() /
                12
            ) * 12;
        renderYearPanel(
            calendarDate,
            anchor,
            startYear
        );
    }
    function renderYearPanel(
        calendarDate,
        anchor,
        startYear
    ){
        panel.innerHTML =
            '';
        panel.classList.remove(
            'hidden'
        );
        const header =
            document.createElement(
                'div'
            );
        header.className =
            'custom-picker-panel-header';
        const title =
            document.createElement(
                'div'
            );
        title.className =
            'custom-picker-panel-title';
        title.textContent =
            startYear +
            ' - ' +
            (
                startYear +
                11
            );
        const navigation =
            document.createElement(
                'div'
            );
        navigation.className =
            'custom-picker-year-navigation';
        const previous =
            document.createElement(
                'button'
            );
        previous.type =
            'button';
        previous.className =
            'custom-picker-year-nav';
        previous.innerHTML =
            '&#8249;';
        const next =
            document.createElement(
                'button'
            );
        next.type =
            'button';
        next.className =
            'custom-picker-year-nav';
        next.innerHTML =
            '&#8250;';
        const close =
            document.createElement(
                'button'
            );
        close.type =
            'button';
        close.className =
            'custom-picker-panel-close';
        close.innerHTML =
            '&times;';
        previous.addEventListener(
            'click',
            function(event){
                event.preventDefault();
                event.stopPropagation();
                renderYearPanel(
                    calendarDate,
                    anchor,
                    startYear - 12
                );
            }
        );
        next.addEventListener(
            'click',
            function(event){
                event.preventDefault();
                event.stopPropagation();
                renderYearPanel(
                    calendarDate,
                    anchor,
                    startYear + 12
                );
            }
        );
        close.addEventListener(
            'click',
            closePanel
        );
        navigation.append(
            previous,
            next,
            close
        );
        header.append(
            title,
            navigation
        );
        panel.appendChild(
            header
        );
        const grid =
            document.createElement(
                'div'
            );
        grid.className =
            'custom-picker-year-grid';
        for(
            let year = startYear;
            year < startYear + 12;
            year++
        ){
            const button =
                document.createElement(
                    'button'
                );
            button.type =
                'button';
            button.className =
                'custom-picker-option';
            button.textContent =
                String(year);
            if(
                year ===
                calendarDate.getFullYear()
            ){
                button.classList.add(
                    'active'
                );
            }
            if(
                year ===
                new Date()
                    .getFullYear()
            ){
                button.classList.add(
                    'current'
                );
            }
            button.disabled =
                year < MIN_YEAR ||
                year > MAX_YEAR;
            if(
                !button.disabled
            ){
                button.addEventListener(
                    'click',
                    function(event){
                        event.preventDefault();
                        event.stopPropagation();
                        const month =
                            calendarDate
                                .getMonth();
                        viewLeft =
                            panelSide ===
                            'left'
                                ? new Date(
                                    year,
                                    month,
                                    1
                                )
                                : new Date(
                                    year,
                                    month - 1,
                                    1
                                );
                        closePanel();
                        renderCalendars();
                    }
                );
            }
            grid.appendChild(
                button
            );
        }
        panel.appendChild(
            grid
        );
        positionPanel(
            panel,
            anchor
        );
    }
    /*
    |--------------------------------------------------------------------------
    | POSITION PANEL
    |--------------------------------------------------------------------------
    */
    function positionPanel(
        element,
        anchor
    ){
        if(
            !element ||
            !anchor
        ){
            return;
        }
        if(
            window.innerWidth <= 767
        ){
            element.style.left =
                '';
            element.style.top =
                '';
            return;
        }
        const rect =
            anchor.getBoundingClientRect();
        const width =
            element.offsetWidth ||
            310;
        const height =
            element.offsetHeight ||
            300;
        let left =
            rect.left +
            rect.width / 2 -
            width / 2;
        let top =
            rect.bottom +
            8;
        if(
            left + width >
            window.innerWidth - 10
        ){
            left =
                window.innerWidth -
                width -
                10;
        }
        if(left < 10){
            left =
                10;
        }
        if(
            top + height >
            window.innerHeight - 10
        ){
            top =
                rect.top -
                height -
                8;
        }
        if(top < 10){
            top =
                10;
        }
        element.style.left =
            left + 'px';
        element.style.top =
            top + 'px';
    }
    function closePanel(){
        if(!panel){
            return;
        }
        panel.classList.add(
            'hidden'
        );
        panel.innerHTML =
            '';
    }
    /*
    |--------------------------------------------------------------------------
    | APPLY DATE RANGE
    |--------------------------------------------------------------------------
    */
    function applyDateRange(){
        if(
            !tempStart ||
            !tempEnd
        ){
            if(
                typeof window.Swal !==
                'undefined'
            ){
                window.Swal.fire({
                    icon:'info',
                    title:'Pilih rentang tanggal',
                    text:
                        'Silakan pilih tanggal awal dan tanggal akhir terlebih dahulu.',
                    confirmButtonText:
                        'Mengerti',
                    confirmButtonColor:
                        '#2563eb'
                });
            } else {
                window.alert(
                    'Silakan pilih tanggal awal dan tanggal akhir terlebih dahulu.'
                );
            }
            return;
        }
        selectedStart =
            cloneDate(
                tempStart
            );
        selectedEnd =
            cloneDate(
                tempEnd
            );
        if(dariInput){
            dariInput.value =
                toISO(
                    selectedStart
                );
        }
        if(sampaiInput){
            sampaiInput.value =
                toISO(
                    selectedEnd
                );
        }
        if(dateInput){
            dateInput.value =
                formatDate(
                    selectedStart
                ) +
                ' - ' +
                formatDate(
                    selectedEnd
                );
        }
        if(clearButton){
            clearButton.style.display =
                'flex';
        }
        hidePicker();
    }
    /*
    |--------------------------------------------------------------------------
    | CLEAR DATE
    |--------------------------------------------------------------------------
    */
    function clearDateRange(){
        selectedStart =
            null;
        selectedEnd =
            null;
        tempStart =
            null;
        tempEnd =
            null;
        if(dariInput){
            dariInput.value =
                '';
        }
        if(sampaiInput){
            sampaiInput.value =
                '';
        }
        if(dateInput){
            dateInput.value =
                '';
        }
        if(clearButton){
            clearButton.style.display =
                'none';
        }
        viewLeft =
            new Date();
        viewLeft.setDate(
            1
        );
        hidePicker();
    }
    /*
    |--------------------------------------------------------------------------
    | DATE EVENTS
    |--------------------------------------------------------------------------
    */
    if(
        dateInput &&
        picker
    ){
        dateInput.addEventListener(
            'click',
            function(event){
                event.preventDefault();
                event.stopPropagation();
                if(
                    picker.classList.contains(
                        'hidden'
                    )
                ){
                    showPicker();
                } else {
                    hidePicker();
                }
            }
        );
        dateInput.addEventListener(
            'focus',
            function(){
                if(
                    picker.classList.contains(
                        'hidden'
                    )
                ){
                    showPicker();
                }
            }
        );
        closePickerButton?.addEventListener(
            'click',
            function(event){
                event.preventDefault();
                event.stopPropagation();
                hidePicker();
            }
        );
        clearPickerButton?.addEventListener(
            'click',
            function(event){
                event.preventDefault();
                event.stopPropagation();
                clearDateRange();
            }
        );
        applyPickerButton?.addEventListener(
            'click',
            function(event){
                event.preventDefault();
                event.stopPropagation();
                applyDateRange();
            }
        );
        clearButton?.addEventListener(
            'click',
            function(event){
                event.preventDefault();
                event.stopPropagation();
                clearDateRange();
            }
        );
        document.addEventListener(
            'mousedown',
            function(event){
                if(
                    picker.classList.contains(
                        'hidden'
                    )
                ){
                    return;
                }
                if(
                    picker.contains(
                        event.target
                    )
                ){
                    return;
                }
                if(
                    panel &&
                    panel.contains(
                        event.target
                    )
                ){
                    return;
                }
                if(
                    dateInput.contains(
                        event.target
                    )
                ){
                    return;
                }
                if(
                    clearButton &&
                    clearButton.contains(
                        event.target
                    )
                ){
                    return;
                }
                hidePicker();
            }
        );
    }
    /*
    |--------------------------------------------------------------------------
    | RESIZE
    |--------------------------------------------------------------------------
    */
    window.addEventListener(
        'resize',
        function(){
            if(
                picker &&
                !picker.classList.contains(
                    'hidden'
                )
            ){
                positionPicker();
            }
            if(
                panel &&
                !panel.classList.contains(
                    'hidden'
                )
            ){
                closePanel();
            }
        }
    );
    /*
    |--------------------------------------------------------------------------
    | SCROLL
    |--------------------------------------------------------------------------
    */
    window.addEventListener(
        'scroll',
        function(){
            if(
                picker &&
                !picker.classList.contains(
                    'hidden'
                ) &&
                window.innerWidth > 767
            ){
                positionPicker();
            }
        },
        true
    );
    /*
    |--------------------------------------------------------------------------
    | FILTER DROPDOWNS
    |--------------------------------------------------------------------------
    */
    function initializeFilterDropdowns(){
        const dropdowns =
            document.querySelectorAll(
                '[data-filter-dropdown]'
            );
        if(!dropdowns.length){
            return;
        }
        function closeDropdown(
            dropdown
        ){
            const trigger =
                dropdown.querySelector(
                    '[data-dropdown-trigger]'
                );
            const menu =
                dropdown.querySelector(
                    '[data-dropdown-menu]'
                );
            if(
                !trigger ||
                !menu
            ){
                return;
            }
            menu.classList.add(
                'hidden'
            );
            trigger.classList.remove(
                'is-open'
            );
            trigger.setAttribute(
                'aria-expanded',
                'false'
            );
        }
        function closeAllDropdowns(
            except = null
        ){
            dropdowns.forEach(
                function(dropdown){
                    if(
                        dropdown !==
                        except
                    ){
                        closeDropdown(
                            dropdown
                        );
                    }
                }
            );
        }
        function updateDropdown(
            dropdown
        ){
            const type =
                dropdown.dataset
                    .filterDropdown;
            const selector =
                type === 'kategori'
                    ? '.kategori-checkbox'
                    : '.status-checkbox';
            const checkboxes =
                dropdown.querySelectorAll(
                    selector
                );
            const checked =
                dropdown.querySelectorAll(
                    selector +
                    ':checked'
                );
            const count =
                checked.length;
            const countElement =
                dropdown.querySelector(
                    '[data-filter-count]'
                );
            const footerCount =
                dropdown.querySelector(
                    '[data-footer-count]'
                );
            const trigger =
                dropdown.querySelector(
                    '[data-dropdown-trigger]'
                );
            if(countElement){
                countElement.textContent =
                    count +
                    ' dipilih';
            }
            if(footerCount){
                footerCount.textContent =
                    count +
                    ' ' +
                    (
                        type ===
                        'kategori'
                            ? 'kategori'
                            : 'status'
                    ) +
                    ' dipilih';
            }
            checkboxes.forEach(
                function(checkbox){
                    const option =
                        checkbox.closest(
                            '.surat-filter-option'
                        );
                    if(option){
                        option.classList.toggle(
                            'is-selected',
                            checkbox.checked
                        );
                    }
                }
            );
            if(trigger){
                trigger.classList.toggle(
                    'is-active',
                    count > 0
                );
            }
        }
        dropdowns.forEach(
            function(dropdown){
                const trigger =
                    dropdown.querySelector(
                        '[data-dropdown-trigger]'
                    );
                const menu =
                    dropdown.querySelector(
                        '[data-dropdown-menu]'
                    );
                if(
                    !trigger ||
                    !menu
                ){
                    return;
                }
                const type =
                    dropdown.dataset
                        .filterDropdown;
                const selector =
                    type === 'kategori'
                        ? '.kategori-checkbox'
                        : '.status-checkbox';
                const checkboxes =
                    dropdown.querySelectorAll(
                        selector
                    );
                const selectAll =
                    dropdown.querySelector(
                        '[data-action="select-all"]'
                    );
                const clearAll =
                    dropdown.querySelector(
                        '[data-action="clear-all"]'
                    );
                trigger.addEventListener(
                    'click',
                    function(event){
                        event.preventDefault();
                        event.stopPropagation();
                        const isOpen =
                            !menu.classList.contains(
                                'hidden'
                            );
                        closeAllDropdowns(
                            dropdown
                        );
                        if(isOpen){
                            closeDropdown(
                                dropdown
                            );
                            return;
                        }
                        menu.classList.remove(
                            'hidden'
                        );
                        trigger.classList.add(
                            'is-open'
                        );
                        trigger.setAttribute(
                            'aria-expanded',
                            'true'
                        );
                        updateDropdown(
                            dropdown
                        );
                    }
                );
                menu.addEventListener(
                    'click',
                    function(event){
                        event.stopPropagation();
                    }
                );
                checkboxes.forEach(
                    function(checkbox){
                        checkbox.addEventListener(
                            'change',
                            function(){
                                updateDropdown(
                                    dropdown
                                );
                            }
                        );
                    }
                );
                selectAll?.addEventListener(
                    'click',
                    function(event){
                        event.preventDefault();
                        event.stopPropagation();
                        checkboxes.forEach(
                            function(checkbox){
                                checkbox.checked =
                                    true;
                            }
                        );
                        updateDropdown(
                            dropdown
                        );
                    }
                );
                clearAll?.addEventListener(
                    'click',
                    function(event){
                        event.preventDefault();
                        event.stopPropagation();
                        checkboxes.forEach(
                            function(checkbox){
                                checkbox.checked =
                                    false;
                            }
                        );
                        updateDropdown(
                            dropdown
                        );
                    }
                );
                updateDropdown(
                    dropdown
                );
            }
        );
        document.addEventListener(
            'click',
            function(event){
                if(
                    event.target.closest(
                        '[data-filter-dropdown]'
                    )
                ){
                    return;
                }
                closeAllDropdowns();
            }
        );
        document.addEventListener(
            'keydown',
            function(event){
                if(
                    event.key === 'Escape'
                ){
                    closeAllDropdowns();
                }
            }
        );
    }
    /*
    |--------------------------------------------------------------------------
    | VALIDASI
    |--------------------------------------------------------------------------
    */
    const filterForm =
        document.getElementById(
            'filterForm'
        );
    filterForm?.addEventListener(
        'submit',
        function(event){
            const start =
                dariInput?.value ||
                '';
            const end =
                sampaiInput?.value ||
                '';
            if(
                start &&
                end &&
                start > end
            ){
                event.preventDefault();
                if(
                    typeof window.Swal !==
                    'undefined'
                ){
                    window.Swal.fire({
                        icon:'warning',
                        title:
                            'Rentang tanggal tidak valid',
                        text:
                            'Tanggal mulai tidak boleh lebih besar dari tanggal akhir.',
                        confirmButtonText:
                            'Mengerti',
                        confirmButtonColor:
                            '#2563eb'
                    });
                } else {
                    window.alert(
                        'Tanggal mulai tidak boleh lebih besar dari tanggal akhir.'
                    );
                }
            }
        }
    );
    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */
    document.querySelectorAll(
        '.delete-btn'
    ).forEach(
        function(button){
            button.addEventListener(
                'click',
                function(){
                    const form =
                        this.closest(
                            '.delete-form'
                        );
                    if(!form){
                        return;
                    }
                    if(
                        typeof window.Swal !==
                        'undefined'
                    ){
                        window.Swal.fire({
                            title:
                                'Hapus Surat Masuk?',
                            text:
                                'Data yang dihapus akan dipindahkan ke tempat sampah.',
                            icon:
                                'warning',
                            showCancelButton:
                                true,
                            confirmButtonColor:
                                '#ef4444',
                            cancelButtonColor:
                                '#64748b',
                            confirmButtonText:
                                'Ya, Hapus!',
                            cancelButtonText:
                                'Batal',
                            reverseButtons:
                                true
                        }).then(
                            function(result){
                                if(
                                    result.isConfirmed
                                ){
                                    form.submit();
                                }
                            }
                        );
                        return;
                    }
                    if(
                        window.confirm(
                            'Yakin ingin menghapus surat masuk ini?'
                        )
                    ){
                        form.submit();
                    }
                }
            );
        }
    );
    /*
    |--------------------------------------------------------------------------
    | INIT
    |--------------------------------------------------------------------------
    */
    initializeFilterDropdowns();
    if(
        selectedStart &&
        selectedEnd &&
        dateInput &&
        clearButton
    ){
        dateInput.value =
            formatDate(
                selectedStart
            ) +
            ' - ' +
            formatDate(
                selectedEnd
            );
        clearButton.style.display =
            'flex';
    }
})();
</script>
<script
    src="https://cdn.jsdelivr.net/npm/sweetalert2@11"
    defer
></script>
@endpush
@endsection

<style>
/* ============================================================================
   PAGINATION - RAPIH & COMPACT
   ============================================================================ */
.archive-pagination{
    padding:8px 16px 9px !important;
    border-top:1px solid #edf2f7 !important;
    background:#fff !important;
}

/* Baris pagination */
.archive-pagination nav{
    font-size:10px !important;
}

.archive-pagination nav p{
    margin:0 !important;
    color:#94a3b8 !important;
    font-size:10px !important;
    line-height:1.2 !important;
}

/* Pastikan area nomor halaman rapat dan sejajar */
.archive-pagination nav > div:last-child{
    display:flex !important;
    align-items:center !important;
    gap:4px !important;
}

.archive-pagination nav > div:last-child > div:last-child{
    display:flex !important;
    align-items:center !important;
    gap:4px !important;
}

/* Hilangkan jarak/margin bawaan Tailwind pada pembungkus tombol */
.archive-pagination nav > div:last-child > div:last-child > span,
.archive-pagination nav > div:last-child > div:last-child > a{
    margin:0 !important;
}

/* Wrapper tombol Laravel/Tailwind */
.archive-pagination nav .relative.z-0.inline-flex{
    display:inline-flex !important;
    align-items:center !important;
    gap:4px !important;
    box-shadow:none !important;
    border-radius:8px !important;
}

/* Tombol Previous / Next dan nomor halaman */
.archive-pagination nav a,
.archive-pagination nav span{
    box-sizing:border-box !important;
}

.archive-pagination nav .relative.z-0.inline-flex > a,
.archive-pagination nav .relative.z-0.inline-flex > span{
    display:inline-flex !important;
    align-items:center !important;
    justify-content:center !important;
    min-width:32px !important;
    width:32px !important;
    height:32px !important;
    margin:0 !important;
    padding:0 !important;
    border:1px solid #dbe4f0 !important;
    border-radius:7px !important;
    font-size:10px !important;
    line-height:1 !important;
    box-shadow:none !important;
}

/* Ikon panah */
.archive-pagination nav .relative.z-0.inline-flex svg{
    width:13px !important;
    height:13px !important;
}

/* Nomor halaman aktif */
.archive-pagination nav .relative.z-0.inline-flex span[aria-current="page"]{
    min-width:32px !important;
    width:32px !important;
    height:32px !important;
    font-size:10px !important;
}

/* Mobile */
@media (max-width:640px){
    .archive-pagination{
        padding:7px 10px 8px !important;
    }

    .archive-pagination nav p{
        font-size:8px !important;
    }

    .archive-pagination nav .relative.z-0.inline-flex{
        gap:3px !important;
    }

    .archive-pagination nav .relative.z-0.inline-flex > a,
    .archive-pagination nav .relative.z-0.inline-flex > span,
    .archive-pagination nav .relative.z-0.inline-flex span[aria-current="page"]{
        min-width:29px !important;
        width:29px !important;
        height:29px !important;
        border-radius:6px !important;
        font-size:9px !important;
    }

    .archive-pagination nav .relative.z-0.inline-flex svg{
        width:11px !important;
        height:11px !important;
    }
}

/* Perbaikan akhir: tombol aksi tetap satu baris dan rata di kolom Aksi. */
.archive-table th.action-cell,
.archive-table td.action-cell {
    width: 132px !important;
    min-width: 132px !important;
    text-align: center !important;
    vertical-align: middle !important;
    white-space: nowrap !important;
}
.archive-table tbody td.action-cell {
    display: table-cell !important;
    padding-left: 8px !important;
    padding-right: 8px !important;
}
.archive-table tbody td.action-cell::before { content: none !important; }
.archive-table .action-buttons {
    display: inline-flex !important;
    flex-direction: row !important;
    flex-wrap: nowrap !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 6px !important;
    width: auto !important;
    margin: 0 auto !important;
    white-space: nowrap !important;
}
.archive-table .action-button {
    box-sizing: border-box !important;
    display: inline-flex !important;
    flex: 0 0 34px !important;
    width: 34px !important;
    min-width: 34px !important;
    height: 34px !important;
    padding: 0 !important;
    align-items: center !important;
    justify-content: center !important;
}
@media (max-width: 760px) {
    .archive-table tbody td.action-cell {
        display: flex !important;
        width: 100% !important;
        min-width: 0 !important;
        align-items: center !important;
        justify-content: space-between !important;
        gap: 10px !important;
    }
    .archive-table tbody td.action-cell::before { content: attr(data-label) !important; }
    .archive-table .action-buttons { margin-left: auto !important; flex: 0 0 auto !important; }
}
</style>
