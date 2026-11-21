#!/usr/bin/env python3
"""Render the parameterised Polish legal templates for one company.

Usage:
    python3 -I render_legal.py --vars company.json --templates ../templates --out OUTDIR
                               [--features reviews=true,newsletter=false,...]
                               [--only regulamin-sklepu,reklamacje] [--no-lint] [--list]
    python3 -I render_legal.py --selftest 200 --vars company.json --templates ../templates

company.json:
    { "variables": { "COMPANY_NAME_FULL": "...", ... },
      "features":  { "reviews": true, "newsletter": false, ... } }

Template language (all markers are HTML comments, so a template is still valid HTML):
    {{NAME}}                 variable; value is HTML-escaped unless NAME ends in _HTML
    <!--IF:expr-->..<!--ELSE-->..<!--ENDIF-->      nestable; expr = feature names with
                             !  (not),  &  (and),  |  (or), no parentheses; "a&b|c" = (a&b)|c
    <li><!--N:name-->        names a numbered list item; {{N:name}} prints its number
                             (counted over the final, rendered list)
    <ol start="auto">        continues the numbering of the previous top-level <ol> in the same <h2> section
    <tr><td><!--R:name-->    names a table row; {{R:name}} prints its row number in its tbody
    <h3><!--H:name-->        names a sub-heading; {{H:name}} prints "<section>.<n>" (e.g. 4.3);
                             sections = <h2> elements counted from 1 in document order

The script fails with the list of variables that are needed but missing (only
variables that appear in blocks that survive the feature switches are needed),
then runs scripts/lint_legal.py on the output.
Standard library only.
"""
import html
import importlib.util
import json
import os
import random
import re
import sys

sys.dont_write_bytecode = True

HERE = os.path.dirname(os.path.abspath(__file__))

# --------------------------------------------------------------------------
# Registry of features (switches). Default is always False.
# --------------------------------------------------------------------------
FEATURES = {
    "dropshipping": "Goods ship from a supplier's warehouse, not from the seller's own stock",
    "free_delivery": "Delivery is free (0 zł) for every order",
    "accounts": "Customers can create an account",
    "guest_checkout": "An order can be placed without an account (only meaningful with accounts=true)",
    "phone_optional": "The phone field at checkout is optional",
    "reviews": "Product reviews are switched on (user-generated content)",
    "review_verified_label": "Reviews by buyers get an automatic 'verified buyer' label",
    "review_remember_cookie": "The review form offers 'remember my details' (WordPress comment cookies)",
    "newsletter": "A newsletter exists",
    "newsletter_double_optin": "The newsletter uses double opt-in (confirmation e-mail)",
    "newsletter_provider_outside_eea": "The newsletter tool processes data outside the EEA",
    "contact_form": "A contact form on the site sends messages to the shop",
    "abandoned_cart": "An abandoned-cart reminder tool is active",
    "express_checkout": "Apple Pay / Google Pay (or similar) buttons order outside the normal checkout button",
    "payment_cards": "Card payments are accepted",
    "payment_stripe": "Payments are processed by Stripe (uses the Stripe-specific text and cookies)",
    "payment_provider_transfers": "A non-Stripe payment provider transfers data outside the EEA",
    "other_consent_tools": "Other analytics/marketing tools exist (described in the EXTRA_*_HTML variables)",
    "ga4": "Google Analytics 4 is loaded (consent-gated)",
    "google_ads": "Google Ads / remarketing tag is loaded (consent-gated)",
    "google_consent_mode_pings": "Google tags load before the visitor chooses and send cookieless 'denied' pings (Consent Mode default)",
    "google_fonts_remote": "The browser still fetches fonts from Google before consent",
    "order_attribution": "WooCommerce Order Attribution (sbjs_* cookies) is on",
    "hostinger_reach_script": "Hostinger Reach / cdn-reach script loads on every page",
    "cookie_hcdn": "A host/CDN cookie 'hcdn' is set on the shop's own domain",
    "cookie_cf_bm": "The anti-bot cookie __cf_bm is set by a third-party script",
    "no_other_trackers": "Measured: no other advertising tools or pixels load (the policy says so)",
    "share_links": "Product share buttons exist as plain links",
    "withdrawal_ip_hash": "The withdrawal form stores a 1-hour IP hash for rate limiting",
    "dpo_appointed": "A data protection officer is appointed",
    "weee": "The shop sells electrical/electronic equipment or batteries (take-back text)",
    "digital_goods": "Goods with digital elements (app-controlled etc.) are sold",
    "own_guarantee": "The seller gives its own guarantee",
    "return_cost_customer": "The consumer pays the direct cost of returning goods (statutory default)",
    "bulky_goods": "Some goods cannot be returned by ordinary post (estimated maximum return cost is stated)",
    "return_address_third_party": "The return address belongs to a third party authorised to receive returns",
    "withdrawal_exceptions": "Some goods are excluded from withdrawal (art. 38 UPK)",
    "adr_participation": "The seller commits to out-of-court dispute resolution before a named body",
    "complaints_same_as_seat": "The complaints address equals the registered seat",
    "name_suppliers_in_privacy": "The privacy policy names the supplier(s) as recipients",
    "supplier_outside_eea": "A supplier or marketplace processes customer data outside the EEA",
    "hosting_transfers_outside_eea": "The hosting provider may process data outside the EEA",
    "vat_exempt": "The seller is VAT-exempt (art. 113 VAT Act); prices are not 'brutto including VAT'",
}

