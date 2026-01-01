<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class Tailwind_Page_Admin {

    private $template;
    private $pending_use_tailwind = null;

    public function __construct(Tailwind_Page_Template $template) {
        $this->template = $template;
        $this->init();
    }

    private function init(): void {
        add_action('init', [$this, 'register_html_tailwind_block']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_html_tailwind_block_assets']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_template_sidebar_assets']);
        add_action('add_meta_boxes', [$this, 'add_template_meta_box']);
        add_action('save_post', [$this, 'save_template_meta'], 10, 2);
        add_action('rest_api_init', [$this, 'register_template_meta_rest']);
        add_filter('rest_pre_insert_wp_template', [$this, 'prepare_template_meta_rest'], 10, 2);
        add_action('rest_after_insert_wp_template', [$this, 'save_template_meta_rest'], 10, 3);
        add_action('rest_after_update_wp_template', [$this, 'save_template_meta_rest'], 10, 3);
        add_action('admin_menu', [$this, 'add_cache_menu']);
        add_action('admin_init', [$this, 'handle_cache_actions']);
    }

    public function register_html_tailwind_block(): void {
        register_block_type('tailwind-page/html-tailwind', array(
            'editor_script' => 'html-tailwind-block-editor',
            'editor_style' => 'html-tailwind-block-editor-style',
            'render_callback' => [$this, 'render_html_tailwind_block'],
            'attributes' => array(
                'content' => array(
                    'type' => 'string',
                    'source' => 'html',
                    'selector' => 'div',
                    'default' => '',
                ),
            ),
        ));
    }

    public function enqueue_template_sidebar_assets(): void {
        $plugin_dir = dirname(dirname(__FILE__));
        $plugin_file = $plugin_dir . '/tailwind-page.php';
        $script_url = plugins_url('admin/assets/js/template-sidebar.js', $plugin_file);
        
        wp_enqueue_script(
            'tailwind-page-template-sidebar',
            $script_url,
            array('wp-plugins', 'wp-editor', 'wp-edit-post', 'wp-edit-site', 'wp-element', 'wp-components', 'wp-data', 'wp-i18n', 'wp-api-fetch'),
            filemtime($plugin_dir . '/admin/assets/js/template-sidebar.js'),
            true
        );
    }

    public function enqueue_html_tailwind_block_assets(): void {
        $plugin_dir = dirname(dirname(__FILE__));
        $plugin_file = $plugin_dir . '/tailwind-page.php';
        
        wp_enqueue_script(
            'html-tailwind-block-editor',
            plugins_url('admin/assets/js/html-tailwind-block.js', $plugin_file),
            array('wp-blocks', 'wp-element', 'wp-components', 'wp-i18n', 'wp-block-editor'),
            filemtime($plugin_dir . '/admin/assets/js/html-tailwind-block.js'),
            true
        );
        
        wp_enqueue_style(
            'html-tailwind-block-editor-style',
            plugins_url('admin/assets/css/html-tailwind-block.css', $plugin_file),
            array(),
            filemtime($plugin_dir . '/admin/assets/css/html-tailwind-block.css')
        );
        
        wp_add_inline_script('html-tailwind-block-editor', '
            function loadTailwindInDocument(targetDoc) {
                if (!targetDoc || !targetDoc.head) {
                    return false;
                }
                if (!targetDoc.querySelector("script[src*=\'tailwindcss\']")) {
                    const script = targetDoc.createElement("script");
                    script.src = "https://cdn.tailwindcss.com/3.4.17";
                    targetDoc.head.appendChild(script);
                    return true;
                }
                return false;
            }
            
            loadTailwindInDocument(document);
            
            function setupIframeTailwind() {
                try {
                    const iframe = document.querySelector("iframe[name=\'editor-canvas\']");
                    
                    if (iframe) {
                        if (iframe.contentDocument && iframe.contentDocument.readyState === "complete") {
                            loadTailwindInDocument(iframe.contentDocument);
                        } else {
                            if (!iframe.hasAttribute("data-tailwind-listener")) {
                                iframe.setAttribute("data-tailwind-listener", "true");
                                iframe.addEventListener("load", function() {
                                    if (iframe.contentDocument) {
                                        loadTailwindInDocument(iframe.contentDocument);
                                    }
                                }, { once: true });
                            }
                        }
                    }
                } catch (e) {
                    console.debug("Tailwind Page: Could not access iframe", e);
                }
            }
            
            if (document.readyState === "loading") {
                document.addEventListener("DOMContentLoaded", setupIframeTailwind);
            } else {
                setupIframeTailwind();
            }
            
            if (document.body) {
                const observer = new MutationObserver(function(mutations) {
                    setupIframeTailwind();
                });
                
                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            } else {
                const observer = new MutationObserver(function(mutations) {
                    if (document.body) {
                        observer.disconnect();
                        observer.observe(document.body, {
                            childList: true,
                            subtree: true
                        });
                    }
                    setupIframeTailwind();
                });
                
                observer.observe(document.documentElement, {
                    childList: true,
                    subtree: false
                });
            }
            
            setTimeout(setupIframeTailwind, 500);
            setTimeout(setupIframeTailwind, 1000);
            setTimeout(setupIframeTailwind, 2000);
        ');
    }

    public function add_template_meta_box(): void {
        add_meta_box(
            'tailwind-page-options',
            'Tailwind CSS',
            [$this, 'render_template_meta_box'],
            'wp_template',
            'side',
            'default'
        );
    }

    public function render_template_meta_box($post): void {
        wp_nonce_field('tailwind_page_meta', 'tailwind_page_meta_nonce');
        $use_tailwind = get_post_meta($post->ID, '_use_tailwind', true);
        ?>
        <label>
            <input type="checkbox" name="use_tailwind" value="1" <?php checked($use_tailwind, '1'); ?>>
            Użyj Tailwind CSS
        </label>
        <p class="description">Włącza Tailwind CSS i usuwa domyślne style WordPress dla tego template.</p>
        <?php
    }

    public function save_template_meta($post_id, $post): void {
        if ($post->post_type !== 'wp_template') {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (!isset($_POST['tailwind_page_meta_nonce']) || !wp_verify_nonce(wp_unslash($_POST['tailwind_page_meta_nonce']), 'tailwind_page_meta')) {
            return;
        }
        
        $use_tailwind = isset($_POST['use_tailwind']) ? '1' : '0';
        update_post_meta($post_id, '_use_tailwind', $use_tailwind);
    }

    public function prepare_template_meta_rest($prepared_post, $request) {
        if (isset($request['use_tailwind'])) {
            $this->pending_use_tailwind = $request['use_tailwind'];
        }
        return $prepared_post;
    }

    public function save_template_meta_rest($post, $request, $creating): void {
        if (isset($request['use_tailwind'])) {
            update_post_meta($post->ID, '_use_tailwind', $request['use_tailwind'] ? '1' : '0');
        } elseif ($this->pending_use_tailwind !== null) {
            update_post_meta($post->ID, '_use_tailwind', $this->pending_use_tailwind ? '1' : '0');
            $this->pending_use_tailwind = null;
        }
    }

    public function register_template_meta_rest(): void {
        register_rest_field('wp_template', 'use_tailwind', array(
            'get_callback' => function($post) {
                $post_id = is_object($post) ? $post->ID : (is_array($post) ? $post['id'] : $post);
                
                if (is_string($post_id) && strpos($post_id, '//') !== false) {
                    $parts = explode('//', $post_id);
                    if (count($parts) === 2) {
                        $template = get_block_template($post_id);
                        if ($template && isset($template->wp_id) && $template->wp_id) {
                            $post_id = $template->wp_id;
                        } else {
                            $posts = get_posts(array(
                                'post_type' => 'wp_template',
                                'name' => $parts[1],
                                'post_status' => array('publish', 'auto-draft'),
                                'numberposts' => 1
                            ));
                            if (!empty($posts)) {
                                $post_id = $posts[0]->ID;
                            } else {
                                return false;
                            }
                        }
                    }
                }
                
                if (!is_numeric($post_id)) {
                    return false;
                }
                
                return get_post_meta($post_id, '_use_tailwind', true) === '1';
            },
            'update_callback' => function($value, $post) {
                if (is_object($post) && isset($post->ID)) {
                    update_post_meta($post->ID, '_use_tailwind', $value ? '1' : '0');
                    return true;
                }
                if (is_array($post) && isset($post['id'])) {
                    $post_id = $post['id'];
                    if (is_numeric($post_id)) {
                        update_post_meta($post_id, '_use_tailwind', $value ? '1' : '0');
                        return true;
                    }
                }
                return true;
            },
            'schema' => array(
                'type' => 'boolean',
                'context' => array('edit')
            )
        ));
    }

    public function render_html_tailwind_block($attributes, $content = '', $block = null): string {
        if (empty($content)) {
            $content = isset($attributes['content']) ? $attributes['content'] : '';
        }
        
        if (empty($content)) {
            return '';
        }
        
        return '<div class="html-tailwind-block">' . $content . '</div>';
    }

    public function add_cache_menu(): void {
        global $menu;

        $menu_exists = false;
        foreach ($menu as $item) {
            if (($item[2] ?? null) === 'tiny-magicwp-pl') {
                $menu_exists = true;
                break;
            }
        }

        if (!$menu_exists) {
            add_menu_page(
                'tiny.magicwp.pl',
                'tiny.magicwp.pl',
                'manage_options',
                'tiny-magicwp-pl',
                '__return_empty_string',
                '',
                30
            );
        }

        add_submenu_page(
            'tiny-magicwp-pl',
            'Tailwind Gutenberg Block',
            'Tailwind Gutenberg Block',
            'manage_options',
            'tailwind-gutenberg-block-cache',
            [$this, 'render_cache_page']
        );
    }

    public function handle_cache_actions(): void {
        if (!isset($_GET['page']) || $_GET['page'] !== 'tailwind-gutenberg-block-cache') {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_GET['action']) && isset($_GET['_wpnonce'])) {
            $nonce = isset($_GET['_wpnonce']) ? wp_unslash($_GET['_wpnonce']) : '';
            if (!wp_verify_nonce($nonce, 'tailwind_cache_action')) {
                wp_die('Security check failed');
            }

            require_once __DIR__ . '/../includes/class-tailwind-cache.php';
            $cache = new Tailwind_Cache();

            $action = isset($_GET['action']) ? sanitize_text_field(wp_unslash($_GET['action'])) : '';

            if ($action === 'delete_all') {
                $deleted = $cache->delete_all_cache();
                wp_safe_redirect(add_query_arg(array(
                    'page' => 'tailwind-gutenberg-block-cache',
                    'deleted_all' => $deleted,
                    '_wpnonce' => wp_create_nonce('tailwind_cache_action')
                ), admin_url('admin.php')));
                exit;
            }

            if ($action === 'delete' && isset($_GET['hash'])) {
                $hash = sanitize_text_field(wp_unslash($_GET['hash']));
                if ($cache->delete_cache_file($hash)) {
                    wp_safe_redirect(add_query_arg(array(
                        'page' => 'tailwind-gutenberg-block-cache',
                        'deleted' => '1',
                        '_wpnonce' => wp_create_nonce('tailwind_cache_action')
                    ), admin_url('admin.php')));
                } else {
                    wp_safe_redirect(add_query_arg(array(
                        'page' => 'tailwind-gutenberg-block-cache',
                        'error' => '1',
                        '_wpnonce' => wp_create_nonce('tailwind_cache_action')
                    ), admin_url('admin.php')));
                }
                exit;
            }
        }
    }

    public function render_cache_page(): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        require_once __DIR__ . '/../includes/class-tailwind-cache.php';
        $cache = new Tailwind_Cache();

        if (isset($_GET['deleted_all']) || isset($_GET['deleted']) || isset($_GET['error'])) {
            if (isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'tailwind_cache_action')) {
                if (isset($_GET['deleted_all'])) {
                    $deleted_count = intval(wp_unslash($_GET['deleted_all']));
                    add_settings_error('tailwind_cache_messages', 'tailwind_cache_message', 
                        sprintf('Deleted %d cache file(s).', $deleted_count), 'updated');
                }

                if (isset($_GET['deleted'])) {
                    add_settings_error('tailwind_cache_messages', 'tailwind_cache_message', 
                        'Cache file deleted successfully.', 'updated');
                }

                if (isset($_GET['error'])) {
                    add_settings_error('tailwind_cache_messages', 'tailwind_cache_message', 
                        'Error deleting cache file.', 'error');
                }
            }
        }

        settings_errors('tailwind_cache_messages');

        $cached_pages = $cache->get_all_cached_pages();
        $pages_with_cache = $this->get_pages_with_cache($cached_pages);

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <p>
                <a href="<?php echo esc_url(wp_nonce_url(
                    add_query_arg(array(
                        'page' => 'tailwind-gutenberg-block-cache',
                        'action' => 'delete_all'
                    ), admin_url('admin.php')),
                    'tailwind_cache_action'
                )); ?>" 
                   class="button button-secondary" 
                   onclick="return confirm('Are you sure you want to delete all cache files?');">
                    Delete All Cache
                </a>
            </p>

            <h2>Cached Pages</h2>
            <?php if (empty($pages_with_cache)): ?>
                <p>No cached pages found.</p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col" class="manage-column">Page URL</th>
                            <th scope="col" class="manage-column">Cache File</th>
                            <th scope="col" class="manage-column">Size</th>
                            <th scope="col" class="manage-column">Modified</th>
                            <th scope="col" class="manage-column">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pages_with_cache as $page): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($page['url']); ?></strong>
                                </td>
                                <td>
                                    <code><?php echo esc_html($page['filename']); ?></code>
                                </td>
                                <td>
                                    <?php echo esc_html(size_format($page['size'])); ?>
                                </td>
                                <td>
                                    <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $page['modified'])); ?>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url(wp_nonce_url(
                                        add_query_arg(array(
                                            'page' => 'tailwind-gutenberg-block-cache',
                                            'action' => 'delete',
                                            'hash' => $page['hash']
                                        ), admin_url('admin.php')),
                                        'tailwind_cache_action'
                                    )); ?>" 
                                       class="button button-small" 
                                       onclick="return confirm('Are you sure you want to delete this cache file?');">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    private function get_pages_with_cache(array $cached_pages): array {
        $pages_with_cache = array();

        $all_pages = get_pages(array(
            'number' => -1,
            'post_status' => array('publish', 'private', 'draft')
        ));

        $all_posts = get_posts(array(
            'numberposts' => -1,
            'post_status' => array('publish', 'private', 'draft'),
            'post_type' => 'any'
        ));

        $all_urls = array();

        foreach ($all_pages as $page) {
            $url = get_permalink($page->ID);
            if ($url) {
                $all_urls[] = $url;
            }
        }

        foreach ($all_posts as $post) {
            $url = get_permalink($post->ID);
            if ($url) {
                $all_urls[] = $url;
            }
        }

        $home_url = home_url('/');
        $all_urls[] = $home_url;

        require_once __DIR__ . '/../includes/class-tailwind-cache.php';
        $cache = new Tailwind_Cache();

        foreach ($cached_pages as $cached_page) {
            $hash = $cached_page['hash'];
            
            foreach ($all_urls as $url) {
                $parsed = wp_parse_url($url);
                $path = $parsed['path'] ?? '/';
                if (!$path) {
                    $path = '/';
                }
                $url_hash = md5($path);
                
                if ($url_hash === $hash) {
                    $pages_with_cache[] = array(
                        'url' => $url,
                        'hash' => $hash,
                        'filename' => $cached_page['filename'],
                        'size' => $cached_page['size'],
                        'modified' => $cached_page['modified']
                    );
                    break;
                }
            }
        }

        return $pages_with_cache;
    }
}

