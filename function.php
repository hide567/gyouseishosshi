<?php
/**
 * 行政書士試験ブログのテーマ機能
 *
 * @package 行政書士試験ブログ
 */

if (!defined('ABSPATH')) {
    exit; // 直接アクセスを禁止
}

/**
 * テーマのセットアップ
 */
function gyouseishoshi_setup() {
    // 自動フィードリンクを有効化
    add_theme_support('automatic-feed-links');

    // titleタグのサポート
    add_theme_support('title-tag');

    // アイキャッチ画像のサポート
    add_theme_support('post-thumbnails');

    // HTML5対応
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ));

    // エディタスタイルのサポート
    add_theme_support('editor-styles');
    
    // ナビゲーションメニュー登録
    register_nav_menus(array(
        'primary' => __('メインメニュー', 'gyouseishoshi'),
        'footer' => __('フッターメニュー', 'gyouseishoshi'),
    ));
}
add_action('after_setup_theme', 'gyouseishoshi_setup');

/**
 * スタイルシートとスクリプトの読み込み
 */
function gyouseishoshi_scripts() {
    // メインのスタイルシート
    wp_enqueue_style('gyouseishoshi-style', get_stylesheet_uri(), array(), wp_get_theme()->get('Version'));
    
    // コメント返信用スクリプト
    if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }
}
add_action('wp_enqueue_scripts', 'gyouseishoshi_scripts');

/**
 * ウィジェットエリアの登録
 */