# --------------------------------------------------------------------------
# Registry of variables: NAME -> dict(req=<expr or 'always' or None>, default=<VAR or literal 'lit:...'>, doc=...)
# req is evaluated against the features: the variable is only required when used in a
# surviving block, so 'req' here is documentation for --list; the real check is usage-based.
# --------------------------------------------------------------------------
V = {}


def var(name, doc, default=None, check=None):
    V[name] = {"doc": doc, "default": default, "check": check}


var("COMPANY_NAME_FULL", "Full legal name exactly as in KRS, e.g. 'EXAMPLE SPÓŁKA Z OGRANICZONĄ ODPOWIEDZIALNOŚCIĄ'")
var("COMPANY_NAME_SHORT", "Short name used in running text, e.g. 'Example sp. z o.o.'")
var("SHOP_URL", "Shop address without trailing slash, e.g. 'https://example.pl'")
var("SEAT_CITY_LOC", "City of the seat in the locative case ('z siedzibą w ...'), e.g. 'Warszawie'")
var("SEAT_ADDRESS", "Registered seat: 'ul. Przykładowa 1, lok. 2, 00-001 Warszawa'")
var("SHOP_DOMAIN", "Shop domain without scheme (derived from SHOP_URL when empty)")
var("CORRESPONDENCE_ADDRESS", "Address for withdrawal letters and the model form", default="SEAT_ADDRESS")
var("COMPLAINTS_ADDRESS", "Address for written complaints", default="SEAT_ADDRESS")
var("RETURN_ADDRESS", "Address where consumers send returned goods")
var("RETURN_ADDRESS_OPERATOR", "Name of the third party that runs the return address (only with return_address_third_party)")
var("KRS", "10-digit KRS number")
var("NIP", "10-digit NIP (checksum is verified)")
var("REGON", "9- or 14-digit REGON (checksum is verified)")
var("SHARE_CAPITAL", "Share capital as written in KRS, e.g. '5 000 zł'")
var("REGISTRY_COURT", "Registry court and division exactly as in the KRS extract")
var("REPRESENTATION_TEXT", "How the company is represented, e.g. 'do składania oświadczeń w imieniu spółki upoważniony jest każdy z członków zarządu samodzielnie'")
var("SHOP_EMAIL", "Shop e-mail address (must be monitored)")
var("SHOP_PHONE", "Shop phone, '+48 123 456 789'")
var("VERSION_DATE", "Date of this version, '20 listopada 2026 r.'")
var("EFFECTIVE_DATE", "Date the Regulamin takes effect, '20 listopada 2026 r.'", default="VERSION_DATE")
var("UPK_TEXT_REF", "Current consolidated-text reference of the consumer rights act, e.g. 'Dz.U. 2026 poz. 1244' (re-verify on ISAP)")
var("WITHDRAWAL_PATH", "Path of the withdrawal-button page", default="lit:/odstap-od-umowy/")
var("CATALOG_CATEGORIES_TEXT", "Product categories in one phrase for the 'no bulky goods / no exceptions' sentences")
var("WAREHOUSE_COUNTRY_LOC", "Country of the dispatch warehouse in the locative, e.g. 'Polsce'", default="lit:Polsce")
var("DELIVERY_METHOD_LABEL_REG", "Delivery method label in the Regulamin table", default="lit:Dostawa kurierem na adres w Polsce")
var("DELIVERY_METHOD_LABEL_PAGE", "Delivery method label on the delivery page", default="DELIVERY_METHOD_LABEL_REG")
var("DELIVERY_PRICE_TEXT", "Delivery price cell, e.g. '0 zł (bezpłatnie)' or '14,99 zł'")
var("DISPATCH_DAYS_MAX", "Binding dispatch time in working days (a number)")
var("TRANSIT_DAYS", "Expected carrier transit time in working days, e.g. '2–5'")
var("TOTAL_DAYS_MAX", "Usual total in working days (a number)")
var("DELIVERY_OUTER_LIMIT_DAYS", "Outer delivery limit in days if no other date is given", default="lit:30")
var("PAYMENT_METHODS_LIST", "Enabled payment methods as a phrase, e.g. 'karta płatnicza, Apple Pay i Google Pay'")
var("PAYMENT_METHODS_LIST_PAGE", "Same for the delivery page (wording may differ)", default="PAYMENT_METHODS_LIST")
var("PAYMENT_METHODS_INSTR", "Instrumental form for 'płatności ...', e.g. 'kartą, Apple Pay i Google Pay'", default="PAYMENT_METHODS_LIST")
var("PAYMENT_METHODS_PARENS", "Short list for refunds, e.g. 'karta, Apple Pay, Google Pay'", default="PAYMENT_METHODS_LIST")
var("PAYMENT_PROVIDER_NAME", "Payment provider brand, e.g. 'Stripe' or 'PayU'")
var("PAYMENT_PROVIDER_ENTITY", "Legal entity of the payment provider you contract with (non-Stripe)")
var("PAYMENT_PROVIDER_COUNTRY", "Country of that entity in Polish, e.g. 'Polska'")
var("PAYMENT_PROVIDER_PRIVACY_URL", "Provider privacy-policy URL")
var("PAYMENT_PROVIDER_ROLE_TEXT", "One sentence: processor and/or controller role of the provider (non-Stripe)")
var("PAYMENT_PROVIDER_TRANSFER_TEXT", "One sentence on transfers outside the EEA by the provider and the safeguard")
var("EXPRESS_WALLETS_TEXT", "Express buttons named, e.g. 'Apple Pay lub Google Pay'")
var("STRIPE_CONTRACT_ENTITY", "Stripe entity in your Services Agreement, e.g. 'Stripe Payments Europe, Limited' (verify in the Stripe dashboard)")
var("STRIPE_SERVICES_ENTITY", "Stripe payment-services entity, e.g. 'Stripe Technology Europe, Limited' (verify on stripe.com/privacy)")
var("STRIPE_US_ENTITY", "Stripe US importer, e.g. 'Stripe, LLC' (verify on stripe.com/legal/dpa)")
var("STRIPE_COUNTRY", "Country of the EEA Stripe entity in Polish, e.g. 'Irlandia'")
var("HOSTING_PROVIDER_NAME", "Hosting company entity per your contract, e.g. 'Hostinger International Ltd.'")
var("HOSTING_PROVIDER_COUNTRY", "Country of that entity in Polish, e.g. 'Cypr'")
var("HOSTING_GROUP_NAME", "Group brand used in running text, e.g. 'Hostinger'")
var("HOSTING_PRIVACY_URL", "Hosting provider privacy-policy URL")
var("HOSTING_TRANSFER_TEXT", "One sentence on where the host may process data outside the EEA and the safeguard")
var("HOSTING_CDN_SCRIPT_HOST", "Host name of the provider script measured on the site, e.g. 'cdn-reach.hostinger.com'")
var("GOOGLE_EU_ENTITY", "Google contracting entity, e.g. 'Google Ireland Limited' (verify in your Google account terms)", default="lit:Google Ireland Limited")
var("GOOGLE_US_ENTITY", "Google US entity for transfers, e.g. 'Google LLC'", default="lit:Google LLC")
var("GA4_ID_SUFFIX", "Suffix of the GA4 measurement ID as seen in the cookie name _ga_XXXXXXXX")
var("REMOTE_FONT_NAME", "Font family still fetched from Google, e.g. 'DM Sans'")
var("CONSENT_COOKIE_NAME", "Name of your consent cookie, e.g. 'ace_consent'")
var("CONSENT_COOKIE_DAYS", "Lifetime of the consent cookie in days, e.g. '180'")
var("CORRESPONDENCE_RETENTION_MONTHS", "Months e-mail correspondence is kept after the last contact, e.g. '12'", default="lit:12")
var("DPO_CONTACT", "Data protection officer contact (only with dpo_appointed)")
var("SUPPLIER_NAME", "Supplier legal name (only with name_suppliers_in_privacy)")
var("SUPPLIER_ADDRESS", "Supplier address (only with name_suppliers_in_privacy)")
var("SUPPLIER_ID_TEXT", "Supplier KRS/NIP in one phrase, e.g. 'KRS 0000000000, NIP 0000000000'")
var("SUPPLIER_DATA_ROLE_TEXT", "One sentence: the supplier's role (separate controller / processor under an art. 28 agreement)")
var("SUPPLIER_TRANSFER_TEXT", "One sentence on the supplier/marketplace transfer outside the EEA and safeguard")
var("NEWSLETTER_PROVIDER_NAME", "Newsletter tool provider and entity")
var("NEWSLETTER_PROVIDER_TRANSFER_TEXT", "One sentence on the newsletter provider transfer outside the EEA")
var("ABANDONED_CART_TOOL", "Name of the abandoned-cart tool")
var("ABANDONED_CART_RETENTION", "Retention of abandoned-cart data, e.g. 'do 30 dni od ostatniej aktywności'")
var("OWN_GUARANTEE_TEXT", "Text of the seller's own guarantee statement (art. 577¹ KC content)")
var("RETURN_COST_SELLER_MECHANISM", "How the seller bears return costs (label sent / documented cost refunded)")
var("BULKY_GOODS_TEXT", "Goods that cannot be returned by ordinary post")
var("BULKY_RETURN_COST_ESTIMATE", "Estimated maximum return cost for them, e.g. 'do 120 zł brutto'")
var("WITHDRAWAL_EXCLUDED_GOODS_TEXT", "Goods excluded from withdrawal and the art. 38 UPK ground")
var("ADR_BODY_NAME", "Name of the ADR body the seller commits to")
var("ADR_BODY_ADDRESS", "Address of that body")
var("ADR_BODY_URL", "Website of that body")
var("WEEE_EXAMPLES", "Examples of electrical goods in the shop, e.g. 'lampy, oświetlenie LED, zabawki elektroniczne'")
var("DIGITAL_GOODS_EXAMPLE", "Example of goods with digital elements, e.g. 'oświetlenie sterowane z telefonu'")
var("EXTRA_NECESSARY_COOKIES_HTML", "Extra <tr> rows (4 cells) for the necessary-cookie table", default="lit:")
var("EXTRA_ANALYTICS_COOKIES_HTML", "Extra <tr> rows for the analytics table", default="lit:")
var("EXTRA_MARKETING_COOKIES_HTML", "Extra <tr> rows for the marketing table", default="lit:")
var("EXTRA_PRIVACY_ROWS_HTML", "Extra <tr> rows (4 cells) for the privacy table", default="lit:")
var("EXTRA_RECIPIENTS_HTML", "Extra <li> items for the recipients list", default="lit:")
var("EXTRA_TRANSFERS_HTML", "Extra <li> items for the third-country list", default="lit:")
var("EXTRA_THIRD_PARTY_CONNECTIONS_HTML", "Extra <li> items for the cookie-policy 'other connections' list", default="lit:")

