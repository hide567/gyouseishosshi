<?php
/**
 * 学習進捗管理機能
 *
 * @package 行政書士試験ブログ
 */

if (!defined('ABSPATH')) {
    exit; // 直接アクセスを禁止
}

/**
 * 管理メニューを追加
 */
function progress_tracker_admin_menu() {
    add_menu_page(
        '学習進捗管理',
        '学習進捗管理',
        'manage_options',
        'progress-tracker',
        'progress_tracker_admin_page',
        'dashicons-welcome-learn-more',
        30
    );
}
add_action('admin_menu', 'progress_tracker_admin_menu');

/**
 * 管理画面の表示
 */
function progress_tracker_admin_page() {
    // 権限チェック
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // 科目データ
    $subjects = array(
        'constitutional' => '憲法',
        'administrative' => '行政法',
        'civil' => '民法',
        'commercial' => '商法・会社法'
    );
    
    // カスタム科目を追加
    $custom_subjects = get_option('progress_tracker_custom_subjects', array());
    if (!empty($custom_subjects) && is_array($custom_subjects)) {
        $subjects = array_merge($subjects, $custom_subjects);
    }
    
    // 章の構成データを取得
    $chapter_structure = get_option('progress_tracker_chapters', array(
        'constitutional' => array(
            'total' => 15,
            'chapters' => array()
        ),
        'administrative' => array(
            'total' => 15,
            'chapters' => array()
        ),
        'civil' => array(
            'total' => 20,
            'chapters' => array()
        ),
        'commercial' => array(
            'total' => 10,
            'chapters' => array()
        )
    ));
    
    // 進捗データを取得
    $progress_data = get_option('progress_tracker_progress', array());
    
    // 科目と章の設定を保存
    if (isset($_POST['save_structure'])) {
        $updated_structure = array();
        
        foreach ($subjects as $subject_key => $subject_name) {
            $total_chapters = isset($_POST[$subject_key . '_chapters']) ? intval($_POST[$subject_key . '_chapters']) : 0;
            
            $chapters = array();
            for ($i = 1; $i <= $total_chapters; $i++) {
                $chapter_title = isset($_POST[$subject_key . '_chapter_' . $i]) ? 
                    sanitize_text_field($_POST[$subject_key . '_chapter_' . $i]) : '第' . $i . '章';
                $section_count = isset($_POST[$subject_key . '_sections_' . $i]) ? 
                    intval($_POST[$subject_key . '_sections_' . $i]) : 1;
                
                $chapters[$i] = array(
                    'title' => $chapter_title,
                    'sections' => $section_count
                );
            }
            
            $updated_structure[$subject_key] = array(
                'total' => $total_chapters,
                'chapters' => $chapters
            );
        }
        
        // データを保存
        update_option('progress_tracker_chapters', $updated_structure);
        $chapter_structure = $updated_structure;
        
        echo '<div class="notice notice-success is-dismissible"><p>科目と章の構造を保存しました。</p></div>';
    }
    
    // 進捗データを保存
    if (isset($_POST['save_progress'])) {
        $updated_progress = array();
        
        foreach ($subjects as $subject_key => $subject_name) {
            $subject_progress = array();
            
            // 各章ごとの進捗
            if (isset($chapter_structure[$subject_key]['chapters']) && is_array($chapter_structure[$subject_key]['chapters'])) {
                foreach ($chapter_structure[$subject_key]['chapters'] as $chapter_id => $chapter_data) {
                    $completed_sections = array();
                    
                    // 各節の完了状態を確認
                    for ($section = 1; $section <= $chapter_data['sections']; $section++) {
                        $field_name = $subject_key . '_chapter_' . $chapter_id . '_section_' . $section;
                        
                        if (isset($_POST[$field_name]) && $_POST[$field_name] == '1') {
                            $completed_sections[] = $section;
                        }
                    }
                    
                    if (!empty($completed_sections)) {
                        $subject_progress[$chapter_id] = $completed_sections;
                    }
                }
            }
            
            // 進捗率を計算
            $total_sections = 0;
            $completed_count = 0;
            
            if (isset($chapter_structure[$subject_key]['chapters']) && is_array($chapter_structure[$subject_key]['chapters'])) {
                foreach ($chapter_structure[$subject_key]['chapters'] as $chapter_id => $chapter_data) {
                    $total_sections += $chapter_data['sections'];
                    
                    if (isset($subject_progress[$chapter_id])) {
                        $completed_count += count($subject_progress[$chapter_id]);
                    }
                }
            }
            
            $percent = ($total_sections > 0) ? min(100, ceil(($completed_count / $total_sections) * 100)) : 0;
            
            $updated_progress[$subject_key] = array(
                'chapters' => $subject_progress,
                'percent' => $percent
            );
        }
        
        // データを保存
        update_option('progress_tracker_progress', $updated_progress);
        $progress_data = $updated_progress;
        
        echo '<div class="notice notice-success is-dismissible"><p>進捗状況を更新しました。</p></div>';
    }
    
    // カスタム科目の追加
    if (isset($_POST['add_subject'])) {
        $subject_key = sanitize_key($_POST['new_subject_key']);
        $subject_name = sanitize_text_field($_POST['new_subject_name']);
        $total_chapters = intval($_POST['new_subject_chapters']);
        
        if (!empty($subject_key) && !empty($subject_name) && $total_chapters > 0) {
            // 既存の科目キーと重複していないか確認
            if (!isset($subjects[$subject_key])) {
                $custom_subjects[$subject_key] = $subject_name;
                update_option('progress_tracker_custom_subjects', $custom_subjects);
                
                // 章構造も初期化
                $chapter_structure[$subject_key] = array(
                    'total' => $total_chapters,
                    'chapters' => array()
                );
                update_option('progress_tracker_chapters', $chapter_structure);
                
                // 進捗データも初期化
                $progress_data[$subject_key] = array(
                    'chapters' => array(),
                    'percent' => 0
                );
                update_option('progress_tracker_progress', $progress_data);
                
                // 科目リストを更新
                $subjects[$subject_key] = $subject_name;
                
                echo '<div class="notice notice-success is-dismissible"><p>科目を追加しました。</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>このキーは既に使用されています。</p></div>';
            }
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>すべての項目を入力してください。</p></div>';
        }
    }
    
    // タブの処理
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'structure';
    ?>
    <div class="wrap">
        <h1>学習進捗管理</h1>
        
        <h2 class="nav-tab-wrapper">
            <a href="?page=progress-tracker&tab=structure" class="nav-tab <?php echo $active_tab == 'structure' ? 'nav-tab-active' : ''; ?>">科目構造設定</a>
            <a href="?page=progress-tracker&tab=progress" class="nav-tab <?php echo $active_tab == 'progress' ? 'nav-tab-active' : ''; ?>">進捗管理</a>
            <a href="?page=progress-tracker&tab=custom" class="nav-tab <?php echo $active_tab == 'custom' ? 'nav-tab-active' : ''; ?>">カスタム科目</a>
        </h2>
        
        <?php if ($active_tab == 'structure'): ?>
        <!-- 科目構造設定タブ -->
        <form method="post" action="">
            <h3>科目と章の設定</h3>
            <p>各科目の章数と名前を設定します。</p>
            
            <div class="subject-settings">
                <?php foreach ($subjects as $subject_key => $subject_name): ?>
                    <div class="subject-section">
                        <h4><?php echo esc_html($subject_name); ?></h4>
                        <table class="form-table">
                            <tr>
                                <th scope="row">総章数</th>
                                <td>
                                    <input type="number" name="<?php echo $subject_key; ?>_chapters" value="<?php echo isset($chapter_structure[$subject_key]['total']) ? esc_attr($chapter_structure[$subject_key]['total']) : 10; ?>" min="1" max="50" class="small-text">
                                </td>
                            </tr>
                        </table>
                        
                        <div class="chapter-settings">
                            <h5>各章の設定</h5>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th width="10%">章番号</th>
                                        <th width="60%">章タイトル</th>
                                        <th width="30%">節数</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $total_chapters = isset($chapter_structure[$subject_key]['total']) ? $chapter_structure[$subject_key]['total'] : 10;
                                    for ($i = 1; $i <= $total_chapters; $i++):
                                        $chapter_title = isset($chapter_structure[$subject_key]['chapters'][$i]['title']) ? 
                                            $chapter_structure[$subject_key]['chapters'][$i]['title'] : '第' . $i . '章';
                                        $section_count = isset($chapter_structure[$subject_key]['chapters'][$i]['sections']) ? 
                                            $chapter_structure[$subject_key]['chapters'][$i]['sections'] : 1;
                                    ?>
                                        <tr>
                                            <td><?php echo $i; ?></td>
                                            <td>
                                                <input type="text" name="<?php echo $subject_key; ?>_chapter_<?php echo $i; ?>" value="<?php echo esc_attr($chapter_title); ?>" class="regular-text">
                                            </td>
                                            <td>
                                                <input type="number" name="<?php echo $subject_key; ?>_sections_<?php echo $i; ?>" value="<?php echo esc_attr($section_count); ?>" min="1" max="20" class="small-text">
                                            </td>
                                        </tr>
                                    <?php endfor; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <p class="submit">
                <input type="submit" name="save_structure" class="button button-primary" value="科目構造を保存">
            </p>
        </form>
        
        <?php elseif ($active_tab == 'progress'): ?>
        <!-- 進捗管理タブ -->
        <form method="post" action="">
            <h3>学習進捗の管理</h3>
            <p>完了した節にチェックを入れてください。</p>
            
            <div class="progress-settings">
                <?php foreach ($subjects as $subject_key => $subject_name): ?>
                    <div class="subject-progress">
                        <h4><?php echo esc_html($subject_name); ?> 
                            <span class="percent-display">
                                (<?php echo isset($progress_data[$subject_key]['percent']) ? esc_html($progress_data[$subject_key]['percent']) : 0; ?>%完了)
                            </span>
                        </h4>
                        
                        <div class="progress-bar-container">
                            <div class="progress-bar-fill" style="width: <?php echo isset($progress_data[$subject_key]['percent']) ? esc_attr($progress_data[$subject_key]['percent']) : 0; ?>%;"></div>
                        </div>
                        
                        <?php if (isset($chapter_structure[$subject_key]['chapters']) && !empty($chapter_structure[$subject_key]['chapters'])): ?>
                            <div class="chapters-container">
                                <?php foreach ($chapter_structure[$subject_key]['chapters'] as $chapter_id => $chapter_data): ?>
                                    <div class="chapter-item">
                                        <h5><?php echo esc_html($chapter_data['title']); ?></h5>
                                        
                                        <div class="sections-list">
                                            <?php for ($section = 1; $section <= $chapter_data['sections']; $section++): ?>
                                                <?php
                                                $is_completed = isset($progress_data[$subject_key]['chapters'][$chapter_id]) && 
                                                                in_array($section, $progress_data[$subject_key]['chapters'][$chapter_id]);
                                                ?>
                                                <label class="section-label<?php echo $is_completed ? ' completed' : ''; ?>">
                                                    <input type="checkbox" name="<?php echo $subject_key; ?>_chapter_<?php echo $chapter_id; ?>_section_<?php echo $section; ?>" value="1" <?php checked($is_completed); ?>>
                                                    節<?php echo $section; ?>
                                                </label>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p>この科目には章が設定されていません。「科目構造設定」タブで設定してください。</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <p class="submit">
                <input type="submit" name="save_progress" class="button button-primary" value="進捗状況を保存">
            </p>
        </form>
        
        <?php else: ?>
        <!-- カスタム科目タブ -->
        <h3>カスタム科目の追加</h3>
        <p>試験や資格ごとに科目を追加できます。</p>
        
        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th scope="row">科目キー（英数字）</th>
                    <td>
                        <input type="text" name="new_subject_key" class="regular-text" pattern="[a-zA-Z0-9_]+">
                        <p class="description">システム内で使用される英数字のID（例：math, english など）</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">科目名</th>
                    <td>
                        <input type="text" name="new_subject_name" class="regular-text">
                        <p class="description">表示される科目名（例：数学，英語 など）</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">初期章数</th>
                    <td>
                        <input type="number" name="new_subject_chapters" value="10" min="1" max="50" class="small-text">
                        <p class="description">この科目の章数</p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="add_subject" class="button button-primary" value="科目を追加">
            </p>
        </form>
        
        <h3>現在のカスタム科目</h3>
        <?php if (!empty($custom_subjects)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>科目キー</th>
                        <th>科目名</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($custom_subjects as $key => $name): ?>
                        <tr>
                            <td><?php echo esc_html($key); ?></td>
                            <td><?php echo esc_html($name); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>現在、カスタム科目はありません。</p>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <style>
    .subject-section {
        margin-bottom: 30px;
        padding: 15px;
        background-color: #fff;
        border: 1px solid #ddd;
        border-radius: 5px;
    }
    .subject-section h4 {
        margin-top: 0;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
    }
    .chapter-settings {
        margin-top: 20px;
    }
    .progress-bar-container {
        height: 20px;
        background-color: #f1f1f1;
        border-radius: 5px;
        margin-bottom: 15px;
        overflow: hidden;
    }
    .progress-bar-fill {
        height: 100%;
        background-color: #4CAF50;
    }
    .subject-progress {
        margin-bottom: 30px;
        padding: 15px;
        background-color: #fff;
        border: 1px solid #ddd;
        border-radius: 5px;
    }
    .chapters-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 15px;
    }
    .chapter-item {
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 3px;
    }
    .chapter-item h5 {
        margin-top: 0;
        margin-bottom: 10px;
        border-bottom: 1px solid #eee;
        padding-bottom: 5px;
    }
    .sections-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    .section-label {
        padding: 5px 10px;
        background-color: #f5f5f5;
        border-radius: 3px;
        cursor: pointer;
    }
    .section-label.completed {
        background-color: #e6f7e6;
    }
    .percent-display {
        font-weight: normal;
        color: #666;
    }
    </style>
    <?php
}

