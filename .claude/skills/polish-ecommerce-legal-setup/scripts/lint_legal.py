#!/usr/bin/env python3
"""Lint a folder of rendered Polish legal pages (HTML fragments for WordPress).

Usage:
    python3 -I lint_legal.py OUTDIR [--vars company.json] [--extra-slugs a,b] [--strict]

What it checks (errors fail the run, warnings are informational):

  Public pages (regulamin-sklepu, polityka-prywatnosci, polityka-cookies,
  dostawa-i-platnosc, zwroty-i-odstapienie, reklamacje, dane-sprzedawcy):
    - leftover template tokens: {{ }}, <!--IF/ELSE/ENDIF/N/R/H--> markers
    - placeholders: <code>[...]</code>, [BRACKETED UPPERCASE NOTES],
      WARIANT, POTWIERDZ(Z), USUN, OPEN-ITEMS, TODO, FIXME, XXX, lorem
    - unbalanced or mis-nested HTML tags
    - artefacts of removed optional blocks: empty <li>/<ul>/<ol>/<p>/<tbody>,
      ",,", " ,", ";;", "()", doubled spaces, a heading directly followed by a heading
    - internal links to slugs that are not in the rendered set (+ --extra-slugs)
    - anchors: every "/slug/#anchor" link must resolve; required anchors
      par-2, par-5, par-6, par-8 (regulamin) and sec-6 (polityka-prywatnosci)
    - "§ N ust. M" cross references inside the Regulamin point to a paragraph that exists
    - format sanity of NIP (checksum), REGON (9 or 14 digits, checksum), KRS (10 digits),
      Polish phone (+48 and 9 digits) found in the text
  Owner-side documents (blok-informacje-..., wzor-umowy-...):
    - only token leftovers, tag balance and format sanity (placeholders in
      <code>[...]</code> are intentional there)
  With --vars: also validates the variable values themselves (NIP checksum, REGON,
  KRS, phone, e-mail, postcode in addresses, date format).

Exit code 0 = no errors, 1 = errors.
Dependency-free (standard library only). Run with: python3 -I lint_legal.py ...
"""
import json
import os
import re
import sys

sys.dont_write_bytecode = True
from html.parser import HTMLParser

PUBLIC_DOCS = {
    "regulamin-sklepu": "regulamin",
    "polityka-prywatnosci": "polityka-prywatnosci",
    "polityka-cookies": "polityka-cookies",
    "dostawa-i-platnosc": "dostawa-i-platnosc",
    "zwroty-i-odstapienie": "zwroty-i-odstapienie",
    "reklamacje": "reklamacje",
    "dane-sprzedawcy": "dane-sprzedawcy",
}
OWNER_DOCS = {
    "blok-informacje-o-bezpieczenstwie-produktu",
    "wzor-umowy-dropshipping",
    "wzor-umowy-powierzenia-danych",
}
DEFAULT_EXTRA_SLUGS = {
    "odstap-od-umowy", "cart", "checkout", "my-account", "shop", "koszyk", "kasa", "moje-konto",
    "sklep", "kontakt",
}
REQUIRED_ANCHORS = {
    "regulamin": ["par-2", "par-5", "par-6", "par-8"],
    "polityka-prywatnosci": ["sec-6"],
}
VOID = {"br", "hr", "img", "input", "meta", "link", "wbr", "col", "source"}

# ---------------------------------------------------------------- checksums


def nip_ok(nip):
    d = re.sub(r"\D", "", nip)
    if len(d) != 10:
        return False
    w = [6, 5, 7, 2, 3, 4, 5, 6, 7]
    s = sum(int(d[i]) * w[i] for i in range(9)) % 11
    return s != 10 and s == int(d[9])