# Derived switches (computed from the features).
DERIVED = {
    "consent_tools": lambda f: f["ga4"] or f["google_ads"] or f["other_consent_tools"],
    "gcm_or_fonts": lambda f: f["google_consent_mode_pings"] or f["google_fonts_remote"],
    "accounts_or_reviews": lambda f: f["accounts"] or f["reviews"],
    "any_third_country": lambda f: (f["payment_stripe"] or f["payment_provider_transfers"] or f["ga4"] or f["google_ads"]
                                    or f["hosting_transfers_outside_eea"] or f["newsletter_provider_outside_eea"] or f["supplier_outside_eea"]),
}

MARKER_RE = re.compile(r"<!--\s*(IF|ELSE|ENDIF)(?::([^>]*?))?\s*-->")
VAR_RE = re.compile(r"\{\{([A-Z][A-Z0-9_]*)\}\}")
NUM_TOKEN_RE = re.compile(r"\{\{([NRHS]):([a-z0-9_]+)\}\}")
X_TOKEN_RE = re.compile(r"\{\{X:([a-z0-9\-]+):([NRHS]):([a-z0-9_]+)\}\}")


class RenderError(Exception):
    pass


def truthy_expr(expr, feats):
    expr = expr.strip()
    result = False
    for term in expr.split("|"):
        ok = True
        for atom in term.split("&"):
            atom = atom.strip()
            neg = atom.startswith("!")
            name = atom[1:].strip() if neg else atom
            if name not in feats:
                raise RenderError("unknown feature '%s' in template expression '%s'" % (name, expr))
            v = bool(feats[name])
            ok = ok and ((not v) if neg else v)
        result = result or ok
    return result


