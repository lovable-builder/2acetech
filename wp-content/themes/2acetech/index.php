<?php
/**
 * Blog index, archives and search results.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="content" class="site-main">
	<div class="container container--narrow">
		<header class="page-head">
			<h1 class="page-title">
				<?php
				if ( is_search() ) {
					/* translators: %s: search query. */
					printf( esc_html__( 'Search results for "%s"', '2acetech' ), esc_html( get_search_query() ) );
				} elseif ( is_archive() ) {
					the_archive_title();
				} else {
					echo esc_html( single_post_title( '', false ) ? single_post_title( '', false ) : __( 'Blog', '2acetech' ) );
				}
				?>
			</h1>
		</header>

		<?php if ( have_posts() ) : ?>
			<div class="posts">
				<?php
				while ( have_posts() ) {
					the_post();
					get_template_part( 'template-parts/content', get_post_type() );
				}
				?>
			</div>
			<?php the_posts_pagination(); ?>
		<?php else : ?>
			<p><?php esc_html_e( 'Nothing found. Try a different search.', '2acetech' ); ?></p>
			<?php get_search_form(); ?>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
