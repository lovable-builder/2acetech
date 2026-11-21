<?php
// Test: camping categories get the tent illustration, others keep theirs. Writes /playground/_tent-result.json
$out = array();
try {
	foreach ( array( 'Camping', 'Kemping i biwak', 'Camping i turystyka', 'Materace dmuchane', 'Sport i turystyka', 'Oświetlenie', 'Ogród', 'Kuchnia' ) as $n ) {
		$out['guess: ' . $n] = ace_guess_art( $n . ' ' . sanitize_title( $n ) );
	}
	$svg = ace_art( 'tent' );
	$out['tent in art names'] = in_array( 'tent', ace_art_names(), true );
	$out['tent in choices'] = isset( ace_art_choices()['tent'] );
	$out['svg ok'] = 0 === strpos( $svg, '<svg' ) && false !== strpos( $svg, '</svg>' ) && '' !== $svg;
	file_put_contents( '/playground/_tent.svg', str_replace( 'var(--art-accent)', '#f50505', $svg ) );
} catch ( Throwable $e ) {
	$out['ERROR'] = $e->getMessage() . ' @ ' . basename( $e->getFile() ) . ':' . $e->getLine();
}
file_put_contents( '/playground/_tent-result.json', wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
