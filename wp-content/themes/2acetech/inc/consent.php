<?php
/**
 * Cookie consent: a banner on the first visit, a settings panel, and a way to
 * change the choice later ("Cookie settings" in the footer).
 *
 * The choice is stored in one first-party cookie, ace_consent, as
 * "version.analytics.marketing" (for example "1.0.0"). That cookie is strictly
 * necessary (it only remembers the choice) and lasts 180 days.
 *
 * Analytics and marketing tools (Google tags from plugins, others added later)
 * must wait for this choice:
 *
 * - Google tags: Consent Mode. ace_consent_google_default() prints "denied" as
 *   the very first script in <head>; consent.js sends "update" with the choice.
 *
 * - In HTML:   <script type="text/plain" data-ace-consent="analytics" src="...">
 *              runs only after the visitor accepts analytics.
 * - In JS:     window.aceConsent.has('analytics'); event "ace:consent".
 * - In PHP:    ace_consent_allowed( 'analytics' ).
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

const ACE_CONSENT_COOKIE  = 'ace_consent';
const ACE_CONSENT_VERSION = 1;

/**
 * The cookie categories shown in the settings panel.
 *
 * @return array[] key => label, text, locked.
 */
function ace_consent_categories() {
	return array(
		'necessary' => array(
			'label'  => __( 'Necessary', '2acetech' ),
			'text'   => __( 'Keep the cart, checkout and your account working, and remember this choice. They cannot be switched off.', '2acetech' ),
			'locked' => true,
		),
		'analytics' => array(
			'label'  => __( 'Analytics', '2acetech' ),
			'text'   => __( 'Help us understand how the shop is used so we can improve it. Off unless you agree.', '2acetech' ),
			'locked' => false,
		),
		'marketing' => array(
			'label'  => __( 'Marketing', '2acetech' ),
			'text'   => __( 'Used to show offers that match your interests, also on other websites. Off unless you agree.', '2acetech' ),
			'locked' => false,
		),
	);
}

/**
 * Has the visitor allowed a category? Reads the consent cookie. Necessary is
 * always true; anything else is false until the visitor agrees.
 *
 * @param string $category necessary|analytics|marketing.
 * @return bool
 */
function ace_consent_allowed( $category ) {
	if ( 'necessary' === $category ) {
		return true;
	}
	if ( empty( $_COOKIE[ ACE_CONSENT_COOKIE ] ) ) {
		return false;
	}
	$parts = explode( '.', sanitize_text_field( wp_unslash( $_COOKIE[ ACE_CONSENT_COOKIE ] ) ) );
	if ( 3 !== count( $parts ) || (int) $parts[0] !== ACE_CONSENT_VERSION ) {
		return false;
	}
	$index = array(
		'analytics' => 1,
		'marketing' => 2,
	);
	return isset( $index[ $category ] ) && '1' === $parts[ $index[ $category ] ];
}

/**
 * Whether to print the consent UI on this request.
 *
 * @return bool
 */
function ace_consent_enabled() {
	return ! is_admin() && ! is_customize_preview() && ! is_feed() && ! wp_doing_ajax() && ! is_embed();
}

/**
 * Google Consent Mode, step 1: everything denied until the visitor chooses.
 *
 * Printed first in <head> (priority 0), before any plugin prints its gtag
 * code, so Google tags never start with storage allowed. consent.js sends the
 * visitor's choice as an "update" (also on every page load for returning
 * visitors). The default is always "denied" and never read from the cookie on
 * the server, so a page cache cannot serve one visitor's choice to another.
 */
function ace_consent_google_default() {
	if ( ! ace_consent_enabled() ) {
		return;
	}
	echo "<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('consent','default',{ad_storage:'denied',ad_user_data:'denied',ad_personalization:'denied',analytics_storage:'denied',wait_for_update:500});</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput
}
add_action( 'wp_head', 'ace_consent_google_default', 0 );

/**
 * The cookie policy page if it is published, else the cookie section of the
 * privacy policy.
 *
 * @return string URL or ''.
 */
function ace_cookie_policy_url() {
	if ( function_exists( 'ace_legal_page_id' ) ) {
		$cookies = ace_legal_page_id( 'cookies' );
		if ( $cookies && 'publish' === get_post_status( $cookies ) ) {
			return get_permalink( $cookies );
		}
		$privacy = ace_legal_page_id( 'privacy' );
		if ( $privacy && 'publish' === get_post_status( $privacy ) ) {
			return get_permalink( $privacy ) . '#sec-6';
		}
	}
	return '';
}

/**
 * Styles and script.
 */
function ace_consent_assets() {
	if ( ! ace_consent_enabled() ) {
		return;
	}
	wp_enqueue_style( 'ace-consent', ACE_URI . '/assets/css/consent.css', array( 'ace-main' ), ace_ver( 'assets/css/consent.css' ) );
	wp_enqueue_script( 'ace-consent', ACE_URI . '/assets/js/consent.js', array(), ace_ver( 'assets/js/consent.js' ), array( 'strategy' => 'defer', 'in_footer' => true ) );
	wp_localize_script(
		'ace-consent',
		'aceConsentConfig',
		array(
			'cookie'  => ACE_CONSENT_COOKIE,
			'version' => ACE_CONSENT_VERSION,
			'days'    => 180,
		)
	);
}
add_action( 'wp_enqueue_scripts', 'ace_consent_assets', 20 );

