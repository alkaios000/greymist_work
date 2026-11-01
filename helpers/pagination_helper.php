<?php
/**
 * File: helpers/pagination_helper.php
 * Fungsi: Helper kalkulasi data paginasi dan rendering bar navigasi halaman (Pagination)
 *         konsisten, modern, responsif, dan mempertahankan seluruh query filter aktif.
 */

if (!function_exists('paginate_data')) {
    /**
     * Menghitung metadata paginasi berdasarkan total item, halaman saat ini, dan batas per halaman.
     */
    function paginate_data(int $total_items, int $current_page = 1, int $per_page = 12): array
    {
        $per_page     = max(1, $per_page);
        $total_pages  = max(1, (int)ceil($total_items / $per_page));
        $current_page = max(1, min($current_page, $total_pages));
        $offset       = ($current_page - 1) * $per_page;
        $from         = $total_items > 0 ? $offset + 1 : 0;
        $to           = min($total_items, $offset + $per_page);

        return [
            'total_items'  => $total_items,
            'per_page'     => $per_page,
            'current_page' => $current_page,
            'total_pages'  => $total_pages,
            'offset'       => $offset,
            'from'         => $from,
            'to'           => $to,
            'has_prev'     => $current_page > 1,
            'has_next'     => $current_page < $total_pages,
        ];
    }
}

if (!function_exists('render_pagination')) {
    /**
     * Merender komponen HTML navigasi halaman (Pagination) dengan tautan yang mempertahankan query string aktif.
     */
    function render_pagination(array $pagination, string $item_label = 'data', string $page_param = 'p'): string
    {
        $total_pages  = (int)($pagination['total_pages'] ?? 1);
        $current_page = (int)($pagination['current_page'] ?? 1);
        $total_items  = (int)($pagination['total_items'] ?? 0);
        $from         = (int)($pagination['from'] ?? 0);
        $to           = (int)($pagination['to'] ?? 0);

        if ($total_items === 0 || $total_pages <= 1) {
            return '';
        }

        // Ambil query parameters saat ini untuk dipertahankan
        $queryParams = $_GET ?? [];

        $build_url = function (int $target_page) use ($queryParams, $page_param): string {
            $params = array_merge($queryParams, [$page_param => $target_page]);
            $front_page = $params['page'] ?? 'dashboard';
            unset($params['page']);
            return route_url((string)$front_page, $params);
        };

        $html = '<div class="pagination-wrapper">';
        $html .= '<div class="pagination-info">';
        $html .= 'Menampilkan <strong>' . $from . '–' . $to . '</strong> dari <strong>' . number_format($total_items) . '</strong> ' . htmlspecialchars($item_label, ENT_QUOTES, 'UTF-8');
        $html .= '</div>';

        $html .= '<ul class="pagination-list" aria-label="Navigasi Halaman">';

        // Tombol Sebelumnya
        if ($current_page > 1) {
            $html .= '<li class="pagination-item"><a href="' . htmlspecialchars($build_url($current_page - 1), ENT_QUOTES, 'UTF-8') . '" class="pagination-link" title="Halaman Sebelumnya">&lsaquo; Sebelumnya</a></li>';
        } else {
            $html .= '<li class="pagination-item"><span class="pagination-link disabled">&lsaquo; Sebelumnya</span></li>';
        }

        // Generate nomor-nomor halaman pintar
        $range = [];
        if ($total_pages <= 7) {
            $range = range(1, $total_pages);
        } else {
            if ($current_page <= 4) {
                $range = [1, 2, 3, 4, 5, '...', $total_pages];
            } elseif ($current_page >= $total_pages - 3) {
                $range = [1, '...', $total_pages - 4, $total_pages - 3, $total_pages - 2, $total_pages - 1, $total_pages];
            } else {
                $range = [1, '...', $current_page - 1, $current_page, $current_page + 1, '...', $total_pages];
            }
        }

        foreach ($range as $page_num) {
            if ($page_num === '...') {
                $html .= '<li class="pagination-item"><span class="pagination-ellipsis">&hellip;</span></li>';
            } elseif ((int)$page_num === $current_page) {
                $html .= '<li class="pagination-item"><span class="pagination-link active" aria-current="page">' . $page_num . '</span></li>';
            } else {
                $html .= '<li class="pagination-item"><a href="' . htmlspecialchars($build_url((int)$page_num), ENT_QUOTES, 'UTF-8') . '" class="pagination-link">' . $page_num . '</a></li>';
            }
        }

        // Tombol Berikutnya
        if ($current_page < $total_pages) {
            $html .= '<li class="pagination-item"><a href="' . htmlspecialchars($build_url($current_page + 1), ENT_QUOTES, 'UTF-8') . '" class="pagination-link" title="Halaman Berikutnya">Berikutnya &rsaquo;</a></li>';
        } else {
            $html .= '<li class="pagination-item"><span class="pagination-link disabled">Berikutnya &rsaquo;</span></li>';
        }

        $html .= '</ul>';
        $html .= '</div>';

        return $html;
    }
}

