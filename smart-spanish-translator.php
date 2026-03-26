<?php
/**
 * Plugin Name: Smart Spanish Translator
 * Description: Auto-translates WordPress content to Spanish, respecting manually translated pages.
 * Version: 1.1.0
 * Author: Diana BluShark
 * Text Domain: smart-spanish-translator
 */
defined('ABSPATH') || exit;

define('SST_VERSION', '1.1.0');
define('SST_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SST_PLUGIN_URL', plugin_dir_url(__FILE__));

// ─── GitHub Updater ───────────────────────────────────────────────────────────
if (file_exists(SST_PLUGIN_DIR . 'plugin-update-checker/plugin-update-checker.php')) {
    require SST_PLUGIN_DIR . 'plugin-update-checker/plugin-update-checker.php';
    $sstUpdateChecker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        'https://github.com/BlusharkDigital/smart-spanish-translator/',
        __FILE__,
        'smart-spanish-translator'
    );
    // Optionally Set the branch that contains the stable release.
    $sstUpdateChecker->setBranch('main');
}

// ─── Admin Menu ───────────────────────────────────────────────────────────────

add_action('admin_menu', function () {
    add_menu_page(
            'Spanish Translator',
            'ES Translator',
            'manage_options',
            'sst-translator',
            'sst_admin_page',
            'dashicons-translation',
            30
    );

    add_submenu_page(
            'sst-translator',
            'Settings',
            'Settings',
            'manage_options',
            'sst-settings',
            'sst_settings_page'
    );
});

// ─── Settings ─────────────────────────────────────────────────────────────────

add_action('admin_init', function () {
    register_setting('sst_options', 'sst_api_key', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('sst_options', 'sst_api_provider', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('sst_options', 'sst_deepl_api_key', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('sst_options', 'sst_auto_translate', ['sanitize_callback' => 'rest_sanitize_boolean']);
    register_setting('sst_options', 'sst_post_types', ['sanitize_callback' => 'sst_sanitize_array']);
    register_setting('sst_options', 'sst_menu_location', ['sanitize_callback' => 'sanitize_text_field', 'default' => 'primary']);
    register_setting('sst_options', 'sst_url_prefix', [
        'sanitize_callback' => function ($val) {
            $val = trim(sanitize_title($val), '/');
            return $val ?: 'es';
        },
        'default' => 'es',
    ]);
});

function sst_sanitize_array($val) {
    return is_array($val) ? array_map('sanitize_text_field', $val) : [];
}

function sst_url_prefix() {
    return get_option('sst_url_prefix', 'es');
}

// ─── Post Meta Box ────────────────────────────────────────────────────────────

add_action('add_meta_boxes', function () {
    $post_types = get_option('sst_post_types', ['post', 'page']);
    foreach ($post_types as $pt) {
        add_meta_box(
                'sst_translation_box',
                '🇪🇸 Spanish Translation',
                'sst_meta_box_html',
                $pt,
                'side',
                'high'
        );
    }
});

function sst_meta_box_html($post) {
    wp_nonce_field('sst_save_meta', 'sst_nonce');

    $is_translation = get_post_meta($post->ID, '_sst_is_translation', true) === '1';
    $is_manual = get_post_meta($post->ID, '_sst_manual_translation', true);
    $last_synced = get_post_meta($post->ID, '_sst_last_synced', true);
    $es_post_id = get_post_meta($post->ID, '_sst_es_post_id', true);
    $source_post_id = get_post_meta($post->ID, '_sst_source_post_id', true);
    ?>
    <div style="font-size:13px; line-height:1.7;">

        <?php if ($is_translation): ?>

            <div style="background:#f0f6fc;border:1px solid #c3dafe;border-radius:6px;padding:10px 12px;margin-bottom:10px;">
                <p style="margin:0 0 6px;font-weight:600;color:#1e40af;">🇪🇸 This is a Spanish translation</p>
                <?php if ($source_post_id && get_post($source_post_id)): ?>
                    <p style="margin:0 0 2px;color:#555;">
                        Linked to: <strong><?php echo esc_html(get_the_title($source_post_id)); ?></strong>
                    </p>
                    <p style="margin:6px 0 0;display:flex;gap:10px;">
                        <a href="<?php echo esc_url(get_edit_post_link($source_post_id)); ?>">← Edit English original</a>
                        <a href="<?php echo esc_url(get_permalink($source_post_id)); ?>" target="_blank">View EN →</a>
                    </p>
                <?php else: ?>
                    <p style="margin:4px 0 0;color:#888;font-size:12px;">English source page not found.</p>
                <?php endif; ?>
            </div>
            <p style="color:#999;font-size:11px;margin:6px 0 0;">
                To re-translate, open the English original and click "Re-translate".
            </p>

        <?php else: ?>

            <p style="margin-top:0;">
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                    <input type="checkbox" name="sst_manual_translation" value="1" <?php checked($is_manual, '1'); ?>>
                    <strong>Manually translated</strong>
                </label>
                <span style="color:#666;font-size:11px;display:block;margin-top:2px;">
                    Check this to skip auto-translation for this page.
                </span>
            </p>

            <hr style="border:none;border-top:1px solid #ddd;margin:10px 0;">

            <?php if ($es_post_id && get_post($es_post_id)): ?>
                <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;padding:10px 12px;margin-bottom:8px;">
                    <p style="margin:0 0 4px;font-weight:600;color:#166534;">✓ Spanish version linked</p>
                    <p style="margin:0;color:#555;font-size:12px;">
                        <strong><?php echo esc_html(get_the_title($es_post_id)); ?></strong>
                    </p>
                    <p style="margin:6px 0 0;display:flex;gap:10px;">
                        <a href="<?php echo esc_url(get_edit_post_link($es_post_id)); ?>">Edit ES →</a>
                        <a href="<?php echo esc_url(get_permalink($es_post_id)); ?>" target="_blank">View ES →</a>
                    </p>
                    <?php if ($last_synced): ?>
                        <p style="margin:6px 0 0;color:#888;font-size:11px;">
                            Synced <?php echo esc_html(human_time_diff(strtotime($last_synced), current_time('timestamp'))); ?> ago
                        </p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div style="background:#fafafa;border:1px solid #e5e7eb;border-radius:6px;padding:10px 12px;margin-bottom:8px;">
                    <p style="margin:0;color:#999;">No Spanish version yet.</p>
                </div>
            <?php endif; ?>

            <button type="button" class="button button-secondary" style="width:100%;margin-top:2px;"
                    onclick="sstTranslateSingle(<?php echo $post->ID; ?>, this)">
                        <?php echo ($es_post_id && get_post($es_post_id)) ? '↺ Re-translate' : '🌐 Translate Now'; ?>
            </button>

        <?php endif; ?>
    </div>

    <script>
        function sstTranslateSingle(postId, btn) {
            const original = btn.textContent;
            btn.textContent = 'Translating…';
            btn.disabled = true;
            fetch(ajaxurl, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'sst_translate_single',
                    post_id: postId,
                    force: '1',
                    nonce: '<?php echo wp_create_nonce("sst_ajax"); ?>'
                })
            })
                    .then(r => r.json())
                    .then(d => {
                        btn.textContent = d.success ? '✓ Done! Reload to see link.' : '✗ ' + (d.data || 'Error');
                        setTimeout(() => {
                            btn.textContent = original;
                            btn.disabled = false;
                        }, 3000);
                    });
        }

        // Visually merge the ACF HrefLang Source box into this Spanish Translation box
        document.addEventListener('DOMContentLoaded', function() {
            var acfBox = document.getElementById('acf-group_sst_plugin_hreflang');
            var sstBox = document.getElementById('sst_translation_box');
            if (acfBox && sstBox) {
                var acfInner = acfBox.querySelector('.inside');
                var sstInner = sstBox.querySelector('.inside');
                if (acfInner && sstInner) {
                    var header = document.createElement('h3');
                    header.innerHTML = 'HrefLang Settings';
                    header.style.cssText = 'font-size:14px; font-weight:600; margin:15px 0 10px; padding:15px 0 0; border-top:1px solid #ddd;';
                    sstInner.appendChild(header);
                    
                    sstInner.appendChild(acfInner);
                    acfBox.style.display = 'none';
                }
            }
        });
    </script>
    <?php
}