/**
 * The logo for the banner: the Site Identity logo, else the bundled one. Not a
 * link, so the visitor stays on the page while deciding.
 *
 * @return string
 */
function ace_consent_logo() {
	$id = (int) get_theme_mod( 'custom_logo' );
	if ( $id ) {
		$img = wp_get_attachment_image( $id, 'medium', false, array( 'class' => 'cookie__logo', 'alt' => get_bloginfo( 'name' ) ) );
		if ( $img ) {
			return $img;
		}
	}
	$file = ACE_DIR . '/assets/img/logo.png';
	if ( ! file_exists( $file ) ) {
		return '';
	}
	$size = getimagesize( $file );
	return sprintf(
		'<img class="cookie__logo" src="%1$s" width="%2$d" height="%3$d" alt="%4$s" decoding="async">',
		esc_url( ACE_URI . '/assets/img/logo.png' ),
		$size ? (int) $size[0] : 0,
		$size ? (int) $size[1] : 0,
		esc_attr( get_bloginfo( 'name' ) )
	);
}

/**
 * The banner and the settings panel. Both start hidden; consent.js shows the
 * banner when there is no valid choice yet.
 */
function ace_consent_markup() {
	if ( ! ace_consent_enabled() ) {
		return;
	}

	$policy_url = ace_cookie_policy_url();
	?>
	<div class="cookie" id="cookie-banner" role="region" aria-labelledby="cookie-title" hidden>
		<div class="cookie__inner">
			<?php echo ace_consent_logo(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<h2 class="cookie__title" id="cookie-title">
				<?php
				/* translators: %s: shop name. */
				echo esc_html( sprintf( __( 'Welcome to %s', '2acetech' ), get_bloginfo( 'name' ) ) );
				?>
			</h2>
			<p><?php esc_html_e( 'We use cookies to make the shop work: the cart, checkout and your account. We may also use analytical and marketing cookies, but only if you agree. You can change your choice at any time with "Cookie settings" at the bottom of every page.', '2acetech' ); ?></p>
			<p>
				<?php esc_html_e( 'Your personal data is controlled by 2ACE sp. z o.o. You will find more about cookies and your rights in our', '2acetech' ); ?>
				<?php if ( $policy_url ) : ?>
					<a href="<?php echo esc_url( $policy_url ); ?>"><?php echo esc_html_x( 'Cookie policy', 'link inside the cookie banner sentence', '2acetech' ); ?></a>.
				<?php else : ?>
					<?php echo esc_html_x( 'Cookie policy', 'link inside the cookie banner sentence', '2acetech' ); ?>.
				<?php endif; ?>
			</p>
			<div class="cookie__actions">
				<button type="button" class="cookie__btn cookie__btn--reject" data-cookie="reject"><?php esc_html_e( 'Reject all', '2acetech' ); ?></button>
				<button type="button" class="cookie__btn cookie__btn--manage" data-cookie="manage"><?php esc_html_e( 'Manage cookies', '2acetech' ); ?></button>
				<button type="button" class="cookie__btn cookie__btn--accept" data-cookie="accept"><?php esc_html_e( 'Accept', '2acetech' ); ?></button>
			</div>
		</div>
	</div>

	<div class="cookie-modal" id="cookie-modal" hidden>
		<div class="cookie-modal__backdrop" data-cookie="close"></div>
		<div class="cookie-modal__panel" role="dialog" aria-modal="true" aria-labelledby="cookie-modal-title" tabindex="-1">
			<h2 id="cookie-modal-title"><?php esc_html_e( 'Cookie settings', '2acetech' ); ?></h2>
			<p><?php esc_html_e( 'Choose which cookies you allow. You can change this at any time.', '2acetech' ); ?></p>
			<ul class="cookie-modal__list">
				<?php foreach ( ace_consent_categories() as $key => $cat ) : ?>
					<li>
						<div class="cookie-modal__text">
							<label for="cookie-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $cat['label'] ); ?></label>
							<p><?php echo esc_html( $cat['text'] ); ?></p>
						</div>
						<input class="cookie-switch" type="checkbox" role="switch" id="cookie-<?php echo esc_attr( $key ); ?>" data-category="<?php echo esc_attr( $key ); ?>" <?php echo $cat['locked'] ? 'checked disabled' : ''; ?>>
					</li>
				<?php endforeach; ?>
			</ul>
			<div class="cookie__actions">
				<button type="button" class="cookie__btn cookie__btn--reject" data-cookie="reject"><?php esc_html_e( 'Reject all', '2acetech' ); ?></button>
				<button type="button" class="cookie__btn cookie__btn--manage" data-cookie="save"><?php esc_html_e( 'Save my choices', '2acetech' ); ?></button>
				<button type="button" class="cookie__btn cookie__btn--accept" data-cookie="accept"><?php esc_html_e( 'Accept all', '2acetech' ); ?></button>
			</div>
			<?php if ( $policy_url ) : ?>
				<p class="cookie-modal__more"><a href="<?php echo esc_url( $policy_url ); ?>"><?php esc_html_e( 'Cookie policy', '2acetech' ); ?></a></p>
			<?php endif; ?>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'ace_consent_markup', 5 );
