<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class Tailwind_Page_Template {

    public function should_use_tailwind(): bool {
        global $_wp_current_template_id;
        
        $template_post_id = null;
        
        if (isset($_wp_current_template_id) && $_wp_current_template_id && function_exists('get_block_template')) {
            $block_template = get_block_template($_wp_current_template_id);
            if ($block_template && isset($block_template->wp_id) && $block_template->wp_id) {
                $template_post_id = $block_template->wp_id;
            }
        }
        
        if (!$template_post_id) {
            $template_slug = get_page_template_slug();
            if ($template_slug) {
                $posts = get_posts(array(
                    'post_type' => 'wp_template',
                    'name' => $template_slug,
                    'post_status' => 'publish',
                    'numberposts' => 1,
                    'fields' => 'ids'
                ));
                if (!empty($posts)) {
                    $template_post_id = $posts[0];
                }
            }
        }
        
        if (!$template_post_id) {
            return false;
        }
        
        $use_tailwind = get_post_meta($template_post_id, '_use_tailwind', true);
        return $use_tailwind === '1';
    }
}

