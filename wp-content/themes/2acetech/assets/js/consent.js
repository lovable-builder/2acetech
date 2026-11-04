/**
 * Cookie consent. The markup comes from inc/consent.php.
 *
 * The choice lives in the cookie "ace_consent" as version.analytics.marketing
 * (for example "1.0.0"). Scripts written as
 *   <script type="text/plain" data-ace-consent="analytics" src="...">
 * are started only after that category is allowed. window.aceConsent.has('x')
 * and the "ace:consent" event let other code follow the choice.
 */
(() => {
	'use strict';

	// wp_localize_script turns numbers into strings, so convert them back.
	const raw = window.aceConsentConfig || { cookie: 'ace_consent', version: 1, days: 180 };
	const cfg = { cookie: raw.cookie, version: Number(raw.version) || 1, days: Number(raw.days) || 180 };
	const banner = document.getElementById('cookie-banner');
	const modal = document.getElementById('cookie-modal');
	if (!banner || !modal) return;

	const panel = modal.querySelector('.cookie-modal__panel');
	const switches = Array.from(modal.querySelectorAll('input[data-category]'));
	const CATEGORIES = ['analytics', 'marketing'];

	/* Cookie ------------------------------------------------------------- */
	function read() {
		const m = document.cookie.match(new RegExp('(?:^|; )' + cfg.cookie + '=([^;]*)'));
		if (!m) return null;
		const p = decodeURIComponent(m[1]).split('.');
		if (p.length !== 3 || parseInt(p[0], 10) !== cfg.version) return null;
		return { analytics: p[1] === '1', marketing: p[2] === '1' };
	}

	function write(state) {
		const value = [cfg.version, state.analytics ? 1 : 0, state.marketing ? 1 : 0].join('.');
		const secure = location.protocol === 'https:' ? '; Secure' : '';
		document.cookie = cfg.cookie + '=' + value + '; Max-Age=' + cfg.days * 86400 + '; Path=/; SameSite=Lax' + secure;
	}

	/* Scripts that wait for consent -------------------------------------- */
	function activate(state) {
		document.querySelectorAll('script[type="text/plain"][data-ace-consent]').forEach((old) => {
			if (!state[old.dataset.aceConsent] || old.dataset.aceStarted) return;
			old.dataset.aceStarted = '1';
			const s = document.createElement('script');
			Array.from(old.attributes).forEach((a) => {
				if (a.name !== 'type' && a.name !== 'data-ace-consent') s.setAttribute(a.name, a.value);
			});
			s.textContent = old.textContent;
			old.parentNode.insertBefore(s, old.nextSibling);
		});
	}

	let current = read();

	window.aceConsent = {
		has: (category) => category === 'necessary' || !!(current && current[category]),
	};

	function announce() {
		document.dispatchEvent(new CustomEvent('ace:consent', { detail: current || { analytics: false, marketing: false } }));
	}

	/* Showing and hiding ------------------------------------------------- */
	let lastFocus = null;

	function openModal() {
		lastFocus = document.activeElement;
		const state = current || { analytics: false, marketing: false };
		switches.forEach((sw) => {
			if (!sw.disabled) sw.checked = !!state[sw.dataset.category];
		});
		banner.hidden = true;
		modal.hidden = false;
		document.documentElement.classList.add('cookie-lock');
		panel.focus();
	}

	function closeModal() {
		modal.hidden = true;
		document.documentElement.classList.remove('cookie-lock');
		if (!current) {
			banner.hidden = false; // No choice yet: back to the banner.
		} else if (lastFocus && document.contains(lastFocus)) {
			lastFocus.focus();
		}
	}

	function decide(state) {
		const before = current;
		current = state;
		write(state);
		banner.hidden = true;
		if (!modal.hidden) closeModal();
		activate(state);
		announce();
		// A script that already ran cannot be taken back: reload if something was withdrawn.
		const withdrawn = before && CATEGORIES.some((c) => before[c] && !state[c]);
		if (withdrawn) location.reload();
	}

	/* Events ------------------------------------------------------------- */
	document.addEventListener('click', (e) => {
		const open = e.target.closest('[data-cookie-open], a[href="#cookie-settings"]');
		if (open) {
			e.preventDefault();
			openModal();
			return;
		}

		const btn = e.target.closest('[data-cookie]');
		if (!btn) return;
		switch (btn.dataset.cookie) {
			case 'accept': decide({ analytics: true, marketing: true }); break;
			case 'reject': decide({ analytics: false, marketing: false }); break;
			case 'manage': openModal(); break;
			case 'close': closeModal(); break;
			case 'save': {
				const state = { analytics: false, marketing: false };
				switches.forEach((sw) => { if (!sw.disabled) state[sw.dataset.category] = sw.checked; });
				decide(state);
				break;
			}
		}
	});

	document.addEventListener('keydown', (e) => {
		if (modal.hidden) return;
		if (e.key === 'Escape') {
			closeModal();
			return;
		}
		if (e.key !== 'Tab') return;
		const items = Array.from(panel.querySelectorAll('button, a[href], input:not([disabled])')).filter((el) => el.offsetParent !== null);
		if (!items.length) return;
		const first = items[0];
		const last = items[items.length - 1];
		if (e.shiftKey && (document.activeElement === first || document.activeElement === panel)) {
			e.preventDefault();
			last.focus();
		} else if (!e.shiftKey && document.activeElement === last) {
			e.preventDefault();
			first.focus();
		}
	});

	/* Start -------------------------------------------------------------- */
	if (current) {
		activate(current);
		announce();
	} else {
		banner.hidden = false;
	}
})();