def eval_ifs(text, feats, doc):
    out = []
    pos = 0
    stack = []  # entries: [parent_active, cond_value, in_else]
    active = True
    for m in MARKER_RE.finditer(text):
        if active:
            out.append(text[pos:m.start()])
        pos = m.end()
        kind, expr = m.group(1), m.group(2)
        if kind == "IF":
            val = truthy_expr(expr or "", feats) if active else False
            stack.append([active, val, False])
            active = active and val
        elif kind == "ELSE":
            if not stack:
                raise RenderError("%s: <!--ELSE--> without <!--IF-->" % doc)
            parent, val, _ = stack[-1]
            stack[-1][2] = True
            active = parent and (not val)
        else:
            if not stack:
                raise RenderError("%s: <!--ENDIF--> without <!--IF-->" % doc)
            parent, _, _ = stack.pop()
            active = parent
    if stack:
        raise RenderError("%s: %d unclosed <!--IF-->" % (doc, len(stack)))
    if active:
        out.append(text[pos:])
    return "".join(out)


def substitute_vars(text, values, doc, missing):
    def repl(m):
        name = m.group(1)
        if name not in values or values[name] is None or values[name] == "":
            if name in V and V[name]["default"] == "lit:":
                return ""
            missing.setdefault(name, set()).add(doc)
            return m.group(0)
        val = values[name]
        return val if name.endswith("_HTML") else html.escape(val, quote=False)
    return VAR_RE.sub(repl, text)


