/**
 * Checkout side panel.
 *
 * Links to the checkout open it in a panel that slides in from the right, over
 * the current page. The panel holds the normal checkout page in a frame named
 * "ace-panel". Inside that frame this same script only makes other pages open
 * in a new tab, so the order is never lost by following a link.
 *
 * No dependencies.
 */
(() => {
	'use strict';

	const cfg = window.aceCheckoutPanel || {};
	const inPanel = window.name === 'ace-panel' && window.self !== window.top;

	const sameOrigin = (u) => u.origin === window.location.origin;
	const trim = (p) => p.replace(/\/+$/, '');
	let checkoutPath = '';
	try {
		checkoutPath = trim(new URL(cfg.url || '', window.location.href).pathname);
	} catch (e) {
		checkoutPath = '';
	}
	const isCheckoutPath = (u) => checkoutPath && (trim(u.pathname) === checkoutPath || trim(u.pathname).indexOf(checkoutPath + '/') === 0);

	const plainClick = (e) => e.button === 0 && !e.metaKey && !e.ctrlKey && !e.shiftKey && !e.altKey;

	/* Inside the panel: legal links, "continue shopping" and so on open in a new tab. */
	if (inPanel) {
		document.addEventListener(
			'click',
			(e) => {
				const a = e.target.closest && e.target.closest('a[href]');
				if (!a || a.target) return;
				let u;
				try {
					u = new URL(a.href, window.location.href);
				} catch (err) {
					return;
				}
				if (/^https?:$/.test(u.protocol) && sameOrigin(u) && !isCheckoutPath(u)) {
					a.target = '_blank';
					a.rel = 'noopener';
				}
			},
			true
		);
		return;
	}

	const panel = document.querySelector('[data-cpanel]');
	const frame = panel && panel.querySelector('[data-cpanel-frame]');
	const loading = panel && panel.querySelector('[data-cpanel-loading]');
	if (!panel || !frame || !checkoutPath) return;

	const root = document.documentElement;
	const reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	let opener = null;
	let done = false; // The frame reached the order confirmation.

	const frameUrl = () => {
		const u = new URL(cfg.url, window.location.href);
		u.searchParams.set('ace_panel', '1');
		return u.href;
	};

	const open = (from) => {
		opener = from || document.activeElement;
		done = false;
		loading.hidden = false;
		frame.src = frameUrl(); // Fresh load: the cart may have changed since last time.
		panel.hidden = false;
		root.classList.add('cpanel-lock');
		// Next frame, so the slide-in transition runs.
		window.requestAnimationFrame(() => window.requestAnimationFrame(() => panel.classList.add('is-open')));
		const close = panel.querySelector('.cpanel__close');
		if (close) close.focus({ preventScroll: true });
	};

	const finish = () => {
		panel.hidden = true;
		frame.removeAttribute('src');
		root.classList.remove('cpanel-lock');
		if (done) {
			window.location.reload(); // Order placed: refresh the cart count and totals.
			return;
		}
		if (opener && opener.focus && document.contains(opener)) opener.focus({ preventScroll: true });
	};

	const close = () => {
		if (panel.hidden) return;
		panel.classList.remove('is-open');
		if (reduce) {
			finish();
		} else {
			window.setTimeout(finish, 320);
		}
	};

	frame.addEventListener('load', () => {
		loading.hidden = true;
		try {
			done = /order-received/.test(frame.contentWindow.location.pathname);
		} catch (e) {
			done = false; // Left our site (a payment page): keep the panel as it is.
		}
	});

	document.addEventListener(
		'click',
		(e) => {
			if (e.defaultPrevented) return;
			const closer = e.target.closest && e.target.closest('[data-cpanel-close]');
			if (closer) {
				e.preventDefault();
				close();
				return;
			}
			const a = e.target.closest && e.target.closest('a[href]');
			if (!a || !plainClick(e) || (a.target && a.target !== '_self') || a.hasAttribute('download')) return;
			let u;
			try {
				u = new URL(a.href, window.location.href);
			} catch (err) {
				return;
			}
			// Exactly the checkout page, not its endpoints (order-pay, order-received).
			if (/^https?:$/.test(u.protocol) && sameOrigin(u) && trim(u.pathname) === checkoutPath) {
				e.preventDefault();
				open(a);
			}
		},
		true
	);

	document.addEventListener('keydown', (e) => {
		if (e.key === 'Escape' && !panel.hidden) close();
	});
})();
