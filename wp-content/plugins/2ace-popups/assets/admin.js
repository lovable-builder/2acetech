/**
 * 2ACE Popups: the designer. Tabs, live preview, colour pickers, media picker, AI helpers.
 */
(($) => {
	'use strict';

	const root = document.getElementById('acepu-designer');
	if (!root) return;
	const cfg = window.aceAdmin;
	const form = document.getElementById('post');
	const stage = root.querySelector('[data-stage]');
	const inner = root.querySelector('[data-stage-inner]');

	/* Tabs --------------------------------------------------------------- */
	const tabs = Array.from(root.querySelectorAll('.acepu-tab'));
	const panels = Array.from(root.querySelectorAll('.acepu-panel'));
	const openTab = (k) => {
		tabs.forEach((t) => t.classList.toggle('is-on', t.dataset.tab === k));
		panels.forEach((p) => p.classList.toggle('is-on', p.dataset.panel === k));
		try { sessionStorage.setItem('acepu_tab', k); } catch (e) { /* ignore */ }
	};
	tabs.forEach((t) => t.addEventListener('click', () => openTab(t.dataset.tab)));
	let first = 'content';
	try { first = sessionStorage.getItem('acepu_tab') || 'content'; } catch (e) { /* ignore */ }
	openTab(tabs.some((t) => t.dataset.tab === first) ? first : 'content');

	/* Show a field only when "key:value" holds ------------------------------- */
	const valueOf = (key) => {
		const els = root.querySelectorAll('[name="acepu[' + key + ']"], [name="acepu[' + key + '][]"]');
		if (!els.length) return [];
		if (els[0].tagName === 'SELECT') return [els[0].value];
		return Array.from(els).filter((e) => e.checked).map((e) => e.value);
	};
	const applyShow = () => {
		root.querySelectorAll('[data-show]').forEach((f) => {
			const [k, v] = f.dataset.show.split(':');
			f.hidden = !valueOf(k).includes(v);
		});
	};

	/* Live preview ------------------------------------------------------------ */
	let timer = 0; let req = 0;
	const scale = () => {
		const w = stage.clientWidth;
		const base = inner.classList.contains('is-phone') ? 420 : 1000;
		inner.style.width = base + 'px';
		const s = w / base;
		inner.style.setProperty('--s', s.toFixed(4));
		stage.style.height = Math.round(inner.offsetHeight * s) + 'px';
	};
	const refresh = () => {
		const fd = new FormData();
		fd.append('action', 'acepu_preview');
		fd.append('nonce', cfg.nonce);
		new FormData(form).forEach((v, k) => { if (k.startsWith('acepu[')) fd.append(k, v); });
		const my = ++req;
		fetch(cfg.ajax, { method: 'POST', body: fd, credentials: 'same-origin' })
			.then((r) => r.json())
			.then((j) => { if (my === req && j.success) { inner.innerHTML = j.data.html; scale(); } });
	};
	const later = () => { clearTimeout(timer); timer = setTimeout(refresh, 350); applyShow(); };
	root.addEventListener('input', later);
	root.addEventListener('change', later);
	window.addEventListener('resize', scale);

	root.querySelectorAll('[data-dev]').forEach((b) => b.addEventListener('click', () => {
		root.querySelectorAll('[data-dev]').forEach((x) => x.classList.toggle('is-on', x === b));
		inner.classList.toggle('is-phone', b.dataset.dev === '420');
		refresh();
	}));

	/* Colour pickers -------------------------------------------------------------- */
	$('.acepu-color').wpColorPicker({ change: later, clear: later });

	/* Media picker ------------------------------------------------------------------ */
	let frame;
	const setImage = (box, id, url) => {
		box.querySelector('input').value = id || '';
		box.querySelector('.acepu-media__thumb').innerHTML = url ? '<img src="' + url + '" alt="">' : '';
		later();
	};
	root.querySelectorAll('[data-media]').forEach((box) => {
		box.querySelector('[data-media-pick]').addEventListener('click', () => {
			frame = wp.media({ title: cfg.i18n.pick, button: { text: cfg.i18n.use }, multiple: false, library: { type: 'image' } });
			frame.on('select', () => {
				const a = frame.state().get('selection').first().toJSON();
				setImage(box, a.id, (a.sizes && a.sizes.thumbnail ? a.sizes.thumbnail.url : a.url));
			});
			frame.open();
		});
		box.querySelector('[data-media-clear]').addEventListener('click', () => setImage(box, 0, ''));
	});

	/* AI --------------------------------------------------------------------------------- */
	const status = root.querySelector('[data-ai-status]');
	const say = (m, err) => { if (status) { status.textContent = m; status.classList.toggle('is-err', !!err); } };
	const fieldsData = () => { const fd = new FormData(); new FormData(form).forEach((v, k) => { if (k.startsWith('acepu[')) fd.append(k, v); }); return fd; };
	const ai = (k) => root.querySelector('[data-ai="' + k + '"]');

	const post = (action, extra, btn) => {
		const fd = fieldsData();
		fd.append('action', action); fd.append('nonce', cfg.nonce);
		Object.keys(extra).forEach((k) => fd.append(k, extra[k]));
		btn.disabled = true; say(cfg.i18n.working);
		return fetch(cfg.ajax, { method: 'POST', body: fd, credentials: 'same-origin' })
			.then((r) => r.json())
			.finally(() => { btn.disabled = false; });
	};

	const setField = (key, val) => {
		const el = root.querySelector('[name="acepu[' + key + ']"]');
		if (!el || val === undefined || val === '') return;
		el.value = val;
		if (el.classList.contains('acepu-color')) $(el).wpColorPicker('color', val);
	};

	root.querySelectorAll('[data-ai-run]').forEach((btn) => btn.addEventListener('click', () => {
		if (btn.dataset.aiRun === 'text') {
			post('acepu_ai_text', { occasion: ai('occasion').value, brief: ai('brief').value, tone: ai('tone').value }, btn).then((j) => {
				if (!j.success) { say(j.data && j.data.msg ? j.data.msg : 'Error', true); return; }
				const f = j.data.fields;
				['eyebrow', 'headline', 'text', 'cta_label', 'dismiss_label', 'badge', 'footnote', 'bg', 'fg', 'accent', 'accent_fg'].forEach((k) => setField(k, f[k]));
				if (f.image_prompt) ai('prompt').value = f.image_prompt;
				later(); say(cfg.i18n.filled);
			}).catch(() => say('Error', true));
		} else {
			post('acepu_ai_image', { prompt: ai('prompt').value, aspect: ai('aspect').value }, btn).then((j) => {
				if (!j.success) { say(j.data && j.data.msg ? j.data.msg : 'Error', true); return; }
				const b = document.createElement('button');
				b.type = 'button'; b.innerHTML = '<img src="' + j.data.url + '" alt="">';
				b.addEventListener('click', () => {
					root.querySelectorAll('[data-ai-results] button').forEach((x) => x.classList.toggle('is-on', x === b));
					setImage(root.querySelector('[data-media]'), j.data.id, j.data.url);
				});
				root.querySelector('[data-ai-results]').prepend(b);
				b.click(); say(cfg.i18n.made);
			}).catch(() => say('Error', true));
		}
	}));

	applyShow();
	refresh();
	setTimeout(scale, 50);
})(jQuery);
