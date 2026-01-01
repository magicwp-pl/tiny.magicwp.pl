<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class Tailwind_Page_Public {

    private $template;

    public function __construct(Tailwind_Page_Template $template) {
        $this->template = $template;
        $this->init();
    }

    private function init(): void {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_tailwind_script']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_cache_capture_script']);
        add_action('wp_enqueue_scripts', [$this, 'remove_wordpress_styles'], 100);
        add_action('wp_head', [$this, 'remove_inline_styles'], 1);
        add_action('wp_print_styles', [$this, 'remove_all_styles'], 100);
        add_action('wp_head', [$this, 'clean_head_output'], 999);
        add_action('wp_enqueue_scripts', [$this, 'remove_wordpress_scripts'], 100);
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        add_action('template_redirect', [$this, 'remove_wordpress_fonts']);
        $this->register_ajax_handlers();
    }

    public function enqueue_tailwind_script(): void {
        if (!$this->template->should_use_tailwind()) {
            return;
        }

        require_once __DIR__ . '/../includes/class-tailwind-cache.php';
        $cache = new Tailwind_Cache();
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
        $uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        $current_url = $protocol . '://' . $host . $uri;

        if ($cache->cache_exists($current_url)) {
            $cache_url = $cache->get_cache_url($current_url);
            $parsed = wp_parse_url($current_url);
            $path = $parsed['path'] ?? '/';
            if (!$path) {
                $path = '/';
            }
            $hash = md5($path);
            $upload_dir = wp_upload_dir();
            $cache_file = $upload_dir['basedir'] . '/tailwind-cache/tailwind-' . $hash . '.css';
            $version = file_exists($cache_file) ? filemtime($cache_file) : '1.0';

            wp_enqueue_style(
                'tailwind-css-cached',
                $cache_url,
                array(),
                $version
            );
        } else {
            wp_enqueue_script(
                'tailwind-css',
                'https://cdn.tailwindcss.com/3.4.17',
                array(),
                '3.4.17',
                false
            );
        }
    }

    public function remove_wordpress_styles(): void {
        if (is_admin()) {
            return;
        }
        
        if (!$this->template->should_use_tailwind()) {
            return;
        }
        
        wp_dequeue_style('wp-block-library');
        wp_dequeue_style('wp-block-library-theme');
        wp_dequeue_style('wc-block-style');
        wp_dequeue_style('classic-theme-styles');
        wp_dequeue_style('global-styles');
        wp_dequeue_style('twentytwentyfive-style');
        wp_dequeue_style('twentytwentyfive-print-style');
        wp_dequeue_style('wp-emoji-styles');
        wp_dequeue_style('wp-emoji-styles-print');
    }

    public function remove_inline_styles(): void {
        if (is_admin()) {
            return;
        }
        
        if (!$this->template->should_use_tailwind()) {
            return;
        }
        
        add_action('wp_print_styles', function() {
            global $wp_styles;
            if (isset($wp_styles->registered['wp-block-library'])) {
                $wp_styles->registered['wp-block-library']->extra = array();
            }
        }, 100);
        
        add_action('wp_print_styles', function() {
            global $wp_styles;
            if (isset($wp_styles->registered['global-styles'])) {
                $wp_styles->registered['global-styles']->extra = array();
            }
        }, 100);
    }

    public function remove_all_styles(): void {
        if (is_admin()) {
            return;
        }
        
        if (!$this->template->should_use_tailwind()) {
            return;
        }
        
        global $wp_styles;
        
        $keep_styles = array(
            'dashicons',
            'admin-bar',
            'admin-bar-css',
            'tailwind-css-cached'
        );
        
        if (isset($wp_styles->registered)) {
            foreach ($wp_styles->registered as $handle => $style) {
                if (!in_array($handle, $keep_styles)) {
                    wp_dequeue_style($handle);
                    wp_deregister_style($handle);
                }
            }
        }
        
        $new_queue = array();
        foreach ($wp_styles->queue as $handle) {
            if (in_array($handle, $keep_styles)) {
                $new_queue[] = $handle;
            }
        }
        $wp_styles->queue = $new_queue;
    }

    public function remove_wordpress_scripts(): void {
        if (is_admin()) {
            return;
        }
        
        if (!$this->template->should_use_tailwind()) {
            return;
        }
        
        wp_dequeue_script('wp-embed');
        wp_dequeue_script('comment-reply');
        wp_dequeue_script('jquery');
    }

    public function remove_wordpress_fonts(): void {
        if (is_admin()) {
            return;
        }
        
        if (!$this->template->should_use_tailwind()) {
            return;
        }
        
        remove_action('wp_head', 'wp_print_font_faces', 50);
        remove_action('wp_head', 'wp_print_font_faces_from_style_variations', 50);
    }

    public function clean_head_output(): void {
        if (is_admin()) {
            return;
        }
        
        if (!$this->template->should_use_tailwind()) {
            return;
        }
        
        ob_start(function($html) {
            $html = preg_replace('/<style[^>]*id=[\'"](wp-block-library-inline-css|global-styles-inline-css|classic-theme-styles-inline-css)[\'"][^>]*>.*?<\/style>/is', '', $html);
            $html = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);
            $html = preg_replace_callback('/<link[^>]*rel=[\'"]stylesheet[\'"][^>]*>/i', function($matches) {
                $link = $matches[0];
                if (preg_match('/href=[\'"]([^\'"]*)[\'"]/', $link, $hrefMatches)) {
                    $href = $hrefMatches[1];
                    if (strpos($href, 'admin-bar') !== false || strpos($href, 'dashicons') !== false || strpos($href, 'tailwind-cache') !== false) {
                        return $link;
                    }
                }
                return '';
            }, $html);
            return $html;
        });
    }

    public function enqueue_cache_capture_script(): void {
        if (!$this->template->should_use_tailwind()) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        require_once __DIR__ . '/../includes/class-tailwind-cache.php';
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
        $uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        $current_url = $protocol . '://' . $host . $uri;
        $cache = new Tailwind_Cache();

        if ($cache->cache_exists($current_url)) {
            return;
        }

        $plugin_dir = dirname(dirname(__FILE__));
        $plugin_file = $plugin_dir . '/tailwind-gutenberg-block-by-tiny-magicwp-pl.php';
        $script_url = plugins_url('public/assets/js/tailwind-cache-capture.js', $plugin_file);

        wp_enqueue_script(
            'tailwind-cache-capture',
            $script_url,
            array(),
            filemtime($plugin_dir . '/public/assets/js/tailwind-cache-capture.js'),
            true
        );

        $parsed = wp_parse_url($current_url);
        $current_path = $parsed['path'] ?? '/';
        if (!$current_path) {
            $current_path = '/';
        }

        wp_localize_script('tailwind-cache-capture', 'tailwindCacheCapture', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tailwind_cache_nonce'),
            'currentUrl' => $current_path
        ));
    }

    private function register_ajax_handlers(): void {
        add_action('wp_ajax_tailwind_save_cache', [$this, 'handle_save_cache']);
    }

    public function handle_save_cache(): void {
        if (!check_ajax_referer('tailwind_cache_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }

        if (!isset($_POST['url']) || !isset($_POST['css'])) {
            wp_send_json_error(array('message' => 'Missing required parameters'));
            return;
        }

        $url = sanitize_text_field(wp_unslash($_POST['url']));
        $css = isset($_POST['css']) ? sanitize_textarea_field(wp_unslash($_POST['css'])) : '';

        if (empty($css)) {
            wp_send_json_error(array('message' => 'CSS content is empty'));
            return;
        }

        require_once __DIR__ . '/../includes/class-tailwind-cache.php';
        $cache = new Tailwind_Cache();

        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
        $full_url = $protocol . '://' . $host . $url;

        if ($cache->save_cache($full_url, $css)) {
            wp_send_json_success(array('message' => 'Cache saved successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to save cache'));
        }
    }
}

