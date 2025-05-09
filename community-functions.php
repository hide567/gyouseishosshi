<?php
/**
 * コミュニティ機能
 *
 * @package 行政書士試験ブログ（Astra子テーマ）
 */

if (!defined('ABSPATH')) {
    exit; // 直接アクセスを禁止
}

// コミュニティ機能のためのカスタム投稿タイプを登録
function register_community_post_types() {
    // トピック用のカスタム投稿タイプ
    register_post_type('community_topic', array(
        'labels' => array(
            'name' => 'トピック',
            'singular_name' => 'トピック'
        ),
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'author', 'comments'),
        'menu_icon' => 'dashicons-format-chat'
    ));
    
    // 質問用のカスタム投稿タイプ
    register_post_type('community_question', array(
        'labels' => array(
            'name' => '質問',
            'singular_name' => '質問'
        ),
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'author', 'comments'),
        'menu_icon' => 'dashicons-format-status'
    ));
}
add_action('init', 'register_community_post_types');

// カスタムタクソノミー（カテゴリー）の登録
function register_community_taxonomies() {
    // トピックのカテゴリー
    register_taxonomy('topic_category', 'community_topic', array(
        'labels' => array(
            'name' => 'トピックカテゴリー',
            'singular_name' => 'トピックカテゴリー'
        ),
        'hierarchical' => true,
        'show_admin_column' => true
    ));
    
    // 質問のカテゴリー
    register_taxonomy('question_category', 'community_question', array(
        'labels' => array(
            'name' => '質問カテゴリー',
            'singular_name' => '質問カテゴリー'
        ),
        'hierarchical' => true,
        'show_admin_column' => true
    ));
}
add_action('init', 'register_community_taxonomies');

// REST APIの登録（フロントエンドからの投稿用）
function register_community_rest_routes() {
    // トピック投稿用
    register_rest_route('community/v1', '/submit-topic', array(
        'methods' => 'POST',
        'callback' => 'handle_submit_topic',
        'permission_callback' => '__return_true'
    ));
    
    // 質問投稿用
    register_rest_route('community/v1', '/submit-question', array(
        'methods' => 'POST',
        'callback' => 'handle_submit_question',
        'permission_callback' => '__return_true'
    ));
    
    // トピックカテゴリー取得用
    register_rest_route('community/v1', '/topic-categories', array(
        'methods' => 'GET',
        'callback' => 'get_topic_categories_rest',
        'permission_callback' => '__return_true'
    ));
}
add_action('rest_api_init', 'register_community_rest_routes');

// REST API用カテゴリー取得ハンドラー
function get_topic_categories_rest() {
    $categories = get_terms(array(
        'taxonomy' => 'topic_category',
        'hide_empty' => false
    ));
    
    if (!empty($categories) && !is_wp_error($categories)) {
        return $categories;
    } else {
        return new WP_Error('no_categories', 'カテゴリーが見つかりませんでした', array('status' => 404));
    }
}

// トピック投稿のハンドラー
function handle_submit_topic($request) {
    $params = $request->get_params();
    
    $post_data = array(
        'post_title' => sanitize_text_field($params['title']),
        'post_content' => wp_kses_post($params['content']),
        'post_status' => 'publish',
        'post_author' => get_current_user_id(),
        'post_type' => 'community_topic'
    );
    
    $post_id = wp_insert_post($post_data);
    
    if ($post_id && !is_wp_error($post_id)) {
        if (!empty($params['category'])) {
            wp_set_object_terms($post_id, intval($params['category']), 'topic_category');
        }
        
        return array(
            'success' => true,
            'post_id' => $post_id
        );
    } else {
        return array(
            'success' => false,
            'message' => 'トピックの投稿に失敗しました'
        );
    }
}

// 質問投稿のハンドラー
function handle_submit_question($request) {
    $params = $request->get_params();
    
    $post_data = array(
        'post_title' => sanitize_text_field($params['title']),
        'post_content' => wp_kses_post($params['content']),
        'post_status' => 'publish',
        'post_author' => get_current_user_id(),
        'post_type' => 'community_question'
    );
    
    $post_id = wp_insert_post($post_data);
    
    if ($post_id && !is_wp_error($post_id)) {
        if (!empty($params['category'])) {
            wp_set_object_terms($post_id, intval($params['category']), 'question_category');
        }
        
        return array(
            'success' => true,
            'post_id' => $post_id
        );
    } else {
        return array(
            'success' => false,
            'message' => '質問の投稿に失敗しました'
        );
    }
}

// コミュニティトピック一覧を取得
function get_community_topics($limit = 5) {
    $args = array(
        'post_type' => 'community_topic',
        'posts_per_page' => $limit,
        'orderby' => 'date',
        'order' => 'DESC'
    );
    
    return get_posts($args);
}

