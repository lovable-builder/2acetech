<?php
/**
 * AI help for designing a pop-up: writes the texts and colours, and paints decorative artwork.
 *
 * It uses the Google Cloud connection that is already set up in the AI Photo Editor plugin (the same
 * service account), so nothing new has to be pasted. A daily limit protects the budget.
 *
 * What it will not do: show products that are not sold, make claims about quality or price, or put
 * text into pictures. Artwork is decoration; the real product photos stay the product photos.
 *
 * @package 2ace-popups
 */

defined( 'ABSPATH' ) || exit;

class ACEPU_AI {

	/** Is the Google connection there? */
	public static function available() {
		return class_exists( 'ACE_PE_Google' ) && (bool) ACE_PE_Google::credentials();
	}

	public static function settings_url() {
		return admin_url( 'edit.php?post_type=product&page=' . ( class_exists( 'ACE_PE_Admin' ) ? ACE_PE_Admin::PAGE : 'ace-photo-editor' ) );
	}

	/** @return int Requests left today. */
	public static function left() {
		$o   = ACEPU_Store::options();
		$day = get_option( 'acepu_ai_day', array( 'd' => '', 'n' => 0 ) );
		$n   = ( isset( $day['d'] ) && current_time( 'Y-m-d' ) === $day['d'] ) ? (int) $day['n'] : 0;
		return max( 0, (int) $o['ai_limit'] - $n );
	}

	private static function spend() {
		$day = get_option( 'acepu_ai_day', array( 'd' => '', 'n' => 0 ) );
		$d   = current_time( 'Y-m-d' );
		update_option( 'acepu_ai_day', array( 'd' => $d, 'n' => ( isset( $day['d'] ) && $d === $day['d'] ? (int) $day['n'] : 0 ) + 1 ), false );
	}

	/** Model request URL. */
	public static function url( $model, $loc, $method ) {
		$c    = ACE_PE_Google::credentials();
		$loc  = preg_replace( '/[^a-z0-9-]/', '', strtolower( (string) $loc ) );
		$loc  = '' === $loc ? 'global' : $loc;
		$host = 'global' === $loc ? 'aiplatform.googleapis.com' : $loc . '-aiplatform.googleapis.com';
		$mod  = preg_replace( '/[^a-zA-Z0-9._-]/', '', (string) $model );
		return sprintf( 'https://%s/v1/projects/%s/locations/%s/publishers/google/models/%s:%s', $host, rawurlencode( $c['project_id'] ), $loc, $mod, $method );
	}

