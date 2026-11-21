<?php
/**
 * Inline SVG product illustrations.
 *
 * Used by the hero, category tiles and the demo product cards that show until
 * WooCommerce has real products. Colours come from CSS variables so the art
 * follows the brand palette.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

/**
 * Names of the available illustrations.
 *
 * @return string[]
 */
function ace_art_names() {
	return array( 'watch', 'laptop', 'headphones', 'camera', 'phone', 'speaker', 'tablet', 'console', 'lamp', 'blocks', 'scooter', 'pot', 'plant', 'tent', 'notebook', 'bottle', 'paw', 'chip' );
}

/**
 * Return an inline SVG illustration.
 *
 * @param string $name  One of ace_art_names().
 * @param string $label Accessible label. Empty marks the art as decorative.
 * @return string
 */
function ace_art( $name, $label = '' ) {
	$a11y = $label ? 'role="img" aria-label="' . esc_attr( $label ) . '"' : 'aria-hidden="true" focusable="false"';
	$open = '<svg class="art art--' . esc_attr( $name ) . '" viewBox="0 0 400 400" xmlns="http://www.w3.org/2000/svg" ' . $a11y . '>';
	$p    = 'a-' . $name; // Gradient id prefix.

	switch ( $name ) {
		case 'watch':
			$body = <<<SVG
<defs>
<linearGradient id="{$p}-body" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#4a5163"/><stop offset="1" stop-color="#13151b"/></linearGradient>
<linearGradient id="{$p}-strap" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#262932"/><stop offset="1" stop-color="#101116"/></linearGradient>
<radialGradient id="{$p}-glass" cx=".3" cy=".22" r=".95"><stop offset="0" stop-color="#2a3347"/><stop offset="1" stop-color="#05060a"/></radialGradient>
</defs>
<path d="M152 14h96l14 92H138z" fill="url(#{$p}-strap)"/>
<path d="M138 294h124l-14 92h-96z" fill="url(#{$p}-strap)"/>
<g stroke="#000" stroke-opacity=".35" stroke-width="2"><path d="M146 50h108M143 72h114M141 90h118"/><path d="M141 312h118M143 330h114M146 348h108"/></g>
<rect x="298" y="168" width="14" height="40" rx="7" fill="#20232c"/>
<rect x="296" y="236" width="10" height="26" rx="5" fill="#1a1c23"/>
<rect x="100" y="96" width="200" height="208" rx="54" fill="url(#{$p}-body)"/>
<rect x="111" y="107" width="178" height="186" rx="45" fill="url(#{$p}-glass)"/>
<circle cx="200" cy="200" r="68" fill="none" stroke="#fff" stroke-opacity=".08" stroke-width="10"/>
<circle cx="200" cy="200" r="68" fill="none" stroke="var(--art-accent)" stroke-width="10" stroke-linecap="round" stroke-dasharray="300 430" transform="rotate(-110 200 200)"/>
<path d="M200 200V152" stroke="#fff" stroke-width="7" stroke-linecap="round"/>
<path d="M200 200l34 20" stroke="#fff" stroke-width="7" stroke-linecap="round"/>
<circle cx="200" cy="200" r="7" fill="var(--art-accent)"/>
<path d="M122 140q22-30 86-32" fill="none" stroke="#fff" stroke-opacity=".28" stroke-width="4" stroke-linecap="round"/>
SVG;
			break;

		case 'laptop':
			$body = <<<SVG
<defs>
<linearGradient id="{$p}-screen" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="var(--art-accent)"/><stop offset="1" stop-color="#1b0b0e"/></linearGradient>
<linearGradient id="{$p}-base" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#cfd3db"/><stop offset="1" stop-color="#8b909b"/></linearGradient>
</defs>
<rect x="64" y="72" width="272" height="182" rx="14" fill="#101218"/>
<rect x="74" y="82" width="252" height="162" rx="7" fill="url(#{$p}-screen)"/>
<circle cx="240" cy="150" r="46" fill="#fff" fill-opacity=".14"/>
<circle cx="240" cy="150" r="26" fill="#fff" fill-opacity=".18"/>
<rect x="94" y="102" width="86" height="8" rx="4" fill="#fff" fill-opacity=".85"/>
<rect x="94" y="120" width="60" height="6" rx="3" fill="#fff" fill-opacity=".45"/>
<rect x="94" y="196" width="34" height="30" rx="6" fill="#fff" fill-opacity=".22"/>
<rect x="136" y="180" width="34" height="46" rx="6" fill="#fff" fill-opacity=".32"/>
<rect x="178" y="206" width="34" height="20" rx="6" fill="#fff" fill-opacity=".22"/>
<path d="M30 264h340l-20 28a16 16 0 0 1-13 7H63a16 16 0 0 1-13-7z" fill="url(#{$p}-base)"/>
<rect x="164" y="264" width="72" height="7" rx="3.5" fill="#6d727d" opacity=".7"/>
<ellipse cx="200" cy="318" rx="150" ry="10" fill="#000" opacity=".12"/>
SVG;
			break;

		case 'headphones':
			$body = <<<SVG
<defs>
<linearGradient id="{$p}-band" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#2c2f38"/><stop offset=".5" stop-color="#4b505d"/><stop offset="1" stop-color="#1c1e25"/></linearGradient>
<linearGradient id="{$p}-cup" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#3a3e4a"/><stop offset="1" stop-color="#12141a"/></linearGradient>
</defs>
<path d="M92 236V196a108 108 0 0 1 216 0v40" fill="none" stroke="url(#{$p}-band)" stroke-width="24" stroke-linecap="round"/>
<path d="M104 190a96 96 0 0 1 192 0" fill="none" stroke="#fff" stroke-opacity=".14" stroke-width="3" stroke-linecap="round"/>
<rect x="58" y="206" width="72" height="124" rx="34" fill="url(#{$p}-cup)"/>
<rect x="270" y="206" width="72" height="124" rx="34" fill="url(#{$p}-cup)"/>
<rect x="116" y="224" width="16" height="88" rx="8" fill="var(--art-accent)"/>
<rect x="268" y="224" width="16" height="88" rx="8" fill="var(--art-accent)"/>
<circle cx="86" cy="268" r="16" fill="none" stroke="#fff" stroke-opacity=".18" stroke-width="3"/>
<circle cx="314" cy="268" r="16" fill="none" stroke="#fff" stroke-opacity=".18" stroke-width="3"/>
<ellipse cx="200" cy="360" rx="130" ry="9" fill="#000" opacity=".1"/>
SVG;
			break;

		case 'camera':
			$body = <<<SVG
<defs>
<linearGradient id="{$p}-body" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#3b3f4b"/><stop offset="1" stop-color="#14161c"/></linearGradient>
<radialGradient id="{$p}-lens" cx=".35" cy=".3" r=".9"><stop offset="0" stop-color="#46526e"/><stop offset=".55" stop-color="#0d1018"/><stop offset="1" stop-color="#05060a"/></radialGradient>
</defs>
<path d="M126 128l16-30h116l16 30z" fill="#20232c"/>
<rect x="48" y="126" width="304" height="196" rx="30" fill="url(#{$p}-body)"/>
<rect x="48" y="126" width="304" height="52" rx="26" fill="#fff" fill-opacity=".05"/>
<circle cx="200" cy="228" r="84" fill="#0a0b10"/>
<circle cx="200" cy="228" r="72" fill="url(#{$p}-lens)"/>
<circle cx="200" cy="228" r="46" fill="none" stroke="var(--art-accent)" stroke-width="4" stroke-opacity=".9"/>
<circle cx="200" cy="228" r="22" fill="#05060a"/>
<path d="M164 196q18-20 46-16" fill="none" stroke="#fff" stroke-opacity=".5" stroke-width="5" stroke-linecap="round"/>
<rect x="298" y="104" width="32" height="14" rx="7" fill="var(--art-accent)"/>
<circle cx="86" cy="160" r="9" fill="var(--art-accent)"/>
<rect x="72" y="262" width="26" height="40" rx="8" fill="#0d0e13" opacity=".6"/>
SVG;
			break;

		case 'phone':
			$body = <<<SVG
<defs>
<linearGradient id="{$p}-body" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#4c5262"/><stop offset="1" stop-color="#14161c"/></linearGradient>
<linearGradient id="{$p}-screen" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#1b1d26"/><stop offset="1" stop-color="var(--art-accent)"/></linearGradient>
</defs>
<rect x="110" y="24" width="180" height="352" rx="38" fill="url(#{$p}-body)"/>
<rect x="120" y="34" width="160" height="332" rx="30" fill="url(#{$p}-screen)"/>
<rect x="172" y="46" width="56" height="15" rx="7.5" fill="#05060a"/>
<rect x="138" y="92" width="124" height="56" rx="14" fill="#fff" fill-opacity=".16"/>
<rect x="138" y="160" width="58" height="58" rx="14" fill="#fff" fill-opacity=".2"/>
<rect x="204" y="160" width="58" height="58" rx="14" fill="#fff" fill-opacity=".12"/>
<rect x="138" y="230" width="124" height="14" rx="7" fill="#fff" fill-opacity=".5"/>
<rect x="138" y="254" width="84" height="10" rx="5" fill="#fff" fill-opacity=".3"/>
<rect x="176" y="346" width="48" height="5" rx="2.5" fill="#fff" fill-opacity=".6"/>
SVG;
			break;

		case 'speaker':
			$body = <<<SVG
<defs>
<linearGradient id="{$p}-body" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#d7dae1"/><stop offset=".45" stop-color="#fafbfc"/><stop offset="1" stop-color="#b9bdc7"/></linearGradient>
</defs>
<ellipse cx="200" cy="372" rx="92" ry="12" fill="#000" opacity=".12"/>
<path d="M170 360h60l10 10h-80z" fill="#9ca0ab"/>
<path d="M200 36c62 0 98 86 98 170 0 76-38 150-98 150S102 282 102 206C102 122 138 36 200 36z" fill="url(#{$p}-body)"/>
<path d="M106 262q94 40 188 0" fill="none" stroke="var(--art-accent)" stroke-width="7" stroke-linecap="round"/>
<g fill="#8a8f9b" fill-opacity=".55"><circle cx="170" cy="130" r="4"/><circle cx="200" cy="130" r="4"/><circle cx="230" cy="130" r="4"/><circle cx="155" cy="156" r="4"/><circle cx="185" cy="156" r="4"/><circle cx="215" cy="156" r="4"/><circle cx="245" cy="156" r="4"/><circle cx="170" cy="182" r="4"/><circle cx="200" cy="182" r="4"/><circle cx="230" cy="182" r="4"/></g>
<circle cx="200" cy="82" r="9" fill="var(--art-accent)"/>
SVG;
			break;

		case 'tablet':
			$body = <<<SVG
<defs>
<linearGradient id="{$p}-screen" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="var(--art-accent)"/><stop offset="1" stop-color="#16171d"/></linearGradient>
</defs>
<g transform="rotate(-8 200 200)">
<rect x="82" y="40" width="236" height="320" rx="28" fill="#e9ebf0"/>
<rect x="94" y="52" width="212" height="296" rx="18" fill="url(#{$p}-screen)"/>
<rect x="116" y="82" width="110" height="12" rx="6" fill="#fff" fill-opacity=".85"/>
<rect x="116" y="104" width="78" height="8" rx="4" fill="#fff" fill-opacity=".45"/>
<circle cx="236" cy="236" r="46" fill="#fff" fill-opacity=".16"/>
<rect x="116" y="290" width="168" height="34" rx="12" fill="#fff" fill-opacity=".2"/>
<circle cx="200" cy="46" r="3" fill="#9aa0ac"/>
</g>
SVG;
			break;

		case 'lamp':
			$body = <<<SVG
<defs>
<linearGradient id="{$p}-shade" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#f6f7f9"/><stop offset="1" stop-color="#b8bcc6"/></linearGradient>
<linearGradient id="{$p}-base" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#4a5163"/><stop offset="1" stop-color="#13151b"/></linearGradient>
<radialGradient id="{$p}-glow" cx=".5" cy=".5" r=".5"><stop offset="0" style="stop-color:var(--art-accent);stop-opacity:.42"/><stop offset="1" style="stop-color:var(--art-accent);stop-opacity:0"/></radialGradient>
</defs>
<circle cx="200" cy="190" r="170" fill="url(#{$p}-glow)"/>
<path d="M112 196l36-108h104l36 108z" fill="url(#{$p}-shade)"/>
<path d="M112 196h176" stroke="#fff" stroke-opacity=".7" stroke-width="3"/>
<path d="M148 88h104" stroke="#000" stroke-opacity=".12" stroke-width="4"/>
<circle cx="200" cy="214" r="20" fill="var(--art-accent)"/>
<circle cx="193" cy="207" r="6" fill="#fff" fill-opacity=".55"/>
<rect x="190" y="226" width="20" height="96" rx="10" fill="url(#{$p}-base)"/>
<ellipse cx="200" cy="330" rx="78" ry="16" fill="url(#{$p}-base)"/>
<ellipse cx="200" cy="326" rx="54" ry="7" fill="#fff" fill-opacity=".1"/>
<ellipse cx="200" cy="356" rx="124" ry="9" fill="#000" opacity=".1"/>
SVG;
			break;

		case 'blocks':
			$body = <<<SVG
<defs>
<linearGradient id="{$p}-dark" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#4a5163"/><stop offset="1" stop-color="#13151b"/></linearGradient>
<linearGradient id="{$p}-light" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#f6f7f9"/><stop offset="1" stop-color="#b8bcc6"/></linearGradient>
</defs>
<rect x="76" y="246" width="248" height="92" rx="14" fill="url(#{$p}-dark)"/>
<rect x="76" y="246" width="248" height="92" rx="14" fill="none" stroke="#fff" stroke-opacity=".14"/>
<rect x="92" y="230" width="42" height="20" rx="9" fill="#262932"/>
<rect x="266" y="230" width="42" height="20" rx="9" fill="#262932"/>
<rect x="114" y="170" width="172" height="80" rx="14" fill="url(#{$p}-light)"/>
<rect x="114" y="170" width="172" height="80" rx="14" fill="none" stroke="#fff" stroke-opacity=".7"/>
<rect x="128" y="154" width="42" height="20" rx="9" fill="#b8bcc6"/>
<rect x="230" y="154" width="42" height="20" rx="9" fill="#b8bcc6"/>
<rect x="150" y="92" width="100" height="82" rx="14" fill="var(--art-accent)"/>
<rect x="150" y="92" width="100" height="82" rx="14" fill="none" stroke="#fff" stroke-opacity=".35"/>
<rect x="162" y="76" width="30" height="20" rx="9" fill="var(--art-accent)"/>
<rect x="208" y="76" width="30" height="20" rx="9" fill="var(--art-accent)"/>
<path d="M92 262h216M130 188h140" stroke="#fff" stroke-opacity=".16" stroke-width="3" stroke-linecap="round"/>
<ellipse cx="200" cy="354" rx="140" ry="9" fill="#000" opacity=".1"/>
SVG;
			break;

		case 'scooter':
			$body = <<<SVG
<defs>
<linearGradient id="{$p}-dark" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#4a5163"/><stop offset="1" stop-color="#13151b"/></linearGradient>
</defs>
<path d="M312 292L268 100" fill="none" stroke="url(#{$p}-dark)" stroke-width="18" stroke-linecap="round"/>
<path d="M232 90h92" fill="none" stroke="#262932" stroke-width="16" stroke-linecap="round"/>
<path d="M232 90h26M298 90h26" fill="none" stroke="var(--art-accent)" stroke-width="16" stroke-linecap="round"/>
<rect x="64" y="284" width="262" height="22" rx="11" fill="url(#{$p}-dark)"/>
<path d="M92 295h132" stroke="var(--art-accent)" stroke-width="5" stroke-linecap="round"/>
<circle cx="96" cy="322" r="30" fill="url(#{$p}-dark)"/>
<circle cx="96" cy="322" r="11" fill="#2f333e"/>
<circle cx="96" cy="322" r="30" fill="none" stroke="#fff" stroke-opacity=".16" stroke-width="3"/>
<circle cx="312" cy="322" r="30" fill="url(#{$p}-dark)"/>
<circle cx="312" cy="322" r="11" fill="#2f333e"/>
<circle cx="312" cy="322" r="30" fill="none" stroke="#fff" stroke-opacity=".16" stroke-width="3"/>
<ellipse cx="204" cy="358" rx="150" ry="9" fill="#000" opacity=".1"/>
SVG;
			break;

		case 'pot':
			$body = <<<SVG
<defs>
<linearGradient id="{$p}-dark" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#4a5163"/><stop offset="1" stop-color="#13151b"/></linearGradient>
<linearGradient id="{$p}-light" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#f6f7f9"/><stop offset="1" stop-color="#b8bcc6"/></linearGradient>
</defs>
<path d="M172 108q-16-18 0-36M204 100q-16-18 0-36M236 108q-16-18 0-36" fill="none" stroke="var(--art-accent)" stroke-width="7" stroke-linecap="round" opacity=".75"/>
<rect x="56" y="214" width="46" height="20" rx="10" fill="#262932"/>
<rect x="298" y="214" width="46" height="20" rx="10" fill="#262932"/>
<rect x="88" y="190" width="224" height="140" rx="32" fill="url(#{$p}-dark)"/>
<rect x="88" y="190" width="224" height="140" rx="32" fill="none" stroke="#fff" stroke-opacity=".12"/>
<rect x="76" y="160" width="248" height="38" rx="19" fill="url(#{$p}-light)"/>
<rect x="76" y="160" width="248" height="38" rx="19" fill="none" stroke="#fff" stroke-opacity=".7"/>
<rect x="176" y="126" width="48" height="38" rx="14" fill="var(--art-accent)"/>
<path d="M112 232h176" stroke="#fff" stroke-opacity=".14" stroke-width="4" stroke-linecap="round"/>
<ellipse cx="200" cy="354" rx="136" ry="9" fill="#000" opacity=".1"/>
SVG;
			break;

		case 'plant':
			$body = <<<SVG
<defs>
<linearGradient id="{$p}-dark" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#4a5163"/><stop offset="1" stop-color="#13151b"/></linearGradient>
<linearGradient id="{$p}-light" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#f6f7f9"/><stop offset="1" stop-color="#b8bcc6"/></linearGradient>
</defs>
<path d="M200 270C122 262 92 196 114 136C180 154 208 210 200 270z" fill="url(#{$p}-dark)"/>
<path d="M200 270C278 262 308 196 286 136C220 154 192 210 200 270z" fill="url(#{$p}-dark)"/>
<path d="M200 258C166 208 168 146 200 80C232 146 234 208 200 258z" fill="var(--art-accent)"/>
<path d="M200 252V120M200 262L134 160M200 262L266 160" fill="none" stroke="#fff" stroke-opacity=".3" stroke-width="3" stroke-linecap="round"/>
<path d="M118 266h164l-20 84H138z" fill="url(#{$p}-light)"/>
<rect x="108" y="256" width="184" height="24" rx="12" fill="url(#{$p}-light)"/>
<rect x="108" y="256" width="184" height="24" rx="12" fill="none" stroke="#fff" stroke-opacity=".7"/>
<ellipse cx="200" cy="360" rx="116" ry="8" fill="#000" opacity=".1"/>
SVG;
			break;

		case 'tent':
			$body = <<<SVG
<defs>
<linearGradient id="{$p}-dark" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#4a5163"/><stop offset="1" stop-color="#13151b"/></linearGradient>
<linearGradient id="{$p}-light" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#f6f7f9"/><stop offset="1" stop-color="#b8bcc6"/></linearGradient>
</defs>
<path d="M120 342L28 356M280 342L372 356" stroke="#13151b" stroke-opacity=".35" stroke-width="3" stroke-linecap="round"/>
<path d="M200 70L338 338H62z" fill="url(#{$p}-dark)"/>
<path d="M200 70L62 338H146z" fill="#fff" opacity=".1"/>
<path d="M200 150L252 338H148z" fill="var(--art-accent)"/>
<path d="M200 150V338" stroke="#fff" stroke-opacity=".38" stroke-width="3" stroke-linecap="round"/>
<path d="M200 72V40" stroke="#4a5163" stroke-width="5" stroke-linecap="round"/>
<path d="M203 42h42l-12 12 12 12h-42z" fill="url(#{$p}-light)"/>
<path d="M62 338H338" stroke="#fff" stroke-opacity=".5" stroke-width="3" stroke-linecap="round"/>
<circle cx="120" cy="344" r="5" fill="url(#{$p}-light)"/>
<circle cx="280" cy="344" r="5" fill="url(#{$p}-light)"/>
<ellipse cx="200" cy="358" rx="150" ry="9" fill="#000" opacity=".1"/>
SVG;
			break;

		case 'notebook':
			$body = <<<SVG
<defs>
<linearGradient id="{$p}-dark" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#4a5163"/><stop offset="1" stop-color="#13151b"/></linearGradient>
<linearGradient id="{$p}-light" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#f6f7f9"/><stop offset="1" stop-color="#b8bcc6"/></linearGradient>
</defs>
<rect x="96" y="64" width="204" height="278" rx="20" fill="url(#{$p}-dark)"/>
<rect x="96" y="64" width="204" height="278" rx="20" fill="none" stroke="#fff" stroke-opacity=".12"/>
<g fill="url(#{$p}-light)"><rect x="82" y="96" width="36" height="12" rx="6"/><rect x="82" y="136" width="36" height="12" rx="6"/><rect x="82" y="176" width="36" height="12" rx="6"/><rect x="82" y="216" width="36" height="12" rx="6"/><rect x="82" y="256" width="36" height="12" rx="6"/><rect x="82" y="296" width="36" height="12" rx="6"/></g>
<rect x="144" y="100" width="124" height="62" rx="10" fill="url(#{$p}-light)"/>
<path d="M162 124h88M162 142h58" stroke="#13151b" stroke-opacity=".5" stroke-width="6" stroke-linecap="round"/>
<path d="M146 210h120M146 240h120M146 270h84" stroke="#fff" stroke-opacity=".18" stroke-width="5" stroke-linecap="round"/>
<g transform="rotate(28 306 220)"><rect x="294" y="96" width="26" height="236" rx="13" fill="url(#{$p}-light)"/><rect x="294" y="96" width="26" height="62" rx="13" fill="var(--art-accent)"/><path d="M294 332l13 28 13-28z" fill="#262932"/></g>
<ellipse cx="200" cy="362" rx="130" ry="8" fill="#000" opacity=".1"/>
SVG;
			break;

		case 'bottle':
			$body = <<<SVG
<defs>
<linearGradient id="{$p}-light" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#f6f7f9"/><stop offset="1" stop-color="#b8bcc6"/></linearGradient>
<linearGradient id="{$p}-dark" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#4a5163"/><stop offset="1" stop-color="#13151b"/></linearGradient>
</defs>
<path d="M172 118q-2-46 28-58q30 12 28 58z" fill="var(--art-accent)"/>
<rect x="156" y="112" width="88" height="30" rx="10" fill="url(#{$p}-dark)"/>
<rect x="146" y="140" width="108" height="196" rx="38" fill="url(#{$p}-light)"/>
<rect x="146" y="140" width="108" height="196" rx="38" fill="none" stroke="#fff" stroke-opacity=".8"/>
<path d="M146 246h108v58a38 38 0 0 1-38 32h-32a38 38 0 0 1-38-32z" fill="var(--art-accent)" fill-opacity=".22"/>
<g stroke="#13151b" stroke-opacity=".35" stroke-width="4" stroke-linecap="round"><path d="M226 176h18M232 202h12M226 228h18M232 254h12"/></g>
<circle cx="190" cy="270" r="18" fill="var(--art-accent)"/>
<ellipse cx="200" cy="360" rx="96" ry="8" fill="#000" opacity=".1"/>
SVG;
			break;

		case 'paw':
			$body = <<<SVG
<defs>
<linearGradient id="{$p}-dark" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#4a5163"/><stop offset="1" stop-color="#13151b"/></linearGradient>
</defs>
<path d="M200 206c-50 0-84 36-70 76 11 28 40 26 70 26s59 2 70-26c14-40-20-76-70-76z" fill="url(#{$p}-dark)"/>
<ellipse cx="116" cy="188" rx="24" ry="34" transform="rotate(-24 116 188)" fill="url(#{$p}-dark)"/>
<ellipse cx="164" cy="132" rx="26" ry="38" transform="rotate(-8 164 132)" fill="url(#{$p}-dark)"/>
<ellipse cx="236" cy="132" rx="26" ry="38" transform="rotate(8 236 132)" fill="url(#{$p}-dark)"/>
<ellipse cx="284" cy="188" rx="24" ry="34" transform="rotate(24 284 188)" fill="url(#{$p}-dark)"/>
<ellipse cx="200" cy="262" rx="30" ry="18" fill="var(--art-accent)"/>
<path d="M150 232q18-14 40-14" fill="none" stroke="#fff" stroke-opacity=".22" stroke-width="4" stroke-linecap="round"/>
<ellipse cx="200" cy="350" rx="112" ry="8" fill="#000" opacity=".1"/>
SVG;
			break;

		case 'chip':
			$body = <<<SVG
<defs>
<linearGradient id="{$p}-dark" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#4a5163"/><stop offset="1" stop-color="#13151b"/></linearGradient>
</defs>
<g stroke="#b8bcc6" stroke-width="10" stroke-linecap="round"><path d="M82 146h30M82 184h30M82 222h30M82 260h30M288 146h30M288 184h30M288 222h30M288 260h30M146 82v30M184 82v30M222 82v30M260 82v30M146 288v30M184 288v30M222 288v30M260 288v30"/></g>
<rect x="108" y="108" width="184" height="184" rx="24" fill="url(#{$p}-dark)"/>
<rect x="108" y="108" width="184" height="184" rx="24" fill="none" stroke="#fff" stroke-opacity=".14"/>
<rect x="150" y="150" width="100" height="100" rx="14" fill="#0e1016" stroke="var(--art-accent)" stroke-width="4"/>
<circle cx="200" cy="200" r="20" fill="var(--art-accent)"/>
<path d="M168 168h18M214 232h18" stroke="#fff" stroke-opacity=".3" stroke-width="5" stroke-linecap="round"/>
<ellipse cx="200" cy="350" rx="116" ry="8" fill="#000" opacity=".1"/>
SVG;
			break;

		case 'console':
		default:
			$body = <<<SVG
<defs>
<linearGradient id="{$p}-body" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#f6f7f9"/><stop offset="1" stop-color="#b8bcc6"/></linearGradient>
</defs>
<rect x="52" y="176" width="296" height="130" rx="40" fill="url(#{$p}-body)"/>
<rect x="52" y="176" width="296" height="130" rx="40" fill="none" stroke="#fff" stroke-opacity=".6"/>
<circle cx="126" cy="236" r="26" fill="#1a1c23"/>
<circle cx="126" cy="236" r="14" fill="#2f333e"/>
<circle cx="274" cy="236" r="26" fill="#1a1c23"/>
<circle cx="274" cy="236" r="14" fill="#2f333e"/>
<rect x="172" y="212" width="56" height="14" rx="7" fill="#1a1c23" fill-opacity=".85"/>
<circle cx="316" cy="214" r="7" fill="var(--art-accent)"/>
<circle cx="340" cy="238" r="7" fill="#1a1c23"/>
<circle cx="316" cy="262" r="7" fill="#1a1c23"/>
<circle cx="292" cy="238" r="7" fill="#1a1c23"/>
<path d="M96 214v28M82 228h28" stroke="#fff" stroke-width="7" stroke-linecap="round"/>
<ellipse cx="200" cy="330" rx="140" ry="10" fill="#000" opacity=".1"/>
SVG;
			break;
	}

	return $open . $body . '</svg>';
}