// コミュニティ質問一覧を取得
function get_community_questions($limit = 5) {
    $args = array(
        'post_type' => 'community_question',
        'posts_per_page' => $limit,
        'orderby' => 'date',
        'order' => 'DESC'
    );
    
    return get_posts($args);
}

// コミュニティページのショートコード
function community_shortcode() {
    ob_start();
    include_once(get_stylesheet_directory() . '/template-parts/community-template.php');
    return ob_get_clean();
}
add_shortcode('community_page', 'community_shortcode');

// コミュニティ用スクリプトを追加
function add_community_scripts() {
    if (is_page('community') || has_shortcode(get_post()->post_content, 'community_page')) {
        wp_enqueue_script('jquery');
        wp_enqueue_script('community-script', get_stylesheet_directory_uri() . '/js/community.js', array('jquery'), '1.0', true);
        wp_localize_script('community-script', 'community_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => rest_url('community/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
        ));
    }
}
add_action('wp_enqueue_scripts', 'add_community_scripts');

// Ajaxハンドラーを追加
function handle_create_topic() {
    // セキュリティチェック
    check_ajax_referer('create_topic_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('ログインが必要です');
        return;
    }
    
    $title = sanitize_text_field($_POST['title']);
    $content = wp_kses_post($_POST['content']);
    $category = intval($_POST['category']);
    
    if (empty($title) || empty($content)) {
        wp_send_json_error('タイトルと内容は必須です');
        return;
    }
    
    $post_data = array(
        'post_title' => $title,
        'post_content' => $content,
        'post_status' => 'publish',
        'post_author' => get_current_user_id(),
        'post_type' => 'community_topic'
    );
    
    $post_id = wp_insert_post($post_data);
    
    if ($post_id && !is_wp_error($post_id)) {
        if ($category) {
            wp_set_object_terms($post_id, $category, 'topic_category');
        }
        
        wp_send_json_success(array(
            'message' => 'トピックが作成されました',
            'redirect' => get_permalink($post_id)
        ));
    } else {
        wp_send_json_error('トピックの作成に失敗しました');
    }
}
add_action('wp_ajax_create_topic', 'handle_create_topic');

// 質問フォーム処理
function handle_question_form_submission() {
    if (isset($_POST['question_nonce']) && wp_verify_nonce($_POST['question_nonce'], 'submit_question_nonce')) {
        $category = isset($_POST['question_category']) ? sanitize_text_field($_POST['question_category']) : '';
        $title = isset($_POST['question_title']) ? sanitize_text_field($_POST['question_title']) : '';
        $content = isset($_POST['question_content']) ? wp_kses_post($_POST['question_content']) : '';
        
        if (empty($title) || empty($content)) {
            wp_redirect(add_query_arg('status', 'error_empty', get_permalink(get_page_by_path('community'))));
            exit;
        }
        
        $post_data = array(
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => 'publish',
            'post_author' => get_current_user_id() ? get_current_user_id() : 1,
            'post_type' => 'community_question'
        );
        
        $post_id = wp_insert_post($post_data);
        
        if ($post_id && !is_wp_error($post_id)) {
            if (!empty($category)) {
                wp_set_object_terms($post_id, $category, 'question_category');
            }
            
            wp_redirect(add_query_arg('status', 'success', get_permalink(get_page_by_path('community'))));
            exit;
        } else {
            wp_redirect(add_query_arg('status', 'error_insert', get_permalink(get_page_by_path('community'))));
            exit;
        }
    } else {
        wp_redirect(add_query_arg('status', 'error_nonce', get_permalink(get_page_by_path('community'))));
        exit;
    }
}
add_action('admin_post_submit_question_form', 'handle_question_form_submission');
add_action('admin_post_nopriv_submit_question_form', 'handle_question_form_submission');

// トピックカテゴリーを取得するためのAJAXハンドラー
function get_topic_categories_ajax() {
    $categories = get_terms(array(
        'taxonomy' => 'topic_category',
        'hide_empty' => false
    ));
    
    $options = '';
    if (!empty($categories) && !is_wp_error($categories)) {
        foreach ($categories as $category) {
            $options .= '<option value="' . esc_attr($category->term_id) . '">' . esc_html($category->name) . '</option>';
        }
        wp_send_json_success($options);
    } else {
        wp_send_json_error('カテゴリーの取得に失敗しました');
    }
}
add_action('wp_ajax_get_topic_categories', 'get_topic_categories_ajax');
add_action('wp_ajax_nopriv_get_topic_categories', 'get_topic_categories_ajax');

// リライトルールをフラッシュする
function community_rewrite_flush() {
    register_community_post_types();
    register_community_taxonomies();
    flush_rewrite_rules();
}
// テーマ有効化時にリライトルールをフラッシュする
register_activation_hook(__FILE__, 'community_rewrite_flush');
