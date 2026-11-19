<?php
/**
 * The cleaning itself: find imported products, get Polish copy from Claude,
 * validate it, apply it, and keep a backup so it can be undone.
 *
 * @package 2ace-product-cleaner
 */

defined( 'ABSPATH' ) || exit;

class ACE_PC_Cleaner {

	const MARKER = '_aliclean';
	const BACKUP = '_aliclean_backup';

	/* ------------------------------------------------------------------
	 * Which products
	 * ---------------------------------------------------------------- */

	/** Is this product an AliExpress import? */
	public static function is_import( $product ) {
		$sku = trim( (string) $product->get_sku() );
		if ( preg_match( '/^\d{13,}$/', $sku ) ) {
			return true;
		}
		foreach ( array_keys( get_post_meta( $product->get_id() ) ) as $key ) {
			$k = strtolower( $key );
			if ( false !== strpos( $k, 'aliexpress' ) || false !== strpos( $k, 'ald' ) ) {
				return true;
			}
		}
		return false;
	}

	/** Own products are never touched. */
	public static function is_protected( $product ) {
		$sku = (string) $product->get_sku();
		return 0 === strpos( $sku, 'RMF-' ) || 0 === stripos( $sku, 'lig' );
	}

	public static function is_cleaned( $id ) {
		return '' !== (string) get_post_meta( $id, self::MARKER, true );
	}

	/**
	 * Imports that still wait for cleaning.
	 *
	 * @param int $limit      How many (0 = all).
	 * @param int $min_age    Skip products changed in the last N seconds (an import may still be running).
	 * @return int[] Product ids, oldest first.
	 */
	public static function waiting( $limit = 0, $min_age = 0 ) {
		$args = array(
			'post_type'      => 'product',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => -1,
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array( array( 'key' => self::MARKER, 'compare' => 'NOT EXISTS' ) ),
		);
		if ( $min_age ) {
			$args['date_query'] = array( array( 'column' => 'post_modified_gmt', 'before' => gmdate( 'Y-m-d H:i:s', time() - $min_age ) ) );
		}
		$out = array();
		foreach ( get_posts( $args ) as $id ) {
			$p = wc_get_product( $id );
			if ( $p && self::is_import( $p ) && ! self::is_protected( $p ) ) {
				$out[] = $id;
				if ( $limit && count( $out ) >= $limit ) {
					break;
				}
			}
		}
		return $out;
	}

	/* ------------------------------------------------------------------
	 * Source data (what Claude sees)
	 * ---------------------------------------------------------------- */

	private static function strip_html( $html, $limit = 7000 ) {
		$s = preg_replace( '#<(script|style)[^>]*>.*?</\1>#si', ' ', (string) $html );
		$s = preg_replace( '#</(li|p|tr|h\d|div)>|<br\s*/?>#i', "\n", $s );
		$s = wp_strip_all_tags( $s );
		$s = html_entity_decode( $s, ENT_QUOTES, 'UTF-8' );
		$s = preg_replace( '/[ \t\xC2\xA0]+/', ' ', $s );
		$s = preg_replace( "/\n\s*\n+/", "\n", $s );
		return mb_substr( trim( $s ), 0, $limit );
	}

	private static function is_origin_label( $label ) {
		return (bool) preg_match( '/ships?\s*from|shipping\s*from|origin/i', $label );
	}

	/**
	 * Normalised view of the product's attributes: label => [key, options(name list), slug_to_name].
	 */
	private static function read_attributes( $product ) {
		$out = array();
		foreach ( $product->get_attributes() as $key => $attr ) {
			$name = $attr->get_name();
			if ( $attr->is_taxonomy() ) {
				$terms = wc_get_product_terms( $product->get_id(), $name, array( 'fields' => 'all' ) );
				$map   = array();
				foreach ( $terms as $t ) {
					$map[ $t->slug ] = $t->name;
				}
				$label   = wc_attribute_label( $name );
				if ( $label === $name ) { // Label not registered: build a readable one from the slug.
					$label = ucwords( str_replace( '-', ' ', preg_replace( '/^pa_/', '', $name ) ) );
				}
				$options = array_values( $map );
			} else {
				$label   = $name;
				$options = array_values( $attr->get_options() );
				$map     = array();
				foreach ( $options as $o ) {
					$map[ $o ] = $o;
				}
			}
			$out[ $label ] = array( 'key' => $key, 'options' => $options, 'map' => $map, 'variation' => $attr->get_variation() );
		}
		return $out;
	}