def resolve_numbering(text, doc):
    """Handle <ol start="auto">, <!--N:x--> and <!--H:x-->; returns (text, names)."""
    tag_re = re.compile(r"<(/?)(ol|ul|li|h2|h3)((?:\s[^>]*)?)>|<!--\s*([NHS]):([a-z0-9_]+)\s*-->")
    names = {"N": {}, "H": {}, "S": {}}
    out = []
    pos = 0
    stack = []  # [tag, counter, depth] for ol/ul
    section = 0
    sub = 0
    section_last = {}  # (section, depth) -> last number of the previous top-level ol
    last_li_number = None
    for m in tag_re.finditer(text):
        out.append(text[pos:m.start()])
        pos = m.end()
        if m.group(4):
            kind, name = m.group(4), m.group(5)
            if kind == "N":
                if last_li_number is None:
                    raise RenderError("%s: <!--N:%s--> outside a list item" % (doc, name))
                names["N"][name] = last_li_number
            elif kind == "S":
                names["S"][name] = section
            else:
                names["H"][name] = "%d.%d" % (section, sub)
            continue
        closing, tag, attrs = m.group(1), m.group(2), m.group(3) or ""
        if tag == "h2" and not closing:
            section += 1
            sub = 0
        elif tag == "h3" and not closing:
            sub += 1
        elif tag in ("ol", "ul"):
            if not closing:
                depth = len(stack)
                start = None
                sm = re.search(r'start="([^"]*)"', attrs)
                if tag == "ol" and sm:
                    if sm.group(1) == "auto":
                        start = section_last.get((section, depth), 0) + 1
                        attrs = attrs.replace('start="auto"', 'start="%d"' % start)
                    else:
                        start = int(sm.group(1))
                stack.append([tag, (start - 1) if start else 0, depth])
                out.append("<%s%s>" % (tag, attrs))
                continue
            elif stack and stack[-1][0] == tag:
                t, counter, depth = stack.pop()
                if t == "ol":
                    section_last[(section, depth)] = counter
        elif tag == "li" and not closing:
            if stack and stack[-1][0] == "ol":
                stack[-1][1] += 1
                last_li_number = stack[-1][1]
        out.append(m.group(0))
    out.append(text[pos:])
    return "".join(out), names


