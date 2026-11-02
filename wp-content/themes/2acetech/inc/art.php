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
	return array( 'watch', 'laptop', 'headphones', 'camera', 'phone', 'speaker', 'tablet', 'console' );
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
