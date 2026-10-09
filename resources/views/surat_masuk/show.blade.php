@extends('layouts.app')

@section('title', 'Detail Surat Masuk')

@section('content')

@php
    use Illuminate\Support\Carbon;

    /* =========================================================
       DATA SURAT
    ========================================================== */

    $status = strtolower(
        trim(
            (string) ($suratMasuk->status ?? 'baru')
        )
    );

    $statusLabels = [
        'baru'           => 'Baru',
        'diproses'       => 'Diproses',
        'didisposisikan' => 'Didisposisikan',
        'selesai'        => 'Selesai',
        'diarsipkan'     => 'Diarsipkan',
    ];

    $statusClasses = [
        'baru'           => 'sm-status-baru',
        'diproses'       => 'sm-status-diproses',
        'didisposisikan' => 'sm-status-disposisi',
        'selesai'        => 'sm-status-selesai',
        'diarsipkan'     => 'sm-status-arsip',
    ];

    $statusLabel = $statusLabels[$status] ?? ucfirst($status);
    $statusClass = $statusClasses[$status] ?? 'sm-status-baru';


    /* =========================================================
       TANGGAL
    ========================================================== */

    $tanggalSurat = '-';
    $tanggalTerima = '-';

    try {
        if ($suratMasuk->tanggal_surat) {
            $tanggalSurat = Carbon::parse(
                $suratMasuk->tanggal_surat
            )->translatedFormat('d F Y');
        }
    } catch (\Throwable $e) {
        $tanggalSurat = (string) $suratMasuk->tanggal_surat;
    }

    try {
        if ($suratMasuk->tanggal_terima) {
            $tanggalTerima = Carbon::parse(
                $suratMasuk->tanggal_terima
            )->translatedFormat('d F Y');
        }
    } catch (\Throwable $e) {
        $tanggalTerima = (string) $suratMasuk->tanggal_terima;
    }


    /* =========================================================
       LAMPIRAN
    ========================================================== */

    $lampiranPath = $suratMasuk->lampiran_file ?? null;
    // Gunakan URL file yang disiapkan oleh SuratMasukController.
    // Untuk storage privat, URL ini berupa temporary URL yang ditandatangani.
    $lampiranUrl = $fileUrl ?? null;
    $lampiranExtension = '';

    if (!empty($lampiranPath)) {
        // Kompatibilitas data lama yang menyimpan URL lengkap tetap dipertahankan.
        if (filter_var($lampiranPath, FILTER_VALIDATE_URL)) {
            $lampiranUrl = $lampiranPath;
        } elseif (empty($lampiranUrl) && Route::has('surat-masuk.preview-lampiran')) {
            // Fallback ke endpoint internal jika URL storage tidak tersedia.
            $lampiranUrl = route('surat-masuk.preview-lampiran', $suratMasuk);
        }

        $cleanPath = parse_url($lampiranPath, PHP_URL_PATH) ?: $lampiranPath;
        $lampiranExtension = strtolower(pathinfo($cleanPath, PATHINFO_EXTENSION));
    }

    $isImage = in_array(
        $lampiranExtension,
        [
            'jpg',
            'jpeg',
            'png',
            'webp',
            'gif',
        ],
        true
    );

    $isPdf = $lampiranExtension === 'pdf';

    $lampiranNama =
        !empty($lampiranPath)
            ? basename($lampiranPath)
            : null;


    /* =========================================================
       HAK AKSES
    ========================================================== */

    $user = auth()->user();

    $userRole = strtolower(
        trim(
            (string) (
                $user->role
                ?? $user->jabatan
                ?? ''
            )
        )
    );

    if ($userRole === 'staff') {
        $userRole = 'staf';
    }

    $canManage = in_array(
        $userRole,
        [
            'admin',
            'pimpinan',
        ],
        true
    );


    /* =========================================================
       DISPOSISI
    ========================================================== */

    $disposisis =
        $suratMasuk->disposisi ?? collect();
@endphp


