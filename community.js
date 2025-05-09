jQuery(document).ready(function($) {
    // デバッグ情報を表示
    console.log('Community script loaded');
    console.log('AJAX URL:', community_vars.ajax_url);
    console.log('REST URL:', community_vars.rest_url);
    
    // トピックカテゴリーの取得（AJAX経由）
    $.ajax({
        url: community_vars.ajax_url,
        type: 'POST',
        data: {
            action: 'get_topic_categories'
        },
        success: function(response) {
            console.log('Category response:', response);
            if (response.success) {
                window.topic_categories = response.data;
                console.log('Categories loaded successfully');
            } else {
                console.log('カテゴリー取得に失敗しました:', response);
            }
        },
        error: function(xhr, status, error) {
            console.log('カテゴリー取得エラー:', status, error);
        }
    });

    // 新規トピック作成ボタンのクリックイベント
    $('.new-topic-btn').on('click', function(e) {
        e.preventDefault();
        console.log('New topic button clicked');
        
        // モーダルの作成とHTML挿入
        var modalHtml = `
            <div id="topic-modal" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:9999;display:flex;justify-content:center;align-items:center;">
                <div style="background:white;padding:20px;border-radius:5px;max-width:600px;width:90%;">
                    <h3>新しいトピックを作成</h3>
                    <form id="new-topic-form">
                        <div class="form-group">
                            <label for="topic-category">カテゴリー</label>
                            <select id="topic-category" class="form-control">
                                <option value="">カテゴリーを選択</option>
                                ${window.topic_categories ? window.topic_categories : ''}
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="topic-title">タイトル</label>
                            <input type="text" id="topic-title" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="topic-content">内容</label>
                            <textarea id="topic-content" class="form-control" rows="5" required></textarea>
                        </div>
                        <input type="hidden" id="topic_nonce" name="topic_nonce" value="${community_vars.nonce}">
                        <div style="text-align:right;margin-top:15px;">
                            <button type="button" id="close-modal" style="margin-right:10px;">キャンセル</button>
                            <button type="submit">投稿する</button>
                        </div>
                    </form>
                </div>
            </div>
        `;
        
        $('body').append(modalHtml);
        console.log('Modal created');
        
        // モーダルを閉じる処理
        $('#close-modal').on('click', function() {
            $('#topic-modal').remove();
        });
        
        // トピック送信処理
        $('#new-topic-form').on('submit', function(e) {
            e.preventDefault();
            console.log('New topic form submitted');
            
            var category = $('#topic-category').val();
            var title = $('#topic-title').val();
            var content = $('#topic-content').val();
            var nonce = $('#topic_nonce').val();
            
            console.log('Form data:', { category, title, content, nonce });
            
            if (!title || !content) {
                alert('タイトルと内容は必須です');
                return;
            }
            
            $.ajax({
                url: community_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'create_topic',
                    title: title,
                    content: content,
                    category: category,
                    nonce: nonce
                },
                success: function(response) {
                    console.log('Topic response:', response);
                    if (response.success) {
                        alert(response.data.message);
                        $('#topic-modal').remove();
                        // ページをリロード
                        location.reload();
                    } else {
                        alert(response.data || 'エラーが発生しました');
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Topic error:', status, error);
                    alert('通信エラーが発生しました');
                }
            });
        });
    });
});
