<?php
/**
 * Dialog Cards用ショートコード機能
 *
 * @package 行政書士試験ブログ
 */

if (!defined('ABSPATH')) {
    exit; // 直接アクセスを禁止
}

/**
 * Dialog Cardsショートコード
 */
function dialog_cards_display_shortcode($atts) {
    // 属性の初期化
    $atts = shortcode_atts(array(
        'id' => 0,           // 特定のH5Pコンテンツを表示
        'subject' => '',     // 科目に関連付けられたカードを表示
        'chapter' => 0,      // 章に関連付けられたカードを表示
        'style' => 'default', // スタイルバリエーション
        'title' => '',       // タイトル
        'description' => '', // 説明
    ), $atts, 'dialog_cards');
    
    // H5Pプラグインが有効か確認
    if (!function_exists('H5P_Plugin') && !class_exists('H5P_Plugin') && !function_exists('h5p_get_instance') && !class_exists('H5PPlugin')) {
        return '<p>H5Pプラグインが有効化されていません。Dialog Cardsを表示するには、H5Pプラグインが必要です。</p>';
    }
    
    // 出力開始
    ob_start();
    
    // スタイルクラスを作成
    $style_class = 'dialog-cards-style-' . sanitize_html_class($atts['style']);
    
    echo '<div class="dialog-cards-container ' . $style_class . '">';
    
    // タイトルと説明がある場合は表示
    if (!empty($atts['title'])) {
        echo '<h3 class="dialog-cards-title">' . esc_html($atts['title']) . '</h3>';
    }
    
    if (!empty($atts['description'])) {
        echo '<div class="dialog-cards-description">' . wp_kses_post($atts['description']) . '</div>';
    }
    
    // 特定のIDが指定されている場合
    if (!empty($atts['id'])) {
        $card_id = intval($atts['id']);
        echo do_shortcode('[h5p id="' . $card_id . '"]');
    }
    // 科目が指定されている場合
    elseif (!empty($atts['subject'])) {
        $subject_key = sanitize_key($atts['subject']);
        $chapter_id = !empty($atts['chapter']) ? intval($atts['chapter']) : 0;
        
        // 科目の存在確認
        $subjects = get_option('progress_tracker_subjects', array());
        if (!isset($subjects[$subject_key])) {
            return '<p>指定された科目「' . esc_html($subject_key) . '」は存在しません。</p>';
        }
        
        // 関連付けられたカードを検索
        $dialog_cards_relations = get_option('dialog_cards_relations', array());
        $filtered_cards = array();
        
        foreach ($dialog_cards_relations as $card_id => $relation) {
            if ($relation['subject'] == $subject_key) {
                if ($chapter_id == 0 || $relation['chapter'] == $chapter_id) {
                    $filtered_cards[] = $card_id;
                }
            }
        }
        
        if (!empty($filtered_cards)) {
            // カードナビゲーションを表示（複数ある場合）
            if (count($filtered_cards) > 1) {
                echo '<div class="dialog-cards-navigation">';
                echo '<button class="dialog-cards-prev-set" disabled>前のセット</button>';
                echo '<span class="dialog-cards-counter">1/' . count($filtered_cards) . '</span>';
                echo '<button class="dialog-cards-next-set">次のセット</button>';
                echo '</div>';
            }
            
            // カードを表示
            $first = true;
            foreach ($filtered_cards as $index => $card_id) {
                // 最初のカードセット以外は非表示にする
                $style = $first ? '' : 'style="display:none;"';
                echo '<div class="dialog-card-set" data-index="' . ($index + 1) . '" ' . $style . '>';
                echo do_shortcode('[h5p id="' . $card_id . '"]');
                echo '</div>';
                
                // セパレータを追加（最後以外）
                if ($index < count($filtered_cards) - 1) {
                    echo '<div class="card-separator"></div>';
                }
                
                $first = false;
            }
            
            // カードナビゲーションを再表示（複数ある場合）
            if (count($filtered_cards) > 1) {
                echo '<div class="dialog-cards-navigation">';
                echo '<button class="dialog-cards-prev-set" disabled>前のセット</button>';
                echo '<span class="dialog-cards-counter">1/' . count($filtered_cards) . '</span>';
                echo '<button class="dialog-cards-next-set">次のセット</button>';
                echo '</div>';
            }
            
            // ナビゲーション用のJavaScript
            if (count($filtered_cards) > 1) {
                ?>
                <script>
                jQuery(document).ready(function($) {
                    var $container = $('.dialog-cards-container');
                    var $cardSets = $container.find('.dialog-card-set');
                    var totalSets = $cardSets.length;
                    var currentIndex = 1;
                    
                    // ナビゲーションボタンのクリックイベント
                    $container.on('click', '.dialog-cards-prev-set', function() {
                        if (currentIndex > 1) {
                            currentIndex--;
                            updateCardDisplay();
                        }
                    });
                    
                    $container.on('click', '.dialog-cards-next-set', function() {
                        if (currentIndex < totalSets) {
                            currentIndex++;
                            updateCardDisplay();
                        }
                    });
                    
                    // カード表示を更新
                    function updateCardDisplay() {
                        $cardSets.hide();
                        $container.find('.dialog-card-set[data-index="' + currentIndex + '"]').show();
                        
                        // カウンターを更新
                        $container.find('.dialog-cards-counter').text(currentIndex + '/' + totalSets);
                        
                        // ボタン状態を更新
                        $container.find('.dialog-cards-prev-set').prop('disabled', currentIndex === 1);
                        $container.find('.dialog-cards-next-set').prop('disabled', currentIndex === totalSets);
                        
                        // 表示位置までスクロール
                        $('html, body').animate({
                            scrollTop: $container.offset().top - 50
                        }, 500);
                    }
                });
                </script>
                <?php
            }
        } else {
            echo '<p>この条件に一致するDialog Cardsはありません。</p>';
        }
    } else {
        echo '<p>Dialog Cards IDまたは科目を指定してください。</p>';
    }
    
    echo '</div>'; // .dialog-cards-container
    
    // スタイルを追加
    ?>
    <style>
    .dialog-cards-container {
        margin-bottom: 30px;
        padding: 20px;
        background-color: #f9f9f9;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .dialog-cards-title {
        margin-top: 0;
        color: #333;
        border-bottom: 2px solid #e0e0e0;
        padding-bottom: 10px;
        margin-bottom: 20px;
    }
    
    .dialog-cards-description {
        margin-bottom: 20px;
        color: #666;
    }
    
    .card-separator {
        height: 2px;
        background-color: #e0e0e0;
        margin: 30px 0;
        position: relative;
    }
    
    .dialog-cards-navigation {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 15px 0;
    }
    
    .dialog-cards-prev-set,
    .dialog-cards-next-set {
        padding: 8px 16px;
        background-color: #4CAF50;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: bold;
    }
    
    .dialog-cards-prev-set:disabled,
    .dialog-cards-next-set:disabled {
        background-color: #cccccc;
        cursor: not-allowed;
    }
    
    .dialog-cards-counter {
        font-weight: bold;
        color: #666;
    }
    
    /* スタイルバリエーション：モダン */
    .dialog-cards-style-modern {
        background-color: #ffffff;
        border-radius: 10px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    
    .dialog-cards-style-modern .dialog-cards-title {
        color: #2196F3;
        border-bottom: none;
        position: relative;
    }
    
    .dialog-cards-style-modern .dialog-cards-title:after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 50px;
        height: 3px;
        background-color: #2196F3;
    }
    
    /* スタイルバリエーション：クラシック */
    .dialog-cards-style-classic {
        background-color: #f5f5f5;
        border: 1px solid #ddd;
        border-radius: 0;
        box-shadow: none;
    }
    
    .dialog-cards-style-classic .dialog-cards-title {
        font-family: Georgia, serif;
        text-align: center;
        color: #333;
    }
    
    /* スタイルバリエーション：ダーク */
    .dialog-cards-style-dark {
        background-color: #2c3e50;
        color: #ecf0f1;
    }
    
    .dialog-cards-style-dark .dialog-cards-title {
        color: #ecf0f1;
        border-bottom-color: #34495e;
    }
    
    .dialog-cards-style-dark .dialog-cards-description {
        color: #bdc3c7;
    }
    </style>
    <?php
    
    return ob_get_clean();
}
add_shortcode('dialog_cards', 'dialog_cards_display_shortcode');

