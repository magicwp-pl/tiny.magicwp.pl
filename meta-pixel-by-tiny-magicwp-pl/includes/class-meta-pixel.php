<?php

declare(strict_types=1);

namespace tiny_magicwp_pl;

if (!defined('ABSPATH')) {
    exit;
}

class Meta_Pixel {

    public function __construct() {
        require_once __DIR__ . '/../admin/class-meta-pixel-admin.php';
        require_once __DIR__ . '/../public/class-meta-pixel-public.php';
    }

    public function run(): void {
	    $public = new Meta_Pixel_Public();
	    add_action('wp_head', [$public, 'add_meta_pixel'], 1000);

		if(!is_admin()) {
			return;
		}

	    $admin = new Meta_Pixel_Admin();
	    add_action('admin_menu', [$admin, 'add_admin_menu']);
	    add_action('admin_init', [$admin, 'register_settings']);
    }

}
