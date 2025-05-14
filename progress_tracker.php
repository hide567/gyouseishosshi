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
        $progress_color = isset($_POST['progress_color']) ? sanitize_hex_color($_POST['progress_color']) : '#4CAF50';
        
        if (!empty($subject_key) && !empty($subject_name) && $total_chapters > 0) {
            // 既存の科目キーと重複していないか確認
            if (!isset($subjects[$subject_key])) {
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
    
    // 科目の削除
    if (isset($_POST['delete_subject']) && isset($_POST['study_track_subject_delete_nonce'])) {
        if (wp_verify_nonce($_POST['study_track_subject_delete_nonce'], 'study_track_subject_delete')) {
            $subject_key = sanitize_key($_POST['delete_subject']);
            
            // カスタム科目から削除
            if (isset($custom_subjects[$subject_key])) {
                unset($custom_subjects[$subject_key]);
                update_option('progress_tracker_custom_subjects', $custom_subjects);
                
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
                
                // 科目リストを更新
                unset($subjects[$subject_key]);
                
                echo '<div class="notice notice-success is-dismissible"><p>科目を削除しました。</p></div>';
            }
        }
    }
    
    // 暗記カードと科目の関連付け
    if (isset($_POST['save_flashcard_relation'])) {
        $flashcard_id = intval($_POST['flashcard_id']);
        $subject_key = sanitize_key($_POST['subject_key']);
        $chapter_id = intval($_POST['chapter_id']);
        
        // 関連データを保存
        $flashcard_relations = get_option('progress_tracker_flashcards', array());
        $flashcard_relations[$flashcard_id] = array(
            'subject' => $subject_key,
            'chapter' => $chapter_id
        );
        update_option('progress_tracker_flashcards', $flashcard_relations);
        
        echo '<div class="notice notice-success is-dismissible"><p>暗記カードと科目の関連付けを保存しました。</p></div>';
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
            <a href="?page=progress-tracker&tab=flashcards" class="nav-tab <?php echo $active_tab == 'flashcards' ? 'nav-tab-active' : ''; ?>">暗記カード</a>
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
        
        <?php elseif ($active_tab == 'flashcards'): ?>
        <!-- 暗記カード管理タブ -->
        <h3>暗記カード管理</h3>
        <p>H5Pプラグインを使って科目ごとの暗記カードを管理できます。</p>

        <?php
        // H5Pプラグインの確認
        // 修正後のコード
// H5Pプラグインの確認（複数の可能性をチェック）
global $wpdb;
$h5p_contents_table = $wpdb->prefix . 'h5p_contents';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$h5p_contents_table'") == $h5p_contents_table;

if (!function_exists('H5P_Plugin') && !class_exists('H5P_Plugin') && !function_exists('h5p_get_instance') && !class_exists('H5PPlugin') && !$table_exists) {
    echo '<div class="notice notice-warning"><p>H5Pプラグインがインストールされていないか、有効化されていません。<a href="' . admin_url('plugin-install.php?s=H5P&tab=search&type=term') . '">こちら</a>からインストールしてください。</p></div>';
}else {
            // H5Pコンテンツの取得
            global $wpdb;
            $h5p_contents_table = $wpdb->prefix . 'h5p_contents';
            
            // テーブルが存在するか確認
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$h5p_contents_table'") == $h5p_contents_table;
            
            if ($table_exists) {
                $h5p_contents = $wpdb->get_results("SELECT id, title, created_at FROM $h5p_contents_table ORDER BY id DESC");
            }
            
            // 関連データを取得
            $flashcard_relations = get_option('progress_tracker_flashcards', array());
            
            // 新しい暗記カードを作成するボタン
            echo '<p><a href="' . admin_url('admin.php?page=h5p&task=new') . '" class="button button-primary">新しい暗記カードを作成</a></p>';
            
            // 既存の暗記カード一覧
            if (isset($h5p_contents) && !empty($h5p_contents)) {
                echo '<h4>暗記カード一覧</h4>';
                echo '<table class="wp-list-table widefat fixed striped">';
                echo '<thead><tr><th>ID</th><th>タイトル</th><th>作成日</th><th>関連科目</th><th>操作</th></tr></thead>';
                echo '<tbody>';
                
                foreach ($h5p_contents as $content) {
                    echo '<tr>';
                    echo '<td>' . esc_html($content->id) . '</td>';
                    echo '<td>' . esc_html($content->title) . '</td>';
                    echo '<td>' . esc_html(date('Y-m-d', strtotime($content->created_at))) . '</td>';
                    
                    // 関連科目の表示
                    echo '<td>';
                    if (isset($flashcard_relations[$content->id])) {
                        $subject_key = $flashcard_relations[$content->id]['subject'];
                        $chapter_id = $flashcard_relations[$content->id]['chapter'];
                        
                        $subject_name = isset($subjects[$subject_key]) ? $subjects[$subject_key] : '不明';
                        $chapter_title = isset($chapter_structure[$subject_key]['chapters'][$chapter_id]['title']) ? 
                                        $chapter_structure[$subject_key]['chapters'][$chapter_id]['title'] : '第' . $chapter_id . '章';
                        
                        echo esc_html($subject_name) . ' - ' . esc_html($chapter_title);
                    } else {
                        echo '未設定';
                    }
                    echo '</td>';
                    
                    // 操作列
                    echo '<td>';
                    echo '<a href="' . admin_url('admin.php?page=h5p&task=show&id=' . $content->id) . '" class="button button-small">表示</a> ';
                    echo '<a href="' . admin_url('admin.php?page=h5p&task=edit&id=' . $content->id) . '" class="button button-small">編集</a> ';
                    echo '<button type="button" class="button button-small set-relation" data-id="' . esc_attr($content->id) . '">関連付け</button>';
                    echo '</td>';
                    
                    echo '</tr>';
                }
                
                echo '</tbody></table>';
                
                // 関連付けモーダル
                ?>
                <div id="relation-modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.4);">
                    <div style="background-color:#fff; margin:10% auto; padding:20px; border:1px solid #888; width:50%; border-radius:5px;">
                        <h3>暗記カードと科目の関連付け</h3>
                        <form method="post" action="">
                            <input type="hidden" id="flashcard_id" name="flashcard_id" value="">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">科目</th>
                                    <td>
                                        <select id="subject_key" name="subject_key" class="regular-text">
                                            <?php foreach ($subjects as $key => $name): ?>
                                                <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($name); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">章</th>
                                    <td>
                                        <select id="chapter_id" name="chapter_id" class="regular-text">
                                            <option value="0">選択してください</option>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                            <p class="submit">
                                <input type="submit" name="save_flashcard_relation" class="button button-primary" value="保存">
                                <button type="button" class="button close-modal">キャンセル</button>
                            </p>
                        </form>
                    </div>
                </div>
                
                <script>
                jQuery(document).ready(function($) {
                    // 章の選択肢を更新する関数
                    function updateChapters() {
                        var subject = $('#subject_key').val();
                        var $chapterSelect = $('#chapter_id');
                        
                        // 章の選択肢をクリア
                        $chapterSelect.empty().append('<option value="0">選択してください</option>');
                        
                        // 科目の章データを取得
                        var chapters = <?php echo json_encode($chapter_structure); ?>;
                        
                        if (chapters[subject] && chapters[subject]['chapters']) {
                            $.each(chapters[subject]['chapters'], function(id, chapter) {
                                $chapterSelect.append(
                                    $('<option>', {
                                        value: id,
                                        text: chapter.title
                                    })
                                );
                            });
                        }
                    }
                    
                    // 科目が変更されたら章の選択肢を更新
                    $('#subject_key').on('change', updateChapters);
                    
                    // 関連付けボタンがクリックされたとき
                    $('.set-relation').on('click', function() {
                        var id = $(this).data('id');
                        $('#flashcard_id').val(id);
                        
                        // 既存の関連付けがあれば選択
                        var relations = <?php echo json_encode($flashcard_relations); ?>;
                        if (relations[id]) {
                            $('#subject_key').val(relations[id].subject);
                            updateChapters();
                            setTimeout(function() {
                                $('#chapter_id').val(relations[id].chapter);
                            }, 100);
                        } else {
                            updateChapters();
                        }
                        
                        // モーダルを表示
                        $('#relation-modal').show();
                    });
                    
                    // モーダルを閉じる
                    $('.close-modal').on('click', function(e) {
                        e.preventDefault();
                        $('#relation-modal').hide();
                    });
                    
                    // モーダル外をクリックしたら閉じる
                    $('#relation-modal').on('click', function(e) {
                        if (e.target.id === 'relation-modal') {
                            $('#relation-modal').hide();
                        }
                    });
                });
                </script>
                <?php
            } else {
                echo '<p>暗記カードがまだ作成されていません。「新しい暗記カードを作成」ボタンから作成してください。</p>';
            }
            
            // ショートコードの使用方法
            echo '<h4>ショートコードの使用方法</h4>';
            echo '<p>以下のショートコードを使って、暗記カードを表示できます：</p>';
            echo '<code>[study_flashcards id="カードID"]</code> - 特定の暗記カードを表示<br>';
            echo '<code>[study_flashcards subject="科目キー"]</code> - 科目に関連付けられたすべての暗記カードを表示<br>';
            echo '<code>[study_flashcards chapter="章ID" subject="科目キー"]</code> - 特定の章に関連付けられた暗記カードを表示';
        }
        ?>
        
        <?php else: ?>
        <!-- カスタム科目タブ 拡張版 -->
        <h3>カスタム科目の追加</h3>
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

        <!-- 科目の削除と編集機能を追加 -->
        <h3>科目の管理</h3>
        <?php if (!empty($custom_subjects)): ?>
            <form method="post" action="">
                <?php wp_nonce_field('study_track_subject_delete', 'study_track_subject_delete_nonce'); ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>科目キー</th>
                            <th>科目名</th>
                            <th>章数</th>
                            <th>進捗</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($custom_subjects as $key => $name): 
                            $total_chapters = isset($chapter_structure[$key]['total']) ? $chapter_structure[$key]['total'] : 0;
                            $percent = isset($progress_data[$key]['percent']) ? $progress_data[$key]['percent'] : 0;
                        ?>
                            <tr>
                                <td><?php echo esc_html($key); ?></td>
                                <td><?php echo esc_html($name); ?></td>
                                <td><?php echo esc_html($total_chapters); ?></td>
								<td>
                                   <div class="progress-mini-bar">
                                       <div class="progress-mini-fill" style="width:<?php echo esc_attr($percent); ?>%;"></div>
                                   </div>
                                   <?php echo esc_html($percent); ?>%
                               </td>
                               <td>
                                   <button type="submit" name="delete_subject" value="<?php echo esc_attr($key); ?>" class="button button-small button-link-delete" onclick="return confirm('この科目を削除してもよろしいですか？関連するすべての進捗データも削除されます。');">削除</button>
                               </td>
                           </tr>
                       <?php endforeach; ?>
                   </tbody>
               </table>
           </form>
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

/**
* カスタマイズ設定ページを追加
*/
function progress_tracker_customize_menu() {
   add_submenu_page(
       'progress-tracker',
       '設定とカスタマイズ',
       '設定とカスタマイズ',
       'manage_options',
       'progress-tracker-settings',
       'progress_tracker_settings_page'
   );
}
add_action('admin_menu', 'progress_tracker_customize_menu');

/**
* 設定ページの内容
*/
function progress_tracker_settings_page() {
   // 権限チェック
   if (!current_user_can('manage_options')) {
       return;
   }
   
   // 保存処理
   if (isset($_POST['save_settings'])) {
       $settings = array(
           'exam_date' => sanitize_text_field($_POST['exam_date']),
           'exam_title' => sanitize_text_field($_POST['exam_title']),
           'progress_bar_color' => sanitize_hex_color($_POST['progress_bar_color']),
           'completed_item_color' => sanitize_hex_color($_POST['completed_item_color'])
       );
       
       update_option('progress_tracker_settings', $settings);
       echo '<div class="notice notice-success is-dismissible"><p>設定を保存しました。</p></div>';
   }
   
   // 現在の設定を取得
   $settings = get_option('progress_tracker_settings', array(
       'exam_date' => '2025-11-09',
       'exam_title' => '試験',
       'progress_bar_color' => '#4CAF50',
       'completed_item_color' => '#e6f7e6'
   ));
   
   ?>
   <div class="wrap">
       <h1>学習進捗管理 - 設定とカスタマイズ</h1>
       
       <form method="post" action="">
           <h3>全般設定</h3>
           <table class="form-table">
               <tr>
                   <th scope="row">試験日</th>
                   <td>
                       <input type="date" name="exam_date" value="<?php echo esc_attr($settings['exam_date']); ?>" class="regular-text">
                       <p class="description">カウントダウンに使用される試験日を設定します。</p>
                   </td>
               </tr>
               <tr>
                   <th scope="row">試験名/資格名</th>
                   <td>
                       <input type="text" name="exam_title" value="<?php echo esc_attr($settings['exam_title']); ?>" class="regular-text">
                       <p class="description">カウントダウンに表示される試験や資格の名称</p>
                   </td>
               </tr>
               <tr>
                   <th scope="row">プログレスバーの色</th>
                   <td>
                       <input type="color" name="progress_bar_color" value="<?php echo esc_attr($settings['progress_bar_color']); ?>">
                   </td>
               </tr>
               <tr>
                   <th scope="row">完了項目の色</th>
                   <td>
                       <input type="color" name="completed_item_color" value="<?php echo esc_attr($settings['completed_item_color']); ?>">
                   </td>
               </tr>
           </table>
           
           <h3>ショートコードの使用方法</h3>
           <div class="shortcode-usage">
               <p>進捗表示ショートコード: <code>[progress_tracker]</code></p>
               <p>特定の科目のみ表示: <code>[progress_tracker subject="constitutional,civil"]</code></p>
               <p>スタイル指定: <code>[progress_tracker style="simple"]</code> (スタイル: default, simple, compact)</p>
               <p>試験カウントダウン: <code>[exam_countdown]</code></p>
               <p>カスタム試験名: <code>[exam_countdown title="司法試験"]</code></p>
               <p>暗記カード表示: <code>[study_flashcards id="1"]</code>または<code>[study_flashcards subject="constitutional"]</code></p>
           </div>
           
           <p class="submit">
               <input type="submit" name="save_settings" class="button button-primary" value="設定を保存">
           </p>
       </form>
   </div>
   
   <style>
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
   </style>
   <?php
}

/**
* 試験日カウントダウンショートコード
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
* 暗記カード表示用ショートコード
*/
function study_flashcards_shortcode($atts) {
   // 属性の初期化
   $atts = shortcode_atts(array(
       'id' => 0,           // 特定のH5Pコンテンツを表示
       'subject' => '',     // 科目に関連付けられたカードを表示
       'chapter' => 0,      // 章に関連付けられたカードを表示
   ), $atts, 'study_flashcards');
   
   // H5Pプラグインが有効か確認
   // 修正後のコード
// H5Pプラグインが有効か確認（複数の可能性をチェック）
global $wpdb;
$h5p_contents_table = $wpdb->prefix . 'h5p_contents';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$h5p_contents_table'") == $h5p_contents_table;

if (!function_exists('H5P_Plugin') && !class_exists('H5P_Plugin') && !function_exists('h5p_get_instance') && !class_exists('H5PPlugin') && !$table_exists) {
    return '<p>H5Pプラグインが有効化されていません。</p>';
}
   
   // 出力開始
   ob_start();
   
   echo '<div class="study-track-flashcards">';
   
   // 特定のIDが指定されている場合
   if (!empty($atts['id'])) {
       $flashcard_id = intval($atts['id']);
       echo do_shortcode('[h5p id="' . $flashcard_id . '"]');
   }
   // 科目が指定されている場合
   elseif (!empty($atts['subject'])) {
       $subject_key = sanitize_key($atts['subject']);
       $chapter_id = !empty($atts['chapter']) ? intval($atts['chapter']) : 0;
       
       // 関連付けられたカードを検索
       $flashcard_relations = get_option('progress_tracker_flashcards', array());
       $filtered_cards = array();
       
       foreach ($flashcard_relations as $card_id => $relation) {
           if ($relation['subject'] == $subject_key) {
               if ($chapter_id == 0 || $relation['chapter'] == $chapter_id) {
                   $filtered_cards[] = $card_id;
               }
           }
       }
       
       if (!empty($filtered_cards)) {
           foreach ($filtered_cards as $card_id) {
               echo do_shortcode('[h5p id="' . $card_id . '"]');
               echo '<div class="flashcard-separator"></div>';
           }
       } else {
           echo '<p>この条件に一致する暗記カードはありません。</p>';
       }
   } else {
       echo '<p>暗記カードIDまたは科目を指定してください。</p>';
   }
   
   echo '</div>';
   
   return ob_get_clean();
}
add_shortcode('study_flashcards', 'study_flashcards_shortcode');
