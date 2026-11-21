# Legal rules register (what the templates and the runbook rely on) and how to refresh it

Created 2026-11-21 from the 2acetech project. **Today in this register = 2026-11-21.**
Law changes: re-verify a rule before relying on it if its verify date is older than 90 days, and always before a client's first publication.

Verify-date codes:

- **[R 2026-11-21]** = re-verified today by the author of this skill against a primary or official source (Sejm ELI API `api.sejm.gov.pl/eli`, EUR-Lex, Commission pages), or a web search for status.
  For Polish acts this means **metadata** (existence, Dz.U. position, dates, entry into force, list of amending acts), not a re-read of every article.
- **[A 2026-11-20]** = verified article by article against ISAP/EUR-Lex by the legal audit of 2acetech (`legal/OPEN-ITEMS.md` sections 4, 5 and 8, `legal/AUDIT-2026-11.md`). Not re-read on 2026-11-21.
- **[K]** = author's knowledge or secondary source; **must be verified against a primary source before it is quoted in a client document.**

Never copy an article number from this table into a client document without opening the source.

## 1. Register

| ID | Rule the documents rely on | Citation | Verify date and source | Notes / what to watch |
|---|---|---|---|---|
| R-01 | Consumer Rights Act, current consolidated text | ustawa z 30.05.2014 o prawach konsumenta, **Dz.U. 2026 poz. 1244** (obwieszczenie Marszałka Sejmu 4.09.2026, ogłoszone 23.09.2026) | [R 2026-11-21] `api.sejm.gov.pl/eli/acts/DU/2026/1244` (IN_FORCE); `.../DU/2014/827` references list it as the latest consolidated text | amending acts in the ELI list: DU/2026/1353 (in force 2027-09-27), DU/2025/1172, DU/2024/1222, DU/2023/1285 ... Re-check the ELI record each refresh; `UPK_TEXT_REF` variable |
| R-02 | UPK articles used: 7, 7a (complaint 14 days, silence = acceptance), 7aa, 11, 12 ust. 1 (21 points), 13, 17 ust. 1-4, 18, 21-24, 27-38, 43a-43g, 43k | UPK | [A 2026-11-20] ISAP consolidated text Dz.U. 2026 poz. 1244 | art. 17 ust. 4: no contract if the order button/label is deficient. Non-conformity regime art. 43a-43g replaces KC rękojmia for consumers since 1 Jan 2023 (art. 43a ust. 1) |
| R-03 | Amendment adding green-claims, guarantee-notice and durability rules | ustawa z 4.09.2026 zmieniająca UPNPR i UPK, **Dz.U. 2026 poz. 1353** (ogłoszona 19.10.2026) | [R 2026-11-21] ELI: entry into force **2027-09-27**; press: implements Dir. (EU) 2024/825 | the EU date was 27 Sept 2026 (Poland late). Plan: standard notice of the statutory guarantee (Implementing Reg. (EU) 2025/1960 **[K]**), green-claims limits, black-list changes (UPNPR art. 7 pkt 10a ...). Article contents not read in full: read before 27 Sept 2027 |
| R-04 | Electronic withdrawal function ("Odstąp od umowy tutaj") | Dir. (EU) 2023/2673 inserting **art. 11a** into Dir. 2011/83/EU; applies from **19 June 2026** (EU) | [R 2026-11-21] press and law-firm notes (CMS 22-23 June 2026: draft UC82 paused; later reports: UC169 in consultation); **ELI list of amending acts of the UPK shows no act implementing it** (checked 2026-11-21) | **Poland had not transposed it.** Keep the function (cheap, matches the directive); art. 30 ust. 3-4 UPK already require a prompt acknowledgement on a durable medium when electronic withdrawal is offered **[A]**. A Polish act may arrive with a short vacatio legis: check legislacja.gov.pl (RCL) every month |
| R-05 | Express wallet buttons need the "zamówienie z obowiązkiem zapłaty" label or equivalent | UPK art. 17 ust. 2-4 | [A 2026-11-20] | a lawyer should approve the wording near Apple Pay/Google Pay/Link |
| R-06 | E-services act: Regulamin before the contract; seller data | ustawa o świadczeniu usług drogą elektroniczną, Dz.U. 2024 poz. 1513, art. 5-8 (art. 10 repealed); amended by **Dz.U. 2026 poz. 1296** (in force 2026-11-06) | [A 2026-11-20] articles; [R 2026-11-21] 1296: "ustawa z 4.09.2026 o zmianie ustawy o świadczeniu usług drogą elektroniczną oraz niektórych innych ustaw", in force **2026-11-06** | 1296 implements the DSA in Poland (UOKiK named as consumer-related authority; amends art. 419 PKE per audit) |
| R-07 | Cookies and e-marketing consent | **Prawo komunikacji elektronicznej**, Dz.U. 2024 poz. 1221: art. **399** ust. 1, 2, 3 pkt 2; art. **398**; art. **400** | [R 2026-11-21] in force since 10.11.2024; [A 2026-11-20] articles | replaced the Telecommunications Law cookie rules. Which authority enforces art. 399: Prezes UKE **[K]**: verify. Whether cookieless Google consent-mode pings need consent: lawyer point |
| R-08 | UPNPR: misleading actions and omissions, black list (art. 7 pkt 7, 10, 25, 26), reviews (art. 6 ust. 4 pkt 7), price advantage (art. 5 ust. 3 pkt 5) | ustawa z 23.08.2007, Dz.U. 2023 poz. 845 | [A 2026-11-20] | R-03 amends it from 2027-09-27 |
| R-09 | Price information / Omnibus: lowest price of the 30 days before a reduction | ustawa o informowaniu o cenach towarów i usług, Dz.U. 2023 poz. 168, art. 3-6; CJEU 26.09.2024 C-330/23 | [A 2026-11-20] | fines up to PLN 20 000 (40 000 repeat). Series of reductions: lawyer |
| R-10 | GPSR | Reg. (EU) 2023/988: art. 4, 9, 11-13, 16, 19, 20; applies since 13.12.2024 | [A 2026-11-20] EUR-Lex | |
| R-11 | Polish GPSR enforcement act | ustawa z 7.11.2025 o nadzorze nad ogólnym bezpieczeństwem produktów, **Dz.U. 2025 poz. 1826**, art. 4 (Polish language), **art. 76 (fine up to PLN 500 000 for breach of GPSR art. 19)** | [R 2026-11-21] ELI: in force **2026-01-03**; [A] articles | |
| R-12 | Toys | Dir. 2009/48/EC art. 7(2), 11(2), annex V part B; **repealed by Reg. (EU) 2025/2509 from 1 Aug 2030** | [A 2026-11-20] | warnings visible before purchase also online |
| R-13 | Electrical/radio/lighting | LVD 2014/35/EU art. 9; EMC 2014/30/EU art. 10; RED 2014/53/EU art. 13; RoHS 2011/65/EU; ecodesign Reg. 2019/2020; energy label Del. Reg. 2019/2015 art. 4 lit. b-c; RED cybersecurity Del. Reg. 2022/30 (from 1 Aug 2025; a 2026 act repeals it from 11 Dec 2027) | [A 2026-11-20] | check EPREL for light sources |
| R-14 | WEEE | ustawa o zużytym sprzęcie elektrycznym i elektronicznym, **Dz.U. 2024 poz. 573**: art. 4 pkt 20, **art. 37 ust. 2** (free take-back at delivery of household equipment: same kind, same functions; no quantity cap in the text), ust. 4, **art. 39** | [A 2026-11-20]; amendment **Dz.U. 2026 poz. 176 from 18.02.2027** (mentioned by the audit, **[K]** content) | batteries (Dz.U. 2025 poz. 809), Reg. (EU) 2023/1542, packaging (Dz.U. 2026 poz. 619), Reg. (EU) 2025/40 (from 12.08.2026) **not read**: environmental adviser |
| R-15 | Out-of-court options and ADR statement; ODR closed | UPK art. 12 ust. 1 pkt 21; ustawa o pozasądowym rozwiązywaniu sporów konsumenckich Dz.U. 2016 poz. 1823 art. 31-32; ODR platform complaints ended 20.03.2025, Reg. 524/2013 repealed from **20.07.2025** by Reg. (EU) 2024/3228 | [A 2026-11-20] | do not link to the ODR platform |
| R-16 | Product Liability Directive (software, importers, platforms) | Dir. (EU) 2024/2853: products placed on the market after **9 Dec 2026** | [R 2026-11-21] web (CMS, Gibson Dunn): date confirmed; Polish transposition status **not verified** | matters most for importers (model D) and smart products; insurance |
| R-17 | Accessibility | ustawa z 26.04.2024, Dz.U. 2024 poz. 731: art. 3 ust. 2 pkt 6 (e-commerce), **art. 4 pkt 1 (micro-enterprises excluded)** | [A 2026-11-20] | |
| R-18 | DSA hosting duties for reviews | Reg. (EU) 2022/2065 art. 3, 11, 12, 14, 16, 17, 19 | [A 2026-11-20] | art. 19 exempts micro/small enterprises only from the platform chapter |
| R-19 | GDPR articles used | Reg. (EU) 2016/679: 5, 6, 7, 12, 13, 15-22, 25, 28, 30, 32, 33, 37, 44-49, 77, 83 | [A 2026-11-20] | UODO and EDPB guidance on consent/cookies (equal prominence of reject) **[K]**: re-read uodo.gov.pl and edpb.europa.eu at each refresh |
| R-20 | Geo-blocking | Reg. (EU) 2018/302 art. 3-5 | [A 2026-11-20] | Poland-only delivery lawful; no IP blocking; foreign consumer may order to a Polish address |
| R-21 | Invoice on consumer's request, cash-register exemption | VAT Act (Dz.U. 2026 poz. 1263) art. **106b ust. 3 pkt 1** (3 months from end of month of delivery/payment), art. 112; Rozporządzenie MF 17.12.2024 Dz.U. 2024 poz. 1902 annex item 41 | [A 2026-11-20] | art. 113 (VAT exemption, 200 000 PLN limit) and art. 88 (no deduction without invoice) **[K]** |
| R-22 | Retention | ustawa o rachunkowości Dz.U. 2026 poz. 522 art. 74 ust. 2-3 (5 years from the start of the year after the financial year); Ordynacja podatkowa Dz.U. 2026 poz. 622 art. 70 § 1 | [A 2026-11-20] | |
| R-23 | KSeF e-invoicing | ustawa o VAT (KSeF 2.0); MF timeline | [R 2026-11-21] web (MF page ksef.podatki.gov.pl and secondary sources): large taxpayers 1 Feb 2026; others 1 Apr 2026; micro with sales <= PLN 10 000 per month: 1 Jan 2027; KSeF number on transfers between taxpayers from 1 Jan 2027 | B2B supplier invoices to the shop; accountant |
| R-24 | Low-value imports | Council Reg. (EU) **2026/382**: EUR 3 flat duty per item category on consignments up to EUR 150 from 1 Jul 2026 to 1 Jul 2028; product identifiers mandatory from 1 Nov 2026 | [R 2026-11-21] web (Commission and Council pages; sources disagree on "per item" vs "per category"): confirm on taxation-customs.ec.europa.eu | model D only |
| R-25 | Civil Code articles | Dz.U. 2026 poz. 795: 22(1), 115, 118, 383(1), 384, 385(1)-385(5) (grey list art. 385(3)), 454(1), 455, 491, 492, 548, 556-581 | [A 2026-11-20] | |
| R-26 | Polish language duties | ustawa o języku polskim Dz.U. 2026 poz. 81 art. 7, 7a | [A 2026-11-20] | |
| R-27 | Governing law | Reg. (EC) 593/2008 (Rome I) art. 6 ust. 2 | [A 2026-11-20] | |
| R-28 | Facts about third parties used in the privacy policy: Stripe entities (Stripe Payments Europe, Limited; Stripe Technology Europe, Limited; Stripe, LLC; DPF + SCC), Google LLC in the EU-US DPF (decision (UE) 2023/1795), Hostinger entities and transfer statement, GA4 retention 2 or 14 months | provider pages: stripe.com/privacy, stripe.com/legal/dpa, stripe.com/legal/ssa/pl; policies.google.com/privacy/frameworks; hostinger.com/legal/privacy-policy, hostinger.com/pl/legal/dpa; support.google.com/analytics/answer/7667196 | [A 2026-11-20] | provider facts change: re-read at every refresh and on every client onboarding |
| R-29 | Right to repair | Dir. (EU) 2024/1799, transposition deadline 31.07.2026 | [R 2026-11-21] CMS note of 12-23 June 2026: Polish draft UC154 not published; **status now unknown** | affects repair/replacement information duties |

