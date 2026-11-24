<?php
/**
 * Brand strip. Uses WooCommerce Brands (logos) when the store has them,
 * otherwise placeholder names.
 *
 * Tabs by category ("Wszystkie", "Sport i turystyka", ...) filter the brands shown; when there are more
 * brands than fit in one row they scroll as a slow ticker that stops on hover (assets/js/brands.js).
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

$brands = ace_brand_list();
if ( ! $brands ) {
	return;
}

// Category tabs: only categories that have brands, the ones with most brands first.
$tabs = array();
if ( count( $brands ) > 1 && function_exists( 'ace_brand_category_map' ) && ! ace_is_demo() ) {
	$map    = ace_brand_category_map();
	$counts = array();
	foreach ( $brands as $b ) {
		foreach ( $b['cats'] as $slug ) {
			$counts[ $slug ] = isset( $counts[ $slug ] ) ? $counts[ $slug ] + 1 : 1;
		}
	}
	arsort( $counts );
	foreach ( array_slice( $counts, 0, 6, true ) as $slug => $n ) {
		if ( isset( $map['cats'][ $slug ] ) ) {
			$tabs[ $slug ] = $map['cats'][ $slug ]->name;
		}
	}
	if ( count( $tabs ) < 2 ) {
		$tabs = array();
	}
}
?>
<section class="brands <?php echo esc_attr( ace_brand_strip_class() ); ?>" style="<?php echo esc_attr( ace_brand_style_attr() ); ?>" aria-label="<?php esc_attr_e( 'Brands', '2acetech' ); ?>" data-brands>
	<div class="container">
		<?php if ( $tabs ) : ?>
			<div class="brands__head">
				<h2 class="brands__title"><?php esc_html_e( 'Brands', '2acetech' ); ?></h2>
				<ul class="brands__tabs" aria-label="<?php esc_attr_e( 'Show brands by category', '2acetech' ); ?>">
					<li><button type="button" class="brands__tab" data-brands-tab="" aria-pressed="true"><?php esc_html_e( 'All', '2acetech' ); ?></button></li>
					<?php foreach ( $tabs as $slug => $name ) : ?>
						<li><button type="button" class="brands__tab" data-brands-tab="<?php echo esc_attr( $slug ); ?>" aria-pressed="false"><?php echo esc_html( $name ); ?></button></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
		<div class="brands__marquee" data-brands-marquee>
			<div class="brands__track">
				<ul class="brands__list" data-brands-list>
					<?php foreach ( $brands as $brand ) : ?>
						<li data-cats="<?php echo esc_attr( implode( ' ', $brand['cats'] ) ); ?>">
							<?php if ( $brand['url'] ) : ?>
								<a class="brand" href="<?php echo esc_url( $brand['url'] ); ?>">
							<?php else : ?>
								<span class="brand">
							<?php endif; ?>
								<?php
								if ( $brand['logo'] ) {
									echo $brand['logo']; // phpcs:ignore WordPress.Security.EscapeOutput
								} else {
									echo esc_html( $brand['label'] );
								}
								?>
							<?php echo $brand['url'] ? '</a>' : '</span>'; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	</div>
</section>
