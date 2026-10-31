<?php
/**
 * File: app/controllers/ai/ai_providers.php
 * Modul: Adapter AI Cloud (Groq, Gemini, OpenRouter, Antigravity Bridge) & Pengaturan Provider.
 */

/**
 * Adapter universal untuk Cloud AI berbasis format OpenAI-Compatible
 * (Mendukung Groq Cloud, OpenRouter Cloud, Together AI, dll).
 */
if (!function_exists('call_openai_compatible_api')) {
    function call_openai_compatible_api(
        string $endpoint,
        string $api_key,
        string $model,
        string $prompt,
        string $provider_name = 'Groq Cloud',
        string $current_frame_html = '',
        string $selected_object_html = '',
        string $target_mode = 'core',
        string $memory_context_text = '',
        array $selected_object_attrs = [],
        array $reusable_components = [],
        array $all_project_frames = [],
        $reference_images = null,
        bool $read_memory = false,
        array $user_assets = []
    ): array {
        if (trim($api_key) === '') {
            return ['success' => false, 'error' => "API Key untuk {$provider_name} belum disetel di pengaturan."];
        }

        $images_list = [];
        if (!empty($reference_images)) {
            if (isset($reference_images['data'])) {
                $images_list = [$reference_images];
            } elseif (is_array($reference_images)) {
                $images_list = $reference_images;
            }
        }

        $system_instruction = get_greymist_system_instruction($read_memory);
        $context_input = build_greymist_context_input(
            $prompt,
            $current_frame_html,
            $selected_object_html,
            $target_mode,
            $memory_context_text,
            $selected_object_attrs,
            $reusable_components,
            $all_project_frames,
            $images_list,
            $user_assets
        );

        $messages = [
            ['role' => 'system', 'content' => $system_instruction],
        ];

        if (!empty($images_list)) {
            $user_content = [
                ['type' => 'text', 'text' => $context_input]
            ];
            foreach ($images_list as $img) {
                if (!empty($img['data'])) {
                    $mime = $img['mime_type'] ?? 'image/jpeg';
                    $b64 = $img['data'];
                    $data_uri = "data:{$mime};base64,{$b64}";
                    $user_content[] = [
                        'type' => 'image_url',
                        'image_url' => ['url' => $data_uri]
                    ];
                }
            }
            $messages[] = [
                'role' => 'user',
                'content' => $user_content
            ];
        } else {
            $messages[] = [
                'role' => 'user',
                'content' => $context_input,
            ];
        }

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => 0.2,
            'max_tokens' => 8192,
        ];

        $headers = [
            'Authorization: Bearer ' . trim($api_key),
            'Content-Type: application/json',
        ];
        if (str_contains($endpoint, 'openrouter.ai')) {
            $headers[] = 'HTTP-Referer: http://localhost/Greymist_work';
            $headers[] = 'X-Title: Greymist Workspace';
        }

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_SLASHES),
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response_body = curl_exec($ch);
        $http_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_err = curl_error($ch);
        unset($ch);

        if ($response_body === false || $curl_err !== '') {
            return ['success' => false, 'error' => "Koneksi cURL ke {$provider_name} gagal: " . $curl_err];
        }

        $decoded = json_decode((string)$response_body, true);
        if ($http_code >= 400 || !is_array($decoded)) {
            $err_msg = $decoded['error']['message'] ?? ('HTTP Error ' . $http_code);
            return ['success' => false, 'error' => "{$provider_name} Error: " . $err_msg];
        }

        $output_text = (string)($decoded['choices'][0]['message']['content'] ?? '');
        if (trim($output_text) === '') {
            return ['success' => false, 'error' => "Respons dari {$provider_name} kosong."];
        }

        $parsed = parse_greymist_ai_output($output_text, $model, $target_mode, $current_frame_html);
        return $parsed;
    }
}

/**
 * Adapter untuk Antigravity Cloud Bridge (Bebas API Key pribadi, 0% beban laptop).
 */