/**
 * Dialog Cards完了時のAjaxハンドラー
 */
function dialog_cards_ajax_record_completion() {
    check_ajax_referer('dialog_cards_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => '認証が必要です。'));
        return;
    }
    
    $content_id = isset($_POST['content_id']) ? intval($_POST['content_id']) : 0;
    
    if ($content_id <= 0) {
        wp_send_json_error(array('message' => '無効なコンテンツIDです。'));
        return;
    }
    
    // ユーザーのDialog Cards完了履歴を取得
    $user_id = get_current_user_id();
    $completion_history = get_user_meta($user_id, 'dialog_cards_completion', true);
    
    if (!is_array($completion_history)) {
        $completion_history = array();
    }
    
    // 完了履歴を更新
    $completion_history[$content_id] = array(
        'date' => current_time('mysql'),
        'score' => 1,
        'max_score' => 1
    );
    
    // ユーザーメタに保存
    update_user_meta($user_id, 'dialog_cards_completion', $completion_history);
    
    // Dialog Cardsと科目の関連付けを取得
    $dialog_cards_relations = get_option('dialog_cards_relations', array());
    
    if (isset($dialog_cards_relations[$content_id])) {
        $rel = $dialog_cards_relations[$content_id];
        
        // 進捗データを更新
        $progress_data = get_option('progress_tracker_progress', array());
        
        if (!isset($progress_data[$rel['subject']])) {
            $progress_data[$rel['subject']] = array(
                'chapters' => array(),
                'percent' => 0,
                'dialog_cards_completed' => array()
            );
        }
        
        if (!isset($progress_data[$rel['subject']]['dialog_cards_completed'])) {
            $progress_data[$rel['subject']]['dialog_cards_completed'] = array();
        }
        
        // Dialog Cards完了を記録
        $progress_data[$rel['subject']]['dialog_cards_completed'][$content_id] = array(
            'date' => current_time('mysql'),
            'chapter' => $rel['chapter']
        );
        
        update_option('progress_tracker_progress', $progress_data);
    }
    
    wp_send_json_success(array('message' => 'Dialog Cards完了が記録されました。'));
}
add_action('wp_ajax_dialog_cards_record_completion', 'dialog_cards_ajax_record_completion');

