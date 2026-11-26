<?php
/**
 * Every setting of a pop-up: type, default, and how it appears in the editor.
 * One list drives the sanitising, the defaults and the editor form.
 *
 * Keys of a field: type (text|textarea|url|int|bool|color|select|multi|media|ids|lines|datetime),
 * def, tab, label, help, opts (select/multi), min/max, show (editor shows it only when "key:value").
 *
 * @package 2ace-popups
 */

defined( 'ABSPATH' ) || exit;

/** Page types a pop-up can target. */
function acepu_page_types() {
	return array(
		'home'        => __( 'Home page', '2ace-popups' ),
		'shop'        => __( 'Shop page', '2ace-popups' ),
		'product_cat' => __( 'Category / brand pages', '2ace-popups' ),
		'product'     => __( 'Product pages', '2ace-popups' ),
		'post'        => __( 'Blog posts', '2ace-popups' ),
		'page'        => __( 'Other pages (About, Contact ...)', '2ace-popups' ),
		'search'      => __( 'Search results', '2ace-popups' ),
		'cart'        => __( 'Cart', '2ace-popups' ),
		'checkout'    => __( 'Checkout', '2ace-popups' ),
		'account'     => __( 'My account', '2ace-popups' ),
	);
}

/** The gifts on a new wheel. kind: percent | fixed | product. */
function acepu_default_prizes() {
	return array(
		array( 'label' => '-5%', 'kind' => 'percent', 'amount' => 5, 'product' => 0, 'weight' => 30 ),
		array( 'label' => '-10%', 'kind' => 'percent', 'amount' => 10, 'product' => 0, 'weight' => 28 ),
		array( 'label' => '-15%', 'kind' => 'percent', 'amount' => 15, 'product' => 0, 'weight' => 18 ),
		array( 'label' => '-20 zł', 'kind' => 'fixed', 'amount' => 20, 'product' => 0, 'weight' => 14 ),
		array( 'label' => '-25%', 'kind' => 'percent', 'amount' => 25, 'product' => 0, 'weight' => 7 ),
		array( 'label' => '-30 zł', 'kind' => 'fixed', 'amount' => 30, 'product' => 0, 'weight' => 3 ),
	);
}

/** The editor tabs. */
function acepu_tabs() {
	return array(
		'content' => __( 'Content', '2ace-popups' ),
		'design'  => __( 'Design', '2ace-popups' ),
		'coupon'  => __( 'Coupon & form', '2ace-popups' ),
		'wheel'   => __( 'Fortune wheel', '2ace-popups' ),
		'when'    => __( 'When to show', '2ace-popups' ),
		'who'     => __( 'Who sees it', '2ace-popups' ),
		'ai'      => __( 'AI designer', '2ace-popups' ),
	);
}