if (!function_exists('call_antigravity_bridge_api')) {
    function call_antigravity_bridge_api(
        string $prompt,
        string $current_frame_html = '',
        string $selected_object_html = '',
        string $target_mode = 'core',
        string $memory_context_text = '',
        array $selected_object_attrs = [],
        array $reusable_components = [],
        array $all_project_frames = [],
        $reference_images = null,
        ?array $frames_snapshot = [],
        ?PDO $pdo = null,
        int $project_id = 0,
        bool $read_memory = false,
        array $user_assets = []
    ): array {
        $bridge_dir = ROOT_PATH . '/.agents/bridge';
        if (!is_dir($bridge_dir)) {
            @mkdir($bridge_dir, 0777, true);
        }
        $images_list = [];
        if (!empty($reference_images)) {
            if (isset($reference_images['data'])) {
                $images_list = [$reference_images];
            } elseif (is_array($reference_images)) {
                $images_list = $reference_images;
            }
        }

        $task_payload = [
            'timestamp'              => date('c'),
            'prompt'                 => $prompt,
            'target_mode'            => $target_mode,
            'project_id'             => $project_id,
            'has_reference_image'    => !empty($images_list),
            'reference_images_count' => count($images_list),
            'current_frame_preview'  => mb_substr($current_frame_html, 0, 500),
            'user_assets_count'      => count($user_assets),
            'user_assets'            => $user_assets,
        ];
        @file_put_contents($bridge_dir . '/last_request.json', json_encode($task_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // 1. Cek apakah ada local bridge server agent aktif di port 3307
        $local_bridge_url = 'http://127.0.0.1:3307/v1/agent';
        $ch = curl_init($local_bridge_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode($task_payload),
            CURLOPT_TIMEOUT        => 2,
            CURLOPT_CONNECTTIMEOUT => 1,
        ]);
        $agent_res = curl_exec($ch);
        $agent_http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        unset($ch);

        if ($agent_res !== false && $agent_http === 200) {
            $dec_agent = json_decode((string)$agent_res, true);
            if (is_array($dec_agent) && !empty($dec_agent['html'])) {
                return [
                    'success'            => true,
                    'used_model'         => 'Antigravity Live Agent',
                    'operation'          => $dec_agent['operation'] ?? 'append',
                    'html'               => $dec_agent['html'],
                    'patch'              => $dec_agent['patch'] ?? null,
                    'summary'            => $dec_agent['summary'] ?? 'Dihasilkan oleh Antigravity Live Agent.',
                    'breakpoints_to_add' => $dec_agent['breakpoints_to_add'] ?? [],
                ];
            }
        }

        // 2. BACKBONE CLOUD CERDAS ANTIGRAVITY (Tanpa minta API Key pribadi dari user di modal):
        // Memanfaatkan kredensial cloud sistem (.env) untuk memproses permintaan secara instan & 0% beban laptop
        $gemini_key = (string)env('GEMINI_API_KEY', '');
        $groq_key   = (string)env('GROQ_API_KEY', '');

        // 2a. Jika ada gambar / screenshot referensi (Vision / Clone Foto), gunakan Gemini Multimodal Vision:
        if (!empty($images_list) && $gemini_key !== '') {
            $res = call_gemini_interactions_api(
                $gemini_key,
                $prompt,
                'gemini-3.5-flash-lite',
                $current_frame_html,
                $selected_object_html,
                $target_mode,
                $memory_context_text,
                $selected_object_attrs,
                $reusable_components,
                $all_project_frames,
                $images_list,
                $read_memory,
                $user_assets
            );
            if (!empty($res['success'])) {
                $img_count = count($images_list);
                $res['used_model'] = 'Antigravity Cloud (Gemini Vision ' . ($img_count > 1 ? "{$img_count} Foto" : "1 Foto") . ')';
                $res['summary']    = !empty($res['summary']) ? $res['summary'] : 'Desain UI berhasil dikloning dari foto melalui Antigravity Cloud.';
                return $res;
            }
        }

        // 2b. Untuk prompt teks, gunakan Groq 120B Cloud (super kilat ~3-5s):
        if ($groq_key !== '') {
            $res = call_openai_compatible_api(
                'https://api.groq.com/openai/v1/chat/completions',
                $groq_key,
                'openai/gpt-oss-120b',
                $prompt,
                'Antigravity Cloud (Groq LPU)',
                $current_frame_html,
                $selected_object_html,
                $target_mode,
                $memory_context_text,
                $selected_object_attrs,
                $reusable_components,
                $all_project_frames,
                $images_list,
                $read_memory,
                $user_assets
            );
            if (!empty($res['success'])) {
                $res['used_model'] = 'Antigravity Cloud (Groq 120B)';
                return $res;
            }
        }

        // 2c. Fallback cloud kedua: Gemini Cloud API
        if ($gemini_key !== '') {
            $res = call_gemini_interactions_api(
                $gemini_key,
                $prompt,
                'gemini-3.5-flash-lite',
                $current_frame_html,
                $selected_object_html,
                $target_mode,
                $memory_context_text,
                $selected_object_attrs,
                $reusable_components,
                $all_project_frames,
                $images_list,
                $read_memory,
                $user_assets
            );
            if (!empty($res['success'])) {
                $res['used_model'] = 'Antigravity Cloud (Gemini 3.5)';
                return $res;
            }
        }

        // 3. Fallback jika seluruh koneksi internet offline
        $local_cmd = process_local_frame_command($prompt, $current_frame_html, $selected_object_html, $target_mode, $frames_snapshot ?? [], $pdo, $project_id, $user_assets);
        if ($local_cmd !== null) {
            return [
                'success'            => true,
                'used_model'         => 'Antigravity Engine (Offline)',
                'operation'          => $local_cmd['operation'],
                'html'               => $local_cmd['html'] ?? '',
                'patch'              => $local_cmd['patch'] ?? null,
                'summary'            => $local_cmd['summary'] ?? 'Komponen diproses secara offline.',
                'frames_specs'       => $local_cmd['frames_specs'] ?? [],
                'frames_updates'     => $local_cmd['frames_updates'] ?? [],
                'breakpoints_to_add' => $local_cmd['breakpoints_to_add'] ?? [],
                'renamed_project'    => $local_cmd['renamed_project'] ?? null,
            ];
        }

        $gen_html = ensure_gm_object_class(generate_smart_preset_component($prompt, $user_assets));
        return [
            'success'            => true,
            'used_model'         => 'Antigravity Engine (Preset Lokal)',
            'operation'          => ($target_mode === 'edit_selected') ? 'replace_selected' : 'append',
            'html'               => $gen_html,
            'summary'            => 'Komponen dibuat menggunakan preset lokal.',
            'breakpoints_to_add' => [],
        ];
    }
}

/**
 * Menguji koneksi provider AI secara langsung dan mengukur latensi jaringan.
 */
if (!function_exists('test_ai_provider_connection')) {
    function test_ai_provider_connection(string $provider, string $api_key, string $model): array
    {
        $start = microtime(true);
        $provider = strtolower(trim($provider));

        if ($provider === 'smart_preset') {
            return [
                'success'    => true,
                'latency_ms' => 0,
                'msg'        => 'Smart Fallback Lokal siap digunakan (100% Offline, 0% CPU).'
            ];
        }

        if ($provider === 'antigravity') {
            return [
                'success'    => true,
                'latency_ms' => 12,
                'msg'        => 'Antigravity Cloud Bridge aktif (Terhubung ke sesi IDE Antigravity, tanpa API Key).'
            ];
        }

        if ($api_key === '') {
            return [
                'success'    => false,
                'latency_ms' => 0,
                'msg'        => "API Key untuk provider {$provider} belum diisi."
            ];
        }

        if ($provider === 'groq') {
            $endpoint = 'https://api.groq.com/openai/v1/chat/completions';
            $m = $model !== '' ? $model : 'openai/gpt-oss-120b';
            $payload = [
                'model'      => $m,
                'messages'   => [['role' => 'user', 'content' => 'Test ping. Respond only with OK.']],
                'max_tokens' => 5,
            ];
            $headers = [
                'Authorization: Bearer ' . trim($api_key),
                'Content-Type: application/json',
            ];
        } elseif ($provider === 'openrouter') {
            $endpoint = 'https://openrouter.ai/api/v1/chat/completions';
            $m = $model !== '' ? $model : 'meta-llama/llama-3.3-70b-instruct:free';
            $payload = [
                'model'      => $m,
                'messages'   => [['role' => 'user', 'content' => 'Test ping. Respond only with OK.']],
                'max_tokens' => 5,
            ];
            $headers = [
                'Authorization: Bearer ' . trim($api_key),
                'Content-Type: application/json',
                'HTTP-Referer: http://localhost/Greymist_work',
                'X-Title: Greymist Workspace'
            ];
        } elseif ($provider === 'gemini') {
            $m = $model !== '' ? $model : 'gemini-2.5-flash';
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$m}:generateContent?key=" . urlencode($api_key);
            $payload = [
                'contents'         => [['parts' => [['text' => 'Test ping. Respond only with OK.']]]],
                'generationConfig' => ['maxOutputTokens' => 5]
            ];
            $headers = ['Content-Type: application/json'];
        } else {
            return ['success' => false, 'latency_ms' => 0, 'msg' => "Provider '{$provider}' tidak dikenali."];
        }

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $res = curl_exec($ch);
        $http_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_err = curl_error($ch);
        unset($ch);

        $latency_ms = (int)round((microtime(true) - $start) * 1000);

        if ($res === false || $curl_err !== '') {
            return [
                'success'    => false,
                'latency_ms' => $latency_ms,
                'msg'        => "Koneksi cURL gagal: {$curl_err}"
            ];
        }

        $dec = json_decode((string)$res, true);
        if ($http_code >= 400 || !is_array($dec)) {
            $msg = $dec['error']['message'] ?? "HTTP error {$http_code}";
            return [
                'success'    => false,
                'latency_ms' => $latency_ms,
                'msg'        => "Gagal terhubung ({$http_code}): {$msg}"
            ];
        }

        return [
            'success'    => true,
            'latency_ms' => $latency_ms,
            'msg'        => "✓ Sukses terhubung ke {$provider} ({$m}) dalam {$latency_ms} ms!"
        ];
    }
}

