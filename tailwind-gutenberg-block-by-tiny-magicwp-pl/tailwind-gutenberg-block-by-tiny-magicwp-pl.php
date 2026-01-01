<?php

declare(strict_types=1);

/**
 * Plugin Name: Tailwind Gutenberg Block by tiny.magicwp.pl
 * Description: Plugin adds new Gutenberg block for Tailwind CSS v3
 * Version: 1.0.9
 * Plugin URI:        https://tiny.magicwp.pl/tailwind-gutenberg-block
 * Author:            tiny.magicwp.pl
 * Author URI:        https://tiny.magicwp.pl
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.txt
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/class-tailwind-page.php';

(new Tailwind_Page())->run();

register_activation_hook(__FILE__, function() {
    $plugin = new Tailwind_Page();
    $plugin->activate();
});
