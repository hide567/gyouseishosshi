<?php
// コミュニティ機能のテンプレート
$topics = get_community_topics(5);
$questions = get_community_questions(5);
$users = get_users(array("number" => 8));
?>
<div class="community-content">
    <h2 class="page-title">行政書士試験 学習コミュニティ</h2>
    
    <div class="community-section">
        <h3 class="section-title">ディスカッションフォーラム</h3>
        <div class="discussion-board">
            <ul class="topic-list">
                <?php if (!empty($topics)) : ?>
                    <?php foreach ($topics as $topic) : ?>
                        <li class="topic-item">
                            <div class="topic-header">
                                <div class="topic-title"><?php echo esc_html($topic->post_title); ?></div>
                                <?php
                                $terms = get_the_terms($topic->ID, "topic_category");
                                if ($terms && !is_wp_error($terms)) :
                                    $term = reset($terms);
                                ?>
                                    <span class="topic-category"><?php echo esc_html($term->name); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="topic-meta">
                                <span>開始: <?php echo esc_html(get_the_author_meta("display_name", $topic->post_author)); ?> - <?php echo get_the_date("Y年n月j日", $topic->ID); ?></span>
                                <div class="topic-stats">
                                    <span>返信: <?php echo get_comments_number($topic->ID); ?></span>
                                    <span>閲覧: <?php echo get_post_meta($topic->ID, "view_count", true) ? get_post_meta($topic->ID, "view_count", true) : "0"; ?></span>
                                </div>
                            </div>
                            <p class="topic-excerpt"><?php echo wp_trim_words($topic->post_content, 20); ?></p>
                        </li>
                    <?php endforeach; ?>
                <?php else : ?>
                    <li class="topic-item">
                        <p>トピックはまだありません。最初のトピックを作成しましょう！</p>
                    </li>
                <?php endif; ?>
            </ul>
            
            <a href="#new-topic" class="new-topic-btn">新しいトピックを作成</a>
        </div>
    </div>
    
    <div class="community-section">
        <h3 class="section-title">質問箱</h3>
        <div class="question-form">
            <form id="question-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <div class="form-group">
                    <label for="question-category" class="form-label">カテゴリー</label>
                    <select id="question-category" name="question_category" class="form-control">
                        <option value="">カテゴリーを選択</option>
                        <?php
                        $question_categories = get_terms(array(
                            "taxonomy" => "question_category",
                            "hide_empty" => false
                        ));
                        
                        if (!empty($question_categories) && !is_wp_error($question_categories)) :
                            foreach ($question_categories as $category) :
                        ?>
                            <option value="<?php echo esc_attr($category->term_id); ?>"><?php echo esc_html($category->name); ?></option>
                        <?php
                            endforeach;
                        endif;
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="question-title" class="form-label">質問タイトル</label>
                    <input type="text" id="question-title" name="question_title" class="form-control" placeholder="質問のタイトル" required>
                </div>
                
                <div class="form-group">
                    <label for="question-content" class="form-label">質問内容</label>
                    <textarea id="question-content" name="question_content" class="form-control" placeholder="質問の内容" required></textarea>
                </div>
                
                <input type="hidden" name="action" value="submit_question_form">
                <?php wp_nonce_field("submit_question_nonce", "question_nonce"); ?>
                <button type="submit" class="submit-btn">質問を送信</button>
            </form>
        </div>
        
        <?php if (!empty($questions)) : ?>
        <div class="recent-answers">
            <h4>最近の質問</h4>
            
            <?php foreach ($questions as $question) : ?>
            <div class="answer-card">
                <div class="answer-question">Q: <?php echo esc_html($question->post_title); ?></div>
                <p class="answer-content"><?php echo wp_trim_words($question->post_content, 30); ?></p>
                <div class="answer-meta">質問者: <?php echo esc_html(get_the_author_meta("display_name", $question->post_author)); ?> - <?php echo get_the_date("Y年n月j日", $question->ID); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="community-section">
        <h3 class="section-title">メンバーディレクトリ</h3>
        <div class="member-grid">
            <?php if (!empty($users)) : ?>
                <?php foreach ($users as $user) : ?>
                    <div class="member-card">
                        <div class="member-avatar"><?php echo esc_html(mb_substr($user->display_name, 0, 1)); ?></div>
                        <h4 class="member-name"><?php echo esc_html($user->display_name); ?></h4>
                        <div class="member-status">登録: <?php echo date("Y年n月", strtotime($user->user_registered)); ?></div>
                        <p class="member-bio"><?php echo esc_html(get_user_meta($user->ID, "description", true)); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <p>メンバーはまだいません。</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // 質問フォーム送信後のステータス表示
    var urlParams = new URLSearchParams(window.location.search);
    var status = urlParams.get('status');
    
    if (status === 'success') {
        alert('質問が送信されました！');
    } else if (status === 'error_empty') {
        alert('タイトルと内容は必須です。もう一度お試しください。');
    } else if (status === 'error_insert') {
        alert('質問の投稿に失敗しました。システム管理者にお問い合わせください。');
    } else if (status === 'error_nonce') {
        alert('セキュリティチェックに失敗しました。ページを更新してもう一度お試しください。');
    } else if (status === 'error') {
        alert('質問の送信に失敗しました。もう一度お試しください。');
    }
});
</script>
