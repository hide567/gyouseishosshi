/**
 * 学習進捗管理用のインタラクティブ機能
 */
jQuery(document).ready(function($) {
    // 章の展開/折りたたみ
    $(document).on('click', '.progress-tracker-shortcode .chapter-header', function() {
        var $chapterItem = $(this).closest('.chapter-item');
        var $sectionsContainer = $chapterItem.find('.chapter-sections');
        var $toggle = $(this).find('.chapter-toggle');
        
        if ($sectionsContainer.is(':visible')) {
            $sectionsContainer.slideUp(200);
            $toggle.text('+');
        } else {
            $sectionsContainer.slideDown(200);
            $toggle.text('-');
        }
    });
    
    // タブ切り替え
    $(document).on('click', '.progress-tracker-shortcode .progress-tab', function() {
        var subjectKey = $(this).data('subject');
        
        // タブ切り替え
        $('.progress-tracker-shortcode .progress-tab').removeClass('active');
        $(this).addClass('active');
        
        // 科目表示切り替え
        $('.progress-tracker-shortcode .progress-subject').hide();
        $('.progress-tracker-shortcode .progress-subject[data-subject="' + subjectKey + '"]').show();
    });
    
    // チェックボックスの処理
    $(document).on('change', '.section-check-level-1', function() {
        var $section = $(this).closest('.section-item');
        var $level2Check = $section.find('.section-check-level-2');
        var isChecked = $(this).prop('checked');
        
        // レベル1のチェックを外した場合、レベル2も自動的に外す
        if (!isChecked) {
            $level2Check.prop('checked', false);
        }
        
        updateSectionStatus($section, isChecked ? 1 : 0);
    });
    
    $(document).on('change', '.section-check-level-2', function() {
        var $section = $(this).closest('.section-item');
        var $level1Check = $section.find('.section-check-level-1');
        var isChecked = $(this).prop('checked');
        
        // レベル2をチェックした場合、レベル1も自動的にチェック
        if (isChecked) {
            $level1Check.prop('checked', true);
        }
        
        updateSectionStatus($section, isChecked ? 2 : 1);
    });
    
    // Ajax処理関数
    function updateSectionStatus($section, level) {
        var subject = $section.data('subject');
        var chapter = $section.data('chapter');
        var section = $section.data('section');
        var nonce = progress_tracker.nonce;
        
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
            url: progress_tracker.ajax_url,
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
                    } else if (response.data.chapter_completed) {
                        $chapter.addClass('completed');
                    }
                    
                    // セクションのスタイルを更新
                    if (level == 0) {
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
