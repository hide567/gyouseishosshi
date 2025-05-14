<?php
/**
 * 学習進捗管理のフロントエンド表示のためのショートコード
 *
 * @package 学習進捗管理
 */

if (!defined('ABSPATH')) {
    exit; // 直接アクセスを禁止
}

/**
 * 学習進捗表示用ショートコード関数（2段階チェック対応版）
 */
function study_progress_tracker_shortcode($atts) {
    // 属性の初期化
    $atts = shortcode_atts(array(
        'subject' => '',        // 特定の科目のみ表示する場合に指定
        'interactive' => 'yes', // 対話型モード（ユーザーがクリックで完了チェックができる）
        'style' => 'default',   // デザインスタイル
    ), $atts, 'study_progress');
    
    // インタラクティブモードの設定
    $interactive = $atts['interactive'] === 'yes' && is_user_logged_in();
    
    // 進捗データを取得
    $progress_data = get_option('progress_tracker_progress', array());
    
    // 科目構造を取得
    $chapter_structure = get_option('progress_tracker_chapters', array());
    
    // チェック設定を取得
    $progress_settings = get_option('progress_tracker_check_settings', array(
        'first_check_color' => '#e6f7e6',
        'second_check_color' => '#ffebcc'
    ));
    
    // 科目一覧
    $subjects = get_option('progress_tracker_subjects', array(
        'constitutional' => '憲法',
        'administrative' => '行政法',
        'civil' => '民法',
        'commercial' => '商法・会社法'
    ));
    
    // 表示する科目をフィルタリング
    if (!empty($atts['subject'])) {
        $subject_keys = explode(',', $atts['subject']);
        
        $filtered_subjects = array();
        foreach ($subject_keys as $key) {
            $key = trim($key);
            if (isset($subjects[$key])) {
                $filtered_subjects[$key] = $subjects[$key];
            }
        }
        
        // フィルタリングの結果が空でなければ、フィルタリングした科目を使用
        if (!empty($filtered_subjects)) {
            $subjects = $filtered_subjects;
        }
    }
    
    // スタイルの選択
    $style_class = 'progress-tracker-' . sanitize_html_class($atts['style']);
    
    // 出力開始
    ob_start();
    
    // 進捗トラッカーのラッパー
    echo '<div class="study-progress-tracker ' . $style_class . '" data-nonce="' . wp_create_nonce('progress_tracker_nonce') . '">';
    
    // 科目タブ（複数科目がある場合のみ）
    if (count($subjects) > 1) {
        echo '<div class="subject-tabs">';
        $first = true;
        foreach ($subjects as $subject_key => $subject_name) {
            $tab_class = $first ? 'subject-tab active' : 'subject-tab';
            echo '<div class="' . $tab_class . '" data-subject="' . esc_attr($subject_key) . '">' . esc_html($subject_name) . '</div>';
            $first = false;
        }
        echo '</div>';
    }
    
    // 各科目の進捗表示
    $first = true;
    foreach ($subjects as $subject_key => $subject_name) {
        // 表示/非表示設定
        $display = $first ? 'block' : 'none';
        $first = false;
        
        // 進捗率
        $percent = isset($progress_data[$subject_key]['percent']) ? $progress_data[$subject_key]['percent'] : 0;
        
        // 科目の進捗バーの色
        $progress_color = isset($chapter_structure[$subject_key]['color']) ? $chapter_structure[$subject_key]['color'] : '#4CAF50';
        
        echo '<div class="subject-progress-container" data-subject="' . esc_attr($subject_key) . '" style="display: ' . $display . ';">';
        
        // 科目名と進捗率
        echo '<h3 class="subject-title">' . esc_html($subject_name);
        echo ' <span class="percent-complete">(' . esc_html($percent) . '%完了)</span>';
        echo '</h3>';
        
        // プログレスバー
        echo '<div class="progress-bar-container">';
        echo '<div class="progress-bar-full" style="width:' . esc_attr($percent) . '%; background-color:' . esc_attr($progress_color) . ';"></div>';
        echo '</div>';
        
        // 章と節の表示
        if (isset($chapter_structure[$subject_key]['chapters']) && !empty($chapter_structure[$subject_key]['chapters'])) {
            echo '<div class="chapters-container">';
            
            foreach ($chapter_structure[$subject_key]['chapters'] as $chapter_id => $chapter) {
                $chapter_completed = false;
                $chapter_mastered = false;
                $chapter_style = '';
                
                // 章の完了・習得状態確認
                if (isset($progress_data[$subject_key]['chapters'][$chapter_id])) {
                    $completed_sections = $progress_data[$subject_key]['chapters'][$chapter_id];
                    $total_sections = $chapter['sections'];
                    
                    // 完了したセクション数
                    $chapter_completed = count($completed_sections) == $total_sections;
                    
                    // 習得したセクション数
                    $mastered_count = 0;
                    foreach ($completed_sections as $section_num => $level) {
                        if ($level >= 2) $mastered_count++;
                    }
                    $chapter_mastered = $mastered_count == $total_sections;
                    
                    // 章の背景色
                    if ($chapter_mastered) {
                        $chapter_style = 'background-color: ' . esc_attr($progress_settings['second_check_color']) . ';';
                    } elseif ($chapter_completed) {
                        $chapter_style = 'background-color: ' . esc_attr($progress_settings['first_check_color']) . ';';
                    }
                }
                
                $chapter_class = 'chapter-item';
                if ($chapter_completed) $chapter_class .= ' completed';
                if ($chapter_mastered) $chapter_class .= ' mastered';
                
                echo '<div class="' . $chapter_class . '" data-subject="' . esc_attr($subject_key) . '" data-chapter="' . esc_attr($chapter_id) . '" style="' . $chapter_style . '">';
                echo '<div class="chapter-header">';
                echo '<span class="chapter-title">' . esc_html($chapter['title']) . '</span>';
                
                // インタラクティブモードの場合のみ展開ボタンを表示
                if ($interactive) {
                    echo '<span class="chapter-toggle">+</span>';
                }
                
                echo '</div>';
                
                // インタラクティブモードの場合のみ節を表示
                if ($interactive) {
                    echo '<div class="sections-container" style="display:none;">';
                    
                    // 節の表示
                    for ($section_id = 1; $section_id <= $chapter['sections']; $section_id++) {
                        // チェック状態を確認
                        $section_level = 0;
                        $section_style = '';
                        
                        if (isset($progress_data[$subject_key]['chapters'][$chapter_id][$section_id])) {
                            $section_level = $progress_data[$subject_key]['chapters'][$chapter_id][$section_id];
                            
                            // セクションの背景色
                            if ($section_level >= 2) {
                                $section_style = 'background-color: ' . esc_attr($progress_settings['second_check_color']) . ';';
                            } elseif ($section_level >= 1) {
                                $section_style = 'background-color: ' . esc_attr($progress_settings['first_check_color']) . ';';
                            }
                        }
                        
                        $section_class = 'section-item';
                        if ($section_level >= 1) $section_class .= ' checked';
                        if ($section_level >= 2) $section_class .= ' mastered';
                        
                        echo '<div class="' . $section_class . '" data-subject="' . esc_attr($subject_key) . '" data-chapter="' . esc_attr($chapter_id) . '" data-section="' . esc_attr($section_id) . '" style="' . $section_style . '">';
                        echo '<span class="section-number">' . esc_html($section_id) . '</span>';
                        echo '<span class="section-title">' . __('節', 'gyouseishoshi-astra-child') . ' ' . esc_html($section_id) . '</span>';
                        echo '<div class="section-checkboxes">';
                        echo '<label title="理解した">';
                        echo '<input type="checkbox" class="level1-check" data-level="1" ' . checked($section_level >= 1, true, false) . '>';
                        echo '<span>理解</span>';
                        echo '</label>';
                        echo '<label title="習得した">';
                        echo '<input type="checkbox" class="level2-check" data-level="2" ' . checked($section_level >= 2, true, false) . '>';
                        echo '<span>習得</span>';
                        echo '</label>';
                        echo '</div>';
                        echo '</div>';
                    }
                    
                    echo '</div>'; // sections-container
                }
                
                echo '</div>'; // chapter-item
            }
            
            echo '</div>'; // chapters-container
        } else {
            echo '<p class="no-chapters">' . __('この科目にはまだ章が設定されていません。', 'gyouseishoshi-astra-child') . '</p>';
        }
        
        echo '</div>'; // subject-progress-container
    }
    
    // 試験日カウントダウン（オプション）
    $settings = get_option('progress_tracker_settings', array(
        'exam_date' => '',
        'exam_title' => '試験'
    ));
    
    if (!empty($settings['exam_date'])) {
        $exam_date = strtotime($settings['exam_date']);
        $today = current_time('timestamp');
        $days_left = floor(($exam_date - $today) / (60 * 60 * 24));
        
        if ($days_left >= 0) {
            $exam_title = !empty($settings['exam_title']) ? $settings['exam_title'] : __('試験', 'gyouseishoshi-astra-child');
            
            echo '<div class="exam-countdown">';
            echo esc_html($exam_title) . __('まであと', 'gyouseishoshi-astra-child') . ' <span class="countdown-number">' . esc_html($days_left) . '</span> ' . __('日', 'gyouseishoshi-astra-child');
            echo '</div>';
        }
    }
    
    echo '</div>'; // study-progress-tracker
    
    // インタラクティブモードのJS（ログイン済みの場合のみ）
    if ($interactive) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // 章の展開/折りたたみ
            $('.study-progress-tracker .chapter-header').on('click', function() {
                var $chapterItem = $(this).closest('.chapter-item');
                var $sectionsContainer = $chapterItem.find('.sections-container');
                var $toggle = $(this).find('.chapter-toggle');
                
                $sectionsContainer.slideToggle(200, function() {
                    if ($sectionsContainer.is(':visible')) {
                        $toggle.text('-');
                    } else {
                        $toggle.text('+');
                    }
                });
            });
            
            // 科目タブの切り替え
            $('.subject-tab').on('click', function() {
                var subjectKey = $(this).data('subject');
                
                // タブの切り替え
                $('.subject-tab').removeClass('active');
                $(this).addClass('active');
                
                // コンテンツの切り替え
                $('.subject-progress-container').hide();
                $('.subject-progress-container[data-subject="' + subjectKey + '"]').show();
            });
            
            // 理解レベルのチェックボックス処理
            $('.study-progress-tracker .level1-check').on('change', function() {
                var $section = $(this).closest('.section-item');
                var $level2Check = $section.find('.level2-check');
                var isChecked = $(this).prop('checked');
                
                // レベル1のチェックを外したらレベル2も外れる
                if (!isChecked) {
                    $level2Check.prop('checked', false);
                    updateSectionStatus($section, 0);
                } else {
                    updateSectionStatus($section, 1);
                }
            });
            
            // 習得レベルのチェックボックス処理
            $('.study-progress-tracker .level2-check').on('change', function() {
                var $section = $(this).closest('.section-item');
                var $level1Check = $section.find('.level1-check');
                var isChecked = $(this).prop('checked');
                
                // レベル2をチェックするとレベル1も自動的にチェック
                if (isChecked) {
                    $level1Check.prop('checked', true);
                    updateSectionStatus($section, 2);
                } else {
                    updateSectionStatus($section, 1);
                }
            });
            
            // 進捗更新用Ajax処理
            function updateSectionStatus($section, level) {
                var subject = $section.data('subject');
                var chapter = $section.data('chapter');
                var section = $section.data('section');
                var nonce = $('.study-progress-tracker').data('nonce');
                
                // Ajax処理
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'progress_tracker_toggle_completion',
                        subject: subject,
                        chapter: chapter,
                        section: section,
                        check_level: level,
                        completed: level > 0,
                        nonce: nonce
                    },
                    beforeSend: function() {
                        $section.css('opacity', 0.5);
                    },
                    success: function(response) {
                        $section.css('opacity', 1);
                        
                        if (response.success) {
                            // 節のスタイルを更新
                            $section.removeClass('checked mastered');
                            
                            if (level >= 1) {
                                $section.addClass('checked');
                                $section.css('background-color', '<?php echo esc_js($progress_settings['first_check_color']); ?>');
                            } else {
                                $section.css('background-color', '');
                            }
                            
                            if (level >= 2) {
                                $section.addClass('mastered');
                                $section.css('background-color', '<?php echo esc_js($progress_settings['second_check_color']); ?>');
                            }
                            
                            // 進捗率を更新
                            var $subjectContainer = $('.subject-progress-container[data-subject="' + subject + '"]');
                            $subjectContainer.find('.percent-complete').text('(' + response.data.percent + '%完了)');
                            $subjectContainer.find('.progress-bar-full').css('width', response.data.percent + '%');
                            
                            // 章の状態を更新
                            var $chapterItem = $('.chapter-item[data-subject="' + subject + '"][data-chapter="' + chapter + '"]');
                            $chapterItem.removeClass('completed mastered');
                            
                            if (response.data.chapter_mastered) {
                                $chapterItem.addClass('completed mastered');
                                $chapterItem.css('background-color', '<?php echo esc_js($progress_settings['second_check_color']); ?>');
                            } else if (response.data.chapter_completed) {
                                $chapterItem.addClass('completed');
                                $chapterItem.css('background-color', '<?php echo esc_js($progress_settings['first_check_color']); ?>');
                            } else {
                                $chapterItem.css('background-color', '');
                            }
                        } else {
                            alert(response.data.message || 'エラーが発生しました。');
                        }
                    },
                    error: function() {
                        $section.css('opacity', 1);
                        alert('通信エラーが発生しました。');
                    }
                });
            }
        });
        </script>
        <?php
    }
    
    // スタイルを追加
    ?>
    <style>
    .study-progress-tracker {
        margin-bottom: 30px;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    }
    
    .subject-tabs {
        display: flex;
        flex-wrap: wrap;
        margin-bottom: 15px;
        border-bottom: 1px solid #ddd;
    }
    
    .subject-tab {
        padding: 8px 16px;
        cursor: pointer;
        margin-right: 5px;
        margin-bottom: -1px;
        border: 1px solid transparent;
        border-top-left-radius: 4px;
        border-top-right-radius: 4px;
        background-color: #f8f8f8;
    }
    
    .subject-tab.active {
        border-color: #ddd;
        border-bottom-color: #fff;
        background-color: #fff;
        font-weight: bold;
    }
    
    .subject-progress-container {
        background-color: #fff;
        padding: 15px;
        border-radius: 5px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .subject-title {
        margin-top: 0;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
        font-size: 18px;
    }
    
    .percent-complete {
        font-weight: normal;
        color: #666;
        font-size: 0.9em;
    }
    
    .progress-bar-container {
        height: 16px;
        background-color: #f1f1f1;
        border-radius: 8px;
        margin-bottom: 20px;
        overflow: hidden;
    }
    
    .progress-bar-full {
        height: 100%;
        transition: width 0.3s ease;
    }
    
    .chapters-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 15px;
        margin-top: 15px;
    }
    
    .chapter-item {
        border: 1px solid #ddd;
        border-radius: 5px;
        overflow: hidden;
    }
    
    .chapter-header {
        padding: 10px;
        background-color: #f9f9f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .chapter-header {
        cursor: pointer;
    }
    
    .chapter-title {
        font-weight: bold;
    }
    
    .chapter-toggle {
        font-weight: bold;
        width: 20px;
        height: 20px;
        line-height: 20px;
        text-align: center;
        border-radius: 50%;
        background-color: #eee;
    }
    
    .sections-container {
        padding: 10px;
    }
    
    .section-item {
        padding: 8px 10px;
        margin-bottom: 5px;
        background-color: #f5f5f5;
        border-radius: 3px;
        display: flex;
        align-items: center;
    }
    
    .section-item {
        cursor: pointer;
    }
    
    .section-item:hover {
        background-color: #eaeaea;
    }
    
    .section-number {
        font-weight: bold;
        margin-right: 10px;
        min-width: 20px;
    }
    
    .section-title {
        flex-grow: 1;
    }
    
    .section-checkboxes {
        display: flex;
        gap: 10px;
    }
    
    .section-checkboxes label {
        display: flex;
        align-items: center;
        gap: 5px;
        cursor: pointer;
    }
    
    .no-chapters {
        color: #666;
        font-style: italic;
    }
    
    .exam-countdown {
        margin-top: 30px;
        text-align: center;
        padding: 15px;
        background-color: #f8f8f8;
        border-radius: 5px;
        font-size: 16px;
    }
    
    .countdown-number {
        font-weight: bold;
        font-size: 1.2em;
        color: #e74c3c;
    }
    
    /* スタイルバリエーション：シンプル */
    .progress-tracker-simple .chapters-container {
        display: block;
    }
    
    .progress-tracker-simple .chapter-item {
        margin-bottom: 10px;
        box-shadow: none;
        border-left: 3px solid #ddd;
    }
    
    /* スタイルバリエーション：カード */
    .progress-tracker-card .chapter-item {
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    .progress-tracker-card .chapter-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    
    /* スタイルバリエーション：ミニマル */
    .progress-tracker-minimal .subject-progress-container {
        box-shadow: none;
        padding: 0;
    }
    
    .progress-tracker-minimal .chapter-item {
        border: none;
        border-bottom: 1px solid #eee;
        border-radius: 0;
    }
    
    .progress-tracker-minimal .chapter-header {
        background-color: transparent;
    }
    </style>
    <?php
    
    return ob_get_clean();
}
add_shortcode('study_progress', 'study_progress_tracker_shortcode');

