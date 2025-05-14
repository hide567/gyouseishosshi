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
    
    // システムデフォルト科目データ
    $default_subjects = array(
        'constitutional' => '憲法',
        'administrative' => '行政法',
        'civil' => '民法',
        'commercial' => '商法・会社法'
    );
    
    // カスタム科目を取得
    $custom_subjects = get_option('progress_tracker_custom_subjects', array());
    
    // 全科目のマージ（ユーザー追加・編集可能）
    $subjects = get_option('progress_tracker_subjects', $default_subjects);
    
    // 章の構成データを取得
    $chapter_structure = get_option('progress_tracker_chapters', array(
        'constitutional' => array(
            'total' => 15,
            'chapters' => array(),
            'color' => '#4CAF50'
        ),
        'administrative' => array(
            'total' => 15,
            'chapters' => array(),
            'color' => '#4CAF50'
        ),
        'civil' => array(
            'total' => 20,
            'chapters' => array(),
            'color' => '#4CAF50'
        ),
        'commercial' => array(
            'total' => 10,
            'chapters' => array(),
            'color' => '#4CAF50'
        )
    ));
    
    // 進捗データを取得
    $progress_data = get_option('progress_tracker_progress', array());
    
    // 進捗チェック設定を取得
    $progress_settings = get_option('progress_tracker_check_settings', array(
        'first_check_color' => '#e6f7e6',
        'second_check_color' => '#ffebcc'
    ));

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
            
            // 進捗バーの色を保持
            $progress_color = isset($chapter_structure[$subject_key]['color']) ? 
                $chapter_structure[$subject_key]['color'] : '#4CAF50';
            
            $updated_structure[$subject_key] = array(
                'total' => $total_chapters,
                'chapters' => $chapters,
                'color' => $progress_color
            );
        }
        
        // データを保存
        update_option('progress_tracker_chapters', $updated_structure);
        $chapter_structure = $updated_structure;
        
        echo '<div class="notice notice-success is-dismissible"><p>科目と章の構造を保存しました。</p></div>';
    }
    
    // 進捗データを保存（2段階チェック対応）
    if (isset($_POST['save_progress'])) {
        $updated_progress = array();
        
        foreach ($subjects as $subject_key => $subject_name) {
            $subject_progress = array();
            
            // 各章ごとの進捗
            if (isset($chapter_structure[$subject_key]['chapters']) && is_array($chapter_structure[$subject_key]['chapters'])) {
                foreach ($chapter_structure[$subject_key]['chapters'] as $chapter_id => $chapter_data) {
                    $completed_sections = array();
                    
                    // 各節の完了状態を確認（1段階目）
                    for ($section = 1; $section <= $chapter_data['sections']; $section++) {
                        $field_name = $subject_key . '_chapter_' . $chapter_id . '_section_' . $section;
                        $second_check_field = $subject_key . '_chapter_' . $chapter_id . '_section_' . $section . '_second';
                        
                        $check_level = 0;
                        if (isset($_POST[$field_name]) && $_POST[$field_name] == '1') {
                            $check_level = 1;
                        }
                        if (isset($_POST[$second_check_field]) && $_POST[$second_check_field] == '1') {
                            $check_level = 2;
                        }
                        
                        if ($check_level > 0) {
                            $completed_sections[$section] = $check_level;
                        }
                    }
                    
                    if (!empty($completed_sections)) {
                        $subject_progress[$chapter_id] = $completed_sections;
                    }
                }
            }
            
            // 進捗率を計算（1段階目と2段階目は同じ重みで計算）
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
        $progress_color = isset($_POST['progress_color']) ? sanitize_hex_color($_POST['progress_color']) : '#4CAF50';
        
        if (!empty($subject_key) && !empty($subject_name) && $total_chapters > 0) {
            // 既存の科目キーと重複していないか確認
            if (!isset($subjects[$subject_key])) {
                // 科目リストに追加
                $subjects[$subject_key] = $subject_name;
                update_option('progress_tracker_subjects', $subjects);
                
                // カスタム科目として記録
                $custom_subjects[$subject_key] = $subject_name;
                update_option('progress_tracker_custom_subjects', $custom_subjects);
                
                // 章構造も初期化
                $chapter_structure[$subject_key] = array(
                    'total' => $total_chapters,
                    'chapters' => array(),
                    'color' => $progress_color
                );
                update_option('progress_tracker_chapters', $chapter_structure);
                
                // 進捗データも初期化
                $progress_data[$subject_key] = array(
                    'chapters' => array(),
                    'percent' => 0
                );
                update_option('progress_tracker_progress', $progress_data);
                
                echo '<div class="notice notice-success is-dismissible"><p>科目を追加しました。</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>このキーは既に使用されています。</p></div>';
            }
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>すべての項目を入力してください。</p></div>';
        }
    }
    
    // 科目の編集
    if (isset($_POST['edit_subject']) && isset($_POST['study_track_subject_edit_nonce'])) {
        if (wp_verify_nonce($_POST['study_track_subject_edit_nonce'], 'study_track_subject_edit')) {
            $subject_key = sanitize_key($_POST['edit_subject_key']);
            $new_subject_name = sanitize_text_field($_POST['edit_subject_name']);
            $progress_color = sanitize_hex_color($_POST['edit_progress_color']);
            
            if (isset($subjects[$subject_key])) {
                // 科目名を更新
                $subjects[$subject_key] = $new_subject_name;
                update_option('progress_tracker_subjects', $subjects);
                
                // カスタム科目リストの場合は更新
                if (isset($custom_subjects[$subject_key])) {
                    $custom_subjects[$subject_key] = $new_subject_name;
                    update_option('progress_tracker_custom_subjects', $custom_subjects);
                }
                
                // 進捗バーの色を更新
                if (isset($chapter_structure[$subject_key])) {
                    $chapter_structure[$subject_key]['color'] = $progress_color;
                    update_option('progress_tracker_chapters', $chapter_structure);
                }
                
                echo '<div class="notice notice-success is-dismissible"><p>科目を更新しました。</p></div>';
            }
        }
    }
    
    // 科目の削除（デフォルト科目も削除可能）
    if (isset($_POST['delete_subject']) && isset($_POST['study_track_subject_delete_nonce'])) {
        if (wp_verify_nonce($_POST['study_track_subject_delete_nonce'], 'study_track_subject_delete')) {
            $subject_key = sanitize_key($_POST['delete_subject']);
            
            // 科目リストから削除
            if (isset($subjects[$subject_key])) {
                unset($subjects[$subject_key]);
                update_option('progress_tracker_subjects', $subjects);
                
                // カスタム科目からも削除（カスタム科目の場合）
                if (isset($custom_subjects[$subject_key])) {
                    unset($custom_subjects[$subject_key]);
                    update_option('progress_tracker_custom_subjects', $custom_subjects);
                }
                
                // 章構造からも削除
                if (isset($chapter_structure[$subject_key])) {
                    unset($chapter_structure[$subject_key]);
                    update_option('progress_tracker_chapters', $chapter_structure);
                }
                
                // 進捗データからも削除
                if (isset($progress_data[$subject_key])) {
                    unset($progress_data[$subject_key]);
                    update_option('progress_tracker_progress', $progress_data);
                }
                
                echo '<div class="notice notice-success is-dismissible"><p>科目を削除しました。</p></div>';
            }
        }
    }
    
    // 進捗チェック設定の保存
    if (isset($_POST['save_check_settings'])) {
        $updated_settings = array(
            'first_check_color' => sanitize_hex_color($_POST['first_check_color']),
            'second_check_color' => sanitize_hex_color($_POST['second_check_color'])
        );
        
        update_option('progress_tracker_check_settings', $updated_settings);
        $progress_settings = $updated_settings;
        
        echo '<div class="notice notice-success is-dismissible"><p>チェック設定を保存しました。</p></div>';
    }

    
    // タブの処理
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'structure';
    ?>
    <div class="wrap">
        <h1>学習進捗管理</h1>   
        <h2 class="nav-tab-wrapper">
    <a href="?page=progress-tracker&tab=subjects" class="nav-tab <?php echo $active_tab == 'subjects' ? 'nav-tab-active' : ''; ?>">科目管理</a>
    <a href="?page=progress-tracker&tab=structure" class="nav-tab <?php echo $active_tab == 'structure' ? 'nav-tab-active' : ''; ?>">科目構造設定</a>
    <a href="?page=progress-tracker&tab=progress" class="nav-tab <?php echo $active_tab == 'progress' ? 'nav-tab-active' : ''; ?>">進捗管理</a>
    <a href="?page=progress-tracker&tab=settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">設定</a>
</h2>
        <?php if ($active_tab == 'subjects'): ?>
        <!-- 科目管理タブ -->
        <div class="admin-section">
            <h3>科目の追加</h3>
            <p>試験や資格ごとに科目を追加できます。</p>
            
            <form method="post" action="">
                <?php wp_nonce_field('study_track_subject_meta_save', 'study_track_subject_meta_nonce'); ?>
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
                    <tr>
                        <th scope="row">進捗バーの色</th>
                        <td>
                            <input type="color" name="progress_color" value="#4CAF50">
                            <p class="description">この科目の進捗バーに使用する色</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="add_subject" class="button button-primary" value="科目を追加">
                </p>
            </form>
        </div>
        
        <div class="admin-section">
            <h3>科目の管理</h3>
            
            <?php if (!empty($subjects)): ?>
                <form method="post" action="">
                    <?php wp_nonce_field('study_track_subject_delete', 'study_track_subject_delete_nonce'); ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>科目キー</th>
                                <th>科目名</th>
                                <th>章数</th>
                                <th>進捗</th>
                                <th>進捗バーの色</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subjects as $key => $name): 
                                $total_chapters = isset($chapter_structure[$key]['total']) ? $chapter_structure[$key]['total'] : 0;
                                $percent = isset($progress_data[$key]['percent']) ? $progress_data[$key]['percent'] : 0;
                                $color = isset($chapter_structure[$key]['color']) ? $chapter_structure[$key]['color'] : '#4CAF50';
                            ?>
                                <tr>
                                    <td><?php echo esc_html($key); ?></td>
                                    <td><?php echo esc_html($name); ?></td>
                                    <td><?php echo esc_html($total_chapters); ?></td>
                                    <td>
                                        <div class="progress-mini-bar">
                                            <div class="progress-mini-fill" style="width:<?php echo esc_attr($percent); ?>%; background-color:<?php echo esc_attr($color); ?>;"></div>
                                        </div>
                                        <?php echo esc_html($percent); ?>%
                                    </td>
                                    <td><span style="display:inline-block; width:20px; height:20px; background-color:<?php echo esc_attr($color); ?>; border-radius:3px;"></span></td>
                                    <td>
                                        <button type="button" class="button button-small edit-subject" data-key="<?php echo esc_attr($key); ?>" data-name="<?php echo esc_attr($name); ?>" data-color="<?php echo esc_attr($color); ?>">編集</button>
                                        <button type="submit" name="delete_subject" value="<?php echo esc_attr($key); ?>" class="button button-small button-link-delete" onclick="return confirm('この科目を削除してもよろしいですか？関連するすべての進捗データも削除されます。');">削除</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </form>
            <?php else: ?>
                <p>現在、科目がありません。「科目を追加」フォームから新しい科目を追加してください。</p>
            <?php endif; ?>
            
            <!-- 科目編集モーダル -->
            <div id="edit-subject-modal" class="modal-overlay" style="display:none;">
                <div class="modal-content">
                    <h3>科目の編集</h3>
                    <form method="post" action="">
                        <?php wp_nonce_field('study_track_subject_edit', 'study_track_subject_edit_nonce'); ?>
                        <input type="hidden" id="edit_subject_key" name="edit_subject_key" value="">
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">科目名</th>
                                <td>
                                    <input type="text" id="edit_subject_name" name="edit_subject_name" class="regular-text">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">進捗バーの色</th>
                                <td>
                                    <input type="color" id="edit_progress_color" name="edit_progress_color" value="#4CAF50">
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <input type="submit" name="edit_subject" class="button button-primary" value="更新">
                            <button type="button" class="button close-modal">キャンセル</button>
                        </p>
                    </form>
                </div>
            </div>
        </div>
        
        <?php elseif ($active_tab == 'structure'): ?>
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
    <p>完了した節にチェックを入れてください。第1段階（理解した）と第2段階（習得した）の2段階でチェックできます。</p>
    
    <div class="progress-settings">
        <?php foreach ($subjects as $subject_key => $subject_name): ?>
            <div class="subject-progress">
                <h4><?php echo esc_html($subject_name); ?> 
                    <span class="percent-display">
                        (<?php echo isset($progress_data[$subject_key]['percent']) ? esc_html($progress_data[$subject_key]['percent']) : 0; ?>%完了)
                    </span>
                </h4>
                
                <div class="progress-bar-container">
                    <div class="progress-bar-fill" style="width: <?php echo isset($progress_data[$subject_key]['percent']) ? esc_attr($progress_data[$subject_key]['percent']) : 0; ?>%; background-color: <?php echo isset($chapter_structure[$subject_key]['color']) ? esc_attr($chapter_structure[$subject_key]['color']) : '#4CAF50'; ?>;"></div>
                </div>
                
                <?php if (isset($chapter_structure[$subject_key]['chapters']) && !empty($chapter_structure[$subject_key]['chapters'])): ?>
                    <div class="chapters-container">
                        <?php foreach ($chapter_structure[$subject_key]['chapters'] as $chapter_id => $chapter_data): 
                            // 章の全節が完了しているか確認
                            $all_sections_completed = false;
                            $all_sections_mastered = false;
                            
                            if (isset($progress_data[$subject_key]['chapters'][$chapter_id])) {
                                $completed_sections = $progress_data[$subject_key]['chapters'][$chapter_id];
                                $all_sections_completed = count($completed_sections) == $chapter_data['sections'];
                                
                                // 第2段階（習得）まで完了しているか確認
                                $mastered_count = 0;
                                foreach ($completed_sections as $section_num => $level) {
                                    if ($level >= 2) $mastered_count++;
                                }
                                $all_sections_mastered = $mastered_count == $chapter_data['sections'];
                            }
                            
                            // 章の背景色を設定
                            $chapter_style = '';
                            if ($all_sections_mastered) {
                                $chapter_style = 'background-color: ' . esc_attr($progress_settings['second_check_color']) . ';';
                            } elseif ($all_sections_completed) {
                                $chapter_style = 'background-color: ' . esc_attr($progress_settings['first_check_color']) . ';';
                            }
                        ?>
                            <div class="chapter-item" style="<?php echo $chapter_style; ?>">
                                <h5><?php echo esc_html($chapter_data['title']); ?></h5>
                                
                                <div class="sections-list">
                                    <?php for ($section = 1; $section <= $chapter_data['sections']; $section++): 
                                        // 1段階目と2段階目のチェック状態
                                        $first_check = false;
                                        $second_check = false;
                                        
                                        if (isset($progress_data[$subject_key]['chapters'][$chapter_id][$section])) {
                                            $check_level = $progress_data[$subject_key]['chapters'][$chapter_id][$section];
                                            $first_check = $check_level >= 1;
                                            $second_check = $check_level >= 2;
                                        }
                                        
                                        // セクションの背景色を設定
                                        $section_style = '';
                                        if ($second_check) {
                                            $section_style = 'background-color: ' . esc_attr($progress_settings['second_check_color']) . ';';
                                        } elseif ($first_check) {
                                            $section_style = 'background-color: ' . esc_attr($progress_settings['first_check_color']) . ';';
                                        }
                                    ?>
                                        <div class="section-item" style="<?php echo $section_style; ?>">
                                            <span class="section-label">節<?php echo $section; ?></span>
                                            <div class="section-checkboxes">
                                                <label title="理解した">
                                                    <input type="checkbox" name="<?php echo $subject_key; ?>_chapter_<?php echo $chapter_id; ?>_section_<?php echo $section; ?>" value="1" <?php checked($first_check); ?>>
                                                    <span class="check-label">理解</span>
                                                </label>
                                                <label title="習得した">
                                                    <input type="checkbox" name="<?php echo $subject_key; ?>_chapter_<?php echo $chapter_id; ?>_section_<?php echo $section; ?>_second" value="1" <?php checked($second_check); ?>>
                                                    <span class="check-label">習得</span>
                                                </label>
                                            </div>
                                        </div>
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

