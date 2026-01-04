<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class Tailwind_Cache {

    private function get_cache_dir(): string {
        $upload_dir = wp_upload_dir();
        return $upload_dir['basedir'] . '/tailwind-cache';
    }

    private function get_cache_url_base(): string {
        $upload_dir = wp_upload_dir();
        return $upload_dir['baseurl'] . '/tailwind-cache';
    }

    private function get_hash_from_url(string $url): string {
        $parsed = wp_parse_url($url);
        $path = $parsed['path'] ?? '/';
        if (!$path) {
            $path = '/';
        }
        return md5($path);
    }

    public function cache_exists(string $url): bool {
        $hash = $this->get_hash_from_url($url);
        $cache_file = $this->get_cache_dir() . '/tailwind-' . $hash . '.css';
        return file_exists($cache_file);
    }

    public function get_cache_url(string $url): string {
        $hash = $this->get_hash_from_url($url);
        return $this->get_cache_url_base() . '/tailwind-' . $hash . '.css';
    }

    public function save_cache(string $url, string $css_content): bool {
        if (empty($css_content)) {
            return false;
        }

        $cache_dir = $this->get_cache_dir();
        if (!is_dir($cache_dir)) {
            $this->ensure_cache_dir();
        }

        $hash = $this->get_hash_from_url($url);
        $cache_file = $cache_dir . '/tailwind-' . $hash . '.css';

        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            require_once ABSPATH . '/wp-admin/includes/file.php';
            WP_Filesystem();
        }

        if ($wp_filesystem->put_contents($cache_file, $css_content, 0644)) {
            return true;
        }

        return false;
    }

    public function ensure_cache_dir(): void {
        $cache_dir = $this->get_cache_dir();
        
        if (!is_dir($cache_dir)) {
            wp_mkdir_p($cache_dir);
            $index_file = $cache_dir . '/index.php';
            if (!file_exists($index_file)) {
                file_put_contents($index_file, '<?php' . PHP_EOL . '// Silence is golden.' . PHP_EOL);
            }
        }
    }

    public function get_all_cached_pages(): array {
        $cache_dir = $this->get_cache_dir();
        $cached_pages = array();

        if (!is_dir($cache_dir)) {
            return $cached_pages;
        }

        $files = glob($cache_dir . '/tailwind-*.css');
        if (!$files) {
            return $cached_pages;
        }

        foreach ($files as $file) {
            if (!file_exists($file)) {
                continue;
            }

            $filename = basename($file);
            if (preg_match('/^tailwind-([a-f0-9]{32})\.css$/', $filename, $matches)) {
                if (!file_exists($file)) {
                    continue;
                }
                $hash = $matches[1];
                $cached_pages[] = array(
                    'hash' => $hash,
                    'file' => $file,
                    'filename' => $filename,
                    'size' => file_exists($file) ? filesize($file) : 0,
                    'modified' => file_exists($file) ? filemtime($file) : 0
                );
            }
        }

        return $cached_pages;
    }

    public function delete_cache_file(string $hash): bool {
        $cache_dir = $this->get_cache_dir();
        $cache_file = $cache_dir . '/tailwind-' . $hash . '.css';
        
        if (file_exists($cache_file)) {
            return wp_delete_file($cache_file) !== false;
        }
        
        return false;
    }

    public function delete_all_cache(): int {
        $cached_pages = $this->get_all_cached_pages();
        $deleted = 0;

        foreach ($cached_pages as $page) {
            if ($this->delete_cache_file($page['hash'])) {
                $deleted++;
            }
        }

        return $deleted;
    }

}