add_action('save_post', function ($post_id) {
    if (!isset($_POST['sst_nonce']) || !wp_verify_nonce($_POST['sst_nonce'], 'sst_save_meta'))
        return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return;
    if (!current_user_can('edit_post', $post_id))
        return;

    $is_manual = isset($_POST['sst_manual_translation']) ? '1' : '0';
    update_post_meta($post_id, '_sst_manual_translation', $is_manual);

    // Auto-translate on save if enabled and not manual
    if ($is_manual === '0' && get_option('sst_auto_translate')) {
        wp_schedule_single_event(time() + 5, 'sst_async_translate', [$post_id, false]);
    }
});

add_action('sst_async_translate', 'sst_translate_post', 10, 2);

// ─── Translation Engine ───────────────────────────────────────────────────────

function sst_translate_post($post_id, $force = false) {
    $post = get_post($post_id);
    if (!$post || !in_array($post->post_status, ['publish', 'future']))
        return ['success' => false, 'message' => 'Post not published.'];

    // Skip Spanish translations themselves
    if (get_post_meta($post_id, '_sst_is_translation', true) === '1') {
        return ['success' => false, 'message' => 'Skipped — this is already a translation.'];
    }

    $is_manual = get_post_meta($post_id, '_sst_manual_translation', true);
    if ($is_manual === '1' && !$force) {
        return ['success' => false, 'message' => 'Skipped — manually translated.'];
    }

    // Skip if a manual Spanish translation URL is already assigned
    $related_url = get_post_meta($post_id, 'related_lang_url', true);
    if (!empty($related_url) && !$force) {
        return ['success' => false, 'message' => 'Skipped — related Spanish URL already assigned.'];
    }

    $provider = get_option('sst_api_provider', 'claude');
    $api_key = get_option('sst_api_key', '');
    $deepl_key = get_option('sst_deepl_api_key', '');

    // Validate that we have a key for the chosen provider
    if ($provider === 'deepl' && empty($deepl_key)) {
        return ['success' => false, 'message' => 'No DeepL API key configured.'];
    }
    if ($provider !== 'deepl' && empty($api_key)) {
        return ['success' => false, 'message' => 'No API key configured.'];
    }

    // Translate title, excerpt, and content
    $translated_title = sst_call_translation_api($post->post_title, $api_key, $provider, true);
    $translated_excerpt = !empty($post->post_excerpt) ? sst_call_translation_api($post->post_excerpt, $api_key, $provider, true) : '';
    $translated_content = sst_call_translation_api($post->post_content, $api_key, $provider, false);

    if (!$translated_title || !$translated_content) {
        return ['success' => false, 'message' => 'Translation API failed.'];
    }

    // Check if a Spanish version already exists
    $es_post_id = get_post_meta($post_id, '_sst_es_post_id', true);

    // Translate the URL slug to Spanish as well
    $translated_slug = sst_translate_slug($post->post_name, $api_key, $provider);

    $es_data = [
        'post_title' => $translated_title,
        'post_content' => $translated_content,
        'post_excerpt' => $translated_excerpt,
        'post_status' => 'publish',
        'post_type' => $post->post_type,
        'post_name' => $translated_slug,
        'meta_input' => [
            '_sst_source_post_id' => $post_id,
            '_sst_is_translation' => '1',
            '_sst_es_slug' => $translated_slug,
            '_sst_translated_title' => $translated_title,
        ],
    ];

    if ($es_post_id && get_post($es_post_id)) {
        $es_data['ID'] = $es_post_id;
        wp_update_post($es_data);
    } else {
        $es_post_id = wp_insert_post($es_data);
        if (is_wp_error($es_post_id)) {
            return ['success' => false, 'message' => $es_post_id->get_error_message()];
        }
        update_post_meta($post_id, '_sst_es_post_id', $es_post_id);
    }

    update_post_meta($post_id, '_sst_last_synced', current_time('mysql'));

    return ['success' => true, 'es_post_id' => $es_post_id];
}