<?php elseif ($active_tab == 'settings'): ?>
<!-- 設定タブ -->
<h3>進捗管理設定</h3>
<form method="post" action="">
    <h4>チェック表示設定</h4>
    <p>進捗管理で使用する2段階チェックの色を設定します。</p>
    
    <table class="form-table">
        <tr>
            <th scope="row">第1段階（理解した）の色</th>
            <td>
                <input type="color" name="first_check_color" value="<?php echo esc_attr($progress_settings['first_check_color']); ?>">
                <p class="description">第1段階でチェックされた項目の背景色</p>
            </td>
        </tr>
        <tr>
            <th scope="row">第2段階（習得した）の色</th>
            <td>
                <input type="color" name="second_check_color" value="<?php echo esc_attr($progress_settings['second_check_color']); ?>">
                <p class="description">第2段階までチェックされた項目の背景色</p>
            </td>
        </tr>
    </table>
    
    <h4>ショートコードの使用方法</h4>
    <div class="shortcode-usage">
        <p>進捗表示ショートコード: <code>[progress_tracker]</code></p>
        <p>特定の科目のみ表示: <code>[progress_tracker subject="constitutional,civil"]</code></p>
        <p>スタイル指定: <code>[progress_tracker style="simple"]</code> (スタイル: default, simple, compact)</p>
        <p>試験カウントダウン: <code>[exam_countdown]</code></p>
        <p>カスタム試験名: <code>[exam_countdown title="司法試験"]</code></p>
        <p>暗記カード表示: <code>[dialog_cards id="1"]</code>または<code>[dialog_cards subject="constitutional"]</code></p>
    </div>
    
    <p class="submit">
        <input type="submit" name="save_check_settings" class="button button-primary" value="設定を保存">
    </p>
