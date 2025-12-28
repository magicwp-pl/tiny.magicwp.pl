<?php

declare(strict_types=1);

/**
 * @link              https://tiny.magicwp.pl
 * @since             1.0.0
 * @package           Meta_Pixel
 *
 * @wordpress-plugin
 * Plugin Name: meta pixel by tiny.magicwp.pl
 * Plugin URI:        https://tiny.magicwp.pl/meta-pixel
 * Description: Plugin do ładowania Meta Pixela
 * Version:           1.0.0
 * Author:            tiny.magicwp.pl
 * Author URI:        https://tiny.magicwp.pl
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 *
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/class-meta-pixel.php';

use tiny_magicwp_pl\Meta_Pixel;

(new Meta_Pixel())->run();