def resolve_rows(text, doc):
    """Row numbers per table, counting only <tbody> rows; returns text without R markers and the map."""
    names = {}
    out = []
    pos = 0
    in_tbody = False
    count = 0
    for m in re.finditer(r"<(/?)(tbody|tr)(?:\s[^>]*)?>|<!--\s*R:([a-z0-9_]+)\s*-->|<table(?:\s[^>]*)?>", text):
        out.append(text[pos:m.start()])
        pos = m.end()
        s = m.group(0)
        if m.group(3):
            if count == 0:
                raise RenderError("%s: <!--R:%s--> outside a tbody row" % (doc, m.group(3)))
            names[m.group(3)] = count
            continue
        if s.startswith("<table"):
            count = 0
            in_tbody = False
        elif m.group(2) == "tbody":
            in_tbody = not m.group(1)
        elif m.group(2) == "tr" and not m.group(1) and in_tbody:
            count += 1
        out.append(s)
    out.append(text[pos:])
    return "".join(out), names


def number_pass(text, doc):
    text, rows = resolve_rows(text, doc)
    text, names = resolve_numbering(text, doc)
    names["R"] = rows

    def repl(m):
        kind, name = m.group(1), m.group(2)
        if name not in names[kind]:
            raise RenderError("%s: {{%s:%s}} refers to a marker that is not in the rendered text (is its block switched off?)" % (doc, kind, name))
        return str(names[kind][name])
    return NUM_TOKEN_RE.sub(repl, text), names


def load_vars(path):
    with open(path, encoding="utf-8") as fh:
        data = json.load(fh)
    variables = data.get("variables", {})
    features = data.get("features", {})
    for k, v in variables.items():
        if not isinstance(v, str):
            raise RenderError("variable %s must be a string (got %s)" % (k, type(v).__name__))
    return variables, features


def build_values(variables):
    values = dict(variables)
    if not values.get("SHOP_DOMAIN") and values.get("SHOP_URL"):
        values["SHOP_DOMAIN"] = re.sub(r"^https?://", "", values["SHOP_URL"]).rstrip("/")
    for name, spec in V.items():
        if values.get(name) in (None, ""):
            d = spec["default"]
            if d is None:
                continue
            if d.startswith("lit:"):
                values[name] = d[4:]
            else:
                values[name] = values.get(d, "")
    return values


def build_features(raw, overrides, values=None):
    feats = {k: False for k in FEATURES}
    warnings = []
    for k, v in raw.items():
        if k.startswith("_"):
            continue
        if k not in FEATURES:
            warnings.append("unknown feature '%s' in vars file (typo?)" % k)
            continue
        feats[k] = bool(v)
    for k, v in (overrides or {}).items():
        if k not in FEATURES:
            raise RenderError("unknown feature '%s' on the command line" % k)
        feats[k] = v
    values = values or {}
    feats["has_extra_analytics"] = bool((values.get("EXTRA_ANALYTICS_COOKIES_HTML") or "").strip())
    feats["has_extra_marketing"] = bool((values.get("EXTRA_MARKETING_COOKIES_HTML") or "").strip())
    for k, fn in DERIVED.items():
        feats[k] = bool(fn(feats))
    if feats["other_consent_tools"] and not (feats["has_extra_analytics"] or feats["has_extra_marketing"]):
        warnings.append("other_consent_tools=true but EXTRA_ANALYTICS_COOKIES_HTML / EXTRA_MARKETING_COOKIES_HTML are empty: describe those tools' cookies or switch the flag off")
    return feats, warnings


