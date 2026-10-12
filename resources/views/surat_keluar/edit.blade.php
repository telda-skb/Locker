@extends('layouts.app')
@section('title', 'Surat Keluar')
@section('content')
@php
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
    | KATEGORI FILTER
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
            $rawKategori,
        ];
    }
    if (!is_array($rawKategori)) {
        $rawKategori = [];
    }
    $selectedKategori = collect(
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
                (string) ((int) $id)
        )
        ->unique()
        ->values()
        ->all();
    /*
    |--------------------------------------------------------------------------
    | STATUS
    |--------------------------------------------------------------------------
    */
    $statusOptions = [
        'draft' =>
            'Draf',
        'diproses' =>
            'Diproses',
        'disetujui' =>
            'Disetujui',
        'dikirim' =>
            'Dikirim',
        'diarsipkan' =>
            'Diarsipkan',
    ];
    $rawStatuses = request(
        'status',
        []
    );
    if (
        is_scalar($rawStatuses) &&
        trim((string) $rawStatuses) !== ''
    ) {
        $rawStatuses = [
            $rawStatuses,
        ];
    }
    if (!is_array($rawStatuses)) {
        $rawStatuses = [];
    }
    $selectedStatus = collect(
        $rawStatuses
    )
        ->flatten()
        ->filter(
            fn ($status) =>
                is_scalar($status)
        )
        ->map(
            function ($status) {
                $status = strtolower(
                    trim(
                        (string) $status
                    )
                );
                return $status === 'draf'
                    ? 'draft'
                    : $status;
            }
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
    | STATUS BADGE
    |--------------------------------------------------------------------------
    */
    $statusBadgeClasses = [
        'draft' =>
            'bg-slate-50 text-slate-700 border-slate-200',
        'diproses' =>
            'bg-amber-50 text-amber-700 border-amber-200',
        'disetujui' =>
            'bg-blue-50 text-blue-700 border-blue-200',
        'dikirim' =>
            'bg-emerald-50 text-emerald-700 border-emerald-200',
        'diarsipkan' =>
            'bg-purple-50 text-purple-700 border-purple-200',
    ];
    /*
    |--------------------------------------------------------------------------
    | TANGGAL
    |--------------------------------------------------------------------------
    */
    $dariTanggal =
        request('dari_tanggal');
    $sampaiTanggal =
        request('sampai_tanggal');
    $visibleDateRange = '';
    try {
        if (
            $dariTanggal &&
            $sampaiTanggal
        ) {
            $visibleDateRange =
                \Illuminate\Support\Carbon::parse(
                    $dariTanggal
                )->format('d/m/Y')
                .
                ' - '
                .
                \Illuminate\Support\Carbon::parse(
                    $sampaiTanggal
                )->format('d/m/Y');
        } elseif ($dariTanggal) {
            $visibleDateRange =
                \Illuminate\Support\Carbon::parse(
                    $dariTanggal
                )->format('d/m/Y');
        } elseif ($sampaiTanggal) {
            $visibleDateRange =
                \Illuminate\Support\Carbon::parse(
                    $sampaiTanggal
                )->format('d/m/Y');
        }
    } catch (\Throwable $e) {
        $visibleDateRange = '';
    }
    /*
    |--------------------------------------------------------------------------
    | FILTER AKTIF
    |--------------------------------------------------------------------------
    */
    $hasFilters =
        request()->filled('search')
        ||
        !empty($selectedKategori)
        ||
        !empty($selectedStatus)
        ||
        request()->filled('dari_tanggal')
        ||
        request()->filled('sampai_tanggal');
    /*
    |--------------------------------------------------------------------------
    | EXPORT FILTER
    |--------------------------------------------------------------------------
    */
    $exportFilters = [];
    $searchValue =
        trim(
            (string) request(
                'search',
                ''
            )
        );
    /*
    |--------------------------------------------------------------------------
    | URUTAN DATA
    |--------------------------------------------------------------------------
    |
    | desc = terbaru ke terlama
    | asc  = terlama ke terbaru
    |
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
        'surat-keluar.index',
        array_merge(
            request()->except(['sort', 'page']),
            ['sort' => $nextSortOrder]
        )
    );
    $sortTitle =
        $sortOrder === 'desc'
            ? 'Klik untuk menampilkan dari terlama ke terbaru'
            : 'Klik untuk menampilkan dari terbaru ke terlama';
    if ($searchValue !== '') {
        $exportFilters['search'] =
            $searchValue;
    }
    $exportFilters['sort'] = $sortOrder;
    if (!empty($selectedKategori)) {
        $exportFilters['kategori_id'] =
            $selectedKategori;
    }
    if (!empty($selectedStatus)) {
        $exportFilters['status'] =
            $selectedStatus;
    }
    if (
        is_scalar($dariTanggal) &&
        preg_match(
            '/^\d{4}-\d{2}-\d{2}$/',
            (string) $dariTanggal
        )
    ) {
        $exportFilters['dari_tanggal'] =
            $dariTanggal;
    }
    if (
        is_scalar($sampaiTanggal) &&
        preg_match(
            '/^\d{4}-\d{2}-\d{2}$/',
            (string) $sampaiTanggal
        )
    ) {
        $exportFilters['sampai_tanggal'] =
            $sampaiTanggal;
    }
    /*
    |--------------------------------------------------------------------------
    | SCORECARD
    |--------------------------------------------------------------------------
    |
    | Scorecard dihitung dari seluruh data surat keluar.
    | Tidak terpengaruh search/filter/pagination.
    |
    */
    $statusCounts = is_array($statusCounts ?? null)
        ? $statusCounts
        : [];
    $suratKeluarStatistics =
        \App\Models\SuratKeluar::query()
            ->selectRaw(
                'COUNT(*) AS total_suratan'
            )
            ->selectRaw(
                "SUM(
                    CASE
                        WHEN status IN ('draft', 'draf')
                        THEN 1
                        ELSE 0
                    END
                ) AS draft_suratan"
            )
            ->selectRaw(
                "SUM(
                    CASE
                        WHEN status = 'diproses'
                        THEN 1
                        ELSE 0
                    END
                ) AS diproses_suratan"
            )
            ->selectRaw(
                "SUM(
                    CASE
                        WHEN status = 'dikirim'
                        THEN 1
                        ELSE 0
                    END
                ) AS dikirim_suratan"
            )
            ->first();
    $totalSuratKeluar =
        (int) (
            $suratKeluarStatistics->total_suratan
            ?? 0
        );
    $suratDraft =
        (int) (
            $suratKeluarStatistics->draft_suratan
            ?? 0
        );
    $suratDiproses =
        (int) (
            $suratKeluarStatistics->diproses_suratan
            ?? 0
        );
    $suratDikirim =
        (int) (
            $suratKeluarStatistics->dikirim_suratan
            ?? 0
        );
    $suratDisetujui = (int) (
        $statusCounts['disetujui']
        ?? 0
    );
    $suratDiarsipkan = (int) (
        $statusCounts['diarsipkan']
        ?? 0
    );
    $categorySummary = collect();
    if (isset($categoryCounts)) {
        $categorySummary = collect($categoryCounts)
            ->mapWithKeys(function ($item) {
                $name = trim((string) ($item->kategori?->nama_kategori ?? 'Tanpa Kategori'));
                return [$name !== '' ? $name : 'Tanpa Kategori' => (int) ($item->total ?? 0)];
            })
            ->filter(fn ($jumlah) => $jumlah > 0)
            ->sortDesc()
            ->take(5);
    }
    if ($categorySummary->isEmpty() && isset($suratKeluars)) {
        $categoryCollection = method_exists($suratKeluars, 'getCollection')
            ? collect($suratKeluars->getCollection())
            : collect($suratKeluars);
        $categorySummary = $categoryCollection
            ->map(fn ($item) => trim((string) ($item->kategori?->nama_kategori ?? 'Tanpa Kategori')))
            ->map(fn ($name) => $name !== '' ? $name : 'Tanpa Kategori')
            ->countBy()
            ->sortDesc()
            ->take(5);
    }
    $statusChartData = [
        'Draf' => ['value' => $suratDraft, 'color' => '#64748b'],
        'Diproses' => ['value' => $suratDiproses, 'color' => '#d97706'],
        'Disetujui' => ['value' => $suratDisetujui, 'color' => '#2563eb'],
        'Dikirim' => ['value' => $suratDikirim, 'color' => '#059669'],
        'Diarsipkan' => ['value' => $suratDiarsipkan, 'color' => '#7c3aed'],
    ];
    $statusChartTotal = array_sum(array_column($statusChartData, 'value'));
    $statusChartGradient = '#e2e8f0';
    if ($statusChartTotal > 0) {
        $segments = [];
        $cursor = 0;
        foreach ($statusChartData as $chartItem) {
            if ((int) $chartItem['value'] <= 0) {
                continue;
            }
            $start = $cursor;
            $cursor += ((int) $chartItem['value'] / $statusChartTotal) * 360;
            $segments[] = $chartItem['color'] . ' ' . round($start, 2) . 'deg ' . round($cursor, 2) . 'deg';
        }
        $statusChartGradient = 'conic-gradient(' . implode(', ', $segments) . ')';
    }
