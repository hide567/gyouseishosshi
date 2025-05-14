<?php
/**
 * 行政書士試験ブログ（Astra子テーマ）の機能
 *
 * @package 行政書士試験ブログ（Astra子テーマ）
 */

if (!defined('ABSPATH')) {
    exit; // 直接アクセスを禁止
}

/**
 * 親テーマと子テーマのスタイルシートを読み込む
 */
function gyouseishoshi_astra_child_enqueue_styles() {
    // 親テーマのスタイルシート
    wp_enqueue_style(
        'astra-theme-css',
        get_template_directory_uri() . '/style.css',
        array(),
        wp_get_theme('astra')->get('Version')
    );
    
    // 子テーマのスタイルシート
    wp_enqueue_style(
        'gyouseishoshi-astra-child-style',
        get_stylesheet_uri(),
        array('astra-theme-css'),
        wp_get_theme()->get('Version')
    );
    
    // インタラクティブ機能のためのJavaScript
    if (is_user_logged_in()) {
        wp_enqueue_script(
            'progress-tracker-interactive',
            get_stylesheet_directory_uri() . '/js/progress-tracker.js',
            array('jquery'),
            wp_get_theme()->get('Version'),
            true
        );
        
        wp_localize_script('progress-tracker-interactive', 'progress_tracker', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('progress_tracker_nonce')
        ));
    }
}
add_action('wp_enqueue_scripts', 'gyouseishoshi_astra_child_enqueue_styles');

/**
 * ショートコード：注釈ボックス
 */
function note_box_shortcode($atts, $content = null) {
    return '<div class="note-box">' . do_shortcode($content) . '</div>';
}
add_shortcode('note', 'note_box_shortcode');

/**
 * ショートコード：重要ボックス
 */
function important_box_shortcode($atts, $content = null) {
    return '<div class="important-box">' . do_shortcode($content) . '</div>';
}
add_shortcode('important', 'important_box_shortcode');

/**
 * 試験日カウントダウン表示関数
 * 新しいカウントダウン機能で置き換えるため非推奨
 * @deprecated
 */
function gyouseishoshi_exam_countdown() {
    // settings から設定を取得
    $settings = get_option('progress_tracker_settings', array(
        'exam_date' => '2025-11-09',
        'exam_title' => '行政書士試験'
    ));
    
    // 日付設定を使用
    $exam_date = isset($settings['exam_date']) ? strtotime($settings['exam_date']) : strtotime('2025-11-09');
    $exam_title = isset($settings['exam_title']) ? $settings['exam_title'] : '行政書士試験';
    $today = current_time('timestamp');
    
    // 残り日数計算
    $days_left = floor(($exam_date - $today) / (60 * 60 * 24));
    
    // カウントダウンHTML生成
    $countdown_html = '<div class="exam-countdown">';
    $countdown_html .= $exam_title . 'まであと <span class="countdown-number">' . $days_left . '</span> 日';
    $countdown_html .= '</div>';
    
    return $countdown_html;
}

/**
 * カウントダウンをサイトヘッダーに表示
 */
function gyouseishoshi_display_countdown() {
    // 新しい progress_tracker のカウントダウンが有効なら優先して表示
    if (function_exists('progress_tracker_countdown_shortcode')) {
        echo progress_tracker_countdown_shortcode(array());
    } else {
        echo gyouseishoshi_exam_countdown();
    }
}
add_action('astra_header_after', 'gyouseishoshi_display_countdown');

/**
 * カウントダウンショートコード
 */
function gyouseishoshi_countdown_shortcode() {
    // 新しい progress_tracker のカウントダウンが有効なら優先して表示
    if (function_exists('progress_tracker_countdown_shortcode')) {
        return progress_tracker_countdown_shortcode(array());
    }
    return gyouseishoshi_exam_countdown();
}
add_shortcode('exam_countdown', 'gyouseishoshi_countdown_shortcode');

// 行政書士試験勉強カレンダー機能を含める
if (file_exists(get_stylesheet_directory() . '/inc/study-calendar.php')) {
    include_once(get_stylesheet_directory() . '/inc/study-calendar.php');
}

// コミュニティ機能を含める
if (file_exists(get_stylesheet_directory() . '/inc/community-functions.php')) {
    include_once(get_stylesheet_directory() . '/inc/community-functions.php');
}

/**
 * カテゴリーの表示形式をカスタマイズする
 */
function custom_category_count_span($links) {
    $links = str_replace('</a> (', '</a><span class="cat-count-span">(', $links);
    $links = str_replace(')', ')</span>', $links);
    return $links;
}
add_filter('wp_list_categories', 'custom_category_count_span');

/**
 * カテゴリーウィジェットに記事数を強制的に表示
 */