def risky_feature_warnings(feats):
    w = []
    if feats["vat_exempt"]:
        w.append("vat_exempt=true: VAT/invoice wording is only partly parameterised; accountant and lawyer must review prices and invoice sentences")
    if feats["adr_participation"]:
        w.append("adr_participation=true: the variant text is NOT in the 2ACE-published master; lawyer must confirm the ADR Act wording")
    if feats["own_guarantee"]:
        w.append("own_guarantee=true: the guarantee statement must meet art. 577(1) para. 2 KC and be handed over with the goods (art. 577(3) KC)")
    if feats["bulky_goods"]:
        w.append("bulky_goods=true: also check Annex 1 wording (art. 12 ust. 1 pkt 10 UPK) with a lawyer")
    if feats["newsletter"] or feats["abandoned_cart"] or feats["contact_form"]:
        w.append("newsletter / abandoned_cart / contact_form blocks were authored 2026-11-21 and are not part of the published 2ACE texts: lawyer review required")
    if not feats["free_delivery"]:
        w.append("free_delivery=false: paid-delivery wording was authored 2026-11-21 and is not part of the published 2ACE texts")
    if not feats["return_cost_customer"]:
        w.append("return_cost_customer=false: seller-pays-returns wording is a variant authored 2026-11-21, not in the 2ACE master")
    if not feats["dropshipping"]:
        w.append("dropshipping=false: own-warehouse wording is a variant authored 2026-11-21, not in the 2ACE master")
    return w


def import_lint():
    spec = importlib.util.spec_from_file_location("lint_legal", os.path.join(HERE, "lint_legal.py"))
    mod = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(mod)
    return mod


def render_all(templates_dir, values, feats, only=None):
    outputs = {}
    all_names = {}
    missing = {}
    names = sorted(f[:-5] for f in os.listdir(templates_dir) if f.endswith(".html"))
    if only:
        names = [n for n in names if n in only]
    for name in names:
        with open(os.path.join(templates_dir, name + ".html"), encoding="utf-8") as fh:
            text = fh.read()
        text = eval_ifs(text, feats, name)
        text = substitute_vars(text, values, name, missing)
        text, nm = number_pass(text, name)
        outputs[name] = text
        all_names[name] = nm
    # cross-document references {{X:doc:K:name}}
    for name in list(outputs):
        def xrepl(m, _doc=name):
            d, k, n = m.group(1), m.group(2), m.group(3)
            if d not in all_names or n not in all_names[d].get(k, {}):
                raise RenderError("%s: {{X:%s:%s:%s}} cannot be resolved (document or marker missing in this rendering)" % (_doc, d, k, n))
            return str(all_names[d][k][n])
        outputs[name] = X_TOKEN_RE.sub(xrepl, outputs[name])
    return outputs, missing


def parse_features_arg(s):
    out = {}
    if not s:
        return out
    for part in s.split(","):
        if not part.strip():
            continue
        if "=" not in part:
            raise RenderError("--features expects name=true|false pairs, got '%s'" % part)
        k, v = part.split("=", 1)
        out[k.strip()] = v.strip().lower() in ("1", "true", "yes", "on")
    return out


def selftest(templates_dir, vars_path, n):
    """Render with random feature combinations and lint every result. Needs a vars file with
    every variable filled (company.example.json)."""
    lint = import_lint()
    variables, raw = load_vars(vars_path)
    values = build_values(variables)
    rnd = random.Random(20261121)
    failures = 0
    combos = [{k: False for k in FEATURES}, {k: True for k in FEATURES}]
    for _ in range(n):
        combos.append({k: rnd.random() < 0.5 for k in FEATURES})
    tmp = os.path.join(os.environ.get("TMPDIR", "/tmp"), "render_selftest_%d" % os.getpid())
    os.makedirs(tmp, exist_ok=True)
    for i, combo in enumerate(combos):
        feats = {k: combo[k] for k in FEATURES}
        row = '<tr>\n<td>x_*</td>\n<td>Przykładowa firma</td>\n<td>przykład</td>\n<td>1 dzień</td>\n</tr>\n'
        vals = dict(values)
        vals["EXTRA_ANALYTICS_COOKIES_HTML"] = row if rnd.random() < 0.5 else ""
        vals["EXTRA_MARKETING_COOKIES_HTML"] = row if rnd.random() < 0.5 else ""
        feats["has_extra_analytics"] = bool(vals["EXTRA_ANALYTICS_COOKIES_HTML"])
        feats["has_extra_marketing"] = bool(vals["EXTRA_MARKETING_COOKIES_HTML"])
        if feats["other_consent_tools"] and not (feats["has_extra_analytics"] or feats["has_extra_marketing"]):
            feats["other_consent_tools"] = False
        for k, fn in DERIVED.items():
            feats[k] = bool(fn(feats))
        try:
            outputs, missing = render_all(templates_dir, vals, feats)
        except RenderError as e:
            failures += 1
            print("combo %d: RENDER ERROR %s" % (i, e))
            continue
        if missing:
            failures += 1
            print("combo %d: missing variables %s" % (i, sorted(missing)))
            continue
        for fn in os.listdir(tmp):
            os.remove(os.path.join(tmp, fn))
        for name, text in outputs.items():
            with open(os.path.join(tmp, name + ".html"), "w", encoding="utf-8") as fh:
                fh.write(text)
        res = lint.lint_dir(tmp, vars_path)
        if res.errors:
            failures += 1
            on = [k for k in FEATURES if feats[k]]
            print("combo %d (%d features on): %d lint error(s)" % (i, len(on), len(res.errors)))
            for e in res.errors[:6]:
                print("   " + e)
    print("selftest: %d combination(s), %d failed" % (len(combos), failures))
    return 1 if failures else 0


