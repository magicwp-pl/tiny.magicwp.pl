<?php

declare(strict_types=1);

namespace tiny_magicwp_pl;

if (!defined('ABSPATH')) {
    exit;
}

class Meta_Pixel_Admin {
    private const MENU_SLUG = 'tiny-magicwp-pl';
    private const SETTINGS_PAGE = 'tiny-magicwp-pl-settings';
    private const SETTINGS_SECTION = 'tiny_magicwp_pl';
    private const OPTION_NAME = 'meta_pixel_id';

    public function add_admin_menu(): void {
        global $menu;

        $menu_exists = false;
        foreach ($menu as $item) {
            if (($item[2] ?? null) === self::MENU_SLUG) {
                $menu_exists = true;
                break;
            }
        }

        if (!$menu_exists) {
            add_menu_page(
                'tiny.magicwp.pl',
                'tiny.magicwp.pl',
                'manage_options',
                self::MENU_SLUG,
                [$this, 'render_settings_page'],
                '',
                30
            );
        }

        add_submenu_page(
            self::MENU_SLUG,
            'meta pixel',
            'meta pixel',
            'manage_options',
            self::SETTINGS_PAGE,
            [$this, 'render_settings_page']
        );
    }

    public function register_settings(): void {
        add_settings_section(
            self::SETTINGS_SECTION,
            'Meta Pixel',
            '__return_empty_string',
            self::SETTINGS_PAGE
        );

        add_settings_field(
            'meta_pixel_id',
            'Meta Pixel ID',
            [$this, 'render_settings_field'],
            self::SETTINGS_PAGE,
            self::SETTINGS_SECTION,
            ['description' => 'Enter your Meta Pixel ID (numbers only)']
        );

        register_setting(self::SETTINGS_PAGE, self::OPTION_NAME, [
            'sanitize_callback' => [$this, 'sanitize_pixel_id']
        ]);
    }

    public function sanitize_pixel_id(string $value): string {
        return sanitize_text_field($value);
    }

    public function render_settings_page(): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_GET['settings-updated'])) {
            add_settings_error('tiny_magicwp_pl_messages', 'tiny_magicwp_pl_message', 'Settings saved', 'updated');
        }

        settings_errors('tiny_magicwp_pl_messages');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields(self::SETTINGS_PAGE);
                do_settings_sections(self::SETTINGS_PAGE);
                submit_button('Save');
                ?>
            </form>
        </div>
        <?php
    }

    public function render_settings_field(array $args = []): void {
        $value = get_option(self::OPTION_NAME, '');
        $description = $args['description'] ?? '';
        echo '<input type="text" name="' . esc_attr(self::OPTION_NAME) . '" value="' . esc_attr($value) . '" class="regular-text" placeholder="123456789012345" pattern="[0-9]+" />';
        if ($description) {
            echo '<p class="description">' . esc_html($description) . '</p>';
        }
    }

    public static function get_option_name(): string {
        return self::OPTION_NAME;
    }
}
