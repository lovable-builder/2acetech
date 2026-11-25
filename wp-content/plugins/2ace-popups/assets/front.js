/**
 * 2ACE Popups: decides in the browser which pop-up appears and when.
 * Page cache safe: the page is the same for everyone, the visitor-specific rules run here.
 */
(() => {
	'use strict';

	const cfg = window.acePopups;
	if (!cfg || !cfg.popups || !cfg.popups.length) return;

	const doc = document.documentElement;
	const t0 = Date.now();

	/* Storage that never throws ---------------------------------------- */
	const store = (kind) => ({
		get(k, d) { try { const v = window[kind].getItem(k); return v === null ? d : JSON.parse(v); } catch (e) { return d; } },
		set(k, v) { try { window[kind].setItem(k, JSON.stringify(v)); } catch (e) { /* private mode */ } },
	});
	const ls = store('localStorage');
	const ss = store('sessionStorage');
	const nowS = () => Math.floor(Date.now() / 1000);

	/* Visitor facts ------------------------------------------------------ */
	let visits = ls.get('acepu_vis', { n: 0 });
	if (!ss.get('acepu_s', false)) { ss.set('acepu_s', true); visits.n += 1; ls.set('acepu_vis', visits); }
	const isNew = visits.n <= 1;
	const pageviews = ss.get('acepu_pv', 0) + 1;
	ss.set('acepu_pv', pageviews);

	let source = ss.get('acepu_src', '');
	if (!source) {
		const q = new URLSearchParams(location.search);
		let ref = '';
		try { ref = document.referrer ? new URL(document.referrer).hostname : ''; } catch (e) { /* ignore */ }
		source = [q.get('utm_source'), q.get('utm_campaign'), q.get('utm_medium'), ref].filter(Boolean).join(' ').toLowerCase();
		ss.set('acepu_src', source || '-');
	}
	const device = () => (window.innerWidth <= 640 ? 'mobile' : window.innerWidth <= 1024 ? 'tablet' : 'desktop');
	const hasCart = () => /(?:^|;\s*)woocommerce_items_in_cart=[1-9]/.test(document.cookie);

	const state = (id) => ls.get('acepu_p' + id, { v: 0, l: 0, c: 0 });
	const saveState = (id, s) => ls.set('acepu_p' + id, s);

	/* Rules --------------------------------------------------------------- */
	const eligible = (p) => {
		if (cfg.preview) return true;
		const n = nowS();
		if (p.from && n < p.from) return false;
		if (p.to && n > p.to) return false;
		// Shop time (offset from the server), so "Mon to Fri, 9-17" means shop time.
		const shop = new Date((n + cfg.tzoff) * 1000);
		const dow = shop.getUTCDay() || 7;
		if (p.days.length && !p.days.includes(dow)) return false;
		const h = shop.getUTCHours();
		if (h < p.hf || h >= p.ht) return false;
		if (!p.dev.includes(device())) return false;
		if (p.vis === 'new' && !isNew) return false;
		if (p.vis === 'returning' && isNew) return false;
		if (p.cart === 'has' && !hasCart()) return false;
		if (p.cart === 'empty' && hasCart()) return false;
		if (p.utm && !p.utm.split(',').some((w) => w.trim() && source.includes(w.trim()))) return false;
		if (p.tr.length === 1 && p.tr[0] === 'click') return true; // Opened on request: no frequency limits.
		const s = state(p.id);
		if (p.conv && s.c) return false;
		if (p.max && s.v >= p.max) return false;
		if (p.freq === 'once' && s.v >= 1) return false;
		if (p.freq === 'days' && s.l && n - s.l < p.fd * 86400) return false;
		if (p.freq === 'session' && ss.get('acepu_shown_' + p.id, false)) return false;
		return true;
	};

	/* Sending events ------------------------------------------------------- */
	const send = (id, event) => {
		if (!cfg.stats) return;
		try {
			fetch(cfg.rest + 'event', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id, event }), keepalive: true });
		} catch (e) { /* statistics are best effort */ }
	};

	/* Showing ---------------------------------------------------------------- */
	let open = null; // The pop-up on screen.
	let queue = [];
	let flushT = 0;

	const focusables = (root) => Array.from(root.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]):not(.acepu__hp), select, textarea, [tabindex]:not([tabindex="-1"])')).filter((el) => el.offsetParent !== null);

	const buildTimer = (root) => {
		const el = root.querySelector('.acepu__timer');
		if (!el) return;
		const end = parseInt(el.dataset.end, 10);
		const set = (k, v) => { const n = el.querySelector('[data-t="' + k + '"]'); if (n) n.textContent = String(v).padStart(2, '0'); };
		const tick = () => {
			const left = end - nowS();
			if (left <= 0) { el.hidden = true; return false; }
			set('d', Math.floor(left / 86400)); set('h', Math.floor(left % 86400 / 3600)); set('m', Math.floor(left % 3600 / 60)); set('s', left % 60);
			return true;
		};
		if (tick()) {
			const iv = setInterval(() => { if (!tick() || !root.isConnected) clearInterval(iv); }, 1000);
		}
	};

	const close = (root, id, byUser = true) => {
		if (!root) return;
		root.classList.remove('is-open');
		doc.classList.remove('acepu-lock');
		if (byUser) { send(id, 'close'); }
		const back = root._back;
		setTimeout(() => { root.remove(); }, 450);
		open = null;
		if (back && back.focus) { try { back.focus({ preventScroll: true }); } catch (e) { /* gone */ } }
	};

	const convert = (id) => { const s = state(id); s.c = 1; saveState(id, s); };

	const wire = (root, p) => {
		root.addEventListener('click', (e) => {
			const t = e.target;
			if (t.closest('[data-acepu-close]')) {
				if (t.classList.contains('acepu__overlay') && root.dataset.clickClose !== '1') return;
				e.preventDefault();
				close(root, p.id);
				return;
			}
			const cp = t.closest('[data-acepu-copy]');
			if (cp) {
				const code = cp.dataset.acepuCopy || (root.querySelector('[data-acepu-code]') || {}).textContent || '';
				const done = () => { const old = cfg.i18n.copy; cp.textContent = cfg.i18n.copied; setTimeout(() => { cp.textContent = old; }, 1800); };
				if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(code).then(done, done); else done();
				send(p.id, 'copy'); convert(p.id);
				return;
			}
			if (t.closest('[data-cta]')) { send(p.id, 'cta'); convert(p.id); }
		});

		const form = root.querySelector('[data-acepu-form]');
		if (form) {
			form.addEventListener('submit', async (e) => {
				e.preventDefault();
				const err = form.querySelector('.acepu__err');
				const btn = form.querySelector('button[type="submit"]');
				const fail = (m) => { err.textContent = m; err.hidden = false; };
				err.hidden = true;
				const email = form.elements.email.value.trim();
				if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return fail(cfg.i18n.email);
				const c1 = form.elements.c1;
				if (c1 && !c1.checked) return fail(cfg.i18n.consent);
				const label = btn.textContent; btn.disabled = true; btn.textContent = cfg.i18n.busy;
				try {
					const res = await fetch(cfg.rest + 'signup', {
						method: 'POST', headers: { 'Content-Type': 'application/json' },
						body: JSON.stringify({ popup: p.id, email, c1: c1 && c1.checked ? '1' : '', c2: form.elements.c2 && form.elements.c2.checked ? '1' : '', website: form.elements.website.value }),
					});
					const j = await res.json();
					if (!res.ok || !j.ok) throw new Error(j.msg || cfg.i18n.error);
					const done = root.querySelector('.acepu__done');
					done.querySelector('[data-acepu-code]').textContent = j.code;
					done.querySelector('[data-acepu-copy]').dataset.acepuCopy = j.code;
					const a = done.querySelector('[data-acepu-apply]');
					a.href = j.apply + (a.dataset.to ? '&to=' + encodeURIComponent(a.dataset.to) : '');
					form.hidden = true; done.hidden = false;
					root.querySelectorAll('.acepu__title, .acepu__text, .acepu__no').forEach((n) => { if (n.classList.contains('acepu__no')) n.hidden = true; });
					convert(p.id);
					const f = focusables(done)[0]; if (f) f.focus();
				} catch (x) {
					fail(x.message || cfg.i18n.error);
					btn.disabled = false; btn.textContent = label;
				}
			});
		}

		root.addEventListener('keydown', (e) => {
			if (e.key === 'Escape') { e.stopPropagation(); close(root, p.id); return; }
			if (e.key !== 'Tab' || root.getAttribute('aria-modal') !== 'true') return;
			const f = focusables(root.querySelector('.acepu__box'));
			if (!f.length) return;
			const first = f[0]; const last = f[f.length - 1];
			if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
			else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
		});
	};

	const show = (p, force = false) => {
		if (open) return false;
		const tpl = document.getElementById('acepu-tpl-' + p.id);
		if (!tpl) return false;
		const root = tpl.content.firstElementChild.cloneNode(true);
		root._back = document.activeElement;
		document.body.appendChild(root);
		root.hidden = false;
		open = root;
		wire(root, p);
		buildTimer(root);
		requestAnimationFrame(() => requestAnimationFrame(() => root.classList.add('is-open')));
		const modal = root.getAttribute('aria-modal') === 'true';
		if (modal) doc.classList.add('acepu-lock');
		setTimeout(() => {
			const target = root.querySelector('input[type="email"]') || root.querySelector('.acepu__btn') || root.querySelector('.acepu__x');
			if (modal && target) { try { target.focus({ preventScroll: true }); } catch (e) { /* ignore */ } }
		}, 350);

		if (!force && !cfg.preview) {
			const s = state(p.id); s.v += 1; s.l = nowS(); saveState(p.id, s);
			ss.set('acepu_shown_' + p.id, true);
			ss.set('acepu_last', nowS());
			send(p.id, 'view');
		}
		return true;
	};

	/* Several triggers may fire together: the highest priority wins. */
	const request = (p) => {
		if (queue.indexOf(p) === -1) queue.push(p);
		clearTimeout(flushT);
		flushT = setTimeout(() => {
			const list = queue; queue = [];
			if (open) return;
			if (!cfg.preview && cfg.gap && nowS() - ss.get('acepu_last', 0) < cfg.gap) return;
			list.sort((a, b) => b.pr - a.pr);
			for (const c of list) { if (eligible(c) && show(c)) break; }
		}, 60);
	};

	/* Triggers --------------------------------------------------------------- */
	const arm = (p) => {
		let fired = false;
		const fire = () => { if (fired) return; fired = true; request(p); };
		if (p.img) { const im = new Image(); im.src = p.img; }

		if (cfg.preview) { setTimeout(() => show(p, true), 400); return; }

		p.tr.forEach((tr) => {
			if (tr === 'time') setTimeout(fire, Math.max(0, p.delay) * 1000);

			if (tr === 'scroll') {
				const on = () => {
					const max = doc.scrollHeight - window.innerHeight;
					if (max > 0 && window.scrollY / max * 100 >= p.sc) { window.removeEventListener('scroll', on); fire(); }
				};
				window.addEventListener('scroll', on, { passive: true });
			}

			if (tr === 'exit') {
				setTimeout(() => {
					document.addEventListener('mouseout', (e) => { if (!e.relatedTarget && e.clientY <= 0) fire(); });
					// Phones have no pointer to leave with: a quick scroll back to the top is the nearest signal.
					let last = window.scrollY; let lt = Date.now();
					window.addEventListener('scroll', () => {
						const y = window.scrollY; const t = Date.now();
						if (device() !== 'desktop' && last - y > window.innerHeight * 0.6 && t - lt < 500 && y < window.innerHeight) fire();
						last = y; lt = t;
					}, { passive: true });
				}, 3000);
			}

			if (tr === 'idle') {
				let iv;
				const reset = () => { clearTimeout(iv); iv = setTimeout(fire, p.idle * 1000); };
				['mousemove', 'keydown', 'scroll', 'touchstart', 'click'].forEach((ev) => window.addEventListener(ev, reset, { passive: true }));
				reset();
			}

			if (tr === 'pageviews' && pageviews >= p.pv) setTimeout(fire, 1500);

			if (tr === 'addtocart') {
				const onAdd = () => setTimeout(fire, 600);
				document.body.addEventListener('added_to_cart', onAdd);
				document.addEventListener('wc-blocks_added_to_cart', onAdd);
				if (window.jQuery) window.jQuery(document.body).on('added_to_cart', onAdd);
			}

			if (tr === 'click' && p.sel) {
				document.addEventListener('click', (e) => {
					const el = e.target.closest(p.sel);
					if (!el) return;
					if (el.tagName === 'A' && (el.getAttribute('href') || '').startsWith('#')) e.preventDefault();
					fired = false; fire();
				});
			}
		});
	};

	/* Wait until the cookie banner is out of the way. ------------------------- */
	const whenClear = (cb) => {
		if (!cfg.cookie || cfg.preview) { cb(); return; }
		const wait = () => {
			const b = document.getElementById('cookie-banner');
			if (!b || b.hidden) { cb(); return; }
			const h = () => { document.removeEventListener('ace:consent', h); setTimeout(cb, 900); };
			document.addEventListener('ace:consent', h);
		};
		if (document.readyState === 'complete') setTimeout(wait, 500); else window.addEventListener('load', () => setTimeout(wait, 500));
	};

	whenClear(() => {
		const todo = cfg.preview ? cfg.popups : cfg.popups.filter(eligible);
		todo.forEach(arm);
	});

	/* A link to #acepu-ID always opens that pop-up. -------------------------- */
	document.addEventListener('click', (e) => {
		const a = e.target.closest('a[href^="#acepu-"]');
		if (!a) return;
		const id = parseInt(a.getAttribute('href').slice(7), 10);
		const p = cfg.popups.find((x) => x.id === id);
		if (p) { e.preventDefault(); show(p, true); }
	});
})();
