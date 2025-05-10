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
            __('学習進捗状況', 'gyouseishoshi-astra-child'),
            array('description' => __('各科目の学習進捗を表示します。', 'gyouseishoshi-astra-child'))
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
        $title = !empty($instance['title']) ? $instance['title'] : __('学習進捗状況', 'gyouseishoshi-astra-child');
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('タイトル:', 'gyouseishoshi-astra-child'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <?php _e('進捗データは管理画面の「設定 > 学習進捗管理」から編集できます。', 'gyouseishoshi-astra-child'); ?>
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
        __('学習進捗管理', 'gyouseishoshi-astra-child'),
        __('学習進捗管理', 'gyouseishoshi-astra-child'),
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
        echo '<div class="notice notice-success is-dismissible"><p>' . __('進捗状況を更新しました。', 'gyouseishoshi-astra-child') . '</p></div>';
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
        <h1><?php _e('学習進捗管理', 'gyouseishoshi-astra-child'); ?></h1>
        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('憲法', 'gyouseishoshi-astra-child'); ?></th>
                    <td>
                        <label><?php _e('進捗率(%):', 'gyouseishoshi-astra-child'); ?> <input type="number" name="constitutional_percent" value="<?php echo esc_attr($progress_data['constitutional']['percent']); ?>" min="0" max="100"></label><br>
                        <label><?php _e('完了章数:', 'gyouseishoshi-astra-child'); ?> <input type="number" name="constitutional_completed" value="<?php echo esc_attr($progress_data['constitutional']['completed']); ?>" min="0"></label><br>
                        <label><?php _e('総章数:', 'gyouseishoshi-astra-child'); ?> <input type="number" name="constitutional_total" value="<?php echo esc_attr($progress_data['constitutional']['total']); ?>" min="1"></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('行政法', 'gyouseishoshi-astra-child'); ?></th>
                    <td>
                        <label><?php _e('進捗率(%):', 'gyouseishoshi-astra-child'); ?> <input type="number" name="administrative_percent" value="<?php echo esc_attr($progress_data['administrative']['percent']); ?>" min="0" max="100"></label><br>
                        <label><?php _e('完了章数:', 'gyouseishoshi-astra-child'); ?> <input type="number" name="administrative_completed" value="<?php echo esc_attr($progress_data['administrative']['completed']); ?>" min="0"></label><br>
                        <label><?php _e('総章数:', 'gyouseishoshi-astra-child'); ?> <input type="number" name="administrative_total" value="<?php echo esc_attr($progress_data['administrative']['total']); ?>" min="1"></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('民法', 'gyouseishoshi-astra-child'); ?></th>
                    <td>
                        <label><?php _e('進捗率(%):', 'gyouseishoshi-astra-child'); ?> <input type="number" name="civil_percent" value="<?php echo esc_attr($progress_data['civil']['percent']); ?>" min="0" max="100"></label><br>
                        <label><?php _e('完了章数:', 'gyouseishoshi-astra-child'); ?> <input type="number" name="civil_completed" value="<?php echo esc_attr($progress_data['civil']['completed']); ?>" min="0"></label><br>
                        <label><?php _e('総章数:', 'gyouseishoshi-astra-child'); ?> <input type="number" name="civil_total" value="<?php echo esc_attr($progress_data['civil']['total']); ?>" min="1"></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('商法・会社法', 'gyouseishoshi-astra-child'); ?></th>
                    <td>
                        <label><?php _e('進捗率(%):', 'gyouseishoshi-astra-child'); ?> <input type="number" name="commercial_percent" value="<?php echo esc_attr($progress_data['commercial']['percent']); ?>" min="0" max="100"></label><br>
                        <label><?php _e('完了章数:', 'gyouseishoshi-astra-child'); ?> <input type="number" name="commercial_completed" value="<?php echo esc_attr($progress_data['commercial']['completed']); ?>" min="0"></label><br>
                        <label><?php _e('総章数:', 'gyouseishoshi-astra-child'); ?> <input type="number" name="commercial_total" value="<?php echo esc_attr($progress_data['commercial']['total']); ?>" min="1"></label>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="submit_progress" id="submit" class="button button-primary" value="<?php _e('変更を保存', 'gyouseishoshi-astra-child'); ?>">
            </p>
        </form>
    </div>
    <?php
}

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
add_action('astra_header_after', 'gyouseishoshi_display_countdown');

/**
 * カウントダウンショートコード
 */
function gyouseishoshi_countdown_shortcode() {
    return gyouseishoshi_exam_countdown();
}
add_shortcode('exam_countdown', 'gyouseishoshi_countdown_shortcode');

// 行政書士試験勉強カレンダー機能を含める
include_once(get_stylesheet_directory() . '/inc/study-calendar.php');

// コミュニティ機能を含める
include_once(get_stylesheet_directory() . '/inc/community-functions.php');

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

