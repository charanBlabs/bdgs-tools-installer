<?php

/**
 * FAQ Management Plugin - Admin Widget
 * Brilliant Directories Plugin
 * 
 * Main admin interface for managing FAQs with 5 tabs:
 * 1. Design - Template selection and preview
 * 2. Manage Groups - Create and manage FAQ groups
 * 3. Manage Questions - CRUD operations for questions
 * 4. Page Assignment - Assign groups to pages
 * 5. Set Priority - Group-specific priority management
 */

function faq_send_json_response($data)
{
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        error_log("FAQ send_json_response: JSON encode failed");
        $data = array('status' => 'error', 'message' => 'JSON encoding failed');
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    if (empty($json)) {
        error_log("FAQ send_json_response: Empty JSON output");
        $json = json_encode(array('status' => 'error', 'message' => 'Empty response'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    echo $json;
    flush();
    exit;
}

if (isset($_POST['bd_faq_ajax']) && $_POST['bd_faq_ajax'] == '1') {
    ini_set('display_errors', 0);
    error_reporting(0);

    try {
        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

        if (!class_exists('brilliantDirectories')) {
            faq_send_json_response(array('status' => 'error', 'message' => 'Brilliant Directories class not found'));
        }

        $db = brilliantDirectories::getDatabaseConfiguration('database');

        if (!$db) {
            faq_send_json_response(array('status' => 'error', 'message' => 'Database connection failed'));
        }

        // Auto-migrate legacy schema/data once (silent in AJAX mode).
        faq_run_auto_migrations($db, false);

        $action = isset($_POST['bd_faq_action']) ? trim($_POST['bd_faq_action']) : '';
        if (empty($action)) {
            faq_send_json_response(array('status' => 'error', 'message' => 'No action specified'));
        }

        $response = array('status' => 'error', 'message' => 'Invalid action');

        switch ($action) {
            case 'save_question':
                $response = handle_save_question($db);
                break;
            case 'update_question':
                $response = handle_update_question($db);
                break;
            case 'delete_question':
                $response = handle_delete_question($db);
                break;
            case 'bulk_delete_questions':
                $response = handle_bulk_delete_questions($db);
                break;
            case 'bulk_assign_questions':
                $response = handle_bulk_assign_questions($db);
                break;
            case 'bulk_delete_groups':
                $response = handle_bulk_delete_groups($db);
                break;
            case 'save_group':
                $response = handle_save_group($db);
                break;
            case 'delete_group':
                $response = handle_delete_group($db);
                break;
            case 'save_assignment':
                $response = handle_save_assignment($db);
                break;
            case 'delete_assignment':
                $response = handle_delete_assignment($db);
                break;
            case 'update_priority':
                $response = handle_update_priority($db);
                break;
            case 'save_design_setting':
                $response = handle_save_design_setting($db);
                break;
            case 'get_design_setting':
                $response = handle_get_design_setting($db);
                break;
            case 'reset_design_settings':
                $response = handle_reset_design_settings($db);
                break;
            case 'faq_live_preview':
                $response = handle_faq_live_preview($db);
                break;
            case 'faq_preview_document':
                $response = handle_faq_preview_document($db);
                break;
            case 'get_page_design_overrides':
                $response = handle_get_page_design_overrides($db);
                break;
            case 'save_page_design_setting':
                $response = handle_save_page_design_setting($db);
                break;
            case 'save_page_design_overrides_batch':
                $response = handle_save_page_design_overrides_batch($db);
                break;
            case 'clear_page_design_overrides':
                $response = handle_clear_page_design_overrides($db);
                break;
            case 'check_design_consistency':
                $response = handle_check_design_consistency($db);
                break;
            case 'get_page_design_override_sources':
                $response = handle_get_page_design_override_sources($db);
                break;
            case 'get_page_design_overrides_by_scope':
                $response = handle_get_page_design_overrides_by_scope($db);
                break;
            case 'get_questions_table':
                $response = handle_get_questions_table($db);
                break;
            case 'get_groups_table':
                $response = handle_get_groups_table($db);
                break;
            case 'get_assignments_table':
                $response = handle_get_assignments_table($db);
                break;
            case 'get_priority_questions':
                $response = handle_get_priority_questions($db);
                break;
            case 'get_question_groups':
                $response = handle_get_question_groups($db);
                break;
            case 'get_group_questions_list':
                $response = handle_get_group_questions_list($db);
                break;
            case 'include_questions_from_group':
                $response = handle_include_questions_from_group($db);
                break;
            case 'get_all_groups':
                $response = handle_get_all_groups($db);
                break;
            case 'save_order':
                $response = handle_save_order($db);
                break;
            case 'update_group_order':
                $response = handle_update_group_order($db);
                break;
            case 'delete_order':
                $response = handle_delete_order($db);
                break;
            case 'get_filtered_questions':
                $response = handle_get_filtered_questions($db);
                break;
            case 'get_filtered_groups':
                $response = handle_get_filtered_groups($db);
                break;
            case 'get_filtered_assignments':
                $response = handle_get_filtered_assignments($db);
                break;
            case 'get_page_groups_order':
                try {
                    $response = handle_get_page_groups_order($db);
                    if (!is_array($response) || empty($response)) {
                        $response = array('status' => 'error', 'message' => 'Invalid or empty response from handler');
                    }
                } catch (Exception $e) {
                    error_log("FAQ get_page_groups_order Exception: " . $e->getMessage() . " | Line: " . $e->getLine());
                    $response = array('status' => 'error', 'message' => 'Error: ' . $e->getMessage());
                } catch (Error $e) {
                    error_log("FAQ get_page_groups_order Fatal Error: " . $e->getMessage() . " | Line: " . $e->getLine());
                    $response = array('status' => 'error', 'message' => 'Fatal error: ' . $e->getMessage());
                } catch (Throwable $e) {
                    error_log("FAQ get_page_groups_order Throwable: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine());
                    $response = array('status' => 'error', 'message' => 'Error: ' . $e->getMessage());
                }

                // Final safety check - ensure response is always set
                if (!isset($response) || !is_array($response) || empty($response)) {
                    error_log("FAQ get_page_groups_order: Response still invalid after all checks. Response: " . var_export($response, true));
                    $response = array('status' => 'error', 'message' => 'Failed to get page groups order - no valid response');
                }
                break;
            case 'get_page_merged_groups':
                $response = handle_get_page_merged_groups($db);
                break;

            case 'get_page_all_assigned_groups':
                $response = handle_get_page_all_assigned_groups($db);
                break;
            case 'get_page_assigned_groups':
                $response = handle_get_page_assigned_groups($db);
                break;
            default:
                $response = array('status' => 'error', 'message' => 'Invalid action: ' . htmlspecialchars($action));
                break;
        }

        if (!is_array($response)) {
            error_log("FAQ Plugin: Response is not an array. Type: " . gettype($response) . ", Value: " . var_export($response, true));
            $response = array('status' => 'error', 'message' => 'Invalid response format from handler');
        }

        // Ensure response is not empty
        if (empty($response)) {
            error_log("FAQ Plugin: Response is empty for action: " . $action);
            $response = array('status' => 'error', 'message' => 'Empty response from handler');
        }

        faq_send_json_response($response);
    } catch (Exception $e) {
        error_log("FAQ Plugin Exception: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine() . " | Trace: " . substr($e->getTraceAsString(), 0, 500));
        faq_send_json_response(array('status' => 'error', 'message' => 'Server error: ' . $e->getMessage()));
    } catch (Error $e) {
        error_log("FAQ Plugin Fatal Error: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine() . " | Trace: " . substr($e->getTraceAsString(), 0, 500));
        faq_send_json_response(array('status' => 'error', 'message' => 'Fatal error: ' . $e->getMessage() . ' (check logs)'));
    } catch (Throwable $e) {
        error_log("FAQ Plugin Throwable: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine() . " | Trace: " . substr($e->getTraceAsString(), 0, 500));
        faq_send_json_response(array('status' => 'error', 'message' => 'Error: ' . $e->getMessage()));
    }
}

if (!ob_get_level()) {
    ob_start();
}

$db = brilliantDirectories::getDatabaseConfiguration('database');

if (!$db) {
    if (ob_get_level()) {
        ob_end_clean();
    }
    die('Database connection failed. Please check your configuration.');
}

$faq_migration_status = faq_run_auto_migrations($db, true);

// Simple function to get website design color defaults
function getWebsiteColorDefaults($db) {
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
                
                // Convert rgb() to hex using sscanf (most reliable method)
                if (stripos($color_value, 'rgb') !== false) {
                    $r = $g = $b = 0;
                    $scan_result = sscanf($color_value, "rgb(%d, %d, %d)", $r, $g, $b);
                    
                    if ($scan_result >= 3 && $r !== null && $g !== null && $b !== null) {
                        $color_value = sprintf('#%02x%02x%02x', $r, $g, $b);
                        $converted = true;
                    } else {
                        // Fallback: Use explode to extract numbers
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
                // Already hex format
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

/**
 * Parse background_color (hex or rgba) for color picker + alpha input.
 * Returns array('hex' => '#rrggbb', 'alpha' => 0-100).
 */
function faq_parse_background_color($bg) {
    $bg = trim($bg);
    if (preg_match('/^rgba?\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*(?:,\s*([\d.]+)\s*)?\)/', $bg, $m)) {
        $r = (int) $m[1]; $g = (int) $m[2]; $b = (int) $m[3];
        $a = isset($m[4]) ? (float) $m[4] : 1.0;
        $hex = '#' . sprintf('%02x%02x%02x', min(255, $r), min(255, $g), min(255, $b));
        return array('hex' => $hex, 'alpha' => (int) round($a * 100));
    }
    if (preg_match('/^#?([0-9a-fA-F]{6})$/', $bg, $m)) {
        $hex = (strpos($bg, '#') === 0) ? $bg : '#' . $m[1];
        return array('hex' => $hex, 'alpha' => 100);
    }
    return array('hex' => '#ffffff', 'alpha' => 100);
}

function faq_parse_color_rgb($color) {
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

function faq_contrast_text_color($background_color, $light = '#ffffff', $dark = '#111827') {
    $rgb = faq_parse_color_rgb($background_color);
    if (!is_array($rgb) || !isset($rgb['r'])) {
        return $dark;
    }
    $yiq = (($rgb['r'] * 299) + ($rgb['g'] * 587) + ($rgb['b'] * 114)) / 1000;
    return ($yiq >= 150) ? $dark : $light;
}

function handle_save_question($db)
{
    if (function_exists('mysql_real_escape_string') && !is_string($db)) {
        $question = isset($_POST['question']) ? mysql_real_escape_string($_POST['question'], $db) : '';
        $answer = isset($_POST['answer']) ? mysql_real_escape_string($_POST['answer'], $db) : '';
        $video_url = isset($_POST['video_url']) ? mysql_real_escape_string($_POST['video_url'], $db) : '';
    } else {
        $question = isset($_POST['question']) ? addslashes($_POST['question']) : '';
        $answer = isset($_POST['answer']) ? addslashes($_POST['answer']) : '';
        $video_url = isset($_POST['video_url']) ? addslashes($_POST['video_url']) : '';
    }

    $group_ids = isset($_POST['group_ids']) ? $_POST['group_ids'] : array();

    if (empty($question) || empty($answer)) {
        return array('status' => 'error', 'message' => 'Question and Answer are required');
    }

    if (empty($group_ids) || !is_array($group_ids)) {
        $unassigned_sql = "SELECT id FROM faq_groups WHERE group_slug = 'unassigned'";
        if (function_exists('mysql') && is_string($db)) {
            $unassigned_query = mysql($db, $unassigned_sql);
        } else {
            $unassigned_query = mysql_query($unassigned_sql, $db);
        }
        if (!$unassigned_query) {
            return array('status' => 'error', 'message' => 'Database error: Unable to find unassigned group');
        }
        $unassigned = mysql_fetch_assoc($unassigned_query);
        if (!$unassigned) {
            return array('status' => 'error', 'message' => 'Unassigned group not found. Please run installation SQL.');
        }
        $group_ids = array($unassigned['id']);
    }

    $video_url_field = !empty($video_url) ? ", video_url" : "";
    $video_url_value = !empty($video_url) ? ", '$video_url'" : "";
    $sql = "INSERT INTO faq_questions (question, answer$video_url_field) VALUES ('$question', '$answer'$video_url_value)";
    if (function_exists('mysql') && is_string($db)) {
        $result = mysql($db, $sql);
    } else {
        $result = mysql_query($sql, $db);
    }
    if (!$result) {
        $error_msg = '';
        if (function_exists('mysql_error') && !is_string($db)) {
            $error_msg = mysql_error($db);
        }
        error_log("Insert Question Error: " . $error_msg);
        return array('status' => 'error', 'message' => 'Failed to save question: ' . $error_msg);
    }

    $question_id = 0;
    if (function_exists('mysql_insert_id') && !is_string($db)) {
        $question_id = mysql_insert_id($db);
    } else if (function_exists('mysql') && is_string($db)) {
        $id_result = mysql($db, "SELECT LAST_INSERT_ID() as id");
        if ($id_result) {
            $id_row = mysql_fetch_assoc($id_result);
            $question_id = $id_row ? intval($id_row['id']) : 0;
        }
    }
    if (!$question_id) {
        return array('status' => 'error', 'message' => 'Failed to get question ID');
    }

    foreach ($group_ids as $group_id) {
        $group_id = intval($group_id);
        $max_order_sql = "SELECT MAX(sort_order) as max_order FROM faq_group_questions WHERE group_id = $group_id";
        if (function_exists('mysql') && is_string($db)) {
            $max_order_query = mysql($db, $max_order_sql);
        } else {
            $max_order_query = mysql_query($max_order_sql, $db);
        }
        if ($max_order_query) {
            $max_order = mysql_fetch_assoc($max_order_query);
            $sort_order = ($max_order && $max_order['max_order'] !== null) ? $max_order['max_order'] + 1 : 1;
        } else {
            $sort_order = 1;
        }

        $link_sql = "INSERT INTO faq_group_questions (group_id, question_id, sort_order) VALUES ($group_id, $question_id, $sort_order)";
        if (function_exists('mysql') && is_string($db)) {
            $link_result = mysql($db, $link_sql);
        } else {
            $link_result = mysql_query($link_sql, $db);
        }
        if (!$link_result) {
            $error_msg = '';
            if (function_exists('mysql_error') && !is_string($db)) {
                $error_msg = mysql_error($db);
            }
            error_log("Link Question to Group Error: " . $error_msg);
        }
    }

    $html = render_questions_table($db);
    return array('status' => 'success', 'message' => 'Question saved successfully', 'html_questions' => $html);
}

function handle_update_question($db)
{
    $question_id = intval($_POST['question_id']);

    if (function_exists('mysql_real_escape_string') && !is_string($db)) {
        $question = isset($_POST['question']) ? mysql_real_escape_string($_POST['question'], $db) : '';
        $answer = isset($_POST['answer']) ? mysql_real_escape_string($_POST['answer'], $db) : '';
        $video_url = isset($_POST['video_url']) ? mysql_real_escape_string($_POST['video_url'], $db) : '';
    } else {
        $question = isset($_POST['question']) ? addslashes($_POST['question']) : '';
        $answer = isset($_POST['answer']) ? addslashes($_POST['answer']) : '';
        $video_url = isset($_POST['video_url']) ? addslashes($_POST['video_url']) : '';
    }

    $group_ids = isset($_POST['group_ids']) ? $_POST['group_ids'] : array();

    if (empty($question) || empty($answer)) {
        return array('status' => 'error', 'message' => 'Question and Answer are required');
    }

    $video_url_clause = "";
    if (isset($video_url)) {
        if (!empty($video_url)) {
            $video_url_clause = ", video_url = '$video_url'";
        } else {
            $video_url_clause = ", video_url = NULL";
        }
    }
    $sql = "UPDATE faq_questions SET question = '$question', answer = '$answer'$video_url_clause WHERE id = $question_id";
    if (function_exists('mysql') && is_string($db)) {
        $update_result = mysql($db, $sql);
    } else {
        $update_result = mysql_query($sql, $db);
    }

    if (!$update_result) {
        $error_msg = '';
        if (function_exists('mysql_error') && !is_string($db)) {
            $error_msg = mysql_error($db);
        }
        error_log("Update Question Error: " . $error_msg);
        return array('status' => 'error', 'message' => 'Failed to update question: ' . $error_msg);
    }

    $delete_sql = "DELETE FROM faq_group_questions WHERE question_id = $question_id";
    if (function_exists('mysql') && is_string($db)) {
        mysql($db, $delete_sql);
    } else {
        mysql_query($delete_sql, $db);
    }

    if (empty($group_ids) || !is_array($group_ids)) {
        $unassigned_sql = "SELECT id FROM faq_groups WHERE group_slug = 'unassigned'";
        if (function_exists('mysql') && is_string($db)) {
            $unassigned_query = mysql($db, $unassigned_sql);
        } else {
            $unassigned_query = mysql_query($unassigned_sql, $db);
        }
        if (!$unassigned_query) {
            return array('status' => 'error', 'message' => 'Database error: Unable to find unassigned group');
        }
        $unassigned = mysql_fetch_assoc($unassigned_query);
        if (!$unassigned) {
            return array('status' => 'error', 'message' => 'Unassigned group not found. Please run installation SQL.');
        }
        $group_ids = array($unassigned['id']);
    }

    foreach ($group_ids as $group_id) {
        $group_id = intval($group_id);
        $max_order_sql = "SELECT MAX(sort_order) as max_order FROM faq_group_questions WHERE group_id = $group_id";
        if (function_exists('mysql') && is_string($db)) {
            $max_order_query = mysql($db, $max_order_sql);
        } else {
            $max_order_query = mysql_query($max_order_sql, $db);
        }
        if ($max_order_query) {
            $max_order = mysql_fetch_assoc($max_order_query);
            $sort_order = ($max_order && $max_order['max_order'] !== null) ? $max_order['max_order'] + 1 : 1;
        } else {
            $sort_order = 1;
        }

        $link_sql = "INSERT INTO faq_group_questions (group_id, question_id, sort_order) VALUES ($group_id, $question_id, $sort_order)";
        if (function_exists('mysql') && is_string($db)) {
            $link_result = mysql($db, $link_sql);
        } else {
            $link_result = mysql_query($link_sql, $db);
        }
        if (!$link_result) {
            $error_msg = '';
            if (function_exists('mysql_error') && !is_string($db)) {
                $error_msg = mysql_error($db);
            }
            error_log("Link Question to Group Error: " . $error_msg);
        }
    }

    $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $per_page = isset($_POST['per_page']) ? max(1, intval($_POST['per_page'])) : 25;
    $show_all = isset($_POST['show_all']) && $_POST['show_all'] == '1';
    $html = render_questions_table($db, $page, $per_page, $show_all);
    return array('status' => 'success', 'message' => 'Question updated successfully', 'html_questions' => $html);
}

function handle_delete_question($db)
{
    $question_id = intval($_POST['question_id']);
    if (function_exists('mysql') && is_string($db)) {
        mysql($db, "DELETE FROM faq_questions WHERE id = $question_id");
    } else {
        mysql_query("DELETE FROM faq_questions WHERE id = $question_id", $db);
    }
    $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $per_page = isset($_POST['per_page']) ? max(1, intval($_POST['per_page'])) : 25;
    $show_all = isset($_POST['show_all']) && $_POST['show_all'] == '1';
    $html = render_questions_table($db, $page, $per_page, $show_all);
    return array('status' => 'success', 'message' => 'Question deleted successfully', 'html_questions' => $html);
}

function handle_bulk_delete_questions($db)
{
    $question_ids = isset($_POST['question_ids']) ? $_POST['question_ids'] : array();
    
    if (empty($question_ids) || !is_array($question_ids)) {
        return array('status' => 'error', 'message' => 'No questions selected');
    }
    
    // Sanitize IDs
    $safe_ids = array_map('intval', $question_ids);
    $ids_string = implode(',', $safe_ids);
    
    // Delete all selected questions
    $sql = "DELETE FROM faq_questions WHERE id IN ($ids_string)";
    if (function_exists('mysql') && is_string($db)) {
        mysql($db, $sql);
    } else {
        mysql_query($sql, $db);
    }
    
    $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $per_page = isset($_POST['per_page']) ? max(1, intval($_POST['per_page'])) : 25;
    $show_all = isset($_POST['show_all']) && $_POST['show_all'] == '1';
    $html = render_questions_table($db, $page, $per_page, $show_all);
    
    return array('status' => 'success', 'message' => count($safe_ids) . ' questions deleted successfully', 'html_questions' => $html);
}

function handle_bulk_assign_questions($db)
{
    $question_ids = isset($_POST['question_ids']) ? $_POST['question_ids'] : array();
    $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
    
    if (empty($question_ids) || !is_array($question_ids)) {
        return array('status' => 'error', 'message' => 'No questions selected');
    }
    
    if ($group_id <= 0) {
        return array('status' => 'error', 'message' => 'Invalid group selected');
    }
    
    // Sanitize IDs
    $safe_ids = array_map('intval', $question_ids);
    
    // Assign each question to the group
    foreach ($safe_ids as $question_id) {
        $question_id = intval($question_id);
        if ($question_id <= 0) {
            continue;
        }

        // Check if assignment already exists
        $check_sql = "SELECT id FROM faq_group_questions WHERE question_id = $question_id AND group_id = $group_id";
        if (function_exists('mysql') && is_string($db)) {
            $check_result = mysql($db, $check_sql);
        } else {
            $check_result = mysql_query($check_sql, $db);
        }
        
        // Only insert if not already assigned
        if (!$check_result || mysql_num_rows($check_result) == 0) {
            $max_order_sql = "SELECT COALESCE(MAX(sort_order), 0) as max_order FROM faq_group_questions WHERE group_id = $group_id";
            if (function_exists('mysql') && is_string($db)) {
                $max_order_result = mysql($db, $max_order_sql);
            } else {
                $max_order_result = mysql_query($max_order_sql, $db);
            }
            $sort_order = 1;
            if ($max_order_result && mysql_num_rows($max_order_result) > 0) {
                $max_order_row = mysql_fetch_assoc($max_order_result);
                $sort_order = intval($max_order_row['max_order']) + 1;
            }

            $insert_sql = "INSERT INTO faq_group_questions (question_id, group_id, sort_order) VALUES ($question_id, $group_id, $sort_order)";
            if (function_exists('mysql') && is_string($db)) {
                mysql($db, $insert_sql);
            } else {
                mysql_query($insert_sql, $db);
            }
        }
    }
    
    $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $per_page = isset($_POST['per_page']) ? max(1, intval($_POST['per_page'])) : 25;
    $show_all = isset($_POST['show_all']) && $_POST['show_all'] == '1';
    $html = render_questions_table($db, $page, $per_page, $show_all);
    
    return array('status' => 'success', 'message' => count($safe_ids) . ' questions assigned successfully', 'html_questions' => $html);
}

function handle_bulk_delete_groups($db)
{
    $group_ids = isset($_POST['group_ids']) ? $_POST['group_ids'] : array();
    
    if (empty($group_ids) || !is_array($group_ids)) {
        return array('status' => 'error', 'message' => 'No groups selected');
    }
    
    // Sanitize IDs
    $safe_ids = array_map('intval', $group_ids);
    
    // Exclude system groups (global, unassigned)
    $excluded = array();
    foreach ($safe_ids as $group_id) {
        $check_sql = "SELECT group_slug FROM faq_groups WHERE id = $group_id";
        if (function_exists('mysql') && is_string($db)) {
            $check_result = mysql($db, $check_sql);
        } else {
            $check_result = mysql_query($check_sql, $db);
        }
        if ($check_result && mysql_num_rows($check_result) > 0) {
            $row = mysql_fetch_assoc($check_result);
            if (in_array($row['group_slug'], array('global', 'unassigned'))) {
                $excluded[] = $group_id;
            }
        }
    }
    
    // Filter out system groups
    $safe_ids = array_diff($safe_ids, $excluded);
    
    if (empty($safe_ids)) {
        return array('status' => 'error', 'message' => 'Only system groups were selected. System groups cannot be deleted.');
    }
    
    $ids_string = implode(',', $safe_ids);
    
    // Delete question-group associations first
    $sql_assoc = "DELETE FROM faq_group_questions WHERE group_id IN ($ids_string)";
    if (function_exists('mysql') && is_string($db)) {
        mysql($db, $sql_assoc);
    } else {
        mysql_query($sql_assoc, $db);
    }
    
    // Delete the groups
    $sql = "DELETE FROM faq_groups WHERE id IN ($ids_string)";
    if (function_exists('mysql') && is_string($db)) {
        mysql($db, $sql);
    } else {
        mysql_query($sql, $db);
    }
    
    $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $per_page = isset($_POST['per_page']) ? max(1, intval($_POST['per_page'])) : 25;
    $show_all = isset($_POST['show_all']) && $_POST['show_all'] == '1';
    $html = render_groups_table($db, $page, $per_page, $show_all);
    
    $message = count($safe_ids) . ' groups deleted successfully';
    if (!empty($excluded)) {
        $message .= ' (' . count($excluded) . ' system groups were skipped)';
    }
    
    return array('status' => 'success', 'message' => $message, 'html_groups' => $html);
}

function handle_save_group($db)
{
    if (!$db) {
        return array('status' => 'error', 'message' => 'Database connection not available');
    }

    try {
        if (!function_exists('render_groups_table')) {
            return array('status' => 'error', 'message' => 'Render function not available');
        }
        if (function_exists('mysql_real_escape_string') && !is_string($db)) {
            $group_name = isset($_POST['group_name']) ? mysql_real_escape_string($_POST['group_name'], $db) : '';
            $group_slug = isset($_POST['group_slug']) ? mysql_real_escape_string($_POST['group_slug'], $db) : '';
        } else {
            $group_name = isset($_POST['group_name']) ? addslashes($_POST['group_name']) : '';
            $group_slug = isset($_POST['group_slug']) ? addslashes($_POST['group_slug']) : '';
        }
        $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;

        if (empty($group_name)) {
            return array('status' => 'error', 'message' => 'Group name is required');
        }

        if ($group_id > 0) {
            $existing_sql = "SELECT group_slug FROM faq_groups WHERE id = $group_id";
            if (function_exists('mysql') && is_string($db)) {
                $existing_query = mysql($db, $existing_sql);
            } else {
                $existing_query = mysql_query($existing_sql, $db);
            }
            if (!$existing_query || mysql_num_rows($existing_query) == 0) {
                return array('status' => 'error', 'message' => 'Group not found');
            }
            $existing = mysql_fetch_assoc($existing_query);
            $group_slug = $existing['group_slug'];

            $update_sql = "UPDATE faq_groups SET group_name = '$group_name' WHERE id = $group_id";
            if (function_exists('mysql') && is_string($db)) {
                $result = mysql($db, $update_sql);
            } else {
                $result = mysql_query($update_sql, $db);
            }
            if (!$result) {
                $error_msg = '';
                if (function_exists('mysql_error') && !is_string($db)) {
                    $error_msg = mysql_error($db);
                }
                error_log("Update Group Error: " . $error_msg);
                return array('status' => 'error', 'message' => 'Failed to update group: ' . $error_msg);
            }
        } else {
            if (empty($group_slug)) {
                $group_slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $group_name));
                $group_slug = trim($group_slug, '-');
            }

            if (in_array($group_slug, array('global', 'unassigned'))) {
                return array('status' => 'error', 'message' => 'This slug is reserved for system use');
            }

            $check_sql = "SELECT id FROM faq_groups WHERE group_slug = '$group_slug'";
            if (function_exists('mysql') && is_string($db)) {
                $check_query = mysql($db, $check_sql);
            } else {
                $check_query = mysql_query($check_sql, $db);
            }
            if ($check_query && mysql_num_rows($check_query) > 0) {
                return array('status' => 'error', 'message' => 'A group with this slug already exists');
            }

            $insert_sql = "INSERT INTO faq_groups (group_name, group_slug) VALUES ('$group_name', '$group_slug')";
            if (function_exists('mysql') && is_string($db)) {
                $result = mysql($db, $insert_sql);
            } else {
                $result = mysql_query($insert_sql, $db);
            }
            if (!$result) {
                $error_msg = '';
                if (function_exists('mysql_error') && !is_string($db)) {
                    $error_msg = mysql_error($db);
                }
                error_log("Insert Group Error: " . $error_msg);
                return array('status' => 'error', 'message' => 'Failed to save group: ' . $error_msg);
            }
        }

        try {
            $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
            $per_page = isset($_POST['per_page']) ? max(1, intval($_POST['per_page'])) : 25;
            $show_all = isset($_POST['show_all']) && $_POST['show_all'] == '1';
            $html = render_groups_table($db, $page, $per_page, $show_all);
            if ($html === false || $html === null || !is_string($html)) {
                error_log("Save Group: render_groups_table returned invalid value. Type: " . gettype($html));
                return array('status' => 'error', 'message' => 'Failed to render groups table');
            }

            if (strpos($html, 'alert-danger') !== false || strpos($html, 'Fatal error') !== false || strpos($html, 'Error rendering') !== false) {
                error_log("Save Group: render_groups_table returned error HTML: " . substr($html, 0, 200));
                return array('status' => 'error', 'message' => 'Error rendering groups table. Please check database connection and table structure.');
            }
        } catch (Exception $e) {
            error_log("Save Group Render Exception: " . $e->getMessage());
            return array('status' => 'error', 'message' => 'Failed to render table: ' . $e->getMessage());
        } catch (Error $e) {
            error_log("Save Group Render Fatal: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine());
            return array('status' => 'error', 'message' => 'Fatal error rendering table: ' . $e->getMessage());
        }

        return array('status' => 'success', 'message' => 'Group saved successfully', 'html_groups' => $html);
    } catch (Exception $e) {
        error_log("Save Group Exception: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
        return array('status' => 'error', 'message' => 'Error: ' . $e->getMessage());
    } catch (Error $e) {
        error_log("Save Group Fatal Error: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine());
        return array('status' => 'error', 'message' => 'Fatal error: ' . $e->getMessage());
    }
}

function handle_delete_group($db)
{
    if (!$db) {
        return array('status' => 'error', 'message' => 'Database connection not available');
    }

    try {
        $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;

        if ($group_id <= 0) {
            error_log("Delete Group: Invalid group_id provided: " . (isset($_POST['group_id']) ? $_POST['group_id'] : 'not set'));
            return array('status' => 'error', 'message' => 'Invalid group ID provided');
        }

        $check_sql = "SELECT id, group_slug, group_name FROM faq_groups WHERE id = $group_id";
        if (function_exists('mysql') && is_string($db)) {
            $group_query = mysql($db, $check_sql);
        } else {
            $group_query = mysql_query($check_sql, $db);
        }

        if (!$group_query) {
            $error_msg = function_exists('mysql_error') && !is_string($db) ? mysql_error($db) : 'Query failed';
            error_log("Delete Group Query Error: " . $error_msg . " | SQL: " . $check_sql);
            return array('status' => 'error', 'message' => 'Failed to check group: ' . $error_msg);
        }

        $group = mysql_fetch_assoc($group_query);
        if (!$group) {
            error_log("Delete Group: Group with ID $group_id not found in database");
            return array('status' => 'error', 'message' => 'Group not found. It may have already been deleted.');
        }

        if (in_array($group['group_slug'], array('global', 'unassigned'))) {
            return array('status' => 'error', 'message' => 'System groups cannot be deleted');
        }

        $delete_sql = "DELETE FROM faq_groups WHERE id = $group_id";
        if (function_exists('mysql') && is_string($db)) {
            $result = mysql($db, $delete_sql);
        } else {
            $result = mysql_query($delete_sql, $db);
        }

        if (!$result) {
            $error_msg = function_exists('mysql_error') && !is_string($db) ? mysql_error($db) : 'Delete failed';
            error_log("Delete Group Delete Error: " . $error_msg . " | SQL: " . $delete_sql);
            return array('status' => 'error', 'message' => 'Failed to delete group: ' . $error_msg);
        }

        $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
        $per_page = isset($_POST['per_page']) ? max(1, intval($_POST['per_page'])) : 25;
        $show_all = isset($_POST['show_all']) && $_POST['show_all'] == '1';
        $html = render_groups_table($db, $page, $per_page, $show_all);
        return array('status' => 'success', 'message' => 'Group deleted successfully', 'html_groups' => $html);
    } catch (Exception $e) {
        error_log("Delete Group Exception: " . $e->getMessage());
        return array('status' => 'error', 'message' => 'Error: ' . $e->getMessage());
    }
}

function handle_save_assignment($db)
{
    $assignment_id = isset($_POST['assignment_id']) ? intval($_POST['assignment_id']) : 0;

    // Handle group_ids - sendAjax sends arrays with [] notation
    $group_ids = array();
    if (isset($_POST['group_ids']) && is_array($_POST['group_ids'])) {
        $group_ids = array_map('intval', $_POST['group_ids']);
        // Remove any zero or negative values
        $group_ids = array_filter($group_ids, function ($id) {
            return $id > 0;
        });
        $group_ids = array_values($group_ids); // Re-index array
    }

    $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
    $page_type = isset($_POST['page_type']) ? $_POST['page_type'] : 'static';
    $show_title = isset($_POST['show_title']) && $_POST['show_title'] == '1' ? 1 : 0;
    $merge_groups = isset($_POST['merge_groups']) && $_POST['merge_groups'] == '1' ? 1 : 0;

    if (function_exists('mysql_real_escape_string') && !is_string($db)) {
        $custom_label = isset($_POST['custom_label']) ? mysql_real_escape_string($_POST['custom_label'], $db) : '';
        $custom_title = isset($_POST['custom_title']) ? mysql_real_escape_string($_POST['custom_title'], $db) : '';
        $custom_subtitle = isset($_POST['custom_subtitle']) ? mysql_real_escape_string($_POST['custom_subtitle'], $db) : '';
        $cta_title = isset($_POST['cta_title']) ? mysql_real_escape_string($_POST['cta_title'], $db) : '';
        $cta_text = isset($_POST['cta_text']) ? mysql_real_escape_string($_POST['cta_text'], $db) : '';
        $cta_email = isset($_POST['cta_email']) ? mysql_real_escape_string($_POST['cta_email'], $db) : '';
    } else {
        $custom_label = isset($_POST['custom_label']) ? addslashes($_POST['custom_label']) : '';
        $custom_title = isset($_POST['custom_title']) ? addslashes($_POST['custom_title']) : '';
        $custom_subtitle = isset($_POST['custom_subtitle']) ? addslashes($_POST['custom_subtitle']) : '';
        $cta_title = isset($_POST['cta_title']) ? addslashes($_POST['cta_title']) : '';
        $cta_text = isset($_POST['cta_text']) ? addslashes($_POST['cta_text']) : '';
        $cta_email = isset($_POST['cta_email']) ? addslashes($_POST['cta_email']) : '';
    }

    // Extract page identifiers early so they're available throughout
    if ($page_type == 'post_type') {
        $data_id = intval($_POST['data_id']);
        $post_page_type = isset($_POST['post_page_type']) ? addslashes($_POST['post_page_type']) : 'search_result_page';
        $page_id = 0;
    } else {
        $page_id = intval($_POST['page_id']);
        $data_id = 0;
        $post_page_type = '';
    }

    // Get all currently assigned groups for this page (need this for both modes)
    $current_merged_groups = array();
    if ($page_type == 'post_type') {
        $get_current_sql = "SELECT group_id FROM faq_page_assignments WHERE data_id = $data_id AND page_type = '$post_page_type' AND merge_groups = 1";
    } else {
        $get_current_sql = "SELECT group_id FROM faq_page_assignments WHERE page_id = $page_id AND merge_groups = 1";
    }

    if (function_exists('mysql') && is_string($db)) {
        $current_result = mysql($db, $get_current_sql);
    } else {
        $current_result = mysql_query($get_current_sql, $db);
    }

    if ($current_result && mysql_num_rows($current_result) > 0) {
        while ($row = mysql_fetch_assoc($current_result)) {
            $current_merged_groups[] = intval($row['group_id']);
        }
    }

    if ($merge_groups == 1 && !empty($group_ids)) {
        // Find groups that were removed from merge (were merged before, now unchecked)
        $removed_group_ids = array_diff($current_merged_groups, $group_ids);

        // Delete ALL existing assignments for the selected groups on this page
        // (both merged and non-merged, to avoid duplicate key conflicts)
        $group_ids_sql = implode(',', array_map('intval', $group_ids));

        if ($page_type == 'post_type') {
            $delete_sql = "DELETE FROM faq_page_assignments WHERE data_id = $data_id AND page_type = '$post_page_type' AND group_id IN ($group_ids_sql)";
        } else {
            $delete_sql = "DELETE FROM faq_page_assignments WHERE page_id = $page_id AND group_id IN ($group_ids_sql)";
        }

        if (function_exists('mysql') && is_string($db)) {
            mysql($db, $delete_sql);
        } else {
            mysql_query($delete_sql, $db);
        }

        // Convert removed groups to separate assignments (preserve them)
        foreach ($removed_group_ids as $removed_gid) {
            if ($page_type == 'post_type') {
                $convert_sql = "INSERT INTO faq_page_assignments (data_id, page_type, group_id, custom_label, custom_title, custom_subtitle, cta_title, cta_text, cta_email, show_title, merge_groups) VALUES ($data_id, '$post_page_type', $removed_gid, '', '', '', '', '', '', 1, 0)";
            } else {
                $convert_sql = "INSERT INTO faq_page_assignments (page_id, group_id, custom_label, custom_title, custom_subtitle, cta_title, cta_text, cta_email, show_title, merge_groups) VALUES ($page_id, $removed_gid, '', '', '', '', '', '', 1, 0)";
            }

            if (function_exists('mysql') && is_string($db)) {
                mysql($db, $convert_sql);
            } else {
                mysql_query($convert_sql, $db);
            }
        }

        // Create new merged assignments for all selected groups
        foreach ($group_ids as $gid) {
            $gid = intval($gid);

            if ($gid > 0) {
                if ($page_type == 'post_type') {
                    $insert_sql = "INSERT INTO faq_page_assignments (data_id, page_type, group_id, custom_label, custom_title, custom_subtitle, cta_title, cta_text, cta_email, show_title, merge_groups) VALUES ($data_id, '$post_page_type', $gid, '$custom_label', '$custom_title', '$custom_subtitle', '$cta_title', '$cta_text', '$cta_email', $show_title, 1)";
                } else {
                    $insert_sql = "INSERT INTO faq_page_assignments (page_id, group_id, custom_label, custom_title, custom_subtitle, cta_title, cta_text, cta_email, show_title, merge_groups) VALUES ($page_id, $gid, '$custom_label', '$custom_title', '$custom_subtitle', '$cta_title', '$cta_text', '$cta_email', $show_title, 1)";
                }

                if (function_exists('mysql') && is_string($db)) {
                    $result = mysql($db, $insert_sql);
                } else {
                    $result = mysql_query($insert_sql, $db);
                }
            }
        }
    } else {
        // Non-merge mode: single group assignment
        if ($group_id <= 0 && !empty($group_ids)) {
            $group_id = intval($group_ids[0]);
        }

        if ($assignment_id > 0) {
            // Check if we're converting from merged to non-merged
            $check_merge_sql = "SELECT merge_groups FROM faq_page_assignments WHERE id = $assignment_id";
            if (function_exists('mysql') && is_string($db)) {
                $check_result = mysql($db, $check_merge_sql);
            } else {
                $check_result = mysql_query($check_merge_sql, $db);
            }

            $was_merged = false;
            if ($check_result && mysql_num_rows($check_result) > 0) {
                $check_row = mysql_fetch_assoc($check_result);
                $was_merged = ($check_row['merge_groups'] == 1);
            }

            if ($was_merged) {
                // Converting from merged to non-merged
                // Update ALL merged rows for this page to be separate individual assignments
                if ($page_type == 'post_type') {
                    $data_id = intval($_POST['data_id']);
                    $post_page_type = isset($_POST['post_page_type']) ? addslashes($_POST['post_page_type']) : 'search_result_page';
                    $update_all_sql = "UPDATE faq_page_assignments SET merge_groups = 0 WHERE data_id = $data_id AND page_type = '$post_page_type' AND merge_groups = 1";
                } else {
                    $page_id = intval($_POST['page_id']);
                    $update_all_sql = "UPDATE faq_page_assignments SET merge_groups = 0 WHERE page_id = $page_id AND merge_groups = 1";
                }

                if (function_exists('mysql') && is_string($db)) {
                    mysql($db, $update_all_sql);
                } else {
                    mysql_query($update_all_sql, $db);
                }
            } else {
                // Regular update of non-merged assignment
                if ($page_type == 'post_type') {
                    $data_id = intval($_POST['data_id']);
                    $post_page_type = isset($_POST['post_page_type']) ? addslashes($_POST['post_page_type']) : 'search_result_page';
                    $update_sql = "UPDATE faq_page_assignments SET data_id = $data_id, page_type = '$post_page_type', page_id = NULL, static_page = NULL, group_id = $group_id, custom_label = '$custom_label', custom_title = '$custom_title', custom_subtitle = '$custom_subtitle', cta_title = '$cta_title', cta_text = '$cta_text', cta_email = '$cta_email', show_title = $show_title, merge_groups = 0 WHERE id = $assignment_id";
                } else {
                    $page_id = intval($_POST['page_id']);
                    $update_sql = "UPDATE faq_page_assignments SET page_id = $page_id, data_id = NULL, page_type = NULL, static_page = NULL, group_id = $group_id, custom_label = '$custom_label', custom_title = '$custom_title', custom_subtitle = '$custom_subtitle', cta_title = '$cta_title', cta_text = '$cta_text', cta_email = '$cta_email', show_title = $show_title, merge_groups = 0 WHERE id = $assignment_id";
                }

                if (function_exists('mysql') && is_string($db)) {
                    mysql($db, $update_sql);
                } else {
                    mysql_query($update_sql, $db);
                }
            }
        } else {
            // New assignment in non-merge mode
            if ($page_type == 'post_type') {
                $data_id = intval($_POST['data_id']);
                $post_page_type = isset($_POST['post_page_type']) ? addslashes($_POST['post_page_type']) : 'search_result_page';
                $insert_sql = "INSERT INTO faq_page_assignments (data_id, page_type, group_id, custom_label, custom_title, custom_subtitle, cta_title, cta_text, cta_email, show_title, merge_groups) VALUES ($data_id, '$post_page_type', $group_id, '$custom_label', '$custom_title', '$custom_subtitle', '$cta_title', '$cta_text', '$cta_email', $show_title, 0)";
            } else {
                $page_id = intval($_POST['page_id']);
                $insert_sql = "INSERT INTO faq_page_assignments (page_id, group_id, custom_label, custom_title, custom_subtitle, cta_title, cta_text, cta_email, show_title, merge_groups) VALUES ($page_id, $group_id, '$custom_label', '$custom_title', '$custom_subtitle', '$cta_title', '$cta_text', '$cta_email', $show_title, 0)";
            }

            if (function_exists('mysql') && is_string($db)) {
                mysql($db, $insert_sql);
            } else {
                mysql_query($insert_sql, $db);
            }
        }
    }

    $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $per_page = isset($_POST['per_page']) ? max(1, intval($_POST['per_page'])) : 25;
    $show_all = isset($_POST['show_all']) && $_POST['show_all'] == '1';
    $html = render_assignments_table($db, $page, $per_page, $show_all);

    return array('status' => 'success', 'message' => 'Assignment saved successfully', 'html_assignments' => $html);
}

function handle_delete_assignment($db)
{
    $assignment_id = intval($_POST['assignment_id']);
    if (function_exists('mysql') && is_string($db)) {
        mysql($db, "DELETE FROM faq_page_assignments WHERE id = $assignment_id");
    } else {
        mysql_query("DELETE FROM faq_page_assignments WHERE id = $assignment_id", $db);
    }
    $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $per_page = isset($_POST['per_page']) ? max(1, intval($_POST['per_page'])) : 25;
    $show_all = isset($_POST['show_all']) && $_POST['show_all'] == '1';
    $html = render_assignments_table($db, $page, $per_page, $show_all);
    return array('status' => 'success', 'message' => 'Assignment deleted successfully', 'html_assignments' => $html);
}

function handle_update_priority($db)
{
    $group_id = intval($_POST['group_id']);

    $priorities = array();
    if (isset($_POST['priorities'])) {
        if (is_string($_POST['priorities'])) {
            $decoded = json_decode($_POST['priorities'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $priorities = $decoded;
            } else {
                $priorities = $_POST['priorities'];
            }
        } else if (is_array($_POST['priorities'])) {
            $priorities = $_POST['priorities'];
        }
    }

    if (empty($priorities) || !is_array($priorities)) {
        return array('status' => 'error', 'message' => 'No priorities provided or invalid format');
    }

    $normalized = 1;
    $updated_count = 0;
    foreach ($priorities as $item) {
        $question_id = 0;
        if (is_array($item)) {
            $question_id = isset($item['question_id']) ? intval($item['question_id']) : 0;
        } else if (is_object($item)) {
            $question_id = isset($item->question_id) ? intval($item->question_id) : 0;
        }

        if ($question_id <= 0) {
            continue;
        }

        $update_sql = "UPDATE faq_group_questions SET sort_order = $normalized WHERE group_id = $group_id AND question_id = $question_id";
        if (function_exists('mysql') && is_string($db)) {
            $update_result = mysql($db, $update_sql);
        } else {
            $update_result = mysql_query($update_sql, $db);
        }

        if (!$update_result) {
            $error_msg = '';
            if (function_exists('mysql_error') && !is_string($db)) {
                $error_msg = mysql_error($db);
            } else if (function_exists('mysql_error')) {
                $error_msg = mysql_error();
            }
            error_log("Priority Update Error: " . $error_msg . " | SQL: " . $update_sql);
            return array('status' => 'error', 'message' => 'Failed to update priority: ' . $error_msg);
        }

        $updated_count++;
        $normalized++;
    }

    if ($updated_count == 0) {
        return array('status' => 'error', 'message' => 'No priorities were updated');
    }

    $html_result = handle_get_priority_questions($db, $group_id);
    if ($html_result['status'] === 'error') {
        return $html_result;
    }
    return array('status' => 'success', 'message' => 'Priority updated successfully', 'html' => $html_result['html']);
}

function render_pagination_html($table_type, $current_page, $total_pages, $total_rows, $per_page, $show_all = false)
{
    if ($show_all || $total_pages <= 1) {
        return '';
    }

    $html = '<div class="faq-pagination-wrapper" style="display: flex; justify-content: space-between; align-items: center; padding: 16px; background: #f8f9fa; border-radius: 8px; flex-wrap: wrap; gap: 12px;">';

    $html .= '<div class="faq-pagination-info" style="color: #6c757d; font-size: 14px;">';
    $start = ($current_page - 1) * $per_page + 1;
    $end = min($current_page * $per_page, $total_rows);
    $html .= 'Showing ' . $start . ' to ' . $end . ' of ' . $total_rows . ' entries';
    $html .= '</div>';

    $html .= '<div class="faq-pagination-controls" style="display: flex; align-items: center; gap: 8px;">';

    // Pagination buttons use data attributes only; click is handled by delegated handler in JS (no inline onclick)
    $data_attr = ' data-faq-table="' . htmlspecialchars($table_type, ENT_QUOTES) . '"';
    if ($current_page > 1) {
        $prev_page = $current_page - 1;
        $html .= '<button type="button" class="faq-plugin-btn faq-plugin-btn-secondary faq-plugin-btn-sm faq-pagination-btn"' . $data_attr . ' data-faq-page="' . $prev_page . '" style="padding: 8px 16px;"><i class="fa fa-chevron-left"></i> Prev</button>';
    }

    $max_pages_to_show = 7;
    $start_page = max(1, $current_page - floor($max_pages_to_show / 2));
    $end_page = min($total_pages, $start_page + $max_pages_to_show - 1);

    if ($start_page > 1) {
        $html .= '<button type="button" class="faq-plugin-btn faq-plugin-btn-secondary faq-plugin-btn-sm faq-pagination-btn"' . $data_attr . ' data-faq-page="1" style="padding: 8px 12px;">1</button>';
        if ($start_page > 2) {
            $html .= '<span style="padding: 8px 4px; color: #6c757d;">...</span>';
        }
    }

    for ($i = $start_page; $i <= $end_page; $i++) {
        if ($i == $current_page) {
            $html .= '<button type="button" class="faq-plugin-btn faq-plugin-btn-primary faq-plugin-btn-sm" style="padding: 8px 12px; min-width: 40px;">' . $i . '</button>';
        } else {
            $html .= '<button type="button" class="faq-plugin-btn faq-plugin-btn-secondary faq-plugin-btn-sm faq-pagination-btn"' . $data_attr . ' data-faq-page="' . $i . '" style="padding: 8px 12px; min-width: 40px;">' . $i . '</button>';
        }
    }

    if ($end_page < $total_pages) {
        if ($end_page < $total_pages - 1) {
            $html .= '<span style="padding: 8px 4px; color: #6c757d;">...</span>';
        }
        $html .= '<button type="button" class="faq-plugin-btn faq-plugin-btn-secondary faq-plugin-btn-sm faq-pagination-btn"' . $data_attr . ' data-faq-page="' . $total_pages . '" style="padding: 8px 12px;">' . $total_pages . '</button>';
    }

    if ($current_page < $total_pages) {
        $next_page = $current_page + 1;
        $html .= '<button type="button" class="faq-plugin-btn faq-plugin-btn-secondary faq-plugin-btn-sm faq-pagination-btn"' . $data_attr . ' data-faq-page="' . $next_page . '" style="padding: 8px 16px;">Next <i class="fa fa-chevron-right"></i></button>';
    }

    $html .= '</div>';
    $html .= '</div>';

    return $html;
}

function handle_save_design_setting($db)
{
    if (function_exists('mysql_real_escape_string') && !is_string($db)) {
        $setting_key = mysql_real_escape_string($_POST['setting_key'], $db);
        $setting_value = mysql_real_escape_string($_POST['setting_value'], $db);
    } else {
        $setting_key = addslashes($_POST['setting_key']);
        $setting_value = addslashes($_POST['setting_value']);
    }

    $sql = "INSERT INTO faq_design_settings (setting_key, setting_value) VALUES ('$setting_key', '$setting_value') ON DUPLICATE KEY UPDATE setting_value = '$setting_value'";
    if (function_exists('mysql') && is_string($db)) {
        mysql($db, $sql);
    } else {
        mysql_query($sql, $db);
    }

    return array('status' => 'success', 'message' => 'Design setting saved successfully');
}

function handle_get_design_setting($db)
{
    // Support both old format (setting_key) and new format (page detection)
    if (isset($_POST['setting_key'])) {
        // Old format - single setting
        if (function_exists('mysql_real_escape_string') && !is_string($db)) {
            $setting_key = mysql_real_escape_string($_POST['setting_key'], $db);
        } else {
            $setting_key = addslashes($_POST['setting_key']);
        }

        $sql = "SELECT setting_value FROM faq_design_settings WHERE setting_key = '$setting_key' LIMIT 1";
        if (function_exists('mysql') && is_string($db)) {
            $result = mysql($db, $sql);
        } else {
            $result = mysql_query($sql, $db);
        }

        $setting_value = '';
        if ($result && mysql_num_rows($result) > 0) {
            $row = mysql_fetch_assoc($result);
            $setting_value = $row['setting_value'];
        }

        return array('status' => 'success', 'setting_value' => $setting_value);
    }

    // New format - page-specific settings detection
    $page_id = isset($_POST['page_id']) ? intval($_POST['page_id']) : 0;
    $data_id = isset($_POST['data_id']) ? intval($_POST['data_id']) : 0;
    $page_type = isset($_POST['page_type']) ? $_POST['page_type'] : '';

    // Determine the setting key based on page type
    $setting_key_suffix = '';
    if ($data_id > 0 && !empty($page_type)) {
        // Post type page
        $setting_key_suffix = '_post_' . $data_id . '_' . $page_type;
    } elseif ($page_id > 0) {
        // Static page
        $setting_key_suffix = '_page_' . $page_id;
    } else {
        // Global settings
        $setting_key_suffix = '';
    }

    // Get design preset and layout type
    $design_preset_key = 'design_preset' . $setting_key_suffix;
    $layout_type_key = 'layout_type' . $setting_key_suffix;

    if (function_exists('mysql_real_escape_string') && !is_string($db)) {
        $design_preset_key = mysql_real_escape_string($design_preset_key, $db);
        $layout_type_key = mysql_real_escape_string($layout_type_key, $db);
    } else {
        $design_preset_key = addslashes($design_preset_key);
        $layout_type_key = addslashes($layout_type_key);
    }

    // Get design preset - try page-specific first, then fall back to global
    $sql = "SELECT setting_value FROM faq_design_settings WHERE setting_key = '$design_preset_key' LIMIT 1";
    if (function_exists('mysql') && is_string($db)) {
        $result = mysql($db, $sql);
    } else {
        $result = mysql_query($sql, $db);
    }

    $design_preset = '';
    if ($result && mysql_num_rows($result) > 0) {
        $row = mysql_fetch_assoc($result);
        $design_preset = $row['setting_value'] ?: '';
    }

    // If no page-specific setting, fall back to global
    if (empty($design_preset) && !empty($setting_key_suffix)) {
        $global_key = 'design_preset';
        if (function_exists('mysql_real_escape_string') && !is_string($db)) {
            $global_key = mysql_real_escape_string($global_key, $db);
        } else {
            $global_key = addslashes($global_key);
        }

        $sql = "SELECT setting_value FROM faq_design_settings WHERE setting_key = '$global_key' LIMIT 1";
        if (function_exists('mysql') && is_string($db)) {
            $result = mysql($db, $sql);
        } else {
            $result = mysql_query($sql, $db);
        }

        if ($result && mysql_num_rows($result) > 0) {
            $row = mysql_fetch_assoc($result);
            $design_preset = $row['setting_value'] ?: 'custom';
        }
    }

    // Final fallback to 'custom'
    if (empty($design_preset)) {
        $design_preset = 'custom';
    }

    // Get layout type - try page-specific first, then fall back to global
    $sql = "SELECT setting_value FROM faq_design_settings WHERE setting_key = '$layout_type_key' LIMIT 1";
    if (function_exists('mysql') && is_string($db)) {
        $result = mysql($db, $sql);
    } else {
        $result = mysql_query($sql, $db);
    }

    $layout_type = '';
    if ($result && mysql_num_rows($result) > 0) {
        $row = mysql_fetch_assoc($result);
        $layout_type = $row['setting_value'] ?: '';
    }

    // If no page-specific setting, fall back to global
    if (empty($layout_type) && !empty($setting_key_suffix)) {
        $global_key = 'layout_type';
        if (function_exists('mysql_real_escape_string') && !is_string($db)) {
            $global_key = mysql_real_escape_string($global_key, $db);
        } else {
            $global_key = addslashes($global_key);
        }

        $sql = "SELECT setting_value FROM faq_design_settings WHERE setting_key = '$global_key' LIMIT 1";
        if (function_exists('mysql') && is_string($db)) {
            $result = mysql($db, $sql);
        } else {
            $result = mysql_query($sql, $db);
        }

        if ($result && mysql_num_rows($result) > 0) {
            $row = mysql_fetch_assoc($result);
            $layout_type = $row['setting_value'] ?: 'accordion';
        }
    }

    // Final fallback to 'accordion'
    if (empty($layout_type)) {
        $layout_type = 'accordion';
    }

    return array(
        'status' => 'success',
        'design_preset' => $design_preset,
        'layout_type' => $layout_type
    );
}

/**
 * Return HTML for the live FAQ preview (used in Custom Layout two-column UI).
 * Reads current faq_design_settings from DB and builds a minimal FAQ sample with inline styles.
 */
function handle_faq_live_preview($db)
{
    try {
        $design_settings = array();
        $settings_result = function_exists('mysql') && is_string($db)
            ? mysql($db, "SELECT setting_key, setting_value FROM faq_design_settings")
            : mysql_query("SELECT setting_key, setting_value FROM faq_design_settings", $db);
        if ($settings_result && mysql_num_rows($settings_result) > 0) {
            while ($row = mysql_fetch_assoc($settings_result)) {
                $design_settings[$row['setting_key']] = $row['setting_value'];
            }
        }
        // Allow POST overrides so live preview reflects current form (e.g. "How block appears") before save
        $overlay_keys = array('layout_type', 'title_alignment', 'font_family', 'premade_font_mode', 'template_lock_mode', 'primary_color', 'background_color', 'card_background_color', 'text_color', 'title_text_color', 'question_text_color', 'answer_text_color', 'title_font_size', 'question_font_size', 'answer_font_size', 'container_width', 'grid_columns', 'video_columns', 'card_radius', 'card_padding');
        foreach ($overlay_keys as $key) {
            if (isset($_POST[$key]) && (string) $_POST[$key] !== '') {
                $design_settings[$key] = $_POST[$key];
            }
        }
        $website_color_defaults = $db ? getWebsiteColorDefaults($db) : array('primary_color' => '#276ccf', 'background_color' => '#ffffff', 'card_background_color' => '#ffffff', 'text_color' => '#1f2937');
    $layout_type = isset($design_settings['layout_type']) ? $design_settings['layout_type'] : 'accordion';
    $title_alignment = isset($design_settings['title_alignment']) ? $design_settings['title_alignment'] : 'center';
    $font_family = isset($design_settings['font_family']) ? $design_settings['font_family'] : 'system';
    $premade_font_mode = isset($design_settings['premade_font_mode']) ? $design_settings['premade_font_mode'] : 'template_default';
    $template_lock_mode = isset($design_settings['template_lock_mode']) ? $design_settings['template_lock_mode'] : 'flexible';
    $allowed_premade_font_modes = array('template_default', 'website_font', 'custom_font');
    if (!in_array($premade_font_mode, $allowed_premade_font_modes, true)) {
        $premade_font_mode = 'template_default';
    }
    if (!in_array($template_lock_mode, array('strict', 'flexible'), true)) {
        $template_lock_mode = 'flexible';
    }
    $primary_color = (isset($design_settings['primary_color']) && trim((string) $design_settings['primary_color']) !== '')
        ? $design_settings['primary_color']
        : $website_color_defaults['primary_color'];
    $background_color = isset($design_settings['background_color']) ? $design_settings['background_color'] : $website_color_defaults['background_color'];
    $card_background_color = isset($design_settings['card_background_color']) ? $design_settings['card_background_color'] : $website_color_defaults['card_background_color'];
    $text_color = isset($design_settings['text_color']) ? $design_settings['text_color'] : $website_color_defaults['text_color'];
    $title_text_color = isset($design_settings['title_text_color']) ? $design_settings['title_text_color'] : $text_color;
    $question_text_color = (isset($design_settings['question_text_color']) && trim((string) $design_settings['question_text_color']) !== '')
        ? $design_settings['question_text_color']
        : $text_color;
    $answer_text_color = isset($design_settings['answer_text_color']) ? $design_settings['answer_text_color'] : $text_color;
    $title_font_size = isset($design_settings['title_font_size']) ? $design_settings['title_font_size'] : '32';
    $question_font_size = isset($design_settings['question_font_size']) ? $design_settings['question_font_size'] : '18';
    $answer_font_size = isset($design_settings['answer_font_size']) ? $design_settings['answer_font_size'] : '16';
    $container_width = isset($design_settings['container_width']) ? $design_settings['container_width'] : '900';
    $grid_columns = isset($design_settings['grid_columns']) ? intval($design_settings['grid_columns']) : 3;
    $video_columns = isset($design_settings['video_columns']) ? intval($design_settings['video_columns']) : 3;
    $card_radius = isset($design_settings['card_radius']) ? intval($design_settings['card_radius']) : 12;
    $card_padding = isset($design_settings['card_padding']) ? intval($design_settings['card_padding']) : 24;

    $font_stacks = array(
        'system' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
        'arial' => 'Arial, sans-serif',
        'helvetica' => 'Helvetica, Arial, sans-serif',
        'georgia' => 'Georgia, serif',
        'times' => '"Times New Roman", Times, serif',
        'courier' => '"Courier New", monospace',
        'verdana' => 'Verdana, sans-serif',
        'roboto' => '"Roboto", sans-serif',
        'open-sans' => '"Open Sans", sans-serif',
        'lato' => '"Lato", sans-serif',
        'montserrat' => '"Montserrat", sans-serif',
        'poppins' => '"Poppins", sans-serif',
        'inter' => '"Inter", sans-serif'
    );
    $font_family_css = isset($font_stacks[$font_family]) ? $font_stacks[$font_family] : $font_stacks['system'];
    $font_family_css_safe = preg_replace('/[^a-zA-Z0-9,\s"-]/', '', $font_family_css);
    if (empty($font_family_css_safe)) {
        $font_family_css_safe = 'inherit';
    }

    $max_width = (strpos($container_width, '%') !== false || $container_width === '100%') ? '100%' : (intval($container_width) . 'px');
    $sample_questions = array(
        array('q' => 'How do I get started?', 'a' => 'You can sign up from the homepage and choose a plan that fits your needs.'),
        array('q' => 'Can I change my plan later?', 'a' => 'Yes. You can upgrade or downgrade at any time from your account settings.'),
        array('q' => 'Where can I find help?', 'a' => 'Visit our help center or contact support via the link in the footer.')
    );

    $wrap_style = 'font-family:' . $font_family_css_safe . ';max-width:' . $max_width . ';margin:0 auto;background:' . htmlspecialchars($background_color) . ';color:' . htmlspecialchars($text_color) . ';padding:24px;border-radius:8px;box-sizing:border-box;';
    $html = '<div class="faq-live-preview-wrap faq-preview-layout-' . htmlspecialchars($layout_type) . '" style="' . $wrap_style . '">';
    $html .= '<h3 style="margin:0 0 16px 0;font-size:' . intval($title_font_size) . 'px;color:' . htmlspecialchars($title_text_color) . ';text-align:' . htmlspecialchars($title_alignment) . ';">Frequently Asked Questions</h3>';

    if ($layout_type === 'grid-card') {
        $card_style = 'background:' . htmlspecialchars($card_background_color) . ';color:' . htmlspecialchars($question_text_color) . ';padding:' . intval($card_padding) . 'px;border-radius:' . intval($card_radius) . 'px;box-shadow:0 1px 3px rgba(0,0,0,0.1);border-left:4px solid ' . htmlspecialchars($primary_color) . ';box-sizing:border-box;';
        $html .= '<div class="faq-preview-grid" style="display:grid;grid-template-columns:repeat(' . max(1, min(4, $grid_columns)) . ',1fr);gap:16px;">';
        foreach ($sample_questions as $i => $item) {
            $html .= '<div style="' . $card_style . '">';
            $html .= '<p style="margin:0 0 8px 0;font-size:' . intval($question_font_size) . 'px;font-weight:600;">' . htmlspecialchars($item['q']) . '</p>';
            $html .= '<p style="margin:0;font-size:' . intval($answer_font_size) . 'px;color:' . htmlspecialchars($answer_text_color) . ';">' . htmlspecialchars($item['a']) . '</p></div>';
        }
        $html .= '</div>';
    } elseif ($layout_type === 'tabbed') {
        $html .= '<div class="faq-preview-tabbed">';
        $html .= '<div class="faq-preview-tabs" style="display:flex;gap:4px;margin-bottom:12px;border-bottom:1px solid rgba(0,0,0,0.1);">';
        foreach ($sample_questions as $i => $item) {
            $active = $i === 0 ? ' faq-preview-tab-active' : '';
            $html .= '<button type="button" class="faq-preview-tab' . $active . '" data-tab="' . $i . '" style="padding:8px 14px;font-size:' . intval($question_font_size) . 'px;background:transparent;border:none;border-bottom:2px solid transparent;margin-bottom:-1px;cursor:pointer;color:' . htmlspecialchars($question_text_color) . ';">' . htmlspecialchars($item['q']) . '</button>';
        }
        $html .= '</div><div class="faq-preview-panels">';
        foreach ($sample_questions as $i => $item) {
            $hidden = $i !== 0 ? ' style="display:none;"' : '';
            $html .= '<div class="faq-preview-panel" data-tab="' . $i . '"' . $hidden . ' style="padding:12px 0;font-size:' . intval($answer_font_size) . 'px;color:' . htmlspecialchars($answer_text_color) . ';">' . htmlspecialchars($item['a']) . '</div>';
        }
        $html .= '</div></div>';
    } elseif ($layout_type === 'search-first') {
        $html .= '<div class="faq-preview-search-first">';
        $html .= '<input type="text" class="faq-preview-search" placeholder="Search FAQs…" style="width:100%;padding:10px 12px;margin-bottom:12px;font-size:14px;border:1px solid rgba(0,0,0,0.15);border-radius:4px;box-sizing:border-box;" readonly>';
        $html .= '<div class="faq-preview-accordion" style="border:1px solid rgba(0,0,0,0.08);border-radius:' . intval($card_radius) . 'px;overflow:hidden;">';
        foreach ($sample_questions as $i => $item) {
            $open_class = $i === 0 ? ' faq-preview-accordion-item-open' : '';
            $html .= '<div class="faq-preview-accordion-item' . $open_class . '" style="border-top:' . ($i > 0 ? '1px solid rgba(0,0,0,0.08);' : 'none;') . '">';
            $html .= '<div class="faq-preview-accordion-question" style="padding:14px 16px;background:' . htmlspecialchars($card_background_color) . ';color:' . htmlspecialchars($question_text_color) . ';font-size:' . intval($question_font_size) . 'px;font-weight:600;cursor:pointer;user-select:none;">' . htmlspecialchars($item['q']) . '</div>';
            $html .= '<div class="faq-preview-accordion-answer" style="padding:12px 16px;background:#fff;color:' . htmlspecialchars($answer_text_color) . ';font-size:' . intval($answer_font_size) . 'px;">' . htmlspecialchars($item['a']) . '</div>';
            $html .= '</div>';
        }
        $html .= '</div></div>';
    } else {
        // Accordion and all other layout types (single-column, sidebar, etc.): collapsible accordion
        $html .= '<div class="faq-preview-accordion" style="border:1px solid rgba(0,0,0,0.08);border-radius:' . intval($card_radius) . 'px;overflow:hidden;">';
        foreach ($sample_questions as $i => $item) {
            $open_class = $i === 0 ? ' faq-preview-accordion-item-open' : '';
            $html .= '<div class="faq-preview-accordion-item' . $open_class . '" style="border-top:' . ($i > 0 ? '1px solid rgba(0,0,0,0.08);' : 'none;') . '">';
            $html .= '<div class="faq-preview-accordion-question" style="padding:14px 16px;background:' . htmlspecialchars($card_background_color) . ';color:' . htmlspecialchars($question_text_color) . ';font-size:' . intval($question_font_size) . 'px;font-weight:600;cursor:pointer;user-select:none;">' . htmlspecialchars($item['q']) . '</div>';
            $html .= '<div class="faq-preview-accordion-answer" style="padding:12px 16px;background:#fff;color:' . htmlspecialchars($answer_text_color) . ';font-size:' . intval($answer_font_size) . 'px;">' . htmlspecialchars($item['a']) . '</div>';
            $html .= '</div>';
        }
        $html .= '</div>';
    }
    $html .= '</div>';
        return array('status' => 'success', 'preview_html' => $html);
    } catch (Exception $e) {
        return array('status' => 'error', 'message' => $e->getMessage(), 'preview_html' => '<p class="faq-live-preview-placeholder" style="color:#b91c1c;"><i class="fa fa-exclamation-triangle"></i> Preview could not be loaded.</p>');
    }
}

/**
 * Build preview HTML using the same structure and classes as the Global Renderer so it looks and behaves the same.
 * Works in the plugin admin (no widget/URL dependency). Returns section HTML + script and optional CDN CSS URL.
 */
function faq_build_preview_same_as_global_renderer($db)
{
    $design_settings = array();
    $settings_result = function_exists('mysql') && is_string($db)
        ? mysql($db, "SELECT setting_key, setting_value FROM faq_design_settings")
        : mysql_query("SELECT setting_key, setting_value FROM faq_design_settings", $db);
    if ($settings_result && mysql_num_rows($settings_result) > 0) {
        while ($row = mysql_fetch_assoc($settings_result)) {
            $design_settings[$row['setting_key']] = $row['setting_value'];
        }
    }
        $overlay_keys = array('layout_type', 'title_alignment', 'font_family', 'premade_font_mode', 'template_lock_mode', 'primary_color', 'background_color', 'card_background_color', 'text_color', 'title_text_color', 'question_text_color', 'answer_text_color', 'title_font_size', 'question_font_size', 'answer_font_size', 'container_width', 'grid_columns', 'video_columns', 'card_radius', 'card_padding', 'card_style', 'design_preset', 'cdn_base_url', 'card_icon_url', 'card_icon_shape');
    foreach ($overlay_keys as $key) {
        if (isset($_POST[$key]) && (string) $_POST[$key] !== '') {
            $design_settings[$key] = $_POST[$key];
        }
    }
    $website_color_defaults = $db ? getWebsiteColorDefaults($db) : array('primary_color' => '#276ccf', 'background_color' => '#ffffff', 'card_background_color' => '#ffffff', 'text_color' => '#1f2937');
    $layout_type = isset($design_settings['layout_type']) ? $design_settings['layout_type'] : 'accordion';
    $title_alignment = isset($design_settings['title_alignment']) ? $design_settings['title_alignment'] : 'center';
    $primary_color = (isset($design_settings['primary_color']) && trim((string) $design_settings['primary_color']) !== '')
        ? $design_settings['primary_color']
        : $website_color_defaults['primary_color'];
    $background_color = isset($design_settings['background_color']) ? $design_settings['background_color'] : $website_color_defaults['background_color'];
    $card_background_color = isset($design_settings['card_background_color']) ? $design_settings['card_background_color'] : $website_color_defaults['card_background_color'];
    $question_text_color = (isset($design_settings['question_text_color']) && trim((string) $design_settings['question_text_color']) !== '')
        ? $design_settings['question_text_color']
        : '#1f2937';
    $answer_text_color = isset($design_settings['answer_text_color']) ? $design_settings['answer_text_color'] : '#374151';
    $title_text_color = isset($design_settings['title_text_color']) ? $design_settings['title_text_color'] : $question_text_color;
    $title_font_size = isset($design_settings['title_font_size']) ? $design_settings['title_font_size'] : '32';
    $question_font_size = isset($design_settings['question_font_size']) ? $design_settings['question_font_size'] : '18';
    $answer_font_size = isset($design_settings['answer_font_size']) ? $design_settings['answer_font_size'] : '16';
    $container_width = isset($design_settings['container_width']) ? $design_settings['container_width'] : '900';
    $grid_columns = max(1, min(4, isset($design_settings['grid_columns']) ? intval($design_settings['grid_columns']) : 3));
    $video_columns = max(1, min(4, isset($design_settings['video_columns']) ? intval($design_settings['video_columns']) : 3));
    $card_radius = isset($design_settings['card_radius']) ? intval($design_settings['card_radius']) : 12;
    $card_padding = isset($design_settings['card_padding']) ? intval($design_settings['card_padding']) : 24;
    $design_preset = isset($design_settings['design_preset']) ? $design_settings['design_preset'] : 'custom';
    $cdn_base_url = isset($design_settings['cdn_base_url']) ? trim($design_settings['cdn_base_url']) : '';
    $card_icon_url = isset($design_settings['card_icon_url']) ? $design_settings['card_icon_url'] : '';
    $card_icon_shape = isset($design_settings['card_icon_shape']) ? $design_settings['card_icon_shape'] : 'circle';
    if (empty($cdn_base_url) && defined('FAQ_OWNER_CDN_URL')) {
        $cdn_base_url = FAQ_OWNER_CDN_URL;
    }
    if (empty($cdn_base_url)) {
        $cdn_base_url = 'https://cdn.bdgrowthsuite.com';
    }
    $font_stacks = array('system' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif', 'arial' => 'Arial, sans-serif', 'helvetica' => 'Helvetica, Arial, sans-serif', 'georgia' => 'Georgia, serif', 'times' => '"Times New Roman", Times, serif', 'courier' => '"Courier New", monospace', 'verdana' => 'Verdana, sans-serif', 'roboto' => '"Roboto", sans-serif', 'open-sans' => '"Open Sans", sans-serif', 'lato' => '"Lato", sans-serif', 'montserrat' => '"Montserrat", sans-serif', 'poppins' => '"Poppins", sans-serif', 'inter' => '"Inter", sans-serif');
    $font_family = isset($design_settings['font_family']) ? $design_settings['font_family'] : 'system';
    $premade_font_mode = isset($design_settings['premade_font_mode']) ? $design_settings['premade_font_mode'] : 'template_default';
    $template_lock_mode = isset($design_settings['template_lock_mode']) ? $design_settings['template_lock_mode'] : 'flexible';
    if (!in_array($premade_font_mode, array('template_default', 'website_font', 'custom_font'), true)) {
        $premade_font_mode = 'template_default';
    }
    if (!in_array($template_lock_mode, array('strict', 'flexible'), true)) {
        $template_lock_mode = 'flexible';
    }
    $font_family_css = isset($font_stacks[$font_family]) ? $font_stacks[$font_family] : $font_stacks['system'];
    $design_css_map = array('minimal' => 'faq-minimal.css', 'split' => 'faq-split.css', 'colorful' => 'faq-colorful.css', 'modern' => 'faq-modern.css', 'simple' => 'faq-simple.css', 'card' => 'faq-card.css', 'classic' => 'faq-classic.css');
    $is_custom = ($design_preset === 'custom' || !isset($design_css_map[$design_preset]));
    if (!$is_custom && $template_lock_mode === 'strict') {
        $strict_layout_map = array(
            'minimal' => 'accordion',
            'split' => 'accordion',
            'colorful' => 'accordion',
            'modern' => 'accordion',
            'simple' => 'accordion',
            'classic' => 'accordion',
            'card' => 'grid-card'
        );
        if (isset($strict_layout_map[$design_preset])) {
            $layout_type = $strict_layout_map[$design_preset];
        }
    }
    if (!$is_custom) {
        $template_bg_key = 'template_bg_' . $design_preset;
        $template_bg_color = isset($design_settings[$template_bg_key]) ? trim((string) $design_settings[$template_bg_key]) : '';
        if ($template_bg_color !== '') {
            $background_color = $template_bg_color;
        }
    }
    $css_url = null;
    if (!$is_custom && isset($design_css_map[$design_preset])) {
        $css_url = rtrim($cdn_base_url, '/') . '/tools/' . $design_css_map[$design_preset];
    }
    $container_width_trim = trim($container_width);
    $is_full_width = ($container_width_trim === '100%' || $container_width_trim === '100' || strpos($container_width_trim, '%') !== false);
    if ($is_full_width) {
        $max_width = '100%';
    } else {
        $container_width_num = intval($container_width);
        if ($container_width_num > 0 && $container_width_num < 300) {
            $container_width = '300';
        } elseif ($container_width_num > 2000) {
            $container_width = '2000';
        }
        $max_width = (intval($container_width) . 'px');
    }
    $card_style = isset($design_settings['card_style']) ? $design_settings['card_style'] : 'shadow';
    $minimal_line_color = '#d1d5db';
    $card_box_styles = array(
        'shadow' => 'box-shadow:0 2px 8px rgba(0,0,0,0.08);border:none;',
        'elevated' => 'box-shadow:0 8px 24px rgba(0,0,0,0.12);border:none;',
        'bordered' => 'box-shadow:none;border:2px solid ' . htmlspecialchars($primary_color) . ';',
        'simple' => 'box-shadow:none;border:1px solid #e5e7eb;',
        'flat' => 'box-shadow:none;border:none;',
        'minimal' => 'box-shadow:none;border:none;border-bottom:1px solid ' . $minimal_line_color . ';'
    );
    $item_box_style = isset($card_box_styles[$card_style]) ? $card_box_styles[$card_style] : $card_box_styles['shadow'];
    $sample = array(
        array('q' => 'How do I get started?', 'a' => 'You can sign up from the homepage and choose a plan that fits your needs.'),
        array('q' => 'Can I change my plan later?', 'a' => 'Yes. You can upgrade or downgrade at any time from your account settings.'),
        array('q' => 'Where can I find help?', 'a' => 'Visit our help center or contact support via the link in the footer.')
    );
    $width_style = $is_full_width ? 'width:100%;max-width:100%;' : 'max-width:' . $max_width . ';';
    $template_keys = array('minimal', 'split', 'colorful', 'modern', 'simple', 'card', 'classic');
    $is_premade_preview = in_array($design_preset, $template_keys, true);
    $container_font_style = '';
    if ($is_custom) {
        $container_font_style = 'font-family:' . $font_family_css . ';';
    } elseif ($is_premade_preview) {
        if ($premade_font_mode === 'website_font') {
            $container_font_style = 'font-family:inherit;';
        } elseif ($premade_font_mode === 'custom_font') {
            $container_font_style = 'font-family:' . $font_family_css . ';';
        }
    }
    $container_style = $width_style . 'margin:0 auto;padding:24px;box-sizing:border-box;background:' . htmlspecialchars($background_color) . ';' . $container_font_style;
    $out = '<section class="bd-faq-container bd-faq-layout-' . htmlspecialchars($layout_type) . '" style="' . $container_style . '" data-primary-color="' . htmlspecialchars($primary_color) . '">';
    $out .= '<h2 class="bd-faq-section-title" style="margin:0 0 16px 0;font-size:' . intval($title_font_size) . 'px;color:' . htmlspecialchars($title_text_color) . ';text-align:' . htmlspecialchars($title_alignment) . ';">Frequently Asked Questions</h2>';

    if ($layout_type === 'grid-card') {
        $out .= '<div class="bd-faq-grid-container" style="display:grid;grid-template-columns:repeat(' . $grid_columns . ',1fr);gap:16px;align-items:start;">';
        foreach ($sample as $i => $item) {
            $shadow = '0 2px 8px rgba(0,0,0,0.08)';
            $shadow_hover = '0 4px 12px rgba(0,0,0,0.12)';
            $onmouseover = "this.style.boxShadow='" . $shadow_hover . "'";
            $onmouseout = "this.style.boxShadow='" . $shadow . "'";
            $out .= '<div class="bd-faq-card-item" style="background:' . htmlspecialchars($card_background_color) . ';border-radius:' . $card_radius . 'px;padding:' . $card_padding . 'px;' . $item_box_style . 'cursor:pointer;display:flex;flex-direction:column;" onclick="toggleFaqCard(' . $i . ')" onmouseover="' . $onmouseover . '" onmouseout="' . $onmouseout . '">';
            $out .= '<div style="display:flex;align-items:center;gap:12px;">';
            if (!empty($card_icon_url)) {
                if ($card_icon_shape === 'circle') {
                    $out .= '<div style="flex-shrink:0;width:48px;height:48px;background:' . htmlspecialchars($primary_color) . ';border-radius:50%;display:flex;align-items:center;justify-content:center;overflow:hidden;"><img src="' . htmlspecialchars($card_icon_url) . '" alt="" style="width:60%;height:60%;object-fit:contain;"></div>';
                } else {
                    $out .= '<div style="flex-shrink:0;width:48px;height:48px;display:flex;align-items:center;justify-content:center;"><img src="' . htmlspecialchars($card_icon_url) . '" alt="" style="max-width:100%;max-height:100%;object-fit:contain;"></div>';
                }
            } else {
                $out .= '<div style="flex-shrink:0;width:40px;height:40px;background:' . htmlspecialchars($primary_color) . ';border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-size:18px;"><i class="fa fa-question"></i></div>';
            }
            $out .= '<div style="flex:1;min-width:0;"><h3 class="bd-faq-card-question" style="margin:0;font-size:' . intval($question_font_size) . 'px;color:' . htmlspecialchars($question_text_color) . ';font-weight:500;">' . htmlspecialchars($item['q']) . '</h3></div>';
            $out .= '<i class="fa fa-plus bd-faq-card-icon" id="faq-card-icon-' . $i . '" style="color:' . htmlspecialchars($primary_color) . ';font-size:16px;flex-shrink:0;"></i></div>';
            $out .= '<div class="bd-faq-card-answer" id="faq-card-answer-' . $i . '" style="max-height:0;overflow:hidden;transition:max-height 0.3s ease;font-size:' . intval($answer_font_size) . 'px;color:' . htmlspecialchars($answer_text_color) . ';"><div style="padding-top:16px;padding-left:52px;">' . htmlspecialchars($item['a']) . '</div></div>';
            $out .= '</div>';
        }
        $out .= '</div>';
    } elseif ($layout_type === 'tabbed') {
        $tab_labels = array('General', 'Account', 'Billing');
        $out .= '<div class="bd-faq-tabbed">';
        $out .= '<div class="bd-faq-tabs" style="display:flex;gap:4px;margin-bottom:24px;border-bottom:2px solid ' . htmlspecialchars($primary_color) . ';flex-wrap:wrap;background:transparent;">';
        foreach ($tab_labels as $tab_index => $label) {
            $active = $tab_index === 0;
            $border_color = $active ? htmlspecialchars($primary_color) : 'transparent';
            $color = $active ? htmlspecialchars($primary_color) : '#1f2937';
            $fw = $active ? '700' : '600';
            $out .= '<button class="bd-faq-tab-btn' . ($active ? ' active' : '') . '" onclick="switchFaqTab(' . $tab_index . ')" style="padding:12px 24px;background:transparent;border:none;border-bottom:3px solid ' . $border_color . ';color:' . $color . ';font-size:16px;font-weight:' . $fw . ';cursor:pointer;transition:all 0.3s;position:relative;top:2px;" id="faq-tab-' . $tab_index . '">' . htmlspecialchars($label) . '</button>';
        }
        $out .= '</div>';
        foreach ($tab_labels as $tab_index => $label) {
            $out .= '<div class="bd-faq-tab-content' . ($tab_index === 0 ? ' active' : '') . '" id="faq-tab-content-' . $tab_index . '" style="display:' . ($tab_index === 0 ? 'block' : 'none') . ';">';
            foreach ($sample as $item) {
                $out .= '<div class="bd-faq-tab-item" style="margin-bottom:24px;background:' . htmlspecialchars($card_background_color) . ';border-radius:' . intval($card_radius) . 'px;padding:20px;' . $item_box_style . '">';
                $out .= '<h3 class="bd-faq-question" style="font-size:' . intval($question_font_size) . 'px;color:' . htmlspecialchars($question_text_color) . ';margin:0 0 12px 0;font-weight:600;padding-bottom:12px;border-bottom:2px solid ' . htmlspecialchars($primary_color) . ';">' . htmlspecialchars($item['q']) . '</h3>';
                $out .= '<div class="bd-faq-answer" style="font-size:' . intval($answer_font_size) . 'px;color:' . htmlspecialchars($answer_text_color) . ';line-height:1.7;padding-top:8px;">' . htmlspecialchars($item['a']) . '</div></div>';
            }
            $out .= '</div>';
        }
        $out .= '</div>';
    } elseif ($layout_type === 'search-first') {
        $search_id = 'preview';
        $onkeyup_search = "filterFaqSearch('" . $search_id . "', this.value)";
        $out .= '<div class="bd-faq-search-first">';
        $out .= '<div class="bd-faq-search-bar" style="max-width:600px;width:100%;margin:0 auto 30px;position:relative;box-sizing:border-box;">';
        $out .= '<input type="text" id="faq-search-input-' . $search_id . '" placeholder="Search FAQs..." onkeyup="' . $onkeyup_search . '" style="width:100%;padding:14px 44px 14px 20px;border:2px solid ' . htmlspecialchars($primary_color) . ';border-radius:8px;font-size:16px;outline:none;box-sizing:border-box;">';
        $out .= '<i class="fa fa-search bd-faq-search-icon" style="position:absolute;right:16px;top:50%;transform:translateY(-50%);color:' . htmlspecialchars($primary_color) . ';pointer-events:none;display:block;font-size:18px;"></i></div>';
        $out .= '<div class="bd-faq-search-results" id="faq-search-results-' . $search_id . '" style="margin:0;padding:0;">';
        foreach ($sample as $item) {
            $q_lower = htmlspecialchars(strtolower($item['q']));
            $a_lower = htmlspecialchars(strtolower($item['a']));
            $out .= '<div class="bd-faq-search-item" style="margin-bottom:24px;padding:20px 0;border-bottom:1px solid rgba(0,0,0,0.08);" data-question="' . $q_lower . '" data-answer="' . $a_lower . '">';
            $out .= '<h3 class="bd-faq-question" style="font-size:' . intval($question_font_size) . 'px;color:' . htmlspecialchars($question_text_color) . ';margin:0 0 12px 0;font-weight:600;">' . htmlspecialchars($item['q']) . '</h3>';
            $out .= '<div class="bd-faq-answer" style="font-size:' . intval($answer_font_size) . 'px;color:' . htmlspecialchars($answer_text_color) . ';line-height:1.6;margin:0;padding:0;">' . htmlspecialchars($item['a']) . '</div></div>';
        }
        $out .= '</div></div>';
    } elseif ($layout_type === 'single-column') {
        $out .= '<div class="bd-faq-single-column" style="max-width:800px;margin:0 auto;">';
        foreach ($sample as $item) {
            $out .= '<div class="bd-faq-single-item" style="margin-bottom:24px;padding-bottom:24px;border-bottom:1px solid rgba(0,0,0,0.1);">';
            $out .= '<h3 class="bd-faq-question" style="font-size:' . intval($question_font_size) . 'px;color:' . htmlspecialchars($question_text_color) . ';margin-bottom:12px;font-weight:600;">' . htmlspecialchars($item['q']) . '</h3>';
            $out .= '<div class="bd-faq-answer" style="font-size:' . intval($answer_font_size) . 'px;color:' . htmlspecialchars($answer_text_color) . ';line-height:1.7;">' . htmlspecialchars($item['a']) . '</div></div>';
        }
        $out .= '</div>';
    } elseif ($layout_type === 'sidebar') {
        $out .= '<div class="bd-faq-sidebar" style="display:flex;gap:30px;">';
        $out .= '<div class="bd-faq-sidebar-nav" style="flex:0 0 250px;position:sticky;top:20px;height:fit-content;">';
        $out .= '<ul style="list-style:none;padding:0;margin:0;">';
        $sidebar_onclick = "scrollToFaqGroup('preview');return false;";
        $out .= '<li style="margin-bottom:8px;"><a href="#faq-group-preview" onclick="' . $sidebar_onclick . '" class="bd-faq-sidebar-link active" style="display:block;padding:12px 16px;background:' . htmlspecialchars($primary_color) . ';color:#ffffff;text-decoration:none;border-radius:6px;">FAQ Preview</a></li>';
        $out .= '</ul></div>';
        $out .= '<div class="bd-faq-sidebar-content" style="flex:1;">';
        $out .= '<div id="faq-group-preview" style="margin-bottom:40px;">';
        foreach ($sample as $i => $item) {
            $out .= '<div class="bd-faq-accordion-item" style="margin-bottom:16px;background:' . htmlspecialchars($card_background_color) . ';border-radius:' . intval($card_radius) . 'px;overflow:hidden;' . $item_box_style . '">';
            $out .= '<div class="bd-faq-header" onclick="toggleFaqAccordion(' . $i . ')" style="padding:16px;cursor:pointer;display:flex;justify-content:space-between;align-items:center;">';
            $out .= '<h3 class="bd-faq-question" style="font-size:' . intval($question_font_size) . 'px;color:' . htmlspecialchars($question_text_color) . ';margin:0;">' . htmlspecialchars($item['q']) . '</h3>';
            $out .= '<i class="fa fa-chevron-down bd-faq-icon" id="faq-icon-' . $i . '"></i></div>';
            $out .= '<div class="bd-faq-body" id="faq-body-' . $i . '" style="max-height:0;overflow:hidden;transition:max-height 0.3s ease;">';
            $out .= '<div class="bd-faq-answer" style="padding:16px;font-size:' . intval($answer_font_size) . 'px;color:' . htmlspecialchars($answer_text_color) . ';">' . htmlspecialchars($item['a']) . '</div></div></div>';
        }
        $out .= '</div></div></div>';
    } elseif ($layout_type === 'persona-based') {
        $out .= '<div class="bd-faq-persona">';
        $persona_labels = array('General', 'Account');
        foreach ($persona_labels as $pi => $pname) {
            $out .= '<div class="bd-faq-persona-section" style="margin-bottom:40px;padding:24px;background:' . htmlspecialchars($card_background_color) . ';border-radius:' . intval($card_radius) . 'px;">';
            $out .= '<h3 style="font-size:20px;color:' . htmlspecialchars($primary_color) . ';margin-bottom:20px;font-weight:600;">' . htmlspecialchars($pname) . '</h3>';
            foreach ($sample as $i => $item) {
                $idx = $pi * 10 + $i;
                $out .= '<div class="bd-faq-accordion-item" style="margin-bottom:12px;">';
                $out .= '<div class="bd-faq-header" onclick="toggleFaqAccordion(' . $idx . ')" style="padding:14px;cursor:pointer;display:flex;justify-content:space-between;align-items:center;background:rgba(0,0,0,0.02);border-radius:6px;">';
                $out .= '<h4 class="bd-faq-question" style="font-size:' . intval($question_font_size) . 'px;color:' . htmlspecialchars($question_text_color) . ';margin:0;font-weight:500;">' . htmlspecialchars($item['q']) . '</h4>';
                $out .= '<i class="fa fa-chevron-down bd-faq-icon" id="faq-icon-' . $idx . '"></i></div>';
                $out .= '<div class="bd-faq-body" id="faq-body-' . $idx . '" style="max-height:0;overflow:hidden;transition:max-height 0.3s ease;">';
                $out .= '<div class="bd-faq-answer" style="padding:14px;font-size:' . intval($answer_font_size) . 'px;color:' . htmlspecialchars($answer_text_color) . ';">' . htmlspecialchars($item['a']) . '</div></div></div>';
            }
            $out .= '</div>';
        }
        $out .= '</div>';
    } elseif ($layout_type === 'conversational') {
        $out .= '<div class="bd-faq-conversational" style="max-width:800px;margin:0 auto;">';
        $out .= '<div class="bd-faq-chat-container" style="background:' . htmlspecialchars($card_background_color) . ';border-radius:' . intval($card_radius) . 'px;padding:24px;min-height:400px;display:flex;flex-direction:column;">';
        $out .= '<div class="bd-faq-chat-messages" id="faq-chat-messages-preview" style="flex:1;margin-bottom:20px;overflow-y:auto;">';
        $out .= '<div class="bd-faq-chat-bot" style="margin-bottom:16px;display:flex;gap:12px;">';
        $out .= '<div style="width:40px;height:40px;background:' . htmlspecialchars($primary_color) . ';border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;flex-shrink:0;"><i class="fa fa-robot"></i></div>';
        $out .= '<div style="flex:1;background:rgba(0,0,0,0.05);padding:12px 16px;border-radius:12px;color:' . htmlspecialchars($question_text_color) . ';">Hi! I can help answer your questions. Click on any question below to see the answer.</div></div>';
        foreach ($sample as $i => $item) {
            $out .= '<div class="bd-faq-chat-user" style="margin-bottom:16px;display:flex;gap:12px;justify-content:flex-end;">';
            $out .= '<div style="flex:1;background:' . htmlspecialchars($primary_color) . ';color:white;padding:12px 16px;border-radius:12px;text-align:right;cursor:pointer;" onclick="showFaqChatAnswer(' . $i . ')">' . htmlspecialchars($item['q']) . '</div>';
            $out .= '<div style="width:40px;height:40px;background:rgba(0,0,0,0.1);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="fa fa-user"></i></div></div>';
            $out .= '<div class="bd-faq-chat-bot-answer" id="faq-chat-answer-' . $i . '" style="display:none;margin-bottom:16px;gap:12px;">';
            $out .= '<div style="width:40px;height:40px;background:' . htmlspecialchars($primary_color) . ';border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;flex-shrink:0;"><i class="fa fa-robot"></i></div>';
            $out .= '<div style="flex:1;background:rgba(0,0,0,0.05);padding:12px 16px;border-radius:12px;color:' . htmlspecialchars($answer_text_color) . ';font-size:' . intval($answer_font_size) . 'px;">' . htmlspecialchars($item['a']) . '</div></div>';
        }
        $out .= '</div></div></div>';
    } elseif ($layout_type === 'video-multimedia') {
        $out .= '<div class="bd-faq-video" style="display:grid;grid-template-columns:repeat(' . $video_columns . ',1fr);gap:24px;">';
        foreach ($sample as $i => $item) {
            $out .= '<div class="bd-faq-video-item" style="background:' . htmlspecialchars($card_background_color) . ';border-radius:' . intval($card_radius) . 'px;padding:24px;box-shadow:0 4px 12px rgba(0,0,0,0.1);">';
            $out .= '<div style="width:100%;height:120px;background:rgba(0,0,0,0.1);border-radius:8px;margin-bottom:16px;display:flex;align-items:center;justify-content:center;"><i class="fa fa-play-circle" style="font-size:48px;color:' . htmlspecialchars($primary_color) . ';"></i></div>';
            $out .= '<h3 class="bd-faq-question" style="font-size:' . intval($question_font_size) . 'px;color:' . htmlspecialchars($question_text_color) . ';margin-bottom:12px;font-weight:600;">' . htmlspecialchars($item['q']) . '</h3>';
            $out .= '<div class="bd-faq-answer" style="font-size:' . intval($answer_font_size) . 'px;color:' . htmlspecialchars($answer_text_color) . ';line-height:1.6;">' . htmlspecialchars($item['a']) . '</div></div>';
        }
        $out .= '</div>';
    } elseif ($layout_type === 'step-by-step') {
        $out .= '<div class="bd-faq-step-by-step" style="max-width:900px;margin:0 auto;position:relative;">';
        $out .= '<div style="position:absolute;left:20px;top:0;bottom:0;width:2px;background:' . htmlspecialchars($primary_color) . ';"></div>';
        $step_num = 1;
        foreach ($sample as $item) {
            $out .= '<div class="bd-faq-step-item" style="position:relative;margin-bottom:32px;padding-left:60px;">';
            $out .= '<div style="position:absolute;left:8px;width:24px;height:24px;background:' . htmlspecialchars($primary_color) . ';border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:bold;font-size:14px;">' . $step_num . '</div>';
            $out .= '<div style="background:' . htmlspecialchars($card_background_color) . ';padding:20px;border-radius:' . intval($card_radius) . 'px;box-shadow:0 2px 8px rgba(0,0,0,0.1);">';
            $out .= '<h3 class="bd-faq-question" style="font-size:' . intval($question_font_size) . 'px;color:' . htmlspecialchars($question_text_color) . ';margin-bottom:12px;font-weight:600;">' . htmlspecialchars($item['q']) . '</h3>';
            $out .= '<div class="bd-faq-answer" style="font-size:' . intval($answer_font_size) . 'px;color:' . htmlspecialchars($answer_text_color) . ';line-height:1.7;">' . htmlspecialchars($item['a']) . '</div></div></div>';
            $step_num++;
        }
        $out .= '</div>';
    } else {
        $out .= '<div class="bd-faq-accordion">';
        foreach ($sample as $i => $item) {
            $out .= '<div class="bd-faq-accordion-item" style="margin-bottom:16px;background:' . htmlspecialchars($card_background_color) . ';border-radius:' . intval($card_radius) . 'px;overflow:hidden;' . $item_box_style . '">';
            $out .= '<div class="bd-faq-header" onclick="toggleFaqAccordion(' . $i . ')" style="padding:16px;cursor:pointer;display:flex;justify-content:space-between;align-items:center;">';
            $out .= '<h3 class="bd-faq-question" style="margin:0;font-size:' . intval($question_font_size) . 'px;color:' . htmlspecialchars($question_text_color) . ';font-weight:600;">' . htmlspecialchars($item['q']) . '</h3>';
            $out .= '<i class="fa fa-chevron-down bd-faq-icon" id="faq-icon-' . $i . '"></i></div>';
            $out .= '<div class="bd-faq-body" id="faq-body-' . $i . '" style="max-height:0;overflow:hidden;transition:max-height 0.3s ease;">';
            $out .= '<div class="bd-faq-answer" style="padding:16px;font-size:' . intval($answer_font_size) . 'px;color:' . htmlspecialchars($answer_text_color) . ';">' . htmlspecialchars($item['a']) . '</div></div></div>';
        }
        $out .= '</div>';
    }
    $out .= '</section>';
    $script = '<script>
    function toggleFaqAccordion(index) {
        var body = document.getElementById("faq-body-" + index);
        var header = body ? body.previousElementSibling : null;
        var icon = document.getElementById("faq-icon-" + index);
        if (!body || !header) return;
        var isActive = body.classList.contains("active");
        document.querySelectorAll(".bd-faq-body").forEach(function(el) { el.classList.remove("active"); el.style.maxHeight = "0px"; });
        document.querySelectorAll(".bd-faq-header").forEach(function(h) { h.classList.remove("active"); });
        document.querySelectorAll(".bd-faq-icon").forEach(function(i) { i.classList.remove("open"); i.className = i.className.replace("fa-chevron-up","fa-chevron-down"); });
        if (!isActive) {
            body.classList.add("active"); body.style.maxHeight = body.scrollHeight + "px";
            header.classList.add("active");
            if (icon) { icon.classList.add("open"); icon.className = icon.className.replace("fa-chevron-down","fa-chevron-up"); }
        }
    }
    function toggleFaqCard(index) {
        var answer = document.getElementById("faq-card-answer-" + index);
        var icon = document.getElementById("faq-card-icon-" + index);
        if (!answer || !icon) return;
        var isExpanded = answer.style.maxHeight && answer.style.maxHeight !== "0px";
        document.querySelectorAll(".bd-faq-card-answer").forEach(function(el) {
            if (el !== answer) { el.style.maxHeight = "0px"; }
        });
        document.querySelectorAll(".bd-faq-card-icon").forEach(function(ic) {
            if (ic !== icon) { ic.classList.remove("fa-minus"); ic.classList.add("fa-plus"); }
        });
        if (isExpanded) { answer.style.maxHeight = "0px"; icon.classList.remove("fa-minus"); icon.classList.add("fa-plus"); }
        else {
            var inner = answer.querySelector("div");
            answer.style.maxHeight = (inner ? inner.scrollHeight + 20 : answer.scrollHeight) + "px";
            icon.classList.remove("fa-plus"); icon.classList.add("fa-minus");
        }
    }
    function switchFaqTab(tabIndex) {
        var tabs = document.querySelectorAll(".bd-faq-tab-btn");
        var contents = document.querySelectorAll(".bd-faq-tab-content");
        var activeColor = "#2AB27B";
        var inactiveColor = "#1f2937";
        if (tabs.length && tabs[0].style) {
            var primaryEl = document.querySelector(".bd-faq-container");
            if (primaryEl && primaryEl.getAttribute("data-primary-color")) activeColor = primaryEl.getAttribute("data-primary-color");
        }
        for (var i = 0; i < tabs.length; i++) {
            if (i === tabIndex) {
                tabs[i].classList.add("active");
                tabs[i].style.borderBottomColor = activeColor;
                tabs[i].style.color = activeColor;
                tabs[i].style.fontWeight = "700";
            } else {
                tabs[i].classList.remove("active");
                tabs[i].style.borderBottomColor = "transparent";
                tabs[i].style.color = inactiveColor;
                tabs[i].style.fontWeight = "600";
            }
        }
        for (var j = 0; j < contents.length; j++) {
            contents[j].style.display = (j === tabIndex) ? "block" : "none";
            contents[j].classList.toggle("active", j === tabIndex);
        }
    }
    function filterFaqSearch(groupId, searchTerm) {
        var results = document.getElementById("faq-search-results-" + groupId);
        if (!results) return;
        var items = results.querySelectorAll(".bd-faq-search-item");
        var term = (searchTerm || "").toLowerCase().trim();
        for (var k = 0; k < items.length; k++) {
            var q = (items[k].getAttribute("data-question") || "");
            var a = (items[k].getAttribute("data-answer") || "");
            items[k].style.display = (term === "" || q.indexOf(term) !== -1 || a.indexOf(term) !== -1) ? "block" : "none";
        }
    }
    function scrollToFaqGroup(groupId) {
        var el = document.getElementById("faq-group-" + groupId);
        if (el) el.scrollIntoView({ behavior: "smooth", block: "start" });
    }
    function showFaqChatAnswer(index) {
        var el = document.getElementById("faq-chat-answer-" + index);
        if (!el) return;
        var isVisible = el.style.display === "flex";
        document.querySelectorAll(".bd-faq-chat-bot-answer").forEach(function(a) { a.style.display = "none"; });
        if (!isVisible) { el.style.display = "flex"; }
    }
    </script>';
    $preview_font_family_css = 'inherit';
    if ($is_custom || $premade_font_mode === 'custom_font') {
        $preview_font_family_css = $font_family_css;
    }
    return array('html' => $out . $script, 'css_url' => $css_url, 'primary_color' => $primary_color, 'font_family_css' => $preview_font_family_css);
}

/**
 * Return full HTML document for iframe live preview.
 * Uses preview markup that matches the Global Renderer (same classes/structure) so it works in the plugin and looks like the frontend.
 */
function handle_faq_preview_document($db)
{
    try {
        $result = faq_build_preview_same_as_global_renderer($db);
        $body = isset($result['html']) ? $result['html'] : '';
        $css_url = isset($result['css_url']) ? $result['css_url'] : null;
        $primary_color = (isset($result['primary_color']) && trim((string) $result['primary_color']) !== '')
            ? $result['primary_color']
            : '#276ccf';
        $active_header_text_color = faq_contrast_text_color($primary_color, '#ffffff', '#111827');
        $font_family_css = isset($result['font_family_css']) ? $result['font_family_css'] : 'system-ui, sans-serif';
        $font_family_css_safe = preg_replace('/[^a-zA-Z0-9,\s"-]/', '', $font_family_css);
        if (empty($font_family_css_safe)) {
            $font_family_css_safe = 'inherit';
        }
        $head = '<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
        $head .= '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" crossorigin="anonymous">';
        if ($css_url) {
            $head .= '<link rel="stylesheet" href="' . htmlspecialchars($css_url) . '" crossorigin="anonymous">';
        } else {
            $head .= '<style>.bd-faq-container{width:100%;margin:0 auto;}.bd-faq-accordion-item{border:1px solid #e5e7eb;border-radius:8px;margin-bottom:1rem;}.bd-faq-header:hover{opacity:0.95;}.bd-faq-icon{transition:transform 0.3s;}.bd-faq-icon.open{transform:rotate(180deg);}.bd-faq-body.active{max-height:2000px;}.bd-faq-chat-bot-answer{display:flex;gap:12px;}</style>';
        }
        /* Scope to body.faq-preview-doc so iframe body CSS does not conflict with main page */
        $head .= '<style>.bd-faq-container .bd-faq-header.active,.bd-faq-accordion-item .bd-faq-header.active{background:' . htmlspecialchars($primary_color) . ' !important;color:' . htmlspecialchars($active_header_text_color) . ' !important;border-left:none !important;}.bd-faq-header.active .bd-faq-question,.bd-faq-header.active h3,.bd-faq-header.active h4,.bd-faq-header.active .bd-faq-icon,.bd-faq-header.active .fa{color:' . htmlspecialchars($active_header_text_color) . ' !important;}body.faq-preview-doc{margin:0;padding:16px;min-height:280px;font-family:' . $font_family_css_safe . ';background:#f1f5f9;width:100%;box-sizing:border-box;}.faq-preview-doc .faq-preview-zoom-wrap{zoom:0.82;width:100%;box-sizing:border-box;}.faq-preview-doc .bd-faq-container{box-sizing:border-box;}</style>';
        $doc = '<!DOCTYPE html><html><head>' . $head . '</head><body class="faq-preview-doc"><div class="faq-preview-zoom-wrap">' . $body . '</div></body></html>';
        return array('status' => 'success', 'html' => $doc);
    } catch (Exception $e) {
        $body = '<p style="padding:16px;color:#b91c1c;">Preview failed: ' . htmlspecialchars($e->getMessage()) . '</p>';
        $doc = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>body.faq-preview-doc{margin:0;padding:16px;background:#f8fafc;}</style></head><body class="faq-preview-doc">' . $body . '</body></html>';
        return array('status' => 'success', 'html' => $doc);
    }
}

function handle_reset_design_settings($db)
{
    // Get website color defaults from website_design_settings table
    $website_color_defaults = getWebsiteColorDefaults($db);
    
    $default_settings = array(
        'layout_type' => 'accordion',
        'title_alignment' => 'center',
        'primary_color' => $website_color_defaults['primary_color'],
        'background_color' => $website_color_defaults['background_color'],
        'card_background_color' => $website_color_defaults['card_background_color'],
        'text_color' => $website_color_defaults['text_color'],
        'title_text_color' => $website_color_defaults['text_color'],
        'question_text_color' => $website_color_defaults['text_color'],
        'answer_text_color' => $website_color_defaults['text_color'],
        'title_font_size' => '32',
        'question_font_size' => '18',
        'answer_font_size' => '16',
        'font_family' => 'system',
        'premade_font_mode' => 'template_default',
        'template_lock_mode' => 'flexible',
        'container_width' => '900',
        'card_style' => 'shadow',
        'grid_columns' => '3',
        'video_columns' => '3',
        'card_radius' => '12',
        'card_padding' => '24',
        'card_icon_url' => '',
        'card_icon_shape' => 'circle'
    );

    foreach ($default_settings as $key => $value) {
        if (function_exists('mysql_real_escape_string') && !is_string($db)) {
            $escaped_key = mysql_real_escape_string($key, $db);
            $escaped_value = mysql_real_escape_string($value, $db);
        } else {
            $escaped_key = addslashes($key);
            $escaped_value = addslashes($value);
        }

        $sql = "INSERT INTO faq_design_settings (setting_key, setting_value) VALUES ('$escaped_key', '$escaped_value') ON DUPLICATE KEY UPDATE setting_value = '$escaped_value'";
        if (function_exists('mysql') && is_string($db)) {
            mysql($db, $sql);
        } else {
            mysql_query($sql, $db);
        }
    }

    return array('status' => 'success', 'message' => 'Settings reset successfully', 'defaults' => $default_settings);
}

/**
 * Ensure faq_page_design_overrides table exists (for upgrades without running SQL manually).
 */
function faq_ensure_page_design_overrides_table($db) {
    $create_sql = "CREATE TABLE IF NOT EXISTS `faq_page_design_overrides` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `scope` VARCHAR(120) NOT NULL COMMENT 'page_123 or post_45_search_result_page',
        `setting_key` VARCHAR(255) NOT NULL COMMENT 'e.g. layout_type, primary_color',
        `setting_value` TEXT DEFAULT NULL,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_scope_key` (`scope`, `setting_key`),
        KEY `idx_scope` (`scope`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Per-page FAQ design overrides'";
    if (function_exists('mysql') && is_string($db)) {
        mysql($db, $create_sql);
    } else {
        mysql_query($create_sql, $db);
    }
}

/**
 * Build scope string for page design overrides: page_<id> or post_<data_id>_<page_type>
 */
function faq_page_design_scope($page_id, $data_id, $page_type) {
    if ($data_id > 0 && $page_type !== null && $page_type !== '') {
        return 'post_' . intval($data_id) . '_' . preg_replace('/[^a-z0-9_]/', '', $page_type);
    }
    if ($page_id > 0) {
        return 'page_' . intval($page_id);
    }
    return '';
}

function faq_run_auto_migrations($db, $collect_messages = false)
{
    $messages = array();
    $changed = false;
    $target_version = 1;
    $current_version = 0;

    $version_sql = "SELECT setting_value FROM faq_design_settings WHERE setting_key = 'faq_migration_version' LIMIT 1";
    if (function_exists('mysql') && is_string($db)) {
        $version_res = mysql($db, $version_sql);
    } else {
        $version_res = mysql_query($version_sql, $db);
    }
    if ($version_res && mysql_num_rows($version_res) > 0) {
        $row = mysql_fetch_assoc($version_res);
        $current_version = intval(isset($row['setting_value']) ? $row['setting_value'] : 0);
    }

    if ($current_version >= $target_version) {
        if ($collect_messages) {
            $messages[] = 'Schema check: up to date.';
        }
        return array('changed' => false, 'messages' => $messages, 'version' => $current_version);
    }

    // Ensure canonical relation table exists.
    $create_group_questions_sql = "CREATE TABLE IF NOT EXISTS `faq_group_questions` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `group_id` INT(11) NOT NULL,
        `question_id` INT(11) NOT NULL,
        `sort_order` INT(11) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_group_question` (`group_id`, `question_id`),
        KEY `idx_group_id` (`group_id`),
        KEY `idx_question_id` (`question_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    if (function_exists('mysql') && is_string($db)) {
        mysql($db, $create_group_questions_sql);
    } else {
        mysql_query($create_group_questions_sql, $db);
    }

    // Migrate legacy table faq_question_groups -> faq_group_questions if it exists.
    $legacy_exists = false;
    $legacy_sql = "SHOW TABLES LIKE 'faq_question_groups'";
    if (function_exists('mysql') && is_string($db)) {
        $legacy_res = mysql($db, $legacy_sql);
    } else {
        $legacy_res = mysql_query($legacy_sql, $db);
    }
    if ($legacy_res && mysql_num_rows($legacy_res) > 0) {
        $legacy_exists = true;
    }

    if ($legacy_exists) {
        $migrate_sql = "INSERT INTO faq_group_questions (group_id, question_id, sort_order)
                        SELECT l.group_id, l.question_id,
                               COALESCE((SELECT MAX(g2.sort_order) + 1 FROM faq_group_questions g2 WHERE g2.group_id = l.group_id), 1) as next_sort
                        FROM faq_question_groups l
                        LEFT JOIN faq_group_questions g ON g.group_id = l.group_id AND g.question_id = l.question_id
                        WHERE g.id IS NULL";
        if (function_exists('mysql') && is_string($db)) {
            mysql($db, $migrate_sql);
        } else {
            mysql_query($migrate_sql, $db);
        }
        $changed = true;
        if ($collect_messages) {
            $messages[] = 'Migrated legacy table faq_question_groups into faq_group_questions.';
        }
    }

    // Ensure faq_page_assignments has show_title.
    $show_title_exists = false;
    $check_show_title_sql = "SHOW COLUMNS FROM faq_page_assignments LIKE 'show_title'";
    if (function_exists('mysql') && is_string($db)) {
        $check_show_title_res = mysql($db, $check_show_title_sql);
    } else {
        $check_show_title_res = mysql_query($check_show_title_sql, $db);
    }
    if ($check_show_title_res && mysql_num_rows($check_show_title_res) > 0) {
        $show_title_exists = true;
    }
    if (!$show_title_exists) {
        $alter_show_title_sql = "ALTER TABLE faq_page_assignments ADD COLUMN show_title TINYINT(1) NOT NULL DEFAULT 1";
        if (function_exists('mysql') && is_string($db)) {
            mysql($db, $alter_show_title_sql);
        } else {
            mysql_query($alter_show_title_sql, $db);
        }
        $changed = true;
        if ($collect_messages) {
            $messages[] = 'Added faq_page_assignments.show_title column.';
        }
    }

    // Ensure faq_page_assignments has merge_groups.
    $merge_exists = false;
    $check_merge_sql = "SHOW COLUMNS FROM faq_page_assignments LIKE 'merge_groups'";
    if (function_exists('mysql') && is_string($db)) {
        $check_merge_res = mysql($db, $check_merge_sql);
    } else {
        $check_merge_res = mysql_query($check_merge_sql, $db);
    }
    if ($check_merge_res && mysql_num_rows($check_merge_res) > 0) {
        $merge_exists = true;
    }
    if (!$merge_exists) {
        $alter_merge_sql = "ALTER TABLE faq_page_assignments ADD COLUMN merge_groups TINYINT(1) NOT NULL DEFAULT 0";
        if (function_exists('mysql') && is_string($db)) {
            mysql($db, $alter_merge_sql);
        } else {
            mysql_query($alter_merge_sql, $db);
        }
        $changed = true;
        if ($collect_messages) {
            $messages[] = 'Added faq_page_assignments.merge_groups column.';
        }
    }

    // Ensure override table exists.
    faq_ensure_page_design_overrides_table($db);

    // Ensure new design settings keys exist.
    $defaults = array(
        'premade_font_mode' => 'template_default',
        'template_lock_mode' => 'flexible'
    );
    foreach ($defaults as $key => $val) {
        if (function_exists('mysql_real_escape_string') && !is_string($db)) {
            $k = mysql_real_escape_string($key, $db);
            $v = mysql_real_escape_string($val, $db);
        } else {
            $k = addslashes($key);
            $v = addslashes($val);
        }
        $ins = "INSERT INTO faq_design_settings (setting_key, setting_value) VALUES ('$k', '$v') ON DUPLICATE KEY UPDATE setting_value = setting_value";
        if (function_exists('mysql') && is_string($db)) {
            mysql($db, $ins);
        } else {
            mysql_query($ins, $db);
        }
    }

    if ($changed) {
        if (function_exists('mysql_real_escape_string') && !is_string($db)) {
            $ver = mysql_real_escape_string((string) $target_version, $db);
        } else {
            $ver = addslashes((string) $target_version);
        }
        $set_ver_sql = "INSERT INTO faq_design_settings (setting_key, setting_value) VALUES ('faq_migration_version', '$ver')
                        ON DUPLICATE KEY UPDATE setting_value = '$ver'";
        if (function_exists('mysql') && is_string($db)) {
            mysql($db, $set_ver_sql);
        } else {
            mysql_query($set_ver_sql, $db);
        }
    } elseif ($collect_messages) {
        $messages[] = 'No legacy schema issues detected.';
    }

    return array('changed' => $changed, 'messages' => $messages, 'version' => $target_version);
}

function handle_check_design_consistency($db)
{
    $issues = array();
    $warnings = array();

    $allowed_keys = array(
        'design_preset', 'layout_type', 'title_alignment', 'font_family', 'premade_font_mode', 'template_lock_mode',
        'primary_color', 'background_color', 'card_background_color', 'text_color', 'title_text_color', 'question_text_color', 'answer_text_color',
        'title_font_size', 'question_font_size', 'answer_font_size', 'container_width', 'card_style', 'grid_columns', 'video_columns',
        'card_radius', 'card_padding', 'card_icon_url', 'card_icon_shape',
        'template_bg_minimal', 'template_bg_split', 'template_bg_colorful', 'template_bg_modern', 'template_bg_simple', 'template_bg_card', 'template_bg_classic'
    );

    $validators = array(
        'design_preset' => '/^(custom|minimal|split|colorful|modern|simple|card|classic)$/',
        'layout_type' => '/^(accordion|search-first|tabbed|single-column|grid-card|sidebar|persona-based|conversational|video-multimedia|step-by-step)$/',
        'title_alignment' => '/^(left|center|right)$/',
        'font_family' => '/^(system|arial|helvetica|georgia|times|courier|verdana|roboto|open-sans|lato|montserrat|poppins|inter)$/',
        'premade_font_mode' => '/^(template_default|website_font|custom_font)$/',
        'template_lock_mode' => '/^(strict|flexible)$/',
        'card_style' => '/^(minimal|shadow|elevated|bordered|simple|flat)$/',
        'card_icon_shape' => '/^(circle|original)$/'
    );

    $numeric_ranges = array(
        'title_font_size' => array(12, 72),
        'question_font_size' => array(12, 36),
        'answer_font_size' => array(12, 24),
        'grid_columns' => array(1, 4),
        'video_columns' => array(1, 4),
        'card_radius' => array(0, 50),
        'card_padding' => array(8, 48)
    );

    $global_settings = array();
    $sql = "SELECT setting_key, setting_value FROM faq_design_settings";
    if (function_exists('mysql') && is_string($db)) {
        $res = mysql($db, $sql);
    } else {
        $res = mysql_query($sql, $db);
    }
    if ($res && mysql_num_rows($res) > 0) {
        while ($row = mysql_fetch_assoc($res)) {
            $global_settings[$row['setting_key']] = $row['setting_value'];
        }
    }

    $scopes = array();
    $scope_sql = "SELECT DISTINCT scope FROM faq_page_design_overrides ORDER BY scope ASC";
    if (function_exists('mysql') && is_string($db)) {
        $scope_res = mysql($db, $scope_sql);
    } else {
        $scope_res = mysql_query($scope_sql, $db);
    }
    if ($scope_res && mysql_num_rows($scope_res) > 0) {
        while ($row = mysql_fetch_assoc($scope_res)) {
            $scopes[] = $row['scope'];
        }
    }

    foreach ($scopes as $scope) {
        $scope_issues = array();
        $scope_warn = array();
        $overrides = array();
        $scope_esc = addslashes($scope);
        $ov_sql = "SELECT setting_key, setting_value FROM faq_page_design_overrides WHERE scope = '$scope_esc'";
        if (function_exists('mysql') && is_string($db)) {
            $ov_res = mysql($db, $ov_sql);
        } else {
            $ov_res = mysql_query($ov_sql, $db);
        }
        if ($ov_res && mysql_num_rows($ov_res) > 0) {
            while ($row = mysql_fetch_assoc($ov_res)) {
                $overrides[$row['setting_key']] = $row['setting_value'];
            }
        }

        if (!isset($overrides['design_preset']) || trim((string) $overrides['design_preset']) === '') {
            $scope_warn[] = 'Missing design_preset override (falls back to global).';
        }

        foreach ($overrides as $key => $val) {
            $v = trim((string) $val);
            if (!in_array($key, $allowed_keys, true) && strpos($key, 'template_bg_') !== 0) {
                $scope_warn[] = 'Unknown key: ' . $key;
                continue;
            }

            if (isset($validators[$key]) && $v !== '' && !preg_match($validators[$key], $v)) {
                $scope_issues[] = 'Invalid value for ' . $key . ': ' . $v;
                continue;
            }

            if (isset($numeric_ranges[$key]) && $v !== '') {
                $num = intval($v);
                $min = $numeric_ranges[$key][0];
                $max = $numeric_ranges[$key][1];
                if ($num < $min || $num > $max) {
                    $scope_issues[] = $key . ' out of range (' . $min . '-' . $max . '): ' . $v;
                    continue;
                }
            }

            if ((strpos($key, 'color') !== false || strpos($key, 'template_bg_') === 0) && $v !== '') {
                $is_hex = preg_match('/^#?[0-9a-fA-F]{6}$/', $v);
                $is_rgba = preg_match('/^rgba?\s*\(/', $v);
                if (!$is_hex && !$is_rgba) {
                    $scope_issues[] = 'Invalid color format for ' . $key . ': ' . $v;
                }
            }
        }

        if (!empty($scope_issues) || !empty($scope_warn)) {
            $issues[] = array(
                'scope' => $scope,
                'errors' => $scope_issues,
                'warnings' => $scope_warn
            );
        }
    }

    // Global quick checks
    if (!isset($global_settings['design_preset']) || trim((string) $global_settings['design_preset']) === '') {
        $warnings[] = 'Global design_preset is not set.';
    }
    if (!isset($global_settings['layout_type']) || trim((string) $global_settings['layout_type']) === '') {
        $warnings[] = 'Global layout_type is not set.';
    }

    return array(
        'status' => 'success',
        'summary' => array(
            'scope_count' => count($scopes),
            'issues_found' => count($issues),
            'global_warnings' => count($warnings)
        ),
        'global_warnings' => $warnings,
        'scope_issues' => $issues
    );
}

function handle_get_page_design_override_sources($db)
{
    $sources = array();
    $seen = array();
    $sql = "SELECT DISTINCT page_id, data_id, page_type FROM faq_page_assignments ORDER BY id DESC";
    if (function_exists('mysql') && is_string($db)) {
        $res = mysql($db, $sql);
    } else {
        $res = mysql_query($sql, $db);
    }
    if ($res && mysql_num_rows($res) > 0) {
        while ($row = mysql_fetch_assoc($res)) {
            $page_id = isset($row['page_id']) ? intval($row['page_id']) : 0;
            $data_id = isset($row['data_id']) ? intval($row['data_id']) : 0;
            $page_type = isset($row['page_type']) ? trim((string) $row['page_type']) : '';
            $scope = faq_page_design_scope($page_id, $data_id, $page_type);
            if ($scope === '' || isset($seen[$scope])) {
                continue;
            }
            $seen[$scope] = true;

            $label = $scope;
            if ($page_id > 0) {
                $seo_sql = "SELECT COALESCE(NULLIF(title,''), filename) as name FROM list_seo WHERE seo_id = " . $page_id . " LIMIT 1";
                if (function_exists('mysql') && is_string($db)) {
                    $seo_res = mysql($db, $seo_sql);
                } else {
                    $seo_res = mysql_query($seo_sql, $db);
                }
                if ($seo_res && mysql_num_rows($seo_res) > 0) {
                    $seo_row = mysql_fetch_assoc($seo_res);
                    $label = 'Page: ' . (isset($seo_row['name']) ? $seo_row['name'] : ('ID ' . $page_id));
                } else {
                    $label = 'Page ID: ' . $page_id;
                }
            } elseif ($data_id > 0) {
                $dc_sql = "SELECT data_name FROM data_categories WHERE data_id = " . $data_id . " LIMIT 1";
                if (function_exists('mysql') && is_string($db)) {
                    $dc_res = mysql($db, $dc_sql);
                } else {
                    $dc_res = mysql_query($dc_sql, $db);
                }
                if ($dc_res && mysql_num_rows($dc_res) > 0) {
                    $dc_row = mysql_fetch_assoc($dc_res);
                    $label = 'Post Type: ' . (isset($dc_row['data_name']) ? $dc_row['data_name'] : ('ID ' . $data_id)) . ' (' . $page_type . ')';
                } else {
                    $label = 'Post Type ID: ' . $data_id . ' (' . $page_type . ')';
                }
            }

            $sources[] = array(
                'scope' => $scope,
                'label' => $label,
                'page_id' => $page_id,
                'data_id' => $data_id,
                'page_type' => $page_type
            );
        }
    }
    return array('status' => 'success', 'sources' => $sources);
}

function handle_get_page_design_overrides_by_scope($db)
{
    $scope = isset($_POST['scope']) ? trim((string) $_POST['scope']) : '';
    if ($scope === '') {
        return array('status' => 'error', 'message' => 'Invalid scope');
    }
    $scope_esc = addslashes($scope);
    $sql = "SELECT setting_key, setting_value FROM faq_page_design_overrides WHERE scope = '$scope_esc'";
    if (function_exists('mysql') && is_string($db)) {
        $res = mysql($db, $sql);
    } else {
        $res = mysql_query($sql, $db);
    }
    $overrides = array();
    if ($res && mysql_num_rows($res) > 0) {
        while ($row = mysql_fetch_assoc($res)) {
            $overrides[$row['setting_key']] = $row['setting_value'];
        }
    }
    return array('status' => 'success', 'scope' => $scope, 'overrides' => $overrides);
}

function handle_get_page_design_overrides($db)
{
    $page_id = isset($_POST['page_id']) ? intval($_POST['page_id']) : 0;
    $data_id = isset($_POST['data_id']) ? intval($_POST['data_id']) : 0;
    $page_type = isset($_POST['page_type']) ? trim($_POST['page_type']) : '';
    $scope = faq_page_design_scope($page_id, $data_id, $page_type);
    if ($scope === '') {
        return array('status' => 'success', 'overrides' => array());
    }
    if (function_exists('mysql_real_escape_string') && !is_string($db)) {
        $scope = mysql_real_escape_string($scope, $db);
    } else {
        $scope = addslashes($scope);
    }
    $sql = "SELECT setting_key, setting_value FROM faq_page_design_overrides WHERE scope = '$scope'";
    if (function_exists('mysql') && is_string($db)) {
        $result = mysql($db, $sql);
    } else {
        $result = mysql_query($sql, $db);
    }
    $overrides = array();
    if ($result && mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_assoc($result)) {
            $overrides[$row['setting_key']] = $row['setting_value'];
        }
    }
    return array('status' => 'success', 'overrides' => $overrides);
}

function handle_save_page_design_setting($db)
{
    faq_ensure_page_design_overrides_table($db);
    $page_id = isset($_POST['page_id']) ? intval($_POST['page_id']) : 0;
    $data_id = isset($_POST['data_id']) ? intval($_POST['data_id']) : 0;
    $page_type = isset($_POST['page_type']) ? trim($_POST['page_type']) : '';
    $scope = faq_page_design_scope($page_id, $data_id, $page_type);
    if ($scope === '') {
        return array('status' => 'error', 'message' => 'Invalid page scope');
    }
    if (function_exists('mysql_real_escape_string') && !is_string($db)) {
        $setting_key = mysql_real_escape_string($_POST['setting_key'], $db);
        $setting_value = mysql_real_escape_string($_POST['setting_value'], $db);
        $scope_esc = mysql_real_escape_string($scope, $db);
    } else {
        $setting_key = addslashes($_POST['setting_key']);
        $setting_value = addslashes($_POST['setting_value']);
        $scope_esc = addslashes($scope);
    }
    $sql = "INSERT INTO faq_page_design_overrides (scope, setting_key, setting_value) VALUES ('$scope_esc', '$setting_key', '$setting_value') ON DUPLICATE KEY UPDATE setting_value = '$setting_value'";
    if (function_exists('mysql') && is_string($db)) {
        mysql($db, $sql);
    } else {
        mysql_query($sql, $db);
    }
    return array('status' => 'success', 'message' => 'Setting saved');
}

function handle_save_page_design_overrides_batch($db)
{
    faq_ensure_page_design_overrides_table($db);
    $page_id = isset($_POST['page_id']) ? intval($_POST['page_id']) : 0;
    $data_id = isset($_POST['data_id']) ? intval($_POST['data_id']) : 0;
    $page_type = isset($_POST['page_type']) ? trim($_POST['page_type']) : '';
    $scope = faq_page_design_scope($page_id, $data_id, $page_type);
    if ($scope === '') {
        return array('status' => 'error', 'message' => 'Invalid page scope');
    }
    $settings = isset($_POST['settings']) ? $_POST['settings'] : '';
    if (is_string($settings)) {
        $settings = json_decode($settings, true);
    }
    if (!is_array($settings) || empty($settings)) {
        return array('status' => 'error', 'message' => 'No settings to save');
    }
    if (function_exists('mysql_real_escape_string') && !is_string($db)) {
        $scope_esc = mysql_real_escape_string($scope, $db);
    } else {
        $scope_esc = addslashes($scope);
    }
    foreach ($settings as $setting_key => $setting_value) {
        $setting_value = (string) $setting_value;
        if (function_exists('mysql_real_escape_string') && !is_string($db)) {
            $setting_key_esc = mysql_real_escape_string($setting_key, $db);
            $setting_value_esc = mysql_real_escape_string($setting_value, $db);
        } else {
            $setting_key_esc = addslashes($setting_key);
            $setting_value_esc = addslashes($setting_value);
        }
        $sql = "INSERT INTO faq_page_design_overrides (scope, setting_key, setting_value) VALUES ('$scope_esc', '$setting_key_esc', '$setting_value_esc') ON DUPLICATE KEY UPDATE setting_value = '$setting_value_esc'";
        if (function_exists('mysql') && is_string($db)) {
            mysql($db, $sql);
        } else {
            mysql_query($sql, $db);
        }
    }
    return array('status' => 'success', 'message' => 'Page design overrides saved');
}

function handle_clear_page_design_overrides($db)
{
    faq_ensure_page_design_overrides_table($db);
    $page_id = isset($_POST['page_id']) ? intval($_POST['page_id']) : 0;
    $data_id = isset($_POST['data_id']) ? intval($_POST['data_id']) : 0;
    $page_type = isset($_POST['page_type']) ? trim($_POST['page_type']) : '';
    $scope = faq_page_design_scope($page_id, $data_id, $page_type);
    if ($scope === '') {
        return array('status' => 'error', 'message' => 'Invalid page scope');
    }
    if (function_exists('mysql_real_escape_string') && !is_string($db)) {
        $scope = mysql_real_escape_string($scope, $db);
    } else {
        $scope = addslashes($scope);
    }
    $sql = "DELETE FROM faq_page_design_overrides WHERE scope = '$scope'";
    if (function_exists('mysql') && is_string($db)) {
        mysql($db, $sql);
    } else {
        mysql_query($sql, $db);
    }
    return array('status' => 'success', 'message' => 'Page design overrides cleared');
}

function handle_get_questions_table($db)
{
    $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $per_page = isset($_POST['per_page']) ? max(1, intval($_POST['per_page'])) : 25;
    $show_all = isset($_POST['show_all']) && $_POST['show_all'] == '1';
    $html = render_questions_table($db, $page, $per_page, $show_all);
    return array('status' => 'success', 'html_questions' => $html);
}

function handle_get_groups_table($db)
{
    $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $per_page = isset($_POST['per_page']) ? max(1, intval($_POST['per_page'])) : 25;
    $show_all = isset($_POST['show_all']) && $_POST['show_all'] == '1';
    $html = render_groups_table($db, $page, $per_page, $show_all);
    return array('status' => 'success', 'html_groups' => $html);
}

function handle_get_assignments_table($db)
{
    $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $per_page = isset($_POST['per_page']) ? max(1, intval($_POST['per_page'])) : 25;
    $show_all = isset($_POST['show_all']) && $_POST['show_all'] == '1';
    $html = render_assignments_table($db, $page, $per_page, $show_all);
    return array('status' => 'success', 'html_assignments' => $html);
}

function handle_get_priority_questions($db, $group_id_param = null)
{
    $group_id = $group_id_param !== null ? intval($group_id_param) : intval($_POST['group_id']);

    if ($group_id <= 0) {
        return array('status' => 'error', 'html' => '<ul id="priority-list" class="list-group"><li class="list-group-item text-muted">Please select a group</li></ul>');
    }

    // Get group name for display
    $group_name_sql = "SELECT group_name FROM faq_groups WHERE id = $group_id";
    if (function_exists('mysql') && is_string($db)) {
        $group_name_result = mysql($db, $group_name_sql);
    } else {
        $group_name_result = mysql_query($group_name_sql, $db);
    }
    $group_name = 'Selected Group';
    if ($group_name_result && mysql_num_rows($group_name_result) > 0) {
        $group_row = mysql_fetch_assoc($group_name_result);
        $group_name = isset($group_row['group_name']) ? $group_row['group_name'] : 'Selected Group';
    }

    $sql = "SELECT q.*, gq.sort_order
                FROM faq_questions q
                JOIN faq_group_questions gq ON q.id = gq.question_id
                WHERE gq.group_id = $group_id
                ORDER BY gq.sort_order ASC, q.id ASC";
    if (function_exists('mysql') && is_string($db)) {
        $result = mysql($db, $sql);
    } else {
        $result = mysql_query($sql, $db);
    }

    $html = '<div class="alert alert-info mb-3" style="background: #e0e7ff; border: 1px solid #667eea; color: #1e3a8a; padding: 12px 16px; border-radius: 8px; font-size: 13px;">';
    $html .= '<i class="fa fa-info-circle"></i> <strong>Editing priorities for:</strong> <span style="font-weight: 600; color: #667eea;">' . htmlspecialchars($group_name) . '</span>. ';
    $html .= 'These priorities only apply to this group. The same question may have different ranks in other groups.';
    $html .= '</div>';
    $html .= '<ul id="priority-list" class="faq-plugin-drag-list">';
    if ($result && mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_assoc($result)) {
            $question_preview = mb_substr(strip_tags($row['question']), 0, 100) . (mb_strlen(strip_tags($row['question'])) > 100 ? '...' : '');
            $sort_order = isset($row['sort_order']) ? intval($row['sort_order']) : 0;
            $html .= '<li class="faq-plugin-drag-item" data-question-id="' . intval($row['id']) . '">
                            <div style="flex: 1;">
                                <strong style="color: #667eea; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Rank #' . $sort_order . '</strong>
                                <span style="margin-left: 16px; color: #334155;">Q#' . intval($row['id']) . ' - ' . htmlspecialchars($question_preview) . '</span>
                                </div>
                            <i class="fa fa-bars faq-plugin-drag-handle" style="cursor: grab;"></i>
                        </li>';
        }
    } else {
        $html .= '<li class="faq-plugin-empty-state" style="padding: 40px; text-align: center; color: #94a3b8;"><i class="fa fa-info-circle"></i> No questions in this group</li>';
    }
    $html .= '</ul>';

    return array('status' => 'success', 'html' => $html);
}

function handle_get_group_questions_list($db)
{
    $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
    if ($group_id <= 0) {
        return array('status' => 'error', 'message' => 'Invalid group', 'questions' => array());
    }
    $sql = "SELECT q.id, q.question
            FROM faq_questions q
            JOIN faq_group_questions gq ON q.id = gq.question_id
            WHERE gq.group_id = $group_id
            ORDER BY gq.sort_order ASC, q.id ASC";
    if (function_exists('mysql') && is_string($db)) {
        $result = mysql($db, $sql);
    } else {
        $result = mysql_query($sql, $db);
    }
    $questions = array();
    if ($result && mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_assoc($result)) {
            $questions[] = array('id' => intval($row['id']), 'question' => isset($row['question']) ? $row['question'] : '');
        }
    }
    return array('status' => 'success', 'questions' => $questions);
}

function handle_include_questions_from_group($db)
{
    $target_group_id = isset($_POST['target_group_id']) ? intval($_POST['target_group_id']) : 0;
    $question_ids = isset($_POST['question_ids']) ? $_POST['question_ids'] : array();
    if ($target_group_id <= 0) {
        return array('status' => 'error', 'message' => 'Select a group first (use the filter above to choose the group to add questions into).');
    }
    if (empty($question_ids) || !is_array($question_ids)) {
        return array('status' => 'error', 'message' => 'No questions selected');
    }
    $safe_ids = array_map('intval', $question_ids);
    $safe_ids = array_filter($safe_ids);
    if (empty($safe_ids)) {
        return array('status' => 'error', 'message' => 'No valid questions selected');
    }
    $max_sql = "SELECT COALESCE(MAX(sort_order), 0) as max_order FROM faq_group_questions WHERE group_id = $target_group_id";
    if (function_exists('mysql') && is_string($db)) {
        $max_result = mysql($db, $max_sql);
    } else {
        $max_result = mysql_query($max_sql, $db);
    }
    $next_order = 1;
    if ($max_result && mysql_num_rows($max_result) > 0) {
        $max_row = mysql_fetch_assoc($max_result);
        $next_order = intval($max_row['max_order']) + 1;
    }
    $added = 0;
    foreach ($safe_ids as $qid) {
        $check_sql = "SELECT id FROM faq_group_questions WHERE group_id = $target_group_id AND question_id = $qid";
        if (function_exists('mysql') && is_string($db)) {
            $check = mysql($db, $check_sql);
        } else {
            $check = mysql_query($check_sql, $db);
        }
        if (!$check || mysql_num_rows($check) == 0) {
            $insert_sql = "INSERT INTO faq_group_questions (group_id, question_id, sort_order) VALUES ($target_group_id, $qid, $next_order)";
            if (function_exists('mysql') && is_string($db)) {
                mysql($db, $insert_sql);
            } else {
                mysql_query($insert_sql, $db);
            }
            $next_order++;
            $added++;
        }
    }
    $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $per_page = isset($_POST['per_page']) ? max(1, intval($_POST['per_page'])) : 25;
    $show_all = isset($_POST['show_all']) && $_POST['show_all'] == '1';
    $html = render_questions_table($db, $page, $per_page, $show_all);
    return array('status' => 'success', 'message' => $added . ' question(s) added to the group.', 'html_questions' => $html, 'added' => $added);
}

function handle_get_all_groups($db)
{
    $sql = "SELECT id, group_name, group_slug FROM faq_groups ORDER BY group_name ASC";
    if (function_exists('mysql') && is_string($db)) {
        $result = mysql($db, $sql);
    } else {
        $result = mysql_query($sql, $db);
    }

    $groups = array();
    if ($result && mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_assoc($result)) {
            $groups[] = array(
                'id' => intval($row['id']),
                'group_name' => $row['group_name'],
                'group_slug' => isset($row['group_slug']) ? $row['group_slug'] : ''
            );
        }
    }

    return array('status' => 'success', 'groups' => $groups);
}

function handle_get_question_groups($db)
{
    $question_id = intval($_POST['question_id']);

    $sql = "SELECT group_id FROM faq_group_questions WHERE question_id = $question_id";
    if (function_exists('mysql') && is_string($db)) {
        $result = mysql($db, $sql);
    } else {
        $result = mysql_query($sql, $db);
    }

    $group_ids = array();
    if ($result) {
        while ($row = mysql_fetch_assoc($result)) {
            if ($row && isset($row['group_id'])) {
                $group_ids[] = $row['group_id'];
            }
        }
    }

    return array('status' => 'success', 'group_ids' => $group_ids);
}

function handle_save_order($db)
{
    $page_id = intval($_POST['page_id']);
    $group_id = intval($_POST['group_id']);

    $max_sql = "SELECT MAX(sort_order) as max_order FROM faq_orders WHERE page_id = $page_id";
    if (function_exists('mysql') && is_string($db)) {
        $max_query = mysql($db, $max_sql);
    } else {
        $max_query = mysql_query($max_sql, $db);
    }
    $max_row = mysql_fetch_assoc($max_query);
    $sort_order = ($max_row && $max_row['max_order'] !== null) ? $max_row['max_order'] + 1 : 1;

    $insert_sql = "INSERT INTO faq_orders (page_id, group_id, sort_order) VALUES ($page_id, $group_id, $sort_order) ON DUPLICATE KEY UPDATE sort_order = $sort_order";
    if (function_exists('mysql') && is_string($db)) {
        $result = mysql($db, $insert_sql);
    } else {
        $result = mysql_query($insert_sql, $db);
    }

    if (!$result) {
        $error_msg = '';
        if (function_exists('mysql_error') && !is_string($db)) {
            $error_msg = mysql_error($db);
        }
        return array('status' => 'error', 'message' => 'Failed to save order: ' . $error_msg);
    }

    $html = render_assignments_table($db);
    return array('status' => 'success', 'message' => 'Order saved successfully', 'html_assignments' => $html);
}

function handle_update_group_order($db)
{
    // Parse orders - they might come as JSON string or array
    $orders = array();
    if (isset($_POST['orders'])) {
        if (is_string($_POST['orders'])) {
            $orders = json_decode($_POST['orders'], true);
            if (!is_array($orders)) {
                $orders = array();
            }
        } else if (is_array($_POST['orders'])) {
            $orders = $_POST['orders'];
        }
    }

    $assignment_type = isset($_POST['assignment_type']) ? $_POST['assignment_type'] : 'static';

    // Check if faq_orders table exists, create if it doesn't
    $check_table = "SHOW TABLES LIKE 'faq_orders'";
    if (function_exists('mysql') && is_string($db)) {
        $table_check = mysql($db, $check_table);
    } else {
        $table_check = mysql_query($check_table, $db);
    }

    if (!$table_check || mysql_num_rows($table_check) == 0) {

        $create_table = "CREATE TABLE IF NOT EXISTS `faq_orders` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `page_id` INT(11) DEFAULT NULL COMMENT 'Static page ID',
                `data_id` INT(11) DEFAULT NULL COMMENT 'Post type data ID',
                `page_type` VARCHAR(50) DEFAULT NULL COMMENT 'Post type',
                `group_id` INT(11) NOT NULL COMMENT 'FAQ group ID',
                `sort_order` INT(11) NOT NULL DEFAULT 0 COMMENT 'Display order',
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `unique_static_page_group_order` (`page_id`, `group_id`),
                UNIQUE KEY `unique_post_type_group_order` (`data_id`, `page_type`(50), `group_id`),
                KEY `idx_page_id` (`page_id`),
                KEY `idx_group_id` (`group_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        if (function_exists('mysql') && is_string($db)) {
            $create_result = mysql($db, $create_table);
        } else {
            $create_result = mysql_query($create_table, $db);
        }

        if (!$create_result) {
            error_log("FAQ update_group_order: ERROR creating table - " . mysql_error($db));
            return array('status' => 'error', 'message' => 'Could not create faq_orders table. Please check database permissions.');
        }
    }

    $success_count = 0;
    $error_count = 0;

    if (empty($orders)) {
        return array(
            'status' => 'error',
            'message' => 'No orders received',
            'saved_count' => 0
        );
    }

    foreach ($orders as $item) {
        $group_id = intval($item['group_id']);
        $sort_order = intval($item['sort_order']);

        if ($assignment_type == 'post_type') {
            $data_id = intval($_POST['data_id']);
            $page_type = addslashes($_POST['page_type']);
            $insert_sql = "INSERT INTO faq_orders (data_id, page_type, group_id, sort_order) VALUES ($data_id, '$page_type', $group_id, $sort_order) ON DUPLICATE KEY UPDATE sort_order = $sort_order";
        } else {
            $page_id = intval($_POST['page_id']);
            $insert_sql = "INSERT INTO faq_orders (page_id, group_id, sort_order) VALUES ($page_id, $group_id, $sort_order) ON DUPLICATE KEY UPDATE sort_order = $sort_order";
        }

        if (function_exists('mysql') && is_string($db)) {
            $result = mysql($db, $insert_sql);
        } else {
            $result = mysql_query($insert_sql, $db);
        }

        if (!$result) {
            $error_count++;
        } else {
            $success_count++;
        }
    }

    if ($error_count > 0) {
        return array(
            'status' => 'error',
            'message' => "Saved $success_count orders, but $error_count failed."
        );
    }

    return array(
        'status' => 'success',
        'message' => 'Group order updated successfully'
    );
}

function handle_delete_order($db)
{
    $order_id = intval($_POST['order_id']);
    if (function_exists('mysql') && is_string($db)) {
        mysql($db, "DELETE FROM faq_orders WHERE id = $order_id");
    } else {
        mysql_query("DELETE FROM faq_orders WHERE id = $order_id", $db);
    }

    $html = render_assignments_table($db);
    return array('status' => 'success', 'message' => 'Order deleted successfully', 'html_assignments' => $html);
}

function handle_get_filtered_questions($db)
{
    if (function_exists('mysql_real_escape_string') && !is_string($db)) {
        $search = isset($_POST['search']) ? mysql_real_escape_string($_POST['search'], $db) : '';
    } else {
        $search = isset($_POST['search']) ? addslashes($_POST['search']) : '';
    }
    $group_filter = isset($_POST['group_filter']) ? intval($_POST['group_filter']) : 0;

    $where = array();
    if (!empty($search)) {
        $where[] = "(q.question LIKE '%$search%' OR q.answer LIKE '%$search%')";
    }
    if ($group_filter > 0) {
        $where[] = "gq.group_id = $group_filter";
    }

    $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $sql = "SELECT q.*, GROUP_CONCAT(g.group_name ORDER BY g.group_name SEPARATOR ', ') as groups
                FROM faq_questions q
                LEFT JOIN faq_group_questions gq ON q.id = gq.question_id
                LEFT JOIN faq_groups g ON gq.group_id = g.id
                $where_clause
                GROUP BY q.id
                ORDER BY q.id DESC";

    $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $per_page = isset($_POST['per_page']) ? max(1, intval($_POST['per_page'])) : 25;
    $show_all = isset($_POST['show_all']) && $_POST['show_all'] == '1';

    $count_sql = "SELECT COUNT(DISTINCT q.id) as total FROM faq_questions q LEFT JOIN faq_group_questions gq ON q.id = gq.question_id $where_clause";
    if (function_exists('mysql') && is_string($db)) {
        $count_result = mysql($db, $count_sql);
    } else {
        $count_result = mysql_query($count_sql, $db);
    }
    $total_rows = 0;
    if ($count_result && mysql_num_rows($count_result) > 0) {
        $count_row = mysql_fetch_assoc($count_result);
        $total_rows = intval($count_row['total']);
    }

    if (!$show_all) {
        $offset = ($page - 1) * $per_page;
        $sql .= " LIMIT $per_page OFFSET $offset";
    }

    if (function_exists('mysql') && is_string($db)) {
        $result = mysql($db, $sql);
    } else {
        $result = mysql_query($sql, $db);
    }
    $table_html = render_questions_table_html($result, $db);
    $total_pages = $show_all ? 1 : max(1, ceil($total_rows / $per_page));
    $pagination_html = render_pagination_html('questions', $page, $total_pages, $total_rows, $per_page, $show_all);

    return array('status' => 'success', 'html_questions' => $table_html . $pagination_html);
}

function handle_get_filtered_groups($db)
{
    if (!$db) {
        return array('status' => 'error', 'message' => 'Database connection not available');
    }

    try {
        if (function_exists('mysql_real_escape_string') && !is_string($db)) {
            $search = isset($_POST['search']) ? mysql_real_escape_string($_POST['search'], $db) : '';
        } else {
            $search = isset($_POST['search']) ? addslashes($_POST['search']) : '';
        }
        $show_system = isset($_POST['show_system']) ? intval($_POST['show_system']) : 1;

        $where = array();
        if (!empty($search)) {
            $where[] = "(group_name LIKE '%$search%' OR group_slug LIKE '%$search%')";
        }
        if (!$show_system) {
            $where[] = "group_slug NOT IN ('global', 'unassigned')";
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT * FROM faq_groups $where_clause ORDER BY id ASC";
        if (function_exists('mysql') && is_string($db)) {
            $result = mysql($db, $sql);
        } else {
            $result = mysql_query($sql, $db);
        }

        if (!$result) {
            $error_msg = function_exists('mysql_error') && !is_string($db) ? mysql_error($db) : 'Query failed';
            error_log("Filter Groups Query Error: " . $error_msg . " | SQL: " . $sql);
            return array('status' => 'error', 'message' => 'Database query failed: ' . $error_msg);
        }

        $html = render_groups_table_html($result, $db);

        return array('status' => 'success', 'html_groups' => $html);
    } catch (Exception $e) {
        error_log("Filter Groups Exception: " . $e->getMessage());
        return array('status' => 'error', 'message' => 'Error: ' . $e->getMessage());
    }
}

function handle_get_filtered_assignments($db)
{
    if (function_exists('mysql_real_escape_string') && !is_string($db)) {
        $page_filter = isset($_POST['page_filter']) ? mysql_real_escape_string($_POST['page_filter'], $db) : '';
    } else {
        $page_filter = isset($_POST['page_filter']) ? addslashes($_POST['page_filter']) : '';
    }
    $group_filter = isset($_POST['group_filter']) ? intval($_POST['group_filter']) : 0;

    $where = array();
    if (!empty($page_filter)) {
        $where[] = "(s.filename LIKE '%$page_filter%' OR s.title LIKE '%$page_filter%' OR dc.data_name LIKE '%$page_filter%')";
    }
    if ($group_filter > 0) {
        $where[] = "a.group_id = $group_filter";
    }

    $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';


    $check_column_sql = "SHOW COLUMNS FROM faq_page_assignments LIKE 'show_title'";
    if (function_exists('mysql') && is_string($db)) {
        $check_result = mysql($db, $check_column_sql);
    } else {
        $check_result = mysql_query($check_column_sql, $db);
    }
    $has_show_title = ($check_result && mysql_num_rows($check_result) > 0);
    $show_title_field = $has_show_title ? "COALESCE(a.show_title, 1) as show_title" : "1 as show_title";

    $check_merge_sql = "SHOW COLUMNS FROM faq_page_assignments LIKE 'merge_groups'";
    if (function_exists('mysql') && is_string($db)) {
        $check_merge_result = mysql($db, $check_merge_sql);
    } else {
        $check_merge_result = mysql_query($check_merge_sql, $db);
    }
    $has_merge_groups = ($check_merge_result && mysql_num_rows($check_merge_result) > 0);
    $merge_groups_field = $has_merge_groups ? ", COALESCE(a.merge_groups, 0) as merge_groups" : ", 0 as merge_groups";

    $sql = "SELECT a.*, 
                        COALESCE(
                            NULLIF(s.title, ''),
                            NULLIF(s.filename, ''),
                            CONCAT(dc.data_name, ' (', 
                                CASE 
                                    WHEN dc.data_type = 4 THEN 'Group'
                                    WHEN dc.data_type = 20 THEN 'Post'
                                    ELSE 'Member'
                                END, ' - ', 
                                CASE 
                                    WHEN a.page_type = 'search_result_page' THEN 'Search Result Page'
                                    WHEN a.page_type = 'detail_page' THEN 'Detail Page'
                                    ELSE ''
                                END, ')'),
                            CONCAT('Page ID: ', a.page_id)
                        ) as display_name,
                        g.group_name, 
                        COALESCE(o.sort_order, 999) as sort_order,
                        $show_title_field
                        $merge_groups_field,
                        CASE 
                            WHEN a.page_id IS NOT NULL THEN 'static'
                            WHEN a.data_id IS NOT NULL THEN 'post_type'
                            ELSE 'unknown'
                        END as assignment_type
                FROM faq_page_assignments a
                LEFT JOIN list_seo s ON a.page_id = s.seo_id
                LEFT JOIN data_categories dc ON a.data_id = dc.data_id
                JOIN faq_groups g ON a.group_id = g.id
                LEFT JOIN faq_orders o ON ((a.page_id IS NOT NULL AND o.page_id = a.page_id) OR (a.data_id IS NOT NULL AND o.data_id = a.data_id AND o.page_type = a.page_type)) AND o.group_id = a.group_id
                $where_clause
                ORDER BY display_name ASC, sort_order ASC, a.id DESC";

    $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $per_page = isset($_POST['per_page']) ? max(1, intval($_POST['per_page'])) : 25;
    $show_all = isset($_POST['show_all']) && $_POST['show_all'] == '1';

    $count_sql = "SELECT COUNT(*) as total 
                        FROM faq_page_assignments a
                        LEFT JOIN list_seo s ON a.page_id = s.seo_id
                        LEFT JOIN data_categories dc ON a.data_id = dc.data_id
                        $where_clause";
    if (function_exists('mysql') && is_string($db)) {
        $count_result = mysql($db, $count_sql);
    } else {
        $count_result = mysql_query($count_sql, $db);
    }
    $total_rows = 0;
    if ($count_result && mysql_num_rows($count_result) > 0) {
        $count_row = mysql_fetch_assoc($count_result);
        $total_rows = intval($count_row['total']);
    }

    if (!$show_all) {
        $offset = ($page - 1) * $per_page;
        $sql .= " LIMIT $per_page OFFSET $offset";
    }

    if (function_exists('mysql') && is_string($db)) {
        $result = mysql($db, $sql);
    } else {
        $result = mysql_query($sql, $db);
    }
    $table_html = render_assignments_table_html($result, $db);
    $total_pages = $show_all ? 1 : max(1, ceil($total_rows / $per_page));
    $pagination_html = render_pagination_html('assignments', $page, $total_pages, $total_rows, $per_page, $show_all);

    return array('status' => 'success', 'html_assignments' => $table_html . $pagination_html);
}

function handle_get_page_merged_groups($db)
{
    $page_id = isset($_POST['page_id']) ? intval($_POST['page_id']) : 0;
    $data_id = isset($_POST['data_id']) ? intval($_POST['data_id']) : 0;
    $page_type = isset($_POST['page_type']) ? addslashes($_POST['page_type']) : '';

    $where = array();
    if ($page_id > 0) {
        $where[] = "a.page_id = $page_id";
    } elseif ($data_id > 0 && !empty($page_type)) {
        $where[] = "a.data_id = $data_id AND a.page_type = '$page_type'";
    } else {
        return array('status' => 'error', 'message' => 'Invalid page parameters');
    }

    $check_merge_sql = "SHOW COLUMNS FROM faq_page_assignments LIKE 'merge_groups'";
    if (function_exists('mysql') && is_string($db)) {
        $check_result = mysql($db, $check_merge_sql);
    } else {
        $check_result = mysql_query($check_merge_sql, $db);
    }
    $has_merge_groups = ($check_result && mysql_num_rows($check_result) > 0);

    $where_clause = 'WHERE ' . implode(' AND ', $where);
    if ($has_merge_groups) {
        $where_clause .= " AND COALESCE(a.merge_groups, 0) = 1";
    }

    $sql = "SELECT a.group_id FROM faq_page_assignments a $where_clause";
    if (function_exists('mysql') && is_string($db)) {
        $result = mysql($db, $sql);
    } else {
        $result = mysql_query($sql, $db);
    }

    $group_ids = array();
    if ($result && mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_assoc($result)) {
            $group_ids[] = intval($row['group_id']);
        }
    }

    return array('status' => 'success', 'group_ids' => $group_ids);
}

function handle_get_page_all_assigned_groups($db)
{
    $page_id = isset($_POST['page_id']) ? intval($_POST['page_id']) : 0;
    $data_id = isset($_POST['data_id']) ? intval($_POST['data_id']) : 0;
    $page_type = isset($_POST['page_type']) ? addslashes($_POST['page_type']) : '';

    $where = array();
    if ($page_id > 0) {
        $where[] = "a.page_id = $page_id";
    } elseif ($data_id > 0 && !empty($page_type)) {
        $where[] = "a.data_id = $data_id AND a.page_type = '$page_type'";
    } else {
        return array('status' => 'error', 'message' => 'Invalid page parameters');
    }

    $where_clause = 'WHERE ' . implode(' AND ', $where);

    // Get ALL groups assigned to this page (merged or not)
    $sql = "SELECT a.group_id FROM faq_page_assignments a $where_clause";
    if (function_exists('mysql') && is_string($db)) {
        $result = mysql($db, $sql);
    } else {
        $result = mysql_query($sql, $db);
    }

    $group_ids = array();
    if ($result && mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_assoc($result)) {
            $group_ids[] = intval($row['group_id']);
        }
    }

    return array('status' => 'success', 'group_ids' => $group_ids);
}

function handle_get_page_assigned_groups($db)
{
    $page_id = isset($_POST['page_id']) ? intval($_POST['page_id']) : 0;
    $data_id = isset($_POST['data_id']) ? intval($_POST['data_id']) : 0;
    $page_type = isset($_POST['page_type']) ? addslashes($_POST['page_type']) : '';
    $assignment_id = isset($_POST['assignment_id']) ? intval($_POST['assignment_id']) : 0;

    $where = array();
    if ($page_id > 0) {
        $where[] = "a.page_id = $page_id";
    } elseif ($data_id > 0 && !empty($page_type)) {
        $where[] = "a.data_id = $data_id AND a.page_type = '$page_type'";
    } else {
        return array('status' => 'success', 'group_ids' => array());
    }

    if ($assignment_id > 0) {
        $where[] = "a.id != $assignment_id";
    }

    $where_clause = 'WHERE ' . implode(' AND ', $where);
    $sql = "SELECT DISTINCT a.group_id FROM faq_page_assignments a $where_clause";

    if (function_exists('mysql') && is_string($db)) {
        $result = mysql($db, $sql);
    } else {
        $result = mysql_query($sql, $db);
    }

    $group_ids = array();
    if ($result) {
        while ($row = mysql_fetch_assoc($result)) {
            if (isset($row['group_id'])) {
                $group_ids[] = intval($row['group_id']);
            }
        }
    }

    return array('status' => 'success', 'group_ids' => $group_ids);
}

function handle_get_page_groups_order($db)
{
    $page_id = isset($_POST['page_id']) ? intval($_POST['page_id']) : 0;
    $data_id = isset($_POST['data_id']) ? intval($_POST['data_id']) : 0;
    $page_type = isset($_POST['page_type']) ? addslashes($_POST['page_type']) : '';
    $assignment_type = isset($_POST['assignment_type']) ? $_POST['assignment_type'] : 'static';

    $check_column_sql = "SHOW COLUMNS FROM faq_page_assignments LIKE 'show_title'";
    if (function_exists('mysql') && is_string($db)) {
        $check_result = mysql($db, $check_column_sql);
        $has_show_title = ($check_result !== false);
    } else {
        $check_result = mysql_query($check_column_sql, $db);
        $has_show_title = ($check_result && mysql_num_rows($check_result) > 0);
    }
    $show_title_field = $has_show_title ? ", COALESCE(a.show_title, 1) as show_title" : "";

    if ($data_id > 0 && !empty($page_type)) {
        $test_sql = "SELECT COUNT(*) as cnt FROM faq_page_assignments WHERE data_id = $data_id AND page_type = '$page_type'";
        if (function_exists('mysql') && is_string($db)) {
            $test_result = mysql($db, $test_sql);
        } else {
            $test_result = mysql_query($test_sql, $db);
        }

        $has_assignments = false;
        if ($test_result) {
            $test_row = mysql_fetch_assoc($test_result);
            if ($test_row && isset($test_row['cnt']) && intval($test_row['cnt']) > 0) {
                $has_assignments = true;
            }
        }

        if (!$has_assignments) {
            return array('status' => 'error', 'message' => 'No assignments found for data_id ' . $data_id . ' and page_type ' . $page_type, 'data_id' => $data_id, 'page_type' => $page_type);
        }

        // Post type assignment
        $sql = "SELECT a.id as assignment_id, a.group_id, a.page_id, a.data_id, a.page_type, a.custom_label, a.custom_title, a.custom_subtitle, a.cta_title, a.cta_text, a.cta_email,
                        CONCAT(COALESCE(dc.data_name, 'Unknown'), ' (', 
                            CASE 
                                WHEN dc.data_type = 4 THEN 'Group'
                                WHEN dc.data_type = 20 THEN 'Post'
                                ELSE 'Member'
                            END, ' - ', 
                            CASE 
                                WHEN a.page_type = 'search_result_page' THEN 'Search Result Page'
                                WHEN a.page_type = 'detail_page' THEN 'Detail Page'
                                ELSE ''
                            END, ')') as display_name,
                        g.id as group_id, g.group_name, 
                        IFNULL(o.sort_order, 999) as sort_order$show_title_field
                    FROM faq_page_assignments a
                    LEFT JOIN data_categories dc ON a.data_id = dc.data_id
                    LEFT JOIN faq_groups g ON a.group_id = g.id
                    LEFT JOIN faq_orders o ON a.data_id = o.data_id AND a.page_type = o.page_type AND a.group_id = o.group_id
                    WHERE a.data_id = $data_id AND a.page_type = '$page_type' AND a.group_id IS NOT NULL
                    ORDER BY IFNULL(o.sort_order, 999) ASC, a.id ASC";
    } elseif ($page_id > 0) {
        // Static page - with faq_orders support
        $sql = "SELECT a.id as assignment_id, 
                        a.group_id, 
                        a.page_id, 
                        a.custom_label,
                        a.custom_title, 
                        a.custom_subtitle,
                        a.cta_title,
                        a.cta_text,
                        a.cta_email,
                        IFNULL(g.group_name, 'Unknown') as group_name,
                        IFNULL(o.sort_order, 999) as sort_order,
                        COALESCE(NULLIF(seo.title, ''), NULLIF(seo.filename, ''), CONCAT('Page ID: ', a.page_id)) as display_name$show_title_field
                    FROM faq_page_assignments a
                    LEFT JOIN faq_groups g ON a.group_id = g.id
                    LEFT JOIN faq_orders o ON a.page_id = o.page_id AND a.group_id = o.group_id
                    LEFT JOIN list_seo seo ON a.page_id = seo.seo_id
                    WHERE a.page_id = $page_id
                    ORDER BY IFNULL(o.sort_order, 999) ASC, a.id ASC
                    LIMIT 50";
    } else {
        return array('status' => 'error', 'message' => 'Invalid page parameters. Please provide either page_id or data_id with page_type.', 'page_id' => $page_id, 'data_id' => $data_id, 'page_type' => $page_type);
    }

    if (function_exists('mysql') && is_string($db)) {
        $result = mysql($db, $sql);
    } else {
        $result = mysql_query($sql, $db);
    }

    if (!$result) {
        return array('status' => 'error', 'message' => 'Database query failed');
    }

    $groups = array();
    while ($row = mysql_fetch_assoc($result)) {
        if ($row && is_array($row)) {
            $groups[] = $row;
        }
    }

    if (empty($groups)) {
        return array('status' => 'error', 'message' => 'No groups assigned to this page');
    }

    $page_name = $groups[0]['display_name'] ?? 'Unknown Page';

    return array(
        'status' => 'success',
        'groups' => $groups,
        'page_name' => $page_name,
        'count' => count($groups)
    );
}

function render_questions_table($db, $page = 1, $per_page = 10, $show_all = false)
{
    if (!$db) {
        return '<div class="alert alert-danger">
                        <strong>Database Error:</strong> No database connection available.
                    </div>';
    }

    $page = max(1, intval($page));
    $per_page = max(1, intval($per_page));

    $count_sql = "SELECT COUNT(DISTINCT q.id) as total FROM faq_questions q";
    if (function_exists('mysql') && is_string($db)) {
        $count_result = mysql($db, $count_sql);
    } else {
        $count_result = mysql_query($count_sql, $db);
    }
    $total_rows = 0;
    if ($count_result && mysql_num_rows($count_result) > 0) {
        $count_row = mysql_fetch_assoc($count_result);
        $total_rows = intval($count_row['total']);
    }

    $sql = "SELECT q.*, GROUP_CONCAT(g.group_name ORDER BY g.group_name SEPARATOR ', ') as groups
                FROM faq_questions q
                LEFT JOIN faq_group_questions gq ON q.id = gq.question_id
                LEFT JOIN faq_groups g ON gq.group_id = g.id
                GROUP BY q.id
                ORDER BY q.id DESC";

    if (!$show_all) {
        $offset = ($page - 1) * $per_page;
        $sql .= " LIMIT $per_page OFFSET $offset";
    }

    if (function_exists('mysql') && is_string($db)) {
        $result = mysql($db, $sql);
    } else {
        $result = mysql_query($sql, $db);
    }

    if ($result === false || $result === null) {
        $error_msg = '';
        $error_num = 0;
        if (function_exists('mysql_error')) {
            if (!is_string($db)) {
                $error_msg = mysql_error($db);
                $error_num = mysql_errno($db);
            }
        }
        error_log("FAQ Questions Query Error [{$error_num}]: " . $error_msg);
        if (empty($error_msg)) {
            $error_msg = "Query failed. Error code: {$error_num}";
        }
        return '<div class="alert alert-danger">Unable to load questions: ' . htmlspecialchars($error_msg) . '</div>';
    }

    $table_html = render_questions_table_html($result, $db);
    $total_pages = $show_all ? 1 : max(1, ceil($total_rows / $per_page));
    $pagination_html = render_pagination_html('questions', $page, $total_pages, $total_rows, $per_page, $show_all);

    return '<div class="faq-table-viewport">' . $table_html . '</div>' . $pagination_html;
}

function render_questions_table_html($result, $db)
{
    $html = '<table class="faq-irow regtext" id="questions-table" border="0">
                    <thead>
                        <tr class="tablehead">
                            <th class="center" style="width: 24px;"><input type="checkbox" id="select-all-questions" onchange="toggleSelectAllQuestions(this.checked)" title="Select All"></th>
                            <th style="width: 60px;">ID</th>
                            <th>Question</th>
                            <th style="width: 250px;">Assigned Groups</th>
                            <th style="width: 200px; min-width: 180px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>';

    if ($result && mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_assoc($result)) {
            $question_preview = mb_substr(strip_tags($row['question']), 0, 80) . (mb_strlen(strip_tags($row['question'])) > 80 ? '...' : '');
            $groups = $row['groups'] ? explode(', ', $row['groups']) : array('Unassigned');

            $groups_html = '';
            foreach ($groups as $group) {
                $groups_html .= '<span class="faq-plugin-badge faq-plugin-badge-secondary" style="margin-right: 6px; margin-bottom: 4px; display: inline-block;">' . htmlspecialchars(trim($group)) . '</span>';
            }

            $html .= '<tr class="tablerow" data-question-id="' . $row['id'] . '">
                            <td class="center" style="width: 24px;"><input type="checkbox" class="question-checkbox" value="' . $row['id'] . '" onchange="updateBulkActionsVisibility()"></td>
                            <td style="white-space: nowrap; width: 1%;"><strong>Q' . $row['id'] . '</strong></td>
                            <td>' . htmlspecialchars($question_preview) . '</td>
                            <td>' . $groups_html . '</td>
                            <td style="white-space: nowrap;">
                                <button class="faq-plugin-btn faq-plugin-btn-primary faq-plugin-btn-sm" onclick="editQuestion(' . htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') . ')" title="Edit" style="margin-right: 6px; display: inline-flex; align-items: center; gap: 4px;">
                                    <i class="fa fa-edit"></i> <span>Edit</span>
                                </button>
                                <button class="faq-plugin-btn faq-plugin-btn-danger faq-plugin-btn-sm" onclick="deleteQuestion(' . $row['id'] . ')" title="Delete" style="display: inline-flex; align-items: center; gap: 4px;">
                                    <i class="fa fa-trash"></i> <span>Delete</span>
                                </button>
                            </td>
                        </tr>';
        }
    } else {
        $html .= '<tr><td colspan="5" class="faq-plugin-empty-state"><i class="fa fa-info-circle"></i> No questions found. Add your first question!</td></tr>';
    }

    $html .= '</tbody></table>';
    return $html;
}

function render_groups_table($db, $page = 1, $per_page = 10, $show_all = false)
{
    if (!$db) {
        return '<div class="alert alert-danger">
                        <strong>Database Error:</strong> No database connection available.
                    </div>';
    }

    $page = max(1, intval($page));
    $per_page = max(1, intval($per_page));

    $count_sql = "SELECT COUNT(*) as total FROM faq_groups";
    if (function_exists('mysql') && is_string($db)) {
        $count_result = mysql($db, $count_sql);
    } else {
        $count_result = mysql_query($count_sql, $db);
    }
    $total_rows = 0;
    if ($count_result && mysql_num_rows($count_result) > 0) {
        $count_row = mysql_fetch_assoc($count_result);
        $total_rows = intval($count_row['total']);
    }

    $sql = "SELECT * FROM faq_groups ORDER BY id ASC";

    if (!$show_all) {
        $offset = ($page - 1) * $per_page;
        $sql .= " LIMIT $per_page OFFSET $offset";
    }

    if (function_exists('mysql') && is_string($db)) {
        $result = mysql($db, $sql);
    } else {
        $result = mysql_query($sql, $db);
    }

    if ($result === false || $result === null) {
        $error_msg = '';
        $error_num = 0;
        if (function_exists('mysql_error')) {
            if (!is_string($db)) {
                $error_msg = mysql_error($db);
                $error_num = mysql_errno($db);
            }
        }
        $result_type = $result === null ? 'NULL' : 'FALSE';
        error_log("FAQ Groups Query Error [{$error_num}]: " . $error_msg . " | SQL: " . $sql . " | Result: {$result_type}");

        if (empty($error_msg)) {
            $error_msg = "Query returned {$result_type}. Error code: {$error_num}";
        }

        return '<div class="alert alert-danger">
                        <strong>Database Error:</strong> Unable to load groups.<br>
                        <small>Error: ' . htmlspecialchars($error_msg) . '</small><br>
                        <small>Error Code: ' . htmlspecialchars($error_num) . '</small><br>
                        <small>SQL: ' . htmlspecialchars($sql) . '</small>
                    </div>';
    }

    $table_html = render_groups_table_html($result, $db);
    $total_pages = $show_all ? 1 : max(1, ceil($total_rows / $per_page));
    $pagination_html = render_pagination_html('groups', $page, $total_pages, $total_rows, $per_page, $show_all);

    return $table_html . $pagination_html;
}

function render_groups_table_html($result, $db)
{
    try {
        if (!$result) {
            return '<div class="alert alert-danger">Invalid database result</div>';
        }

        if (!function_exists('mysql_num_rows') && !function_exists('mysql_fetch_assoc')) {
            return '<div class="alert alert-danger">MySQL functions not available</div>';
        }

        // Per-group question counts and page assignment details for Details column
        $qcounts = array();
        $group_pages = array();
        if ($db) {
            $qcount_sql = "SELECT group_id, COUNT(*) as cnt FROM faq_group_questions GROUP BY group_id";
            if (function_exists('mysql') && is_string($db)) {
                $qres = mysql($db, $qcount_sql);
            } else {
                $qres = mysql_query($qcount_sql, $db);
            }
            if ($qres && mysql_num_rows($qres) > 0) {
                while ($qr = mysql_fetch_assoc($qres)) {
                    $qcounts[intval($qr['group_id'])] = intval($qr['cnt']);
                }
            }
            $assign_sql = "SELECT a.group_id,
                COALESCE(NULLIF(TRIM(s.title),''), NULLIF(TRIM(s.filename),''), dc.data_name, CONCAT('Page ', COALESCE(a.page_id, a.data_id))) as display_name
                FROM faq_page_assignments a
                LEFT JOIN list_seo s ON a.page_id = s.seo_id
                LEFT JOIN data_categories dc ON a.data_id = dc.data_id";
            if (function_exists('mysql') && is_string($db)) {
                $ares = mysql($db, $assign_sql);
            } else {
                $ares = mysql_query($assign_sql, $db);
            }
            if ($ares && mysql_num_rows($ares) > 0) {
                while ($ar = mysql_fetch_assoc($ares)) {
                    $gid = intval($ar['group_id']);
                    if (!isset($group_pages[$gid])) {
                        $group_pages[$gid] = array();
                    }
                    $name = isset($ar['display_name']) && $ar['display_name'] !== null ? trim($ar['display_name']) : '';
                    if ($name !== '') {
                        $name = replaceBDPlaceholders($name, $db);
                        $group_pages[$gid][] = $name;
                    }
                }
            }
        }

        $html = '<table class="faq-irow regtext" id="groups-table" border="0">
                    <thead>
                        <tr class="tablehead">
                            <th class="center" style="width: 24px;"><input type="checkbox" id="select-all-groups" onchange="toggleSelectAllGroups(this.checked)" title="Select All"></th>
                            <th style="width: 60px;">ID</th>
                            <th>Group Name</th>
                            <th style="min-width: 200px;">Details</th>
                            <th style="width: 200px; min-width: 180px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>';

        if ($result) {
            $num_rows = 0;
            if (function_exists('mysql_num_rows')) {
                $num_rows = mysql_num_rows($result);
            }

            $row_count = 0;
            $has_rows = false;

            if (function_exists('mysql_data_seek') && $num_rows > 0) {
                mysql_data_seek($result, 0);
            }

            while (($row = mysql_fetch_assoc($result)) !== false) {
                if (!$row || !is_array($row)) break;
                $has_rows = true;
                $row_count++;

                $is_system = isset($row['group_slug']) && in_array($row['group_slug'], array('global', 'unassigned'));
                $delete_btn = $is_system ?
                    '<span class="text-muted"><i class="fa fa-lock"></i> Protected</span>' :
                    '<button class="faq-plugin-btn faq-plugin-btn-danger faq-plugin-btn-sm" onclick="deleteGroup(' . intval($row['id']) . ')" title="Delete" style="display: inline-flex; align-items: center; gap: 4px;"><i class="fa fa-trash"></i> <span>Delete</span></button>';

                $group_data = array(
                    'id' => intval($row['id']),
                    'group_name' => isset($row['group_name']) ? $row['group_name'] : '',
                    'group_slug' => isset($row['group_slug']) ? $row['group_slug'] : ''
                );
                $group_json = json_encode($group_data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
                if ($group_json === false) {
                    $group_json = json_encode(array('id' => intval($row['id']), 'group_name' => '', 'group_slug' => ''), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
                }

                $gid = intval($row['id']);
                $qcount = isset($qcounts[$gid]) ? $qcounts[$gid] : 0;
                $pages = isset($group_pages[$gid]) ? $group_pages[$gid] : array();
                $details_parts = array();
                $details_parts[] = $qcount . ' question' . ($qcount !== 1 ? 's' : '');
                if (count($pages) > 0) {
                    $page_list = implode(', ', array_map('htmlspecialchars', array_slice($pages, 0, 5)));
                    if (count($pages) > 5) {
                        $page_list .= ' +' . (count($pages) - 5) . ' more';
                    }
                    $details_parts[] = 'Assigned to: ' . $page_list;
                } else {
                    $details_parts[] = 'Not assigned to any page';
                }
                $details_html = '<span class="faq-plugin-badge faq-plugin-badge-secondary" style="margin-right: 6px;" title="Questions in this group">' . $qcount . ' Q</span>';
                if (count($pages) > 0) {
                    $full_list = implode(', ', array_map('htmlspecialchars', $pages));
                    $details_html .= ' <span class="faq-plugin-badge faq-plugin-badge-info" title="' . htmlspecialchars($full_list, ENT_QUOTES, 'UTF-8') . '">' . count($pages) . ' page(s)</span>';
                }

                $checkbox_html = $is_system ? 
                    '<input type="checkbox" class="group-checkbox" value="' . intval($row['id']) . '" disabled title="System groups cannot be deleted">' :
                    '<input type="checkbox" class="group-checkbox" value="' . intval($row['id']) . '" onchange="updateBulkGroupsVisibility()">';

                $html .= '<tr class="tablerow" data-group-id="' . intval($row['id']) . '">
                                <td class="center" style="width: 24px;">' . $checkbox_html . '</td>
                                <td style="white-space: nowrap; width: 1%;"><strong>#' . intval($row['id']) . '</strong></td>
                                <td>' . htmlspecialchars(isset($row['group_name']) ? $row['group_name'] : '') . ($is_system ? ' <span class="faq-plugin-badge faq-plugin-badge-info" style="margin-left: 8px;">System</span>' : '') . '</td>
                                <td><div style="font-size: 12px; color: #475569;">' . $details_html . '</div><div style="margin-top: 4px; font-size: 12px; color: #64748b;">' . htmlspecialchars(implode(' · ', $details_parts)) . '</div></td>
                                <td style="white-space: nowrap;">
                                    <button class="faq-plugin-btn faq-plugin-btn-primary faq-plugin-btn-sm" onclick="editGroup(' . htmlspecialchars($group_json, ENT_QUOTES, 'UTF-8') . '); return false;" title="Edit" style="margin-right: 6px; display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="fa fa-edit"></i> <span>Edit</span>
                                </button>
                                ' . $delete_btn . '
                            </td>
                        </tr>';
            }

            if (!$has_rows) {
                $html .= '<tr><td colspan="5" class="faq-plugin-empty-state"><i class="fa fa-info-circle"></i> No groups found.</td></tr>';
            }
        } else {
            $html .= '<tr><td colspan="5" class="faq-plugin-empty-state"><i class="fa fa-info-circle"></i> No groups found. (Invalid result)</td></tr>';
        }

        $html .= '</tbody></table>';
        return $html;
    } catch (Exception $e) {
        error_log("Render Groups Table HTML Exception: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
        return '<div class="alert alert-danger">Error rendering groups table: ' . htmlspecialchars($e->getMessage()) . '</div>';
    } catch (Error $e) {
        error_log("Render Groups Table HTML Fatal: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine());
        return '<div class="alert alert-danger">Fatal error rendering groups table</div>';
    }
}

function render_assignments_table($db, $page = 1, $per_page = 10, $show_all = false, $public_domain = null)
{
    // Get public domain if not provided
    if ($public_domain === null) {
        $design_settings = array();
        if (function_exists('mysql') && is_string($db)) {
            $settings_result = mysql($db, "SELECT * FROM faq_design_settings");
        } else {
            $settings_result = mysql_query("SELECT * FROM faq_design_settings", $db);
        }
        if ($settings_result) {
            while ($s = mysql_fetch_assoc($settings_result)) {
                $design_settings[$s['setting_key']] = $s['setting_value'];
            }
        }
        $public_domain = getPublicDomain($design_settings);
    }
    if (!$db) {
        return '<div class="alert alert-danger">
                        <strong>Database Error:</strong> No database connection available.
                    </div>';
    }

    $page = max(1, intval($page));
    $per_page = max(1, intval($per_page));

    $check_column_sql = "SHOW COLUMNS FROM faq_page_assignments LIKE 'show_title'";
    if (function_exists('mysql') && is_string($db)) {
        $check_result = mysql($db, $check_column_sql);
    } else {
        $check_result = mysql_query($check_column_sql, $db);
    }
    $has_show_title = ($check_result && mysql_num_rows($check_result) > 0);

    $check_merge_sql = "SHOW COLUMNS FROM faq_page_assignments LIKE 'merge_groups'";
    if (function_exists('mysql') && is_string($db)) {
        $check_merge_result = mysql($db, $check_merge_sql);
    } else {
        $check_merge_result = mysql_query($check_merge_sql, $db);
    }
    $has_merge_groups = ($check_merge_result && mysql_num_rows($check_merge_result) > 0);

    if ($has_show_title) {
        $show_title_field = "COALESCE(a.show_title, 1) as show_title";
    } else {
        $show_title_field = "1 as show_title";
    }

    $merge_groups_field = $has_merge_groups ? ", COALESCE(a.merge_groups, 0) as merge_groups" : ", 0 as merge_groups";

    $count_sql = "SELECT COUNT(*) as total FROM faq_page_assignments a";
    if (function_exists('mysql') && is_string($db)) {
        $count_result = mysql($db, $count_sql);
    } else {
        $count_result = mysql_query($count_sql, $db);
    }
    $total_rows = 0;
    if ($count_result && mysql_num_rows($count_result) > 0) {
        $count_row = mysql_fetch_assoc($count_result);
        $total_rows = intval($count_row['total']);
    }

    $sql = "SELECT a.*, 
                    COALESCE(
                        NULLIF(s.title, ''),
                        NULLIF(s.filename, ''),
                        CONCAT(dc.data_name, ' (', 
                            CASE 
                                WHEN dc.data_type = 4 THEN 'Group'
                                WHEN dc.data_type = 20 THEN 'Post'
                                ELSE 'Member'
                            END, ' - ', 
                            CASE 
                                WHEN a.page_type = 'search_result_page' THEN 'Search Result Page'
                                WHEN a.page_type = 'detail_page' THEN 'Detail Page'
                                ELSE ''
                            END, ')'),
                        CONCAT('Page ID: ', a.page_id)
                    ) as display_name,
                    g.group_name,
                    (SELECT COUNT(*) FROM faq_group_questions WHERE group_id = a.group_id) as group_question_count,
                    COALESCE(o.sort_order, 999) as sort_order,
                    $show_title_field
                    $merge_groups_field,
                    CASE 
                        WHEN a.page_id IS NOT NULL THEN 'static'
                        WHEN a.data_id IS NOT NULL THEN 'post_type'
                        ELSE 'unknown'
                    END as assignment_type,
                    s.filename as page_filename,
                    dc.data_name as data_name,
                    dc.data_type as data_type
                FROM faq_page_assignments a
                LEFT JOIN list_seo s ON a.page_id = s.seo_id
                LEFT JOIN data_categories dc ON a.data_id = dc.data_id
                JOIN faq_groups g ON a.group_id = g.id
                LEFT JOIN faq_orders o ON ((a.page_id IS NOT NULL AND o.page_id = a.page_id) OR (a.data_id IS NOT NULL AND o.data_id = a.data_id AND o.page_type = a.page_type)) AND o.group_id = a.group_id
                ORDER BY display_name ASC, sort_order ASC, a.id DESC";

    if (!$show_all) {
        $offset = ($page - 1) * $per_page;
        $sql .= " LIMIT $per_page OFFSET $offset";
    }

    if (function_exists('mysql') && is_string($db)) {
        $result = mysql($db, $sql);
    } else {
        $result = mysql_query($sql, $db);
    }

    if ($result === false || $result === null) {
        $error_msg = '';
        $error_num = 0;
        if (function_exists('mysql_error')) {
            if (!is_string($db)) {
                $error_msg = mysql_error($db);
                $error_num = mysql_errno($db);
            } else {
                $error_msg = "Query execution failed. Please check if all columns exist.";
            }
        }
        error_log("FAQ Assignments Query Error [{$error_num}]: " . $error_msg . " | SQL: " . substr($sql, 0, 200));
        if (empty($error_msg)) {
            $error_msg = "Query failed. Error code: {$error_num}. Please run upgrade_show_title.sql if you haven't already.";
        }
        return '<div class="alert alert-danger">Unable to load assignments: ' . htmlspecialchars($error_msg) . '<br><small>SQL: ' . htmlspecialchars(substr($sql, 0, 300)) . '...</small></div>';
    }

    // Use provided public_domain or calculate if not provided
    if ($public_domain === null) {
        $design_settings = array();
        if (function_exists('mysql') && is_string($db)) {
            $settings_result = mysql($db, "SELECT * FROM faq_design_settings");
        } else {
            $settings_result = mysql_query("SELECT * FROM faq_design_settings", $db);
        }
        if ($settings_result) {
            while ($s = mysql_fetch_assoc($settings_result)) {
                $design_settings[$s['setting_key']] = $s['setting_value'];
            }
        }
        $public_domain = getPublicDomain($design_settings);
    }

    $table_html = render_assignments_table_html($result, $db, $public_domain);
    $total_pages = $show_all ? 1 : max(1, ceil($total_rows / $per_page));
    $pagination_html = render_pagination_html('assignments', $page, $total_pages, $total_rows, $per_page, $show_all);

    return '<div class="faq-table-viewport">' . $table_html . '</div>' . $pagination_html;
}

function render_assignments_table_html($result, $db, $public_domain = '')
{
    // Always fetch public domain from database to ensure we have the latest saved value
    if (function_exists('mysql') && is_string($db)) {
        $settings_result_for_url = mysql($db, "SELECT * FROM faq_design_settings WHERE setting_key = 'public_domain'");
    } else {
        $settings_result_for_url = mysql_query("SELECT * FROM faq_design_settings WHERE setting_key = 'public_domain'", $db);
    }
    if ($settings_result_for_url && mysql_num_rows($settings_result_for_url) > 0) {
        $setting_row_for_url = mysql_fetch_assoc($settings_result_for_url);
        $saved_public_domain = trim($setting_row_for_url['setting_value']);
        if (!empty($saved_public_domain)) {
            // Remove any existing protocol to prevent double protocols
            $saved_public_domain = preg_replace('/^https?:\/\//', '', $saved_public_domain);
            // Ensure it has protocol
            $saved_public_domain = 'https://' . ltrim($saved_public_domain, '/');
            $public_domain = rtrim($saved_public_domain, '/');
        }
    }
    // If still empty, use getPublicDomain function
    if (empty($public_domain)) {
        $all_design_settings = array();
        if (function_exists('mysql') && is_string($db)) {
            $all_settings_result = mysql($db, "SELECT * FROM faq_design_settings");
        } else {
            $all_settings_result = mysql_query("SELECT * FROM faq_design_settings", $db);
        }
        if ($all_settings_result) {
            while ($s = mysql_fetch_assoc($all_settings_result)) {
                $all_design_settings[$s['setting_key']] = $s['setting_value'];
            }
        }
        $public_domain = getPublicDomain($all_design_settings);
    }
    
    $html = '<style>
        @media (max-width: 768px) {
            .faq-page-title {
                font-size: 12px !important;
                word-break: break-word;
                max-width: 150px;
                display: inline-block;
            }
        }
    </style>
    <table class="faq-irow regtext" border="0">
                    <thead>
                        <tr class="tablehead">
                            <th class="center" style="white-space: nowrap; width: 1%;">ID</th>
                            <th style="min-width: 200px;">Page/Post Type</th>
                            <th>Group</th>
                            <th style="min-width: 150px;">Custom Title</th>
                            <th style="width: 100px;">Show Title</th>
                            <th style="width: 220px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>';

    if ($result && mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_assoc($result)) {
            // Build page URL
            $page_url = '';
            $page_url_title = '';
            if ($row['assignment_type'] == 'static' && !empty($row['page_filename'])) {
                // Static page - use filename
                $page_url = '/' . ltrim($row['page_filename'], '/');
                $page_url_title = 'View Page: ' . htmlspecialchars($row['page_filename']);
            } elseif ($row['assignment_type'] == 'post_type' && !empty($row['data_id'])) {
                // Post type - construct URL based on data_id and page_type
                $data_id = intval($row['data_id']);
                $page_type = $row['page_type'] ?? 'search_result_page';
                if ($page_type == 'detail_page') {
                    // Detail page URL format (may vary by BD setup)
                    $page_url = '/detail/' . $data_id;
                } else {
                    // Search result page URL format
                    $page_url = '/search/' . $data_id;
                }
                $page_url_title = 'View ' . ($row['data_name'] ?? 'Post Type') . ' Page';
            }
            
            // Create URL link with tooltip (built after we have resolved page name below)
            $url_link = '';
            $full_url = '';
            if (!empty($page_url)) {
                // Use the public_domain we fetched at the start of the function
                // Ensure public_domain has trailing slash removed and page_url starts with /
                $public_domain_clean = rtrim($public_domain, '/');
                
                // If public_domain is empty, try to get it again or use fallback
                if (empty($public_domain_clean)) {
                    $fallback_design_settings = array();
                    if (function_exists('mysql') && is_string($db)) {
                        $fallback_settings_result = mysql($db, "SELECT * FROM faq_design_settings");
                    } else {
                        $fallback_settings_result = mysql_query("SELECT * FROM faq_design_settings", $db);
                    }
                    if ($fallback_settings_result) {
                        while ($fs = mysql_fetch_assoc($fallback_settings_result)) {
                            $fallback_design_settings[$fs['setting_key']] = $fs['setting_value'];
                        }
                    }
                    $public_domain_clean = getPublicDomain($fallback_design_settings);
                    $public_domain_clean = rtrim($public_domain_clean, '/');
                }
                
                // Final fallback: use current protocol and host if still empty
                if (empty($public_domain_clean)) {
                    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
                    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
                    // Remove admin prefix if present
                    if (preg_match('/^admin\.|^admin-|\.admin\./', $host)) {
                        $host = preg_replace('/^admin\.|^admin-|\.admin\./', '', $host);
                    }
                    $public_domain_clean = $protocol . '://' . $host;
                }
                
                $page_url_clean = '/' . ltrim($page_url, '/');
                $full_url = $public_domain_clean . $page_url_clean;
            }
            
            // Replace BD placeholders in display name (%%%website_name%%% etc.) so correct page name is shown
            $display_name_processed = replaceBDPlaceholders($row['display_name'], $db);
            $page_display_escaped = htmlspecialchars($display_name_processed);
            $type_badge = ($row['assignment_type'] == 'post_type') ?
                '<span class="faq-plugin-badge faq-plugin-badge-info">Post Type</span>' :
                '<span class="faq-plugin-badge faq-plugin-badge-secondary">Static</span>';
            // First line: page name as link (with icon) so users see it's clickable; second line: page type tag
            if (!empty($page_url) && !empty($full_url)) {
                $view_tooltip = htmlspecialchars($page_display_escaped . ' — ' . $full_url . ' (Opens in new tab)', ENT_QUOTES);
                $page_name_link = '<a href="' . htmlspecialchars($full_url, ENT_QUOTES) . '" target="_blank" rel="noopener noreferrer" class="faq-page-name-link" title="' . $view_tooltip . '"><i class="fa fa-external-link" aria-hidden="true"></i> ' . $page_display_escaped . '</a>';
            } else {
                $page_name_link = '<span class="faq-page-title">' . $page_display_escaped . '</span>';
            }
            $page_display = '<div class="page-display-tab-col"><div class="faq-page-name-row">' . $page_name_link . '</div><div class="faq-page-type-row">' . $type_badge . '</div></div>';

            $show_title_badge = ($row['show_title'] == 1 || $row['show_title'] === true) ?
                '<span class="faq-plugin-badge faq-plugin-badge-success">Yes</span>' :
                '<span class="faq-plugin-badge faq-plugin-badge-secondary">No</span>';

            $merge_badge = (isset($row['merge_groups']) && ($row['merge_groups'] == 1 || $row['merge_groups'] === true)) ?
                '<span class="faq-plugin-badge faq-plugin-badge-primary" style="margin-left: 6px;" title="Groups will be merged into single accordion">Merged</span>' : '';

            $assignment_data = array(
                'id' => intval($row['id']),
                'group_id' => intval($row['group_id']),
                'page_id' => isset($row['page_id']) && $row['page_id'] !== null ? intval($row['page_id']) : null,
                'data_id' => isset($row['data_id']) && $row['data_id'] !== null ? intval($row['data_id']) : null,
                'page_type' => isset($row['page_type']) && $row['page_type'] !== null ? $row['page_type'] : null,
                'custom_label' => isset($row['custom_label']) ? $row['custom_label'] : '',
                'custom_title' => isset($row['custom_title']) ? $row['custom_title'] : '',
                'custom_subtitle' => isset($row['custom_subtitle']) ? $row['custom_subtitle'] : '',
                'cta_title' => isset($row['cta_title']) ? $row['cta_title'] : '',
                'cta_text' => isset($row['cta_text']) ? $row['cta_text'] : '',
                'cta_email' => isset($row['cta_email']) ? $row['cta_email'] : '',
                'show_title' => isset($row['show_title']) ? (intval($row['show_title']) == 1 ? 1 : 0) : 1,
                'merge_groups' => isset($row['merge_groups']) ? (intval($row['merge_groups']) == 1 ? 1 : 0) : 0,
                'assignment_type' => isset($row['assignment_type']) ? $row['assignment_type'] : 'static'
            );
            $assignment_json = json_encode($assignment_data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
            $assignment_json_escaped = htmlspecialchars($assignment_json, ENT_QUOTES, 'UTF-8');

            $row_style = ($merge_badge) ? 'background-color: #f0f8ff;' : '';
            $group_qcount = isset($row['group_question_count']) ? intval($row['group_question_count']) : 0;
            $group_cell = '<div class="faq-assignment-group-col"><div class="faq-assignment-group-name-row">' . '<span class="faq-plugin-badge faq-plugin-badge-primary">' . htmlspecialchars($row['group_name']) . '</span>' . $merge_badge . '</div><div class="faq-assignment-group-meta"><span class="faq-assignment-group-q-badge" title="' . $group_qcount . ' question' . ($group_qcount !== 1 ? 's' : '') . ' in this group">' . $group_qcount . ' Q</span></div></div>';
            $html .= '<tr class="tablerow" style="' . $row_style . '">
                            <td style="white-space: nowrap; width: 1%;"><strong>#' . $row['id'] . '</strong></td>
                            <td>' . $page_display . '</td>
                            <td>' . $group_cell . '</td>
                            <td>' . htmlspecialchars($row['custom_title'] ?: '-') . '</td>
                            <td>' . $show_title_badge . '</td>
                            <td class="faq-assignment-actions-cell">
                                <div class="faq-assignment-actions-wrap">
                                    <button class="faq-plugin-btn faq-plugin-btn-secondary faq-plugin-btn-sm" onclick="openPageDesignModal(' . ($row['page_id'] !== null ? intval($row['page_id']) : 'null') . ', ' . (isset($row['data_id']) && $row['data_id'] !== null ? intval($row['data_id']) : 'null') . ', ' . htmlspecialchars(json_encode(isset($row['page_type']) ? $row['page_type'] : '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') . ', ' . htmlspecialchars(json_encode($display_name_processed, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') . ')" title="Override design for this page"><i class="fa fa-paint-brush"></i> <span>Design</span></button>
                                    <button class="faq-plugin-btn faq-plugin-btn-secondary faq-plugin-btn-sm" onclick="orderPageGroups(' . ($row['page_id'] ?: ($row['data_id'] ?: 0)) . ', ' . htmlspecialchars(json_encode($row['display_name'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') . ', ' . htmlspecialchars(json_encode($row['assignment_type'] ?: 'static', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') . ', ' . htmlspecialchars(json_encode($row['page_type'] ?: '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') . ')" title="Order Groups"><i class="fa fa-sort"></i> <span>Order</span></button>
                                    <button class="faq-plugin-btn faq-plugin-btn-primary faq-plugin-btn-sm" onclick="editAssignment(' . $assignment_json_escaped . ')" title="' . ($merge_badge ? 'Edit Merged Groups (edits all merged rows together)' : 'Edit') . '"><i class="fa fa-edit"></i> <span>Edit</span></button>
                                    <button class="faq-plugin-btn faq-plugin-btn-danger faq-plugin-btn-sm" onclick="deleteAssignment(' . $row['id'] . ')" title="Delete"><i class="fa fa-trash"></i> <span>Delete</span></button>
                                </div>
                            </td>
                        </tr>';
        }
    } else {
        $html .= '<tr><td colspan="6" class="faq-plugin-empty-state"><i class="fa fa-info-circle"></i> No assignments found. Assign a group to a page!</td></tr>';
    }

    $html .= '</tbody></table>';
    return $html;
}

$all_groups = array();
if (function_exists('mysql') && is_string($db)) {
    $groups_result = mysql($db, "SELECT * FROM faq_groups ORDER BY group_name ASC");
} else {
    $groups_result = mysql_query("SELECT * FROM faq_groups ORDER BY group_name ASC", $db);
}
if ($groups_result) {
    while ($g = mysql_fetch_assoc($groups_result)) {
        $all_groups[] = $g;
    }
}

$all_pages = array();
if (function_exists('mysql') && is_string($db)) {
    $pages_result = mysql($db, "SELECT seo_id, filename FROM list_seo WHERE filename != '' ORDER BY filename ASC");
} else {
    $pages_result = mysql_query("SELECT seo_id, filename FROM list_seo WHERE filename != '' ORDER BY filename ASC", $db);
}
if ($pages_result) {
    while ($p = mysql_fetch_assoc($pages_result)) {
        $all_pages[] = $p;
    }
}

$all_post_types = array();
if (function_exists('mysql') && is_string($db)) {
    $post_types_result = mysql($db, "SELECT data_id, data_name, data_type FROM data_categories ORDER BY data_name ASC");
} else {
    $post_types_result = mysql_query("SELECT data_id, data_name, data_type FROM data_categories ORDER BY data_name ASC", $db);
}
if ($post_types_result) {
    while ($pt = mysql_fetch_assoc($post_types_result)) {
        $all_post_types[] = $pt;
    }
}

$design_settings = array();
if (function_exists('mysql') && is_string($db)) {
    $settings_result = mysql($db, "SELECT * FROM faq_design_settings");
} else {
    $settings_result = mysql_query("SELECT * FROM faq_design_settings", $db);
}
if ($settings_result) {
    while ($s = mysql_fetch_assoc($settings_result)) {
        $design_settings[$s['setting_key']] = $s['setting_value'];
    }
}

// Default CDN URL provided by plugin owner (for automatic updates via API)
// Users can override this with their own CDN URL if needed
define('FAQ_OWNER_CDN_URL', 'https://cdn.bdgrowthsuite.com'); // Change this to your actual CDN URL

// Get public domain for page URLs (admin domain might be different from public domain)
function getPublicDomain($design_settings) {
    // First, check if manually configured
    if (isset($design_settings['public_domain']) && !empty($design_settings['public_domain'])) {
        $domain = trim($design_settings['public_domain']);
        // Remove any existing protocol to prevent double protocols
        $domain = preg_replace('/^https?:\/\//', '', $domain);
        // Ensure it has protocol
        $domain = 'https://' . ltrim($domain, '/');
        return rtrim($domain, '/');
    }
    
    // Fallback: try to detect from current domain (remove admin subdomain if present)
    $current_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    if (preg_match('/^admin\.|^admin-|\.admin\./', $current_host)) {
        // Remove admin prefix
        $public_host = preg_replace('/^admin\.|^admin-|\.admin\./', '', $current_host);
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        return $protocol . '://' . $public_host;
    }
    
    // Last resort: use current domain
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    return $protocol . '://' . $current_host;
}

// Function to replace BD placeholders in text (e.g. %%%website_name%%%, %%%example_name%%%)
function replaceBDPlaceholders($text, $db = null) {
    if (empty($text)) {
        return $text;
    }
    
    // Resolve a single placeholder key (e.g. "website_name") to display value
    $resolvePlaceholder = function($key) use ($db) {
        $key = trim($key);
        if ($key === '') return '';
        // Known keys: try DB or $GLOBALS['w']
        if ($key === 'website_name') {
            $website_name = '';
            if ($db) {
                if (function_exists('mysql') && is_string($db)) {
                    $website_result = mysql($db, "SELECT website_name FROM websites WHERE website_id = 1 LIMIT 1");
                } else {
                    $website_result = mysql_query("SELECT website_name FROM websites WHERE website_id = 1 LIMIT 1", $db);
                }
                if ($website_result && mysql_num_rows($website_result) > 0) {
                    $website_row = mysql_fetch_assoc($website_result);
                    $website_name = $website_row['website_name'] ?? '';
                }
            }
            if (empty($website_name) && isset($GLOBALS['w']) && isset($GLOBALS['w']['website_name'])) {
                $website_name = $GLOBALS['w']['website_name'];
            }
            return $website_name !== '' ? $website_name : 'Website';
        }
        // Try $GLOBALS['w'][$key] for other BD context variables
        if (isset($GLOBALS['w']) && is_array($GLOBALS['w']) && isset($GLOBALS['w'][$key]) && (is_string($GLOBALS['w'][$key]) || is_numeric($GLOBALS['w'][$key]))) {
            return (string) $GLOBALS['w'][$key];
        }
        // Friendly label for unknown placeholders so we never show raw %%%var%%%
        return ucwords(str_replace('_', ' ', $key));
    };
    
    // Replace all %%%variable_name%%% patterns
    $text = preg_replace_callback('/%%%([a-z0-9_]+)%%%/i', function($m) use ($resolvePlaceholder) {
        return $resolvePlaceholder($m[1]);
    }, $text);
    
    return $text;
}

$active_design = isset($design_settings['design_preset']) ? $design_settings['design_preset'] : 'custom';
// Use custom CDN if set, otherwise use owner's default CDN
$cdn_base_url = isset($design_settings['cdn_base_url']) && !empty($design_settings['cdn_base_url']) 
    ? $design_settings['cdn_base_url'] 
    : FAQ_OWNER_CDN_URL;

// Get public domain for generating page URLs
$public_domain = getPublicDomain($design_settings);

// Get website color defaults from website_design_settings
$website_color_defaults = getWebsiteColorDefaults($db);

// Load customization settings
$layout_type = isset($design_settings['layout_type']) ? $design_settings['layout_type'] : 'accordion';
$title_alignment = isset($design_settings['title_alignment']) ? $design_settings['title_alignment'] : 'center';
$font_family = isset($design_settings['font_family']) ? $design_settings['font_family'] : 'system';
$premade_font_mode = isset($design_settings['premade_font_mode']) ? $design_settings['premade_font_mode'] : 'template_default';
$template_lock_mode = isset($design_settings['template_lock_mode']) ? $design_settings['template_lock_mode'] : 'flexible';
if (!in_array($premade_font_mode, array('template_default', 'website_font', 'custom_font'), true)) {
    $premade_font_mode = 'template_default';
}
if (!in_array($template_lock_mode, array('strict', 'flexible'), true)) {
    $template_lock_mode = 'flexible';
}
$primary_color = (isset($design_settings['primary_color']) && trim((string) $design_settings['primary_color']) !== '')
    ? $design_settings['primary_color']
    : $website_color_defaults['primary_color'];
$background_color = isset($design_settings['background_color']) ? $design_settings['background_color'] : $website_color_defaults['background_color'];
$background_color_parsed = faq_parse_background_color($background_color);
$card_background_color = isset($design_settings['card_background_color']) ? $design_settings['card_background_color'] : $website_color_defaults['card_background_color'];
$text_color = isset($design_settings['text_color']) ? $design_settings['text_color'] : $website_color_defaults['text_color'];
$title_text_color = isset($design_settings['title_text_color']) ? $design_settings['title_text_color'] : $text_color;
$question_text_color = (isset($design_settings['question_text_color']) && trim((string) $design_settings['question_text_color']) !== '')
    ? $design_settings['question_text_color']
    : $text_color;
$answer_text_color = isset($design_settings['answer_text_color']) ? $design_settings['answer_text_color'] : $text_color;
$title_font_size = isset($design_settings['title_font_size']) ? $design_settings['title_font_size'] : '32';
$question_font_size = isset($design_settings['question_font_size']) ? $design_settings['question_font_size'] : '18';
$answer_font_size = isset($design_settings['answer_font_size']) ? $design_settings['answer_font_size'] : '16';

// Container and Card layout settings
$container_width = isset($design_settings['container_width']) ? $design_settings['container_width'] : '900';
$card_style = isset($design_settings['card_style']) ? $design_settings['card_style'] : 'shadow';

// Grid/Card layout settings
$grid_columns = isset($design_settings['grid_columns']) ? intval($design_settings['grid_columns']) : 3;
$video_columns = isset($design_settings['video_columns']) ? intval($design_settings['video_columns']) : 3;
$card_radius = isset($design_settings['card_radius']) ? intval($design_settings['card_radius']) : 12;
$card_icon_url = isset($design_settings['card_icon_url']) ? $design_settings['card_icon_url'] : '';
$card_icon_shape = isset($design_settings['card_icon_shape']) ? $design_settings['card_icon_shape'] : 'circle';
$card_padding = isset($design_settings['card_padding']) ? intval($design_settings['card_padding']) : 24;

// Design configuration - Easy to add new designs here
// CDN base URL: https://cdn.bdgrowthsuite.com
// CSS files are in /tools/ directory
// Preview images should be in /tools/images/ directory
// Design Templates (Fixed layouts with minimal customization)
// Default container background per premade template (from CDN CSS) - used by "Use default" in Page Assignment
$design_config = array(
    'minimal' => array(
        'name' => 'Minimal',
        'description' => 'Ultra-clean design with subtle borders and elegant simplicity',
        'preview_image' => rtrim($cdn_base_url, '/') . '/tools/images/faq-minimal-preview.gif',
        'preview_image_small' => rtrim($cdn_base_url, '/') . '/tools/images/faq-minimal-preview-small.jpg',
        'fixed_layout' => 'accordion',
        'allow_customization' => 'colors_only',
        'default_bg' => '#ffffff'
    ),
    'split' => array(
        'name' => 'Split Layout',
        'description' => 'Two-column layout with intro section and numbered accordion',
        'preview_image' => rtrim($cdn_base_url, '/') . '/tools/images/faq-split-preview.gif',
        'preview_image_small' => rtrim($cdn_base_url, '/') . '/tools/images/faq-split-preview-small.jpg',
        'fixed_layout' => 'accordion',
        'allow_customization' => 'colors_only',
        'default_bg' => '#f5f5f0'
    ),
    'colorful' => array(
        'name' => 'Colorful',
        'description' => 'Vibrant design with bold pink header and eye-catching style',
        'preview_image' => rtrim($cdn_base_url, '/') . '/tools/images/faq-colorful-preview.gif',
        'preview_image_small' => rtrim($cdn_base_url, '/') . '/tools/images/faq-colorful-preview-small.jpg',
        'fixed_layout' => 'accordion',
        'allow_customization' => 'colors_only',
        'default_bg' => '#ffffff'
    ),
    'modern' => array(
        'name' => 'Modern',
        'description' => 'Clean, contemporary style with smooth animations',
        'preview_image' => rtrim($cdn_base_url, '/') . '/tools/images/faq-modern-preview.gif',
        'preview_image_small' => rtrim($cdn_base_url, '/') . '/tools/images/faq-modern-preview-small.jpg',
        'fixed_layout' => null, // null means layout can be customized
        'allow_customization' => 'full',
        'default_bg' => '#667eea' // gradient start in CDN
    ),
    'simple' => array(
        'name' => 'Simple',
        'description' => 'Minimalist style focused on content',
        'preview_image' => rtrim($cdn_base_url, '/') . '/tools/images/faq-simple-preview.gif',
        'preview_image_small' => rtrim($cdn_base_url, '/') . '/tools/images/faq-simple-preview-small.jpg',
        'fixed_layout' => null, // null means layout can be customized
        'allow_customization' => 'full',
        'default_bg' => '#ffffff'
    ),
    'card' => array(
        'name' => 'Card',
        'description' => 'Card-based style with visual appeal',
        'preview_image' => rtrim($cdn_base_url, '/') . '/tools/images/faq-card-preview.gif',
        'preview_image_small' => rtrim($cdn_base_url, '/') . '/tools/images/faq-card-preview-small.jpg',
        'fixed_layout' => 'grid-card', // Card style works best with grid-card layout
        'allow_customization' => 'full',
        'default_bg' => '#1e3a8a'
    ),
    'classic' => array(
        'name' => 'Classic',
        'description' => 'Traditional, professional appearance',
        'preview_image' => rtrim($cdn_base_url, '/') . '/tools/images/faq-classic-preview.gif',
        'preview_image_small' => rtrim($cdn_base_url, '/') . '/tools/images/faq-classic-preview-small.jpg',
        'fixed_layout' => null, // null means layout can be customized
        'allow_customization' => 'full',
        'default_bg' => '#ffffff'
    )
);


?>

<!-- Simple WYSIWYG Editor (Native Browser API - No External Dependencies) -->
<style>
/* Simple Rich Text Editor Styles */
.bd-faq-editor-wrapper {
    border: 1px solid #ddd;
    border-radius: 4px;
    background: #fff;
}
.bd-faq-editor-toolbar {
    background: #f8f9fa;
    border-bottom: 1px solid #ddd;
    padding: 8px;
    display: flex;
    gap: 4px;
    flex-wrap: wrap;
    border-radius: 4px 4px 0 0;
}
.bd-faq-editor-btn {
    background: #fff;
    border: 1px solid #ddd;
    padding: 6px 10px;
    cursor: pointer;
    border-radius: 3px;
    font-size: 13px;
    color: #333;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.bd-faq-editor-btn:hover {
    background: #e9ecef;
    border-color: #adb5bd;
}
.bd-faq-editor-btn.active {
    background: #007bff;
    color: #fff;
    border-color: #007bff;
}
.bd-faq-editor-btn i {
    font-size: 14px;
}
.bd-faq-editor-content {
    min-height: 250px;
    padding: 12px;
    outline: none;
    overflow-y: auto;
    border-radius: 0 0 4px 4px;
}
.bd-faq-editor-content:focus {
    outline: none;
}
.bd-faq-editor-content[contenteditable="true"]:empty:before {
    content: attr(data-placeholder);
    color: #999;
    font-style: italic;
}
</style>
<script>
    if (typeof bootstrap === 'undefined' && typeof $.fn.modal === 'undefined') {
        document.write('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">');
    }
    
    window.toggleAdvancedSettings = function() {
        try {
            var section = document.getElementById('advanced_settings_section');
            if (section) {
                if (section.style.display === 'none' || section.style.display === '') {
                    section.style.display = 'block';
                } else {
                    section.style.display = 'none';
                }
            }
        } catch (e) {
            console.error('FAQ Plugin: Error toggling advanced settings:', e);
        }
    };

    window.saveBottomSettingsPublicDomain = async function() {
        var publicDomain = ($('#settings_public_domain').val() || '').trim();
        if (publicDomain && !publicDomain.match(/^https?:\/\/.+/)) {
            showToast('error', 'Please enter a valid URL starting with http:// or https://');
            return;
        }
        const response = await sendAjax('save_design_setting', {
            setting_key: 'public_domain',
            setting_value: publicDomain
        }, null, true);
        if (response && response.status === 'success') {
            $('#public_domain').val(publicDomain);
            showToast('success', 'Public domain saved.');
            setTimeout(function() { location.reload(); }, 600);
        } else {
            showToast('error', (response && response.message) ? response.message : 'Failed to save public domain.');
        }
    };

    window.saveBottomSettingsCdn = async function() {
        var cdnUrl = ($('#settings_cdn_base_url').val() || '').trim();
        const ownerCdn = <?php echo json_encode(FAQ_OWNER_CDN_URL); ?>;
        var cdnToSave = (cdnUrl === ownerCdn) ? '' : cdnUrl;
        const response = await sendAjax('save_design_setting', {
            setting_key: 'cdn_base_url',
            setting_value: cdnToSave
        }, null, true);
        if (response && response.status === 'success') {
            $('#cdn_base_url').val(cdnUrl);
            showToast('success', cdnToSave ? 'Custom CDN saved.' : 'Using default CDN.');
        } else {
            showToast('error', (response && response.message) ? response.message : 'Failed to save CDN.');
        }
    };

    window.showHelpTopic = function(topicId) {
        $('.faq-help-topic-panel').hide();
        $('#' + topicId).show();
        $('.faq-help-topic-btn').removeClass('active');
        $('.faq-help-topic-btn[data-topic="' + topicId + '"]').addClass('active');
    };
</script>

<style>
    /* Toggle Switch Styles */
    .design-mode-toggle input:checked+span {
        background-color: #10b981 !important;
    }

    .design-mode-toggle input:checked+span+span {
        transform: translateX(26px);
    }

    .design-mode-toggle span:first-of-type {
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .design-mode-toggle span:last-of-type {
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
    }
    
    /* Toast message styling - smaller text */
    .faq-toast-small .swal2-title {
        font-size: 14px !important;
        line-height: 1.4 !important;
        padding: 10px 15px !important;
    }
    
    @media (max-width: 768px) {
        .faq-toast-small .swal2-title {
            font-size: 13px !important;
            padding: 8px 12px !important;
        }
    }
</style>

    <div id="globalLoader">
        <div class="loader-spinner"></div>
    </div>
    
    <style>
        @keyframes spin {
            0% { transform: translateY(-50%) rotate(0deg); }
            100% { transform: translateY(-50%) rotate(360deg); }
        }
        .search-loader {
            display: none;
        }
    </style>

<div id="table-header-module">
    <h2>FAQ Management Plugin</h2>
</div>
<div class="faq-plugin-container">
    <div class="profile-tabs faq-design-tab-active" id="profile-tabs">

        <ul class="main-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#tab-design">
                    <i class="bi bi-palette-fill"></i> <strong>Design</strong>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#tab-groups">
                    <i class="bi bi-stack"></i> <strong>Manage Groups</strong>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#tab-questions">
                    <i class="fa fa-question-circle"></i> <strong>Manage Questions</strong>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#tab-assignments">
                    <i class="fa fa-link"></i> <strong>Page Assignment</strong>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#tab-priority">
                    <i class="fa fa-sort"></i> <strong>Set Priority</strong>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#tab-help">
                    <i class="fa fa-question-circle"></i> <strong>Help</strong>
                </a>
            </li>
        </ul>

        

        <div id="tab-design" class="tab-pane fade show active">
            <div class="faq-plugin-tab-content">
                <div class="faq-design-info-row">
                    <p class="alert alert_info faq-info-alert-block faq-design-info-left">
                        <i class="fa fa-info-circle"></i> <strong>What this tab does:</strong> Control how your FAQ block looks on the frontend. Choose how your FAQ looks — use the switch on the right to pick <strong>Custom Layout</strong> or <strong>Pre-made Templates</strong>.
                    </p>
                    <div class="faq-design-toggle-box faq-design-toggle-right">
                        <div class="faq-design-mode-descriptions">
                            <span class="faq-desc-line faq-desc-custom"><strong>Custom Layout</strong> — Full control over colors, fonts, and layout.</span>
                            <span class="faq-desc-line faq-desc-templates"><strong>Templates</strong> — Professional, production-ready. Click a card to activate. Some fixed layout (colors only), others full customization.</span>
                        </div>
                    </div>
                </div>

                <h2 class="settings_title faq-design-settings-title">
                    <span class="faq-design-title-left"><i class="fa fa-paint-brush fa-fw"></i> Design Settings</span>
                    <div class="faq-design-toggle-inline faq-design-switch-next-to-title">
                        <span class="faq-toggle-indicator"><i class="fa fa-exchange"></i> Switch:</span>
                        <span id="toggle_label_custom" class="faq-toggle-opt">Custom Layout</span>
                        <label class="design-mode-toggle design-mode-toggle-compact">
                            <input type="checkbox" id="design_mode_toggle" <?php echo in_array($active_design, ['minimal', 'split', 'colorful']) ? 'checked' : ''; ?> onchange="toggleDesignMode(this.checked)">
                            <span class="design-mode-slider"></span>
                            <span class="design-mode-knob"></span>
                        </label>
                        <span id="toggle_label_template" class="faq-toggle-opt faq-toggle-opt-alt">Templates</span>
                    </div>
                </h2>
                <hr class="faq-design-title-rule" />

                <!-- Custom Layout Section -->
                <div id="custom_layout_section" style="<?php echo in_array($active_design, ['minimal', 'split', 'colorful', 'modern', 'simple', 'card', 'classic']) ? 'display: none;' : ''; ?>">
                    <div class="faq-design-instructions-block">
                        <h4 class="faq-design-instructions-title"><i class="fa fa-lightbulb-o"></i> Design instructions</h4>
                        <ol class="faq-design-instructions-list">
                            <li>Every setting below controls how your FAQ block looks on the frontend. Change a value and the preview updates in real time.</li>
                            <li><strong>Where colors are used:</strong> <em>Section background</em> — behind the whole FAQ block; <em>Card/item background</em> — each question row (and answer area); <em>Theme/accent</em> — active question highlight, borders, buttons; <em>Title</em> — section heading text; <em>Question/Answer text</em> — question and answer text color.</li>
                        </ol>
                    </div>
                    <div class="faq-plugin-section-header faq-section-header-compact">
                        <h4 class="faq-plugin-section-title">Custom Layout Settings</h4>
                        <p class="faq-section-subtitle">Settings are grouped by where they apply: Title, All Questions, Active Question, and Active Answer.</p>
                    </div>

                    <!-- Template Fixed Layout Notice -->
                    <div id="fixed_layout_notice" style="display: none; background: #fff3cd; border: 1px solid #ffc107; border-radius: 6px; padding: 12px 16px; margin: 20px 0; color: #856404;">
                        <strong style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 18px;">⚠️</span>
                            <span>This template has a fixed layout</span>
                        </strong>
                        <p style="margin: 8px 0 0 0; font-size: 13px;">
                            The selected template has a pre-designed structure that cannot be changed. Only color customization is available to match your brand.
                        </p>
                    </div>

                    <div class="faq-custom-layout-split">
                        <div class="faq-custom-layout-settings">
                            <div class="accordion" role="tablist" id="faqDesignAccordion">
                        <!-- Section: Layout & Width -->
                        <div class="setting_holder accordion-section">
                            <h2 class="accordion-header" role="tab" tabindex="0">
                                Layout &amp; Width
                                <a class="expand_module" href="#" aria-expanded="false"><i class="fa fa-chevron-down"></i></a>
                            </h2>
                            <ul class="inline_list accordion-content" role="tabpanel">
                                <li>
                                    <label for="customization_layout_type">How FAQ blocks appear</label>
                                    <select name="layout_type" autocomplete="off" id="customization_layout_type" onchange="handleLayoutTypeChange(this.value)">
                                        <?php
                                        $all_layouts = array(
                                            'accordion' => 'Accordion',
                                            'search-first' => 'Search-First (Help Center)',
                                            'tabbed' => 'Tabbed Navigation',
                                            'single-column' => 'Single Column',
                                            'grid-card' => 'Grid/Card Layout',
                                            'sidebar' => 'Sidebar Navigation',
                                            'persona-based' => 'Persona-Based',
                                            'conversational' => 'Conversational (Chatbot)',
                                            'video-multimedia' => 'Video/Multimedia',
                                            'step-by-step' => 'Step-by-Step/Flowchart'
                                        );
                                        foreach ($all_layouts as $layout_key => $layout_name):
                                            $is_selected = $layout_type == $layout_key;
                                        ?>
                                            <option value="<?php echo htmlspecialchars($layout_key); ?>" <?php echo $is_selected ? 'selected' : ''; ?>><?php echo htmlspecialchars($layout_name); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted" id="layout_type_help_text" style="display: block; margin-top: 4px;">Accordion, tabs, grid, etc.</small>
                                    <div id="tabbed_layout_note" style="display: none; margin-top: 8px; padding: 10px; background: #e3f2fd; border-left: 3px solid #2196f3; border-radius: 4px; font-size: 13px; color: #1565c0;">
                                        <i class="fa fa-info-circle"></i> <strong>Note:</strong> Tabbed works best with multiple FAQ groups on the page.
                                    </div>
                                </li>
                                <li id="grid_columns_wrapper" style="<?php echo $layout_type != 'grid-card' ? 'display: none;' : ''; ?>">
                                    <label for="customization_grid_columns">Cards per row (Grid)</label>
                                    <select name="grid_columns" autocomplete="off" id="customization_grid_columns" onchange="saveCustomizationSetting('grid_columns', this.value)">
                                        <option value="2" <?php echo $grid_columns == 2 ? 'selected' : ''; ?>>2</option>
                                        <option value="3" <?php echo $grid_columns == 3 ? 'selected' : ''; ?>>3</option>
                                        <option value="4" <?php echo $grid_columns == 4 ? 'selected' : ''; ?>>4</option>
                                    </select>
                                </li>
                                <li id="video_columns_wrapper" style="<?php echo $layout_type != 'video-multimedia' ? 'display: none;' : ''; ?>">
                                    <label for="customization_video_columns">Cards per row (Video)</label>
                                    <select name="video_columns" autocomplete="off" id="customization_video_columns" onchange="saveCustomizationSetting('video_columns', this.value)">
                                        <option value="1" <?php echo $video_columns == 1 ? 'selected' : ''; ?>>1</option>
                                        <option value="2" <?php echo $video_columns == 2 ? 'selected' : ''; ?>>2</option>
                                        <option value="3" <?php echo $video_columns == 3 ? 'selected' : ''; ?>>3</option>
                                        <option value="4" <?php echo $video_columns == 4 ? 'selected' : ''; ?>>4</option>
                                    </select>
                                </li>
                                <li id="card_radius_wrapper">
                                    <label for="customization_card_radius">Card corner radius (px)</label>
                                    <input type="number" name="card_radius" id="customization_card_radius" value="<?php echo htmlspecialchars($card_radius); ?>" min="0" max="50" onchange="saveCustomizationSetting('card_radius', this.value)" placeholder="12">
                                </li>
                                <li>
                                    <label for="customization_container_width">FAQ area max width</label>
                                    <?php 
                                    $is_preset_width = in_array($container_width, array('100%', '900', '1100', '1400'));
                                    $custom_width_value = $is_preset_width ? '' : $container_width;
                                    ?>
                                    <div style="display: flex; gap: 8px; align-items: center;">
                                        <select name="container_width" autocomplete="off" id="customization_container_width" onchange="handleContainerWidthChange(this.value)" style="flex: 1;">
                                            <option value="100%" <?php echo $container_width == '100%' ? 'selected' : ''; ?>>Full Width (100%)</option>
                                            <option value="900" <?php echo $container_width == '900' ? 'selected' : ''; ?>>Narrow (900px)</option>
                                            <option value="1100" <?php echo $container_width == '1100' ? 'selected' : ''; ?>>Medium (1100px)</option>
                                            <option value="1400" <?php echo $container_width == '1400' ? 'selected' : ''; ?>>Wide (1400px)</option>
                                            <option value="custom" <?php echo !$is_preset_width ? 'selected' : ''; ?>>Custom</option>
                                        </select>
                                        <input type="number" name="container_width_custom" id="customization_container_width_custom" value="<?php echo htmlspecialchars($custom_width_value); ?>" placeholder="800" min="300" max="2000" style="width: 100px; <?php echo $is_preset_width ? 'display: none;' : ''; ?>" oninput="saveCustomizationSettingDebounced('container_width', this.value)">
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <!-- Section: Title Settings -->
                        <div class="setting_holder accordion-section">
                            <h2 class="accordion-header" role="tab" tabindex="0">
                                Title Settings
                                <a class="expand_module" href="#" aria-expanded="false"><i class="fa fa-chevron-down"></i></a>
                            </h2>
                            <ul class="inline_list accordion-content" role="tabpanel">
                                <li class="faq-setting-where-used">Used for the FAQ section heading (e.g. &ldquo;Frequently Asked Questions&rdquo;) and the area behind it.</li>
                                <li>
                                    <label for="customization_background_color_text">Background + Opacity</label>
                                    <input type="text" name="background_color" class="faq-spectrum-input faq-spectrum-alpha" id="customization_background_color_text" data-setting="background_color" value="<?php echo htmlspecialchars($background_color); ?>" placeholder="rgba(255,255,255,1)">
                                    <small class="text-muted" style="display: block; margin-top: 4px;">Section background (behind title and all content). Use picker to set opacity.</small>
                                </li>
                                <li>
                                    <label for="customization_title_font_size">Title text size (px)</label>
                                    <input type="number" name="title_font_size" id="customization_title_font_size" value="<?php echo htmlspecialchars($title_font_size); ?>" min="12" max="72" oninput="saveCustomizationSettingDebounced('title_font_size', this.value)" placeholder="32">
                                </li>
                                <li>
                                    <label for="customization_title_text_color_text">Text color</label>
                                    <input type="text" name="title_text_color" class="faq-spectrum-input" id="customization_title_text_color_text" data-setting="title_text_color" value="<?php echo htmlspecialchars($title_text_color); ?>" placeholder="#1f2937">
                                </li>
                                <li>
                                    <label for="customization_title_alignment">Alignment</label>
                                    <select name="title_alignment" autocomplete="off" id="customization_title_alignment" onchange="saveCustomizationSetting('title_alignment', this.value)">
                                        <option value="left" <?php echo $title_alignment == 'left' ? 'selected' : ''; ?>>Left</option>
                                        <option value="center" <?php echo $title_alignment == 'center' ? 'selected' : ''; ?>>Center</option>
                                        <option value="right" <?php echo $title_alignment == 'right' ? 'selected' : ''; ?>>Right</option>
                                    </select>
                                </li>
                            </ul>
                        </div>
                        <!-- Section: All Questions -->
                        <div class="setting_holder accordion-section">
                            <h2 class="accordion-header" role="tab" tabindex="0">
                                All Questions
                                <a class="expand_module" href="#" aria-expanded="false"><i class="fa fa-chevron-down"></i></a>
                            </h2>
                            <ul class="inline_list accordion-content" role="tabpanel">
                                <li class="faq-setting-where-used">Applied to each question row when collapsed (and to the answer area background).</li>
                                <li>
                                    <label for="customization_card_background_color_text">Background</label>
                                    <input type="text" name="card_background_color" class="faq-spectrum-input" id="customization_card_background_color_text" data-setting="card_background_color" value="<?php echo htmlspecialchars($card_background_color); ?>" placeholder="#ffffff" onchange="var a=document.getElementById('customization_card_background_color_text_active_answer_alias');if(a)a.value=this.value;">
                                    <small class="text-muted" style="display: block; margin-top: 4px;">Card/item background for each Q&amp;A block.</small>
                                </li>
                                <li>
                                    <label for="customization_question_font_size">Question text size (px)</label>
                                    <input type="number" name="question_font_size" id="customization_question_font_size" value="<?php echo htmlspecialchars($question_font_size); ?>" min="12" max="36" oninput="var a=document.getElementById('customization_question_font_size_active_alias');if(a)a.value=this.value;saveCustomizationSettingDebounced('question_font_size', this.value)" placeholder="18">
                                </li>
                                <li>
                                    <label for="customization_question_text_color_text">Text color</label>
                                    <input type="text" name="question_text_color" class="faq-spectrum-input" id="customization_question_text_color_text" data-setting="question_text_color" value="<?php echo htmlspecialchars($question_text_color); ?>" placeholder="#1f2937" onchange="var a=document.getElementById('customization_question_text_color_text_active_alias');if(a)a.value=this.value;">
                                </li>
                                <li>
                                    <label for="customization_card_style">Block style</label>
                                    <select name="card_style" autocomplete="off" id="customization_card_style" onchange="saveCustomizationSetting('card_style', this.value)">
                                        <option value="minimal" <?php echo $card_style == 'minimal' ? 'selected' : ''; ?>>Minimal (Clean Lines)</option>
                                        <option value="shadow" <?php echo $card_style == 'shadow' ? 'selected' : ''; ?>>Shadow</option>
                                        <option value="elevated" <?php echo $card_style == 'elevated' ? 'selected' : ''; ?>>Elevated (Strong Shadow)</option>
                                        <option value="bordered" <?php echo $card_style == 'bordered' ? 'selected' : ''; ?>>Bordered</option>
                                        <option value="simple" <?php echo $card_style == 'simple' ? 'selected' : ''; ?>>Simple (Light Border)</option>
                                        <option value="flat" <?php echo $card_style == 'flat' ? 'selected' : ''; ?>>Flat (No Border/Shadow)</option>
                                    </select>
                                </li>
                            </ul>
                        </div>
                        <!-- Section: Active Question -->
                        <div class="setting_holder accordion-section">
                            <h2 class="accordion-header" role="tab" tabindex="0">
                                Active Question
                                <a class="expand_module" href="#" aria-expanded="false"><i class="fa fa-chevron-down"></i></a>
                            </h2>
                            <ul class="inline_list accordion-content" role="tabpanel">
                                <li class="faq-setting-where-used">When a question is expanded (active), these settings control active question background, text size, and text color.</li>
                                <li>
                                    <label for="customization_primary_color_text">Background color</label>
                                    <input type="text" name="primary_color" class="faq-spectrum-input" id="customization_primary_color_text" data-setting="primary_color" value="<?php echo htmlspecialchars($primary_color); ?>" placeholder="#1e3a8a">
                                    <small class="text-muted" style="display: block; margin-top: 4px;">Theme/accent; used as active question highlight, borders, and accents.</small>
                                </li>
                                <li>
                                    <label for="customization_question_font_size_active_alias">Question text size (px)</label>
                                    <input type="number" name="question_font_size_active_alias" id="customization_question_font_size_active_alias" value="<?php echo htmlspecialchars($question_font_size); ?>" min="12" max="36" oninput="document.getElementById('customization_question_font_size').value=this.value;saveCustomizationSettingDebounced('question_font_size', this.value)" placeholder="18">
                                </li>
                                <li>
                                    <label for="customization_question_text_color_text_active_alias">Text color</label>
                                    <input type="text" name="question_text_color_active_alias" class="faq-spectrum-input" id="customization_question_text_color_text_active_alias" value="<?php echo htmlspecialchars($question_text_color); ?>" placeholder="#1f2937" onchange="document.getElementById('customization_question_text_color_text').value=this.value;saveCustomizationSetting('question_text_color', this.value);">
                                </li>
                            </ul>
                        </div>
                        <!-- Section: Active Answer -->
                        <div class="setting_holder accordion-section">
                            <h2 class="accordion-header" role="tab" tabindex="0">
                                Active Answer
                                <a class="expand_module" href="#" aria-expanded="false"><i class="fa fa-chevron-down"></i></a>
                            </h2>
                            <ul class="inline_list accordion-content" role="tabpanel">
                                <li class="faq-setting-where-used">The visible answer area when a question is expanded. Set background, answer text size, and answer text color here.</li>
                                <li>
                                    <label for="customization_card_background_color_text_active_answer_alias">Background</label>
                                    <input type="text" name="active_answer_background_alias" class="faq-spectrum-input" id="customization_card_background_color_text_active_answer_alias" value="<?php echo htmlspecialchars($card_background_color); ?>" placeholder="#ffffff" onchange="document.getElementById('customization_card_background_color_text').value=this.value;saveCustomizationSetting('card_background_color', this.value);">
                                </li>
                                <li>
                                    <label for="customization_answer_font_size">Answer text size (px)</label>
                                    <input type="number" name="answer_font_size" id="customization_answer_font_size" value="<?php echo htmlspecialchars($answer_font_size); ?>" min="12" max="24" oninput="saveCustomizationSettingDebounced('answer_font_size', this.value)" placeholder="16">
                                </li>
                                <li>
                                    <label for="customization_answer_text_color_text">Text color</label>
                                    <input type="text" name="answer_text_color" class="faq-spectrum-input" id="customization_answer_text_color_text" data-setting="answer_text_color" value="<?php echo htmlspecialchars($answer_text_color); ?>" placeholder="#1f2937">
                                </li>
                            </ul>
                        </div>
                        <!-- Section: Font (global) -->
                        <div class="setting_holder accordion-section">
                            <h2 class="accordion-header" role="tab" tabindex="0">
                                Font (all text)
                                <a class="expand_module" href="#" aria-expanded="false"><i class="fa fa-chevron-down"></i></a>
                            </h2>
                            <ul class="inline_list accordion-content" role="tabpanel">
                                <li>
                                    <label for="customization_font_family">FAQ text font</label>
                                    <select name="font_family" autocomplete="off" id="customization_font_family" onchange="saveCustomizationSetting('font_family', this.value)">
                                        <option value="system" <?php echo $font_family == 'system' ? 'selected' : ''; ?>>System Default</option>
                                        <option value="arial" <?php echo $font_family == 'arial' ? 'selected' : ''; ?>>Arial</option>
                                        <option value="helvetica" <?php echo $font_family == 'helvetica' ? 'selected' : ''; ?>>Helvetica</option>
                                        <option value="georgia" <?php echo $font_family == 'georgia' ? 'selected' : ''; ?>>Georgia</option>
                                        <option value="times" <?php echo $font_family == 'times' ? 'selected' : ''; ?>>Times New Roman</option>
                                        <option value="courier" <?php echo $font_family == 'courier' ? 'selected' : ''; ?>>Courier New</option>
                                        <option value="verdana" <?php echo $font_family == 'verdana' ? 'selected' : ''; ?>>Verdana</option>
                                        <option value="roboto" <?php echo $font_family == 'roboto' ? 'selected' : ''; ?>>Roboto</option>
                                        <option value="open-sans" <?php echo $font_family == 'open-sans' ? 'selected' : ''; ?>>Open Sans</option>
                                        <option value="lato" <?php echo $font_family == 'lato' ? 'selected' : ''; ?>>Lato</option>
                                        <option value="montserrat" <?php echo $font_family == 'montserrat' ? 'selected' : ''; ?>>Montserrat</option>
                                        <option value="poppins" <?php echo $font_family == 'poppins' ? 'selected' : ''; ?>>Poppins</option>
                                        <option value="inter" <?php echo $font_family == 'inter' ? 'selected' : ''; ?>>Inter</option>
                                    </select>
                                    <small class="text-muted" style="display: block; margin-top: 4px;">Applies to title, questions, and answers.</small>
                                </li>
                            </ul>
                        </div>
                        <!-- Section: Grid/Card Options (visible only when layout is grid-card) -->
                        <div id="grid_card_options" class="setting_holder accordion-section faq-grid-only-section" style="<?php echo $layout_type != 'grid-card' ? 'display: none !important;' : ''; ?>">
                            <h2 class="accordion-header" role="tab" tabindex="0">
                                Grid / Card Options
                                <a class="expand_module" href="#" aria-expanded="false"><i class="fa fa-chevron-down"></i></a>
                            </h2>
                            <ul class="inline_list accordion-content" role="tabpanel">
                                <li>
                                    <label for="customization_card_icon_url">Card icon URL (Grid)</label>
                                    <input type="text" name="card_icon_url" id="customization_card_icon_url" value="<?php echo htmlspecialchars($card_icon_url); ?>" onchange="saveCustomizationSetting('card_icon_url', this.value); updateCardIconPreview(this.value);" placeholder="https://example.com/icon.png">
                                    <small class="text-muted" style="display: block; margin-top: 4px;">Leave empty to use the default question-mark icon.</small>
                                </li>
                                <li>
                                    <label for="customization_card_icon_shape">Icon shape (Grid)</label>
                                    <select name="card_icon_shape" autocomplete="off" id="customization_card_icon_shape" onchange="saveCustomizationSetting('card_icon_shape', this.value); updateCardIconPreview(document.getElementById('customization_card_icon_url').value);">
                                        <option value="circle" <?php echo $card_icon_shape == 'circle' ? 'selected' : ''; ?>>Circle</option>
                                        <option value="original" <?php echo $card_icon_shape == 'original' ? 'selected' : ''; ?>>Original</option>
                                    </select>
                                </li>
                                <li>
                                    <label for="customization_card_padding">Card padding (px, Grid)</label>
                                    <input type="number" name="card_padding" id="customization_card_padding" value="<?php echo htmlspecialchars($card_padding); ?>" min="8" max="48" oninput="saveCustomizationSettingDebounced('card_padding', this.value)" placeholder="24">
                                </li>
                                <li>
                                    <label>Icon preview</label>
                                    <div id="card_icon_preview" style="width: 48px; height: 48px; background: <?php echo $card_icon_shape == 'circle' ? htmlspecialchars($primary_color) : 'transparent'; ?>; border-radius: <?php echo $card_icon_shape == 'circle' ? '50%' : '0'; ?>; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                        <?php if (!empty($card_icon_url)): ?>
                                            <img src="<?php echo htmlspecialchars($card_icon_url); ?>" alt="Icon" style="width: 100%; height: 100%; object-fit: contain;">
                                        <?php else: ?>
                                            <i class="fa fa-question" style="color: white; font-size: 24px;"></i>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                        </div>
                        <div class="faq-custom-layout-preview">
                            <div class="faq-live-preview-panel">
                                <h4 class="faq-live-preview-title"><i class="fa fa-eye"></i> Live Preview</h4>
                                <div id="faq-live-preview-content" class="faq-live-preview-content">
                                    <p class="faq-live-preview-placeholder"><i class="fa fa-spinner fa-spin"></i> Loading preview…</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <script>
                    (function() {
                        function initFaqDesignAccordion() {
                            var accordion = document.getElementById('faqDesignAccordion');
                            if (!accordion) return;
                            var contents = accordion.querySelectorAll('.accordion-content');
                            var headers = accordion.querySelectorAll('.accordion-header');
                            contents.forEach(function(content) { content.classList.remove('is-open'); });
                            headers.forEach(function(header) {
                                var link = header.querySelector('.expand_module');
                                var icon = link ? link.querySelector('i') : null;
                                if (link) link.setAttribute('aria-expanded', 'false');
                                if (icon) icon.className = 'fa fa-chevron-down';
                            });
                            accordion.addEventListener('click', function(e) {
                                var header = e.target.closest('.accordion-header');
                                if (!header) return;
                                e.preventDefault();
                                var section = header.closest('.accordion-section');
                                var content = section ? section.querySelector('.accordion-content') : null;
                                var link = header.querySelector('.expand_module');
                                var icon = link ? link.querySelector('i') : null;
                                if (content) {
                                    var isOpen = content.classList.contains('is-open');
                                    content.classList.toggle('is-open', !isOpen);
                                    if (link) link.setAttribute('aria-expanded', !isOpen ? 'true' : 'false');
                                    if (icon) icon.className = !isOpen ? 'fa fa-chevron-up' : 'fa fa-chevron-down';
                                }
                            });
                        }
                        if (document.readyState === 'loading') {
                            document.addEventListener('DOMContentLoaded', initFaqDesignAccordion);
                        } else {
                            initFaqDesignAccordion();
                        }
                    })();
                    </script>
                    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/spectrum/1.8.0/spectrum.min.css">
                    <script src="https://cdnjs.cloudflare.com/ajax/libs/spectrum/1.8.0/spectrum.min.js"></script>
                    <script>
                    (function() {
                        function initFaqSpectrumPickers() {
                            if (typeof jQuery === 'undefined' || !jQuery.fn.spectrum) return;
                            var $ = jQuery;
                            $('.faq-spectrum-input').filter(function() { return !$(this).closest('#pageDesignModal').length; }).each(function() {
                                var $el = $(this);
                                if ($el.data('spectrum')) return;
                                var setting = $el.data('setting');
                                var isAlpha = $el.hasClass('faq-spectrum-alpha');
                                $el.spectrum({
                                    showInput: true,
                                    showButtons: true,
                                    showAlpha: isAlpha,
                                    preferredFormat: isAlpha ? 'rgb' : 'hex',
                                    change: function(c) {
                                        if (!c) return;
                                        var val;
                                        if (isAlpha) {
                                            var r = c.toRgb();
                                            val = 'rgba(' + r.r + ',' + r.g + ',' + r.b + ',' + (typeof c.alpha === 'number' ? c.alpha : 1) + ')';
                                        } else {
                                            val = c.toHexString();
                                        }
                                        $el.val(val);
                                        if (setting && typeof window.saveCustomizationSetting === 'function') window.saveCustomizationSetting(setting, val);
                                        if (typeof window.refreshFaqLivePreviewDebounced === 'function') window.refreshFaqLivePreviewDebounced();
                                    }
                                });
                            });
                            $('.faq-template-bg-spectrum').filter(function() { return !$(this).closest('#pageDesignModal').length; }).each(function() {
                                var $el = $(this);
                                if ($el.data('spectrum')) return;
                                var templateKey = $el.data('template-key');
                                $el.spectrum({
                                    showInput: true,
                                    showButtons: true,
                                    showAlpha: false,
                                    preferredFormat: 'hex',
                                    containerClassName: 'faq-template-grid-tpl-spectrum',
                                    replacerClassName: 'faq-template-grid-tpl-replacer',
                                    appendTo: 'body',
                                    show: function() {
                                        var inst = $el.data('spectrum');
                                        var $replacer = (inst && inst.replacer && inst.replacer.length) ? inst.replacer : $el.siblings('.faq-template-grid-tpl-replacer').first();
                                        var trigger = ($replacer && $replacer[0]) ? $replacer[0] : null;
                                        if (!trigger) return;
                                        function positionPicker() {
                                            var rect = trigger.getBoundingClientRect();
                                            var $c = (inst && inst.container) || $('body .sp-container.faq-template-grid-tpl-spectrum').last() || $('body .faq-template-grid-tpl-spectrum').last();
                                            if (!$c || !$c.length) return false;
                                            $c.css({ position: 'fixed', top: (rect.bottom + 6) + 'px', left: Math.max(6, rect.left) + 'px' });
                                            return true;
                                        }
                                        if (!positionPicker()) setTimeout(positionPicker, 0);
                                        setTimeout(positionPicker, 50);
                                    },
                                    change: function(c) {
                                        if (!c) return;
                                        var val = c.toHexString();
                                        $el.val(val);
                                        $('.faq-template-bg-swatch[data-template-key="' + templateKey + '"]').css('background-color', val);
                                        if (typeof window.saveTemplateBgColor === 'function') window.saveTemplateBgColor(templateKey, val);
                                        if (typeof window.refreshFaqLivePreviewDebounced === 'function') window.refreshFaqLivePreviewDebounced();
                                    }
                                });
                            });
                            $(document).off('click.faqTemplateBgSwatch', '.faq-template-bg-swatch').on('click.faqTemplateBgSwatch', '.faq-template-bg-swatch', function(e) {
                                e.preventDefault();
                                e.stopPropagation();
                                var templateKey = $(this).data('template-key');
                                if (!templateKey) return;
                                var $spec = $('#template_bg_' + templateKey + '_spectrum');
                                if (!$spec.length || typeof $spec.spectrum !== 'function') return;
                                var inst = $spec.data('spectrum');
                                if (inst && inst.replacer && inst.replacer.length) {
                                    inst.replacer.trigger('click');
                                } else {
                                    $spec.spectrum('show');
                                }
                            });
                            $(document).off('click.faqTemplateBgReset', '.faq-template-bg-reset').on('click.faqTemplateBgReset', '.faq-template-bg-reset', function(e) {
                                e.preventDefault();
                                e.stopPropagation();
                                var templateKey = $(this).data('template-key');
                                if (!templateKey) return;
                                if (typeof window.resetTemplateBgColor === 'function') window.resetTemplateBgColor(templateKey, e);
                            });
                        }
                        if (document.readyState === 'loading') {
                            document.addEventListener('DOMContentLoaded', initFaqSpectrumPickers);
                        } else {
                            initFaqSpectrumPickers();
                        }
                    })();
                    </script>

                </div>
                <!-- End Custom Layout Section -->

                <!-- Templates Section -->
                <div id="templates_section" style="<?php echo !in_array($active_design, ['minimal', 'split', 'colorful', 'modern', 'simple', 'card', 'classic']) ? 'display: none;' : ''; ?>">
                    <div class="faq-premade-templates-header">
                        <div class="faq-premade-templates-title-row">
                            <h4 class="faq-premade-templates-title">Pre-Made Design Templates</h4>
                            <span class="faq-premade-templates-badge">READY TO USE</span>
                            <p class="faq-premade-templates-desc">Professional, production-ready templates. Some have fixed layouts (only colors customizable), others allow full customization of layout and colors.</p>
                        </div>
                        <div class="faq-template-typography-row">
                            <label for="customization_premade_font_mode" class="faq-template-typography-label">Text Style</label>
                            <select name="premade_font_mode" id="customization_premade_font_mode" class="faq-plugin-select faq-template-typography-select" onchange="saveCustomizationSetting('premade_font_mode', this.value, true)">
                                <option value="template_default" <?php echo $premade_font_mode === 'template_default' ? 'selected' : ''; ?>>Template Default</option>
                                <option value="website_font" <?php echo $premade_font_mode === 'website_font' ? 'selected' : ''; ?>>Match Website Font</option>
                                <option value="custom_font" <?php echo $premade_font_mode === 'custom_font' ? 'selected' : ''; ?>>Use Custom Font</option>
                            </select>
                            <label for="customization_template_lock_mode" class="faq-template-typography-label" style="margin-left: 10px;">Template Rules</label>
                            <select name="template_lock_mode" id="customization_template_lock_mode" class="faq-plugin-select faq-template-typography-select" onchange="saveCustomizationSetting('template_lock_mode', this.value, true)">
                                <option value="strict" <?php echo $template_lock_mode === 'strict' ? 'selected' : ''; ?>>Locked (Background + Text Style only)</option>
                                <option value="flexible" <?php echo $template_lock_mode === 'flexible' ? 'selected' : ''; ?>>Open (Allow current overrides)</option>
                            </select>
                            <button type="button" class="faq-plugin-btn faq-plugin-btn-secondary faq-plugin-btn-sm" onclick="resetOnlyTemplateColors()"><i class="fa fa-tint"></i> Reset template colors</button>
                            <button type="button" class="faq-plugin-btn faq-plugin-btn-secondary faq-plugin-btn-sm" onclick="resetOnlyTypography()"><i class="fa fa-font"></i> Reset text style</button>
                        </div>
                        <p class="faq-premade-templates-color-tip">
                            <i class="fa fa-info-circle"></i> Click the <strong>colored circle</strong> on a card to change its background color; click the <strong>reset circle</strong> <i class="fa fa-undo"></i> to restore the default.
                        </p>
                    </div>

                    <div class="faq-templates-grid">
                        <?php foreach ($design_config as $design_key => $design_info): ?>
                            <div class="faq-template-card-wrap">
                                <div class="faq-plugin-card faq-design-card-compact faq-template-card <?php echo $active_design == $design_key ? 'active' : ''; ?>" data-design-preset="<?php echo htmlspecialchars($design_key); ?>">
                                    <div class="faq-design-preview" onclick="previewDesign('<?php echo htmlspecialchars($design_key); ?>')">
                                        <div class="faq-design-preview-image">
                                            <img src="<?php echo htmlspecialchars($design_info['preview_image_small']); ?>"
                                                alt="<?php echo htmlspecialchars($design_info['name']); ?> Preview"
                                                class="faq-preview-thumbnail"
                                                data-preview-fallback="<?php echo htmlspecialchars(isset($design_info['preview_image']) ? $design_info['preview_image'] : ''); ?>"
                                                onerror="var f=this.getAttribute('data-preview-fallback'); if(f){ this.onerror=null; this.src=f; } else { this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjMwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iNDAwIiBoZWlnaHQ9IjMwMCIgZmlsbD0iI2Y1ZjdmYSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTgiIGZpbGw9IiM2NDc0OGIiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5QcmV2aWV3IEltYWdlPC90ZXh0Pjwvc3ZnPg=='; }">
                                        </div>
                                        <?php if ($active_design == $design_key): ?>
                                            <div class="faq-design-active-badge">
                                                <i class="fa fa-check-circle"></i> Active
                                            </div>
                                        <?php endif; ?>
                                        <div class="faq-design-preview-overlay">
                                            <i class="fa fa-search-plus"></i> Preview
                                        </div>
                                    </div>
                                    <div class="faq-design-info">
                                        <div class="faq-template-card-header">
                                            <div class="faq-template-card-title-line" title="<?php echo htmlspecialchars($design_info['name'] . ' — ' . $design_info['description']); ?>">
                                                <span class="faq-template-card-title"><?php echo htmlspecialchars($design_info['name']); ?></span>
                                                <span class="faq-template-card-subtitle"><?php echo htmlspecialchars($design_info['description']); ?></span>
                                            </div>
                                            <?php if (in_array($design_key, ['minimal', 'split', 'colorful', 'modern', 'simple', 'card', 'classic'])):
                                                $template_bg_key = 'template_bg_' . $design_key;
                                                $current_bg = isset($design_settings[$template_bg_key]) ? $design_settings[$template_bg_key] : '';
                                                if (empty($current_bg)) {
                                                    $defaults = [
                                                        'minimal' => '#ffffff',
                                                        'split' => '#f5f5f0',
                                                        'colorful' => '#fef3f8',
                                                        'modern' => '#f8fafc',
                                                        'simple' => '#ffffff',
                                                        'card' => '#f1f5f9',
                                                        'classic' => '#fafafa'
                                                    ];
                                                    $current_bg = isset($defaults[$design_key]) ? $defaults[$design_key] : '#ffffff';
                                                }
                                            ?>
                                            <div class="faq-template-color-row">
                                                <input type="text" class="faq-template-bg-spectrum" id="template_bg_<?php echo $design_key; ?>_spectrum" value="<?php echo htmlspecialchars($current_bg); ?>" data-template-key="<?php echo htmlspecialchars($design_key); ?>" style="position:absolute;width:0;height:0;opacity:0;pointer-events:none;">
                                                <span class="faq-template-bg-swatch" data-template-key="<?php echo htmlspecialchars($design_key); ?>" title="Background color – click to choose" style="background-color:<?php echo htmlspecialchars($current_bg); ?>;"></span>
                                                <span class="faq-template-bg-reset" data-template-key="<?php echo htmlspecialchars($design_key); ?>" title="Reset to original color"><i class="fa fa-undo"></i></span>
                                            </div>
                                            <?php endif; ?>
                                        </div>

                                        <button class="faq-plugin-btn faq-plugin-btn-primary faq-plugin-btn-sm faq-template-activate-btn" onclick="selectDesign('<?php echo htmlspecialchars($design_key); ?>', event)">
                                            <i class="fa fa-check"></i> <?php echo $active_design == $design_key ? 'Active' : 'Activate'; ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="faq-design-settings-launcher" style="margin-top: 14px; border-top: 1px solid #e5e9f0; padding-top: 12px;">
                    <button type="button" class="faq-plugin-btn faq-plugin-btn-secondary faq-plugin-btn-sm" onclick="toggleAdvancedSettings()">
                        <i class="fa fa-cog"></i> Settings
                    </button>
                    <div id="advanced_settings_section" style="display: none; margin-top: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px;">
                        <div class="row" style="margin-bottom: 10px;">
                            <div class="col-md-6" style="margin-bottom: 8px;">
                                <label for="settings_public_domain" class="faq-plugin-label">Public domain (preview links)</label>
                                <div class="input-group">
                                    <input type="text" id="settings_public_domain" class="faq-plugin-input" placeholder="https://yourdomain.com" value="<?php echo htmlspecialchars(isset($design_settings['public_domain']) ? $design_settings['public_domain'] : ''); ?>">
                                    <span class="input-group-append">
                                        <button class="faq-plugin-btn faq-plugin-btn-primary" type="button" onclick="saveBottomSettingsPublicDomain()"><i class="fa fa-save"></i> Save</button>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6" style="margin-bottom: 8px;">
                                <label for="settings_cdn_base_url" class="faq-plugin-label">CDN base URL</label>
                                <div class="input-group">
                                    <input type="text" id="settings_cdn_base_url" class="faq-plugin-input" placeholder="<?php echo htmlspecialchars(FAQ_OWNER_CDN_URL); ?>" value="<?php echo htmlspecialchars($cdn_base_url); ?>">
                                    <span class="input-group-append">
                                        <button class="faq-plugin-btn faq-plugin-btn-primary" type="button" onclick="saveBottomSettingsCdn()"><i class="fa fa-save"></i> Save</button>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom: 8px;">
                            <button type="button" class="faq-plugin-btn faq-plugin-btn-secondary faq-plugin-btn-sm" onclick="resetDesignSettings()"><i class="fa fa-undo"></i> Reset all settings</button>
                            <button type="button" class="faq-plugin-btn faq-plugin-btn-secondary faq-plugin-btn-sm" onclick="runDesignConsistencyCheck()"><i class="fa fa-check-square-o"></i> Run design check</button>
                        </div>
                        <?php if (isset($faq_migration_status) && is_array($faq_migration_status)): ?>
                        <div class="alert alert-info faq-migration-status-box" style="margin: 8px 0;">
                            <i class="fa fa-database"></i> <strong>Auto migration:</strong> <?php echo !empty($faq_migration_status['updated']) ? 'applied' : 'checked'; ?>
                            <ul style="margin: 6px 0 0 18px; padding: 0;">
                                <?php if (!empty($faq_migration_status['messages'])): ?>
                                    <?php foreach ($faq_migration_status['messages'] as $migration_msg): ?>
                                        <li><?php echo htmlspecialchars($migration_msg); ?></li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li>No schema changes were needed.</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                        <div id="design_consistency_results" style="display:none; margin-bottom: 0;"></div>
                    </div>
                </div>
            </div>
            <!-- End Templates Section -->
        </div>


        <div id="tab-groups" class="tab-pane fade">
            <div class="faq-plugin-tab-content">
                <div class="faq-plugin-info-alert faq-tab-info-alert">
                    <i class="fa fa-info-circle"></i> <strong>Manage Groups</strong> – Create and manage FAQ groups to organize your questions.
                    Groups like "Pricing", "General", or "Support" help categorize FAQs. System groups (Global, Unassigned) are protected.
                    <strong>Filters:</strong> Search by name/slug, toggle system groups visibility.
                    <strong>Slug:</strong> Each group has a unique slug (auto-generated, read-only after creation).
                </div>

                <div id="table-header-module" class="faq-table-header-module faq-table-header-module-groups">
                    <h2 class="faq-table-header-title">Manage Groups</h2>
                    <div class="faq-table-header-filters">
                        <input type="text" id="group-search" placeholder="Search groups by name or slug..." onkeyup="debouncedFilterGroups()" onblur="hideSearchLoader('group-search')">
                        <label class="faq-table-toolbar-label">Show per page:</label>
                        <select id="groups-per-page" onchange="changePerPage('groups', this.value)">
                            <option value="25" selected>25</option>
                            <option value="all">Show All</option>
                        </select>
                        <label class="faq-table-toolbar-check-label">
                            <input type="checkbox" class="form-check-input" id="show-system-groups" checked onchange="filterGroups()">
                            Show system groups
                        </label>
                    </div>
                    <button class="faq-plugin-btn faq-plugin-btn-primary faq-table-header-btn" onclick="openGroupModal()">
                        <i class="fa fa-plus"></i> Add Group
                    </button>
                </div>

                <!-- Bulk Actions Bar for Groups -->
                <div id="bulk-actions-groups" style="display: none; margin-bottom: 16px; padding: 12px 16px; background: #fff3e0; border-radius: 8px; border: 1px solid #ffcc80;">
                    <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                        <span style="color: #e65100; font-weight: 500;"><i class="fa fa-check-square"></i> <span id="selected-groups-count">0</span> selected</span>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <button class="faq-plugin-btn faq-plugin-btn-danger" onclick="executeBulkDeleteGroups()" style="white-space: nowrap;">
                                <i class="fa fa-trash"></i> Delete Selected
                            </button>
                            <button class="faq-plugin-btn faq-plugin-btn-secondary" onclick="clearGroupSelection()" style="white-space: nowrap;">
                                <i class="fa fa-times"></i> Clear
                            </button>
                        </div>
                    </div>
                </div>

                <div id="container-groups">
                    <?php echo render_groups_table($db, 1, 25, false); ?>
                </div>
            </div>
        </div>

        <div id="tab-questions" class="tab-pane fade">
            <div class="faq-plugin-tab-content">
                <div class="faq-plugin-info-alert faq-tab-info-alert">
                    <i class="fa fa-info-circle"></i> <strong>Manage Questions</strong> – Add, edit, and delete FAQ questions.
                    <strong>Select a group first</strong> (dropdown below) to see and manage questions in that group. Assign questions to multiple groups; if no group is selected, questions default to &ldquo;Unassigned&rdquo;.
                    <strong>Filters:</strong> Search by question/answer text, filter by group.
                    <a href="#" class="faq-link-to-priority" onclick="switchToPriorityTab(); return false;"><i class="fa fa-sort"></i> Set display order for a group &rarr; Set Priority</a>
                </div>

                <!-- Filters row: above the header, so filters are grouped and don't crowd the title -->
                <div class="faq-questions-filters-row">
                    <div class="faq-toolbar-group">
                        <label class="faq-filter-group-label" for="question-group-filter"><span class="faq-filter-group-label-text">Filter by group</span></label>
                        <select id="question-group-filter" class="faq-toolbar-select" onchange="filterQuestions(); hideSearchLoader('question-search');" title="Select a group first to see its questions">
                            <option value="0">-- Select group to filter --</option>
                            <?php foreach ($all_groups as $group): ?>
                                <option value="<?php echo $group['id']; ?>"><?php echo htmlspecialchars($group['group_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="faq-toolbar-group">
                        <label class="faq-toolbar-label-inline" for="question-search"><span class="faq-toolbar-label-text">Search</span></label>
                        <input type="text" id="question-search" class="faq-toolbar-search" placeholder="Questions or answers..." onkeyup="debouncedFilterQuestions()" onblur="hideSearchLoader('question-search')">
                    </div>
                    <div class="faq-toolbar-group">
                        <label class="faq-toolbar-label-inline" for="questions-per-page"><span class="faq-toolbar-label-text">Per page</span></label>
                        <select id="questions-per-page" class="faq-toolbar-select faq-toolbar-select-sm" onchange="changePerPage('questions', this.value)">
                            <option value="25" selected>25</option>
                            <option value="all">Show All</option>
                        </select>
                    </div>
                </div>

                <!-- Header: title + actions only (clean, no filters) -->
                <div id="table-header-module" class="faq-table-header-module faq-table-header-module-questions">
                    <h2 class="faq-table-header-title">Manage Questions</h2>
                    <div class="faq-table-header-actions">
                        <button class="faq-plugin-btn faq-plugin-btn-primary faq-table-header-btn" onclick="openQuestionModal()">
                            <i class="fa fa-plus"></i> Add Question
                        </button>
                        <button class="faq-plugin-btn faq-plugin-btn-secondary faq-table-header-btn" onclick="openIncludeFromGroupModal()" title="Add questions from another group into the selected group">
                            <i class="fa fa-copy"></i> Include from Group
                        </button>
                    </div>
                </div>

                <!-- Bulk Actions Bar -->
                <div id="bulk-actions-questions" style="display: none; margin-bottom: 16px; padding: 12px 16px; background: #e3f2fd; border-radius: 8px; border: 1px solid #90caf9;">
                    <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                        <span style="color: #1565c0; font-weight: 500;"><i class="fa fa-check-square"></i> <span id="selected-questions-count">0</span> selected</span>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <select class="faq-plugin-select" id="bulk-action-type" style="width: auto; min-width: 180px;">
                                <option value="">-- Select Action --</option>
                                <option value="delete">Delete Selected</option>
                                <option value="assign">Assign to Group</option>
                            </select>
                            <select class="faq-plugin-select" id="bulk-assign-group" style="width: auto; min-width: 150px; display: none;">
                                <?php foreach ($all_groups as $group): ?>
                                    <?php if ($group['group_slug'] !== 'unassigned'): ?>
                                        <option value="<?php echo $group['id']; ?>"><?php echo htmlspecialchars($group['group_name']); ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <button class="faq-plugin-btn faq-plugin-btn-primary" onclick="executeBulkAction()" style="white-space: nowrap;">
                                <i class="fa fa-check"></i> Apply
                            </button>
                            <button class="faq-plugin-btn faq-plugin-btn-secondary" onclick="clearQuestionSelection()" style="white-space: nowrap;">
                                <i class="fa fa-times"></i> Clear
                            </button>
                        </div>
                    </div>
                </div>

                <div id="container-questions">
                    <?php echo render_questions_table($db, 1, 25, false); ?>
                </div>
                <p class="faq-questions-priority-link">
                    <a href="#" class="faq-link-to-priority" onclick="switchToPriorityTab(); return false;"><i class="fa fa-sort"></i> Set display order for questions in a group &rarr; <strong>Set Priority</strong></a>
                </p>
            </div>
        </div>

        <div id="tab-assignments" class="tab-pane fade">
            <div class="faq-plugin-tab-content">
                <div class="faq-plugin-info-alert faq-tab-info-alert">
                    <i class="fa fa-info-circle"></i> <strong>Page Assignment</strong> – Assign FAQ groups to specific website pages.
                    Multiple groups per page; use "Order" for drag-and-drop display order. Custom titles/subtitles override defaults. No assignment shows "Global" group.
                </div>

                <div id="table-header-module" class="faq-table-header-module faq-table-header-module-assignments">
                    <h2 class="faq-table-header-title">Page Assignment</h2>
                    <div class="faq-table-header-filters">
                        <input type="text" class="faq-table-toolbar-input" id="assignment-page-filter" placeholder="Filter by page filename..." onkeyup="filterAssignments()">
                        <select id="assignment-group-filter" onchange="filterAssignments()">
                            <option value="0">All Groups</option>
                            <?php foreach ($all_groups as $group): ?>
                                <option value="<?php echo $group['id']; ?>"><?php echo htmlspecialchars($group['group_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label class="faq-table-toolbar-label">Show per page:</label>
                        <select id="assignments-per-page" onchange="changePerPage('assignments', this.value)">
                            <option value="25" selected>25</option>
                            <option value="all">Show All</option>
                        </select>
                    </div>
                    <button class="faq-plugin-btn faq-plugin-btn-primary faq-table-header-btn" onclick="openAssignmentModal()">
                        <i class="fa fa-plus"></i> New Assignment
                    </button>
                </div>

                <div class="row mb-3" id="assignments-pagination-controls" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                    
                </div>

                <div id="container-assignments">
                    <?php echo render_assignments_table($db, 1, 25, false); ?>
                </div>
            </div>
        </div>

        <div id="tab-priority" class="tab-pane fade" data-faq-tab="priority">
            <div class="faq-plugin-tab-content">
                <div class="faq-plugin-info-alert faq-tab-info-alert">
                    <i class="fa fa-info-circle"></i> <strong>Priority</strong> – Set group-specific priority/order for questions.
                    Each question can have different ranks per group. Select a group → drag and drop to reorder → priorities auto-save. Order here sets frontend order for that group.
                </div>

                <div class="faq-table-header-module faq-table-header-module-priority">
                    <h2 class="faq-table-header-title">Set Priority</h2>
                    <div class="faq-table-header-filters">
                        <select id="priority-group-select" onchange="loadPriorityQuestions()">
                            <option value="">-- Select Group --</option>
                            <?php foreach ($all_groups as $group): ?>
                                <?php if (strtolower(trim($group['group_name'] ?? '')) === 'faq') continue; ?>
                                <option value="<?php echo $group['id']; ?>"><?php echo htmlspecialchars($group['group_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div id="priority-questions-list"></div>
            </div>
        </div>

        <div id="tab-help" class="tab-pane fade" data-faq-tab="help">
            <div class="faq-plugin-tab-content">
                <div class="faq-plugin-info-alert faq-tab-info-alert">
                    <i class="fa fa-info-circle"></i> <strong>Help</strong> - Click any section below to view detailed notes.
                </div>
                <div style="padding: 14px;">
                    <div class="faq-help-topic-tabs" style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom: 12px;">
                        <button type="button" class="faq-plugin-btn faq-plugin-btn-secondary faq-plugin-btn-sm faq-help-topic-btn active" data-topic="help_design" onclick="showHelpTopic('help_design')">Design</button>
                        <button type="button" class="faq-plugin-btn faq-plugin-btn-secondary faq-plugin-btn-sm faq-help-topic-btn" data-topic="help_custom" onclick="showHelpTopic('help_custom')">Custom Settings</button>
                        <button type="button" class="faq-plugin-btn faq-plugin-btn-secondary faq-plugin-btn-sm faq-help-topic-btn" data-topic="help_questions" onclick="showHelpTopic('help_questions')">Questions</button>
                        <button type="button" class="faq-plugin-btn faq-plugin-btn-secondary faq-plugin-btn-sm faq-help-topic-btn" data-topic="help_groups" onclick="showHelpTopic('help_groups')">Groups</button>
                        <button type="button" class="faq-plugin-btn faq-plugin-btn-secondary faq-plugin-btn-sm faq-help-topic-btn" data-topic="help_assignment" onclick="showHelpTopic('help_assignment')">Page Assignment</button>
                        <button type="button" class="faq-plugin-btn faq-plugin-btn-secondary faq-plugin-btn-sm faq-help-topic-btn" data-topic="help_bulk" onclick="showHelpTopic('help_bulk')">Bulk Operations</button>
                        <button type="button" class="faq-plugin-btn faq-plugin-btn-secondary faq-plugin-btn-sm faq-help-topic-btn" data-topic="help_priority" onclick="showHelpTopic('help_priority')">Set Priority</button>
                        <button type="button" class="faq-plugin-btn faq-plugin-btn-secondary faq-plugin-btn-sm faq-help-topic-btn" data-topic="help_features" onclick="showHelpTopic('help_features')">New Features</button>
                    </div>

                    <div id="help_design" class="faq-help-topic-panel" style="background:#fff; border:1px solid #e5e9f0; border-radius:10px; padding:14px;">
                        <h4 style="margin:0 0 10px 0; font-size:16px; color:#1f2937;"><i class="fa fa-paint-brush"></i> Design Tab Guide</h4>
                        <ul style="margin:0; padding-left:18px; color:#475569; font-size:13px; line-height:1.7;">
                            <li><strong>Custom Layout:</strong> Best when you want full control over every visual element.</li>
                            <li><strong>Templates:</strong> Best when you want a polished design quickly with minimal effort.</li>
                            <li><strong>Text Style:</strong> 
                                Template Default uses template font. Match Website Font uses your site typography. Use Custom Font uses your selected font from custom settings.
                            </li>
                            <li><strong>Template Rules:</strong> 
                                Locked mode protects template structure; Open mode lets you keep flexible overrides.
                            </li>
                        </ul>
                    </div>

                    <div id="help_custom" class="faq-help-topic-panel" style="display:none; background:#fff; border:1px solid #e5e9f0; border-radius:10px; padding:14px;">
                        <h4 style="margin:0 0 10px 0; font-size:16px; color:#1f2937;"><i class="fa fa-sliders"></i> Custom Settings Details</h4>
                        <ul style="margin:0; padding-left:18px; color:#475569; font-size:13px; line-height:1.7;">
                            <li><strong>Layout & Width:</strong> Controls structure (accordion, grid, video, tabs) and FAQ container width.</li>
                            <li><strong>Title Settings:</strong> Controls title background + opacity, title text size, alignment, and color.</li>
                            <li><strong>All Questions:</strong> Controls normal card background, question text size, question text color, and block style.</li>
                            <li><strong>Active Question:</strong> Controls active question background, text size, and text color while open.</li>
                            <li><strong>Active Answer:</strong> Controls open answer background, answer text size, and answer text color.</li>
                            <li><strong>Grid/Card Options:</strong> Use for card-style layouts only (columns, radius, padding, icon).</li>
                        </ul>
                    </div>

                    <div id="help_questions" class="faq-help-topic-panel" style="display:none; background:#fff; border:1px solid #e5e9f0; border-radius:10px; padding:14px;">
                        <h4 style="margin:0 0 10px 0; font-size:16px; color:#1f2937;"><i class="fa fa-question-circle"></i> Manage Questions</h4>
                        <ul style="margin:0; padding-left:18px; color:#475569; font-size:13px; line-height:1.7;">
                            <li><strong>Add/Edit Question:</strong> Question + Answer are required. Video URL is optional and used by video layouts.</li>
                            <li><strong>Assign to Groups:</strong> A question can belong to multiple groups. If no group is selected, it goes to Unassigned.</li>
                            <li><strong>Search/Filter:</strong> Use search and group filter to quickly find records before bulk actions.</li>
                            <li><strong>Row actions:</strong> Edit updates question content and group links. Delete removes the question.</li>
                        </ul>
                    </div>

                    <div id="help_groups" class="faq-help-topic-panel" style="display:none; background:#fff; border:1px solid #e5e9f0; border-radius:10px; padding:14px;">
                        <h4 style="margin:0 0 10px 0; font-size:16px; color:#1f2937;"><i class="fa fa-stack-overflow"></i> Manage Groups</h4>
                        <ul style="margin:0; padding-left:18px; color:#475569; font-size:13px; line-height:1.7;">
                            <li><strong>Create Groups:</strong> Use clear group names (for example: Pricing, Support, Shipping).</li>
                            <li><strong>System Groups:</strong> Global and Unassigned are protected and cannot be deleted.</li>
                            <li><strong>Details column:</strong> Shows question count and where a group is assigned.</li>
                            <li><strong>Search:</strong> Filter by group name/slug for fast maintenance.</li>
                        </ul>
                    </div>

                    <div id="help_assignment" class="faq-help-topic-panel" style="display:none; background:#fff; border:1px solid #e5e9f0; border-radius:10px; padding:14px;">
                        <h4 style="margin:0 0 10px 0; font-size:16px; color:#1f2937;"><i class="fa fa-link"></i> Page Assignment & Page Design Modal</h4>
                        <ul style="margin:0; padding-left:18px; color:#475569; font-size:13px; line-height:1.7;">
                            <li><strong>Override design for this page:</strong> Enables per-page style control without touching global design.</li>
                            <li><strong>Copy design from / Paste overrides:</strong> Fast way to reuse design between pages.</li>
                            <li><strong>Preview Source badge:</strong> Shows current preview source so you avoid editing the wrong scope.</li>
                            <li><strong>Clear overrides:</strong> Safely return that page to global design behavior.</li>
                            <li><strong>Merge Groups Together:</strong> Combines selected groups into one FAQ block on frontend.</li>
                        </ul>
                    </div>

                    <div id="help_bulk" class="faq-help-topic-panel" style="display:none; background:#fff; border:1px solid #e5e9f0; border-radius:10px; padding:14px;">
                        <h4 style="margin:0 0 10px 0; font-size:16px; color:#1f2937;"><i class="fa fa-tasks"></i> Bulk Operations (Important)</h4>
                        <ul style="margin:0; padding-left:18px; color:#475569; font-size:13px; line-height:1.7;">
                            <li><strong>Bulk delete questions:</strong> In Manage Questions, select multiple rows using checkboxes, then run delete once.</li>
                            <li><strong>Bulk assign questions:</strong> Select questions, choose target group, then assign all selected at once.</li>
                            <li><strong>Bulk delete groups:</strong> Select non-system groups only. System groups are skipped automatically.</li>
                            <li><strong>Safe workflow:</strong> Filter first -> select exact rows -> apply bulk action -> verify table refresh message.</li>
                            <li><strong>Pagination note:</strong> Bulk selection applies to currently selected rows; check page/filter before executing.</li>
                        </ul>
                    </div>

                    <div id="help_priority" class="faq-help-topic-panel" style="display:none; background:#fff; border:1px solid #e5e9f0; border-radius:10px; padding:14px;">
                        <h4 style="margin:0 0 10px 0; font-size:16px; color:#1f2937;"><i class="fa fa-sort"></i> Set Priority</h4>
                        <ul style="margin:0; padding-left:18px; color:#475569; font-size:13px; line-height:1.7;">
                            <li><strong>Group-specific order:</strong> Priority is stored per group, not globally.</li>
                            <li><strong>Drag and drop:</strong> Move items to reorder. Save updates the frontend display order for that group.</li>
                            <li><strong>Tip:</strong> Recheck priority after adding/importing many new questions.</li>
                        </ul>
                    </div>

                    <div id="help_features" class="faq-help-topic-panel" style="display:none; background:#fff; border:1px solid #e5e9f0; border-radius:10px; padding:14px;">
                        <h4 style="margin:0 0 10px 0; font-size:16px; color:#1f2937;"><i class="fa fa-star"></i> New Features Explained</h4>
                        <ul style="margin:0; padding-left:18px; color:#475569; font-size:13px; line-height:1.7;">
                            <li><strong>Template Rules (Locked/Open):</strong> 
                                Use Locked for consistency when clients should not break the template layout. Use Open when advanced overrides are required.
                            </li>
                            <li><strong>Reset template colors / Reset text style:</strong> 
                                Quick resets that avoid full reset and keep other settings untouched.
                            </li>
                            <li><strong>Settings panel (bottom):</strong> 
                                Includes CDN, public domain, full reset, migration status, and design consistency check in one place.
                            </li>
                            <li><strong>Design check:</strong> 
                                Useful for admin QA. Optional for clients; keep hidden in Settings to reduce clutter.
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
</div>

<div class="modal fade faq-plugin-modal bd-faq-modal" id="bd-faq-question-modal" tabindex="-1" role="dialog" aria-labelledby="bd-faq-question-modal-label" data-backdrop="static" data-keyboard="false">
    <div class="faq-plugin-modal-dialog bd-faq-modal-dialog" role="document">
        <div class="faq-plugin-modal-content bd-faq-modal-content">
            <div class="faq-plugin-modal-header bd-faq-modal-header">
                <h5 class="faq-plugin-modal-title bd-faq-modal-title" id="bd-faq-question-modal-label">Add/Edit Question</h5>
                <button type="button" class="faq-plugin-modal-close bd-faq-modal-close" onclick="closeQuestionModal()" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="faq-plugin-modal-body">
                <form id="bd-faq-question-form" class="bd-faq-question-form" onsubmit="saveQuestion(event)">
                    <input type="hidden" id="bd-faq-question-id" name="question_id">

                    <div class="form-group" style="margin-bottom: 16px;">
                        <label class="faq-plugin-label">Question</label>
                        <textarea class="faq-plugin-input" id="bd-faq-question-text" name="question" rows="3" required style="resize: vertical;"></textarea>
                    </div>

                    <div class="form-group" style="margin-bottom: 16px;">
                        <label class="faq-plugin-label">Answer</label>
                        <div class="bd-faq-editor-wrapper">
                            <div class="bd-faq-editor-toolbar" id="bd-faq-editor-toolbar">
                                <button type="button" class="bd-faq-editor-btn" data-command="bold" title="Bold">
                                    <i class="fa fa-bold"></i>
                                </button>
                                <button type="button" class="bd-faq-editor-btn" data-command="italic" title="Italic">
                                    <i class="fa fa-italic"></i>
                                </button>
                                <button type="button" class="bd-faq-editor-btn" data-command="insertUnorderedList" title="Bullet List">
                                    <i class="fa fa-list-ul"></i>
                                </button>
                                <button type="button" class="bd-faq-editor-btn" data-command="insertOrderedList" title="Numbered List">
                                    <i class="fa fa-list-ol"></i>
                                </button>
                                <button type="button" class="bd-faq-editor-btn" data-command="justifyLeft" title="Align Left">
                                    <i class="fa fa-align-left"></i>
                                </button>
                                <button type="button" class="bd-faq-editor-btn" data-command="justifyCenter" title="Align Center">
                                    <i class="fa fa-align-center"></i>
                                </button>
                                <button type="button" class="bd-faq-editor-btn" data-command="justifyRight" title="Align Right">
                                    <i class="fa fa-align-right"></i>
                                </button>
                                <button type="button" class="bd-faq-editor-btn" data-command="justifyFull" title="Justify">
                                    <i class="fa fa-align-justify"></i>
                                </button>
                                <span style="border-left: 1px solid #ddd; margin: 0 4px;"></span>
                                <div style="display: inline-flex; align-items: center; gap: 4px; position: relative;">
                                    <input type="color" id="bd-faq-text-color-picker" value="#e67e22" style="width: 0; height: 0; padding: 0; border: none; position: absolute; opacity: 0;">
                                    <button type="button" class="bd-faq-editor-btn" id="bd-faq-text-color-btn" title="Text Color - Select text and click to apply color">
                                        <i class="fa fa-font" style="color: #e67e22;"></i>
                                        <span style="width: 14px; height: 3px; background: #e67e22; display: block; margin-top: 2px;" id="bd-faq-color-indicator"></span>
                                    </button>
                                </div>
                                <span style="border-left: 1px solid #ddd; margin: 0 4px;"></span>
                                <button type="button" class="bd-faq-editor-btn" id="bd-faq-clean-format-btn" title="Clean Format (Remove all styling and use paragraph font)" style="background: #fff3e0;">
                                    <i class="fa fa-eraser"></i> Clean Format
                                </button>
                            </div>
                            <div class="bd-faq-editor-content" id="bd-faq-answer-text" contenteditable="true" data-placeholder="Enter your answer here..."></div>
                        </div>
                        <textarea id="bd-faq-answer-text-hidden" name="answer" style="display: none;"></textarea>
                        <small class="text-muted" style="display: block; margin-top: 4px; font-size: 11px;">
                            <i class="fa fa-info-circle"></i> Use the formatting toolbar to add bullet points, bold text, italic, etc.
                        </small>
                    </div>

                    <div class="form-group bd-faq-video-url-group" id="bd-faq-video-url-group" style="margin-bottom: 16px; display: none;">
                        <label class="faq-plugin-label">Video URL <small class="text-muted">(Optional - for video-based FAQ layouts)</small></label>
                        <input type="url" class="faq-plugin-input" id="bd-faq-video-url" name="video_url" placeholder="https://www.youtube.com/watch?v=... or https://vimeo.com/...">
                        <small class="text-muted" style="display: block; margin-top: 4px; font-size: 11px;">Supports YouTube and Vimeo URLs. Leave empty if not using video-based layout.</small>
                    </div>

                    <div class="form-group" style="margin-bottom: 16px;">
                        <label class="faq-plugin-label">Assign to Groups</label>
                        <div style="max-height: 180px; overflow-y: auto; border: 2px solid #e5e9f0; padding: 12px; border-radius: 8px; background: #f8fafc;">
                            <?php
                            $assignable_groups = array_filter($all_groups, function($g) {
                                return empty($g['group_slug']) || $g['group_slug'] !== 'unassigned';
                            });
                            foreach ($assignable_groups as $group): ?>
                                <div class="form-check group-checkbox-item" style="padding: 6px 10px; border-radius: 6px; transition: all 0.2s ease; border: 1px solid transparent; display: inline-block; margin-right: 12px; margin-bottom: 6px; min-width: 180px; max-width: 250px;">
                                    <input class="form-check-input" type="checkbox" name="group_ids[]" value="<?php echo $group['id']; ?>" id="group_<?php echo $group['id']; ?>" style="cursor: pointer; width: 16px; height: 16px; margin-right: 8px; margin-top: 2px; float: left;">
                                    <label class="form-check-label" for="group_<?php echo $group['id']; ?>" style="cursor: pointer; font-weight: 500; font-size: 13px; display: block; margin-left: 24px;">
                                        <?php echo htmlspecialchars($group['group_name']); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <small class="text-muted" style="display: block; margin-top: 6px; font-size: 11px;">If no group selected, question will be assigned to "Unassigned"</small>
                    </div>
                </form>
            </div>
            <div class="faq-plugin-modal-footer">
                <button type="button" class="faq-plugin-btn faq-plugin-btn-secondary" onclick="closeQuestionModal()">Cancel</button>
                <button type="submit" form="bd-faq-question-form" class="faq-plugin-btn faq-plugin-btn-primary">Save Question</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade faq-plugin-modal bd-faq-modal" id="bd-faq-group-modal" tabindex="-1" role="dialog" aria-labelledby="bd-faq-group-modal-label" data-backdrop="static" data-keyboard="false">
    <div class="faq-plugin-modal-dialog bd-faq-modal-dialog" role="document">
        <div class="faq-plugin-modal-content bd-faq-modal-content">
            <div class="faq-plugin-modal-header bd-faq-modal-header">
                <h5 class="faq-plugin-modal-title bd-faq-modal-title" id="bd-faq-group-modal-label">Add/Edit Group</h5>
                <button type="button" class="faq-plugin-modal-close bd-faq-modal-close" onclick="closeGroupModal()" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="faq-plugin-modal-body">
                <form id="bd-faq-group-form" class="bd-faq-group-form" onsubmit="saveGroup(event)">
                    <input type="hidden" id="bd-faq-group-id" name="group_id">

                    <div class="form-group" style="margin-bottom: 16px;">
                        <label class="faq-plugin-label">Group Name</label>
                        <input type="text" class="faq-plugin-input" id="bd-faq-group-name" name="group_name" required oninput="generateGroupSlug()">
                        <small class="text-muted" style="display: block; margin-top: 4px; font-size: 11px;">Enter a descriptive name for this FAQ group (e.g., "Pricing", "General Support")</small>
                    </div>

                    <div class="form-group faq-group-slug-field-hidden" style="margin-bottom: 0; display: none;">
                        <label class="faq-plugin-label">Shortcode/Slug <small class="text-muted">(System-generated)</small></label>
                        <div style="display: flex; gap: 8px;">
                            <input type="text" class="faq-plugin-input" id="bd-faq-group-slug" name="group_slug" readonly style="flex: 1; background: #f8fafc;">
                            <button class="faq-plugin-btn faq-plugin-btn-secondary" type="button" onclick="copySlug($('#bd-faq-group-slug').val())" title="Copy shortcode" style="padding: 10px 16px;">
                                <i class="fa fa-copy"></i>
                            </button>
                        </div>
                        <small class="text-muted" style="display: block; margin-top: 4px; font-size: 11px;">System-generated shortcode. Auto-generated on create, immutable on edit.</small>
                    </div>
                </form>
            </div>
            <div class="faq-plugin-modal-footer">
                <button type="button" class="faq-plugin-btn faq-plugin-btn-secondary" onclick="closeGroupModal()">Cancel</button>
                <button type="submit" form="bd-faq-group-form" class="faq-plugin-btn faq-plugin-btn-primary">Save Group</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade faq-plugin-modal bd-faq-modal" id="bd-faq-include-from-group-modal" tabindex="-1" role="dialog" aria-labelledby="bd-faq-include-from-group-label" data-backdrop="static" data-keyboard="false">
    <div class="faq-plugin-modal-dialog bd-faq-modal-dialog" role="document">
        <div class="faq-plugin-modal-content bd-faq-modal-content">
            <div class="faq-plugin-modal-header bd-faq-modal-header">
                <h5 class="faq-plugin-modal-title bd-faq-modal-title" id="bd-faq-include-from-group-label">Include Questions from Another Group</h5>
                <button type="button" class="faq-plugin-modal-close bd-faq-modal-close" onclick="closeIncludeFromGroupModal()" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="faq-plugin-modal-body">
                <p id="include-from-group-target-hint" class="text-muted" style="font-size: 13px; margin-bottom: 12px;"></p>
                <div class="form-group">
                    <label class="faq-plugin-label">Source group (questions to copy from)</label>
                    <select class="faq-plugin-select" id="include-from-group-source" style="width: 100%;">
                        <option value="">-- Select group --</option>
                        <?php foreach ($all_groups as $group): ?>
                            <?php if (strtolower(trim($group['group_slug'] ?? '')) !== 'unassigned'): ?>
                                <option value="<?php echo $group['id']; ?>"><?php echo htmlspecialchars($group['group_name']); ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="button" class="faq-plugin-btn faq-plugin-btn-secondary" onclick="loadIncludeFromGroupQuestions()" style="margin-bottom: 12px;">
                    <i class="fa fa-refresh"></i> Load questions
                </button>
                <div id="include-from-group-list" style="max-height: 280px; overflow-y: auto; border: 1px solid #e5e9f0; border-radius: 8px; padding: 12px; background: #f8fafc;">
                    <p class="text-muted" style="margin: 0; font-size: 13px;">Select a source group and click Load questions.</p>
                </div>
            </div>
            <div class="faq-plugin-modal-footer">
                <button type="button" class="faq-plugin-btn faq-plugin-btn-secondary" onclick="closeIncludeFromGroupModal()">Cancel</button>
                <button type="button" class="faq-plugin-btn faq-plugin-btn-primary" id="include-from-group-apply-btn" onclick="applyIncludeFromGroup()" disabled>
                    <i class="fa fa-plus"></i> Add selected to group
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade faq-plugin-modal" id="assignmentModal" tabindex="-1" role="dialog" aria-labelledby="assignmentModalLabel" data-backdrop="static" data-keyboard="false">
    <div class="faq-plugin-modal-dialog" role="document">
        <div class="faq-plugin-modal-content">
            <div class="faq-plugin-modal-header">
                <h5 class="faq-plugin-modal-title" id="assignmentModalLabel">Add/Edit Page Assignment</h5>
                <button type="button" class="faq-plugin-modal-close" onclick="closeAssignmentModal()" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="faq-plugin-modal-body">
                <form id="assignmentForm" onsubmit="saveAssignment(event)">
                    <input type="hidden" id="assignment_id" name="assignment_id">
                    <input type="hidden" id="assignment_page_type" name="page_type" value="static">

                    <div id="merge-edit-info" style="display: none; background-color: #e7f3ff; border: 1px solid #2196F3; border-radius: 6px; padding: 12px 16px; margin-bottom: 20px;">
                        <div style="display: flex; align-items: flex-start; gap: 10px;">
                            <i class="fa fa-info-circle" style="color: #2196F3; font-size: 18px; margin-top: 2px;"></i>
                            <div style="flex: 1;">
                                <strong style="color: #1976D2; display: block; margin-bottom: 4px;">Editing Merged Groups</strong>
                                <p style="margin: 0; color: #555; font-size: 13px; line-height: 1.5;">
                                    You are editing a merged group assignment. All groups in this merged set share the same title and subtitle.
                                    Changes you make here will update <strong>all merged rows</strong> for this page.
                                    On the frontend, all selected groups will be combined into a single accordion.
                                </p>
                                <p style="margin: 6px 0 0 0; color: #555; font-size: 13px; line-height: 1.5;">
                                    <strong>To unmerge:</strong> Uncheck "Merge Groups Together" and save. All groups will be preserved as separate individual assignments.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="row" style="margin-bottom: 16px;">
                        <div class="col-md-4">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="faq-plugin-label">Page Type</label>
                                <select class="faq-plugin-select" id="assignment_page_type_selector" onchange="togglePageTypeSelector()" required>
                                    <option value="static">Static Page</option>
                                    <option value="post_type">Post Type Page</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group" id="static_page_group" style="margin-bottom: 0;">
                                <label class="faq-plugin-label">Select Static Page</label>
                                <select class="faq-plugin-select" id="assignment_page_id" name="page_id" onchange="filterAssignedGroups()">
                                    <option value="">-- Select Static Page --</option>
                                    <?php foreach ($all_pages as $page): ?>
                                        <option value="<?php echo $page['seo_id']; ?>"><?php echo htmlspecialchars($page['filename']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group" id="post_type_group" style="display: none; margin-bottom: 0;">
                                <label class="faq-plugin-label">Select Post Type</label>
                                <select class="faq-plugin-select" id="assignment_data_id" name="data_id" onchange="updatePostTypePageType(); filterAssignedGroups();">
                                    <option value="">-- Select Post Type --</option>
                                    <?php foreach ($all_post_types as $pt):
                                        $type_label = '';
                                        if ($pt['data_type'] == 4) {
                                            $type_label = 'Group';
                                        } elseif ($pt['data_type'] == 20) {
                                            $type_label = 'Post';
                                        } else {
                                            $type_label = 'Member';
                                        }
                                    ?>
                                        <option value="<?php echo $pt['data_id']; ?>" data-type="<?php echo $pt['data_type']; ?>">
                                            <?php echo htmlspecialchars($pt['data_name']); ?> (<?php echo $type_label; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group" id="post_type_page_type_group" style="display: none; margin-bottom: 0;">
                                <label class="faq-plugin-label">Page Context</label>
                                <select class="faq-plugin-select" id="assignment_post_page_type" name="post_page_type">
                                    <option value="search_result_page">Search Result Page</option>
                                    <option value="detail_page">Detail Page</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <small class="text-muted" style="display: block; margin-top: -8px; margin-bottom: 16px; font-size: 11px; padding-left: 12px;">
                        <i class="fa fa-info-circle"></i> Groups already assigned to this page will be hidden from the group selection below.
                    </small>

                    <div class="faq-plugin-divider"></div>

                    <div class="form-group" style="margin-bottom: 16px;">
                        <div style="background: #f8fafc; border: 2px solid #e5e9f0; border-radius: 8px; padding: 12px; margin-bottom: 8px;">
                            <div class="form-check faq-assignment-inline-check" style="margin-bottom: 0;">
                                <input class="form-check-input" type="checkbox" id="assignment_merge_groups" name="merge_groups" value="1" onchange="toggleMergeGroupsUI()" style="cursor: pointer; width: 16px; height: 16px; margin: 0;">
                                <label class="form-check-label" for="assignment_merge_groups" style="cursor: pointer; margin: 0; font-weight: 600; font-size: 13px;">
                                    Merge Groups Together
                                </label>
                            </div>
                        </div>
                        <small class="text-muted" style="display: block; font-size: 11px; margin-bottom: 0;">
                            <strong>Merge Groups:</strong> Select multiple groups to combine into a single accordion.
                            <span style="color: #666; font-size: 11px;">All merged groups share the same title/subtitle.</span>
                        </small>
                    </div>

                    <div class="form-group" id="single-group-selection" style="margin-bottom: 16px;">
                        <label class="faq-plugin-label">Select Group</label>
                        <select class="faq-plugin-select" id="assignment_group_id" name="group_id" required>
                            <option value="">-- Select Group --</option>
                            <?php foreach ($all_groups as $group): ?>
                                <option value="<?php echo $group['id']; ?>"><?php echo htmlspecialchars($group['group_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" id="multiple-group-selection" style="display: none; margin-bottom: 16px;">
                        <label class="faq-plugin-label">Select Groups (Multiple)</label>
                        <div style="max-height: 200px; overflow-y: auto; border: 2px solid #e5e9f0; padding: 12px; border-radius: 8px; background: #f8fafc;">
                            <?php foreach ($all_groups as $group): ?>
                                <div class="form-check group-checkbox-item" data-group-id="<?php echo $group['id']; ?>" style="padding: 6px 10px; border-radius: 6px; transition: all 0.2s ease; border: 1px solid transparent; display: inline-block; margin-right: 12px; margin-bottom: 6px; min-width: 180px; max-width: 250px;">
                                    <input class="form-check-input assignment-group-checkbox" type="checkbox" name="assignment_group_ids[]" value="<?php echo $group['id']; ?>" id="assignment_group_<?php echo $group['id']; ?>" style="cursor: pointer; width: 16px; height: 16px; margin-right: 8px; margin-top: 2px; float: left;">
                                    <label class="form-check-label" for="assignment_group_<?php echo $group['id']; ?>" style="cursor: pointer; font-weight: 500; font-size: 13px; display: block; margin-left: 24px;">
                                        <?php echo htmlspecialchars($group['group_name']); ?>
                                        <span class="group-assigned-badge" style="display: none; margin-left: 6px; padding: 1px 6px; font-size: 10px; border-radius: 3px; background: #d4edda; color: #155724; font-weight: 600;">Assigned</span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <small class="text-muted" style="display: block; margin-top: 6px; font-size: 11px;">
                            <i class="fa fa-info-circle"></i> <strong>Groups with "Assigned" badge</strong> are currently assigned to this page.
                        </small>
                    </div>

                    <div class="form-group" style="margin-bottom: 16px;">
                        <div style="background: #f8fafc; border: 2px solid #e5e9f0; border-radius: 8px; padding: 12px; margin-bottom: 8px;">
                            <div class="form-check faq-assignment-inline-check" style="margin-bottom: 0;">
                                <input class="form-check-input" type="checkbox" id="assignment_show_title" name="show_title" value="1" checked style="cursor: pointer; width: 16px; height: 16px; margin: 0;">
                                <label class="form-check-label" for="assignment_show_title" style="cursor: pointer; margin: 0; font-weight: 600; font-size: 13px;">
                                    Show Title
                                </label>
                            </div>
                        </div>
                        <small class="text-muted" style="display: block; font-size: 11px;">Enable or disable title display for this FAQ group</small>
                    </div>

                    <!-- Template Info Card -->
                    <div id="bd-faq-template-info-card" class="bd-faq-template-info-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; padding: 10px 14px; margin-bottom: 16px; color: white; display: none;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i class="fa fa-palette" style="font-size: 18px; opacity: 0.9;"></i>
                            <div>
                                <div style="font-weight: 600; font-size: 13px; margin-bottom: 2px;">Template: <span id="bd-faq-current-template-name">Loading...</span></div>
                                <div style="font-size: 11px; opacity: 0.9;" id="bd-faq-template-description">Loading template info...</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Label Text Field (Template-specific) -->
                        <div class="col-md-12">
                            <div class="form-group template-field" id="label_field_group" style="margin-bottom: 12px; display: none;">
                                <label class="faq-plugin-label" style="display: flex; align-items: center; gap: 8px;">
                                    <span>Label Text</span>
                                    <span style="background: #f59e0b; color: white; padding: 2px 6px; font-size: 9px; border-radius: 3px; font-weight: 600;">SPLIT</span>
                                </label>
                                <input type="text" class="faq-plugin-input" id="assignment_custom_label" name="custom_label" placeholder="e.g., Frequently Asked Questions">
                                <small class="text-muted" style="display: block; margin-top: 4px; font-size: 11px;">
                                    <i class="fa fa-info-circle"></i> Small text above main title
                                </small>
                            </div>
                        </div>
                        <!-- Main Title Field -->
                        <div class="col-md-6">
                            <div class="form-group template-field" id="title_field_group" style="margin-bottom: 12px;">
                                <label class="faq-plugin-label">Main Title <small class="text-muted">(Optional)</small></label>
                                <input type="text" class="faq-plugin-input" id="assignment_custom_title" name="custom_title" placeholder="e.g., Find answers to common questions">
                                <small class="text-muted" style="display: block; margin-top: 4px; font-size: 11px;" id="bd-faq-title-help-text">
                                    Leave empty for default
                                </small>
                            </div>
                        </div>
                        <!-- Subtitle Field -->
                        <div class="col-md-6">
                            <div class="form-group template-field bd-faq-subtitle-field-group" id="bd-faq-subtitle-field-group" style="margin-bottom: 12px;">
                                <label class="faq-plugin-label">Subtitle <small class="text-muted">(Optional)</small></label>
                                <textarea class="faq-plugin-input" id="assignment_custom_subtitle" name="custom_subtitle" rows="2" style="resize: vertical;" placeholder="e.g., Browse through our curated list"></textarea>
                                <small class="text-muted" style="display: block; margin-top: 4px; font-size: 11px;">
                                    <i class="fa fa-info-circle"></i> Description below title
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- CTA Fields (Split Template Only) -->
                    <div id="bd-faq-cta-fields-section" style="display: none; background: #fef3c7; border: 2px solid #f59e0b; border-radius: 8px; padding: 14px; margin-bottom: 16px;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                            <i class="fa fa-envelope" style="color: #f59e0b; font-size: 16px;"></i>
                            <h4 style="margin: 0; color: #92400e; font-size: 14px; font-weight: 600;">Call-to-Action Box</h4>
                            <span style="background: #f59e0b; color: white; padding: 2px 6px; font-size: 9px; border-radius: 3px; font-weight: 600;">SPLIT</span>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group" style="margin-bottom: 12px;">
                                    <label class="faq-plugin-label">CTA Title</label>
                                    <input type="text" class="faq-plugin-input" id="assignment_cta_title" name="cta_title" placeholder="e.g., Need Any Help?">
                                    <small class="text-muted" style="display: block; margin-top: 2px; font-size: 10px;">Bold heading</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group" style="margin-bottom: 12px;">
                                    <label class="faq-plugin-label">CTA Text</label>
                                    <textarea class="faq-plugin-input" id="assignment_cta_text" name="cta_text" rows="2" style="resize: vertical;" placeholder="e.g., Still have questions?"></textarea>
                                    <small class="text-muted" style="display: block; margin-top: 2px; font-size: 10px;">Description</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group" style="margin-bottom: 12px;">
                                    <label class="faq-plugin-label">Contact Email</label>
                                    <input type="email" class="faq-plugin-input" id="assignment_cta_email" name="cta_email" placeholder="e.g., info@yoursite.com">
                                    <small class="text-muted" style="display: block; margin-top: 2px; font-size: 10px;">Email address</small>
                                </div>
                            </div>
                        </div>

                        <div style="background: #fffbeb; border-left: 3px solid #f59e0b; padding: 8px 10px; margin-top: 8px; border-radius: 4px;">
                            <small style="color: #92400e; font-size: 11px;">
                                <i class="fa fa-lightbulb-o"></i> <strong>Tip:</strong> Leave empty for defaults. CTA appears in left column.
                            </small>
                        </div>
                    </div>
                </form>
            </div>
            <div class="faq-plugin-modal-footer">
                <button type="button" class="faq-plugin-btn faq-plugin-btn-secondary" onclick="closeAssignmentModal()">Cancel</button>
                <button type="submit" form="assignmentForm" class="faq-plugin-btn faq-plugin-btn-primary">Save Assignment</button>
            </div>
        </div>
    </div>
</div>

<!-- Page Design Override Modal - All design options (custom + premade) -->
<div class="modal fade faq-plugin-modal" id="pageDesignModal" tabindex="-1" role="dialog" aria-labelledby="pageDesignModalLabel" data-backdrop="static" data-keyboard="false">
    <div class="faq-plugin-modal-dialog faq-page-design-modal-dialog" role="document" style="max-width: 1400px;">
        <div class="faq-plugin-modal-content">
            <div class="faq-plugin-modal-header">
                <h5 class="faq-plugin-modal-title" id="pageDesignModalLabel"><i class="fa fa-paint-brush"></i> Design for this page</h5>
                <button type="button" class="faq-plugin-modal-close" onclick="closePageDesignModal()" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="faq-plugin-modal-body faq-page-design-modal-body" style="max-height: 80vh; overflow-y: auto;">
                <div class="faq-page-design-top-row">
                    <div class="form-check faq-page-design-override-check">
                        <input class="form-check-input" type="checkbox" id="page_design_override_check" onchange="togglePageDesignForm(this.checked)">
                        <label class="form-check-label" for="page_design_override_check"><strong>Override design for this page</strong></label>
                    </div>
                    <div class="faq-page-design-top-meta" id="page_design_top_meta">
                        <p class="faq-page-design-page-name text-muted" id="pageDesignModalPageName"></p>
                        <div class="faq-design-toggle-inline faq-page-design-switch">
                            <span class="faq-toggle-indicator"><i class="fa fa-exchange"></i> Switch:</span>
                            <span id="page_design_toggle_label_custom" class="faq-toggle-opt">Custom Layout</span>
                            <label class="design-mode-toggle design-mode-toggle-compact">
                                <input type="checkbox" id="page_design_mode_toggle" onchange="togglePageDesignMode(this.checked)">
                                <span class="design-mode-slider"></span>
                                <span class="design-mode-knob"></span>
                            </label>
                            <span id="page_design_toggle_label_template" class="faq-toggle-opt faq-toggle-opt-alt">Templates</span>
                        </div>
                    </div>
                </div>
                <div id="page_design_form_wrap" style="display: none;" class="faq-page-design-form-with-preview">
                <input type="hidden" id="page_design_design_preset" value="custom">
                <div class="faq-page-design-settings-col">
                <div id="page_design_settings_inner">
                    <div class="accordion faq-page-design-custom-accordion" role="tablist" id="pageDesignCustomAccordion">
                        <div class="setting_holder accordion-section page_design_custom_only">
                            <h2 class="accordion-header" role="tab" tabindex="0">
                                Layout &amp; Width
                                <a class="expand_module"><i class="fa fa-chevron-down"></i></a>
                            </h2>
                            <ul class="inline_list accordion-content" role="tabpanel">
                                <li>
                                    <div class="row">
                                        <div class="col-md-6"><label class="faq-plugin-label">How FAQ blocks appear</label>
                                            <select class="faq-plugin-select" id="page_design_layout_type" style="width: 100%;" onchange="togglePageDesignLayoutSections(this.value); refreshPageDesignPreviewDebounced();">
                                                <?php
                                                $page_design_layouts = array(
                                                    'accordion' => 'Accordion',
                                                    'search-first' => 'Search-First (Help Center)',
                                                    'tabbed' => 'Tabbed Navigation',
                                                    'single-column' => 'Single Column',
                                                    'grid-card' => 'Grid/Card Layout',
                                                    'sidebar' => 'Sidebar Navigation',
                                                    'persona-based' => 'Persona-Based',
                                                    'conversational' => 'Conversational (Chatbot)',
                                                    'video-multimedia' => 'Video/Multimedia',
                                                    'step-by-step' => 'Step-by-Step/Flowchart'
                                                );
                                                foreach ($page_design_layouts as $plk => $pln): ?>
                                                <option value="<?php echo htmlspecialchars($plk); ?>"><?php echo htmlspecialchars($pln); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6"><label class="faq-plugin-label">FAQ area max width</label><select class="faq-plugin-select" id="page_design_container_width" style="width: 100%;" onchange="togglePageDesignContainerWidthCustom(this.value); refreshPageDesignPreviewDebounced();"><option value="100%">Full Width (100%)</option><option value="900">Narrow (900px)</option><option value="1100">Medium (1100px)</option><option value="1400">Wide (1400px)</option><option value="custom">Custom</option></select></div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6" id="page_design_container_width_custom_wrapper" style="margin-bottom: 10px; display: none;"><label class="faq-plugin-label">Custom width</label><input type="number" class="faq-plugin-input" id="page_design_container_width_custom" min="300" max="2000" placeholder="900" style="width: 100%;"></div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div id="page_design_colors_section" class="setting_holder accordion-section page_design_custom_only">
                            <h2 class="accordion-header" role="tab" tabindex="0">
                                Title Settings
                                <a class="expand_module"><i class="fa fa-chevron-down"></i></a>
                            </h2>
                            <ul class="inline_list accordion-content" role="tabpanel">
                                <li class="faq-setting-where-used">Used for section heading area (title background, title text, and alignment).</li>
                                <li>
                                    <div class="row">
                                        <div class="col-md-6" style="margin-bottom: 10px;">
                                            <label class="faq-plugin-label">Background + Opacity</label>
                                            <input type="text" class="faq-spectrum-input faq-spectrum-alpha" id="page_design_background_color_text" data-setting="background_color" data-page-design="1" value="rgba(255,255,255,1)" placeholder="rgba(255,255,255,1)">
                                            <input type="hidden" id="page_design_background_alpha" value="100">
                                        </div>
                                        <div class="col-md-6" style="margin-bottom: 10px;">
                                            <label class="faq-plugin-label">Alignment</label>
                                            <select class="faq-plugin-select" id="page_design_title_alignment" style="width: 100%;" onchange="refreshPageDesignPreviewDebounced();">
                                                <option value="left">Left</option>
                                                <option value="center">Center</option>
                                                <option value="right">Right</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3" style="margin-bottom: 10px;">
                                            <label class="faq-plugin-label">Title text size (px)</label>
                                            <input type="number" class="faq-plugin-input" id="page_design_title_font_size" min="12" max="72" placeholder="32" style="width: 100%;">
                                        </div>
                                        <div class="col-md-3" style="margin-bottom: 10px;">
                                            <label class="faq-plugin-label">Text color</label>
                                            <input type="text" class="faq-spectrum-input" id="page_design_title_text_color_text" data-setting="title_text_color" data-page-design="1" value="#1f2937" placeholder="#1f2937" onchange="togglePageDesignDefaultTextColorVisibility();">
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="setting_holder accordion-section page_design_custom_only">
                            <h2 class="accordion-header" role="tab" tabindex="0">
                                All Questions
                                <a class="expand_module"><i class="fa fa-chevron-down"></i></a>
                            </h2>
                            <ul class="inline_list accordion-content" role="tabpanel">
                                <li class="faq-setting-where-used">Used for collapsed question cards and default card text styling.</li>
                                <li>
                                    <div class="row">
                                        <div class="col-md-3" style="margin-bottom: 10px;">
                                            <label class="faq-plugin-label">Background</label>
                                            <input type="text" class="faq-spectrum-input" id="page_design_card_background_color_text" data-setting="card_background_color" data-page-design="1" value="#ffffff" placeholder="#ffffff">
                                        </div>
                                        <div class="col-md-3" style="margin-bottom: 10px;">
                                            <label class="faq-plugin-label">Question text size (px)</label>
                                            <input type="number" class="faq-plugin-input" id="page_design_question_font_size" min="12" max="36" placeholder="18" style="width: 100%;">
                                        </div>
                                        <div class="col-md-3" style="margin-bottom: 10px;">
                                            <label class="faq-plugin-label">Text color</label>
                                            <input type="text" class="faq-spectrum-input" id="page_design_question_text_color_text" data-setting="question_text_color" data-page-design="1" value="#1f2937" placeholder="#1f2937" onchange="togglePageDesignDefaultTextColorVisibility();">
                                        </div>
                                        <div class="col-md-3" style="margin-bottom: 10px;">
                                            <label class="faq-plugin-label">Block style</label>
                                            <select class="faq-plugin-select" id="page_design_card_style" style="width: 100%;">
                                                <option value="minimal">Minimal</option>
                                                <option value="shadow">Shadow</option>
                                                <option value="elevated">Elevated</option>
                                                <option value="bordered">Bordered</option>
                                                <option value="simple">Simple</option>
                                                <option value="flat">Flat</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3" id="page_design_default_text_color_row" style="margin-bottom: 10px;">
                                            <label class="faq-plugin-label">Default text color</label>
                                            <input type="text" class="faq-spectrum-input" id="page_design_text_color_text" data-setting="text_color" data-page-design="1" value="#1f2937" placeholder="#1f2937" onchange="togglePageDesignDefaultTextColorVisibility();">
                                            <small class="text-muted" style="display: block; margin-top: 2px; font-size: 11px;">Fallback when title/question/answer colors are not set</small>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="setting_holder accordion-section page_design_custom_only">
                            <h2 class="accordion-header" role="tab" tabindex="0">
                                Active Question
                                <a class="expand_module"><i class="fa fa-chevron-down"></i></a>
                            </h2>
                            <ul class="inline_list accordion-content" role="tabpanel">
                                <li class="faq-setting-where-used">Used when a question is active/open.</li>
                                <li>
                                    <div class="row">
                                        <div class="col-md-4" style="margin-bottom: 10px;">
                                            <label class="faq-plugin-label">Background color</label>
                                            <input type="text" class="faq-spectrum-input" id="page_design_primary_color_text" data-setting="primary_color" data-page-design="1" value="#1e3a8a" placeholder="#1e3a8a">
                                        </div>
                                        <div class="col-md-4" style="margin-bottom: 10px;">
                                            <label class="faq-plugin-label">Question text size (px)</label>
                                            <input type="number" class="faq-plugin-input" id="page_design_question_font_size_active_alias" min="12" max="36" placeholder="18" style="width: 100%;" oninput="document.getElementById('page_design_question_font_size').value=this.value;refreshPageDesignPreviewDebounced();">
                                        </div>
                                        <div class="col-md-4" style="margin-bottom: 10px;">
                                            <label class="faq-plugin-label">Text color</label>
                                            <input type="text" class="faq-spectrum-input" id="page_design_question_text_color_text_active_alias" value="#1f2937" placeholder="#1f2937" onchange="document.getElementById('page_design_question_text_color_text').value=this.value;refreshPageDesignPreviewDebounced();">
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="setting_holder accordion-section page_design_custom_only">
                            <h2 class="accordion-header" role="tab" tabindex="0">
                                Active Answer
                                <a class="expand_module"><i class="fa fa-chevron-down"></i></a>
                            </h2>
                            <ul class="inline_list accordion-content" role="tabpanel">
                                <li class="faq-setting-where-used">Used for the visible answer area under the active question.</li>
                                <li>
                                    <div class="row">
                                        <div class="col-md-3" style="margin-bottom: 10px;"><label class="faq-plugin-label">Background</label><input type="text" class="faq-spectrum-input" id="page_design_card_background_color_text_active_answer_alias" value="#ffffff" placeholder="#ffffff" onchange="document.getElementById('page_design_card_background_color_text').value=this.value;refreshPageDesignPreviewDebounced();"></div>
                                        <div class="col-md-3" style="margin-bottom: 10px;"><label class="faq-plugin-label">Answer text size (px)</label><input type="number" class="faq-plugin-input" id="page_design_answer_font_size" min="12" max="24" placeholder="16" style="width: 100%;"></div>
                                        <div class="col-md-3" style="margin-bottom: 10px;">
                                            <label class="faq-plugin-label">Text color</label>
                                            <input type="text" class="faq-spectrum-input" id="page_design_answer_text_color_text" data-setting="answer_text_color" data-page-design="1" value="#1f2937" placeholder="#1f2937" onchange="togglePageDesignDefaultTextColorVisibility();">
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="setting_holder accordion-section page_design_custom_only">
                            <h2 class="accordion-header" role="tab" tabindex="0">
                                Font (all text)
                                <a class="expand_module"><i class="fa fa-chevron-down"></i></a>
                            </h2>
                            <ul class="inline_list accordion-content" role="tabpanel">
                                <li>
                                    <div class="row">
                                        <div class="col-md-6" style="margin-bottom: 10px;">
                                            <label class="faq-plugin-label">FAQ text font</label>
                                            <select class="faq-plugin-select" id="page_design_font_family" style="width: 100%;" onchange="refreshPageDesignPreviewDebounced();">
                                                <option value="system">System Default</option>
                                                <option value="arial">Arial</option>
                                                <option value="helvetica">Helvetica</option>
                                                <option value="georgia">Georgia</option>
                                                <option value="times">Times New Roman</option>
                                                <option value="courier">Courier New</option>
                                                <option value="verdana">Verdana</option>
                                                <option value="roboto">Roboto</option>
                                                <option value="open-sans">Open Sans</option>
                                                <option value="lato">Lato</option>
                                                <option value="montserrat">Montserrat</option>
                                                <option value="poppins">Poppins</option>
                                                <option value="inter">Inter</option>
                                            </select>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div id="page_design_card_options" class="setting_holder accordion-section page_design_custom_only" style="display: block;">
                            <h2 class="accordion-header" role="tab" tabindex="0">
                                Card & Layout Options
                                <a class="expand_module"><i class="fa fa-chevron-down"></i></a>
                            </h2>
                            <ul class="inline_list accordion-content" role="tabpanel">
                                <li>
                                    <div class="row">
                                        <div class="col-md-3" id="page_design_grid_columns_wrap" style="margin-bottom: 10px;"><label class="faq-plugin-label">Grid columns</label><select class="faq-plugin-select" id="page_design_grid_columns" style="width: 100%;"><option value="2">2</option><option value="3">3</option><option value="4">4</option></select></div>
                                        <div class="col-md-3" style="margin-bottom: 10px;"><label class="faq-plugin-label">Card corner radius (px)</label><input type="number" class="faq-plugin-input" id="page_design_card_radius" min="0" max="50" placeholder="12" style="width: 100%;"></div>
                                        <div class="col-md-3" style="margin-bottom: 10px;"><label class="faq-plugin-label">Card padding</label><input type="number" class="faq-plugin-input" id="page_design_card_padding" min="8" max="48" placeholder="24" style="width: 100%;"></div>
                                    </div>
                                    <div class="row" id="page_design_card_icon_wrap">
                                        <div class="col-md-6" style="margin-bottom: 10px;"><label class="faq-plugin-label">Card icon URL</label><input type="text" class="faq-plugin-input" id="page_design_card_icon_url" placeholder="https://..." style="width: 100%;"></div>
                                        <div class="col-md-3" style="margin-bottom: 10px;"><label class="faq-plugin-label">Icon shape</label><select class="faq-plugin-select" id="page_design_card_icon_shape" style="width: 100%;"><option value="circle">Circle</option><option value="original">Original</option></select></div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                </div>
                <!-- Preview column: same as Design tab (live iframe for custom, premade cards for templates) -->
                <div class="faq-page-design-preview-col">
                    <div id="page_design_preview_custom" class="faq-page-design-live-preview page_design_preview_mode">
                        <h4 class="faq-live-preview-title"><i class="fa fa-eye"></i> Live Preview <span id="page_design_preview_source_badge" class="faq-plugin-badge faq-plugin-badge-info">Global</span></h4>
                        <div id="page_design_live_preview_content" class="faq-live-preview-content">
                            <p class="faq-live-preview-placeholder"><i class="fa fa-spinner fa-spin"></i> Loading preview…</p>
                        </div>
                    </div>
                    <div id="page_design_preview_premade" class="faq-page-design-premade-preview page_design_preview_mode" style="display: none;">
                        <h4 class="faq-live-preview-title faq-page-design-premade-title-row">
                            <span class="faq-page-design-premade-title-left">
                                <span><i class="fa fa-picture-o"></i> Pre-Made Templates</span>
                                <span id="page_design_preview_source_badge_premade" class="faq-plugin-badge faq-plugin-badge-info">Global</span>
                            </span>
                            <span class="faq-page-design-premade-title-right faq-page-design-copy-tools">
                                <label for="page_design_copy_source" class="faq-template-typography-label" style="margin:0;">Copy design from</label>
                                <select id="page_design_copy_source" class="faq-plugin-select faq-template-typography-select faq-page-design-copy-source">
                                    <option value="">Select page…</option>
                                </select>
                                <button type="button" class="faq-plugin-btn faq-plugin-btn-secondary faq-plugin-btn-sm" onclick="copyPageDesignFromSelected()"><i class="fa fa-copy"></i> Copy</button>
                                <button type="button" class="faq-plugin-btn faq-plugin-btn-secondary faq-plugin-btn-sm" onclick="pastePageDesignOverrides()"><i class="fa fa-clipboard"></i> Paste overrides</button>
                            </span>
                        </h4>
                        <div class="faq-page-design-premade-inline-tip"><i class="fa fa-info-circle"></i> Click template to select. Use <strong>color circle</strong> to change background, <strong>reset</strong> <i class="fa fa-undo"></i> to restore.</div>
                        <div class="faq-page-design-premade-controls">
                            <label for="page_design_premade_font_mode" class="faq-template-typography-label">Text Style</label>
                            <select class="faq-plugin-select faq-template-typography-select" id="page_design_premade_font_mode" onchange="refreshPageDesignPreviewDebounced();" style="width: 190px;">
                                <option value="template_default">Template Default</option>
                                <option value="website_font">Match Website Font</option>
                                <option value="custom_font">Use Custom Font</option>
                            </select>
                            <label for="page_design_template_lock_mode" class="faq-template-typography-label">Template Rules</label>
                            <select class="faq-plugin-select faq-template-typography-select" id="page_design_template_lock_mode" onchange="refreshPageDesignPreviewDebounced();" style="width: 240px;">
                                <option value="strict">Locked (Background + Text Style only)</option>
                                <option value="flexible">Open (Allow current overrides)</option>
                            </select>
                            <button type="button" class="faq-plugin-btn faq-plugin-btn-secondary faq-plugin-btn-sm" onclick="resetPageTemplateColorsLocal()"><i class="fa fa-tint"></i> Reset template colors</button>
                            <button type="button" class="faq-plugin-btn faq-plugin-btn-secondary faq-plugin-btn-sm" onclick="resetPageTypographyLocal()"><i class="fa fa-font"></i> Reset text style</button>
                        </div>
                        <div class="faq-templates-grid faq-page-design-templates-grid">
                            <?php foreach ($design_config as $pdk => $pdinfo):
                                $pdef_bg = isset($pdinfo['default_bg']) ? $pdinfo['default_bg'] : '#ffffff';
                                if ($pdef_bg !== '' && substr($pdef_bg, 0, 1) !== '#') $pdef_bg = '#' . $pdef_bg;
                            ?>
                            <div class="faq-template-card-wrap">
                                <div class="faq-plugin-card faq-design-card-compact faq-template-card faq-page-design-template-card" data-design-preset="<?php echo htmlspecialchars($pdk); ?>">
                                    <div class="faq-design-preview">
                                        <div class="faq-design-preview-image">
                                            <img src="<?php echo htmlspecialchars($pdinfo['preview_image_small']); ?>" alt="<?php echo htmlspecialchars($pdinfo['name']); ?> Preview" class="faq-preview-thumbnail" data-preview-fallback="<?php echo htmlspecialchars(isset($pdinfo['preview_image']) ? $pdinfo['preview_image'] : ''); ?>" onerror="var f=this.getAttribute('data-preview-fallback'); if(f){ this.onerror=null; this.src=f; } else { this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjMwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iNDAwIiBoZWlnaHQ9IjMwMCIgZmlsbD0iI2Y1ZjdmYSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTgiIGZpbGw9IiM2NDc0OGIiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5QcmV2aWV3PC90ZXh0Pjwvc3ZnPg=='; }">
                                        </div>
                                        <div class="faq-design-preview-overlay" style="pointer-events:none;"><i class="fa fa-check"></i> Select</div>
                                    </div>
                                    <div class="faq-design-info">
                                        <div class="faq-template-card-header">
                                            <div class="faq-template-card-title-line" title="<?php echo htmlspecialchars($pdinfo['name'] . ' — ' . $pdinfo['description']); ?>">
                                                <span class="faq-template-card-title"><?php echo htmlspecialchars($pdinfo['name']); ?></span>
                                                <span class="faq-template-card-subtitle"><?php echo htmlspecialchars($pdinfo['description']); ?></span>
                                            </div>
                                            <?php if (in_array($pdk, ['minimal', 'split', 'colorful', 'modern', 'simple', 'card', 'classic'])): ?>
                                            <div class="faq-template-color-row">
                                                <input type="text" class="faq-template-bg-spectrum page-design-template-bg" id="page_design_template_bg_<?php echo htmlspecialchars($pdk); ?>_spectrum" value="<?php echo htmlspecialchars($pdef_bg); ?>" data-template-key="<?php echo htmlspecialchars($pdk); ?>" style="position:absolute;width:0;height:0;opacity:0;pointer-events:none;">
                                                <span class="faq-template-bg-reset page_design_template_bg_clear" data-template="<?php echo htmlspecialchars($pdk); ?>" title="Reset to original color"><i class="fa fa-undo"></i></span>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                </div>
            </div>
            <div class="faq-plugin-modal-footer">
                <button type="button" class="faq-plugin-btn faq-plugin-btn-secondary" onclick="closePageDesignModal()">Cancel</button>
                <button type="button" class="faq-plugin-btn faq-plugin-btn-danger" id="page_design_clear_btn" style="display: none;" onclick="clearPageDesignOverrides()">Clear overrides</button>
                <button type="button" class="faq-plugin-btn faq-plugin-btn-primary" id="page_design_save_btn" onclick="savePageDesignOverrides()"><i class="fa fa-check"></i> Save</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade faq-plugin-modal" id="designPreviewModal" tabindex="-1" role="dialog" aria-labelledby="designPreviewModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog faq-plugin-modal-dialog" role="document" style="max-width: 90%; max-width: 1200px;">
        <div class="modal-content faq-plugin-modal-content">
            <div class="modal-header faq-plugin-modal-header">
                <h5 class="modal-title faq-plugin-modal-title" id="designPreviewModalLabel">Design Preview</h5>
                <button type="button" class="close faq-plugin-modal-close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body faq-plugin-modal-body" style="text-align: center; padding: 30px;">
                <p class="design-preview-description" style="color: #64748b; margin-bottom: 24px; font-size: 16px;"></p>
                <div style="background: #f8fafc; border-radius: 12px; padding: 20px; border: 1px solid #e5e9f0; overflow: auto; max-height: 70vh;">
                    <img class="design-preview-image img-responsive" src="" alt="Design Preview" style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);" />
                </div>
                <p style="color: #94a3b8; font-size: 14px; margin-top: 16px; font-style: italic;">Use the close button to exit</p>
            </div>
            <div class="modal-footer faq-plugin-modal-footer">
                <button type="button" class="faq-plugin-btn faq-plugin-btn-secondary design-close-btn" data-dismiss="modal">Close Preview</button>
                <button type="button" class="faq-plugin-btn faq-plugin-btn-primary design-activate-btn" data-design-preset="">
                    <i class="fa fa-check"></i> Activate This Design
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    var widgetName = 'FAQ Management Plugin';
    if (window.location.href.indexOf('widget=') > -1) {
        var urlParams = new URLSearchParams(window.location.search);
        widgetName = urlParams.get('widget') || 'FAQ Management Plugin';
    }
    var encodedWidgetName = encodeURIComponent(widgetName);
    var baseUrl = window.location.protocol + '//' + window.location.host;
    
    // Use public domain from plugin settings, fallback to current host
    var publicDomainFromSettings = '<?php echo isset($public_domain) && !empty($public_domain) ? rtrim(addslashes($public_domain), "/") : ""; ?>';
    var ajaxBaseUrl = publicDomainFromSettings || baseUrl;
    var ajaxUrl = ajaxBaseUrl + '/api/widget/get/html/FAQ%20Management%20Plugin';
    var designConfigData = <?php echo json_encode($design_config); ?>;
    var faqGlobalDesignSettings = <?php echo json_encode(array(
        'layout_type' => $layout_type,
        'design_preset' => isset($design_settings['design_preset']) ? $design_settings['design_preset'] : 'custom',
        'title_alignment' => $title_alignment,
        'primary_color' => $primary_color,
        'background_color' => $background_color,
        'card_background_color' => $card_background_color,
        'text_color' => $text_color,
        'title_text_color' => $title_text_color,
        'question_text_color' => $question_text_color,
        'answer_text_color' => $answer_text_color,
        'font_family' => $font_family,
        'premade_font_mode' => $premade_font_mode,
        'template_lock_mode' => $template_lock_mode,
        'title_font_size' => $title_font_size,
        'question_font_size' => $question_font_size,
        'answer_font_size' => $answer_font_size,
        'container_width' => $container_width,
        'card_style' => $card_style,
        'grid_columns' => (string)$grid_columns,
        'video_columns' => (string)$video_columns,
        'card_radius' => (string)$card_radius,
        'card_padding' => (string)$card_padding,
        'card_icon_url' => $card_icon_url,
        'card_icon_shape' => $card_icon_shape
    )); ?>;

    window.toggleAdvancedSettings = function() {
        try {
            var section = document.getElementById('advanced_settings_section');
            if (section) {
                if (section.style.display === 'none' || section.style.display === '') {
                    section.style.display = 'block';
                } else {
                    section.style.display = 'none';
                }
            }
        } catch (e) {
            console.error('FAQ Plugin: Error toggling advanced settings:', e);
        }
    };

    async function sendAjax(action, data, callback, suppressAutoToast, retryCount, suppressLoader) {
        retryCount = retryCount || 0;
        const maxRetries = 2;
        if (!suppressLoader) {
            showLoader();
        }

        try {
            const formData = new FormData();
            formData.append('bd_faq_ajax', '1');
            formData.append('bd_faq_action', action);

            for (const key in data) {
                if (data.hasOwnProperty(key)) {
                    if (Array.isArray(data[key])) {
                        if (data[key].length > 0 && typeof data[key][0] === 'object' && data[key][0] !== null) {
                            formData.append(key, JSON.stringify(data[key]));
                        } else {
                            data[key].forEach((value, index) => {
                                formData.append(key + '[]', value);
                            });
                        }
                    } else if (typeof data[key] === 'object' && data[key] !== null) {
                        formData.append(key, JSON.stringify(data[key]));
                    } else {
                        formData.append(key, data[key]);
                    }
                }
            }

            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 30000); // 30 second timeout

            const response = await fetch(ajaxUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                signal: controller.signal
            });

            clearTimeout(timeoutId);

            if (!response.ok) {
                // Retry on network errors
                if (retryCount < maxRetries && (response.status >= 500 || response.status === 0)) {
                    if (!suppressLoader) hideLoader();
                    await new Promise(resolve => setTimeout(resolve, 1000 * (retryCount + 1))); // Exponential backoff
                    return sendAjax(action, data, callback, suppressAutoToast, retryCount + 1, suppressLoader);
                }
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const responseText = await response.text();

            if (!responseText || responseText.trim() === '') {
                hideLoader();
                const errorResponse = {
                    status: 'error',
                    message: 'Empty response from server.'
                };
                showToast('error', errorResponse.message);
                if (callback) callback(errorResponse);
                return errorResponse;
            }

            let actualResponse = null;
            const trimmedText = responseText.trim();

            try {
                actualResponse = JSON.parse(trimmedText);
            } catch (e) {
                const jsonPattern = /\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}/g;
                const jsonMatches = trimmedText.match(jsonPattern);

                if (jsonMatches && jsonMatches.length > 0) {
                    for (let i = jsonMatches.length - 1; i >= 0; i--) {
                        try {
                            const parsed = JSON.parse(jsonMatches[i]);
                            if (parsed.status && (parsed.status === 'success' || parsed.status === 'error')) {
                                actualResponse = parsed;
                                break;
                            }
                        } catch (e2) {
                            continue;
                        }
                    }
                }

                if (!actualResponse) {
                    console.error('Failed to parse response:', trimmedText.substring(0, 500));
                    if (!suppressLoader) hideLoader();
                    const errorResponse = {
                        status: 'error',
                        message: 'Invalid response format. Please check console.'
                    };
                    showToast('error', errorResponse.message);
                    if (callback) callback(errorResponse);
                    return errorResponse;
                }
            }

            hideLoader();

            if (actualResponse && actualResponse.status === 'success') {
                if (actualResponse.html_groups) {
                    $('#container-groups').html(actualResponse.html_groups);
                }
                if (actualResponse.html_questions) {
                    $('#container-questions').html(actualResponse.html_questions);
                }
                if (actualResponse.html_assignments) {
                    $('#container-assignments').html(actualResponse.html_assignments);
                }
                if (actualResponse.html) {
                    $('#priority-questions-list').html(actualResponse.html);
                }

                if (callback) callback(actualResponse);
                if (!suppressAutoToast && actualResponse.message) {
                    showToast('success', actualResponse.message);
                }
            } else {
                console.error('Error Response:', actualResponse);
                if (!suppressAutoToast) {
                    showToast('error', 'Error: ' + (actualResponse ? actualResponse.message : 'Unknown error'));
                }
                if (callback) callback(actualResponse);
            }

            // Return the response for Promise-based usage
            return actualResponse;
        } catch (error) {
            if (!suppressLoader) hideLoader();
            
            // Retry on network errors (fetch failures, timeouts, etc.)
            if (retryCount < maxRetries && (error.name === 'AbortError' || error.message.includes('fetch') || error.message.includes('Failed to fetch'))) {
                await new Promise(resolve => setTimeout(resolve, 1000 * (retryCount + 1))); // Exponential backoff
                return sendAjax(action, data, callback, suppressAutoToast, retryCount + 1, suppressLoader);
            }
            
            console.error('Fetch Error:', error);
            const errorResponse = {
                status: 'error',
                message: error.message || 'Network error occurred. Please try again.'
            };
            
            // Only show toast if not a retry attempt
            if (retryCount >= maxRetries) {
                showToast('error', 'Network error: ' + errorResponse.message + '. Please check your connection and try again.');
            }
            
            if (callback) {
                callback(errorResponse);
            }
            // Return error response for Promise-based usage
            return errorResponse;
        }
    }

    function showLoader() {
        $('#globalLoader').addClass('active');
    }

    function hideLoader() {
        $('#globalLoader').removeClass('active');
    }

    // Search input loading indicators
    function showSearchLoader(inputId) {
        var $input = $('#' + inputId);
        var $parent = $input.parent();
        if ($parent.find('.search-loader').length === 0) {
            $parent.css('position', 'relative');
            $parent.append('<div class="search-loader" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; border: 2px solid #f3f3f3; border-top: 2px solid #3498db; border-radius: 50%; animation: spin 0.8s linear infinite; pointer-events: none;"></div>');
        }
        $parent.find('.search-loader').show();
    }

    function hideSearchLoader(inputId) {
        var $input = $('#' + inputId);
        $input.parent().find('.search-loader').hide();
    }

    // Debounce function for search inputs
    var searchDebounceTimers = {};

    function debounceSearch(inputId, callback, delay) {
        if (searchDebounceTimers[inputId]) {
            clearTimeout(searchDebounceTimers[inputId]);
        }
        searchDebounceTimers[inputId] = setTimeout(function() {
            callback();
        }, delay || 400);
    }

    window.handleLayoutTypeChange = function(layoutType) {
        var tabbedNote = document.getElementById('tabbed_layout_note');
        if (tabbedNote) {
            tabbedNote.style.display = layoutType === 'tabbed' ? 'block' : 'none';
        }
        
        // Show/hide grid-specific options
        var gridColumnsWrapper = document.getElementById('grid_columns_wrapper');
        var videoColumnsWrapper = document.getElementById('video_columns_wrapper');
        var cardRadiusWrapper = document.getElementById('card_radius_wrapper');
        var gridCardOptions = document.getElementById('grid_card_options');
        
        var isGridLayout = layoutType === 'grid-card';
        var isVideoLayout = layoutType === 'video-multimedia';
        if (gridColumnsWrapper) gridColumnsWrapper.style.display = isGridLayout ? 'block' : 'none';
        if (videoColumnsWrapper) videoColumnsWrapper.style.display = isVideoLayout ? 'block' : 'none';
        if (cardRadiusWrapper) cardRadiusWrapper.style.display = 'block';
        if (gridCardOptions) {
            gridCardOptions.style.setProperty('display', isGridLayout ? 'block' : 'none', 'important');
        }
        
        saveCustomizationSetting('layout_type', layoutType);
        if (typeof window.refreshFaqLivePreviewDebounced === 'function') window.refreshFaqLivePreviewDebounced(100);
    };

    window.handleContainerWidthChange = function(value) {
        var customInput = document.getElementById('customization_container_width_custom');
        if (value === 'custom') {
            if (customInput) {
                customInput.style.display = 'block';
                customInput.focus();
                saveCustomizationSetting('container_width', customInput.value || '900');
            }
            if (typeof window.refreshFaqLivePreviewDebounced === 'function') window.refreshFaqLivePreviewDebounced(200);
        } else {
            if (customInput) {
                customInput.style.display = 'none';
            }
            saveCustomizationSetting('container_width', value);
            if (typeof window.refreshFaqLivePreviewDebounced === 'function') window.refreshFaqLivePreviewDebounced(200);
        }
    };

    function faqHexToRgb(hex) {
        hex = (hex || '').replace(/^#/, '');
        if (hex.length !== 6 || !/^[0-9a-fA-F]{6}$/.test(hex)) return { r: 255, g: 255, b: 255 };
        return {
            r: parseInt(hex.substr(0, 2), 16),
            g: parseInt(hex.substr(2, 2), 16),
            b: parseInt(hex.substr(4, 2), 16)
        };
    }

    function faqBuildRgba(hex, alphaPercent) {
        var raw = parseInt(alphaPercent, 10);
        var a = (isNaN(raw) ? 100 : Math.max(0, Math.min(100, raw))) / 100;
        var rgb = faqHexToRgb(hex);
        return 'rgba(' + rgb.r + ',' + rgb.g + ',' + rgb.b + ',' + a + ')';
    }

    function faqNormalizeOpacityColor(value) {
        var v = (value || '').trim();
        if (!v) return 'rgba(255,255,255,1)';
        if (/^#?[0-9a-fA-F]{6}$/.test(v)) {
            var hex = v.charAt(0) === '#' ? v : ('#' + v);
            return faqBuildRgba(hex, 100);
        }
        var rgb = v.match(/^rgb\s*\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/i);
        if (rgb) {
            return 'rgba(' + Math.max(0, Math.min(255, parseInt(rgb[1], 10))) + ',' + Math.max(0, Math.min(255, parseInt(rgb[2], 10))) + ',' + Math.max(0, Math.min(255, parseInt(rgb[3], 10))) + ',1)';
        }
        var rgba = v.match(/^rgba\s*\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*([0-9]*\.?[0-9]+)\s*\)$/i);
        if (rgba) {
            var alpha = parseFloat(rgba[4]);
            if (isNaN(alpha)) alpha = 1;
            alpha = Math.max(0, Math.min(1, alpha));
            return 'rgba(' + Math.max(0, Math.min(255, parseInt(rgba[1], 10))) + ',' + Math.max(0, Math.min(255, parseInt(rgba[2], 10))) + ',' + Math.max(0, Math.min(255, parseInt(rgba[3], 10))) + ',' + alpha + ')';
        }
        return 'rgba(255,255,255,1)';
    }

    window.faqSyncBackgroundColorFromPicker = function() {
        var textEl = document.getElementById('customization_background_color_text');
        var text = textEl ? textEl.value.trim() : '';
        if (text) saveCustomizationSetting('background_color', text);
    };

    window.faqSyncBackgroundColorFromAlpha = function() {
        var textEl = document.getElementById('customization_background_color_text');
        var text = textEl ? textEl.value.trim() : '';
        if (text) saveCustomizationSetting('background_color', text);
    };

    window.faqSyncBackgroundColorFromText = function() {
        var text = (document.getElementById('customization_background_color_text').value || '').trim();
        var m = text.match(/^rgba?\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*(?:,\s*([\d.]+)\s*)?\)/);
        if (m) {
            saveCustomizationSetting('background_color', text);
            return;
        }
        if (/^#?[0-9a-fA-F]{6}$/.test(text)) {
            saveCustomizationSetting('background_color', faqBuildRgba(text.charAt(0) === '#' ? text : '#' + text, 100));
        }
    };

    window.updateCardIconPreview = function(url) {
        var preview = document.getElementById('card_icon_preview');
        if (!preview) return;
        
        var shapeSelect = document.getElementById('customization_card_icon_shape');
        var shape = shapeSelect ? shapeSelect.value : 'circle';
        var primaryColorEl = document.getElementById('customization_primary_color_text');
        var primaryColor = (primaryColorEl && primaryColorEl.value && /^#?[0-9a-fA-F]{6}$/.test(primaryColorEl.value)) ? (primaryColorEl.value.charAt(0) === '#' ? primaryColorEl.value : '#' + primaryColorEl.value) : '#1e3a8a';
        
        // Update preview container shape
        if (shape === 'circle') {
            preview.style.borderRadius = '50%';
            preview.style.background = primaryColor;
        } else {
            preview.style.borderRadius = '0';
            preview.style.background = 'transparent';
        }
        
        if (url && url.trim() !== '') {
            var img = document.createElement('img');
            img.src = url;
            img.alt = 'Icon';
            img.style.cssText = 'width: 100%; height: 100%; object-fit: contain;';
            img.onerror = function() {
                preview.innerHTML = '<i class="fa fa-question" style="color: white; font-size: 24px;"></i>';
                preview.style.background = primaryColor;
                preview.style.borderRadius = '50%';
            };
            preview.innerHTML = '';
            preview.appendChild(img);
        } else {
            preview.innerHTML = '<i class="fa fa-question" style="color: white; font-size: 24px;"></i>';
            preview.style.background = primaryColor;
            preview.style.borderRadius = '50%';
        }
    };

    // Debounce timers for settings
    var saveDebounceTimers = {};
    
    // Debounced save function for inputs that change rapidly (like number inputs)
    window.saveCustomizationSettingDebounced = function(settingKey, settingValue, suppressToast, delay) {
        delay = delay || 500; // Default 500ms delay
        
        // Clear any existing timer for this setting
        if (saveDebounceTimers[settingKey]) {
            clearTimeout(saveDebounceTimers[settingKey]);
        }
        
        // Set new timer
        saveDebounceTimers[settingKey] = setTimeout(function() {
            saveCustomizationSetting(settingKey, settingValue, suppressToast);
        }, delay);
    };
    
    window.saveCustomizationSetting = async function(settingKey, settingValue, suppressToast) {
        if (settingKey === 'background_color') {
            settingValue = faqNormalizeOpacityColor(settingValue);
            var bgEl = document.getElementById('customization_background_color_text');
            if (bgEl) bgEl.value = settingValue;
        }
        try {
            const response = await sendAjax('save_design_setting', {
                setting_key: settingKey,
                setting_value: settingValue
            }, null, suppressToast);
            
            if (!suppressToast) {
                if (response && response.status === 'success') {
                    showToast('success', 'Settings saved');
                } else {
                    showToast('error', response.message || 'Save failed');
                }
            }
            if (response && response.status === 'success' && typeof window.refreshFaqLivePreviewDebounced === 'function') {
                window.refreshFaqLivePreviewDebounced();
            }
        } catch (error) {
            console.error('Error saving customization setting:', error);
            if (!suppressToast) {
                showToast('error', 'Failed to save customization');
            }
        }
    };

    function getFaqLivePreviewFormData() {
        var data = {};
        var idToKey = {
            'customization_layout_type': 'layout_type',
            'customization_title_alignment': 'title_alignment',
            'customization_font_family': 'font_family',
            'customization_premade_font_mode': 'premade_font_mode',
            'customization_template_lock_mode': 'template_lock_mode',
            'customization_primary_color_text': 'primary_color',
            'customization_background_color_text': 'background_color',
            'customization_card_background_color_text': 'card_background_color',
            'customization_title_text_color_text': 'title_text_color',
            'customization_question_text_color_text': 'question_text_color',
            'customization_answer_text_color_text': 'answer_text_color',
            'customization_title_font_size': 'title_font_size',
            'customization_question_font_size': 'question_font_size',
            'customization_answer_font_size': 'answer_font_size',
            'customization_container_width': 'container_width',
            'customization_grid_columns': 'grid_columns',
            'customization_video_columns': 'video_columns',
            'customization_card_radius': 'card_radius',
            'customization_card_padding': 'card_padding',
            'customization_card_style': 'card_style'
        };
        for (var id in idToKey) {
            var input = document.getElementById(id);
            if (input && (input.value || input.value === 0)) data[idToKey[id]] = input.value;
        }
        if (data.container_width === 'custom') {
            var customWidthInput = document.getElementById('customization_container_width_custom');
            data.container_width = (customWidthInput && customWidthInput.value) ? customWidthInput.value : '900';
        }
        var textColorEl = document.getElementById('customization_text_color_text');
        if (textColorEl && textColorEl.value) data['text_color'] = textColorEl.value;
        return data;
    }


    window.refreshFaqLivePreview = function() {
        var el = document.getElementById('faq-live-preview-content');
        if (!el) return;
        var panel = el.closest('.faq-custom-layout-preview');
        if (!panel || panel.offsetParent === null) return;
        el.classList.add('faq-live-preview-loading');
        el.innerHTML = '<p class="faq-live-preview-placeholder"><i class="fa fa-spinner fa-spin"></i> Loading preview…</p>';
        var previewData = typeof getFaqLivePreviewFormData === 'function' ? getFaqLivePreviewFormData() : {};
        sendAjax('faq_preview_document', previewData, function(response) {
            el.classList.remove('faq-live-preview-loading');
            if (response && response.status === 'success' && response.html) {
                var iframe = el.querySelector('.faq-live-preview-iframe');
                if (!iframe) {
                    iframe = document.createElement('iframe');
                    iframe.className = 'faq-live-preview-iframe';
                    iframe.setAttribute('sandbox', 'allow-same-origin allow-scripts');
                    iframe.title = 'FAQ Live Preview';
                }
                el.innerHTML = '';
                el.appendChild(iframe);
                try {
                    iframe.srcdoc = response.html;
                } catch (err) {
                    el.innerHTML = '<p class="faq-live-preview-placeholder" style="color:#94a3b8;"><i class="fa fa-info-circle"></i> Preview could not be loaded.</p>';
                }
            } else {
                el.innerHTML = '<p class="faq-live-preview-placeholder" style="color:#94a3b8;"><i class="fa fa-info-circle"></i> Preview will appear here. Save a design setting to refresh.</p>';
            }
        }, true, 0, true);
    };

    var faqLivePreviewDebounceTimer = null;

    window.refreshFaqLivePreviewDebounced = function(delayMs) {
        if (faqLivePreviewDebounceTimer) clearTimeout(faqLivePreviewDebounceTimer);
        faqLivePreviewDebounceTimer = setTimeout(function() { window.refreshFaqLivePreview(); }, delayMs || 500);
    };

    (function initFaqLivePreview() {
        function run() {
            var section = document.getElementById('custom_layout_section');
            if (section && section.style.display !== 'none') setTimeout(function() { window.refreshFaqLivePreview(); }, 300);
        }
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run);
        else run();
    })();

    (function initFaqLivePreviewInteractivity() {
        function attach() {
            var container = document.getElementById('faq-live-preview-content');
            if (!container) return;
            container.addEventListener('click', function(e) {
            var q = e.target.closest('.faq-preview-accordion-question');
            if (q) {
                e.preventDefault();
                var item = q.closest('.faq-preview-accordion-item');
                if (!item) return;
                var wasOpen = item.classList.contains('faq-preview-accordion-item-open');
                var list = item.closest('.faq-preview-accordion');
                if (list) {
                    var siblings = list.querySelectorAll('.faq-preview-accordion-item');
                    for (var i = 0; i < siblings.length; i++) siblings[i].classList.remove('faq-preview-accordion-item-open');
                }
                if (!wasOpen) item.classList.add('faq-preview-accordion-item-open');
                return;
            }
            var tab = e.target.closest('.faq-preview-tab');
            if (tab && tab.hasAttribute('data-tab')) {
                e.preventDefault();
                var idx = tab.getAttribute('data-tab');
                var wrap = tab.closest('.faq-preview-tabbed');
                if (!wrap) return;
                var tabs = wrap.querySelectorAll('.faq-preview-tab');
                var panels = wrap.querySelectorAll('.faq-preview-panel');
                for (var t = 0; t < tabs.length; t++) tabs[t].classList.remove('faq-preview-tab-active');
                tab.classList.add('faq-preview-tab-active');
                for (var p = 0; p < panels.length; p++) {
                    panels[p].style.display = panels[p].getAttribute('data-tab') === idx ? '' : 'none';
                }
            }
        });
        }
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', attach);
        else attach();
    })();

    window.resetDesignSettings = function() {
        swal({
            title: 'Reset to Default?',
            text: 'This will reset all customization settings (colors, fonts, alignment, layout) to their default values. This action cannot be undone.',
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, reset to default!',
            cancelButtonText: 'Cancel',
            closeOnConfirm: false
        }, function(isConfirm) {
            if (isConfirm) {
                sendAjax('reset_design_settings', {}, function(response) {
                    if (response && response.status === 'success') {
                        if (response.defaults) {
                            var defaults = response.defaults;

                            $('#customization_layout_type').val(defaults.layout_type || 'accordion');
                            $('#customization_title_alignment').val(defaults.title_alignment || 'center');
                            $('#customization_font_family').val(defaults.font_family || 'system');
                            $('#customization_premade_font_mode').val(defaults.premade_font_mode || 'template_default');
                            $('#customization_template_lock_mode').val(defaults.template_lock_mode || 'flexible');

                            function setSpectrumIfInited(selector, value) {
                                var $el = $(selector);
                                if (!$el.length) return;
                                $el.val(value);
                                try {
                                    if (typeof $el.spectrum === 'function') $el.spectrum('set', value);
                                } catch (e) {}
                            }
                            var primaryDef = defaults.primary_color || '#1e3a8a';
                            setSpectrumIfInited('#customization_primary_color_text', primaryDef);

                            var bg = defaults.background_color || 'rgba(255,255,255,1)';
                            setSpectrumIfInited('#customization_background_color_text', bg);

                            var cardDef = defaults.card_background_color || '#ffffff';
                            setSpectrumIfInited('#customization_card_background_color_text', cardDef);

                            var titleDef = defaults.title_text_color || '#1f2937';
                            setSpectrumIfInited('#customization_title_text_color_text', titleDef);

                            var qDef = defaults.question_text_color || '#1f2937';
                            setSpectrumIfInited('#customization_question_text_color_text', qDef);

                            var aDef = defaults.answer_text_color || '#1f2937';
                            setSpectrumIfInited('#customization_answer_text_color_text', aDef);

                            $('#customization_title_font_size').val(defaults.title_font_size || '32');
                            $('#customization_question_font_size').val(defaults.question_font_size || '18');
                            $('#customization_answer_font_size').val(defaults.answer_font_size || '16');
                            
                            // Container and Card settings
                            $('#customization_container_width').val(defaults.container_width || '900');
                            $('#customization_card_style').val(defaults.card_style || 'shadow');
                            
                            // Grid/Card settings
                            $('#customization_grid_columns').val(defaults.grid_columns || '3');
                            $('#customization_video_columns').val(defaults.video_columns || '3');
                            $('#customization_card_radius').val(defaults.card_radius || '12');
                            $('#customization_card_padding').val(defaults.card_padding || '24');
                            $('#customization_card_icon_url').val(defaults.card_icon_url || '');
                            $('#customization_card_icon_shape').val(defaults.card_icon_shape || 'circle');
                            updateCardIconPreview('');
                            
                            // Update visibility of grid options
                            handleLayoutTypeChange(defaults.layout_type || 'accordion');
                        }

                        swal('Reset!', 'All customization settings have been reset to default values.', 'success');
                    } else {
                        swal('Error!', response.message || 'Failed to reset settings. Please try again.', 'error');
                    }
                });
            }
        });
    };

    function getTemplateBgDefaults() {
        return {
            minimal: '#ffffff',
            split: '#f5f5f0',
            colorful: '#fef3f8',
            modern: '#f8fafc',
            simple: '#ffffff',
            card: '#f1f5f9',
            classic: '#fafafa'
        };
    }

    window.runDesignConsistencyCheck = function() {
        var $box = $('#design_consistency_results');
        $box.show().html('<div class="alert alert-info"><i class="fa fa-spinner fa-spin"></i> Checking saved design keys…</div>');
        sendAjax('check_design_consistency', {}, function(resp) {
            if (!resp || resp.status !== 'success') {
                $box.html('<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> ' + ((resp && resp.message) ? resp.message : 'Consistency check failed.') + '</div>');
                return;
            }
            var summary = resp.summary || {};
            var html = '';
            html += '<div class="alert alert-info" style="margin-bottom:8px;">';
            html += '<strong>Summary:</strong> ';
            html += 'Scopes: ' + (summary.scopes || 0) + ', ';
            html += 'Issues: ' + (summary.issues || 0) + ', ';
            html += 'Warnings: ' + (summary.warnings || 0);
            html += '</div>';
            if (Array.isArray(resp.global_warnings) && resp.global_warnings.length) {
                html += '<div class="alert alert-warning"><strong>Global warnings:</strong><ul style="margin:6px 0 0 18px;">';
                resp.global_warnings.forEach(function(w) { html += '<li>' + String(w) + '</li>'; });
                html += '</ul></div>';
            }
            var scopes = resp.scopes || {};
            var scopeKeys = Object.keys(scopes);
            if (!scopeKeys.length) {
                html += '<div class="alert alert-success" style="margin-bottom:0;"><i class="fa fa-check"></i> No scope-level design issues found.</div>';
            } else {
                html += '<div style="border:1px solid #e2e8f0;border-radius:6px;background:#fff;max-height:280px;overflow:auto;padding:10px;">';
                scopeKeys.forEach(function(scope) {
                    var info = scopes[scope] || {};
                    var issues = info.issues || [];
                    var warnings = info.warnings || [];
                    html += '<div style="padding:6px 0;border-bottom:1px solid #f1f5f9;">';
                    html += '<strong>' + scope + '</strong> ';
                    if (issues.length) html += '<span class="faq-plugin-badge faq-plugin-badge-danger">' + issues.length + ' issue(s)</span> ';
                    if (warnings.length) html += '<span class="faq-plugin-badge faq-plugin-badge-warning">' + warnings.length + ' warning(s)</span>';
                    if (issues.length || warnings.length) {
                        html += '<ul style="margin:6px 0 0 18px;">';
                        issues.forEach(function(i) { html += '<li style="color:#b91c1c;">' + String(i) + '</li>'; });
                        warnings.forEach(function(w) { html += '<li style="color:#92400e;">' + String(w) + '</li>'; });
                        html += '</ul>';
                    }
                    html += '</div>';
                });
                html += '</div>';
            }
            $box.html(html);
        }, true);
    };

    window.resetOnlyTemplateColors = function() {
        var defaults = getTemplateBgDefaults();
        var keys = Object.keys(defaults);
        if (!keys.length) return;
        var pending = keys.length;
        keys.forEach(function(key) {
            var val = defaults[key];
            sendAjax('save_design_setting', { setting_key: 'template_bg_' + key, setting_value: val }, function() {
                pending--;
                if (pending <= 0) {
                    keys.forEach(function(k) {
                        var hex = defaults[k];
                        $('#template_bg_' + k + '_spectrum').val(hex);
                        $('.faq-template-bg-swatch[data-template-key="' + k + '"]').css('background-color', hex);
                        try { $('#template_bg_' + k + '_spectrum').spectrum('set', hex); } catch (e) {}
                    });
                    refreshFaqLivePreviewDebounced(50);
                    showToast('success', 'Template colors reset.');
                }
            }, true);
        });
    };

    window.resetOnlyTypography = function() {
        $('#customization_premade_font_mode').val('template_default');
        $('#customization_font_family').val('system');
        sendAjax('save_design_setting', { setting_key: 'premade_font_mode', setting_value: 'template_default' }, function() {
            sendAjax('save_design_setting', { setting_key: 'font_family', setting_value: 'system' }, function() {
                refreshFaqLivePreviewDebounced(50);
                showToast('success', 'Text style reset.');
            }, true);
        }, true);
    };

    var paginationState = {
        groups: {
            page: 1,
            per_page: 25,
            show_all: false
        },
        questions: {
            page: 1,
            per_page: 25,
            show_all: false
        },
        assignments: {
            page: 1,
            per_page: 25,
            show_all: false
        }
    };

    var filterState = {
        questions: {
            search: '',
            group_filter: ''
        },
        assignments: {
            page_filter: '',
            group_filter: ''
        },
        groups: {
            search: '',
            show_system: 1
        }
    };

    function changePage(tableType, page) {
        if (!paginationState[tableType]) {
            paginationState[tableType] = {
                page: 1,
                per_page: 25,
                show_all: false
            };
        }
        paginationState[tableType].page = page;

        if (tableType === 'questions') {
            var search = $('#question-search').val() || '';
            var groupFilter = $('#question-group-filter').val() || '';
            if (search || groupFilter) {
                filterQuestions();
                return;
            }
        } else if (tableType === 'assignments') {
            var pageFilter = $('#assignment-page-filter').val() || '';
            var groupFilter = $('#assignment-group-filter').val() || '';
            if (pageFilter || groupFilter) {
                filterAssignments();
                return;
            }
        } else if (tableType === 'groups') {
            var search = $('#group-search').val() || '';
            var showSystem = $('#show-system-groups').is(':checked') ? 1 : 0;
            if (search || !showSystem) {
                filterGroups();
                return;
            }
        }

        refreshTable(tableType);
    }

    function changePerPage(tableType, perPage) {
        if (!paginationState[tableType]) {
            paginationState[tableType] = {
                page: 1,
                per_page: 25,
                show_all: false
            };
        }
        var isAll = (String(perPage).toLowerCase() === 'all');
        paginationState[tableType].show_all = isAll;
        paginationState[tableType].per_page = isAll ? 25 : Math.max(1, parseInt(perPage, 10));
        paginationState[tableType].page = 1;

        if (tableType === 'questions') {
            var search = $('#question-search').val() || '';
            var groupFilter = $('#question-group-filter').val() || '';
            if (search || groupFilter) {
                filterQuestions();
                return;
            }
        } else if (tableType === 'assignments') {
            var pageFilter = $('#assignment-page-filter').val() || '';
            var groupFilter = $('#assignment-group-filter').val() || '';
            if (pageFilter || groupFilter) {
                filterAssignments();
                return;
            }
        } else if (tableType === 'groups') {
            var search = $('#group-search').val() || '';
            var showSystem = $('#show-system-groups').is(':checked') ? 1 : 0;
            if (search || !showSystem) {
                filterGroups();
                return;
            }
        }

        refreshTable(tableType);
    }

    function toggleShowAll(tableType, showAll) {
        if (!paginationState[tableType]) {
            paginationState[tableType] = {
                page: 1,
                per_page: 25,
                show_all: false
            };
        }
        paginationState[tableType].show_all = showAll;
        if (showAll) {
            paginationState[tableType].page = 1;
        }

        if (tableType === 'questions') {
            var search = $('#question-search').val() || '';
            var groupFilter = $('#question-group-filter').val() || '';
            if (search || groupFilter) {
                filterState.questions.search = search;
                filterState.questions.group_filter = groupFilter;
                filterQuestions();
                return;
            }
        } else if (tableType === 'assignments') {
            var pageFilter = $('#assignment-page-filter').val() || '';
            var groupFilter = $('#assignment-group-filter').val() || '';
            if (pageFilter || groupFilter) {
                filterState.assignments.page_filter = pageFilter;
                filterState.assignments.group_filter = groupFilter;
                filterAssignments();
                return;
            }
        } else if (tableType === 'groups') {
            var search = $('#group-search').val() || '';
            var showSystem = $('#show-system-groups').is(':checked') ? 1 : 0;
            if (search || !showSystem) {
                filterState.groups.search = search;
                filterState.groups.show_system = showSystem;
                filterGroups();
                return;
            }
        }

        refreshTable(tableType);
    }

    // Expose pagination functions globally so inline onclick in AJAX-loaded HTML can find them
    window.changePage = changePage;
    window.changePerPage = changePerPage;
    window.toggleShowAll = toggleShowAll;

    // Delegated click handler for pagination buttons (works when content is replaced via AJAX).
    // Use .attr() so we read current DOM; set state and call refreshTable directly so pagination
    // works even when inline onclick is not in scope (e.g. iframe or dynamic content).
    $(document).on('click', '.faq-pagination-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var $btn = $(this);
        var tableType = $btn.attr('data-faq-table');
        var page = parseInt($btn.attr('data-faq-page'), 10);
        if (!tableType || isNaN(page) || page < 1) return;
        if (!paginationState[tableType]) {
            paginationState[tableType] = { page: 1, per_page: 25, show_all: false };
        }
        paginationState[tableType].page = page;
        refreshTable(tableType);
    });

    function refreshTable(tableType) {
        var state = paginationState[tableType] || {
            page: 1,
            per_page: 25,
            show_all: false
        };
        var action = '';
        var containerId = '';
        var responseKey = '';

        if (tableType === 'groups') {
            action = 'get_groups_table';
            containerId = 'container-groups';
            responseKey = 'html_groups';
        } else if (tableType === 'questions') {
            var selectedGroup = $('#question-group-filter').val() || '0';
            if (selectedGroup === '0' || selectedGroup === '') {
                showQuestionsSelectGroupPlaceholder();
                return;
            }
            action = 'get_questions_table';
            containerId = 'container-questions';
            responseKey = 'html_questions';
        } else if (tableType === 'assignments') {
            action = 'get_assignments_table';
            containerId = 'container-assignments';
            responseKey = 'html_assignments';
        } else {
            return;
        }

        var data = {
            page: state.page,
            per_page: state.per_page,
            show_all: state.show_all ? '1' : '0'
        };

        sendAjax(action, data, function(response) {
            if (response && response.status === 'success' && response[responseKey]) {
                $('#' + containerId).html(response[responseKey]);
            }
        });
    }

    async function toggleDesignMode(showTemplates) {
        var customSection = document.getElementById('custom_layout_section');
        var templatesSection = document.getElementById('templates_section');
        var toggleLabelCustom = document.getElementById('toggle_label_custom');
        var toggleLabelTemplate = document.getElementById('toggle_label_template');

        if (showTemplates) {
            customSection.style.display = 'none';
            templatesSection.style.display = 'block';
            toggleLabelCustom.style.opacity = '0.5';
            toggleLabelTemplate.style.opacity = '1';

            var designConfig = <?php echo json_encode($design_config); ?>;
            var firstTemplateKey = Object.keys(designConfig)[0];

            try {
                const response = await sendAjax('get_design_setting', {
                    setting_key: 'design_preset'
                });
                
                var currentDesign = response && response.setting_value ? response.setting_value : 'custom';

                if (currentDesign === 'custom' || !designConfig[currentDesign]) {
                    await selectDesign(firstTemplateKey, null, true);
                }
            } catch (error) {
                console.error('Error in toggleDesignMode:', error);
            }
        } else {
            customSection.style.display = 'block';
            templatesSection.style.display = 'none';
            toggleLabelCustom.style.opacity = '1';
            toggleLabelTemplate.style.opacity = '0.5';

            await selectDesign('custom', null, true);
            if (typeof window.refreshFaqLivePreview === 'function') window.refreshFaqLivePreview();
        }
    }

    async function selectDesign(preset, event, suppressToast) {
        if (event) {
            event.stopPropagation();
        }

        var designConfig = <?php echo json_encode($design_config); ?>;
        var selectedDesign = designConfig[preset];
        
        // If switching from custom layout to premade template, clear custom background colors
        var isSwitchingToPremade = preset !== 'custom' && selectedDesign;
        var customBackgroundColors = ['background_color', 'card_background_color', 'primary_color', 'text_color'];
        
        try {
            // Always suppress auto-toast here; we show a single toast in updateUIAfterDesignSwitch
            const response = await sendAjax('save_design_setting', {
                setting_key: 'design_preset',
                setting_value: preset
            }, null, true);
            
            if (response && response.status === 'success') {
                // Clear custom layout background colors when switching to premade templates
                if (isSwitchingToPremade && customBackgroundColors.length > 0) {
                    // Clear all colors in parallel using Promise.all
                    await Promise.all(
                        customBackgroundColors.map(colorKey => 
                            sendAjax('save_design_setting', {
                                setting_key: colorKey,
                                setting_value: ''
                            }, null, true)
                        )
                    );
                }
                
                updateUIAfterDesignSwitch(preset, selectedDesign, suppressToast);
            } else {
                if (!suppressToast) {
                    showToast('error', 'Failed to save design setting. Please try again.');
                }
            }
        } catch (error) {
            console.error('Error in selectDesign:', error);
            if (!suppressToast) {
                showToast('error', 'Failed to save design setting. Please try again.');
            }
        }
    }
    
    async function updateUIAfterDesignSwitch(preset, selectedDesign, suppressToast) {
        $('.faq-plugin-card[data-design-preset]').removeClass('active');
        $('.faq-plugin-card[data-design-preset]').find('.faq-design-active-badge').remove();
        $('.faq-plugin-card[data-design-preset="' + preset + '"]').addClass('active');
        $('.faq-plugin-card[data-design-preset="' + preset + '"] .faq-design-preview').append('<div class="faq-design-active-badge"><i class="fa fa-check-circle"></i> Active</div>');
        $('.faq-plugin-card[data-design-preset] button').each(function() {
            var cardPreset = $(this).closest('.faq-plugin-card').data('design-preset');
            if (cardPreset === preset) {
                $(this).html('<i class="fa fa-check"></i> Active');
            } else {
                $(this).html('<i class="fa fa-check"></i> Activate');
            }
        });
        
        if (selectedDesign && selectedDesign.allow_customization === 'colors_only' && selectedDesign.fixed_layout) {
            await sendAjax('save_design_setting', {
                setting_key: 'layout_type',
                setting_value: selectedDesign.fixed_layout
            }, null, true, 0, true);
        }
        
        updateLayoutLockStatus(preset);

        if (!suppressToast) {
            // Only show one toast message for design activation
            showToast('success', 'Design activated successfully');
        }
    }

    function updateLayoutLockStatus(preset) {
        var designConfig = <?php echo json_encode($design_config); ?>;

        var selectedDesign = designConfig[preset];
        var layoutTypeSelect = document.getElementById('customization_layout_type');
        var layoutTypeHelp = document.getElementById('layout_type_help');
        var fixedLayoutNotice = document.getElementById('fixed_layout_notice');

        if (!selectedDesign || preset === 'custom') {
            if (layoutTypeSelect) {
                layoutTypeSelect.disabled = false;
                layoutTypeSelect.style.opacity = '1';
                layoutTypeSelect.style.cursor = 'pointer';
            }

            if (layoutTypeHelp) {
                layoutTypeHelp.innerHTML = 'Choose how FAQs are displayed and interacted with';
            }

            if (fixedLayoutNotice) {
                fixedLayoutNotice.style.display = 'none';
            }
            return;
        }

        if (selectedDesign.allow_customization === 'colors_only') {
            if (layoutTypeSelect) {
                layoutTypeSelect.disabled = true;
                layoutTypeSelect.value = selectedDesign.fixed_layout || 'accordion';
                layoutTypeSelect.style.opacity = '0.6';
                layoutTypeSelect.style.cursor = 'not-allowed';
            }

            if (layoutTypeHelp) {
                layoutTypeHelp.innerHTML = '<strong style="color: #856404;">? Layout is fixed for this template</strong>';
            }

            if (fixedLayoutNotice) {
                fixedLayoutNotice.style.display = 'block';
            }

        } else {
            if (layoutTypeSelect) {
                layoutTypeSelect.disabled = false;
                layoutTypeSelect.style.opacity = '1';
                layoutTypeSelect.style.cursor = 'pointer';
            }

            if (layoutTypeHelp) {
                layoutTypeHelp.innerHTML = 'Choose how FAQs are displayed and interacted with';
            }

            if (fixedLayoutNotice) {
                fixedLayoutNotice.style.display = 'none';
            }
        }
    }

    $(document).ready(function() {
        var activeDesign = <?php echo json_encode($active_design); ?>;
        if (activeDesign) {
            updateLayoutLockStatus(activeDesign);
        }
        
        // Initialize layout-specific UI visibility on page load
        var currentLayoutType = $('#customization_layout_type').val();
        if (currentLayoutType) {
            // Show/hide tabbed note
            var tabbedNote = document.getElementById('tabbed_layout_note');
            if (tabbedNote) {
                tabbedNote.style.display = currentLayoutType === 'tabbed' ? 'block' : 'none';
            }
            
            // Show/hide grid-card options
            var gridColumnsWrapper = document.getElementById('grid_columns_wrapper');
            var videoColumnsWrapper = document.getElementById('video_columns_wrapper');
            var cardRadiusWrapper = document.getElementById('card_radius_wrapper');
            var gridCardOptions = document.getElementById('grid_card_options');
            var isGridLayout = currentLayoutType === 'grid-card';
            var isVideoLayout = currentLayoutType === 'video-multimedia';
            
            if (gridColumnsWrapper) gridColumnsWrapper.style.display = isGridLayout ? 'block' : 'none';
            if (videoColumnsWrapper) videoColumnsWrapper.style.display = isVideoLayout ? 'block' : 'none';
            if (cardRadiusWrapper) cardRadiusWrapper.style.display = 'block';
            if (gridCardOptions) gridCardOptions.style.setProperty('display', isGridLayout ? 'block' : 'none', 'important');
        }
        
    });

    window.savePublicDomain = async function() {
        var publicDomain = $('#public_domain').val().trim();
        
        if (publicDomain && !publicDomain.match(/^https?:\/\/.+/)) {
            alert('Please enter a valid URL starting with http:// or https://');
            return;
        }
        
        try {
            const response = await sendAjax('save_design_setting', {
                setting_key: 'public_domain',
                setting_value: publicDomain
            });
            
            if (response && response.status === 'success') {
                $('#public_domain').closest('.col-md-12').find('small').html(
                    '<span style="color: #10b981;"><i class="fa fa-check-circle"></i> Public domain saved successfully!</span>'
                );
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                showToast('error', 'Failed to save domain');
            }
        } catch (error) {
            console.error('Error saving public domain:', error);
            showToast('error', 'Network error: ' + (error.message || 'Unknown error'));
        }
    };

    window.saveCdnUrl = async function() {
        var cdnUrl = $('#cdn_base_url').val().trim();
        var ownerCdn = <?php echo json_encode(FAQ_OWNER_CDN_URL); ?>;
        
        var cdnToSave = cdnUrl === ownerCdn ? '' : cdnUrl;
        var isCustom = cdnUrl && cdnUrl !== ownerCdn;

        try {
            const response = await sendAjax('save_design_setting', {
                setting_key: 'cdn_base_url',
                setting_value: cdnToSave
            });
            
            if (response && response.status === 'success') {
                var displayCdn = cdnUrl || ownerCdn;
                var displayUrl = displayCdn.replace(/\/$/, '') + '/tools/faq-[design].css';
                
                if (isCustom) {
                    showToast('success', 'CDN URL saved');
                } else {
                    showToast('success', 'Using default CDN');
                }
                
                var statusText = isCustom 
                    ? '<span style="color: #3b82f6;">Using custom CDN</span> - '
                    : '<span style="color: #10b981;">Using owner&apos;s CDN</span> (automatic updates) - ';
                
                $('#cdn_base_url').closest('.col-md-12').find('small').html(
                    '<strong>Current:</strong> ' + statusText + '<code>' + displayUrl + '</code><br>' +
                    '<i class="fa fa-info-circle"></i> Leave empty to use owner&apos;s CDN. Enter custom URL to override.'
                );
            } else {
                showToast('error', 'Failed to save CDN');
            }
        } catch (error) {
            console.error('Error saving CDN URL:', error);
                showToast('error', 'Failed to save CDN');
        }
    };

    window.saveTemplateBgColor = async function(templateKey, colorValue, event) {
        if (event) {
            event.stopPropagation();
            event.preventDefault();
        }

        var settingKey = 'template_bg_' + templateKey;

        try {
            const response = await sendAjax('save_design_setting', {
                setting_key: settingKey,
                setting_value: colorValue
            });
            
            if (response && response.status === 'success') {
                showToast('success', 'Background color saved for ' + templateKey.charAt(0).toUpperCase() + templateKey.slice(1) + ' template!');
            } else {
                showToast('error', 'Failed to save color');
            }
        } catch (error) {
            console.error('Error saving template background color:', error);
            showToast('error', 'Failed to save color');
        }
    };

    window.resetTemplateBgColor = async function(templateKey, event) {
        if (event) {
            event.stopPropagation();
            event.preventDefault();
        }

        var defaultColors = {
            'minimal': '#ffffff',
            'split': '#f5f5f0',
            'colorful': '#fef3f8',
            'modern': '#f8fafc',
            'simple': '#ffffff',
            'card': '#f1f5f9',
            'classic': '#fafafa'
        };

        var defaultColor = defaultColors[templateKey];

        if (!defaultColor) {
            showToast('error', 'Invalid template key.');
            return;
        }

        var $spec = $('#template_bg_' + templateKey + '_spectrum');
        if ($spec.length && $spec.spectrum) $spec.spectrum('set', defaultColor);
        $('.faq-template-bg-swatch[data-template-key="' + templateKey + '"]').css('background-color', defaultColor);

        var settingKey = 'template_bg_' + templateKey;
        
        try {
            const response = await sendAjax('save_design_setting', {
                setting_key: settingKey,
                setting_value: defaultColor
            });
            
            if (response && response.status === 'success') {
                showToast('success', 'Color reset');
            } else {
                showToast('error', 'Failed to reset');
            }
        } catch (error) {
            console.error('Error resetting template background color:', error);
            showToast('error', 'Failed to reset');
        }
    };

    window.previewDesign = function(preset) {
        if (!designConfigData || !designConfigData[preset]) {
            showToast('error', 'Preview data not found for this design.');
            return;
        }

        var designInfo = designConfigData[preset];
        var modal = $('#designPreviewModal');

        modal.find('#designPreviewModalLabel').text(designInfo.name + ' Design Preview');
        modal.find('.design-preview-description').text(designInfo.description);
        var previewImg = modal.find('.design-preview-image');
        previewImg.attr('src', designInfo.preview_image);
        previewImg.attr('alt', designInfo.name + ' Design Preview');
        previewImg.off('error').on('error', function() {
            $(this).css({
                'border': '2px dashed #cbd5e0',
                'padding': '40px',
                'background': '#f8fafc'
            });
        });
        modal.find('.design-activate-btn').attr('data-design-preset', preset);

        modal.modal({
            backdrop: 'static',
            keyboard: false,
            show: true
        });

        modal.off('click.designActivate', '.design-activate-btn').on('click.designActivate', '.design-activate-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var designPreset = $(this).attr('data-design-preset');
            if (designPreset) {
                selectDesign(designPreset, null);
                modal.modal('hide');
            }
        });

        modal.off('click.designClose', '.faq-plugin-modal-close, .design-close-btn, [data-dismiss="modal"]').on('click.designClose', '.faq-plugin-modal-close, .design-close-btn, [data-dismiss="modal"]', function(e) {
            e.preventDefault();
            e.stopPropagation();
            modal.modal('hide');
        });

        // modal.off('click.designBackdrop').on('click.designBackdrop', function(e) {
        //     if ($(e.target).hasClass('modal') || $(e.target).hasClass('faq-plugin-modal') || ($(e.target).closest('.modal-dialog').length === 0 && !$(e.target).closest('.modal-content').length)) {
        //         modal.modal('hide');
        //     }
        // });

        // $(document).off('keydown.designPreview').on('keydown.designPreview', function(e) {
        //     if (e.keyCode === 27 && modal.is(':visible')) {
        //         modal.modal('hide');
        //         $(document).off('keydown.designPreview');
        //     }
        // });

        modal.off('hidden.bs.modal.designPreview').on('hidden.bs.modal.designPreview', function() {
            modal.off('click.designActivate click.designClose');
            $(document).off('keydown.designPreview');
            $(document).off('keydown.designPreview');
        });
    };

    var bdFaqAnswerEditorInitialized = false;

    function destroyBdFaqAnswerEditor() {
        bdFaqAnswerEditorInitialized = false;
    }

    function initBdFaqAnswerEditor() {
        if (bdFaqAnswerEditorInitialized) {
            return;
        }
        
        var $editor = $('#bd-faq-answer-text');
        var $hiddenTextarea = $('#bd-faq-answer-text-hidden');
        var $toolbar = $('#bd-faq-editor-toolbar');
        
        if ($editor.length === 0) {
            return;
        }
        
        if (!$editor.is(':visible')) {
            setTimeout(function() {
                initBdFaqAnswerEditor();
            }, 200);
            return;
        }
        
        $toolbar.find('.bd-faq-editor-btn[data-command]').off('click').on('click', function(e) {
            e.preventDefault();
            var command = $(this).data('command');
            $editor[0].focus();
            document.execCommand(command, false, null);
            updateToolbarState();
        });
        
        // Clean Format button handler
        $('#bd-faq-clean-format-btn').off('click').on('click', function(e) {
            e.preventDefault();
            
            // Get the plain text content (strips all HTML)
            var plainText = $editor.text();
            
            // Replace the content with clean paragraph structure
            // Split by double newlines or multiple spaces to detect paragraphs
            var paragraphs = plainText.split(/\n\n+|\r\n\r\n+/);
            var cleanHtml = '';
            
            for (var i = 0; i < paragraphs.length; i++) {
                var para = paragraphs[i].trim();
                if (para) {
                    cleanHtml += '<p>' + para.replace(/\n/g, '<br>') + '</p>';
                }
            }
            
            // If no paragraphs detected, just use the plain text
            if (!cleanHtml) {
                cleanHtml = '<p>' + plainText + '</p>';
            }
            
            $editor.html(cleanHtml);
            $hiddenTextarea.val(cleanHtml);
            
            showToast('success', 'Format cleaned successfully');
        });
        
        // Text Color button handler
        var $colorPicker = $('#bd-faq-text-color-picker');
        var $colorBtn = $('#bd-faq-text-color-btn');
        var $colorIndicator = $('#bd-faq-color-indicator');
        var $colorIcon = $colorBtn.find('.fa-font');
        
        // Store selection before opening color picker
        var savedSelection = null;
        
        function saveSelection() {
            if (window.getSelection) {
                var sel = window.getSelection();
                if (sel.rangeCount > 0) {
                    savedSelection = sel.getRangeAt(0);
                }
            }
        }
        
        function restoreSelection() {
            if (savedSelection) {
                if (window.getSelection) {
                    var sel = window.getSelection();
                    sel.removeAllRanges();
                    sel.addRange(savedSelection);
                }
            }
        }
        
        $colorBtn.off('click').on('click', function(e) {
            e.preventDefault();
            saveSelection();
            $colorPicker[0].click();
        });
        
        $colorPicker.off('input change').on('input change', function() {
            var color = $(this).val();
            
            // Update the indicator color
            $colorIndicator.css('background', color);
            $colorIcon.css('color', color);
            
            // Restore selection and apply color
            $editor[0].focus();
            restoreSelection();
            document.execCommand('foreColor', false, color);
            
            // Sync to textarea
            $hiddenTextarea.val($editor.html());
        });
        
        function updateToolbarState() {
            $toolbar.find('.bd-faq-editor-btn').each(function() {
                var $btn = $(this);
                var command = $btn.data('command');
                try {
                    var isActive = document.queryCommandState(command);
                    $btn.toggleClass('active', isActive);
                } catch(e) {
                }
            });
        }
        
        $editor.on('mouseup keyup', function() {
            updateToolbarState();
        });
        
        function syncToTextarea() {
            var content = $editor.html();
            $hiddenTextarea.val(content);
        }
        
        $editor.on('input', syncToTextarea);
        $editor.on('blur', syncToTextarea);
        
        bdFaqAnswerEditorInitialized = true;
    }

    async function openQuestionModal(questionData) {
        destroyBdFaqAnswerEditor();
        
        // Clear editing state if not editing
        if (!questionData || !questionData.id) {
            window.editingQuestionId = null;
            window.editingQuestionGroupIds = null;
        } else {
            // If editing, fetch question groups BEFORE refreshing groups list
            window.editingQuestionId = questionData.id;
            try {
                const groupResponse = await sendAjax('get_question_groups', {
                    question_id: questionData.id
                }, null, true);
                
                if (groupResponse && groupResponse.status === 'success' && groupResponse.group_ids) {
                    window.editingQuestionGroupIds = groupResponse.group_ids;
                } else {
                    window.editingQuestionGroupIds = [];
                }
            } catch (error) {
                console.error('Error fetching question groups:', error);
                window.editingQuestionGroupIds = [];
            }
        }
        
        // Refresh groups list to ensure latest groups are available
        // This will use window.editingQuestionGroupIds if set
        await refreshGroupsDropdowns();
        
        var $form = $('#bd-faq-question-form');
        if ($form.length > 0 && $form[0]) {
            $form[0].reset();
        }
        $('#bd-faq-question-id').val('');
        $('#bd-faq-video-url').val('');

        var storedQuestionData = questionData || null;

        var $answerField = $('#bd-faq-answer-text');
        var $hiddenTextarea = $('#bd-faq-answer-text-hidden');
        if ($answerField.length > 0) {
            $answerField.html('');
            $hiddenTextarea.val('');
        }

        if (storedQuestionData) {
            var setQuestionData = function() {
                var $questionIdField = $('#bd-faq-question-id');
                var $questionTextField = $('#bd-faq-question-text');
                var $videoUrlField = $('#bd-faq-video-url');
                
                if ($questionIdField.length > 0) {
                    $questionIdField.val(storedQuestionData.id || '');
                }
                
                if ($questionTextField.length > 0) {
                    $questionTextField.val(storedQuestionData.question || '');
                }
                
                if ($videoUrlField.length > 0) {
                    $videoUrlField.val(storedQuestionData.video_url || '');
                }
                
                if (storedQuestionData.answer) {
                    if ($answerField.length > 0) {
                        $answerField.html(storedQuestionData.answer);
                    }
                    if ($hiddenTextarea.length > 0) {
                        $hiddenTextarea.val(storedQuestionData.answer);
                    }
                }
                
                // Only clear checkboxes if not editing (editing will set them via refreshQuestionModalGroups)
                if (!window.editingQuestionGroupIds) {
                    $('input[name="group_ids[]"]').prop('checked', false);
                }
            };
            
            setQuestionData();
            setTimeout(setQuestionData, 100);
            setTimeout(setQuestionData, 300);
        } else {
            // Clear checkboxes for new question
            $('input[name="group_ids[]"]').prop('checked', false);
        }

        checkVideoLayoutAndShowField();

        $('#bd-faq-question-modal').off('shown.bs.modal.bdFaqEditor');

        $('#bd-faq-question-modal').modal({
            backdrop: 'static',
            keyboard: false,
            show: true
        });

        var editorInitialized = false;
        var initEditorWithData = function() {
            if (editorInitialized) return;
            editorInitialized = true;
            
            setTimeout(function() {
                if (storedQuestionData) {
                    var $questionIdField = $('#bd-faq-question-id');
                    var $questionTextField = $('#bd-faq-question-text');
                    var $answerField = $('#bd-faq-answer-text');
                    var $hiddenTextarea = $('#bd-faq-answer-text-hidden');
                    
                    if ($questionIdField.length > 0) {
                        $questionIdField.val(storedQuestionData.id || '');
                    }
                    if ($questionTextField.length > 0) {
                        $questionTextField.val(storedQuestionData.question || '');
                    }
                    if (storedQuestionData.answer && $answerField.length > 0) {
                        $answerField.html(storedQuestionData.answer);
                    }
                    if (storedQuestionData.answer && $hiddenTextarea.length > 0) {
                        $hiddenTextarea.val(storedQuestionData.answer);
                    }
                }
                
                initBdFaqAnswerEditor();
                
                if (storedQuestionData && storedQuestionData.answer) {
                    var $editor = $('#bd-faq-answer-text');
                    var $hiddenTextarea = $('#bd-faq-answer-text-hidden');
                    if ($editor.length > 0 && !$editor.html().trim()) {
                        $editor.html(storedQuestionData.answer);
                    }
                    if ($hiddenTextarea.length > 0) {
                        $hiddenTextarea.val(storedQuestionData.answer);
                    }
                }
            }, 200);
        };
        
        $('#bd-faq-question-modal').one('shown.bs.modal.bdFaqEditor', initEditorWithData);
        
        setTimeout(function() {
            if (!editorInitialized && $('#bd-faq-question-modal').is(':visible')) {
                initEditorWithData();
            }
        }, 1000);
    }

    function closeQuestionModal() {
        destroyBdFaqAnswerEditor();
        
        // Clear editing state
        window.editingQuestionId = null;
        window.editingQuestionGroupIds = null;
        
        $('#bd-faq-question-modal').modal('hide');
        $('body').removeClass('modal-open');
        $('.modal-backdrop').remove();
    }

    async function editQuestion(questionData) {
        // Open modal - it will fetch groups internally
        await openQuestionModal(questionData);
        
        // Ensure checkboxes are set after modal is fully rendered
        // (openQuestionModal already fetched and stored groups)
        if (window.editingQuestionGroupIds && Array.isArray(window.editingQuestionGroupIds)) {
            setTimeout(function() {
                window.editingQuestionGroupIds.forEach(function(gid) {
                    var $checkbox = $('#group_' + gid);
                    if ($checkbox.length > 0) {
                        $checkbox.prop('checked', true);
                    }
                });
            }, 200);
        }
    }

    async function checkVideoLayoutAndShowField() {
        try {
            const response = await sendAjax('get_design_setting', {
                setting_key: 'layout_type'
            });
            
            if (response && response.status === 'success' && response.setting_value === 'video-multimedia') {
                $('#bd-faq-video-url-group').show();
            } else {
                $('#bd-faq-video-url-group').hide();
            }
        } catch (error) {
            console.error('Error checking video layout:', error);
            $('#bd-faq-video-url-group').hide();
        }
    }

    function saveQuestion(e) {
        e.preventDefault();
        
        var answerContent = '';
        var $editor = $('#bd-faq-answer-text');
        var $hiddenTextarea = $('#bd-faq-answer-text-hidden');
        
        if ($editor.length > 0) {
            answerContent = $editor.html() || $hiddenTextarea.val() || '';
        } else {
            answerContent = $hiddenTextarea.val() || '';
        }
        
        if (answerContent) {
            answerContent = answerContent.replace(/<p><br><\/p>/g, '').replace(/<p><\/p>/g, '').replace(/<p>\s*<\/p>/g, '').trim();
        }
        
        if (!answerContent || answerContent.trim() === '' || answerContent === '<p><br></p>' || answerContent === '<p></p>') {
            swal('Error!', 'Answer is required. Please enter an answer.', 'error');
            return false;
        }
        
        $hiddenTextarea.val(answerContent);
        
        var state = paginationState.questions || {
            page: 1,
            per_page: 25,
            show_all: false
        };
        var formData = {
            question_id: $('#bd-faq-question-id').val(),
            question: $('#bd-faq-question-text').val(),
            answer: answerContent,
            video_url: $('#bd-faq-video-url').val() || '',
            group_ids: $('input[name="group_ids[]"]:checked').map(function() {
                return $(this).val();
            }).get(),
            page: state.page,
            per_page: state.per_page,
            show_all: state.show_all ? '1' : '0'
        };
        var action = formData.question_id ? 'update_question' : 'save_question';
        sendAjax(action, formData, function(response) {
            closeQuestionModal();

            var search = $('#question-search').val() || '';
            var groupFilter = $('#question-group-filter').val() || '';

            if (search || groupFilter) {
                filterQuestions();
            } else if (response && response.html_questions) {
                $('#container-questions').html(response.html_questions);
            }
        });
    }

    function deleteQuestion(id) {
        swal({
            title: 'Are you sure?',
            text: 'This question will be deleted permanently!',
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            closeOnConfirm: false
        }, function(isConfirm) {
            if (isConfirm) {
                var state = paginationState.questions || {
                    page: 1,
                    per_page: 25,
                    show_all: false
                };
                sendAjax('delete_question', {
                    question_id: id,
                    page: state.page,
                    per_page: state.per_page,
                    show_all: state.show_all ? '1' : '0'
                }, function(response) {
                    var search = $('#question-search').val() || '';
                    var groupFilter = $('#question-group-filter').val() || '';

                    if (search || groupFilter) {
                        filterQuestions();
                    } else if (response && response.html_questions) {
                        $('#container-questions').html(response.html_questions);
                    }
                    swal('Deleted!', 'Question has been deleted.', 'success');
                });
            }
        });
    }

    // ========================================
    // BULK ACTIONS FOR QUESTIONS
    // ========================================
    
    function toggleSelectAllQuestions(checked) {
        $('.question-checkbox').prop('checked', checked);
        updateBulkActionsVisibility();
    }
    
    function updateBulkActionsVisibility() {
        var selectedCount = $('.question-checkbox:checked').length;
        $('#selected-questions-count').text(selectedCount);
        
        if (selectedCount > 0) {
            $('#bulk-actions-questions').slideDown(200);
        } else {
            $('#bulk-actions-questions').slideUp(200);
            $('#select-all-questions').prop('checked', false);
        }
        
        // Update select-all checkbox state
        var totalCheckboxes = $('.question-checkbox').length;
        if (totalCheckboxes > 0 && selectedCount === totalCheckboxes) {
            $('#select-all-questions').prop('checked', true);
        } else {
            $('#select-all-questions').prop('checked', false);
        }
    }
    
    function clearQuestionSelection() {
        $('.question-checkbox').prop('checked', false);
        $('#select-all-questions').prop('checked', false);
        updateBulkActionsVisibility();
    }
    
    // Show/hide group selector based on action type
    $(document).on('change', '#bulk-action-type', function() {
        if ($(this).val() === 'assign') {
            $('#bulk-assign-group').show();
        } else {
            $('#bulk-assign-group').hide();
        }
    });
    
    function executeBulkAction() {
        var action = $('#bulk-action-type').val();
        var selectedIds = [];
        
        $('.question-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });
        
        if (selectedIds.length === 0) {
            showToast('error', 'No questions selected');
            return;
        }
        
        if (!action) {
            showToast('error', 'Please select an action');
            return;
        }
        
        if (action === 'delete') {
            swal({
                title: 'Delete ' + selectedIds.length + ' questions?',
                text: 'This action cannot be undone!',
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete them!',
                closeOnConfirm: false
            }, function(isConfirm) {
                if (isConfirm) {
                    var state = paginationState.questions || { page: 1, per_page: 25, show_all: false };
                    sendAjax('bulk_delete_questions', {
                        question_ids: selectedIds,
                        page: state.page,
                        per_page: state.per_page,
                        show_all: state.show_all ? '1' : '0'
                    }, function(response) {
                        if (response && response.status === 'success') {
                            $('#container-questions').html(response.html_questions);
                            clearQuestionSelection();
                            swal('Deleted!', selectedIds.length + ' questions have been deleted.', 'success');
                        } else {
                            swal('Error!', response.message || 'Failed to delete questions', 'error');
                        }
                    });
                }
            });
        } else if (action === 'assign') {
            var groupId = $('#bulk-assign-group').val();
            if (!groupId) {
                showToast('error', 'Please select a group');
                return;
            }
            
            var groupName = $('#bulk-assign-group option:selected').text();
            swal({
                title: 'Assign to ' + groupName + '?',
                text: selectedIds.length + ' questions will be assigned to this group.',
                type: 'info',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, assign them!',
                closeOnConfirm: false
            }, function(isConfirm) {
                if (isConfirm) {
                    var state = paginationState.questions || { page: 1, per_page: 25, show_all: false };
                    sendAjax('bulk_assign_questions', {
                        question_ids: selectedIds,
                        group_id: groupId,
                        page: state.page,
                        per_page: state.per_page,
                        show_all: state.show_all ? '1' : '0'
                    }, function(response) {
                        if (response && response.status === 'success') {
                            $('#container-questions').html(response.html_questions);
                            clearQuestionSelection();
                            swal('Assigned!', selectedIds.length + ' questions assigned to ' + groupName + '.', 'success');
                        } else {
                            swal('Error!', response.message || 'Failed to assign questions', 'error');
                        }
                    });
                }
            });
        }
    }

    function openGroupModal(groupData) {
        var $form = $('#bd-faq-group-form');
        if ($form.length > 0 && $form[0]) {
            $form[0].reset();
        }
        $('#bd-faq-group-id').val('');
        if (groupData && groupData.id) {
            $('#bd-faq-group-id').val(groupData.id);
            $('#bd-faq-group-name').val(groupData.group_name || '');
            $('#bd-faq-group-slug').val(groupData.group_slug || '');
            $('#bd-faq-group-slug').prop('readonly', true);
            $('#bd-faq-group-modal-label').text('Edit Group');
        } else {
            $('#bd-faq-group-slug').prop('readonly', true);
            $('#bd-faq-group-slug').val('');
            $('#bd-faq-group-modal-label').text('Add Group');
        }
        $('#bd-faq-group-modal').modal({
            backdrop: 'static',
            keyboard: false,
            show: true
        });
    }

    function closeGroupModal() {
        $('#bd-faq-group-modal').modal('hide');
        $('body').removeClass('modal-open');
        $('.modal-backdrop').remove();
    }

    window.switchToPriorityTab = function() {
        var $tab = $('a[data-toggle="tab"][href="#tab-priority"]');
        if ($tab.length) $tab.tab('show');
    };

    window.openIncludeFromGroupModal = function() {
        var targetId = $('#question-group-filter').val() || '0';
        targetId = parseInt(targetId, 10);
        var targetName = $('#question-group-filter option:selected').text() || '';
        var $hint = $('#include-from-group-target-hint');
        var $applyBtn = $('#include-from-group-apply-btn');
        if (targetId <= 0 || targetName.indexOf('Select') !== -1) {
            $hint.html('<i class="fa fa-exclamation-circle"></i> <strong>Select a group first</strong> using the &ldquo;Filter by group&rdquo; dropdown above. That group will receive the questions you add here.');
            $applyBtn.prop('disabled', true);
        } else {
            $hint.html('Adding questions into group: <strong>' + $('<div>').text(targetName).html() + '</strong>');
            $applyBtn.prop('disabled', false);
        }
        $('#include-from-group-source').val('');
        $('#include-from-group-list').html('<p class="text-muted" style="margin: 0; font-size: 13px;">Select a source group and click Load questions.</p>');
        $('#bd-faq-include-from-group-modal').modal({ backdrop: 'static', keyboard: false, show: true });
    };

    window.closeIncludeFromGroupModal = function() {
        $('#bd-faq-include-from-group-modal').modal('hide');
    };

    window.loadIncludeFromGroupQuestions = function() {
        var sourceId = $('#include-from-group-source').val();
        if (!sourceId) {
            showToast('error', 'Select a source group first');
            return;
        }
        var $list = $('#include-from-group-list');
        $list.html('<p class="text-muted"><i class="fa fa-spinner fa-spin"></i> Loading…</p>');
        sendAjax('get_group_questions_list', { group_id: sourceId }, function(response) {
            if (response.status !== 'success' || !response.questions || !response.questions.length) {
                $list.html('<p class="text-muted" style="margin: 0;">No questions in this group.</p>');
                return;
            }
            var html = '<div class="faq-include-checkboxes">';
            response.questions.forEach(function(q) {
                var txt = (q.question || '').substring(0, 120);
                if ((q.question || '').length > 120) txt += '…';
                html += '<label class="faq-include-check-item"><input type="checkbox" class="include-q-check" value="' + q.id + '"> <span>' + $('<div>').text(txt).html() + '</span></label>';
            });
            html += '</div>';
            $list.html(html);
        });
    };

    window.applyIncludeFromGroup = function() {
        var targetId = $('#question-group-filter').val() || '0';
        targetId = parseInt(targetId, 10);
        if (targetId <= 0) {
            showToast('error', 'Select a group in the filter above first');
            return;
        }
        var ids = [];
        $('#include-from-group-list .include-q-check:checked').each(function() {
            ids.push($(this).val());
        });
        if (ids.length === 0) {
            showToast('error', 'Select at least one question');
            return;
        }
        var perPage = $('#questions-per-page').val() || '25';
        var showAll = perPage === 'all' ? '1' : '0';
        sendAjax('include_questions_from_group', {
            target_group_id: targetId,
            question_ids: ids,
            page: 1,
            per_page: perPage,
            show_all: showAll
        }, function(response) {
            if (response.status === 'success') {
                showToast('success', response.message || 'Questions added');
                if (typeof filterQuestions === 'function') filterQuestions();
                closeIncludeFromGroupModal();
            } else {
                showToast('error', response.message || 'Failed to add questions');
            }
        });
    };

    function editGroup(groupDataStr) {
        try {
            if (!groupDataStr) {
                console.error('Invalid group data: undefined or empty');
                showToast('error', 'Invalid group data. Please try again.');
                return;
            }

            var groupData;
            if (typeof groupDataStr === 'string') {
                groupDataStr = groupDataStr.replace(/^['"]|['"]$/g, '');
                groupData = JSON.parse(groupDataStr);
            } else {
                groupData = groupDataStr;
            }

            if (groupData && groupData.id) {
                openGroupModal(groupData);
            } else {
                console.error('Invalid group data:', groupData, 'Original:', groupDataStr);
                showToast('error', 'Invalid group data. Please try again.');
            }
        } catch (e) {
            console.error('Error parsing group data:', e, 'Original string:', groupDataStr);
            showToast('error', 'Failed to load');
        }
    }

    window.generateGroupSlug = function() {
        var groupId = $('#bd-faq-group-id').val();
        var groupName = $('#bd-faq-group-name').val();
        var $slugField = $('#bd-faq-group-slug');
        
        // Only auto-generate slug if it's a new group (no ID) and slug field is empty or matches old name
        if (!groupId && groupName) {
            var generatedSlug = groupName.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
            $slugField.val(generatedSlug);
        }
    };

    function saveGroup(e) {
        e.preventDefault();
        var groupId = $('#bd-faq-group-id').val();
        var groupName = $('#bd-faq-group-name').val();
        var groupSlug = $('#bd-faq-group-slug').val();

        // Ensure slug is generated if missing
        if (!groupId && !groupSlug && groupName) {
            groupSlug = groupName.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
            $('#bd-faq-group-slug').val(groupSlug);
        }

        var state = paginationState.groups || {
            page: 1,
            per_page: 25,
            show_all: false
        };
        var formData = {
            group_id: groupId,
            group_name: groupName,
            group_slug: groupSlug || $('#bd-faq-group-slug').val(),
            page: state.page,
            per_page: state.per_page,
            show_all: state.show_all ? '1' : '0'
        };

        // Optimistic UI update - close modal immediately for faster UX
        closeGroupModal();

        (async function() {
            try {
                const response = await sendAjax('save_group', formData);
                
                if (response && response.status === 'success') {
                    // Update UI immediately
                    if (response.html_groups) {
                        $('#container-groups').html(response.html_groups);
                    } else {
                        // Use setTimeout to ensure smooth update
                        setTimeout(function() {
                            filterGroups();
                        }, 50);
                    }
                    
                    // Refresh dropdowns and wait for completion before allowing filtering
                    refreshGroupsDropdowns().catch(function() {
                        // Retry once after a short delay
                        setTimeout(function() {
                            refreshGroupsDropdowns();
                        }, 500);
                    });
                    
                    showToast('success', 'Group saved');
                } else {
                    showToast('error', 'Failed to save group');
                    // Reopen modal on error
                    setTimeout(function() {
                        openGroupModal({ id: groupId, group_name: groupName, group_slug: groupSlug });
                    }, 300);
                }
            } catch (error) {
                console.error('Error saving group:', error);
                showToast('error', 'Failed to save group. Please try again.');
                // Reopen modal on error
                setTimeout(function() {
                    openGroupModal({ id: groupId, group_name: groupName, group_slug: groupSlug });
                }, 300);
            }
        })();
    }

    async function refreshGroupsDropdowns() {
        try {
            const response = await sendAjax('get_all_groups', {});
            
            if (response && response.status === 'success' && response.groups) {
                var $questionFilter = $('#question-group-filter');
                var selectedValue = $questionFilter.val();

                $questionFilter.empty();
                $questionFilter.append('<option value="0">-- Select group to filter --</option>');

                response.groups.forEach(function(group) {
                    $questionFilter.append('<option value="' + group.id + '">' +
                        $('<div>').text(group.group_name).html() + '</option>');
                });

                if (selectedValue) {
                    $questionFilter.val(selectedValue);
                }

                refreshQuestionModalGroups(response.groups);
            }
        } catch (error) {
            console.error('Error refreshing groups dropdowns:', error);
        }
    }

    function refreshQuestionModalGroups(groups) {
        // Exclude "Unassigned" from assignable list - questions go there when no group is selected
        var assignableGroups = groups.filter(function(g) {
            return !g.group_slug || g.group_slug !== 'unassigned';
        });

        // Try multiple selectors to find the group container
        var $groupContainer = $('#bd-faq-question-modal').find('div[style*="max-height: 180px"], div[style*="max-height: 200px"]');
        if ($groupContainer.length === 0) {
            // Fallback: find by structure - div containing checkboxes with name="group_ids[]"
            $groupContainer = $('#bd-faq-question-modal').find('div').has('input[name="group_ids[]"]');
        }
        if ($groupContainer.length > 0) {
            var checkedGroups = [];
            
            // First, preserve currently checked groups
            $groupContainer.find('input[type="checkbox"]:checked').each(function() {
                checkedGroups.push($(this).val());
            });
            
            // If we're editing a question and have stored group IDs, use those instead
            if (window.editingQuestionGroupIds && Array.isArray(window.editingQuestionGroupIds)) {
                checkedGroups = window.editingQuestionGroupIds.map(function(id) {
                    return id.toString();
                });
            }

            $groupContainer.empty();
            assignableGroups.forEach(function(group) {
                var groupIdStr = group.id.toString();
                var isChecked = checkedGroups.indexOf(groupIdStr) !== -1;
                var checkboxHtml = '<div class="form-check group-checkbox-item" style="padding: 6px 10px; border-radius: 6px; transition: all 0.2s ease; border: 1px solid transparent; display: inline-block; margin-right: 12px; margin-bottom: 6px; min-width: 180px; max-width: 250px;">' +
                    '<input class="form-check-input" type="checkbox" name="group_ids[]" value="' + group.id + '" id="group_' + group.id + '"' +
                    (isChecked ? ' checked' : '') + ' style="cursor: pointer; width: 16px; height: 16px; margin-right: 8px; margin-top: 2px; float: left;">' +
                    '<label class="form-check-label" for="group_' + group.id + '" style="cursor: pointer; font-weight: 500; font-size: 13px; display: block; margin-left: 24px;">' +
                    $('<div>').text(group.group_name).html() + '</label></div>';
                $groupContainer.append(checkboxHtml);
            });
        }

        refreshAssignmentModalGroups(groups);
    }

    function refreshAssignmentModalGroups(groups) {
        var $assignmentGroupSelects = $('#assignmentModal').find('select[name="group_ids[]"], select[id*="group"]');
        $assignmentGroupSelects.each(function() {
            var $select = $(this);
            var selectedValues = $select.val() || [];
            if (!Array.isArray(selectedValues)) {
                selectedValues = [selectedValues];
            }

            $select.empty();
            groups.forEach(function(group) {
                var isSelected = selectedValues.indexOf(group.id.toString()) !== -1 ||
                    selectedValues.indexOf(group.id) !== -1;
                $select.append('<option value="' + group.id + '"' +
                    (isSelected ? ' selected' : '') + '>' +
                    $('<div>').text(group.group_name).html() + '</option>');
            });
        });
    }

    function copySlug(slug) {
        var tempInput = document.createElement('input');
        tempInput.value = slug;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand('copy');
        document.body.removeChild(tempInput);
        showToast('success', 'Shortcode copied to clipboard!');
    }

    function filterGroups() {
        var search = $('#group-search').val();
        var showSystem = $('#show-system-groups').is(':checked') ? 1 : 0;
        paginationState.groups.page = 1;

        var state = paginationState.groups || {
            page: 1,
            per_page: 25,
            show_all: false
        };
        
        // Show search loader if searching
        if (search) {
            showSearchLoader('group-search');
        }
        
        sendAjax('get_filtered_groups', {
            search: search,
            show_system: showSystem,
            page: state.page,
            per_page: state.per_page,
            show_all: state.show_all ? '1' : '0'
        }, function(response) {
            hideSearchLoader('group-search');
            if (response && response.status === 'success') {
                if (response.html_groups) {
                    $('#container-groups').html(response.html_groups);
                } else {
                    refreshTable('groups');
                }
            } else {
                console.error('Failed to filter groups:', response);
                if (response && response.message) {
                    showToast('error', response.message);
                } else {
                    showToast('error', 'Failed to filter groups. Please try again.');
                }
            }
        });
    }

    // Debounced search for groups
    function debouncedFilterGroups() {
        debounceSearch('group-search', function() {
            filterGroups();
        }, 400);
    }

    function showQuestionsSelectGroupPlaceholder() {
        var html = '<div class="faq-questions-select-group-placeholder">' +
            '<div class="faq-questions-placeholder-icon"><i class="fa fa-filter"></i></div>' +
            '<p class="faq-questions-placeholder-title">Select a group to view questions</p>' +
            '<p class="faq-questions-placeholder-text">Use the <strong>Filter by group</strong> dropdown above to choose a group. Questions for that group will appear here.</p>' +
            '</div>';
        $('#container-questions').html(html);
    }

    function filterQuestions() {
        var search = $('#question-search').val();
        var groupFilter = $('#question-group-filter').val();
        paginationState.questions.page = 1;

        // Require a group to be selected before showing any results
        if (!groupFilter || groupFilter === '0') {
            hideSearchLoader('question-search');
            showQuestionsSelectGroupPlaceholder();
            return;
        }

        var state = paginationState.questions || {
            page: 1,
            per_page: 25,
            show_all: false
        };
        
        if (search) {
            showSearchLoader('question-search');
        }
        
        var $groupFilter = $('#question-group-filter');
        var optionExists = $groupFilter.find('option[value="' + groupFilter + '"]').length > 0;
        if (!optionExists) {
            hideSearchLoader('question-search');
            setTimeout(function() {
                filterQuestions();
            }, 300);
            return;
        }
        
        sendAjax('get_filtered_questions', {
            search: search,
            group_filter: groupFilter,
            page: state.page,
            per_page: state.per_page,
            show_all: state.show_all ? '1' : '0'
        }, function(response) {
            hideSearchLoader('question-search');
            if (response && response.status === 'success' && response.html_questions) {
                $('#container-questions').html(response.html_questions);
            } else if (response && response.status === 'error') {
                console.error('Error filtering questions:', response.message);
                if (response.message && (response.message.includes('group') || response.message.includes('invalid'))) {
                    refreshGroupsDropdowns().then(function() {
                        setTimeout(function() {
                            filterQuestions();
                        }, 200);
                    });
                }
            }
        });
    }

    // Debounced search for questions
    function debouncedFilterQuestions() {
        debounceSearch('question-search', function() {
            filterQuestions();
        }, 400);
    }

    function filterAssignments() {
        var pageFilter = $('#assignment-page-filter').val();
        var groupFilter = $('#assignment-group-filter').val();
        paginationState.assignments.page = 1;

        var state = paginationState.assignments || {
            page: 1,
            per_page: 25,
            show_all: false
        };

        sendAjax('get_filtered_assignments', {
            page_filter: pageFilter,
            group_filter: groupFilter,
            page: state.page,
            per_page: state.per_page,
            show_all: state.show_all ? '1' : '0'
        }, function(response) {
            if (response && response.status === 'success' && response.html_assignments) {
                $('#container-assignments').html(response.html_assignments);
            }
        });
    }

    function orderPageGroups(pageId, pageName, assignmentType, pageType) {

        if (!pageId || pageId == 0) {
            showToast('error', 'Invalid page ID. Cannot order groups.');
            console.error('Order Page Groups Error: Invalid pageId', pageId);
            return;
        }

        try {
            if (typeof pageName === 'string') {
                pageName = pageName.replace(/^['"]|['"]$/g, '');
                if (pageName.startsWith('{') || pageName.startsWith('[') || pageName.startsWith('"')) {
                    pageName = JSON.parse(pageName);
                }
            }
            if (typeof assignmentType === 'string') {
                assignmentType = assignmentType.replace(/^['"]|['"]$/g, '');
                if (assignmentType.startsWith('{') || assignmentType.startsWith('[') || assignmentType.startsWith('"')) {
                    assignmentType = JSON.parse(assignmentType);
                }
            }
            if (typeof pageType === 'string') {
                pageType = pageType.replace(/^['"]|['"]$/g, '');
                if (pageType.startsWith('{') || pageType.startsWith('[') || pageType.startsWith('"')) {
                    pageType = JSON.parse(pageType);
                }
            }
        } catch (e) {
            // Parameters were not JSON strings, using as-is
        }


        var requestData = {};
        var orderContext = {};

        if (assignmentType == 'post_type' && pageType) {
            var dataId = pageId;
            var postPageType = pageType || 'search_result_page';
            requestData = {
                data_id: dataId,
                page_type: postPageType,
                assignment_type: 'post_type'
            };
            orderContext = {
                dataId: dataId,
                pageType: postPageType,
                type: 'post_type'
            };
        } else {
            requestData = {
                page_id: pageId,
                assignment_type: 'static'
            };
            orderContext = {
                pageId: pageId,
                pageName: pageName,
                type: 'static'
            };
        }

        showLoader();
        sendAjax('get_page_groups_order', requestData, function(response) {
            hideLoader();

            if (!response) {
                showToast('error', 'Failed to load groups. Please try again.');
                console.error('Order Page Groups Error: No response from server');
                return;
            }

            if (response.status === 'error') {
                showToast('error', response.message || 'Failed to load groups for ordering.');
                console.error('Order Page Groups Error:', response.message);
                if (response.sql) {
                    console.error('SQL Query:', response.sql);
                }
                if (response.debug_count !== undefined) {
                    console.error('Debug: Found', response.debug_count, 'assignments but query returned no groups');
                }
                return;
            }

            if (!response.groups || !Array.isArray(response.groups)) {
                showToast('error', 'Invalid groups data received from server.');
                console.error('Order Page Groups Error: Groups is not an array:', response.groups);
                return;
            }

            if (response.groups.length === 0) {
                showToast('warning', 'No groups found for this page. Please assign groups first.');
                return;
            }

            if (response.groups && response.groups.length > 0) {
                var displayPageName = response.page_name || pageName || 'Unknown Page';

                var html = '<div class="alert alert-info"><strong>Order Groups for Page:</strong> ' + displayPageName + '</div>';
                html += '<p class="text-muted" style="font-size: 13px; margin-bottom: 15px;"><i class="fa fa-info-circle"></i> Drag and drop the entire card to reorder groups. The order determines how groups appear on the frontend.</p>';
                html += '<ul id="page-groups-order-list" class="list-group mb-3" style="min-height: 100px;">';
                response.groups.forEach(function(group, index) {
                    var assignmentId = group.assignment_id || group.id || 'N/A';
                    var groupId = group.group_id || 'N/A';
                    var groupName = group.group_name || 'Unknown Group';
                    var currentOrder = group.sort_order || 999;

                    html += '<li class="list-group-item faq-order-item" data-assignment-id="' + assignmentId + '" data-group-id="' + groupId + '" style="cursor: move; user-select: none; padding: 16px; margin-bottom: 8px; border: 1px solid #e5e9f0; border-radius: 8px; background: #ffffff; transition: all 0.2s;">';
                    html += '<div class="d-flex justify-content-between align-items-center">';
                    html += '<div style="flex: 1;">';
                    html += '<div style="margin-bottom: 4px;"><strong style="color: #667eea; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Assignment #' + assignmentId + '</strong> <span style="color: #94a3b8; font-size: 11px;">| Group ID: ' + groupId + '</span></div>';
                    html += '<div><strong style="color: #1e293b; font-size: 14px;">Rank #' + currentOrder + '</strong> - <span class="badge badge-primary" style="background: #667eea; padding: 4px 10px; border-radius: 4px;">' + groupName + '</span></div>';
                    html += '</div>';
                    html += '<i class="fa fa-bars text-muted" style="cursor: grab; margin-left: 16px; font-size: 18px; color: #94a3b8;"></i>';
                    html += '</div></li>';
                });
                html += '</ul>';
                window.currentOrderContext = orderContext;
                
                var footerHtml = '<button type="button" class="faq-plugin-btn faq-plugin-btn-secondary" onclick="closePageOrderModal()">Cancel</button>';
                footerHtml += '<button type="button" class="faq-plugin-btn faq-plugin-btn-primary" onclick="savePageGroupOrder(window.currentOrderContext)">Save Order</button>';

                if ($('#page-order-modal').length === 0) {
                    $('body').append('<div class="modal fade faq-plugin-modal bd-faq-modal" id="page-order-modal" tabindex="-1" role="dialog" aria-labelledby="page-order-modal-label" data-backdrop="static" data-keyboard="false"><div class="faq-plugin-modal-dialog bd-faq-modal-dialog modal-lg" role="document"><div class="faq-plugin-modal-content bd-faq-modal-content"><div class="faq-plugin-modal-header bd-faq-modal-header"><h5 class="faq-plugin-modal-title bd-faq-modal-title" id="page-order-modal-label"><i class="fa fa-sort"></i> Order Groups for Page</h5><button type="button" class="faq-plugin-modal-close bd-faq-modal-close" onclick="closePageOrderModal()" aria-label="Close"><span aria-hidden="true">&times;</span></button></div><div class="faq-plugin-modal-body" id="page-order-content"></div><div class="faq-plugin-modal-footer bd-faq-modal-footer" id="page-order-footer"></div></div></div></div>');
                }
                $('#page-order-content').html(html);
                $('#page-order-footer').html(footerHtml);
                $('#page-order-modal').modal({
                    backdrop: 'static',
                    keyboard: false,
                    show: true
                });

                setTimeout(function() {

                    if (typeof $.fn.sortable !== 'undefined') {
                        $('#page-groups-order-list').sortable({
                            axis: 'y',
                            placeholder: 'list-group-item faq-order-item',
                            tolerance: 'pointer',
                            cursor: 'move',
                            opacity: 0.7,
                            forceHelperSize: true,
                            forcePlaceholderSize: true,
                            start: function(e, ui) {
                                $(ui.item).css('background', '#f0f4ff');
                                $(ui.item).css('border-color', '#667eea');
                            },
                            stop: function(e, ui) {
                                $(ui.item).css('background', '#ffffff');
                                $(ui.item).css('border-color', '#e5e9f0');
                            }
                        });
                    } else {
                        $.getScript('https://code.jquery.com/ui/1.13.2/jquery-ui.min.js', function() {
                            $('#page-groups-order-list').sortable({
                                axis: 'y',
                                placeholder: 'list-group-item faq-order-item',
                                tolerance: 'pointer',
                                cursor: 'move',
                                opacity: 0.7,
                                start: function(e, ui) {
                                    $(ui.item).css('background', '#f0f4ff');
                                    $(ui.item).css('border-color', '#667eea');
                                },
                                stop: function(e, ui) {
                                    $(ui.item).css('background', '#ffffff');
                                    $(ui.item).css('border-color', '#e5e9f0');
                                }
                            });
                        });
                    }
                }, 200);
            }
        });
    }

    function closePageOrderModal() {
        $('#page-order-modal').modal('hide');
    }

    function savePageGroupOrder(orderContext) {

        if (!orderContext) {
            showToast('error', 'Invalid order context. Please try again.');
            console.error('Save Page Group Order Error: Missing orderContext');
            return;
        }

        var orders = [];
        $('#page-groups-order-list li').each(function(index) {
            var groupId = $(this).data('group-id');
            var assignmentId = $(this).data('assignment-id');
            if (groupId && assignmentId) {
                orders.push({
                    assignment_id: assignmentId,
                    group_id: groupId,
                    sort_order: index + 1
                });
            }
        });

        if (orders.length === 0) {
            showToast('error', 'No groups found to save. Please try again.');
            return;
        }

        var requestData = {
            orders: orders
        };
        if (orderContext.type == 'post_type') {
            requestData.data_id = orderContext.dataId;
            requestData.page_type = orderContext.pageType;
            requestData.assignment_type = 'post_type';
        } else {
            requestData.page_id = orderContext.pageId;
            requestData.assignment_type = 'static';
        }


        showLoader();
        sendAjax('update_group_order', requestData, function(response) {
            hideLoader();

            if (response && response.status === 'success') {
                $('#page-order-modal').modal('hide');
                showToast('success', 'Group order saved successfully!');
                filterAssignments();
            } else {
                showToast('error', response.message || 'Failed to save group order. Please try again.');
            }
        }, function(error) {
            hideLoader();
            showToast('error', 'Failed to save group order. Please check your connection and try again.');
            console.error('Save Page Group Order AJAX Error:', error);
        });
    }

    function deleteGroup(id) {
        swal({
            title: 'Are you sure?',
            text: 'This group will be deleted and all question assignments will be removed!',
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            closeOnConfirm: false
        }, function(isConfirm) {
            if (isConfirm) {
                var state = paginationState.groups || {
                    page: 1,
                    per_page: 25,
                    show_all: false
                };
                sendAjax('delete_group', {
                    group_id: id,
                    page: state.page,
                    per_page: state.per_page,
                    show_all: state.show_all ? '1' : '0'
                }, function(response) {
                    if (response && response.status === 'success') {
                        if (response.html_groups) {
                            $('#container-groups').html(response.html_groups);
                        } else {
                            filterGroups();
                        }
                        refreshGroupsDropdowns();
                        swal('Deleted!', response.message || 'Group has been deleted.', 'success');
                    } else {
                        swal('Error!', response.message || 'Failed to delete group.', 'error');
                    }
                });
            }
        });
    }

    // ========================================
    // BULK ACTIONS FOR GROUPS
    // ========================================
    
    function toggleSelectAllGroups(checked) {
        // Only select non-disabled checkboxes (non-system groups)
        $('.group-checkbox:not(:disabled)').prop('checked', checked);
        updateBulkGroupsVisibility();
    }
    
    function updateBulkGroupsVisibility() {
        var selectedCount = $('.group-checkbox:checked:not(:disabled)').length;
        $('#selected-groups-count').text(selectedCount);
        
        if (selectedCount > 0) {
            $('#bulk-actions-groups').slideDown(200);
        } else {
            $('#bulk-actions-groups').slideUp(200);
            $('#select-all-groups').prop('checked', false);
        }
        
        // Update select-all checkbox state (only for non-disabled checkboxes)
        var totalCheckboxes = $('.group-checkbox:not(:disabled)').length;
        if (totalCheckboxes > 0 && selectedCount === totalCheckboxes) {
            $('#select-all-groups').prop('checked', true);
        } else {
            $('#select-all-groups').prop('checked', false);
        }
    }
    
    function clearGroupSelection() {
        $('.group-checkbox').prop('checked', false);
        $('#select-all-groups').prop('checked', false);
        updateBulkGroupsVisibility();
    }
    
    function executeBulkDeleteGroups() {
        var selectedIds = [];
        
        $('.group-checkbox:checked:not(:disabled)').each(function() {
            selectedIds.push($(this).val());
        });
        
        if (selectedIds.length === 0) {
            showToast('error', 'No groups selected');
            return;
        }
        
        swal({
            title: 'Delete ' + selectedIds.length + ' groups?',
            text: 'All question assignments for these groups will be removed. This action cannot be undone!',
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete them!',
            closeOnConfirm: false
        }, function(isConfirm) {
            if (isConfirm) {
                var state = paginationState.groups || { page: 1, per_page: 25, show_all: false };
                sendAjax('bulk_delete_groups', {
                    group_ids: selectedIds,
                    page: state.page,
                    per_page: state.per_page,
                    show_all: state.show_all ? '1' : '0'
                }, function(response) {
                    if (response && response.status === 'success') {
                        $('#container-groups').html(response.html_groups);
                        clearGroupSelection();
                        refreshGroupsDropdowns();
                        swal('Deleted!', selectedIds.length + ' groups have been deleted.', 'success');
                    } else {
                        swal('Error!', response.message || 'Failed to delete groups', 'error');
                    }
                });
            }
        });
    }

    function toggleMergeGroupsUI() {
        var mergeEnabled = $('#assignment_merge_groups').is(':checked');
        var assignmentId = $('#assignment_id').val();

        if (mergeEnabled) {
            $('#single-group-selection').hide();
            $('#assignment_group_id').removeAttr('required');
            $('#multiple-group-selection').show();
            $('.assignment-group-checkbox').removeAttr('required');

            if (assignmentId) {
                loadPageAssignedGroups();
            }
        } else {
            $('#multiple-group-selection').hide();
            $('.assignment-group-checkbox').removeAttr('required').prop('checked', false);

            $('.group-checkbox-item').css({
                'border': '2px solid transparent',
                'background': 'transparent'
            });
            $('.group-assigned-badge').hide();

            $('#single-group-selection').show();
            $('#assignment_group_id').attr('required', 'required');
        }
    }

    function loadPageAssignedGroups() {
        var pageType = $('#assignment_page_type_selector').val();
        var pageId = null;
        var dataId = null;
        var postPageType = null;

        if (pageType === 'post_type') {
            dataId = parseInt($('#assignment_data_id').val()) || 0;
            postPageType = $('#assignment_post_page_type').val() || '';
        } else {
            pageId = parseInt($('#assignment_page_id').val()) || 0;
        }

        if ((pageId && pageId > 0) || (dataId && dataId > 0)) {
            var findData = {
                page_id: pageId || 0,
                data_id: dataId || 0,
                page_type: postPageType || ''
            };

            sendAjax('get_page_all_assigned_groups', findData, function(response) {
                if (response && response.status === 'success' && response.group_ids) {
                    $('.assignment-group-checkbox').prop('checked', false);
                    $('.group-checkbox-item').css({
                        'border': '2px solid transparent',
                        'background': 'transparent'
                    });
                    $('.group-assigned-badge').hide();

                    response.group_ids.forEach(function(gid) {
                        var checkbox = $('#assignment_group_' + gid);
                        checkbox.prop('checked', true);

                        var container = checkbox.closest('.group-checkbox-item');
                        container.css({
                            'border': '2px solid #28a745',
                            'background': '#f0fff4'
                        });
                        container.find('.group-assigned-badge').show();
                    });
                }
            });
        }
    }

    function openAssignmentModal(assignmentData) {
        $('#assignmentForm')[0].reset();
        $('#assignment_id').val('');
        $('#assignment_page_type_selector').val('static');
        $('#assignment_show_title').prop('checked', true);
        $('#assignment_merge_groups').prop('checked', false);
        $('.assignment-group-checkbox').prop('checked', false);
        togglePageTypeSelector();
        toggleMergeGroupsUI();

        if (assignmentData) {
            $('#assignment_id').val(assignmentData.id);
            $('#assignment_group_id').val(assignmentData.group_id);
            $('#assignment_custom_label').val(assignmentData.custom_label || '');
            $('#assignment_custom_title').val(assignmentData.custom_title || '');
            $('#assignment_custom_subtitle').val(assignmentData.custom_subtitle || '');
            $('#assignment_cta_title').val(assignmentData.cta_title || '');
            $('#assignment_cta_text').val(assignmentData.cta_text || '');
            $('#assignment_cta_email').val(assignmentData.cta_email || '');
            $('#assignment_show_title').prop('checked', assignmentData.show_title !== undefined ? (assignmentData.show_title == 1 || assignmentData.show_title === true) : true);
            $('#assignment_merge_groups').prop('checked', assignmentData.merge_groups !== undefined ? (assignmentData.merge_groups == 1 || assignmentData.merge_groups === true) : false);
            toggleMergeGroupsUI();

            if (assignmentData.assignment_type == 'post_type' || (assignmentData.data_id !== null && assignmentData.data_id !== undefined && assignmentData.data_id !== '')) {
                $('#assignment_page_type_selector').val('post_type');
                togglePageTypeSelector();
                $('#assignment_data_id').val(assignmentData.data_id !== null && assignmentData.data_id !== undefined ? assignmentData.data_id : '');
                $('#assignment_post_page_type').val(assignmentData.page_type || 'search_result_page');
            } else {
                $('#assignment_page_id').val(assignmentData.page_id !== null && assignmentData.page_id !== undefined ? assignmentData.page_id : '');
            }

            var mergeEnabled = $('#assignment_merge_groups').is(':checked');
            if (mergeEnabled) {
                var pageId = (assignmentData.page_id !== null && assignmentData.page_id !== undefined && assignmentData.page_id !== '') ? parseInt(assignmentData.page_id) : null;
                var dataId = (assignmentData.data_id !== null && assignmentData.data_id !== undefined && assignmentData.data_id !== '') ? parseInt(assignmentData.data_id) : null;
                var pageType = assignmentData.page_type || null;

                if ((pageId !== null && pageId > 0) || (dataId !== null && dataId > 0 && pageType)) {
                    var findData = {
                        page_id: pageId !== null ? pageId : 0,
                        data_id: dataId !== null ? dataId : 0,
                        page_type: pageType || ''
                    };

                    sendAjax('get_page_all_assigned_groups', findData, function(response) {
                        if (response && response.status === 'success' && response.group_ids) {
                            response.group_ids.forEach(function(gid) {
                                $('#assignment_group_' + gid).prop('checked', true);
                            });
                        } else if (response && response.status === 'error') {
                            console.error('Error loading assigned groups:', response.message);
                        }
                    });
                }
            }
        }

        if (assignmentData && assignmentData.merge_groups == 1) {
            setTimeout(function() {
                $('#merge-edit-info').show();
            }, 100);
        } else {
            $('#merge-edit-info').hide();
        }

        loadTemplateSpecificFields(assignmentData);

        $('#assignmentModal').modal({
            backdrop: 'static',
            keyboard: false,
            show: true
        });
    }

    function loadTemplateSpecificFields(assignmentData) {
        var pageId = null;
        var dataId = null;
        var pageType = null;

        if (assignmentData) {
            if (assignmentData.assignment_type == 'post_type' || (assignmentData.data_id !== null && assignmentData.data_id !== undefined && assignmentData.data_id !== '')) {
                dataId = assignmentData.data_id;
                pageType = assignmentData.page_type || 'search_result_page';
            } else {
                pageId = assignmentData.page_id;
            }
        } else {
            var selectedPageType = $('#assignment_page_type_selector').val();
            if (selectedPageType === 'post_type') {
                dataId = parseInt($('#assignment_data_id').val()) || 0;
                pageType = $('#assignment_post_page_type').val() || 'search_result_page';
            } else {
                pageId = parseInt($('#assignment_page_id').val()) || 0;
            }
        }

        var requestData = {
            page_id: pageId || 0,
            data_id: dataId || 0,
            page_type: pageType || ''
        };


        sendAjax('get_design_setting', requestData, function(response) {
            if (response && response.status === 'success') {
                var designPreset = response.design_preset || 'custom';
                var layoutType = response.layout_type || 'accordion';


                updateFieldsForTemplate(designPreset, layoutType);
            } else {

                updateFieldsForTemplate('custom', 'accordion');
            }
        });
    }

    function updateFieldsForTemplate(designPreset, layoutType) {
        var templateConfig = {
            'minimal': {
                name: 'Minimal Design',
                description: 'Clean, minimalist FAQ accordion',
                showLabel: false,
                showTitle: true,
                showSubtitle: true,
                showCTA: false,
                color: '#3b82f6'
            },
            'split': {
                name: 'Split Layout',
                description: 'Two-column design with label, title, subtitle, and CTA box',
                showLabel: true,
                showTitle: true,
                showSubtitle: true,
                showCTA: true,
                color: '#f59e0b'
            },
            'colorful': {
                name: 'Colorful Design',
                description: 'Vibrant design with title and subtitle',
                showLabel: false,
                showTitle: true,
                showSubtitle: true,
                showCTA: false,
                color: '#ec4899'
            },
            'custom': {
                name: 'Custom Layout (' + layoutType.charAt(0).toUpperCase() + layoutType.slice(1) + ')',
                description: 'Flexible design with customizable elements',
                showLabel: false,
                showTitle: true,
                showSubtitle: true,
                showCTA: false,
                color: '#8b5cf6'
            }
        };

        var config = templateConfig[designPreset] || templateConfig['custom'];

        $('#bd-faq-template-info-card').css('background', 'linear-gradient(135deg, ' + config.color + ' 0%, ' + adjustColorBrightness(config.color, -20) + ' 100%)');
        $('#bd-faq-current-template-name').text(config.name);
        $('#bd-faq-template-description').text(config.description);
        $('#bd-faq-template-info-card').slideDown(300);

        if (config.showLabel) {
            $('#label_field_group').slideDown(300);
        } else {
            $('#label_field_group').slideUp(300);
        }

        if (config.showTitle) {
            $('#title_field_group').slideDown(300);
        } else {
            $('#title_field_group').slideUp(300);
        }

        if (config.showSubtitle) {
            $('#bd-faq-subtitle-field-group').slideDown(300);
        } else {
            $('#bd-faq-subtitle-field-group').slideUp(300);
        }

        if (config.showCTA) {
            $('#bd-faq-cta-fields-section').slideDown(300);
        } else {
            $('#bd-faq-cta-fields-section').slideUp(300);
        }

        if (designPreset === 'split') {
            $('#bd-faq-title-help-text').html('<i class="fa fa-info-circle"></i> Large, bold text that appears below the label');
        } else if (designPreset === 'minimal') {
            $('#bd-faq-title-help-text').html('<i class="fa fa-info-circle"></i> Title is not displayed in Minimal template');
        } else {
            $('#bd-faq-title-help-text').html('<i class="fa fa-info-circle"></i> Leave empty to use default "Frequently Asked Questions"');
        }
    }

    function adjustColorBrightness(color, percent) {
        var num = parseInt(color.replace('#', ''), 16);
        var amt = Math.round(2.55 * percent);
        var R = (num >> 16) + amt;
        var G = (num >> 8 & 0x00FF) + amt;
        var B = (num & 0x0000FF) + amt;
        return '#' + (0x1000000 + (R < 255 ? R < 1 ? 0 : R : 255) * 0x10000 +
                (G < 255 ? G < 1 ? 0 : G : 255) * 0x100 +
                (B < 255 ? B < 1 ? 0 : B : 255))
            .toString(16).slice(1);
    }

    function togglePageTypeSelector() {
        var pageType = $('#assignment_page_type_selector').val();
        if (pageType == 'post_type') {
            $('#static_page_group').hide();
            $('#assignment_page_id').removeAttr('required');
            $('#post_type_group').show();
            $('#post_type_page_type_group').show();
            $('#assignment_data_id').attr('required', 'required');
            $('#assignment_post_page_type').attr('required', 'required');
        } else {
            $('#static_page_group').show();
            $('#assignment_page_id').attr('required', 'required');
            $('#post_type_group').hide();
            $('#post_type_page_type_group').hide();
            $('#assignment_data_id').removeAttr('required');
            $('#assignment_post_page_type').removeAttr('required');
        }

        filterAssignedGroups();
    }

    function filterAssignedGroups() {
        var pageId = $('#assignment_page_id').val();
        var dataId = $('#assignment_data_id').val();
        var pageType = $('#assignment_post_page_type').val();
        var assignmentId = $('#assignment_id').val() || 0;


        $('#bd-faq-assignment-group-id option').show().prop('disabled', false);
        $('.assignment-group-checkbox').closest('.form-check').show();
        $('.assignment-group-checkbox').prop('disabled', false);


        if ((pageId || (dataId && pageType)) && !assignmentId) {
            var requestData = {
                page_id: pageId || 0,
                data_id: dataId || 0,
                page_type: pageType || '',
                assignment_id: 0
            };

            sendAjax('get_page_assigned_groups', requestData, function(response) {
                if (response && response.status === 'success' && response.group_ids && response.group_ids.length > 0) {

                    response.group_ids.forEach(function(groupId) {
                        $('#bd-faq-assignment-group-id option[value="' + groupId + '"]').hide();
                    });

                    response.group_ids.forEach(function(groupId) {
                        var $checkbox = $('#assignment_group_' + groupId);
                        $checkbox.closest('.form-check').hide();
                        $checkbox.prop('disabled', true).prop('checked', false);
                    });
                }
            });
        }
    }

    function updatePostTypePageType() {
        var dataId = $('#assignment_data_id').val();
        if (dataId) {
            $('#post_type_page_type_group').show();
        } else {
            $('#post_type_page_type_group').hide();
        }

        if (dataId) {
            setTimeout(function() {
                filterAssignedGroups();
            }, 100);
        }
    }

    function closeAssignmentModal() {
        $('#assignmentModal').modal('hide');
        $('body').removeClass('modal-open');
        $('.modal-backdrop').remove();
    }

    var pageDesignModalScope = { page_id: null, data_id: null, page_type: '', hasOverrides: false, isMergedMode: false };
    var pageDesignCopiedOverrides = null;

    function openPageDesignModal(pageId, dataId, pageType, displayName) {
        pageDesignModalScope.page_id = pageId;
        pageDesignModalScope.data_id = dataId;
        pageDesignModalScope.page_type = (typeof pageType === 'string') ? pageType : (pageType || '');
        pageDesignModalScope.hasOverrides = false;
        pageDesignModalScope.isMergedMode = false;
        $('#pageDesignModal').removeClass('page-design-expanded');
        $('#pageDesignModalLabel').html('<i class="fa fa-paint-brush"></i> Design for this page');
        $("#pageDesignModalPageName").text(displayName || 'Page');
        $("#page_design_override_check").prop('checked', false);
        $("#page_design_form_wrap").css('display', 'none');
        $("#page_design_clear_btn").hide();
        updatePageDesignPreviewSourceBadge();
        setPageDesignTopControlsState(false);
        loadPageDesignOverrideSources();
        detectPageDesignMergedMode();
        loadPageDesignOverrides();
        $('#pageDesignModal').modal('show');
    }

    function closePageDesignModal() {
        $('#pageDesignModal').modal('hide');
        $('body').removeClass('modal-open');
        $('.modal-backdrop').remove();
    }

    function setPageDesignTopControlsState(enabled) {
        $('#page_design_top_meta').css('display', enabled ? 'flex' : 'none');
        $('#page_design_mode_toggle').prop('disabled', !enabled);
        updatePageDesignPreviewSourceBadge();
    }

    function updatePageDesignPreviewSourceBadge() {
        var $badge = $('#page_design_preview_source_badge');
        var $badgePremade = $('#page_design_preview_source_badge_premade');
        if (!$badge.length && !$badgePremade.length) return;
        var label = 'Global';
        var cls = 'faq-plugin-badge-info';
        if (pageDesignModalScope.isMergedMode) {
            label = 'Merged Group Mode';
            cls = 'faq-plugin-badge-primary';
        } else if (pageDesignModalScope.hasOverrides) {
            label = 'Page Override';
            cls = 'faq-plugin-badge-success';
        } else if ($('#page_design_override_check').is(':checked')) {
            label = 'Global (Unsaved Override)';
            cls = 'faq-plugin-badge-secondary';
        }
        [$badge, $badgePremade].forEach(function($el) {
            if (!$el || !$el.length) return;
            $el.removeClass('faq-plugin-badge-info faq-plugin-badge-primary faq-plugin-badge-success faq-plugin-badge-secondary')
                .addClass(cls)
                .text(label);
        });
    }

    function detectPageDesignMergedMode() {
        sendAjax('get_page_merged_groups', {
            page_id: pageDesignModalScope.page_id || 0,
            data_id: pageDesignModalScope.data_id || 0,
            page_type: pageDesignModalScope.page_type || ''
        }, function(resp) {
            var merged = !!(resp && resp.status === 'success' && Array.isArray(resp.group_ids) && resp.group_ids.length);
            pageDesignModalScope.isMergedMode = merged;
            updatePageDesignPreviewSourceBadge();
        }, true);
    }

    function loadPageDesignOverrideSources() {
        var $sel = $('#page_design_copy_source');
        if (!$sel.length) return;
        $sel.html('<option value="">Loading pages…</option>');
        sendAjax('get_page_design_override_sources', {}, function(resp) {
            $sel.html('<option value="">Select page…</option>');
            if (!(resp && resp.status === 'success' && Array.isArray(resp.sources))) return;
            resp.sources.forEach(function(src) {
                if (!src || !src.scope) return;
                var label = src.label || src.scope;
                $sel.append('<option value="' + String(src.scope).replace(/"/g, '&quot;') + '">' + $('<div/>').text(label).html() + '</option>');
            });
        }, true);
    }

    function togglePageDesignForm(checked) {
        $('#pageDesignModal').toggleClass('page-design-expanded', !!checked);
        $('#page_design_form_wrap').css('display', checked ? 'flex' : 'none');
        $('#page_design_clear_btn').toggle(checked && pageDesignModalScope.hasOverrides);
        setPageDesignTopControlsState(!!checked);
        if (checked) {
            if (!pageDesignModalScope.hasOverrides && typeof faqGlobalDesignSettings === 'object') {
                applyGlobalDesignToPageForm(faqGlobalDesignSettings);
            }
            syncPageDesignModeToggleFromPreset();
            togglePageDesignPresetSections($('#page_design_design_preset').val());
            togglePageDesignPreviewMode($('#page_design_design_preset').val());
            ensurePageDesignColorInputsValid();
            togglePageDesignContainerWidthCustom($('#page_design_container_width').val());
            initPageDesignModalAccordion();
            if (typeof window.collapsePageDesignModalAccordion === 'function') window.collapsePageDesignModalAccordion();
            initPageDesignModalSpectrum();
            setTimeout(function() { refreshPageDesignPreview(); }, 100);
        }
        updatePageDesignPreviewSourceBadge();
    }

    function syncPageDesignModeToggleFromPreset() {
        var preset = $('#page_design_design_preset').val() || 'custom';
        var isTemplates = (preset !== 'custom');
        $('#page_design_mode_toggle').prop('checked', isTemplates);
        $('#page_design_toggle_label_custom').css('opacity', isTemplates ? '0.5' : '1');
        $('#page_design_toggle_label_template').css('opacity', isTemplates ? '1' : '0.5');
    }

    function togglePageDesignMode(showTemplates) {
        var preset = $('#page_design_design_preset').val() || 'custom';
        if (showTemplates) {
            if (preset === 'custom' && designConfigData) {
                var firstKey = Object.keys(designConfigData)[0];
                if (firstKey) {
                    $('#page_design_design_preset').val(firstKey);
                    preset = firstKey;
                }
            }
            $('#page_design_toggle_label_custom').css('opacity', '0.5');
            $('#page_design_toggle_label_template').css('opacity', '1');
        } else {
            $('#page_design_design_preset').val('custom');
            preset = 'custom';
            $('#page_design_toggle_label_custom').css('opacity', '1');
            $('#page_design_toggle_label_template').css('opacity', '0.5');
        }
        handlePageDesignPresetChange(preset);
        refreshPageDesignPreviewDebounced();
    }

    function togglePageDesignPreviewMode(preset) {
        var isCustom = (preset === 'custom');
        $('#page_design_preview_custom').toggle(isCustom);
        $('#page_design_preview_premade').toggle(!isCustom);
        $('.faq-page-design-template-card').removeClass('active').filter('[data-design-preset="' + (preset || '') + '"]').addClass('active');
        if (isCustom) setTimeout(function() { refreshPageDesignPreview(); }, 50);
        if (!isCustom && typeof initPageDesignModalSpectrum === 'function') setTimeout(initPageDesignModalSpectrum, 0);
    }

    function getPageDesignPreviewFormData() {
        var data = {};
        var idToKey = {
            'page_design_layout_type': 'layout_type',
            'page_design_title_alignment': 'title_alignment',
            'page_design_font_family': 'font_family',
            'page_design_premade_font_mode': 'premade_font_mode',
            'page_design_template_lock_mode': 'template_lock_mode',
            'page_design_primary_color_text': 'primary_color',
            'page_design_background_color_text': 'background_color',
            'page_design_card_background_color_text': 'card_background_color',
            'page_design_title_text_color_text': 'title_text_color',
            'page_design_question_text_color_text': 'question_text_color',
            'page_design_answer_text_color_text': 'answer_text_color',
            'page_design_text_color_text': 'text_color',
            'page_design_title_font_size': 'title_font_size',
            'page_design_question_font_size': 'question_font_size',
            'page_design_answer_font_size': 'answer_font_size',
            'page_design_container_width': 'container_width',
            'page_design_grid_columns': 'grid_columns',
            'page_design_video_columns': 'video_columns',
            'page_design_card_radius': 'card_radius',
            'page_design_card_padding': 'card_padding',
            'page_design_card_style': 'card_style',
            'page_design_design_preset': 'design_preset'
        };
        for (var id in idToKey) {
            var input = document.getElementById(id);
            if (input && (input.value || input.value === 0)) data[idToKey[id]] = input.value;
        }
        if (data.container_width === 'custom') {
            var customWidthInput = document.getElementById('page_design_container_width_custom');
            data.container_width = (customWidthInput && customWidthInput.value) ? customWidthInput.value : '900';
        }
        return data;
    }

    window.refreshPageDesignPreview = function() {
        var el = document.getElementById('page_design_live_preview_content');
        if (!el) return;
        var customPanel = document.getElementById('page_design_preview_custom');
        if (!customPanel || customPanel.style.display === 'none') return;
        el.classList.add('faq-live-preview-loading');
        el.innerHTML = '<p class="faq-live-preview-placeholder"><i class="fa fa-spinner fa-spin"></i> Loading preview…</p>';
        var previewData = typeof getPageDesignPreviewFormData === 'function' ? getPageDesignPreviewFormData() : {};
        sendAjax('faq_preview_document', previewData, function(response) {
            el.classList.remove('faq-live-preview-loading');
            if (response && response.status === 'success' && response.html) {
                var iframe = el.querySelector('.faq-live-preview-iframe');
                if (!iframe) {
                    iframe = document.createElement('iframe');
                    iframe.className = 'faq-live-preview-iframe';
                    iframe.setAttribute('sandbox', 'allow-same-origin allow-scripts');
                    iframe.title = 'FAQ Live Preview';
                }
                el.innerHTML = '';
                el.appendChild(iframe);
                try {
                    iframe.srcdoc = response.html;
                } catch (err) {
                    el.innerHTML = '<p class="faq-live-preview-placeholder" style="color:#94a3b8;"><i class="fa fa-info-circle"></i> Preview could not be loaded.</p>';
                }
            } else {
                el.innerHTML = '<p class="faq-live-preview-placeholder" style="color:#94a3b8;"><i class="fa fa-info-circle"></i> Preview will appear here.</p>';
            }
        }, true, 0, true);
    };

    var pageDesignPreviewDebounceTimer = null;
    window.refreshPageDesignPreviewDebounced = function(delayMs) {
        if (pageDesignPreviewDebounceTimer) clearTimeout(pageDesignPreviewDebounceTimer);
        pageDesignPreviewDebounceTimer = setTimeout(function() { window.refreshPageDesignPreview(); }, delayMs || 500);
    };

    function initPageDesignModalAccordion() {
        var accordion = document.getElementById('pageDesignCustomAccordion');
        if (!accordion) return;

        function getVisibleSections() {
            return Array.prototype.slice.call(accordion.querySelectorAll('.accordion-section')).filter(function(section) {
                return section.offsetParent !== null;
            });
        }

        function closeSection(section) {
            if (!section) return;
            var content = section.querySelector('.accordion-content');
            var icon = section.querySelector('.expand_module i');
            var header = section.querySelector('.accordion-header');
            if (content) content.classList.remove('is-open');
            if (icon) icon.className = 'fa fa-chevron-down';
            if (header) header.setAttribute('aria-expanded', 'false');
        }

        window.syncPageDesignModalAccordionState = function() {
            Array.prototype.slice.call(accordion.querySelectorAll('.accordion-section')).forEach(function(section) {
                var content = section.querySelector('.accordion-content');
                var icon = section.querySelector('.expand_module i');
                var header = section.querySelector('.accordion-header');
                var visible = section.offsetParent !== null;
                var isOpen = !!(visible && content && content.classList.contains('is-open'));
                if (!visible && content) content.classList.remove('is-open');
                if (icon) icon.className = isOpen ? 'fa fa-chevron-up' : 'fa fa-chevron-down';
                if (header) header.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });
        };

        window.collapsePageDesignModalAccordion = function() {
            getVisibleSections().forEach(function(section) { closeSection(section); });
        };

        if (accordion.dataset.bound === '1') {
            if (typeof window.syncPageDesignModalAccordionState === 'function') window.syncPageDesignModalAccordionState();
            return;
        }
        accordion.dataset.bound = '1';

        accordion.addEventListener('click', function(e) {
            var header = e.target.closest('.accordion-header');
            if (!header || !accordion.contains(header)) return;
            var section = header.closest('.accordion-section');
            var content = section ? section.querySelector('.accordion-content') : null;
            if (!section || !content) return;
            if (content.classList.contains('is-open')) {
                closeSection(section);
            } else {
                getVisibleSections().forEach(function(s) { if (s !== section) closeSection(s); });
                content.classList.add('is-open');
                var icon = section.querySelector('.expand_module i');
                var linkHeader = section.querySelector('.accordion-header');
                if (icon) icon.className = 'fa fa-chevron-up';
                if (linkHeader) linkHeader.setAttribute('aria-expanded', 'true');
            }
        });

        accordion.addEventListener('keydown', function(e) {
            if (e.key !== 'Enter' && e.key !== ' ') return;
            var header = e.target.closest('.accordion-header');
            if (!header || !accordion.contains(header)) return;
            e.preventDefault();
            header.click();
        });

        if (typeof window.syncPageDesignModalAccordionState === 'function') window.syncPageDesignModalAccordionState();
    }

    function initPageDesignModalSpectrum() {
        if (typeof jQuery === 'undefined' || !jQuery.fn.spectrum) return;
        var $ = jQuery;
        var appendToBody = 'body';
        $('#pageDesignModal .faq-spectrum-input').each(function() {
            var $el = $(this);
            if ($el.data('spectrum')) return;
            var isAlpha = $el.hasClass('faq-spectrum-alpha');
            $el.spectrum({
                showInput: true,
                showButtons: true,
                showAlpha: isAlpha,
                preferredFormat: isAlpha ? 'rgb' : 'hex',
                appendTo: appendToBody,
                containerClassName: 'faq-page-design-spectrum-container',
                change: function(c) {
                    if (!c) return;
                    var val;
                    if (isAlpha) {
                        var r = c.toRgb();
                        val = 'rgba(' + r.r + ',' + r.g + ',' + r.b + ',' + (typeof c.alpha === 'number' ? c.alpha : 1) + ')';
                    } else {
                        val = c.toHexString();
                    }
                    $el.val(val);
                    if (typeof window.togglePageDesignDefaultTextColorVisibility === 'function') window.togglePageDesignDefaultTextColorVisibility();
                    if (typeof window.refreshPageDesignPreviewDebounced === 'function') window.refreshPageDesignPreviewDebounced();
                }
            });
        });
        $('#pageDesignModal .page-design-template-bg').each(function() {
            var $el = $(this);
            if ($el.data('spectrum')) return;
            var tk = $el.data('template-key');
            $el.spectrum({
                showInput: true,
                showButtons: true,
                showAlpha: false,
                preferredFormat: 'hex',
                containerClassName: 'faq-page-design-tpl-spectrum',
                replacerClassName: 'faq-page-design-tpl-replacer',
                appendTo: appendToBody,
                show: function() {
                    var inst = $el.data('spectrum');
                    var $replacer = (inst && inst.replacer && inst.replacer.length) ? inst.replacer : $el.siblings('.faq-page-design-tpl-replacer').first();
                    var trigger = ($replacer && $replacer[0]) ? $replacer[0] : null;
                    if (!trigger) return;
                    function positionPicker() {
                        var rect = trigger.getBoundingClientRect();
                        var $c = (inst && inst.container) || $('body .sp-container.faq-page-design-tpl-spectrum').last() || $('body .faq-page-design-tpl-spectrum').last();
                        if (!$c || !$c.length) return false;
                        var viewportW = window.innerWidth || document.documentElement.clientWidth || 0;
                        var viewportH = window.innerHeight || document.documentElement.clientHeight || 0;
                        var pickerW = $c.outerWidth() || 220;
                        var pickerH = $c.outerHeight() || 310;
                        var margin = 8;

                        // Default: show below trigger.
                        var top = rect.bottom + 6;
                        // If it would overflow bottom, flip above trigger.
                        if ((top + pickerH) > (viewportH - margin)) {
                            top = rect.top - pickerH - 6;
                        }
                        // Clamp vertically within viewport.
                        top = Math.max(margin, Math.min(top, viewportH - pickerH - margin));

                        // Start aligned to trigger left, then clamp horizontally.
                        var left = rect.left;
                        if ((left + pickerW) > (viewportW - margin)) {
                            left = viewportW - pickerW - margin;
                        }
                        left = Math.max(margin, left);

                        $c.css({ position: 'fixed', top: top + 'px', left: left + 'px' });
                        return true;
                    }
                    if (!positionPicker()) setTimeout(positionPicker, 0);
                    setTimeout(positionPicker, 50);
                },
                change: function(c) {
                    if (!c) return;
                    var val = c.toHexString();
                    $el.val(val);
                    if (typeof window.refreshPageDesignPreviewDebounced === 'function') window.refreshPageDesignPreviewDebounced();
                }
            });
        });
        $(document).off('click.faqPageDesignCard', '#pageDesignModal .faq-page-design-template-card').on('click.faqPageDesignCard', '#pageDesignModal .faq-page-design-template-card', function(e) {
            if ($(e.target).closest('.faq-template-bg-reset, .faq-page-design-tpl-replacer').length) return;
            var preset = $(this).data('design-preset');
            if (!preset) return;
            $('#page_design_design_preset').val(preset);
            $('#page_design_mode_toggle').prop('checked', true);
            $('#page_design_toggle_label_custom').css('opacity', '0.5');
            $('#page_design_toggle_label_template').css('opacity', '1');
            handlePageDesignPresetChange(preset);
            refreshPageDesignPreviewDebounced();
        });
    }

    function applyGlobalDesignToPageForm(o) {
        if (!o) return;
        $('#page_design_design_preset').val(o.design_preset || 'custom');
        $('#page_design_layout_type').val(o.layout_type || 'accordion');
        $('#page_design_title_alignment').val(o.title_alignment || 'center');
        setPageDesignColor('page_design_primary_color', o.primary_color || '#1e3a8a');
        pageDesignSetBackgroundFromValue(o.background_color || 'rgba(255,255,255,1)');
        setPageDesignColor('page_design_card_background_color', o.card_background_color || '#ffffff');
        setPageDesignColor('page_design_text_color', o.text_color || '#1f2937');
        setPageDesignColor('page_design_title_text_color', o.title_text_color || '#1f2937');
        setPageDesignColor('page_design_question_text_color', o.question_text_color || '#1f2937');
        setPageDesignColor('page_design_answer_text_color', o.answer_text_color || '#1f2937');
        $('#page_design_font_family').val(o.font_family || 'system');
        $('#page_design_premade_font_mode').val(o.premade_font_mode || 'template_default');
        $('#page_design_template_lock_mode').val(o.template_lock_mode || 'flexible');
        $('#page_design_title_font_size').val(o.title_font_size || '32');
        $('#page_design_question_font_size').val(o.question_font_size || '18');
        $('#page_design_answer_font_size').val(o.answer_font_size || '16');
        $('#page_design_question_font_size_active_alias').val(o.question_font_size || '18');
        $('#page_design_question_text_color_text_active_alias').val(o.question_text_color || '#1f2937');
        $('#page_design_card_background_color_text_active_answer_alias').val(o.card_background_color || '#ffffff');
        var cw = o.container_width || '900';
        if (cw === '100%' || cw === '900' || cw === '1100' || cw === '1400') {
            $('#page_design_container_width').val(cw);
            $('#page_design_container_width_custom').val('');
        } else {
            $('#page_design_container_width').val('custom');
            $('#page_design_container_width_custom').val(cw);
        }
        togglePageDesignContainerWidthCustom($('#page_design_container_width').val());
        $('#page_design_card_style').val(o.card_style || 'shadow');
        $('#page_design_grid_columns').val(o.grid_columns || '3');
        $('#page_design_video_columns').val(o.video_columns || '3');
        $('#page_design_card_radius').val(o.card_radius || '12');
        $('#page_design_card_padding').val(o.card_padding || '24');
        $('#page_design_card_icon_url').val(o.card_icon_url || '');
        $('#page_design_card_icon_shape').val(o.card_icon_shape || 'circle');
        togglePageDesignPresetSections(o.design_preset || 'custom');
        togglePageDesignLayoutSections(o.layout_type || 'accordion');
        togglePageDesignDefaultTextColorVisibility();
        if (o.design_preset && o.design_preset !== 'custom' && designConfigData) {
            Object.keys(designConfigData).forEach(function(tk) {
                var defaultBg = (designConfigData[tk] && designConfigData[tk].default_bg) ? designConfigData[tk].default_bg : '#ffffff';
                if (defaultBg.indexOf('#') !== 0) defaultBg = '#' + defaultBg;
                var $spec = $('#page_design_template_bg_' + tk + '_spectrum');
                if ($spec.length) {
                    $spec.val(defaultBg);
                    try { if (typeof $spec.spectrum === 'function') $spec.spectrum('set', defaultBg); } catch (e) {}
                }
            });
        }
    }

    function ensurePageDesignColorInputsValid() {
        var defaults = [
            ['page_design_primary_color_text', '#1e3a8a'],
            ['page_design_card_background_color_text', '#ffffff'],
            ['page_design_text_color_text', '#1f2937'],
            ['page_design_title_text_color_text', '#1f2937'],
            ['page_design_question_text_color_text', '#1f2937'],
            ['page_design_answer_text_color_text', '#1f2937']
        ];
        defaults.forEach(function(pair) {
            var id = pair[0], fallback = pair[1];
            var el = document.getElementById(id);
            if (el && (!el.value || !/^#?[0-9a-fA-F]{6}$/.test(String(el.value).trim()))) {
                el.value = fallback;
                try { if (typeof jQuery !== 'undefined') jQuery(el).spectrum('set', fallback); } catch (e) {}
            }
        });
        var bgText = document.getElementById('page_design_background_color_text');
        if (bgText && (!bgText.value || (bgText.value.indexOf('rgba') !== 0 && !/^#?[0-9a-fA-F]{6}$/.test(String(bgText.value).trim())))) {
            bgText.value = 'rgba(255,255,255,1)';
            try { if (typeof jQuery !== 'undefined') jQuery(bgText).spectrum('set', 'rgba(255,255,255,1)'); } catch (e) {}
        }
    }

    function togglePageDesignPresetSections(preset) {
        var isCustom = (preset === 'custom');
        var $wrap = $('#page_design_form_wrap');
        if (isCustom) {
            $wrap.removeClass('faq-page-design-mode-templates');
            $('.page_design_custom_only').show();
            togglePageDesignLayoutSections($('#page_design_layout_type').val());
            if (typeof window.syncPageDesignModalAccordionState === 'function') window.syncPageDesignModalAccordionState();
        } else {
            $wrap.addClass('faq-page-design-mode-templates');
            $('.page_design_custom_only').hide();
        }
    }

    function togglePageDesignLayoutSections(layoutType) {
        var cardOpts = document.getElementById('page_design_card_options');
        var gridCols = document.getElementById('page_design_grid_columns_wrap');
        var cardIcon = document.getElementById('page_design_card_icon_wrap');
        if (cardOpts) cardOpts.style.display = 'block';
        if (gridCols) gridCols.style.display = (layoutType === 'grid-card') ? 'block' : 'none';
        if (cardIcon) cardIcon.style.display = (layoutType === 'grid-card') ? 'flex' : 'none';
        if (typeof window.syncPageDesignModalAccordionState === 'function') window.syncPageDesignModalAccordionState();
    }

    function togglePageDesignContainerWidthCustom(containerWidthVal) {
        var wrap = document.getElementById('page_design_container_width_custom_wrapper');
        if (wrap) wrap.style.display = (containerWidthVal === 'custom') ? 'block' : 'none';
    }

    function togglePageDesignDefaultTextColorVisibility() {
        var titleEl = document.getElementById('page_design_title_text_color_text');
        var questionEl = document.getElementById('page_design_question_text_color_text');
        var answerEl = document.getElementById('page_design_answer_text_color_text');
        var row = document.getElementById('page_design_default_text_color_row');
        if (!row) return;
        var titleSet = (titleEl && (titleEl.value || '').trim().length > 0);
        var questionSet = (questionEl && (questionEl.value || '').trim().length > 0);
        var answerSet = (answerEl && (answerEl.value || '').trim().length > 0);
        row.style.display = (titleSet && questionSet && answerSet) ? 'none' : 'block';
    }

    function pageDesignHexToRgb(hex) {
        hex = (hex || '').replace(/^#/, '');
        if (hex.length !== 6 || !/^[0-9a-fA-F]{6}$/.test(hex)) return { r: 255, g: 255, b: 255 };
        return { r: parseInt(hex.substr(0, 2), 16), g: parseInt(hex.substr(2, 2), 16), b: parseInt(hex.substr(4, 2), 16) };
    }

    function pageDesignBuildRgba(hex, alphaPercent) {
        var raw = parseInt(alphaPercent, 10);
        var a = (isNaN(raw) ? 100 : Math.max(0, Math.min(100, raw))) / 100;
        var rgb = pageDesignHexToRgb(hex);
        return 'rgba(' + rgb.r + ',' + rgb.g + ',' + rgb.b + ',' + a + ')';
    }

    function pageDesignSetBackgroundFromValue(val) {
        val = (val || '').trim();
        var textEl = document.getElementById('page_design_background_color_text');
        if (!textEl) return;
        var m = val.match(/^rgba?\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*(?:,\s*([\d.]+)\s*)?\)/);
        if (m) {
            textEl.value = val;
            var $el = $(textEl);
            try { if (typeof $el.spectrum === 'function') $el.spectrum('set', val); } catch (e) {}
            return;
        }
        if (/^#?[0-9a-fA-F]{6}$/.test(val)) {
            var hex = val.charAt(0) === '#' ? val : '#' + val;
            var rgba = pageDesignBuildRgba(hex, 100);
            textEl.value = rgba;
            var $el = $(textEl);
            try { if (typeof $el.spectrum === 'function') $el.spectrum('set', rgba); } catch (e) {}
        }
    }

    function handlePageDesignPresetChange(preset) {
        if (!$('#page_design_override_check').is(':checked')) return;
        togglePageDesignPresetSections(preset);
        togglePageDesignPreviewMode(preset);
        togglePageDesignLayoutSections($('#page_design_layout_type').val());
        togglePageDesignContainerWidthCustom($('#page_design_container_width').val());
    }

    function loadPageDesignOverrides(overridePreset) {
        var data = {
            page_id: pageDesignModalScope.page_id || 0,
            data_id: pageDesignModalScope.data_id || 0,
            page_type: pageDesignModalScope.page_type || ''
        };
        sendAjax('get_page_design_overrides', data, function(response) {
            var o = (response && response.status === 'success' && response.overrides) ? response.overrides : {};
            var hasOverrides = Object.keys(o).length > 0 || overridePreset !== undefined;
            if (hasOverrides) {
                pageDesignModalScope.hasOverrides = true;
                $('#page_design_override_check').prop('checked', true);
                $('#page_design_form_wrap').css('display', 'flex');
                $('#page_design_clear_btn').show();
                $('#pageDesignModal').addClass('page-design-expanded');
                setPageDesignTopControlsState(true);
                var preset = overridePreset !== undefined ? overridePreset : (o.design_preset || 'custom');
                $('#page_design_design_preset').val(preset);
                $('#page_design_layout_type').val(o.layout_type || 'accordion');
                $('#page_design_title_alignment').val(o.title_alignment || 'center');
                setPageDesignColor('page_design_primary_color', o.primary_color || '#1e3a8a');
                pageDesignSetBackgroundFromValue(o.background_color || 'rgba(255,255,255,1)');
                setPageDesignColor('page_design_card_background_color', o.card_background_color || '#ffffff');
                setPageDesignColor('page_design_text_color', o.text_color || '#1f2937');
                setPageDesignColor('page_design_title_text_color', o.title_text_color || '#1f2937');
                setPageDesignColor('page_design_question_text_color', o.question_text_color || '#1f2937');
                setPageDesignColor('page_design_answer_text_color', o.answer_text_color || '#1f2937');
                $('#page_design_font_family').val(o.font_family || 'system');
                $('#page_design_premade_font_mode').val(o.premade_font_mode || 'template_default');
                $('#page_design_template_lock_mode').val(o.template_lock_mode || (faqGlobalDesignSettings.template_lock_mode || 'flexible'));
                $('#page_design_title_font_size').val(o.title_font_size || '32');
                $('#page_design_question_font_size').val(o.question_font_size || '18');
                $('#page_design_answer_font_size').val(o.answer_font_size || '16');
                $('#page_design_question_font_size_active_alias').val(o.question_font_size || '18');
                $('#page_design_question_text_color_text_active_alias').val(o.question_text_color || '#1f2937');
                $('#page_design_card_background_color_text_active_answer_alias').val(o.card_background_color || '#ffffff');
                var cw = o.container_width || '900';
                if (cw === '100%' || cw === '900' || cw === '1100' || cw === '1400') {
                    $('#page_design_container_width').val(cw);
                    $('#page_design_container_width_custom').val('');
                } else {
                    $('#page_design_container_width').val('custom');
                    $('#page_design_container_width_custom').val(cw);
                }
                togglePageDesignContainerWidthCustom($('#page_design_container_width').val());
                $('#page_design_card_style').val(o.card_style || 'shadow');
                $('#page_design_grid_columns').val(o.grid_columns || '3');
                $('#page_design_card_radius').val(o.card_radius || '12');
                $('#page_design_card_padding').val(o.card_padding || '24');
                $('#page_design_card_icon_url').val(o.card_icon_url || '');
                $('#page_design_card_icon_shape').val(o.card_icon_shape || 'circle');
                var templateKeys = designConfigData ? Object.keys(designConfigData) : ['minimal', 'split', 'colorful', 'modern', 'simple', 'card', 'classic'];
                templateKeys.forEach(function(tk) {
                    var key = 'template_bg_' + tk;
                    var v = (o[key] === undefined || o[key] === null || o[key] === '') ? '' : (o[key] + '').trim();
                    if (v && v.indexOf('#') !== 0 && /^[0-9a-fA-F]{6}$/.test(v)) v = '#' + v;
                    var defaultBg = (designConfigData && designConfigData[tk] && designConfigData[tk].default_bg) ? designConfigData[tk].default_bg : '#ffffff';
                    if (defaultBg.indexOf('#') !== 0) defaultBg = '#' + defaultBg;
                    var displayVal = (v && v.length) ? v : defaultBg;
                    var $spec = $('#page_design_template_bg_' + tk + '_spectrum');
                    if ($spec.length) {
                        $spec.val(displayVal);
                        try { if (typeof $spec.spectrum === 'function') $spec.spectrum('set', displayVal); } catch (e) {}
                    }
                });
                syncPageDesignModeToggleFromPreset();
                togglePageDesignPresetSections(preset);
                togglePageDesignPreviewMode(preset);
                togglePageDesignLayoutSections(o.layout_type || 'accordion');
                togglePageDesignDefaultTextColorVisibility();
                initPageDesignModalSpectrum();
                setTimeout(function() { refreshPageDesignPreview(); }, 150);
                updatePageDesignPreviewSourceBadge();
            } else {
                pageDesignModalScope.hasOverrides = false;
                $('#page_design_override_check').prop('checked', false);
                $('#page_design_form_wrap').css('display', 'none');
                $('#page_design_clear_btn').hide();
                $('#pageDesignModal').removeClass('page-design-expanded');
                setPageDesignTopControlsState(false);
                updatePageDesignPreviewSourceBadge();
            }
        }, true);
    }

    function setPageDesignColor(prefix, hex) {
        hex = (hex && hex.indexOf('#') === 0) ? hex : ('#' + (hex || '000000'));
        var textId = prefix + '_text';
        var $textEl = $('#' + textId);
        if ($textEl.length) {
            $textEl.val(hex);
            try { if (typeof $textEl.spectrum === 'function') $textEl.spectrum('set', hex); } catch (e) {}
        }
    }

    window.copyPageDesignFromSelected = function() {
        var scope = $('#page_design_copy_source').val();
        if (!scope) {
            showToast('error', 'Select a page source first.');
            return;
        }
        sendAjax('get_page_design_overrides_by_scope', { scope: scope }, function(resp) {
            if (!(resp && resp.status === 'success' && resp.overrides)) {
                showToast('error', (resp && resp.message) ? resp.message : 'Unable to copy overrides.');
                return;
            }
            pageDesignCopiedOverrides = resp.overrides;
            showToast('success', 'Design copied from selected page.');
        }, true);
    };

    window.pastePageDesignOverrides = function() {
        if (!pageDesignCopiedOverrides || typeof pageDesignCopiedOverrides !== 'object') {
            showToast('error', 'No copied overrides found. Copy from a source page first.');
            return;
        }
        pageDesignModalScope.hasOverrides = true;
        $('#page_design_override_check').prop('checked', true);
        $('#page_design_form_wrap').css('display', 'flex');
        $('#page_design_clear_btn').show();
        $('#pageDesignModal').addClass('page-design-expanded');
        setPageDesignTopControlsState(true);
        applyGlobalDesignToPageForm(pageDesignCopiedOverrides);
        var preset = pageDesignCopiedOverrides.design_preset || 'custom';
        $('#page_design_design_preset').val(preset);
        handlePageDesignPresetChange(preset);
        refreshPageDesignPreviewDebounced(50);
        updatePageDesignPreviewSourceBadge();
        showToast('success', 'Copied overrides pasted. Save to apply.');
    };

    window.resetPageTemplateColorsLocal = function() {
        var defaults = getTemplateBgDefaults();
        Object.keys(defaults).forEach(function(tk) {
            var hex = defaults[tk];
            var $spec = $('#page_design_template_bg_' + tk + '_spectrum');
            if ($spec.length) {
                $spec.val(hex);
                try { if (typeof $spec.spectrum === 'function') $spec.spectrum('set', hex); } catch (e) {}
            }
        });
        refreshPageDesignPreviewDebounced(50);
    };

    window.resetPageTypographyLocal = function() {
        $('#page_design_premade_font_mode').val('template_default');
        $('#page_design_font_family').val('system');
        refreshPageDesignPreviewDebounced(50);
    };

    $(document).on('click', '.page_design_template_bg_clear', function() {
        var tk = $(this).data('template');
        var defaultHex = (designConfigData && designConfigData[tk] && designConfigData[tk].default_bg) ? designConfigData[tk].default_bg : '#ffffff';
        if (defaultHex.indexOf('#') !== 0) defaultHex = '#' + defaultHex;
        var $spec = $('#page_design_template_bg_' + tk + '_spectrum');
        if ($spec.length) {
            $spec.val(defaultHex);
            try { if (typeof $spec.spectrum === 'function') $spec.spectrum('set', defaultHex); } catch (e) {}
        }
        if (typeof window.refreshPageDesignPreviewDebounced === 'function') window.refreshPageDesignPreviewDebounced();
    });

    function savePageDesignOverrides() {
        if (!$('#page_design_override_check').is(':checked')) {
            var $saveBtn = $('#page_design_save_btn');
            if ($saveBtn.prop('disabled')) return;
            $saveBtn.prop('disabled', true);
            sendAjax('clear_page_design_overrides', {
                page_id: pageDesignModalScope.page_id || 0,
                data_id: pageDesignModalScope.data_id || 0,
                page_type: pageDesignModalScope.page_type || ''
            }, function(response) {
                $saveBtn.prop('disabled', false);
                if (response && response.status === 'success') {
                    showToast('success', 'Page will use global design.');
                    closePageDesignModal();
                    refreshTable('assignments');
                } else {
                    showToast('error', (response && response.message) ? response.message : 'Failed to clear overrides.');
                }
            }, true, 0, false);
            return;
        }
        var $saveBtn = $('#page_design_save_btn');
        if ($saveBtn.prop('disabled')) return;
        $saveBtn.prop('disabled', true);

        var cwVal = $('#page_design_container_width').val();
        if (cwVal === 'custom') cwVal = $('#page_design_container_width_custom').val() || '900';
        function ensureHex(textId, fallback) {
            var v = ($('#' + textId).val() || '').trim();
            if (!v || !/^#?[0-9a-fA-F]{6}$/.test(v)) return fallback || '#000000';
            return v.charAt(0) === '#' ? v : '#' + v;
        }
        var settings = {};
        function set(key, val) {
            val = (val === undefined || val === null) ? '' : String(val);
            if (val.trim() === '') return;
            if (key !== 'background_color' && (key.indexOf('color') !== -1 || key.indexOf('template_bg') !== -1) && val && val.indexOf('rgba') !== 0 && val.indexOf('#') !== 0 && /^[0-9a-fA-F]{6}$/.test(val)) val = '#' + val;
            settings[key] = val;
        }
        set('layout_type', $('#page_design_layout_type').val());
        set('design_preset', $('#page_design_design_preset').val());
        set('title_alignment', $('#page_design_title_alignment').val());
        set('primary_color', ensureHex('page_design_primary_color_text', '#1e3a8a'));
        set('background_color', faqNormalizeOpacityColor(($('#page_design_background_color_text').val() || '').trim() || 'rgba(255,255,255,1)'));
        set('card_background_color', ensureHex('page_design_card_background_color_text', '#ffffff'));
        set('text_color', ensureHex('page_design_text_color_text', '#1f2937'));
        set('title_text_color', ensureHex('page_design_title_text_color_text', '#1f2937'));
        set('question_text_color', ensureHex('page_design_question_text_color_text', '#1f2937'));
        set('answer_text_color', ensureHex('page_design_answer_text_color_text', '#1f2937'));
        set('font_family', $('#page_design_font_family').val());
        set('premade_font_mode', $('#page_design_premade_font_mode').val());
        set('template_lock_mode', $('#page_design_template_lock_mode').val() || 'flexible');
        set('title_font_size', $('#page_design_title_font_size').val());
        set('question_font_size', $('#page_design_question_font_size').val());
        set('answer_font_size', $('#page_design_answer_font_size').val());
        set('container_width', cwVal);
        set('card_style', $('#page_design_card_style').val());
        set('grid_columns', $('#page_design_grid_columns').val());
        set('video_columns', $('#page_design_video_columns').val());
        set('card_radius', $('#page_design_card_radius').val());
        set('card_padding', $('#page_design_card_padding').val());
        set('card_icon_url', $('#page_design_card_icon_url').val());
        set('card_icon_shape', $('#page_design_card_icon_shape').val());
        $('#pageDesignModal [id^="page_design_template_bg_"][id$="_spectrum"]').each(function() {
            var id = this.id;
            var tk = id.replace('page_design_template_bg_', '').replace('_spectrum', '');
            var v = ($(this).val() || '').trim();
            if (v && v.indexOf('#') !== 0 && /^[0-9a-fA-F]{6}$/.test(v)) v = '#' + v;
            set('template_bg_' + tk, (v && /^#?[0-9a-fA-F]{6}$/.test(v.replace('#', ''))) ? (v.charAt(0) === '#' ? v : '#' + v) : '');
        });

        var selectedPreset = $('#page_design_design_preset').val() || 'custom';
        var lockMode = $('#page_design_template_lock_mode').val() || 'flexible';
        if (selectedPreset !== 'custom' && lockMode === 'strict') {
            var strictSettings = {
                design_preset: selectedPreset,
                premade_font_mode: settings.premade_font_mode || 'template_default',
                template_lock_mode: 'strict'
            };
            Object.keys(settings).forEach(function(key) {
                if (key.indexOf('template_bg_') === 0) strictSettings[key] = settings[key];
            });
            settings = strictSettings;
        }

        var requestScope = {
            page_id: pageDesignModalScope.page_id || 0,
            data_id: pageDesignModalScope.data_id || 0,
            page_type: pageDesignModalScope.page_type || ''
        };
        function runBatchSave() {
            sendAjax('save_page_design_overrides_batch', {
                page_id: requestScope.page_id,
                data_id: requestScope.data_id,
                page_type: requestScope.page_type,
                settings: JSON.stringify(settings)
            }, function(resp) {
                $saveBtn.prop('disabled', false);
                if (resp && resp.status === 'success') {
                    showToast('success', 'Page design overrides saved.');
                    closePageDesignModal();
                    refreshTable('assignments');
                } else {
                    showToast('error', (resp && resp.message) ? resp.message : 'Failed to save.');
                }
            }, true, 0, false);
        }

        if (selectedPreset !== 'custom' && lockMode === 'strict') {
            sendAjax('clear_page_design_overrides', requestScope, function(clearResp) {
                if (clearResp && clearResp.status === 'success') runBatchSave();
                else {
                    $saveBtn.prop('disabled', false);
                    showToast('error', (clearResp && clearResp.message) ? clearResp.message : 'Failed to enforce strict template lock.');
                }
            }, true, 0, false);
            return;
        }
        runBatchSave();
    }

    function clearPageDesignOverrides() {
        if (!confirm('Remove all design overrides for this page? It will use the global design again.')) return;
        sendAjax('clear_page_design_overrides', {
            page_id: pageDesignModalScope.page_id || 0,
            data_id: pageDesignModalScope.data_id || 0,
            page_type: pageDesignModalScope.page_type || ''
        }, function(response) {
            if (response && response.status === 'success') {
                showToast('success', 'Page design overrides cleared.');
                closePageDesignModal();
                refreshTable('assignments');
            }
        });
    }

    function editAssignment(assignmentData) {
        if (typeof assignmentData === 'string') {
            try {
                assignmentData = JSON.parse(assignmentData);
            } catch (e) {
                showToast('error', 'Invalid assignment data. Please refresh the page and try again.');
                console.error('Failed to parse assignment data:', e);
                return;
            }
        }

        if (!assignmentData || !assignmentData.id) {
            showToast('error', 'Invalid assignment data. Please refresh the page and try again.');
            return;
        }

        openAssignmentModal(assignmentData);
    }

    function saveAssignment(e) {
        e.preventDefault();
        var pageType = $('#assignment_page_type_selector').val();
        var mergeEnabled = $('#assignment_merge_groups').is(':checked');
        var groupIds = [];

        if (mergeEnabled) {
            $('.assignment-group-checkbox:checked').each(function() {
                groupIds.push($(this).val());
            });
            if (groupIds.length === 0) {
                showToast('error', 'Please select at least one group when merge mode is enabled.');
                return;
            }
        } else {
            var singleGroupId = $('#assignment_group_id').val();
            if (!singleGroupId) {
                showToast('error', 'Please select a group.');
                return;
            }
            groupIds = [singleGroupId];
        }

        var baseFormData = {
            assignment_id: $('#assignment_id').val(),
            page_type: pageType,
            custom_label: $('#assignment_custom_label').val(),
            custom_title: $('#assignment_custom_title').val(),
            custom_subtitle: $('#assignment_custom_subtitle').val(),
            cta_title: $('#assignment_cta_title').val(),
            cta_text: $('#assignment_cta_text').val(),
            cta_email: $('#assignment_cta_email').val(),
            show_title: $('#assignment_show_title').is(':checked') ? '1' : '0',
            merge_groups: mergeEnabled ? '1' : '0'
        };

        if (pageType == 'post_type') {
            baseFormData.data_id = $('#assignment_data_id').val();
            baseFormData.post_page_type = $('#assignment_post_page_type').val();
        } else {
            baseFormData.page_id = $('#assignment_page_id').val();
        }

        var assignmentId = $('#assignment_id').val();
        var saveCount = 0;
        var totalGroups = groupIds.length;
        var hasError = false;

        var state = paginationState.assignments || {
            page: 1,
            per_page: 25,
            show_all: false
        };
        var formData = Object.assign({}, baseFormData);
        formData.group_ids = groupIds;
        formData.group_id = groupIds.length > 0 ? groupIds[0] : '';
        formData.page = state.page;
        formData.per_page = state.per_page;
        formData.show_all = state.show_all ? '1' : '0';
        if (assignmentId && !mergeEnabled) {
            formData.assignment_id = assignmentId;
        } else {
            formData.assignment_id = '0';
        }

        sendAjax('save_assignment', formData, function(response) {
            closeAssignmentModal();
            if (response && response.status === 'error') {
                showToast('error', response.message || 'Failed to save assignment. Please try again.');
            } else {
                if (response && response.html_assignments) {
                    $('#container-assignments').html(response.html_assignments);
                }
                showToast('success', mergeEnabled && totalGroups > 1 ?
                    totalGroups + ' groups assigned and merged successfully!' :
                    'Assignment saved successfully!');
            }
        });
    }

    function deleteAssignment(id) {
        swal({
            title: 'Are you sure?',
            text: 'This assignment will be deleted!',
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            closeOnConfirm: false
        }, function(isConfirm) {
            if (isConfirm) {
                var state = paginationState.assignments || {
                    page: 1,
                    per_page: 25,
                    show_all: false
                };
                sendAjax('delete_assignment', {
                    assignment_id: id,
                    page: state.page,
                    per_page: state.per_page,
                    show_all: state.show_all ? '1' : '0'
                }, function(response) {
                    if (response && response.html_assignments) {
                        $('#container-assignments').html(response.html_assignments);
                    }
                    swal('Deleted!', 'Assignment has been deleted.', 'success');
                });
            }
        });
    }

    function loadPriorityQuestions() {
        var groupId = $('#priority-group-select').val();
        if (!groupId) {
            $('#priority-questions-list').html('');
            return;
        }

        if ($('#priority-list').hasClass('ui-sortable')) {
            $('#priority-list').sortable('destroy');
        }

        sendAjax('get_priority_questions', {
            group_id: groupId
        }, function(response) {
            if (response && response.html) {
                $('#priority-questions-list').html(response.html);
                setTimeout(function() {
                    initSortable();
                }, 100);
            } else {
                console.error('No HTML returned from get_priority_questions:', response);
            }
        });
    }

    function initSortable() {
        if (typeof $.fn.sortable === 'undefined') {
            $('<link>').attr({
                rel: 'stylesheet',
                href: 'https://code.jquery.com/ui/1.13.2/themes/ui-lightness/jquery-ui.css'
            }).appendTo('head');

            $.getScript('https://code.jquery.com/ui/1.13.2/jquery-ui.min.js', function() {
                setupSortable();
            });
        } else {
            setupSortable();
        }
    }

    function setupSortable() {
        if ($('#priority-list').hasClass('ui-sortable')) {
            $('#priority-list').sortable('destroy');
        }

        $('#priority-list .faq-plugin-drag-item').off('selectstart dragstart').on('selectstart dragstart', function(e) {
            e.preventDefault();
            return false;
        });

        $('#priority-list').sortable({
            axis: 'y',
            cursor: 'move',
            opacity: 0.85,
            tolerance: 'pointer',
            distance: 5,
            cancel: '',
            helper: function(e, item) {
                var helper = item.clone();
                helper.css({
                    'user-select': 'none',
                    '-webkit-user-select': 'none',
                    '-moz-user-select': 'none',
                    '-ms-user-select': 'none',
                    'cursor': 'move',
                    'box-shadow': '0 12px 32px rgba(102, 126, 234, 0.4)'
                });
                return helper;
            },
            start: function(e, ui) {
                ui.item.css({
                    'user-select': 'none',
                    '-webkit-user-select': 'none',
                    '-moz-user-select': 'none',
                    '-ms-user-select': 'none',
                    'transform': 'rotate(2deg) scale(1.02)'
                });
                ui.placeholder.css({
                    'background-color': '#f8fafc',
                    'border': '2px dashed #667eea',
                    'border-radius': '12px',
                    'height': ui.item.height() + 'px',
                    'visibility': 'visible',
                    'opacity': '0.6'
                });
            },
            stop: function(e, ui) {
                ui.item.css({
                    'user-select': '',
                    '-webkit-user-select': '',
                    '-moz-user-select': '',
                    '-ms-user-select': '',
                    'transform': ''
                });
            },
            update: function(event, ui) {
                var priorities = [];
                $('#priority-list li').each(function(index) {
                    var questionId = $(this).data('question-id');
                    if (questionId) {
                        priorities.push({
                            question_id: questionId,
                            sort_order: index + 1
                        });
                    }
                });

                if (priorities.length === 0) {
                    return;
                }

                var groupId = $('#priority-group-select').val();
                if (!groupId) {
                    console.error('No group selected');
                    return;
                }

                sendAjax('update_priority', {
                    group_id: groupId,
                    priorities: priorities
                }, function(response) {
                    if (response && response.status === 'success') {
                        if (response.html) {
                            $('#priority-questions-list').html(response.html);
                            setTimeout(function() {
                                setupSortable();
                            }, 100);
                        }
                    } else {
                        console.error('Priority update failed:', response);
                        loadPriorityQuestions();
                    }
                });
            }
        });
    }

    $(document).on('input', '#group_name', function() {
        var groupId = $('#group_id').val();
        if (!groupId) {
            var slug = $(this).val().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
            $('#group_slug').val(slug);
        }
    });

    function showToast(icon, message) {
        // Ensure loader is hidden before showing toast
        hideLoader();
        
        // Clean message - remove any boolean values or extra data
        var cleanMessage = String(message || '').trim();
        if (cleanMessage === 'true' || cleanMessage === 'false') {
            cleanMessage = '';
        }
        
        var iconClass = icon === 'success' ? 'success' : (icon === 'error' ? 'error' : 'info');
        swal({
            title: cleanMessage || (icon === 'success' ? 'Success' : 'Error'),
            type: iconClass,
            timer: 2500,
            showConfirmButton: false,
            allowOutsideClick: true,
            customClass: 'faq-toast-small',
            html: false
        });
    }

    if (typeof $.fn.tab === 'undefined' && typeof $.fn.modal === 'undefined') {
        $.getScript('https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js', function() {
        });
    }

    $(document).ready(function() {

        var $tabContainer = $('#profile-tabs');

        function setActiveByVisiblePane() {
            var $visible = $tabContainer.find('.tab-pane.show.active').first();
            if (!$visible.length) {
                $visible = $tabContainer.find('.tab-pane').filter(function() {
                    return $(this).css('display') === 'block' || $(this).is(':visible');
                }).first();
            }
            var paneId = $visible.attr('id');
            if (!paneId) return;

            $tabContainer.find('.main-tabs .nav-item').removeClass('active');
            $tabContainer.find('.main-tabs .nav-link').removeClass('active');
            $tabContainer.find('.main-tabs .nav-link i').removeClass('active');

            var $activeLink = $tabContainer.find('.main-tabs .nav-link[href="#' + paneId + '"]');
            if ($activeLink.length) {
                $activeLink.closest('.nav-item').addClass('active');
                $activeLink.addClass('active');
                $activeLink.find('i').addClass('active');
            }
            var isDesign = (paneId === 'tab-design');
            $tabContainer.toggleClass('faq-design-tab-active', isDesign);
            var $preview = $('.faq-custom-layout-preview');
            $preview.each(function() {
                var el = this;
                if (isDesign) {
                    el.style.removeProperty('display');
                    el.style.removeProperty('visibility');
                    el.style.removeProperty('height');
                    el.style.removeProperty('overflow');
                } else {
                    el.style.setProperty('display', 'none', 'important');
                    el.style.setProperty('visibility', 'hidden', 'important');
                    el.style.setProperty('height', '0', 'important');
                    el.style.setProperty('overflow', 'hidden', 'important');
                }
            });
        }

        function switchTab(tabLink) {
            var target = $(tabLink).attr('href');
            if (!target || target.indexOf('#') !== 0) return;

            $tabContainer.find('.main-tabs .nav-item').removeClass('active');
            $tabContainer.find('.main-tabs .nav-link').removeClass('active');
            $tabContainer.find('.main-tabs .nav-link i').removeClass('active');

            $(tabLink).closest('.nav-item').addClass('active');
            $(tabLink).addClass('active');
            $(tabLink).find('i').addClass('active');

            $tabContainer.find('.tab-pane').each(function() {
                $(this).removeClass('show active').css('display', 'none');
            });

            var $target = $(target);
            if ($target.length) {
                $target.addClass('show active').css('display', 'block');
            }
            var isDesign = (target === '#tab-design');
            $tabContainer.toggleClass('faq-design-tab-active', isDesign);
            var $preview = $('.faq-custom-layout-preview');
            $preview.each(function() {
                var el = this;
                if (isDesign) {
                    el.style.removeProperty('display');
                    el.style.removeProperty('visibility');
                    el.style.removeProperty('height');
                    el.style.removeProperty('overflow');
                } else {
                    el.style.setProperty('display', 'none', 'important');
                    el.style.setProperty('visibility', 'hidden', 'important');
                    el.style.setProperty('height', '0', 'important');
                    el.style.setProperty('overflow', 'hidden', 'important');
                }
            });
        }

        var initBootstrapTabs = function() {
            if (typeof $.fn.tab === 'undefined') {
                setTimeout(initBootstrapTabs, 50);
                return;
            }
            try {
                $tabContainer.find('a[data-toggle="tab"]').tab();
                $tabContainer.find('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
                    var target = $(e.target).attr('href');
                    var isDesign = (target === '#tab-design');
                    $tabContainer.toggleClass('faq-design-tab-active', isDesign);
                    if (target === '#tab-questions' && typeof filterQuestions === 'function') {
                        filterQuestions();
                    }
                    var $preview = $('.faq-custom-layout-preview');
                    $preview.each(function() {
                        var el = this;
                        if (isDesign) {
                            el.style.removeProperty('display');
                            el.style.removeProperty('visibility');
                            el.style.removeProperty('height');
                            el.style.removeProperty('overflow');
                        } else {
                            el.style.setProperty('display', 'none', 'important');
                            el.style.setProperty('visibility', 'hidden', 'important');
                            el.style.setProperty('height', '0', 'important');
                            el.style.setProperty('overflow', 'hidden', 'important');
                        }
                    });
                });
            } catch (e) {
                console.error('FAQ Plugin: Bootstrap tab initialization failed:', e);
            }
        };

        $tabContainer.find('a[data-toggle="tab"]').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            switchTab(this);
            return false;
        });

        initBootstrapTabs();
        setActiveByVisiblePane();
        setTimeout(setActiveByVisiblePane, 350);
        setTimeout(setActiveByVisiblePane, 1000);
        if ($('#container-questions').length && $('#question-group-filter').length && ($('#question-group-filter').val() === '0' || $('#question-group-filter').val() === '')) {
            showQuestionsSelectGroupPlaceholder();
        }

        $('#toggle_advanced_settings_btn').on('click', function(e) {
            e.preventDefault();
            window.toggleAdvancedSettings();
        });

        $('#bd-faq-question-modal, #bd-faq-group-modal, #assignmentModal').on('hidden.bs.modal', function() {
            if ($(this).attr('id') === 'bd-faq-question-modal') {
                destroyBdFaqAnswerEditor();
            }
            $('body').removeClass('modal-open');
            $('.modal-backdrop').remove();
        });
    });
</script>

<?php
if (ob_get_level() && !isset($_POST['bd_faq_ajax'])) {
    ob_end_flush();
}
?>



