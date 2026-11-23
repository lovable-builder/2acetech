<?php
/**
 * Google Cloud (Vertex AI) connection: service-account sign-in and the image-edit request.
 *
 * The service account JSON is pasted by the shop owner on the settings page and kept in one option that
 * is never printed back. Sign-in uses the standard JWT bearer flow (RS256) and the access token is cached.
 *
 * @package 2ace-photo-editor
 */

defined( 'ABSPATH' ) || exit;

class ACE_PE_Google {

	const OPTION = 'ace_pe_settings';

	/** Settings with defaults. */
	public static function settings() {
		return wp_parse_args(
			get_option( self::OPTION, array() ),
			array(
				'sa'          => '',
				'model'       => 'gemini-3-pro-image',
				'location'    => 'global',
				'daily_limit' => 40,
			)
		);
	}

	/** Decoded service account, or null when not (correctly) set. */
	public static function credentials() {
		$o = self::settings();
		$j = json_decode( (string) $o['sa'], true );
		if ( ! is_array( $j ) || empty( $j['client_email'] ) || empty( $j['private_key'] ) || empty( $j['project_id'] ) ) {
			return null;
		}
		return $j;
	}

	private static function b64u( $s ) {
		return rtrim( strtr( base64_encode( $s ), '+/', '-_' ), '=' );
	}

	/**
	 * The signed JWT that is exchanged for an access token.
	 *
	 * @param array    $c   Service account.
	 * @param int|null $now Timestamp (tests).
	 * @return string|WP_Error
	 */
	public static function jwt( $c, $now = null ) {
		$now    = $now ? $now : time();
		$head   = self::b64u( wp_json_encode( array( 'alg' => 'RS256', 'typ' => 'JWT' ) ) );
		$claims = self::b64u(
			wp_json_encode(
				array(
					'iss'   => $c['client_email'],
					'scope' => 'https://www.googleapis.com/auth/cloud-platform',
					'aud'   => 'https://oauth2.googleapis.com/token',
					'iat'   => $now,
					'exp'   => $now + 3600,
				)
			)
		);
		$sig = '';
		if ( ! function_exists( 'openssl_sign' ) || ! openssl_sign( $head . '.' . $claims, $sig, $c['private_key'], OPENSSL_ALGO_SHA256 ) ) {
			return new WP_Error( 'ace_pe_sign', __( 'Could not sign in to Google: the private key in the service account is not valid.', '2ace-photo-editor' ) );
		}
		return $head . '.' . $claims . '.' . self::b64u( $sig );
	}

	/** A cached access token. */
	public static function token() {
		$cached = get_transient( 'ace_pe_token' );
		if ( $cached ) {
			return $cached;
		}
		$c = self::credentials();
		if ( ! $c ) {
			return new WP_Error( 'ace_pe_nocreds', __( 'Google Cloud is not connected yet: paste the service account JSON on the settings page.', '2ace-photo-editor' ) );
		}
		$jwt = self::jwt( $c );
		if ( is_wp_error( $jwt ) ) {
			return $jwt;
		}
		$r = wp_remote_post(
			'https://oauth2.googleapis.com/token',
			array(
				'timeout' => 30,
				'body'    => array(
					'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
					'assertion'  => $jwt,
				),
			)
		);
		if ( is_wp_error( $r ) ) {
			return new WP_Error( 'ace_pe_http', $r->get_error_message() );
		}
		$j = json_decode( wp_remote_retrieve_body( $r ), true );
		if ( empty( $j['access_token'] ) ) {
			$why = isset( $j['error_description'] ) ? $j['error_description'] : ( isset( $j['error'] ) ? $j['error'] : 'HTTP ' . wp_remote_retrieve_response_code( $r ) );
			return new WP_Error( 'ace_pe_auth', sprintf( /* translators: %s: Google's reason. */ __( 'Google refused the sign-in: %s', '2ace-photo-editor' ), $why ) );
		}
		set_transient( 'ace_pe_token', $j['access_token'], max( 300, (int) ( isset( $j['expires_in'] ) ? $j['expires_in'] : 3600 ) - 300 ) );
		return $j['access_token'];
	}

	/** URL of a Vertex AI publisher-model method. */
	public static function url( $method ) {
		$o    = self::settings();
		$c    = self::credentials();
		$loc  = preg_replace( '/[^a-z0-9-]/', '', strtolower( (string) $o['location'] ) );
		$host = ( 'global' === $loc || '' === $loc ) ? 'aiplatform.googleapis.com' : $loc . '-aiplatform.googleapis.com';
		$loc  = '' === $loc ? 'global' : $loc;
		$mod  = preg_replace( '/[^a-zA-Z0-9._-]/', '', (string) $o['model'] );
		return sprintf( 'https://%s/v1/projects/%s/locations/%s/publishers/google/models/%s:%s', $host, rawurlencode( $c['project_id'] ), $loc, $mod, $method );
	}

