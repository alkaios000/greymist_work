<?php
$baseDir = "c:/laragon/www/Greymist_framework/Greymist (version 1.1 - refactoring_2)";
$ignoreDirs = ['fontawesome', 'font-awesome', 'sontawesome', 'greymist_css_var', 'img', 'shortable', 'sortable', 'v_env', 'db', 'backups', '.vscode', '.git', 'vendor', 'node_modules'];

$stats = [
    'total_files' => 0,
    'total_lines' => 0,
    'total_blank' => 0,
    'total_comments' => 0,
    'total_code' => 0,
    'by_extension' => []
];

function isIgnored($path) {
    global $ignoreDirs, $baseDir;
    $rel = str_replace(str_replace('\\', '/', $baseDir) . '/', '', str_replace('\\', '/', $path));
    $parts = explode('/', $rel);
    foreach ($parts as $part) {
        if (in_array(strtolower($part), $ignoreDirs)) {
            return true;
        }
    }
    return false;
}

$iter = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

$validExts = ['php', 'html', 'js', 'css', 'json', 'py', 'txt', 'md', 'sql'];

foreach ($iter as $file) {
    if ($file->isDir()) continue;
    if (isIgnored($file->getPathname())) continue;
    
    $ext = strtolower($file->getExtension());
    if (!in_array($ext, $validExts)) continue;
    
    $stats['total_files']++;
    $content = file_get_contents($file->getPathname());
    // remove carriage returns for simpler splitting
    $content = str_replace("\r", "", $content);
    $lines = explode("\n", $content);
    
    $total = count($lines);
    $blank = 0;
    $comment = 0;
    $code = 0;
    
    $inBlockComment = false;
    $blockEndMarker = '';
    
    foreach ($lines as $line) {
        $t = trim($line);
        if ($t === '') {
            $blank++;
            continue;
        }
        
        if ($inBlockComment) {
            $comment++;
            if (strpos($t, $blockEndMarker) !== false) {
                $inBlockComment = false;
            }
        } else {
            if (in_array($ext, ['php', 'js', 'css'])) {
                if (str_starts_with($t, '//') || ($ext === 'php' && str_starts_with($t, '#'))) {
                    $comment++;
                } elseif (str_starts_with($t, '/*')) {
                    $comment++;
                    if (strpos(substr($t, 2), '*/') === false) {
                        $inBlockComment = true;
                        $blockEndMarker = '*/';
                    }
                } else {
                    $code++;
                }
            } elseif ($ext === 'html') {
                if (str_starts_with($t, '<!--')) {
                    $comment++;
                    if (strpos(substr($t, 4), '-->') === false) {
                        $inBlockComment = true;
                        $blockEndMarker = '-->';
                    }
                } else {
                    $code++;
                }
            } elseif ($ext === 'py') {
                if (str_starts_with($t, '#')) {
                    $comment++;
                } elseif ((str_starts_with($t, '"""') || str_starts_with($t, "'''")) && strlen($t) >= 3) {
                    $comment++;
                    $marker = substr($t, 0, 3);
                    if (substr_count($t, $marker) === 1) {
                        $inBlockComment = true;
                        $blockEndMarker = $marker;
                    }
                } else {
                    $code++;
                }
            } else {
                $code++;
            }
        }
    }
    
    $stats['total_lines'] += $total;
    $stats['total_blank'] += $blank;
    $stats['total_comments'] += $comment;
    $stats['total_code'] += $code;
    
    if (!isset($stats['by_extension'][$ext])) {
        $stats['by_extension'][$ext] = ['files' => 0, 'total' => 0, 'code' => 0, 'blank' => 0, 'comment' => 0];
    }
    
    $stats['by_extension'][$ext]['files']++;
    $stats['by_extension'][$ext]['total'] += $total;
    $stats['by_extension'][$ext]['code'] += $code;
    $stats['by_extension'][$ext]['blank'] += $blank;
    $stats['by_extension'][$ext]['comment'] += $comment;
}

file_put_contents('data.json', json_encode($stats, JSON_PRETTY_PRINT));
?>