<style>
    /* =========================================================
       PAGE
    ========================================================== */

    .sm-detail-page {
        width: 100%;
        max-width: 1280px;
        margin: 0 auto;
        padding: 14px 18px 36px;
        color: #334155;
    }

    .sm-detail-page *,
    .sm-detail-page *::before,
    .sm-detail-page *::after {
        box-sizing: border-box;
    }


    /* =========================================================
       ALERT
    ========================================================== */

    .sm-alert {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 14px;
        padding: 11px 14px;
        border-radius: 11px;
    }

    .sm-alert-success {
        background: #f0fdf4;
        color: #166534;
    }

    .sm-alert-error {
        background: #fff1f2;
        color: #991b1b;
    }

    .sm-alert-inner {
        display: flex;
        align-items: center;
        gap: 9px;
        min-width: 0;
    }

    .sm-alert-icon {
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 30px;
        border-radius: 8px;
    }

    .sm-alert-success .sm-alert-icon {
        background: #dcfce7;
        color: #16a34a;
    }

    .sm-alert-error .sm-alert-icon {
        background: #ffe4e6;
        color: #dc2626;
    }

    .sm-alert-text {
        margin: 0;
        font-size: 11px;
        line-height: 1.5;
        font-weight: 700;
    }

    .sm-alert-close {
        width: 27px;
        height: 27px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 27px;
        border: 0;
        border-radius: 7px;
        background: transparent;
        cursor: pointer;
    }

    .sm-alert-close:hover {
        background: rgba(15, 23, 42, .05);
    }


    /* =========================================================
       HEADER
    ========================================================== */

    .sm-header {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        min-height: 84px;
        margin-bottom: 16px;
        padding: 17px 20px;
        overflow: hidden;
        border-radius: 16px;
        background:
            linear-gradient(
                135deg,
                #0f172a 0%,
                #1e293b 55%,
                #312e81 100%
            );
        color: #fff;
        box-shadow:
            0 10px 30px rgba(15, 23, 42, .10);
    }

    .sm-header::after {
        content: "";
        position: absolute;
        width: 180px;
        height: 180px;
        right: -70px;
        top: -100px;
        border-radius: 50%;
        background: rgba(99, 102, 241, .17);
        pointer-events: none;
    }

    .sm-header-left {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    .sm-back {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 40px;
        border-radius: 10px;
        background: rgba(255, 255, 255, .09);
        color: #fff;
        text-decoration: none;
        transition: .16s ease;
    }

    .sm-back:hover {
        background: rgba(255, 255, 255, .17);
        transform: translateX(-2px);
    }

    .sm-header-content {
        min-width: 0;
    }

    .sm-breadcrumb {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 3px;
        font-size: 9px;
        color: #94a3b8;
    }

    .sm-breadcrumb a {
        color: #cbd5e1;
        text-decoration: none;
    }

    .sm-breadcrumb a:hover {
        color: #fff;
    }

    .sm-header-title {
        margin: 0;
        font-size: 20px;
        line-height: 1.3;
        font-weight: 800;
        letter-spacing: -.02em;
    }

    .sm-header-subtitle {
        margin: 4px 0 0;
        font-size: 10px;
        line-height: 1.5;
        color: #cbd5e1;
    }

    .sm-header-actions {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        flex-wrap: wrap;
        gap: 7px;
    }

    .sm-header-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        min-height: 34px;
        padding: 0 11px;
        border-radius: 8px;
        font-size: 9px;
        font-weight: 800;
        text-decoration: none;
        white-space: nowrap;
        transition: .15s ease;
    }

    .sm-header-btn-edit {
        background: rgba(245, 158, 11, .15);
        color: #fde68a;
    }

    .sm-header-btn-edit:hover {
        background: rgba(245, 158, 11, .25);
    }

    .sm-header-btn-print {
        background: rgba(16, 185, 129, .13);
        color: #a7f3d0;
    }

    .sm-header-btn-print:hover {
        background: rgba(16, 185, 129, .23);
    }

    .sm-header-btn-label {
        background: rgba(96, 165, 250, .12);
        color: #bfdbfe;
    }

    .sm-header-btn-label:hover {
        background: rgba(96, 165, 250, .22);
    }


    /* =========================================================
       TOP LAYOUT
       KIRI  = INFORMASI + PERIHAL
       KANAN = RIWAYAT DISPOSISI
    ========================================================== */

    .sm-top-grid {
        display: grid;
        grid-template-columns:
            minmax(0, 2fr)
            minmax(330px, .9fr);
        gap: 16px;
        align-items: stretch;
    }

    .sm-left-column {
        min-width: 0;
        min-height: 100%;
        display: flex;
        flex-direction: column;
    }

    .sm-right-column {
        min-width: 0;
        min-height: 100%;
        display: flex;
    }


    /* =========================================================
       GENERAL CARD
    ========================================================== */

    .sm-card {
        width: 100%;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        background: #fff;
        box-shadow:
            0 7px 25px rgba(15, 23, 42, .055);
    }


    /* =========================================================
       CARD HEADER
    ========================================================== */

    .sm-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        min-height: 61px;
        padding: 11px 15px;
        border-bottom: 1px solid #e5e7eb;
        background:
            linear-gradient(
                135deg,
                #f8fafc 0%,
                #ffffff 55%,
                #f5f3ff 100%
            );
    }

    .sm-card-heading {
        display: flex;
        align-items: center;
        gap: 9px;
        min-width: 0;
    }

    .sm-card-heading-icon {
        width: 34px;
        height: 34px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 34px;
        border-radius: 9px;
        background: #eef2ff;
        color: #4f46e5;
    }

    .sm-card-title {
        margin: 0;
        font-size: 12px;
        line-height: 1.35;
        font-weight: 800;
        color: #1e293b;
    }

    .sm-card-subtitle {
        margin: 2px 0 0;
        font-size: 9px;
        line-height: 1.4;
        color: #94a3b8;
    }


    /* =========================================================
       STATUS
    ========================================================== */

    .sm-status {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 26px;
        padding: 0 10px;
        border-radius: 999px;
        font-size: 9px;
        font-weight: 800;
        white-space: nowrap;
    }

    .sm-status-baru {
        background: #eff6ff;
        color: #1d4ed8;
    }

    .sm-status-diproses {
        background: #fffbeb;
        color: #b45309;
    }

    .sm-status-disposisi {
        background: #f5f3ff;
        color: #6d28d9;
    }

    .sm-status-selesai {
        background: #f0fdf4;
        color: #15803d;
    }

    .sm-status-arsip {
        background: #f1f5f9;
        color: #475569;
    }


    /* =========================================================
       INFORMASI SURAT
    ========================================================== */

    .sm-info-card {
        flex: 0 0 auto;
        overflow: hidden;
    }

    .sm-info-table-wrap {
        width: 100%;
        overflow-x: auto;
    }

    .sm-info-table {
        width: 100%;
        min-width: 760px;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .sm-info-table td {
        width: 33.333%;
        height: 108px;
        padding: 18px 19px;
        vertical-align: top;
        border-right: 1px solid #e5e7eb;
        border-bottom: 1px solid #e5e7eb;
    }

    .sm-info-table td:last-child {
        border-right: 0;
    }

    .sm-info-table tr:last-child td {
        border-bottom: 0;
    }

    .sm-info-table tr:first-child td:nth-child(odd),
    .sm-info-table tr:last-child td:nth-child(odd) {
        background: #f8fafc;
    }

    .sm-info-label {
        display: block;
        margin-bottom: 9px;
        font-size: 8px;
        line-height: 1.3;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: #94a3b8;
    }

    .sm-info-value {
        margin: 0;
        font-size: 12px;
        line-height: 1.6;
        font-weight: 750;
        color: #1e293b;
        overflow-wrap: anywhere;
    }

    .sm-info-date {
        display: flex;
        align-items: flex-start;
        gap: 7px;
    }

    .sm-date-icon {
        width: 15px;
        height: 15px;
        flex: 0 0 15px;
        margin-top: 2px;
        color: #6366f1;
    }


    /* =========================================================
       PERIHAL
       MENGISI SELURUH RUANG YANG TERSISA
    ========================================================== */

    .sm-perihal-card {
        width: 100%;
        min-height: 0;
        margin-top: 16px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
    }

    .sm-perihal-body {
        flex: 1 1 auto;
        display: flex;
        padding: 16px 17px 17px;
    }

    .sm-perihal-box {
        width: 100%;
        min-height: 110px;
        flex: 1 1 auto;
        display: flex;
        align-items: flex-start;
        padding: 15px 16px;
        border: 1px solid #e5e7eb;
        border-radius: 11px;
        background: #f8fafc;
    }

    .sm-perihal-text {
        margin: 0;
        font-size: 12px;
        line-height: 1.75;
        color: #475569;
        white-space: pre-line;
        overflow-wrap: anywhere;
    }


    /* =========================================================
       DISPOSISI
    ========================================================== */

    .sm-disposition-card {
        width: 100%;
        height: 100%;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .sm-disposition-body {
        flex: 1 1 auto;
        padding: 15px 16px 18px;
        overflow: hidden;
    }

    .sm-create-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        min-height: 30px;
        padding: 0 10px;
        border-radius: 8px;
        background: #7c3aed;
        color: #fff;
        font-size: 9px;
        font-weight: 800;
        text-decoration: none;
        white-space: nowrap;
        transition: .15s ease;
    }

    .sm-create-btn:hover {
        background: #6d28d9;
    }


    /* =========================================================
       TIMELINE
    ========================================================== */

    .sm-timeline {
        position: relative;
        max-height: 100%;
        overflow-y: auto;
        padding-right: 4px;
    }

    .sm-timeline::-webkit-scrollbar {
        width: 4px;
    }

    .sm-timeline::-webkit-scrollbar-track {
        background: transparent;
    }

    .sm-timeline::-webkit-scrollbar-thumb {
        background: #ddd6fe;
        border-radius: 999px;
    }

    .sm-timeline-item {
        position: relative;
        padding-left: 23px;
        padding-bottom: 20px;
    }

    .sm-timeline-item:last-child {
        padding-bottom: 0;
    }

    .sm-timeline-line {
        position: absolute;
        top: 9px;
        bottom: 0;
        left: 5px;
        width: 1px;
        background: #ddd6fe;
    }

    .sm-timeline-item:last-child .sm-timeline-line {
        display: none;
    }

    .sm-timeline-dot {
        position: absolute;
        top: 4px;
        left: 0;
        width: 11px;
        height: 11px;
        border-radius: 50%;
        background: #7c3aed;
        box-shadow:
            0 0 0 4px #f5f3ff;
    }

    .sm-disp-route {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 5px;
        padding-right: 3px;
        font-size: 10px;
        line-height: 1.5;
        font-weight: 800;
    }

    .sm-disp-from {
        color: #334155;
        overflow-wrap: anywhere;
    }

    .sm-disp-to {
        color: #7c3aed;
        overflow-wrap: anywhere;
    }

    .sm-disp-status {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-top: 6px;
        min-height: 21px;
        padding: 0 8px;
        border-radius: 6px;
        font-size: 8px;
        font-weight: 800;
        white-space: nowrap;
    }

    .sm-disp-menunggu {
        background: #f5f3ff;
        color: #6d28d9;
    }

    .sm-disp-diproses {
        background: #fffbeb;
        color: #b45309;
    }

    .sm-disp-selesai {
        background: #f0fdf4;
        color: #15803d;
    }

    .sm-disp-ditolak {
        background: #fff1f2;
        color: #be123c;
    }

    .sm-disp-content {
        margin-top: 7px;
        padding: 10px 11px;
        border-radius: 9px;
        background: #f8fafc;
    }

    .sm-disp-text {
        margin: 0;
        font-size: 10px;
        line-height: 1.65;
        color: #64748b;
        white-space: pre-line;
        overflow-wrap: anywhere;
    }

    .sm-disp-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 5px 8px;
        margin-top: 6px;
    }

    .sm-disp-date {
        font-size: 8px;
        color: #94a3b8;
        font-weight: 700;
    }

    .sm-disp-deadline {
        font-size: 8px;
        color: #d97706;
        font-weight: 700;
    }


    /* =========================================================
       EMPTY
    ========================================================== */

    .sm-empty {
        width: 100%;
        min-height: 260px;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 30px 10px;
        text-align: center;
    }

    .sm-empty-icon {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 11px;
        border-radius: 13px;
        background: #f8fafc;
        color: #94a3b8;
    }

    .sm-empty-title {
        margin: 0;
        font-size: 10px;
        font-weight: 800;
        color: #475569;
    }

    .sm-empty-text {
        margin: 4px 0 0;
        font-size: 9px;
        line-height: 1.5;
        color: #94a3b8;
    }


    /* =========================================================
       LAMPIRAN DIGITAL
       FULL WIDTH
    ========================================================== */

    .sm-attachment-card {
        width: 100%;
        margin-top: 16px;
        overflow: hidden;
    }

    .sm-attachment-body {
        padding: 16px 17px 18px;
    }

    .sm-extension {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 24px;
        padding: 0 9px;
        border-radius: 7px;
        background: #f1f5f9;
        color: #64748b;
        font-family:
            ui-monospace,
            SFMono-Regular,
            Menlo,
            Monaco,
            Consolas,
            monospace;
        font-size: 8px;
        font-weight: 800;
        text-transform: uppercase;
    }


    /* =========================================================
       FILE BAR
    ========================================================== */

    .sm-file-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        width: 100%;
        margin-bottom: 12px;
        padding: 11px;
        border: 1px solid #e5e7eb;
        border-radius: 11px;
        background: #f8fafc;
    }

    .sm-file-info {
        display: flex;
        align-items: center;
        gap: 9px;
        min-width: 0;
    }

    .sm-file-icon {
        width: 35px;
        height: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 35px;
        border-radius: 9px;
        background: #eef2ff;
        color: #4f46e5;
    }

    .sm-file-name {
        margin: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 10px;
        font-weight: 750;
        color: #334155;
    }

    .sm-file-state {
        margin: 2px 0 0;
        font-size: 8px;
        color: #94a3b8;
    }

    .sm-file-actions {
        display: flex;
        align-items: center;
        gap: 6px;
        flex: 0 0 auto;
    }

    .sm-file-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        min-height: 32px;
        padding: 0 11px;
        border-radius: 8px;
        font-size: 9px;
        font-weight: 800;
        text-decoration: none;
        white-space: nowrap;
        transition: .15s ease;
    }

    .sm-file-btn-open {
        background: #4f46e5;
        color: #fff;
    }

    .sm-file-btn-open:hover {
        background: #4338ca;
    }

    .sm-file-btn-download {
        border: 1px solid #e5e7eb;
        background: #fff;
        color: #475569;
    }

    .sm-file-btn-download:hover {
        background: #f1f5f9;
    }


    /* =========================================================
       VIEWER
    ========================================================== */

    .sm-viewer {
        width: 100%;
        overflow: hidden;
        padding: 5px;
        border-radius: 12px;
        background: #0f172a;
    }

    .sm-image-wrapper {
        width: 100%;
        min-height: 340px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: auto;
        border-radius: 8px;
        background: #111827;
    }

    .sm-image {
        display: block;
        width: auto;
        max-width: 100%;
        max-height: 720px;
        object-fit: contain;
    }

    .sm-pdf {
        display: block;
        width: 100%;
        height: 720px;
        border: 0;
        border-radius: 8px;
        background: #fff;
    }

    .sm-viewer-empty {
        min-height: 300px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 30px;
        border-radius: 8px;
        background: #fff;
        text-align: center;
    }

    .sm-viewer-empty-title {
        margin: 9px 0 0;
        font-size: 11px;
        font-weight: 800;
        color: #334155;
    }

    .sm-viewer-empty-text {
        max-width: 450px;
        margin: 5px auto 0;
        font-size: 9px;
        line-height: 1.6;
        color: #94a3b8;
    }

    .sm-no-file {
        display: flex;
        align-items: center;
        gap: 9px;
        padding: 13px;
        border-radius: 10px;
        background: #f8fafc;
        color: #94a3b8;
    }

    .sm-no-file p {
        margin: 0;
        font-size: 9px;
        line-height: 1.5;
    }


    /* =========================================================
       FOOTER
    ========================================================== */

    .sm-system-footer {
        margin-top: 12px;
        text-align: center;
    }

    .sm-system-footer span {
        font-size: 8px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: #cbd5e1;
    }


    /* =========================================================
       TABLET
    ========================================================== */

    @media (max-width: 980px) {
        .sm-top-grid {
            grid-template-columns: 1fr;
            align-items: start;
        }

        .sm-right-column {
            min-height: auto;
        }

        .sm-disposition-card {
            height: auto;
        }

        .sm-timeline {
            max-height: none;
            overflow: visible;
        }

        .sm-perihal-card {
            flex: 0 0 auto;
        }

        .sm-perihal-body {
            flex: 0 0 auto;
        }

        .sm-perihal-box {
            min-height: 110px;
        }
    }


    /* =========================================================
       MOBILE
    ========================================================== */

    @media (max-width: 700px) {

        .sm-detail-page {
            padding: 9px 10px 25px;
        }

        .sm-header {
            align-items: flex-start;
            flex-direction: column;
            padding: 15px;
        }

        .sm-header-left {
            width: 100%;
        }

        .sm-header-actions {
            width: 100%;
            justify-content: flex-start;
            padding-top: 9px;
            border-top: 1px solid rgba(255,255,255,.10);
        }

        .sm-header-btn {
            flex: 1 1 auto;
        }

        .sm-card-header {
            padding: 11px 13px;
        }

        .sm-card-subtitle {
            font-size: 8px;
        }

        .sm-info-table-wrap {
            overflow-x: auto;
        }

        .sm-info-table {
            min-width: 680px;
        }

        .sm-info-table td {
            height: 100px;
            padding: 15px;
        }

        .sm-perihal-body {
            padding: 14px;
        }

        .sm-file-bar {
            align-items: stretch;
            flex-direction: column;
        }

        .sm-file-actions {
            width: 100%;
        }

        .sm-file-btn {
            flex: 1;
        }

        .sm-image-wrapper {
            min-height: 250px;
        }

        .sm-pdf {
            height: 550px;
        }
    }


    /* =========================================================
       SMALL MOBILE
    ========================================================== */

    @media (max-width: 470px) {

        .sm-header-title {
            font-size: 18px;
        }

        .sm-header-subtitle {
            font-size: 9px;
        }

        .sm-header-actions {
            flex-direction: column;
        }

        .sm-header-btn {
            width: 100%;
        }

        .sm-card-subtitle {
            display: none;
        }

        .sm-info-table {
            min-width: 0;
        }

        .sm-info-table,
        .sm-info-table tbody,
        .sm-info-table tr,
        .sm-info-table td {
            display: block;
            width: 100%;
        }

        .sm-info-table td {
            height: auto;
            min-height: 82px;
            border-right: 0;
        }

        .sm-info-table td:last-child {
            border-right: 0;
        }

        .sm-info-value {
            font-size: 11px;
        }

        .sm-file-actions {
            flex-direction: column;
        }

        .sm-file-btn {
            width: 100%;
        }

        .sm-pdf {
            height: 450px;
        }
    }