	/**
	 * POST a JSON body to a model method.
	 *
	 * @return array|WP_Error Decoded JSON.
	 */
	public static function call( $method, $body, $timeout = 120 ) {
		if ( ! self::credentials() ) {
			return new WP_Error( 'ace_pe_nocreds', __( 'Google Cloud is not connected yet: paste the service account JSON on the settings page.', '2ace-photo-editor' ) );
		}
		$token = self::token();
		if ( is_wp_error( $token ) ) {
			return $token;
		}
		$r = wp_remote_post(
			self::url( $method ),
			array(
				'timeout' => $timeout,
				'headers' => array( 'Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode( $body ),
			)
		);
		if ( is_wp_error( $r ) ) {
			return new WP_Error( 'ace_pe_http', $r->get_error_message() );
		}
		$code = (int) wp_remote_retrieve_response_code( $r );
		$j    = json_decode( wp_remote_retrieve_body( $r ), true );
		if ( $code >= 400 || ! is_array( $j ) ) {
			if ( 401 === $code ) {
				delete_transient( 'ace_pe_token' );
			}
			$why = isset( $j['error']['message'] ) ? $j['error']['message'] : 'HTTP ' . $code;
			return new WP_Error( 'ace_pe_api', sprintf( /* translators: %s: Google's message. */ __( 'Google returned an error: %s', '2ace-photo-editor' ), $why ) );
		}
		return $j;
	}

	/**
	 * Ask the model to edit one image.
	 *
	 * @param string $bytes  Source image.
	 * @param string $mime   Its mime type.
	 * @param string $prompt Instruction.
	 * @return array|WP_Error array( bytes, mime ).
	 */
	public static function edit( $bytes, $mime, $prompt ) {
		$j = self::call(
			'generateContent',
			array(
				'contents'         => array(
					array(
						'role'  => 'user',
						'parts' => array(
							array( 'inlineData' => array( 'mimeType' => $mime, 'data' => base64_encode( $bytes ) ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
							array( 'text' => $prompt ),
						),
					),
				),
				'generationConfig' => array( 'responseModalities' => array( 'IMAGE' ) ),
			)
		);
		if ( is_wp_error( $j ) ) {
			return $j;
		}
		$cand = isset( $j['candidates'][0] ) ? $j['candidates'][0] : array();
		foreach ( isset( $cand['content']['parts'] ) ? $cand['content']['parts'] : array() as $part ) {
			$in = isset( $part['inlineData'] ) ? $part['inlineData'] : ( isset( $part['inline_data'] ) ? $part['inline_data'] : null );
			if ( $in && ! empty( $in['data'] ) ) {
				$out = base64_decode( $in['data'], true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
				if ( false !== $out && '' !== $out ) {
					return array( 'bytes' => $out, 'mime' => isset( $in['mimeType'] ) ? $in['mimeType'] : ( isset( $in['mime_type'] ) ? $in['mime_type'] : 'image/png' ) );
				}
			}
		}
		$why = '';
		if ( ! empty( $j['promptFeedback']['blockReason'] ) ) {
			$why = 'blocked: ' . $j['promptFeedback']['blockReason'];
		} elseif ( ! empty( $cand['finishReason'] ) ) {
			$why = 'finish reason: ' . $cand['finishReason'];
		}
		return new WP_Error( 'ace_pe_noimage', sprintf( /* translators: %s: reason. */ __( 'Google did not return an edited image (%s). Try again, or pick a different photo.', '2ace-photo-editor' ), $why ? $why : 'empty answer' ) );
	}

	/** Connection check: sign in and make a tiny, free request to the model. */
	public static function test() {
		$c = self::credentials();
		if ( ! $c ) {
			return new WP_Error( 'ace_pe_nocreds', __( 'Paste the service account JSON first, then save.', '2ace-photo-editor' ) );
		}
		delete_transient( 'ace_pe_token' );
		$j = self::call( 'countTokens', array( 'contents' => array( array( 'role' => 'user', 'parts' => array( array( 'text' => 'ping' ) ) ) ) ), 30 );
		if ( is_wp_error( $j ) ) {
			return $j;
		}
		return array( 'account' => $c['client_email'], 'project' => $c['project_id'] );
	}
}