/**
 * 進捗表示ウィジェット
 */
class Progress_Tracker_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'progress_tracker_widget',
            '学習進捗状況',
            array('description' => '学習進捗を表示します。')
        );
    }
    
    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        $title = !empty($instance['title']) ? apply_filters('widget_title', $instance['title']) : '学習進捗状況';
        echo $args['before_title'] . esc_html($title) . $args['after_title'];
        
        // 科目データ
        $subjects = array(
            'constitutional' => '憲法',
            'administrative' => '行政法',
            'civil' => '民法',
            'commercial' => '商法・会社法'
        );
        
        // カスタム科目を追加
        $custom_subjects = get_option('progress_tracker_custom_subjects', array());
        if (!empty($custom_subjects) && is_array($custom_subjects)) {
            $subjects = array_merge($subjects, $custom_subjects);
        }
        
        // 進捗データを取得
        $progress_data = get_option('progress_tracker_progress', array());
        
        // 章構造データを取得
        $chapter_structure = get_option('progress_tracker_chapters', array());
        
        ?>
        <div class="progress-widget">
            <?php foreach ($subjects as $subject_key => $subject_name): ?>
                <?php
                $percent = isset($progress_data[$subject_key]['percent']) ? $progress_data[$subject_key]['percent'] : 0;
                $completed_chapters = isset($progress_data[$subject_key]['chapters']) ? count($progress_data[$subject_key]['chapters']) : 0;
                $total_chapters = isset($chapter_structure[$subject_key]['total']) ? $chapter_structure[$subject_key]['total'] : 0;
                ?>
                <div class="subject-item">
                    <p><?php echo esc_html($subject_name); ?></p>
                    <div class="progress-bar">
                        <div class="progress" style="width: <?php echo esc_attr($percent); ?>%;"></div>
                    </div>
                    <div class="progress-stats">
                        <span><?php echo esc_html($percent); ?>%</span>
                        <span><?php echo esc_html($completed_chapters); ?>/<?php echo esc_html($total_chapters); ?>章</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <style>
        .progress-widget .subject-item {
            margin-bottom: 15px;
        }
        .progress-widget .progress-bar {
            height: 15px;
            background-color: #e9ecef;
            border-radius: 3px;
            margin-bottom: 5px;
            overflow: hidden;
        }
        .progress-widget .progress {
            height: 100%;
            background-color: #4a6fa5;
        }
        .progress-widget .progress-stats {
            display: flex;
            justify-content: space-between;
            font-size: 0.9em;
            color: #666;
        }
        </style>
        <?php
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : '学習進捗状況';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">タイトル:</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            進捗データは「学習進捗管理」メニューから編集できます。
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        return $instance;
    }
}

