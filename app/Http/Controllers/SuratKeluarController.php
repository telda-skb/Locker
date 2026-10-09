<?php

namespace App\Http\Controllers;

use App\Http\Requests\SuratKeluarRequest;
use App\Models\ActivityLog;
use App\Models\KategoriSurat;
use App\Models\SuratKeluar;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class SuratKeluarController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | STATUS
    |--------------------------------------------------------------------------
    */

    private const STATUS_OPTIONS = [
        'draft',
        'diproses',
        'disetujui',
        'dikirim',
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
    | PDF
    |--------------------------------------------------------------------------
    */

    private const PDF_PREVIEW_TTL =
        1800;

    private const PDF_COMPRESSION_PROFILES = [
        [
            'name' => 'ebook-150',
            'preset' => '/ebook',
            'color_dpi' => 150,
            'gray_dpi' => 150,
            'mono_dpi' => 300,
            'jpeg_quality' => 70,
        ],

        [
            'name' => 'ebook-120',
            'preset' => '/ebook',
            'color_dpi' => 120,
            'gray_dpi' => 120,
            'mono_dpi' => 240,
            'jpeg_quality' => 60,
        ],

        [
            'name' => 'screen-96',
            'preset' => '/screen',
            'color_dpi' => 96,
            'gray_dpi' => 96,
            'mono_dpi' => 200,
            'jpeg_quality' => 50,
        ],
    ];

    /*
    |--------------------------------------------------------------------------
    | IMAGE
    |--------------------------------------------------------------------------
    */

    private const MAX_IMAGE_WIDTH =
        2500;

    private const MAX_IMAGE_HEIGHT =
        2500;

    private const MAX_COMPRESSED_IMAGE_SIZE =
        20 * 1024 * 1024;

    private const JPEG_QUALITY =
        82;

    /*
    |--------------------------------------------------------------------------
    | AUTH / ROLE
    |--------------------------------------------------------------------------
    */

    private function userRole(): string
    {
        $user = Auth::user();

        if (!$user) {
            return '';
        }

        if (method_exists($user, 'normalizedRole')) {
            $role = $user->normalizedRole();
        } else {
            $role = strtolower(
                trim(
                    (string) (
                        $user->role ??
                        $user->jabatan ??
                        ''
                    )
                )
            );
        }

        return $role === 'staf'
            ? 'staff'
            : $role;
    }

    private function ensureUserAuthenticated(): void
    {
        if (!Auth::check()) {
            abort(
                401,
                'Anda harus login terlebih dahulu.'
            );
        }
    }

    private function authorizeManageSurat(): void
    {
        $this->ensureUserAuthenticated();

        if (!in_array(
            $this->userRole(),
            [
                'admin',
                'pimpinan',
            ],
            true
        )) {
            abort(
                403,
                'Anda tidak memiliki hak akses untuk mengelola surat keluar.'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | STATUS
    |--------------------------------------------------------------------------
    */

    private function normalizeStatus(
        ?string $status
    ): string {
        $status = strtolower(
            trim(
                (string) $status
            )
        );

        if ($status === 'draf') {
            return 'draft';
        }

        return $status;
    }

    private function getValidStatus(
        ?string $status
    ): string {
        $status = $this->normalizeStatus($status);

        return in_array(
            $status,
            self::STATUS_OPTIONS,
            true
        )
            ? $status
            : 'draft';
    }

    /*
    |--------------------------------------------------------------------------
    | FILTER QUERY
    |--------------------------------------------------------------------------
    */

    public function buildFilteredQuery(
        Request $request
    ): Builder {
        $this->ensureUserAuthenticated();

        $query = SuratKeluar::query()
            ->with([
                'kategori',
                'pembuat',
                'penandatangan',
            ]);

        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */

        $search = trim(
            (string) $request->input(
                'search',
                ''
            )
        );

        if ($search !== '') {
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
                            'nomor_surat',
                            'like',
                            $keyword
                        )
                        ->orWhere(
                            'perihal',
                            'like',
                            $keyword
                        )
                        ->orWhere(
                            'pengirim',
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
                        )
                        ->orWhereHas(
                            'pembuat',
                            function (
                                Builder $user
                            ) use (
                                $keyword
                            ): void {
                                $user
                                    ->where(
                                        'name',
                                        'like',
                                        $keyword
                                    )
                                    ->orWhere(
                                        'email',
                                        'like',
                                        $keyword
                                    )
                                    ->orWhere(
                                        'jabatan',
                                        'like',
                                        $keyword
                                    );
                            }
                        )
                        ->orWhereHas(
                            'penandatangan',
                            function (
                                Builder $user
                            ) use (
                                $keyword
                            ): void {
                                $user
                                    ->where(
                                        'name',
                                        'like',
                                        $keyword
                                    )
                                    ->orWhere(
                                        'email',
                                        'like',
                                        $keyword
                                    )
                                    ->orWhere(
                                        'jabatan',
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

        $kategoriInput =
            $request->input(
                'kategori_id',
                $request->input(
                    'kategori_surat_id',
                    []
                )
            );

        if (
            is_scalar($kategoriInput) &&
            trim(
                (string) $kategoriInput
            ) !== ''
        ) {
            $kategoriInput = [
                $kategoriInput,
            ];
        }

        if (!is_array($kategoriInput)) {
            $kategoriInput = [];
        }

        $kategoriIds =
            collect($kategoriInput)
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
                ->values();

        if ($kategoriIds->isNotEmpty()) {
            $query->whereIn(
                'kategori_surat_id',
                $kategoriIds->all()
            );
        }

        /*
        |--------------------------------------------------------------------------
        | STATUS
        |--------------------------------------------------------------------------
        */

        $statusInput =
            $request->input(
                'status',
                []
            );

        if (
            is_scalar($statusInput) &&
            trim(
                (string) $statusInput
            ) !== ''
        ) {
            $statusInput = [
                $statusInput,
            ];
        }

        if (!is_array($statusInput)) {
            $statusInput = [];
        }

        $statuses =
            collect($statusInput)
                ->flatten()
                ->filter(
                    fn ($status) =>
                        is_scalar($status)
                )
                ->map(
                    fn ($status) =>
                        $this->normalizeStatus(
                            (string) $status
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
                ->values();

        if ($statuses->isNotEmpty()) {
            $query->whereIn(
                'status',
                $statuses->all()
            );
        }

        /*
        |--------------------------------------------------------------------------
        | TANGGAL
        |--------------------------------------------------------------------------
        */

        $dariTanggal = trim(
            (string) $request->input(
                'dari_tanggal',
                ''
            )
        );

        $sampaiTanggal = trim(
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

        if ($validDariTanggal) {
            $query->whereDate(
                'tanggal_keluar',
                '>=',
                $dariTanggal
            );
        }

        if ($validSampaiTanggal) {
            $query->whereDate(
                'tanggal_keluar',
                '<=',
                $sampaiTanggal
            );
        }

        return $query;
    }

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
        | STATISTIK
        |--------------------------------------------------------------------------
        |
        | Statistik dihitung dari seluruh arsip surat keluar.
        | Tidak dipengaruhi search, filter, maupun pagination.
        | Empat nilai utama digunakan sebagai scorecard halaman.
        | Disetujui dan Diarsipkan juga dihitung untuk ringkasan status.
        |
        */

        $statistics = SuratKeluar::query()
            ->selectRaw('COUNT(*) AS total_surat_keluar')
            ->selectRaw(
                "SUM(
                    CASE
                        WHEN LOWER(TRIM(status)) IN ('draft', 'draf')
                        THEN 1
                        ELSE 0
                    END
                ) AS surat_draft"
            )
            ->selectRaw(
                "SUM(
                    CASE
                        WHEN LOWER(TRIM(status)) = 'diproses'
                        THEN 1
                        ELSE 0
                    END
                ) AS surat_diproses"
            )
            ->selectRaw(
                "SUM(
                    CASE
                        WHEN LOWER(TRIM(status)) = 'disetujui'
                        THEN 1
                        ELSE 0
                    END
                ) AS surat_disetujui"
            )
            ->selectRaw(
                "SUM(
                    CASE
                        WHEN LOWER(TRIM(status)) = 'dikirim'
                        THEN 1
                        ELSE 0
                    END
                ) AS surat_dikirim"
            )
            ->selectRaw(
                "SUM(
                    CASE
                        WHEN LOWER(TRIM(status)) = 'diarsipkan'
                        THEN 1
                        ELSE 0
                    END
                ) AS surat_diarsipkan"
            )
            ->first();

        $totalSuratKeluar = (int) ($statistics->total_surat_keluar ?? 0);
        $suratDraft = (int) ($statistics->surat_draft ?? 0);
        $suratDiproses = (int) ($statistics->surat_diproses ?? 0);
        $suratDisetujui = (int) ($statistics->surat_disetujui ?? 0);
        $suratDikirim = (int) ($statistics->surat_dikirim ?? 0);
        $suratDiarsipkan = (int) ($statistics->surat_diarsipkan ?? 0);

        $statusCounts = [
            'total' => $totalSuratKeluar,
            'draft' => $suratDraft,
            'diproses' => $suratDiproses,
            'disetujui' => $suratDisetujui,
            'dikirim' => $suratDikirim,
            'diarsipkan' => $suratDiarsipkan,
        ];

        /*
        |--------------------------------------------------------------------------
        | QUERY DATA TABEL
        |--------------------------------------------------------------------------
        |
        | Tetap menggunakan semua filter yang sudah tersedia.
        |
        */

        $query = $this->buildFilteredQuery($request);

        /*
        |--------------------------------------------------------------------------
        | STATUS FILE EXPORT
        |--------------------------------------------------------------------------
        */

        $hasUploadedFile = (clone $query)
            ->whereNotNull('lampiran_file')
            ->where('lampiran_file', '<>', '')
            ->exists();

        /*
        |--------------------------------------------------------------------------
        | RINGKASAN KATEGORI
        |--------------------------------------------------------------------------
        |
        | Mengikuti seluruh data yang dapat dilihat user,
        | bukan hanya halaman pagination aktif.
        |
        */

        $categoryCounts = SuratKeluar::query()
            ->select('kategori_surat_id')
            ->selectRaw('COUNT(*) AS total')
            ->with('kategori:id,nama_kategori')
            ->groupBy('kategori_surat_id')
            ->orderByDesc('total')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | URUTAN DATA + PAGINATION
        |--------------------------------------------------------------------------
        |
        | Default: terbaru ke terlama (DESC).
        | sort=asc: terlama ke terbaru.
        | Nilai selain asc/desc dikembalikan ke DESC.
        |
        */

        $sortOrder = strtolower(
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

        $suratKeluars = $query
            ->orderByRaw('tanggal_keluar IS NULL ASC')
            ->orderBy('tanggal_keluar', $sortOrder)
            ->orderBy('created_at', $sortOrder)
            ->orderBy('id', $sortOrder)
            ->paginate(5)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | KATEGORI
        |--------------------------------------------------------------------------
        */

        $kategoris = KategoriSurat::query()
            ->orderBy('nama_kategori')
            ->get();

        return view(
            'surat_keluar.index',
            [
                'suratKeluars' => $suratKeluars,
                'kategoris' => $kategoris,
                'totalSuratKeluar' => $totalSuratKeluar,
                'suratDraft' => $suratDraft,
                'suratDiproses' => $suratDiproses,
                'suratDisetujui' => $suratDisetujui,
                'suratDikirim' => $suratDikirim,
                'suratDiarsipkan' => $suratDiarsipkan,
                'statusCounts' => $statusCounts,
                'categoryCounts' => $categoryCounts,
                'hasUploadedFile' => $hasUploadedFile,
                'sortOrder' => $sortOrder,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        $this->authorizeManageSurat();

        $this->cleanupPdfPreview();

        $kategoris =
            KategoriSurat::query()
                ->orderBy(
                    'nama_kategori'
                )
                ->get();

        $users =
            User::query()
                ->where(
                    'is_active',
                    true
                )
                ->orderBy(
                    'name'
                )
                ->get();

        return view(
            'surat_keluar.create',
            compact(
                'kategoris',
                'users'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PREVIEW COMPRESSION
    |--------------------------------------------------------------------------
    */

    public function previewCompression(
        Request $request
    ) {
        $this->authorizeManageSurat();

        $request->validate(
            [
                'lampiran_file' => [
                    'required',
                    'file',
                    'mimes:pdf,jpg,jpeg,png',
                    'max:' . self::MAX_FILE_SIZE_KB,
                ],
            ],
            [
                'lampiran_file.required' =>
                    'File lampiran wajib dipilih.',

                'lampiran_file.file' =>
                    'File lampiran tidak valid.',

                'lampiran_file.mimes' =>
                    'Gunakan PDF, JPG, JPEG, atau PNG.',

                'lampiran_file.max' =>
                    'Ukuran file maksimal 20 MB.',
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
                                $file->getError()
                            )
                            : 'File tidak ditemukan.',
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

        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }

        try {
            $originalSize =
                (int) $file->getSize();

            if ($originalSize <= 0) {
                throw new RuntimeException(
                    'Ukuran file tidak valid.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | PDF
            |--------------------------------------------------------------------------
            */

            if ($extension === 'pdf') {
                $inputPath =
                    $file->getRealPath();

                if (
                    !$inputPath ||
                    !is_readable($inputPath)
                ) {
                    throw new RuntimeException(
                        'File PDF temporary tidak dapat dibaca.'
                    );
                }

                // PDF di bawah 2 MB tidak perlu dikompres.
                if (
                    $originalSize <
                    self::COMPRESSION_THRESHOLD
                ) {
                    $token = Str::random(64);
                    $directory = storage_path('app/pdf-compression');

                    if (
                        !is_dir($directory) &&
                        !mkdir($directory, 0775, true) &&
                        !is_dir($directory)
                    ) {
                        throw new RuntimeException(
                            'Folder preview PDF tidak dapat dibuat.'
                        );
                    }

                    $previewPath =
                        $directory . DIRECTORY_SEPARATOR . 'preview_' . $token . '.pdf';

                    if (!copy($inputPath, $previewPath)) {
                        throw new RuntimeException(
                            'Gagal membuat preview PDF asli.'
                        );
                    }

                    $fileHash = hash_file('sha256', $inputPath);

                    if (!$fileHash) {
                        @unlink($previewPath);
                        throw new RuntimeException(
                            'Hash file PDF tidak dapat dibuat.'
                        );
                    }

                    session()->put(
                        'surat_keluar_pdf_preview',
                        [
                            'token' => $token,
                            'path' => $previewPath,
                            'file_hash' => $fileHash,
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
                            'type' => 'pdf',
                            'compressed' => false,
                            'token' => $token,
                            'preview_url' => route(
                                'surat-keluar.compression-preview',
                                ['token' => $token]
                            ),
                            'original_size' => $originalSize,
                            'compressed_size' => $originalSize,
                            'original_size_text' => $this->formatBytes($originalSize),
                            'compressed_size_text' => $this->formatBytes($originalSize),
                            'saving_percent' => 0,
                            'profile' => 'none',
                            'message' => 'PDF di bawah 2 MB, sehingga tidak perlu dikompres. File asli akan digunakan.',
                        ]
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | HASH
                |--------------------------------------------------------------------------
                */

                $fileHash =
                    hash_file(
                        'sha256',
                        $inputPath
                    );

                if (!$fileHash) {
                    throw new RuntimeException(
                        'Hash file PDF tidak dapat dibuat.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | HAPUS PREVIEW LAMA
                |--------------------------------------------------------------------------
                */

                $this->cleanupPdfPreview();

                /*
                |--------------------------------------------------------------------------
                | COMPRESS
                |--------------------------------------------------------------------------
                */

                $result =
                    $this->compressPdfToTemporaryFile(
                        $inputPath
                    );

                $compressedSize =
                    (int) $result['size'];

                $profile =
                    (string) $result['profile'];

                $temporaryCompressedPath =
                    (string) $result['path'];

                /*
                |--------------------------------------------------------------------------
                | HASIL TIDAK LEBIH KECIL
                |--------------------------------------------------------------------------
                */

                if (
                    $compressedSize >=
                    $originalSize
                ) {
                    if (
                        is_file(
                            $temporaryCompressedPath
                        )
                    ) {
                        @unlink(
                            $temporaryCompressedPath
                        );
                    }

                    $token =
                        Str::random(64);

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
                            'Folder preview PDF tidak dapat dibuat.'
                        );
                    }

                    $previewPath =
                        $directory .
                        DIRECTORY_SEPARATOR .
                        'preview_' .
                        $token .
                        '.pdf';

                    if (
                        !copy(
                            $inputPath,
                            $previewPath
                        )
                    ) {
                        throw new RuntimeException(
                            'Gagal membuat preview PDF asli.'
                        );
                    }

                    session()->put(
                        'surat_keluar_pdf_preview',
                        [
                            'token' =>
                                $token,

                            'path' =>
                                $previewPath,

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

                            'type' =>
                                'pdf',

                            'compressed' =>
                                false,

                            'token' =>
                                $token,

                            'preview_url' =>
                                route(
                                    'surat-keluar.compression-preview',
                                    [
                                        'token' =>
                                            $token,
                                    ]
                                ),

                            'original_size' =>
                                $originalSize,

                            'compressed_size' =>
                                $originalSize,

                            'original_size_text' =>
                                $this->formatBytes(
                                    $originalSize
                                ),

                            'compressed_size_text' =>
                                $this->formatBytes(
                                    $originalSize
                                ),

                            'saving_percent' =>
                                0,

                            'profile' =>
                                $profile,

                            'message' =>
                                'PDF sudah cukup optimal. File asli digunakan sebagai hasil preview dan akan dipertahankan saat disimpan.',
                        ]
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | HASIL COMPRESSED
                |--------------------------------------------------------------------------
                */

                $token =
                    Str::random(64);

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
                    if (
                        is_file(
                            $temporaryCompressedPath
                        )
                    ) {
                        @unlink(
                            $temporaryCompressedPath
                        );
                    }

                    throw new RuntimeException(
                        'Folder preview PDF tidak dapat dibuat.'
                    );
                }

                $previewPath =
                    $directory .
                    DIRECTORY_SEPARATOR .
                    'preview_' .
                    $token .
                    '.pdf';

                if (
                    !rename(
                        $temporaryCompressedPath,
                        $previewPath
                    )
                ) {
                    if (
                        is_file(
                            $temporaryCompressedPath
                        )
                    ) {
                        @unlink(
                            $temporaryCompressedPath
                        );
                    }

                    throw new RuntimeException(
                        'Gagal menyimpan preview PDF hasil compression.'
                    );
                }

                session()->put(
                    'surat_keluar_pdf_preview',
                    [
                        'token' =>
                            $token,

                        'path' =>
                            $previewPath,

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
                    $this->calculateSavingPercent(
                        $originalSize,
                        $compressedSize
                    );

                Log::info(
                    'Preview compression PDF Surat Keluar berhasil.',
                    [
                        'user_id' =>
                            Auth::id(),

                        'original_size' =>
                            $originalSize,

                        'compressed_size' =>
                            $compressedSize,

                        'saving_percent' =>
                            $savingPercent,

                        'profile' =>
                            $profile,

                        'token' =>
                            $token,
                    ]
                );

                return response()->json(
                    [
                        'success' =>
                            true,

                        'type' =>
                            'pdf',

                        'compressed' =>
                            true,

                        'token' =>
                            $token,

                        'preview_url' =>
                            route(
                                'surat-keluar.compression-preview',
                                [
                                    'token' =>
                                        $token,
                                ]
                            ),

                        'original_size' =>
                            $originalSize,

                        'compressed_size' =>
                            $compressedSize,

                        'original_size_text' =>
                            $this->formatBytes(
                                $originalSize
                            ),

                        'compressed_size_text' =>
                            $this->formatBytes(
                                $compressedSize
                            ),

                        'saving_percent' =>
                            $savingPercent,

                        'profile' =>
                            $profile,

                        'message' =>
                            'PDF berhasil dikompresi. Hasil compression tersedia untuk preview.',
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | IMAGE
            |--------------------------------------------------------------------------
            */

            // Gambar di bawah 2 MB tidak perlu dikompres.
            if (
                $originalSize <
                self::COMPRESSION_THRESHOLD
            ) {
                return response()->json(
                    [
                        'success' => true,
                        'type' => 'image',
                        'compressed' => false,
                        'original_size' => $originalSize,
                        'compressed_size' => $originalSize,
                        'original_size_text' => $this->formatBytes($originalSize),
                        'compressed_size_text' => $this->formatBytes($originalSize),
                        'saving_percent' => 0,
                        'profile' => 'none',
                        'message' => 'Gambar di bawah 2 MB, sehingga tidak perlu dikompres. File asli akan digunakan.',
                    ]
                );
            }

            $result =
                $this->compressImageToTemporaryFile(
                    $file
                );

            $compressedSize =
                (int) $result['size'];

            if (
                is_file(
                    $result['path']
                )
            ) {
                @unlink(
                    $result['path']
                );
            }

            return response()->json(
                [
                    'success' =>
                        true,

                    'type' =>
                        'image',

                    'compressed' =>
                        $compressedSize <
                        $originalSize,

                    'original_size' =>
                        $originalSize,

                    'compressed_size' =>
                        $compressedSize,

                    'original_size_text' =>
                        $this->formatBytes(
                            $originalSize
                        ),

                    'compressed_size_text' =>
                        $this->formatBytes(
                            $compressedSize
                        ),

                    'saving_percent' =>
                        $this->calculateSavingPercent(
                            $originalSize,
                            $compressedSize
                        ),

                    'profile' =>
                        'GD JPEG',

                    'message' =>
                        $compressedSize <
                        $originalSize
                            ? 'Gambar berhasil dikompresi.'
                            : 'Gambar tidak menjadi lebih kecil.',
                ]
            );

        } catch (Throwable $e) {
            Log::error(
                'Gagal preview compression Surat Keluar.',
                [
                    'message' =>
                        $e->getMessage(),

                    'file' =>
                        $file
                            ? $file->getClientOriginalName()
                            : null,

                    'user_id' =>
                        Auth::id(),

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
    | COMPRESSION PREVIEW
    |--------------------------------------------------------------------------
    */

    public function compressionPreview(
        string $token
    ) {
        $this->authorizeManageSurat();

        $token =
            trim(
                $token
            );

        if ($token === '') {
            abort(
                404,
                'Token preview tidak valid.'
            );
        }

        $preview =
            session(
                'surat_keluar_pdf_preview'
            );

        if (
            !is_array($preview)
        ) {
            abort(
                404,
                'Preview PDF tidak ditemukan.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDASI TOKEN
        |--------------------------------------------------------------------------
        */

        $previewToken =
            (string) (
                $preview['token'] ??
                ''
            );

        if (
            $previewToken === '' ||
            !hash_equals(
                $previewToken,
                $token
            )
        ) {
            abort(
                404,
                'Token preview PDF tidak valid.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDASI USER
        |--------------------------------------------------------------------------
        */

        $previewUserId =
            (int) (
                $preview['user_id'] ??
                0
            );

        if (
            $previewUserId !==
            (int) Auth::id()
        ) {
            abort(
                403,
                'Preview PDF bukan milik pengguna ini.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDASI WAKTU
        |--------------------------------------------------------------------------
        */

        $createdAt =
            (int) (
                $preview['created_at'] ??
                0
            );

        $age =
            now()->timestamp -
            $createdAt;

        if (
            $createdAt <= 0 ||
            $age < 0 ||
            $age > self::PDF_PREVIEW_TTL
        ) {
            $this->cleanupPdfPreview();

            abort(
                410,
                'Preview PDF telah kedaluwarsa.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | PATH
        |--------------------------------------------------------------------------
        */

        $path =
            (string) (
                $preview['path'] ??
                ''
            );

        if (
            $path === '' ||
            !is_file($path) ||
            !is_readable($path)
        ) {
            $this->cleanupPdfPreview();

            abort(
                404,
                'File preview PDF tidak ditemukan.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDASI PDF
        |--------------------------------------------------------------------------
        */

        $header =
            @file_get_contents(
                $path,
                false,
                null,
                0,
                5
            );

        if (
            $header !==
            '%PDF-'
        ) {
            $this->cleanupPdfPreview();

            abort(
                415,
                'File preview bukan PDF yang valid.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | RESPONSE INLINE
        |--------------------------------------------------------------------------
        */

        return response()->file(
            $path,
            [
                'Content-Type' =>
                    'application/pdf',

                'Content-Disposition' =>
                    'inline; filename="preview-compression.pdf"',

                'Cache-Control' =>
                    'private, max-age=300',

                'X-Content-Type-Options' =>
                    'nosniff',
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */

    public function store(
        SuratKeluarRequest $request
    ) {
        $this->authorizeManageSurat();

        $data =
            $request->validated();

        $data['dibuat_oleh'] =
            Auth::id();

        $data['status'] =
            $this->getValidStatus(
                $data['status'] ??
                'draft'
            );

        $storedAttachment =
            null;

        DB::beginTransaction();

        try {
            /*
            |--------------------------------------------------------------------------
            | FILE
            |--------------------------------------------------------------------------
            */

            if (
                $request->hasFile(
                    'lampiran_file'
                )
            ) {
                $storedAttachment =
                    $this->storeUploadedFile(
                        $request->file(
                            'lampiran_file'
                        )
                    );

                $data['lampiran_file'] =
                    $storedAttachment;

            } elseif (
                $request->filled(
                    'captured_image'
                )
            ) {
                $storedAttachment =
                    $this->storeBase64Image(
                        (string) $request->input(
                            'captured_image'
                        )
                    );

                $data['lampiran_file'] =
                    $storedAttachment;
            }

            unset(
                $data['captured_image']
            );

            /*
            |--------------------------------------------------------------------------
            | CREATE
            |--------------------------------------------------------------------------
            */

            $suratKeluar =
                SuratKeluar::create(
                    $data
                );

            /*
            |--------------------------------------------------------------------------
            | LOG
            |--------------------------------------------------------------------------
            */

            $nomorSurat = trim(
                (string) (
                    $suratKeluar->nomor_surat ??
                    ''
                )
            );

            $perihalSurat = trim(
                (string) (
                    $suratKeluar->perihal ??
                    ''
                )
            );

            $statusSurat = $this->getValidStatus(
                $suratKeluar->status
            );

            $actorName = trim(
                (string) (
                    Auth::user()?->name ??
                    'Sistem'
                )
            );

            $this->logActivity(
                'create',
                'surat_keluar',
                $actorName .
                ' membuat surat keluar #' .
                $suratKeluar->id .
                ' | Nomor: ' .
                ($nomorSurat !== '' ? $nomorSurat : '-') .
                ' | Perihal: ' .
                ($perihalSurat !== '' ? $perihalSurat : 'Tanpa perihal') .
                ' | Status: ' .
                ucfirst($statusSurat)
            );

            DB::commit();

            /*
            |--------------------------------------------------------------------------
            | CLEANUP
            |--------------------------------------------------------------------------
            */

            $this->cleanupPdfPreview();

            return redirect()
                ->route(
                    'surat-keluar.index'
                )
                ->with(
                    'success',
                    'Surat keluar berhasil ditambahkan.'
                );

        } catch (Throwable $e) {
            DB::rollBack();

            if ($storedAttachment) {
                $this->deleteAttachment(
                    $storedAttachment
                );
            }

            $this->cleanupPdfPreview();

            Log::error(
                'Gagal menyimpan surat keluar.',
                [
                    'message' =>
                        $e->getMessage(),

                    'user_id' =>
                        Auth::id(),

                    'disk' =>
                        $this->getStorageDisk(),

                    'trace' =>
                        $e->getTraceAsString(),
                ]
            );

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Gagal menyimpan surat keluar: ' .
                    $e->getMessage()
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW
    |--------------------------------------------------------------------------
    */

    public function show(
        SuratKeluar $suratKeluar
    ) {
        $this->ensureUserAuthenticated();

        $suratKeluar->load(
            [
                'kategori',
                'pembuat',
                'penandatangan',
            ]
        );

        $fileUrl =
            $this->getFileUrl(
                $suratKeluar->lampiran_file,
                $suratKeluar
            );

        return view(
            'surat_keluar.show',
            compact(
                'suratKeluar',
                'fileUrl'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT
    |--------------------------------------------------------------------------
    */

    public function edit(
        SuratKeluar $suratKeluar
    ) {
        $this->authorizeManageSurat();

        $this->cleanupPdfPreview();

        $kategoris =
            KategoriSurat::query()
                ->orderBy(
                    'nama_kategori'
                )
                ->get();

        $users =
            User::query()
                ->where(
                    'is_active',
                    true
                )
                ->orderBy(
                    'name'
                )
                ->get();

        $fileUrl =
            $this->getFileUrl(
                $suratKeluar->lampiran_file,
                $suratKeluar
            );

        return view(
            'surat_keluar.edit',
            compact(
                'suratKeluar',
                'kategoris',
                'users',
                'fileUrl'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(
        SuratKeluarRequest $request,
        SuratKeluar $suratKeluar
    ) {
        $this->authorizeManageSurat();

        $data =
            $request->validated();

        $oldStatus =
            $this->getValidStatus(
                $suratKeluar->status
            );

        $data['status'] =
            $this->getValidStatus(
                $data['status'] ??
                $suratKeluar->status
            );

        $newStatus =
            $data['status'];

        $oldAttachment =
            $suratKeluar->lampiran_file;

        $newAttachment =
            null;

        DB::beginTransaction();

        try {
            /*
            |--------------------------------------------------------------------------
            | FILE BARU
            |--------------------------------------------------------------------------
            */

            if (
                $request->hasFile(
                    'lampiran_file'
                )
            ) {
                $newAttachment =
                    $this->storeUploadedFile(
                        $request->file(
                            'lampiran_file'
                        )
                    );

                $data['lampiran_file'] =
                    $newAttachment;

            } elseif (
                $request->filled(
                    'captured_image'
                )
            ) {
                $newAttachment =
                    $this->storeBase64Image(
                        (string) $request->input(
                            'captured_image'
                        )
                    );

                $data['lampiran_file'] =
                    $newAttachment;

            } else {
                unset(
                    $data['lampiran_file']
                );
            }

            unset(
                $data['captured_image']
            );

            /*
            |--------------------------------------------------------------------------
            | UPDATE
            |--------------------------------------------------------------------------
            */

            $suratKeluar->update(
                $data
            );

            /*
            |--------------------------------------------------------------------------
            | LOG
            |--------------------------------------------------------------------------
            */

            $nomorSurat = trim(
                (string) (
                    $suratKeluar->nomor_surat ??
                    ''
                )
            );

            $perihalSurat = trim(
                (string) (
                    $suratKeluar->perihal ??
                    ''
                )
            );

            $actorName = trim(
                (string) (
                    Auth::user()?->name ??
                    'Sistem'
                )
            );

            if ($oldStatus !== $newStatus) {

                $this->logActivity(
                    'status',
                    'surat_keluar',
                    $actorName .
                    ' mengubah status surat keluar #' .
                    $suratKeluar->id .
                    ' | Nomor: ' .
                    ($nomorSurat !== '' ? $nomorSurat : '-') .
                    ' | Perihal: ' .
                    ($perihalSurat !== '' ? $perihalSurat : 'Tanpa perihal') .
                    ' | Status: ' .
                    ucfirst($oldStatus) .
                    ' → ' .
                    ucfirst($newStatus)
                );

            } else {

                $this->logActivity(
                    'update',
                    'surat_keluar',
                    $actorName .
                    ' memperbarui surat keluar #' .
                    $suratKeluar->id .
                    ' | Nomor: ' .
                    ($nomorSurat !== '' ? $nomorSurat : '-') .
                    ' | Perihal: ' .
                    ($perihalSurat !== '' ? $perihalSurat : 'Tanpa perihal') .
                    ' | Status: ' .
                    ucfirst($newStatus)
                );

            }

            DB::commit();

            /*
            |--------------------------------------------------------------------------
            | HAPUS FILE LAMA
            |--------------------------------------------------------------------------
            */

            if (
                $newAttachment &&
                $oldAttachment &&
                $newAttachment !==
                    $oldAttachment
            ) {
                $this->deleteAttachment(
                    $oldAttachment
                );
            }

            $this->cleanupPdfPreview();

            return redirect()
                ->route(
                    'surat-keluar.index'
                )
                ->with(
                    'success',
                    'Surat keluar berhasil diperbarui.'
                );

        } catch (Throwable $e) {
            DB::rollBack();

            if (
                $newAttachment &&
                $newAttachment !==
                    $oldAttachment
            ) {
                $this->deleteAttachment(
                    $newAttachment
                );
            }

            $this->cleanupPdfPreview();

            Log::error(
                'Gagal memperbarui surat keluar.',
                [
                    'surat_keluar_id' =>
                        $suratKeluar->id,

                    'message' =>
                        $e->getMessage(),

                    'user_id' =>
                        Auth::id(),

                    'disk' =>
                        $this->getStorageDisk(),

                    'trace' =>
                        $e->getTraceAsString(),
                ]
            );

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Gagal memperbarui surat keluar: ' .
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
        SuratKeluar $suratKeluar
    ) {
        $this->authorizeManageSurat();

        $id =
            $suratKeluar->id;

        $nomorSurat =
            trim(
                (string) (
                    $suratKeluar->nomor_surat ??
                    ''
                )
            );

        $perihalSurat =
            trim(
                (string) (
                    $suratKeluar->perihal ??
                    ''
                )
            );

        $actorName =
            trim(
                (string) (
                    Auth::user()?->name ??
                    'Sistem'
                )
            );

        $attachment =
            $suratKeluar->lampiran_file;

        try {
            DB::transaction(
                function () use (
                    $suratKeluar,
                    $id,
                    $actorName,
                    $nomorSurat,
                    $perihalSurat
                ): void {

                    $suratKeluar->delete();

                    $this->logActivity(
                        'delete',
                        'surat_keluar',
                        $actorName .
                        ' menghapus surat keluar #' .
                        $id .
                        ' | Nomor: ' .
                        ($nomorSurat !== '' ? $nomorSurat : '-') .
                        ' | Perihal: ' .
                        ($perihalSurat !== '' ? $perihalSurat : 'Tanpa perihal')
                    );
                }
            );

            if ($attachment) {
                $this->deleteAttachment(
                    $attachment
                );
            }

            return redirect()
                ->route(
                    'surat-keluar.index'
                )
                ->with(
                    'success',
                    'Surat keluar berhasil dipindahkan ke sampah.'
                );

        } catch (Throwable $e) {
            Log::error(
                'Gagal menghapus surat keluar.',
                [
                    'surat_keluar_id' =>
                        $id,

                    'message' =>
                        $e->getMessage(),

                    'user_id' =>
                        Auth::id(),
                ]
            );

            return back()
                ->with(
                    'error',
                    'Gagal menghapus surat keluar: ' .
                    $e->getMessage()
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PREVIEW LAMPIRAN
    |--------------------------------------------------------------------------
    */

    public function previewLampiran(
        SuratKeluar $suratKeluar
    ) {
        $this->ensureUserAuthenticated();

        $path =
            $suratKeluar->lampiran_file;

        if (!$path) {
            abort(
                404,
                'Lampiran tidak tersedia.'
            );
        }

        if (
            filter_var(
                $path,
                FILTER_VALIDATE_URL
            )
        ) {
            return redirect()->away(
                $path
            );
        }

        $disk =
            $this->storage();

        $mimeType =
            $this->getMimeTypeFromPath(
                $path
            );

        try {
            $stream =
                $disk->readStream(
                    $path
                );

            if ($stream === false) {
                throw new RuntimeException(
                    'File lampiran tidak dapat dibaca dari storage.'
                );
            }

            $headers = [
                'Content-Type' =>
                    $mimeType,

                'Content-Disposition' =>
                    'inline; filename="' .
                    basename($path) .
                    '"',

                'Cache-Control' =>
                    'private, max-age=300',

                'X-Content-Type-Options' =>
                    'nosniff',
            ];

            return response()->stream(
                function () use (
                    $stream
                ): void {

                    fpassthru(
                        $stream
                    );

                    if (
                        is_resource(
                            $stream
                        )
                    ) {
                        fclose(
                            $stream
                        );
                    }
                },
                200,
                $headers
            );

        } catch (Throwable $e) {
            Log::error(
                'Gagal preview lampiran surat keluar.',
                [
                    'path' =>
                        $path,

                    'message' =>
                        $e->getMessage(),

                    'disk' =>
                        $this->getStorageDisk(),

                    'surat_keluar_id' =>
                        $suratKeluar->id,
                ]
            );

            return back()
                ->with(
                    'error',
                    'Gagal membuka lampiran file: ' .
                    $e->getMessage()
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CETAK
    |--------------------------------------------------------------------------
    */

    public function cetak(
        SuratKeluar $suratKeluar
    ) {
        $this->ensureUserAuthenticated();

        $suratKeluar->load(
            [
                'kategori',
                'pembuat',
                'penandatangan',
            ]
        );

        return view(
            'surat_keluar.cetak',
            compact(
                'suratKeluar'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | LABEL
    |--------------------------------------------------------------------------
    */

    public function label(
        SuratKeluar $suratKeluar
    ) {
        $this->ensureUserAuthenticated();

        $suratKeluar->load(
            'kategori'
        );

        return view(
            'surat_keluar.label',
            compact(
                'suratKeluar'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ACTIVITY LOG
    |--------------------------------------------------------------------------
    */

    public function storeLog(
        Request $request,
        SuratKeluar $suratKeluar
    ) {
        $this->authorizeManageSurat();

        $this->logActivity(
            'log',
            'surat_keluar',
            (Auth::user()?->name ?? 'Sistem') .
            ' menambahkan catatan aktivitas untuk surat keluar #' .
            $suratKeluar->id .
            ' | Nomor: ' .
            (($suratKeluar->nomor_surat ?? '') !== ''
                ? $suratKeluar->nomor_surat
                : '-') .
            ' | Perihal: ' .
            (($suratKeluar->perihal ?? '') !== ''
                ? $suratKeluar->perihal
                : 'Tanpa perihal')
        );

        return back()
            ->with(
                'success',
                'Log surat berhasil disimpan.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | STORE UPLOADED FILE
    |--------------------------------------------------------------------------
    */

    private function storeUploadedFile(
        ?UploadedFile $file
    ): string {
        if (!$file) {
            throw new RuntimeException(
                'File lampiran tidak ditemukan.'
            );
        }

        if (!$file->isValid()) {
            throw new RuntimeException(
                $this->getUploadErrorMessage(
                    $file->getError()
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

        if ($extension === 'jpeg') {
            $extension = 'jpg';
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
            !is_readable($realPath)
        ) {
            throw new RuntimeException(
                'File upload tidak dapat dibaca oleh server.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | SIGNATURE
        |--------------------------------------------------------------------------
        */

        $handle =
            fopen(
                $realPath,
                'rb'
            );

        if ($handle === false) {
            throw new RuntimeException(
                'File upload tidak dapat dibuka oleh server.'
            );
        }

        $header =
            fread(
                $handle,
                16
            );

        fclose(
            $handle
        );

        if (
            $header === false ||
            $header === ''
        ) {
            throw new RuntimeException(
                'File upload kosong.'
            );
        }

        $isPng =
            str_starts_with(
                $header,
                "\x89PNG\r\n\x1a\n"
            );

        $isJpeg =
            str_starts_with(
                $header,
                "\xFF\xD8\xFF"
            );

        $isPdf =
            str_starts_with(
                $header,
                '%PDF'
            );

        /*
        |--------------------------------------------------------------------------
        | VALIDASI SIGNATURE
        |--------------------------------------------------------------------------
        */

        if (
            $extension === 'png' &&
            !$isPng
        ) {
            throw new RuntimeException(
                'File PNG tidak valid.'
            );
        }

        if (
            in_array(
                $extension,
                [
                    'jpg',
                    'jpeg',
                ],
                true
            ) &&
            !$isJpeg
        ) {
            throw new RuntimeException(
                'File JPG/JPEG tidak valid.'
            );
        }

        if (
            $extension === 'pdf' &&
            !$isPdf
        ) {
            throw new RuntimeException(
                'File PDF tidak valid.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | PDF
        |--------------------------------------------------------------------------
        */

        if ($isPdf) {
            return $this->storePdfUsingPreview(
                $file
            );
        }

        /*
        |--------------------------------------------------------------------------
        | IMAGE
        |--------------------------------------------------------------------------
        */

        if (
            !$isJpeg &&
            !$isPng
        ) {
            throw new RuntimeException(
                'Isi file tidak dikenali sebagai PDF, JPG, JPEG, atau PNG.'
            );
        }

        $imageInfo =
            @getimagesize(
                $realPath
            );

        if ($imageInfo === false) {
            throw new RuntimeException(
                'File gambar tidak valid.'
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
            $contents = file_get_contents($realPath);

            if ($contents === false || $contents === '') {
                throw new RuntimeException(
                    'Gagal membaca file gambar asli.'
                );
            }

            return $this->storeBinaryFile(
                $contents,
                $extension,
                $actualMime
            );
        }

        return $this->compressAndStoreImage(
            $file,
            $realPath
        );
    }

    /*
    |--------------------------------------------------------------------------
    | STORE PDF USING PREVIEW
    |--------------------------------------------------------------------------
    */

    private function storePdfUsingPreview(
        UploadedFile $file
    ): string {
        if (!$file->isValid()) {
            throw new RuntimeException(
                $this->getUploadErrorMessage(
                    $file->getError()
                )
            );
        }

        $inputPath =
            $file->getRealPath();

        if (
            !$inputPath ||
            !is_readable($inputPath)
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
            $originalContents = file_get_contents($inputPath);

            if ($originalContents === false || $originalContents === '') {
                throw new RuntimeException(
                    'Gagal membaca PDF asli.'
                );
            }

            session()->forget('surat_keluar_pdf_preview');

            return $this->storeBinaryFile(
                $originalContents,
                'pdf',
                'application/pdf'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | HASH FILE
        |--------------------------------------------------------------------------
        */

        $currentHash =
            hash_file(
                'sha256',
                $inputPath
            );

        if (!$currentHash) {
            throw new RuntimeException(
                'Hash PDF tidak dapat dibuat.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | SESSION PREVIEW
        |--------------------------------------------------------------------------
        */

        $preview =
            session(
                'surat_keluar_pdf_preview'
            );

        if (is_array($preview)) {
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

            /*
            |--------------------------------------------------------------------------
            | PREVIEW COMPRESSED
            |--------------------------------------------------------------------------
            */

            if (
                $sameUser &&
                $notExpired &&
                $sameFile &&
                !empty(
                    $preview['use_compressed']
                ) &&
                $previewPath !== '' &&
                is_file($previewPath) &&
                is_readable($previewPath)
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
                        'application/pdf'
                    );

                @unlink(
                    $previewPath
                );

                session()->forget(
                    'surat_keluar_pdf_preview'
                );

                return $uploadedPath;
            }

            /*
            |--------------------------------------------------------------------------
            | PREVIEW ASLI
            |--------------------------------------------------------------------------
            */

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
                    is_file($previewPath)
                ) {
                    @unlink(
                        $previewPath
                    );
                }

                session()->forget(
                    'surat_keluar_pdf_preview'
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
                    'application/pdf'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | PREVIEW INVALID
            |--------------------------------------------------------------------------
            */

            if (
                $previewPath !== '' &&
                is_file($previewPath)
            ) {
                @unlink(
                    $previewPath
                );
            }

            session()->forget(
                'surat_keluar_pdf_preview'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FALLBACK
        |--------------------------------------------------------------------------
        */

        return $this->storeCompressedPdf(
            $inputPath
        );
    }

    /*
    |--------------------------------------------------------------------------
    | STORE COMPRESSED PDF
    |--------------------------------------------------------------------------
    */

    private function storeCompressedPdf(
        string $inputPath
    ): string {
        if (
            !is_file($inputPath) ||
            !is_readable($inputPath)
        ) {
            throw new RuntimeException(
                'File PDF tidak dapat dibaca.'
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

        $result =
            $this->compressPdfToTemporaryFile(
                $inputPath
            );

        $temporaryPath =
            $result['path'];

        $compressedSize =
            $result['size'];

        try {
            /*
            |--------------------------------------------------------------------------
            | ASLI LEBIH KECIL
            |--------------------------------------------------------------------------
            */

            if (
                $compressedSize >=
                $originalSize
            ) {
                $contents =
                    file_get_contents(
                        $inputPath
                    );

                if (
                    $contents === false ||
                    $contents === ''
                ) {
                    throw new RuntimeException(
                        'Gagal membaca PDF asli.'
                    );
                }

                Log::info(
                    'PDF surat keluar tidak menjadi lebih kecil.',
                    [
                        'original_size' =>
                            $originalSize,

                        'compressed_size' =>
                            $compressedSize,

                        'saving_percent' =>
                            0,

                        'profile' =>
                            $result['profile'],
                    ]
                );

                return $this->storeBinaryFile(
                    $contents,
                    'pdf',
                    'application/pdf'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | BACA HASIL
            |--------------------------------------------------------------------------
            */

            $contents =
                file_get_contents(
                    $temporaryPath
                );

            if (
                $contents === false ||
                $contents === ''
            ) {
                throw new RuntimeException(
                    'Gagal membaca hasil compression PDF.'
                );
            }

            $finalSize =
                strlen(
                    $contents
                );

            if (
                $finalSize >
                self::MAX_FILE_SIZE
            ) {
                throw new RuntimeException(
                    'PDF hasil compression masih melebihi batas 20 MB.'
                );
            }

            Log::info(
                'PDF surat keluar berhasil dikompresi.',
                [
                    'original_size' =>
                        $originalSize,

                    'compressed_size' =>
                        $finalSize,

                    'saving_percent' =>
                        $this->calculateSavingPercent(
                            $originalSize,
                            $finalSize
                        ),

                    'profile' =>
                        $result['profile'],
                ]
            );

            return $this->storeBinaryFile(
                $contents,
                'pdf',
                'application/pdf'
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
    | GHOSTSCRIPT
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
                'Ghostscript tidak ditemukan atau tidak dapat dijalankan.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | TEST
        |--------------------------------------------------------------------------
        */

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

        if ($versionCode !== 0) {
            throw new RuntimeException(
                'Ghostscript gagal dijalankan.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | DIRECTORY
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | PROFILES
        |--------------------------------------------------------------------------
        */

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
                ' -dMonoImageDownsampleType=/Subsample' .
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

            $output =
                [];

            $exitCode =
                0;

            @exec(
                $command . ' 2>&1',
                $output,
                $exitCode
            );

            if (
                $exitCode !== 0 ||
                !is_file($outputPath)
            ) {
                Log::warning(
                    'Profile Ghostscript surat keluar gagal.',
                    [
                        'profile' =>
                            $profile['name'],

                        'exit_code' =>
                            $exitCode,

                        'output' =>
                            implode(
                                PHP_EOL,
                                $output
                            ),
                    ]
                );

                if (
                    is_file($outputPath)
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

            /*
            |--------------------------------------------------------------------------
            | VALIDATE PDF
            |--------------------------------------------------------------------------
            */

            $header =
                @file_get_contents(
                    $outputPath,
                    false,
                    null,
                    0,
                    5
                );

            if ($header !== '%PDF-') {
                @unlink(
                    $outputPath
                );

                Log::warning(
                    'Ghostscript menghasilkan file bukan PDF.',
                    [
                        'profile' =>
                            $profile['name'],
                    ]
                );

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | PILIH TERKECIL
            |--------------------------------------------------------------------------
            */

            if (
                $bestSize === null ||
                $size < $bestSize
            ) {
                if (
                    $bestOutput &&
                    is_file($bestOutput)
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
    | IMAGE
    |--------------------------------------------------------------------------
    */

    private function compressAndStoreImage(
        UploadedFile $file,
        string $realPath
    ): string {
        $result =
            $this->compressImageToTemporaryFile(
                $file
            );

        $temporaryPath =
            $result['path'];

        try {
            $contents =
                file_get_contents(
                    $temporaryPath
                );

            if (
                $contents === false ||
                $contents === ''
            ) {
                throw new RuntimeException(
                    'Hasil compression gambar tidak dapat dibaca.'
                );
            }

            if (
                strlen($contents) >
                self::MAX_FILE_SIZE
            ) {
                throw new RuntimeException(
                    'Gambar hasil compression masih melebihi batas 20 MB.'
                );
            }

            return $this->storeBinaryFile(
                $contents,
                'jpg',
                'image/jpeg'
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

    private function compressImageToTemporaryFile(
        UploadedFile $file
    ): array {
        if (
            !extension_loaded('gd')
        ) {
            throw new RuntimeException(
                'PHP GD belum aktif. Aktifkan ekstensi GD.'
            );
        }

        $realPath =
            $file->getRealPath();

        if (
            !$realPath ||
            !is_readable($realPath)
        ) {
            throw new RuntimeException(
                'File gambar tidak dapat dibaca.'
            );
        }

        $extension =
            strtolower(
                trim(
                    (string) $file
                        ->getClientOriginalExtension()
                )
            );

        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }

        $source =
            match ($extension) {
                'png' =>
                    @imagecreatefrompng(
                        $realPath
                    ),

                'jpg' =>
                    @imagecreatefromjpeg(
                        $realPath
                    ),

                default =>
                    false,
            };

        if ($source === false) {
            throw new RuntimeException(
                'Gambar tidak dapat diproses oleh GD.'
            );
        }

        $destination =
            null;

        try {
            $sourceWidth =
                imagesx(
                    $source
                );

            $sourceHeight =
                imagesy(
                    $source
                );

            if (
                $sourceWidth <= 0 ||
                $sourceHeight <= 0
            ) {
                throw new RuntimeException(
                    'Ukuran gambar tidak valid.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | MEMORY
            |--------------------------------------------------------------------------
            */

            $pixelCount =
                $sourceWidth *
                $sourceHeight;

            if (
                $pixelCount >
                50000000
            ) {
                throw new RuntimeException(
                    'Resolusi gambar terlalu besar.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | RESIZE
            |--------------------------------------------------------------------------
            */

            $scale =
                min(
                    self::MAX_IMAGE_WIDTH /
                        $sourceWidth,

                    self::MAX_IMAGE_HEIGHT /
                        $sourceHeight,

                    1
                );

            $targetWidth =
                max(
                    1,
                    (int) round(
                        $sourceWidth *
                        $scale
                    )
                );

            $targetHeight =
                max(
                    1,
                    (int) round(
                        $sourceHeight *
                        $scale
                    )
                );

            $destination =
                imagecreatetruecolor(
                    $targetWidth,
                    $targetHeight
                );

            if ($destination === false) {
                throw new RuntimeException(
                    'Canvas gambar gagal dibuat.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | WHITE BACKGROUND
            |--------------------------------------------------------------------------
            */

            $white =
                imagecolorallocate(
                    $destination,
                    255,
                    255,
                    255
                );

            imagefill(
                $destination,
                0,
                0,
                $white
            );

            /*
            |--------------------------------------------------------------------------
            | RESAMPLE
            |--------------------------------------------------------------------------
            */

            if (
                !imagecopyresampled(
                    $destination,
                    $source,
                    0,
                    0,
                    0,
                    0,
                    $targetWidth,
                    $targetHeight,
                    $sourceWidth,
                    $sourceHeight
                )
            ) {
                throw new RuntimeException(
                    'Gagal melakukan resize gambar.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | QUALITY
            |--------------------------------------------------------------------------
            */

            $qualities = [
                self::JPEG_QUALITY,
                76,
                70,
                64,
                58,
                52,
                46,
                40,
            ];

            $bestTemporaryPath =
                null;

            $bestSize =
                null;

            foreach (
                $qualities as $quality
            ) {
                $candidate =
                    tempnam(
                        sys_get_temp_dir(),
                        'e_arsip_sk_img_'
                    );

                if (!$candidate) {
                    continue;
                }

                $success =
                    imagejpeg(
                        $destination,
                        $candidate,
                        $quality
                    );

                if (!$success) {
                    @unlink(
                        $candidate
                    );

                    continue;
                }

                $size =
                    filesize(
                        $candidate
                    );

                if (
                    $size === false ||
                    $size <= 0
                ) {
                    @unlink(
                        $candidate
                    );

                    continue;
                }

                if (
                    $bestSize === null ||
                    $size < $bestSize
                ) {
                    if (
                        $bestTemporaryPath &&
                        is_file(
                            $bestTemporaryPath
                        )
                    ) {
                        @unlink(
                            $bestTemporaryPath
                        );
                    }

                    $bestTemporaryPath =
                        $candidate;

                    $bestSize =
                        $size;

                } else {
                    @unlink(
                        $candidate
                    );
                }

                if (
                    $size <=
                    self::MAX_COMPRESSED_IMAGE_SIZE
                ) {
                    break;
                }
            }

            if (
                !$bestTemporaryPath ||
                $bestSize === null
            ) {
                throw new RuntimeException(
                    'Compression gambar gagal.'
                );
            }

            if (
                $bestSize >
                self::MAX_FILE_SIZE
            ) {
                @unlink(
                    $bestTemporaryPath
                );

                throw new RuntimeException(
                    'Gambar hasil compression masih lebih besar dari 20 MB.'
                );
            }

            return [
                'path' =>
                    $bestTemporaryPath,

                'size' =>
                    $bestSize,
            ];

        } finally {
            if (
                $source instanceof \GdImage
            ) {
                imagedestroy(
                    $source
                );
            }

            if (
                $destination instanceof \GdImage
            ) {
                imagedestroy(
                    $destination
                );
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CAMERA BASE64
    |--------------------------------------------------------------------------
    */

    private function storeBase64Image(
        string $base64
    ): string {
        $base64 =
            trim(
                $base64
            );

        if ($base64 === '') {
            throw new RuntimeException(
                'Data scan kamera kosong.'
            );
        }

        if (
            !preg_match(
                '~^data:image/(png|jpeg|jpg);base64,~i',
                $base64
            )
        ) {
            throw new RuntimeException(
                'Format hasil scan kamera tidak valid.'
            );
        }

        $comma =
            strpos(
                $base64,
                ','
            );

        if ($comma === false) {
            throw new RuntimeException(
                'Data scan kamera tidak valid.'
            );
        }

        $encoded =
            substr(
                $base64,
                $comma + 1
            );

        $decoded =
            base64_decode(
                $encoded,
                true
            );

        if (
            $decoded === false ||
            $decoded === ''
        ) {
            throw new RuntimeException(
                'Data scan kamera tidak valid.'
            );
        }

        if (
            strlen($decoded) >
            self::MAX_FILE_SIZE
        ) {
            throw new RuntimeException(
                'Ukuran hasil scan kamera maksimal 20 MB.'
            );
        }

        $imageInfo =
            @getimagesizefromstring(
                $decoded
            );

        if ($imageInfo === false) {
            throw new RuntimeException(
                'Data scan bukan gambar yang valid.'
            );
        }

        $mime =
            strtolower(
                (string) (
                    $imageInfo['mime'] ??
                    ''
                )
            );

        if ($mime === 'image/jpg') {
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
                'Jenis gambar hasil scan tidak didukung.'
            );
        }

        // Gambar hasil scan di bawah 2 MB disimpan apa adanya tanpa kompresi.
        if (
            strlen($decoded) <
            self::COMPRESSION_THRESHOLD
        ) {
            $extension =
                $mime === 'image/png'
                    ? 'png'
                    : 'jpg';

            return $this->storeBinaryFile(
                $decoded,
                $extension,
                $mime
            );
        }

        $temporaryPath =
            tempnam(
                sys_get_temp_dir(),
                'e_arsip_scan_'
            );

        if (!$temporaryPath) {
            throw new RuntimeException(
                'File temporary scan gagal dibuat.'
            );
        }

        try {
            if (
                file_put_contents(
                    $temporaryPath,
                    $decoded
                ) === false
            ) {
                throw new RuntimeException(
                    'Gagal membuat file temporary scan.'
                );
            }

            $uploadedFile =
                new UploadedFile(
                    $temporaryPath,
                    'scan.jpg',
                    'image/jpeg',
                    null,
                    true
                );

            return $this->compressAndStoreImage(
                $uploadedFile,
                $temporaryPath
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
    | STORE BINARY
    |--------------------------------------------------------------------------
    */

    private function storeBinaryFile(
        string $contents,
        string $extension,
        string $mimeType
    ): string {
        if ($contents === '') {
            throw new RuntimeException(
                'Data file kosong.'
            );
        }

        $size =
            strlen(
                $contents
            );

        if (
            $size >
            self::MAX_FILE_SIZE
        ) {
            throw new RuntimeException(
                'Data file melebihi batas 20 MB.'
            );
        }

        $safeExtension =
            strtolower(
                trim(
                    $extension
                )
            );

        $fileName =
            'surat-keluar_' .
            now()->format(
                'Ymd_His'
            ) .
            '_' .
            Str::lower(
                Str::random(20)
            ) .
            '.' .
            $safeExtension;

        $path =
            'lampiran/surat_keluar/' .
            $fileName;

        try {
            $disk =
                $this->storage();

            $saved =
                $disk->put(
                    $path,
                    $contents,
                    [
                        'visibility' =>
                            'private',

                        'ContentType' =>
                            $mimeType,
                    ]
                );

            if (!$saved) {
                throw new RuntimeException(
                    'File gagal disimpan ke storage lokal.'
                );
            }

            return $path;

        } catch (Throwable $e) {
            Log::error(
                'Gagal menyimpan binary surat keluar.',
                [
                    'message' =>
                        $e->getMessage(),

                    'path' =>
                        $path,

                    'extension' =>
                        $safeExtension,

                    'mime' =>
                        $mimeType,

                    'size' =>
                        $size,

                    'disk' =>
                        $this->getStorageDisk(),
                ]
            );

            throw new RuntimeException(
                'Gagal menyimpan file ke Supabase: ' .
                $e->getMessage(),
                previous: $e
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE ATTACHMENT
    |--------------------------------------------------------------------------
    */

    private function deleteAttachment(
        ?string $path
    ): void {
        if (
            !$path ||
            trim($path) === ''
        ) {
            return;
        }

        if (
            filter_var(
                $path,
                FILTER_VALIDATE_URL
            )
        ) {
            return;
        }

        try {
            $disk =
                $this->storage();

            if (
                $disk->exists(
                    $path
                )
            ) {
                $disk->delete(
                    $path
                );
            }

        } catch (Throwable $e) {
            Log::warning(
                'Gagal menghapus lampiran surat keluar.',
                [
                    'path' =>
                        $path,

                    'message' =>
                        $e->getMessage(),

                    'disk' =>
                        $this->getStorageDisk(),
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
                'surat_keluar_pdf_preview'
            );

        if (
            is_array($preview) &&
            !empty($preview['path'])
        ) {
            $path =
                (string) $preview['path'];

            if (
                $path !== '' &&
                is_file($path)
            ) {
                @unlink($path);
            }
        }

        session()->forget(
            'surat_keluar_pdf_preview'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | STORAGE
    |--------------------------------------------------------------------------
    */

    private function getStorageDisk(): string
    {
        // Semua lampiran baru disimpan pada storage lokal persisten Laravel.
        return 'local';
    }

    private function storage(): FilesystemAdapter
    {
        return Storage::disk(
            $this->getStorageDisk()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | UPLOAD ERROR
    |--------------------------------------------------------------------------
    */

    private function getUploadErrorMessage(
        int $error
    ): string {
        return match ($error) {
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
                $error,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | MIME TYPE
    |--------------------------------------------------------------------------
    */

    private function getMimeTypeFromPath(
        string $path
    ): string {
        return match (
            strtolower(
                pathinfo(
                    $path,
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
    | FILE URL
    |--------------------------------------------------------------------------
    */

    private function getFileUrl(
        ?string $file,
        ?SuratKeluar $suratKeluar = null
    ): ?string {
        if (!$file) {
            return null;
        }

        // URL lama tetap dapat dibuka selama migrasi data berlangsung.
        if (filter_var($file, FILTER_VALIDATE_URL)) {
            return $file;
        }

        if (!$suratKeluar) {
            return null;
        }

        return route('surat-keluar.preview-lampiran', [
            'suratKeluar' => $suratKeluar->getRouteKey(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | DATE
    |--------------------------------------------------------------------------
    */

    private function isValidDate(
        ?string $date
    ): bool {
        if ($date === null) {
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
            count($parts) !== 3
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
    | FORMAT BYTES
    |--------------------------------------------------------------------------
    */

    private function formatBytes(
        int $bytes
    ): string {
        if ($bytes <= 0) {
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
                1024 *
                1024
            ),
            2,
            ',',
            '.'
        ) .
        ' MB';
    }

    /*
    |--------------------------------------------------------------------------
    | SAVING PERCENT
    |--------------------------------------------------------------------------
    */

    private function calculateSavingPercent(
        int $originalSize,
        int $compressedSize
    ): float {
        if (
            $originalSize <= 0 ||
            $compressedSize >= $originalSize
        ) {
            return 0;
        }

        return round(
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
        } catch (Throwable $e) {
            Log::warning(
                'Gagal mencatat Activity Log surat keluar.',
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
}