function sst_translate_slug($slug, $api_key, $provider) {
    $readable = str_replace(['-', '_'], ' ', $slug);
    $prompt = "Translate these URL slug words to Spanish. Return ONLY lowercase hyphenated slug words suitable for a URL (no spaces, no special characters, use hyphens). Example: 'our services' → 'nuestros-servicios'. Input: {$readable}";

    if ($provider === 'deepl') {
        // DeepL translates the readable form, then we slugify the result
        $deepl_key = get_option('sst_deepl_api_key', '');
        $translated = sst_deepl_translate($readable, $deepl_key);
    } elseif ($provider === 'claude') {
        $translated = sst_claude_translate($readable, $prompt, $api_key);
    } elseif ($provider === 'openai') {
        $translated = sst_openai_translate($prompt, $api_key);
    } else {
        $translated = false;
    }

    if (!$translated)
        return $slug . '-es';

    // Sanitize into a clean hyphenated slug
    $translated = strtolower(trim($translated));

    // Transliterate Spanish accented characters to ASCII equivalents
    // so "nuestros-servicios" doesn't lose letters and merge words
    $accent_map = [
        'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'ã' => 'a',
        'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
        'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'õ' => 'o',
        'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
        'ñ' => 'n', 'ç' => 'c',
    ];
    $translated = strtr($translated, $accent_map);

    $translated = preg_replace('/[\s_]+/', '-', $translated);      // spaces/underscores → hyphens
    $translated = preg_replace('/[^a-z0-9\-]/', '', $translated);  // strip remaining special chars
    $translated = preg_replace('/-+/', '-', $translated);          // collapse double hyphens
    $translated = trim($translated, '-');

    return $translated ?: $slug . '-es';
}

function sst_call_translation_api($text, $api_key, $provider, $is_title = false) {
    if (empty(trim($text)))
        return $text;

    $prompt = $is_title ? "Translate the following page/post title to Spanish. Return ONLY the translated title, nothing else:\n\n{$text}" : "Translate the following WordPress post content to Spanish. Preserve all HTML tags exactly. Return ONLY the translated HTML, nothing else:\n\n{$text}";

    if ($provider === 'deepl') {
        $deepl_key = get_option('sst_deepl_api_key', '');
        return sst_deepl_translate($text, $deepl_key);
    } elseif ($provider === 'claude') {
        return sst_claude_translate($text, $prompt, $api_key);
    } elseif ($provider === 'openai') {
        return sst_openai_translate($prompt, $api_key);
    }

    return false;
}

function sst_claude_translate($text, $prompt, $api_key) {
    $response = wp_remote_post('https://api.anthropic.com/v1/messages', [
        'timeout' => 60,
        'headers' => [
            'x-api-key' => $api_key,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ],
        'body' => json_encode([
            'model' => 'claude-sonnet-4-20250514',
            'max_tokens' => 4096,
            'messages' => [['role' => 'user', 'content' => $prompt]],
        ]),
    ]);

    if (is_wp_error($response))
        return false;
    $body = json_decode(wp_remote_retrieve_body($response), true);
    return $body['content'][0]['text'] ?? false;
}

function sst_openai_translate($prompt, $api_key) {
    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'timeout' => 60,
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        ],
        'body' => json_encode([
            'model' => 'gpt-4o',
            'messages' => [['role' => 'user', 'content' => $prompt]],
        ]),
    ]);

    if (is_wp_error($response))
        return false;
    $body = json_decode(wp_remote_retrieve_body($response), true);
    return $body['choices'][0]['message']['content'] ?? false;
}

