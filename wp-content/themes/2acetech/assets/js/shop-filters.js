/**
 * Shop filter panel: applies a choice at once on desktop, slides in as a drawer on phones,
 * folds long option lists. Everything also works without this script (the form has a
 * "Show results" button).
 */
(() => {
	'use strict';

	const panel = document.querySelector('[data-filters]');
	const form = document.querySelector('[data-filters-form]');
	if (!panel || !form) return;

	const desktop = window.matchMedia('(min-width: 961px)');
	const doc = document.documentElement;
	const cfg = window.aceFilters || { more: 'Show more', less: 'Show less' };

	/* Drawer (phones) ---------------------------------------------------- */
	const setOpen = (open) => {
		doc.classList.toggle('filters-open', open);
		const opener = document.querySelector('[data-filters-open]');
		if (opener) opener.setAttribute('aria-expanded', String(open));
		if (open) {
			const first = panel.querySelector('summary, input, a');
			if (first) first.focus({ preventScroll: true });
		}
	};
	document.querySelectorAll('[data-filters-open]').forEach((b) => b.addEventListener('click', () => setOpen(true)));
	document.querySelectorAll('[data-filters-close]').forEach((b) => b.addEventListener('click', () => setOpen(false)));
	document.addEventListener('keydown', (e) => { if (e.key === 'Escape') setOpen(false); });
	desktop.addEventListener('change', () => setOpen(false));

	/* Apply -------------------------------------------------------------- */
	let t;
	const submit = () => {
		// Keep the address short: leave empty price boxes out.
		form.querySelectorAll('input[type="number"]').forEach((i) => { if (i.value === '') i.disabled = true; });
		form.submit();
	};
	form.addEventListener('submit', () => {
		form.querySelectorAll('input[type="number"]').forEach((i) => { if (i.value === '') i.disabled = true; });
	});
	form.addEventListener('change', (e) => {
		if (!desktop.matches) return; // On phones the visitor picks several, then taps "Show results".
		clearTimeout(t);
		t = setTimeout(submit, e.target.type === 'number' ? 600 : 250);
	});

	/* Long lists: show the first six, fold the rest ------------------------ */
	const LIMIT = 6;
	panel.querySelectorAll('ul[data-more]').forEach((ul) => {
		const items = Array.from(ul.children);
		if (items.length <= LIMIT + 1) return;
		const hasChecked = items.slice(LIMIT).some((li) => li.querySelector('input:checked'));
		const btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'sf-more';
		const apply = (open) => {
			items.forEach((li, i) => { li.hidden = !open && i >= LIMIT; });
			btn.textContent = open ? cfg.less : cfg.more;
			btn.setAttribute('aria-expanded', String(open));
		};
		let open = hasChecked;
		apply(open);
		btn.addEventListener('click', () => { open = !open; apply(open); });
		ul.after(btn);
	});
})();