function gyouseishoshi_widgets_init() {
    register_sidebar(array(
        'name'          => __('サイドバー', 'gyouseishoshi'),
        'id'            => 'sidebar-1',
        'description'   => __('サイドバーに表示されるウィジェット', 'gyouseishoshi'),
        'before_widget' => '<div class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));
    
    register_sidebar(array(
        'name'          => __('フッターウィジェット', 'gyouseishoshi'),
        'id'            => 'footer-widget',
        'description'   => __('フッターに表示されるウィジェット', 'gyouseishoshi'),
        'before_widget' => '<div class="footer-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="footer-widget-title">',
        'after_title'   => '</h4>',
    ));
}
add_action('widgets_init', 'gyouseishoshi_widgets_init');

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
 * カスタムウィジェット：学習進捗
 */
function gyouseishoshi_progress_widget() {
    register_widget('Gyouseishoshi_Progress_Widget');
}
add_action('widgets_init', 'gyouseishoshi_progress_widget');

/**
 * 学習進捗ウィジェットクラス
 */
class Gyouseishoshi_Progress_Widget extends WP_Widget {
    
    function __construct() {
        parent::__construct(
            'gyouseishoshi_progress',
            __('学習進捗状況', 'gyouseishoshi'),
            array('description' => __('各科目の学習進捗を表示します。', 'gyouseishoshi'))
        );
    }
    
    // ウィジェットの表示部分
    public function widget($args, $instance) {
        echo $args['before_widget'];
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        
        // 進捗データ取得（オプションから）
        $progress_data = get_option('gyouseishoshi_progress_data', array(
            'constitutional' => array('percent' => 75, 'completed' => 11, 'total' => 15),
            'administrative' => array('percent' => 60, 'completed' => 9, 'total' => 15),
            'civil' => array('percent' => 45, 'completed' => 9, 'total' => 20),
            'commercial' => array('percent' => 30, 'completed' => 3, 'total' => 10)
        ));
        
        ?>
        <div class="progress-widget">
            <div>
                <p>憲法</p>
                <div class="progress-bar">
                    <div class="progress" style="width: <?php echo esc_attr($progress_data['constitutional']['percent']); ?>%;"></div>
                </div>
                <div class="progress-stats">
                    <span><?php echo esc_html($progress_data['constitutional']['percent']); ?>%</span>
                    <span><?php echo esc_html($progress_data['constitutional']['completed']); ?>/<?php echo esc_html($progress_data['constitutional']['total']); ?>章完了</span>
                </div>
            </div>
            
            <div>
                <p>行政法</p>
                <div class="progress-bar">
                    <div class="progress" style="width: <?php echo esc_attr($progress_data['administrative']['percent']); ?>%;"></div>
                </div>
                <div class="progress-stats">
                    <span><?php echo esc_html($progress_data['administrative']['percent']); ?>%</span>
                    <span><?php echo esc_html($progress_data['administrative']['completed']); ?>/<?php echo esc_html($progress_data['administrative']['total']); ?>章完了</span>
                </div>
            </div>
            
            <div>
                <p>民法</p>
                <div class="progress-bar">
                    <div class="progress" style="width: <?php echo esc_attr($progress_data['civil']['percent']); ?>%;"></div>
                </div>
                <div class="progress-stats">
                    <span><?php echo esc_html($progress_data['civil']['percent']); ?>%</span>
                    <span><?php echo esc_html($progress_data['civil']['completed']); ?>/<?php echo esc_html($progress_data['civil']['total']); ?>章完了</span>
                </div>
            </div>
            
            <div>
                <p>商法・会社法</p>
                <div class="progress-bar">
                    <div class="progress" style="width: <?php echo esc_attr($progress_data['commercial']['percent']); ?>%;"></div>
                </div>
                <div class="progress-stats">
                    <span><?php echo esc_html($progress_data['commercial']['percent']); ?>%</span>
                    <span><?php echo esc_html($progress_data['commercial']['completed']); ?>/<?php echo esc_html($progress_data['commercial']['total']); ?>章完了</span>
                </div>
            </div>
        </div>
        <?php
        
        echo $args['after_widget'];
    }
    
    // 管理画面のフォーム
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('学習進捗状況', 'gyouseishoshi');
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('タイトル:', 'gyouseishoshi'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <?php _e('進捗データは管理画面の「設定 > 学習進捗管理」から編集できます。', 'gyouseishoshi'); ?>
        </p>
        <?php
    }
    
    // ウィジェット設定の保存
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        return $instance;
    }
}

/**
 * 管理画面の設定ページを追加
 */
function gyouseishoshi_add_admin_menu() {
    add_options_page(
        __('学習進捗管理', 'gyouseishoshi'),
        __('学習進捗管理', 'gyouseishoshi'),
        'manage_options',
        'gyouseishoshi-progress',
        'gyouseishoshi_progress_page'
    );
}
add_action('admin_menu', 'gyouseishoshi_add_admin_menu');

/**
 * 設定ページの内容
 */
function gyouseishoshi_progress_page() {
    // 権限チェック
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // 保存処理
    if (isset($_POST['submit_progress'])) {
        // データを取得・サニタイズ
        $progress_data = array(
            'constitutional' => array(
                'percent' => intval($_POST['constitutional_percent']),
                'completed' => intval($_POST['constitutional_completed']),
                'total' => intval($_POST['constitutional_total'])
            ),
            'administrative' => array(
                'percent' => intval($_POST['administrative_percent']),
                'completed' => intval($_POST['administrative_completed']),
                'total' => intval($_POST['administrative_total'])
            ),
            'civil' => array(
                'percent' => intval($_POST['civil_percent']),
                'completed' => intval($_POST['civil_completed']),
                'total' => intval($_POST['civil_total'])
            ),
            'commercial' => array(
                'percent' => intval($_POST['commercial_percent']),
                'completed' => intval($_POST['commercial_completed']),
                'total' => intval($_POST['commercial_total'])
            )
        );
        
        // データを保存
        update_option('gyouseishoshi_progress_data', $progress_data);
        echo '<div class="notice notice-success is-dismissible"><p>' . __('進捗状況を更新しました。', 'gyouseishoshi') . '</p></div>';
    }
    
    // 現在の進捗データを取得
    $progress_data = get_option('gyouseishoshi_progress_data', array(
        'constitutional' => array('percent' => 75, 'completed' => 11, 'total' => 15),
        'administrative' => array('percent' => 60, 'completed' => 9, 'total' => 15),
        'civil' => array('percent' => 45, 'completed' => 9, 'total' => 20),
        'commercial' => array('percent' => 30, 'completed' => 3, 'total' => 10)
    ));
    
    ?>
    <div class="wrap">
        <h1><?php _e('学習進捗管理', 'gyouseishoshi'); ?></h1>
        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('憲法', 'gyouseishoshi'); ?></th>
                    <td>
                        <label><?php _e('進捗率(%):', 'gyouseishoshi'); ?> <input type="number" name="constitutional_percent" value="<?php echo esc_attr($progress_data['constitutional']['percent']); ?>" min="0" max="100"></label><br>
                        <label><?php _e('完了章数:', 'gyouseishoshi'); ?> <input type="number" name="constitutional_completed" value="<?php echo esc_attr($progress_data['constitutional']['completed']); ?>" min="0"></label><br>
                        <label><?php _e('総章数:', 'gyouseishoshi'); ?> <input type="number" name="constitutional_total" value="<?php echo esc_attr($progress_data['constitutional']['total']); ?>" min="1"></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('行政法', 'gyouseishoshi'); ?></th>
                    <td>
                        <label><?php _e('進捗率(%):', 'gyouseishoshi'); ?> <input type="number" name="administrative_percent" value="<?php echo esc_attr($progress_data['administrative']['percent']); ?>" min="0" max="100"></label><br>
                        <label><?php _e('完了章数:', 'gyouseishoshi'); ?> <input type="number" name="administrative_completed" value="<?php echo esc_attr($progress_data['administrative']['completed']); ?>" min="0"></label><br>
                        <label><?php _e('総章数:', 'gyouseishoshi'); ?> <input type="number" name="administrative_total" value="<?php echo esc_attr($progress_data['administrative']['total']); ?>" min="1"></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('民法', 'gyouseishoshi'); ?></th>
                    <td>
                        <label><?php _e('進捗率(%):', 'gyouseishoshi'); ?> <input type="number" name="civil_percent" value="<?php echo esc_attr($progress_data['civil']['percent']); ?>" min="0" max="100"></label><br>
                        <label><?php _e('完了章数:', 'gyouseishoshi'); ?> <input type="number" name="civil_completed" value="<?php echo esc_attr($progress_data['civil']['completed']); ?>" min="0"></label><br>
                        <label><?php _e('総章数:', 'gyouseishoshi'); ?> <input type="number" name="civil_total" value="<?php echo esc_attr($progress_data['civil']['total']); ?>" min="1"></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('商法・会社法', 'gyouseishoshi'); ?></th>
                    <td>
                        <label><?php _e('進捗率(%):', 'gyouseishoshi'); ?> <input type="number" name="commercial_percent" value="<?php echo esc_attr($progress_data['commercial']['percent']); ?>" min="0" max="100"></label><br>
                        <label><?php _e('完了章数:', 'gyouseishoshi'); ?> <input type="number" name="commercial_completed" value="<?php echo esc_attr($progress_data['commercial']['completed']); ?>" min="0"></label><br>
                        <label><?php _e('総章数:', 'gyouseishoshi'); ?> <input type="number" name="commercial_total" value="<?php echo esc_attr($progress_data['commercial']['total']); ?>" min="1"></label>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="submit_progress" id="submit" class="button button-primary" value="<?php _e('変更を保存', 'gyouseishoshi'); ?>">
            </p>
        </form>
    </div>
    <?php
}

/**
 * 抜粋の長さを変更
 */
function gyouseishoshi_excerpt_length($length) {
    return 80;
}
add_filter('excerpt_length', 'gyouseishoshi_excerpt_length');

/**
 * 抜粋の末尾を変更
 */
function gyouseishoshi_excerpt_more($more) {
    return '...';
}
add_filter('excerpt_more', 'gyouseishoshi_excerpt_more');

/**
 * テーマの動作に必要な追加ファイルを読み込み
 */
// inc/template-functions.php がある場合は読み込む
$template_functions = get_template_directory() . '/inc/template-functions.php';
if (file_exists($template_functions)) {
    require_once $template_functions;
}

/**
 * 未分類カテゴリーのスタイル変更とカウントダウン表示
 */
function gyouseishoshi_custom_styles() {
    // 未分類カテゴリーのスタイル
    echo '<style>
        
        /* カウントダウン表示用スタイル */
        .exam-countdown {
            background-color: var(--secondary-color);
            color: white;
            padding: 10px 15px;
            text-align: center;
            font-weight: bold;
            border-radius: 5px;
            margin: 15px 0;
        }
        
        .countdown-number {
            font-size: 1.4em;
            color: #f9ca24; /* 黄色強調 */
        }
    </style>';
}
add_action('wp_head', 'gyouseishoshi_custom_styles');

/**
 * 試験日カウントダウン表示関数
 */
function gyouseishoshi_exam_countdown() {
    // 試験日を設定（2025年11月9日と仮定）
    $exam_date = strtotime('2025-11-09');
    $today = current_time('timestamp');
    
    // 残り日数計算
    $days_left = floor(($exam_date - $today) / (60 * 60 * 24));
    
    // カウントダウンHTML生成
    $countdown_html = '<div class="exam-countdown">';
    $countdown_html .= '行政書士試験まであと <span class="countdown-number">' . $days_left . '</span> 日';
    $countdown_html .= '</div>';
    
    return $countdown_html;
}

/**
 * カウントダウンをサイトヘッダーに表示
 */
function gyouseishoshi_display_countdown() {
    echo gyouseishoshi_exam_countdown();
}
add_action('wp_body_open', 'gyouseishoshi_display_countdown');

/**
 * カウントダウンショートコード
 */
function gyouseishoshi_countdown_shortcode() {
    return gyouseishoshi_exam_countdown();
}
add_shortcode('exam_countdown', 'gyouseishoshi_countdown_shortcode');

/**
 * 行政書士試験勉強カレンダー機能
 * 
 * 4月から12月までの勉強スケジュールを管理するためのカレンダー
 */

/**
 * 勉強カレンダーウィジェットを追加
 */
class Gyouseishoshi_Study_Calendar_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'gyouseishoshi_study_calendar',
            '行政書士勉強カレンダー',
            array('description' => '試験までの勉強スケジュールを表示します')
        );
    }

    public function widget($args, $instance) {
        $title = !empty($instance['title']) ? $instance['title'] : '勉強カレンダー';
        
        echo $args['before_widget'];
        echo $args['before_title'] . $title . $args['after_title'];
        
        // カレンダーの内容を表示
        $this->display_calendar();
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : '勉強カレンダー';
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">タイトル:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" 
                   name="<?php echo $this->get_field_name('title'); ?>" type="text" 
                   value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = !empty($new_instance['title']) ? sanitize_text_field($new_instance['title']) : '';
        return $instance;
    }
    
    private function display_calendar() {
        // 今日の日付
        $today = current_time('Y-m-d');
        $current_month = date('n', strtotime($today));
        $current_year = date('Y', strtotime($today));
        
        // 試験日（2025年11月9日と仮定）
        $exam_date = '2025-11-09';
        
        // スケジュールデータを取得
        $schedules = get_option('gyouseishoshi_study_schedules', array());
        
        // 今月のカレンダーを表示
        $this->render_month_calendar($current_year, $current_month, $today, $exam_date, $schedules);
    }
    
    private function render_month_calendar($year, $month, $today, $exam_date, $schedules) {
        $first_day = mktime(0, 0, 0, $month, 1, $year);
        $days_in_month = date('t', $first_day);
        $day_of_week = date('w', $first_day);
        
        // カレンダーのHTML
        echo '<div class="study-calendar">';
        echo '<div class="calendar-header">';
        echo '<span class="month-year">' . date('Y年n月', $first_day) . '</span>';
        echo '</div>';
        
        echo '<table class="calendar-table">';
        echo '<tr>';
        echo '<th>日</th><th>月</th><th>火</th><th>水</th><th>木</th><th>金</th><th>土</th>';
        echo '</tr>';
        
        echo '<tr>';
        
        // 月の最初の日までの空白を表示
        for ($i = 0; $i < $day_of_week; $i++) {
            echo '<td class="empty-day"></td>';
        }
        
        // 日付を表示
        $current_day = 1;
        $current_position = $day_of_week;
        
        while ($current_day <= $days_in_month) {
            if ($current_position % 7 === 0) {
                echo '</tr><tr>';
            }
            
            $date_string = sprintf('%04d-%02d-%02d', $year, $month, $current_day);
            $class = 'calendar-day';
            
            // 今日の日付にクラスを追加
            if ($date_string === $today) {
                $class .= ' today';
            }
            
            // 試験日にクラスを追加
            if ($date_string === $exam_date) {
                $class .= ' exam-day';
            }
            
            // スケジュールがある日にクラスを追加
            $has_schedule = isset($schedules[$date_string]) && !empty($schedules[$date_string]);
            if ($has_schedule) {
                $class .= ' has-schedule';
            }
            
            echo '<td class="' . $class . '">';
            echo '<div class="day-number">' . $current_day . '</div>';
            
            // スケジュールがある場合は表示
            if ($has_schedule) {
                echo '<div class="schedule-item" title="' . esc_attr($schedules[$date_string]) . '">';
                echo substr($schedules[$date_string], 0, 15) . (strlen($schedules[$date_string]) > 15 ? '...' : '');
                echo '</div>';
            }
            
            echo '</td>';
            
            $current_day++;
            $current_position++;
        }
        
        // 月の最後の日以降の空白を表示
        $remaining_cells = 7 - ($current_position % 7);
        if ($remaining_cells < 7) {
            for ($i = 0; $i < $remaining_cells; $i++) {
                echo '<td class="empty-day"></td>';
            }
        }
        
        echo '</tr>';
        echo '</table>';
        
        // カレンダーの下に操作リンクを表示
        echo '<div class="calendar-footer">';
        echo '<a href="' . admin_url('admin.php?page=gyouseishoshi-study-calendar') . '" class="edit-schedule">スケジュール編集</a>';
        echo '</div>';
        
        echo '</div>';
        
        // カレンダーのスタイル
        echo '<style>
            .study-calendar {
                margin-bottom: 20px;
                font-size: 14px;
            }
            .calendar-header {
                text-align: center;
                margin-bottom: 10px;
            }
            .month-year {
                font-weight: bold;
                font-size: 16px;
            }
            .calendar-table {
                width: 100%;
                border-collapse: collapse;
            }
            .calendar-table th {
                padding: 5px;
                background-color: var(--secondary-color);
                color: white;
                text-align: center;
            }
            .calendar-table td {
                padding: 5px;
                border: 1px solid #ddd;
                height: 30px;
                vertical-align: top;
                width: 14.28%;
            }
            .empty-day {
                background-color: #f9f9f9;
            }
            .day-number {
                font-weight: bold;
                margin-bottom: 2px;
            }
            .today {
                background-color: #f0f8ff;
            }
            .exam-day {
                background-color: #fff0f0;
            }
            .has-schedule {
                background-color: #f0fff0;
            }
            .schedule-item {
                font-size: 11px;
                color: #555;
                cursor: pointer;
            }
            .calendar-footer {
                text-align: right;
                margin-top: 10px;
                font-size: 12px;
            }
            .edit-schedule {
                color: var(--primary-color);
                text-decoration: none;
            }
            .edit-schedule:hover {
                text-decoration: underline;
            }
        </style>';
    }
}