/* Perapian responsif area lampiran tanpa mengubah fungsi halaman. */
.sm-file-bar { min-width: 0; }
.sm-file-info { flex: 1 1 auto; min-width: 0; }
.sm-file-actions { display: flex; flex: 0 0 auto; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: 6px; }
.sm-viewer { width: 100%; min-width: 0; }
.sm-pdf { display: block; width: 100%; min-height: 480px; height: 720px; border: 0; background: #fff; }
@media (max-width: 640px) {
  .sm-file-bar { align-items: flex-start; flex-direction: column; gap: 10px; }
  .sm-file-actions { width: 100%; justify-content: flex-start; }
  .sm-pdf { height: 70vh; min-height: 420px; }
}
</style>


<div class="sm-detail-page">

    {{-- =====================================================
         ALERT SUCCESS
    ====================================================== --}}

    @if(session('success'))

        <div
            class="sm-alert sm-alert-success"
            role="alert"
        >

            <div class="sm-alert-inner">

                <div class="sm-alert-icon">

                    <svg
                        class="w-4 h-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>

                </div>

                <p class="sm-alert-text">
                    {{ session('success') }}
                </p>

            </div>


            <button
                type="button"
                class="sm-alert-close"
                onclick="this.closest('[role=alert]')?.remove()"
                aria-label="Tutup"
            >

                <svg
                    class="w-4 h-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
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

    @endif


    {{-- =====================================================
         ALERT ERROR
    ====================================================== --}}

    @if(session('error'))

        <div
            class="sm-alert sm-alert-error"
            role="alert"
        >

            <div class="sm-alert-inner">

                <div class="sm-alert-icon">

                    <svg
                        class="w-4 h-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>

                </div>

                <p class="sm-alert-text">
                    {{ session('error') }}
                </p>

            </div>


            <button
                type="button"
                class="sm-alert-close"
                onclick="this.closest('[role=alert]')?.remove()"
                aria-label="Tutup"
            >

                <svg
                    class="w-4 h-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
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

    @endif


    {{-- =====================================================
         HEADER
    ====================================================== --}}

    <div class="sm-header">

        <div class="sm-header-left">

            <a
                href="{{ route('surat-masuk.index') }}"
                class="sm-back"
                title="Kembali"
                aria-label="Kembali ke Surat Masuk"
            >

                <svg
                    class="w-4 h-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18"
                    />
                </svg>

            </a>


            <div class="sm-header-content">

                <div class="sm-breadcrumb">

                    <a href="{{ route('surat-masuk.index') }}">
                        Surat Masuk
                    </a>

                    <span>/</span>

                    <span>Detail Arsip</span>

                </div>


                <h1 class="sm-header-title">
                    Detail Surat Masuk
                </h1>


                <p class="sm-header-subtitle">
                    Informasi surat, dokumen digital, dan riwayat disposisi.
                </p>

            </div>

        </div>


        <div class="sm-header-actions">

            @if(
                $canManage &&
                Route::has('surat-masuk.edit')
            )

                <a
                    href="{{ route('surat-masuk.edit', $suratMasuk) }}"
                    class="sm-header-btn sm-header-btn-edit"
                >

                    <svg
                        class="w-3.5 h-3.5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                        />
                    </svg>

                    Edit Surat

                </a>

            @endif


            @if(
                Route::has('surat-masuk.cetak-disposisi')
            )

                <a
                    href="{{ route('surat-masuk.cetak-disposisi', $suratMasuk) }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="sm-header-btn sm-header-btn-print"
                >

                    <svg
                        class="w-3.5 h-3.5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4h4m2 5h6a2 2 0 002-2v-3H8v3a2 2 0 002 2z"
                        />
                    </svg>

                    Cetak Disposisi

                </a>

            @endif


            @if(
                Route::has('surat-masuk.cetak-label')
            )

                <a
                    href="{{ route('surat-masuk.cetak-label', $suratMasuk) }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="sm-header-btn sm-header-btn-label"
                >

                    <svg
                        class="w-3.5 h-3.5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M7 7h10M7 11h10M7 15h10M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z"
                        />
                    </svg>

                    Cetak Label

                </a>

            @endif

        </div>

    </div>


    {{-- =====================================================
         INFORMASI SURAT + DISPOSISI
    ====================================================== --}}

    <div class="sm-top-grid">


        {{-- =================================================
             KIRI
             INFORMASI SURAT + PERIHAL
        ================================================== --}}

        <div class="sm-left-column">


            {{-- =============================================
                 INFORMASI SURAT
            ============================================== --}}

            <div class="sm-card sm-info-card">

                <div class="sm-card-header">

                    <div class="sm-card-heading">

                        <div class="sm-card-heading-icon">

                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586A1.5 1.5 0 0118 8.5V19a2 2 0 01-2 2z"
                                />
                            </svg>

                        </div>


                        <div>

                            <h2 class="sm-card-title">
                                Informasi Surat
                            </h2>

                            <p class="sm-card-subtitle">
                                Data utama arsip surat masuk.
                            </p>

                        </div>

                    </div>


                    <span class="sm-status {{ $statusClass }}">
                        {{ $statusLabel }}
                    </span>

                </div>


                <div class="sm-info-table-wrap">

                    <table class="sm-info-table">

                        <tbody>

                            {{-- BARIS 1 --}}

                            <tr>

                                <td>

                                    <span class="sm-info-label">
                                        Nomor Surat
                                    </span>

                                    <p class="sm-info-value">
                                        {{ $suratMasuk->nomor_surat ?? '-' }}
                                    </p>

                                </td>


                                <td>

                                    <span class="sm-info-label">
                                        Pengirim
                                    </span>

                                    <p class="sm-info-value">
                                        {{ $suratMasuk->pengirim ?? '-' }}
                                    </p>

                                </td>


                                <td>

                                    <span class="sm-info-label">
                                        Kategori Surat
                                    </span>

                                    <p class="sm-info-value">
                                        {{ $suratMasuk->kategori?->nama_kategori ?? '-' }}
                                    </p>

                                </td>

                            </tr>


                            {{-- BARIS 2 --}}

                            <tr>

                                <td>

                                    <span class="sm-info-label">
                                        Tanggal Surat
                                    </span>

                                    <p class="sm-info-value sm-info-date">

                                        <svg
                                            class="sm-date-icon"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M8 2v4M16 2v4M3 10h18M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"
                                            />
                                        </svg>

                                        <span>
                                            {{ $tanggalSurat }}
                                        </span>

                                    </p>

                                </td>


                                <td>

                                    <span class="sm-info-label">
                                        Tanggal Diterima
                                    </span>

                                    <p class="sm-info-value sm-info-date">

                                        <svg
                                            class="sm-date-icon"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M8 2v4M16 2v4M3 10h18M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"
                                            />
                                        </svg>

                                        <span>
                                            {{ $tanggalTerima }}
                                        </span>

                                    </p>

                                </td>


                                <td>

                                    <span class="sm-info-label">
                                        Lokasi Arsip Fisik
                                    </span>

                                    <p class="sm-info-value">
                                        {{ $suratMasuk->lokasi_arsip_fisik ?: '-' }}
                                    </p>

                                </td>

                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>


            {{-- =============================================
                 PERIHAL
                 TINGGI MENGISI RUANG KOSONG
            ============================================== --}}

            <div class="sm-card sm-perihal-card">

                <div class="sm-card-header">

                    <div class="sm-card-heading">

                        <div class="sm-card-heading-icon">

                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M4 6h16M4 12h16M4 18h10"
                                />
                            </svg>

                        </div>


                        <div>

                            <h2 class="sm-card-title">
                                Perihal Surat
                            </h2>

                            <p class="sm-card-subtitle">
                                Pokok atau tujuan utama surat.
                            </p>

                        </div>

                    </div>

                </div>


                <div class="sm-perihal-body">

                    <div class="sm-perihal-box">

                        <p class="sm-perihal-text">
                            {{ $suratMasuk->perihal ?? 'Tanpa Perihal' }}
                        </p>

                    </div>

                </div>

            </div>

        </div>


        {{-- =================================================
             KANAN
             RIWAYAT DISPOSISI
        ================================================== --}}

        <div class="sm-right-column">

            <div class="sm-card sm-disposition-card">


                <div class="sm-card-header">

                    <div class="sm-card-heading">

                        <div
                            class="sm-card-heading-icon"
                            style="background:#f5f3ff;color:#7c3aed;"
                        >

                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"
                                />
                            </svg>

                        </div>


                        <div>

                            <h2 class="sm-card-title">
                                Riwayat Disposisi
                            </h2>

                            <p class="sm-card-subtitle">
                                Instruksi dan tindak lanjut surat.
                            </p>

                        </div>

                    </div>


                    @if(
                        $canManage &&
                        Route::has('disposisi.create')
                    )

                        <a
                            href="{{ route('disposisi.create', $suratMasuk) }}"
                            class="sm-create-btn"
                        >

                            <svg
                                class="w-3.5 h-3.5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2.5"
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>

                            Buat

                        </a>

                    @endif

                </div>


                <div class="sm-disposition-body">

                    @if($disposisis->count())

                        <div class="sm-timeline">

                            @foreach($disposisis as $d)

                                @php

                                    $disposisiStatus =
                                        strtolower(
                                            trim(
                                                (string) (
                                                    $d->status
                                                    ?? 'menunggu'
                                                )
                                            )
                                        );


                                    $disposisiLabels = [
                                        'menunggu' => 'Menunggu',
                                        'diproses' => 'Diproses',
                                        'selesai'  => 'Selesai',
                                        'ditolak'  => 'Ditolak',
                                    ];


                                    $disposisiClasses = [
                                        'menunggu' => 'sm-disp-menunggu',
                                        'diproses' => 'sm-disp-diproses',
                                        'selesai'  => 'sm-disp-selesai',
                                        'ditolak'  => 'sm-disp-ditolak',
                                    ];


                                    $disposisiLabel =
                                        $disposisiLabels[
                                            $disposisiStatus
                                        ]
                                        ?? ucfirst(
                                            $disposisiStatus
                                        );


                                    $disposisiClass =
                                        $disposisiClasses[
                                            $disposisiStatus
                                        ]
                                        ?? 'sm-disp-menunggu';


                                    $tanggalDisposisi = '-';


                                    if ($d->created_at) {

                                        try {

                                            $tanggalDisposisi =
                                                Carbon::parse(
                                                    $d->created_at
                                                )->translatedFormat(
                                                    'd/m/Y H:i'
                                                );

                                        } catch (\Throwable $e) {

                                            $tanggalDisposisi =
                                                (string) $d->created_at;

                                        }

                                    }


                                    $batasWaktu = null;


                                    if ($d->batas_waktu) {

                                        try {

                                            $batasWaktu =
                                                Carbon::parse(
                                                    $d->batas_waktu
                                                )->translatedFormat(
                                                    'd/m/Y'
                                                );

                                        } catch (\Throwable $e) {

                                            $batasWaktu =
                                                (string) $d->batas_waktu;

                                        }

                                    }


                                    $isiDisposisi =
                                        $d->isi_disposisi
                                        ?? $d->instruksi
                                        ?? $d->catatan
                                        ?? '';

                                @endphp


                                <div class="sm-timeline-item">

                                    <div class="sm-timeline-line"></div>

                                    <div class="sm-timeline-dot"></div>


                                    <div class="sm-disp-route">

                                        <span class="sm-disp-from">
                                            {{ $d->dari?->name ?? '-' }}
                                        </span>


                                        <svg
                                            class="w-3 h-3 text-purple-300"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M14 5l7 7m0 0l-7 7m7-7H3"
                                            />
                                        </svg>


                                        <span class="sm-disp-to">
                                            {{ $d->kepada?->name ?? '-' }}
                                        </span>

                                    </div>


                                    <span
                                        class="sm-disp-status {{ $disposisiClass }}"
                                    >
                                        {{ $disposisiLabel }}
                                    </span>


                                    <div class="sm-disp-content">

                                        <p class="sm-disp-text">

                                            @if(filled($isiDisposisi))

                                                {{ $isiDisposisi }}

                                            @else

                                                <span class="text-slate-300">
                                                    Tidak ada instruksi atau catatan.
                                                </span>

                                            @endif

                                        </p>

                                    </div>


                                    <div class="sm-disp-meta">

                                        @if($batasWaktu)

                                            <span class="sm-disp-deadline">
                                                Batas:
                                                {{ $batasWaktu }}
                                            </span>

                                        @endif


                                        <span class="sm-disp-date">
                                            {{ $tanggalDisposisi }}
                                        </span>

                                    </div>

                                </div>

                            @endforeach

                        </div>

                    @else

                        <div class="sm-empty">

                            <div class="sm-empty-icon">

                                <svg
                                    class="w-6 h-6"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.5"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                    />
                                </svg>

                            </div>


                            <p class="sm-empty-title">
                                Belum ada disposisi
                            </p>


                            <p class="sm-empty-text">
                                Belum ada instruksi atau tindak lanjut.
                            </p>


                            @if(
                                $canManage &&
                                Route::has('disposisi.create')
                            )

                                <a
                                    href="{{ route('disposisi.create', $suratMasuk) }}"
                                    class="sm-create-btn"
                                    style="margin-top:12px;"
                                >

                                    <svg
                                        class="w-3.5 h-3.5"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2.5"
                                            d="M12 4v16m8-8H4"
                                        />
                                    </svg>

                                    Buat Disposisi

                                </a>

                            @endif

                        </div>

                    @endif

                </div>

            </div>

        </div>

    </div>


    {{-- =====================================================
         LAMPIRAN DIGITAL
         FULL WIDTH DI BAWAH
    ====================================================== --}}

    <div class="sm-card sm-attachment-card">


        <div class="sm-card-header">

            <div class="sm-card-heading">

                <div class="sm-card-heading-icon">

                    <svg
                        class="w-4 h-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586A1.5 1.5 0 0118 8.5V19a2 2 0 01-2 2z"
                        />
                    </svg>

                </div>


                <div>

                    <h2 class="sm-card-title">
                        Berkas Lampiran Digital
                    </h2>

                    <p class="sm-card-subtitle">
                        Dokumen digital yang tersimpan pada arsip surat ini.
                    </p>

                </div>

            </div>


            @if($lampiranExtension)

                <span class="sm-extension">
                    .{{ $lampiranExtension }}
                </span>

            @endif

        </div>


        <div class="sm-attachment-body">

            @if(
                !empty($lampiranPath) &&
                !empty($lampiranUrl)
            )


                <div class="sm-file-bar">

                    <div class="sm-file-info">

                        <div class="sm-file-icon">

                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586A1.5 1.5 0 0118 8.5V19a2 2 0 01-2 2"
                                />
                            </svg>

                        </div>


                        <div class="min-w-0">

                            <p class="sm-file-name">
                                {{ $lampiranNama }}
                            </p>

                            <p class="sm-file-state">
                                Lampiran tersimpan
                            </p>

                        </div>

                    </div>


                    <div class="sm-file-actions">

                        <a
                            href="{{ $lampiranUrl }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="sm-file-btn sm-file-btn-open"
                        >

                            <svg
                                class="w-3.5 h-3.5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                                />
                            </svg>

                            Buka

                        </a>


                        <a
                            href="{{ $lampiranUrl }}"
                            download
                            class="sm-file-btn sm-file-btn-download"
                        >

                            <svg
                                class="w-3.5 h-3.5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                />
                            </svg>

                            Unduh

                        </a>

                    </div>

                </div>


                {{-- =================================================
                     VIEWER
                ================================================== --}}

                <div class="sm-viewer">

                    @if($isImage)

                        <div class="sm-image-wrapper">

                            <img
                                src="{{ $lampiranUrl }}"
                                alt="Lampiran Surat Masuk"
                                class="sm-image"
                                loading="lazy"
                            >

                        </div>


                    @elseif($isPdf)

                        <iframe
                            src="{{ $lampiranUrl }}"
                            title="Pratinjau PDF Surat Masuk"
                            class="sm-pdf"
                            loading="lazy"
                        ></iframe>


                    @else

                        <div class="sm-viewer-empty">

                            <svg
                                class="w-10 h-10 text-slate-300"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.5"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586A1.5 1.5 0 0118 8.5V19a2 2 0 01-2 2"
                                />
                            </svg>


                            <p class="sm-viewer-empty-title">
                                Pratinjau tidak tersedia
                            </p>


                            <p class="sm-viewer-empty-text">
                                Format

                                <strong>
                                    .{{ $lampiranExtension ?: 'dokumen' }}
                                </strong>

                                tidak dapat ditampilkan langsung.
                                Gunakan tombol Buka atau Unduh.
                            </p>

                        </div>

                    @endif

                </div>

            @else

                <div class="sm-no-file">

                    <svg
                        class="w-4 h-4 shrink-0 text-slate-300"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.5"
                            d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636"
                        />
                    </svg>


                    <p>
                        Tidak ada berkas digital yang dilampirkan pada surat ini.
                    </p>

                </div>

            @endif

        </div>

    </div>


    {{-- =====================================================
         FOOTER
    ====================================================== --}}

    <div class="sm-system-footer">

        <span>
            Sistem Kendali Surat Masuk
        </span>

    </div>

</div>

@endsection