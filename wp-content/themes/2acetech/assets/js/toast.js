/**
 * In-site notifications ("toasts").
 *
 * - Turns WooCommerce messages ("added to your cart", coupon applied, errors)
 *   into cards that slide in at the top corner instead of a grey bar.
 * - Shows an "added to cart" card with the product photo and buttons when a
 *   product is added from a list without reloading the page.
 * - Routes every browser pop-up (window.alert, used by WooCommerce and some
 *   plugins) into the same cards, so nothing opens a native browser dialog.
 *
 * No dependencies; uses jQuery only to hear WooCommerce's own events.
 */
(() => {
	'use strict';

	const cfg = Object.assign(
		{
			close: 'Close notification',
			added: 'Added to cart',
			viewCart: 'View cart',
			checkout: 'Checkout',
			cartUrl: '',
			checkoutUrl: '',
		},
		window.aceToast || {}
	);

	const reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	const MAX = 3;
	let box = null;

	const ICONS = {
		success: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>',
		error: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 7v6m0 4h.01"/></svg>',
		info: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 11v6m0-10h.01"/></svg>',
	};

	const container = () => {
		if (box && document.body.contains(box)) return box;
		box = document.createElement('div');
		box.className = 'toasts';
		box.setAttribute('role', 'region');
		box.setAttribute('aria-label', cfg.close);
		document.body.appendChild(box);
		return box;
	};

	const dismiss = (el) => {
		if (!el || el.dataset.gone) return;
		el.dataset.gone = '1';
		el.classList.add('is-leaving');
		const done = () => el.parentNode && el.parentNode.removeChild(el);
		if (reduceMotion) done();
		else {
			el.addEventListener('animationend', done, { once: true });
			setTimeout(done, 400);
		}
	};

	/**
	 * Show a card.
	 *
	 * @param {Object} o
	 * @param {string} [o.type]    success | error | info
	 * @param {string} [o.title]   Bold first line.
	 * @param {string} [o.text]    Second line.
	 * @param {string} [o.image]   Product photo URL.
	 * @param {Array}  [o.actions] [{ label, href, primary }]
	 * @param {number} [o.timeout] Milliseconds; 0 keeps it until closed.
	 */
	const toast = (o) => {
		const type = o.type || 'info';
		const host = container();
		while (host.children.length >= MAX) host.removeChild(host.firstElementChild);

		const el = document.createElement('div');
		el.className = `toast toast--${type}`;
		el.setAttribute('role', type === 'error' ? 'alert' : 'status');

		if (o.image) {
			const img = document.createElement('img');
			img.className = 'toast__img';
			img.src = o.image;
			img.alt = '';
			el.appendChild(img);
		} else {
			const ic = document.createElement('span');
			ic.className = 'toast__icon';
			ic.innerHTML = ICONS[type] || ICONS.info;
			el.appendChild(ic);
		}

		const body = document.createElement('div');
		body.className = 'toast__body';
		if (o.title) {
			const t = document.createElement('p');
			t.className = 'toast__title';
			t.textContent = o.title;
			body.appendChild(t);
		}
		if (o.text) {
			const p = document.createElement('p');
			p.className = 'toast__text';
			p.textContent = o.text;
			body.appendChild(p);
		}
		if (o.actions && o.actions.length) {
			const row = document.createElement('div');
			row.className = 'toast__actions';
			o.actions.forEach((a) => {
				if (!a.href) return;
				const link = document.createElement('a');
				link.className = 'toast__btn' + (a.primary ? ' toast__btn--primary' : '');
				link.href = a.href;
				link.textContent = a.label;
				row.appendChild(link);
			});
			if (row.children.length) body.appendChild(row);
		}
		el.appendChild(body);

		const x = document.createElement('button');
		x.type = 'button';
		x.className = 'toast__close';
		x.setAttribute('aria-label', cfg.close);
		x.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>';
		x.addEventListener('click', () => dismiss(el));
		el.appendChild(x);

		const ms = o.timeout === undefined ? (type === 'error' ? 9000 : 6500) : o.timeout;
		if (ms > 0) {
			const bar = document.createElement('span');
			bar.className = 'toast__bar';
			bar.style.setProperty('--ms', `${ms}ms`);
			el.appendChild(bar);
			if (reduceMotion) {
				setTimeout(() => dismiss(el), ms);
			} else {
				bar.addEventListener('animationend', () => dismiss(el), { once: true });
			}
		}

		host.appendChild(el);
		return el;
	};

	window.aceToastShow = toast;

	/* WooCommerce messages become cards --------------------------------- */
	const productImage = () => {
		const img = document.querySelector('.single-product .woocommerce-product-gallery__image img, .single-product img.wp-post-image');
		return img ? img.currentSrc || img.src : '';
	};

	const convert = (scope) => {
		(scope || document).querySelectorAll('.woocommerce-message, .woocommerce-error').forEach((n) => {
			if (n.dataset.toasted) return;
			// Checkout validation errors stay next to the fields they are about.
			if (n.closest('form.checkout, .woocommerce-NoticeGroup-checkout, .woocommerce-checkout')) return;
			n.dataset.toasted = '1';

			const isError = n.classList.contains('woocommerce-error');
			const forward = n.querySelector('a.wc-forward');
			const clone = n.cloneNode(true);
			clone.querySelectorAll('a.button, a.wc-forward, .button').forEach((b) => b.parentNode && b.parentNode.removeChild(b));
			let text;
			if (isError) {
				text = Array.from(clone.querySelectorAll('li')).map((li) => li.textContent.replace(/\s+/g, ' ').trim()).filter(Boolean).join(' ');
				if (!text) text = clone.textContent.replace(/\s+/g, ' ').trim();
			} else {
				text = clone.textContent.replace(/\s+/g, ' ').trim();
			}
			if (!text) return;

			const actions = [];
			let image = '';
			if (!isError && forward) {
				// "Added to your cart" on a product page: show the photo and both buttons.
				actions.push({ label: forward.textContent.trim() || cfg.viewCart, href: forward.href });
				if (cfg.checkoutUrl && forward.href !== cfg.checkoutUrl) actions.push({ label: cfg.checkout, href: cfg.checkoutUrl, primary: true });
				image = productImage();
			}
			toast({ type: isError ? 'error' : 'success', text, image, actions });
			n.parentNode && n.parentNode.removeChild(n);
		});
	};

	/* Added from a product list without reloading ----------------------- */
	if (window.jQuery) {
		window.jQuery(document.body).on('added_to_cart', (e, fragments, hash, $btn) => {
			const btn = $btn && $btn[0];
			const card = btn && btn.closest ? btn.closest('li.product, .pcard, .rail__item') : null;
			const title = card && card.querySelector('.woocommerce-loop-product__title, .pcard__title');
			const img = card && card.querySelector('img');
			toast({
				type: 'success',
				title: cfg.added,
				text: title ? title.textContent.replace(/\s+/g, ' ').trim() : '',
				image: img ? img.currentSrc || img.src : '',
				actions: [
					{ label: cfg.viewCart, href: cfg.cartUrl },
					{ label: cfg.checkout, href: cfg.checkoutUrl, primary: true },
				],
			});
		});
		// Notices WooCommerce adds after load (coupon, quantity update).
		window.jQuery(document.body).on('updated_wc_div updated_cart_totals applied_coupon removed_coupon', () => convert());
	}

	/* No native browser dialogs ----------------------------------------- */
	window.alert = (message) => {
		const text = String(message === undefined || message === null ? '' : message).replace(/\s+/g, ' ').trim();
		if (text) toast({ type: 'info', text, timeout: 8000 });
	};

	const start = () => convert();
	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
	else start();
})();