function acepu_schema() {
	static $s = null;
	if ( null !== $s ) {
		return $s;
	}
	$days = array( '1' => __( 'Mon', '2ace-popups' ), '2' => __( 'Tue', '2ace-popups' ), '3' => __( 'Wed', '2ace-popups' ), '4' => __( 'Thu', '2ace-popups' ), '5' => __( 'Fri', '2ace-popups' ), '6' => __( 'Sat', '2ace-popups' ), '7' => __( 'Sun', '2ace-popups' ) );
	$s    = array(
		// Content.
		'layout'        => array( 'type' => 'select', 'def' => 'split', 'tab' => 'content', 'label' => __( 'Layout', '2ace-popups' ), 'opts' => array( 'modal' => __( 'Centered box', '2ace-popups' ), 'split' => __( 'Picture + text', '2ace-popups' ), 'full' => __( 'Full screen', '2ace-popups' ), 'slide' => __( 'Corner slide-in', '2ace-popups' ), 'bar' => __( 'Bottom bar', '2ace-popups' ) ) ),
		'image_id'      => array( 'type' => 'media', 'def' => 0, 'tab' => 'content', 'label' => __( 'Picture / artwork', '2ace-popups' ), 'help' => __( 'Pick from the Media Library, or create artwork in the AI designer tab.', '2ace-popups' ) ),
		'image_pos'     => array( 'type' => 'select', 'def' => 'left', 'tab' => 'content', 'label' => __( 'Picture position', '2ace-popups' ), 'opts' => array( 'left' => __( 'Left', '2ace-popups' ), 'right' => __( 'Right', '2ace-popups' ), 'top' => __( 'Top', '2ace-popups' ), 'background' => __( 'Behind the text', '2ace-popups' ) ) ),
		'badge'         => array( 'type' => 'text', 'def' => '', 'tab' => 'content', 'label' => __( 'Badge', '2ace-popups' ), 'help' => __( 'Short, on the picture: -10%, 2 for 1 ... Leave empty for none.', '2ace-popups' ) ),
		'eyebrow'       => array( 'type' => 'text', 'def' => '', 'tab' => 'content', 'label' => __( 'Small line above the headline', '2ace-popups' ) ),
		'headline'      => array( 'type' => 'text', 'def' => '', 'tab' => 'content', 'label' => __( 'Headline', '2ace-popups' ) ),
		'text'          => array( 'type' => 'textarea', 'def' => '', 'tab' => 'content', 'label' => __( 'Text', '2ace-popups' ), 'help' => __( 'You can use {discount}, {coupon_code}, {end_date}, {site}.', '2ace-popups' ) ),
		'cta_label'     => array( 'type' => 'text', 'def' => '', 'tab' => 'content', 'label' => __( 'Button text', '2ace-popups' ) ),
		'cta_url'       => array( 'type' => 'url', 'def' => '', 'tab' => 'content', 'label' => __( 'Button link', '2ace-popups' ), 'help' => __( 'Where the button leads (empty = the shop). With a coupon, the code is applied to the cart on the way.', '2ace-popups' ) ),
		'dismiss_label' => array( 'type' => 'text', 'def' => '', 'tab' => 'content', 'label' => __( '"No thanks" link', '2ace-popups' ), 'help' => __( 'Always give a clear way out. Leave empty to show only the close button.', '2ace-popups' ) ),
		'countdown'     => array( 'type' => 'bool', 'def' => 0, 'tab' => 'content', 'label' => __( 'Countdown to the end of the offer', '2ace-popups' ), 'help' => __( 'Counts down to the "Hide after" date in the When to show tab. It is the real end date, never an endless timer (misleading timers are illegal).', '2ace-popups' ) ),
		'footnote'      => array( 'type' => 'text', 'def' => '', 'tab' => 'content', 'label' => __( 'Small print', '2ace-popups' ), 'help' => __( 'Conditions: validity, minimum order, "not combined with other codes".', '2ace-popups' ) ),

		// Design.
		'bg'            => array( 'type' => 'color', 'def' => '#ffffff', 'tab' => 'design', 'label' => __( 'Background', '2ace-popups' ) ),
		'fg'            => array( 'type' => 'color', 'def' => '#0b0b0d', 'tab' => 'design', 'label' => __( 'Text colour', '2ace-popups' ) ),
		'accent'        => array( 'type' => 'color', 'def' => '#f50505', 'tab' => 'design', 'label' => __( 'Accent (button, badge)', '2ace-popups' ) ),
		'accent_fg'     => array( 'type' => 'color', 'def' => '#ffffff', 'tab' => 'design', 'label' => __( 'Text on accent', '2ace-popups' ) ),
		'overlay'       => array( 'type' => 'color', 'def' => '#0b0b0d', 'tab' => 'design', 'label' => __( 'Dimming colour', '2ace-popups' ) ),
		'overlay_op'    => array( 'type' => 'int', 'def' => 60, 'min' => 0, 'max' => 90, 'tab' => 'design', 'label' => __( 'Dimming strength (%)', '2ace-popups' ) ),
		'blur'          => array( 'type' => 'bool', 'def' => 1, 'tab' => 'design', 'label' => __( 'Blur the page behind', '2ace-popups' ) ),
		'radius'        => array( 'type' => 'int', 'def' => 20, 'min' => 0, 'max' => 40, 'tab' => 'design', 'label' => __( 'Corner radius (px)', '2ace-popups' ) ),
		'width'         => array( 'type' => 'int', 'def' => 760, 'min' => 320, 'max' => 1000, 'tab' => 'design', 'label' => __( 'Width (px)', '2ace-popups' ) ),
		'anim'          => array( 'type' => 'select', 'def' => 'rise', 'tab' => 'design', 'label' => __( 'Entrance', '2ace-popups' ), 'opts' => array( 'rise' => __( 'Rise and fade', '2ace-popups' ), 'zoom' => __( 'Zoom', '2ace-popups' ), 'fade' => __( 'Fade', '2ace-popups' ), 'none' => __( 'None', '2ace-popups' ) ) ),
		'close_text'    => array( 'type' => 'bool', 'def' => 0, 'tab' => 'design', 'label' => __( 'Close button with the word "Zamknij"', '2ace-popups' ) ),
		'click_close'   => array( 'type' => 'bool', 'def' => 1, 'tab' => 'design', 'label' => __( 'Close when the dimmed area is clicked', '2ace-popups' ) ),

		// Coupon and form.
		'mode'          => array( 'type' => 'select', 'def' => 'info', 'tab' => 'coupon', 'label' => __( 'What the pop-up does', '2ace-popups' ), 'opts' => array( 'info' => __( 'Message and button only', '2ace-popups' ), 'code' => __( 'Show an existing coupon code', '2ace-popups' ), 'email' => __( 'E-mail sign-up that creates a personal one-time coupon', '2ace-popups' ), 'wheel' => __( 'Fortune wheel: spin, then sign up to get the gift', '2ace-popups' ) ) ),
		'code'          => array( 'type' => 'text', 'def' => '', 'tab' => 'coupon', 'label' => __( 'Coupon code', '2ace-popups' ), 'help' => __( 'A coupon you created in Marketing > Coupons.', '2ace-popups' ), 'show' => 'mode:code' ),
		'gen_type'      => array( 'type' => 'select', 'def' => 'percent', 'tab' => 'coupon', 'label' => __( 'Discount type', '2ace-popups' ), 'opts' => array( 'percent' => __( 'Percentage', '2ace-popups' ), 'fixed_cart' => __( 'Fixed amount (zł)', '2ace-popups' ) ), 'show' => 'mode:email' ),
		'gen_amount'    => array( 'type' => 'int', 'def' => 10, 'min' => 1, 'max' => 1000, 'tab' => 'coupon', 'label' => __( 'Discount', '2ace-popups' ), 'show' => 'mode:email' ),
		'gen_days'      => array( 'type' => 'int', 'def' => 14, 'min' => 1, 'max' => 365, 'tab' => 'coupon', 'label' => __( 'Coupon valid for (days)', '2ace-popups' ), 'show' => 'mode:email|wheel' ),
		'gen_min'       => array( 'type' => 'int', 'def' => 0, 'min' => 0, 'max' => 100000, 'tab' => 'coupon', 'label' => __( 'Minimum order (zł, 0 = none)', '2ace-popups' ), 'show' => 'mode:email|wheel' ),
		'gen_nosale'    => array( 'type' => 'bool', 'def' => 0, 'tab' => 'coupon', 'label' => __( 'Not valid on products already on sale', '2ace-popups' ), 'show' => 'mode:email|wheel' ),
		'gen_prefix'    => array( 'type' => 'text', 'def' => 'ACE', 'tab' => 'coupon', 'label' => __( 'Code prefix', '2ace-popups' ), 'show' => 'mode:email|wheel' ),
		'email_ph'      => array( 'type' => 'text', 'def' => '', 'tab' => 'coupon', 'label' => __( 'E-mail field hint', '2ace-popups' ), 'show' => 'mode:email|wheel' ),
		'consent_text'  => array( 'type' => 'textarea', 'def' => '', 'tab' => 'coupon', 'label' => __( 'Consent text (required tick box)', '2ace-popups' ), 'help' => __( 'GDPR: the visitor must tick it to get the code. It is never pre-ticked. The privacy policy link is added after the text.', '2ace-popups' ), 'show' => 'mode:email|wheel' ),
		'news_on'       => array( 'type' => 'bool', 'def' => 0, 'tab' => 'coupon', 'label' => __( 'Offer an optional newsletter tick box', '2ace-popups' ), 'help' => __( 'Separate, optional and never pre-ticked. A newsletter needs its own consent.', '2ace-popups' ), 'show' => 'mode:email|wheel' ),
		'news_text'     => array( 'type' => 'textarea', 'def' => '', 'tab' => 'coupon', 'label' => __( 'Newsletter tick box text', '2ace-popups' ), 'show' => 'mode:email|wheel' ),
		'done_title'    => array( 'type' => 'text', 'def' => '', 'tab' => 'coupon', 'label' => __( 'Thank-you headline', '2ace-popups' ), 'show' => 'mode:email|wheel' ),
		'done_text'     => array( 'type' => 'textarea', 'def' => '', 'tab' => 'coupon', 'label' => __( 'Thank-you text', '2ace-popups' ), 'show' => 'mode:email|wheel' ),
		'mail_subject'  => array( 'type' => 'text', 'def' => '', 'tab' => 'coupon', 'label' => __( 'Subject of the e-mail with the code', '2ace-popups' ), 'show' => 'mode:email|wheel' ),
		'mail_text'     => array( 'type' => 'textarea', 'def' => '', 'tab' => 'coupon', 'label' => __( 'Text of the e-mail with the code', '2ace-popups' ), 'show' => 'mode:email|wheel' ),

		// Fortune wheel.
		'prizes'        => array( 'type' => 'prizes', 'def' => acepu_default_prizes(), 'tab' => 'wheel', 'label' => __( 'What is on the wheel', '2ace-popups' ), 'help' => __( 'Every slice is a gift: nobody leaves with nothing. The chance of a slice is its weight divided by the total of all weights. The coupon is created only after the winner signs up, and it is personal and one-time.', '2ace-popups' ) ),
		'wheel_btn'     => array( 'type' => 'text', 'def' => 'Zakręć kołem', 'tab' => 'wheel', 'label' => __( 'Spin button text', '2ace-popups' ) ),
		'win_title'     => array( 'type' => 'text', 'def' => 'Wygrywasz: {prize}!', 'tab' => 'wheel', 'label' => __( 'Headline after the spin', '2ace-popups' ), 'help' => __( 'Use {prize} for the label of the slice that won.', '2ace-popups' ) ),
		'win_text'      => array( 'type' => 'textarea', 'def' => 'Podaj adres e-mail, a prześlemy Ci prezent. Kod jest jednorazowy i przypisany do Twojego adresu.', 'tab' => 'wheel', 'label' => __( 'Text after the spin', '2ace-popups' ) ),
		'rules_url'     => array( 'type' => 'url', 'def' => '', 'tab' => 'wheel', 'label' => __( 'Link to the rules of the game', '2ace-popups' ), 'help' => __( 'Shown under the wheel as "Regulamin". Publish the rules first: who organises it, that taking part is free and needs no purchase, what the gifts are, how the winner gets them and how to complain.', '2ace-popups' ) ),

		// When.
		'triggers'      => array( 'type' => 'multi', 'def' => array( 'time' ), 'tab' => 'when', 'label' => __( 'Show the pop-up when', '2ace-popups' ), 'help' => __( 'It appears at the first of the chosen moments.', '2ace-popups' ), 'opts' => array( 'time' => __( 'some seconds after the page opens', '2ace-popups' ), 'scroll' => __( 'the visitor has scrolled down', '2ace-popups' ), 'exit' => __( 'the visitor is about to leave (exit intent)', '2ace-popups' ), 'idle' => __( 'the visitor is inactive', '2ace-popups' ), 'pageviews' => __( 'the visitor has viewed several pages', '2ace-popups' ), 'addtocart' => __( 'a product was added to the cart', '2ace-popups' ), 'click' => __( 'a button or link is clicked', '2ace-popups' ) ) ),
		'delay'         => array( 'type' => 'int', 'def' => 8, 'min' => 0, 'max' => 600, 'tab' => 'when', 'label' => __( 'Seconds after page load', '2ace-popups' ), 'show' => 'triggers:time' ),
		'scroll_pct'    => array( 'type' => 'int', 'def' => 40, 'min' => 5, 'max' => 100, 'tab' => 'when', 'label' => __( 'Scrolled down (% of the page)', '2ace-popups' ), 'show' => 'triggers:scroll' ),
		'idle_sec'      => array( 'type' => 'int', 'def' => 20, 'min' => 5, 'max' => 600, 'tab' => 'when', 'label' => __( 'Inactive for (seconds)', '2ace-popups' ), 'show' => 'triggers:idle' ),
		'pv_n'          => array( 'type' => 'int', 'def' => 3, 'min' => 2, 'max' => 50, 'tab' => 'when', 'label' => __( 'Pages viewed in this visit', '2ace-popups' ), 'show' => 'triggers:pageviews' ),
		'click_sel'     => array( 'type' => 'text', 'def' => '', 'tab' => 'when', 'label' => __( 'CSS selector of the button', '2ace-popups' ), 'help' => __( 'Example: .my-button. Any link to #acepu-ID (shown after saving) also opens this pop-up.', '2ace-popups' ), 'show' => 'triggers:click' ),
		'freq'          => array( 'type' => 'select', 'def' => 'days', 'tab' => 'when', 'label' => __( 'How often the same visitor sees it', '2ace-popups' ), 'opts' => array( 'session' => __( 'Once per visit', '2ace-popups' ), 'days' => __( 'Once every N days', '2ace-popups' ), 'once' => __( 'Only once, ever', '2ace-popups' ), 'always' => __( 'Every time the conditions are met', '2ace-popups' ) ) ),
		'freq_days'     => array( 'type' => 'int', 'def' => 7, 'min' => 1, 'max' => 365, 'tab' => 'when', 'label' => __( 'N (days)', '2ace-popups' ), 'show' => 'freq:days' ),
		'max_views'     => array( 'type' => 'int', 'def' => 0, 'min' => 0, 'max' => 100, 'tab' => 'when', 'label' => __( 'At most this many times per visitor (0 = no limit)', '2ace-popups' ) ),
		'stop_conv'     => array( 'type' => 'bool', 'def' => 1, 'tab' => 'when', 'label' => __( 'Never show again after the visitor took the offer', '2ace-popups' ) ),
		'priority'      => array( 'type' => 'int', 'def' => 10, 'min' => 1, 'max' => 99, 'tab' => 'when', 'label' => __( 'Priority (higher wins when several pop-ups qualify)', '2ace-popups' ) ),
		'start'         => array( 'type' => 'datetime', 'def' => '', 'tab' => 'when', 'label' => __( 'Show from', '2ace-popups' ), 'help' => __( 'Shop time. Empty = right away.', '2ace-popups' ) ),
		'end'           => array( 'type' => 'datetime', 'def' => '', 'tab' => 'when', 'label' => __( 'Hide after', '2ace-popups' ), 'help' => __( 'Shop time. Empty = no end. Used by the countdown.', '2ace-popups' ) ),
		'days'          => array( 'type' => 'multi', 'def' => array(), 'tab' => 'when', 'label' => __( 'Only on these days (none ticked = every day)', '2ace-popups' ), 'opts' => $days ),
		'hour_from'     => array( 'type' => 'int', 'def' => 0, 'min' => 0, 'max' => 23, 'tab' => 'when', 'label' => __( 'Only from hour', '2ace-popups' ) ),
		'hour_to'       => array( 'type' => 'int', 'def' => 24, 'min' => 1, 'max' => 24, 'tab' => 'when', 'label' => __( '... until hour', '2ace-popups' ), 'help' => __( '0 to 24 = all day.', '2ace-popups' ) ),

		// Who.
		'pages'         => array( 'type' => 'multi', 'def' => array( 'home', 'shop', 'product_cat', 'product', 'post', 'page' ), 'tab' => 'who', 'label' => __( 'Show on', '2ace-popups' ), 'opts' => acepu_page_types() ),
		'cats'          => array( 'type' => 'ids', 'def' => array(), 'tab' => 'who', 'label' => __( 'Only for these product categories', '2ace-popups' ), 'help' => __( 'On category pages and product pages of the ticked categories. None = all.', '2ace-popups' ) ),
		'url_in'        => array( 'type' => 'lines', 'def' => '', 'tab' => 'who', 'label' => __( 'Only where the address contains (one per line)', '2ace-popups' ) ),
		'url_out'       => array( 'type' => 'lines', 'def' => '', 'tab' => 'who', 'label' => __( 'Never where the address contains (one per line)', '2ace-popups' ) ),
		'devices'       => array( 'type' => 'multi', 'def' => array( 'desktop', 'tablet', 'mobile' ), 'tab' => 'who', 'label' => __( 'Devices', '2ace-popups' ), 'opts' => array( 'desktop' => __( 'Computer', '2ace-popups' ), 'tablet' => __( 'Tablet', '2ace-popups' ), 'mobile' => __( 'Phone', '2ace-popups' ) ) ),
		'visitor'       => array( 'type' => 'select', 'def' => 'all', 'tab' => 'who', 'label' => __( 'Visitors', '2ace-popups' ), 'opts' => array( 'all' => __( 'Everyone', '2ace-popups' ), 'new' => __( 'New visitors only', '2ace-popups' ), 'returning' => __( 'Returning visitors only', '2ace-popups' ) ) ),
		'login'         => array( 'type' => 'select', 'def' => 'out', 'tab' => 'who', 'label' => __( 'Logged in?', '2ace-popups' ), 'opts' => array( 'all' => __( 'Everyone', '2ace-popups' ), 'out' => __( 'Visitors who are not logged in', '2ace-popups' ), 'in' => __( 'Logged-in customers only', '2ace-popups' ) ) ),
		'cart'          => array( 'type' => 'select', 'def' => 'any', 'tab' => 'who', 'label' => __( 'Cart', '2ace-popups' ), 'opts' => array( 'any' => __( 'Whatever is in the cart', '2ace-popups' ), 'has' => __( 'Cart has products (good for exit intent)', '2ace-popups' ), 'empty' => __( 'Cart is empty', '2ace-popups' ) ) ),
		'utm'           => array( 'type' => 'text', 'def' => '', 'tab' => 'who', 'label' => __( 'Came from (source or campaign contains)', '2ace-popups' ), 'help' => __( 'Matches utm_source / utm_campaign or the referring site. Example: facebook, newsletter.', '2ace-popups' ) ),
	);
	return $s;
}
