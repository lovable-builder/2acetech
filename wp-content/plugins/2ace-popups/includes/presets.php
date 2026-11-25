<?php
/**
 * Ready-made starting points for the usual occasions. Texts are in Polish (the shop's language);
 * edit anything after choosing one.
 *
 * @package 2ace-popups
 */

defined( 'ABSPATH' ) || exit;

/**
 * @return array[] id => array( name, desc, title, set ) where set overrides the defaults.
 */
function acepu_presets() {
	$consent = 'Chcę otrzymać jednorazowy kod rabatowy na podany adres e-mail. Administratorem danych jest 2ACE sp. z o.o.; adres e-mail wykorzystamy wyłącznie do wysłania kodu.';
	$news    = 'Chcę też otrzymywać newsletter z ofertami 2ACE (zgodę mogę cofnąć w każdej chwili).';
	return array(
		'welcome'   => array(
			'name'  => 'Welcome -10% for an e-mail',
			'desc'  => 'New visitors leave an e-mail and get a personal one-time coupon. The classic that fills your list.',
			'title' => 'Powitanie -10%',
			'set'   => array(
				'layout' => 'split', 'image_pos' => 'left', 'badge' => '-10%', 'eyebrow' => 'Dla nowych klientów',
				'headline' => 'Witaj w 2ACE! Odbierz 10% zniżki na pierwsze zakupy',
				'text' => 'Zostaw adres e-mail, a prześlemy Ci jednorazowy kod rabatowy {discount}. Wystarczy wpisać go w koszyku.',
				'cta_label' => 'Odbierz kod', 'dismiss_label' => 'Nie, dziękuję',
				'footnote' => 'Kod jednorazowy, ważny 14 dni od zapisu, nie łączy się z innymi kodami.',
				'mode' => 'email', 'gen_type' => 'percent', 'gen_amount' => 10, 'gen_days' => 14, 'gen_prefix' => 'WITAJ',
				'email_ph' => 'Twój adres e-mail', 'consent_text' => $consent, 'news_on' => 1, 'news_text' => $news,
				'done_title' => 'Twój kod jest gotowy!', 'done_text' => 'Skopiuj go lub użyj od razu. Wysłaliśmy go też na Twój e-mail.',
				'mail_subject' => 'Twój kod rabatowy 2ACE', 'mail_text' => 'Dziękujemy za zapisanie się! Oto Twój jednorazowy kod rabatowy. Wpisz go w koszyku przy składaniu zamówienia.',
				'triggers' => array( 'time' ), 'delay' => 8, 'freq' => 'days', 'freq_days' => 30, 'visitor' => 'new', 'login' => 'out',
			),
		),
		'blackfriday' => array(
			'name'  => 'Black Friday',
			'desc'  => 'Dark and loud, with a real countdown to the end date you set. Shows an existing coupon.',
			'title' => 'Black Friday',
			'set'   => array(
				'layout' => 'split', 'image_pos' => 'right', 'badge' => '-15%', 'eyebrow' => 'Black Friday',
				'headline' => 'Czarny piątek w 2ACE: 15% taniej na cały sklep',
				'text' => 'Użyj kodu {coupon_code} w koszyku. Promocja trwa tylko do {end_date}.',
				'cta_label' => 'Zobacz oferty', 'dismiss_label' => 'Może później', 'countdown' => 1,
				'footnote' => 'Kod nie łączy się z innymi kodami rabatowymi.',
				'bg' => '#0b0b0d', 'fg' => '#ffffff', 'accent' => '#f50505', 'accent_fg' => '#ffffff', 'overlay_op' => 75,
				'mode' => 'code', 'code' => 'BLACKFRIDAY', 'triggers' => array( 'time' ), 'delay' => 5, 'freq' => 'days', 'freq_days' => 1,
			),
		),
		'cyber'     => array(
			'name'  => 'Cyber Monday',
			'desc'  => 'A bright, electric sibling of Black Friday for the Monday after.',
			'title' => 'Cyber Monday',
			'set'   => array(
				'layout' => 'modal', 'image_pos' => 'top', 'badge' => '-12%', 'eyebrow' => 'Cyber Monday',
				'headline' => 'Cyberponiedziałek: 12% rabatu do końca dnia',
				'text' => 'Wpisz kod {coupon_code} w koszyku i zaoszczędź na sprzęcie, który naprawdę się przyda.',
				'cta_label' => 'Do sklepu', 'dismiss_label' => 'Nie teraz', 'countdown' => 1,
				'bg' => '#0f1330', 'fg' => '#ffffff', 'accent' => '#00d4ff', 'accent_fg' => '#04111a', 'overlay' => '#050816', 'overlay_op' => 70,
				'mode' => 'code', 'code' => 'CYBER12', 'triggers' => array( 'time' ), 'delay' => 6, 'freq' => 'session',
			),
		),
		'christmas' => array(
			'name'  => 'Christmas gifts',
			'desc'  => 'Warm festive colours; coupon for gift shopping.',
			'title' => 'Prezenty na Święta',
			'set'   => array(
				'layout' => 'split', 'image_pos' => 'left', 'badge' => '-12%', 'eyebrow' => 'Święta w 2ACE',
				'headline' => 'Prezenty pod choinkę z rabatem 12%',
				'text' => 'Zamów do {end_date} z kodem {coupon_code} i zdąż przed świętami.',
				'cta_label' => 'Wybierz prezent', 'dismiss_label' => 'Nie, dziękuję',
				'footnote' => 'Terminy dostaw podajemy w koszyku.',
				'bg' => '#fff8f0', 'fg' => '#2a1210', 'accent' => '#b3121a', 'accent_fg' => '#ffffff', 'overlay' => '#1b0a08',
				'mode' => 'code', 'code' => 'SWIETA12', 'triggers' => array( 'time', 'scroll' ), 'delay' => 10, 'scroll_pct' => 35, 'freq' => 'days', 'freq_days' => 3,
			),
		),
		'exit'      => array(
			'name'  => 'Exit intent: wait, your cart!',
			'desc'  => 'Shown when a visitor with products in the cart is about to leave. Gives a small coupon by e-mail.',
			'title' => 'Zaczekaj! Twój koszyk czeka',
			'set'   => array(
				'layout' => 'modal', 'image_pos' => 'top', 'badge' => '-5%', 'eyebrow' => 'Zaczekaj chwilę',
				'headline' => 'Zostawiasz coś w koszyku',
				'text' => 'Dokończ zamówienie teraz, a damy Ci {discount} na te zakupy. Zostaw e-mail, a odeślemy kod.',
				'cta_label' => 'Odbierz 5%', 'dismiss_label' => 'Nie, dziękuję',
				'footnote' => 'Kod jednorazowy, ważny 3 dni, nie łączy się z innymi kodami.',
				'mode' => 'email', 'gen_type' => 'percent', 'gen_amount' => 5, 'gen_days' => 3, 'gen_prefix' => 'KOSZYK',
				'email_ph' => 'Twój adres e-mail', 'consent_text' => $consent,
				'done_title' => 'Kod w drodze!', 'done_text' => 'Użyj go teraz i dokończ zamówienie.',
				'mail_subject' => 'Twój kod na koszyk w 2ACE', 'mail_text' => 'Oto Twój jednorazowy kod rabatowy. Wpisz go w koszyku, aby dokończyć zamówienie.',
				'triggers' => array( 'exit' ), 'freq' => 'days', 'freq_days' => 7, 'cart' => 'has', 'width' => 520,
			),
		),
		'shipping'  => array(
			'name'  => 'Free delivery bar',
			'desc'  => 'A slim bar at the bottom that reminds about free courier delivery. No coupon.',
			'title' => 'Pasek: bezpłatna dostawa',
			'set'   => array(
				'layout' => 'bar', 'eyebrow' => '', 'headline' => 'Bezpłatna dostawa kurierem na terenie całej Polski',
				'text' => 'Wysyłka w 2–5 dni roboczych.', 'cta_label' => 'Zobacz sklep',
				'bg' => '#0b0b0d', 'fg' => '#ffffff', 'accent' => '#f50505', 'accent_fg' => '#ffffff',
				'mode' => 'info', 'triggers' => array( 'time' ), 'delay' => 4, 'freq' => 'session', 'width' => 1000,
			),
		),
		'camping'   => array(
			'name'  => 'Season sale: camping',
			'desc'  => 'Corner slide-in for a category promotion. Tick the category under "Who sees it".',
			'title' => 'Sezon na camping',
			'set'   => array(
				'layout' => 'slide', 'image_pos' => 'top', 'badge' => '-10%', 'eyebrow' => 'Sezon kempingowy',
				'headline' => 'Camping taniej o 10%',
				'text' => 'Materace, pompki i krzesła turystyczne z kodem {coupon_code}.',
				'cta_label' => 'Zobacz camping', 'dismiss_label' => 'Zamknij',
				'bg' => '#10261b', 'fg' => '#ffffff', 'accent' => '#7ee081', 'accent_fg' => '#07210f', 'width' => 400,
				'mode' => 'code', 'code' => 'CAMPING10', 'triggers' => array( 'scroll' ), 'scroll_pct' => 30, 'freq' => 'days', 'freq_days' => 2,
			),
		),
		'newsletter' => array(
			'name'  => 'Minimal newsletter box',
			'desc'  => 'Centered, quiet and clean. Coupon-free e-mail invitation: the optional box only.',
			'title' => 'Newsletter',
			'set'   => array(
				'layout' => 'modal', 'image_pos' => 'background', 'eyebrow' => 'Bądź na bieżąco',
				'headline' => 'Nowości i okazje prosto na e-mail',
				'text' => 'Nie spamujemy: tylko nowe produkty i prawdziwe promocje.',
				'cta_label' => 'Zapisz mnie', 'dismiss_label' => 'Nie, dziękuję',
				'mode' => 'email', 'gen_type' => 'percent', 'gen_amount' => 5, 'gen_days' => 14, 'gen_prefix' => 'NEWS',
				'email_ph' => 'Twój adres e-mail', 'consent_text' => $consent, 'news_on' => 1, 'news_text' => $news,
				'done_title' => 'Dziękujemy!', 'done_text' => 'Kod rabatowy na pierwsze zakupy już czeka.',
				'mail_subject' => 'Twój kod rabatowy 2ACE', 'mail_text' => 'Dziękujemy za zapis! Oto Twój kod rabatowy.',
				'triggers' => array( 'scroll', 'idle' ), 'scroll_pct' => 50, 'idle_sec' => 25, 'freq' => 'days', 'freq_days' => 30, 'width' => 520,
			),
		),
	);
}
