<?php
/**
 * Pages. Cart, checkout and account render here via WooCommerce shortcodes
 * or blocks, so the container stays wide enough for them.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="content" class="site-main">
	<div class="container <?php echo ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() ) ) ? '' : 'container--narrow'; ?>">
		<?php
		while ( have_posts() ) {
			the_post();
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry' ); ?>>
				<header class="page-head"><h1 class="page-title"><?php the_title(); ?></h1></header>
				<div class="entry-content"><?php the_content(); ?></div>
				<?php wp_link_pages(); ?>
			</article>
			<?php
			if ( comments_open() || get_comments_number() ) {
				comments_template();
			}
		}
		?>
	</div>
</main>
<?php
get_footer();