/**
 * Ajax処理：進捗更新
 */
function study_progress_ajax_update() {
    // セキュリティチェック
    check_ajax_referer('progress_tracker_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => '認証が必要です。'));
        return;
    }
    
    // パラメータを取得
    $subject_key = sanitize_key($_POST['subject']);
    $chapter_id = intval($_POST['chapter']);
    $section_id = intval($_POST['section']);
    $check_level = intval($_POST['check_level']); // 0=なし, 1=理解, 2=習得
    $completed = $_POST['completed'] === 'true';
    
    // 進捗データを取得
    $progress_data = get_option('progress_tracker_progress', array());
    
    // 科目データがない場合は初期化
    if (!isset($progress_data[$subject_key])) {
        $progress_data[$subject_key] = array(
            'chapters' => array(),
            'percent' => 0
        );
    }
    
    // 章データがない場合は初期化
    if (!isset($progress_data[$subject_key]['chapters'][$chapter_id])) {
        $progress_data[$subject_key]['chapters'][$chapter_id] = array();
    }
    
    // 進捗状態を更新
    if ($check_level > 0) {
        // チェックレベルを設定
        $progress_data[$subject_key]['chapters'][$chapter_id][$section_id] = $check_level;
    } else {
        // チェックを削除
        unset($progress_data[$subject_key]['chapters'][$chapter_id][$section_id]);
        
        // 空の章は削除
        if (empty($progress_data[$subject_key]['chapters'][$chapter_id])) {
            unset($progress_data[$subject_key]['chapters'][$chapter_id]);
        }
    }
    
    // 進捗率を再計算
    $chapter_structure = get_option('progress_tracker_chapters', array());
    $total_sections = 0;
    $completed_count = 0;
    
    if (isset($chapter_structure[$subject_key]['chapters']) && is_array($chapter_structure[$subject_key]['chapters'])) {
        foreach ($chapter_structure[$subject_key]['chapters'] as $ch_id => $chapter_data) {
            $total_sections += $chapter_data['sections'];
            
            if (isset($progress_data[$subject_key]['chapters'][$ch_id])) {
                $completed_count += count($progress_data[$subject_key]['chapters'][$ch_id]);
            }
        }
    }
    
    $percent = ($total_sections > 0) ? min(100, ceil(($completed_count / $total_sections) * 100)) : 0;
    $progress_data[$subject_key]['percent'] = $percent;
    
    // データを保存
    update_option('progress_tracker_progress', $progress_data);
    
    // 章がすべてチェックされているか確認
    $chapter_completed = false;
    $chapter_mastered = false;
    
    if (isset($chapter_structure[$subject_key]['chapters'][$chapter_id])) {
        $total_chapter_sections = $chapter_structure[$subject_key]['chapters'][$chapter_id]['sections'];
        
        if (isset($progress_data[$subject_key]['chapters'][$chapter_id])) {
            $chapter_completed = count($progress_data[$subject_key]['chapters'][$chapter_id]) == $total_chapter_sections;
            
            // 習得レベルのセクション数を確認
            $mastered_count = 0;
            foreach ($progress_data[$subject_key]['chapters'][$chapter_id] as $sect => $level) {
                if ($level >= 2) $mastered_count++;
            }
            $chapter_mastered = $mastered_count == $total_chapter_sections;
        }
    }
    
    // 結果を返す
    wp_send_json_success(array(
        'percent' => $percent,
        'chapter_completed' => $chapter_completed,
        'chapter_mastered' => $chapter_mastered
    ));
}
add_action('wp_ajax_study_progress_update', 'study_progress_ajax_update');

/**
 * ショートコードをGutenbergブロックとして登録
 */
function study_progress_register_block() {
    if (!function_exists('register_block_type')) {
        return; // Gutenbergが有効でない場合は終了
    }
    
    wp_register_script(
        'study-progress-block',
        plugins_url('js/study-progress-block.js', __FILE__),
        array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor'),
        filemtime(plugin_dir_path(__FILE__) . 'js/study-progress-block.js')
    );
    
    register_block_type('gyouseishoshi/study-progress', array(
        'editor_script' => 'study-progress-block',
        'render_callback' => 'study_progress_tracker_shortcode',
        'attributes' => array(
            'subject' => array(
                'type' => 'string',
                'default' => ''
            ),
            'interactive' => array(
                'type' => 'string',
                'default' => 'yes'
            ),
            'style' => array(
                'type' => 'string',
                'default' => 'default'
            )
        )
    ));
}
add_action('init', 'study_progress_register_block');