def regon_ok(regon):
    d = re.sub(r"\D", "", regon)
    if len(d) == 9:
        w = [8, 9, 2, 3, 4, 5, 6, 7]
        s = sum(int(d[i]) * w[i] for i in range(8)) % 11 % 10
        return s == int(d[8])
    if len(d) == 14:
        if not regon_ok(d[:9]):
            return False
        w = [2, 4, 8, 5, 0, 9, 7, 3, 6, 1, 2, 4, 8]
        s = sum(int(d[i]) * w[i] for i in range(13)) % 11 % 10
        return s == int(d[13])
    return False


def krs_ok(krs):
    return bool(re.fullmatch(r"\d{10}", krs.strip()))


def phone_ok(phone):
    digits = re.sub(r"[\s\-]", "", phone)
    return bool(re.fullmatch(r"\+48\d{9}", digits))


MONTHS = "stycznia|lutego|marca|kwietnia|maja|czerwca|lipca|sierpnia|września|października|listopada|grudnia"
DATE_RE = re.compile(r"^\d{1,2} (%s) \d{4} r\.$" % MONTHS)
EMAIL_RE = re.compile(r"^[^@\s<>\"']+@[^@\s<>\"']+\.[A-Za-z]{2,}$")
POSTCODE_RE = re.compile(r"\b\d{2}-\d{3}\b")

# ---------------------------------------------------------------- html parsing


