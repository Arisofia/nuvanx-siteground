<?php
/**
 * Master Page Template - NUVANX Page Shell
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
            <article id="post-<?php the_ID(); ?>" <?php post_class('prose prose-slate max-w-none'); ?>>
                <header class="mb-8">
                    <h1 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl"><?php the_title(); ?></h1>
                </header>
                <div class="entry-content">
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
