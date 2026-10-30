<?php
/**
 * File: helpers/upload_helper.php
 * Fungsi: Validasi ekstensi, ukuran, tipe MIME, serta penyimpanan file upload secara aman.
 */

if (!function_exists('validate_and_upload_file')) {
    /**
     * Memvalidasi dan memindahkan file yang diunggah ke dalam sub-folder uploads/.
     *
     * @param array  $file         Elemen dari $_FILES['nama_input']
     * @param string $category_dir Sub-folder di dalam uploads/ (misal: 'foto_profil', 'background', 'assets')
     * @param array  $allowed_exts Daftar ekstensi yang diizinkan (huruf kecil)
     * @param int    $max_size_mb  Batas ukuran maksimal dalam Megabyte
     * @param string $prefix       Awalan nama file unik
     * @return array ['success' => bool, 'path' => string, 'filename' => string, 'original_name' => string, 'error' => string]
     */
    function validate_and_upload_file(
        array $file,
        string $category_dir,
        array $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        int $max_size_mb = 5,
        string $prefix = 'file_'
    ): array {
        if (!isset($file['error']) || is_array($file['error'])) {
            return ['success' => false, 'error' => 'Parameter file unggahan tidak valid.'];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Terjadi kesalahan saat mengunggah file (Kode: ' . (int)$file['error'] . ').'];
        }

        $max_bytes = $max_size_mb * 1024 * 1024;
        if ($file['size'] > $max_bytes) {
            return ['success' => false, 'error' => "Ukuran file melebihi batas maksimal {$max_size_mb}MB."];
        }

        $original_name = basename((string)$file['name']);
        $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed_exts, true)) {
            return [
                'success' => false,
                'error'   => 'Format file tidak didukung. Gunakan: ' . strtoupper(implode(', ', $allowed_exts)) . '.'
            ];
        }

        // Validasi MIME type aktual menggunakan finfo untuk mencegah penyamaran script
        $allowed_mimes = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            'svg'  => 'image/svg+xml',
            'pdf'  => 'application/pdf',
        ];

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detected_mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $expected_mimes = [];
            foreach ($allowed_exts as $allowed_ext) {
                if (isset($allowed_mimes[$allowed_ext])) {
                    $expected_mimes[] = $allowed_mimes[$allowed_ext];
                }
            }
            if (!empty($expected_mimes) && !in_array($detected_mime, $expected_mimes, true)) {
                return ['success' => false, 'error' => 'Konten file tidak sesuai dengan format gambar yang diizinkan.'];
            }
        }

        $clean_category = trim(str_replace(['..', '\\'], ['', '/'], $category_dir), '/');
        $target_dir = ROOT_PATH . '/uploads/' . $clean_category;

        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        // Hasilkan nama file acak yang aman
        $unique_name = $prefix . uniqid('', true) . '.' . $ext;
        $unique_name = str_replace('.', '_', substr($unique_name, 0, -(strlen($ext) + 1))) . '.' . $ext;
        $target_path = $target_dir . '/' . $unique_name;
        $relative_path = 'uploads/' . $clean_category . '/' . $unique_name;

        if (!move_uploaded_file($file['tmp_name'], $target_path)) {
            return ['success' => false, 'error' => 'Gagal menyimpan file ke direktori server.'];
        }

        return [
            'success'       => true,
            'path'          => $relative_path,
            'filename'      => $unique_name,
            'original_name' => pathinfo($original_name, PATHINFO_FILENAME),
            'error'         => '',
        ];
    }
}