function force_category_count_display($args) {
    // 記事数表示を強制的に有効化
    $args['show_count'] = 1;
    return $args;
}
add_filter('widget_categories_args', 'force_category_count_display');

/**
 * カテゴリー記事数のフォーマットを整える
 */
function custom_category_count_format($output) {
    // 標準の括弧付き数字をカスタム表示に置き換え
    $output = preg_replace('/<\/a> \(([0-9]+)\)/', '</a><span class="cat-count">($1)</span>', $output);
    return $output;
}
add_filter('wp_list_categories', 'custom_category_count_format');

/**
 * 学習進捗管理機能を読み込む
 */
if (file_exists(get_stylesheet_directory() . '/inc/progress-tracker.php')) {
    require_once get_stylesheet_directory() . '/inc/progress-tracker.php';
}

/**
 * Dialog Cards管理機能を読み込む
 */
if (file_exists(get_stylesheet_directory() . '/inc/dialog-cards-manager.php')) {
    require_once get_stylesheet_directory() . '/inc/dialog-cards-manager.php';
}

/**
 * 管理画面の「不明なタスク」問題を修正（Dialog Cards対応版）
 */
function fix_h5p_task_redirect() {
    global $pagenow;
    
    // 管理画面編集ページでパラメーターを確認
    if ($pagenow == 'admin.php' && isset($_GET['page']) && $_GET['page'] == 'h5p' && isset($_GET['task'])) {
        // タスクに基づいてリダイレクト
        $task = sanitize_text_field($_GET['task']);
        
        // 編集タスクが指定されている場合
        if ($task == 'edit' && isset($_GET['id'])) {
            $id = intval($_GET['id']);
            wp_redirect(admin_url("admin.php?page=h5p-content&id={$id}"));
            exit;
        }
        
        // 新規作成タスクの場合
        if ($task == 'new') {
            wp_redirect(admin_url('admin.php?page=h5p-new'));
            exit;
        }
        
        // 表示タスクの場合
        if ($task == 'show' && isset($_GET['id'])) {
            $id = intval($_GET['id']);
            
            // コンテンツタイプを確認し、Dialog Cardsの場合は適切なURLにリダイレクト
            global $wpdb;
            $content_table = $wpdb->prefix . 'h5p_contents';
            $library_table = $wpdb->prefix . 'h5p_libraries';
            
            // コンテンツのライブラリを確認
            $content_library = $wpdb->get_var($wpdb->prepare(
                "SELECT l.name FROM {$library_table} l
                JOIN {$content_table} c ON c.library_id = l.id
                WHERE c.id = %d",
                $id
            ));
            
            if ($content_library == 'H5P.DialogCards' || strpos($content_library, 'DialogCards') !== false) {
                // Dialog Cards用のリダイレクト
                wp_redirect(admin_url("admin.php?page=h5p-content&id={$id}&view=true"));
                exit;
            } else {
                // 通常のリダイレクト
                wp_redirect(admin_url("admin.php?page=h5p-content&id={$id}&view=true"));
                exit;
            }
        }
    }
    
    // Dialog Cardsのアクセス権限問題を修正
    if ($pagenow == 'admin.php' && isset($_GET['page']) && $_GET['page'] == 'h5p-content' && isset($_GET['id']) && isset($_GET['view'])) {
        global $wpdb;
        $content_table = $wpdb->prefix . 'h5p_contents';
        $library_table = $wpdb->prefix . 'h5p_libraries';
        $id = intval($_GET['id']);
        
        // コンテンツのライブラリを確認
        $content_library = $wpdb->get_var($wpdb->prepare(
            "SELECT l.name FROM {$library_table} l
            JOIN {$content_table} c ON c.library_id = l.id
            WHERE c.id = %d",
            $id
        ));
        
        // Dialog Cardsの場合
        if ($content_library == 'H5P.DialogCards' || strpos($content_library, 'DialogCards') !== false) {
            // 表示権限を付与
            add_filter('h5p_alter_user_result', function ($content_id, $user_id) use ($id) {
                if ($content_id == $id) {
                    return (object) array(
                        'user_id' => $user_id,
                        'content_id' => $content_id,
                        'view_access' => true
                    );
                }
                return null;
            }, 10, 2);
        }
    }
}
add_action('admin_init', 'fix_h5p_task_redirect');
// Dialog Cards 管理機能を読み込む
if (file_exists(get_stylesheet_directory() . '/Dialog-Cards.php')) {
    require_once get_stylesheet_directory() . '/Dialog-Cards.php';
} elseif (file_exists(get_stylesheet_directory() . '/inc/Dialog-Cards.php')) {
    require_once get_stylesheet_directory() . '/inc/Dialog-Cards.php';
}
