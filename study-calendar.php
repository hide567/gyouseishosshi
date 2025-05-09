<?php
/**
 * 行政書士試験勉強カレンダー機能
 * 
 * @package 行政書士試験ブログ（Astra子テーマ）
 */

if (!defined('ABSPATH')) {
    exit; // 直接アクセスを禁止
}

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

/**
 * 勉強カレンダー管理ページの内容（4月から12月）
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
