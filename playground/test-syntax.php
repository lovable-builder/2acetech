<?php
$out = array( 'bad' => array(), 'checked' => 0 );
$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( '/wordpress/wp-content/themes/2acetech', FilesystemIterator::SKIP_DOTS ) );
foreach ( $it as $f ) {
	if ( 'php' !== $f->getExtension() ) { continue; }
	$out['checked']++;
	try { token_get_all( file_get_contents( $f->getPathname() ), TOKEN_PARSE ); } catch ( Throwable $e ) { $out['bad'][ $f->getPathname() ] = $e->getMessage() . ' line ' . $e->getLine(); }
}
// The script is enqueued and the strings localised.
$out['toast enqueued'] = (bool) ( do_action( 'wp_enqueue_scripts' ) || wp_script_is( 'ace-toast', 'enqueued' ) );
$out['localized'] = wp_scripts()->get_data( 'ace-toast', 'data' );
file_put_contents( '/playground/_syntax-result.json', wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
