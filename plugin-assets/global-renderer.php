<?php

/**
 * FAQ Global Renderer - Frontend Widget
 * Brilliant Directories Plugin
 * 
 * Auto-detects current page and renders appropriate FAQs
 * Generates JSON-LD schema and HTML accordion
 */

// Get database connection
$db = brilliantDirectories::getDatabaseConfiguration('database');

if (!$db) {
    // Database connection failed, don't render anything
    return;
}

// ============================================
// PAGE DETECTION
// ============================================
$detected_filename = '';
$detected_data_id = null;
$detected_page_type = null;

if (!empty($page['filename'])) {
    $detected_filename = $page['filename'];
}

// Check for post type pages (data_id and page_type from BD context)
if (isset($dc) && !empty($dc['data_id'])) {
    $detected_data_id = intval($dc['data_id']);

    // Determine page type based on context
    // If we're on a detail page, there should be a specific post/item
    // Otherwise, it's a search result page
    if (isset($pars) && !empty($pars[3])) {
        $detected_page_type = 'detail_page';
    } else {
        $detected_page_type = 'search_result_page';
    }
}

if (empty($detected_filename) && empty($detected_data_id)) {
    // No page detected, don't render anything
    return;
}

// ============================================
// ASSIGNMENT LOOKUP (Multiple Groups Per Page)
// ============================================
$assignments = array();

// Try static page first
if (!empty($detected_filename)) {
    if (function_exists('mysql_real_escape_string') && !is_string($db)) {
        $escaped_filename = mysql_real_escape_string($detected_filename, $db);
    } else {
        $escaped_filename = addslashes($detected_filename);
    }

    $page_sql = "SELECT seo_id FROM list_seo WHERE filename = '$escaped_filename' LIMIT 1";
    if (function_exists('mysql') && is_string($db)) {
        $page_result = mysql($db, $page_sql);
    } else {
        $page_result = mysql_query($page_sql, $db);
    }

    $page_id = null;
    if ($page_result && mysql_num_rows($page_result) > 0) {
        $page_row = mysql_fetch_assoc($page_result);
        $page_id = intval($page_row['seo_id']);
    }

    if ($page_id) {
        // Fetch all assigned groups for this static page, ordered by faq_orders
        $assignments_sql = "SELECT g.id as group_id, g.group_slug, g.group_name, 
                                    a.custom_label, a.custom_title, a.custom_subtitle,
                                    a.cta_title, a.cta_text, a.cta_email,
                                    COALESCE(a.show_title, 1) as show_title,
                                    COALESCE(a.merge_groups, 0) as merge_groups,
                                    COALESCE(o.sort_order, 999) as sort_order,
                                    a.page_id,
                                    o.id as order_id
                                FROM faq_page_assignments a
                                JOIN faq_groups g ON a.group_id = g.id
                                LEFT JOIN faq_orders o ON a.page_id = o.page_id AND a.group_id = o.group_id
                                WHERE a.page_id = $page_id
                                ORDER BY sort_order ASC, a.id ASC";

        if (function_exists('mysql') && is_string($db)) {
            $assignments_result = mysql($db, $assignments_sql);
        } else {
            $assignments_result = mysql_query($assignments_sql, $db);
        }

        if ($assignments_result && mysql_num_rows($assignments_result) > 0) {
            while ($ass = mysql_fetch_assoc($assignments_result)) {
                $assignments[] = $ass;
            }
        }
    }
}

// Try post type page if no static page assignments found
if (empty($assignments) && !empty($detected_data_id) && !empty($detected_page_type)) {
    if (function_exists('mysql_real_escape_string') && !is_string($db)) {
        $escaped_page_type = mysql_real_escape_string($detected_page_type, $db);
    } else {
        $escaped_page_type = addslashes($detected_page_type);
    }

    // Fetch all assigned groups for this post type page, ordered by faq_orders
    $assignments_sql = "SELECT g.id as group_id, g.group_slug, g.group_name, 
                                a.custom_label, a.custom_title, a.custom_subtitle,
                                a.cta_title, a.cta_text, a.cta_email,
                                COALESCE(a.show_title, 1) as show_title,
                                COALESCE(a.merge_groups, 0) as merge_groups,
                                COALESCE(o.sort_order, 999) as sort_order
                            FROM faq_page_assignments a
                            JOIN faq_groups g ON a.group_id = g.id
                            LEFT JOIN faq_orders o ON a.data_id = o.data_id AND a.page_type = o.page_type AND a.group_id = o.group_id
                            WHERE a.data_id = $detected_data_id AND a.page_type = '$escaped_page_type'
                            ORDER BY sort_order ASC, a.id ASC";
    if (function_exists('mysql') && is_string($db)) {
        $assignments_result = mysql($db, $assignments_sql);
    } else {
        $assignments_result = mysql_query($assignments_sql, $db);
    }

    if ($assignments_result && mysql_num_rows($assignments_result) > 0) {
        while ($ass = mysql_fetch_assoc($assignments_result)) {
            $assignments[] = $ass;
        }
    }
}

// Build scope for per-page design overrides (used after we know we have assignments)
if (!empty($assignments)) {
    if ($page_id) {
        $current_page_scope = 'page_' . $page_id;
    } elseif (!empty($detected_data_id) && !empty($detected_page_type)) {
        $current_page_scope = 'post_' . intval($detected_data_id) . '_' . preg_replace('/[^a-z0-9_]/', '', $detected_page_type);
    }
}

// If no assignments found, don't render anything
// FAQs will only show if explicitly assigned to a page via Page Assignment tab
if (empty($assignments)) {
    return;
}

// ============================================
// FETCH QUESTIONS FOR EACH GROUP
// ============================================
$groups_with_questions = array();
$merge_groups_enabled = !empty($assignments);
$merged_questions = array();
$merged_group_info = null;

// Merge only when ALL assignment rows on this page are explicitly marked as merged.
// This avoids accidental "everything collapsed into one design" when rows are mixed.
foreach ($assignments as $assignment) {
    if (!isset($assignment['merge_groups']) || intval($assignment['merge_groups']) !== 1) {
        $merge_groups_enabled = false;
        break;
    }
}
if ($merge_groups_enabled && !empty($assignments)) {
    $merged_group_info = $assignments[0];
}

foreach ($assignments as $assignment) {
    $group_id = intval($assignment['group_id']);
    $group_sort_order = isset($assignment['sort_order']) ? intval($assignment['sort_order']) : 999;

    $questions_sql = "SELECT q.*, gq.sort_order, g.group_name as group_name
                        FROM faq_questions q
                        JOIN faq_group_questions gq ON q.id = gq.question_id
                        JOIN faq_groups g ON gq.group_id = g.id
                        WHERE gq.group_id = $group_id
                        ORDER BY gq.sort_order ASC, q.id ASC";
    if (function_exists('mysql') && is_string($db)) {
        $questions_result = mysql($db, $questions_sql);
    } else {
        $questions_result = mysql_query($questions_sql, $db);
    }

    $questions = array();
    if ($questions_result && mysql_num_rows($questions_result) > 0) {
        while ($q = mysql_fetch_assoc($questions_result)) {
            $questions[] = $q;
        }
    }

    // If merge mode is enabled, collect all questions together with group order
    if ($merge_groups_enabled && !empty($questions)) {
        foreach ($questions as $q) {
            $q['source_group'] = $assignment['group_name'];
            $q['group_sort_order'] = $group_sort_order; // Add group order for later sorting
            $merged_questions[] = $q;
        }
    } elseif (!empty($questions)) {
        // Only include groups that have questions (separate mode)
        $groups_with_questions[] = array(
            'group' => $assignment,
            'questions' => $questions
        );
    }
}

// If merge mode is enabled, create a single merged group
// Note: Multiple database rows exist (one per group) but frontend renders as single section
// Uses the first merged row's title/subtitle for display
if ($merge_groups_enabled && !empty($merged_questions)) {
    // Sort merged questions by:
    // 1. Group order (from faq_orders table)
    // 2. Question order within each group (sort_order)
    // 3. Question ID as final tiebreaker
    usort($merged_questions, function ($a, $b) {
        // First, sort by group order
        $group_order_a = isset($a['group_sort_order']) ? intval($a['group_sort_order']) : 999;
        $group_order_b = isset($b['group_sort_order']) ? intval($b['group_sort_order']) : 999;

        if ($group_order_a != $group_order_b) {
            return $group_order_a - $group_order_b;
        }

        // If same group order, sort by question order within group
        $question_order_a = isset($a['sort_order']) ? intval($a['sort_order']) : 999;
        $question_order_b = isset($b['sort_order']) ? intval($b['sort_order']) : 999;

        if ($question_order_a != $question_order_b) {
            return $question_order_a - $question_order_b;
        }

        // If same question order, sort by ID
        return intval($a['id']) - intval($b['id']);
    });

    $groups_with_questions = array(array(
        'group' => $merged_group_info,  // Uses first merged row's assignment info
        'questions' => $merged_questions
    ));
}

// If no groups have questions, don't render
if (empty($groups_with_questions)) {
    return;
}

// ============================================
// GET DESIGN SETTINGS
// ============================================
// Default CDN URL provided by plugin owner (for automatic updates via API)
// Users can override this with their own CDN URL if needed
if (!defined('FAQ_OWNER_CDN_URL')) {
    define('FAQ_OWNER_CDN_URL', 'https://cdn.bdgrowthsuite.com'); // Change this to your actual CDN URL
}

$design_preset = 'modern';
$cdn_base_url = '';

// Get all design settings
$design_settings_sql = "SELECT setting_key, setting_value FROM faq_design_settings";
if (function_exists('mysql') && is_string($db)) {
    $design_settings_result = mysql($db, $design_settings_sql);
} else {
    $design_settings_result = mysql_query($design_settings_sql, $db);
}

$customization_settings = array();
if ($design_settings_result && mysql_num_rows($design_settings_result) > 0) {
    while ($setting = mysql_fetch_assoc($design_settings_result)) {
        $key = $setting['setting_key'];
        $value = $setting['setting_value'];

        if ($key == 'design_preset') {
            $design_preset = $value;
        } elseif ($key == 'cdn_base_url') {
            $cdn_base_url = trim($value);
        } else {
            $customization_settings[$key] = $value;
        }
    }
}

// Per-page design overrides: merge in overrides for current page scope (overrides replace global)
if (!empty($current_page_scope)) {
    $check_table = "SHOW TABLES LIKE 'faq_page_design_overrides'";
    if (function_exists('mysql') && is_string($db)) {
        $table_check = mysql($db, $check_table);
    } else {
        $table_check = mysql_query($check_table, $db);
    }
    if ($table_check && mysql_num_rows($table_check) > 0) {
        if (function_exists('mysql_real_escape_string') && !is_string($db)) {
            $scope_esc = mysql_real_escape_string($current_page_scope, $db);
        } else {
            $scope_esc = addslashes($current_page_scope);
        }
        $overrides_sql = "SELECT setting_key, setting_value FROM faq_page_design_overrides WHERE scope = '$scope_esc'";
        if (function_exists('mysql') && is_string($db)) {
            $overrides_result = mysql($db, $overrides_sql);
        } else {
            $overrides_result = mysql_query($overrides_sql, $db);
        }
        if ($overrides_result && mysql_num_rows($overrides_result) > 0) {
            while ($row = mysql_fetch_assoc($overrides_result)) {
                $customization_settings[$row['setting_key']] = $row['setting_value'];
                if ($row['setting_key'] === 'design_preset') {
                    $design_preset = $row['setting_value'];
                }
            }
        }
    }
}

