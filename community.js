/**
 * コミュニティページのJavaScript
 */
jQuery(document).ready(function($) {
    // タブ切り替え
    $('.tab-item').on('click', function() {
        var tabId = $(this).data('tab');
        
        // タブメニューのアクティブ状態を切り替え
        $('.tab-item').removeClass('active');
        $(this).addClass('active');
        
        // コンテンツの表示・非表示を切り替え
        $('.tab-content').removeClass('active');
        $('#' + tabId + '-content').addClass('active');
    });
    
    // トピックカテゴリーを読み込み
    function loadTopicCategories() {
        $.ajax({
            url: community_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'get_topic_categories'
            },
            success: function(response) {
                if (response.success) {
                    $('#topic-category').append(response.data);
                }
            }
        });
    }
    
    // ページ読み込み時にトピックカテゴリーを取得
    loadTopicCategories();
    
    // トピック作成フォームの送信
    $('#create-topic-form').on('submit', function(e) {
        e.preventDefault();
        
        var category = $('#topic-category').val();
        var title = $('#topic-title').val();
        var content = $('#topic-content').val();
        var nonce = $('#topic_nonce').val();
        
        if (!title || !content || !category) {
            $('#topic-form-message').html('すべての項目を入力してください。').addClass('notice notice-error').show();
            return;
        }
        
        $.ajax({
            url: community_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'create_topic',
                category: category,
                title: title,
                content: content,
                nonce: nonce
            },
            beforeSend: function() {
                $('#topic-form-message').html('投稿中...').removeClass('notice-error').addClass('notice notice-info').show();
            },
            success: function(response) {
                if (response.success) {
                    $('#topic-form-message').html('トピックが作成されました。リダイレクトします...').removeClass('notice-info notice-error').addClass('notice-success');
                    setTimeout(function() {
                        window.location.href = response.data.redirect;
                    }, 1500);
                } else {
                    $('#topic-form-message').html('エラーが発生しました: ' + response.data).removeClass('notice-info notice-success').addClass('notice-error');
                }
            },
            error: function() {
                $('#topic-form-message').html('通信エラーが発生しました。もう一度お試しください。').removeClass('notice-info notice-success').addClass('notice-error');
            }
        });
    });
    
    // URLからタブパラメータを取得して対応するタブを表示
    function showTabFromUrl() {
        var urlParams = new URLSearchParams(window.location.search);
        var tab = urlParams.get('tab');
        
        if (tab) {
            $('.tab-item[data-tab="' + tab + '"]').click();
        }
    }
    
    // ページ読み込み時にURLからタブを取得して表示
    showTabFromUrl();
});
