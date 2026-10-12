@extends('layouts.app')

@section('title', 'Edit Surat Keluar')

@section('content')

@php
    $compressionResult = session('compression_result');
@endphp

<style>
    .sk-page,
    .sk-page * {
        box-sizing: border-box;
    }

    .sk-page {
        width: 100%;
        max-width: 1180px;
        margin: 0 auto;
        padding: 14px 18px 30px;
        color: #1e293b;
    }

    .sk-topbar {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 12px;
        margin-bottom: 12px;
    }

    .sk-back {
        min-height: 35px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 0 12px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #fff;
        color: #475569;
        font-size: 10px;
        font-weight: 800;
        text-decoration: none;
        transition: .15s ease;
    }

    .sk-back:hover {
        border-color: #94a3b8;
        background: #f8fafc;
        color: #1e293b;
    }

    .sk-shell {
        overflow: hidden;
        border: 1.5px solid #94a3b8;
        border-radius: 14px;
        background: #fff;
        box-shadow:
            0 12px 30px rgba(15, 23, 42, .07),
            0 2px 6px rgba(15, 23, 42, .04);
    }

    .sk-header {
        position: relative;
        overflow: hidden;
        padding: 18px 20px;
        background: linear-gradient(
            135deg,
            #047857 0%,
            #059669 45%,
            #2563eb 100%
        );
        color: #fff;
    }

    .sk-header::after {
        content: "";
        position: absolute;
        width: 220px;
        height: 220px;
        right: -75px;
        bottom: -90px;
        border-radius: 50%;
        background: rgba(255,255,255,.08);
    }

    .sk-header-inner {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
    }

    .sk-header-main {
        display: flex;
        align-items: center;
        gap: 11px;
        min-width: 0;
    }

    .sk-header-icon {
        width: 42px;
        height: 42px;
        flex: 0 0 42px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(255,255,255,.30);
        border-radius: 10px;
        background: rgba(255,255,255,.13);
        backdrop-filter: blur(5px);
    }

    .sk-header-kicker {
        margin: 0;
        font-size: 8px;
        font-weight: 800;
        letter-spacing: .1em;
        text-transform: uppercase;
        color: rgba(255,255,255,.78);
    }

    .sk-header-title {
        margin: 2px 0 0;
        font-size: 19px;
        line-height: 1.3;
        font-weight: 850;
    }

    .sk-header-desc {
        margin: 3px 0 0;
        max-width: 700px;
        font-size: 8.5px;
        line-height: 1.5;
        color: rgba(255,255,255,.83);
    }

    .sk-header-badge {
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        min-height: 28px;
        padding: 0 9px;
        border: 1px solid rgba(255,255,255,.25);
        border-radius: 999px;
        background: rgba(255,255,255,.11);
        font-size: 8px;
        font-weight: 800;
        white-space: nowrap;
    }

    .sk-body {
        padding: 16px;
    }

    .sk-error {
        margin-bottom: 12px;
        padding: 11px 13px;
        border: 1px solid #fecaca;
        border-radius: 9px;
        background: #fff7f7;
        color: #be123c;
    }

    .sk-error-title {
        margin: 0;
        font-size: 10px;
        font-weight: 800;
    }

    .sk-error-list {
        margin: 4px 0 0;
        padding-left: 16px;
        font-size: 8px;
        line-height: 1.55;
    }

    .sk-server-result {
        margin-bottom: 12px;
        padding: 11px;
        border: 1px solid #a7f3d0;
        border-radius: 9px;
        background: #ecfdf5;
    }

    .sk-server-title {
        margin: 0 0 7px;
        font-size: 10px;
        font-weight: 800;
        color: #047857;
    }

    .sk-server-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 6px;
    }

    .sk-server-item {
        padding: 7px;
        border: 1px solid #bbf7d0;
        border-radius: 7px;
        background: rgba(255,255,255,.72);
    }

    .sk-server-label {
        display: block;
        margin-bottom: 2px;
        font-size: 6.5px;
        color: #64748b;
    }

    .sk-server-value {
        font-size: 8px;
        font-weight: 800;
        color: #047857;
    }

    .sk-section + .sk-section {
        margin-top: 17px;
        padding-top: 17px;
        border-top: 1.5px solid #e2e8f0;
    }

    .sk-section-head {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        margin-bottom: 10px;
    }

    .sk-section-marker {
        width: 4px;
        min-height: 28px;
        flex: 0 0 4px;
        border-radius: 999px;
        background: #059669;
    }

    .sk-section-marker-blue {
        background: #2563eb;
    }

    .sk-section-title {
        margin: 0;
        font-size: 13px;
        line-height: 1.3;
        font-weight: 850;
        color: #1e293b;
    }

    .sk-section-desc {
        margin: 3px 0 0;
        font-size: 8px;
        line-height: 1.45;
        color: #64748b;
    }

    .sk-field-table {
        overflow: hidden;
        border: 1.5px solid #94a3b8;
        border-radius: 10px;
    }

    .sk-field-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .sk-field {
        min-width: 0;
        padding: 11px;
        border-right: 1.5px solid #cbd5e1;
        border-bottom: 1.5px solid #cbd5e1;
        background: #fff;
    }

    .sk-field:nth-child(2n) {
        border-right: 0;
    }

    .sk-field-full {
        grid-column: 1 / -1;
        border-right: 0;
    }

    .sk-field-label {
        display: block;
        margin-bottom: 5px;
        font-size: 9px;
        line-height: 1.3;
        font-weight: 850;
        color: #475569;
    }

    .sk-required {
        color: #dc2626;
    }

    .sk-control {
        width: 100%;
        min-height: 40px;
        border: 1.5px solid #94a3b8;
        border-radius: 8px;
        background: #fff;
        color: #1e293b;
        padding: 8px 10px;
        font-size: 11px;
        line-height: 1.4;
        outline: none;
        transition: .15s ease;
    }

    input.sk-control,
    select.sk-control {
        height: 40px;
        padding: 0 10px;
    }

    textarea.sk-control {
        min-height: 82px;
        resize: vertical;
    }

    .sk-control::placeholder {
        color: #94a3b8;
    }

    .sk-control:hover {
        border-color: #64748b;
    }

    .sk-control:focus {
        border-color: #059669;
        box-shadow: 0 0 0 3px rgba(5,150,105,.08);
    }

    .sk-control-error {
        border-color: #ef4444 !important;
        background: #fff7f7 !important;
    }

    .sk-field-error {
        margin: 4px 0 0;
        font-size: 7.5px;
        line-height: 1.45;
        color: #dc2626;
        font-weight: 700;
    }

    .sk-attachment {
        overflow: hidden;
        border: 1.5px solid #94a3b8;
        border-radius: 10px;
        background: #fff;
    }

    .sk-attachment-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 10px 11px;
        border-bottom: 1.5px solid #bfdbfe;
        background: #eff6ff;
    }

    .sk-attachment-header-left {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
    }

    .sk-attachment-icon {
        width: 32px;
        height: 32px;
        flex: 0 0 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #bfdbfe;
        border-radius: 7px;
        background: #fff;
        color: #2563eb;
    }

    .sk-attachment-title {
        margin: 0;
        font-size: 10px;
        font-weight: 850;
        color: #1e293b;
    }

    .sk-attachment-desc {
        margin: 2px 0 0;
        font-size: 7px;
        color: #64748b;
    }

    .sk-badge {
        padding: 4px 7px;
        border: 1px solid #cbd5e1;
        border-radius: 999px;
        background: #fff;
        color: #64748b;
        font-size: 6.5px;
        font-weight: 800;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .sk-attachment-body {
        padding: 11px;
    }

    .sk-info-box {
        display: flex;
        align-items: flex-start;
        gap: 7px;
        margin-bottom: 9px;
        padding: 8px;
        border: 1px solid #c7d2fe;
        border-radius: 8px;
        background: #eef2ff;
        color: #4338ca;
    }

    .sk-info-box svg {
        width: 14px;
        height: 14px;
        flex: 0 0 14px;
        margin-top: 1px;
    }

    .sk-info-box p {
        margin: 0;
        font-size: 7.5px;
        line-height: 1.55;
    }

    .sk-file-summary {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 6px;
        margin-bottom: 9px;
    }

    .sk-summary-item {
        padding: 7px 8px;
        border: 1px solid #e2e8f0;
        border-radius: 7px;
        background: #f8fafc;
    }

    .sk-summary-label {
        display: block;
        margin-bottom: 2px;
        font-size: 6.5px;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: .03em;
    }

    .sk-summary-value {
        font-size: 8px;
        font-weight: 800;
        color: #334155;
    }

    .sk-upload-box {
        position: relative;
        min-height: 135px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 15px;
        border: 1.5px dashed #94a3b8;
        border-radius: 9px;
        background: #f8fafc;
        text-align: center;
        cursor: pointer;
        transition: .15s ease;
    }

    .sk-upload-box:hover {
        border-color: #60a5fa;
        background: #eff6ff;
    }

    .sk-upload-box.has-file {
        border-color: #34d399;
        background: #ecfdf5;
    }

    .sk-upload-icon {
        width: 38px;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 7px;
        border: 1px solid #bfdbfe;
        border-radius: 8px;
        background: #dbeafe;
        color: #2563eb;
    }

    .sk-upload-box.has-file .sk-upload-icon {
        border-color: #a7f3d0;
        background: #d1fae5;
        color: #059669;
    }

    .sk-upload-title {
        font-size: 9px;
        font-weight: 850;
        color: #334155;
    }

    .sk-upload-subtitle {
        margin-top: 2px;
        font-size: 7px;
        color: #64748b;
    }

    .sk-upload-limit {
        margin-top: 4px;
        padding: 3px 7px;
        border-radius: 999px;
        background: #dbeafe;
        color: #2563eb;
        font-size: 6.5px;
        font-weight: 800;
    }

    .sk-upload-input {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
    }

    .sk-file-info {
        display: none;
        margin-top: 8px;
        padding: 8px;
        border: 1px solid #a7f3d0;
        border-radius: 8px;
        background: #ecfdf5;
    }

    .sk-file-info.show {
        display: block;
    }

    .sk-file-info-title {
        margin: 0;
        font-size: 8px;
        font-weight: 850;
        color: #047857;
    }

    .sk-file-info-name {
        margin: 2px 0 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 7.5px;
        font-weight: 750;
        color: #334155;
    }

    .sk-file-info-size {
        margin: 2px 0 0;
        font-size: 7px;
        color: #64748b;
    }

    .sk-file-info-actions {
        display: flex;
        justify-content: flex-end;
        margin-top: 5px;
    }

    .sk-clear-file {
        min-height: 26px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        padding: 0 8px;
        border: 1px solid #a7f3d0;
        border-radius: 6px;
        background: #fff;
        color: #059669;
        font-size: 7px;
        font-weight: 800;
        cursor: pointer;
    }

    .sk-clear-file:hover {
        background: #f0fdf4;
    }

    .sk-compression-panel {
        display: none;
        margin-top: 8px;
        padding: 9px;
        border: 1px solid #c7d2fe;
        border-radius: 8px;
        background: #eef2ff;
        color: #4338ca;
    }

    .sk-compression-panel.show {
        display: block;
    }

    .sk-compression-panel.success {
        border-color: #a7f3d0;
        background: #ecfdf5;
        color: #047857;
    }

    .sk-compression-panel.warning {
        border-color: #fde68a;
        background: #fffbeb;
        color: #a16207;
    }

    .sk-compression-panel.error {
        border-color: #fecaca;
        background: #fff1f2;
        color: #be123c;
    }

    .sk-compression-panel.processing {
        border-color: #bfdbfe;
        background: #eff6ff;
        color: #1d4ed8;
    }

    .sk-compression-title {
        margin: 0 0 6px;
        font-size: 8px;
        font-weight: 850;
    }

    .sk-compression-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 5px;
    }

    .sk-compression-item {
        padding: 6px;
        border: 1px solid rgba(148,163,184,.25);
        border-radius: 6px;
        background: rgba(255,255,255,.58);
    }

    .sk-compression-label {
        display: block;
        margin-bottom: 2px;
        font-size: 6px;
        color: #64748b;
    }

    .sk-compression-value {
        font-size: 7.5px;
        font-weight: 800;
        color: #334155;
    }

    .sk-compression-progress {
        display: none;
        margin-top: 8px;
    }

    .sk-compression-progress.show {
        display: block;
    }

    .sk-progress-track {
        position: relative;
        width: 100%;
        height: 7px;
        overflow: hidden;
        border-radius: 999px;
        background: #dbeafe;
    }

    .sk-progress-bar {
        position: absolute;
        left: -25%;
        top: 0;
        width: 28%;
        height: 100%;
        border-radius: 999px;
        background: #2563eb;
        animation: sk-progress-move 1.3s ease-in-out infinite;
    }

    @keyframes sk-progress-move {
        0% {
            left: -28%;
        }

        50% {
            left: 45%;
        }

        100% {
            left: 100%;
        }
    }

    .sk-compression-status {
        margin-top: 6px;
        font-size: 7px;
        line-height: 1.5;
        font-weight: 700;
    }

    .sk-preview {
        display: none;
        margin-top: 8px;
        overflow: hidden;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #0f172a;
    }

    .sk-preview.show {
        display: block;
    }

    .sk-preview-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 6px;
        padding: 7px 8px;
        border-bottom: 1px solid #334155;
        background: #111827;
        color: #fff;
    }

    .sk-preview-title {
        margin: 0;
        font-size: 7.5px;
        font-weight: 800;
    }

    .sk-preview-badge {
        padding: 3px 6px;
        border-radius: 999px;
        background: rgba(255,255,255,.10);
        color: #cbd5e1;
        font-size: 5.5px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .sk-preview-image {
        display: block;
        width: 100%;
        max-height: 430px;
        object-fit: contain;
        background: #fff;
    }

    .sk-preview-pdf {
        display: block;
        width: 100%;
        height: 430px;
        border: 0;
        background: #fff;
    }

    .sk-preview-caption {
        padding: 6px 8px;
        background: #111827;
        color: #94a3b8;
        font-size: 6.5px;
        line-height: 1.4;
    }

    .sk-footer {
        display: flex;
        justify-content: flex-end;
        gap: 7px;
        padding: 10px 13px;
        border-top: 1.5px solid #cbd5e1;
        background: #f8fafc;
    }

    .sk-footer-btn {
        min-height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 0 13px;
        border-radius: 8px;
        font-size: 8px;
        font-weight: 850;
        text-decoration: none;
        cursor: pointer;
        transition: .15s ease;
    }

    .sk-cancel {
        border: 1.5px solid #cbd5e1;
        background: #fff;
        color: #475569;
    }

    .sk-cancel:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
    }

    .sk-submit {
        border: 1.5px solid #059669;
        background: #059669;
        color: #fff;
        box-shadow: 0 3px 8px rgba(5,150,105,.14);
    }

    .sk-submit:hover:not(:disabled) {
        background: #047857;
        border-color: #047857;
    }

    .sk-submit:disabled {
        opacity: .6;
        cursor: not-allowed;
    }

    .sk-hidden {
        display: none !important;
    }

    @media (max-width: 900px) {
        .sk-server-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .sk-compression-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 640px) {
        .sk-page {
            padding: 7px 9px 20px;
        }

        .sk-back {
            width: 100%;
        }

        .sk-header-inner {
            align-items: flex-start;
            flex-direction: column;
        }

        .sk-header-badge {
            align-self: flex-start;
        }

        .sk-body {
            padding: 10px;
        }

        .sk-field-grid {
            grid-template-columns: 1fr;
        }

        .sk-field,
        .sk-field:nth-child(2n) {
            border-right: 0;
        }

        .sk-field-full {
            grid-column: auto;
        }

        .sk-file-summary {
            grid-template-columns: 1fr;
        }

        .sk-compression-grid {
            grid-template-columns: 1fr 1fr;
        }

        .sk-footer {
            flex-direction: column-reverse;
            align-items: stretch;
        }

        .sk-footer-btn {
            width: 100%;
        }

        .sk-preview-pdf {
            height: 330px;
        }

        .sk-preview-image {
            max-height: 330px;
        }
    }

    @media (max-width: 420px) {
        .sk-header {
            padding: 14px;
        }

        .sk-header-title {
            font-size: 16px;
        }

        .sk-header-desc {
            font-size: 7.5px;
        }

        .sk-compression-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="sk-page">

    <div class="sk-topbar">

        <a
            href="{{ route('surat-keluar.index') }}"
            class="sk-back"
        >
            <svg
                width="14"
                height="14"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M10 19l-7-7m0 0l7-7m-7 7h18"
                />
            </svg>

            Kembali
        </a>

    </div>

    @if($errors->any())

        <div class="sk-error">

            <p class="sk-error-title">
                Data belum dapat disimpan.
            </p>

            <ul class="sk-error-list">

                @foreach($errors->all() as $error)

                    <li>
                        {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif

    @if(is_array($compressionResult))

        <div class="sk-server-result">

            <p class="sk-server-title">
                Hasil Compression Server
            </p>

            <div class="sk-server-grid">

                <div class="sk-server-item">

                    <span class="sk-server-label">
                        Jenis
                    </span>

                    <span class="sk-server-value">
                        {{ strtoupper($compressionResult['type'] ?? '-') }}
                    </span>

                </div>

                <div class="sk-server-item">

                    <span class="sk-server-label">
                        Ukuran Asli
                    </span>

                    <span class="sk-server-value">
                        {{ number_format(($compressionResult['original_size'] ?? 0) / 1048576, 2) }} MB
                    </span>

                </div>

                <div class="sk-server-item">

                    <span class="sk-server-label">
                        Ukuran Akhir
                    </span>

                    <span class="sk-server-value">
                        {{ number_format(($compressionResult['compressed_size'] ?? 0) / 1048576, 2) }} MB
                    </span>

                </div>

                <div class="sk-server-item">

                    <span class="sk-server-label">
                        Penghematan
                    </span>

                    <span class="sk-server-value">
                        {{ number_format((float) ($compressionResult['saving_percent'] ?? 0), 2) }}%
                    </span>

                </div>

            </div>

            @if(!empty($compressionResult['profile']))

                <div style="margin-top:6px;font-size:7px;color:#047857;">

                    Profile:
                    <strong>
                        {{ $compressionResult['profile'] }}
                    </strong>

                </div>

            @endif

        </div>

    @endif

    <form
        id="form-surat-keluar"
        method="POST"
        action="{{ route('surat-keluar.update', $suratKeluar) }}"
        enctype="multipart/form-data"
        novalidate
    >

        @csrf
        @method('PUT')

        <div class="sk-shell">

            <header class="sk-header">

                <div class="sk-header-inner">

                    <div class="sk-header-main">

                        <div class="sk-header-icon">

                            <svg
                                width="21"
                                height="21"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M6 3h8l5 5v13H6a2 2 0 01-2-2V5a2 2 0 012-2z"
                                />

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M14 3v6h5M8 13h8M8 17h6"
                                />
                            </svg>

                        </div>

                        <div>

                            <p class="sk-header-kicker">
                                Sistem E-Arsip
                            </p>

                            <h1 class="sk-header-title">
                                Catat Surat Keluar
                            </h1>

                            <p class="sk-header-desc">
                                Perbarui data surat keluar. Lampiran lama tetap digunakan jika tidak memilih file baru.
                                PDF tetap PDF dan diproses menggunakan
                                Ghostscript. JPG/JPEG/PNG dioptimalkan menjadi JPG.
                            </p>

                        </div>

                    </div>

                    <span class="sk-header-badge">
                        Arsip Surat Keluar
                    </span>

                </div>

            </header>

            <div class="sk-body">

                <section class="sk-section">

                    <div class="sk-section-head">

                        <div class="sk-section-marker"></div>

                        <div>

                            <h2 class="sk-section-title">
                                Informasi Utama Surat
                            </h2>

                            <p class="sk-section-desc">
                                Lengkapi identitas, tanggal, kategori,
                                status, dan isi surat keluar.
                            </p>

                        </div>

                    </div>

                    <div class="sk-field-table">

                        <div class="sk-field-grid">

                            <div class="sk-field">

                                <label
                                    for="nomor_surat"
                                    class="sk-field-label"
                                >
                                    Nomor Surat
                                    <span class="sk-required">*</span>
                                </label>

                                <input
                                    type="text"
                                    id="nomor_surat"
                                    name="nomor_surat"
                                    value="{{ old('nomor_surat', $suratKeluar->nomor_surat) }}"
                                    maxlength="255"
                                    required
                                    autocomplete="off"
                                    placeholder="Contoh: 005/SK/I/2026"
                                    class="sk-control @error('nomor_surat') sk-control-error @enderror"
                                >

                                @error('nomor_surat')

                                    <p class="sk-field-error">
                                        {{ $message }}
                                    </p>

                                @enderror

                            </div>

                            <div class="sk-field">

                                <label
                                    for="pengirim"
                                    class="sk-field-label"
                                >
                                    Tujuan Surat
                                    <span class="sk-required">*</span>
                                </label>

                                <input
                                    type="text"
                                    id="pengirim"
                                    name="pengirim"
                                    value="{{ old('pengirim', $suratKeluar->pengirim) }}"
                                    maxlength="150"
                                    required
                                    autocomplete="organization"
                                    placeholder="Contoh: PT Maju Takgentar"
                                    class="sk-control @error('pengirim') sk-control-error @enderror"
                                >

                                @error('pengirim')

                                    <p class="sk-field-error">
                                        {{ $message }}
                                    </p>

                                @enderror

                            </div>

                            <div class="sk-field">

                                <label
                                    for="tanggal_surat"
                                    class="sk-field-label"
                                >
                                    Tanggal Surat
                                    <span class="sk-required">*</span>
                                </label>

                                <input
                                    type="date"
                                    id="tanggal_surat"
                                    name="tanggal_surat"
                                    value="{{ old('tanggal_surat', $suratKeluar->tanggal_surat ? \Illuminate\Support\Carbon::parse($suratKeluar->tanggal_surat)->format('Y-m-d') : now()->format('Y-m-d')) }}"
                                    required
                                    class="sk-control @error('tanggal_surat') sk-control-error @enderror"
                                >

                                @error('tanggal_surat')

                                    <p class="sk-field-error">
                                        {{ $message }}
                                    </p>

                                @enderror

                            </div>

                            <div class="sk-field">

                                <label
                                    for="tanggal_keluar"
                                    class="sk-field-label"
                                >
                                    Tanggal Keluar
                                    <span class="sk-required">*</span>
                                </label>

                                <input
                                    type="date"
                                    id="tanggal_keluar"
                                    name="tanggal_keluar"
                                    value="{{ old('tanggal_keluar', $suratKeluar->tanggal_keluar ? \Illuminate\Support\Carbon::parse($suratKeluar->tanggal_keluar)->format('Y-m-d') : now()->format('Y-m-d')) }}"
                                    required
                                    class="sk-control @error('tanggal_keluar') sk-control-error @enderror"
                                >

                                @error('tanggal_keluar')

                                    <p class="sk-field-error">
                                        {{ $message }}
                                    </p>

                                @enderror

                            </div>

                            <div class="sk-field">

                                <label
                                    for="kategori_surat_id"
                                    class="sk-field-label"
                                >
                                    Kategori Surat
                                    <span class="sk-required">*</span>
                                </label>

                                <select
                                    id="kategori_surat_id"
                                    name="kategori_surat_id"
                                    required
                                    class="sk-control @error('kategori_surat_id') sk-control-error @enderror"
                                >

                                    <option
                                        value=""
                                        disabled
                                        @selected(!old('kategori_surat_id'))
                                    >
                                        Pilih kategori surat
                                    </option>

                                    @foreach(($kategoris ?? collect()) as $kategori)

                                        <option
                                            value="{{ $kategori->id }}"
                                            @selected(
                                                (string) old('kategori_surat_id', $suratKeluar->kategori_surat_id) ===
                                                (string) $kategori->id
                                            )
                                        >
                                            {{ $kategori->nama_kategori }}

                                            @if(!empty($kategori->sifat))

                                                ({{ ucfirst($kategori->sifat) }})

                                            @endif

                                        </option>

                                    @endforeach

                                </select>

                                @if(
                                    !isset($kategoris) ||
                                    $kategoris->isEmpty()
                                )

                                    <p
                                        class="sk-field-error"
                                        style="color:#a16207;"
                                    >
                                        Belum ada kategori surat yang tersedia.
                                    </p>

                                @endif

                                @error('kategori_surat_id')

                                    <p class="sk-field-error">
                                        {{ $message }}
                                    </p>

                                @enderror

                            </div>

                            <div class="sk-field">

                                <label
                                    for="status"
                                    class="sk-field-label"
                                >
                                    Status Surat
                                    <span class="sk-required">*</span>
                                </label>

                                <select
                                    id="status"
                                    name="status"
                                    required
                                    class="sk-control @error('status') sk-control-error @enderror"
                                >

                                    <option
                                        value="draft"
                                        @selected(old('status', $suratKeluar->status) === 'draft')
                                    >
                                        Draft
                                    </option>

                                    <option
                                        value="diproses"
                                        @selected(old('status', $suratKeluar->status) === 'diproses')
                                    >
                                        Diproses
                                    </option>

                                    <option
                                        value="disetujui"
                                        @selected(old('status', $suratKeluar->status) === 'disetujui')
                                    >
                                        Disetujui
                                    </option>

                                    <option
                                        value="dikirim"
                                        @selected(old('status', $suratKeluar->status) === 'dikirim')
                                    >
                                        Dikirim
                                    </option>

                                    <option
                                        value="diarsipkan"
                                        @selected(old('status', $suratKeluar->status) === 'diarsipkan')
                                    >
                                        Diarsipkan
                                    </option>

                                </select>

                                @error('status')

                                    <p class="sk-field-error">
                                        {{ $message }}
                                    </p>

                                @enderror

                            </div>

                            <div class="sk-field sk-field-full">

                                <label
                                    for="perihal"
                                    class="sk-field-label"
                                >
                                    Perihal
                                    <span class="sk-required">*</span>
                                </label>

                                <textarea
                                    id="perihal"
                                    name="perihal"
                                    rows="3"
                                    maxlength="255"
                                    required
                                    placeholder="Tuliskan perihal surat..."
                                    class="sk-control @error('perihal') sk-control-error @enderror"
                                >{{ old('perihal', $suratKeluar->perihal) }}</textarea>

                                @error('perihal')

                                    <p class="sk-field-error">
                                        {{ $message }}
                                    </p>

                                @enderror

                            </div>

                        </div>

                    </div>

                </section>

                <section class="sk-section">

                    <div class="sk-section-head">

                        <div class="sk-section-marker sk-section-marker-blue"></div>

                        <div>

                            <h2 class="sk-section-title">
                                Lampiran Berkas Digital
                            </h2>

                            <p class="sk-section-desc">
                                PDF akan dikompresi server dan hasilnya dapat
                                dilihat sebelum disimpan. Gambar dikompresi
                                langsung di browser.
                            </p>

                        </div>

                    </div>

                    <div class="sk-attachment">

                        <div class="sk-attachment-header">

                            <div class="sk-attachment-header-left">

                                <div class="sk-attachment-icon">

                                    <svg
                                        width="16"
                                        height="16"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M7 3h7l4 4v14H7a2 2 0 01-2-2V5a2 2 0 012-2z"
                                        />

                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M14 3v5h5"
                                        />
                                    </svg>

                                </div>

                                <div>

                                    <h3 class="sk-attachment-title">
                                        Dokumen Surat
                                    </h3>

                                    <p class="sk-attachment-desc">
                                        PDF, JPG, JPEG, PNG · maksimal 20 MB
                                    </p>

                                </div>

                            </div>

                            <span class="sk-badge">
                                Opsional
                            </span>

                        </div>

                        <div class="sk-attachment-body">

                            @if(!empty($suratKeluar->lampiran_file))
                                <div style="margin-bottom:9px;padding:9px;border:1px solid #a7f3d0;border-radius:8px;background:#ecfdf5;color:#047857;font-size:8px;line-height:1.5;">
                                    <strong>Lampiran saat ini tersedia.</strong>
                                    Pilih file baru hanya jika ingin mengganti lampiran. Jika tidak memilih file baru, lampiran lama akan tetap disimpan.
                                    @if(!empty($fileUrl))
                                        <div style="margin-top:5px;">
                                            <a href="{{ $fileUrl }}" target="_blank" rel="noopener" style="color:#047857;font-weight:800;text-decoration:underline;">Lihat lampiran saat ini</a>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <div class="sk-info-box">

                                <svg
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <circle
                                        cx="12"
                                        cy="12"
                                        r="9"
                                    />

                                    <path
                                        stroke-linecap="round"
                                        d="M12 10v6M12 7h.01"
                                    />
                                </svg>

                                <p>

                                    <strong>PDF:</strong>
                                    file tetap PDF dan dikompresi
                                    oleh Ghostscript di server.

                                    <br>

                                    <strong>JPG/JPEG/PNG:</strong>
                                    diproses di browser, di-resize
                                    bila perlu, kemudian dikonversi
                                    menjadi JPG.

                                </p>

                            </div>

                            <div class="sk-file-summary">

                                <div class="sk-summary-item">

                                    <span class="sk-summary-label">
                                        Batas Upload
                                    </span>

                                    <span class="sk-summary-value">
                                        20 MB
                                    </span>

                                </div>

                                <div class="sk-summary-item">

                                    <span class="sk-summary-label">
                                        PDF
                                    </span>

                                    <span class="sk-summary-value">
                                        Ghostscript
                                    </span>

                                </div>

                                <div class="sk-summary-item">

                                    <span class="sk-summary-label">
                                        Gambar
                                    </span>

                                    <span class="sk-summary-value">
                                        JPG optimized
                                    </span>

                                </div>

                            </div>

                            <label
                                id="sk-upload-box"
                                for="lampiran_file"
                                class="sk-upload-box"
                            >

                                <div
                                    id="sk-upload-icon"
                                    class="sk-upload-icon"
                                >

                                    <svg
                                        width="19"
                                        height="19"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M12 16V4m0 0L8 8m4-4l4 4M5 15v2a2 2 0 002 2h10a2 2 0 002-2v-2"
                                        />
                                    </svg>

                                </div>

                                <span
                                    id="sk-upload-title"
                                    class="sk-upload-title"
                                >
                                    Klik untuk memilih file pengganti
                                </span>

                                <span
                                    id="sk-upload-subtitle"
                                    class="sk-upload-subtitle"
                                >
                                    PDF, JPG, JPEG, PNG
                                </span>

                                <span class="sk-upload-limit">
                                    Maksimal 20 MB
                                </span>

                                <input
                                    id="lampiran_file"
                                    name="lampiran_file"
                                    type="file"
                                    accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                    class="sk-upload-input"
                                >

                            </label>

                            <div
                                id="sk-file-info"
                                class="sk-file-info"
                            >

                                <p
                                    id="sk-file-info-title"
                                    class="sk-file-info-title"
                                >
                                    File siap digunakan
                                </p>

                                <p
                                    id="sk-file-info-name"
                                    class="sk-file-info-name"
                                ></p>

                                <p
                                    id="sk-file-info-size"
                                    class="sk-file-info-size"
                                ></p>

                                <div class="sk-file-info-actions">

                                    <button
                                        type="button"
                                        id="sk-clear-file"
                                        class="sk-clear-file"
                                    >
                                        Hapus File
                                    </button>

                                </div>

                            </div>

                            <div
                                id="sk-compression-panel"
                                class="sk-compression-panel"
                            >

                                <p
                                    id="sk-compression-title"
                                    class="sk-compression-title"
                                >
                                    Informasi Compression
                                </p>

                                <div class="sk-compression-grid">

                                    <div class="sk-compression-item">

                                        <span class="sk-compression-label">
                                            Format Asli
                                        </span>

                                        <span
                                            id="sk-original-format"
                                            class="sk-compression-value"
                                        >
                                            -
                                        </span>

                                    </div>

                                    <div class="sk-compression-item">

                                        <span class="sk-compression-label">
                                            Format Akhir
                                        </span>

                                        <span
                                            id="sk-final-format"
                                            class="sk-compression-value"
                                        >
                                            -
                                        </span>

                                    </div>

                                    <div class="sk-compression-item">

                                        <span class="sk-compression-label">
                                            Ukuran Asli
                                        </span>

                                        <span
                                            id="sk-original-size"
                                            class="sk-compression-value"
                                        >
                                            -
                                        </span>

                                    </div>

                                    <div class="sk-compression-item">

                                        <span class="sk-compression-label">
                                            Ukuran Hasil
                                        </span>

                                        <span
                                            id="sk-final-size"
                                            class="sk-compression-value"
                                        >
                                            -
                                        </span>

                                    </div>

                                </div>

                                <div
                                    id="sk-compression-progress"
                                    class="sk-compression-progress"
                                >

                                    <div class="sk-progress-track">

                                        <div class="sk-progress-bar"></div>

                                    </div>

                                    <div
                                        id="sk-compression-status"
                                        class="sk-compression-status"
                                    >
                                        Menunggu proses...
                                    </div>

                                </div>

                                <div
                                    style="
                                        margin-top:6px;
                                        font-size:7px;
                                        line-height:1.5;
                                    "
                                >

                                    <span
                                        id="sk-compression-message"
                                    ></span>

                                </div>

                            </div>

                            <div
                                id="sk-preview"
                                class="sk-preview"
                            >

                                <div class="sk-preview-header">

                                    <p
                                        id="sk-preview-title"
                                        class="sk-preview-title"
                                    >
                                        Preview Dokumen
                                    </p>

                                    <span
                                        id="sk-preview-badge"
                                        class="sk-preview-badge"
                                    >
                                        Preview
                                    </span>

                                </div>

                                <img
                                    id="sk-preview-image"
                                    class="sk-preview-image sk-hidden"
                                    alt="Preview dokumen"
                                >

                                <iframe
                                    id="sk-preview-pdf"
                                    class="sk-preview-pdf sk-hidden"
                                    title="Preview PDF"
                                ></iframe>

                                <div
                                    id="sk-preview-caption"
                                    class="sk-preview-caption"
                                >
                                    File baru akan dikirim setelah formulir disimpan. Lampiran lama tetap ada jika tidak ada file baru yang dipilih.
                                </div>

                            </div>

                            @error('lampiran_file')

                                <p class="sk-field-error">
                                    {{ $message }}
                                </p>

                            @enderror

                        </div>

                    </div>

                </section>

            </div>

            <div class="sk-footer">

                <a
                    href="{{ route('surat-keluar.index') }}"
                    class="sk-footer-btn sk-cancel"
                >

                    <svg
                        width="14"
                        height="14"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>

                    Batal

                </a>

                <button
                    type="submit"
                    id="sk-submit"
                    class="sk-footer-btn sk-submit"
                >

                    <svg
                        id="sk-submit-icon"
                        width="14"
                        height="14"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>

                    <svg
                        id="sk-submit-loading"
                        class="sk-hidden"
                        width="14"
                        height="14"
                        viewBox="0 0 24 24"
                        fill="none"
                    >
                        <circle
                            cx="12"
                            cy="12"
                            r="9"
                            stroke="currentColor"
                            stroke-width="3"
                            opacity=".30"
                        />

                        <path
                            d="M21 12a9 9 0 00-9-9"
                            stroke="currentColor"
                            stroke-width="3"
                            stroke-linecap="round"
                        />
                    </svg>

                    <span id="sk-submit-text">
                        Simpan Perubahan
                    </span>

                </button>

            </div>

        </div>

    </form>

</div>

@push('scripts')

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener(
    'DOMContentLoaded',
    function () {

        'use strict';

        const form =
            document.getElementById(
                'form-surat-keluar'
            );

        const fileInput =
            document.getElementById(
                'lampiran_file'
            );

        const uploadBox =
            document.getElementById(
                'sk-upload-box'
            );

        const uploadTitle =
            document.getElementById(
                'sk-upload-title'
            );

        const uploadSubtitle =
            document.getElementById(
                'sk-upload-subtitle'
            );

        const fileInfo =
            document.getElementById(
                'sk-file-info'
            );

        const fileInfoTitle =
            document.getElementById(
                'sk-file-info-title'
            );

        const fileInfoName =
            document.getElementById(
                'sk-file-info-name'
            );

        const fileInfoSize =
            document.getElementById(
                'sk-file-info-size'
            );

        const clearFileButton =
            document.getElementById(
                'sk-clear-file'
            );

        const compressionPanel =
            document.getElementById(
                'sk-compression-panel'
            );

        const compressionTitle =
            document.getElementById(
                'sk-compression-title'
            );

        const compressionMessage =
            document.getElementById(
                'sk-compression-message'
            );

        const originalFormat =
            document.getElementById(
                'sk-original-format'
            );

        const finalFormat =
            document.getElementById(
                'sk-final-format'
            );

        const originalSize =
            document.getElementById(
                'sk-original-size'
            );

        const finalSize =
            document.getElementById(
                'sk-final-size'
            );

        const compressionProgress =
            document.getElementById(
                'sk-compression-progress'
            );

        const compressionStatus =
            document.getElementById(
                'sk-compression-status'
            );

        const preview =
            document.getElementById(
                'sk-preview'
            );

        const previewTitle =
            document.getElementById(
                'sk-preview-title'
            );

        const previewBadge =
            document.getElementById(
                'sk-preview-badge'
            );

        const previewImage =
            document.getElementById(
                'sk-preview-image'
            );

        const previewPdf =
            document.getElementById(
                'sk-preview-pdf'
            );

        const previewCaption =
            document.getElementById(
                'sk-preview-caption'
            );

        const tanggalSurat =
            document.getElementById(
                'tanggal_surat'
            );

        const tanggalKeluar =
            document.getElementById(
                'tanggal_keluar'
            );

        const submitButton =
            document.getElementById(
                'sk-submit'
            );

        const submitIcon =
            document.getElementById(
                'sk-submit-icon'
            );

        const submitLoading =
            document.getElementById(
                'sk-submit-loading'
            );

        const submitText =
            document.getElementById(
                'sk-submit-text'
            );

        if (
            !form ||
            !fileInput
        ) {
            return;
        }

        const MAX_FILE_SIZE =
            20 *
            1024 *
            1024;

        const IMAGE_TARGET_SIZE =
            2.5 *
            1024 *
            1024;

        const IMAGE_MAX_RESULT_SIZE =
            9.5 *
            1024 *
            1024;

        const IMAGE_MAX_DIMENSION =
            2500;

        const IMAGE_MIN_DIMENSION =
            1000;

        const JPEG_QUALITIES = [
            0.90,
            0.86,
            0.82,
            0.78,
            0.74,
            0.70,
            0.66,
            0.62,
            0.58,
            0.54,
            0.50,
            0.46,
            0.42,
            0.38
        ];

        let submitting =
            false;

        let processingToken =
            0;

        let previewObjectUrl =
            null;

        function formatFileSize(
            bytes
        ) {

            if (
                !Number.isFinite(bytes) ||
                bytes <= 0
            ) {
                return '0 KB';
            }

            if (
                bytes < 1024
            ) {
                return bytes + ' B';
            }

            if (
                bytes < 1024 * 1024
            ) {
                return (
                    (
                        bytes / 1024
                    ).toFixed(1) +
                    ' KB'
                );
            }

            return (
                (
                    bytes /
                    1024 /
                    1024
                ).toFixed(2) +
                ' MB'
            );
        }

        function getExtension(
            file
        ) {

            return String(
                file?.name || ''
            )
                .split('.')
                .pop()
                .toLowerCase();
        }

        function isImageExtension(
            extension
        ) {

            return [
                'jpg',
                'jpeg',
                'png'
            ].includes(
                extension
            );
        }

        function isAllowedExtension(
            extension
        ) {

            return [
                'pdf',
                'jpg',
                'jpeg',
                'png'
            ].includes(
                extension
            );
        }

        function getReductionPercent(
            original,
            final
        ) {

            if (
                !original ||
                original <= 0
            ) {
                return 0;
            }

            return Math.max(
                0,
                Math.round(
                    (
                        1 -
                        (
                            final /
                            original
                        )
                    ) *
                    100
                )
            );
        }

        function showAlert(
            icon,
            title,
            text
        ) {

            if (
                typeof window.Swal !==
                'undefined'
            ) {

                window.Swal.fire({
                    icon,
                    title,
                    text:
                        String(
                            text ??
                            ''
                        ),
                    confirmButtonText:
                        'Mengerti',
                    confirmButtonColor:
                        '#059669'
                });

                return;
            }

            window.alert(
                String(
                    text ??
                    ''
                )
            );
        }

        function resetCompression() {

            compressionPanel.className =
                'sk-compression-panel';

            compressionTitle.textContent =
                'Informasi Compression';

            compressionMessage.textContent =
                '';

            originalFormat.textContent =
                '-';

            finalFormat.textContent =
                '-';

            originalSize.textContent =
                '-';

            finalSize.textContent =
                '-';

            compressionProgress.classList.remove(
                'show'
            );

            compressionStatus.textContent =
                '';
        }

        function showCompression(
            data,
            type = ''
        ) {

            compressionPanel.className =
                'sk-compression-panel show';

            if (
                type
            ) {

                compressionPanel.classList.add(
                    type
                );
            }

            compressionTitle.textContent =
                data.title ||
                'Informasi Compression';

            compressionMessage.innerHTML =
                data.message ||
                '';

            originalFormat.textContent =
                data.originalFormat ||
                '-';

            finalFormat.textContent =
                data.finalFormat ||
                '-';

            originalSize.textContent =
                data.originalSize ||
                '-';

            finalSize.textContent =
                data.finalSize ||
                '-';
        }

        function showCompressionProgress(
            message
        ) {

            compressionProgress.classList.add(
                'show'
            );

            compressionStatus.textContent =
                message ||
                'Sedang memproses...';
        }

        function hideCompressionProgress() {

            compressionProgress.classList.remove(
                'show'
            );

            compressionStatus.textContent =
                '';
        }

        function clearPreviewObjectUrl() {

            if (
                previewObjectUrl
            ) {

                URL.revokeObjectURL(
                    previewObjectUrl
                );

                previewObjectUrl =
                    null;
            }
        }

        function resetPreview() {

            preview.classList.remove(
                'show'
            );

            previewTitle.textContent =
                'Preview Dokumen';

            previewBadge.textContent =
                'Preview';

            previewCaption.textContent =
                'File baru akan dikirim setelah formulir disimpan. Lampiran lama tetap ada jika tidak ada file baru yang dipilih.';

            previewImage.classList.add(
                'sk-hidden'
            );

            previewPdf.classList.add(
                'sk-hidden'
            );

            previewImage.removeAttribute(
                'src'
            );

            previewPdf.removeAttribute(
                'src'
            );

            clearPreviewObjectUrl();
        }

        function showImagePreview(
            file
        ) {

            resetPreview();

            previewObjectUrl =
                URL.createObjectURL(
                    file
                );

            preview.classList.add(
                'show'
            );

            previewTitle.textContent =
                'Preview Gambar';

            previewBadge.textContent =
                'JPG';

            previewImage.classList.remove(
                'sk-hidden'
            );

            previewImage.src =
                previewObjectUrl;

            previewCaption.textContent =
                'Preview berasal dari gambar hasil kompresi browser.';
        }

        function showPdfPreviewOriginal(
            file
        ) {

            resetPreview();

            previewObjectUrl =
                URL.createObjectURL(
                    file
                );

            preview.classList.add(
                'show'
            );

            previewTitle.textContent =
                'Preview PDF Asli';

            previewBadge.textContent =
                'PDF';

            previewPdf.classList.remove(
                'sk-hidden'
            );

            previewPdf.src =
                previewObjectUrl;

            previewCaption.textContent =
                'Preview menggunakan file PDF asli. Server sedang menyiapkan hasil compression.';
        }

        function showPdfCompressedPreview(
            previewUrl
        ) {

            clearPreviewObjectUrl();

            preview.classList.add(
                'show'
            );

            previewTitle.textContent =
                'Preview PDF Hasil Compression';

            previewBadge.textContent =
                'PDF COMPRESSED';

            previewPdf.classList.remove(
                'sk-hidden'
            );

            previewPdf.src =
                previewUrl;

            previewCaption.textContent =
                'Preview ini menggunakan file PDF hasil compression Ghostscript dari server.';
        }

        function resetFileVisual() {

            uploadBox.classList.remove(
                'has-file'
            );

            uploadTitle.textContent =
                'Klik untuk memilih file pengganti';

            uploadSubtitle.textContent =
                'PDF, JPG, JPEG, PNG';

            fileInfo.classList.remove(
                'show'
            );

            fileInfoTitle.textContent =
                'File siap digunakan';

            fileInfoName.textContent =
                '';

            fileInfoSize.textContent =
                '';

            resetCompression();

            resetPreview();
        }

        function showFileVisual(
            file,
            title = 'File siap digunakan'
        ) {

            uploadBox.classList.add(
                'has-file'
            );

            uploadTitle.textContent =
                title;

            fileInfo.classList.add(
                'show'
            );

            fileInfoTitle.textContent =
                title;

            fileInfoName.textContent =
                file.name;

            fileInfoSize.textContent =
                formatFileSize(
                    file.size
                );
        }

        function clearFile() {

            processingToken++;

            fileInput.value =
                '';

            resetFileVisual();
        }

        clearFileButton?.addEventListener(
            'click',
            function () {

                clearFile();

            }
        );

        function loadImage(
            file
        ) {

            return new Promise(
                function (
                    resolve,
                    reject
                ) {

                    const objectUrl =
                        URL.createObjectURL(
                            file
                        );

                    const image =
                        new Image();

                    image.onload =
                        function () {

                            URL.revokeObjectURL(
                                objectUrl
                            );

                            resolve(
                                image
                            );
                        };

                    image.onerror =
                        function () {

                            URL.revokeObjectURL(
                                objectUrl
                            );

                            reject(
                                new Error(
                                    'Gambar tidak dapat dibaca oleh browser.'
                                )
                            );
                        };

                    image.src =
                        objectUrl;
                }
            );
        }

        function calculateDimensions(
            width,
            height,
            maxDimension
        ) {

            if (
                width <= maxDimension &&
                height <= maxDimension
            ) {

                return {
                    width,
                    height
                };
            }

            const scale =
                Math.min(
                    maxDimension / width,
                    maxDimension / height
                );

            return {
                width:
                    Math.max(
                        1,
                        Math.round(
                            width *
                            scale
                        )
                    ),

                height:
                    Math.max(
                        1,
                        Math.round(
                            height *
                            scale
                        )
                    )
            };
        }

        function buildDimensionList(
            width,
            height
        ) {

            const dimensions = [
                2500,
                2200,
                2000,
                1800,
                1600,
                1400,
                1200,
                1000
            ];

            const result = [];

            dimensions.forEach(
                function (
                    maxDimension
                ) {

                    const item =
                        calculateDimensions(
                            width,
                            height,
                            maxDimension
                        );

                    if (
                        item.width <
                            IMAGE_MIN_DIMENSION &&
                        item.height <
                            IMAGE_MIN_DIMENSION
                    ) {
                        return;
                    }

                    const exists =
                        result.some(
                            function (
                                existing
                            ) {

                                return (
                                    existing.width ===
                                    item.width &&
                                    existing.height ===
                                    item.height
                                );
                            }
                        );

                    if (
                        !exists
                    ) {

                        result.push(
                            item
                        );
                    }
                }
            );

            return result;
        }

        function canvasToFile(
            image,
            width,
            height,
            quality,
            originalName
        ) {

            return new Promise(
                function (
                    resolve,
                    reject
                ) {

                    const canvas =
                        document.createElement(
                            'canvas'
                        );

                    canvas.width =
                        width;

                    canvas.height =
                        height;

                    const context =
                        canvas.getContext(
                            '2d',
                            {
                                alpha:
                                    false
                            }
                        );

                    if (
                        !context
                    ) {

                        reject(
                            new Error(
                                'Browser tidak mendukung Canvas.'
                            )
                        );

                        return;
                    }

                    context.fillStyle =
                        '#ffffff';

                    context.fillRect(
                        0,
                        0,
                        width,
                        height
                    );

                    context.imageSmoothingEnabled =
                        true;

                    context.imageSmoothingQuality =
                        'high';

                    context.drawImage(
                        image,
                        0,
                        0,
                        width,
                        height
                    );

                    canvas.toBlob(
                        function (
                            blob
                        ) {

                            if (
                                !blob
                            ) {

                                reject(
                                    new Error(
                                        'Browser gagal membuat JPG.'
                                    )
                                );

                                return;
                            }

                            const baseName =
                                String(
                                    originalName ||
                                    'surat_keluar'
                                )
                                    .replace(
                                        /\.[^/.]+$/,
                                        ''
                                    )
                                    .replace(
                                        /[^a-zA-Z0-9_-]/g,
                                        '_'
                                    );

                            const fileName =
                                (
                                    baseName ||
                                    'surat_keluar'
                                ) +
                                '_compressed.jpg';

                            resolve(
                                new File(
                                    [blob],
                                    fileName,
                                    {
                                        type:
                                            'image/jpeg',

                                        lastModified:
                                            Date.now()
                                    }
                                )
                            );

                        },
                        'image/jpeg',
                        quality
                    );

                }
            );
        }

        async function compressImageFile(
            file
        ) {

            const image =
                await loadImage(
                    file
                );

            const width =
                image.naturalWidth ||
                image.width;

            const height =
                image.naturalHeight ||
                image.height;

            if (
                width <= 0 ||
                height <= 0
            ) {

                throw new Error(
                    'Dimensi gambar tidak valid.'
                );
            }

            const dimensions =
                buildDimensionList(
                    width,
                    height
                );

            let bestFile =
                null;

            for (
                const dimension
                of dimensions
            ) {

                for (
                    const quality
                    of JPEG_QUALITIES
                ) {

                    const result =
                        await canvasToFile(
                            image,
                            dimension.width,
                            dimension.height,
                            quality,
                            file.name
                        );

                    if (
                        !bestFile ||
                        result.size <
                        bestFile.size
                    ) {

                        bestFile =
                            result;
                    }

                    if (
                        result.size <=
                        IMAGE_TARGET_SIZE
                    ) {

                        return result;
                    }
                }
            }

            if (
                bestFile &&
                bestFile.size <=
                IMAGE_MAX_RESULT_SIZE
            ) {

                return bestFile;
            }

            throw new Error(
                'Hasil kompresi gambar masih terlalu besar.'
            );
        }

        async function processPdfCompression(
            file,
            token
        ) {

            showCompressionProgress(
                'PDF sedang dikirim ke server...'
            );

            showCompression(
                {
                    title:
                        '⏳ Memproses PDF',

                    originalFormat:
                        'PDF',

                    finalFormat:
                        'PDF',

                    originalSize:
                        formatFileSize(
                            file.size
                        ),

                    finalSize:
                        'Memproses...',

                    message:
                        'Ghostscript sedang melakukan compression PDF di server.'
                },
                'processing'
            );

            const formData =
                new FormData();

            formData.append(
                'lampiran_file',
                file
            );

            formData.append(
                '_token',
                '{{ csrf_token() }}'
            );

            try {

                const response =
                    await fetch(
                        '{{ route('surat-keluar.preview-compression') }}',
                        {
                            method:
                                'POST',

                            body:
                                formData,

                            headers: {
                                'X-Requested-With':
                                    'XMLHttpRequest',

                                'Accept':
                                    'application/json'
                            }
                        }
                    );

                let result =
                    null;

                try {

                    result =
                        await response.json();

                } catch (
                    jsonError
                ) {

                    throw new Error(
                        'Server tidak mengembalikan response JSON yang valid.'
                    );
                }

                if (
                    !response.ok ||
                    !result ||
                    !result.success
                ) {

                    throw new Error(
                        result?.message ||
                        'Compression PDF gagal.'
                    );
                }

                if (
                    token !==
                    processingToken
                ) {

                    return;
                }

                if (
                    result.compressed &&
                    result.preview_url
                ) {

                    fileInfoTitle.textContent =
                        'Compression PDF selesai';

                    uploadSubtitle.textContent =
                        'PDF • hasil compression server';

                    fileInfoSize.textContent =
                        result.compressed_size_text ||
                        formatFileSize(
                            result.compressed_size
                        );

                    showCompression(
                        {
                            title:
                                '✓ Compression PDF berhasil',

                            originalFormat:
                                'PDF',

                            finalFormat:
                                'PDF',

                            originalSize:
                                result.original_size_text ||
                                formatFileSize(
                                    result.original_size
                                ),

                            finalSize:
                                result.compressed_size_text ||
                                formatFileSize(
                                    result.compressed_size
                                ),

                            message:
                                'PDF berhasil dikompresi oleh Ghostscript. ' +
                                'Penghematan <strong>' +
                                Number(
                                    result.saving_percent ||
                                    0
                                ).toFixed(2) +
                                '%</strong>. ' +
                                'Profile: <strong>' +
                                (
                                    result.profile ||
                                    '-'
                                ) +
                                '</strong>.'
                        },
                        'success'
                    );

                    hideCompressionProgress();

                    showPdfCompressedPreview(
                        result.preview_url
                    );

                    return;
                }

                fileInfoTitle.textContent =
                    'PDF sudah optimal';

                uploadSubtitle.textContent =
                    'PDF • file asli dipertahankan';

                fileInfoSize.textContent =
                    formatFileSize(
                        file.size
                    );

                showCompression(
                    {
                        title:
                            'PDF sudah optimal',

                        originalFormat:
                            'PDF',

                        finalFormat:
                            'PDF',

                        originalSize:
                            result.original_size_text ||
                            formatFileSize(
                                result.original_size
                            ),

                        finalSize:
                            result.compressed_size_text ||
                            formatFileSize(
                                result.compressed_size
                            ),

                        message:
                            'Hasil compression tidak lebih kecil daripada file asli. File asli akan dipertahankan.'
                    },
                    'warning'
                );

                hideCompressionProgress();

                showPdfPreviewOriginal(
                    file
                );

            } catch (
                error
            ) {

                console.error(
                    'PDF compression error:',
                    error
                );

                if (
                    token !==
                    processingToken
                ) {

                    return;
                }

                showCompression(
                    {
                        title:
                            '❌ Compression PDF gagal',

                        originalFormat:
                            'PDF',

                        finalFormat:
                            'PDF',

                        originalSize:
                            formatFileSize(
                                file.size
                            ),

                        finalSize:
                            '-',

                        message:
                            String(
                                error?.message ||
                                'Server gagal memproses PDF.'
                            )
                    },
                    'error'
                );

                compressionProgress.classList.remove(
                    'show'
                );

                compressionStatus.textContent =
                    'Compression gagal.';

                showPdfPreviewOriginal(
                    file
                );

                showAlert(
                    'error',
                    'Compression PDF gagal',
                    error?.message ||
                    'Server gagal memproses PDF.'
                );
            }
        }

        fileInput.addEventListener(
            'change',
            async function () {

                const file =
                    fileInput.files?.[0];

                if (
                    !file
                ) {

                    resetFileVisual();

                    return;
                }

                const token =
                    ++processingToken;

                resetPreview();
                resetCompression();

                const extension =
                    getExtension(
                        file
                    );

                if (
                    !isAllowedExtension(
                        extension
                    )
                ) {

                    clearFile();

                    showAlert(
                        'warning',
                        'Format tidak didukung',
                        'Gunakan PDF, JPG, JPEG, atau PNG.'
                    );

                    return;
                }

                if (
                    file.size <= 0
                ) {

                    clearFile();

                    showAlert(
                        'warning',
                        'File tidak valid',
                        'File yang dipilih kosong.'
                    );

                    return;
                }

                if (
                    file.size >
                    MAX_FILE_SIZE
                ) {

                    clearFile();

                    showAlert(
                        'warning',
                        'File terlalu besar',
                        'Ukuran file maksimal 20 MB.'
                    );

                    return;
                }

                showFileVisual(
                    file
                );

                if (
                    extension === 'pdf'
                ) {

                    uploadSubtitle.textContent =
                        'PDF • memproses compression server';

                    showPdfPreviewOriginal(
                        file
                    );

                    await processPdfCompression(
                        file,
                        token
                    );

                    return;
                }

                if (
                    !isImageExtension(
                        extension
                    )
                ) {

                    return;
                }

                uploadSubtitle.textContent =
                    'Gambar • akan dikonversi menjadi JPG';

                showImagePreview(
                    file
                );

                showCompression(
                    {
                        title:
                            '⏳ Memproses gambar',

                        originalFormat:
                            extension.toUpperCase(),

                        finalFormat:
                            'Sedang diproses',

                        originalSize:
                            formatFileSize(
                                file.size
                            ),

                        finalSize:
                            'Menghitung...',

                        message:
                            'Browser sedang melakukan resize dan compression gambar.'
                    },
                    'processing'
                );

                try {

                    const compressed =
                        await compressImageFile(
                            file
                        );

                    if (
                        token !==
                        processingToken
                    ) {

                        return;
                    }

                    if (
                        compressed.size >
                        MAX_FILE_SIZE
                    ) {

                        throw new Error(
                            'Ukuran hasil compression masih lebih dari 20 MB.'
                        );
                    }

                    const transfer =
                        new DataTransfer();

                    transfer.items.add(
                        compressed
                    );

                    fileInput.files =
                        transfer.files;

                    const reduction =
                        getReductionPercent(
                            file.size,
                            compressed.size
                        );

                    fileInfoTitle.textContent =
                        'Hasil kompresi siap dikirim';

                    fileInfoName.textContent =
                        compressed.name;

                    fileInfoSize.textContent =
                        formatFileSize(
                            compressed.size
                        );

                    uploadSubtitle.textContent =
                        'JPG • hasil compression browser';

                    showImagePreview(
                        compressed
                    );

                    showCompression(
                        {
                            title:
                                '✓ Compression gambar berhasil',

                            originalFormat:
                                extension.toUpperCase(),

                            finalFormat:
                                'JPG',

                            originalSize:
                                formatFileSize(
                                    file.size
                                ),

                            finalSize:
                                formatFileSize(
                                    compressed.size
                                ),

                            message:
                                'Penghematan sekitar <strong>' +
                                reduction +
                                '%</strong>. File final yang dikirim adalah JPG.'
                        },
                        'success'
                    );

                } catch (
                    error
                ) {

                    console.error(
                        'Image compression error:',
                        error
                    );

                    if (
                        token !==
                        processingToken
                    ) {

                        return;
                    }

                    clearFile();

                    showAlert(
                        'error',
                        'Gagal memproses gambar',
                        error?.message ||
                        'Gambar gagal dikompresi.'
                    );
                }

            }
        );

        form.addEventListener(
            'submit',
            function (
                event
            ) {

                if (
                    submitting
                ) {

                    event.preventDefault();

                    return;
                }

                const suratDate =
                    tanggalSurat?.value ||
                    '';

                const keluarDate =
                    tanggalKeluar?.value ||
                    '';

                if (
                    suratDate &&
                    keluarDate &&
                    keluarDate <
                    suratDate
                ) {

                    event.preventDefault();

                    showAlert(
                        'warning',
                        'Tanggal tidak valid',
                        'Tanggal keluar tidak boleh lebih awal dari tanggal surat.'
                    );

                    return;
                }

                const file =
                    fileInput.files?.[0] ||
                    null;

                if (
                    file
                ) {

                    const extension =
                        getExtension(
                            file
                        );

                    if (
                        !isAllowedExtension(
                            extension
                        )
                    ) {

                        event.preventDefault();

                        showAlert(
                            'warning',
                            'Format file tidak valid',
                            'Gunakan PDF, JPG, JPEG, atau PNG.'
                        );

                        return;
                    }

                    if (
                        file.size <= 0
                    ) {

                        event.preventDefault();

                        showAlert(
                            'warning',
                            'File tidak valid',
                            'File yang dipilih kosong.'
                        );

                        return;
                    }

                    if (
                        file.size >
                        MAX_FILE_SIZE
                    ) {

                        event.preventDefault();

                        showAlert(
                            'warning',
                            'File terlalu besar',
                            'Ukuran lampiran maksimal 20 MB.'
                        );

                        return;
                    }

                    if (
                        extension === 'pdf'
                    ) {

                        const isProcessing =
                            compressionPanel.classList.contains(
                                'processing'
                            ) &&
                            compressionProgress.classList.contains(
                                'show'
                            );

                        if (
                            isProcessing
                        ) {

                            event.preventDefault();

                            showAlert(
                                'info',
                                'PDF masih diproses',
                                'Tunggu sampai compression PDF selesai sebelum menyimpan.'
                            );

                            return;
                        }
                    }
                }

                submitting =
                    true;

                submitButton.disabled =
                    true;

                submitIcon.classList.add(
                    'sk-hidden'
                );

                submitLoading.classList.remove(
                    'sk-hidden'
                );

                if (
                    file &&
                    getExtension(file) ===
                    'pdf'
                ) {

                    submitText.textContent =
                        'Menyimpan PDF...';

                } else if (
                    file
                ) {

                    submitText.textContent =
                        'Menyimpan lampiran...';

                } else {

                    submitText.textContent =
                        'Menyimpan...';
                }

            }
        );

        window.addEventListener(
            'beforeunload',
            function () {

                clearPreviewObjectUrl();

            }
        );

    }
);
</script>

@endpush

@endsection