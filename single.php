<?php
/**
 * Master Single Post Template - NUVANX Page Shell
 * @package NUVANX_SiteGround
 */

get_header(); 
?>

<main id="main-content" class="nvx-page-shell min-h-screen bg-slate-50 text-slate-900 antialiased">
    <div class="nvx-container mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <?php
        while (have_posts()) :
            the_post();
            ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class('max-w-4xl mx-auto bg-white p-8 rounded-2xl shadow-sm border border-slate-100'); ?>>
                <header class="mb-8">
                    <div class="text-xs font-semibold uppercase tracking-wider text-teal-600 mb-2">
                        <?php the_category(', '); ?>
                    </div>
                    <h1 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl mb-4"><?php the_title(); ?></h1>
                    <div class="text-sm text-slate-500">
                        <time datetime="<?php echo get_the_date('c'); ?>"><?php echo get_the_date(); ?></time>
                    </div>
                </header>
                <div class="entry-content prose prose-slate max-w-none">
                    <?php the_content(); ?>
                </div>
            </article>
        <?php
        endwhile;
        ?>
    </div>
</main>

<?php 
get_footer();