	/* ------------------------------------------------------------------
	 * Claude
	 * ---------------------------------------------------------------- */

	private static function prompt() {
		return <<<'TXT'
Jesteś redaktorem sklepu internetowego (polski sklep z elektroniką i oświetleniem).
Na podstawie danych źródłowych produktu przygotuj polskie opisy. Zwróć WYŁĄCZNIE jeden obiekt JSON, bez komentarza i bez ``` .

ZASADY (bezwzględne):
- Wszystko po polsku. Używaj WYŁĄCZNIE faktów z opisu źródłowego i atrybutów. Niczego nie wymyślaj. Brak danych = pomiń.
- Bez obietnic dostawy, bez gwarancji, bez certyfikatów i norm (CE, FCC, RoHS itp.), nawet jeśli są w źródle.
- Usuń nazwy sprzedawców i marek ze źródła (pole "Brand Name", nazwy w opisie), numery modeli, kraj pochodzenia, wzmianki o AliExpress i Chinach, nazwy aplikacji sprzedawcy.
- Pomiń nieprecyzyjne "zakresy" z filtrów (np. Luminous Flux 60-249, Wattage 0-5W) i dane sprzeczne. Przy sprzecznych wartościach wybierz ostrożniejszą i opisz to w "notes".
- Tytuł: 45-65 znaków, bez słów pisanych WIELKIMI literami (akronimy typu LED, RGB, USB, IP65 są dozwolone), bez upychania słów kluczowych.
- Zmiana nazw atrybutów: Color->Kolor, Emitting Color->Kolor światła, Size->Rozmiar, Length->Długość, Wattage->Moc, Plug Type->Wtyczka, Material->Materiał, Shape->Kształt, Voltage->Napięcie, Quantity->Ilość. WAŻNE: jeśli wartości atrybutu wyraźnie nie pasują do jego nazwy (np. "Emitting Color" zawiera długości rolek, "Wattage" zawiera "100M 800LED"), nazwij go według rzeczywistej zawartości i napisz o tym w "notes".
- Atrybuty "Ships From"/"Shipping From"/"Origin" pomiń w "attrs" (usuwa je wtyczka).
- Każdą wartość każdego zachowanego atrybutu przetłumacz na polski (np. "Warm White" -> "Ciepła biel", "EU Plug" -> "EU"). Podaj wszystkie wartości z listy, bez wyjątku.
- Atrybut zawierający tylko nieprecyzyjny zakres z filtra (np. Wattage "0-5W") albo dane sprzeczne: ustaw {"drop": true} (atrybut zostanie usunięty ze sklepu).
- Atrybut z jedną wartością u wszystkich wariantów ustaw "variation": false, chyba że jest jedynym atrybutem produktu - wtedy true.

FORMAT ODPOWIEDZI:
{
 "title": "...",
 "short_bullets": ["4-6 punktów, każdy max 10 słów"],
 "intro": "2-3 zdania wstępu",
 "features": ["3-8 najważniejszych cech"],
 "spec_rows": [["Nazwa parametru","Wartość"]],
 "tags": ["3-5 polskich tagów"],
 "attrs": { "<oryginalna nazwa atrybutu>": {"name": "polska nazwa", "variation": true, "values": {"<oryginalna wartość>": "<polska wartość>"}} },
 "notes": ["krótko: sprzeczności, pominięte dane, zmiany nazw wbrew liście"]
}
(spec_rows: tabela Specyfikacja, min. 3 wiersze, tylko potwierdzone dane. Dla atrybutu do usunięcia: "<nazwa>": {"drop": true}.)

DANE ŹRÓDŁOWE:

TXT;
	}

	/* ------------------------------------------------------------------
	 * The AI provider (DeepSeek by default, or Anthropic Claude)
	 * ---------------------------------------------------------------- */

	/** One HTTP call to the provider. Returns the decoded JSON body or throws with the provider's own message. */
	private static function http( $o, $method, $path, $body = null ) {
		if ( 'anthropic' === $o['provider'] ) {
			$url     = 'https://api.anthropic.com' . $path;
			$headers = array( 'x-api-key' => $o['api_key'], 'anthropic-version' => '2023-06-01', 'content-type' => 'application/json' );
		} else {
			$url     = 'https://api.deepseek.com' . $path;
			$headers = array( 'Authorization' => 'Bearer ' . $o['api_key'], 'content-type' => 'application/json' );
		}
		$args = array( 'method' => $method, 'timeout' => 150, 'headers' => $headers );
		if ( null !== $body ) {
			$args['body'] = wp_json_encode( $body );
		}
		$res = wp_remote_request( $url, $args );
		if ( is_wp_error( $res ) ) {
			throw new Exception( $res->get_error_message() );
		}
		$code = wp_remote_retrieve_response_code( $res );
		$json = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( $code < 200 || $code >= 300 ) {
			$msg = isset( $json['error']['message'] ) ? $json['error']['message'] : 'HTTP ' . $code;
			throw new Exception( ( 'anthropic' === $o['provider'] ? 'Claude API: ' : 'DeepSeek API: ' ) . $msg );
		}
		return is_array( $json ) ? $json : array();
	}

	/** Model ids the key can use. */
	public static function list_models( $o ) {
		$j   = self::http( $o, 'GET', 'anthropic' === $o['provider'] ? '/v1/models' : '/models' );
		$ids = array();
		foreach ( isset( $j['data'] ) ? $j['data'] : array() as $m ) {
			if ( ! empty( $m['id'] ) ) {
				$ids[] = $m['id'];
			}
		}
		return $ids;
	}

	/** The model to use: the one typed in settings, else (DeepSeek) the cheapest-looking one the account offers. */
	public static function model( $o ) {
		if ( '' !== $o['model'] ) {
			return $o['model'];
		}
		if ( 'anthropic' === $o['provider'] ) {
			return 'claude-sonnet-5-5';
		}
		$cached = get_transient( 'ace_pc_model' );
		if ( $cached ) {
			return $cached;
		}
		$pick = 'deepseek-chat';
		try {
			$ids = self::list_models( $o );
			foreach ( array( '/flash/i', '/chat/i' ) as $re ) {
				foreach ( $ids as $id ) {
					if ( preg_match( $re, $id ) ) {
						$pick = $id;
						break 2;
					}
				}
			}
			if ( 'deepseek-chat' === $pick && $ids && ! in_array( $pick, $ids, true ) ) {
				foreach ( $ids as $id ) {
					if ( ! preg_match( '/reason|pro/i', $id ) ) {
						$pick = $id;
						break;
					}
				}
			}
		} catch ( Exception $e ) {
			return $pick; // Do not cache a guess made while the call failed.
		}
		set_transient( 'ace_pc_model', $pick, 12 * HOUR_IN_SECONDS );
		return $pick;
	}

	/** Ask the AI. Returns the decoded JSON object or throws. */
	private static function ask( $source, $extra = '' ) {
		$prompt = self::prompt() . wp_json_encode( $source, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) . $extra;

		// Tests (and advanced users) can supply the answer without calling the API.
		$fake = apply_filters( 'ace_pc_llm_json', null, $source, $extra );
		if ( is_array( $fake ) ) {
			return $fake;
		}

		$o = ACE_PC_Settings::get();
		if ( '' === $o['api_key'] ) {
			throw new Exception( __( 'No API key set (WooCommerce > Product Cleaner).', '2ace-product-cleaner' ) );
		}
		$model = self::model( $o );
		if ( 'anthropic' === $o['provider'] ) {
			$j    = self::http( $o, 'POST', '/v1/messages', array( 'model' => $model, 'max_tokens' => 4000, 'messages' => array( array( 'role' => 'user', 'content' => $prompt ) ) ) );
			$text = isset( $j['content'][0]['text'] ) ? $j['content'][0]['text'] : '';
		} else {
			$j    = self::http(
				$o,
				'POST',
				'/chat/completions',
				array(
					'model'           => $model,
					'messages'        => array(
						array( 'role' => 'system', 'content' => 'You reply with one valid JSON object only.' ),
						array( 'role' => 'user', 'content' => $prompt ),
					),
					'response_format' => array( 'type' => 'json_object' ),
					'max_tokens'      => 8000,
					'temperature'     => 0.3,
					'stream'          => false,
				)
			);
			$text = isset( $j['choices'][0]['message']['content'] ) ? $j['choices'][0]['message']['content'] : '';
		}
		$text = preg_replace( '/^```(?:json)?\s*|\s*```$/', '', trim( $text ) );
		if ( ! preg_match( '/\{.*\}/s', $text, $m ) ) {
			throw new Exception( 'The AI returned no JSON.' );
		}
		$json = json_decode( $m[0], true );
		if ( ! is_array( $json ) ) {
			throw new Exception( 'The AI answer was not valid JSON.' );
		}
		return $json;
	}

	/* ------------------------------------------------------------------
	 * Checks
	 * ---------------------------------------------------------------- */

	private static function words( $s ) {
		return count( preg_split( '/\s+/u', trim( wp_strip_all_tags( $s ) ), -1, PREG_SPLIT_NO_EMPTY ) );
	}

	private static function validate( $c, $attrs, $source_text ) {
		$e = array();
		$t = isset( $c['title'] ) ? (string) $c['title'] : '';
		$n = mb_strlen( $t );
		if ( $n < 45 || $n > 65 ) {
			$e[] = "title is $n characters (need 45-65)";
		}
		foreach ( preg_split( '/\s+/u', $t ) as $w ) {
			if ( preg_match( '/^[A-ZĄĆĘŁŃÓŚŹŻ]{5,}$/u', trim( $w, ',.' ) ) ) {
				$e[] = 'CAPS word in title';
			}
		}
		$b = isset( $c['short_bullets'] ) && is_array( $c['short_bullets'] ) ? $c['short_bullets'] : array();
		if ( count( $b ) < 4 || count( $b ) > 6 ) {
			$e[] = count( $b ) . ' short bullets (need 4-6)';
		}
		foreach ( $b as $x ) {
			if ( self::words( $x ) > 10 ) {
				$e[] = "bullet over 10 words: $x";
			}
		}
		$intro = isset( $c['intro'] ) ? (string) $c['intro'] : '';
		$sent  = preg_match_all( '/[.!?](?:\s|$)/u', $intro );
		if ( $sent < 2 || $sent > 3 ) {
			$e[] = "intro has $sent sentences (need 2-3)";
		}
		$f = isset( $c['features'] ) && is_array( $c['features'] ) ? $c['features'] : array();
		if ( count( $f ) < 3 || count( $f ) > 8 ) {
			$e[] = 'features need 3-8 items';
		}
		$rows = isset( $c['spec_rows'] ) && is_array( $c['spec_rows'] ) ? $c['spec_rows'] : array();
		if ( count( $rows ) < 3 ) {
			$e[] = 'spec table needs 3+ rows';
		}
		$tags = isset( $c['tags'] ) && is_array( $c['tags'] ) ? array_unique( $c['tags'] ) : array();
		if ( count( $tags ) < 3 || count( $tags ) > 5 ) {
			$e[] = 'tags need 3-5 distinct items';
		}
		$blob = $t . ' ' . $intro . ' ' . implode( ' ', $b ) . ' ' . implode( ' ', $f ) . ' ' . implode( ' ', $tags );
		foreach ( $rows as $r ) {
			$blob .= ' ' . implode( ' ', (array) $r );
		}
		if ( preg_match( '/aliexpress|alibaba|china|chin(?:y|ą|ach|om)\b|gwarancj|certyf|\bCE\b|FCC|RoHS|dostaw|wysył|wysyl/iu', $blob, $m ) ) {
			$e[] = 'forbidden word in copy: ' . $m[0];
		}
		if ( preg_match_all( '/(?:Brand Name|Model Number)\s*:\s*([^\n]+)/i', $source_text, $mm ) ) {
			foreach ( $mm[1] as $brand ) {
				$brand = trim( $brand );
				if ( mb_strlen( $brand ) > 2 && false !== mb_stripos( $blob, $brand ) ) {
					$e[] = "seller/brand/model in copy: $brand";
				}
			}
		}
		$plans = isset( $c['attrs'] ) && is_array( $c['attrs'] ) ? $c['attrs'] : array();
		$names = array();
		foreach ( $attrs as $label => $a ) {
			if ( self::is_origin_label( $label ) ) {
				continue;
			}
			$plan = isset( $plans[ $label ] ) ? $plans[ $label ] : null;
			if ( $plan && ! empty( $plan['drop'] ) ) {
				continue;
			}
			if ( ! $plan || empty( $plan['name'] ) ) {
				$e[] = "no plan for attribute $label";
				continue;
			}
			$names[] = $plan['name'];
			foreach ( $a['options'] as $o ) {
				if ( empty( $plan['values'][ $o ] ) ) {
					$e[] = "attribute $label: value '$o' not translated";
				}
			}
		}
		if ( count( $names ) !== count( array_unique( $names ) ) ) {
			$e[] = 'duplicate attribute names';
		}
		return $e;
	}

	/* ------------------------------------------------------------------
	 * Helpers
	 * ---------------------------------------------------------------- */

	/** Round UP to the next price ending in .99 (never lowers a price). */
	public static function up99( $v ) {
		$n = ceil( round( (float) $v - 0.99, 4 ) );
		return number_format( $n + 0.99, 2, '.', '' );
	}

	private static function next_sku( $prefix ) {
		global $wpdb;
		$like = $wpdb->esc_like( $prefix ) . '%';
		$skus = $wpdb->get_col( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_sku' AND meta_value LIKE %s", $like ) );
		$top  = 0;
		foreach ( $skus as $s ) {
			if ( preg_match( '/^' . preg_quote( $prefix, '/' ) . '(\d+)$/i', $s, $m ) ) {
				$top = max( $top, (int) $m[1] );
			}
		}
		return $prefix . sprintf( '%04d', $top + 1 );
	}

	private static function html( $c ) {
		$ul = function ( $items ) {
			return '<ul>' . implode( '', array_map( function ( $i ) {
				return '<li>' . esc_html( $i ) . '</li>';
			}, $items ) ) . '</ul>';
		};
		$rows = '';
		foreach ( $c['spec_rows'] as $r ) {
			$rows .= '<tr><th>' . esc_html( $r[0] ) . '</th><td>' . esc_html( isset( $r[1] ) ? $r[1] : '' ) . '</td></tr>';
		}
		return array(
			$ul( $c['short_bullets'] ),
			'<p>' . esc_html( $c['intro'] ) . '</p><h3>Najważniejsze cechy</h3>' . $ul( $c['features'] ) . '<h3>Specyfikacja</h3><table>' . $rows . '</table>',
		);
	}

	/* ------------------------------------------------------------------
	 * Backup / undo
	 * ---------------------------------------------------------------- */

	private static function snapshot( $product ) {
		$id   = $product->get_id();
		$post = get_post( $id );
		$snap = array(
			'post'   => array( 'post_title' => $post->post_title, 'post_name' => $post->post_name, 'post_content' => $post->post_content, 'post_excerpt' => $post->post_excerpt ),
			'sku'    => get_post_meta( $id, '_sku', true ),
			'attrs'  => get_post_meta( $id, '_product_attributes', true ),
			'cats'   => wp_get_object_terms( $id, 'product_cat', array( 'fields' => 'ids' ) ),
			'tags'   => wp_get_object_terms( $id, 'product_tag', array( 'fields' => 'ids' ) ),
			'terms'  => array(),
			'prices' => array( '_regular_price' => get_post_meta( $id, '_regular_price', true ), '_sale_price' => get_post_meta( $id, '_sale_price', true ) ),
			'vars'   => array(),
		);
		if ( is_array( $snap['attrs'] ) ) {
			foreach ( $snap['attrs'] as $key => $a ) {
				if ( ! empty( $a['is_taxonomy'] ) ) {
					$snap['terms'][ $key ] = wp_get_object_terms( $id, $key, array( 'fields' => 'ids' ) );
				}
			}
		}
		foreach ( get_children( array( 'post_parent' => $id, 'post_type' => 'product_variation', 'post_status' => 'any', 'numberposts' => -1 ) ) as $v ) {
			$meta = array();
			foreach ( array( '_regular_price', '_sale_price', '_price' ) as $k ) {
				$meta[ $k ] = get_post_meta( $v->ID, $k, true );
			}
			foreach ( get_post_meta( $v->ID ) as $k => $val ) {
				if ( 0 === strpos( $k, 'attribute_' ) ) {
					$meta[ $k ] = $val[0];
				}
			}
			$snap['vars'][ $v->ID ] = array( 'status' => $v->post_status, 'meta' => $meta );
		}
		return $snap;
	}

	public static function undo( $id ) {
		$snap = get_post_meta( $id, self::BACKUP, true );
		if ( ! is_array( $snap ) ) {
			throw new Exception( __( 'No backup found for this product.', '2ace-product-cleaner' ) );
		}
		wp_update_post( array_merge( array( 'ID' => $id ), $snap['post'] ) );
		update_post_meta( $id, '_sku', $snap['sku'] );
		update_post_meta( $id, '_product_attributes', $snap['attrs'] );
		wp_set_object_terms( $id, array_map( 'intval', (array) $snap['cats'] ), 'product_cat' );
		wp_set_object_terms( $id, array_map( 'intval', (array) $snap['tags'] ), 'product_tag' );
		foreach ( (array) $snap['terms'] as $tax => $ids ) {
			wp_set_object_terms( $id, array_map( 'intval', (array) $ids ), $tax );
		}
		foreach ( (array) $snap['prices'] as $k => $val ) {
			update_post_meta( $id, $k, $val );
		}
		foreach ( (array) $snap['vars'] as $vid => $v ) {
			foreach ( get_post_meta( $vid ) as $k => $_ ) {
				if ( 0 === strpos( $k, 'attribute_' ) ) {
					delete_post_meta( $vid, $k );
				}
			}
			foreach ( $v['meta'] as $k => $val ) {
				update_post_meta( $vid, $k, $val );
			}
			wp_update_post( array( 'ID' => $vid, 'post_status' => $v['status'] ) );
		}
		delete_post_meta( $id, self::MARKER );
		delete_post_meta( $id, self::BACKUP );
		self::refresh( $id );
	}

	private static function refresh( $id ) {
		$p = wc_get_product( $id );
		if ( $p && $p->is_type( 'variable' ) ) {
			WC_Product_Variable::sync( $id );
		}
		wc_delete_product_transients( $id );
		clean_post_cache( $id );
	}

	/* ------------------------------------------------------------------
	 * The job
	 * ---------------------------------------------------------------- */

	/**
	 * Clean one product. Throws on anything unsafe (nothing is written then).
	 *
	 * @return array title, sku, url, notes.
	 */
	public static function clean( $id ) {
		$product = wc_get_product( $id );
		if ( ! $product || ! in_array( $product->get_type(), array( 'simple', 'variable' ), true ) ) {
			throw new Exception( __( 'Unsupported product type.', '2ace-product-cleaner' ) );
		}
		if ( self::is_protected( $product ) ) {
			throw new Exception( __( 'This is one of your own products, it is never changed.', '2ace-product-cleaner' ) );
		}
		if ( self::is_cleaned( $id ) ) {
			throw new Exception( __( 'Already cleaned. Use Undo first to run it again.', '2ace-product-cleaner' ) );
		}
		$opt   = ACE_PC_Settings::get();
		$attrs = self::read_attributes( $product );

		$vars = array();   // [ variation_id => [ label => option name ] ]
		if ( $product->is_type( 'variable' ) ) {
			foreach ( $product->get_children() as $vid ) {
				$v = wc_get_product( $vid );
				if ( ! $v ) {
					continue;
				}
				$row = array();
				$raw = $v->get_attributes(); // key (pa_x or sanitised name) => slug / option.
				foreach ( $attrs as $label => $a ) {
					$val = isset( $raw[ $a['key'] ] ) ? $raw[ $a['key'] ] : ( isset( $raw[ sanitize_title( $a['key'] ) ] ) ? $raw[ sanitize_title( $a['key'] ) ] : '' );
					if ( isset( $a['map'][ $val ] ) ) {
						$val = $a['map'][ $val ];
					}
					$row[ $label ] = $val;
				}
				$vars[ $vid ] = $row;
			}
		}

		$source_text = self::strip_html( $product->get_description() ? $product->get_description() : $product->get_short_description() );
		$src         = array( 'title' => $product->get_name(), 'description' => $source_text, 'attributes' => array() );
		foreach ( $attrs as $label => $a ) {
			$src['attributes'][] = array( 'name' => $label, 'options' => $a['options'] );
		}

		// Ask, validate, and give Claude one chance to fix its own mistakes.
		$c    = null;
		$errs = array( 'not generated' );
		for ( $try = 1; $try <= 2; $try++ ) {
			$c    = self::ask( $src, 1 === $try ? '' : "\n\nPoprzednia odpowiedź miała błędy, popraw je: " . implode( '; ', $errs ) );
			$errs = self::validate( $c, $attrs, $source_text . ' ' . $product->get_name() );
			if ( ! $errs ) {
				break;
			}
		}
		if ( $errs ) {
			throw new Exception( 'Copy failed the checks: ' . implode( '; ', $errs ) );
		}

		// Parent attributes: per-product ones, so shared (global) attributes stay untouched.
		$kept   = array();
		foreach ( $attrs as $label => $a ) {
			$plan = $c['attrs'][ $label ] ?? null;
			if ( self::is_origin_label( $label ) || ( $plan && ! empty( $plan['drop'] ) ) ) {
				continue;
			}
			$kept[ $label ] = $a;
		}
		$variable = $product->is_type( 'variable' );
		$wc_attrs = array();
		$plan_for = array();
		$pos      = 0;
		foreach ( $kept as $label => $a ) {
			$plan   = $c['attrs'][ $label ];
			$as_var = $variable && ( ! empty( $plan['variation'] ) || 1 === count( $kept ) );
			$opts   = array();
			foreach ( $a['options'] as $o ) {
				$opts[] = $plan['values'][ $o ];
			}
			$attr = new WC_Product_Attribute();
			$attr->set_id( 0 );
			$attr->set_name( $plan['name'] );
			$attr->set_options( array_values( array_unique( $opts ) ) );
			$attr->set_position( $pos++ );
			$attr->set_visible( true );
			$attr->set_variation( $as_var );
			$wc_attrs[]       = $attr;
			$plan_for[ $label ] = array( $plan, $as_var );
		}

		// Variations: only the ones shipping from Poland stay on sale; prices up to .99.
		$has_origin = false;
		foreach ( array_keys( $attrs ) as $label ) {
			$has_origin = $has_origin || self::is_origin_label( $label );
		}
		$var_plan = array();
		$live     = 0;
		foreach ( $vars as $vid => $row ) {
			$origin = '';
			foreach ( $row as $label => $val ) {
				if ( self::is_origin_label( $label ) ) {
					$origin = $val;
				}
			}
			$new = array();
			foreach ( $plan_for as $label => $pf ) {
				if ( $pf[1] ) {
					$new[ sanitize_title( $pf[0]['name'] ) ] = $pf[0]['values'][ $row[ $label ] ];
				}
			}
			$private = $has_origin && 'poland' !== strtolower( $origin );
			$live   += $private ? 0 : 1;
			$v       = wc_get_product( $vid );
			$reg     = $v->get_regular_price();
			$sale    = $v->get_sale_price();
			$reg_n   = '' !== $reg ? self::up99( $reg ) : '';
			$sale_n  = '' !== $sale ? self::up99( $sale ) : '';
			if ( '' !== $reg_n && '' !== $sale_n && (float) $sale_n >= (float) $reg_n ) {
				throw new Exception( "Variation $vid: sale price $sale_n is not below regular $reg_n." );
			}
			$var_plan[ $vid ] = array( 'attrs' => $new, 'private' => $private, 'reg' => $reg_n, 'sale' => $sale_n );
		}
		if ( $vars && ! $live ) {
			throw new Exception( 'No variation ships from Poland. Needs a manual decision.' );
		}
		$combos = array();
		foreach ( $var_plan as $vp ) {
			if ( ! $vp['private'] ) {
				ksort( $vp['attrs'] );
				$combos[] = wp_json_encode( $vp['attrs'] );
			}
		}
		if ( count( $combos ) !== count( array_unique( $combos ) ) ) {
			throw new Exception( "Duplicate options remain after removing 'Ships from'." );
		}

		list( $short, $long ) = self::html( $c );
		$allowed = array( 'p' => array(), 'ul' => array(), 'li' => array(), 'h3' => array(), 'table' => array(), 'tr' => array(), 'th' => array(), 'td' => array(), 'strong' => array() );

		// ---- everything checked: back up, then write.
		$snap = self::snapshot( $product );
		update_post_meta( $id, self::BACKUP, $snap );

		$sku = (string) $product->get_sku();
		if ( preg_match( '/^\d{13,}$/', trim( $sku ) ) ) {
			$sku = self::next_sku( $opt['sku_prefix'] );
		}
		$product->set_name( $c['title'] );
		$product->set_slug( sanitize_title( $c['title'] ) );
		$product->set_short_description( wp_kses( $short, $allowed ) );
		$product->set_description( wp_kses( $long, $allowed ) );
		if ( $sku !== $product->get_sku() ) {
			$product->set_sku( $sku );
		}
		$product->set_attributes( $wc_attrs );
		if ( ! $variable ) {
			$r = $product->get_regular_price();
			$s = $product->get_sale_price();
			if ( '' !== $r ) {
				$product->set_regular_price( self::up99( $r ) );
			}
			if ( '' !== $s ) {
				$product->set_sale_price( self::up99( $s ) );
			}
		}
		$cat = get_term_by( 'name', $opt['category'], 'product_cat' );
		if ( $cat ) {
			$cur = $product->get_category_ids();
			$unc = get_term_by( 'slug', 'uncategorized', 'product_cat' );
			if ( ! $cur || ( $unc && array( (int) $unc->term_id ) === array_map( 'intval', $cur ) ) ) {
				$product->set_category_ids( array( (int) $cat->term_id ) );
			}
		}
		$product->save();
		wp_set_object_terms( $id, array_values( array_unique( $c['tags'] ) ), 'product_tag' );

		foreach ( $var_plan as $vid => $vp ) {
			$v = wc_get_product( $vid );
			$v->set_attributes( $vp['attrs'] );
			if ( '' !== $vp['reg'] ) {
				$v->set_regular_price( $vp['reg'] );
			}
			if ( '' !== $vp['sale'] ) {
				$v->set_sale_price( $vp['sale'] );
			}
			$v->set_status( $vp['private'] ? 'private' : 'publish' );
			$v->save();
		}
		update_post_meta( $id, self::MARKER, gmdate( 'Y-m-d' ) );
		update_post_meta( $id, '_aliclean_notes', isset( $c['notes'] ) ? array_map( 'sanitize_text_field', (array) $c['notes'] ) : array() );
		self::refresh( $id );

		return array(
			'title' => $c['title'],
			'sku'   => $sku,
			'url'   => get_permalink( $id ),
			'notes' => isset( $c['notes'] ) ? (array) $c['notes'] : array(),
			'live'  => $live,
			'total' => count( $vars ),
		);
	}
}