function sst_deepl_translate($text, $api_key) {
    if (empty($api_key))
        return false;

    // DeepL Free keys end in :fx, paid keys don't — use correct endpoint
    $is_free = str_ends_with($api_key, ':fx');
    $endpoint = $is_free ? 'https://api-free.deepl.com/v2/translate' : 'https://api.deepl.com/v2/translate';

    $response = wp_remote_post($endpoint, [
        'timeout' => 60,
        'headers' => [
            'Authorization' => 'DeepL-Auth-Key ' . $api_key,
            'Content-Type' => 'application/json',
        ],
        'body' => json_encode([
            'text' => [$text],
            'target_lang' => 'ES',
            'tag_handling' => 'html', // preserves HTML tags automatically
        ]),
    ]);

    if (is_wp_error($response))
        return false;
    $body = json_decode(wp_remote_retrieve_body($response), true);
    return $body['translations'][0]['text'] ?? false;
}

// ─── AJAX Handlers ────────────────────────────────────────────────────────────

add_action('wp_ajax_sst_translate_single', function () {
    check_ajax_referer('sst_ajax', 'nonce');
    if (!current_user_can('edit_posts'))
        wp_send_json_error('Permission denied.');

    $post_id = intval($_POST['post_id'] ?? 0);
    $force = !empty($_POST['force']);
    $result = sst_translate_post($post_id, $force);

    if ($result['success']) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error($result['message']);
    }
});

add_action('wp_ajax_sst_bulk_translate', function () {
    check_ajax_referer('sst_ajax', 'nonce');
    if (!current_user_can('manage_options'))
        wp_send_json_error('Permission denied.');

    $post_types = get_option('sst_post_types', ['post', 'page']);
    $posts = get_posts([
        'post_type' => $post_types,
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => [
            'relation' => 'AND',
            // Exclude already-translated Spanish posts
            [
                'key' => '_sst_is_translation',
                'compare' => 'NOT EXISTS',
            ],
            // Exclude manually translated
            [
                'relation' => 'OR',
                ['key' => '_sst_manual_translation', 'compare' => 'NOT EXISTS'],
                ['key' => '_sst_manual_translation', 'value' => '0'],
            ],
        ],
    ]);

    $count = 0;
    foreach ($posts as $pid) {
        $result = sst_translate_post($pid, false);
        if ($result['success'])
            $count++;
    }

    wp_send_json_success(['translated' => $count, 'total' => count($posts)]);
});

// ─── Admin Pages ──────────────────────────────────────────────────────────────

function sst_admin_page() {
    $post_types = get_option('sst_post_types', ['post', 'page']);
    $nonce = wp_create_nonce('sst_ajax');

    // Build stats
    $all_posts = get_posts(['post_type' => $post_types, 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids']);
    $manual_posts = get_posts([
        'post_type' => $post_types, 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids',
        'meta_query' => [['key' => '_sst_manual_translation', 'value' => '1']],
    ]);
    $translated_posts = get_posts([
        'post_type' => $post_types, 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids',
        'meta_key' => '_sst_es_post_id',
    ]);
    $total = count($all_posts);
    $manual = count($manual_posts);
    $synced = count($translated_posts);
    $pending = $total - $manual - ($synced - $manual);
    ?>
    <div class="wrap">
        <h1>🇪🇸 Smart Spanish Translator</h1>

        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin:20px 0;">
            <?php
            foreach ([
        ['Total Pages/Posts', $total, '#2271b1'],
        ['Manually Translated', $manual, '#9b59b6'],
        ['Auto-Translated', $synced, '#27ae60'],
        ['Pending', max(0, $total - $synced - $manual), '#e67e22'],
            ] as [$label, $val, $color]):
                ?>
                <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:16px;text-align:center;border-top:4px solid <?php echo $color; ?>;">
                    <div style="font-size:28px;font-weight:700;color:<?php echo $color; ?>;"><?php echo $val; ?></div>
                    <div style="color:#666;font-size:13px;"><?php echo $label; ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px;margin-bottom:20px;">
            <h2 style="margin-top:0;">Bulk Actions</h2>
            <p style="color:#555;">Translate all non-manual pages/posts that haven't been translated yet. Pages marked as <strong>Manually Translated</strong> will be skipped.</p>
            <button class="button button-primary" id="sst-bulk-btn" onclick="sstBulkTranslate(this, '<?php echo $nonce; ?>')">
                Translate All Pending
            </button>
            <span id="sst-bulk-result" style="margin-left:12px;color:#27ae60;font-weight:600;"></span>
        </div>

        <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px;">
            <h2 style="margin-top:0;">All Content</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Manual?</th>
                        <th>Translated?</th>
                        <th>Last Synced</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $posts = get_posts([
                        'post_type' => $post_types,
                        'post_status' => 'publish',
                        'posts_per_page' => 50,
                        'meta_query' => [['key' => '_sst_is_translation', 'compare' => 'NOT EXISTS']],
                    ]);
                    foreach ($posts as $p):
                        $is_manual = get_post_meta($p->ID, '_sst_manual_translation', true) === '1';
                        $es_post_id = get_post_meta($p->ID, '_sst_es_post_id', true);
                        $last_synced = get_post_meta($p->ID, '_sst_last_synced', true);
                        ?>
                        <tr>
                            <td><a href="<?php echo get_edit_post_link($p->ID); ?>"><?php echo esc_html($p->post_title); ?></a></td>
                            <td><?php echo esc_html($p->post_type); ?></td>
                            <td><?php echo $is_manual ? '<span style="color:#9b59b6;">✓ Manual</span>' : '—'; ?></td>
                            <td><?php echo $es_post_id ? '<span style="color:#27ae60;">✓ Yes</span>' : '<span style="color:#e67e22;">No</span>'; ?></td>
                            <td style="font-size:12px;color:#888;"><?php echo $last_synced ? human_time_diff(strtotime($last_synced)) . ' ago' : '—'; ?></td>
                            <td>
                                <?php if ($es_post_id): ?>
                                    <a href="<?php echo get_edit_post_link($es_post_id); ?>" style="font-size:12px;">Edit ES</a> |
                                <?php endif; ?>
                                <?php if (!$is_manual): ?>
                                    <button class="button button-small" onclick="sstTranslate(<?php echo $p->ID; ?>, false, this, '<?php echo $nonce; ?>')">Translate</button>
                                <?php else: ?>
                                    <button class="button button-small" onclick="sstTranslate(<?php echo $p->ID; ?>, true, this, '<?php echo $nonce; ?>')" style="border-color:#c00;color:#c00;">Force</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function sstTranslate(postId, force, btn, nonce) {
            btn.textContent = '…';
            btn.disabled = true;
            fetch(ajaxurl, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({action: 'sst_translate_single', post_id: postId, force: force ? '1' : '0', nonce})
            }).then(r => r.json()).then(d => {
                btn.textContent = d.success ? '✓' : '✗';
                setTimeout(() => location.reload(), 1500);
            });
        }

        function sstBulkTranslate(btn, nonce) {
            btn.textContent = 'Translating… (this may take a while)';
            btn.disabled = true;
            fetch(ajaxurl, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({action: 'sst_bulk_translate', nonce})
            }).then(r => r.json()).then(d => {
                btn.textContent = 'Done!';
                document.getElementById('sst-bulk-result').textContent =
                        d.success ? `Translated ${d.data.translated} of ${d.data.total} posts.` : 'Error: ' + d.data;
                setTimeout(() => location.reload(), 2000);
            });
        }
    </script>
    <?php
}

