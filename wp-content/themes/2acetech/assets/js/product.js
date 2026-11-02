/**
 * Product page: quantity stepper, option swatches, sticky buy bar, copy link.
 * The stepper also runs on the cart page. No dependencies; uses jQuery only to
 * listen for WooCommerce's own events when it is present.
 */
(() => {
	'use strict';

	const $ = (sel, ctx = document) => ctx.querySelector(sel);
	const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));
	const fire = (el, type) => el.dispatchEvent(new Event(type, { bubbles: true }));

	/* Quantity stepper -------------------------------------------------- */
	function enhanceQuantity(root = document) {
		$$('.quantity', root).forEach((wrap) => {
			const input = $('input.qty', wrap);
			if (!input || input.type === 'hidden' || wrap.dataset.stepper) return;
			wrap.dataset.stepper = '1';

			const make = (label, text, dir) => {
				const b = document.createElement('button');
				b.type = 'button';
				b.className = 'qty-btn';
				b.setAttribute('aria-label', label);
				b.textContent = text;
				b.addEventListener('click', () => {
					const step = parseFloat(input.step) || 1;
					const min = input.min === '' ? 0 : parseFloat(input.min);
					const max = input.max === '' ? Infinity : parseFloat(input.max);
					let val = (parseFloat(input.value) || min) + dir * step;
					val = Math.min(max, Math.max(min, val));
					input.value = val;
					fire(input, 'input');
					fire(input, 'change');
				});
				return b;
			};
			wrap.insertBefore(make('Decrease quantity', '−', -1), wrap.firstChild);
			wrap.appendChild(make('Increase quantity', '+', 1));
		});
	}
	enhanceQuantity();
	if (window.jQuery) {
		window.jQuery(document.body).on('updated_cart_totals updated_wc_div', () => enhanceQuantity());
	}

	/* Option swatches ---------------------------------------------------- */
	const form = $('form.variations_form');
	if (form) {
		const isColorAttr = (el) => /colou?r|kolor/i.test(el.id + ' ' + (el.name || ''));
		const lightColor = (c) => {
			const probe = document.createElement('span');
			probe.style.color = c;
			document.body.appendChild(probe);
			const rgb = getComputedStyle(probe).color.match(/\d+/g) || [0, 0, 0];
			probe.remove();
			return rgb[0] * 0.299 + rgb[1] * 0.587 + rgb[2] * 0.114 > 215;
		};

		const builders = [];
		$$('table.variations select', form).forEach((select) => {
			const options = $$('option', select).filter((o) => o.value !== '');
			if (!options.length) return;

			const color = isColorAttr(select);
			const group = document.createElement('div');
			group.className = 'swatches';
			group.setAttribute('role', 'group');
			const label = $('label[for="' + select.id + '"]', form);
			if (label) group.setAttribute('aria-label', label.textContent);

			const buttons = options.map((o) => {
				const b = document.createElement('button');
				b.type = 'button';
				b.className = 'swatch';
				b.dataset.value = o.value;
				b.title = o.textContent;
				const useColor = color && window.CSS && CSS.supports('color', o.textContent.trim());
				if (useColor) {
					b.classList.add('swatch--color');
					b.style.setProperty('--sw', o.textContent.trim());
					if (lightColor(o.textContent.trim())) b.classList.add('is-light');
					b.innerHTML = '<span class="screen-reader-text"></span>';
					b.firstChild.textContent = o.textContent;
				} else {
					b.textContent = o.textContent;
				}
				b.addEventListener('click', () => {
					select.value = select.value === o.value ? '' : o.value;
					fire(select, 'change');
				});
				group.appendChild(b);
				return b;
			});

			const sync = () => {
				const available = new Set($$('option', select).map((o) => o.value));
				buttons.forEach((b) => {
					const on = select.value === b.dataset.value;
					b.setAttribute('aria-pressed', String(on));
					b.disabled = !available.has(b.dataset.value) && !on;
				});
			};

			select.classList.add('is-swatched');
			select.setAttribute('aria-hidden', 'true');
			select.tabIndex = -1;
			select.parentNode.insertBefore(group, select);
			select.addEventListener('change', sync);
			builders.push(sync);
			sync();
		});

		// WooCommerce rebuilds <option>s as choices narrow; keep buttons in step.
		const resync = () => setTimeout(() => builders.forEach((fn) => fn()), 0);
		if (window.jQuery) {
			window.jQuery(form).on('woocommerce_update_variation_values reset_data found_variation', resync);
		}
		form.addEventListener('change', resync);
	}

	/* Sticky buy bar (phones) -------------------------------------------- */
	const bar = $('[data-buybar]');
	const cart = $('form.cart');
	if (bar && cart && 'IntersectionObserver' in window) {
		const btn = $('[data-buybar-btn]', bar);
		const mobile = window.matchMedia('(max-width: 860px)');
		const show = (on) => {
			bar.classList.toggle('is-visible', on && mobile.matches);
			bar.setAttribute('aria-hidden', String(!(on && mobile.matches)));
			btn.tabIndex = on && mobile.matches ? 0 : -1;
		};
		new IntersectionObserver(([entry]) => {
			// Show only after the buy form has scrolled off the top.
			show(!entry.isIntersecting && entry.boundingClientRect.top < 0);
		}, { threshold: 0 }).observe(cart);

		btn.addEventListener('click', () => {
			const real = $('.single_add_to_cart_button', cart);
			if (real && !real.disabled && !real.classList.contains('disabled')) {
				real.click();
			} else {
				cart.scrollIntoView({ behavior: 'smooth', block: 'center' });
			}
		});
	}

	/* Copy link ---------------------------------------------------------- */
	$$('[data-copy-link]').forEach((btn) => {
		btn.addEventListener('click', async () => {
			try {
				await navigator.clipboard.writeText(btn.dataset.copyLink);
				btn.classList.add('is-done');
				btn.title = btn.dataset.copied;
				setTimeout(() => btn.classList.remove('is-done'), 2000);
			} catch (e) {
				// Older browsers / insecure contexts: copy via a temporary field.
				const t = document.createElement('textarea');
				t.value = btn.dataset.copyLink;
				t.style.position = 'fixed';
				t.style.opacity = '0';
				document.body.appendChild(t);
				t.select();
				document.execCommand('copy');
				t.remove();
				btn.classList.add('is-done');
				setTimeout(() => btn.classList.remove('is-done'), 2000);
			}
		});
	});
})();
