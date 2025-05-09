php
<?php
/**
 * 投稿詳細ページのテンプレート
 *
 * @package 行政書士試験ブログ
 */

get_header();
?>

<div class="site-content">
    <main class="content-area">
        <?php
        while (have_posts()) :
            the_post();
        ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class('entry'); ?>>
                <header class="entry-header">
                    <h1 class="entry-title"><?php the_title(); ?></h1>
                    <div class="entry-meta">
                        <?php if (has_category()) : ?>
                            <span class="entry-category"><?php the_category(', '); ?></span>
                        <?php endif; ?>
                        <span class="entry-date"><?php echo get_the_date(); ?></span>
                    </div>
                </header>

                <div class="entry-content">
                    <?php
                    the_content();
                    
                    wp_link_pages(array(
                        'before' => '<div class="page-links">' . __('ページ:', 'gyouseishoshi'),
                        'after'  => '</div>',
                    ));
                    ?>
                </div>

                <?php if (has_tag()) : ?>
                    <div class="post-tags">
                        <?php the_tags('', ' ', ''); ?>
                    </div>
                <?php endif; ?>
            </article>

            <?php
            // 前後の投稿へのナビゲーションを表示
            the_post_navigation(array(
                'prev_text' => '<span class="nav-subtitle">' . __('前の投稿:', 'gyouseishoshi') . '</span> <span class="nav-title">%title</span>',
                'next_text' => '<span class="nav-subtitle">' . __('次の投稿:', 'gyouseishoshi') . '</span> <span class="nav-title">%title</span>',
            ));

            // コメントテンプレート
            if (comments_open() || get_comments_number()) :
                comments_template();
            endif;
        endwhile;
        ?>
    </main>

    <?php get_sidebar(); ?>
</div>

<?php get_footer(); ?>
