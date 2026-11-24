/**
 * My Account (logged out): switch between the login and the register form.
 * The page is marked by an inline script in <head> (class acct-toggle on <html>); this handles clicks,
 * the #register link and a register form that came back with an error (e-mail already filled in).
 */
(() => {
	'use strict';
	const root = document.documentElement;
	if (!root.classList.contains('acct-toggle')) return;

	const show = (register, updateUrl) => {
		root.classList.toggle('acct-register', register);
		if (updateUrl && window.history && history.replaceState) {
			history.replaceState(null, '', register ? '#register' : location.pathname + location.search);
		}
		const first = document.querySelector(register ? '.u-column2 input' : '.u-column1 input');
		if (first && updateUrl) first.focus({ preventScroll: true });
	};

	document.addEventListener('click', (e) => {
		const link = e.target.closest('[data-acct-switch]');
		if (!link) return;
		e.preventDefault();
		show(link.dataset.acctSwitch === 'register', true);
	});
	window.addEventListener('hashchange', () => show(location.hash === '#register', false));

	// A register attempt that failed comes back with the e-mail filled in: stay on that form.
	const email = document.querySelector('.woocommerce-form-register input[name="email"]');
	if (email && email.value) show(true, false);
})();