</form>
<?php endif; ?>
    </div>
    
    <style>
    /* 全体のスタイリング */
    .admin-section {
        margin-bottom: 30px;
        padding: 20px;
        background-color: #fff;
        border: 1px solid #ddd;
        border-radius: 5px;
    }
    
    /* 科目セクション */
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
    
    /* 進捗バーのスタイル */
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
    
    /* 科目の進捗管理 */
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
    
    /* 節のスタイル */
    .sections-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .section-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px;
        border: 1px solid #eee;
        border-radius: 3px;
    }
    .section-label {
        font-weight: bold;
    }
    .section-checkboxes {
        display: flex;
        gap: 10px;
    }
    .section-checkboxes label {
        display: flex;
        align-items: center;
        gap: 3px;
        cursor: pointer;
    }
    .check-label {
        font-size: 0.85em;
    }
    
    /* モーダルスタイル */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
        z-index: 100;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    .modal-content {
        background-color: #fff;
        padding: 20px;
        border-radius: 5px;
        width: 500px;
        max-width: 90%;
    }
    
    /* ミニ進捗バー */
    .progress-mini-bar {
        height: 10px;
        width: 100px;
        background-color: #f1f1f1;
        border-radius: 5px;
        overflow: hidden;
        margin-bottom: 5px;
    }
    .progress-mini-fill {
        height: 100%;
        background-color: #4CAF50;
    }
    
    /* ショートコード使用方法 */
    .shortcode-usage {
        background: #f9f9f9;
        padding: 15px;
        border-radius: 5px;
        border-left: 4px solid #0073aa;
    }
    .shortcode-usage code {
        background: #fff;
        padding: 3px 5px;
        border-radius: 3px;
    }
    
    /* その他のスタイル */
    .percent-display {
        font-weight: normal;
        color: #666;
    }
    </style>
    <?php
}

