/**
 * Scroll-driven hero.
 *
 * Maps scroll progress through the tall .hero__track onto N scenes. Each
 * scene gets two CSS custom properties, which hero.css turns into motion:
 *   --w  weight 0..1   (1 while the scene is in focus, cross-fades to its neighbours)
 *   --d  distance      (signed: negative = coming up, positive = already passed)
 *
 * One rAF-throttled scroll handler, no layout reads beyond one rect per frame.
 */
(() => {
	'use strict';

	const hero = document.querySelector('[data-hero]');
	if (!hero) return;
	if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

	const track = hero.querySelector('.hero__track');
	const stage = hero.querySelector('.hero__stage');
	const copies = Array.from(hero.querySelectorAll('[data-copy]'));
	const arts = Array.from(hero.querySelectorAll('[data-art]'));
	const bgs = Array.from(hero.querySelectorAll('[data-bg]'));
	const dots = Array.from(hero.querySelectorAll('[data-dot]'));
	const n = copies.length;
	if (!track || !stage || n < 1) return;

	const clamp = (v, lo = 0, hi = 1) => Math.min(hi, Math.max(lo, v));

	// Full weight for |d| < 0.25, linear fade to zero at |d| = 0.75. Two
	// neighbouring scenes sum to 1 at the midpoint, so the cross-fade is even.
	const weight = (d) => clamp(1 - (Math.abs(d) - 0.25) / 0.5);

	// The product photos are cut-outs, so two of them half-visible on top of
	// each other look like a double exposure. Give them a tighter fade: one
	// leaves completely before the next arrives.
	const artWeight = (d) => clamp(1 - (Math.abs(d) - 0.2) / 0.3);

	let active = -1;
	let queued = false;

	function update() {
		queued = false;

		const stick = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--stick')) || 0;
		const rect = track.getBoundingClientRect();
		const range = Math.max(1, track.offsetHeight - stage.offsetHeight);
		const progress = clamp((stick - rect.top) / range);
		const f = progress * (n - 1);

		hero.style.setProperty('--p', progress.toFixed(4));
		hero.classList.toggle('is-scrolled', progress > 0.015);

		let nearest = 0;
		for (let i = 0; i < n; i++) {
			const d = f - i;
			const w = weight(d);
			const wv = w.toFixed(3);
			const dv = d.toFixed(3);

			copies[i].style.setProperty('--w', wv);
			copies[i].style.setProperty('--d', dv);
			if (arts[i]) {
				arts[i].style.setProperty('--w', artWeight(d).toFixed(3));
				arts[i].style.setProperty('--d', dv);
			}
			if (bgs[i]) bgs[i].style.opacity = wv;
			if (dots[i]) dots[i].style.setProperty('--fill', clamp(f - (i - 1)).toFixed(3));

			if (Math.abs(d) < Math.abs(f - nearest)) nearest = i;
		}

		if (nearest !== active) {
			active = nearest;
			copies.forEach((el, i) => {
				const on = i === active;
				el.classList.toggle('is-active', on);
				el.inert = !on; // Hidden scenes must not be focusable.
				if (arts[i]) arts[i].classList.toggle('is-active', on);
				if (dots[i]) {
					dots[i].classList.toggle('is-active', on);
					if (on) dots[i].setAttribute('aria-current', 'true');
					else dots[i].removeAttribute('aria-current');
				}
			});
		}
	}

	function queue() {
		if (!queued) {
			queued = true;
			requestAnimationFrame(update);
		}
	}

	// Jump to a scene by scrolling to the matching point on the track.
	dots.forEach((dot, i) => {
		dot.addEventListener('click', () => {
			const stick = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--stick')) || 0;
			const range = Math.max(1, track.offsetHeight - stage.offsetHeight);
			const trackTop = track.getBoundingClientRect().top + window.scrollY;
			const target = trackTop - stick + (n > 1 ? (i / (n - 1)) * range : 0);
			window.scrollTo({ top: target + (i === 0 ? 0 : 1), behavior: 'smooth' });
		});
	});

	// Subtle pointer parallax for the product art (fine pointers only).
	if (window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
		stage.addEventListener('pointermove', (e) => {
			const r = stage.getBoundingClientRect();
			hero.style.setProperty('--mx', (((e.clientX - r.left) / r.width) * 2 - 1).toFixed(3));
			hero.style.setProperty('--my', (((e.clientY - r.top) / r.height) * 2 - 1).toFixed(3));
		}, { passive: true });
		stage.addEventListener('pointerleave', () => {
			hero.style.setProperty('--mx', 0);
			hero.style.setProperty('--my', 0);
		});
	}

	window.addEventListener('scroll', queue, { passive: true });
	window.addEventListener('resize', queue, { passive: true });
	if ('ResizeObserver' in window) new ResizeObserver(queue).observe(stage);
	update();
})();