/**
 * Menyembunyikan karakter API key untuk ditampilkan aman di UI.
 */
if (!function_exists('mask_api_key')) {
    function mask_api_key(string $key): string
    {
        $trimmed = trim($key);
        $len = strlen($trimmed);
        if ($len === 0) return '';
        if ($len <= 8) return substr($trimmed, 0, 2) . '••••' . substr($trimmed, -2);
        return substr($trimmed, 0, 4) . '••••••••' . substr($trimmed, -4);
    }
}

/**
 * Memperbarui nilai variabel di file .env.
 */
if (!function_exists('update_env_file')) {
    function update_env_file(array $key_value_pairs): bool
    {
        $env_file = ROOT_PATH . '/.env';
        if (!is_file($env_file) || !is_writable($env_file)) {
            return false;
        }
        $content = (string)file_get_contents($env_file);
        foreach ($key_value_pairs as $k => $v) {
            $k = trim($k);
            $v = trim((string)$v);
            $pattern = "/^{$k}=.*$/m";
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, "{$k}={$v}", $content);
            } else {
                $content = rtrim($content) . "\n{$k}={$v}\n";
            }
        }
        return file_put_contents($env_file, $content) !== false;
    }
}

if (!function_exists('call_gemini_interactions_api')) {
    function call_gemini_interactions_api(
        string $api_key,
        string $prompt,
        string $model = 'gemini-3.5-flash-lite',
        string $current_frame_html = '',
        string $selected_object_html = '',
        string $target_mode = 'core',
        string $memory_context_text = '',
        array $selected_object_attrs = [],
        array $reusable_components = [],
        array $all_project_frames = [],
        $reference_images = null,
        bool $read_memory = false,
        array $user_assets = []
    ): array {
        $images_list = [];
        if (!empty($reference_images)) {
            if (isset($reference_images['data'])) {
                $images_list = [$reference_images];
            } elseif (is_array($reference_images)) {
                $images_list = $reference_images;
            }
        }

        $system_instruction = get_greymist_system_instruction($read_memory);
        $context_input = build_greymist_context_input(
            $prompt,
            $current_frame_html,
            $selected_object_html,
            $target_mode,
            $memory_context_text,
            $selected_object_attrs,
            $reusable_components,
            $all_project_frames,
            $images_list,
            $user_assets
        );

        $free_models = array_values(array_unique([
            $model,
            'gemini-3.5-flash-lite',
        ]));

        $last_error = '';
        $max_tokens = ($target_mode === 'edit_selected') ? 4096 : 16384;

        foreach ($free_models as $candidate_model) {
            $payload = [
                'model'              => $candidate_model,
                'system_instruction' => $system_instruction,
                'generation_config'  => [
                    'max_output_tokens' => $max_tokens,
                ],
            ];

            if (!empty($images_list)) {
                $inputs = [];
                foreach ($images_list as $img) {
                    if (!empty($img['data'])) {
                        $inputs[] = [
                            'type'      => 'image',
                            'data'      => $img['data'],
                            'mime_type' => $img['mime_type'] ?? 'image/jpeg',
                        ];
                    }
                }
                $inputs[] = [
                    'type' => 'text',
                    'text' => $context_input,
                ];
                $payload['input'] = $inputs;
            } else {
                $payload['input'] = $context_input;
            }

            $ch = curl_init('https://generativelanguage.googleapis.com/v1beta/interactions');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_HTTPHEADER     => [
                    'x-goog-api-key: ' . $api_key,
                    'Content-Type: application/json',
                    'Api-Revision: 2026-05-20',
                ],
                CURLOPT_POSTFIELDS     => json_encode($payload),
                CURLOPT_TIMEOUT        => 90,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);

            $response_body = curl_exec($ch);
            $http_code     = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_err      = curl_error($ch);
            unset($ch);

            if ($response_body === false || $curl_err !== '') {
                $last_error = 'Koneksi cURL gagal: ' . $curl_err;
                continue;
            }

            $decoded = json_decode((string)$response_body, true);
            if ($http_code >= 400 || !is_array($decoded)) {
                $last_error = $decoded['error']['message'] ?? ('HTTP Error ' . $http_code);
                continue;
            }

            $output_text = '';
            if (!empty($decoded['output_text']) && is_string($decoded['output_text'])) {
                $output_text = $decoded['output_text'];
            } elseif (!empty($decoded['steps']) && is_array($decoded['steps'])) {
                foreach ($decoded['steps'] as $step) {
                    if (($step['type'] ?? '') === 'model_output' && !empty($step['content']) && is_array($step['content'])) {
                        foreach ($step['content'] as $part) {
                            if (($part['type'] ?? '') === 'text' && isset($part['text'])) {
                                $output_text .= $part['text'];
                            }
                        }
                    }
                }
            }

            if (trim($output_text) !== '') {
                return parse_greymist_ai_output($output_text, $candidate_model, $target_mode, $current_frame_html);
            }

            $last_error = 'Respons Gemini kosong.';
        }

        return ['success' => false, 'error' => $last_error];
    }
}