@endphp
@push('styles')
<style>
/* PAGE */
.surat-page{min-width:0;color:#172033;}
/* HEADER */
.surat-page-header{display:flex;align-items:center;justify-content:space-between;gap:20px;margin-bottom:18px;}
.surat-page-header-left{display:flex;align-items:center;gap:14px;min-width:0;}
.surat-page-icon{display:flex;align-items:center;justify-content:center;width:56px;height:56px;flex:0 0 56px;border-radius:16px;background:linear-gradient(135deg,#dbeafe,#eff6ff);color:#2563eb;}
.surat-page-title{margin:0;color:#172554;font-size:28px;font-weight:800;line-height:1.1;letter-spacing:-.025em;}
.surat-page-description{margin-top:4px;color:#64748b;font-size:12px;line-height:1.45;}
.surat-header-actions{display:flex;align-items:center;justify-content:flex-end;gap:8px;flex-wrap:wrap;}
.surat-export-button,.surat-create-button{display:inline-flex;align-items:center;justify-content:center;gap:6px;min-height:40px;padding:0 13px;border-radius:10px;font-size:11px;font-weight:700;line-height:1;text-decoration:none;transition:.15s ease;}
.surat-export-button{border:1px solid #dbe4f0;background:#fff;color:#475569;box-shadow:0 2px 8px rgba(15,23,42,.035);}
.surat-export-button:hover{transform:translateY(-1px);}
.surat-export-button.excel:hover{border-color:#a7f3d0;background:#ecfdf5;color:#047857;}
.surat-export-button.pdf:hover{border-color:#fecdd3;background:#fff1f2;color:#be123c;}
.surat-create-button{border:1px solid #2563eb;background:#2563eb;color:#fff;box-shadow:0 8px 20px rgba(37,99,235,.18);}
.surat-create-button:hover{border-color:#1d4ed8;background:#1d4ed8;transform:translateY(-1px);box-shadow:0 12px 24px rgba(37,99,235,.23);}
/* SCORECARD */
.surat-keluar-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:16px;}
.surat-keluar-summary-card{position:relative;display:flex;align-items:center;min-width:0;min-height:94px;gap:12px;overflow:hidden;padding:15px 16px;border:1px solid #dbe4f0;border-radius:15px;background:#fff;box-shadow:0 3px 12px rgba(15,23,42,.035);transition:transform .15s ease,box-shadow .15s ease,border-color .15s ease;}
.surat-keluar-summary-card:hover{transform:translateY(-1px);border-color:#cbd5e1;box-shadow:0 7px 20px rgba(15,23,42,.06);}
.surat-keluar-summary-card::before{content:'';position:absolute;left:0;top:12px;bottom:12px;width:3px;border-radius:0 6px 6px 0;background:#2563eb;}
.surat-keluar-summary-card:nth-child(2)::before{background:#64748b;}
.surat-keluar-summary-card:nth-child(3)::before{background:#d97706;}
.surat-keluar-summary-card:nth-child(4)::before{background:#059669;}
.surat-keluar-summary-icon{display:flex;align-items:center;justify-content:center;width:42px;height:42px;flex:0 0 42px;border-radius:11px;}
.surat-keluar-summary-icon.blue{background:#eff6ff;color:#2563eb;}
.surat-keluar-summary-icon.slate{background:#f1f5f9;color:#64748b;}
.surat-keluar-summary-icon.amber{background:#fffbeb;color:#d97706;}
.surat-keluar-summary-icon.green{background:#ecfdf5;color:#059669;}
.surat-keluar-summary-icon.purple{background:#f5f3ff;color:#7c3aed;}
.surat-keluar-summary-content{min-width:0;}
.surat-keluar-summary-label{margin:0 0 3px;color:#64748b;font-size:10px;font-weight:700;}
.surat-keluar-summary-value{margin:0;color:#0f172a;font-size:23px;font-weight:800;line-height:1;}
.surat-keluar-summary-note{margin-top:4px;color:#94a3b8;font-size:8.5px;line-height:1.3;}
/* WORKSPACE */
.surat-workspace{display:grid;grid-template-columns:minmax(0,1fr) 292px;gap:16px;align-items:start;}
.surat-main-card,.surat-side-card{overflow:hidden;border:1px solid #dbe4f0;border-radius:16px;background:#fff;box-shadow:0 3px 14px rgba(15,23,42,.04);}
.surat-main-card-header,.surat-side-card-header{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:15px 18px;border-bottom:1px solid #edf2f7;}
.surat-main-card-title-wrap{display:flex;align-items:center;gap:10px;min-width:0;}
.surat-main-card-icon,.surat-side-card-icon{display:flex;align-items:center;justify-content:center;width:36px;height:36px;flex:0 0 36px;border-radius:10px;background:#eff6ff;color:#2563eb;}
.surat-main-card-title{margin:0;color:#172033;font-size:14px;font-weight:800;}
.surat-main-card-subtitle{margin-top:2px;color:#94a3b8;font-size:9px;line-height:1.4;}
.surat-sort-badge{display:inline-flex;align-items:center;gap:5px;min-height:30px;padding:0 9px;border:1px solid #dbe4f0;border-radius:9px;background:#fff;color:#475569;font-size:9px;font-weight:700;text-decoration:none;white-space:nowrap;cursor:pointer;transition:.15s ease;}.surat-sort-badge:hover{border-color:#bfdbfe;background:#eff6ff;color:#2563eb;transform:translateY(-1px);box-shadow:0 5px 14px rgba(37,99,235,.10);}
/* FILTER */
.surat-filter-card{margin:0;padding:14px 18px 15px;border:0;border-bottom:1px solid #edf2f7;border-radius:0;background:#fff;box-shadow:none;}
.surat-filter-main{display:grid;grid-template-columns:minmax(0,1fr) auto minmax(270px,320px);gap:9px;align-items:center;}
.surat-search-wrapper{position:relative;min-width:0;}
.surat-search-icon{position:absolute;top:50%;left:12px;z-index:2;display:flex;align-items:center;justify-content:center;width:17px;height:17px;color:#64748b;transform:translateY(-50%);pointer-events:none;}
.surat-search-input,.surat-date-input{width:100%;height:42px;border:1px solid #d6e0ec;border-radius:10px;outline:none;background:#fff;color:#334155;font-size:11px;transition:border-color .15s ease,box-shadow .15s ease;}
.surat-search-input{padding:0 12px 0 38px;}
.surat-search-input::placeholder,.surat-date-input::placeholder{color:#94a3b8;}
.surat-search-input:hover,.surat-date-input:hover{border-color:#b8c5d6;}
.surat-search-input:focus,.surat-date-input:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.08);}
.surat-filter-actions{display:flex;align-items:center;justify-content:flex-start;gap:5px;min-width:0;}
.surat-filter-submit{display:inline-flex;align-items:center;justify-content:center;gap:5px;width:82px;height:42px;min-width:82px;padding:0 10px;border:1px solid #2563eb;border-radius:10px;background:#2563eb;color:#fff;font-size:10px;font-weight:700;cursor:pointer;box-shadow:0 5px 14px rgba(37,99,235,.14);transition:.15s ease;}
.surat-filter-submit:hover{border-color:#1d4ed8;background:#1d4ed8;box-shadow:0 7px 18px rgba(37,99,235,.18);}
.surat-filter-submit.has-filter{border-color:#2563eb;background:#2563eb;color:#fff;}
.surat-filter-reset{display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border:1px solid #d6e0ec;border-radius:9px;background:#fff;color:#64748b;text-decoration:none;transition:.15s ease;}
.surat-filter-reset:hover{border-color:#fecdd3;background:#fff1f2;color:#e11d48;}
.surat-date-wrapper{position:relative;min-width:0;}
.surat-date-field{position:relative;}
.surat-date-input{padding:0 38px 0 38px;cursor:pointer;}
.surat-date-left-icon{position:absolute;top:50%;left:12px;z-index:2;color:#64748b;transform:translateY(-50%);pointer-events:none;}
.surat-date-chevron{position:absolute;top:50%;right:11px;z-index:2;color:#64748b;transform:translateY(-50%);pointer-events:none;}
.surat-date-clear{position:absolute;top:50%;right:28px;z-index:3;display:none;align-items:center;justify-content:center;width:26px;height:26px;border:0;border-radius:7px;background:transparent;color:#94a3b8;cursor:pointer;transform:translateY(-50%);padding:0;}
.surat-date-clear.is-visible{display:flex;}
.surat-date-clear:hover{background:#fff1f2;color:#e11d48;}
.filter-row{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin-top:8px;}
.filter-dropdown{position:relative;min-width:0;}
.filter-dropdown-trigger{display:flex;align-items:center;width:100%;min-height:44px;gap:8px;padding:6px 9px;border:1px solid #d6e0ec;border-radius:10px;background:#fff;color:#334155;text-align:left;cursor:pointer;transition:.15s ease;}
.filter-dropdown-trigger:hover{border-color:#b8c5d6;background:#f8fafc;}
.filter-dropdown-trigger[aria-expanded="true"]{border-color:#3b82f6;background:#f8fbff;box-shadow:0 0 0 3px rgba(59,130,246,.08);}
.filter-dropdown-trigger.status-trigger[aria-expanded="true"]{border-color:#d97706;background:#fffcf0;box-shadow:0 0 0 3px rgba(217,119,6,.08);}
.filter-dropdown-trigger-content{display:flex;align-items:center;gap:8px;min-width:0;flex:1;}
.filter-dropdown-icon{display:flex;align-items:center;justify-content:center;width:28px;height:28px;flex:0 0 28px;border-radius:8px;background:#eff6ff;color:#2563eb;}
.filter-dropdown-icon.status{background:#fffbeb;color:#d97706;}
.filter-dropdown-text{min-width:0;flex:1;}
.filter-dropdown-title,.filter-dropdown-subtitle{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.filter-dropdown-title{color:#334155;font-size:10px;font-weight:800;}
.filter-dropdown-subtitle{margin-top:2px;color:#94a3b8;font-size:8px;}
.filter-dropdown-count{display:inline-flex;align-items:center;justify-content:center;min-width:54px;min-height:21px;padding:0 6px;border-radius:999px;font-size:7.5px;font-weight:800;white-space:nowrap;}
.filter-dropdown-count.category{background:#eff6ff;color:#2563eb;}
.filter-dropdown-count.status{background:#fffbeb;color:#b45309;}
.filter-dropdown-arrow{flex:0 0 auto;color:#94a3b8;transition:.15s ease;}
.filter-dropdown-trigger[aria-expanded="true"] .filter-dropdown-arrow{transform:rotate(180deg);color:#2563eb;}
.filter-dropdown-trigger.status-trigger[aria-expanded="true"] .filter-dropdown-arrow{color:#d97706;}
.filter-dropdown-menu{position:absolute;top:calc(100% + 7px);left:0;right:0;z-index:10020;display:none;overflow:hidden;border:1px solid #dbe4f0;border-radius:12px;background:#fff;box-shadow:0 20px 45px rgba(15,23,42,.14),0 5px 18px rgba(15,23,42,.08);}
.filter-dropdown.open .filter-dropdown-menu{display:block;}
.filter-dropdown-menu-header{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:9px 10px;border-bottom:1px solid #edf2f7;background:#f8fafc;}
.filter-dropdown-menu-title-wrap{min-width:0;}
.filter-dropdown-menu-title{display:block;color:#334155;font-size:9px;font-weight:800;}
.filter-dropdown-menu-description{display:block;margin-top:2px;color:#94a3b8;font-size:7px;}
.filter-dropdown-actions{display:inline-flex;align-items:center;gap:2px;flex:0 0 auto;}
.filter-dropdown-action{border:0;border-radius:6px;background:transparent;padding:4px 5px;font-size:7px;font-weight:800;cursor:pointer;}
.filter-dropdown-action.category{color:#2563eb}.filter-dropdown-action.status{color:#d97706}.filter-dropdown-action.clear{color:#64748b}
.filter-dropdown-action.category:hover{background:#eff6ff}.filter-dropdown-action.status:hover{background:#fffbeb}.filter-dropdown-action.clear:hover{background:#f1f5f9}
.filter-dropdown-divider{color:#cbd5e1;font-size:8px;}
.filter-dropdown-options{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:6px;max-height:240px;overflow-y:auto;padding:8px;}
.filter-dropdown-option{display:flex;align-items:center;min-width:0;min-height:36px;gap:6px;padding:6px 7px;border:1px solid #dbe3ed;border-radius:8px;background:#fff;cursor:pointer;transition:.15s ease;}
.filter-dropdown-option:hover{border-color:#93c5fd;background:#eff6ff;}
.filter-dropdown-option.status-option:hover{border-color:#fbbf24;background:#fffbeb;}
.filter-dropdown-option input{width:13px;height:13px;margin:0;cursor:pointer;accent-color:#2563eb;}
.filter-dropdown-option span{min-width:0;overflow:hidden;color:#475569;font-size:8px;font-weight:600;text-overflow:ellipsis;white-space:nowrap;}
.filter-dropdown-option:has(input:checked){border-color:#2563eb;background:#eff6ff}.filter-dropdown-option.status-option:has(input:checked){border-color:#d97706;background:#fffbeb}
.filter-dropdown-menu-footer{display:flex;align-items:center;justify-content:space-between;gap:6px;padding:6px 9px;border-top:1px solid #edf2f7;}
.filter-dropdown-footer-count,.filter-dropdown-footer-hint{font-size:7px;line-height:1.2;}
.filter-dropdown-footer-count{color:#64748b;font-weight:700}.filter-dropdown-footer-hint{color:#94a3b8;}
.filter-dropdown-empty{padding:18px 10px;text-align:center;color:#94a3b8;font-size:8px;}
/* SIDEBAR */
.surat-sidebar{display:flex;flex-direction:column;gap:12px;min-width:0;}
.surat-side-card-header{justify-content:flex-start;}
.surat-side-card-title{color:#172033;font-size:10px;font-weight:800;}
.surat-side-card-content{padding:14px;}
.surat-category-list{display:flex;flex-direction:column;}
.surat-category-row{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:9px 0;border-bottom:1px solid #f1f5f9;}
.surat-category-row:first-child{padding-top:0}.surat-category-row:last-child{border-bottom:0;padding-bottom:0;}
.surat-category-name{min-width:0;overflow:hidden;color:#475569;font-size:8.5px;font-weight:600;text-overflow:ellipsis;white-space:nowrap;}
.surat-category-count{display:inline-flex;align-items:center;justify-content:center;min-width:22px;height:21px;padding:0 6px;border-radius:999px;background:#eff6ff;color:#2563eb;font-size:8px;font-weight:800;}
.surat-side-empty{padding:8px 0 2px;color:#94a3b8;font-size:8.5px;}
.surat-donut{position:relative;display:flex;align-items:center;justify-content:center;width:148px;height:148px;margin:2px auto 14px;border-radius:999px;background:var(--donut);}
.surat-donut::after{content:'';position:absolute;inset:25px;border-radius:999px;background:#fff;box-shadow:0 0 0 1px rgba(226,232,240,.8);}
.surat-donut-center{position:relative;z-index:2;text-align:center}.surat-donut-number{color:#172033;font-size:25px;font-weight:800;line-height:1}.surat-donut-label{margin-top:4px;color:#94a3b8;font-size:8px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;}
.surat-legend{display:flex;flex-direction:column;gap:8px}.surat-legend-row{display:flex;align-items:center;justify-content:space-between;gap:8px}.surat-legend-name{display:flex;align-items:center;gap:7px;min-width:0;color:#64748b;font-size:8.5px;font-weight:600}.surat-legend-dot{width:8px;height:8px;flex:0 0 8px;border-radius:999px}.surat-legend-value{color:#334155;font-size:9px;font-weight:800;}
/* TABLE */
.archive-table-wrapper{overflow:hidden;background:#fff;}
.archive-table-scroll{overflow-x:auto;}
.archive-table{width:100%;table-layout:fixed;border-collapse:collapse;border-spacing:0;background:#fff;}
.archive-table thead{background:#f8fafc}.archive-table thead tr{border-bottom:1px solid #e2e8f0}
.archive-table thead th{padding:11px 12px;color:#64748b;font-size:8px;font-weight:800;letter-spacing:.04em;text-align:left;text-transform:uppercase;white-space:nowrap;}
.archive-table tbody tr{background:#fff;transition:background-color .15s ease}.archive-table tbody tr:nth-child(even){background:#fbfdff}.archive-table tbody tr:hover{background:#f8fbff}
.archive-table tbody td{padding:12px;border-bottom:1px solid #edf2f7;color:#475569;font-size:10px;line-height:1.4;vertical-align:middle;}
.archive-table tbody tr:last-child td{border-bottom:0}.archive-table tbody td:last-child{text-align:center}
.category-badge{
    display:inline-flex;
    align-items:center;
    gap:5px;
    min-height:25px;
    max-width:100%;
    padding:0 8px;
    overflow:hidden;
    border:1px solid transparent;
    border-radius:999px;
    font-size:8.5px;
    font-weight:700;
    line-height:1;
    white-space:nowrap;
    text-overflow:ellipsis;
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
.archive-table .cell-date{color:#334155;font-weight:700;white-space:nowrap}.archive-table .cell-sender{color:#334155}.archive-table .cell-subject{max-width:100%;overflow:hidden;color:#1e293b;font-weight:700;text-overflow:ellipsis;white-space:nowrap}.archive-table .cell-category{color:#64748b}.archive-table .cell-status{white-space:nowrap}
.archive-table .sender-badge{display:inline-block;max-width:100%;overflow:hidden;padding:5px 8px;border:0;border-radius:8px;background:#f8fafc;color:#334155;font-weight:700;text-overflow:ellipsis;white-space:nowrap;}
.archive-table .status-badge{display:inline-flex;align-items:center;gap:5px;border-width:0;border-radius:999px;padding:5px 8px;font-size:8px;font-weight:800;white-space:nowrap;}
.archive-table .action-cell{width:112px;white-space:nowrap}.archive-table th:nth-child(1),.archive-table td:nth-child(1){width:100px}.archive-table th:nth-child(2),.archive-table td:nth-child(2){width:170px}.archive-table th:nth-child(3),.archive-table td:nth-child(3){width:auto}.archive-table th:nth-child(4),.archive-table td:nth-child(4){width:126px}.archive-table th:nth-child(5),.archive-table td:nth-child(5){width:100px}.archive-table th:nth-child(6),.archive-table td:nth-child(6){width:112px}
.action-buttons{display:inline-flex;align-items:center;justify-content:center;gap:4px}.archive-table .action-button{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border:1px solid #e2e8f0;border-radius:9px;background:#fff;color:#64748b;transition:.15s ease}.archive-table .action-button:hover{border-color:#bfdbfe;background:#eff6ff;color:#2563eb}.archive-table .action-button.edit:hover{border-color:#fde68a;background:#fffbeb;color:#d97706}.archive-table .action-button.delete:hover{border-color:#fecdd3;background:#fff1f2;color:#e11d48}
.archive-table-empty{padding:44px 16px!important;text-align:center;}
.archive-pagination{padding:11px 18px;border-top:1px solid #edf2f7}
/* DATE PICKER */
.custom-date-picker,.custom-picker-panel{border:1px solid #cbd5e1;background:#fff;box-shadow:0 24px 70px rgba(15,23,42,.18),0 8px 25px rgba(15,23,42,.08);}
.custom-date-picker{position:fixed;z-index:999999;width:720px;max-width:calc(100vw - 20px);overflow:hidden;border-radius:14px}.custom-date-picker.hidden,.custom-picker-panel.hidden{display:none!important}
.custom-date-picker-header{display:flex;align-items:center;justify-content:space-between;padding:11px 14px;border-bottom:1px solid #e2e8f0}.custom-date-picker-title{color:#334155;font-size:12px;font-weight:800}.custom-date-picker-close,.custom-picker-panel-close{display:flex;align-items:center;justify-content:center;border:0;background:#f8fafc;color:#64748b;cursor:pointer}.custom-date-picker-close{width:30px;height:30px;border-radius:8px;font-size:18px}.custom-date-picker-close:hover,.custom-picker-panel-close:hover{background:#f1f5f9;color:#ef4444}
.custom-date-calendars,.custom-date-picker-calendars{display:grid;grid-template-columns:repeat(2,minmax(0,1fr))}.custom-calendar{padding:13px 15px 11px}.custom-calendar+.custom-calendar{border-left:1px solid #e2e8f0}.custom-calendar-head{display:flex;align-items:center;justify-content:space-between;gap:5px;min-height:38px;margin-bottom:5px}.custom-calendar-heading{display:flex;align-items:center;justify-content:center;flex:1;gap:4px}.custom-calendar-nav{display:flex;align-items:center;justify-content:center;width:32px;height:32px;border:0;border-radius:8px;background:transparent;color:#64748b;font-size:21px;cursor:pointer}.custom-calendar-nav:hover{background:#eff6ff;color:#2563eb}
.custom-calendar-month-button,.custom-calendar-year-button{display:inline-flex;align-items:center;justify-content:center;gap:5px;min-height:32px;padding:6px 9px;border:1px solid #dbe3ed;border-radius:8px;background:#f8fafc;color:#334155;font-size:11px;font-weight:800;cursor:pointer}.custom-calendar-month-button:hover,.custom-calendar-year-button:hover{border-color:#93c5fd;background:#eff6ff;color:#2563eb}.custom-calendar-month-button::after,.custom-calendar-year-button::after{content:'';width:6px;height:6px;margin-top:-3px;border-right:1.5px solid currentColor;border-bottom:1.5px solid currentColor;transform:rotate(45deg)}
.custom-calendar-weekdays,.custom-calendar-days{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:2px}.custom-calendar-weekday{display:flex;align-items:center;justify-content:center;height:26px;color:#94a3b8;font-size:9px;font-weight:800}.custom-calendar-day{display:flex;align-items:center;justify-content:center;height:34px;border:0;border-radius:7px;background:transparent;color:#475569;font-size:10px;font-weight:600;cursor:pointer}.custom-calendar-day:hover{background:#eff6ff;color:#2563eb}.custom-calendar-day.other-month{color:#cbd5e1}.custom-calendar-day.today{box-shadow:inset 0 0 0 1px #93c5fd;color:#2563eb}.custom-calendar-day.in-range{background:#eff6ff;color:#2563eb}.custom-calendar-day.range-start,.custom-calendar-day.range-end{background:#2563eb;color:#fff}.custom-calendar-day.range-start{border-radius:999px 0 0 999px}.custom-calendar-day.range-end{border-radius:0 999px 999px 0}.custom-calendar-day.range-start.range-end{border-radius:999px}
.custom-date-picker-footer{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 12px;border-top:1px solid #e2e8f0}.custom-date-picker-selected{min-width:0;color:#64748b;font-size:10px;font-weight:700}.custom-date-picker-actions{display:flex;align-items:center;gap:6px}.custom-date-picker-button{height:34px;border:1px solid #dbe3ed;border-radius:8px;padding:0 12px;background:#f8fafc;color:#475569;font-size:10px;font-weight:700;cursor:pointer}.custom-date-picker-button.apply{border-color:#2563eb;background:#2563eb;color:#fff}
.custom-picker-panel{position:fixed;z-index:1000000;width:310px;max-width:calc(100vw - 20px);padding:12px;border-radius:12px}.custom-picker-panel-header{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:10px;padding-bottom:9px;border-bottom:1px solid #e2e8f0}.custom-picker-panel-title{flex:1;color:#334155;font-size:12px;font-weight:800;text-align:center}.custom-picker-panel-close{width:28px;height:28px;border-radius:7px;font-size:17px}.custom-picker-month-grid,.custom-picker-year-grid,.custom-picker-grid{display:grid;gap:7px}.custom-picker-month-grid{grid-template-columns:repeat(3,1fr)}.custom-picker-year-grid{grid-template-columns:repeat(4,1fr)}.custom-picker-option{display:flex;align-items:center;justify-content:center;min-height:40px;padding:0 6px;border:1px solid #dbe3ed;border-radius:9px;background:#fff;color:#475569;font-size:10px;font-weight:700;cursor:pointer}.custom-picker-option:hover{border-color:#93c5fd;background:#eff6ff;color:#2563eb}.custom-picker-option.active{border-color:#2563eb;background:#2563eb;color:#fff}.custom-picker-option.current:not(.active){box-shadow:inset 0 0 0 1px #93c5fd}
/* RESPONSIVE */
@media(max-width:1100px){
.surat-keluar-summary{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
.surat-workspace{grid-template-columns:minmax(0,1fr)}
.surat-sidebar{display:grid;grid-template-columns:repeat(2,minmax(0,1fr))}
.surat-filter-main{grid-template-columns:minmax(0,1fr) auto}
.surat-date-wrapper{grid-column:1/-1}
}
@media(max-width:767px){
.surat-page-header{align-items:flex-start;flex-direction:column;gap:12px}
.surat-page-header-left{width:100%}
.surat-header-actions{width:100%;display:grid;grid-template-columns:repeat(3,minmax(0,1fr))}
.surat-header-actions .surat-export-button,.surat-header-actions .surat-create-button{width:100%;padding:0 8px;font-size:9px}
.surat-page-title{font-size:24px}
.surat-page-description{font-size:11px}
.surat-keluar-summary{grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}
.surat-keluar-summary-card{min-height:86px;padding:12px;gap:8px}
.surat-keluar-summary-icon{width:37px;height:37px;flex-basis:37px;border-radius:10px}
.surat-keluar-summary-value{font-size:20px}
.surat-keluar-summary-note{font-size:8px}
.surat-sidebar{display:flex;flex-direction:column}
.surat-filter-card{padding:12px}
.surat-filter-main{grid-template-columns:minmax(0,1fr) auto;gap:8px}
.surat-filter-actions{width:auto}
.surat-filter-submit{width:82px;min-width:82px}
.surat-filter-reset{width:34px;flex:0 0 34px}
.surat-date-wrapper{grid-column:1/-1}
.filter-row{grid-template-columns:repeat(2,minmax(0,1fr));gap:7px}
.filter-dropdown-menu{position:fixed;top:50%;left:50%;right:auto;width:calc(100vw - 24px);max-width:430px;max-height:80vh;transform:translate(-50%,-50%)}
.filter-dropdown-options{grid-template-columns:repeat(3,minmax(0,1fr));max-height:calc(80vh - 145px)}
.filter-dropdown-option span{font-size:8px}
.archive-table-scroll{overflow:visible}
.archive-table{min-width:0;table-layout:auto}
.archive-table thead{display:none}
.archive-table,.archive-table tbody,.archive-table tr,.archive-table td{display:block;width:100%}
.archive-table tbody tr{margin:0;padding:10px 12px;border-bottom:1px solid #edf2f7;background:#fff}
.archive-table tbody td{display:grid;grid-template-columns:82px minmax(0,1fr);gap:10px;align-items:center;padding:6px 0;border:0;font-size:10px}
.archive-table tbody td::before{content:attr(data-label);color:#94a3b8;font-size:8px;font-weight:800;letter-spacing:.03em;text-transform:uppercase}
.archive-table tbody td:last-child{display:flex;justify-content:space-between;align-items:center;padding-top:8px;margin-top:4px;border-top:1px solid #f1f5f9;text-align:left}
.archive-table tbody td:last-child::before{content:attr(data-label)}
.archive-table .action-buttons{margin-left:auto}
.archive-table .sender-badge{max-width:none}
.archive-table .cell-subject{white-space:normal}
.custom-date-picker{top:50%;left:50%;width:calc(100vw - 16px);max-height:calc(100vh - 16px);overflow-y:auto;transform:translate(-50%,-50%)}
.custom-date-calendars,.custom-date-picker-calendars{grid-template-columns:1fr}
.custom-calendar+.custom-calendar{border-top:1px solid #e2e8f0;border-left:0}
.custom-calendar-day{height:38px}
.custom-picker-panel{top:50%!important;left:50%!important;width:calc(100vw - 24px);transform:translate(-50%,-50%)}
body.date-picker-lock{overflow:hidden}
}
@media(max-width:480px){
.surat-keluar-summary{grid-template-columns:1fr}
.surat-header-actions{grid-template-columns:1fr 1fr}
.surat-header-actions .surat-create-button{grid-column:1/-1}
.surat-filter-main{grid-template-columns:1fr auto}
.surat-filter-actions{justify-content:flex-end}
.surat-filter-submit{width:78px;min-width:78px;height:40px;font-size:9px}
.surat-filter-reset{width:32px;height:32px}
.filter-row{grid-template-columns:1fr}
.filter-dropdown-options{grid-template-columns:repeat(2,minmax(0,1fr))}
.filter-dropdown-menu-header{align-items:flex-start}
.filter-dropdown-actions{margin-top:1px}
.filter-dropdown-footer-count,.filter-dropdown-footer-hint{font-size:6.5px}
}</style>
@endpush
<div class="surat-page space-y-4">
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
                    Surat Keluar
                </h1>
                <p class="surat-page-description">
                    Kelola dan pantau seluruh arsip surat keluar organisasi Anda.
                </p>
            </div>
        </div>
        <div class="surat-header-actions">
            <a
                href="{{ route('export.surat-keluar.excel', $exportFilters) }}"
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
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A2 2 0 0120 6.707V19a2 2 0 01-2 2z"
                    />
                </svg>
                Excel
            </a>
            <a
                href="{{ route('export.surat-keluar.pdf', $exportFilters) }}"
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
                    href="{{ route('surat-keluar.create') }}"
                    class="surat-create-button"
                    title="Tambah Surat Keluar"
                    aria-label="Tambah Surat Keluar"
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
                    Surat Keluar
                </a>
            @endif
        </div>
    </div>
    {{-- =====================================================
         SCORECARD
    ====================================================== --}}
    <div class="surat-keluar-summary">
        <div class="surat-keluar-summary-card">
            <div class="surat-keluar-summary-icon blue">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7h8M8 11h8M8 15h5M6 3h9l4 4v14H6a2 2 0 01-2-2V5a2 2 0 012-2z"/>
                </svg>
            </div>
            <div class="surat-keluar-summary-content">
                <p class="surat-keluar-summary-label">Total Surat Keluar</p>
                <p class="surat-keluar-summary-value">{{ number_format($totalSuratKeluar, 0, ',', '.') }}</p>
                <div class="surat-keluar-summary-note">Seluruh surat keluar</div>
            </div>
        </div>
        <div class="surat-keluar-summary-card">
            <div class="surat-keluar-summary-icon slate">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 3h9l3 3v15H6a2 2 0 01-2-2V5a2 2 0 012-2z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 3v4h4"/>
                </svg>
            </div>
            <div class="surat-keluar-summary-content">
                <p class="surat-keluar-summary-label">Draf</p>
                <p class="surat-keluar-summary-value">{{ number_format($suratDraft, 0, ',', '.') }}</p>
                <div class="surat-keluar-summary-note">Surat yang masih berupa draf</div>
            </div>
        </div>
        <div class="surat-keluar-summary-card">
            <div class="surat-keluar-summary-icon amber">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v6l4 2"/>
                    <circle cx="12" cy="12" r="9" stroke-width="1.8"/>
                </svg>
            </div>
            <div class="surat-keluar-summary-content">
                <p class="surat-keluar-summary-label">Diproses</p>
                <p class="surat-keluar-summary-value">{{ number_format($suratDiproses, 0, ',', '.') }}</p>
                <div class="surat-keluar-summary-note">Surat yang sedang diproses</div>
            </div>
        </div>
        <div class="surat-keluar-summary-card">
            <div class="surat-keluar-summary-icon green">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M22 2L11 13"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M22 2l-7 20-4-9-9-4 20-7z"/>
                </svg>
            </div>
            <div class="surat-keluar-summary-content">
                <p class="surat-keluar-summary-label">Dikirim</p>
                <p class="surat-keluar-summary-value">{{ number_format($suratDikirim, 0, ',', '.') }}</p>
                <div class="surat-keluar-summary-note">Surat yang telah dikirim</div>
            </div>
        </div>
    </div>
    <div class="surat-workspace">
        <div class="surat-main-card">
            <div class="surat-main-card-header">
                <div class="surat-main-card-title-wrap">
                    <div class="surat-main-card-icon">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 4h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 9h8M8 13h8M8 17h5"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h2 class="surat-main-card-title">Daftar Surat Keluar</h2>
                        <p class="surat-main-card-subtitle">Menampilkan surat keluar sesuai filter yang dipilih.</p>
                    </div>
                </div>
                <a
                    href="{{ $sortUrl }}"
                    class="surat-sort-badge"
                    title="{{ $sortTitle }}"
                    aria-label="{{ $sortTitle }}"
                >
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 6h12M8 12h8M8 18h5"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6v12m0 0l-2-2m2 2l2-2"/>
                    </svg>
                    {{ $sortLabel }}
                </a>
            </div>
    {{-- =====================================================
         FILTER
    ====================================================== --}}
    <div class="surat-filter-card">
        <form id="filterForm" method="GET" action="{{ route('surat-keluar.index') }}">
            <input type="hidden" name="sort" value="{{ $sortOrder }}">
            {{-- SEARCH + FILTER + DATE --}}
            <div class="surat-filter-main">
                <div class="surat-search-wrapper">
                    <div class="surat-search-icon" aria-hidden="true">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0a7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="search" id="search" name="search" value="{{ request('search') }}" placeholder="Cari perihal, nomor surat, atau tujuan..." autocomplete="off" class="surat-search-input">
                </div>
                <div class="surat-filter-actions">
                    <button type="submit" class="surat-filter-submit {{ $hasFilters ? 'has-filter' : '' }}" title="Terapkan filter">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707v3.414L9 14V9.707a1 1 0 00-.293-.707L3.293 6.293A1 1 0 013 5.586V4z" />
                        </svg>
                        <span>Filter</span>
                    </button>
                    @if($hasFilters)
                        <a href="{{ route('surat-keluar.index') }}" title="Reset Filter" aria-label="Reset Filter" class="surat-filter-reset">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </a>
                    @endif
                </div>
                <div class="surat-date-wrapper">
                    <div class="surat-date-field">
                        <div class="surat-date-left-icon" aria-hidden="true">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <input type="text" id="date-range" value="{{ $visibleDateRange }}" readonly autocomplete="off" placeholder="Rentang tanggal" class="surat-date-input" aria-label="Pilih rentang tanggal">
                        <button type="button" id="clearDateRange" class="surat-date-clear {{ $visibleDateRange ? 'is-visible' : '' }}" title="Hapus rentang tanggal" aria-label="Hapus rentang tanggal">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <input type="hidden" name="dari_tanggal" id="dari_tanggal" value="{{ $dariTanggal }}">
                    <input type="hidden" name="sampai_tanggal" id="sampai_tanggal" value="{{ $sampaiTanggal }}">
                </div>
            </div>
        {{-- KATEGORI + STATUS --}}
            <div class="filter-row">
                {{-- KATEGORI --}}
                <div
                    class="filter-dropdown"
                    id="kategoriDropdown"
                >
                    <button
                        type="button"
                        id="kategoriDropdownButton"
                        class="filter-dropdown-trigger"
                        aria-expanded="false"
                        aria-controls="kategoriDropdownMenu"
                    >
                        <span class="filter-dropdown-icon">
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16"
                                />
                            </svg>
                        </span>
                        <span class="filter-dropdown-trigger-content">
                            <span class="filter-dropdown-text">
                                <span class="filter-dropdown-title">
                                    Kategori Surat
                                </span>
                                <span
                                    class="filter-dropdown-subtitle"
                                    id="kategoriSummary"
                                >
                                    Semua kategori
                                </span>
                            </span>
                            <span
                                class="filter-dropdown-count category"
                                id="kategoriCount"
                            >
                                {{ count($selectedKategori) }} dipilih
                            </span>
                        </span>
                        <svg
                            class="filter-dropdown-arrow h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 9l6 6l6-6"
                            />
                        </svg>
                    </button>
                    <div
                        id="kategoriDropdownMenu"
                        class="filter-dropdown-menu"
                    >
                        <div class="filter-dropdown-menu-header">
                            <div class="filter-dropdown-menu-title-wrap">
                                <span class="filter-dropdown-menu-title">
                                    Pilih Kategori
                                </span>
                                <span class="filter-dropdown-menu-description">
                                    Checkbox dapat dipilih lebih dari satu
                                </span>
                            </div>
                            <div class="filter-dropdown-actions">
                                <button
                                    type="button"
                                    id="selectAllKategori"
                                    class="filter-dropdown-action category"
                                >
                                    Pilih Semua
                                </button>
                                <span class="filter-dropdown-divider">
                                    |
                                </span>
                                <button
                                    type="button"
                                    id="clearAllKategori"
                                    class="filter-dropdown-action clear"
                                >
                                    Batalkan
                                </button>
                            </div>
                        </div>
                        @if(
                            isset($kategoris) &&
                            $kategoris->count()
                        )
                            <div class="filter-dropdown-options">
                                @foreach($kategoris as $kategori)
                                    <label class="filter-dropdown-option">
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
                                            title="{{ $kategori->nama_kategori }}"
                                        >
                                            {{ $kategori->nama_kategori }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        @else
                            <div class="filter-dropdown-empty">
                                Belum ada kategori surat.
                            </div>
                        @endif
                        <div class="filter-dropdown-menu-footer">
                            <span
                                id="kategoriFooterCount"
                                class="filter-dropdown-footer-count"
                            >
                                {{ count($selectedKategori) }}
                                kategori dipilih
                            </span>
                            <span class="filter-dropdown-footer-hint">
                                Klik Filter untuk menerapkan
                            </span>
                        </div>
                    </div>
                </div>
                {{-- STATUS --}}
                <div
                    class="filter-dropdown"
                    id="statusDropdown"
                >
                    <button
                        type="button"
                        id="statusDropdownButton"
                        class="filter-dropdown-trigger status-trigger"
                        aria-expanded="false"
                        aria-controls="statusDropdownMenu"
                    >
                        <span class="filter-dropdown-icon status">
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12l2 2l4-4m6 2a9 9 0 11-18 0a9 9 0 0118 0z"
                                />
                            </svg>
                        </span>
                        <span class="filter-dropdown-trigger-content">
                            <span class="filter-dropdown-text">
                                <span class="filter-dropdown-title">
                                    Status Surat
                                </span>
                                <span
                                    class="filter-dropdown-subtitle"
                                    id="statusSummary"
                                >
                                    Semua status
                                </span>
                            </span>
                            <span
                                class="filter-dropdown-count status"
                                id="statusCount"
                            >
                                {{ count($selectedStatus) }} dipilih
                            </span>
                        </span>
                        <svg
                            class="filter-dropdown-arrow h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 9l6 6l6-6"
                            />
                        </svg>
                    </button>
                    <div
                        id="statusDropdownMenu"
                        class="filter-dropdown-menu"
                    >
                        <div class="filter-dropdown-menu-header">
                            <div class="filter-dropdown-menu-title-wrap">
                                <span class="filter-dropdown-menu-title">
                                    Pilih Status
                                </span>
                                <span class="filter-dropdown-menu-description">
                                    Checkbox dapat dipilih lebih dari satu
                                </span>
                            </div>
                            <div class="filter-dropdown-actions">
                                <button
                                    type="button"
                                    id="selectAllStatus"
                                    class="filter-dropdown-action status"
                                >
                                    Pilih Semua
                                </button>
                                <span class="filter-dropdown-divider">
                                    |
                                </span>
                                <button
                                    type="button"
                                    id="clearAllStatus"
                                    class="filter-dropdown-action clear"
                                >
                                    Batalkan
                                </button>
                            </div>
                        </div>
                        <div class="filter-dropdown-options">
                            @foreach(
                                $statusOptions
                                as $value => $label
                            )
                                <label class="filter-dropdown-option status-option">
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
                                    <span>
                                        {{ $label }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <div class="filter-dropdown-menu-footer">
                            <span
                                id="statusFooterCount"
                                class="filter-dropdown-footer-count"
                            >
                                {{ count($selectedStatus) }}
                                status dipilih
                            </span>
                            <span class="filter-dropdown-footer-hint">
                                Klik Filter untuk menerapkan
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    {{-- =====================================================
         TABLE
    ====================================================== --}}
    <div class="archive-table-wrapper">
        <div class="archive-table-scroll">
            <table class="archive-table">
                <thead>
                    <tr>
                        <th>
                            Tanggal Keluar
                        </th>
                        <th>
                            Tujuan Surat
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
                        ($suratKeluars ?? collect())
                        as $s
                    )
                        @php
                            /*
                            |------------------------------------------------------
                            | STATUS
                            |------------------------------------------------------
                            */
                            $status = strtolower(
                                trim(
                                    (string) (
                                        $s->status ??
                                        'draft'
                                    )
                                )
                            );
                            if ($status === 'draf') {
                                $status = 'draft';
                            }
                            $statusLabel =
                                $statusOptions[$status]
                                ??
                                ucfirst($status);
                            $badgeClass =
                                $statusBadgeClasses[$status]
                                ??
                                'bg-slate-100 text-slate-600 border-slate-200';
                            /*
                            |------------------------------------------------------
                            | TUJUAN
                            |------------------------------------------------------
                            */
                            $tujuan =
                                trim(
                                    (string) (
                                        $s->tujuan_surat ??
                                        $s->pengirim ??
                                        ''
                                    )
                                );
                            $tujuan =
                                $tujuan !== ''
                                    ? $tujuan
                                    : '-';
                            /*
                            |------------------------------------------------------
                            | TANGGAL
                            |------------------------------------------------------
                            */
                            $tanggalRaw =
                                $s->tanggal_keluar
                                ??
                                $s->tanggal_surat
                                ??
                                null;
                            $tanggalKeluar = '-';
                            if ($tanggalRaw) {
                                try {
                                    $tanggalKeluar =
                                        \Illuminate\Support\Carbon::parse(
                                            $tanggalRaw
                                        )->format(
                                            'd/m/Y'
                                        );
                                } catch (\Throwable $e) {
                                    $tanggalKeluar = '-';
                                }
                            }
                            /*
                            |------------------------------------------------------
                            | PERIHAL
                            |------------------------------------------------------
                            */
                            $perihal =
                                trim(
                                    (string) (
                                        $s->perihal ??
                                        ''
                                    )
                                );
                            $perihal =
                                $perihal !== ''
                                    ? $perihal
                                    : '-';
                            /*
                            |------------------------------------------------------
                            | KATEGORI
                            |------------------------------------------------------
                            */
                            $kategoriNama =
                                $s->kategori?->nama_kategori
                                ??
                                '-';

                            $kategoriSifat = strtolower(
                                trim(
                                    (string) (
                                        $s->kategori?->sifat
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
                            <td class="cell-date" data-label="Tanggal Keluar">
                                {{ $tanggalKeluar }}
                            </td>
                            <td class="cell-sender" data-label="Tujuan Surat">
                                <span
                                    class="sender-badge"
                                    title="{{ $tujuan }}"
                                >
                                    {{ $tujuan }}
                                </span>
                            </td>
                            <td
                                data-label="Perihal"
                                class="cell-subject max-w-xs truncate"
                                title="{{ $perihal }}"
                            >
                                {{ $perihal }}
                            </td>
                            <td class="cell-category" data-label="Kategori">
                                <span
                                    class="category-badge {{ $categoryClass }}"
                                    title="{{ $kategoriNama }}"
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
                                    {{ $kategoriNama }}
                                </span>
                            </td>
                            <td class="cell-status" data-label="Status">
                                <span
                                    class="status-badge {{ $badgeClass }}"
                                >
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td class="action-cell" data-label="Aksi">
                                <div class="action-buttons">
                                    {{-- DETAIL --}}
                                    <a
                                        href="{{ route('surat-keluar.show', $s) }}"
                                        class="action-button detail"
                                        title="Lihat Detail"
                                        aria-label="Lihat detail surat"
                                    >
                                        <svg
                                            class="h-4 w-4"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
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
                                    {{-- EDIT + DELETE --}}
                                    @if($canManage)
                                        <a
                                            href="{{ route('surat-keluar.edit', $s) }}"
                                            class="action-button edit"
                                            title="Ubah Data"
                                            aria-label="Ubah data surat"
                                        >
                                            <svg
                                                class="h-4 w-4"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
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
                                                    d="M18.5 2.5a2.121 2.121 0 013 3L11.828 15H9v-2.828l9.5-9.5z"
                                                />
                                            </svg>
                                        </a>
                                        <form
                                            action="{{ route('surat-keluar.destroy', $s) }}"
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
                                                        d="M4 7h16m-5-3H9a1 1 0 01-1 1v2h8V5a1 1 0 01-1-1z"
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
                                <div class="flex flex-col items-center justify-center">
                                    <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                        <svg
                                            class="h-6 w-6"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-1.414 0l-2.414-2.414A1 1 0 006.586 13H4"
                                            />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-semibold text-slate-700 sm:text-base">
                                        Belum ada data surat keluar
                                    </p>
                                    <p class="mt-0.5 max-w-md px-4 text-center text-[11px] text-slate-400 sm:text-xs">
                                        @if($hasFilters)
                                            Tidak ada surat yang sesuai dengan filter yang digunakan.
                                        @else
                                            Belum ada data surat keluar yang tersimpan.
                                        @endif
                                    </p>
                                    @if($hasFilters)
                                        <a
                                            href="{{ route('surat-keluar.index') }}"
                                            class="mt-3 inline-flex items-center rounded-lg bg-slate-900 px-3 py-2 text-[11px] font-semibold text-white transition hover:bg-slate-800"
                                        >
                                            Reset Filter
                                        </a>
                                    @elseif($canManage)
                                        <a
                                            href="{{ route('surat-keluar.create') }}"
                                            class="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-2 text-[11px] font-semibold text-white transition hover:bg-blue-700"
                                        >
                                            <svg
                                                class="h-3.5 w-3.5"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M12 4v16m8-8H4"
                                                />
                                            </svg>
                                            Tambah Surat Keluar
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{-- =====================================================
             PAGINATION
        ====================================================== --}}
        @if(
            isset($suratKeluars) &&
            method_exists(
                $suratKeluars,
                'hasPages'
            ) &&
            $suratKeluars->hasPages()
        )
            <div class="border-t border-slate-200 px-4 py-3 sm:px-6 sm:py-4">
                {{ $suratKeluars->withQueryString()->links() }}
            </div>
        @endif
    </div>
        </div>
        <aside class="surat-sidebar">
            <div class="surat-side-card">
                <div class="surat-side-card-header">
                    <div class="surat-side-card-icon">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h10"/>
                        </svg>
                    </div>
                    <div class="surat-side-card-title">Kategori Surat</div>
                </div>
                <div class="surat-side-card-content">
                    @if($categorySummary->isNotEmpty())
                        <div class="surat-category-list">
                            @foreach($categorySummary as $name => $jumlah)
                                <div class="surat-category-row">
                                    <span class="surat-category-name" title="{{ $name }}">{{ $name }}</span>
                                    <span class="surat-category-count">{{ $jumlah }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="surat-side-empty">Belum ada kategori surat.</div>
                    @endif
                </div>
            </div>
            <div class="surat-side-card">
                <div class="surat-side-card-header">
                    <div class="surat-side-card-icon">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 17V7l5-4 5 4v10l-5 4-5-4z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 7l5-3 1 1v12l-5 3-1-1V7z"/>
                        </svg>
                    </div>
                    <div class="surat-side-card-title">Ringkasan Status</div>
                </div>
                <div class="surat-side-card-content">
                    <div class="surat-donut" style="--donut:{{ $statusChartGradient }};">
                        <div class="surat-donut-center">
                            <div class="surat-donut-number">{{ number_format($statusChartTotal, 0, ',', '.') }}</div>
                            <div class="surat-donut-label">Surat</div>
                        </div>
                    </div>
                    <div class="surat-legend">
                        @foreach($statusChartData as $label => $item)
                            <div class="surat-legend-row">
                                <span class="surat-legend-name">
                                    <span class="surat-legend-dot" style="background:{{ $item['color'] }};"></span>
                                    {{ $label }}
                                </span>
                                <span class="surat-legend-value">{{ number_format($item['value'], 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>
{{-- =========================================================
     DATE PICKER
========================================================= --}}
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
        class="custom-date-calendars custom-date-picker-calendars"
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
{{-- =========================================================
     MONTH / YEAR PANEL
========================================================= --}}
<div
    id="customPickerPanel"
    class="custom-picker-panel hidden"
></div>
@push('scripts')
<script>
(function () {
    'use strict';
    /* =========================================================
       CONSTANT
    ========================================================= */
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
        new Date().getFullYear() + 20;
    /* =========================================================
       ELEMENT
    ========================================================= */
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
    const clearDateButton =
        document.getElementById(
            'clearDateRange'
        );
    const datePicker =
        document.getElementById(
            'customDatePicker'
        );
    const calendars =
        document.getElementById(
            'customDateCalendars'
        );
    const pickerPanel =
        document.getElementById(
            'customPickerPanel'
        );
    const closeDatePickerButton =
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
    /* =========================================================
       STATE
    ========================================================= */
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
            ? cloneDate(selectedStart)
            : null;
    let tempEnd =
        selectedEnd
            ? cloneDate(selectedEnd)
            : null;
    let viewMonth =
        selectedStart
            ? new Date(
                selectedStart.getFullYear(),
                selectedStart.getMonth(),
                1
            )
            : new Date();
    let activePanelSide =
        'left';
    let activePanelYear =
        new Date().getFullYear();
    viewMonth.setDate(1);
    /* =========================================================
       HELPER
    ========================================================= */
    function cloneDate(date) {
        return new Date(
            date.getFullYear(),
            date.getMonth(),
            date.getDate()
        );
    }
    function parseDate(value) {
        if (!value) {
            return null;
        }
        const match =
            String(value).match(
                /^(\d{4})-(\d{2})-(\d{2})$/
            );
        if (!match) {
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
        return (
            date.getFullYear() === year &&
            date.getMonth() === month &&
            date.getDate() === day
        )
            ? date
            : null;
    }
    function pad(value) {
        return String(
            value
        ).padStart(
            2,
            '0'
        );
    }
    function toISO(date) {
        if (!date) {
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
    function formatDate(date) {
        if (!date) {
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
    function sameDate(a, b) {
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
    function addMonths(date, amount) {
        return new Date(
            date.getFullYear(),
            date.getMonth() + amount,
            1
        );
    }
    function isBetween(date, start, end) {
        if (
            !date ||
            !start ||
            !end
        ) {
            return false;
        }
        const value =
            toISO(date);
        return (
            value > toISO(start) &&
            value < toISO(end)
        );
    }
    /* =========================================================
       CALENDAR RENDER
    ========================================================= */
    function renderCalendars() {
        if (!calendars) {
            return;
        }
        calendars.innerHTML = '';
        const leftDate =
            new Date(
                viewMonth.getFullYear(),
                viewMonth.getMonth(),
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
            positionDatePicker
        );
    }
    function createCalendar(date, side) {
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
        const heading =
            document.createElement(
                'div'
            );
        heading.className =
            'custom-calendar-heading';
        const monthButton =
            document.createElement(
                'button'
            );
        monthButton.type =
            'button';
        monthButton.className =
            'custom-calendar-month-button';
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
            'custom-calendar-year-button';
        yearButton.textContent =
            String(
                date.getFullYear()
            );
        heading.append(
            monthButton,
            yearButton
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
        header.append(
            previous,
            heading,
            next
        );
        calendar.appendChild(
            header
        );
        monthButton.addEventListener(
            'click',
            function (event) {
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
            function (event) {
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
            function (event) {
                event.preventDefault();
                event.stopPropagation();
                viewMonth =
                    addMonths(
                        viewMonth,
                        -1
                    );
                renderCalendars();
            }
        );
        next.addEventListener(
            'click',
            function (event) {
                event.preventDefault();
                event.stopPropagation();
                viewMonth =
                    addMonths(
                        viewMonth,
                        1
                    );
                renderCalendars();
            }
        );
        const weekdays =
            document.createElement(
                'div'
            );
        weekdays.className =
            'custom-calendar-weekdays';
        WEEKDAYS.forEach(
            function (day) {
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
        for (
            let index = 0;
            index < 42;
            index++
        ) {
            let dayNumber;
            let cellDate;
            let otherMonth = false;
            if (
                index < mondayOffset
            ) {
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
                otherMonth = true;
            }
            else if (
                index >=
                mondayOffset +
                daysInMonth
            ) {
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
                otherMonth = true;
            }
            else {
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
                String(dayNumber);
            if (otherMonth) {
                button.classList.add(
                    'other-month'
                );
            }
            if (
                sameDate(
                    cellDate,
                    new Date()
                )
            ) {
                button.classList.add(
                    'today'
                );
            }
            if (
                tempStart &&
                tempEnd &&
                isBetween(
                    cellDate,
                    tempStart,
                    tempEnd
                )
            ) {
                button.classList.add(
                    'in-range'
                );
            }
            if (
                tempStart &&
                sameDate(
                    cellDate,
                    tempStart
                )
            ) {
                button.classList.add(
                    'range-start'
                );
            }
            if (
                tempEnd &&
                sameDate(
                    cellDate,
                    tempEnd
                )
            ) {
                button.classList.add(
                    'range-end'
                );
            }
            button.addEventListener(
                'click',
                function (event) {
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
    function selectDate(date) {
        const chosen =
            cloneDate(date);
        if (
            !tempStart ||
            tempEnd
        ) {
            tempStart =
                chosen;
            tempEnd =
                null;
        }
        else if (
            toISO(chosen) <
            toISO(tempStart)
        ) {
            tempEnd =
                cloneDate(
                    tempStart
                );
            tempStart =
                chosen;
        }
        else {
            tempEnd =
                chosen;
        }
        renderCalendars();
    }
    function updateSelectedLabel() {
        if (!selectedLabel) {
            return;
        }
        if (
            tempStart &&
            tempEnd
        ) {
            selectedLabel.textContent =
                formatDate(
                    tempStart
                )
                +
                ' - '
                +
                formatDate(
                    tempEnd
                );
        }
        else if (tempStart) {
            selectedLabel.textContent =
                formatDate(
                    tempStart
                )
                +
                ' - pilih tanggal akhir';
        }
        else {
            selectedLabel.textContent =
                'Pilih tanggal awal';
        }
    }
    /* =========================================================
       DATE PICKER POSITION
    ========================================================= */
    function positionDatePicker() {
        if (
            !datePicker ||
            !dateInput ||
            window.innerWidth <= 767
        ) {
            return;
        }
        const rect =
            dateInput.getBoundingClientRect();
        const width =
            datePicker.offsetWidth ||
            720;
        const height =
            datePicker.offsetHeight ||
            500;
        let left =
            rect.left +
            rect.width / 2 -
            width / 2;
        let top =
            rect.bottom +
            8;
        if (
            left + width >
            window.innerWidth - 10
        ) {
            left =
                window.innerWidth -
                width -
                10;
        }
        if (left < 10) {
            left = 10;
        }
        if (
            top + height >
            window.innerHeight - 10
        ) {
            top =
                rect.top -
                height -
                8;
        }
        if (top < 10) {
            top = 10;
        }
        datePicker.style.left =
            left + 'px';
        datePicker.style.top =
            top + 'px';
    }
    /* =========================================================
       SHOW / HIDE
    ========================================================= */
    function showDatePicker() {
        if (!datePicker) {
            return;
        }
        closePickerPanel();
        tempStart =
            selectedStart
                ? cloneDate(selectedStart)
                : null;
        tempEnd =
            selectedEnd
                ? cloneDate(selectedEnd)
                : null;
        if (tempStart) {
            viewMonth =
                new Date(
                    tempStart.getFullYear(),
                    tempStart.getMonth(),
                    1
                );
        }
        renderCalendars();
        datePicker.classList.remove(
            'hidden'
        );
        document.body.classList.add(
            'date-picker-lock'
        );
        requestAnimationFrame(
            positionDatePicker
        );
    }
    function hideDatePicker() {
        if (!datePicker) {
            return;
        }
        datePicker.classList.add(
            'hidden'
        );
        closePickerPanel();
        document.body.classList.remove(
            'date-picker-lock'
        );
    }
    /* =========================================================
       MONTH PANEL
    ========================================================= */
    function showMonthPanel(
        calendarDate,
        anchor,
        side
    ) {
        if (!pickerPanel) {
            return;
        }
        activePanelSide =
            side;
        activePanelYear =
            calendarDate.getFullYear();
        pickerPanel.innerHTML =
            '';
        pickerPanel.classList.remove(
            'hidden'
        );
        const header =
            document.createElement(
                'div'
            );
        header.className =
            'custom-picker-panel-header';
        const previous =
            document.createElement(
                'button'
            );
        previous.type =
            'button';
        previous.className =
            'custom-picker-panel-nav';
        previous.innerHTML =
            '&#8249;';
        const title =
            document.createElement(
                'div'
            );
        title.className =
            'custom-picker-panel-title';
        const next =
            document.createElement(
                'button'
            );
        next.type =
            'button';
        next.className =
            'custom-picker-panel-nav';
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
        header.append(
            previous,
            title,
            next,
            close
        );
        pickerPanel.appendChild(
            header
        );
        const grid =
            document.createElement(
                'div'
            );
        grid.className =
            'custom-picker-grid';
        pickerPanel.appendChild(
            grid
        );
        function renderMonths() {
            title.textContent =
                String(
                    activePanelYear
                );
            grid.innerHTML =
                '';
            MONTHS.forEach(
                function (
                    monthName,
                    monthIndex
                ) {
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
                    if (
                        activePanelYear ===
                            calendarDate.getFullYear() &&
                        monthIndex ===
                            calendarDate.getMonth()
                    ) {
                        button.classList.add(
                            'active'
                        );
                    }
                    button.addEventListener(
                        'click',
                        function (event) {
                            event.preventDefault();
                            event.stopPropagation();
                            const target =
                                new Date(
                                    activePanelYear,
                                    monthIndex,
                                    1
                                );
                            viewMonth =
                                activePanelSide ===
                                    'left'
                                    ? target
                                    : addMonths(
                                        target,
                                        -1
                                    );
                            closePickerPanel();
                            renderCalendars();
                        }
                    );
                    grid.appendChild(
                        button
                    );
                }
            );
            positionPickerPanel(
                pickerPanel,
                anchor
            );
        }
        previous.addEventListener(
            'click',
            function (event) {
                event.preventDefault();
                event.stopPropagation();
                activePanelYear =
                    Math.max(
                        MIN_YEAR,
                        activePanelYear - 1
                    );
                renderMonths();
            }
        );
        next.addEventListener(
            'click',
            function (event) {
                event.preventDefault();
                event.stopPropagation();
                activePanelYear =
                    Math.min(
                        MAX_YEAR,
                        activePanelYear + 1
                    );
                renderMonths();
            }
        );
        close.addEventListener(
            'click',
            closePickerPanel
        );
        renderMonths();
    }
    /* =========================================================
       YEAR PANEL
    ========================================================= */
    function showYearPanel(
        calendarDate,
        anchor,
        side
    ) {
        if (!pickerPanel) {
            return;
        }
        activePanelSide =
            side;
        let startYear =
            Math.floor(
                calendarDate.getFullYear() /
                12
            ) * 12;
        startYear =
            Math.max(
                MIN_YEAR,
                startYear
            );
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
    ) {
        pickerPanel.innerHTML =
            '';
        pickerPanel.classList.remove(
            'hidden'
        );
        const header =
            document.createElement(
                'div'
            );
        header.className =
            'custom-picker-panel-header';
        const previous =
            document.createElement(
                'button'
            );
        previous.type =
            'button';
        previous.className =
            'custom-picker-panel-nav';
        previous.innerHTML =
            '&#8249;';
        const title =
            document.createElement(
                'div'
            );
        title.className =
            'custom-picker-panel-title';
        const next =
            document.createElement(
                'button'
            );
        next.type =
            'button';
        next.className =
            'custom-picker-panel-nav';
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
        header.append(
            previous,
            title,
            next,
            close
        );
        pickerPanel.appendChild(
            header
        );
        const grid =
            document.createElement(
                'div'
            );
        grid.className =
            'custom-picker-grid';
        pickerPanel.appendChild(
            grid
        );
        function renderYears() {
            title.textContent =
                startYear +
                ' - ' +
                (
                    startYear + 11
                );
            grid.innerHTML =
                '';
            for (
                let index = 0;
                index < 12;
                index++
            ) {
                const year =
                    startYear +
                    index;
                const button =
                    document.createElement(
                        'button'
                    );
                button.type =
                    'button';
                button.className =
                    'custom-picker-option';
                button.textContent =
                    String(
                        year
                    );
                if (
                    year ===
                    calendarDate.getFullYear()
                ) {
                    button.classList.add(
                        'active'
                    );
                }
                if (
                    year ===
                    new Date().getFullYear()
                ) {
                    button.classList.add(
                        'current'
                    );
                }
                button.addEventListener(
                    'click',
                    function (event) {
                        event.preventDefault();
                        event.stopPropagation();
                        const month =
                            calendarDate.getMonth();
                        viewMonth =
                            activePanelSide ===
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
                        closePickerPanel();
                        renderCalendars();
                    }
                );
                grid.appendChild(
                    button
                );
            }
            positionPickerPanel(
                pickerPanel,
                anchor
            );
        }
        previous.addEventListener(
            'click',
            function (event) {
                event.preventDefault();
                event.stopPropagation();
                startYear =
                    Math.max(
                        MIN_YEAR,
                        startYear - 12
                    );
                renderYears();
            }
        );
        next.addEventListener(
            'click',
            function (event) {
                event.preventDefault();
                event.stopPropagation();
                const maxStart =
                    Math.floor(
                        MAX_YEAR / 12
                    ) * 12;
                startYear =
                    Math.min(
                        maxStart,
                        startYear + 12
                    );
                renderYears();
            }
        );
        close.addEventListener(
            'click',
            closePickerPanel
        );
        renderYears();
    }
    /* =========================================================
       PANEL POSITION
    ========================================================= */
    function positionPickerPanel(
        element,
        anchor
    ) {
        if (
            !element ||
            !anchor
        ) {
            return;
        }
        if (
            window.innerWidth <= 767
        ) {
            element.style.left =
                '50%';
            element.style.top =
                '50%';
            return;
        }
        const rect =
            anchor.getBoundingClientRect();
        const width =
            element.offsetWidth ||
            320;
        const height =
            element.offsetHeight ||
            280;
        let left =
            rect.left;
        let top =
            rect.bottom +
            8;
        if (
            left + width >
            window.innerWidth - 10
        ) {
            left =
                window.innerWidth -
                width -
                10;
        }
        if (left < 10) {
            left = 10;
        }
        if (
            top + height >
            window.innerHeight - 10
        ) {
            top =
                rect.top -
                height -
                8;
        }
        if (top < 10) {
            top = 10;
        }
        element.style.left =
            left + 'px';
        element.style.top =
            top + 'px';
    }
    function closePickerPanel() {
        if (!pickerPanel) {
            return;
        }
        pickerPanel.classList.add(
            'hidden'
        );
        pickerPanel.innerHTML =
            '';
    }
    /* =========================================================
       APPLY DATE
    ========================================================= */
    function applyDateRange() {
        if (
            !tempStart ||
            !tempEnd
        ) {
            if (
                typeof window.Swal !==
                'undefined'
            ) {
                window.Swal.fire({
                    icon:
                        'info',
                    title:
                        'Pilih rentang tanggal',
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
        if (dariInput) {
            dariInput.value =
                toISO(
                    selectedStart
                );
        }
        if (sampaiInput) {
            sampaiInput.value =
                toISO(
                    selectedEnd
                );
        }
        if (dateInput) {
            dateInput.value =
                formatDate(
                    selectedStart
                )
                +
                ' - '
                +
                formatDate(
                    selectedEnd
                );
        }
        if (clearDateButton) {
            clearDateButton.classList.remove(
                'hidden'
            );
            clearDateButton.classList.add(
                'flex'
            );
        }
        hideDatePicker();
    }
    /* =========================================================
       CLEAR DATE
    ========================================================= */
    function clearDateRange() {
        selectedStart =
            null;
        selectedEnd =
            null;
        tempStart =
            null;
        tempEnd =
            null;
        if (dariInput) {
            dariInput.value = '';
        }
        if (sampaiInput) {
            sampaiInput.value = '';
        }
        if (dateInput) {
            dateInput.value = '';
        }
        if (clearDateButton) {
            clearDateButton.classList.remove(
                'flex'
            );
            clearDateButton.classList.add(
                'hidden'
            );
        }
        viewMonth =
            new Date();
        viewMonth.setDate(
            1
        );
        hideDatePicker();
    }
    /* =========================================================
       DATE EVENT
    ========================================================= */
    if (
        dateInput &&
        datePicker
    ) {
        dateInput.addEventListener(
            'click',
            function (event) {
                event.preventDefault();
                event.stopPropagation();
                if (
                    datePicker.classList.contains(
                        'hidden'
                    )
                ) {
                    showDatePicker();
                } else {
                    hideDatePicker();
                }
            }
        );
        dateInput.addEventListener(
            'focus',
            function () {
                if (
                    datePicker.classList.contains(
                        'hidden'
                    )
                ) {
                    showDatePicker();
                }
            }
        );
        closeDatePickerButton?.addEventListener(
            'click',
            function (event) {
                event.preventDefault();
                event.stopPropagation();
                hideDatePicker();
            }
        );
        clearPickerButton?.addEventListener(
            'click',
            function (event) {
                event.preventDefault();
                event.stopPropagation();
                clearDateRange();
            }
        );
        applyPickerButton?.addEventListener(
            'click',
            function (event) {
                event.preventDefault();
                event.stopPropagation();
                applyDateRange();
            }
        );
        clearDateButton?.addEventListener(
            'click',
            function (event) {
                event.preventDefault();
                event.stopPropagation();
                clearDateRange();
            }
        );
        document.addEventListener(
            'mousedown',
            function (event) {
                if (
                    datePicker.classList.contains(
                        'hidden'
                    )
                ) {
                    return;
                }
                if (
                    datePicker.contains(
                        event.target
                    )
                ) {
                    return;
                }
                if (
                    pickerPanel &&
                    pickerPanel.contains(
                        event.target
                    )
                ) {
                    return;
                }
                if (
                    dateInput.contains(
                        event.target
                    )
                ) {
                    return;
                }
                if (
                    clearDateButton &&
                    clearDateButton.contains(
                        event.target
                    )
                ) {
                    return;
                }
                hideDatePicker();
            }
        );
    }
    /* =========================================================
       WINDOW
    ========================================================= */
    window.addEventListener(
        'resize',
        function () {
            if (
                datePicker &&
                !datePicker.classList.contains(
                    'hidden'
                )
            ) {
                positionDatePicker();
            }
            if (
                pickerPanel &&
                !pickerPanel.classList.contains(
                    'hidden'
                )
            ) {
                closePickerPanel();
            }
        }
    );
    window.addEventListener(
        'scroll',
        function () {
            if (
                datePicker &&
                !datePicker.classList.contains(
                    'hidden'
                ) &&
                window.innerWidth > 767
            ) {
                positionDatePicker();
            }
        },
        true
    );
    /* =========================================================
       DROPDOWN
    ========================================================= */
    const kategoriDropdown =
        document.getElementById(
            'kategoriDropdown'
        );
    const statusDropdown =
        document.getElementById(
            'statusDropdown'
        );
    const kategoriButton =
        document.getElementById(
            'kategoriDropdownButton'
        );
    const statusButton =
        document.getElementById(
            'statusDropdownButton'
        );
    const kategoriCount =
        document.getElementById(
            'kategoriCount'
        );
    const statusCount =
        document.getElementById(
            'statusCount'
        );
    const kategoriSummary =
        document.getElementById(
            'kategoriSummary'
        );
    const statusSummary =
        document.getElementById(
            'statusSummary'
        );
    const kategoriFooterCount =
        document.getElementById(
            'kategoriFooterCount'
        );
    const statusFooterCount =
        document.getElementById(
            'statusFooterCount'
        );
    const kategoriCheckboxes =
        document.querySelectorAll(
            '.kategori-checkbox'
        );
    const statusCheckboxes =
        document.querySelectorAll(
            '.status-checkbox'
        );
    function closeDropdown(dropdown) {
        if (!dropdown) {
            return;
        }
        dropdown.classList.remove(
            'open'
        );
        const button =
            dropdown.querySelector(
                '.filter-dropdown-trigger'
            );
        if (button) {
            button.setAttribute(
                'aria-expanded',
                'false'
            );
        }
    }
    function closeAllDropdowns() {
        closeDropdown(
            kategoriDropdown
        );
        closeDropdown(
            statusDropdown
        );
    }
    function openDropdown(dropdown) {
        if (!dropdown) {
            return;
        }
        if (
            dropdown ===
            kategoriDropdown
        ) {
            closeDropdown(
                statusDropdown
            );
        }
        if (
            dropdown ===
            statusDropdown
        ) {
            closeDropdown(
                kategoriDropdown
            );
        }
        dropdown.classList.add(
            'open'
        );
        const button =
            dropdown.querySelector(
                '.filter-dropdown-trigger'
            );
        if (button) {
            button.setAttribute(
                'aria-expanded',
                'true'
            );
        }
    }
    function getCheckedLabels(
        selector
    ) {
        return Array.from(
            document.querySelectorAll(
                selector +
                ':checked'
            )
        )
        .map(
            function (checkbox) {
                const label =
                    checkbox.closest(
                        'label'
                    );
                const span =
                    label?.querySelector(
                        'span'
                    );
                return (
                    span?.textContent
                        .trim() ||
                    ''
                );
            }
        )
        .filter(Boolean);
    }
    function updateKategoriFilter() {
        const checked =
            document.querySelectorAll(
                '.kategori-checkbox:checked'
            );
        const count =
            checked.length;
        if (kategoriCount) {
            kategoriCount.textContent =
                count +
                ' dipilih';
        }
        if (kategoriFooterCount) {
            kategoriFooterCount.textContent =
                count +
                ' kategori dipilih';
        }
        if (kategoriSummary) {
            const labels =
                getCheckedLabels(
                    '.kategori-checkbox'
                );
            if (!labels.length) {
                kategoriSummary.textContent =
                    'Semua kategori';
            }
            else if (
                labels.length <= 2
            ) {
                kategoriSummary.textContent =
                    labels.join(', ');
            }
            else {
                kategoriSummary.textContent =
                    labels.length +
                    ' kategori dipilih';
            }
        }
    }
    function updateStatusFilter() {
        const checked =
            document.querySelectorAll(
                '.status-checkbox:checked'
            );
        const count =
            checked.length;
        if (statusCount) {
            statusCount.textContent =
                count +
                ' dipilih';
        }
        if (statusFooterCount) {
            statusFooterCount.textContent =
                count +
                ' status dipilih';
        }
        if (statusSummary) {
            const labels =
                getCheckedLabels(
                    '.status-checkbox'
                );
            if (!labels.length) {
                statusSummary.textContent =
                    'Semua status';
            }
            else if (
                labels.length <= 2
            ) {
                statusSummary.textContent =
                    labels.join(', ');
            }
            else {
                statusSummary.textContent =
                    labels.length +
                    ' status dipilih';
            }
        }
    }
    kategoriButton?.addEventListener(
        'click',
        function (event) {
            event.preventDefault();
            event.stopPropagation();
            if (
                kategoriDropdown?.classList.contains(
                    'open'
                )
            ) {
                closeDropdown(
                    kategoriDropdown
                );
            }
            else {
                openDropdown(
                    kategoriDropdown
                );
            }
        }
    );
    statusButton?.addEventListener(
        'click',
        function (event) {
            event.preventDefault();
            event.stopPropagation();
            if (
                statusDropdown?.classList.contains(
                    'open'
                )
            ) {
                closeDropdown(
                    statusDropdown
                );
            }
            else {
                openDropdown(
                    statusDropdown
                );
            }
        }
    );
    kategoriCheckboxes.forEach(
        function (checkbox) {
            checkbox.addEventListener(
                'change',
                updateKategoriFilter
            );
        }
    );
    statusCheckboxes.forEach(
        function (checkbox) {
            checkbox.addEventListener(
                'change',
                updateStatusFilter
            );
        }
    );
    document
        .getElementById(
            'selectAllKategori'
        )
        ?.addEventListener(
            'click',
            function (event) {
                event.preventDefault();
                event.stopPropagation();
                kategoriCheckboxes.forEach(
                    function (checkbox) {
                        checkbox.checked =
                            true;
                    }
                );
                updateKategoriFilter();
            }
        );
    document
        .getElementById(
            'clearAllKategori'
        )
        ?.addEventListener(
            'click',
            function (event) {
                event.preventDefault();
                event.stopPropagation();
                kategoriCheckboxes.forEach(
                    function (checkbox) {
                        checkbox.checked =
                            false;
                    }
                );
                updateKategoriFilter();
            }
        );
    document
        .getElementById(
            'selectAllStatus'
        )
        ?.addEventListener(
            'click',
            function (event) {
                event.preventDefault();
                event.stopPropagation();
                statusCheckboxes.forEach(
                    function (checkbox) {
                        checkbox.checked =
                            true;
                    }
                );
                updateStatusFilter();
            }
        );
    document
        .getElementById(
            'clearAllStatus'
        )
        ?.addEventListener(
            'click',
            function (event) {
                event.preventDefault();
                event.stopPropagation();
                statusCheckboxes.forEach(
                    function (checkbox) {
                        checkbox.checked =
                            false;
                    }
                );
                updateStatusFilter();
            }
        );
    document.addEventListener(
        'click',
        function (event) {
            const target =
                event.target;
            if (
                !(target instanceof Element)
            ) {
                return;
            }
            if (
                !target.closest(
                    '.filter-dropdown'
                )
            ) {
                closeAllDropdowns();
            }
        }
    );
    /* =========================================================
       ESC
    ========================================================= */
    document.addEventListener(
        'keydown',
        function (event) {
            if (
                event.key !==
                'Escape'
            ) {
                return;
            }
            closeAllDropdowns();
            if (
                datePicker &&
                !datePicker.classList.contains(
                    'hidden'
                )
            ) {
                hideDatePicker();
            }
        }
    );
    /* =========================================================
       FILTER VALIDATION
    ========================================================= */
    const filterForm =
        document.getElementById(
            'filterForm'
        );
    filterForm?.addEventListener(
        'submit',
        function (event) {
            const start =
                dariInput?.value ||
                '';
            const end =
                sampaiInput?.value ||
                '';
            if (
                start &&
                end &&
                start > end
            ) {
                event.preventDefault();
                if (
                    typeof window.Swal !==
                    'undefined'
                ) {
                    window.Swal.fire({
                        icon:
                            'warning',
                        title:
                            'Rentang tanggal tidak valid',
                        text:
                            'Tanggal mulai tidak boleh lebih besar dari tanggal akhir.',
                        confirmButtonText:
                            'Mengerti',
                        confirmButtonColor:
                            '#2563eb'
                    });
                }
                else {
                    window.alert(
                        'Tanggal mulai tidak boleh lebih besar dari tanggal akhir.'
                    );
                }
            }
        }
    );
    /* =========================================================
       DELETE
    ========================================================= */
    document
        .querySelectorAll(
            '.delete-btn'
        )
        .forEach(
            function (button) {
                button.addEventListener(
                    'click',
                    function () {
                        const form =
                            this.closest(
                                '.delete-form'
                            );
                        if (!form) {
                            return;
                        }
                        if (
                            typeof window.Swal !==
                            'undefined'
                        ) {
                            window.Swal.fire({
                                title:
                                    'Hapus Surat Keluar?',
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
                            })
                            .then(
                                function (result) {
                                    if (
                                        result.isConfirmed
                                    ) {
                                        form.submit();
                                    }
                                }
                            );
                        }
                        else {
                            if (
                                window.confirm(
                                    'Yakin ingin menghapus surat keluar ini?'
                                )
                            ) {
                                form.submit();
                            }
                        }
                    }
                );
            }
        );
    /* =========================================================
       INITIALIZE
    ========================================================= */
    updateKategoriFilter();
    updateStatusFilter();
    if (
        selectedStart &&
        selectedEnd &&
        dateInput &&
        clearDateButton
    ) {
        dateInput.value =
            formatDate(
                selectedStart
            )
            +
            ' - '
            +
            formatDate(
                selectedEnd
            );
        clearDateButton.classList.remove(
            'hidden'
        );
        clearDateButton.classList.add(
            'flex'
        );
    }
})();
</script>
<script
    src="https://cdn.jsdelivr.net/npm/sweetalert2@11"
    defer
></script>
@endpush
@endsection