/**
 * 勉強カレンダー管理ページを追加
 */
function gyouseishoshi_study_calendar_menu() {
    add_menu_page(
        '勉強カレンダー管理',
        '勉強カレンダー',
        'edit_posts',
        'gyouseishoshi-study-calendar',
        'gyouseishoshi_study_calendar_page',
        'dashicons-calendar-alt',
        25
    );
}
add_action('admin_menu', 'gyouseishoshi_study_calendar_menu');

 /*勉強カレンダー管理ページの内容（4月から12月）- 修正版
 */
function gyouseishoshi_study_calendar_page() {
    // スケジュールデータを保存
    if (isset($_POST['save_schedules']) && isset($_POST['schedules'])) {
        // 既存のスケジュールを取得
        $existing_schedules = get_option('gyouseishoshi_study_schedules', array());
        
        // 新しいスケジュールを既存のものとマージ
        $schedules = array_merge($existing_schedules, $_POST['schedules']);
        
        // 空のエントリを削除
        foreach ($schedules as $date => $content) {
            if (empty($content)) {
                unset($schedules[$date]);
            } else {
                $schedules[$date] = sanitize_text_field($content);
            }
        }
        
        // 更新したスケジュールを保存
        update_option('gyouseishoshi_study_schedules', $schedules);
        echo '<div class="notice notice-success"><p>スケジュールを保存しました。</p></div>';
    }
    
    // 現在のスケジュールデータを取得
    $schedules = get_option('gyouseishoshi_study_schedules', array());
    
    // 表示する月（4月から12月）
    $months_to_display = array(
        array('year' => 2025, 'month' => 4),
        array('year' => 2025, 'month' => 5),
        array('year' => 2025, 'month' => 6),
        array('year' => 2025, 'month' => 7),
        array('year' => 2025, 'month' => 8),
        array('year' => 2025, 'month' => 9),
        array('year' => 2025, 'month' => 10),
        array('year' => 2025, 'month' => 11),
        array('year' => 2025, 'month' => 12)
    );
    
    // 現在選択されている月（GETパラメータまたはデフォルト）
    $current_tab = isset($_GET['month']) ? intval($_GET['month']) : date('n');
    if ($current_tab < 4 || $current_tab > 12) {
        $current_tab = date('n');
    }
    
    echo '<div class="wrap">';
    echo '<h1>行政書士試験 勉強カレンダー管理</h1>';
    
    // タブナビゲーション
    echo '<h2 class="nav-tab-wrapper">';
    foreach ($months_to_display as $month_data) {
        $month = $month_data['month'];
        $month_name = date_i18n('n月', mktime(0, 0, 0, $month, 1, $month_data['year']));
        $active = ($month == $current_tab) ? 'nav-tab-active' : '';
        echo '<a href="?page=gyouseishoshi-study-calendar&month=' . $month . '" class="nav-tab ' . $active . '">' . $month_name . '</a>';
    }
    echo '</h2>';
    
    // 選択した月のカレンダーを表示
    echo '<form method="post">';
    
    // 現在選択されている月のインデックスを検索
    $selected_month_index = 0;
    foreach ($months_to_display as $index => $month_data) {
        if ($month_data['month'] == $current_tab) {
            $selected_month_index = $index;
            break;
        }
    }
    
    // 選択した月のカレンダーを表示
    display_admin_calendar(
        $months_to_display[$selected_month_index]['year'],
        $months_to_display[$selected_month_index]['month'],
        $schedules
    );
    
    echo '<p><input type="submit" name="save_schedules" class="button button-primary" value="スケジュールを保存"></p>';
    echo '</form>';
    
    echo '</div>';
    
    // 管理画面用のスタイル
    echo '<style>
        .admin-calendar {
            width: 100%;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .admin-calendar-header {
            text-align: center;
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 18px;
        }
        .admin-calendar-table {
            width: 100%;
            border-collapse: collapse;
        }
        .admin-calendar-table th {
            padding: 8px;
            background-color: #2c3e50;
            color: white;
            text-align: center;
        }
        .admin-calendar-table td {
            padding: 8px;
            border: 1px solid #ddd;
            height: 100px;
            vertical-align: top;
        }
        .admin-day-number {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .admin-schedule-input {
            width: 100%;
            height: 80px;
            font-size: 12px;
            box-sizing: border-box;
        }
        .admin-today {
            background-color: #e8f4ff;
        }
        .admin-exam-day {
            background-color: #ffe8e8;
        }
    </style>';
}

/**
 * 管理画面用のカレンダーを表示
 */
function display_admin_calendar($year, $month, $schedules) {
    $first_day = mktime(0, 0, 0, $month, 1, $year);
    $days_in_month = date('t', $first_day);
    $day_of_week = date('w', $first_day);
    $today = current_time('Y-m-d');
    
    // 試験日（2025年11月9日と仮定）
    $exam_date = '2025-11-09';
    
    echo '<div class="admin-calendar">';
    echo '<div class="admin-calendar-header">' . date('Y年n月', $first_day) . '</div>';
    
    echo '<table class="admin-calendar-table">';
    echo '<tr>';
    echo '<th>日</th><th>月</th><th>火</th><th>水</th><th>木</th><th>金</th><th>土</th>';
    echo '</tr>';
    
    echo '<tr>';
    
    // 月の最初の日までの空白を表示
    for ($i = 0; $i < $day_of_week; $i++) {
        echo '<td></td>';
    }
    
    // 日付を表示
    $current_day = 1;
    $current_position = $day_of_week;
    
    while ($current_day <= $days_in_month) {
        if ($current_position % 7 === 0) {
            echo '</tr><tr>';
        }
        
        $date_string = sprintf('%04d-%02d-%02d', $year, $month, $current_day);
        $class = '';
        
        // 今日の日付にクラスを追加
        if ($date_string === $today) {
            $class .= ' admin-today';
        }
        
        // 試験日にクラスを追加
        if ($date_string === $exam_date) {
            $class .= ' admin-exam-day';
        }
        
        $schedule_content = isset($schedules[$date_string]) ? $schedules[$date_string] : '';
        
        echo '<td class="' . $class . '">';
        echo '<div class="admin-day-number">' . $current_day . '</div>';
        echo '<textarea class="admin-schedule-input" name="schedules[' . $date_string . ']" placeholder="スケジュールを入力...">' . esc_textarea($schedule_content) . '</textarea>';
        echo '</td>';
        
        $current_day++;
        $current_position++;
    }
    
    // 月の最後の日以降の空白を表示
    $remaining_cells = 7 - ($current_position % 7);
    if ($remaining_cells < 7) {
        for ($i = 0; $i < $remaining_cells; $i++) {
            echo '<td></td>';
        }
    }
    
    echo '</tr>';
    echo '</table>';
    echo '</div>';
}

/**
 * ウィジェットを登録
 */
function gyouseishoshi_register_study_calendar_widget() {
    register_widget('Gyouseishoshi_Study_Calendar_Widget');
}
add_action('widgets_init', 'gyouseishoshi_register_study_calendar_widget');

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
    include(get_template_directory() . '/template-parts/community-template.php');
    return ob_get_clean();
}
add_shortcode('community_page', 'community_shortcode');

// functions.phpにコミュニティー動作追加
function add_community_scripts() {
    if (is_page('community') || has_shortcode(get_post()->post_content, 'community_page')) {
        wp_enqueue_script('jquery');
        wp_enqueue_script('community-script', get_template_directory_uri() . '/js/community.js', array('jquery'), '1.0', true);
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
add_action('wp_ajax_get_topic_categories', 'get_topic_categories_ajax');add_action('wp_ajax_nopriv_get_topic_categories', 'get_topic_categories_ajax');

// デバッグ用：エラーログを有効化
function enable_community_debug() {
    if (current_user_can('manage_options')) {
        // 管理者向けのみ
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        ini_set('log_errors', 1);
        ini_set('error_log', WP_CONTENT_DIR . '/debug.log');
    }
}
add_action('init', 'enable_community_debug');

// カスタム投稿タイプ登録後にフラッシュルールを行う
function community_rewrite_flush() {
    register_community_post_types();
    register_community_taxonomies();
    flush_rewrite_rules();
}
// この関数を直接実行して、リライトルールを即時更新
function manual_flush_rules() {
    flush_rewrite_rules();
}
add_action('init', 'manual_flush_rules', 20);