// Use owner's default CDN if no custom CDN is set
if (empty($cdn_base_url)) {
    $cdn_base_url = FAQ_OWNER_CDN_URL;
}

// Simple function to get website design color defaults
if (!function_exists('getWebsiteColorDefaults')) {
    function getWebsiteColorDefaults($db)
    {
        $defaults = array(
            'primary_color' => '#1e3a8a',
            'background_color' => '#ffffff',
            'card_background_color' => '#ffffff',
            'text_color' => '#1f2937'
        );

        if (!$db) {
            return $defaults;
        }

        // Mapping: custom_62 = primary, custom_1 = background, custom_2 = text, custom_5 = card background
        $color_map = array(
            'custom_62' => 'primary_color',
            'custom_1' => 'background_color',
            'custom_2' => 'text_color',
            'custom_5' => 'card_background_color'
        );

        foreach ($color_map as $custom_key => $setting_key) {
            $sql = "SELECT setting_value FROM website_design_settings WHERE setting_name = '" . addslashes($custom_key) . "' AND layout_group = 'default_layout' LIMIT 1";

            if (function_exists('mysql') && is_string($db)) {
                $result = mysql($db, $sql);
            } else if (function_exists('mysql_query')) {
                $result = mysql_query($sql, $db);
            } else {
                continue;
            }

            if ($result && function_exists('mysql_num_rows') && mysql_num_rows($result) > 0) {
                $row = mysql_fetch_assoc($result);
                if ($row && !empty($row['setting_value'])) {
                    $color_value = trim($row['setting_value']);
                    $original_value = $color_value;
                    $converted = false;

                    // Method 1: Use sscanf for rgb(r, g, b) - most reliable
                    if (stripos($color_value, 'rgb') !== false) {
                        $r = $g = $b = 0;
                        $scan_result = sscanf($color_value, "rgb(%d, %d, %d)", $r, $g, $b);

                        if ($scan_result >= 3 && $r !== null && $g !== null && $b !== null) {
                            $color_value = sprintf('#%02x%02x%02x', $r, $g, $b);
                            $converted = true;
                        } else {
                            // Fallback: Use explode
                            $clean = preg_replace('/[^0-9,]/', '', $original_value);
                            $parts = explode(',', $clean);

                            if (count($parts) >= 3) {
                                $r = intval(trim($parts[0]));
                                $g = intval(trim($parts[1]));
                                $b = intval(trim($parts[2]));
                                if ($r >= 0 && $r <= 255 && $g >= 0 && $g <= 255 && $b >= 0 && $b <= 255) {
                                    $color_value = sprintf('#%02x%02x%02x', $r, $g, $b);
                                    $converted = true;
                                }
                            }
                        }
                    }
                    // Method 2: Already hex format
                    else if (preg_match('/^#?[0-9a-fA-F]{6}$/', $color_value)) {
                        $color_value = '#' . ltrim($color_value, '#');
                        $converted = true;
                    }

                    // Validate and set
                    if ($converted && !empty($color_value) && preg_match('/^#[0-9a-fA-F]{6}$/', $color_value)) {
                        $defaults[$setting_key] = $color_value;
                    }
                }
            }
        }

        return $defaults;
    }
}

if (!function_exists('faq_renderer_parse_color_rgb')) {
    function faq_renderer_parse_color_rgb($color)
    {
        $color = trim((string) $color);
        if ($color === '' || strtolower($color) === 'transparent') return null;

        if (preg_match('/^#?([0-9a-fA-F]{6})$/', $color, $m)) {
            $hex = $m[1];
            return array(
                'r' => hexdec(substr($hex, 0, 2)),
                'g' => hexdec(substr($hex, 2, 2)),
                'b' => hexdec(substr($hex, 4, 2))
            );
        }

        if (preg_match('/^rgba?\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*(?:,\s*([\d.]+)\s*)?\)/i', $color, $m)) {
            if (isset($m[4]) && $m[4] !== '' && floatval($m[4]) < 0.45) {
                return null;
            }
            return array(
                'r' => max(0, min(255, intval($m[1]))),
                'g' => max(0, min(255, intval($m[2]))),
                'b' => max(0, min(255, intval($m[3])))
            );
        }

        return null;
    }
}

if (!function_exists('faq_renderer_contrast_text_color')) {
    function faq_renderer_contrast_text_color($background_color, $light = '#ffffff', $dark = '#111827')
    {
        $rgb = faq_renderer_parse_color_rgb($background_color);
        if (!is_array($rgb) || !isset($rgb['r'])) {
            return $dark;
        }
        $yiq = (($rgb['r'] * 299) + ($rgb['g'] * 587) + ($rgb['b'] * 114)) / 1000;
        return ($yiq >= 150) ? $dark : $light;
    }
}

// Get website color defaults
$website_color_defaults = getWebsiteColorDefaults($db);

// Load customization settings with defaults
$design_style = isset($customization_settings['design_style']) ? $customization_settings['design_style'] : 'modern';
$layout_type = isset($customization_settings['layout_type']) ? $customization_settings['layout_type'] : 'accordion';
$title_alignment = isset($customization_settings['title_alignment']) ? $customization_settings['title_alignment'] : 'center';
$font_family = isset($customization_settings['font_family']) ? $customization_settings['font_family'] : 'system';
$premade_font_mode = isset($customization_settings['premade_font_mode']) ? $customization_settings['premade_font_mode'] : 'template_default';
$allowed_premade_font_modes = array('template_default', 'website_font', 'custom_font');
if (!in_array($premade_font_mode, $allowed_premade_font_modes, true)) {
    $premade_font_mode = 'template_default';
}
$primary_color = (isset($customization_settings['primary_color']) && trim((string) $customization_settings['primary_color']) !== '')
    ? $customization_settings['primary_color']
    : $website_color_defaults['primary_color'];
$background_color = isset($customization_settings['background_color']) ? $customization_settings['background_color'] : $website_color_defaults['background_color'];
$card_background_color = isset($customization_settings['card_background_color']) ? $customization_settings['card_background_color'] : $website_color_defaults['card_background_color'];
$text_color = isset($customization_settings['text_color']) ? $customization_settings['text_color'] : $website_color_defaults['text_color'];
$title_text_color = isset($customization_settings['title_text_color']) ? $customization_settings['title_text_color'] : $text_color;
$question_text_color = (isset($customization_settings['question_text_color']) && trim((string) $customization_settings['question_text_color']) !== '')
    ? $customization_settings['question_text_color']
    : $text_color;
$answer_text_color = isset($customization_settings['answer_text_color']) ? $customization_settings['answer_text_color'] : $text_color;
$title_font_size = isset($customization_settings['title_font_size']) ? intval($customization_settings['title_font_size']) : 32;
$question_font_size = isset($customization_settings['question_font_size']) ? intval($customization_settings['question_font_size']) : 18;
$answer_font_size = isset($customization_settings['answer_font_size']) ? intval($customization_settings['answer_font_size']) : 16;

// Container and card style settings
$container_width = isset($customization_settings['container_width']) ? $customization_settings['container_width'] : '900';
$card_style = isset($customization_settings['card_style']) ? $customization_settings['card_style'] : 'shadow';

// Grid/Card layout settings
$grid_columns = isset($customization_settings['grid_columns']) ? intval($customization_settings['grid_columns']) : 3;
$video_columns = isset($customization_settings['video_columns']) ? intval($customization_settings['video_columns']) : 3;
$card_radius = isset($customization_settings['card_radius']) ? intval($customization_settings['card_radius']) : 12;
$card_radius = max(0, min(50, $card_radius));
$card_icon_url = isset($customization_settings['card_icon_url']) ? trim($customization_settings['card_icon_url']) : '';
$card_icon_shape = isset($customization_settings['card_icon_shape']) ? $customization_settings['card_icon_shape'] : 'circle';
$card_padding = isset($customization_settings['card_padding']) ? intval($customization_settings['card_padding']) : 24;
$active_header_text_color = faq_renderer_contrast_text_color($primary_color, '#ffffff', '#111827');
$step_badge_text_color = faq_renderer_contrast_text_color($primary_color, '#ffffff', '#111827');
$video_columns = max(1, min(4, $video_columns));

// Font family mapping
$font_families = array(
    'system' => 'inherit',
    'arial' => 'Arial, sans-serif',
    'helvetica' => 'Helvetica, Arial, sans-serif',
    'georgia' => 'Georgia, serif',
    'times' => '"Times New Roman", Times, serif',
    'courier' => '"Courier New", Courier, monospace',
    'verdana' => 'Verdana, Geneva, sans-serif',
    'roboto' => '"Roboto", sans-serif',
    'open-sans' => '"Open Sans", sans-serif',
    'lato' => '"Lato", sans-serif',
    'montserrat' => '"Montserrat", sans-serif',
    'poppins' => '"Poppins", sans-serif',
    'inter' => '"Inter", sans-serif'
);
$selected_font_family = isset($font_families[$font_family]) ? $font_families[$font_family] : $font_families['system'];
$use_custom_font = ($font_family !== 'system' && $selected_font_family !== 'inherit');
$selected_font_family_css = preg_replace('/[^a-zA-Z0-9,\s"-]/', '', $selected_font_family);
if (empty($selected_font_family_css)) {
    $selected_font_family_css = 'inherit';
}

// ============================================
// GENERATE JSON-LD SCHEMA (All Questions)
// ============================================
$schema = array(
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => array()
);

foreach ($groups_with_questions as $group_data) {
    foreach ($group_data['questions'] as $q) {
        $schema['mainEntity'][] = array(
            '@type' => 'Question',
            'name' => strip_tags($q['question']),
            'acceptedAnswer' => array(
                '@type' => 'Answer',
                'text' => strip_tags($q['answer'])
            )
        );
    }
}

// ============================================
// RENDER OUTPUT
// ============================================
?>

<!-- FAQ Management Plugin - Global Renderer -->
<?php
// Load design-specific CSS based on preset
// All design presets (minimal, split, colorful, modern, simple, card, classic) are now templates
// Custom layout (design_preset == 'custom') uses classic.css as default
$design_css_map = array(
    'minimal' => 'faq-minimal.css',
    'split' => 'faq-split.css',
    'colorful' => 'faq-colorful.css',
    'modern' => 'faq-modern.css',
    'simple' => 'faq-simple.css',
    'card' => 'faq-card.css',
    'classic' => 'faq-classic.css'
);

