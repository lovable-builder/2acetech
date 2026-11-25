<?php
// Test: Sign in with Google scripts wait for consent. Writes /playground/_siwg-result.json
$out = array();
try {
	$sample = '<div>x</div><!-- Przycisk --> <style>.a{}</style> <script src="https://accounts.google.com/gsi/client"></script> <script data-siwg-config="{&quot;clientID&quot;:&quot;1.apps&quot;,&quot;shouldShowOneTapPrompt&quot;:true}" src="https://2acetech.pl/wp-content/plugins/google-site-kit/dist/assets/js/sign-in-with-google-abc.js"></script> <!-- Koniec --> <script id="wc-no-js">var a=1;</script><script src="https://2acetech.pl/wp-includes/js/dist/hooks.min.js"></script>';
	$r = ace_siwg_defer_scripts( $sample );
	$out['gsi library waits'] = false !== strpos( $r, '<script type="text/plain" data-ace-consent="marketing" src="https://accounts.google.com/gsi/client">' );
	$out['site kit script waits'] = (bool) preg_match( '#<script type="text/plain" data-ace-consent="marketing" data-siwg-config=#', $r );
	$out['config + src kept on the Site Kit script'] = false !== strpos( $r, 'shouldShowOneTapPrompt' ) && false !== strpos( $r, 'sign-in-with-google-abc.js' );
	$out['other scripts untouched'] = false !== strpos( $r, '<script id="wc-no-js">' ) && false !== strpos( $r, '<script src="https://2acetech.pl/wp-includes/js/dist/hooks.min.js">' );
	$out['no early google script left'] = 0 === preg_match( '#<script src="https://accounts\.google\.com#', $r );
	$out['idempotent'] = ace_siwg_defer_scripts( $r ) === $r;

	// The real footer hook chain: buffer start -> footer output -> buffer end.
	ob_start();
	do_action( 'wp_footer' );
	ob_end_clean();
	$out['hooks registered'] = false !== has_action( 'wp_footer', 'ace_siwg_buffer_start' ) && false !== has_action( 'wp_footer', 'ace_siwg_buffer_end' );
	add_action( 'wp_footer', function () use ( $sample ) { echo $sample; }, 5 );
	$_COOKIE = array(); $GLOBALS['wp_query']->is_admin = false;
	ob_start(); ace_siwg_buffer_start(); echo $sample; ace_siwg_buffer_end(); $buffered = ob_get_clean();
	$out['buffer rewrites a real footer output'] = false !== strpos( $buffered, 'type="text/plain" data-ace-consent="marketing" src="https://accounts.google.com/gsi/client"' );
	$out['consent.js keeps script order'] = false !== strpos( file_get_contents( get_theme_file_path( 'assets/js/consent.js' ) ), "s.async = old.hasAttribute('async')" );
} catch ( Throwable $e ) {
	$out['ERROR'] = $e->getMessage() . ' @ ' . basename( $e->getFile() ) . ':' . $e->getLine();
}
file_put_contents( '/playground/_siwg-result.json', wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
