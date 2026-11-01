<?php
/**
 * File: app/controllers/ai_action.php
 * Fungsi: Controller Utama untuk Plugin AI Workspace di Greymist.
 *         Memuat modul-modul AI modular di sub-direktori app/controllers/ai/:
 *         - ai_memory.php           (Manajemen cache memori konteks & riwayat arsitektur)
 *         - ai_html_sanitizer.php    (Sanitasi HTML, class gm-object, semantic ID, & navbar cross-linker)
 *         - ai_local_operations.php  (Smart fallback presets & manipulasi DOM lokal / patch)
 *         - ai_prompt_parser.php     (Sistem prompt & parser output AI)
 *         - ai_providers.php         (Adapter cloud AI: Groq, Gemini, OpenRouter, Antigravity Bridge)
 */

require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/models/component_model.php';
require_once ROOT_PATH . '/models/frame_model.php';
require_once ROOT_PATH . '/models/project_model.php';
require_once ROOT_PATH . '/models/asset_model.php';

// Muat seluruh sub-modul AI
require_once __DIR__ . '/ai/ai_memory.php';
require_once __DIR__ . '/ai/ai_html_sanitizer.php';
require_once __DIR__ . '/ai/ai_local_operations.php';
require_once __DIR__ . '/ai/ai_prompt_parser.php';
require_once __DIR__ . '/ai/ai_providers.php';

@set_time_limit(300);
@ini_set('memory_limit', '1024M');

if (defined('AI_ACTION_LIBRARY_ONLY')) {
    return;
}

$user_id = current_user_id() ?? (isset($_GET['user_id']) ? (int)$_GET['user_id'] : null);
if (!$user_id) {
    json_response(['status' => 'ERROR', 'success' => false, 'msg' => 'Silakan login terlebih dahulu.'], 401);
}

$ai_action = $_GET['action'] ?? $_POST['action'] ?? $_GET['ai_action'] ?? $_POST['ai_action'] ?? 'ai_generate_component';
$raw_input = file_get_contents('php://input');
$data      = json_decode((string)$raw_input, true);
if (!is_array($data)) {
    $data = $_POST;
}