// Determine which CSS file to load
$css_key = $design_preset;

// Custom layouts don't use CDN CSS - they use inline customization styles only
$is_custom_design = ($design_preset == 'custom');

// If design_preset is not in the map (and not custom), default to classic
if (!$is_custom_design && !isset($design_css_map[$css_key])) {
    $css_key = 'classic';
}

// Only load external CSS for premade templates, not custom layouts
if (!$is_custom_design) {
    $css_filename = $design_css_map[$css_key];
    
    // Determine CSS URL: Use CDN if configured, otherwise use local path
    $use_cdn = !empty($cdn_base_url);
    $css_url = '';
    
    if ($use_cdn) {
        // Remove trailing slash from CDN URL if present
        $cdn_base = rtrim($cdn_base_url, '/');
        // CDN URL format: https://cdn.bdgrowthsuite.com/tools/faq-modern.css
        // Ensure single slash between domain and path
        $css_url = $cdn_base . '/tools/' . $css_filename;
        // Remove any double slashes that might occur
        $css_url = preg_replace('#([^:])//+#', '$1/', $css_url);
        echo '<link rel="stylesheet" href="' . htmlspecialchars($css_url) . '" crossorigin="anonymous">';
    } else {
        // Use local CSS file
        $local_css_path = '/css/' . $css_filename;
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . $local_css_path)) {
            echo '<link rel="stylesheet" href="' . $local_css_path . '">';
        } else {
            // Fallback to default CSS
            $default_css = '/css/faq-default.css';
            if (file_exists($_SERVER['DOCUMENT_ROOT'] . $default_css)) {
                echo '<link rel="stylesheet" href="' . $default_css . '">';
            }
        }
    }
} else {
    // Custom layout: Output inline critical styles only (no CDN dependency)
    echo '<style>
        .bd-faq-container { width: 100%; max-width: 100%; margin: 0 auto; padding: clamp(12px, 2vw, 24px); box-sizing: border-box; }
        .bd-faq-accordion-item { border: 1px solid #e0e0e0; border-radius: 8px; margin-bottom: 1rem; background: #fff; }
        .bd-faq-header { padding: 1.5rem; cursor: pointer; display: flex; justify-content: space-between; align-items: center; background: #f8f9fa; }
        .bd-faq-header:hover { background: #e9ecef; }
        .bd-faq-header.active { background: #007bff; color: ' . htmlspecialchars($active_header_text_color) . '; }
        .bd-faq-question { font-size: 1.25rem; font-weight: 600; margin: 0; flex: 1; }
        .bd-faq-icon { transition: transform 0.3s ease; }
        .bd-faq-icon.open { transform: rotate(180deg); }
        .bd-faq-body { max-height: 0; overflow: hidden; transition: max-height 0.3s ease, padding 0.3s ease; padding: 0 1.5rem; }
        .bd-faq-body.active { max-height: 2000px; padding: 1.5rem; }
        .bd-faq-answer { line-height: 1.7; color: #333; }
        .bd-faq-section-title { font-size: 2rem; font-weight: 700; margin-bottom: 1.5rem; }
        .bd-faq-section-subtitle { font-size: 1.125rem; color: #6c757d; margin-bottom: 2rem; }
    </style>';
}

// Load Google Fonts if needed
$google_font_map = array(
    'roboto' => 'Roboto',
    'open-sans' => 'Open+Sans',
    'lato' => 'Lato',
    'montserrat' => 'Montserrat',
    'poppins' => 'Poppins',
    'inter' => 'Inter'
);
if ($use_custom_font && isset($google_font_map[$font_family]) && ($design_preset == 'custom' || $premade_font_mode === 'custom_font')) {
    $font_name = $google_font_map[$font_family];
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '<link href="https://fonts.googleapis.com/css2?family=' . $font_name . ':wght@400;600;700&display=swap" rel="stylesheet">';
}

// Generate inline styles for customization
// Always apply customization styles for custom layouts, and also for templates if they allow customization
$is_custom_layout = ($design_preset == 'custom');
$is_premade_template = !$is_custom_layout && in_array($design_preset, ['minimal', 'split', 'colorful', 'modern', 'simple', 'card', 'classic']);
$font_family_rule = '';
if ($is_custom_layout) {
    if ($use_custom_font) {
        $font_family_rule = 'font-family: ' . $selected_font_family_css . ' !important;';
    }
} elseif ($is_premade_template) {
    if ($premade_font_mode === 'website_font') {
        $font_family_rule = 'font-family: inherit !important;';
    } elseif ($premade_font_mode === 'custom_font' && $use_custom_font) {
        $font_family_rule = 'font-family: ' . $selected_font_family_css . ' !important;';
    }
}
$is_card_style = ($design_preset == 'card');
$title_color = $is_card_style ? '#ffffff' : $title_text_color;
$subtitle_color = $is_card_style ? 'rgba(255,255,255,0.9)' : '#6c757d';
$hover_bg = $is_card_style ? 'rgba(255,255,255,0.1)' : '#e9ecef';

// Generate container width CSS
$container_max_width = ($container_width == '100%') ? '100%' : $container_width . 'px';

// Generate card style CSS based on selected style
$card_style_css = '';
$card_style_extra_css = '';
$minimal_line_color = '#cbd5e1';
$accordion_radius = $card_radius;
switch ($card_style) {
    case 'minimal':
        // Clean lines style like premade minimal template
        $card_style_css = 'box-shadow: none !important; border: none !important;';
        $card_style_extra_css = '
        .bd-faq-accordion-item,
        .bd-faq-tab-item,
        .bd-faq-video-item,
        .bd-faq-card-item {
            border-bottom: 1px solid ' . $minimal_line_color . ' !important;
        }
        .bd-faq-accordion-item:last-child,
        .bd-faq-tab-item:last-child,
        .bd-faq-video-item:last-child,
        .bd-faq-card-item:last-child { border-bottom: none !important; }
        .bd-faq-accordion-item { margin-bottom: 0 !important; }
        .bd-faq-header { background: transparent !important; padding: 20px 16px; }
        .bd-faq-header:hover { background-color: rgba(0,0,0,0.03) !important; }
        .bd-faq-body { padding: 0 16px 20px 16px; }';
        break;
    case 'shadow':
        $card_style_css = 'box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important; border: none !important;';
        break;
    case 'elevated':
        $card_style_css = 'box-shadow: 0 4px 16px rgba(0,0,0,0.12) !important; border: none !important;';
        break;
    case 'bordered':
        $card_style_css = 'box-shadow: none !important; border: 2px solid ' . htmlspecialchars($primary_color) . ' !important;';
        break;
    case 'simple':
        $card_style_css = 'box-shadow: none !important; border: 1px solid #e5e7eb !important;';
        break;
    case 'flat':
        $card_style_css = 'box-shadow: none !important; border: none !important;';
        break;
    default:
        $card_style_css = 'box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important; border: none !important;';
}

// Generate customization styles - for premade templates, only apply non-conflicting styles
echo '<style id="faq-customization-styles">
    /* Custom Design Settings */
    .bd-faq-container {
        ' . $font_family_rule . '
        ' . ($is_custom_layout ? 'background-color: ' . htmlspecialchars($background_color) . ' !important; max-width: ' . $container_max_width . ' !important;' : '') . '
        padding: 40px 20px;
    }
    .bd-faq-container .bd-faq-section-title,
    .bd-faq-container .bd-faq-section-subtitle,
    .bd-faq-container .bd-faq-question,
    .bd-faq-container .bd-faq-answer,
    .bd-faq-container .bd-faq-card-question,
    .bd-faq-container .bd-faq-card-answer,
    .bd-faq-container .bd-faq-tab-btn,
    .bd-faq-container h2,
    .bd-faq-container h3,
    .bd-faq-container h4,
    .bd-faq-container p,
    .bd-faq-container input,
    .bd-faq-container textarea,
    .bd-faq-container select {
        ' . $font_family_rule . '
    }
    ' . ($is_custom_layout ? '
    /* Section title/subtitle - custom layout only; premade templates use CDN CSS */
    .bd-faq-section-title {
        ' . $font_family_rule . '
        text-align: ' . htmlspecialchars($title_alignment) . ' !important;
        font-size: ' . intval($title_font_size) . 'px !important;
        color: ' . htmlspecialchars($title_color) . ' !important;
    }
    .bd-faq-section-subtitle {
        ' . $font_family_rule . '
        text-align: ' . htmlspecialchars($title_alignment) . ' !important;
        color: ' . htmlspecialchars($subtitle_color) . ' !important;
    }
    ' : '') . '
    ' . ($is_custom_layout ? '
    /* Custom layout - Question styles */
    .bd-faq-question,
    .bd-faq-card-question,
    .bd-faq-tab-item .bd-faq-question,
    .bd-faq-search-item .bd-faq-question,
    .bd-faq-single-item .bd-faq-question,
    .bd-faq-step-item .bd-faq-question,
    .bd-faq-video-item .bd-faq-question,
    .bd-faq-persona-section .bd-faq-question,
    .bd-faq-sidebar .bd-faq-question {
        ' . $font_family_rule . '
        font-size: ' . intval($question_font_size) . 'px !important;
        font-weight: 600 !important;
        color: ' . htmlspecialchars($question_text_color) . ' !important;
    }
    /* Custom layout - Answer styles */
    .bd-faq-answer,
    .bd-faq-card-answer,
    .bd-faq-tab-item .bd-faq-answer,
    .bd-faq-search-item .bd-faq-answer,
    .bd-faq-single-item .bd-faq-answer,
    .bd-faq-step-item .bd-faq-answer,
    .bd-faq-video-item .bd-faq-answer,
    .bd-faq-persona-section .bd-faq-answer,
    .bd-faq-sidebar .bd-faq-answer {
        ' . $font_family_rule . '
        font-size: ' . intval($answer_font_size) . 'px !important;
        color: ' . htmlspecialchars($answer_text_color) . ' !important;
    }
    /* Force answer content to use configured font styles, but allow custom text colors from editor */
    .bd-faq-answer *,
    .bd-faq-card-answer * {
        font-family: inherit !important;
        font-size: inherit !important;
        line-height: inherit !important;
    }
    .bd-faq-answer p,
    .bd-faq-card-answer p {
        margin: 0 0 1em 0;
    }
    .bd-faq-answer p:last-child,
    .bd-faq-card-answer p:last-child {
        margin-bottom: 0;
    }
    /* Custom layout - Card background */
    .bd-faq-accordion-item,
    .bd-faq-card-item,
    .bd-faq-tab-item,
    .bd-faq-video-item,
    .bd-faq-step-item .bd-faq-step-content,
    .bd-faq-persona-section {
        background-color: ' . htmlspecialchars($card_background_color) . ' !important;
    }
    /* Custom layout card style */
    .bd-faq-accordion-item,
    .bd-faq-card-item,
    .bd-faq-tab-item,
    .bd-faq-video-item,
    .bd-faq-persona-section,
    .bd-faq-step-item .bd-faq-step-content {
        ' . $card_style_css . '
    }
    .bd-faq-accordion-item,
    .bd-faq-card-item,
    .bd-faq-tab-item,
    .bd-faq-video-item,
    .bd-faq-persona-section,
    .bd-faq-chat-container,
    .bd-faq-step-item .bd-faq-step-content {
        border-radius: ' . intval($accordion_radius) . 'px !important;
    }
    ' . $card_style_extra_css . '
    /* Custom layout styles - full control */
    .bd-faq-header {
        background: transparent;
        transition: background-color 0.2s ease, opacity 0.2s ease;
    }
    .bd-faq-header:hover {
        opacity: 0.8;
    }
    .bd-faq-header.active {
        background-color: ' . htmlspecialchars($primary_color) . ' !important;
        opacity: 1;
    }
    .bd-faq-header.active .bd-faq-question,
    .bd-faq-header.active .bd-faq-icon,
    .bd-faq-header.active .bd-faq-toggle-icon,
    .bd-faq-header.active .fa {
        color: ' . htmlspecialchars($active_header_text_color) . ' !important;
    }
    .bd-faq-icon {
        color: ' . htmlspecialchars($question_text_color) . ';
        font-size: 16px !important;
        transition: transform 0.3s ease;
    }
    .bd-faq-icon.open {
        transform: rotate(180deg);
    }
    ' : ($is_premade_template ? '
    /* Premade template: no overrides - everything (colors, layout, typography) comes from CDN CSS.
       Only container background is applied via template_bg_* on the section element when set. */
    ' : '
    /* Fallback styles */
    .bd-faq-header.active {
        background-color: ' . htmlspecialchars($primary_color) . ' !important;
        color: ' . htmlspecialchars($active_header_text_color) . ' !important;
    }
    ')) . '
    .bd-faq-body {
        --faq-max-height: 0px;
        max-height: var(--faq-max-height) !important;
        padding-top: 0 !important;
        padding-bottom: 0 !important;
        transition: max-height 0.3s ease !important;
        overflow: hidden !important;
    }
    .bd-faq-body.active {
        padding-top: 16px !important;
        padding-bottom: 16px !important;
    }
    /* Additional styles for custom layouts */
    ' . ($is_custom_layout ? '
    .bd-faq-container.bd-faq-layout-' . htmlspecialchars($layout_type) . ' {
        background-color: ' . htmlspecialchars($background_color) . ' !important;
    }
    ' : '') . '
    </style>';
?>

<script type="application/ld+json">
    <?php echo json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
</script>

<?php
// Get template-specific background color
$container_bg_style = '';
if (in_array($design_preset, ['minimal', 'split', 'colorful', 'modern', 'simple', 'card', 'classic'])) {
    $template_bg_key = 'template_bg_' . $design_preset;
    $template_bg_color = isset($customization_settings[$template_bg_key]) ? $customization_settings[$template_bg_key] : '';

    if (!empty($template_bg_color)) {
        $container_bg_style = ' style="background-color: ' . htmlspecialchars($template_bg_color) . ' !important;"';
    }
}
?>

<section class="bd-faq-container bd-faq-layout-<?php echo htmlspecialchars($layout_type); ?>" <?php echo $container_bg_style; ?>>
    <?php
    $question_index = 0;

    // ============================================
    // TABBED LAYOUT - Render once for all groups (outside loop)
    // ============================================
    if ($layout_type == 'tabbed'): ?>
        <?php if (count($groups_with_questions) > 1): ?>
            <!-- Multiple Groups - Show tabs for each group -->
            <div class="bd-faq-tabbed">
                <?php
                // Get section title from first group
                $first_group = $groups_with_questions[0]['group'];
                $section_title = $first_group['custom_title'] ?: 'Frequently Asked Questions';
                $section_subtitle = $first_group['custom_subtitle'] ?: '';
                $show_default_title = !in_array($design_preset, array('minimal', 'split', 'colorful'));

                // Show title if enabled
                if ($show_default_title && ($first_group['show_title'] == 1 || $first_group['show_title'] === true) && $section_title): ?>
                    <h2 class="bd-faq-section-title"><?php echo htmlspecialchars($section_title); ?></h2>
                <?php endif; ?>

                <?php if ($show_default_title && $section_subtitle): ?>
                    <p class="bd-faq-section-subtitle"><?php echo htmlspecialchars($section_subtitle); ?></p>
                <?php endif; ?>

                <div class="bd-faq-tabs" style="display: flex; gap: 4px; margin-bottom: 24px; border-bottom: 2px solid <?php echo htmlspecialchars($primary_color); ?>; flex-wrap: wrap; background: transparent;">
                    <?php $tab_index = 0;
                    foreach ($groups_with_questions as $tab_group_data): ?>
                        <?php
                        // Use custom_label, custom_title, or fallback to group_name
                        $tab_label = !empty($tab_group_data['group']['custom_label'])
                            ? $tab_group_data['group']['custom_label']
                            : (!empty($tab_group_data['group']['custom_title'])
                                ? $tab_group_data['group']['custom_title']
                                : $tab_group_data['group']['group_name']);
                        ?>
                        <button class="bd-faq-tab-btn <?php echo $tab_index == 0 ? 'active' : ''; ?>"
                            onclick="switchFaqTab(<?php echo $tab_index; ?>)"
                            style="padding: 12px 24px; background: transparent; border: none; border-bottom: 3px solid <?php echo $tab_index == 0 ? '#2AB27B' : 'transparent'; ?>; color: <?php echo $tab_index == 0 ? '#2AB27B' : '#1f2937'; ?>; font-size: 16px; font-weight: <?php echo $tab_index == 0 ? '700' : '600'; ?>; cursor: pointer; transition: all 0.3s; position: relative; top: 2px;"
                            id="faq-tab-<?php echo $tab_index; ?>">
                            <?php echo htmlspecialchars($tab_label); ?>
                        </button>
                        <?php $tab_index++; ?>
                    <?php endforeach; ?>
                </div>
                <?php $tab_index = 0;
                foreach ($groups_with_questions as $tab_group_data): ?>
                    <div class="bd-faq-tab-content <?php echo $tab_index == 0 ? 'active' : ''; ?>" id="faq-tab-content-<?php echo $tab_index; ?>" style="display: <?php echo $tab_index == 0 ? 'block' : 'none'; ?>;">
                        <?php foreach ($tab_group_data['questions'] as $q): ?>
                            <div class="bd-faq-tab-item" style="margin-bottom: 24px; background: <?php echo htmlspecialchars($card_background_color); ?>; border-radius: <?php echo intval($card_radius); ?>px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                                <h3 class="bd-faq-question" style="font-size: <?php echo intval($question_font_size); ?>px; color: <?php echo htmlspecialchars($question_text_color); ?>; margin: 0 0 12px 0; font-weight: 600; padding-bottom: 12px; border-bottom: 2px solid <?php echo htmlspecialchars($primary_color); ?>;">
                                    <?php echo htmlspecialchars(strip_tags($q['question'])); ?>
                                </h3>
                                <div class="bd-faq-answer" style="font-size: <?php echo intval($answer_font_size); ?>px; color: <?php echo htmlspecialchars($answer_text_color); ?>; line-height: 1.7; padding-top: 8px;">
                                    <?php echo $q['answer']; ?>
                                </div>
                            </div>
                            <?php $question_index++; ?>
                        <?php endforeach; ?>
                    </div>
                    <?php $tab_index++; ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Single Group - Hide tabs, show content in regular format -->
            <div class="bd-faq-tabbed">
                <?php
                $single_group = $groups_with_questions[0];
                $group = $single_group['group'];
                $section_title = $group['custom_title'] ?: 'Frequently Asked Questions';
                $section_subtitle = $group['custom_subtitle'] ?: '';
                $show_default_title = !in_array($design_preset, array('minimal', 'split', 'colorful'));

                if ($show_default_title && ($group['show_title'] == 1 || $group['show_title'] === true) && $section_title): ?>
                    <h2 class="bd-faq-section-title"><?php echo htmlspecialchars($section_title); ?></h2>
                <?php endif; ?>

                <?php if ($show_default_title && $section_subtitle): ?>
                    <p class="bd-faq-section-subtitle"><?php echo htmlspecialchars($section_subtitle); ?></p>
                <?php endif; ?>

                <?php foreach ($single_group['questions'] as $q): ?>
                    <div class="bd-faq-tab-item" style="margin-bottom: 24px; background: <?php echo htmlspecialchars($card_background_color); ?>; border-radius: <?php echo intval($card_radius); ?>px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                        <h3 class="bd-faq-question" style="font-size: <?php echo intval($question_font_size); ?>px; color: <?php echo htmlspecialchars($question_text_color); ?>; margin: 0 0 12px 0; font-weight: 600; padding-bottom: 12px; border-bottom: 2px solid <?php echo htmlspecialchars($primary_color); ?>;">
                            <?php echo htmlspecialchars(strip_tags($q['question'])); ?>
                        </h3>
                        <div class="bd-faq-answer" style="font-size: <?php echo intval($answer_font_size); ?>px; color: <?php echo htmlspecialchars($answer_text_color); ?>; line-height: 1.7; padding-top: 8px;">
                            <?php echo $q['answer']; ?>
                        </div>
                    </div>
                    <?php $question_index++; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php elseif ($layout_type == 'sidebar'):
        // ============================================
        // SIDEBAR LAYOUT - Render once for all groups (outside loop)
        // ============================================
    ?>
        <?php
        // Get section title from first group
        $first_group = $groups_with_questions[0]['group'];
        $section_title = $first_group['custom_title'] ?: 'Frequently Asked Questions';
        $section_subtitle = $first_group['custom_subtitle'] ?: '';
        $show_default_title = !in_array($design_preset, array('minimal', 'split', 'colorful'));

        // Show title if enabled
        if ($show_default_title && ($first_group['show_title'] == 1 || $first_group['show_title'] === true) && $section_title): ?>
            <h2 class="bd-faq-section-title"><?php echo htmlspecialchars($section_title); ?></h2>
        <?php endif; ?>

        <?php if ($show_default_title && $section_subtitle): ?>
            <p class="bd-faq-section-subtitle"><?php echo htmlspecialchars($section_subtitle); ?></p>
        <?php endif; ?>

        <div class="bd-faq-sidebar" style="display: flex; gap: 30px;">
            <div class="bd-faq-sidebar-nav" style="flex: 0 0 250px; position: sticky; top: 20px; height: fit-content;">
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <?php $nav_index = 0;
                    foreach ($groups_with_questions as $nav_group_data): ?>
                        <?php
                        // Use custom_label, custom_title, or fallback to group_name
                        $nav_label = !empty($nav_group_data['group']['custom_label'])
                            ? $nav_group_data['group']['custom_label']
                            : (!empty($nav_group_data['group']['custom_title'])
                                ? $nav_group_data['group']['custom_title']
                                : $nav_group_data['group']['group_name']);
                        ?>
                        <li style="margin-bottom: 8px;">
                            <a href="#faq-group-<?php echo $nav_group_data['group']['group_id']; ?>"
                                onclick="scrollToFaqGroup('<?php echo $nav_group_data['group']['group_id']; ?>'); return false;"
                                class="bd-faq-sidebar-link"
                                style="display: block; padding: 12px 16px; background: <?php echo htmlspecialchars($card_background_color); ?>; color: <?php echo htmlspecialchars($question_text_color); ?>; text-decoration: none; border-radius: 6px; transition: all 0.2s;"
                                onmouseover="if (!this.classList.contains('active')) { this.style.background='<?php echo htmlspecialchars($primary_color); ?>'; this.style.color='#ffffff'; }"
                                onmouseout="if (!this.classList.contains('active')) { this.style.background='<?php echo htmlspecialchars($card_background_color); ?>'; this.style.color='<?php echo htmlspecialchars($text_color); ?>'; }">
                                <?php echo htmlspecialchars($nav_label); ?>
                            </a>
                        </li>
                        <?php $nav_index++; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="bd-faq-sidebar-content" style="flex: 1;">
                <?php foreach ($groups_with_questions as $sidebar_group_data): ?>
                    <div id="faq-group-<?php echo $sidebar_group_data['group']['group_id']; ?>" style="margin-bottom: 40px;">
                        <?php foreach ($sidebar_group_data['questions'] as $q): ?>
                            <div class="bd-faq-accordion-item" style="margin-bottom: 16px; background: <?php echo htmlspecialchars($card_background_color); ?>; border-radius: <?php echo intval($card_radius); ?>px; overflow: hidden;">
                                <div class="bd-faq-header" onclick="toggleFaqAccordion(<?php echo $question_index; ?>)" style="padding: 16px; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                                    <h3 class="bd-faq-question" style="font-size: <?php echo intval($question_font_size); ?>px; color: <?php echo htmlspecialchars($question_text_color); ?>; margin: 0;">
                                        <?php echo htmlspecialchars(strip_tags($q['question'])); ?>
                                    </h3>
                                    <i class="fa fa-chevron-down bd-faq-icon" id="faq-icon-<?php echo $question_index; ?>"></i>
                                </div>
                                <div class="bd-faq-body" id="faq-body-<?php echo $question_index; ?>" style="max-height: 0; overflow: hidden; transition: max-height 0.3s ease;">
                                    <div class="bd-faq-answer" style="padding: 16px; font-size: <?php echo intval($answer_font_size); ?>px; color: <?php echo htmlspecialchars($answer_text_color); ?>;">
                                        <?php echo $q['answer']; ?>
                                    </div>
                                </div>
                            </div>
                            <?php $question_index++; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php elseif ($layout_type == 'persona-based'):
        // ============================================
        // PERSONA-BASED LAYOUT - Render once for all groups (outside loop)
        // ============================================
    ?>
        <?php
        // Get section title from first group
        $first_group = $groups_with_questions[0]['group'];
        $section_title = $first_group['custom_title'] ?: 'Frequently Asked Questions';
        $section_subtitle = $first_group['custom_subtitle'] ?: '';
        $show_default_title = !in_array($design_preset, array('minimal', 'split', 'colorful'));

        // Show title if enabled
        if ($show_default_title && ($first_group['show_title'] == 1 || $first_group['show_title'] === true) && $section_title): ?>
            <h2 class="bd-faq-section-title"><?php echo htmlspecialchars($section_title); ?></h2>
        <?php endif; ?>

        <?php if ($show_default_title && $section_subtitle): ?>
            <p class="bd-faq-section-subtitle"><?php echo htmlspecialchars($section_subtitle); ?></p>
        <?php endif; ?>

        <div class="bd-faq-persona">
            <?php
            // Group questions by persona (for now, show all in one section - can be enhanced)
            foreach ($groups_with_questions as $persona_group_data): ?>
                <div class="bd-faq-persona-section" style="margin-bottom: 40px; padding: 24px; background: <?php echo htmlspecialchars($card_background_color); ?>; border-radius: <?php echo intval($card_radius); ?>px;">
                    <h3 style="font-size: 20px; color: <?php echo htmlspecialchars($primary_color); ?>; margin-bottom: 20px; font-weight: 600;">
                        <?php echo htmlspecialchars($persona_group_data['group']['group_name']); ?>
                    </h3>
                    <?php foreach ($persona_group_data['questions'] as $q): ?>
                        <div class="bd-faq-accordion-item" style="margin-bottom: 12px;">
                            <div class="bd-faq-header" onclick="toggleFaqAccordion(<?php echo $question_index; ?>)" style="padding: 14px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; background: rgba(0,0,0,0.02); border-radius: 6px;">
                                <h4 class="bd-faq-question" style="font-size: <?php echo intval($question_font_size); ?>px; color: <?php echo htmlspecialchars($question_text_color); ?>; margin: 0; font-weight: 500;">
                                    <?php echo htmlspecialchars(strip_tags($q['question'])); ?>
                                </h4>
                                <i class="fa fa-chevron-down bd-faq-icon" id="faq-icon-<?php echo $question_index; ?>"></i>
                            </div>
                            <div class="bd-faq-body" id="faq-body-<?php echo $question_index; ?>" style="max-height: 0; overflow: hidden; transition: max-height 0.3s ease;">
                                <div class="bd-faq-answer" style="padding: 14px; font-size: <?php echo intval($answer_font_size); ?>px; color: <?php echo htmlspecialchars($answer_text_color); ?>;">
                                    <?php echo $q['answer']; ?>
                                </div>
                            </div>
                        </div>
                        <?php $question_index++; ?>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php else:
        // ============================================
        // OTHER LAYOUTS - Render per group (inside loop)
        // ============================================
        foreach ($groups_with_questions as $group_data):
            $group = $group_data['group'];
            $questions = $group_data['questions'];
            $section_title = $group['custom_title'] ?: 'Frequently Asked Questions';
            $section_subtitle = $group['custom_subtitle'] ?: '';
        ?>
            <div class="bd-faq-group-section" style="margin-bottom: 3rem;">
                <?php
                // Don't show default titles for fixed-layout templates (they have custom headers)
                $show_default_title = !in_array($design_preset, array('minimal', 'split', 'colorful'));

                if ($show_default_title && ($group['show_title'] == 1 || $group['show_title'] === true) && $section_title): ?>
                    <h2 class="bd-faq-section-title"><?php echo htmlspecialchars($section_title); ?></h2>
                <?php endif; ?>

                <?php if ($show_default_title && $section_subtitle): ?>
                    <p class="bd-faq-section-subtitle"><?php echo htmlspecialchars($section_subtitle); ?></p>
                <?php endif; ?>

                <?php
                // ============================================
                // TEMPLATE-SPECIFIC RENDERING
                // Check design preset FIRST for fixed-layout templates
                // ============================================

                // MINIMAL TEMPLATE - Simple clean accordion
                if ($design_preset == 'minimal'): ?>
                    <!-- Optional Title Section -->
                    <?php if ($group['show_title'] == 1 || $group['show_title'] === true): ?>
                        <div class="bd-faq-minimal-header">
                            <h2 class="bd-faq-section-title"><?php echo htmlspecialchars($section_title); ?></h2>
                            <?php if ($section_subtitle): ?>
                                <p class="bd-faq-section-subtitle"><?php echo htmlspecialchars($section_subtitle); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Minimal Accordion -->
                    <div class="bd-faq-minimal-accordion">
                        <?php foreach ($questions as $q): ?>
                            <div class="bd-faq-accordion-item">
                                <div class="bd-faq-header" onclick="toggleFaqAccordion(<?php echo $question_index; ?>)">
                                    <h3 class="bd-faq-question"><?php echo htmlspecialchars(strip_tags($q['question'])); ?></h3>
                                    <i class="fa fa-chevron-down bd-faq-icon" id="faq-icon-<?php echo $question_index; ?>"></i>
                                </div>
                                <div class="bd-faq-body" id="faq-body-<?php echo $question_index; ?>">
                                    <div class="bd-faq-answer">
                                        <?php echo $q['answer']; ?>
                                    </div>
                                </div>
                            </div>
                            <?php $question_index++; ?>
                        <?php endforeach; ?>
                    </div>

                <?php
                // SPLIT TEMPLATE - Two-column layout
                elseif ($design_preset == 'split'):
                    // Get custom label or use default
                    $section_label = !empty($group['custom_label']) ? $group['custom_label'] : 'Frequently Asked Questions';

                    // Get custom CTA values or use defaults
                    $cta_title = !empty($group['cta_title']) ? $group['cta_title'] : "Need Any Help? We're Here To Guide You Out!";
                    $cta_text = !empty($group['cta_text']) ? $group['cta_text'] : "Still have questions? We're here to help you!";
                    $cta_email = !empty($group['cta_email']) ? $group['cta_email'] : 'info@domainname.com';
                ?>
                    <div class="bd-faq-split-wrapper">
                        <!-- Left Column - Intro Section -->
                        <div class="bd-faq-intro-section">
                            <?php if ($group['show_title'] == 1 || $group['show_title'] === true): ?>
                                <span class="bd-faq-intro-label"><?php echo htmlspecialchars($section_label); ?></span>
                                <h2 class="bd-faq-section-title"><?php echo htmlspecialchars($section_title); ?></h2>
                                <?php if ($section_subtitle): ?>
                                    <p class="bd-faq-section-subtitle"><?php echo htmlspecialchars($section_subtitle); ?></p>
                                <?php endif; ?>
                            <?php endif; ?>

                            <!-- CTA Box -->
                            <div class="bd-faq-cta-box">
                                <h4 class="bd-faq-cta-title"><?php echo htmlspecialchars($cta_title); ?></h4>
                                <p class="bd-faq-cta-text"><?php echo htmlspecialchars($cta_text); ?></p>
                                <a href="mailto:<?php echo htmlspecialchars($cta_email); ?>" class="bd-faq-cta-button">
                                    ✉ Email Us: <?php echo htmlspecialchars($cta_email); ?>
                                </a>
                            </div>
                        </div>

                        <!-- Right Column - Numbered Accordion -->
                        <div class="bd-faq-questions-section">
                            <?php $item_number = 1;
                            foreach ($questions as $q): ?>
                                <div class="bd-faq-accordion-item">
                                    <div class="bd-faq-header" onclick="toggleFaqAccordion(<?php echo $question_index; ?>)">
                                        <span class="bd-faq-number"><?php echo $item_number; ?>.</span>
                                        <h3 class="bd-faq-question"><?php echo htmlspecialchars(strip_tags($q['question'])); ?></h3>
                                        <span class="bd-faq-icon" id="faq-icon-<?php echo $question_index; ?>"></span>
                                    </div>
                                    <div class="bd-faq-body" id="faq-body-<?php echo $question_index; ?>">
                                        <div class="bd-faq-answer">
                                            <?php echo $q['answer']; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php $question_index++;
                                $item_number++; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                <?php
                // COLORFUL TEMPLATE - Pink header with accordion
                elseif ($design_preset == 'colorful'): ?>
                    <!-- Header Section -->
                    <?php if ($group['show_title'] == 1 || $group['show_title'] === true): ?>
                        <div class="bd-faq-header-section">
                            <h2 class="bd-faq-section-title"><?php echo htmlspecialchars($section_title); ?></h2>
                            <?php if ($section_subtitle): ?>
                                <p class="bd-faq-section-subtitle"><?php echo htmlspecialchars($section_subtitle); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Accordion Container -->
                    <div class="bd-faq-accordion-container">
                        <?php foreach ($questions as $q): ?>
                            <div class="bd-faq-accordion-item">
                                <div class="bd-faq-header" onclick="toggleFaqAccordion(<?php echo $question_index; ?>)">
                                    <h3 class="bd-faq-question"><?php echo htmlspecialchars(strip_tags($q['question'])); ?></h3>
                                    <div class="bd-faq-toggle-icon" id="faq-icon-<?php echo $question_index; ?>"></div>
                                </div>
                                <div class="bd-faq-body" id="faq-body-<?php echo $question_index; ?>">
                                    <div class="bd-faq-answer">
                                        <?php echo $q['answer']; ?>
                                    </div>
                                </div>
                            </div>
                            <?php $question_index++; ?>
                        <?php endforeach; ?>
                    </div>

                <?php
                // ============================================
                // LAYOUT TYPE RENDERING (for flexible templates)
                // ============================================
                elseif ($layout_type == 'search-first'): ?>
                    <div class="bd-faq-search-first">
                        <div class="bd-faq-search-bar" style="max-width: 600px; width: 100%; margin: 0 auto 30px; position: relative; box-sizing: border-box;">
                            <input type="text" id="faq-search-input-<?php echo $group['group_id']; ?>"
                                placeholder="Search FAQs..."
                                onkeyup="filterFaqSearch('<?php echo $group['group_id']; ?>', this.value)"
                                style="width: 100%; padding: 14px 44px 14px 20px; border: 2px solid <?php echo htmlspecialchars($primary_color); ?>; border-radius: 8px; font-size: 16px; outline: none; box-sizing: border-box;">
                            <i class="fa fa-search bd-faq-search-icon" style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); color: <?php echo htmlspecialchars($primary_color); ?>; pointer-events: none; display: block; font-size: 18px;"></i>
                        </div>
                        <div class="bd-faq-search-results" id="faq-search-results-<?php echo $group['group_id']; ?>" style="margin: 0; padding: 0;">
                            <?php foreach ($questions as $q): ?>
                                <div class="bd-faq-search-item" style="margin-bottom: 24px; padding: 20px 0; border-bottom: 1px solid rgba(0,0,0,0.08);" data-question="<?php echo htmlspecialchars(strtolower(strip_tags($q['question']))); ?>" data-answer="<?php echo htmlspecialchars(strtolower(strip_tags($q['answer']))); ?>">
                                    <h3 class="bd-faq-question" style="font-size: <?php echo intval($question_font_size); ?>px; color: <?php echo htmlspecialchars($question_text_color); ?>; margin: 0 0 12px 0; font-weight: 600;">
                                        <?php echo htmlspecialchars(strip_tags($q['question'])); ?>
                                    </h3>
                                    <div class="bd-faq-answer" style="font-size: <?php echo intval($answer_font_size); ?>px; color: <?php echo htmlspecialchars($answer_text_color); ?>; line-height: 1.6; margin: 0; padding: 0;">
                                        <?php echo $q['answer']; ?>
                                    </div>
                                </div>
                                <?php $question_index++; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php elseif ($layout_type == 'single-column'): ?>
                    <div class="bd-faq-single-column" style="max-width: 800px; margin: 0 auto;">
                        <?php foreach ($questions as $q): ?>
                            <div class="bd-faq-single-item" style="margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px solid rgba(0,0,0,0.1);">
                                <h3 class="bd-faq-question" style="font-size: <?php echo intval($question_font_size); ?>px; color: <?php echo htmlspecialchars($question_text_color); ?>; margin-bottom: 12px; font-weight: 600;">
                                    <?php echo htmlspecialchars(strip_tags($q['question'])); ?>
                                </h3>
                                <div class="bd-faq-answer" style="font-size: <?php echo intval($answer_font_size); ?>px; color: <?php echo htmlspecialchars($answer_text_color); ?>; line-height: 1.7;">
                                    <?php echo $q['answer']; ?>
                                </div>
                            </div>
                            <?php $question_index++; ?>
                        <?php endforeach; ?>
                    </div>
                <?php elseif ($layout_type == 'grid-card' || $is_card_style): ?>
                    <?php 
                    // Calculate grid columns - responsive with fixed columns on desktop
                    $grid_gap = 16;
                    $min_card_width = $grid_columns == 2 ? 280 : ($grid_columns == 4 ? 200 : 240);
                    ?>
                    <div class="bd-faq-grid-container" style="display: grid; grid-template-columns: repeat(<?php echo $grid_columns; ?>, 1fr); gap: <?php echo $grid_gap; ?>px; width: 100%; box-sizing: border-box; align-items: start;">
                        <?php foreach ($questions as $q): ?>
                            <div class="bd-faq-card-item" style="background: <?php echo htmlspecialchars($card_background_color); ?>; border-radius: <?php echo $card_radius; ?>px; padding: <?php echo $card_padding; ?>px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; display: flex; flex-direction: column; word-wrap: break-word; overflow-wrap: break-word; box-sizing: border-box;" onclick="toggleFaqCard(<?php echo $question_index; ?>)" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.12)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.08)';">
                                <div style="display: flex; align-items: center; gap: 12px; width: 100%;">
                                    <?php if (!empty($card_icon_url)): ?>
                                        <?php if ($card_icon_shape == 'circle'): ?>
                                            <div style="flex-shrink: 0; width: 48px; height: 48px; background: <?php echo htmlspecialchars($primary_color); ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                                <img src="<?php echo htmlspecialchars($card_icon_url); ?>" alt="" style="width: 60%; height: 60%; object-fit: contain;">
                                            </div>
                                        <?php else: ?>
                                            <div style="flex-shrink: 0; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;">
                                                <img src="<?php echo htmlspecialchars($card_icon_url); ?>" alt="" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div style="flex-shrink: 0; width: 40px; height: 40px; background: <?php echo htmlspecialchars($primary_color); ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px;">
                                            <i class="fa fa-question"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div style="flex: 1; min-width: 0;">
                                        <h3 class="bd-faq-card-question" style="margin: 0; font-size: <?php echo intval($question_font_size); ?>px; color: <?php echo htmlspecialchars($question_text_color); ?>; font-weight: 500; word-wrap: break-word; overflow-wrap: break-word; line-height: 1.4;">
                                            <?php echo htmlspecialchars(strip_tags($q['question'])); ?>
                                        </h3>
                                    </div>
                                    <i class="fa fa-plus bd-faq-card-icon" id="faq-card-icon-<?php echo $question_index; ?>" style="color: <?php echo htmlspecialchars($primary_color); ?>; font-size: 16px; flex-shrink: 0; transition: transform 0.2s;"></i>
                                </div>
                                <div class="bd-faq-card-answer" id="faq-card-answer-<?php echo $question_index; ?>" style="max-height: 0; overflow: hidden; transition: max-height 0.3s ease; font-size: <?php echo intval($answer_font_size); ?>px; color: <?php echo htmlspecialchars($answer_text_color); ?>; line-height: 1.6; word-wrap: break-word; overflow-wrap: break-word;">
                                    <div style="padding-top: 16px; padding-left: <?php echo !empty($card_icon_url) ? '60' : '52'; ?>px;">
                                        <?php echo $q['answer']; ?>
                                    </div>
                                </div>
                            </div>
                            <?php $question_index++; ?>
                        <?php endforeach; ?>
                    </div>
                    <style>
                        @media (max-width: 992px) {
                            .bd-faq-grid-container { grid-template-columns: repeat(2, 1fr) !important; }
                        }
                        @media (max-width: 600px) {
                            .bd-faq-grid-container { grid-template-columns: 1fr !important; }
                        }
                    </style>
                <?php elseif ($layout_type == 'conversational'): ?>
                    <div class="bd-faq-conversational" style="max-width: 800px; margin: 0 auto;">
                        <div class="bd-faq-chat-container" style="background: <?php echo htmlspecialchars($card_background_color); ?>; border-radius: <?php echo intval($card_radius); ?>px; padding: 24px; min-height: 400px; display: flex; flex-direction: column;">
                            <div class="bd-faq-chat-messages" id="faq-chat-messages-<?php echo $group['group_id']; ?>" style="flex: 1; margin-bottom: 20px; overflow-y: auto;">
                                <div class="bd-faq-chat-bot" style="margin-bottom: 16px; display: flex; gap: 12px;">
                                    <div style="width: 40px; height: 40px; background: <?php echo htmlspecialchars($primary_color); ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0;">
                                        <i class="fa fa-robot"></i>
                                    </div>
                                    <div style="flex: 1; background: rgba(0,0,0,0.05); padding: 12px 16px; border-radius: 12px; color: <?php echo htmlspecialchars($text_color); ?>;">
                                        Hi! I can help answer your questions. Click on any question below to see the answer.
                                    </div>
                                </div>
                                <?php foreach ($questions as $q): ?>
                                    <div class="bd-faq-chat-user" style="margin-bottom: 16px; display: flex; gap: 12px; justify-content: flex-end;">
                                        <div style="flex: 1; background: <?php echo htmlspecialchars($primary_color); ?>; color: white; padding: 12px 16px; border-radius: 12px; text-align: right; cursor: pointer;" onclick="showFaqChatAnswer(<?php echo $question_index; ?>)">
                                            <?php echo htmlspecialchars(strip_tags($q['question'])); ?>
                                        </div>
                                        <div style="width: 40px; height: 40px; background: rgba(0,0,0,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                            <i class="fa fa-user"></i>
                                        </div>
                                    </div>
                                    <div class="bd-faq-chat-bot-answer" id="faq-chat-answer-<?php echo $question_index; ?>" style="display: none; margin-bottom: 16px; gap: 12px;">
                                        <div style="width: 40px; height: 40px; background: <?php echo htmlspecialchars($primary_color); ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0;">
                                            <i class="fa fa-robot"></i>
                                        </div>
                                        <div style="flex: 1; background: rgba(0,0,0,0.05); padding: 12px 16px; border-radius: 12px; color: <?php echo htmlspecialchars($answer_text_color); ?>; font-size: <?php echo intval($answer_font_size); ?>px;">
                                            <?php echo $q['answer']; ?>
                                        </div>
                                    </div>
                                    <?php $question_index++; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php elseif ($layout_type == 'video-multimedia'): ?>
                    <div class="bd-faq-video" style="display: grid; grid-template-columns: repeat(<?php echo $video_columns; ?>, 1fr); gap: 24px;">
                        <?php foreach ($questions as $q): ?>
                            <div class="bd-faq-video-item" style="background: <?php echo htmlspecialchars($card_background_color); ?>; border-radius: <?php echo intval($card_radius); ?>px; padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                                <?php
                                $video_url = isset($q['video_url']) ? trim($q['video_url']) : '';
                                if (!empty($video_url)):
                                    // Convert YouTube and Vimeo URLs to embed format
                                    $embed_url = '';

                                    // Comprehensive YouTube URL matching - handles all formats including query parameters
                                    $video_id = '';

                                    // Clean and decode URL - handle HTML entities, URL encoding, and database escaping
                                    $decoded_url = $video_url;
                                    // Remove slashes added by mysql_real_escape_string or addslashes
                                    $decoded_url = stripslashes($decoded_url);
                                    // Decode HTML entities
                                    $decoded_url = html_entity_decode($decoded_url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                                    // Decode URL encoding
                                    $decoded_url = urldecode($decoded_url);
                                    // Clean whitespace
                                    $decoded_url = trim($decoded_url);

                                    // Normalize URL: add protocol if missing, handle www/non-www
                                    $normalized_url = $decoded_url;
                                    if (!preg_match('/^https?:\/\//i', $normalized_url)) {
                                        $normalized_url = 'https://' . $normalized_url;
                                    }

                                    // Parse YouTube video ID using multiple strategies
                                    // Strategy 1: youtu.be short URLs (e.g., https://youtu.be/VIDEO_ID or youtu.be/VIDEO_ID?list=...)
                                    // Match youtu.be/ followed by 11 characters (video ID), then optionally anything else
                                    if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]{11})/i', $normalized_url, $matches)) {
                                        $video_id = $matches[1];
                                    }
                                    // Strategy 2: youtube.com/embed/VIDEO_ID or youtube.com/v/VIDEO_ID
                                    elseif (preg_match('/(?:youtube\.com\/(?:embed|v)\/)([a-zA-Z0-9_-]{11})/i', $normalized_url, $matches)) {
                                        $video_id = $matches[1];
                                    }
                                    // Strategy 3: Extract from query string v= parameter (most common format)
                                    // Handles: youtube.com/watch?v=ID, www.youtube.com/watch?v=ID, m.youtube.com/watch?v=ID
                                    // Also handles: youtube.com/watch?list=XXX&v=ID&t=123
                                    elseif (preg_match('/(?:youtube\.com|www\.youtube\.com|m\.youtube\.com)\/watch\?(?:v=|.*&v=)([a-zA-Z0-9_-]{11})/i', $normalized_url, $matches)) {
                                        $video_id = $matches[1];
                                    }
                                    // Strategy 4: Generic extraction - find v= parameter anywhere in YouTube URL
                                    elseif (preg_match('/youtube[^\/]*\/.*[?&]v=([a-zA-Z0-9_-]{11})/i', $normalized_url, $matches)) {
                                        $video_id = $matches[1];
                                    }
                                    // Strategy 5: Try parsing as URL and extracting from query string (try normalized, decoded, and original)
                                    if (empty($video_id) && function_exists('parse_url')) {
                                        $urls_to_try = array($normalized_url, $decoded_url, $video_url);
                                        foreach ($urls_to_try as $url_to_parse) {
                                            if (empty($video_id)) {
                                                $parsed = parse_url($url_to_parse);
                                                if ($parsed && isset($parsed['query'])) {
                                                    parse_str($parsed['query'], $query_params);
                                                    if (isset($query_params['v']) && preg_match('/^[a-zA-Z0-9_-]{11}$/', $query_params['v'])) {
                                                        $video_id = $query_params['v'];
                                                        break;
                                                    }
                                                }
                                                // Also check path for youtu.be format - extract first 11-char segment
                                                if (empty($video_id) && isset($parsed['path'])) {
                                                    $path = trim($parsed['path'], '/');
                                                    // For youtu.be, the path should be the video ID
                                                    if (isset($parsed['host']) && (strpos($parsed['host'], 'youtu.be') !== false || strpos($parsed['host'], 'youtube') !== false)) {
                                                        // Extract first 11 characters from path (before any query params or fragments)
                                                        if (preg_match('/^([a-zA-Z0-9_-]{11})/', $path, $path_matches)) {
                                                            $video_id = $path_matches[1];
                                                            break;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    // Strategy 6: Last resort - try original and decoded URLs with all patterns
                                    if (empty($video_id)) {
                                        $urls_to_check = array($decoded_url, $video_url);
                                        foreach ($urls_to_check as $check_url) {
                                            if (empty($video_id)) {
                                                // Try youtu.be (with optional query parameters)
                                                if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]{11})/i', $check_url, $matches)) {
                                                    $video_id = $matches[1];
                                                    break;
                                                }
                                                // Try embed/v format
                                                elseif (preg_match('/youtube\.com\/(?:embed|v)\/([a-zA-Z0-9_-]{11})/i', $check_url, $matches)) {
                                                    $video_id = $matches[1];
                                                    break;
                                                }
                                                // Try watch?v= format
                                                elseif (preg_match('/youtube[^\/]*\/watch\?(?:v=|.*&v=)([a-zA-Z0-9_-]{11})/i', $check_url, $matches)) {
                                                    $video_id = $matches[1];
                                                    break;
                                                }
                                                // Try any v= parameter
                                                elseif (preg_match('/[?&]v=([a-zA-Z0-9_-]{11})/i', $check_url, $matches)) {
                                                    $video_id = $matches[1];
                                                    break;
                                                }
                                            }
                                        }
                                    }

                                    // Final fallback: Extract any 11-character ID after youtu.be/ anywhere in the string
                                    if (empty($video_id)) {
                                        // Try all URL variations one more time with a very simple pattern
                                        // Include original URL first, then processed versions
                                        $all_urls = array(
                                            $video_url,  // Original from database
                                            $decoded_url,  // Decoded version
                                            $normalized_url,  // Normalized with protocol
                                            'https://' . ltrim($decoded_url, '/'),
                                            'http://' . ltrim($decoded_url, '/'),
                                            'https://' . ltrim($video_url, '/'),
                                            'http://' . ltrim($video_url, '/')
                                        );
                                        $all_urls = array_values(array_unique($all_urls));

                                        foreach ($all_urls as $test_url) {
                                            if (empty($test_url)) continue;

                                            if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]{11})/i', $test_url, $matches)) {
                                                $video_id = $matches[1];
                                                break;
                                            }
                                            if (preg_match('/youtu[^\.]*\.be\/([a-zA-Z0-9_-]{11})/i', $test_url, $matches)) {
                                                $video_id = $matches[1];
                                                break;
                                            }
                                            if (preg_match('/([a-zA-Z0-9_-]{11})/i', $test_url, $matches)) {
                                                if (stripos($test_url, 'youtu.be') !== false) {
                                                    $pos = stripos($test_url, 'youtu.be/');
                                                    if ($pos !== false) {
                                                        $after_youtube = substr($test_url, $pos + 9); // 9 = length of "youtu.be/"
                                                        if (preg_match('/^([a-zA-Z0-9_-]{11})/i', $after_youtube, $id_matches)) {
                                                            $video_id = $id_matches[1];
                                                            break;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    if (!empty($video_id)) {
                                        $video_id = trim($video_id);
                                        if (strlen($video_id) == 11 && preg_match('/^[a-zA-Z0-9_-]{11}$/', $video_id)) {
                                            $embed_url = 'https://www.youtube.com/embed/' . $video_id;
                                        } else {
                                            $video_id = '';
                                            $embed_url = '';
                                        }
                                    }

                                    if (empty($embed_url) && preg_match('/vimeo\.com\/(?:.*\/)?(\d+)/i', $video_url, $matches)) {
                                        $embed_url = 'https://player.vimeo.com/video/' . $matches[1];
                                    }

                                    if (!empty($embed_url)):
                                ?>
                                        <div style="width: 100%; height: 200px; border-radius: 8px; margin-bottom: 16px; position: relative; overflow: hidden;">
                                            <iframe src="<?php echo htmlspecialchars($embed_url); ?>"
                                                style="width: 100%; height: 100%; border: none; border-radius: 8px;"
                                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                                allowfullscreen
                                                frameborder="0"
                                                loading="lazy"></iframe>
                                            <div style="position: absolute; bottom: 8px; right: 8px; background: rgba(0,0,0,0.7); color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">Video</div>
                                        </div>
                                    <?php else: ?>
                                        <div style="width: 100%; height: 200px; background: rgba(0,0,0,0.1); border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-bottom: 16px; position: relative;">
                                            <i class="fa fa-play-circle" style="font-size: 48px; color: <?php echo htmlspecialchars($primary_color); ?>; cursor: pointer;"></i>
                                            <div style="position: absolute; bottom: 8px; right: 8px; background: rgba(0,0,0,0.7); color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">Invalid Video URL</div>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div style="width: 100%; height: 200px; background: rgba(0,0,0,0.1); border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-bottom: 16px; position: relative;">
                                        <i class="fa fa-play-circle" style="font-size: 48px; color: <?php echo htmlspecialchars($primary_color); ?>; cursor: pointer;"></i>
                                        <div style="position: absolute; bottom: 8px; right: 8px; background: rgba(0,0,0,0.7); color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">No Video</div>
                                    </div>
                                <?php endif; ?>
                                <h3 class="bd-faq-question" style="font-size: <?php echo intval($question_font_size); ?>px; color: <?php echo htmlspecialchars($question_text_color); ?>; margin-bottom: 12px; font-weight: 600;">
                                    <?php echo htmlspecialchars(strip_tags($q['question'])); ?>
                                </h3>
                                <div class="bd-faq-answer" style="font-size: <?php echo intval($answer_font_size); ?>px; color: <?php echo htmlspecialchars($text_color); ?>; line-height: 1.6;">
                                    <?php echo $q['answer']; ?>
                                </div>
                            </div>
                            <?php $question_index++; ?>
                        <?php endforeach; ?>
                    </div>
                    <style>
                        @media (max-width: 992px) {
                            .bd-faq-video { grid-template-columns: repeat(2, 1fr) !important; }
                        }
                        @media (max-width: 600px) {
                            .bd-faq-video { grid-template-columns: 1fr !important; }
                        }
                    </style>
                <?php elseif ($layout_type == 'step-by-step'): ?>
                    <div class="bd-faq-step-by-step" style="max-width: 900px; margin: 0 auto; position: relative;">
                        <div style="position: absolute; left: 20px; top: 0; bottom: 0; width: 2px; background: <?php echo htmlspecialchars($primary_color); ?>;"></div>
                        <?php $step_num = 1;
                        foreach ($questions as $q): ?>
                            <div class="bd-faq-step-item" style="position: relative; margin-bottom: 32px; padding-left: 60px;">
                                <div class="bd-faq-step-number" style="position: absolute; left: 8px; width: 24px; height: 24px; background: <?php echo htmlspecialchars($primary_color); ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: <?php echo htmlspecialchars($step_badge_text_color); ?>; font-weight: bold; font-size: 14px;">
                                    <?php echo $step_num; ?>
                                </div>
                                <div class="bd-faq-step-content" style="background: <?php echo htmlspecialchars($card_background_color); ?>; padding: 20px; border-radius: <?php echo intval($card_radius); ?>px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                    <h3 class="bd-faq-question" style="font-size: <?php echo intval($question_font_size); ?>px; color: <?php echo htmlspecialchars($question_text_color); ?>; margin-bottom: 12px; font-weight: 600;">
                                        <?php echo htmlspecialchars(strip_tags($q['question'])); ?>
                                    </h3>
                                    <div class="bd-faq-answer" style="font-size: <?php echo intval($answer_font_size); ?>px; color: <?php echo htmlspecialchars($answer_text_color); ?>; line-height: 1.7;">
                                        <?php echo $q['answer']; ?>
                                    </div>
                                </div>
                            </div>
                            <?php $step_num++;
                            $question_index++; ?>
                        <?php endforeach; ?>
                    </div>
                <?php else:
                ?>
                    <div class="bd-faq-accordion">
                        <?php foreach ($questions as $q): ?>
                            <div class="bd-faq-accordion-item">
                                <div class="bd-faq-header"
                                    onclick="toggleFaqAccordion(<?php echo $question_index; ?>)"
                                    role="button"
                                    tabindex="0"
                                    aria-expanded="false"
                                    aria-controls="faq-body-<?php echo $question_index; ?>">
                                    <h3 class="bd-faq-question"><?php echo htmlspecialchars(strip_tags($q['question'])); ?></h3>
                                    <i class="fa fa-chevron-down bd-faq-icon" id="faq-icon-<?php echo $question_index; ?>"></i>
                                </div>
                                <div class="bd-faq-body"
                                    id="faq-body-<?php echo $question_index; ?>"
                                    role="region"
                                    aria-labelledby="faq-question-<?php echo $question_index; ?>">
                                    <div class="bd-faq-answer">
                                        <?php echo $q['answer']; ?>
                                    </div>
                                </div>
                            </div>
                            <?php $question_index++; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif;
    ?>
</section>

<script>    
    function toggleFaqAccordion(index) {
        var header = document.querySelector('[onclick="toggleFaqAccordion(' + index + ')"]');
        var body = document.getElementById('faq-body-' + index);
        var icon = document.getElementById('faq-icon-' + index);

        if (!body || !header || !icon) return;

        var isActive = body.classList.contains('active') || (body.style.getPropertyValue('--faq-max-height') && body.style.getPropertyValue('--faq-max-height') !== '0px' && body.style.getPropertyValue('--faq-max-height') !== '0');

        document.querySelectorAll('.bd-faq-body').forEach(function(item) {
            if (item.id !== 'faq-body-' + index) {
                item.classList.remove('active');
                item.style.setProperty('--faq-max-height', '0px');
                item.style.maxHeight = '0px';
                if (item.previousElementSibling) {
                    item.previousElementSibling.classList.remove('active');
                    item.previousElementSibling.setAttribute('aria-expanded', 'false');
                }
                var otherIcon = item.previousElementSibling ? item.previousElementSibling.querySelector('.bd-faq-icon, .bd-faq-toggle-icon') : null;
                if (otherIcon) {
                    otherIcon.classList.remove('open');
                }
                var otherAccordionItem = item.closest('.bd-faq-accordion-item');
                if (otherAccordionItem) {
                    otherAccordionItem.classList.remove('open');
                }
            }
        });

        var accordionItem = header.closest('.bd-faq-accordion-item');

        if (isActive) {
            body.classList.remove('active');
            body.style.setProperty('--faq-max-height', '0px');
            body.style.maxHeight = '0px';
            header.classList.remove('active');
            header.setAttribute('aria-expanded', 'false');
            icon.classList.remove('open');
            if (accordionItem) accordionItem.classList.remove('open');
        } else {
            body.classList.add('active');
            header.classList.add('active');
            header.setAttribute('aria-expanded', 'true');
            icon.classList.add('open');
            if (accordionItem) accordionItem.classList.add('open');

            var maxHeight = body.scrollHeight + 'px';
            body.style.setProperty('--faq-max-height', maxHeight);
            body.style.maxHeight = maxHeight;

            setTimeout(function() {
                if (body.classList.contains('active')) {
                    var newMaxHeight = body.scrollHeight + 'px';
                    body.style.setProperty('--faq-max-height', newMaxHeight);
                    body.style.maxHeight = newMaxHeight;
                }
            }, 50);
        }
    }


    function toggleFaqCard(index) {
        var answer = document.getElementById('faq-card-answer-' + index);
        var icon = document.getElementById('faq-card-icon-' + index);

        if (!answer || !icon) return;

        var isExpanded = answer.style.maxHeight && answer.style.maxHeight !== '0px';

        if (isExpanded) {
            answer.style.maxHeight = '0px';
            icon.classList.remove('fa-minus');
            icon.classList.add('fa-plus');
        } else {
            var innerContent = answer.querySelector('div');
            if (innerContent) {
                answer.style.maxHeight = (innerContent.scrollHeight + 20) + 'px';
            } else {
                answer.style.maxHeight = answer.scrollHeight + 'px';
            }
            icon.classList.remove('fa-plus');
            icon.classList.add('fa-minus');

            setTimeout(function() {
                if (answer.style.maxHeight !== '0px') {
                    if (innerContent) {
                        answer.style.maxHeight = (innerContent.scrollHeight + 20) + 'px';
                    } else {
                        answer.style.maxHeight = answer.scrollHeight + 'px';
                    }
                }
            }, 50);
        }
    }

    function filterFaqSearch(groupId, searchTerm) {
        var results = document.getElementById('faq-search-results-' + groupId);
        if (!results) return;

        var items = results.querySelectorAll('.bd-faq-search-item');
        var term = searchTerm.toLowerCase().trim();

        items.forEach(function(item) {
            var question = item.getAttribute('data-question') || '';
            var answer = item.getAttribute('data-answer') || '';

            if (term === '' || question.indexOf(term) !== -1 || answer.indexOf(term) !== -1) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    }

    function switchFaqTab(tabIndex) {
        var tabs = document.querySelectorAll('.bd-faq-tab-btn');
        var contents = document.querySelectorAll('.bd-faq-tab-content');

        var activeColor = '#2AB27B';
        var inactiveColor = '#1f2937';

        tabs.forEach(function(tab, index) {
            if (index === tabIndex) {
                tab.classList.add('active');
                tab.style.borderBottomColor = activeColor;
                tab.style.color = activeColor;
                tab.style.fontWeight = '700';
            } else {
                tab.classList.remove('active');
                tab.style.borderBottomColor = 'transparent';
                tab.style.color = inactiveColor;
                tab.style.fontWeight = '600';
            }
        });

        contents.forEach(function(content, index) {
            if (index === tabIndex) {
                content.classList.add('active');
                content.style.display = 'block';
            } else {
                content.classList.remove('active');
                content.style.display = 'none';
            }
        });
    }

    function scrollToFaqGroup(groupId) {
        var element = document.getElementById('faq-group-' + groupId);
        if (element) {
            var offset = 100;
            var elementPosition = element.getBoundingClientRect().top;
            var offsetPosition = elementPosition + window.pageYOffset - offset;

            window.scrollTo({
                top: offsetPosition,
                behavior: 'smooth'
            });

            setTimeout(function() {
                updateSidebarActiveState();
            }, 500);
        }
    }

    function updateSidebarActiveState() {
        var sidebarLinks = document.querySelectorAll('.bd-faq-sidebar-nav a');
        var sections = document.querySelectorAll('[id^="faq-group-"]');

        if (sidebarLinks.length === 0 || sections.length === 0) {
            return;
        }

        var scrollPosition = window.pageYOffset || document.documentElement.scrollTop;
        var offset = 100;

        var activeSection = null;
        var activeIndex = -1;

        sections.forEach(function(section, index) {
            var rect = section.getBoundingClientRect();
            var sectionTop = rect.top + scrollPosition;
            var sectionBottom = sectionTop + rect.height;

            if (scrollPosition + offset >= sectionTop && scrollPosition + offset < sectionBottom) {
                activeSection = section;
                activeIndex = index;
            }
        });

        if (!activeSection) {
            for (var i = sections.length - 1; i >= 0; i--) {
                var rect = sections[i].getBoundingClientRect();
                var sectionTop = rect.top + scrollPosition;
                if (scrollPosition + offset >= sectionTop) {
                    activeSection = sections[i];
                    activeIndex = i;
                    break;
                }
            }
        }

        // Update sidebar links
        sidebarLinks.forEach(function(link, index) {
            var groupId = link.getAttribute('href').replace('#faq-group-', '');
            var isActive = (activeSection && activeSection.id === 'faq-group-' + groupId) ||
                (activeIndex === index && activeSection);

            var primaryColor = link.getAttribute('data-primary-color');
            var cardBgColor = link.getAttribute('data-card-bg-color');
            var textColor = link.getAttribute('data-text-color');

            if (isActive) {
                link.classList.add('active');
                if (primaryColor) link.style.background = primaryColor;
                link.style.color = '#ffffff';
                link.style.fontWeight = '600';
            } else {
                link.classList.remove('active');
                if (cardBgColor) link.style.background = cardBgColor;
                if (textColor) link.style.color = textColor;
                link.style.fontWeight = 'normal';
            }
        });
    }

    if (document.querySelector('.bd-faq-sidebar-nav')) {
        var sidebarLinks = document.querySelectorAll('.bd-faq-sidebar-nav a');
        sidebarLinks.forEach(function(link) {
            link.setAttribute('data-primary-color', '<?php echo htmlspecialchars($primary_color); ?>');
            link.setAttribute('data-card-bg-color', '<?php echo htmlspecialchars($card_background_color); ?>');
            link.setAttribute('data-text-color', '<?php echo htmlspecialchars($text_color); ?>');
        });

        var scrollTimeout;
        window.addEventListener('scroll', function() {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(updateSidebarActiveState, 100);
        });

        setTimeout(updateSidebarActiveState, 500);

        sidebarLinks.forEach(function(link) {
            link.addEventListener('click', function() {
                var groupId = this.getAttribute('href').replace('#faq-group-', '');
                scrollToFaqGroup(groupId);
            });
        });
    }

    function showFaqChatAnswer(index) {
        var answerDiv = document.getElementById('faq-chat-answer-' + index);
        if (!answerDiv) return;

        var isHidden = answerDiv.style.display === 'none' || !answerDiv.style.display || answerDiv.style.display === '';

        if (isHidden) {
            answerDiv.style.display = 'flex';
            setTimeout(function() {
                answerDiv.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest'
                });
            }, 100);
        } else {
            answerDiv.style.display = 'none';
        }
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            var target = e.target;
            if (target.classList.contains('bd-faq-header')) {
                e.preventDefault();
                var onclick = target.getAttribute('onclick');
                if (onclick) {
                    eval(onclick);
                }
            }
        }
    });

</script>
