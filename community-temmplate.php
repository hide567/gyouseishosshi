<?php
/**
 * コミュニティページのテンプレート
 *
 * @package 行政書士試験ブログ（Astra子テーマ）
 */

// 直接アクセスを禁止
if (!defined('ABSPATH')) {
    exit;
}

// ステータスメッセージの表示
if (isset($_GET['status'])) {
    $status = sanitize_text_field($_GET['status']);
    if ($status == 'success') {
        echo '<div class="notice notice-success"><p>質問が投稿されました。</p></div>';
    } elseif (strpos($status, 'error') === 0) {
        echo '<div class="notice notice-error"><p>エラーが発生しました。もう一度お試しください。</p></div>';
    }
}
?>

<div class="community-container">
    <div class="community-header">
        <h2>行政書士試験 コミュニティ</h2>
        <p>試験対策や勉強方法について、情報交換や質問ができるコミュニティです。</p>
    </div>
    
    <!-- タブナビゲーション -->
    <div class="community-tabs">
        <ul class="tab-navigation">
            <li class="tab-item active" data-tab="topics">最新トピック</li>
            <li class="tab-item" data-tab="questions">質問一覧</li>
            <li class="tab-item" data-tab="create">トピック作成</li>
            <li class="tab-item" data-tab="ask">質問する</li>
        </ul>
    </div>
    
    <!-- タブコンテンツ -->
    <div class="tab-content-container">
        <!-- 最新トピック -->
        <div class="tab-content active" id="topics-content">
            <h3>最新のトピック</h3>
            <?php
            $topics = get_community_topics(10);
            if (!empty($topics)) {
                echo '<ul class="topic-list">';
                foreach ($topics as $topic) {
                    $author = get_the_author_meta('display_name', $topic->post_author);
                    $date = get_the_date('Y年m月d日', $topic->ID);
                    $comments = get_comments_number($topic->ID);
                    
                    // カテゴリーを取得
                    $categories = get_the_terms($topic->ID, 'topic_category');
                    $category_html = '';
                    if (!empty($categories) && !is_wp_error($categories)) {
                        $category_html = '<span class="topic-category">' . esc_html($categories[0]->name) . '</span>';
                    }
                    
                    echo '<li class="topic-item">';
                    echo '<h4><a href="' . get_permalink($topic->ID) . '">' . esc_html($topic->post_title) . '</a></h4>';
                    echo '<div class="topic-meta">';
                    echo $category_html;
                    echo '<span class="topic-author">投稿者: ' . esc_html($author) . '</span>';
                    echo '<span class="topic-date">投稿日: ' . $date . '</span>';
                    echo '<span class="topic-comments">コメント: ' . $comments . '</span>';
                    echo '</div>';
                    echo '<div class="topic-excerpt">' . get_the_excerpt($topic->ID) . '</div>';
                    echo '</li>';
                }
                echo '</ul>';
            } else {
                echo '<p>まだトピックがありません。最初のトピックを作成しましょう！</p>';
            }
            ?>
        </div>
        
        <!-- 質問一覧 -->
        <div class="tab-content" id="questions-content">
            <h3>質問一覧</h3>
            <?php
            $questions = get_community_questions(10);
            if (!empty($questions)) {
                echo '<ul class="question-list">';
                foreach ($questions as $question) {
                    $author = get_the_author_meta('display_name', $question->post_author);
                    $date = get_the_date('Y年m月d日', $question->ID);
                    $answers = get_comments_number($question->ID);
                    
                    // カテゴリーを取得
                    $categories = get_the_terms($question->ID, 'question_category');
                    $category_html = '';
                    if (!empty($categories) && !is_wp_error($categories)) {
                        $category_html = '<span class="question-category">' . esc_html($categories[0]->name) . '</span>';
                    }
                    
                    echo '<li class="question-item">';
                    echo '<h4><a href="' . get_permalink($question->ID) . '">' . esc_html($question->post_title) . '</a></h4>';
                    echo '<div class="question-meta">';
                    echo $category_html;
                    echo '<span class="question-author">質問者: ' . esc_html($author) . '</span>';
                    echo '<span class="question-date">質問日: ' . $date . '</span>';
                    echo '<span class="question-answers">回答: ' . $answers . '</span>';
                    echo '</div>';
                    echo '<div class="question-excerpt">' . get_the_excerpt($question->ID) . '</div>';
                    echo '</li>';
                }
                echo '</ul>';
            } else {
                echo '<p>まだ質問がありません。最初の質問をしてみましょう！</p>';
            }
            ?>
        </div>
        
        <!-- トピック作成フォーム -->
        <div class="tab-content" id="create-content">
            <h3>新しいトピックを作成</h3>
            <?php if (is_user_logged_in()) : ?>
                <form id="create-topic-form" class="community-form">
                    <div class="form-group">
                        <label for="topic-category">カテゴリー</label>
                        <select id="topic-category" name="category" required>
                            <option value="">カテゴリーを選択</option>
                            <!-- カテゴリーはJavaScriptで動的に読み込み -->
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="topic-title">タイトル</label>
                        <input type="text" id="topic-title" name="title" required placeholder="トピックのタイトルを入力してください">
                    </div>
                    
                    <div class="form-group">
                        <label for="topic-content">内容</label>
                        <textarea id="topic-content" name="content" rows="6" required placeholder="トピックの内容を入力してください"></textarea>
                    </div>
                    
                    <div class="form-submit">
                        <button type="submit" class="button button-primary">トピックを投稿</button>
                        <div id="topic-form-message"></div>
                    </div>
                    
                    <?php wp_nonce_field('create_topic_nonce', 'topic_nonce'); ?>
                </form>
            <?php else : ?>
                <p>トピックを作成するには<a href="<?php echo wp_login_url(get_permalink()); ?>">ログイン</a>してください。</p>
            <?php endif; ?>
        </div>
        
        <!-- 質問フォーム -->
        <div class="tab-content" id="ask-content">
            <h3>質問する</h3>
            <form id="ask-question-form" class="community-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="submit_question_form">
                
                <div class="form-group">
                    <label for="question-category">カテゴリー</label>
                    <select id="question-category" name="question_category" required>
                        <option value="">カテゴリーを選択</option>
                        <?php
                        $question_categories = get_terms(array(
                            'taxonomy' => 'question_category',
                            'hide_empty' => false
                        ));
                        
                        if (!empty($question_categories) && !is_wp_error($question_categories)) {
                            foreach ($question_categories as $cat) {
                                echo '<option value="' . esc_attr($cat->term_id) . '">' . esc_html($cat->name) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="question-title">質問タイトル</label>
                    <input type="text" id="question-title" name="question_title" required placeholder="質問のタイトルを入力してください">
                </div>
                
                <div class="form-group">
                    <label for="question-content">質問内容</label>
                    <textarea id="question-content" name="question_content" rows="6" required placeholder="質問の詳細を入力してください"></textarea>
                </div>
                
                <div class="form-submit">
                    <button type="submit" class="button button-primary">質問を投稿</button>
                </div>
                
                <?php wp_nonce_field('submit_question_nonce', 'question_nonce'); ?>
            </form>
        </div>
    </div>
</div>

<style>
.community-container {
    margin: 20px 0;
}

.community-header {
    margin-bottom: 20px;
}

.community-tabs {
    margin-bottom: 20px;
}

.tab-navigation {
    display: flex;
    list-style: none;
    padding: 0;
    margin: 0;
    border-bottom: 1px solid #ddd;
}

.tab-item {
    padding: 10px 15px;
    cursor: pointer;
    border: 1px solid transparent;
    border-bottom: none;
    margin-bottom: -1px;
    background-color: #f5f5f5;
    margin-right: 5px;
}

.tab-item.active {
    background-color: white;
    border-color: #ddd;
    border-bottom-color: white;
}

.tab-content {
    display: none;
    padding: 20px;
    border: 1px solid #ddd;
    border-top: none;
}

.tab-content.active {
    display: block;
}

.topic-list, .question-list {
    list-style: none;
    padding: 0;
}

.topic-item, .question-item {
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.topic-item:last-child, .question-item:last-child {
    border-bottom: none;
}

.topic-meta, .question-meta {
    font-size: 0.9em;
    color: #666;
    margin: 5px 0;
}

.topic-category, .question-category {
    background-color: #f0f0f0;
    padding: 3px 8px;
    border-radius: 3px;
    margin-right: 10px;
}

.topic-author, .topic-date, .topic-comments,
.question-author, .question-date, .question-answers {
    margin-right: 15px;
}

.topic-excerpt, .question-excerpt {
    margin-top: 10px;
}

.community-form {
    max-width: 800px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-group input[type="text"],
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 3px;
}

.form-submit {
    margin-top: 20px;
}

#topic-form-message {
    margin-top: 10px;
    padding: 10px;
    display: none;
}

.notice {
    padding: 10px;
    margin-bottom: 20px;
    border-radius: 3px;
}

.notice-success {
    background-color: #dff0d8;
    color: #3c763d;
    border: 1px solid #d6e9c6;
}

.notice-error {
    background-color: #f2dede;
    color: #a94442;
    border: 1px solid #ebccd1;
}
</style>