## 2. How to refresh the register (do this at the start of every onboarding and each quarter)

1. **Polish acts.** For each Dz.U. reference: `https://api.sejm.gov.pl/eli/acts/DU/{year}/{pos}` (status, dates). For the base act (e.g. UPK `DU/2014/827`) read `references` > "Inf. o tekście jednolitym" (latest consolidated text) and "Akty zmieniające" (amending acts with entry-into-force dates). Open the consolidated text on ISAP (`isap.sejm.gov.pl`) and re-read the articles used. Compare with the previous `UPK_TEXT_REF`.
2. **Pending Polish legislation.** `legislacja.gov.pl` (RCL) and `sejm.gov.pl` druki: search "ustawa o prawach konsumenta", "przycisk odstąpienia", "informowanie o cenach", "nadzór nad ogólnym bezpieczeństwem produktów". Watch UC82/UC169 (withdrawal function), the right-to-repair bill, PLD transposition.
3. **EU acts.** `eur-lex.europa.eu` (Polish text of the regulation/directive): check "consolidated version", application dates, delegated acts. EU Commission pages for customs and product safety (`commission.europa.eu`, `taxation-customs.ec.europa.eu`, Safety Gate).
4. **Authorities.** UODO (`uodo.gov.pl`: cookies/consent guidance, decisions), UOKiK (`uokik.gov.pl`: decisions on e-commerce, price reductions, dark patterns, reviews), Prezes UKE (PKE art. 399 enforcement), Inspekcja Handlowa.
5. **Provider facts.** Stripe, Google, Hostinger (and any other processor): privacy policy, DPA, SSA entity names, DPF status (`dataprivacyframework.gov`).
6. **Record.** Update the verify dates in this table and in the client's OPEN-ITEMS section "Sources and versions used" (what was read, when, which version).
7. **Re-render** the client's texts with a new `VERSION_DATE` and publish with 14 days' notice where Regulamin § 10 requires it.