	/** @return array|WP_Error Decoded JSON answer. */
	public static function call( $model, $loc, $body, $timeout = 120 ) {
		if ( ! self::available() ) {
			return new WP_Error( 'acepu_ai_off', __( 'Google AI is not connected. Connect it in AI photo editor settings first.', '2ace-popups' ) );
		}
		if ( ! self::left() ) {
			return new WP_Error( 'acepu_ai_limit', __( 'The daily AI limit is reached. You can raise it in Pop-ups > Settings.', '2ace-popups' ) );
		}
		$token = ACE_PE_Google::token();
		if ( is_wp_error( $token ) ) {
			return $token;
		}
		$r = wp_remote_post(
			self::url( $model, $loc, 'generateContent' ),
			array(
				'timeout' => $timeout,
				'headers' => array( 'Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode( $body ),
			)
		);
		if ( is_wp_error( $r ) ) {
			return new WP_Error( 'acepu_ai_http', $r->get_error_message() );
		}
		self::spend();
		$code = (int) wp_remote_retrieve_response_code( $r );
		$j    = json_decode( wp_remote_retrieve_body( $r ), true );
		if ( $code >= 400 || ! is_array( $j ) ) {
			$why = isset( $j['error']['message'] ) ? $j['error']['message'] : 'HTTP ' . $code;
			return new WP_Error( 'acepu_ai_api', sprintf( /* translators: %s: Google's message. */ __( 'Google returned an error: %s', '2ace-popups' ), $why ) );
		}
		return $j;
	}

	/* ---------------------------------------------------------------------
	 * Texts and colours
	 * ------------------------------------------------------------------ */

	/**
	 * Build the instruction for the copywriter model.
	 *
	 * @param array $in occasion, brief, discount, mode, tone.
	 * @return string
	 */
	public static function text_prompt( $in ) {
		$mode = array(
			'email' => 'The visitor leaves an e-mail address and receives a personal one-time coupon.',
			'code'  => 'The pop-up shows a ready coupon code.',
			'info'  => 'The pop-up is an announcement with one button, no coupon.',
		);
		return "You write copy for a pop-up of the Polish online shop 2acetech.pl (camping and outdoor gear, stage and LED lighting, ceiling fans; free courier delivery in Poland, shipping in 2-5 working days).\n"
			. "Write ALL visible texts in Polish. Natural, warm, concrete. Maximum one exclamation mark in total.\n"
			. 'Occasion: ' . $in['occasion'] . "\n"
			. 'What the owner wants: ' . $in['brief'] . "\n"
			. 'Discount to mention (use exactly this and invent no other number): ' . ( '' !== $in['discount'] ? $in['discount'] : 'none, do not mention a discount' ) . "\n"
			. 'Behaviour: ' . ( isset( $mode[ $in['mode'] ] ) ? $mode[ $in['mode'] ] : $mode['info'] ) . "\n"
			. 'Tone: ' . $in['tone'] . "\n"
			. "Hard rules (Polish consumer law): no unprovable superlatives (najlepszy, najtańszy, numer 1), no invented scarcity or deadlines, no promise of stock, no claims about product quality that were not given, no old price / 'was' price. Do not name a date unless it is given in the brief.\n"
			. "Field limits: eyebrow max 28 characters; headline max 60; text max 140; cta_label max 24 (verb); dismiss_label max 24 (polite); badge max 8 (e.g. -10%) or empty; footnote max 100 (conditions, e.g. one-time code, validity, not combined with other codes).\n"
			. "Palette: choose bg, fg, accent, accent_fg as #rrggbb that suit the occasion, readable (contrast of fg on bg and accent_fg on accent at least 4.5:1). The shop brand colour is red #f50505 on black or white; keep it unless the occasion clearly needs another accent.\n"
			. "image_prompt: an English prompt for DECORATIVE background artwork that fits the occasion and palette: atmosphere, light, shapes, abstract or scenery. No text, no letters, no logos, no people's faces, and do not depict any specific product.\n";
	}

	/**
	 * @param array $in occasion, brief, discount, mode, tone.
	 * @return array|WP_Error Fields: eyebrow, headline, text, cta_label, dismiss_label, badge, footnote, bg, fg, accent, accent_fg, image_prompt.
	 */
	public static function suggest( $in ) {
		$o   = ACEPU_Store::options();
		$str = array( 'type' => 'STRING' );
		$j   = self::call(
			$o['ai_text'],
			$o['ai_text_loc'],
			array(
				'contents'         => array( array( 'role' => 'user', 'parts' => array( array( 'text' => self::text_prompt( $in ) ) ) ) ),
				'generationConfig' => array(
					'temperature'      => 0.8,
					'responseMimeType' => 'application/json',
					'responseSchema'   => array(
						'type'       => 'OBJECT',
						'properties' => array(
							'eyebrow'       => $str,
							'headline'      => $str,
							'text'          => $str,
							'cta_label'     => $str,
							'dismiss_label' => $str,
							'badge'         => $str,
							'footnote'      => $str,
							'bg'            => $str,
							'fg'            => $str,
							'accent'        => $str,
							'accent_fg'     => $str,
							'image_prompt'  => $str,
						),
					),
				),
			),
			60
		);
		if ( is_wp_error( $j ) ) {
			return $j;
		}
		$raw = '';
		foreach ( isset( $j['candidates'][0]['content']['parts'] ) ? $j['candidates'][0]['content']['parts'] : array() as $part ) {
			if ( isset( $part['text'] ) ) {
				$raw .= $part['text'];
			}
		}
		return self::parse_text( $raw );
	}

	/** Turn the model's answer into clean fields (also tolerates ```json fences). */
	public static function parse_text( $raw ) {
		$raw = trim( preg_replace( '/^```(?:json)?\s*|\s*```$/i', '', trim( (string) $raw ) ) );
		$d   = json_decode( $raw, true );
		if ( ! is_array( $d ) || empty( $d['headline'] ) ) {
			return new WP_Error( 'acepu_ai_parse', __( 'The AI answer could not be read. Please try again.', '2ace-popups' ) );
		}
		$out = array();
		foreach ( array( 'eyebrow', 'headline', 'text', 'cta_label', 'dismiss_label', 'badge', 'footnote', 'image_prompt' ) as $k ) {
			$out[ $k ] = isset( $d[ $k ] ) ? sanitize_text_field( (string) $d[ $k ] ) : '';
		}
		foreach ( array( 'bg', 'fg', 'accent', 'accent_fg' ) as $k ) {
			$c = isset( $d[ $k ] ) ? sanitize_hex_color( (string) $d[ $k ] ) : '';
			if ( $c ) {
				$out[ $k ] = $c;
			}
		}
		return $out;
	}

	/* ---------------------------------------------------------------------
	 * Artwork
	 * ------------------------------------------------------------------ */

	/**
	 * Paint artwork and put it in the Media Library.
	 *
	 * @param string $prompt Description.
	 * @param string $aspect portrait|landscape|square.
	 * @return array|WP_Error id, url.
	 */
	public static function artwork( $prompt, $aspect ) {
		$o      = ACEPU_Store::options();
		$ratios = array( 'portrait' => '4:5', 'landscape' => '16:9', 'square' => '1:1' );
		$ratio  = isset( $ratios[ $aspect ] ) ? $ratios[ $aspect ] : '4:5';
		$full   = trim( $prompt ) . ' Decorative background artwork for a website promotion pop-up. Strictly no text, no letters, no numbers, no logos, no watermarks, no human faces. Keep calm negative space. High quality, cinematic lighting.';
		$body   = array(
			'contents'         => array( array( 'role' => 'user', 'parts' => array( array( 'text' => $full ) ) ) ),
			'generationConfig' => array( 'responseModalities' => array( 'IMAGE' ), 'imageConfig' => array( 'aspectRatio' => $ratio ) ),
		);
		$j = self::call( $o['ai_image'], $o['ai_image_loc'], $body, 150 );
		if ( is_wp_error( $j ) && 'acepu_ai_api' === $j->get_error_code() && false !== stripos( $j->get_error_message(), 'imageconfig' ) ) {
			unset( $body['generationConfig']['imageConfig'] ); // An older model without aspect ratio.
			$j = self::call( $o['ai_image'], $o['ai_image_loc'], $body, 150 );
		}
		if ( is_wp_error( $j ) ) {
			return $j;
		}
		$bytes = '';
		$mime  = 'image/png';
		foreach ( isset( $j['candidates'][0]['content']['parts'] ) ? $j['candidates'][0]['content']['parts'] : array() as $part ) {
			$in = isset( $part['inlineData'] ) ? $part['inlineData'] : ( isset( $part['inline_data'] ) ? $part['inline_data'] : null );
			if ( $in && ! empty( $in['data'] ) ) {
				$bytes = base64_decode( $in['data'], true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
				$mime  = isset( $in['mimeType'] ) ? $in['mimeType'] : ( isset( $in['mime_type'] ) ? $in['mime_type'] : 'image/png' );
				break;
			}
		}
		if ( ! $bytes ) {
			return new WP_Error( 'acepu_ai_noimg', __( 'Google did not return a picture. Try a different description.', '2ace-popups' ) );
		}
		return self::save_image( $bytes, $mime );
	}

	/** Store picture bytes as a Media Library item marked as AI-generated. */
	public static function save_image( $bytes, $mime ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$ext = 'image/jpeg' === $mime ? 'jpg' : ( 'image/webp' === $mime ? 'webp' : 'png' );
		$up  = wp_upload_bits( 'popup-artwork-' . gmdate( 'Ymd-His' ) . '-' . wp_generate_password( 4, false ) . '.' . $ext, null, $bytes );
		if ( ! empty( $up['error'] ) ) {
			return new WP_Error( 'acepu_ai_save', $up['error'] );
		}
		$id = wp_insert_attachment(
			array(
				'post_mime_type' => $mime,
				'post_title'     => __( 'Pop-up artwork (AI-generated)', '2ace-popups' ),
				'post_status'    => 'inherit',
			),
			$up['file']
		);
		if ( is_wp_error( $id ) || ! $id ) {
			return new WP_Error( 'acepu_ai_save', __( 'Could not save the picture.', '2ace-popups' ) );
		}
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $up['file'] ) );
		update_post_meta( $id, '_acepu_ai', 1 );
		return array( 'id' => (int) $id, 'url' => wp_get_attachment_image_url( $id, 'medium' ) );
	}
}