def main(argv):
    args = argv[1:]
    opts = {"vars": None, "templates": None, "out": None, "features": "", "only": None, "no_lint": False, "list": False, "selftest": None}
    i = 0
    while i < len(args):
        a = args[i]
        if a in ("-h", "--help"):
            print(__doc__)
            return 0
        elif a == "--vars":
            opts["vars"] = args[i + 1]; i += 2
        elif a == "--templates":
            opts["templates"] = args[i + 1]; i += 2
        elif a == "--out":
            opts["out"] = args[i + 1]; i += 2
        elif a == "--features":
            opts["features"] = args[i + 1]; i += 2
        elif a == "--only":
            opts["only"] = set(args[i + 1].split(",")); i += 2
        elif a == "--selftest":
            opts["selftest"] = int(args[i + 1]); i += 2
        elif a == "--no-lint":
            opts["no_lint"] = True; i += 1
        elif a == "--list":
            opts["list"] = True; i += 1
        else:
            print("unknown argument: %s" % a)
            return 2
    if opts["list"]:
        print("FEATURES (default false):")
        for k, d in FEATURES.items():
            print("  %-34s %s" % (k, d))
        print("\nVARIABLES (* = has a default):")
        for k, s in V.items():
            print("  %-34s%s %s" % (k, "*" if s["default"] is not None else " ", s["doc"]))
        return 0
    if not opts["templates"]:
        opts["templates"] = os.path.join(HERE, "..", "templates")
    if opts["selftest"] is not None:
        if not opts["vars"]:
            print("--selftest needs --vars")
            return 2
        return selftest(opts["templates"], opts["vars"], opts["selftest"])
    if not (opts["vars"] and opts["out"]):
        print("need --vars and --out (see --help)")
        return 2
    try:
        variables, raw_feats = load_vars(opts["vars"])
        values = build_values(variables)
        feats, warns = build_features(raw_feats, parse_features_arg(opts["features"]), values)
        outputs, missing = render_all(opts["templates"], values, feats, opts["only"])
    except RenderError as e:
        print("ERROR: %s" % e)
        return 1
    for w in warns + risky_feature_warnings(feats):
        print("WARN  " + w)
    if missing:
        print("ERROR: %d variable(s) are needed by the selected features but missing from %s:" % (len(missing), opts["vars"]))
        for name in sorted(missing):
            doc = V.get(name, {}).get("doc", "(not in the registry: typo in a template?)")
            print("  %-34s %s   [used in: %s]" % (name, doc, ", ".join(sorted(missing[name]))))
        return 1
    os.makedirs(opts["out"], exist_ok=True)
    for fn in os.listdir(opts["out"]):
        if fn.endswith(".html"):
            os.remove(os.path.join(opts["out"], fn))
    for name, text in outputs.items():
        with open(os.path.join(opts["out"], name + ".html"), "w", encoding="utf-8") as fh:
            fh.write(text)
    print("rendered %d file(s) into %s" % (len(outputs), opts["out"]))
    unused = sorted(k for k in variables if k not in V)
    if unused:
        print("WARN  variables not in the registry (ignored): %s" % ", ".join(unused))
    if opts["no_lint"]:
        return 0
    lint = import_lint()
    res = lint.lint_dir(opts["out"], opts["vars"])
    for w in res.warnings:
        print("WARN  " + w)
    for e in res.errors:
        print("ERROR " + e)
    print("lint: %d error(s), %d warning(s)" % (len(res.errors), len(res.warnings)))
    return 1 if res.errors else 0


if __name__ == "__main__":
    sys.exit(main(sys.argv))
