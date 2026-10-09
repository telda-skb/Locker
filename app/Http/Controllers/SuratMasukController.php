<?php

namespace App\Http\Controllers;

use App\Http\Requests\SuratMasukRequest;
use App\Models\ActivityLog;
use App\Models\KategoriSurat;
use App\Models\SuratMasuk;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class SuratMasukController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | STATUS
    |--------------------------------------------------------------------------
    */

    private const STATUS_OPTIONS = [
        'baru',
        'diproses',
        'didisposisikan',
        'selesai',
        'diarsipkan',
    ];

    /*
    |--------------------------------------------------------------------------
    | FILE
    |--------------------------------------------------------------------------
    */

    private const ALLOWED_FILE_EXTENSIONS = [
        'pdf',
        'jpg',
        'jpeg',
        'png',
    ];

    private const ALLOWED_IMAGE_MIMES = [
        'image/jpeg',
        'image/png',
    ];

    private const MAX_FILE_SIZE_KB =
        20480;

    private const MAX_FILE_SIZE =
        20 * 1024 * 1024;

    private const COMPRESSION_THRESHOLD =
        2 * 1024 * 1024;

    /*
    |--------------------------------------------------------------------------
    | PDF COMPRESSION
    |--------------------------------------------------------------------------
    */

    private const PDF_COMPRESSION_TIMEOUT =
        300;

    private const PDF_PREVIEW_TTL =
        1800;

    private const PDF_COMPRESSION_PROFILES = [
        [
            'name' =>
                'ebook-150',

            'preset' =>
                '/ebook',

            'color_dpi' =>
                150,

            'gray_dpi' =>
                150,

            'mono_dpi' =>
                300,

            'jpeg_quality' =>
                70,
        ],

        [
            'name' =>
                'ebook-120',

            'preset' =>
                '/ebook',

            'color_dpi' =>
                120,

            'gray_dpi' =>
                120,

            'mono_dpi' =>
                240,

            'jpeg_quality' =>
                60,
        ],

        [
            'name' =>
                'screen-96',

            'preset' =>
                '/screen',

            'color_dpi' =>
                96,

            'gray_dpi' =>
                96,

            'mono_dpi' =>
                200,

            'jpeg_quality' =>
                50,
        ],
    ];

    /*
    |--------------------------------------------------------------------------
    | IMAGE COMPRESSION
    |--------------------------------------------------------------------------
    */

    private const MAX_COMPRESSED_IMAGE_SIZE =
        20 * 1024 * 1024;

    private const MAX_IMAGE_WIDTH =
        2500;

    private const MAX_IMAGE_HEIGHT =
        2500;

    private const JPEG_QUALITY =
        82;

    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index(
        Request $request
    ) {
        $this->ensureUserAuthenticated();

        /*
        |--------------------------------------------------------------------------
        | QUERY AKSES DASAR
        |--------------------------------------------------------------------------
        |
        | Query ini TIDAK menggunakan filter search/kategori/status/tanggal.
        |
        | Tujuannya hanya menentukan surat mana yang boleh dilihat oleh user.
        |
        */

        $accessQuery =
            $this->buildSuratMasukAccessQuery();


        /*
        |--------------------------------------------------------------------------
        | SCORECARD
        |--------------------------------------------------------------------------
        |
        | Scorecard dihitung dari seluruh surat yang boleh dilihat user.
        |
        | Admin:
        |   seluruh surat
        |
        | Pimpinan:
        |   seluruh surat
        |
        | Staff:
        |   hanya surat yang didisposisikan kepada dirinya
        |
        | Scorecard tidak dipengaruhi:
        |   - search
        |   - kategori
        |   - status
        |   - rentang tanggal
        |   - pagination
        |
        */

        $statusResult =
            (clone $accessQuery)
                ->reorder()
                ->selectRaw(
                    "
                    COUNT(*) AS total,

                    SUM(
                        CASE
                            WHEN LOWER(
                                TRIM(
                                    COALESCE(
                                        status,
                                        ''
                                    )
                                )
                            ) = 'baru'
                            THEN 1
                            ELSE 0
                        END
                    ) AS baru,

                    SUM(
                        CASE
                            WHEN LOWER(
                                TRIM(
                                    COALESCE(
                                        status,
                                        ''
                                    )
                                )
                            ) = 'didisposisikan'
                            THEN 1
                            ELSE 0
                        END
                    ) AS didisposisikan,

                    SUM(
                        CASE
                            WHEN LOWER(
                                TRIM(
                                    COALESCE(
                                        status,
                                        ''
                                    )
                                )
                            ) = 'selesai'
                            THEN 1
                            ELSE 0
                        END
                    ) AS selesai,

                    SUM(
                        CASE
                            WHEN LOWER(
                                TRIM(
                                    COALESCE(
                                        status,
                                        ''
                                    )
                                )
                            ) = 'diarsipkan'
                            THEN 1
                            ELSE 0
                        END
                    ) AS diarsipkan
                    "
                )
                ->first();


        /*
        |--------------------------------------------------------------------------
        | SCORECARD NORMALIZATION
        |--------------------------------------------------------------------------
        */

        $statusCounts = [
            'total' =>
                (int) (
                    $statusResult->total
                    ?? 0
                ),

            'baru' =>
                (int) (
                    $statusResult->baru
                    ?? 0
                ),

            'didisposisikan' =>
                (int) (
                    $statusResult->didisposisikan
                    ?? 0
                ),

            'selesai' =>
                (int) (
                    $statusResult->selesai
                    ?? 0
                ),

            'diarsipkan' =>
                (int) (
                    $statusResult->diarsipkan
                    ?? 0
                ),
        ];


        /*
        |--------------------------------------------------------------------------
        | RINGKASAN KATEGORI
        |--------------------------------------------------------------------------
        |
        | Dipakai pada panel ringkasan di halaman Surat Masuk.
        | Perhitungan menggunakan query akses dasar sehingga mengikuti
        | hak akses user dan tidak dipengaruhi filter maupun pagination.
        |
        */

        $categoryCounts =
            (clone $accessQuery)
                ->reorder()
                ->select('kategori_surat_id')
                ->selectRaw('COUNT(*) AS total')
                ->with('kategori:id,nama_kategori')
                ->groupBy('kategori_surat_id')
                ->orderByDesc('total')
                ->get();


        /*
        |--------------------------------------------------------------------------
        | QUERY TABEL
        |--------------------------------------------------------------------------
        |
        | Berbeda dari scorecard.
        |
        | Query ini menerima seluruh filter yang dikirim dari Blade.
        |
        */

        $query =
            $this->buildSuratMasukQuery(
                $request
            );


        /*
        |--------------------------------------------------------------------------
        | STATUS FILE UNTUK EXPORT
        |--------------------------------------------------------------------------
        |
        | Tombol Export Excel dan PDF hanya ditampilkan apabila pada data
        | yang sedang ditampilkan/filter terdapat minimal satu surat
        | yang mempunyai file lampiran.
        |
        | Tidak memeriksa isi file ke storage agar tidak melakukan request
        | storage berulang pada setiap pembukaan halaman.
        |
        */

        $hasUploadedFile =
            (clone $query)
                ->whereNotNull('lampiran_file')
                ->where(
                    'lampiran_file',
                    '<>',
                    ''
                )
                ->exists();


        /*
        |--------------------------------------------------------------------------
        | URUTAN DATA
        |--------------------------------------------------------------------------
        | desc = terbaru ke terlama
        | asc  = terlama ke terbaru
        |
        | Nilai selain asc/desc dipaksa kembali ke desc agar URL tidak dapat
        | mengubah kolom ORDER BY secara langsung.
        */

        $sortOrder =
            strtolower(
                trim(
                    (string) $request->input(
                        'sort',
                        'desc'
                    )
                )
            );

        if (!in_array($sortOrder, ['asc', 'desc'], true)) {
            $sortOrder = 'desc';
        }


        /*
        |--------------------------------------------------------------------------
        | PAGINATION
        |--------------------------------------------------------------------------
        */

        $suratMasuks =
            $query
                ->orderBy(
                    'tanggal_terima',
                    $sortOrder
                )
                ->orderBy(
                    'id',
                    $sortOrder
                )
                ->paginate(5)
                ->withQueryString();


        /*
        |--------------------------------------------------------------------------
        | KATEGORI
        |--------------------------------------------------------------------------
        */

        $kategoris =
            KategoriSurat::query()
                ->orderBy(
                    'nama_kategori'
                )
                ->get();


        /*
        |--------------------------------------------------------------------------
        | VIEW
        |--------------------------------------------------------------------------
        */

        return view(
            'surat_masuk.index',
            compact(
                'suratMasuks',
                'kategoris',
                'statusCounts',
                'categoryCounts',
                'hasUploadedFile',
                'sortOrder'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | BUILD QUERY AKSES DASAR
    |--------------------------------------------------------------------------
    |
    | Hanya menangani hak akses data.
    |
    | Jangan memasukkan filter request ke sini karena query ini digunakan
    | juga oleh scorecard.
    |
    */

    private function buildSuratMasukAccessQuery(): Builder
    {
        $this->ensureUserAuthenticated();

        $query =
            SuratMasuk::query()
                ->with([
                    'kategori',
                    'penerima',
                ]);


        /*
        |--------------------------------------------------------------------------
        | STAFF
        |--------------------------------------------------------------------------
        |
        | Staff hanya melihat surat yang mempunyai disposisi kepadanya.
        |
        */

        if (
            $this->userIsStaff()
        ) {

            $query->whereHas(
                'disposisi',
                function (
                    Builder $disposisi
                ): void {

                    $disposisi->where(
                        'kepada_user_id',
                        (int) Auth::id()
                    );
                }
            );
        }


        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | BUILD QUERY
    |--------------------------------------------------------------------------
    */

    public function buildSuratMasukQuery(
        Request $request
    ): Builder {

        $this->ensureUserAuthenticated();


        /*
        |--------------------------------------------------------------------------
        | QUERY DASAR + HAK AKSES
        |--------------------------------------------------------------------------
        */

        $query =
            $this->buildSuratMasukAccessQuery();


        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */

        $search =
            trim(
                (string) $request->input(
                    'search',
                    ''
                )
            );


        if (
            $search !== ''
        ) {

            $keyword =
                '%' .
                $search .
                '%';


            $query->where(
                function (
                    Builder $q
                ) use (
                    $keyword
                ): void {

                    $q
                        ->where(
                            'nomor_agenda',
                            'like',
                            $keyword
                        )

                        ->orWhere(
                            'nomor_surat',
                            'like',
                            $keyword
                        )

                        ->orWhere(
                            'pengirim',
                            'like',
                            $keyword
                        )

                        ->orWhere(
                            'perihal',
                            'like',
                            $keyword
                        )

                        ->orWhere(
                            'ringkasan',
                            'like',
                            $keyword
                        )

                        ->orWhereHas(
                            'kategori',
                            function (
                                Builder $kategori
                            ) use (
                                $keyword
                            ): void {

                                $kategori
                                    ->where(
                                        'nama_kategori',
                                        'like',
                                        $keyword
                                    )

                                    ->orWhere(
                                        'kode',
                                        'like',
                                        $keyword
                                    )

                                    ->orWhere(
                                        'sifat',
                                        'like',
                                        $keyword
                                    );
                            }
                        );
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | KATEGORI
        |--------------------------------------------------------------------------
        */

        $rawKategoriIds =
            $request->input(
                'kategori_surat_id',
                $request->input(
                    'kategori_id',
                    []
                )
            );


        if (
            is_scalar(
                $rawKategoriIds
            ) &&
            trim(
                (string) $rawKategoriIds
            ) !== ''
        ) {

            $rawKategoriIds = [
                $rawKategoriIds,
            ];
        }


        if (
            !is_array(
                $rawKategoriIds
            )
        ) {

            $rawKategoriIds = [];
        }


        $kategoriIds =
            collect(
                $rawKategoriIds
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
                    (int) $id
            )
            ->unique()
            ->values()
            ->all();


        if (
            !empty(
                $kategoriIds
            )
        ) {

            $query->whereIn(
                'kategori_surat_id',
                $kategoriIds
            );
        }


        /*
        |--------------------------------------------------------------------------
        | STATUS
        |--------------------------------------------------------------------------
        */

        $rawStatuses =
            $request->input(
                'status',
                []
            );


        if (
            is_scalar(
                $rawStatuses
            )
        ) {

            $rawStatuses = [
                $rawStatuses,
            ];
        }


        if (
            !is_array(
                $rawStatuses
            )
        ) {

            $rawStatuses = [];
        }


        $statuses =
            collect(
                $rawStatuses
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
                    in_array(
                        $status,
                        self::STATUS_OPTIONS,
                        true
                    )
            )
            ->unique()
            ->values()
            ->all();


        if (
            !empty(
                $statuses
            )
        ) {

            $query->whereIn(
                'status',
                $statuses
            );
        }


        /*
        |--------------------------------------------------------------------------
        | RENTANG TANGGAL
        |--------------------------------------------------------------------------
        */

        $dariTanggal =
            trim(
                (string) $request->input(
                    'dari_tanggal',
                    ''
                )
            );


        $sampaiTanggal =
            trim(
                (string) $request->input(
                    'sampai_tanggal',
                    ''
                )
            );


        $validDariTanggal =
            $this->isValidDate(
                $dariTanggal
            );


        $validSampaiTanggal =
            $this->isValidDate(
                $sampaiTanggal
            );


        /*
        |--------------------------------------------------------------------------
        | NORMALISASI RENTANG
        |--------------------------------------------------------------------------
        */

        if (
            $validDariTanggal &&
            $validSampaiTanggal &&
            $dariTanggal > $sampaiTanggal
        ) {

            [
                $dariTanggal,
                $sampaiTanggal,
            ] = [
                $sampaiTanggal,
                $dariTanggal,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | FROM DATE
        |--------------------------------------------------------------------------
        */

        if (
            $validDariTanggal
        ) {

            $query->whereDate(
                'tanggal_terima',
                '>=',
                $dariTanggal
            );
        }


        /*
        |--------------------------------------------------------------------------
        | TO DATE
        |--------------------------------------------------------------------------
        */

        if (
            $validSampaiTanggal
        ) {

            $query->whereDate(
                'tanggal_terima',
                '<=',
                $sampaiTanggal
            );
        }


        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        $this->ensureUserCanManageSurat();

        $this->cleanupPdfPreview();

        $kategoris =
            KategoriSurat::query()
                ->orderBy(
                    'nama_kategori'
                )
                ->get();

        $nomorAgenda =
            SuratMasuk::generateNomorAgenda();

        return view(
            'surat_masuk.create',
            compact(
                'kategoris',
                'nomorAgenda'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PREVIEW COMPRESSION PDF
    |--------------------------------------------------------------------------
    */

    public function previewCompression(
        Request $request
    ) {
        $this->ensureUserCanManageSurat();

        $request->validate(
            [
                'lampiran_file' => [
                    'required',
                    'file',
                    'mimes:pdf',
                    'max:' . self::MAX_FILE_SIZE_KB,
                ],
            ],
            [
                'lampiran_file.required' =>
                    'File PDF wajib dipilih.',

                'lampiran_file.file' =>
                    'File PDF tidak valid.',

                'lampiran_file.mimes' =>
                    'File harus berformat PDF.',

                'lampiran_file.max' =>
                    'Ukuran PDF maksimal 20 MB.',
            ]
        );

        $file =
            $request->file(
                'lampiran_file'
            );

        if (
            !$file ||
            !$file->isValid()
        ) {
            return response()->json(
                [
                    'success' =>
                        false,

                    'message' =>
                        $file
                            ? $this->getUploadErrorMessage(
                                $file
                            )
                            : 'File PDF tidak ditemukan.',
                ],
                422
            );
        }

        $extension =
            strtolower(
                trim(
                    (string) $file
                        ->getClientOriginalExtension()
                )
            );

        if (
            $extension !== 'pdf'
        ) {
            return response()->json(
                [
                    'success' =>
                        false,

                    'message' =>
                        'File harus berformat PDF.',
                ],
                422
            );
        }

        $mime =
            strtolower(
                (string) $file->getMimeType()
            );

        if (
            $mime !==
            'application/pdf'
        ) {
            return response()->json(
                [
                    'success' =>
                        false,

                    'message' =>
                        'File PDF tidak valid.',
                ],
                422
            );
        }

        $inputPath =
            $file->getRealPath();

        if (
            !$inputPath ||
            !is_readable(
                $inputPath
            )
        ) {
            return response()->json(
                [
                    'success' =>
                        false,

                    'message' =>
                        'File PDF temporary tidak dapat dibaca.',
                ],
                422
            );
        }

        $originalSize =
            filesize(
                $inputPath
            );

        if (
            $originalSize === false ||
            $originalSize <= 0
        ) {
            return response()->json(
                [
                    'success' =>
                        false,

                    'message' =>
                        'Ukuran file PDF tidak dapat dibaca.',
                ],
                422
            );
        }

        if (
            $originalSize >
            self::MAX_FILE_SIZE
        ) {
            return response()->json(
                [
                    'success' =>
                        false,

                    'message' =>
                        'Ukuran PDF maksimal 20 MB.',
                ],
                422
            );
        }

        // PDF di bawah 2 MB tidak perlu dikompres.
        if (
            $originalSize <
            self::COMPRESSION_THRESHOLD
        ) {
            $token =
                Str::random(64);

            session()->put(
                'surat_masuk_pdf_preview',
                [
                    'token' => $token,
                    'file_hash' => hash_file('sha256', $inputPath),
                    'original_size' => $originalSize,
                    'compressed_size' => $originalSize,
                    'profile' => 'none',
                    'use_compressed' => false,
                    'user_id' => (int) Auth::id(),
                    'created_at' => now()->timestamp,
                ]
            );

            return response()->json(
                [
                    'success' => true,
                    'compressed' => false,
                    'token' => $token,
                    'original_size' => $originalSize,
                    'compressed_size' => $originalSize,
                    'saving_percent' => 0,
                    'profile' => 'none',
                    'original_size_text' => $this->formatBytes($originalSize),
                    'compressed_size_text' => $this->formatBytes($originalSize),
                    'message' => 'PDF di bawah 2 MB, sehingga tidak perlu dikompres. File asli akan digunakan.',
                ]
            );
        }

        try {

            $fileHash =
                hash_file(
                    'sha256',
                    $inputPath
                );

            if (
                !$fileHash
            ) {
                throw new RuntimeException(
                    'Hash file PDF tidak dapat dibuat.'
                );
            }

            $this->cleanupPdfPreview();

            $result =
                $this->compressPdfToTemporaryFile(
                    $inputPath
                );

            $temporaryPath =
                $result['path'];

            $compressedSize =
                $result['size'];

            $profile =
                $result['profile'];


            if (
                $compressedSize >=
                $originalSize
            ) {

                @unlink(
                    $temporaryPath
                );

                $token =
                    Str::random(64);

                session()->put(
                    'surat_masuk_pdf_preview',
                    [
                        'token' =>
                            $token,

                        'file_hash' =>
                            $fileHash,

                        'original_size' =>
                            $originalSize,

                        'compressed_size' =>
                            $originalSize,

                        'profile' =>
                            $profile,

                        'use_compressed' =>
                            false,

                        'user_id' =>
                            (int) Auth::id(),

                        'created_at' =>
                            now()->timestamp,
                    ]
                );

                return response()->json(
                    [
                        'success' =>
                            true,

                        'compressed' =>
                            false,

                        'token' =>
                            $token,

                        'original_size' =>
                            $originalSize,

                        'compressed_size' =>
                            $originalSize,

                        'saving_percent' =>
                            0,

                        'profile' =>
                            $profile,

                        'original_size_text' =>
                            $this->formatBytes(
                                $originalSize
                            ),

                        'compressed_size_text' =>
                            $this->formatBytes(
                                $originalSize
                            ),

                        'message' =>
                            'PDF sudah cukup optimal. File asli akan digunakan.',
                    ]
                );
            }


            $token =
                Str::random(64);


            $directory =
                storage_path(
                    'app/pdf-compression'
                );


            if (
                !is_dir(
                    $directory
                ) &&
                !mkdir(
                    $directory,
                    0775,
                    true
                ) &&
                !is_dir(
                    $directory
                )
            ) {

                @unlink(
                    $temporaryPath
                );

                throw new RuntimeException(
                    'Folder preview PDF tidak dapat dibuat.'
                );
            }


            $finalPreviewPath =
                $directory .
                DIRECTORY_SEPARATOR .
                'preview_' .
                $token .
                '.pdf';


            if (
                !rename(
                    $temporaryPath,
                    $finalPreviewPath
                )
            ) {

                @unlink(
                    $temporaryPath
                );

                throw new RuntimeException(
                    'Gagal menyimpan hasil compression temporary.'
                );
            }


            session()->put(
                'surat_masuk_pdf_preview',
                [
                    'token' =>
                        $token,

                    'path' =>
                        $finalPreviewPath,

                    'file_hash' =>
                        $fileHash,

                    'original_size' =>
                        $originalSize,

                    'compressed_size' =>
                        $compressedSize,

                    'profile' =>
                        $profile,

                    'use_compressed' =>
                        true,

                    'user_id' =>
                        (int) Auth::id(),

                    'created_at' =>
                        now()->timestamp,
                ]
            );


            $savingPercent =
                round(
                    (
                        1 -
                        (
                            $compressedSize /
                            $originalSize
                        )
                    ) *
                    100,
                    2
                );


            Log::info(
                'Preview PDF berhasil dikompresi.',
                [
                    'original_size' =>
                        $originalSize,

                    'compressed_size' =>
                        $compressedSize,

                    'saving_percent' =>
                        $savingPercent,

                    'profile' =>
                        $profile,

                    'user_id' =>
                        Auth::id(),

                    'file_name' =>
                        $file->getClientOriginalName(),
                ]
            );


            return response()->json(
                [
                    'success' =>
                        true,

                    'compressed' =>
                        true,

                    'token' =>
                        $token,

                    'original_size' =>
                        $originalSize,

                    'compressed_size' =>
                        $compressedSize,

                    'saving_percent' =>
                        $savingPercent,

                    'profile' =>
                        $profile,

                    'original_size_text' =>
                        $this->formatBytes(
                            $originalSize
                        ),

                    'compressed_size_text' =>
                        $this->formatBytes(
                            $compressedSize
                        ),

                    'message' =>
                        'PDF berhasil dikompresi dan siap disimpan.',
                ]
            );

        } catch (
            Throwable $e
        ) {

            Log::error(
                'Gagal preview compression PDF Surat Masuk.',
                [
                    'message' =>
                        $e->getMessage(),

                    'user_id' =>
                        Auth::id(),

                    'file_name' =>
                        $file->getClientOriginalName(),

                    'trace' =>
                        $e->getTraceAsString(),
                ]
            );


            return response()->json(
                [
                    'success' =>
                        false,

                    'message' =>
                        $e->getMessage(),
                ],
                500
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */

    public function store(
        SuratMasukRequest $request
    ) {
        $this->ensureUserCanManageSurat();

        $data =
            $request->validated();

        $diskName =
            $this->getStorageDisk();

        $uploadedPath =
            null;

        DB::beginTransaction();

        try {

            $nomorInput =
                trim(
                    (string) $request->input(
                        'nomor_agenda',
                        ''
                    )
                );


            if (
                $nomorInput === '' ||
                SuratMasuk::where(
                    'nomor_agenda',
                    $nomorInput
                )->exists()
            ) {

                $data['nomor_agenda'] =
                    SuratMasuk::generateNomorAgenda();

            } else {

                $data['nomor_agenda'] =
                    $nomorInput;
            }


            $data['diterima_oleh'] =
                (int) Auth::id();


            $status =
                strtolower(
                    trim(
                        (string) (
                            $data['status'] ??
                            'baru'
                        )
                    )
                );


            $data['status'] =
                in_array(
                    $status,
                    self::STATUS_OPTIONS,
                    true
                )
                    ? $status
                    : 'baru';


            if (
                $request->hasFile(
                    'lampiran_file'
                )
            ) {

                $file =
                    $request->file(
                        'lampiran_file'
                    );


                $extension =
                    strtolower(
                        trim(
                            (string) $file
                                ->getClientOriginalExtension()
                        )
                    );


                if (
                    $extension ===
                    'jpeg'
                ) {
                    $extension =
                        'jpg';
                }


                if (
                    $extension ===
                    'pdf'
                ) {

                    $uploadedPath =
                        $this->storePdfUsingPreview(
                            $file,
                            $diskName
                        );

                } else {

                    $uploadedPath =
                        $this->storeUploadedFile(
                            $file,
                            $diskName
                        );
                }


                $data['lampiran_file'] =
                    $uploadedPath;

            } elseif (
                $request->filled(
                    'captured_image'
                )
            ) {

                $uploadedPath =
                    $this->uploadBase64Image(
                        (string) $request->input(
                            'captured_image'
                        ),
                        $diskName
                    );


                $data['lampiran_file'] =
                    $uploadedPath;
            }


            unset(
                $data['captured_image']
            );


            $surat =
                SuratMasuk::create(
                    $data
                );


            $this->logActivity(
                'create',
                'surat_masuk',
                sprintf(
                    'Menambah surat masuk %s - %s',
                    $surat->nomor_agenda,
                    $surat->perihal
                )
            );


            DB::commit();


            $this->cleanupPdfPreview();


            return redirect()
                ->route(
                    'surat-masuk.index'
                )
                ->with(
                    'success',
                    'Surat masuk berhasil dicatat dengan nomor agenda ' .
                    $surat->nomor_agenda
                );

        } catch (
            Throwable $e
        ) {

            DB::rollBack();


            if (
                $uploadedPath
            ) {

                $this->deleteStorageFile(
                    $uploadedPath,
                    $diskName
                );
            }


            $this->cleanupPdfPreview();


            Log::error(
                'Gagal menyimpan Surat Masuk.',
                [
                    'message' =>
                        $e->getMessage(),

                    'user_id' =>
                        Auth::id(),

                    'disk' =>
                        $diskName,

                    'trace' =>
                        $e->getTraceAsString(),
                ]
            );


            return back()
                ->withInput()
                ->with(
                    'error',
                    'Gagal menyimpan surat masuk: ' .
                    $e->getMessage()
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | STORE PDF USING PREVIEW
    |--------------------------------------------------------------------------
    */

    private function storePdfUsingPreview(
        UploadedFile $file,
        string $diskName
    ): string {

        if (
            !$file->isValid()
        ) {

            throw new RuntimeException(
                $this->getUploadErrorMessage(
                    $file
                )
            );
        }


        $inputPath =
            $file->getRealPath();


        if (
            !$inputPath ||
            !is_readable(
                $inputPath
            )
        ) {

            throw new RuntimeException(
                'File PDF temporary tidak dapat dibaca.'
            );
        }


        $originalSize =
            filesize(
                $inputPath
            );


        if (
            $originalSize === false ||
            $originalSize <= 0
        ) {

            throw new RuntimeException(
                'Ukuran PDF tidak dapat dibaca.'
            );
        }


        if (
            $originalSize >
            self::MAX_FILE_SIZE
        ) {

            throw new RuntimeException(
                'Ukuran PDF maksimal 20 MB.'
            );
        }

        // PDF di bawah 2 MB disimpan apa adanya tanpa Ghostscript.
        if (
            $originalSize <
            self::COMPRESSION_THRESHOLD
        ) {
            $contents =
                file_get_contents($inputPath);

            if ($contents === false || $contents === '') {
                throw new RuntimeException(
                    'Gagal membaca PDF asli.'
                );
            }

            session()->forget('surat_masuk_pdf_preview');

            return $this->storeBinaryFile(
                $contents,
                'pdf',
                'application/pdf',
                'surat-masuk',
                $diskName
            );
        }


        $currentHash =
            hash_file(
                'sha256',
                $inputPath
            );


        if (
            !$currentHash
        ) {

            throw new RuntimeException(
                'Hash PDF tidak dapat dibuat.'
            );
        }


        $preview =
            session(
                'surat_masuk_pdf_preview'
            );


        if (
            is_array($preview)
        ) {

            $previewUserId =
                (int) (
                    $preview['user_id'] ??
                    0
                );


            $createdAt =
                (int) (
                    $preview['created_at'] ??
                    0
                );


            $previewPath =
                (string) (
                    $preview['path'] ??
                    ''
                );


            $previewHash =
                (string) (
                    $preview['file_hash'] ??
                    ''
                );


            $sameUser =
                $previewUserId ===
                (int) Auth::id();


            $notExpired =
                $createdAt > 0 &&
                (
                    now()->timestamp -
                    $createdAt
                ) <=
                self::PDF_PREVIEW_TTL;


            $sameFile =
                $previewHash !== '' &&
                hash_equals(
                    $previewHash,
                    $currentHash
                );


            if (
                $sameUser &&
                $notExpired &&
                $sameFile &&
                !empty(
                    $preview['use_compressed']
                ) &&
                $previewPath !== '' &&
                is_file(
                    $previewPath
                ) &&
                is_readable(
                    $previewPath
                )
            ) {

                $compressedContents =
                    file_get_contents(
                        $previewPath
                    );


                if (
                    $compressedContents === false ||
                    $compressedContents === ''
                ) {

                    throw new RuntimeException(
                        'Hasil compression temporary tidak dapat dibaca.'
                    );
                }


                $compressedSize =
                    strlen(
                        $compressedContents
                    );


                if (
                    $compressedSize <= 0
                ) {

                    throw new RuntimeException(
                        'Ukuran hasil compression tidak valid.'
                    );
                }


                if (
                    $compressedSize >
                    self::MAX_FILE_SIZE
                ) {

                    throw new RuntimeException(
                        'PDF hasil compression melebihi batas 20 MB.'
                    );
                }


                $uploadedPath =
                    $this->storeBinaryFile(
                        $compressedContents,
                        'pdf',
                        'application/pdf',
                        'surat-masuk',
                        $diskName
                    );


                @unlink(
                    $previewPath
                );


                session()->forget(
                    'surat_masuk_pdf_preview'
                );


                return $uploadedPath;
            }


            if (
                $sameUser &&
                $notExpired &&
                $sameFile &&
                empty(
                    $preview['use_compressed']
                )
            ) {

                if (
                    $previewPath !== '' &&
                    is_file(
                        $previewPath
                    )
                ) {

                    @unlink(
                        $previewPath
                    );
                }


                session()->forget(
                    'surat_masuk_pdf_preview'
                );


                $originalContents =
                    file_get_contents(
                        $inputPath
                    );


                if (
                    $originalContents === false ||
                    $originalContents === ''
                ) {

                    throw new RuntimeException(
                        'Gagal membaca PDF asli.'
                    );
                }


                return $this->storeBinaryFile(
                    $originalContents,
                    'pdf',
                    'application/pdf',
                    'surat-masuk',
                    $diskName
                );
            }


            if (
                $previewPath !== '' &&
                is_file(
                    $previewPath
                )
            ) {

                @unlink(
                    $previewPath
                );
            }


            session()->forget(
                'surat_masuk_pdf_preview'
            );
        }


        return $this->storeCompressedPdf(
            $inputPath,
            $diskName
        );
    }

    /*
    |--------------------------------------------------------------------------
    | COMPRESS PDF TO TEMPORARY FILE
    |--------------------------------------------------------------------------
    */

    private function compressPdfToTemporaryFile(
        string $inputPath
    ): array {

        if (
            !is_file($inputPath) ||
            !is_readable($inputPath)
        ) {

            throw new RuntimeException(
                'File PDF tidak dapat dibaca.'
            );
        }


        $ghostscript =
            PHP_OS_FAMILY === 'Windows'
                ? 'gswin64c'
                : '/usr/bin/gs';


        if (
            PHP_OS_FAMILY !== 'Windows' &&
            !is_executable(
                $ghostscript
            )
        ) {

            throw new RuntimeException(
                'Ghostscript tidak ditemukan pada server.'
            );
        }


        $versionOutput =
            [];

        $versionCode =
            0;


        @exec(
            escapeshellarg(
                $ghostscript
            ) .
            ' --version 2>&1',
            $versionOutput,
            $versionCode
        );


        if (
            $versionCode !== 0
        ) {

            throw new RuntimeException(
                'Ghostscript tidak dapat dijalankan.'
            );
        }


        $directory =
            storage_path(
                'app/pdf-compression'
            );


        if (
            !is_dir($directory) &&
            !mkdir(
                $directory,
                0775,
                true
            ) &&
            !is_dir($directory)
        ) {

            throw new RuntimeException(
                'Folder temporary compression PDF tidak dapat dibuat.'
            );
        }


        $bestOutput =
            null;

        $bestSize =
            null;

        $bestProfile =
            null;


        foreach (
            self::PDF_COMPRESSION_PROFILES
            as $profile
        ) {

            $outputPath =
                $directory .
                DIRECTORY_SEPARATOR .
                'compressed_' .
                Str::uuid() .
                '.pdf';


            $command =
                escapeshellarg(
                    $ghostscript
                ) .
                ' -sDEVICE=pdfwrite' .
                ' -dCompatibilityLevel=1.4' .
                ' -dPDFSETTINGS=' .
                escapeshellarg(
                    $profile['preset']
                ) .
                ' -dDetectDuplicateImages=true' .
                ' -dCompressFonts=true' .
                ' -dCompressStreams=true' .
                ' -dDownsampleColorImages=true' .
                ' -dColorImageResolution=' .
                (int) $profile['color_dpi'] .
                ' -dColorImageDownsampleType=/Bicubic' .
                ' -dAutoFilterColorImages=false' .
                ' -dColorImageFilter=/DCTEncode' .
                ' -dJPEGQ=' .
                (int) $profile['jpeg_quality'] .
                ' -dDownsampleGrayImages=true' .
                ' -dGrayImageResolution=' .
                (int) $profile['gray_dpi'] .
                ' -dGrayImageDownsampleType=/Bicubic' .
                ' -dAutoFilterGrayImages=false' .
                ' -dGrayImageFilter=/DCTEncode' .
                ' -dDownsampleMonoImages=true' .
                ' -dMonoImageResolution=' .
                (int) $profile['mono_dpi'] .
                ' -dMonoImageDownsampleType=/Bicubic' .
                ' -dNOPAUSE' .
                ' -dBATCH' .
                ' -dQUIET' .
                ' -dSAFER' .
                ' -sOutputFile=' .
                escapeshellarg(
                    $outputPath
                ) .
                ' ' .
                escapeshellarg(
                    $inputPath
                );


            $commandOutput =
                [];

            $exitCode =
                0;


            @exec(
                $command . ' 2>&1',
                $commandOutput,
                $exitCode
            );


            if (
                $exitCode !== 0 ||
                !is_file(
                    $outputPath
                )
            ) {

                Log::warning(
                    'Profile Ghostscript gagal.',
                    [
                        'profile' =>
                            $profile['name'],

                        'exit_code' =>
                            $exitCode,

                        'output' =>
                            implode(
                                PHP_EOL,
                                $commandOutput
                            ),
                    ]
                );


                if (
                    is_file(
                        $outputPath
                    )
                ) {

                    @unlink(
                        $outputPath
                    );
                }


                continue;
            }


            $size =
                filesize(
                    $outputPath
                );


            if (
                $size === false ||
                $size <= 0
            ) {

                @unlink(
                    $outputPath
                );

                continue;
            }


            $header =
                @file_get_contents(
                    $outputPath,
                    false,
                    null,
                    0,
                    5
                );


            if (
                $header !== '%PDF-'
            ) {

                @unlink(
                    $outputPath
                );


                Log::warning(
                    'Ghostscript menghasilkan output bukan PDF valid.',
                    [
                        'profile' =>
                            $profile['name'],
                    ]
                );


                continue;
            }


            if (
                $bestSize === null ||
                $size < $bestSize
            ) {

                if (
                    $bestOutput &&
                    is_file(
                        $bestOutput
                    )
                ) {

                    @unlink(
                        $bestOutput
                    );
                }


                $bestOutput =
                    $outputPath;

                $bestSize =
                    $size;

                $bestProfile =
                    $profile['name'];

            } else {

                @unlink(
                    $outputPath
                );
            }
        }


        if (
            !$bestOutput ||
            $bestSize === null
        ) {

            throw new RuntimeException(
                'Ghostscript gagal menghasilkan PDF terkompresi.'
            );
        }


        if (
            $bestSize >
            self::MAX_FILE_SIZE
        ) {

            @unlink(
                $bestOutput
            );


            throw new RuntimeException(
                'PDF hasil compression masih melebihi batas 20 MB.'
            );
        }


        return [
            'path' =>
                $bestOutput,

            'size' =>
                $bestSize,

            'profile' =>
                $bestProfile,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | STORE COMPRESSED PDF
    |--------------------------------------------------------------------------
    */

    private function storeCompressedPdf(
        string $inputPath,
        string $diskName
    ): string {

        $originalSize =
            filesize(
                $inputPath
            );


        if (
            $originalSize === false ||
            $originalSize <= 0
        ) {

            throw new RuntimeException(
                'Ukuran PDF tidak dapat dibaca.'
            );
        }


        if (
            $originalSize >
            self::MAX_FILE_SIZE
        ) {

            throw new RuntimeException(
                'Ukuran PDF maksimal 20 MB.'
            );
        }


        $result =
            $this->compressPdfToTemporaryFile(
                $inputPath
            );


        $temporaryPath =
            $result['path'];


        $compressedSize =
            $result['size'];


        $profile =
            $result['profile'];


        try {

            if (
                $compressedSize >=
                $originalSize
            ) {

                $originalContents =
                    file_get_contents(
                        $inputPath
                    );


                if (
                    $originalContents === false ||
                    $originalContents === ''
                ) {

                    throw new RuntimeException(
                        'Gagal membaca PDF asli.'
                    );
                }


                Log::info(
                    'PDF tidak menjadi lebih kecil setelah compression; file asli digunakan.',
                    [
                        'original_size' =>
                            $originalSize,

                        'compressed_size' =>
                            $compressedSize,

                        'saving_percent' =>
                            0,

                        'profile' =>
                            $profile,

                        'disk' =>
                            $diskName,
                    ]
                );


                return $this->storeBinaryFile(
                    $originalContents,
                    'pdf',
                    'application/pdf',
                    'surat-masuk',
                    $diskName
                );
            }


            $compressedData =
                file_get_contents(
                    $temporaryPath
                );


            if (
                $compressedData === false ||
                $compressedData === ''
            ) {

                throw new RuntimeException(
                    'Gagal membaca PDF hasil compression.'
                );
            }


            $finalSize =
                strlen(
                    $compressedData
                );


            if (
                $finalSize >
                self::MAX_FILE_SIZE
            ) {

                throw new RuntimeException(
                    'PDF hasil compression masih melebihi batas 20 MB.'
                );
            }


            $savingPercent =
                round(
                    (
                        1 -
                        (
                            $finalSize /
                            $originalSize
                        )
                    ) *
                    100,
                    2
                );


            Log::info(
                'PDF Surat Masuk berhasil dikompresi.',
                [
                    'original_size' =>
                        $originalSize,

                    'compressed_size' =>
                        $finalSize,

                    'saving_percent' =>
                        $savingPercent,

                    'profile' =>
                        $profile,

                    'disk' =>
                        $diskName,
                ]
            );


            return $this->storeBinaryFile(
                $compressedData,
                'pdf',
                'application/pdf',
                'surat-masuk',
                $diskName
            );

        } finally {

            if (
                is_file(
                    $temporaryPath
                )
            ) {

                @unlink(
                    $temporaryPath
                );
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CAMERA BASE64
    |--------------------------------------------------------------------------
    */

    private function uploadBase64Image(
        string $base64String,
        string $diskName
    ): string {

        $base64String =
            trim(
                $base64String
            );


        if (
            $base64String === ''
        ) {

            throw new RuntimeException(
                'Data gambar kamera kosong.'
            );
        }


        if (
            !preg_match(
                '~^data:image/(png|jpeg|jpg);base64,~i',
                $base64String
            )
        ) {

            throw new RuntimeException(
                'Format hasil scan kamera tidak valid.'
            );
        }


        $commaPosition =
            strpos(
                $base64String,
                ','
            );


        if (
            $commaPosition === false
        ) {

            throw new RuntimeException(
                'Data gambar kamera tidak valid.'
            );
        }


        $encoded =
            substr(
                $base64String,
                $commaPosition + 1
            );


        $decodedData =
            base64_decode(
                $encoded,
                true
            );


        if (
            $decodedData === false ||
            $decodedData === ''
        ) {

            throw new RuntimeException(
                'Gagal memproses gambar kamera. Data Base64 tidak valid.'
            );
        }


        if (
            strlen(
                $decodedData
            ) >
            self::MAX_FILE_SIZE
        ) {

            throw new RuntimeException(
                'Ukuran hasil scan kamera maksimal 20 MB.'
            );
        }


        $imageInfo =
            @getimagesizefromstring(
                $decodedData
            );


        if (
            $imageInfo === false
        ) {

            throw new RuntimeException(
                'Data kamera bukan gambar yang valid.'
            );
        }


        $actualMime =
            strtolower(
                (string) (
                    $imageInfo['mime'] ??
                    ''
                )
            );


        if (
            $actualMime ===
            'image/jpg'
        ) {

            $actualMime =
                'image/jpeg';
        }


        if (
            !in_array(
                $actualMime,
                self::ALLOWED_IMAGE_MIMES,
                true
            )
        ) {

            throw new RuntimeException(
                'Jenis gambar hasil scan tidak didukung.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | SCAN DI BAWAH 2 MB
        |--------------------------------------------------------------------------
        |
        | Sama seperti upload file biasa, scan kamera di bawah 2 MB
        | tidak dikompres. File disimpan dalam bentuk aslinya.
        |
        */

        if (
            strlen(
                $decodedData
            ) <
            self::COMPRESSION_THRESHOLD
        ) {

            $extension =
                $actualMime ===
                'image/png'
                    ? 'png'
                    : 'jpg';

            return $this->storeBinaryFile(
                $decodedData,
                $extension,
                $actualMime,
                'scan',
                $diskName
            );
        }


        /*
        |--------------------------------------------------------------------------
        | SCAN 2-20 MB
        |--------------------------------------------------------------------------
        */

        return $this->storeCompressedImage(
            $decodedData,
            $diskName,
            'scan'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | COMPRESS IMAGE
    |--------------------------------------------------------------------------
    */

    private function storeCompressedImage(
        string $contents,
        string $diskName,
        string $prefix
    ): string {

        if (
            !function_exists(
                'imagecreatefromstring'
            ) ||
            !function_exists(
                'imagejpeg'
            )
        ) {

            throw new RuntimeException(
                'PHP GD belum tersedia. Aktifkan ekstensi GD pada server.'
            );
        }


        $imageInfo =
            @getimagesizefromstring(
                $contents
            );


        if (
            $imageInfo === false
        ) {

            throw new RuntimeException(
                'File bukan gambar yang valid.'
            );
        }


        $mime =
            strtolower(
                (string) (
                    $imageInfo['mime'] ??
                    ''
                )
            );


        if (
            $mime ===
            'image/jpg'
        ) {

            $mime =
                'image/jpeg';
        }


        if (
            !in_array(
                $mime,
                self::ALLOWED_IMAGE_MIMES,
                true
            )
        ) {

            throw new RuntimeException(
                'Format gambar tidak didukung.'
            );
        }


        $sourceWidth =
            (int) (
                $imageInfo[0] ??
                0
            );


        $sourceHeight =
            (int) (
                $imageInfo[1] ??
                0
            );


        if (
            $sourceWidth <= 0 ||
            $sourceHeight <= 0
        ) {

            throw new RuntimeException(
                'Dimensi gambar tidak valid.'
            );
        }


        $pixelCount =
            $sourceWidth *
            $sourceHeight;


        if (
            $pixelCount >
            50000000
        ) {

            throw new RuntimeException(
                'Resolusi gambar terlalu besar. Gunakan gambar dengan resolusi lebih kecil.'
            );
        }


        $source =
            @imagecreatefromstring(
                $contents
            );


        if (
            $source === false
        ) {

            throw new RuntimeException(
                'Gagal membaca gambar menggunakan GD.'
            );
        }


        $scale =
            min(
                self::MAX_IMAGE_WIDTH /
                    $sourceWidth,

                self::MAX_IMAGE_HEIGHT /
                    $sourceHeight,

                1
            );


        $newWidth =
            max(
                1,
                (int) round(
                    $sourceWidth *
                    $scale
                )
            );


        $newHeight =
            max(
                1,
                (int) round(
                    $sourceHeight *
                    $scale
                )
            );


        $canvas =
            @imagecreatetruecolor(
                $newWidth,
                $newHeight
            );


        if (
            $canvas === false
        ) {

            imagedestroy(
                $source
            );


            throw new RuntimeException(
                'Gagal membuat canvas gambar.'
            );
        }


        $white =
            imagecolorallocate(
                $canvas,
                255,
                255,
                255
            );


        imagefill(
            $canvas,
            0,
            0,
            $white
        );


        if (
            !imagecopyresampled(
                $canvas,
                $source,
                0,
                0,
                0,
                0,
                $newWidth,
                $newHeight,
                $sourceWidth,
                $sourceHeight
            )
        ) {

            imagedestroy(
                $source
            );

            imagedestroy(
                $canvas
            );


            throw new RuntimeException(
                'Gagal melakukan resize gambar.'
            );
        }


        imagedestroy(
            $source
        );


        $qualities = [
            self::JPEG_QUALITY,
            75,
            68,
            60,
            52,
            45,
            38,
        ];


        $compressedData =
            null;


        foreach (
            $qualities as $quality
        ) {

            ob_start();


            $success =
                imagejpeg(
                    $canvas,
                    null,
                    $quality
                );


            $output =
                ob_get_clean();


            if (
                !$success ||
                $output === false ||
                $output === ''
            ) {

                imagedestroy(
                    $canvas
                );


                throw new RuntimeException(
                    'Gagal melakukan compression gambar.'
                );
            }


            $compressedData =
                $output;


            if (
                strlen(
                    $compressedData
                ) <=
                self::MAX_COMPRESSED_IMAGE_SIZE
            ) {

                break;
            }
        }


        $attempt =
            0;


        while (
            $compressedData !== null &&
            strlen(
                $compressedData
            ) >
            self::MAX_COMPRESSED_IMAGE_SIZE &&
            $attempt < 5
        ) {

            $attempt++;


            $newWidth =
                max(
                    1,
                    (int) floor(
                        imagesx(
                            $canvas
                        ) *
                        0.75
                    )
                );


            $newHeight =
                max(
                    1,
                    (int) floor(
                        imagesy(
                            $canvas
                        ) *
                        0.75
                    )
                );


            $smallerCanvas =
                @imagecreatetruecolor(
                    $newWidth,
                    $newHeight
                );


            if (
                $smallerCanvas === false
            ) {

                imagedestroy(
                    $canvas
                );


                throw new RuntimeException(
                    'Gagal melakukan resize lanjutan gambar.'
                );
            }


            $white =
                imagecolorallocate(
                    $smallerCanvas,
                    255,
                    255,
                    255
                );


            imagefill(
                $smallerCanvas,
                0,
                0,
                $white
            );


            if (
                !imagecopyresampled(
                    $smallerCanvas,
                    $canvas,
                    0,
                    0,
                    0,
                    0,
                    $newWidth,
                    $newHeight,
                    imagesx($canvas),
                    imagesy($canvas)
                )
            ) {

                imagedestroy(
                    $canvas
                );

                imagedestroy(
                    $smallerCanvas
                );


                throw new RuntimeException(
                    'Gagal melakukan resize lanjutan gambar.'
                );
            }


            imagedestroy(
                $canvas
            );


            $canvas =
                $smallerCanvas;


            ob_start();


            $success =
                imagejpeg(
                    $canvas,
                    null,
                    45
                );


            $output =
                ob_get_clean();


            if (
                !$success ||
                $output === false ||
                $output === ''
            ) {

                imagedestroy(
                    $canvas
                );


                throw new RuntimeException(
                    'Gagal melakukan compression lanjutan gambar.'
                );
            }


            $compressedData =
                $output;
        }


        imagedestroy(
            $canvas
        );


        if (
            $compressedData === null ||
            $compressedData === ''
        ) {

            throw new RuntimeException(
                'Hasil compression gambar kosong.'
            );
        }


        if (
            strlen(
                $compressedData
            ) >
            self::MAX_FILE_SIZE
        ) {

            throw new RuntimeException(
                'Gambar masih melebihi batas 20 MB setelah compression.'
            );
        }


        return $this->storeBinaryFile(
            $compressedData,
            'jpg',
            'image/jpeg',
            $prefix,
            $diskName
        );
    }

    /*
    |--------------------------------------------------------------------------
    | STORE BINARY FILE
    |--------------------------------------------------------------------------
    */

    private function storeBinaryFile(
        string $contents,
        string $extension,
        string $mimeType,
        string $prefix,
        string $diskName
    ): string {
        if ($contents === '') {
            throw new RuntimeException('Data file kosong.');
        }

        if (strlen($contents) > self::MAX_FILE_SIZE) {
            throw new RuntimeException('Data file melebihi batas 20 MB.');
        }

        if ($diskName !== 'local') {
            throw new RuntimeException('FILESYSTEM_DISK harus menggunakan local.');
        }

        $safePrefix = Str::slug($prefix, '-');
        $extension = strtolower(trim($extension));
        if (!in_array($extension, self::ALLOWED_FILE_EXTENSIONS, true)) {
            throw new RuntimeException('Ekstensi file tidak diizinkan.');
        }

        $fileName = $safePrefix . '_' . now()->format('Ymd_His') . '_' . Str::lower(Str::random(12)) . '.' . $extension;
        $path = 'lampiran/surat_masuk/' . $fileName;

        try {
            $saved = Storage::disk('local')->put($path, $contents);
            if (!$saved) {
                throw new RuntimeException('Laravel gagal menyimpan file ke storage lokal.');
            }
            return $path;
        } catch (Throwable $e) {
            Log::error('Gagal menyimpan file Surat Masuk ke storage lokal.', [
                'message' => $e->getMessage(),
                'path' => $path,
                'extension' => $extension,
                'mime' => $mimeType,
                'size' => strlen($contents),
                'disk' => 'local',
            ]);
            throw new RuntimeException('Gagal menyimpan file ke storage lokal: ' . $e->getMessage(), previous: $e);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | FILE URL
    |--------------------------------------------------------------------------
    */

    private function getFileUrl(
        ?string $file
    ): ?string {

        if (
            !$file
        ) {
            return null;
        }


        if (
            filter_var(
                $file,
                FILTER_VALIDATE_URL
            )
        ) {

            return $file;
        }


        $diskName =
            $this->getStorageDisk();


        try {

            $disk =
                Storage::disk(
                    $diskName
                );


            if (
                !method_exists(
                    $disk,
                    'temporaryUrl'
                )
            ) {

                return null;
            }


            $mimeType =
                $this->getMimeTypeFromPath(
                    $file
                );


            $url =
                $disk->temporaryUrl(
                    $file,
                    now()->addMinutes(30),
                    [
                        'ResponseContentType' =>
                            $mimeType,

                        'ResponseContentDisposition' =>
                            'inline; filename="' .
                            basename($file) .
                            '"',
                    ]
                );


            return $url ?: null;

        } catch (
            Throwable $e
        ) {

            Log::warning(
                'Gagal membuat URL lampiran Surat Masuk.',
                [
                    'message' =>
                        $e->getMessage(),

                    'file' =>
                        $file,

                    'disk' =>
                        $diskName,
                ]
            );


            return null;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE STORAGE FILE
    |--------------------------------------------------------------------------
    */

    private function deleteStorageFile(
        ?string $file,
        ?string $diskName = null
    ): void {

        if (
            !$file ||
            filter_var(
                $file,
                FILTER_VALIDATE_URL
            )
        ) {

            return;
        }


        $diskName ??=
            $this->getStorageDisk();


        try {

            $disk =
                Storage::disk(
                    $diskName
                );


            if (
                $disk->exists(
                    $file
                )
            ) {

                $disk->delete(
                    $file
                );
            }

        } catch (
            Throwable $e
        ) {

            Log::warning(
                'Gagal menghapus file Surat Masuk.',
                [
                    'message' =>
                        $e->getMessage(),

                    'file' =>
                        $file,

                    'disk' =>
                        $diskName,
                ]
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CLEANUP PDF PREVIEW
    |--------------------------------------------------------------------------
    */

    private function cleanupPdfPreview(): void
    {
        $preview =
            session(
                'surat_masuk_pdf_preview'
            );


        if (
            is_array($preview) &&
            !empty(
                $preview['path']
            )
        ) {

            $path =
                (string) $preview['path'];


            if (
                $path !== '' &&
                is_file(
                    $path
                )
            ) {

                @unlink(
                    $path
                );
            }
        }


        session()->forget(
            'surat_masuk_pdf_preview'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | FORMAT BYTES
    |--------------------------------------------------------------------------
    */

    private function formatBytes(
        int $bytes
    ): string {

        if (
            $bytes <= 0
        ) {

            return '0 KB';
        }


        if (
            $bytes <
            1024 * 1024
        ) {

            return number_format(
                $bytes / 1024,
                1,
                ',',
                '.'
            ) .
            ' KB';
        }


        return number_format(
            $bytes / (
                1024 * 1024
            ),
            2,
            ',',
            '.'
        ) .
        ' MB';
    }

    /*
    |--------------------------------------------------------------------------
    | MIME TYPE
    |--------------------------------------------------------------------------
    */

    private function getMimeTypeFromPath(
        string $file
    ): string {

        return match (
            strtolower(
                pathinfo(
                    $file,
                    PATHINFO_EXTENSION
                )
            )
        ) {

            'pdf' =>
                'application/pdf',

            'jpg',
            'jpeg' =>
                'image/jpeg',

            'png' =>
                'image/png',

            default =>
                'application/octet-stream',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | PREVIEW LAMPIRAN
    |--------------------------------------------------------------------------
    */

    public function previewLampiran(SuratMasuk $suratMasuk)
    {
        $this->ensureCanView($suratMasuk);
        $file = $suratMasuk->lampiran_file;

        if (!$file) {
            abort(404, 'File lampiran tidak ditemukan.');
        }

        // Dukungan untuk record lama yang masih menyimpan URL eksternal.
        if (filter_var($file, FILTER_VALIDATE_URL)) {
            return redirect()->away($file);
        }

        $disk = Storage::disk($this->getStorageDisk());
        if (!$disk->exists($file)) {
            abort(404, 'File lampiran tidak ditemukan di penyimpanan lokal.');
        }

        $mimeType = $this->getMimeTypeFromPath($file);
        $contents = $disk->get($file);

        return response($contents, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . addslashes(basename($file)) . '"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | DOWNLOAD LAMPIRAN
    |--------------------------------------------------------------------------
    */

    public function downloadLampiran(SuratMasuk $suratMasuk)
    {
        $this->ensureCanView($suratMasuk);
        $file = $suratMasuk->lampiran_file;

        if (!$file) {
            abort(404, 'File lampiran tidak ditemukan.');
        }

        // Dukungan untuk record lama yang masih menyimpan URL eksternal.
        if (filter_var($file, FILTER_VALIDATE_URL)) {
            return redirect()->away($file);
        }

        $disk = Storage::disk($this->getStorageDisk());
        if (!$disk->exists($file)) {
            abort(404, 'File lampiran tidak ditemukan di penyimpanan lokal.');
        }

        $contents = $disk->get($file);

        return response()->streamDownload(
            function () use ($contents): void {
                echo $contents;
            },
            basename($file),
            [
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store, max-age=0',
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(
        SuratMasukRequest $request,
        SuratMasuk $suratMasuk
    ) {

        $this->ensureUserCanManageSurat();


        $data =
            $request->validated();


        $diskName =
            $this->getStorageDisk();


        $oldFile =
            $suratMasuk->lampiran_file;


        $newFile =
            null;


        DB::beginTransaction();


        try {

            if (
                array_key_exists(
                    'status',
                    $data
                )
            ) {

                $status =
                    strtolower(
                        trim(
                            (string) $data['status']
                        )
                    );


                if (
                    in_array(
                        $status,
                        self::STATUS_OPTIONS,
                        true
                    )
                ) {

                    $data['status'] =
                        $status;

                } else {

                    $data['status'] =
                        'baru';
                }
            }


            if (
                $request->hasFile(
                    'lampiran_file'
                )
            ) {

                $file =
                    $request->file(
                        'lampiran_file'
                    );


                if (
                    !$file ||
                    !$file->isValid()
                ) {

                    throw new RuntimeException(
                        $file
                            ? $this->getUploadErrorMessage(
                                $file
                            )
                            : 'File lampiran tidak ditemukan.'
                    );
                }


                $fileSize =
                    $file->getSize();


                if (
                    $fileSize === false ||
                    $fileSize <= 0
                ) {

                    throw new RuntimeException(
                        'Ukuran file tidak dapat dibaca.'
                    );
                }


                if (
                    $fileSize >
                    self::MAX_FILE_SIZE
                ) {

                    throw new RuntimeException(
                        'Ukuran file lampiran maksimal 20 MB.'
                    );
                }


                $extension =
                    strtolower(
                        trim(
                            (string) $file
                                ->getClientOriginalExtension()
                        )
                    );


                if (
                    $extension ===
                    'jpeg'
                ) {

                    $extension =
                        'jpg';
                }


                if (
                    !in_array(
                        $extension,
                        self::ALLOWED_FILE_EXTENSIONS,
                        true
                    )
                ) {

                    throw new RuntimeException(
                        'Format file tidak didukung. Gunakan PDF, JPG, JPEG, atau PNG.'
                    );
                }


                if (
                    $extension ===
                    'pdf'
                ) {

                    $newFile =
                        $this->storePdfUsingPreview(
                            $file,
                            $diskName
                        );

                } else {

                    $newFile =
                        $this->storeUploadedFile(
                            $file,
                            $diskName
                        );
                }


                $data['lampiran_file'] =
                    $newFile;

            } elseif (
                $request->filled(
                    'captured_image'
                )
            ) {

                $newFile =
                    $this->uploadBase64Image(
                        (string) $request->input(
                            'captured_image'
                        ),
                        $diskName
                    );


                $data['lampiran_file'] =
                    $newFile;

            } else {

                unset(
                    $data['lampiran_file']
                );
            }


            unset(
                $data['captured_image']
            );


            $suratMasuk->update(
                $data
            );


            $this->logActivity(
                'update',
                'surat_masuk',
                'Mengubah surat masuk ' .
                $suratMasuk->nomor_agenda
            );


            DB::commit();


            if (
                $newFile &&
                $oldFile &&
                $oldFile !== $newFile
            ) {

                $this->deleteStorageFile(
                    $oldFile,
                    $diskName
                );
            }


            $this->cleanupPdfPreview();


            return redirect()
                ->route(
                    'surat-masuk.index'
                )
                ->with(
                    'success',
                    'Surat masuk berhasil diperbarui.'
                );

        } catch (
            Throwable $e
        ) {

            DB::rollBack();


            if (
                $newFile &&
                $newFile !== $oldFile
            ) {

                $this->deleteStorageFile(
                    $newFile,
                    $diskName
                );
            }


            $this->cleanupPdfPreview();


            Log::error(
                'Gagal memperbarui Surat Masuk.',
                [
                    'message' =>
                        $e->getMessage(),

                    'surat_id' =>
                        $suratMasuk->id,

                    'user_id' =>
                        Auth::id(),

                    'disk' =>
                        $diskName,

                    'trace' =>
                        $e->getTraceAsString(),
                ]
            );


            return back()
                ->withInput()
                ->with(
                    'error',
                    'Gagal memperbarui surat masuk: ' .
                    $e->getMessage()
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY
    |--------------------------------------------------------------------------
    */

    public function destroy(
        SuratMasuk $suratMasuk
    ) {

        $this->ensureUserCanManageSurat();


        $nomor =
            $suratMasuk->nomor_agenda;


        try {

            DB::transaction(
                function () use (
                    $suratMasuk,
                    $nomor
                ): void {

                    $suratMasuk->delete();


                    $this->logActivity(
                        'delete',
                        'surat_masuk',
                        'Menghapus surat masuk ' .
                        $nomor
                    );
                }
            );


            return back()->with(
                'success',
                'Surat masuk berhasil dipindahkan ke arsip sampah.'
            );

        } catch (
            Throwable $e
        ) {

            Log::error(
                'Gagal menghapus Surat Masuk.',
                [
                    'message' =>
                        $e->getMessage(),

                    'surat_id' =>
                        $suratMasuk->id,

                    'user_id' =>
                        Auth::id(),
                ]
            );


            return back()->with(
                'error',
                'Gagal menghapus surat masuk: ' .
                $e->getMessage()
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CETAK LABEL
    |--------------------------------------------------------------------------
    */

    public function cetakLabel(
        SuratMasuk $suratMasuk
    ) {

        $this->ensureCanView(
            $suratMasuk
        );


        $suratMasuk->load(
            'kategori'
        );


        return view(
            'surat_masuk.label',
            compact(
                'suratMasuk'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CETAK DISPOSISI
    |--------------------------------------------------------------------------
    */

    public function cetakDisposisi(
        SuratMasuk $suratMasuk
    ) {

        $this->ensureCanView(
            $suratMasuk
        );


        $suratMasuk->load(
            [
                'kategori',
                'disposisi.dari',
                'disposisi.kepada',
            ]
        );


        return view(
            'surat_masuk.disposisi_pdf',
            compact(
                'suratMasuk'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESS CONTROL
    |--------------------------------------------------------------------------
    */

    private function ensureCanView(
        SuratMasuk $suratMasuk
    ): void {

        $this->ensureUserAuthenticated();


        $role =
            $this->resolveSuratUserRole();


        /*
        |--------------------------------------------------------------------------
        | ADMIN
        |--------------------------------------------------------------------------
        */

        if (
            $role === 'admin'
        ) {

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | PIMPINAN
        |--------------------------------------------------------------------------
        */

        if (
            $role === 'pimpinan'
        ) {

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | STAFF
        |--------------------------------------------------------------------------
        */

        if (
            $role === 'staff'
        ) {

            $hasDisposisi =
                $suratMasuk
                    ->disposisi()
                    ->where(
                        'kepada_user_id',
                        (int) Auth::id()
                    )
                    ->exists();


            if (
                $hasDisposisi
            ) {

                return;
            }
        }


        abort(
            403,
            'Anda tidak memiliki izin untuk melihat surat masuk ini.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RESOLVE ROLE
    |--------------------------------------------------------------------------
    */

    private function resolveSuratUserRole(): string
    {
        $user =
            Auth::user();


        if (
            !$user
        ) {

            return '';
        }


        $role =
            strtolower(
                trim(
                    (string) (
                        $user->role ??
                        ''
                    )
                )
            );


        if (
            $role === '' &&
            isset(
                $user->jabatan
            )
        ) {

            $role =
                strtolower(
                    trim(
                        (string) $user->jabatan
                    )
                );
        }


        return $role === 'staf'
            ? 'staff'
            : $role;
    }

    /*
    |--------------------------------------------------------------------------
    | STORAGE DISK
    |--------------------------------------------------------------------------
    */

    private function getStorageDisk(): string
    {
        $disk = strtolower(trim((string) config('filesystems.default', 'local')));
        if ($disk !== 'local') {
            throw new RuntimeException('FILESYSTEM_DISK harus diset ke "local" untuk penyimpanan file surat.');
        }
        return 'local';
    }


    /*
    |--------------------------------------------------------------------------
    | UPLOAD ERROR MESSAGE
    |--------------------------------------------------------------------------
    */

    private function getUploadErrorMessage(
        UploadedFile $file
    ): string {

        return match (
            $file->getError()
        ) {

            UPLOAD_ERR_INI_SIZE =>
                'Ukuran file melebihi batas upload server.',

            UPLOAD_ERR_FORM_SIZE =>
                'Ukuran file melebihi batas form.',

            UPLOAD_ERR_PARTIAL =>
                'File hanya terupload sebagian. Silakan coba lagi.',

            UPLOAD_ERR_NO_FILE =>
                'Tidak ada file yang dipilih.',

            UPLOAD_ERR_NO_TMP_DIR =>
                'Folder temporary upload PHP tidak tersedia.',

            UPLOAD_ERR_CANT_WRITE =>
                'PHP gagal menulis file upload.',

            UPLOAD_ERR_EXTENSION =>
                'Upload file dihentikan oleh konfigurasi PHP.',

            default =>
                'File gagal diupload. Kode upload PHP: ' .
                $file->getError(),
        };
    }

    /*
    |--------------------------------------------------------------------------
    | STORE UPLOADED FILE
    |--------------------------------------------------------------------------
    */

    private function storeUploadedFile(
        ?UploadedFile $file,
        string $diskName
    ): string {

        if (
            !$file
        ) {

            throw new RuntimeException(
                'File lampiran tidak ditemukan.'
            );
        }


        if (
            !$file->isValid()
        ) {

            throw new RuntimeException(
                $this->getUploadErrorMessage(
                    $file
                )
            );
        }


        $fileSize =
            $file->getSize();


        if (
            $fileSize === false ||
            $fileSize <= 0
        ) {

            throw new RuntimeException(
                'Ukuran file tidak dapat dibaca.'
            );
        }


        if (
            $fileSize >
            self::MAX_FILE_SIZE
        ) {

            throw new RuntimeException(
                'Ukuran file lampiran maksimal 20 MB.'
            );
        }


        $extension =
            strtolower(
                trim(
                    (string) $file
                        ->getClientOriginalExtension()
                )
            );


        if (
            $extension ===
            'jpeg'
        ) {

            $extension =
                'jpg';
        }


        if (
            !in_array(
                $extension,
                self::ALLOWED_FILE_EXTENSIONS,
                true
            )
        ) {

            throw new RuntimeException(
                'Format file tidak didukung. Gunakan PDF, JPG, JPEG, atau PNG.'
            );
        }


        $realPath =
            $file->getRealPath();


        if (
            !$realPath ||
            !is_readable(
                $realPath
            )
        ) {

            throw new RuntimeException(
                'File temporary upload tidak dapat dibaca.'
            );
        }


        if (
            $extension ===
            'pdf'
        ) {

            $mime =
                strtolower(
                    (string) $file->getMimeType()
                );


            if (
                $mime !==
                'application/pdf'
            ) {

                throw new RuntimeException(
                    'File PDF tidak valid.'
                );
            }


            return $this->storePdfUsingPreview(
                $file,
                $diskName
            );
        }


        $contents =
            file_get_contents(
                $realPath
            );


        if (
            $contents === false ||
            $contents === ''
        ) {

            throw new RuntimeException(
                'Gagal membaca file gambar.'
            );
        }


        $imageInfo =
            @getimagesizefromstring(
                $contents
            );


        if (
            $imageInfo === false
        ) {

            throw new RuntimeException(
                'File bukan gambar yang valid.'
            );
        }


        $actualMime =
            strtolower(
                (string) (
                    $imageInfo['mime'] ??
                    ''
                )
            );


        if (
            $actualMime ===
            'image/jpg'
        ) {

            $actualMime =
                'image/jpeg';
        }


        if (
            !in_array(
                $actualMime,
                self::ALLOWED_IMAGE_MIMES,
                true
            )
        ) {

            throw new RuntimeException(
                'Jenis file gambar tidak didukung.'
            );
        }

        // Gambar di bawah 2 MB disimpan dalam format aslinya tanpa kompresi.
        if (
            $fileSize <
            self::COMPRESSION_THRESHOLD
        ) {
            return $this->storeBinaryFile(
                $contents,
                $extension,
                $actualMime,
                'surat-masuk',
                $diskName
            );
        }


        return $this->storeCompressedImage(
            $contents,
            $diskName,
            'surat-masuk'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DATE VALIDATION
    |--------------------------------------------------------------------------
    */

    private function isValidDate(
        ?string $date
    ): bool {

        if (
            $date === null
        ) {

            return false;
        }


        $date =
            trim(
                $date
            );


        if (
            !preg_match(
                '/^\d{4}-\d{2}-\d{2}$/',
                $date
            )
        ) {

            return false;
        }


        $parts =
            explode(
                '-',
                $date
            );


        if (
            count(
                $parts
            ) !== 3
        ) {

            return false;
        }


        return checkdate(
            (int) $parts[1],
            (int) $parts[2],
            (int) $parts[0]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ACTIVITY LOG
    |--------------------------------------------------------------------------
    */

    private function logActivity(
        string $action,
        string $module,
        string $description
    ): void {

        try {

            if (
                class_exists(
                    ActivityLog::class
                ) &&
                method_exists(
                    ActivityLog::class,
                    'catat'
                )
            ) {

                ActivityLog::catat(
                    $action,
                    $module,
                    $description
                );
            }

        } catch (
            Throwable $e
        ) {

            Log::warning(
                'Gagal mencatat Activity Log Surat Masuk.',
                [
                    'message' =>
                        $e->getMessage(),

                    'action' =>
                        $action,

                    'module' =>
                        $module,
                ]
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | AUTHENTICATION
    |--------------------------------------------------------------------------
    */

    private function ensureUserAuthenticated(): void
    {
        if (
            !Auth::check()
        ) {

            abort(
                401,
                'Anda harus login terlebih dahulu.'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | MANAGE SURAT
    |--------------------------------------------------------------------------
    */

    private function ensureUserCanManageSurat(): void
    {
        $this->ensureUserAuthenticated();


        $role =
            $this->resolveSuratUserRole();


        if (
            !in_array(
                $role,
                [
                    'admin',
                    'pimpinan',
                ],
                true
            )
        ) {

            abort(
                403,
                'Anda tidak memiliki izin untuk mengelola surat masuk.'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | STAFF CHECK
    |--------------------------------------------------------------------------
    */

    private function userIsStaff(): bool
    {
        return
            $this->resolveSuratUserRole() ===
            'staff';
    }
}