function sst_settings_page() {
    $provider = get_option('sst_api_provider', 'claude');
    $api_key = get_option('sst_api_key', '');
    $deepl_api_key = get_option('sst_deepl_api_key', '');
    $auto = get_option('sst_auto_translate', false);
    $post_types = get_option('sst_post_types', ['post', 'page']);
    $all_types = get_post_types(['public' => true], 'objects');
    $menu_location = get_option('sst_menu_location', 'primary');
    $url_prefix = get_option('sst_url_prefix', 'es');
    $nav_locations = get_registered_nav_menus(); // all registered locations from theme
    ?>
    <div class="wrap">
        <h1>Spanish Translator — Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('sst_options'); ?>
            <table class="form-table">
                <tr>
                    <th>API Provider</th>
                    <td>
                        <select name="sst_api_provider" id="sst_provider_select" onchange="sstToggleKeyFields(this.value)">
                            <option value="claude" <?php selected($provider, 'claude'); ?>>Claude (Anthropic)</option>
                            <option value="openai" <?php selected($provider, 'openai'); ?>>OpenAI (GPT-4o)</option>
                            <option value="deepl" <?php selected($provider, 'deepl'); ?>>DeepL</option>
                        </select>
                        <p class="description">DeepL gives the most natural Spanish translations. Claude and OpenAI are better for preserving complex HTML layouts.</p>
                    </td>
                </tr>
                <tr id="sst_row_claude_openai" <?php echo $provider === 'deepl' ? 'style="display:none"' : ''; ?>>
                    <th>Claude / OpenAI API Key</th>
                    <td>
                        <input type="password" name="sst_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text">
                        <p class="description">Required if using Claude (Anthropic) or OpenAI as the provider.</p>
                    </td>
                </tr>
                <tr id="sst_row_deepl" <?php echo $provider !== 'deepl' ? 'style="display:none"' : ''; ?>>
                    <th>DeepL API Key</th>
                    <td>
                        <input type="password" name="sst_deepl_api_key" value="<?php echo esc_attr($deepl_api_key); ?>" class="regular-text">
                        <p class="description">
                            Get your key at <a href="https://www.deepl.com/pro-api" target="_blank">deepl.com/pro-api</a>.
                            Free tier available (500,000 chars/month). Free keys end in <code>:fx</code> — the plugin detects this automatically.
                        </p>
                    </td>
                </tr>

                <tr>
                    <th>URL Prefix</th>
                    <td>
                        <code><?php echo esc_html(home_url('/')); ?></code>
                        <input type="text" name="sst_url_prefix" value="<?php echo esc_attr($url_prefix); ?>"
                               style="width:80px;" placeholder="es">
                        <code>/page-slug/</code>
                        <p class="description">
                            The folder prefix for all translated URLs. Default is <code>es</code> → <code>/es/about/</code>.
                            You could use <code>espanol</code>, <code>es-mx</code>, etc.<br>
                            <strong>After changing this, go to Settings → Permalinks and click Save Changes</strong> to flush rewrite rules.
                        </p>
                    </td>
                </tr>

                <tr>
                    <th>Language Switcher Menu</th>
                    <td>
                        <?php if (!empty($nav_locations)): ?>
                            <select name="sst_menu_location">
                                <?php foreach ($nav_locations as $loc => $desc): ?>
                                    <option value="<?php echo esc_attr($loc); ?>" <?php selected($menu_location, $loc); ?>>
                                        <?php echo esc_html($desc); ?> (<code><?php echo esc_html($loc); ?></code>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">The nav menu where the EN/ES dropdown will appear.</p>
                        <?php else: ?>
                            <input type="text" name="sst_menu_location" value="<?php echo esc_attr($menu_location); ?>" class="regular-text" placeholder="primary">
                            <p class="description">Enter your theme's menu location slug (e.g. <code>primary</code>, <code>main</code>, <code>header-menu</code>).</p>
                        <?php endif; ?>
                    </td>
                </tr>

                <tr>
                    <th>Auto-translate on Save</th>
                    <td>
                        <label>
                            <input type="checkbox" name="sst_auto_translate" value="1" <?php checked($auto); ?>>
                            Automatically translate when a post is published or updated
                        </label>
                    </td>
                </tr>
                <tr>
                    <th>Post Types to Translate</th>
                    <td>
                        <?php foreach ($all_types as $pt): if ($pt->name === 'attachment') continue; ?>
                            <label style="display:block;margin-bottom:4px;">
                                <input type="checkbox" name="sst_post_types[]" value="<?php echo esc_attr($pt->name); ?>"
                                       <?php checked(in_array($pt->name, $post_types)); ?>>
                                <?php echo esc_html($pt->label); ?> (<code><?php echo esc_html($pt->name); ?></code>)
                            </label>
                        <?php endforeach; ?>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Settings'); ?>
        </form>
        <script>
            function sstToggleKeyFields(provider) {
                document.getElementById('sst_row_claude_openai').style.display = provider === 'deepl' ? 'none' : '';
                document.getElementById('sst_row_deepl').style.display = provider === 'deepl' ? '' : 'none';
            }
        </script>
    </div>
    <?php
}

// ─── H1 / Banner Title Filter ─────────────────────────────────────────────────

/**
 * When viewing a translated Spanish page, filter the_title() so the H1/banner
 * shows the translated title rather than the raw post_title fallback some
 * themes use independently of post_content.
 */
add_filter('the_title', function ($title, $post_id) {
    if (!is_admin() && get_post_meta($post_id, '_sst_is_translation', true) === '1') {
        $translated = get_post_meta($post_id, '_sst_translated_title', true);
        if ($translated)
            return $translated;
    }
    return $title;
}, 10, 2);

/**
 * Also filter wp_title and document <title> for SEO / browser tab.
 */
add_filter('wp_title', function ($title) {
    if (is_singular()) {
        $post_id = get_queried_object_id();
        if (get_post_meta($post_id, '_sst_is_translation', true) === '1') {
            $translated = get_post_meta($post_id, '_sst_translated_title', true);
            if ($translated)
                return $translated . ' ';
        }
    }
    return $title;
});

// ─── Language Switcher ────────────────────────────────────────────────────────

/**
 * Inject the language switcher dropdown into the primary nav menu.
 * Figures out the current page's counterpart (EN↔ES) and links to it.
 */
add_filter('wp_nav_menu_items', 'sst_add_language_switcher', 10, 2);

function sst_add_language_switcher($items, $args) {
    $target_location = get_option('sst_menu_location', 'primary');
    if (!isset($args->theme_location) || $args->theme_location !== $target_location) {
        return $items;
    }

    //$current_post_id    = get_queried_object_id();
    // Blog page special case
    if (is_home() && !is_front_page()) {
        $current_post_id = (int) get_option('page_for_posts');
    } else {
        $current_post_id = get_queried_object_id();
    }

    $manual_lang = get_post_meta($current_post_id, 'current_page_language', true);
    $is_es = get_post_meta($current_post_id, '_sst_is_translation', true) === '1';
    if ($manual_lang) {
        $is_es = (strtolower($manual_lang) === 'spanish');
    }

    if ($is_es) {
        // We're on a Spanish page — link back to the English source
        $source_id = get_post_meta($current_post_id, '_sst_source_post_id', true);
        $en_url = $source_id ? get_permalink($source_id) : home_url('/');
        $es_url = get_permalink($current_post_id);
        $active_lang = 'es';
    } else {
        // We're on an English page — link to the Spanish version if it exists
        $es_post_id = get_post_meta($current_post_id, '_sst_es_post_id', true);
        $en_url = get_permalink($current_post_id) ?: home_url('/');
        $es_url = $es_post_id ? get_permalink($es_post_id) : null;
        $active_lang = 'en';
    }

    // Apply Related Lang URL override instead of hiding the dropdown
    $custom_related_url = get_post_meta($current_post_id, 'related_lang_url', true);
    if (!empty($custom_related_url)) {
        if (is_array($custom_related_url) && isset($custom_related_url['url'])) {
            $custom_related_url = $custom_related_url['url'];
        }
        if (is_string($custom_related_url) && !empty($custom_related_url)) {
            if ($active_lang === 'en') {
                $es_url = $custom_related_url;
            } else {
                $en_url = $custom_related_url;
            }
        }
    }

    $en_label = __('English', 'smart-spanish-translator');
    $es_label = __('Español', 'smart-spanish-translator');
    $active_label = $active_lang === 'en' ? $en_label : $es_label;
    $has_es = !empty($es_url);

    ob_start();
    ?>
    <li class="menu-item sst-language-switcher<?php echo!$has_es ? ' sst-no-translation' : ''; ?>">
        <button class="sst-lang-btn" aria-haspopup="true" aria-expanded="false">
            <span class="sst-lang-flag"><?php echo $active_lang === 'en' ? '🇺🇸' : '🇪🇸'; ?></span>
            <span class="sst-lang-label"><?php echo esc_html($active_label); ?></span>
            <span class="sst-lang-caret" aria-hidden="true">▾</span>
        </button>
        <ul class="sst-lang-dropdown" role="menu">
            <li role="menuitem" class="<?php echo $active_lang === 'en' ? 'sst-active' : ''; ?>">
                <a href="<?php echo esc_url($en_url); ?>">
                    🇺🇸 <?php echo esc_html($en_label); ?>
                    <?php if ($active_lang === 'en') echo '<span class="sst-check">✓</span>'; ?>
                </a>
            </li>
            <li role="menuitem" class="<?php echo $active_lang === 'es' ? 'sst-active' : ''; ?>">
                <?php if ($has_es): ?>
                    <a href="<?php echo esc_url($es_url); ?>">
                        🇪🇸 <?php echo esc_html($es_label); ?>
                        <?php if ($active_lang === 'es') echo '<span class="sst-check">✓</span>'; ?>
                    </a>
                <?php else: ?>
                    <span class="sst-unavailable">🇪🇸 <?php echo esc_html($es_label); ?> <em>(not yet translated)</em></span>
                <?php endif; ?>
            </li>
        </ul>
    </li>
    <?php
    $switcher = ob_get_clean();

    return $items . $switcher;
}

/**
 * Enqueue the language switcher styles and JS.
 */
add_action('wp_enqueue_scripts', function () {
    wp_add_inline_style('wp-block-library', sst_switcher_css());
    wp_add_inline_script('jquery', sst_switcher_js());
});

function sst_switcher_css() {
    return '
    .sst-language-switcher { position: relative; display: flex; align-items: center; }

    .sst-lang-btn {
        display: flex;
        align-items: center;
        gap: 5px;
        background: none;
        border: 1px solid currentColor;
        border-radius: 4px;
        padding: 5px 10px;
        cursor: pointer;
        font-size: 14px;
        color: inherit;
        font-family: inherit;
        line-height: 1;
        opacity: 0.85;
        transition: opacity 0.15s;
    }
    .sst-lang-btn:hover { opacity: 1; }
    .sst-lang-caret { font-size: 10px; margin-left: 2px; transition: transform 0.2s; }
    .sst-lang-btn[aria-expanded="true"] .sst-lang-caret { transform: rotate(180deg); }

    .sst-lang-dropdown {
        display: none;
        position: absolute;
        top: calc(100% + 6px);
        right: 0;
        min-width: 170px;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 6px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.12);
        list-style: none;
        margin: 0;
        padding: 4px 0;
        z-index: 9999;
    }
    .sst-lang-dropdown.sst-open { display: block; }

    .sst-lang-dropdown li a,
    .sst-lang-dropdown li .sst-unavailable {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 9px 14px;
        color: #333;
        text-decoration: none;
        font-size: 14px;
        gap: 8px;
        white-space: nowrap;
    }
    .sst-lang-dropdown li a:hover { background: #f5f5f5; }
    .sst-lang-dropdown li.sst-active a { font-weight: 600; color: #000; }
    .sst-lang-dropdown .sst-check { color: #2271b1; font-size: 12px; margin-left: auto; }
    .sst-lang-dropdown .sst-unavailable { color: #999; cursor: default; }
    .sst-lang-dropdown .sst-unavailable em { font-size: 11px; }
    ';
}

function sst_switcher_js() {
    return '
    document.addEventListener("DOMContentLoaded", function() {
        var btn = document.querySelector(".sst-lang-btn");
        var dropdown = document.querySelector(".sst-lang-dropdown");
        if (!btn || !dropdown) return;

        btn.addEventListener("click", function(e) {
            e.stopPropagation();
            var open = dropdown.classList.toggle("sst-open");
            btn.setAttribute("aria-expanded", open ? "true" : "false");
        });

        document.addEventListener("click", function() {
            dropdown.classList.remove("sst-open");
            btn.setAttribute("aria-expanded", "false");
        });

        // Keyboard: close on Escape
        document.addEventListener("keydown", function(e) {
            if (e.key === "Escape") {
                dropdown.classList.remove("sst-open");
                btn.setAttribute("aria-expanded", "false");
            }
        });
    });
    ';
}

// ─── /es/ URL Rewrite Rules ───────────────────────────────────────────────────

/**
 * Register rewrite rules so /es/slug/ resolves to the Spanish post.
 * Works for pages, posts, and custom post types.
 */
add_action('init', function () {
    $prefix = sst_url_prefix();
    $p = preg_quote($prefix, '/');

    add_rewrite_rule(
            "^{$p}/blog/([^/]+)/?$",
            'index.php?post_type=es_blog&name=$matches[1]',
            'top'
    );

    // Simple: /es/slug/
    add_rewrite_rule("^{$p}/([^/]+)/?$", 'index.php?sst_es_slug=$matches[1]', 'top');

    // Date-based posts: /es/2024/01/slug/ or /es/2024/01/30/slug/
    add_rewrite_rule("^{$p}/[0-9]{4}/[0-9]{2}/([^/]+)/?$", 'index.php?sst_es_slug=$matches[1]', 'top');
    add_rewrite_rule("^{$p}/[0-9]{4}/[0-9]{2}/[0-9]{2}/([^/]+)/?$", 'index.php?sst_es_slug=$matches[1]', 'top');

    // Nested: /es/parent/slug/
    add_rewrite_rule("^{$p}/([^/]+)/([^/]+)/?$", 'index.php?sst_es_slug=$matches[2]', 'top');
}, 5);

add_filter('query_vars', function ($vars) {
    $vars[] = 'sst_es_slug';
    return $vars;
});

/**
 * When WordPress processes a request with sst_es_slug,
 * find the matching Spanish post and serve it.
 */
add_action('parse_request', function ($wp) {
    if (empty($wp->query_vars['sst_es_slug']))
        return;

    $slug = sanitize_title($wp->query_vars['sst_es_slug']);

    $cpt_check = get_posts([
        'name' => $slug,
        'post_status' => 'publish',
        'post_type' => 'es_blog',
        'posts_per_page' => 1,
    ]);
    if (!empty($cpt_check)) {
        unset($wp->query_vars['sst_es_slug']);
        return;
    }
    // Find the post with this slug that is an auto-translation
    $posts = get_posts([
        'name' => $slug,
        'post_status' => 'publish',
        'post_type' => 'any',
        'posts_per_page' => 1,
        'meta_query' => [['key' => '_sst_is_translation', 'value' => '1']],
    ]);

    if (empty($posts))
        return; // fall through to 404

    $post = $posts[0];
    $wp->query_vars = [];

    //    $wp->query_vars['p']         = $post->ID;
    //    $wp->query_vars['post_type'] = $post->post_type;

    if ($post->post_type === 'page') {
        $wp->query_vars['page_id'] = $post->ID;
    } elseif ($post->post_type === 'post') {
        $wp->query_vars['p'] = $post->ID;
    } else {
        $post_type_obj = get_post_type_object($post->post_type);
        $query_var = $post_type_obj ? $post_type_obj->query_var : false;

        if ($query_var) {
            $wp->query_vars[$query_var] = $post->post_name;
        } else {
            $wp->query_vars['name'] = $post->post_name;
            $wp->query_vars['post_type'] = $post->post_type;
        }
    }
});

/**
 * Filter the permalink of Spanish translation posts to use /es/{slug}/
 */
add_filter('post_link', 'sst_filter_es_permalink', 10, 2);
add_filter('page_link', 'sst_filter_es_permalink', 10, 2);
add_filter('post_type_link', 'sst_filter_es_permalink', 10, 2);

function sst_filter_es_permalink($url, $post_or_id) {
    $post_id = is_object($post_or_id) ? $post_or_id->ID : (int) $post_or_id;
    if (!$post_id)
        return $url;

    $is_translation = get_post_meta($post_id, '_sst_is_translation', true);
    if ($is_translation !== '1')
        return $url;

    $slug = get_post_field('post_name', $post_id);
    $prefix = sst_url_prefix();
    return home_url('/' . $prefix . '/' . $slug . '/');
}

//Auto-set SST meta when ACF related_lang_url is saved
add_action('acf/save_post', function ($post_id) {

    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id))
        return;

    $lang = get_field('current_page_language', $post_id);
    if (!$lang || strtolower($lang) !== 'spanish')
        return;

    $related_url = get_field('related_lang_url', $post_id);
    if (empty($related_url))
        return;

    update_post_meta($post_id, '_sst_is_translation', '1');

    $source_post_id = url_to_postid($related_url);
    if ($source_post_id) {
        update_post_meta($post_id, '_sst_source_post_id', $source_post_id);

        update_post_meta($source_post_id, '_sst_es_post_id', $post_id);

        $template = get_post_meta($source_post_id, '_wp_page_template', true);
        if (!empty($template)) {
            update_post_meta($post_id, '_wp_page_template', $template);
        }
    }
}, 20);

// ─── Flush rewrite rules on activation/deactivation ──────────────────────────

register_activation_hook(__FILE__, function () {
    $prefix = sst_url_prefix();
    add_rewrite_rule('^' . preg_quote($prefix, '/') . '/([^/]+)/?$', 'index.php?sst_es_slug=$matches[1]', 'top');
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});

// ─── ACF HrefLang Source Local Field Group ────────────────────────────────────
add_action('acf/init', function() {
    if (!function_exists('acf_add_local_field_group')) return;

    $post_types = get_option('sst_post_types', ['post', 'page']);
    if (!is_array($post_types)) $post_types = ['post', 'page'];
    $locations = [];
    foreach ($post_types as $pt) {
        $locations[] = [
            [
                'param' => 'post_type',
                'operator' => '==',
                'value' => $pt,
            ]
        ];
    }

    acf_add_local_field_group(array(
        'key' => 'group_sst_plugin_hreflang',
        'title' => 'HrefLang Source',
        'fields' => array(
            array(
                'key' => 'field_sst_plugin_lang',
                'label' => 'Current Page Language',
                'name' => 'current_page_language',
                'type' => 'radio',
                'choices' => array(
                    'English' => 'English',
                    'Spanish' => 'Spanish',
                ),
                'default_value' => 'English',
                'layout' => 'vertical',
                'return_format' => 'value',
            ),
            array(
                'key' => 'field_sst_plugin_url',
                'label' => 'Related Lang URL',
                'name' => 'related_lang_url',
                'type' => 'link',
                'instructions' => 'If an existing Spanish or English page link is added here, it will overwrite the automatic page on the dropdown.',
                'return_format' => 'url',
            ),
        ),
        'location' => $locations,
        'menu_order' => 0,
        'position' => 'side',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'active' => true,
    ));
});
