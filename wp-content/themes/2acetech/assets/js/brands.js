/**
 * Brand strip: category tabs and a slow ticker.
 *
 * - Tabs show only the brands that sell in the chosen category.
 * - When the visible brands do not fit in one row, the row becomes a seamless ticker
 *   (the list is repeated once; the copy is hidden from screen readers and keyboard).
 *   It pauses on hover and focus; with "reduce motion" it is a normal swipeable row.
 * - Fits in one row? Then it stays a still, centred row, nothing moves.
 *
 * No dependencies. Without JavaScript the strip is the plain wrapped list.
 */
(() => {
	'use strict';

	const root = document.querySelector('[data-brands]');
	if (!root) return;
	const marquee = root.querySelector('[data-brands-marquee]');
	const track = root.querySelector('.brands__track');
	const list = root.querySelector('[data-brands-list]');
	if (!marquee || !track || !list) return;
	document.documentElement.classList.add('js');

	const SPEED = 55; // pixels per second
	let clone = null;

	const removeClone = () => {
		if (clone) { clone.remove(); clone = null; }
		marquee.classList.remove('is-moving');
	};

	const layout = () => {
		removeClone();
		const visible = list.querySelectorAll('li:not([hidden])');
		if (visible.length < 2) return;
		// Measure one row of the visible brands in ticker form (no wrapping).
		marquee.classList.add('is-moving');
		const one = list.scrollWidth;
		const fits = one <= marquee.clientWidth;
		marquee.classList.remove('is-moving');
		if (fits) return;

		clone = list.cloneNode(true);
		clone.classList.add('brands__list--clone');
		clone.removeAttribute('data-brands-list');
		clone.setAttribute('aria-hidden', 'true');
		clone.querySelectorAll('a, button').forEach((el) => el.setAttribute('tabindex', '-1'));
		track.appendChild(clone);
		marquee.style.setProperty('--brands-time', Math.max(12, Math.round(one / SPEED)) + 's');
		marquee.classList.add('is-moving');
	};

	const tabs = Array.from(root.querySelectorAll('[data-brands-tab]'));
	const choose = (slug) => {
		tabs.forEach((t) => t.setAttribute('aria-pressed', t.dataset.brandsTab === slug ? 'true' : 'false'));
		list.querySelectorAll('li').forEach((li) => {
			const cats = (li.dataset.cats || '').split(' ');
			li.hidden = !!slug && cats.indexOf(slug) === -1;
		});
		layout();
	};
	tabs.forEach((t) => t.addEventListener('click', () => choose(t.dataset.brandsTab)));

	let timer;
	window.addEventListener('resize', () => { clearTimeout(timer); timer = setTimeout(layout, 150); });
	// Logos change the widths when they load.
	window.addEventListener('load', layout);
	layout();
})();