/**
* 進捗表示ウィジェット（2段階チェック対応）
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
       $subjects = get_option('progress_tracker_subjects', array(
           'constitutional' => '憲法',
           'administrative' => '行政法',
           'civil' => '民法',
           'commercial' => '商法・会社法'
       ));
       
       // 進捗データを取得
       $progress_data = get_option('progress_tracker_progress', array());
       
       // 章構造データを取得
       $chapter_structure = get_option('progress_tracker_chapters', array());
       
       ?>
       <div class="progress-widget">
           <?php foreach ($subjects as $subject_key => $subject_name): ?>
               <?php
               $percent = isset($progress_data[$subject_key]['percent']) ? $progress_data[$subject_key]['percent'] : 0;
               $completed_chapters = 0;
               $mastered_chapters = 0;
               $total_chapters = isset($chapter_structure[$subject_key]['total']) ? $chapter_structure[$subject_key]['total'] : 0;
               
               // 完了した章と習得した章をカウント
               if (isset($progress_data[$subject_key]['chapters'])) {
                   $completed_chapters = count($progress_data[$subject_key]['chapters']);
                   
                   // 習得した章（すべての節が第2段階まで完了している章）をカウント
                   foreach ($progress_data[$subject_key]['chapters'] as $chapter_id => $sections) {
                       $all_mastered = true;
                       $total_sections = isset($chapter_structure[$subject_key]['chapters'][$chapter_id]['sections']) ? 
                           $chapter_structure[$subject_key]['chapters'][$chapter_id]['sections'] : 0;
                           
                       if ($total_sections > 0) {
                           $mastered_count = 0;
                           foreach ($sections as $section_num => $level) {
                               if ($level >= 2) $mastered_count++;
                           }
                           if ($mastered_count == $total_sections) {
                               $mastered_chapters++;
                           }
                       }
                   }
               }
               
               // 進捗バーの色
               $bar_color = isset($chapter_structure[$subject_key]['color']) ? $chapter_structure[$subject_key]['color'] : '#4CAF50';
               ?>
               <div class="subject-item">
                   <p><?php echo esc_html($subject_name); ?></p>
                   <div class="progress-bar">
                       <div class="progress" style="width: <?php echo esc_attr($percent); ?>%; background-color: <?php echo esc_attr($bar_color); ?>;"></div>
                   </div>
                   <div class="progress-stats">
                       <span><?php echo esc_html($percent); ?>%</span>
                       <span>完了: <?php echo esc_html($completed_chapters); ?>/<?php echo esc_html($total_chapters); ?>章</span>
                       <span>習得: <?php echo esc_html($mastered_chapters); ?>/<?php echo esc_html($total_chapters); ?>章</span>
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
       }
       .progress-widget .progress-stats {
           display: flex;
           flex-wrap: wrap;
           justify-content: space-between;
           font-size: 0.85em;
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
* フロントエンド用Ajax処理（進捗の更新）
*/
function progress_tracker_ajax_toggle_completion() {
    // 認証チェック
    check_ajax_referer('progress_tracker_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => '認証が必要です。'));
        return;
    }
    
    // パラメータを取得
    $subject_key = sanitize_key($_POST['subject']);
    $chapter_id = intval($_POST['chapter']);
    $section_id = intval($_POST['section']);
    $check_level = intval($_POST['check_level']); // 1=理解, 2=習得
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
    if ($completed) {
        // チェックを追加
        $progress_data[$subject_key]['chapters'][$chapter_id][$section_id] = $check_level;
    } else {
        // チェックを削除（レベルが下がる場合はそのレベルに設定）
        if ($check_level == 1 && isset($progress_data[$subject_key]['chapters'][$chapter_id][$section_id]) && $progress_data[$subject_key]['chapters'][$chapter_id][$section_id] > 1) {
            // 第2段階から第1段階に下げる
            $progress_data[$subject_key]['chapters'][$chapter_id][$section_id] = 1;
        } else {
            // 完全に削除
            unset($progress_data[$subject_key]['chapters'][$chapter_id][$section_id]);
            
            // 空の章は削除
            if (empty($progress_data[$subject_key]['chapters'][$chapter_id])) {
                unset($progress_data[$subject_key]['chapters'][$chapter_id]);
            }
        }
    }
    
    // 進捗率を再計算
    $total_sections = 0;
    $completed_count = 0;
    
    // 章構造データを取得
    $chapter_structure = get_option('progress_tracker_chapters', array());
    
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
add_action('wp_ajax_progress_tracker_toggle_completion', 'progress_tracker_ajax_toggle_completion');

/**
* ショートコード：進捗表示（2段階チェック対応）
*/
function progress_tracker_shortcode($atts) {
   // 属性の初期化
   $atts = shortcode_atts(array(
       'subject' => '',    // 特定の科目のみ表示
       'style' => 'default', // スタイル
       'interactive' => is_user_logged_in() ? 'yes' : 'no' // インタラクティブモード
   ), $atts, 'progress_tracker');
   
   // 科目データ
   $subjects = get_option('progress_tracker_subjects', array(
       'constitutional' => '憲法',
       'administrative' => '行政法',
       'civil' => '民法',
       'commercial' => '商法・会社法'
   ));
   
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
   
   // チェック設定を取得
   // チェック設定を取得
   $progress_settings = get_option('progress_tracker_check_settings', array(
       'first_check_color' => '#e6f7e6',
       'second_check_color' => '#ffebcc'
   ));
   
   // インタラクティブモード
   $interactive = $atts['interactive'] === 'yes' && is_user_logged_in();
   
   // 出力開始
   ob_start();
   ?>
   <div class="progress-tracker-shortcode style-<?php echo esc_attr($atts['style']); ?>" data-nonce="<?php echo wp_create_nonce('progress_tracker_nonce'); ?>">
       <?php if (count($subjects) > 1): ?>
       <div class="progress-tabs">
           <?php 
           $first = true;
           foreach ($subjects as $subject_key => $subject_name): 
               $tab_class = $first ? 'progress-tab active' : 'progress-tab';
               $first = false;
           ?>
               <div class="<?php echo $tab_class; ?>" data-subject="<?php echo esc_attr($subject_key); ?>"><?php echo esc_html($subject_name); ?></div>
           <?php endforeach; ?>
       </div>
       <?php endif; ?>
       
       <?php 
       $first = true;
       foreach ($subjects as $subject_key => $subject_name): 
           $percent = isset($progress_data[$subject_key]['percent']) ? $progress_data[$subject_key]['percent'] : 0;
           $display = $first ? 'block' : 'none';
           $first = false;
           
           // 進捗バーの色
           $bar_color = isset($chapter_structure[$subject_key]['color']) ? $chapter_structure[$subject_key]['color'] : '#4CAF50';
       ?>
           <div class="progress-subject" data-subject="<?php echo esc_attr($subject_key); ?>" style="display: <?php echo $display; ?>;">
               <h4><?php echo esc_html($subject_name); ?> <span class="percent">(<?php echo esc_html($percent); ?>%)</span></h4>
               <div class="progress-bar-container">
                   <div class="progress-bar-fill" style="width: <?php echo esc_attr($percent); ?>%; background-color: <?php echo esc_attr($bar_color); ?>;"></div>
               </div>
               
               <?php if (isset($chapter_structure[$subject_key]['chapters']) && !empty($chapter_structure[$subject_key]['chapters'])): ?>
                   <div class="progress-chapters">
                       <?php foreach ($chapter_structure[$subject_key]['chapters'] as $chapter_id => $chapter_data): 
                           // 章の進捗状況
                           $chapter_completed = false;
                           $chapter_mastered = false;
                           $chapter_style = '';
                           
                           if (isset($progress_data[$subject_key]['chapters'][$chapter_id])) {
                               $sections = $progress_data[$subject_key]['chapters'][$chapter_id];
                               $total_sections = $chapter_data['sections'];
                               
                               $chapter_completed = count($sections) == $total_sections;
                               
                               // 習得までの進捗を確認
                               $mastered_count = 0;
                               foreach ($sections as $section_num => $level) {
                                   if ($level >= 2) $mastered_count++;
                               }
                               $chapter_mastered = $mastered_count == $total_sections;
                               
                               // 背景色を設定
                               if ($chapter_mastered) {
                                   $chapter_style = 'background-color: ' . esc_attr($progress_settings['second_check_color']) . ';';
                               } elseif ($chapter_completed) {
                                   $chapter_style = 'background-color: ' . esc_attr($progress_settings['first_check_color']) . ';';
                               }
                           }
                           
                           $expandable = $interactive && isset($chapter_data['sections']) && $chapter_data['sections'] > 0;
                           $chapter_class = $expandable ? 'chapter-item expandable' : 'chapter-item';
                           $chapter_class .= $chapter_completed ? ' completed' : '';
                           $chapter_class .= $chapter_mastered ? ' mastered' : '';
                       ?>
                           <div class="<?php echo $chapter_class; ?>" data-subject="<?php echo esc_attr($subject_key); ?>" data-chapter="<?php echo esc_attr($chapter_id); ?>" style="<?php echo $chapter_style; ?>">
                               <div class="chapter-header">
                                   <span class="chapter-title"><?php echo esc_html($chapter_data['title']); ?></span>
                                   <?php if ($expandable): ?>
                                   <span class="chapter-toggle">+</span>
                                   <?php endif; ?>
                               </div>
                               
                               <?php if ($expandable): ?>
                               <div class="chapter-sections" style="display: none;">
                                   <?php for ($section = 1; $section <= $chapter_data['sections']; $section++): 
                                       $section_level = 0;
                                       $section_style = '';
                                       
                                       if (isset($progress_data[$subject_key]['chapters'][$chapter_id][$section])) {
                                           $section_level = $progress_data[$subject_key]['chapters'][$chapter_id][$section];
                                           
                                           if ($section_level >= 2) {
                                               $section_style = 'background-color: ' . esc_attr($progress_settings['second_check_color']) . ';';
                                           } elseif ($section_level >= 1) {
                                               $section_style = 'background-color: ' . esc_attr($progress_settings['first_check_color']) . ';';
                                           }
                                       }
                                       
                                       $section_class = 'section-item';
                                       $section_class .= $section_level >= 1 ? ' checked' : '';
                                       $section_class .= $section_level >= 2 ? ' mastered' : '';
                                   ?>
                                       <div class="<?php echo $section_class; ?>" data-subject="<?php echo esc_attr($subject_key); ?>" data-chapter="<?php echo esc_attr($chapter_id); ?>" data-section="<?php echo esc_attr($section); ?>" style="<?php echo $section_style; ?>">
                                           <span class="section-title">節<?php echo $section; ?></span>
                                           <?php if ($interactive): ?>
                                           <div class="section-checkboxes">
                                               <label title="理解した" class="checkbox-label">
                                                   <input type="checkbox" class="section-check-level-1" <?php checked($section_level >= 1); ?>>
                                                   <span>理解</span>
                                               </label>
                                               <label title="習得した" class="checkbox-label">
                                                   <input type="checkbox" class="section-check-level-2" <?php checked($section_level >= 2); ?>>
                                                   <span>習得</span>
                                               </label>
                                           </div>
                                           <?php else: ?>
                                           <div class="section-status">
                                               <?php if ($section_level >= 2): ?>
                                               <span class="status-mastered">習得済</span>
                                               <?php elseif ($section_level >= 1): ?>
                                               <span class="status-checked">理解済</span>
                                               <?php endif; ?>
                                           </div>
                                           <?php endif; ?>
                                       </div>
                                   <?php endfor; ?>
                               </div>
                               <?php endif; ?>
                           </div>
                       <?php endforeach; ?>
                   </div>
               <?php else: ?>
                   <p class="no-chapters">この科目にはまだ章が設定されていません。</p>
               <?php endif; ?>
           </div>
       <?php endforeach; ?>
   </div>
   
   <?php if ($interactive): ?>
   <script>
   jQuery(document).ready(function($) {
       // タブ切り替え
       $('.progress-tracker-shortcode .progress-tab').on('click', function() {
           var subjectKey = $(this).data('subject');
           
           // タブ切り替え
           $('.progress-tracker-shortcode .progress-tab').removeClass('active');
           $(this).addClass('active');
           
           // 科目表示切り替え
           $('.progress-tracker-shortcode .progress-subject').hide();
           $('.progress-tracker-shortcode .progress-subject[data-subject="' + subjectKey + '"]').show();
       });
       
       // 章の開閉
       $('.progress-tracker-shortcode .chapter-header').on('click', function() {
           var $chapter = $(this).closest('.chapter-item');
           var $sections = $chapter.find('.chapter-sections');
           var $toggle = $(this).find('.chapter-toggle');
           
           if ($sections.is(':visible')) {
               $sections.slideUp(200);
               $toggle.text('+');
           } else {
               $sections.slideDown(200);
               $toggle.text('-');
           }
       });
       
       // チェックボックスの処理
       $('.progress-tracker-shortcode .section-check-level-1').on('change', function() {
           var $section = $(this).closest('.section-item');
           var $level2Check = $section.find('.section-check-level-2');
           var isChecked = $(this).prop('checked');
           
           // レベル1のチェックを外した場合、レベル2も自動的に外す
           if (!isChecked) {
               $level2Check.prop('checked', false);
           }
           
           updateSectionStatus($section, isChecked ? 1 : 0);
       });
       
       $('.progress-tracker-shortcode .section-check-level-2').on('change', function() {
           var $section = $(this).closest('.section-item');
           var $level1Check = $section.find('.section-check-level-1');
           var isChecked = $(this).prop('checked');
           
           // レベル2をチェックした場合、レベル1も自動的にチェック
           if (isChecked) {
               $level1Check.prop('checked', true);
           }
           
           updateSectionStatus($section, isChecked ? 2 : 1);
       });
       
       function updateSectionStatus($section, level) {
           var subject = $section.data('subject');
           var chapter = $section.data('chapter');
           var section = $section.data('section');
           var nonce = $('.progress-tracker-shortcode').data('nonce');
           
           // レベルに応じたスタイル更新
           $section.removeClass('checked mastered');
           
           if (level >= 1) {
               $section.addClass('checked');
           }
           
           if (level >= 2) {
               $section.addClass('mastered');
           }
           
           // Ajaxで更新
           $.ajax({
               url: ajaxurl,
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
                   $section.css('opacity', 0.7);
               },
               success: function(response) {
                   $section.css('opacity', 1);
                   
                   if (response.success) {
                       var $subject = $('.progress-subject[data-subject="' + subject + '"]');
                       var $chapter = $('.chapter-item[data-subject="' + subject + '"][data-chapter="' + chapter + '"]');
                       
                       // 進捗バーと割合を更新
                       $subject.find('.percent').text('(' + response.data.percent + '%)');
                       $subject.find('.progress-bar-fill').css('width', response.data.percent + '%');
                       
                       // 章のスタイルを更新
                       $chapter.removeClass('completed mastered');
                       
                       if (response.data.chapter_mastered) {
                           $chapter.addClass('completed mastered');
                           $chapter.css('background-color', '<?php echo esc_js($progress_settings['second_check_color']); ?>');
                       } else if (response.data.chapter_completed) {
                           $chapter.addClass('completed');
                           $chapter.css('background-color', '<?php echo esc_js($progress_settings['first_check_color']); ?>');
                       } else {
                           $chapter.css('background-color', '');
                       }
                       
                       // セクションのスタイルを更新
                       if (level >= 2) {
                           $section.css('background-color', '<?php echo esc_js($progress_settings['second_check_color']); ?>');
                       } else if (level >= 1) {
                           $section.css('background-color', '<?php echo esc_js($progress_settings['first_check_color']); ?>');
                       } else {
                           $section.css('background-color', '');
                       }
                   } else {
                       alert('更新に失敗しました。');
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
   <?php endif; ?>
   
   <style>
   /* 基本スタイル */
   .progress-tracker-shortcode {
       font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
       margin-bottom: 30px;
   }
   
   /* タブスタイル */
   .progress-tabs {
       display: flex;
       flex-wrap: wrap;
       margin-bottom: 15px;
       border-bottom: 1px solid #ddd;
   }
   
   .progress-tab {
       padding: 8px 16px;
       cursor: pointer;
       margin-right: 5px;
       border: 1px solid #ddd;
       border-bottom: none;
       border-radius: 5px 5px 0 0;
       background-color: #f8f8f8;
   }
   
   .progress-tab.active {
       background-color: #fff;
       border-bottom-color: #fff;
       margin-bottom: -1px;
       font-weight: bold;
   }
   
   /* 進捗バー */
   .progress-bar-container {
       height: 16px;
       background-color: #f1f1f1;
       border-radius: 8px;
       margin-bottom: 20px;
       overflow: hidden;
   }
   
   .progress-bar-fill {
       height: 100%;
       transition: width 0.3s ease;
   }
   
   /* 章スタイル */
   .progress-chapters {
       display: grid;
       grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
       gap: 15px;
   }
   
   .chapter-item {
       border: 1px solid #ddd;
       border-radius: 5px;
       overflow: hidden;
       margin-bottom: 5px;
   }
   
   .chapter-header {
       padding: 10px;
       background-color: #f9f9f9;
       display: flex;
       justify-content: space-between;
       align-items: center;
   }
   
   .chapter-item.expandable .chapter-header {
       cursor: pointer;
   }
   
   .chapter-title {
       font-weight: bold;
   }
   
   .chapter-toggle {
       width: 24px;
       height: 24px;
       line-height: 24px;
       text-align: center;
       background-color: #eee;
       border-radius: 50%;
       cursor: pointer;
   }
   
   /* 節スタイル */
   .chapter-sections {
       padding: 10px;
   }
   
   .section-item {
       display: flex;
       justify-content: space-between;
       align-items: center;
       padding: 8px;
       border: 1px solid #eee;
       border-radius: 3px;
       margin-bottom: 5px;
   }
   
   .section-title {
       font-weight: 500;
   }
   
   .section-checkboxes {
       display: flex;
       gap: 10px;
   }
   
   .checkbox-label {
       display: flex;
       align-items: center;
       gap: 3px;
       cursor: pointer;
   }
   
   .status-checked {
       color: #4CAF50;
   }
   
   .status-mastered {
       color: #ff9800;
       font-weight: bold;
   }
   
   /* シンプルスタイル */
   .progress-tracker-shortcode.style-simple .progress-chapters {
       display: block;
   }
   
   .progress-tracker-shortcode.style-simple .chapter-item {
       margin-bottom: 5px;
       box-shadow: none;
       border-left-width: 3px;
   }
   
   /* コンパクトスタイル */
   .progress-tracker-shortcode.style-compact .chapter-header {
       padding: 5px 10px;
       min-height: 40px;
   }
   
   .progress-tracker-shortcode.style-compact .section-item {
       padding: 5px 8px;
   }
   
   .progress-tracker-shortcode.style-compact .progress-chapters {
       gap: 5px;
   }
   </style>
   <?php
   
   return ob_get_clean();
}
add_shortcode('progress_tracker', 'progress_tracker_shortcode');

/**
* ショートコード：試験カウントダウン
*/
function progress_tracker_countdown_shortcode($atts) {
   // 属性の初期化
   $atts = shortcode_atts(array(
       'title' => '',    // カスタム試験名
   ), $atts, 'exam_countdown');
   
   // 設定を取得
   $settings = get_option('progress_tracker_settings', array(
       'exam_date' => '2025-11-09',
       'exam_title' => '試験'
   ));
   
   if (empty($settings['exam_date'])) {
       return '<p>試験日が設定されていません。</p>';
   }
   
   $exam_date = strtotime($settings['exam_date']);
   $today = current_time('timestamp');
   $days_left = floor(($exam_date - $today) / (60 * 60 * 24));
   
   if ($days_left < 0) {
       return '<p>試験日は過ぎました。</p>';
   }
   
   // 試験名はショートコードの属性か、設定の値を使用
   $exam_title = !empty($atts['title']) ? $atts['title'] : $settings['exam_title'];
   
   $output = '<div class="exam-countdown">';
   $output .= esc_html($exam_title) . 'まであと <span class="countdown-number">' . esc_html($days_left) . '</span> 日';
   $output .= '</div>';
   
   // スタイルを追加
   $output .= '<style>
   .exam-countdown {
       background-color: #334e68;
       color: white;
       padding: 10px 15px;
       text-align: center;
       font-weight: bold;
       border-radius: 5px;
       margin: 15px 0;
   }
   .countdown-number {
       font-size: 1.4em;
       color: #f9ca24;
   }
   </style>';
   
   return $output;
}
add_shortcode('exam_countdown', 'progress_tracker_countdown_shortcode');


/**
 * 管理画面ロード時に初期設定を実行
 */
function progress_tracker_admin_init() {
    // 初回実行時のみ、デフォルト科目をセットアップ
    if (get_option('progress_tracker_initialized') !== 'yes') {
        $default_subjects = array(
            'constitutional' => '憲法',
            'administrative' => '行政法',
            'civil' => '民法',
            'commercial' => '商法・会社法'
        );
        
        update_option('progress_tracker_subjects', $default_subjects);
        update_option('progress_tracker_initialized', 'yes');
    }
}
add_action('admin_init', 'progress_tracker_admin_init');
