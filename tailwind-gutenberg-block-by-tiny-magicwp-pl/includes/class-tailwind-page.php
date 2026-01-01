<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/class-tailwind-page-template.php';
require_once __DIR__ . '/class-tailwind-cache.php';
require_once __DIR__ . '/../admin/class-tailwind-page-admin.php';
require_once __DIR__ . '/../public/class-tailwind-page-public.php';

class Tailwind_Page {

    public function __construct() {
    }

    public function run(): void {
        $template = new Tailwind_Page_Template();
        $public = new Tailwind_Page_Public($template);
        $admin = new Tailwind_Page_Admin($template);
    }

    public function activate(): void {
        $existing_template = get_posts(array(
            'post_type' => 'wp_template',
            'post_name' => 'page-for-tailwind-v3',
            'post_status' => 'publish',
            'numberposts' => 1
        ));
        
        if (empty($existing_template)) {
            $template_content = '<!-- wp:post-content {"align":"full","layout":{"inherit":true}} /-->';
            
            $template_id = wp_insert_post(array(
                'post_title' => 'Page for Tailwind V3',
                'post_name' => 'page-for-tailwind-v3',
                'post_status' => 'publish',
                'post_content' => $template_content,
                'post_type' => 'wp_template'
            ));
            
            if ($template_id && !is_wp_error($template_id)) {
                $current_theme = get_stylesheet();
                $theme_term = get_term_by('slug', $current_theme, 'wp_theme');
                
                if (!$theme_term) {
                    $theme_term = wp_insert_term($current_theme, 'wp_theme');
                    if (!is_wp_error($theme_term)) {
                        $theme_term_id = $theme_term['term_id'];
                    }
                } else {
                    $theme_term_id = $theme_term->term_id;
                }
                
                if (isset($theme_term_id)) {
                    wp_set_post_terms($template_id, array($theme_term_id), 'wp_theme');
                }
                
                update_post_meta($template_id, '_use_tailwind', '1');
            }
        }

        $cache = new Tailwind_Cache();
        $cache->ensure_cache_dir();
    }
}

