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
	// Labels come from PHP so they follow the site language.
	const text = Object.assign(
		{
			decrease: 'Decrease quantity',
			increase: 'Increase quantity',
			prevPhoto: 'Previous photo',
			nextPhoto: 'Next photo',
			chooseOptions: 'Please choose the product options first.',
			unavailable: 'Sorry, this combination is not available.',
		},
		window.aceProduct || {}
	);

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
			wrap.insertBefore(make(text.decrease, '−', -1), wrap.firstChild);
			wrap.appendChild(make(text.increase, '+', 1));
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

	/* Gallery: arrows on the photo, active thumbnail kept in view -------- */
	const gallery = $('.woocommerce-product-gallery');
	if (gallery && window.jQuery) {
		const $g = window.jQuery(gallery);
		let ready = false;

		const setup = () => {
			const viewport = $('.flex-viewport', gallery);
			const thumbs = $('.flex-control-thumbs', gallery);
			if (ready || !viewport || !thumbs || thumbs.children.length < 2) return;
			ready = true;

			const arrow = (dir, label, path) => {
				const b = document.createElement('button');
				b.type = 'button';
				b.className = `gallery-nav gallery-nav--${dir}`;
				b.setAttribute('aria-label', label);
				b.innerHTML = `<svg viewBox="0 0 24 24" aria-hidden="true"><path d="${path}"/></svg>`;
				b.addEventListener('click', (e) => {
					e.preventDefault();
					e.stopPropagation();
					$g.flexslider(dir);
				});
				viewport.appendChild(b);
			};
			arrow('prev', text.prevPhoto, 'm15 6-6 6 6 6');
			arrow('next', text.nextPhoto, 'm9 6 6 6-6 6');

			// Frame counter and progress line, light sweep on every change.
			const slides = $$('.woocommerce-product-gallery__image', gallery);
			const counter = document.createElement('span');
			counter.className = 'gallery-count';
			counter.setAttribute('aria-hidden', 'true');
			viewport.appendChild(counter);
			const pad = (n) => String(n).padStart(2, '0');
			let current = -1;
			let sweepTimer = 0;
			const syncFrame = () => {
				const index = Math.max(0, slides.findIndex((s) => s.classList.contains('flex-active-slide')));
				if (index === current) return;
				const first = current === -1;
				current = index;
				counter.textContent = `${pad(index + 1)} / ${pad(slides.length)}`;
				viewport.style.setProperty('--p', String((index + 1) / slides.length));
				if (!first) {
					viewport.classList.remove('is-switching');
					void viewport.offsetWidth; // restart the animation
					viewport.classList.add('is-switching');
					clearTimeout(sweepTimer);
					sweepTimer = setTimeout(() => viewport.classList.remove('is-switching'), 900);
				}
			};
			syncFrame();
			slides.forEach((s) => new MutationObserver(syncFrame).observe(s, { attributes: true, attributeFilter: ['class'] }));

			// Slide the thumbnail strip, never the page.
			const keepInView = () => {
				const li = $('.flex-active', thumbs);
				const item = li && li.closest('li');
				if (!item) return;
				thumbs.scrollTo({ left: item.offsetLeft - (thumbs.clientWidth - item.offsetWidth) / 2, behavior: 'smooth' });
			};
			new MutationObserver(keepInView).observe(thumbs, { attributes: true, subtree: true, attributeFilter: ['class'] });
		};

		// WooCommerce builds the slider after load; wait for it.
		new MutationObserver(setup).observe(gallery, { childList: true, subtree: true });
		window.addEventListener('load', setup);
		setup();
	}

	/* Variation-gallery plugin (vargal): frame counter, progress line and a
	 * light sweep on every photo change. It builds its slider with JS, so wait
	 * for its viewport to appear. */
	(() => {
		const root = $('.vargal-product-gallery');
		if (!root) return;
		const pad = (n) => String(n).padStart(2, '0');
		let bound = false;
		const bind = () => {
			const viewport = $('.vargal-product-gallery-viewport');
			const slides = viewport ? $$('.woocommerce-product-gallery__image', viewport) : [];
			if (bound || !viewport || slides.length < 2) return;
			bound = true;
			const counter = document.createElement('span');
			counter.className = 'gallery-count';
			counter.setAttribute('aria-hidden', 'true');
			viewport.appendChild(counter);
			let current = -1;
			let timer = 0;
			const sync = () => {
				const index = Math.max(0, slides.findIndex((s) => s.classList.contains('vargal-product-gallery-active-slide')));
				if (index === current) return;
				const first = current === -1;
				current = index;
				counter.textContent = `${pad(index + 1)} / ${pad(slides.length)}`;
				viewport.style.setProperty('--p', String((index + 1) / slides.length));
				if (!first) {
					viewport.classList.remove('is-switching');
					void viewport.offsetWidth; // restart the animation
					viewport.classList.add('is-switching');
					clearTimeout(timer);
					timer = setTimeout(() => viewport.classList.remove('is-switching'), 900);
				}
			};
			sync();
			slides.forEach((s) => new MutationObserver(sync).observe(s, { attributes: true, attributeFilter: ['class'] }));
		};
		new MutationObserver(bind).observe(root, { childList: true, subtree: true });
		window.addEventListener('load', bind);
		bind();
	})();

	/* Products with nothing to choose ------------------------------------
	 * Some imports are "variable" products with one variation and no option
	 * to pick (the AliExpress "ship from" attribute is hidden). WooCommerce's
	 * script never enables the button when there are no options, so enable it
	 * with the only variation. */
	const jq = window.jQuery;
	if (jq) {
		$$('form.variations_form').forEach((form) => {
			if ($('.variations select', form)) return;
			let list = null;
			try {
				list = JSON.parse(form.getAttribute('data-product_variations') || 'null');
			} catch (e) {
				list = null;
			}
			if (!Array.isArray(list) || list.length !== 1) return;
			const variation = list[0];
			const apply = () => {
				const btn = $('.single_add_to_cart_button', form);
				if (btn && btn.classList.contains('wc-variation-selection-needed')) {
					jq(form).trigger('found_variation', [variation]);
				}
			};
			jq(form).on('wc_variation_form', apply);
			[60, 500].forEach((ms) => setTimeout(apply, ms));
		});
	}

	/* Inline message instead of the browser alert -------------------------
	 * WooCommerce shows a pop-up when the button is pressed before every
	 * option is chosen. Show a styled note and mark the missing options. */
	const clearNotice = (form) => {
		const note = $('.ace-vnotice', form);
		if (note) note.parentNode.removeChild(note);
		$$('.is-missing', form).forEach((el) => el.classList.remove('is-missing'));
	};
	document.addEventListener(
		'click',
		(e) => {
			const btn = e.target.closest ? e.target.closest('.single_add_to_cart_button') : null;
			if (!btn || !btn.classList.contains('disabled')) return;
			const form = btn.closest('form.variations_form');
			if (!form) return;
			e.preventDefault();
			e.stopImmediatePropagation();

			const params = window.wc_add_to_cart_variation_params || {};
			const unavailable = btn.classList.contains('wc-variation-is-unavailable');
			const msg = unavailable
				? params.i18n_unavailable_text || text.unavailable
				: params.i18n_make_a_selection_text || text.chooseOptions;

			clearNotice(form);
			const note = document.createElement('div');
			note.className = 'ace-vnotice';
			note.setAttribute('role', 'alert');
			note.textContent = msg || '';
			const anchor = $('.woocommerce-variation-add-to-cart', form) || btn.parentNode;
			anchor.parentNode.insertBefore(note, anchor);

			const missing = $$('.variations select', form).filter((s) => !s.value);
			missing.forEach((s) => {
				const row = s.closest('tr') || s.parentNode;
				row.classList.add('is-missing');
			});
			const first = missing[0] || note;
			first.scrollIntoView({ block: 'center', behavior: 'smooth' });
			if (missing[0]) missing[0].focus({ preventScroll: true });
		},
		true
	);
	if (jq) {
		jq(document).on('change found_variation', 'form.variations_form', function () {
			clearNotice(this);
		});
	}
})();
