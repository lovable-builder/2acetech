<?php
/**
 * "Best deals" grid with one tab per top-level category.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

$cats = array();
foreach ( ace_category_list( 5 ) as $cat ) {
	$items = ace_get_products( 'sale', 8, $cat['slug'] );

	// Top up to a full row with the category's other products.
	if ( $items && count( $items ) < 4 ) {
		$key = static function ( $item ) {
			return is_array( $item ) ? $item['title'] : $item->get_id();
		};
		$have = array_map( $key, $items );
		foreach ( ace_get_products( 'latest', 8, $cat['slug'] ) as $extra ) {
			if ( count( $items ) >= 4 ) {
				break;
			}
			if ( ! in_array( $key( $extra ), $have, true ) ) {
				$items[] = $extra;
			}
		}
	}

	if ( $items ) {
		$cat['items'] = $items;
		$cats[]       = $cat;
	}
}
if ( ! $cats ) {
	return;
}
$uid = wp_unique_id( 'deals-' );
?>
<section class="section section--mist" data-reveal aria-labelledby="<?php echo esc_attr( $uid ); ?>">
	<div class="container" data-tabs>
		<header class="section__head section__head--tabs">
			<h2 class="section__title" id="<?php echo esc_attr( $uid ); ?>"><?php echo esc_html( ace_text( 'ace_title_deals' ) ); ?></h2>
			<div class="tabs__list" role="tablist" aria-label="<?php esc_attr_e( 'Deal categories', '2acetech' ); ?>">
				<?php foreach ( $cats as $n => $cat ) : ?>
					<button class="tabs__tab" type="button" role="tab" id="<?php echo esc_attr( "$uid-tab-$n" ); ?>" aria-controls="<?php echo esc_attr( "$uid-panel-$n" ); ?>" aria-selected="<?php echo 0 === $n ? 'true' : 'false'; ?>" tabindex="<?php echo 0 === $n ? '0' : '-1'; ?>"><?php echo esc_html( $cat['label'] ); ?></button>
				<?php endforeach; ?>
			</div>
		</header>

		<?php foreach ( $cats as $n => $cat ) : ?>
			<div class="tabs__panel" role="tabpanel" id="<?php echo esc_attr( "$uid-panel-$n" ); ?>" aria-labelledby="<?php echo esc_attr( "$uid-tab-$n" ); ?>" <?php echo 0 === $n ? '' : 'hidden'; ?>>
				<div class="grid grid--4">
					<?php foreach ( $cat['items'] as $item ) : ?>
						<?php ace_product_card( $item ); ?>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</section>
