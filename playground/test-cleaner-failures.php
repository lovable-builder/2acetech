<?php
// A bad product must not block or loop; a cleaned product must never be cleaned again.
global $wpdb;
$out = array();
try {
	update_option( 'ace_pc_settings', array( 'provider' => 'deepseek', 'keys' => array( 'deepseek' => 'x' ), 'model' => 'm', 'auto' => 1, 'category' => 'Oświetlenie', 'sku_prefix' => '2AC-' ) );
	wp_insert_term( 'Oświetlenie', 'product_cat' );
	$mk = function ( $name, $sku ) use ( $wpdb ) {
		$p = new WC_Product_Simple(); $p->set_name( $name ); $p->set_sku( $sku ); $p->set_status( 'publish' ); $p->set_regular_price( '50.20' );
		$p->set_description( 'Some English text about a lamp.' );
		$id = $p->save();
		update_post_meta( $id, '_vi_wad_aliexpress_product_id', $sku );
		$wpdb->update( $wpdb->posts, array( 'post_modified_gmt' => gmdate( 'Y-m-d H:i:s', time() - 3600 ) ), array( 'ID' => $id ) ); // old enough
		clean_post_cache( $id );
		return $id;
	};
	$bad  = $mk( 'BAD import product one', '1111111111111' );
	$good = $mk( 'GOOD import product two', '2222222222222' );
	$calls = 0;
	add_filter( 'ace_pc_llm_json', function ( $x, $source ) use ( &$calls ) {
		$calls++;
		if ( false !== strpos( $source['title'], 'BAD' ) ) { return array( 'title' => 'too short' ); } // fails the checks
		return array(
			'title' => 'Lampka biurkowa LED z regulacją jasności, 3 tryby',
			'short_bullets' => array( 'Regulacja jasności', 'Trzy tryby światła', 'Lekka konstrukcja', 'Zasilanie USB' ),
			'intro' => 'Lampka biurkowa LED do pracy i nauki. Ma trzy tryby światła. Jasność można regulować.',
			'features' => array( 'Regulacja jasności', 'Trzy tryby światła', 'Zasilanie USB' ),
			'spec_rows' => array( array( 'Źródło światła', 'LED' ), array( 'Tryby', '3' ), array( 'Zasilanie', 'USB' ) ),
			'category' => array( 'Oświetlenie', 'Lampki biurkowe' ),
			'tags' => array( 'lampka biurkowa', 'lampka LED', 'oświetlenie biurka' ),
			'attrs' => array(), 'notes' => array(),
		);
	}, 10, 2 );

	$snap = function ( $label ) use ( $bad, $good, &$calls, &$out ) {
		$out[ $label ] = array(
			'calls_so_far' => $calls,
			'bad_fails' => (int) get_post_meta( $bad, '_aliclean_fails', true ), 'bad_marker' => get_post_meta( $bad, '_aliclean', true ),
			'good_marker' => get_post_meta( $good, '_aliclean', true ), 'good_title' => wc_get_product( $good )->get_name(),
			'auto_waiting' => ACE_PC_Cleaner::waiting( 0, 300, true ), 'all_waiting' => ACE_PC_Cleaner::waiting(),
		);
	};
	$run = function () { delete_transient( 'ace_pc_lock' ); ACE_PC_Settings::cron(); };

	$run();                $snap( 'run1' );   // bad fails once, good is cleaned
	$run();                $snap( 'run2_immediately' ); // bad is in its 30 min pause: no new call
	update_post_meta( $bad, '_aliclean_fail_at', time() - 4000 ); $run(); $snap( 'run3_after_pause' );   // 2nd failure
	update_post_meta( $bad, '_aliclean_fail_at', time() - 4000 ); $run(); $snap( 'run4_after_pause' );   // 3rd failure
	update_post_meta( $bad, '_aliclean_fail_at', time() - 4000 ); $run(); $snap( 'run5_gave_up' );       // no more tries
} catch ( Throwable $e ) { $out['ERROR'] = $e->getMessage() . ' @ ' . basename( $e->getFile() ) . ':' . $e->getLine(); }
file_put_contents( '/playground/_failure-result.json', wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
