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
        add_action('wp_enqueue_scripts', [$this, 'remove_wordpress_styles'], 100);
        add_action('wp_head', [$this, 'remove_inline_styles'], 1);
        add_action('wp_print_styles', [$this, 'remove_all_styles'], 100);
        add_action('wp_head', [$this, 'clean_head_output'], 999);
        add_action('wp_enqueue_scripts', [$this, 'remove_wordpress_scripts'], 100);
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        add_action('template_redirect', [$this, 'remove_wordpress_fonts']);
    }

    public function enqueue_tailwind_script(): void {
        if (!$this->template->should_use_tailwind()) {
            return;
        }
        
        wp_enqueue_script(
            'tailwind-css',
            'https://cdn.tailwindcss.com/3.4.17',
            array(),
            '3.4.17',
            false
        );
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
            'admin-bar-css'
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
            $html = preg_replace('/<link[^>]*rel=[\'"]stylesheet[\'"][^>]*(?<!admin-bar)(?<!dashicons)[^>]*>/i', '', $html);
            return $html;
        });
    }
}