switch ($ai_action) {
    case 'noop':
        return;
    case 'ai_transcribe_audio':
        $groq_key = (string)($_SESSION['groq_api_key_override'] ?? env('GROQ_API_KEY', ''));
        if ($groq_key === '') {
            json_response([
                'status'  => 'ERROR',
                'success' => false,
                'message' => 'GROQ_API_KEY belum dikonfigurasi untuk transkripsi audio Whisper.',
            ], 400);
        }

        $audio_file_path = null;
        $temp_created = false;

        if (isset($_FILES['audio']) && is_uploaded_file($_FILES['audio']['tmp_name'])) {
            $audio_file_path = $_FILES['audio']['tmp_name'];
        } elseif (!empty($data['audio_base64'])) {
            $b64 = (string)$data['audio_base64'];
            if (str_contains($b64, ',')) {
                $b64 = explode(',', $b64, 2)[1];
            }
            $bin = base64_decode($b64);
            if ($bin !== false && $bin !== '') {
                $audio_file_path = tempnam(sys_get_temp_dir(), 'gm_voice_') . '.webm';
                file_put_contents($audio_file_path, $bin);
                $temp_created = true;
            }
        }

        if (!$audio_file_path || !file_exists($audio_file_path)) {
            json_response([
                'status'  => 'ERROR',
                'success' => false,
                'message' => 'File audio rekaman tidak diterima.',
            ], 400);
        }

        $cfile = new CURLFile($audio_file_path, 'audio/webm', 'audio.webm');
        $post_fields = [
            'file'            => $cfile,
            'model'           => 'whisper-large-v3-turbo',
            'response_format' => 'json',
            'language'        => 'id',
        ];

        $ch = curl_init('https://api.groq.com/openai/v1/audio/transcriptions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $groq_key,
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $resp = curl_exec($ch);
        $err  = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($temp_created && file_exists($audio_file_path)) {
            @unlink($audio_file_path);
        }

        if ($err !== '' || $code !== 200) {
            json_response([
                'status'  => 'ERROR',
                'success' => false,
                'message' => 'Transkripsi suara Whisper gagal: ' . ($err ?: $resp),
            ], 500);
        }

        $resp_data = json_decode((string)$resp, true);
        $transcript = trim((string)($resp_data['text'] ?? ''));

        json_response([
            'status'     => 'OK',
            'success'    => true,
            'transcript' => $transcript,
        ]);
        break;

    case 'ai_get_memory':
        $project_id = (int)($data['project_id'] ?? $_GET['project_id'] ?? 0);
        $snapshot   = isset($data['frames_snapshot']) && is_array($data['frames_snapshot']) ? $data['frames_snapshot'] : [];
        $memory     = get_project_ai_memory($pdo, $project_id, $snapshot);
        json_response([
            'status'    => 'OK',
            'success'   => true,
            'ai_memory' => $memory,
        ]);
        break;

    case 'ai_get_provider_settings':
        $current_provider = (string)($_SESSION['ai_provider_override'] ?? env('AI_PROVIDER', ''));
        if ($current_provider === '') {
            if (env('GROQ_API_KEY', '') !== '') {
                $current_provider = 'groq';
            } elseif (env('GEMINI_API_KEY', '') !== '') {
                $current_provider = 'gemini';
            } else {
                $current_provider = 'antigravity';
            }
        }

        $groq_key = (string)($_SESSION['groq_api_key_override'] ?? env('GROQ_API_KEY', ''));
        $openrouter_key = (string)($_SESSION['openrouter_api_key_override'] ?? env('OPENROUTER_API_KEY', ''));
        $gemini_key = (string)($_SESSION['gemini_api_key_override'] ?? env('GEMINI_API_KEY', ''));

        json_response([
            'status'               => 'OK',
            'success'              => true,
            'provider'             => $current_provider,
            'groq_model'           => (string)env('GROQ_MODEL', 'openai/gpt-oss-120b'),
            'openrouter_model'     => (string)env('OPENROUTER_MODEL', 'meta-llama/llama-3.3-70b-instruct:free'),
            'gemini_model'         => (string)env('GEMINI_MODEL', 'gemini-3.5-flash-lite'),
            'groq_has_key'         => $groq_key !== '',
            'groq_key_masked'      => mask_api_key($groq_key),
            'openrouter_has_key'   => $openrouter_key !== '',
            'openrouter_key_masked'=> mask_api_key($openrouter_key),
            'gemini_has_key'       => $gemini_key !== '',
            'gemini_key_masked'    => mask_api_key($gemini_key),
            'available_providers'  => [
                [
                    'id'            => 'groq',
                    'name'          => 'Groq Cloud (Gratis & Super Cepat 70B)',
                    'desc'          => 'Inference cloud super cepat (~300 t/s), 0% CPU laptop. Gratis di console.groq.com',
                    'default_model' => 'openai/gpt-oss-120b',
                    'needs_key'     => true,
                ],
                [
                    'id'            => 'antigravity',
                    'name'          => 'Antigravity Cloud Bridge',
                    'desc'          => 'Bawaan Antigravity IDE, tanpa butuh API key pribadi & 0% beban laptop',
                    'default_model' => 'antigravity-cloud',
                    'needs_key'     => false,
                ],
                [
                    'id'            => 'openrouter',
                    'name'          => 'OpenRouter Cloud (Free Tier)',
                    'desc'          => 'Pilihan model cloud open-source gratis di openrouter.ai',
                    'default_model' => 'meta-llama/llama-3.3-70b-instruct:free',
                    'needs_key'     => true,
                ],
                [
                    'id'            => 'gemini',
                    'name'          => 'Google Gemini (API Key Pribadi)',
                    'desc'          => 'Menggunakan Google AI Studio API Key pribadi',
                    'default_model' => 'gemini-2.5-flash',
                    'needs_key'     => true,
                ],
                [
                    'id'            => 'smart_preset',
                    'name'          => 'Smart Fallback Lokal (Offline)',
                    'desc'          => '100% offline rule-based tanpa koneksi internet & 0% beban laptop',
                    'default_model' => 'local-rule-engine',
                    'needs_key'     => false,
                ],
            ]
        ]);
        break;

    case 'ai_save_provider_settings':
        $new_provider = trim((string)($data['provider'] ?? 'groq'));
        $updates = ['AI_PROVIDER' => $new_provider];

        if (isset($data['groq_api_key']) && trim((string)$data['groq_api_key']) !== '') {
            $updates['GROQ_API_KEY'] = trim((string)$data['groq_api_key']);
            $_SESSION['groq_api_key_override'] = $updates['GROQ_API_KEY'];
        }
        if (isset($data['groq_model']) && trim((string)$data['groq_model']) !== '') {
            $updates['GROQ_MODEL'] = trim((string)$data['groq_model']);
        }

        if (isset($data['openrouter_api_key']) && trim((string)$data['openrouter_api_key']) !== '') {
            $updates['OPENROUTER_API_KEY'] = trim((string)$data['openrouter_api_key']);
            $_SESSION['openrouter_api_key_override'] = $updates['OPENROUTER_API_KEY'];
        }
        if (isset($data['openrouter_model']) && trim((string)$data['openrouter_model']) !== '') {
            $updates['OPENROUTER_MODEL'] = trim((string)$data['openrouter_model']);
        }

        if (isset($data['gemini_api_key']) && trim((string)$data['gemini_api_key']) !== '') {
            $updates['GEMINI_API_KEY'] = trim((string)$data['gemini_api_key']);
            $_SESSION['gemini_api_key_override'] = $updates['GEMINI_API_KEY'];
        }
        if (isset($data['gemini_model']) && trim((string)$data['gemini_model']) !== '') {
            $updates['GEMINI_MODEL'] = trim((string)$data['gemini_model']);
        }

        $_SESSION['ai_provider_override'] = $new_provider;
        update_env_file($updates);

        json_response([
            'status'   => 'OK',
            'success'  => true,
            'provider' => $new_provider,
            'msg'      => "Pengaturan provider AI ({$new_provider}) berhasil disimpan ke .env.",
        ]);
        break;

    case 'ai_test_provider_connection':
        $test_provider = trim((string)($data['provider'] ?? 'groq'));
        $test_key = trim((string)($data['api_key'] ?? ''));
        $test_model = trim((string)($data['model'] ?? ''));

        if ($test_key === '') {
            if ($test_provider === 'groq') {
                $test_key = (string)($_SESSION['groq_api_key_override'] ?? env('GROQ_API_KEY', ''));
            } elseif ($test_provider === 'openrouter') {
                $test_key = (string)($_SESSION['openrouter_api_key_override'] ?? env('OPENROUTER_API_KEY', ''));
            } elseif ($test_provider === 'gemini') {
                $test_key = (string)($_SESSION['gemini_api_key_override'] ?? env('GEMINI_API_KEY', ''));
            }
        }

        $res = test_ai_provider_connection($test_provider, $test_key, $test_model);
        json_response([
            'status'     => $res['success'] ? 'OK' : 'ERROR',
            'success'    => $res['success'],
            'latency_ms' => $res['latency_ms'],
            'msg'        => $res['msg'],
        ]);
        break;

    case 'ai_save_api_key':
        $new_key = trim((string)($data['api_key'] ?? ''));
        update_env_file(['GEMINI_API_KEY' => $new_key]);
        $_SESSION['gemini_api_key_override'] = $new_key;

        json_response([
            'status'  => 'OK',
            'success' => true,
            'msg'     => 'GEMINI_API_KEY berhasil disimpan ke .env.',
            'has_key' => $new_key !== '',
        ]);
        break;

    case 'ai_get_user_assets':
        $db_assets = get_user_bg_images($pdo, (int)$user_id);
        $project_id = (int)($data['project_id'] ?? $_GET['project_id'] ?? 0);
        $formatted = [];
        $seen = [];
        foreach ($db_assets as $dba) {
            $path_dir = rtrim(str_replace('\\', '/', (string)$dba['path']), '/');
            if (str_ends_with($path_dir, '/' . $dba['image'])) {
                $full_url = $path_dir;
            } else {
                $full_url = $path_dir . '/' . $dba['image'];
            }
            $full_url = ltrim($full_url, '/');
            if (isset($seen[$full_url])) continue;
            $seen[$full_url] = true;
            $formatted[] = [
                'id'         => (int)$dba['image_id'],
                'name'       => trim((string)($dba['image_name'] ?: pathinfo($dba['image'], PATHINFO_FILENAME))),
                'url'        => $full_url,
                'file'       => $dba['image'],
                'created_at' => $dba['created_at'],
            ];
        }

        if ($project_id > 0) {
            $proj = get_project_by_id($pdo, $project_id);
            if (!empty($proj['user_id']) && (int)$proj['user_id'] !== (int)$user_id) {
                $p_assets = get_user_bg_images($pdo, (int)$proj['user_id']);
                foreach ($p_assets as $pa) {
                    $path_dir = rtrim(str_replace('\\', '/', (string)$pa['path']), '/');
                    if (str_ends_with($path_dir, '/' . $pa['image'])) {
                        $full_url = $path_dir;
                    } else {
                        $full_url = $path_dir . '/' . $pa['image'];
                    }
                    $full_url = ltrim($full_url, '/');
                    if (isset($seen[$full_url])) continue;
                    $seen[$full_url] = true;
                    $formatted[] = [
                        'id'         => (int)$pa['image_id'],
                        'name'       => trim((string)($pa['image_name'] ?: pathinfo($pa['image'], PATHINFO_FILENAME))),
                        'url'        => $full_url,
                        'file'       => $pa['image'],
                        'created_at' => $pa['created_at'],
                    ];
                }
            }
        }

        // Jika daftar aset pengguna masih kosong, sertakan aset yang tersedia di sistem
        if (empty($formatted)) {
            $all_sys = $pdo->query("SELECT image_id, image, image_name, path, created_at FROM gm_bg_images ORDER BY created_at DESC LIMIT 30")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($all_sys as $sa) {
                $path_dir = rtrim(str_replace('\\', '/', (string)$sa['path']), '/');
                if (str_ends_with($path_dir, '/' . $sa['image'])) {
                    $full_url = $path_dir;
                } else {
                    $full_url = $path_dir . '/' . $sa['image'];
                }
                $full_url = ltrim($full_url, '/');
                if (isset($seen[$full_url])) continue;
                $seen[$full_url] = true;
                $formatted[] = [
                    'id'         => (int)$sa['image_id'],
                    'name'       => trim((string)($sa['image_name'] ?: pathinfo($sa['image'], PATHINFO_FILENAME))),
                    'url'        => $full_url,
                    'file'       => $sa['image'],
                    'created_at' => $sa['created_at'],
                ];
            }
        }

        json_response([
            'status'  => 'OK',
            'success' => true,
            'assets'  => $formatted,
            'count'   => count($formatted),
        ]);
        break;

    case 'ai_generate_component':
    default:
        $prompt                = trim((string)($data['prompt'] ?? ''));
        $save_to_library       = !empty($data['save_to_library']);
        $component_name        = trim((string)($data['component_name'] ?? ''));
        $inline_api_key        = trim((string)($data['api_key'] ?? ''));
        $requested_model       = trim((string)($data['model'] ?? ''));
        $requested_provider    = trim((string)($data['provider'] ?? ''));
        $current_frame_html    = (string)($data['current_frame_html'] ?? '');
        $selected_object_html  = (string)($data['selected_object_html'] ?? '');
        $target_mode           = trim((string)($data['target_mode'] ?? 'core'));
        $project_id            = (int)($data['project_id'] ?? $_GET['project_id'] ?? 0);
        $frames_snapshot       = isset($data['frames_snapshot']) && is_array($data['frames_snapshot']) ? $data['frames_snapshot'] : [];
        $selected_object_attrs = isset($data['selected_object_attrs']) && is_array($data['selected_object_attrs']) ? $data['selected_object_attrs'] : [];
        $active_frame_id       = (int)($data['active_frame_id'] ?? 0);

        // Ekstrak & hubungkan Aset Gambar dari Menu Assets pengguna
        $user_assets = [];
        $seen_asset_urls = [];
        if (!empty($data['user_assets']) && is_array($data['user_assets'])) {
            foreach ($data['user_assets'] as $ua) {
                if (is_array($ua) && !empty($ua['url'])) {
                    $u_url = ltrim((string)$ua['url'], '/');
                    if (!isset($seen_asset_urls[$u_url])) {
                        $seen_asset_urls[$u_url] = true;
                        $user_assets[] = [
                            'id'   => (int)($ua['id'] ?? 0),
                            'name' => trim((string)($ua['name'] ?? pathinfo($u_url, PATHINFO_FILENAME))),
                            'url'  => $u_url,
                            'file' => (string)($ua['file'] ?? basename($u_url)),
                        ];
                    }
                }
            }
        }

        // CATATAN KEAMANAN & PRIVASI ASET:
        // AI TIDAK BOLEH otomatis mengakses seluruh aset gambar di database.
        // AI HANYA BOLEH menggunakan aset jika pengguna secara manual memilih/men-drag-and-drop aset ke modal/input prompt AI ($data['user_assets']).
        // Jika $data['user_assets'] kosong, $user_assets tetap berupa array kosong [] sehingga AI tidak sembarangan menyisipkan gambar lokal.

        // Ekstrak & hubungkan Chart Pengguna dari Menu Assets
        $user_charts = [];
        $seen_chart_ids = [];
        if (!empty($data['user_charts']) && is_array($data['user_charts'])) {
            foreach ($data['user_charts'] as $uc) {
                if (is_array($uc)) {
                    $cid = (int)($uc['chart_id'] ?? 0);
                    if ($cid > 0) $seen_chart_ids[$cid] = true;
                    $user_charts[] = [
                        'chart_id'    => $cid,
                        'chart_name'  => trim((string)($uc['chart_name'] ?? 'Chart')),
                        'chart_type'  => (string)($uc['chart_type'] ?? 'bar'),
                        'library_cdn' => (string)($uc['library_cdn'] ?? 'chartjs'),
                        'cdn_url'     => (string)($uc['cdn_url'] ?? ''),
                        'chart_code'  => (string)($uc['chart_code'] ?? ''),
                    ];
                }
            }
        }

        // Muat seluruh chart milik pengguna dari database gm_user_charts
        if ($pdo) {
            $db_charts = get_user_charts($pdo, (int)$user_id);
            foreach ($db_charts as $dbc) {
                $cid = (int)$dbc['chart_id'];
                if (!isset($seen_chart_ids[$cid])) {
                    $seen_chart_ids[$cid] = true;
                    $user_charts[] = [
                        'chart_id'    => $cid,
                        'chart_name'  => trim((string)$dbc['chart_name']),
                        'chart_type'  => (string)$dbc['chart_type'],
                        'library_cdn' => (string)$dbc['library_cdn'],
                        'cdn_url'     => (string)($dbc['cdn_url'] ?? ''),
                        'chart_code'  => (string)$dbc['chart_code'],
                    ];
                }
            }
        }

        // Ekstrak foto/gambar referensi jika diunggah pengguna (Multimodal Vision / Multi-Foto)
        $raw_ref_images = [];
        if (isset($data['reference_images']) && is_array($data['reference_images'])) {
            $raw_ref_images = $data['reference_images'];
        } elseif (isset($data['reference_image']) && is_array($data['reference_image'])) {
            if (isset($data['reference_image']['data'])) {
                $raw_ref_images = [$data['reference_image']];
            } else {
                $raw_ref_images = $data['reference_image'];
            }
        }

        $clean_ref_images = [];
        foreach ($raw_ref_images as $raw_img) {
            if (!is_array($raw_img) || empty($raw_img['data'])) {
                continue;
            }
            $ref_data = trim((string)$raw_img['data']);
            $ref_mime = trim((string)($raw_img['mime_type'] ?? 'image/jpeg'));
            $ref_name = trim((string)($raw_img['name'] ?? 'referensi.png'));
            if (str_contains($ref_data, ',')) {
                $parts = explode(',', $ref_data, 2);
                if (preg_match('#data:(image/[^;]+);base64#i', $parts[0], $mime_m)) {
                    $ref_mime = $mime_m[1];
                }
                $ref_data = $parts[1];
            }
            if ($ref_data !== '') {
                $clean_ref_images[] = [
                    'data'      => $ref_data,
                    'mime_type' => $ref_mime,
                    'name'      => $ref_name,
                ];
            }
        }
        $clean_ref_image = !empty($clean_ref_images) ? $clean_ref_images[0] : null;

        if ($prompt === '' && !empty($clean_ref_images)) {
            $img_cnt = count($clean_ref_images);
            if ($img_cnt === 1) {
                $prompt = "Buat tampilan antarmuka (UI) website modern yang meniru tata letak (layout), hierarki visual, dan komponen dari foto referensi ini menggunakan komponen .gm-object Greymist.";
            } else {
                $prompt = "Bangun tampilan antarmuka (UI) website modern yang mengombinasikan tata letak, hierarki komponen, dan elemen visual dari {$img_cnt} foto referensi terlampir menggunakan komponen .gm-object Greymist secara utuh dan harmonis.";
            }
        }

        if ($prompt === '') {
            json_response(['status' => 'ERROR', 'success' => false, 'msg' => 'Prompt atau instruksi AI wajib diisi.']);
        }

        // Cek apakah prompt menargetkan seluruh halaman (project-wide)
        $is_project_wide = (bool)preg_match('/\b(semua\s+(?:halaman|pages?|frame)|seluruh\s+(?:halaman|pages?|frame)|tiap\s+(?:halaman|pages?|frame)|all\s+pages?|konsisten(?:kan)?)\b/i', $prompt);

        // Jika instruksi bersifat project-wide, batalkan penargetan edit_selected lokal yang tidak sengaja terbawa
        if ($is_project_wide && $target_mode === 'edit_selected') {
            $target_mode = 'core';
            $selected_object_html = '';
        }

        // Muat daftar frame proyek dari database (ringan & memastikan AI & engine lokal sadar konteks multi-page)
        $all_db_frames = ($project_id > 0) ? get_project_frames($pdo, $project_id) : [];
        if (empty($all_db_frames) && !empty($frames_snapshot)) {
            $all_db_frames = $frames_snapshot;
        }

        // Komponen yang dapat digunakan ulang (reusable) dari snapshot & frame saat ini
        $reusable_comps = extract_reusable_components(!empty($frames_snapshot) ? $frames_snapshot : $all_db_frames, $current_frame_html);

        // Baca status opsi Memori Proyek (Default: FALSE / MODE KILAT)
        $read_memory = !empty($data['read_memory']);

        if ($read_memory) {
            $ai_memory           = get_project_ai_memory($pdo, $project_id, $frames_snapshot);
            $memory_context_text = format_ai_memory_for_prompt($ai_memory);
        } else {
            $ai_memory           = [];
            $memory_context_text = '';
        }

        // Resolusi Provider AI Aktif
        $active_provider = $requested_provider !== ''
            ? $requested_provider
            : ((string)($_SESSION['ai_provider_override'] ?? env('AI_PROVIDER', '')));

        if ($active_provider === '') {
            if (env('GROQ_API_KEY', '') !== '') {
                $active_provider = 'groq';
            } elseif (env('GEMINI_API_KEY', '') !== '') {
                $active_provider = 'gemini';
            } else {
                $active_provider = 'antigravity';
            }
        }

        $source_mode        = 'Smart Fallback Lokal (Offline)';
        $api_warning        = '';
        $operation          = ($target_mode === 'edit_selected') ? 'replace_selected' : 'append';
        $summary_msg        = '';
        $frames_specs       = [];
        $frames_updates     = [];
        $patch_data         = null;
        $breakpoints_to_add = [];
        $ai_res             = null;

        if ($active_provider === 'groq') {
            $groq_key   = $inline_api_key !== '' ? $inline_api_key : ((string)($_SESSION['groq_api_key_override'] ?? env('GROQ_API_KEY', '')));
            $groq_model = $requested_model !== '' ? $requested_model : (string)env('GROQ_MODEL', 'openai/gpt-oss-120b');
            $gemini_key = (string)($_SESSION['gemini_api_key_override'] ?? env('GEMINI_API_KEY', ''));
            if (!empty($clean_ref_images) && $gemini_key !== '') {
                // Groq fokus pada model teks LPU. Untuk referensi foto/gambar, otomatis alihkan ke Gemini Multimodal Vision!
                $ai_res = call_gemini_interactions_api(
                    $gemini_key,
                    $prompt,
                    'gemini-3.5-flash-lite',
                    $current_frame_html,
                    $selected_object_html,
                    $target_mode,
                    $memory_context_text,
                    $selected_object_attrs,
                    $reusable_comps,
                    $all_db_frames,
                    $clean_ref_images,
                    $read_memory,
                    $user_assets,
                    $user_charts
                );
                $img_cnt = count($clean_ref_images);
                $source_mode = 'Google Gemini Vision (Auto-routed untuk ' . ($img_cnt > 1 ? "{$img_cnt} Foto" : "Foto") . ')';
            } elseif ($groq_key !== '') {
                $ai_res = call_openai_compatible_api(
                    'https://api.groq.com/openai/v1/chat/completions',
                    $groq_key,
                    $groq_model,
                    $prompt,
                    'Groq Cloud',
                    $current_frame_html,
                    $selected_object_html,
                    $target_mode,
                    $memory_context_text,
                    $selected_object_attrs,
                    $reusable_comps,
                    $all_db_frames,
                    $clean_ref_images,
                    $read_memory,
                    $user_assets,
                    $user_charts
                );
                $source_mode = 'Groq Cloud (' . ($ai_res['used_model'] ?? $groq_model) . ')';
            } else {
                $api_warning = 'GROQ_API_KEY belum diisi. Menggunakan Smart Fallback Lokal.';
            }
        } elseif ($active_provider === 'openrouter') {
            $or_key   = $inline_api_key !== '' ? $inline_api_key : ((string)($_SESSION['openrouter_api_key_override'] ?? env('OPENROUTER_API_KEY', '')));
            $or_model = $requested_model !== '' ? $requested_model : (string)env('OPENROUTER_MODEL', 'meta-llama/llama-3.3-70b-instruct:free');
            if ($or_key !== '') {
                $ai_res = call_openai_compatible_api(
                    'https://openrouter.ai/api/v1/chat/completions',
                    $or_key,
                    $or_model,
                    $prompt,
                    'OpenRouter Cloud',
                    $current_frame_html,
                    $selected_object_html,
                    $target_mode,
                    $memory_context_text,
                    $selected_object_attrs,
                    $reusable_comps,
                    $all_db_frames,
                    $clean_ref_images,
                    $read_memory,
                    $user_assets,
                    $user_charts
                );
                $source_mode = 'OpenRouter Cloud (' . ($ai_res['used_model'] ?? $or_model) . ')';
            } else {
                $api_warning = 'OPENROUTER_API_KEY belum diisi. Menggunakan Smart Fallback Lokal.';
            }
        } elseif ($active_provider === 'antigravity') {
            $ai_res = call_antigravity_bridge_api(
                $prompt,
                $current_frame_html,
                $selected_object_html,
                $target_mode,
                $memory_context_text,
                $selected_object_attrs,
                $reusable_comps,
                $all_db_frames,
                $clean_ref_images,
                $frames_snapshot,
                $pdo,
                $project_id,
                $read_memory,
                $user_assets,
                $user_charts
            );
            $source_mode = 'Antigravity Cloud Bridge';
        } elseif ($active_provider === 'gemini') {
            $gemini_key   = $inline_api_key !== '' ? $inline_api_key : ((string)($_SESSION['gemini_api_key_override'] ?? env('GEMINI_API_KEY', '')));
            $gemini_model = $requested_model !== '' ? $requested_model : (string)env('GEMINI_MODEL', 'gemini-3.5-flash-lite');
            if ($gemini_key !== '') {
                $ai_res = call_gemini_interactions_api(
                    $gemini_key,
                    $prompt,
                    $gemini_model,
                    $current_frame_html,
                    $selected_object_html,
                    $target_mode,
                    $memory_context_text,
                    $selected_object_attrs,
                    $reusable_comps,
                    $all_db_frames,
                    $clean_ref_images,
                    $read_memory,
                    $user_assets,
                    $user_charts
                );
                $source_mode = 'Google Gemini (' . ($ai_res['used_model'] ?? $gemini_model) . ')';
            } else {
                $api_warning = 'GEMINI_API_KEY belum diisi. Menggunakan Smart Fallback Lokal.';
            }
        }

        if ($ai_res !== null && !empty($ai_res['success'])) {
            $generated_html     = $ai_res['html'] ?? '';
            $operation          = $ai_res['operation'] ?? $operation;
            $summary_msg        = $ai_res['summary'] ?? '';
            $frames_specs       = $ai_res['frames_specs'] ?? [];
            $frames_updates     = $ai_res['frames_updates'] ?? [];
            $patch_data         = $ai_res['patch'] ?? null;
            $breakpoints_to_add = $ai_res['breakpoints_to_add'] ?? [];
        } else {
            if ($ai_res !== null && !empty($ai_res['error'])) {
                $api_warning = $ai_res['error'];
            }
            $local_cmd = process_local_frame_command($prompt, $current_frame_html, $selected_object_html, $target_mode, $frames_snapshot, $pdo, $project_id, $user_assets, $user_charts);
            if ($local_cmd !== null) {
                $operation          = $local_cmd['operation'];
                $generated_html     = $local_cmd['html'] ?? '';
                $summary_msg        = $local_cmd['summary'] ?? '';
                $frames_specs       = $local_cmd['frames_specs'] ?? [];
                $frames_updates     = $local_cmd['frames_updates'] ?? [];
                $patch_data         = $local_cmd['patch'] ?? null;
                $breakpoints_to_add = $local_cmd['breakpoints_to_add'] ?? [];
            } else {
                $is_removal_or_tweak = (bool)preg_match('/\b(hapus|hilangkan|buang|delete|remove|tanpa|tiadakan|singkirkan|ganti|ubah|edit|perbaiki|pindah)\b/i', $prompt);
                if ($is_removal_or_tweak) {
                    $operation      = 'explain';
                    $generated_html = $current_frame_html;
                    $summary_msg    = 'Greymist AI Content Guard: Tidak ditemukan elemen yang cocok untuk diubah/dihapus sesuai instruksi. Seluruh konten dan desain Anda tetap dipertahankan 100% utuh tanpa ditimpa.';
                } else {
                    $generated_html = ensure_gm_object_class(generate_smart_preset_component($prompt, $user_assets, $user_charts));
                }
            }
        }

        // Bersihkan seluruh output dari karakter emoji / emotikon
        $generated_html = clean_emojis_from_html($generated_html);
        if (!empty($frames_specs)) {
            foreach ($frames_specs as &$fs) {
                $fs['html'] = clean_emojis_from_html((string)($fs['html'] ?? ''));
            }
            unset($fs);
        }

        // Jika pengguna memilih target_mode = 'new_frame' tetapi AI mengembalikan operasi append biasa,
        // otomatis bungkus menjadi pembuatan frame baru.
        if ($target_mode === 'new_frame' && $operation === 'append' && trim($generated_html) !== '' && empty($frames_specs)) {
            $new_page_name  = $component_name !== '' && $component_name !== 'AI Component'
                ? $component_name
                : ('Page: ' . mb_substr($prompt, 0, 22));
            $operation      = 'create_frames';
            $frames_specs[] = [
                'frame_name' => $new_page_name,
                'html'       => $generated_html,
            ];
        }

        // Penanganan Operasi 0: GANTI NAMA PROYEK (RENAME PROJECT) di Database gm_projects & Header Workspace
        $renamed_project = $gemini_res['renamed_project'] ?? ($local_cmd['renamed_project'] ?? null);
        if ($renamed_project && !empty($renamed_project['new_name'])) {
            $new_proj_name = trim($renamed_project['new_name']);
            if ($project_id > 0) {
                $stmt = $pdo->prepare("UPDATE gm_projects SET project_name = ? WHERE project_id = ?");
                $stmt->execute([$new_proj_name, $project_id]);
                $renamed_project['project_id'] = $project_id;
            }
            $ai_memory['project_name'] = $new_proj_name;
        }

        // Penanganan Operasi 1: Simpan frame baru dan lakukan SINKRONISASI RESIPROKAL ke seluruh halaman
        $created_frames          = [];
        $updated_existing_frames = [];
        $renamed_frame           = null;

        if ($operation === 'create_frames' && !empty($frames_specs)) {
            // Tahap 1: Cek apakah ada frame kosong di proyek saat ini (termasuk Main Frame awal yang masih kosong)
            $available_empty_frames = [];
            if ($project_id > 0) {
                $current_db_frames = get_project_frames($pdo, $project_id);
                $active_empty = null;
                $other_empty  = [];
                foreach ($current_db_frames as $cdf) {
                    $c_html = trim((string)($cdf['core_content'] ?? ''));
                    if ($c_html === '' || trim(strip_tags($c_html)) === '' || !str_contains($c_html, 'gm-object')) {
                        if ($active_frame_id > 0 && (int)$cdf['frame_id'] === $active_frame_id) {
                            $active_empty = $cdf;
                        } else {
                            $other_empty[] = $cdf;
                        }
                    }
                }
                if ($active_empty !== null) {
                    $available_empty_frames[] = $active_empty;
                }
                foreach ($other_empty as $oe) {
                    $available_empty_frames[] = $oe;
                }
            }

            // Tahap 2: Simpan seluruh frame untuk mendapatkan ID numerik resminya
            $frame_name_to_id = [];
            $new_ids          = [];
            foreach ($frames_specs as $idx => $spec) {
                $f_name = trim((string)($spec['frame_name'] ?? ('Page ' . ($idx + 1))));
                $f_html = (string)($spec['html'] ?? '');

                if ($project_id > 0) {
                    // Gunakan frame kosong yang sudah ada jika tersedia (misal Main Frame awal diubah menjadi Home / Beranda)
                    if (!empty($available_empty_frames)) {
                        $reused_empty = array_shift($available_empty_frames);
                        $fid = (int)$reused_empty['frame_id'];
                        $stmtUpEmpty = $pdo->prepare("UPDATE gm_frames SET frame_name = ?, core_content = ? WHERE frame_id = ?");
                        $stmtUpEmpty->execute([$f_name, $f_html, $fid]);
                    } else {
                        $db_frame = add_project_frame($pdo, $project_id, $f_name, $f_html);
                        $fid = (int)$db_frame['frame_id'];
                    }
                } else {
                    $fid = time() + $idx;
                }

                $frame_name_to_id[$f_name] = $fid;
                $new_ids[] = $fid;
                $created_frames[] = [
                    'frame_id'     => $fid,
                    'frame_name'   => $f_name,
                    'core_content' => $f_html,
                ];
            }

            // Tahap 2: Buat peta seluruh frame proyek (existing + baru)
            $all_frames_map = [];
            if ($project_id > 0) {
                $fresh_frames = get_project_frames($pdo, $project_id);
                foreach ($fresh_frames as $ff) {
                    $all_frames_map[$ff['frame_name']] = (int)$ff['frame_id'];
                }
            } else {
                foreach ($frame_name_to_id as $name => $fid) {
                    $all_frames_map[$name] = $fid;
                }
            }

            // Tahap 3: Sinkronisasi silang pada seluruh frame baru yang baru dibuat
            if (!empty($all_frames_map) && $project_id > 0) {
                foreach ($created_frames as &$cf) {
                    $synced_html = cross_link_navbar_in_html($cf['core_content'], $all_frames_map, $cf['frame_id'], $cf['frame_name']);
                    if ($synced_html !== $cf['core_content']) {
                        $cf['core_content'] = $synced_html;
                        $stmtUp = $pdo->prepare('UPDATE gm_frames SET core_content = ? WHERE frame_id = ?');
                        $stmtUp->execute([$synced_html, $cf['frame_id']]);
                    }
                }
                unset($cf);

                // Tahap 4: SINKRONISASI RESIPROKAL ke frame-frame lama (misal Home / Beranda)
                // agar navbar pada halaman lama juga otomatis memuat tautan menuju halaman-halaman baru ini!
                $existing_frames_in_db = get_project_frames($pdo, $project_id);
                foreach ($existing_frames_in_db as $ef) {
                    $efid = (int)$ef['frame_id'];
                    if (in_array($efid, $new_ids, true)) {
                        continue; // Lewati yang baru dibuat karena sudah disinkronkan di atas
                    }

                    $old_html = (string)($ef['core_content'] ?? '');
                    $synced_old = cross_link_navbar_in_html($old_html, $all_frames_map, $efid, (string)$ef['frame_name']);
                    if ($synced_old !== $old_html) {
                        $stmtUp = $pdo->prepare('UPDATE gm_frames SET core_content = ? WHERE frame_id = ?');
                        $stmtUp->execute([$synced_old, $efid]);
                        $updated_existing_frames[] = [
                            'frame_id'     => $efid,
                            'frame_name'   => (string)$ef['frame_name'],
                            'core_content' => $synced_old,
                        ];
                    }
                }
            }
        }

        // Penanganan Operasi 2: BATCH UPDATE FRAMES (Intervensi gaya pada seluruh halaman proyek)
        $saved_batch_updates = [];
        if ($operation === 'batch_update_frames' && !empty($frames_updates) && $project_id > 0) {
            $fresh_frames = get_project_frames($pdo, $project_id);
            foreach ($frames_updates as $fu) {
                $target_ident = trim((string)($fu['frame_identifier'] ?? ''));
                $new_f_html   = (string)($fu['html'] ?? '');
                $matched_fid  = 0;
                $matched_name = '';

                foreach ($fresh_frames as $ff) {
                    if ((string)$ff['frame_id'] === $target_ident || strtolower((string)$ff['frame_name']) === strtolower($target_ident)) {
                        $matched_fid  = (int)$ff['frame_id'];
                        $matched_name = (string)$ff['frame_name'];
                        break;
                    }
                }

                if ($matched_fid > 0 && $new_f_html !== '') {
                    $stmtUp = $pdo->prepare('UPDATE gm_frames SET core_content = ? WHERE frame_id = ?');
                    $stmtUp->execute([$new_f_html, $matched_fid]);
                    $saved_batch_updates[] = [
                        'frame_id'   => $matched_fid,
                        'frame_name' => $matched_name,
                        'html'       => $new_f_html,
                    ];
                }
            }
        }

                // Jika operasi adalah append pada frame yang masih kosong dan bernama 'Main Frame' / 'Untitled Frame',
        // otomatis rename frame tersebut agar namanya representatif (misal Home / nama komponen).
        if ($operation === 'append' && trim($current_frame_html) === '' && $project_id > 0) {
            $cur_active_id = $active_frame_id;
            if ($cur_active_id <= 0 && !empty($all_db_frames)) {
                $cur_active_id = (int)$all_db_frames[0]['frame_id'];
            }
            if ($cur_active_id > 0) {
                foreach ($all_db_frames as $adf) {
                    if ((int)$adf['frame_id'] === $cur_active_id) {
                        $cur_name = trim((string)$adf['frame_name']);
                        if (in_array(strtolower($cur_name), ['main frame', 'untitled frame', 'frame 1', 'page 1'], true)) {
                            $new_name = 'Home';
                            if ($component_name !== '' && $component_name !== 'AI Component') {
                                $new_name = $component_name;
                            } elseif (preg_match('/(?:halaman|page|frame|buat|landing\s*page)\s+([a-zA-Z0-9\s\-]{3,25})/i', $prompt, $pm)) {
                                $cand = ucwords(trim($pm[1]));
                                if ($cand !== '') $new_name = $cand;
                            }
                            $stmtRen = $pdo->prepare("UPDATE gm_frames SET frame_name = ? WHERE frame_id = ?");
                            $stmtRen->execute([$new_name, $cur_active_id]);
                            $renamed_frame = [
                                'frame_id' => $cur_active_id,
                                'new_name' => $new_name,
                            ];
                        }
                        break;
                    }
                }
            }
        }

        $saved_component = null;
        if ($save_to_library && $operation === 'append' && trim($generated_html) !== '') {
            $final_name = $component_name !== '' ? $component_name : ('AI: ' . mb_substr($prompt, 0, 32));
            $comp_id    = create_user_component($pdo, (int)$user_id, $final_name, $generated_html, 'AI Generated');
            $saved_component = [
                'component_id'   => $comp_id,
                'user_id'        => (int)$user_id,
                'component_name' => $final_name,
                'html_content'   => $generated_html,
                'category'       => 'AI Generated',
            ];
        }

        $default_msg = match ($operation) {
            'explain'             => $summary_msg !== '' ? $summary_msg : 'Isi frame berhasil dianalisis.',
            'rename_project'      => $summary_msg !== '' ? $summary_msg : ('Nama proyek berhasil diubah menjadi "' . ($renamed_project['new_name'] ?? '') . '".'),
            'patch_attributes'    => $summary_msg !== '' ? $summary_msg : ('Atribut elemen berhasil diperbarui (' . $source_mode . ').'),
            'replace_selected'    => 'Objek aktif berhasil diperbarui (' . $source_mode . ').',
            'replace_frame'       => $summary_msg !== '' ? $summary_msg : 'Isi frame berhasil diperbarui (' . $source_mode . ').',
            'create_frames'       => count($created_frames) . ' Frame Halaman baru berhasil dibuat & saling terhubung (' . $source_mode . ').',
            'batch_update_frames' => count($saved_batch_updates) . ' Halaman proyek berhasil diperbarui serentak secara konsisten (' . $source_mode . ').',
            'add_breakpoints'     => 'Breakpoint responsif berhasil ditambahkan ke toolbar.',
            default               => 'Komponen berhasil ditambahkan (' . $source_mode . ').',
        };

        // Perbarui & simpan cache memori AI proyek ke database gm_projects
        if ($project_id > 0) {
            $updated_memory = get_project_ai_memory($pdo, $project_id, []);
            $updated_memory['history'][] = [
                'time'           => date('H:i:s'),
                'prompt'         => mb_substr($prompt, 0, 140),
                'operation'      => $operation,
                'action_summary' => $default_msg,
            ];
            if ($operation === 'create_frames' && !empty($created_frames)) {
                $page_names = array_map(fn($cf) => $cf['frame_name'], $created_frames);
                $updated_memory['system_summary'] = 'Sistem memiliki halaman: ' . implode(', ', $page_names) . ' (instruksi: ' . mb_substr($prompt, 0, 100) . ')';
            } elseif ($operation === 'batch_update_frames') {
                $updated_memory['system_summary'] = 'Gaya seluruh halaman diselaraskan: ' . mb_substr($prompt, 0, 100);
            } elseif (empty($updated_memory['system_summary'])) {
                $updated_memory['system_summary'] = 'Proyek dibangun dengan instruksi: ' . mb_substr($prompt, 0, 100);
            }
            save_project_ai_memory($pdo, $project_id, $updated_memory);
            $ai_memory = $updated_memory;
        }

        json_response([
            'status'                  => 'OK',
            'success'                 => true,
            'operation'               => $operation,
            'patch'                   => $patch_data,
            'summary'                 => $summary_msg,
            'msg'                     => $default_msg,
            'source_mode'             => $source_mode,
            'api_warning'             => $api_warning,
            'html'                    => $generated_html,
            'created_frames'          => $created_frames,
            'updated_existing_frames' => $updated_existing_frames,
            'renamed_frame'           => $renamed_frame,
            'renamed_project'         => $renamed_project,
            'frames_updates'          => $saved_batch_updates,
            'breakpoints_to_add'      => $breakpoints_to_add,
            'component'               => $saved_component,
            'ai_memory'               => $ai_memory,
            'used_assets_count'       => count($user_assets),
        ]);
        break;
}
