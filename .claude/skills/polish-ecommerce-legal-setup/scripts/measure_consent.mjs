#!/usr/bin/env node
/**
 * Measure what a shop loads and stores BEFORE consent, AFTER "reject" and AFTER "accept".
 * Read-only: one anonymous visit with a fresh, throw-away Chrome profile. No login, no forms, no orders.
 *
 * Usage:
 *   node measure_consent.mjs https://shop.example/ [--chrome "/path/to/chrome"] [--wait 6000]
 *        [--reject '[data-cookie="reject"]'] [--accept '[data-cookie="accept"]'] [--json out.json]
 *
 * Output: for each phase (A first visit, B after reject + reload, C after accept + reload):
 *   - hosts contacted (request count), grouped; third-party hosts flagged
 *   - requests matching tracking patterns (google-analytics, googletagmanager, doubleclick, facebook, tiktok, hotjar, clarity, fonts.g*, stripe, hcaptcha, ...)
 *   - Google consent signals (gcs=, gcd=, npa= parameters of collect calls)
 *   - cookies set (name, domain, expiry in days, session or persistent)
 * The reject/accept selectors default to the 2acetech banner; pass your banner's selectors for another theme.
 *
 * Needs Node >= 22 (global WebSocket and fetch) and Google Chrome/Chromium. No npm packages.
 * Use the result to write/verify the cookie policy tables (references/facts-vs-claims-truth-table.md).
 */
