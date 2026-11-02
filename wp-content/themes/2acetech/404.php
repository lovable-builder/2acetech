<?php
/**
 * 404.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="content" class="site-main">
	<div class="container container--narrow notfound">
		<p class="notfound__code">404</p>
		<h1 class="page-title"><?php esc_html_e( 'This page has gone missing', '2acetech' ); ?></h1>
		<p><?php esc_html_e( 'The link may be broken or the page may have moved. Try a search, or head back to the shop.', '2acetech' ); ?></p>
		<?php get_search_form(); ?>
		<p><a class="btn btn--primary" href="<?php echo esc_url( ace_shop_url() ); ?>"><?php esc_html_e( 'Back to the shop', '2acetech' ); ?><?php echo ace_icon( 'arrow', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a></p>
	</div>
</main>
<?php
get_footer();
