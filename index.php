<?php
/**
 * メインテンプレートファイル
 *
 * @package 行政書士試験ブログ
 */

get_header();
?>

<div class="site-content">
    <main class="content-area">
        <?php if (have_posts()) : ?>
            <?php while (have_posts()) : the_post(); ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class('entry'); ?>>
                    <header class="entry-header">
                        <h2 class="entry-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                        <div class="entry-meta">
                            <?php if (has_category()) : ?>
                                <span class="entry-category"><?php the_category(', '); ?></span>
                            <?php endif; ?>
                            <span class="entry-date"><?php echo get_the_date(); ?></span>
                        </div>
                    </header>

                    <div class="entry-content">
                        <?php
                        if (is_singular()) {
                            the_content();
                        } else {
                            the_excerpt();
                            echo '<p><a href="' . get_permalink() . '" class="read-more">続きを読む →</a></p>';
                        }
                        ?>
                    </div>

                    <?php if (has_tag() && is_singular()) : ?>
                        <div class="post-tags">
                            <?php the_tags('', ' ', ''); ?>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endwhile; ?>

            <div class="pagination">
                <?php echo paginate_links(); ?>
            </div>
        <?php else : ?>
            <article class="no-results">
                <header class="entry-header">
                    <h1 class="entry-title">コンテンツが見つかりません</h1>
                </header>
                <div class="entry-content">
                    <p>お探しのコンテンツは見つかりませんでした。検索をお試しください。</p>
                    <?php get_search_form(); ?>
                </div>
            </article>
        <?php endif; ?>
    </main>

    <?php get_sidebar(); ?>
</div>

<?php get_footer(); ?>