class Checker(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.stack = []
        self.errors = []
        self.ids = []
        self.hrefs = []
        self.text = []

    def handle_starttag(self, tag, attrs):
        d = dict(attrs)
        if "id" in d:
            self.ids.append(d["id"])
        if tag == "a" and "href" in d:
            self.hrefs.append(d["href"])
        if tag not in VOID:
            self.stack.append((tag, self.getpos()))

    def handle_startendtag(self, tag, attrs):
        d = dict(attrs)
        if "id" in d:
            self.ids.append(d["id"])

    def handle_endtag(self, tag):
        if tag in VOID:
            return
        if not self.stack:
            self.errors.append("stray </%s> at line %d" % (tag, self.getpos()[0]))
            return
        top, pos = self.stack[-1]
        if top == tag:
            self.stack.pop()
            return
        # tolerate omitted </li> / </p> never produced by our templates -> report
        if tag in [t for t, _ in self.stack]:
            self.errors.append("</%s> at line %d closes while <%s> (line %d) is still open" % (tag, self.getpos()[0], top, pos[0]))
            while self.stack and self.stack[-1][0] != tag:
                self.stack.pop()
            if self.stack:
                self.stack.pop()
        else:
            self.errors.append("stray </%s> at line %d" % (tag, self.getpos()[0]))

    def handle_data(self, data):
        self.text.append(data)

    def close(self):
        super().close()
        for tag, pos in self.stack:
            self.errors.append("<%s> opened at line %d is never closed" % (tag, pos[0]))


def anchors_from_html(slug, html):
    """Same rules as the theme (inc/legal.php ace_legal_anchors) plus explicit ids."""
    anchors = set(re.findall(r'\bid="([^"]+)"', html))
    if slug == "regulamin":
        anchors |= {"par-%s" % n for n in re.findall(r"<h2>§ (\d+)\.", html)}
        anchors |= {"zalacznik-%s" % n for n in re.findall(r"<h2>Załącznik nr (\d+)\.", html)}
    elif slug == "polityka-prywatnosci":
        anchors |= {"sec-%s" % n for n in re.findall(r"<h2>(\d+)\.", html)}
    return anchors


def regulamin_structure(html):
    """{paragraph number: highest 'ust.' number} computed from <ol start> numbering."""
    out = {}
    parts = re.split(r"(?=<h2>)", html)
    for part in parts:
        m = re.match(r"<h2>§ (\d+)\.", part)
        if not m:
            continue
        par = int(m.group(1))
        depth = 0
        count = 0
        # walk tags
        pos = 0
        last = 0
        for tm in re.finditer(r"<(/?)(ol|ul|li)([^>]*)>", part):
            closing, tag, attrs = tm.group(1), tm.group(2), tm.group(3)
            if tag in ("ol", "ul"):
                if not closing:
                    if depth == 0 and tag == "ol":
                        sm = re.search(r'start="(\d+)"', attrs)
                        count = int(sm.group(1)) - 1 if sm else 0
                    depth += 1
                    if tag == "ul":
                        pass
                else:
                    depth -= 1
            elif tag == "li" and not closing and depth == 1:
                # direct item of a top-level list: only <ol> counts, <ul> items are not 'ust.'
                count += 1
                last = count
        out[par] = last
    return out


# ---------------------------------------------------------------- the lint


class Result:
    def __init__(self):
        self.errors = []
        self.warnings = []

    def err(self, doc, msg):
        self.errors.append("%s: %s" % (doc, msg))

    def warn(self, doc, msg):
        self.warnings.append("%s: %s" % (doc, msg))


def check_formats(res, doc, html):
    plain = re.sub(r"<[^>]+>", " ", html)
    plain = re.sub(r"&nbsp;", " ", plain)
    for m in re.finditer(r"\bNIP\b[:\s]*([0-9][0-9\- ]{8,12}[0-9])", plain):
        if not nip_ok(m.group(1)):
            res.err(doc, "NIP '%s' fails the checksum" % m.group(1).strip())
    for m in re.finditer(r"\bREGON\b[:\s]*([0-9]{5,14})", plain):
        if not regon_ok(m.group(1)):
            res.err(doc, "REGON '%s' is not a valid 9/14-digit REGON" % m.group(1))
    for m in re.finditer(r"\bKRS\b(?: pod numerem)?[:\s]*([0-9]{4,12})", plain):
        if not krs_ok(m.group(1)):
            res.err(doc, "KRS '%s' is not 10 digits" % m.group(1))
    for m in re.finditer(r"\+48[ \d\-]*\d", plain):
        cand = m.group(0).strip()
        if not phone_ok(cand):
            res.err(doc, "phone '%s' is not +48 followed by 9 digits" % cand)


def check_public(res, doc, html, ctx):
    # placeholders
    if "<code>[" in html or "<code>&#91;" in html:
        res.err(doc, "placeholder in <code>[...]</code> left in a public page")
    for m in re.finditer(r"\[([A-ZĄĆĘŁŃÓŚŹŻ][A-ZĄĆĘŁŃÓŚŹŻ0-9 _/,.\-–:'\"„”()…]{2,})\]", html):
        res.err(doc, "bracketed uppercase note left: [%s]" % m.group(1))
    for word in ("WARIANT", "POTWIERDŹ", "POTWIERDZ", "USUŃ", "USUN", "OPEN-ITEMS", "TODO", "FIXME", "XXX"):
        if re.search(r"(?<![A-Za-zĄĆĘŁŃÓŚŹŻąćęłńóśźż])%s(?![A-Za-zĄĆĘŁŃÓŚŹŻąćęłńóśźż])" % re.escape(word), html):
            res.err(doc, "forbidden marker word '%s' present" % word)
    if re.search(r"lorem ipsum", html, re.I):
        res.err(doc, "lorem ipsum present")
    # artefacts of removed optional blocks
    plain_only = re.sub(r"<[^>]+>", "\0", html)
    artefacts = [
        (r"<(li|p|td|th|h2|h3)>\s*</\1>", "empty <%s> element"),
        (r"<(ul|ol|tbody|table)[^>]*>\s*</\1>", "empty <%s> element"),
        (r"</h2>\s*<h2>", "heading directly followed by another h2 (empty section)"),
        (r"</h3>\s*<h[23]>", "h3 directly followed by another heading (empty subsection)"),
    ]
    for pat, msg in artefacts:
        for m in re.finditer(pat, html):
            res.err(doc, (msg % m.group(1)) if "%s" in msg else msg)
    for pat, msg in [
        (r",\s*,", "doubled comma"),
        (r"\s,(?!\d)", "space before comma"),
        (r";\s*;", "doubled semicolon"),
        (r"\(\s*\)", "empty parentheses"),
        (r"(?<![.…])\.\s*\.(?![.…])", "doubled full stop"),
        (r"[^\S\n]{2,}(?=[^\s<])", "doubled space"),
        (r"\(\s+[^)]", "space after ("),
        (r"[^\s(]\s+\)", "space before )"),
        (r" ;", "space before semicolon"),
        (r": ?:", "doubled colon"),
        (r"\bi\s+i\b|\boraz\s+oraz\b|\balbo\s+albo\b", "doubled conjunction"),
    ]:
        for m in re.finditer(pat, html):
            line = html.count("\n", 0, m.start()) + 1
            res.err(doc, "%s at line %d: ...%s..." % (msg, line, re.sub(r"\s+", " ", html[max(0, m.start() - 25):m.end() + 25])))
            break
    # links
    checker = ctx["checker"]
    shop = ctx["shop_url"]
    for href in checker.hrefs:
        if href.startswith(("mailto:", "tel:", "#")):
            continue
        if href.startswith("/"):
            path = href
        elif shop and href.startswith(shop):
            path = href[len(shop):] or "/"
        else:
            continue  # external link
        pm = re.match(r"^(/[^?#]*)?(?:#(.*))?$", path)
        slug = (pm.group(1) or "/").strip("/")
        anchor = pm.group(2)
        if slug == "":
            continue
        known = set(ctx["page_slugs"]) | ctx["extra_slugs"]
        if slug not in known:
            res.err(doc, "internal link to unknown slug '/%s/' (href=%s)" % (slug, href))
            continue
        if anchor and slug in ctx["anchors"] and anchor not in ctx["anchors"][slug]:
            res.err(doc, "link %s points to anchor '#%s' that does not exist in /%s/" % (href, anchor, slug))
    # duplicate ids
    seen = set()
    for i in checker.ids:
        if i in seen:
            res.err(doc, "duplicate id '%s'" % i)
        seen.add(i)


def check_crossrefs(res, doc, html, structure):
    for m in re.finditer(r"(?<!art\. )(?<!art\.&nbsp;)§\s*(\d+)\s+ust\.\s*(\d+)(?:\s*[–-]\s*(\d+))?", html):
        par, a = int(m.group(1)), int(m.group(2))
        b = int(m.group(3)) if m.group(3) else a
        if par not in structure:
            res.err(doc, "reference '%s' points to a § that does not exist" % m.group(0))
        elif max(a, b) > structure[par]:
            res.err(doc, "reference '%s': § %d has only %d paragraphs" % (m.group(0), par, structure[par]))


def lint_dir(outdir, vars_path=None, extra_slugs=None, strict=False):
    res = Result()
    files = {}
    for fn in sorted(os.listdir(outdir)):
        if fn.endswith(".html"):
            with open(os.path.join(outdir, fn), encoding="utf-8") as fh:
                files[fn[:-5]] = fh.read()
    if not files:
        res.err("(folder)", "no .html files in %s" % outdir)
        return res

    shop_url = ""
    variables = None
    if vars_path:
        with open(vars_path, encoding="utf-8") as fh:
            data = json.load(fh)
        variables = data.get("variables", data)
        shop_url = (variables.get("SHOP_URL") or "").rstrip("/")

    page_slugs = {PUBLIC_DOCS[k] for k in files if k in PUBLIC_DOCS}
    extra = set(DEFAULT_EXTRA_SLUGS) | set(extra_slugs or [])
    anchors = {PUBLIC_DOCS[k]: anchors_from_html(PUBLIC_DOCS[k], v) for k, v in files.items() if k in PUBLIC_DOCS}

    structure = regulamin_structure(files["regulamin-sklepu"]) if "regulamin-sklepu" in files else {}

    # required anchors
    for slug, req in REQUIRED_ANCHORS.items():
        if slug in page_slugs:
            for a in req:
                if a not in anchors.get(slug, set()):
                    res.err(slug, "required anchor '#%s' missing (the theme footer links to it)" % a)
    for missing in sorted(set(PUBLIC_DOCS.values()) - page_slugs):
        res.warn("(set)", "public page '%s' not present in the folder" % missing)

    for name, html in files.items():
        # token leftovers (all documents)
        if re.search(r"\{\{|\}\}", html):
            toks = sorted(set(re.findall(r"\{\{[^}]*\}\}?", html)))[:8]
            res.err(name, "leftover {{ }} tokens: %s" % ", ".join(toks))
        if re.search(r"<!--\s*(IF|IFNOT|ELSE|ENDIF|N|R|H)\b", html):
            res.err(name, "leftover template markers <!--IF/ELSE/ENDIF/N/R/H-->")
        c = Checker()
        c.feed(html)
        c.close()
        for e in c.errors:
            res.err(name, "HTML: " + e)
        check_formats(res, name, html)
        if name in OWNER_DOCS:
            continue
        if name in PUBLIC_DOCS:
            ctx = {
                "checker": c,
                "shop_url": shop_url,
                "page_slugs": page_slugs,
                "extra_slugs": extra,
                "anchors": anchors,
            }
            check_public(res, name, html, ctx)
        else:
            res.warn(name, "file is neither a known public page nor an owner-side document; only basic checks ran")
    if "regulamin-sklepu" in files:
        # cross references in every public page that cite the Regulamin
        for name, html in files.items():
            if name in PUBLIC_DOCS:
                check_crossrefs(res, name, html, structure)

    if variables is not None:
        for k, v in variables.items():
            if not isinstance(v, str) or v == "":
                continue
            if k == "NIP" and not nip_ok(v):
                res.err("vars", "NIP %s fails the checksum" % v)
            if k == "REGON" and not regon_ok(v):
                res.err("vars", "REGON %s invalid (9 or 14 digits, checksum)" % v)
            if k == "KRS" and not krs_ok(v):
                res.err("vars", "KRS %s is not 10 digits" % v)
            if k.endswith("PHONE") and not phone_ok(v):
                res.err("vars", "%s '%s' must look like +48 123 456 789" % (k, v))
            if k.endswith("EMAIL") and not EMAIL_RE.match(v):
                res.err("vars", "%s '%s' is not an e-mail address" % (k, v))
            if k.endswith("_DATE") and not DATE_RE.match(v):
                res.err("vars", "%s '%s' must look like '20 listopada 2026 r.'" % (k, v))
            if k.endswith("ADDRESS") and k not in ("SUPPLIER_ADDRESS", "ADR_BODY_ADDRESS") and not POSTCODE_RE.search(v):
                res.warn("vars", "%s has no NN-NNN postcode" % k)
            if k.endswith("_URL") and not re.match(r"^https://", v):
                res.warn("vars", "%s should start with https://" % k)
            if k == "SHOP_URL" and v.endswith("/"):
                res.err("vars", "SHOP_URL must not end with '/'")
    return res


def main(argv):
    args = argv[1:]
    if not args or args[0] in ("-h", "--help"):
        print(__doc__)
        return 2
    outdir = args[0]
    vars_path = None
    extra = []
    i = 1
    while i < len(args):
        if args[i] == "--vars":
            vars_path = args[i + 1]
            i += 2
        elif args[i] == "--extra-slugs":
            extra = [s for s in args[i + 1].split(",") if s]
            i += 2
        else:
            print("unknown argument %s" % args[i])
            return 2
    res = lint_dir(outdir, vars_path, extra)
    for w in res.warnings:
        print("WARN  " + w)
    for e in res.errors:
        print("ERROR " + e)
    print("lint: %d error(s), %d warning(s)" % (len(res.errors), len(res.warnings)))
    return 1 if res.errors else 0


if __name__ == "__main__":
    sys.exit(main(sys.argv))