Primary-source list to re-check (task list): UPK; UPNPR; ustawa o informowaniu o cenach (Omnibus); GPSR (Reg. 2023/988) and the Polish act; DSA (Reg. 2022/2065) and Dz.U. 2026 poz. 1296; European Accessibility Act (Dir. 2019/882) and Dz.U. 2024 poz. 731; WEEE, battery, packaging acts and Regs 2023/1542, 2025/40; RODO and UODO/EDPB guidance; PKE art. 398-400; the EU withdrawal-button rules (Dir. 2023/2673); Dir. 2024/825; Dir. 2024/1799; PLD 2024/2853; customs rules for low-value consignments.

## 3. Risks that are assumptions, not verified facts (open legal-risk list)

1. Withdrawal function is not yet a Polish legal duty (R-04): wording of the page and the acknowledgement may need to change when the Polish act is published.
2. Cookieless Google consent-mode pings before consent: treated as needing disclosure, possibly consent (lawyer).
3. Stripe security cookies as "strictly necessary" under art. 399 ust. 3 pkt 2 PKE (lawyer).
4. Abandoned-cart reminders treated as commercial information needing prior consent (art. 398 PKE).
5. Third-country transfers to suppliers/marketplaces: mechanism and roles (art. 44-49 RODO).
6. Own-warehouse, paid-delivery, seller-pays-returns, ADR-participation, newsletter, contact-form and abandoned-cart text variants were **authored on 2026-11-21 and were not part of the published 2ACE texts**: lawyer review required.
7. The statutory Annex 1 wording for "seller bears return costs" ("Poniesiemy koszty zwrotu towarów") and for bulky goods must be compared with annex 1 UPK on ISAP.
8. Related-party transfer pricing rules and thresholds for connected supplier/shop (model B): accountant/tax adviser; provisions not verified.
9. KSeF details and VAT deduction for marketplace purchases (model C): accountant.
10. Polish transposition status of PLD 2024/2853 and right-to-repair: not verified.
