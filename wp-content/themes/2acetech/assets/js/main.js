/**
 * 2acetech: header, navigation, rails, tabs, reveal, countdown.
 * No dependencies.
 */
(() => {
	'use strict';

	const doc = document.documentElement;
	const $ = (sel, ctx = document) => ctx.querySelector(sel);
	const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));
	const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	/* Header: sticky offset for the hero, compact shadow on scroll ------- */
	const header = $('[data-header]');
	if (header) {
		const setStick = () => {
			const bar = $('#wpadminbar');
			const barH = bar && getComputedStyle(bar).position === 'fixed' ? bar.offsetHeight : 0;
			// The open phone menu lives inside the header and its max-height is
			// derived from --stick, so leave it out or the two chase each other.
			const menu = $('[data-nav]', header);
			const menuH = menu && menu.classList.contains('is-open') ? menu.offsetHeight : 0;
			doc.style.setProperty('--stick', `${header.offsetHeight - menuH + barH}px`);
		};
		setStick();
		if ('ResizeObserver' in window) new ResizeObserver(setStick).observe(header);
		window.addEventListener('resize', setStick, { passive: true });

		const onScroll = () => header.classList.toggle('is-stuck', window.scrollY > 8);
		onScroll();
		window.addEventListener('scroll', onScroll, { passive: true });
	}

	/* Mobile navigation -------------------------------------------------- */
	const navToggle = $('[data-nav-toggle]');
	const nav = $('[data-nav]');
	if (navToggle && nav) {
		navToggle.addEventListener('click', () => {
			const open = nav.classList.toggle('is-open');
			navToggle.setAttribute('aria-expanded', String(open));
		});
	}

	/* Departments dropdown ----------------------------------------------- */
	const dept = $('[data-departments]');
	if (dept) {
		const btn = $('[data-departments-toggle]', dept);
		const panel = $('.departments__panel', dept);
		const set = (open) => {
			btn.setAttribute('aria-expanded', String(open));
			panel.hidden = !open;
		};
		btn.addEventListener('click', () => set(panel.hidden));
		document.addEventListener('click', (e) => { if (!dept.contains(e.target)) set(false); });
		dept.addEventListener('keydown', (e) => {
			if (e.key === 'Escape') { set(false); btn.focus(); }
		});
	}

	/* Product rails ------------------------------------------------------ */
	$$('[data-rail]').forEach((rail) => {
		const section = rail.closest('section');
		const prev = $('[data-rail-prev]', section);
		const next = $('[data-rail-next]', section);
		const step = () => Math.max(rail.clientWidth * 0.9, 240);
		const sync = () => {
			if (!prev || !next) return;
			prev.disabled = rail.scrollLeft < 4;
			next.disabled = rail.scrollLeft + rail.clientWidth >= rail.scrollWidth - 4;
		};
		prev && prev.addEventListener('click', () => rail.scrollBy({ left: -step(), behavior: 'smooth' }));
		next && next.addEventListener('click', () => rail.scrollBy({ left: step(), behavior: 'smooth' }));
		rail.addEventListener('scroll', sync, { passive: true });
		window.addEventListener('resize', sync, { passive: true });
		sync();
	});

	/* Tabs (WAI-ARIA tabs pattern) --------------------------------------- */
	$$('[data-tabs]').forEach((root) => {
		const tabs = $$('[role="tab"]', root);
		const select = (tab, focus) => {
			tabs.forEach((t) => {
				const on = t === tab;
				t.setAttribute('aria-selected', String(on));
				t.tabIndex = on ? 0 : -1;
				const panel = document.getElementById(t.getAttribute('aria-controls'));
				if (panel) panel.hidden = !on;
			});
			if (focus) tab.focus();
		};
		tabs.forEach((tab, i) => {
			tab.addEventListener('click', () => select(tab));
			tab.addEventListener('keydown', (e) => {
				const keys = { ArrowRight: 1, ArrowLeft: -1 };
				if (e.key in keys) {
					e.preventDefault();
					select(tabs[(i + keys[e.key] + tabs.length) % tabs.length], true);
				} else if (e.key === 'Home') { e.preventDefault(); select(tabs[0], true); }
				else if (e.key === 'End') { e.preventDefault(); select(tabs[tabs.length - 1], true); }
			});
		});
	});

	/* Reveal on scroll --------------------------------------------------- */
	const reveals = $$('[data-reveal]');
	if (reveals.length) {
		if (!('IntersectionObserver' in window) || reduceMotion) {
			reveals.forEach((el) => el.classList.add('is-in'));
		} else {
			const io = new IntersectionObserver((entries) => {
				entries.forEach((entry) => {
					if (entry.isIntersecting) {
						entry.target.classList.add('is-in');
						io.unobserve(entry.target);
					}
				});
			}, { threshold: 0.1, rootMargin: '0px 0px -6% 0px' });
			reveals.forEach((el) => io.observe(el));
		}
	}

	/* Countdown ---------------------------------------------------------- */
	$$('[data-countdown]').forEach((el) => {
		const end = Date.parse(el.dataset.countdown);
		if (Number.isNaN(end)) return;
		const parts = {
			d: $('[data-cd="d"]', el), h: $('[data-cd="h"]', el),
			m: $('[data-cd="m"]', el), s: $('[data-cd="s"]', el),
		};
		const pad = (n) => String(n).padStart(2, '0');
		const tick = () => {
			let left = Math.max(0, Math.floor((end - Date.now()) / 1000));
			const d = Math.floor(left / 86400); left %= 86400;
			const h = Math.floor(left / 3600); left %= 3600;
			const m = Math.floor(left / 60);
			parts.d.textContent = pad(d);
			parts.h.textContent = pad(h);
			parts.m.textContent = pad(m);
			parts.s.textContent = pad(left % 60);
		};
		tick();
		setInterval(tick, 1000);
	});

	/* Back to top -------------------------------------------------------- */
	const toTop = $('[data-to-top]');
	if (toTop) {
		toTop.hidden = false;
		const toggle = () => toTop.classList.toggle('is-visible', window.scrollY > 900);
		window.addEventListener('scroll', toggle, { passive: true });
		toggle();
		toTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' }));
	}
})();
