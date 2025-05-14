<?php
/**
 * Dialog Cards 管理モジュール
 *
 * @package 行政書士試験ブログ
 */

if (!defined('ABSPATH')) {
    exit; // 直接アクセスを禁止
}

/**
 * Dialog Cards 管理メニューを追加
 */
function dialog_cards_admin_menu() {
    add_menu_page(
        'Dialog Cards 管理',
        'Dialog Cards',
        'manage_options',
        'dialog-cards-manager',
        'dialog_cards_admin_page',
        'dashicons-index-card',
        31
    );
}
add_action('admin_menu', 'dialog_cards_admin_menu');

/**
 * Dialog Cards 管理画面を表示
 */
function dialog_cards_admin_page() {
    // 権限チェック
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // H5Pプラグインの確認
    global $wpdb;
    $h5p_contents_table = $wpdb->prefix . 'h5p_contents';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$h5p_contents_table'") == $h5p_contents_table;

    if (!$table_exists) {
        echo '<div class="notice notice-error"><p>H5Pプラグインがインストールされていないか有効化されていません。Dialog Cardsを使用するには、H5Pプラグインが必要です。</p></div>';
        return;
    }
    
    // 科目データ
    $subjects = get_option('progress_tracker_subjects', array(
        'constitutional' => '憲法',
        'administrative' => '行政法',
        'civil' => '民法',
        'commercial' => '商法・会社法'
    ));
    
    // 章構造データを取得
    $chapter_structure = get_option('progress_tracker_chapters', array());
    
    // Dialog Cards のリレーションデータを取得
    $dialog_cards_relations = get_option('dialog_cards_relations', array());
    
    // 関連付けを保存
    if (isset($_POST['save_dialogcard_relation'])) {
        check_admin_referer('save_dialogcard_relation');
        
        $dialog_card_id = intval($_POST['dialog_card_id']);
        $subject_key = sanitize_key($_POST['subject_key']);
        $chapter_id = intval($_POST['chapter_id']);
        
        // 関連データを保存
        $dialog_cards_relations[$dialog_card_id] = array(
            'subject' => $subject_key,
            'chapter' => $chapter_id
        );
        
        update_option('dialog_cards_relations', $dialog_cards_relations);
        
        echo '<div class="notice notice-success is-dismissible"><p>Dialog Cardと科目の関連付けを保存しました。</p></div>';
    }
    
    // Dialog Cards の設定を保存
    if (isset($_POST['save_dialog_cards_settings'])) {
        check_admin_referer('save_dialog_cards_settings');
        
        $dialog_cards_settings = array(
            'default_front_text' => sanitize_text_field($_POST['default_front_text']),
            'default_back_text' => sanitize_text_field($_POST['default_back_text']),
            'card_size' => sanitize_text_field($_POST['card_size']),
            'auto_play' => isset($_POST['auto_play']) ? 'yes' : 'no'
        );
        
        update_option('dialog_cards_settings', $dialog_cards_settings);
        
        echo '<div class="notice notice-success is-dismissible"><p>Dialog Cards の設定を保存しました。</p></div>';
    }
    
    // Dialog Cards のバッチ作成
    if (isset($_POST['create_dialog_cards_batch'])) {
        check_admin_referer('create_dialog_cards_batch');
        
        $subject_key = sanitize_key($_POST['batch_subject']);
        $chapter_id = intval($_POST['batch_chapter']);
        $card_count = intval($_POST['card_count']);
        
        if ($subject_key && $chapter_id && $card_count > 0) {
            // H5P APIを使用して新しいDialog Cardsを作成
            if (function_exists('h5p_get_instance') && class_exists('H5PCore')) {
                $h5p = h5p_get_instance();
                $subject_name = isset($subjects[$subject_key]) ? $subjects[$subject_key] : '';
                $chapter_title = '';
                
                if (isset($chapter_structure[$subject_key]['chapters'][$chapter_id]['title'])) {
                    $chapter_title = $chapter_structure[$subject_key]['chapters'][$chapter_id]['title'];
                }
                
                $title = $subject_name . ' - ' . $chapter_title . ' Dialog Cards';
                
                // Dialog Cards のデフォルト設定を取得
                $dialog_cards_settings = get_option('dialog_cards_settings', array(
                    'default_front_text' => '質問',
                    'default_back_text' => '回答',
                    'card_size' => 'medium',
                    'auto_play' => 'no'
                ));
                
                // Dialog Cards のパラメータを設定
                $params = array(
                    'title' => $title,
                    'dialogCards' => array(
                        'mode' => 'normal',
                        'description' => $title . 'の説明',
                        'cardSize' => $dialog_cards_settings['card_size'],
                        'cards' => array()
                    )
                );
                
                // カードを追加
                for ($i = 1; $i <= $card_count; $i++) {
                    $params['dialogCards']['cards'][] = array(
                        'text' => $dialog_cards_settings['default_front_text'] . ' ' . $i,
                        'answer' => $dialog_cards_settings['default_back_text'] . ' ' . $i
                    );
                }
                
                // H5P コンテンツを作成
                $content = array(
                    'id' => NULL,
                    'library' => 'H5P.DialogCards 1.8',
                    'title' => $title,
                    'params' => json_encode($params),
                    'slug' => 'dialog-cards-' . $subject_key . '-' . $chapter_id
                );
                
                // H5P コンテンツを保存
                $h5p_content_id = $h5p->core->saveContent($content);
                
                if ($h5p_content_id) {
                    // 関連付けを保存
                    $dialog_cards_relations[$h5p_content_id] = array(
                        'subject' => $subject_key,
                        'chapter' => $chapter_id
                    );
                    
                    update_option('dialog_cards_relations', $dialog_cards_relations);
                    
                    echo '<div class="notice notice-success is-dismissible"><p>' . $card_count . '枚のDialog Cardsを作成しました。IDは: ' . $h5p_content_id . ' です。</p></div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible"><p>Dialog Cardsの作成に失敗しました。</p></div>';
                }
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>H5P APIが利用できません。プラグインが正しくインストールされているか確認してください。</p></div>';
            }
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>すべての項目を入力してください。</p></div>';
        }
    }
    
    // タブの処理
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'list';
    ?>
    <div class="wrap">
        <h1>Dialog Cards 管理</h1>
        
        <h2 class="nav-tab-wrapper">
            <a href="?page=dialog-cards-manager&tab=list" class="nav-tab <?php echo $active_tab == 'list' ? 'nav-tab-active' : ''; ?>">一覧と関連付け</a>
            <a href="?page=dialog-cards-manager&tab=create" class="nav-tab <?php echo $active_tab == 'create' ? 'nav-tab-active' : ''; ?>">新規作成</a>
            <a href="?page=dialog-cards-manager&tab=batch" class="nav-tab <?php echo $active_tab == 'batch' ? 'nav-tab-active' : ''; ?>">バッチ作成</a>
            <a href="?page=dialog-cards-manager&tab=settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">設定</a>
        </h2>
        
        <?php if ($active_tab == 'list'): ?>
        <!-- Dialog Cards 一覧と関連付け -->
        <div class="admin-section">
            <h3>Dialog Cards 一覧</h3>
            <?php
            // H5P コンテンツの取得
            $dialog_cards = array();
            
            if ($table_exists) {
                $dialog_cards = $wpdb->get_results("
                    SELECT c.id, c.title, c.created_at, l.name AS library_name 
                    FROM {$wpdb->prefix}h5p_contents c
                    JOIN {$wpdb->prefix}h5p_libraries l ON c.library_id = l.id
                    WHERE l.name = 'H5P.DialogCards'
                    OR l.name LIKE '%DialogCards%'
                    ORDER BY c.id DESC
                ");
            }
            
            if (!empty($dialog_cards)):
            ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>タイトル</th>
                            <th>作成日</th>
                            <th>関連科目</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dialog_cards as $card): ?>
                            <tr>
                                <td><?php echo esc_html($card->id); ?></td>
                                <td><?php echo esc_html($card->title); ?></td>
                                <td><?php echo esc_html(date('Y-m-d', strtotime($card->created_at))); ?></td>
                                <td>
                                    <?php
                                    if (isset($dialog_cards_relations[$card->id])) {
                                        $rel = $dialog_cards_relations[$card->id];
                                        $subject_name = isset($subjects[$rel['subject']]) ? $subjects[$rel['subject']] : '不明';
                                        $chapter_title = '';
                                        
                                        if (isset($chapter_structure[$rel['subject']]['chapters'][$rel['chapter']]['title'])) {
                                            $chapter_title = $chapter_structure[$rel['subject']]['chapters'][$rel['chapter']]['title'];
                                        } else {
                                            $chapter_title = '第' . $rel['chapter'] . '章';
                                        }
                                        
                                        echo esc_html($subject_name) . ' - ' . esc_html($chapter_title);
                                    } else {
                                        echo '未設定';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=h5p&id=' . $card->id . '&task=show'); ?>" class="button button-small">表示</a>
                                    <a href="<?php echo admin_url('admin.php?page=h5p-content&id=' . $card->id); ?>" class="button button-small">編集</a>
                                    <button type="button" class="button button-small set-relation" data-id="<?php echo esc_attr($card->id); ?>">関連付け</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- 関連付けモーダル -->
                <div id="relation-modal" class="modal-overlay" style="display:none;">
                    <div class="modal-content">
                        <h3>Dialog Cardと科目の関連付け</h3>
                        <form method="post" action="">
                            <?php wp_nonce_field('save_dialogcard_relation'); ?>
                            <input type="hidden" id="dialog_card_id" name="dialog_card_id" value="">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">科目</th>
                                    <td>
                                        <select id="subject_key" name="subject_key" class="regular-text">
                                            <option value="">選択してください</option>
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
                                <input type="submit" name="save_dialogcard_relation" class="button button-primary" value="保存">
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
                        
                        if (subject && chapters[subject] && chapters[subject]['chapters']) {
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
                        $('#dialog_card_id').val(id);
                        
                        // 既存の関連付けがあれば選択
                        var relations = <?php echo json_encode($dialog_cards_relations); ?>;
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
                        $(this).closest('.modal-overlay').hide();
                    });
                    
                    // モーダル外をクリックしたら閉じる
                    $('.modal-overlay').on('click', function(e) {
                        if (e.target === this) {
                            $(this).hide();
                        }
                    });
                });
                </script>
            <?php else: ?>
                <p>Dialog Cardsがまだ作成されていません。「新規作成」タブから作成するか、「バッチ作成」タブで複数のカードをまとめて作成してください。</p>
            <?php endif; ?>
            
            <h4>ショートコードの使用方法</h4>
            <div class="shortcode-usage">
                <p>以下のショートコードを使って、Dialog Cardsを表示できます：</p>
                <p><code>[dialog_cards id="カードID"]</code> - 特定のDialog Cardsを表示</p>
                <p><code>[dialog_cards subject="科目キー"]</code> - 科目に関連付けられたすべてのDialog Cardsを表示</p>
                <p><code>[dialog_cards chapter="章ID" subject="科目キー"]</code> - 特定の章に関連付けられたDialog Cardsを表示</p>
            </div>
        </div>
        
        <?php elseif ($active_tab == 'create'): ?>
        <!-- 新規作成タブ -->
        <div class="admin-section">
            <h3>新しいDialog Cardsを作成</h3>
            <p>H5Pエディタで新しいDialog Cardsを作成します。作成後、「一覧と関連付け」タブで科目と関連付けることができます。</p>
            <p><a href="<?php echo admin_url('admin.php?page=h5p-new'); ?>" class="button button-primary">H5Pコンテンツ作成ページへ</a></p>
            <p class="description">※H5P編集画面で「Dialog Cards」を選択してください。</p>
        </div>
        
        <?php elseif ($active_tab == 'batch'): ?>
        <!-- バッチ作成タブ -->
        <div class="admin-section">
            <h3>Dialog Cardsのバッチ作成</h3>
            <p>複数のDialog Cardsをまとめて作成できます。作成後に個別に編集することもできます。</p>
            
            <form method="post" action="">
                <?php wp_nonce_field('create_dialog_cards_batch'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">科目</th>
                        <td>
                            <select id="batch_subject" name="batch_subject" class="regular-text" required>
                                <option value="">選択してください</option>
                                <?php foreach ($subjects as $key => $name): ?>
                                    <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">章</th>
                        <td>
                            <select id="batch_chapter" name="batch_chapter" class="regular-text" required>
                                <option value="0">選択してください</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">カード枚数</th>
                        <td>
                            <input type="number" name="card_count" value="10" min="1" max="100" class="small-text" required>
                            <p class="description">作成するカードの枚数（後で編集可能）</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="create_dialog_cards_batch" class="button button-primary" value="カードをバッチ作成">
                </p>
            </form>
            
            <script>
            jQuery(document).ready(function($) {
                // 章の選択肢を更新する関数
                function updateBatchChapters() {
                    var subject = $('#batch_subject').val();
                    var $chapterSelect = $('#batch_chapter');
                    
                    // 章の選択肢をクリア
                    $chapterSelect.empty().append('<option value="0">選択してください</option>');
                    
                    // 科目の章データを取得
                    var chapters = <?php echo json_encode($chapter_structure); ?>;
                    
                    if (subject && chapters[subject] && chapters[subject]['chapters']) {
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
                $('#batch_subject').on('change', updateBatchChapters);
            });
            </script>
        </div>
        
        <?php elseif ($active_tab == 'settings'): ?>
        <!-- 設定タブ -->
        <div class="admin-section">
            <h3>Dialog Cards 設定</h3>
            <p>Dialog Cardsのデフォルト設定を行います。</p>
            
            <?php
            // Dialog Cards の設定を取得
            $dialog_cards_settings = get_option('dialog_cards_settings', array(
                'default_front_text' => '質問',
                'default_back_text' => '回答',
                'card_size' => 'medium',
                'auto_play' => 'no'
            ));
            ?>
            
            <form method="post" action="">
                <?php wp_nonce_field('save_dialog_cards_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">デフォルト表面テキスト</th>
                        <td>
                            <input type="text" name="default_front_text" value="<?php echo esc_attr($dialog_cards_settings['default_front_text']); ?>" class="regular-text">
                            <p class="description">バッチ作成時のカード表面のデフォルトテキスト</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">デフォルト裏面テキスト</th>
                        <td>
                            <input type="text" name="default_back_text" value="<?php echo esc_attr($dialog_cards_settings['default_back_text']); ?>" class="regular-text">
                            <p class="description">バッチ作成時のカード裏面のデフォルトテキスト</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">カードサイズ</th>
                        <td>
                            <select name="card_size" class="regular-text">
                                <option value="small" <?php selected($dialog_cards_settings['card_size'], 'small'); ?>>小</option>
                                <option value="medium" <?php selected($dialog_cards_settings['card_size'], 'medium'); ?>>中</option>
                                <option value="large" <?php selected($dialog_cards_settings['card_size'], 'large'); ?>>大</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">自動再生</th>
                        <td>
                            <label>
                                <input type="checkbox" name="auto_play" <?php checked($dialog_cards_settings['auto_play'], 'yes'); ?>>
                                カードを自動的に次へ進める
                            </label>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="save_dialog_cards_settings" class="button button-primary" value="設定を保存">
                </p>
            </form>
        </div>
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
    
    /* ショートコード使用方法 */
    .shortcode-usage {
        background: #f9f9f9;
        padding: 15px;
        border-radius: 5px;
        border-left: 4px solid #0073aa;
        margin-top: 20px;
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
 * Dialog Cards 表示用ショートコード
 */
function dialog_cards_shortcode($atts) {
    // 属性の初期化
    $atts = shortcode_atts(array(
        'id' => 0,           // 特定のH5Pコンテンツを表示
        'subject' => '',     // 科目に関連付けられたカードを表示
        'chapter' => 0,      // 章に関連付けられたカードを表示
    ), $atts, 'dialog_cards');
    
    // H5Pプラグインが有効か確認
    if (!function_exists('H5P_Plugin') && !class_exists('H5P_Plugin') && !function_exists('h5p_get_instance') && !class_exists('H5PPlugin')) {
        return '<p>H5Pプラグインが有効化されていません。</p>';
    }
    
    // 出力開始
    ob_start();
    
    echo '<div class="dialog-cards-container">';
    
    // 特定のIDが指定されている場合
    if (!empty($atts['id'])) {
        $card_id = intval($atts['id']);
        echo do_shortcode('[h5p id="' . $card_id . '"]');
    }
    // 科目が指定されている場合
    elseif (!empty($atts['subject'])) {
        $subject_key = sanitize_key($atts['subject']);
        $chapter_id = !empty($atts['chapter']) ? intval($atts['chapter']) : 0;
        
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
            foreach ($filtered_cards as $card_id) {
                echo do_shortcode('[h5p id="' . $card_id . '"]');
                echo '<div class="card-separator"></div>';
            }
        } else {
            echo '<p>この条件に一致するDialog Cardsはありません。</p>';
        }
    } else {
        echo '<p>Dialog Cards IDまたは科目を指定してください。</p>';
    }
    
    echo '</div>';
    
    // スタイルを追加
    echo '<style>
    .dialog-cards-container {
        margin-bottom: 30px;
    }
    
    .card-separator {
        height: 20px;
        border-top: 1px solid #eee;
        margin: 20px 0;
    }
    </style>';
    
    return ob_get_clean();
}
add_shortcode('dialog_cards', 'dialog_cards_shortcode');

/**
 * H5Pのリダイレクト修正機能
 * 
 * H5Pプラグインの管理画面リダイレクトの問題を修正
 * Dialog Cards 表示時のアクセス権限エラーに対応
 */
function fix_h5p_task_redirect() {
    global $pagenow;
    
    // 管理画面編集ページでパラメーターを確認
    if ($pagenow == 'admin.php' && isset($_GET['page']) && $_GET['page'] == 'h5p' && isset($_GET['task'])) {
        // タスクに基づいてリダイレクト
        $task = sanitize_text_field($_GET['task']);
        
        // 編集タスクが指定されている場合
        if ($task == 'edit' && isset($_GET['id'])) {
            $id = intval($_GET['id']);
            wp_redirect(admin_url("admin.php?page=h5p-content&id={$id}"));
            exit;
        }
        
        // 新規作成タスクの場合
        if ($task == 'new') {
            wp_redirect(admin_url('admin.php?page=h5p-new'));
            exit;
        }
        
        // 表示タスクの場合
        if ($task == 'show' && isset($_GET['id'])) {
            $id = intval($_GET['id']);
            
            // Dialog Cards表示時のアクセス権限エラーを修正
            // コンテンツタイプをDialog Cardsに変更
            global $wpdb;
            $content_table = $wpdb->prefix . 'h5p_contents';
            $library_table = $wpdb->prefix . 'h5p_libraries';
            
            // コンテンツのライブラリを確認
            $content_library = $wpdb->get_var($wpdb->prepare(
                "SELECT l.name FROM {$library_table} l
                JOIN {$content_table} c ON c.library_id = l.id
                WHERE c.id = %d",
                $id
            ));
            
            // フラッシュカードもしくはDialogCardsの場合
            if ($content_library == 'H5P.Flashcards' || $content_library == 'H5P.DialogCards' || strpos($content_library, 'DialogCards') !== false) {
                // 正しいURLへリダイレクト
                wp_redirect(admin_url("admin.php?page=h5p-content&id={$id}&view=true"));
                exit;
            } else {
                // 通常の表示URLへ
                wp_redirect(admin_url("admin.php?page=h5p-content&id={$id}&view=true"));
                exit;
            }
        }
    }
    
    // コンテンツの表示権限エラーを修正
    if ($pagenow == 'admin.php' && isset($_GET['page']) && $_GET['page'] == 'h5p-content' && isset($_GET['id']) && isset($_GET['view'])) {
        global $wpdb;
        $content_table = $wpdb->prefix . 'h5p_contents';
        $library_table = $wpdb->prefix . 'h5p_libraries';
        $id = intval($_GET['id']);
        
        // コンテンツのライブラリを確認
        $content_library = $wpdb->get_var($wpdb->prepare(
            "SELECT l.name FROM {$library_table} l
            JOIN {$content_table} c ON c.library_id = l.id
            WHERE c.id = %d",
            $id
        ));
        
        // Dialog Cardsのアクセス権限問題を修正
        if ($content_library == 'H5P.Flashcards' || $content_library == 'H5P.DialogCards' || strpos($content_library, 'DialogCards') !== false) {
            // Dialog Cardsのビュー権限を持っていない場合、適切な権限を付与
            add_filter('h5p_alter_user_result', function ($content_id, $user_id) use ($id) {
                if ($content_id == $id) {
                    return (object) array(
                        'user_id' => $user_id,
                        'content_id' => $content_id,
                        'view_access' => true
                    );
                }
                return null;
            }, 10, 2);
        }
    }
}
add_action('admin_init', 'fix_h5p_task_redirect');

/**
 * Dialog Cardsのためのスクリプトとスタイルを登録
 */
function dialog_cards_enqueue_scripts() {
    if (is_singular() && has_shortcode(get_post()->post_content, 'dialog_cards')) {
        wp_enqueue_style(
            'dialog-cards-style',
            get_stylesheet_directory_uri() . '/css/dialog-cards.css',
            array(),
            wp_get_theme()->get('Version')
        );
        
        wp_enqueue_script(
            'dialog-cards-script',
            get_stylesheet_directory_uri() . '/js/dialog-cards.js',
            array('jquery'),
            wp_get_theme()->get('Version'),
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'dialog_cards_enqueue_scripts');

/**
 * Dialog Cardsの設定を初期化
 */
function dialog_cards_init() {
    // Dialog Cardsの設定が存在しない場合は初期設定を作成
    if (get_option('dialog_cards_settings') === false) {
        $default_settings = array(
            'default_front_text' => '質問',
            'default_back_text' => '回答',
            'card_size' => 'medium',
            'auto_play' => 'no'
        );
        
        update_option('dialog_cards_settings', $default_settings);
    }
    
    // プログレストラッカーとDialog Cardsの互換性を確保
    // 既存のフラッシュカード関連付けデータをDialog Cardsに移行
    $flashcard_relations = get_option('progress_tracker_flashcards', array());
    $dialog_cards_relations = get_option('dialog_cards_relations', array());
    
    if (!empty($flashcard_relations) && empty($dialog_cards_relations)) {
        update_option('dialog_cards_relations', $flashcard_relations);
    }
}
add_action('admin_init', 'dialog_cards_init');

/**
 * Dialog Cards用のAjaxエンドポイントを追加
 */
function dialog_cards_ajax_get_chapters() {
    check_ajax_referer('dialog_cards_ajax_nonce');
    
    $subject_key = sanitize_key($_POST['subject_key']);
    $chapter_structure = get_option('progress_tracker_chapters', array());
    
    $chapters = array();
    if (isset($chapter_structure[$subject_key]['chapters'])) {
        $chapters = $chapter_structure[$subject_key]['chapters'];
    }
    
    wp_send_json_success($chapters);
}
add_action('wp_ajax_dialog_cards_get_chapters', 'dialog_cards_ajax_get_chapters');

/**
 * Dialog Cards用のコラム設定
 * H5P編集画面にDialog Cardsの関連付け情報を表示
 */
function dialog_cards_add_h5p_columns($columns) {
    $columns['related_subject'] = '関連科目';
    return $columns;
}
add_filter('manage_h5p_posts_columns', 'dialog_cards_add_h5p_columns');

function dialog_cards_h5p_column_content($column_name, $post_id) {
    if ($column_name != 'related_subject') {
        return;
    }
    
    global $wpdb;
    $h5p_contents_table = $wpdb->prefix . 'h5p_contents';
    
    // H5Pコンテンツを取得
    $h5p_id = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$h5p_contents_table} WHERE post_id = %d",
        $post_id
    ));
    
    if (!$h5p_id) {
        echo '-';
        return;
    }
    
    // 関連付けデータを取得
    $dialog_cards_relations = get_option('dialog_cards_relations', array());
    
    if (isset($dialog_cards_relations[$h5p_id])) {
        $subjects = get_option('progress_tracker_subjects', array());
        $chapter_structure = get_option('progress_tracker_chapters', array());
        
        $rel = $dialog_cards_relations[$h5p_id];
        $subject_name = isset($subjects[$rel['subject']]) ? $subjects[$rel['subject']] : '不明';
        $chapter_title = '';
        
        if (isset($chapter_structure[$rel['subject']]['chapters'][$rel['chapter']]['title'])) {
            $chapter_title = $chapter_structure[$rel['subject']]['chapters'][$rel['chapter']]['title'];
        } else {
            $chapter_title = '第' . $rel['chapter'] . '章';
        }
        
        echo esc_html($subject_name) . ' - ' . esc_html($chapter_title);
    } else {
        echo '未設定';
    }
}
add_action('manage_h5p_posts_custom_column', 'dialog_cards_h5p_column_content', 10, 2);

/**
 * Dialog Cardsのフロントエンド表示をカスタマイズ
 */
function dialog_cards_customize_h5p_content($content) {
    // カスタムスタイルやスクリプトを追加
    return $content;
}
add_filter('h5p_embed_content', 'dialog_cards_customize_h5p_content');

/**
 * H5Pコンテンツの学習進捗と関連付ける機能
 */
function dialog_cards_completed_handler($content_id, $user_id, $score, $max_score) {
    // Dialog Cardsが完了した時の処理
    $dialog_cards_relations = get_option('dialog_cards_relations', array());
    
    if (isset($dialog_cards_relations[$content_id])) {
        $rel = $dialog_cards_relations[$content_id];
        $subject_key = $rel['subject'];
        $chapter_id = $rel['chapter'];
        
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
        
        // Dialog Cardsの完了を記録
        $progress_data[$subject_key]['dialog_cards_completed'][$content_id] = array(
            'score' => $score,
            'max_score' => $max_score,
            'date' => current_time('mysql')
        );
        
        // 進捗データを更新
        update_option('progress_tracker_progress', $progress_data);
    }
}
add_action('h5p_completed', 'dialog_cards_completed_handler', 10, 4);