/**
 * Dialog Cards学習分析記録用Ajaxハンドラー
 */
function dialog_cards_ajax_record_analytics() {
    check_ajax_referer('dialog_cards_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => '認証が必要です。'));
        return;
    }
    
    $content_id = isset($_POST['content_id']) ? intval($_POST['content_id']) : 0;
    $card_times = isset($_POST['card_times']) ? $_POST['card_times'] : array();
    
    if ($content_id <= 0 || empty($card_times)) {
        wp_send_json_error(array('message' => '無効なデータです。'));
        return;
    }
    
    // ユーザーの学習分析データを取得
    $user_id = get_current_user_id();
    $analytics_data = get_user_meta($user_id, 'dialog_cards_analytics', true);
    
    if (!is_array($analytics_data)) {
        $analytics_data = array();
    }
    
    if (!isset($analytics_data[$content_id])) {
        $analytics_data[$content_id] = array(
            'sessions' => array(),
            'card_times' => array()
        );
    }
    
    // 新しいセッションを記録
    $session_id = uniqid('session_');
    $analytics_data[$content_id]['sessions'][$session_id] = array(
        'date' => current_time('mysql'),
        'card_times' => $card_times
    );
    
    // カードごとの累積時間を更新
    foreach ($card_times as $card_index => $time) {
        if (!isset($analytics_data[$content_id]['card_times'][$card_index])) {
            $analytics_data[$content_id]['card_times'][$card_index] = 0;
        }
        $analytics_data[$content_id]['card_times'][$card_index] += $time;
    }
    
    // ユーザーメタに保存
    update_user_meta($user_id, 'dialog_cards_analytics', $analytics_data);
    
    wp_send_json_success(array('message' => '学習分析データが記録されました。'));
}
add_action('wp_ajax_dialog_cards_record_analytics', 'dialog_cards_ajax_record_analytics');

/**
 * Dialog Cards用スクリプト登録
 */
function dialog_cards_register_scripts() {
    wp_register_script(
        'dialog-cards-js',
        get_stylesheet_directory_uri() . '/js/dialog-cards.js',
        array('jquery'),
        wp_get_theme()->get('Version'),
        true
    );
    
    wp_localize_script('dialog-cards-js', 'dialogCardsData', array(
        'nonce' => wp_create_nonce('dialog_cards_nonce'),
        'ajaxurl' => admin_url('admin-ajax.php')
    ));
    
    wp_register_style(
        'dialog-cards-css',
        get_stylesheet_directory_uri() . '/css/dialog-cards.css',
        array(),
        wp_get_theme()->get('Version')
    );
}
add_action('wp_enqueue_scripts', 'dialog_cards_register_scripts');

/**
 * ショートコードがページに存在する場合にスクリプトを読み込む
 */
function dialog_cards_enqueue_scripts() {
    global $post;
    
    if (is_a($post, 'WP_Post') && (
        has_shortcode($post->post_content, 'dialog_cards') ||
        has_shortcode($post->post_content, 'h5p')
    )) {
        wp_enqueue_script('dialog-cards-js');
        wp_enqueue_style('dialog-cards-css');
    }
}
add_action('wp_enqueue_scripts', 'dialog_cards_enqueue_scripts', 20);