import { spawn } from 'node:child_process';
import { mkdtempSync, rmSync, writeFileSync, existsSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const args = process.argv.slice(2);
if (!args.length || args[0].startsWith('-')) {
	console.error('usage: node measure_consent.mjs URL [--chrome PATH] [--wait MS] [--reject SELECTOR] [--accept SELECTOR] [--json FILE]');
	process.exit(2);
}
const url = args[0];
const opt = (name, def) => { const i = args.indexOf(name); return i >= 0 ? args[i + 1] : def; };
const wait = Number(opt('--wait', '6000'));
const rejectSel = opt('--reject', '[data-cookie="reject"]');
const acceptSel = opt('--accept', '[data-cookie="accept"]');
const jsonOut = opt('--json', '');
const candidates = [
	opt('--chrome', ''),
	'/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
	'/usr/bin/google-chrome', '/usr/bin/chromium', '/usr/bin/chromium-browser',
	'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
].filter(Boolean);
const chrome = candidates.find((p) => existsSync(p));
if (!chrome) { console.error('Chrome not found; pass --chrome PATH'); process.exit(2); }

const profile = mkdtempSync(join(tmpdir(), 'consent-measure-'));
const port = 9300 + Math.floor(Math.random() * 500);
const proc = spawn(chrome, [
	'--headless=new', `--remote-debugging-port=${port}`, `--user-data-dir=${profile}`,
	'--no-first-run', '--no-default-browser-check', '--disable-extensions', '--disable-sync', '--window-size=1280,900', 'about:blank',
], { stdio: 'ignore' });

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
async function connect() {
	for (let i = 0; i < 50; i++) {
		try {
			const list = await (await fetch(`http://127.0.0.1:${port}/json`)).json();
			const page = list.find((t) => t.type === 'page');
			if (page) return page.webSocketDebuggerUrl;
		} catch { /* not ready */ }
		await sleep(200);
	}
	throw new Error('could not reach Chrome DevTools');
}

const TRACK = /(google-analytics|googletagmanager|googleadservices|doubleclick|googlesyndication|google\.com\/(ccm|pagead)|facebook\.(com|net)|connect\.facebook|fbcdn|tiktok|hotjar|clarity\.ms|fonts\.googleapis|fonts\.gstatic|stripe\.com|stripecdn|hcaptcha|cloudflare|gravatar|youtube|vimeo|linkedin|snap\.licdn|pinterest|criteo|taboola|outbrain|hubspot|klaviyo|brevo|sendinblue|mailchimp|cdn-reach|hostinger)/i;

(async () => {
	const ws = new WebSocket(await connect());
	let id = 0; const pending = new Map(); const listeners = [];
	ws.onmessage = (ev) => {
		const m = JSON.parse(ev.data);
		if (m.id && pending.has(m.id)) { pending.get(m.id)(m); pending.delete(m.id); }
		else listeners.forEach((l) => l(m));
	};
	await new Promise((r) => (ws.onopen = r));
	const send = (method, params = {}) => new Promise((resolve) => { const i = ++id; pending.set(i, resolve); ws.send(JSON.stringify({ id: i, method, params })); });

	let requests = [];
	listeners.push((m) => { if (m.method === 'Network.requestWillBeSent') requests.push(m.params.request.url); });
	await send('Network.enable');
	await send('Page.enable');
	await send('Runtime.enable');

	const results = {};
	async function phase(name, action) {
		requests = [];
		await action();
		await sleep(wait);
		const cookies = (await send('Network.getAllCookies')).result.cookies;
		const hosts = {};
		for (const u of requests) { try { const h = new URL(u).host; hosts[h] = (hosts[h] || 0) + 1; } catch { /* data: urls */ } }
		const base = new URL(url).host.replace(/^www\./, '');
		results[name] = {
			hosts,
			third_party_hosts: Object.keys(hosts).filter((h) => !h.replace(/^www\./, '').endsWith(base)),
			tracking_requests: requests.filter((u) => TRACK.test(u)).map((u) => (u.length > 220 ? u.slice(0, 220) + '...' : u)),
			consent_signals: requests.filter((u) => /[?&](gcs|gcd|npa|dma)=/.test(u)).map((u) => (u.match(/[?&](gcs|gcd|npa|dma)=[^&]+/g) || []).join('')),
			cookies: cookies.map((c) => ({ name: c.name, domain: c.domain, lifetime: c.session ? 'session' : `${Math.round((c.expires - Date.now() / 1000) / 86400)}d` })),
		};
	}
	const evalJs = async (expr) => (await send('Runtime.evaluate', { expression: expr, awaitPromise: true, returnByValue: true })).result?.result?.value;
	const click = (sel) => evalJs(`(() => { const e = document.querySelector(${JSON.stringify(sel)}); if (!e) return 'NOT FOUND'; e.click(); return 'clicked'; })()`);

	await phase('A_first_visit_no_click', async () => { await send('Page.navigate', { url }); });
	await phase('B_after_reject_and_reload', async () => {
		results.reject_click = await click(rejectSel);
		await sleep(800);
		await send('Page.navigate', { url });
	});
	// phase C needs a fresh state: clear cookies and storage, then accept
	await send('Network.clearBrowserCookies');
	await send('Network.clearBrowserCache');
	await phase('C_after_accept_and_reload', async () => {
		await send('Page.navigate', { url });
		await sleep(wait);
		results.accept_click = await click(acceptSel);
		await sleep(800);
		await send('Page.navigate', { url });
	});

	for (const [name, r] of Object.entries(results)) {
		if (typeof r === 'string') { console.log(`${name}: ${r}`); continue; }
		console.log(`\n=== ${name}`);
		console.log('hosts:', Object.entries(r.hosts).sort((a, b) => b[1] - a[1]).map(([h, n]) => `${h}(${n})`).join(', '));
		console.log('third-party hosts:', r.third_party_hosts.join(', ') || '-');
		console.log('tracking-pattern requests:', r.tracking_requests.length ? '\n  ' + r.tracking_requests.join('\n  ') : '-');
		console.log('google consent signals:', r.consent_signals.length ? r.consent_signals.join(' | ') : '-');
		console.log('cookies:', r.cookies.length ? r.cookies.map((c) => `${c.name} [${c.domain}, ${c.lifetime}]`).join(', ') : '-');
	}
	if (jsonOut) writeFileSync(jsonOut, JSON.stringify(results, null, 2));
	ws.close();
	proc.kill();
	await sleep(700);
	try { rmSync(profile, { recursive: true, force: true, maxRetries: 8, retryDelay: 250 }); } catch { /* temp dir, harmless */ }
	process.exit(0);
})().catch((e) => { console.error('measure failed:', e.message); try { proc.kill(); } catch { /* */ } process.exit(1); });
