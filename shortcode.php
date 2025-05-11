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
 * 学習進捗表示用ショートコード関数
 */
function study_progress_tracker_shortcode($atts) {
    // 属性の初期化
    $atts = shortcode_atts(array(
        'subject' => '',        // 特定の科目のみ表示する場合に指定
        'interactive' => 'yes', // 対話型モード（ユーザーがクリックで完了チェックができる）
        'style' => 'default',   // デザインスタイル
    ), $atts, 'study_progress');
    
    // 進捗データを取得
    $progress_data = get_option('gyouseishoshi_progress_data', array());
    
    // 科目構造を取得
    $subject_structure_extended = get_option('gyouseishoshi_subject_structure_extended', array());
    
    // 表示設定を取得
    $display_settings = get_option('study_progress_display_settings', array(
        'progress_bar_color' => '#4CAF50',
        'completed_item_color' => '#e6f7e6',
        'show_percentage' => true,
        'show_fraction' => true
    ));
    
    // 科目一覧
    $subjects = array(
        'constitutional' => __('憲法', 'gyouseishoshi-astra-child'),
        'administrative' => __('行政法', 'gyouseishoshi-astra-child'),
        'civil' => __('民法', 'gyouseishoshi-astra-child'),
        'commercial' => __('商法・会社法', 'gyouseishoshi-astra-child')
    );
    
    // カスタム科目を追加
    $custom_subjects = get_option('study_progress_custom_subjects', array());
    $subjects = array_merge($subjects, $custom_subjects);
    
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
    
    // インタラクティブモードの設定
    $interactive = $atts['interactive'] === 'yes' && is_user_logged_in();
    
    // スタイルの選択
    $style_class = 'progress-tracker-' . sanitize_html_class($atts['style']);
    
    // 出力開始
    ob_start();
    
    // 進捗トラッカーのラッパー
    echo '<div class="study-progress-tracker ' . $style_class . '">';
    
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
    foreach ($subjects as $subject_key => $subject_name) {
        // 最初の科目かどうか
        $is_first = $subject_key === array_key_first($subjects);
        
        // 進捗データがない場合の初期化
        if (!isset($progress_data[$subject_key])) {
            $progress_data[$subject_key] = array(
                'percent' => 0,
                'chapters' => array()
            );
        }
        
        // 科目構造がない場合の初期化
        if (!isset($subject_structure_extended[$subject_key])) {
            continue; // この科目はスキップ
        }
        
        // 進捗コンテナの表示/非表示設定
        $container_style = (count($subjects) > 1 && !$is_first) ? 'display:none;' : '';
        
        // 科目ごとの進捗コンテナ
        echo '<div class="subject-progress-container" data-subject="' . esc_attr($subject_key) . '" style="' . $container_style . '">';
        
        // 科目名と進捗率
        echo '<h3 class="subject-title">' . esc_html($subject_name);
        
        // パーセンテージ表示（オプション）
        if (!empty($display_settings['show_percentage'])) {
            echo ' <span class="percent-complete">(' . esc_html($progress_data[$subject_key]['percent']) . '%完了)</span>';
        }
        
        // 分数形式表示（オプション）
        if (!empty($display_settings['show_fraction'])) {
            $completed_chapters = count($progress_data[$subject_key]['chapters']);
            $total_chapters = $subject_structure_extended[$subject_key]['total_chapters'];
            echo ' <span class="fraction-complete">(' . esc_html($completed_chapters) . '/' . esc_html($total_chapters) . '章)</span>';
        }
        
        echo '</h3>';
        
        // プログレスバー
        echo '<div class="progress-bar-container">';
        echo '<div class="progress-bar-full" style="width:' . esc_attr($progress_data[$subject_key]['percent']) . '%; background-color:' . esc_attr($display_settings['progress_bar_color']) . ';"></div>';
        echo '</div>';
        
        // 章と節の表示
        if (!empty($subject_structure_extended[$subject_key]['chapters'])) {
            echo '<div class="chapters-container">';
            
            foreach ($subject_structure_extended[$subject_key]['chapters'] as $chapter_id => $chapter) {
                $chapter_completed = isset($progress_data[$subject_key]['chapters'][$chapter_id]) && 
                                   count($progress_data[$subject_key]['chapters'][$chapter_id]) == $chapter['sections'];
                
                $chapter_class = $chapter_completed ? 'chapter-item completed' : 'chapter-item';
                $completed_style = $chapter_completed ? 'background-color:' . esc_attr($display_settings['completed_item_color']) . ';' : '';
                
                echo '<div class="' . $chapter_class . '" data-subject="' . esc_attr($subject_key) . '" data-chapter="' . esc_attr($chapter_id) . '" style="' . $completed_style . '">';
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
                        $section_completed = isset($progress_data[$subject_key]['chapters'][$chapter_id]) && 
                                           in_array($section_id, $progress_data[$subject_key]['chapters'][$chapter_id]);
                        
                        $section_class = $section_completed ? 'section-item completed' : 'section-item';
                        $section_style = $section_completed ? 'background-color:' . esc_attr($display_settings['completed_item_color']) . ';' : '';
                        
                        echo '<div class="' . $section_class . '" data-subject="' . esc_attr($subject_key) . '" data-chapter="' . esc_attr($chapter_id) . '" data-section="' . esc_attr($section_id) . '" style="' . $section_style . '">';
                        echo '<span class="section-number">' . esc_html($section_id) . '</span>';
                        echo '<span class="section-title">' . __('節', 'gyouseishoshi-astra-child') . ' ' . esc_html($section_id) . '</span>';
                        echo '<span class="section-status">' . ($section_completed ? '✓' : '') . '</span>';
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
    if (!empty($display_settings['exam_date'])) {
        $exam_date = strtotime($display_settings['exam_date']);
        $today = current_time('timestamp');
        $days_left = floor(($exam_date - $today) / (60 * 60 * 24));
        
        if ($days_left >= 0) {
            $exam_title = !empty($display_settings['exam_title']) ? $display_settings['exam_title'] : __('試験', 'gyouseishoshi-astra-child');
            
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
            
            // 節のクリックによる完了マーク
            $('.study-progress-tracker .section-item').on('click', function() {
                var $sectionItem = $(this);
                var subject = $sectionItem.data('subject');
                var chapter = $sectionItem.data('chapter');
                var section = $sectionItem.data('section');
                var isCompleted = $sectionItem.hasClass('completed');
                
                // Ajax処理
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'toggle_section_completion',
                        subject: subject,
                        chapter: chapter,
                        section: section,
                        completed: !isCompleted,
                        nonce: '<?php echo wp_create_nonce('gyouseishoshi_progress_nonce'); ?>'
                    },
                    beforeSend: function() {
                        $sectionItem.css('opacity', 0.5);
                    },
                    success: function(response) {
                        $sectionItem.css('opacity', 1);
                        
                        if (response.success) {
                            // 節の状態を更新
                            if (isCompleted) {
                                $sectionItem.removeClass('completed');
                                $sectionItem.css('background-color', '');
                                $sectionItem.find('.section-status').text('');
                            } else {
                                $sectionItem.addClass('completed');
                                $sectionItem.css('background-color', '<?php echo esc_js($display_settings['completed_item_color']); ?>');
                                $sectionItem.find('.section-status').text('✓');
                            }
                            
                            // 進捗率を更新
                            var $subjectContainer = $('.subject-progress-container[data-subject="' + subject + '"]');
                            $subjectContainer.find('.percent-complete').text('(' + response.data.percent + '%完了)');
                            $subjectContainer.find('.progress-bar-full').css('width', response.data.percent + '%');
                            
                            // 章の状態を更新
                            var $chapterItem = $('.chapter-item[data-subject="' + subject + '"][data-chapter="' + chapter + '"]');
                            
                            if (response.data.chapter_completed) {
                                $chapterItem.addClass('completed');
                                $chapterItem.css('background-color', '<?php echo esc_js($display_settings['completed_item_color']); ?>');
                            } else {
                                $chapterItem.removeClass('completed');
                                $chapterItem.css('background-color', '');
                            }
                            
                            // 完了章数を更新（オプション）
                            if ($('.fraction-complete').length) {
                                // 章数を再計算
                                var completedChapters = $('.chapter-item.completed[data-subject="' + subject + '"]').length;
                                var totalChapters = $('.chapter-item[data-subject="' + subject + '"]').length;
                                $subjectContainer.find('.fraction-complete').text('(' + completedChapters + '/' + totalChapters + '章)');
                            }
                        } else {
                            alert(response.data.message || 'エラーが発生しました。');
                        }
                    },
                    error: function() {
                        $sectionItem.css('opacity', 1);
                        alert('通信エラーが発生しました。');
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
    
    .percent-complete, .fraction-complete {
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
        background-color: <?php echo esc_attr($display_settings['progress_bar_color']); ?>;
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
    
    <?php if ($interactive): ?>
    .chapter-header {
        cursor: pointer;
    }
    <?php endif; ?>
    
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
    
    .chapter-item.completed .chapter-header {
        background-color: <?php echo esc_attr($display_settings['completed_item_color']); ?>;
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
    
    <?php if ($interactive): ?>
    .section-item {
        cursor: pointer;
    }
    
    .section-item:hover {
        background-color: #eaeaea;
    }
    <?php endif; ?>
    
    .section-item.completed {
        background-color: <?php echo esc_attr($display_settings['completed_item_color']); ?>;
    }
    
    .section-number {
        font-weight: bold;
        margin-right: 10px;
        min-width: 20px;
    }
    
    .section-title {
        flex-grow: 1;
    }
    
    .section-status {
        color: #4CAF50;
        font-weight: bold;
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
    
    .progress-tracker-simple .chapter-item.completed {
        border-left-color: <?php echo esc_attr($display_settings['progress_bar_color']); ?>;
    }
    
    /* スタイルバリエーション：カード */
    .progress-tracker-card .chapter-item {
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    <?php if ($interactive): ?>
    .progress-tracker-card .chapter-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    <?php endif; ?>
    
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
    
    .progress-tracker-minimal .chapter-item.completed .chapter-header {
        background-color: transparent;
    }
    
    .progress-tracker-minimal .chapter-item.completed .chapter-title {
        color: #4CAF50;
    }
    </style>
    <?php
    
    return ob_get_clean();
}
add_shortcode('study_progress', 'study_progress_tracker_shortcode');

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

/**
 * 試験日カウントダウンショートコード
 */
function study_progress_exam_countdown_shortcode($atts) {
    $atts = shortcode_atts(array(
        'title' => '', // カスタム試験名（オプション）
    ), $atts, 'exam_countdown');
    
    // 表示設定を取得
    $display_settings = get_option('study_progress_display_settings', array(
        'exam_date' => '',
        'exam_title' => __('試験', 'gyouseishoshi-astra-child')
    ));
    
    if (empty($display_settings['exam_date'])) {
        return '<p>' . __('試験日が設定されていません。', 'gyouseishoshi-astra-child') . '</p>';
    }
    
    $exam_date = strtotime($display_settings['exam_date']);
    $today = current_time('timestamp');
    $days_left = floor(($exam_date - $today) / (60 * 60 * 24));
    
    if ($days_left < 0) {
        return '<p>' . __('試験日は過ぎました。', 'gyouseishoshi-astra-child') . '</p>';
    }
    
    // 試験名はショートコードの属性か、設定の値を使用
    $exam_title = !empty($atts['title']) ? $atts['title'] : $display_settings['exam_title'];
    
    $output = '<div class="exam-countdown">';
    $output .= esc_html($exam_title) . __('まであと', 'gyouseishoshi-astra-child') . ' <span class="countdown-number">' . esc_html($days_left) . '</span> ' . __('日', 'gyouseishoshi-astra-child');
    $output .= '</div>';
    
    return $output;
}
add_shortcode('exam_countdown', 'study_progress_exam_countdown_shortcode');
