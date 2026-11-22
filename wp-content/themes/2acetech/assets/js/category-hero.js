/**
 * Category carousel hero: arrows, dots, autoplay.
 *
 * The slides are a native scroll-snap row, so swiping and scrolling already work.
 * This adds: previous/next, one dot per category (with a progress bar), autoplay
 * that pauses on hover, focus, touch and when the tab is hidden, and a
 * keyboard-friendly track. No autoplay if the visitor prefers reduced motion.
 *
 * No dependencies.
 */
(() => {
	'use strict';

	const root = document.querySelector('[data-chero]');
	if (!root) return;
	const track = root.querySelector('[data-chero-track]');
	const slides = Array.from(root.querySelectorAll('[data-chero-slide]'));
	const dots = Array.from(root.querySelectorAll('[data-chero-dot]'));
	if (!track || slides.length < 2) return;

	const INTERVAL = 6500;
	const reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	root.style.setProperty('--chero-ms', INTERVAL + 'ms');
	document.documentElement.classList.add('js');

	let index = 0;
	let timer = 0;
	let hold = 0; // Number of reasons not to auto-advance right now.

	const show = (i, behavior) => {
		index = (i + slides.length) % slides.length;
		track.scrollTo({ left: index * track.clientWidth, behavior: behavior || 'smooth' });
	};

	const mark = (i) => {
		slides.forEach((s, n) => {
			s.classList.toggle('is-active', n === i);
			s.setAttribute('aria-hidden', n === i ? 'false' : 'true');
			s.querySelectorAll('a').forEach((a) => (n === i ? a.removeAttribute('tabindex') : a.setAttribute('tabindex', '-1')));
		});
		dots.forEach((d, n) => {
			d.classList.toggle('is-active', n === i);
			d.classList.toggle('is-done', n < i);
			if (n === i) d.setAttribute('aria-current', 'true');
			else d.removeAttribute('aria-current');
		});
	};

	// Which slide is in view (after swipes, arrows or dots).
	let raf = 0;
	track.addEventListener(
		'scroll',
		() => {
			cancelAnimationFrame(raf);
			raf = requestAnimationFrame(() => {
				const w = track.clientWidth || 1;
				const i = Math.round(track.scrollLeft / w);
				if (i !== index && i >= 0 && i < slides.length) {
					index = i;
					mark(i);
					restart();
				}
			});
		},
		{ passive: true }
	);

	const tick = () => {
		if (hold > 0 || document.hidden) return;
		show(index + 1);
	};
	const stop = () => window.clearInterval(timer);
	const restart = () => {
		stop();
		if (reduce) return;
		// Restart the progress bar on the active dot.
		const d = dots[index];
		if (d) {
			const fill = d.querySelector('.chero__dot-fill');
			if (fill) {
				fill.style.animation = 'none';
				void fill.offsetWidth;
				fill.style.animation = '';
			}
		}
		timer = window.setInterval(tick, INTERVAL);
	};

	root.querySelector('[data-chero-prev]')?.addEventListener('click', () => { show(index - 1); });
	root.querySelector('[data-chero-next]')?.addEventListener('click', () => { show(index + 1); });
	dots.forEach((d) => d.addEventListener('click', () => show(Number(d.dataset.cheroDot))));

	// Pause while the visitor is looking or touching.
	const pause = () => { hold += 1; root.classList.add('is-paused'); };
	const resume = () => { hold = Math.max(0, hold - 1); if (!hold) root.classList.remove('is-paused'); };
	root.addEventListener('mouseenter', pause);
	root.addEventListener('mouseleave', resume);
	root.addEventListener('focusin', pause);
	root.addEventListener('focusout', resume);
	root.addEventListener('touchstart', pause, { passive: true });
	root.addEventListener('touchend', () => window.setTimeout(resume, 4000), { passive: true });

	track.addEventListener('keydown', (e) => {
		if (e.key === 'ArrowRight') { e.preventDefault(); show(index + 1); }
		if (e.key === 'ArrowLeft') { e.preventDefault(); show(index - 1); }
	});

	if (reduce) root.classList.add('no-auto');
	mark(0);
	restart();
})();