/**
 * ウィジェットの登録
 */
function progress_tracker_register_widget() {
    register_widget('Progress_Tracker_Widget');
}
add_action('widgets_init', 'progress_tracker_register_widget');

/**
 * ショートコード
 */
function progress_tracker_shortcode($atts) {
    // 属性の初期化
    $atts = shortcode_atts(array(
        'subject' => '',    // 特定の科目のみ表示
        'style' => 'default'// スタイル
    ), $atts, 'progress_tracker');
    
    // 科目データ
    $subjects = array(
        'constitutional' => '憲法',
        'administrative' => '行政法',
        'civil' => '民法',
        'commercial' => '商法・会社法'
    );
    
    // カスタム科目を追加
    $custom_subjects = get_option('progress_tracker_custom_subjects', array());
    if (!empty($custom_subjects) && is_array($custom_subjects)) {
        $subjects = array_merge($subjects, $custom_subjects);
    }
    
    // 特定の科目だけ表示する場合
    if (!empty($atts['subject'])) {
        $subject_keys = explode(',', $atts['subject']);
        $filtered_subjects = array();
        
        foreach ($subject_keys as $key) {
            $key = trim($key);
            if (isset($subjects[$key])) {
                $filtered_subjects[$key] = $subjects[$key];
            }
        }
        
        if (!empty($filtered_subjects)) {
            $subjects = $filtered_subjects;
        }
    }
    
    // 進捗データを取得
    $progress_data = get_option('progress_tracker_progress', array());
    
    // 章構造データを取得
    $chapter_structure = get_option('progress_tracker_chapters', array());
    
    // 出力開始
    ob_start();
    ?>
    <div class="progress-tracker-shortcode style-<?php echo esc_attr($atts['style']); ?>">
        <?php foreach ($subjects as $subject_key => $subject_name): ?>
            <?php
            $percent = isset($progress_data[$subject_key]['percent']) ? $progress_data[$subject_key]['percent'] : 0;
            $completed_chapters = isset($progress_data[$subject_key]['chapters']) ? count($progress_data[$subject_key]['chapters']) : 0;
            $total_chapters = isset($chapter_structure[$subject_key]['total']) ? $chapter_structure[$subject_key]['total'] : 0;
            ?>
            <div class="progress-subject">
                <h4><?php echo esc_html($subject_name); ?> <span class="percent">(<?php echo esc_html($percent); ?>%)</span></h4>
                <div class="progress-bar-container">
                    <div class="progress-bar-fill" style="width: <?php echo esc_attr($percent); ?>%;"></div>
                </div>
                <div class="progress-details">
                    <?php echo esc_html($completed_chapters); ?>/<?php echo esc_html($total_chapters); ?> 章完了
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <style>
    .progress-tracker-shortcode {
        margin-bottom: 20px;
    }
    .progress-tracker-shortcode .progress-subject {
        margin-bottom: 15px;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        background-color: #f9f9f9;
    }
    .progress-tracker-shortcode h4 {
        margin-top: 0;
        margin-bottom: 10px;
    }
    .progress-tracker-shortcode .percent {
        font-weight: normal;
        color: #666;
    }
    .progress-tracker-shortcode .progress-bar-container {
        height: 20px;
        background-color: #f1f1f1;
        border-radius: 5px;
        margin-bottom: 5px;
        overflow: hidden;
    }
    .progress-tracker-shortcode .progress-bar-fill {
        height: 100%;
        background-color: #4CAF50;
        transition: width 0.3s ease;
    }
    .progress-tracker-shortcode .progress-details {
        font-size: 0.9em;
        color: #666;
        text-align: right;
    }
    
    /* 追加スタイル: シンプル */
    .progress-tracker-shortcode.style-simple .progress-subject {
        border: none;
        padding: 5px 0;
        background-color: transparent;
    }
    
    /* 追加スタイル: コンパクト */
    .progress-tracker-shortcode.style-compact {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    .progress-tracker-shortcode.style-compact .progress-subject {
        flex: 1 1 200px;
        min-width: 200px;
    }
    </style>
    <?php
    
    return ob_get_clean();
}
add_shortcode('progress_tracker', 'progress_tracker_shortcode');
