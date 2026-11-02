<?php
/**
 * Pages. Cart, checkout and account render here via WooCommerce shortcodes
 * or blocks, so the container stays wide enough for them.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

get_header();

$ace_flow = function_exists( 'is_cart' ) && ( is_cart() || is_checkout() );
?>
<main id="content" class="site-main">
	<div class="container <?php echo ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() ) ) ? '' : 'container--narrow'; ?>">
		<?php
		while ( have_posts() ) {
			the_post();
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry' ); ?>>
				<header class="page-head<?php echo $ace_flow ? ' page-head--flow' : ''; ?>">
					<h1 class="page-title"><?php the_title(); ?></h1>
					<?php
					if ( $ace_flow ) {
						ace_checkout_steps();
					}
					?>
				</header>
				<div class="entry-content"><?php the_content(); ?></div>
				<?php if ( $ace_flow ) : ?>
					<div class="flow-foot">
						<?php if ( is_cart() ) : ?>
							<a class="back-link" href="<?php echo esc_url( ace_shop_url() ); ?>"><?php echo ace_icon( 'arrow', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php esc_html_e( 'Continue shopping', '2acetech' ); ?></a>
						<?php endif; ?>
						<?php ace_single_perks( 'perks--row' ); ?>
					</div>
				<?php endif; ?>
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
