# Open items for the shop owner: legal documents for 2acetech.pl

Updated 20 November 2026 for 2ACE sp. z o.o. This file is in English for you. The documents themselves are in Polish and are meant for consumers.

**These are drafts based on public sources and on the store's code and live pages. They are not legal advice. A Polish lawyer (radca prawny or adwokat) must review them before you take real orders.**

Companion files in this folder:

| File | What it is |
|---|---|
| `AUDIT-2026-11.md` | Full audit with risk-ranked findings, developer to-do list and facts needed |
| `regulamin-sklepu.html` | Terms of sale (Regulamin) with the two statutory annexes |
| `polityka-prywatnosci.html` | Privacy policy |
| `polityka-cookies.html` | Cookie policy |
| `zwroty-i-odstapienie.html` | Returns and withdrawal page with the model form |
| `reklamacje.html` | Complaints and non-conformity ("rękojmia") page with a complaint form |
| `dostawa-i-platnosc.html` | Delivery, payment, invoices, used electronics and batteries |
| `dane-sprzedawcy.html` | Company data and contact (use as the Contact page text) |
| `blok-informacje-o-bezpieczenstwie-produktu.html` | Block for every product page (product-safety information) |
| `wzor-umowy-dropshipping.html` | Template supplier agreement (B2B), not for the website |
| `wzor-umowy-powierzenia-danych.html` | Template data-processing agreement (art. 28 GDPR), not for the website |
| `regulamin-sklepu.md`, `polityka-prywatnosci.md` | **Old drafts of 3-4 Nov 2026. Superseded by the `.html` files. Do not publish them.** Delete when you are ready. |

The theme also holds copies of the two original templates in `wp-content/themes/2acetech/legal/`. The developer must replace them with the new `.html` files (see AUDIT, section "What the developer can change").

Contents: 1. Publication conditions · 2. Questions for you (numbered checklist) · 3. Decisions you must take · 4. Corrections to the original brief, with sources · 5. Grey-list check (art. 385³ KC) · 6. What a Polish lawyer should confirm · 7. Company data check · 8. Sources and versions used

---

## 1. Publication conditions: what must be true before each document goes live

Several sentences in the documents describe what the shop does. They become true only after the changes below. Do not publish a page earlier.

| Document | Must be true first |
|---|---|
| Regulamin | (a) Phone number filled in. (b) Return address filled in. (c) The "Terms" page is set in WooCommerce so checkout shows a working link, preferably with a tick box (AUDIT C-1, H-1). (d) The order confirmation e-mail carries the Regulamin as PDF (AUDIT C-4). (e) Every product page carries the safety block (§ 3 ust. 6). (f) Only the payment methods listed in § 5 ust. 2 are enabled. (g) Express Apple Pay / Google Pay buttons are labelled or switched off (AUDIT H-2). (h) The checkout summary shows the delivery time and the total price including VAT (AUDIT H-1; § 4 ust. 2 pkt 4). (i) The trust strip, perks and home-page placeholders are corrected (AUDIT H-3, H-10). |
| Polityka prywatności | Section 3 and 4 filled in with your real suppliers, platforms and tools. Every row for a tool you do not use is deleted. Rows 13 to 16 match what is actually installed (AUDIT C-2, H-4, H-5). |
| Polityka cookies | A developer has confirmed in the browser (DevTools, Network and Application tabs) that **before consent and after "Odrzuć wszystkie"** no request goes to Google and no `_ga*` or `sbjs_*` cookie is set, and that fonts come from your own server. The cookie table matches what is really set. |
| Zwroty / Reklamacje / Dostawa | Same facts as the Regulamin. Return cost variant, return address and the way defective goods are collected are decided (questions 6 to 9). |
| Blok bezpieczeństwa | You hold the manufacturer's data, declaration of conformity and Polish instructions for each product. No documents means no sale. |

---

## 2. Questions for you: numbered checklist

Answer each item. Where a document has a `[BRACKET]` it is listed here. "Where" shows the document and place.

### A. Company and contact

1. **Phone number** of the shop. Where: all documents (`[NUMER TELEFONU SKLEPU]`), also Customize > Store details > Phone. The law requires an e-mail address and a phone number (art. 12 ust. 1 pkt 3 UPK). The call must not cost more than an ordinary call (art. 11 UPK).
2. Is `info@2acetech.pl` a real mailbox that someone reads every working day? Your DNS has an SPF record for Hostinger mail but **two DMARC records** (invalid) and a Brevo verification record. Ask the developer to fix DMARC (see AUDIT L-5). Is Brevo in use (newsletter or order e-mails)? If yes it must be named in the privacy policy (section 3).
3. Registry court. The documents say "Sąd Rejonowy dla m.st. Warszawy, XIV Wydział Gospodarczy KRS". I inferred it from the KRS case number `WA.XIV NS-REJ.KRS/50872/26/912/`. Check it on the current KRS extract (odpis aktualny) from ekrs.ms.gov.pl.
4. Do you want a separate data-protection address (e.g. rodo@)? It must exist and be read. If not, the privacy policy keeps `info@2acetech.pl`.
5. Is 2ACE a micro-enterprise (fewer than 10 employees and turnover or balance sheet total not above EUR 2 million, counting linked companies)? This decides the accessibility exemption (AUDIT M-8).

### B. Returns, complaints, guarantees

6. **Return address** (`[ADRES DO ZWROTÓW]`). Where do customers send goods? Options: your own address, a returns address you rent, or the supplier's warehouse in Poland. If it is the supplier's warehouse, the supplier must agree in writing to accept returns as your authorised person (art. 34 ust. 1 UPK). Never use the sender on the parcel label unless you agreed that.
7. **Who pays return shipping?** Variant A (default in the text): the consumer pays the direct cost (art. 34 ust. 2 UPK). Variant B: you pay. Either is lawful if stated before the order. For bulky or battery goods (hoverboard) you must also state an estimated maximum return cost (`[KWOTA ZŁ]`; art. 12 ust. 1 pkt 10 UPK). If you leave this out, the consumer does not bear the cost (art. 23, art. 34 ust. 2 UPK).
8. **Defective goods:** how will you collect them at your cost? Default in the text: you order a courier, or you refund the shipping cost against proof (art. 43d ust. 5 UPK). Confirm you will do this.
9. **Out-of-court dispute resolution:** variant A (no commitment, but a statement after each unresolved complaint, art. 32 ADR Act) or variant B (you agree to a named body). Keep it identical in `regulamin-sklepu.html` § 8 ust. 7 and `reklamacje.html`.
10. Any goods that are excluded from withdrawal (art. 38 UPK)? The text says none today. If you add custom-made or sealed hygiene goods, mark them on the product page.
11. Do any products really come with a **manufacturer's guarantee card in Polish**? The text says: no guarantee from you; a manufacturer's guarantee only where the product page says so and the card is in the box. List such products or say "none". (The trust strip claim "Gwarancja producenta na każdy produkt" was removed from the documents; the developer must remove it from the site, see AUDIT H-3.)

### C. Delivery, payment, invoices

12. Do your suppliers **guarantee in writing** a dispatch time of up to 3 working days and a transit time of 2 to 5 working days, and that every variation on sale ships from a warehouse **in Poland**? What happens when stock runs out in Poland? These times become part of your contract with the customer (art. 22 UPK).
13. Do suppliers send a **tracking number**? The site currently promises "z numerem śledzenia". If not, the claim must go.
14. Carriers: do you want to name them? (Not required. The Regulamin says "kurier".)
15. **Payment methods enabled today**: the live checkout loads Stripe card, and the code also registers Klarna, Stripe Link and WooPayments (WooCommerce Payments). Which are switched on? The documents list only card, Apple Pay and Google Pay. Disable the rest, or add them to the documents and the privacy policy. When BLIK and Przelewy24 go live, update `regulamin-sklepu.html` § 5 ust. 2, `dostawa-i-platnosc.html` section 5 and the privacy policy.
16. **Stripe:** the exact Stripe entity named in your Stripe agreement (usually Stripe Payments Europe, Ltd., Ireland); confirm the Data Processing Agreement is part of your account terms; is automatic fraud screening (Radar) on?
17. **Sales documents and VAT (ask your accountant):** (a) What do customers get: receipt, confirmation, invoice? (b) May you rely on the cash-register exemption for mail-order sales paid through a payment institution? The exemption in the Ministry of Finance regulation of 17 Dec 2024 (Dz.U. 2024 poz. 1902, annex item 41) requires payment through a bank, post or SKOK to your bank account with data that identify the buyer. Card payments settled by Stripe are a grey area; the authorities have given mixed views. (c) WooCommerce taxes are **off** today (the live cart shows no VAT line). Prices are shown gross by default but VAT is not computed in orders. Decide with the accountant how VAT and invoices are handled. (d) Invoice on request: art. 106b ust. 3 VAT Act (3 months from the end of the month of sale or payment). (e) KSeF does not apply to invoices for consumers; B2B rules are the accountant's.
18. **Used electronics, batteries, packaging (BDO):** (a) Do you have your own BDO registration number? Ask your accountant or an environmental adviser. (b) Ask each supplier in writing for its BDO number and who is the "wprowadzający sprzęt". The WEEE Act requires a distributor to offer only equipment placed on the market by a company entered in the register (art. 39 pkt 1 lit. a). (c) How will you offer **free take-back of old equipment when you deliver new household equipment** (art. 37 ust. 2)? The text in `dostawa-i-platnosc.html` has `[SPOSÓB ODBIORU ZUŻYTEGO SPRZĘTU]` and `[ADRES STRONY Z WYKAZEM PUNKTÓW ZBIERANIA]`. Options: courier take-back arranged with suppliers, or a contract with a collection organisation.

### D. Products and prices

19. **For every product:** manufacturer (name, postal address, e-mail), responsible person in the EU if the manufacturer is outside the EU, model and batch identifier, photo of the label, CE marking, declaration of conformity, warnings and Polish instructions. Use the block in `blok-informacje-o-bezpieczenstwie-produktu.html`. Products without this data must not be sold (art. 19 GPSR; art. 76 of the Polish GPSR Act: fine up to PLN 500,000).
20. **Toys:** minimum age and the warnings printed on the toy or box, in Polish, visible before purchase. **Hoverboard:** declaration of conformity (low voltage, EMC, and radio equipment if it has Bluetooth), battery data, charger data, and the transport rules for lithium batteries for your courier. Is it a toy or sports equipment? That is the manufacturer's classification; ask for it.
21. Which lamps, LED strips and string lights are "light sources" or contain one and therefore need an **energy label and product information sheet** shown online (art. 4 lit. b, c Delegated Reg. 2019/2015)? Check each model in EPREL or ask the supplier.
22. **Product texts** were rewritten by the Product Cleaner plugin through an AI service. Someone must read each text and delete claims you cannot prove (compatibility, "works with all", power, battery life). Public statements about a product become part of what you must deliver (art. 43b ust. 2 pkt 2 UPK).
23. **Crossed-out "regular price"** (24 of 28 products in your catalogue, none on the 4 products now live): was each product ever sold at that price in the shop? If not, remove the regular price. If yes, you must show the lowest price of the previous 30 days next to every announced reduction (art. 4 ust. 2 and 3 Price Information Act). Fine up to PLN 20,000 (art. 6).

### E. Data and tools

24. **Google tools** (Site Kit with GA4, Google for WooCommerce with Google Ads). Do you want to keep them? If yes, they must be switched on only after consent and Google Consent Mode must follow the banner (developer task AUDIT C-2). If not, deactivate them. Today they send data before consent and after "Odrzuć wszystkie" (tested 20 Nov 2026).
25. **WooCommerce Order Attribution** sets `sbjs_*` cookies before consent. Default decision: switch it off (WooCommerce > Settings > Advanced > Features).
26. **Abandoned-cart plugin** (Woo Abandoned Cart Recovery; its GDPR switch is off) and the **Hostinger Reach** plugin (script loaded on every page). Are they used? Default decision: deactivate. If you keep them, reminder e-mails need prior consent (art. 398 PKE) and the privacy policy rows 13 and the tool name must be filled in.
27. **Product reviews** (Photo Reviews plugin; the review form says in English "Review now to get coupon!"). Keep reviews? If yes: who may review (verified buyers only is safest), is a coupon given for a review (then regardless of rating and labelled), are photos allowed. Fill the conditional blocks in Regulamin § 2 ust. 7 to 9 and privacy row 14. If no: turn reviews off in WooCommerce > Settings > Products.
28. **Newsletter:** will you run one? If yes: which tool, a consent tick box with text, double opt-in, a stored record of consent, an unsubscribe link in every message. The theme's built-in form does none of this. If no: delete § 10 of the Regulamin and privacy row 12, and remove the form.
29. Exact names for privacy section 3: hosting company (Hostinger legal entity and server location), mail provider (Hostinger mail, Brevo, SMTP plugin), accountant, couriers.
30. **Suppliers and platforms:** which platform do you order on (the AliDropship plugin places orders through AliExpress)? In which country are the supplier companies based? What exactly does the plugin send to the supplier (name, address, phone, e-mail)? Does the plugin vendor's server receive any customer data? These facts fill privacy section 3 and 4. If data leave the EEA you need a transfer mechanism (art. 44 to 49 GDPR). The supplier's name stays out of the Regulamin and the pages.
31. Retention periods you really apply: server logs, e-mail correspondence, abandoned carts, review data. Privacy table has `[...]` for them.
32. Do you have social-media profiles? (Footer shows none today. If you add them, the privacy policy needs a short note.)

### F. Processes

33. Who reads `info@2acetech.pl` and the **Withdrawals** list in wp-admin, and how fast? Complaints must be answered within 14 days (art. 7a UPK); refunds within 14 days of the withdrawal statement (art. 32 ust. 1 UPK).
34. Who handles product-safety reports and data-subject requests? You need a simple written procedure and a log (GPSR art. 12, 20; GDPR art. 12, 33(5)).
35. Create the records the law expects: **record of processing activities** (art. 30 GDPR; the small-company exception does not apply because your processing is not occasional), a register of data-processing agreements, a breach register. No DPO is required (art. 37 ust. 1 GDPR).

---

## 3. Decisions you must take

| # | Decision | Default in the text | Note |
|---|---|---|---|
| D1 | Sell only to consumers | yes | Selling to companies needs a separate B2B Regulamin. |
| D2 | Return shipping | consumer pays | Free returns are a business choice. If you choose free returns, change Regulamin § 6 ust. 15, annex 1, the returns page. |
| D3 | Newsletter | not offered | See question 28. |
| D4 | Google Analytics / Ads | to be decided | See question 24. Deactivation is the cheapest safe option. |
| D5 | Product reviews | to be decided | See question 27. |
| D6 | Out-of-court dispute resolution | variant A | See question 9. |
| D7 | Own guarantee | none | If you offer one, the statement must have the content required by art. 577¹ § 2 KC and be handed over with the goods (art. 577³ KC). |
| D8 | Delivery time | prep 3 working days binding, transit 2-5 working days expected, 30 days outer limit | Shorter promises only if suppliers confirm in writing. |
| D9 | When the contract is concluded | when you send the order confirmation after successful payment | Align WooCommerce e-mails with Regulamin § 4 ust. 4. |
| D10 | Trust strip and product-page perks | remove "Gwarancja", "Łatwe zwroty", "szybka dostawa" | See AUDIT H-3. |
| D11 | Express wallet buttons (Apple Pay, Google Pay, Link) | keep only if labelled | AUDIT H-2. |
| D12 | Take-back of old equipment | to be decided | Question 18. |
| D13 | Pause orders | recommended until Critical items are fixed | WooCommerce > Settings > Site visibility, or disable the checkout. The checkout works today with a live Stripe key. |

---

## 4. Corrections to the original brief (with sources)

| # | Brief | Finding | Source |
|---|---|---|---|
| 1 | Button wording "art. 8 ust. 2" ("Zamawiam i płacę") | The rule is **art. 17 ust. 2 and 3 UPK**: the consumer must expressly confirm the payment obligation and the button must say "zamówienie z obowiązkiem zapłaty" or an equivalent clear wording. **Art. 17 ust. 4: if not met, the contract is not concluded.** Art. 8 UPK is about contracts other than at a distance. The shop's real button text, seen on the live checkout on 20 Nov 2026, is **"Zamówienie z obowiązkiem zapłaty"** (the statutory words). "Zamawiam i płacę" is also widely accepted as equivalent but the statutory phrase is safer. Express wallet buttons are a separate risk (AUDIT H-2). | UPK Dz.U. 2026 poz. 1244; live checkout |
| 2 | "Delivery date statement (art. 22 / 30-day rule)" | **Art. 22 UPK** says the art. 12 information is part of the contract and changes only by express agreement. The consolidated UPK contains **no 30-day delivery rule**. That rule is in Directive 2011/83/EU art. 18 and Poland did not copy it. What applies: art. 455, 491, 492 KC. The text uses a 30-day outer limit as your own promise. | UPK (searched for "30 dni"); KC Dz.U. 2026 poz. 795 |
| 3 | "Rękojmia arts. 556 ff. as amended" | For **consumers**, KC arts. 556 ff. no longer apply (art. 43a ust. 1 UPK). The regime is **non-conformity, art. 43a to 43g UPK**: 2 years from delivery (art. 43c ust. 1), presumption that the defect existed at delivery, repair or replacement first, then price reduction or withdrawal (art. 43d, 43e). "Rękojmia" stays only as a plain-language label in the documents. KC arts. 556 ff. still matter between you and your supplier (B2B), see the supplier template. | UPK; KC |
| 4 | "EU ODR platform closure of 20 July 2025" | Complaints through the platform ended **20 March 2025**; Regulation 524/2013 is repealed with effect from **20 July 2025** (Regulation (EU) 2024/3228). Do not link to the platform or state ODR duties. What remains: art. 12 ust. 1 pkt 21 UPK and the ADR Act (art. 31 to 32). | EUR-Lex (Polish text) |
| 5 | "EU electronic withdrawal function from 19 June 2026" | Directive (EU) 2023/2673 adds art. 11a to Directive 2011/83/EU (apply from 19 June 2026). **Poland has not transposed it as of 20 Nov 2026:** the consolidated UPK (state 2 Sept 2026) has no such article; the only 2026 amendment of the UPK found (Dz.U. 2026 poz. 1353, in force 27 Sept 2027) concerns green claims and repairs; press reports a draft UC169 in consultation (not a primary source). Art. 30 ust. 3 to 4 UPK already regulates what happens when a shop *offers* electronic withdrawal. The shop's function (link "Odstąp od umowy tutaj", confirm button, acknowledgement with date and time) matches the directive, so keep it. | ISAP/Sejm ELI API; EUR-Lex |
| 6 | "Replacement of Telecommunications Law cookie rules by Electronic Communications Law" | Correct: **art. 399 PKE** (consent unless strictly necessary, ust. 3 pkt 2), **art. 398** (e-marketing consent), art. 400 (consent standard as in GDPR). Art. 10 of the e-services act is repealed. The DSA implementing act (Dz.U. 2026 poz. 1296, in force 6 Nov 2026) amends art. 419 PKE only. | PKE Dz.U. 2024 poz. 1221; Dz.U. 2026 poz. 1296 |
| 7 | "Digital Content/Goods Directive 2019/771" | 2019/771 is the **Sale of Goods Directive** (UPK chapter 5a, art. 43a to 43g). The **Digital Content Directive is 2019/770** (UPK chapter 5b). For smart lamps with an app, art. 43b ust. 3 and art. 43k ust. 3 UPK matter (updates). | UPK |
| 8 | "European Accessibility Act and the Polish implementing act" | The Polish act is the **act of 26 April 2024, Dz.U. 2024 poz. 731**. E-commerce services are covered (art. 3 ust. 2 pkt 6) but **services of micro-enterprises are excluded (art. 4 pkt 1)**. | ISAP |
| 9 | "GPSR Reg. (EU) 2023/988" (no article given) | Applies since 13 Dec 2024. Online offers must show manufacturer, EU responsible person, product identifier and warnings: **art. 19**. Distributors: art. 12. Trader selling under own name: art. 13(1). Polish act of 7 Nov 2025 (Dz.U. 2025 poz. 1826, in force 3 Jan 2026): art. 4 (Polish language), **art. 76 (fine up to PLN 500,000 for breach of GPSR art. 19)**. | EUR-Lex; ISAP |
| 10 | "Toy Safety Directive 2009/48/EC" | Still the law. It is repealed from **1 Aug 2030** by Regulation (EU) 2025/2509. Art. 7(2) (distributor checks) and art. 11(2) (warnings visible before purchase, also online) apply. | EUR-Lex |
| 11 | "LVD/EMC/RoHS/Ecodesign" (no RED) | Add **RED 2014/53/EU** for products with Wi-Fi or Bluetooth (art. 13 distributors) and Delegated Reg. (EU) 2022/30 (cybersecurity, from 1 Aug 2025; a 2026 delegated act repeals it from 11 Dec 2027). Lighting: Reg. 2019/2020 and 2019/2015 (energy label, art. 4 for dealers). | EUR-Lex |
| 12 | "VAT/invoices/cash-register exemption" | See question 17: Dz.U. 2024 poz. 1902, annex item 41, and art. 106b ust. 3 VAT Act (Dz.U. 2026 poz. 1263). | ISAP |
| 13 | "Omnibus (art. 4 ustawy o informowaniu o cenach)" | Correct: art. 4 ust. 2, 3, 5; art. 5 (consumer may demand the most favourable price); art. 6 fines (PLN 20,000; PLN 40,000 for repeats). CJEU 26 Sept 2024, C-330/23 (Aldi Süd): a percentage or amount reduction must be calculated from the lowest price of the previous 30 days. | ISAP Dz.U. 2023 poz. 168; CURIA press release |
| 14 | "Crossed-out price on 24 of 28 items" | The **live** shop shows only **4 published products, none with a crossed-out price** (Store API, 20 Nov 2026). The 24 of 28 refers to the catalogue not yet public. The assessment (AUDIT H-6) applies when you publish. | Live shop |
| 15 | "Trust strip: Gwarancja w cenie / Gwarancja producenta na każdy produkt" | Confirmed live on the home page. Not supported by any fact you gave. **Removed from the documents; must be removed from the site.** Presenting a statutory right ("14 dni", "łatwe zwroty") as a selling point is also a black-list practice (art. 7 pkt 10 UPNPR). | Live home page; UPNPR Dz.U. 2023 poz. 845 |
| 16 | Existing templates | Defects found in the previous drafts: Annex 2 contained markdown artefacts `(<em>)`; the privacy policy said "no transfers outside the EEA", "no analytics or ads" and "Order Attribution is off", which the live shop contradicts; the footer anchor links assumed one Regulamin page; § 5 and § 6 still dealt with non-EU shipping. All fixed in the new files. | Code and live site |

---

## 5. Grey-list check (art. 385³ KC) and related checks

I read the 23 points of art. 385³ KC in the current Civil Code (Dz.U. 2026 poz. 795) and checked every clause that could touch them.

| Clause in the new Regulamin | Grey-list point(s) checked | Result |
|---|---|---|
| § 1 ust. 6, § 4 ust. 2 pkt 5, § 4 ust. 7 (Regulamin available before the order, tick box, PDF with confirmation) | pkt 4 (no chance to read before the contract); art. 384 § 1 KC; art. 8 ust. 1 to 2 e-services act | OK once the Terms page is linked at checkout and the tick box exists. **Not OK today** (no page, no link). |
| § 1 ust. 7 (offer only to Poland; any consumer may order with delivery to Poland) | pkt 23; Reg. 2018/302 art. 4(1)(a) | OK. |
| § 1 ust. 8 (dropshipping: you are the seller, sender may differ) | pkt 21 (liability depends on others' performance), pkt 2 | OK. The text says the consumer does not need to contact the supplier and you are liable. |
| § 2 ust. 2 to 4 (account termination) | pkt 14, 15 | OK. Consumer ends any time at no cost; seller only for listed reasons with 14 days' notice. |
| § 2 ust. 5 (account not required) | pkt 7 | OK. |
| § 2 ust. 7 to 9 (reviews; incentive; licence; notice and takedown) | pkt 10 (change without valid reason), pkt 8; UPNPR art. 6 ust. 4 pkt 7, art. 7 pkt 25 and 26 | OK as drafted (conditional). The licence is non-exclusive, free, only while published. |
| § 3 ust. 1 (offer data is part of the contract) | pkt 10, 19, 20 | OK. Follows art. 22 UPK. |
| § 3 ust. 3 (reference price) | UPNPR art. 5 ust. 3 pkt 5; Price Information Act art. 4 | OK as a promise. Requires correct reference prices (AUDIT H-6). |
| § 3 ust. 5 (no payment surcharge) | art. 383¹ KC | OK. |
| § 3 ust. 6 (safety information on every product page) | not a grey-list clause; GPSR art. 19 | True only after the block is on the pages. |
| § 4 ust. 5 (reasons to refuse or cancel an order, including supplier cannot ship) | pkt 8 (performance depends only on the trader's will), pkt 12, 13 (no refund) | OK. Closed list of external reasons; full refund within 14 days. Do not widen it. |
| § 4 ust. 6 (consumer may withdraw offer before acceptance) | pkt 14 | OK. Art. 31 ust. 2 UPK. |
| § 5 ust. 3 (payment failure) | pkt 16, 17 | OK. No fee or penalty. |
| § 5 ust. 8 to 9 (prep time binding, transit expected, 30-day limit, art. 491 to 492 KC kept) | pkt 8, 10, 19, 2 | OK as drafted. **Would not be OK** as a bare "times are estimates and may change". |
| § 5 ust. 10 (risk in transit) | pkt 2; art. 548 § 3 KC | OK. Risk stays with you until delivery. |
| § 5 ust. 11 (parcels in several shipments, no extra cost) | pkt 19 | OK. |
| § 6 (withdrawal) | pkt 14, 12, 13, 16, 17; art. 7 UPK | OK. No fee, no shorter period, no "opened packaging" ban. Return cost and diminished value as in art. 34 ust. 2 and 4. |
| § 6 ust. 19 (deduction for diminished value) | pkt 3 | OK; set-off of the consumer is untouched. |
| § 7 (non-conformity, art. 43a to 43g) | pkt 1, 2; art. 7 UPK | OK. Shortens nothing. Says guarantee is additional. |
| § 7 ust. 9 (no own guarantee, manufacturer guarantee only when stated) | art. 12 ust. 1 pkt 14 UPK; UPNPR art. 5 | OK and true only if the site stops claiming "gwarancja na każdy produkt". |
| § 8 (complaints: 14 days, silence means accepted, many channels) | pkt 4, 9, 11, 21 | OK. |
| § 8 ust. 8 (courts) | pkt 23 | OK. No jurisdiction clause. |
| § 11 (changes of the Regulamin) | pkt 10, 19 | OK. Closed list of reasons, 14 days' notice, old orders keep the old version, free exit for account holders. |
| § 12 ust. 1 (Polish law) | pkt 23; Rome I art. 6(2) | OK. Mandatory home-country rights are kept. |
| § 12 ust. 2 (more favourable of Regulamin and information pages wins) | art. 385 § 2 KC | OK; helps the consumer. |
| **Clauses deliberately not included** | pkt 2, 5, 16, 17, 20, 23 | No liability cap, no force-majeure exclusion, no assignment of the contract, no contractual penalties, no "price error, we may cancel" clause, no jurisdiction clause, no restocking fee, no shorter withdrawal period, no "photos are for illustration" disclaimer. Do not add these without a lawyer. |

---

## 6. What a Polish lawyer should confirm

1. Registry court and KRS extract data.
2. Whether express wallet buttons (Apple Pay, Google Pay, Link) satisfy art. 17 ust. 3 UPK and which wording is safest (AUDIT H-2).
3. The "prep time binding, transit expected, 30 days outer limit" structure under art. 22 UPK and art. 385³ pkt 8, 10, 19.
4. Limitation period for non-conformity claims (art. 118 KC: 6 years for consumer claims, 3 years for business-related claims; my privacy retention text is conservative).
5. Whether review sections count as a "hosting service" under the DSA and which DSA articles apply to you (art. 11, 12, 14, 16, 17; art. 19 exempts micro and small companies only from the platform chapter).
6. Whether cookieless Google pings sent before consent (consent mode with default "denied") need consent under GDPR for the IP address; I treated them as needing consent.
7. Classification of Stripe security cookies (`__stripe_mid`, `__stripe_sid`, `m`, anti-bot cookies) as strictly necessary under art. 399 ust. 3 pkt 2 PKE.
8. Whether abandoned-cart reminders are "commercial information" needing prior consent under art. 398 PKE (I assumed yes).
9. Third-country transfers to suppliers and purchasing platforms (art. 44 to 49 GDPR; art. 49 ust. 1 lit. b only for occasional transfers) and who is controller and processor in your dropshipping chain.
10. WEEE, batteries and packaging duties in your dropshipping set-up: who is "wprowadzający sprzęt" (WEEE Act art. 4 pkt 20), what you must register in BDO, and the one-for-one take-back at delivery (art. 37 ust. 2). I verified the WEEE Act (Dz.U. 2024 poz. 573) only; the battery act (Dz.U. 2025 poz. 809), the packaging act (Dz.U. 2026 poz. 619), Regulation (EU) 2023/1542 and Regulation (EU) 2025/40 (from 12 Aug 2026) I did not read in detail.
11. Partial-withdrawal refund wording and risk of loss in return shipping (the UPK is silent; the draft is neutral).
12. Whether the claims "Elektronika premium" and "Szybka dostawa" are acceptable puffery once delivery takes 5 to 8 working days.
13. The supplier template: liability limits, contractual penalties, indemnity for administrative fines, jurisdiction.
14. The conditional blocks (reviews, newsletter, abandoned carts, analytics, ADR variants, return-cost variants).
15. Upcoming: Dz.U. 2026 poz. 1353 (from 27 Sept 2027: green claims, new black-list items such as art. 7 pkt 10a, legal-guarantee notice, durability and software-update information) and a Polish transposition of art. 11a (withdrawal function). Plan updates.
16. Penalties for breach of art. 19 GPSR are in art. 76 of the Polish act; check how the market-surveillance bodies (UOKiK, Inspekcja Handlowa; UKE for radio equipment) enforce them against dropshippers.

---

## 7. Company data check (KRS API and white-list API, 20 November 2026)

| Item | Verified value | Source |
|---|---|---|
| Name | 2ACE SPÓŁKA Z OGRANICZONĄ ODPOWIEDZIALNOŚCIĄ | KRS and white list |
| KRS | **0001267111** (registered 17.09.2026; extract state 18.09.2026) | KRS API |
| NIP | **1133212948**, VAT status "Czynny", VAT registration 2026-10-01 | white list |
| REGON | **545746743** (KRS shows the 14-digit form 54574674300000) | white list, KRS |
| Address | ul. Ostrobramska 101A, lok. 301, 04-041 Warszawa | KRS (the white list writes "101A/301") |
| Share capital | 5 000,00 PLN | KRS |
| Main activity (PKD) | other non-specialised retail sale | KRS |
| Bank accounts | two accounts on the white list (not printed in the documents) | white list |
| Registry court | inferred from the case number (see question 3) | KRS |

The KRS extract does not list an e-mail or a website. The e-mail `info@2acetech.pl` comes from your brief and the theme default. The number 545746743 is the **REGON**, never a KRS number.

---

## 8. Sources and versions used (all accessed 20 November 2026)

- Ustawa o prawach konsumenta, tekst jednolity **Dz.U. 2026 poz. 1244** (state 2 Sept 2026): art. 7, 7a, 7aa, 12, 13, 17, 18, 21 to 24, 27 to 38, 43a to 43g, 43k, annexes 1 and 2. Dz.U. 2026 poz. 1353 (amends UPK and UPNPR, in force 27 Sept 2027).
- Kodeks cywilny, Dz.U. 2026 poz. 795: art. 22¹, 115, 118, 383¹, 384, 385 to 385⁵, 454¹, 455, 491, 492, 548, 556 to 581.
- Ustawa o świadczeniu usług drogą elektroniczną, Dz.U. 2024 poz. 1513 (art. 5 to 8, 10 repealed); amended from 6 Nov 2026 by Dz.U. 2026 poz. 1296.
- Prawo komunikacji elektronicznej, Dz.U. 2024 poz. 1221 (art. 398 to 400).
- Ustawa o przeciwdziałaniu nieuczciwym praktykom rynkowym, Dz.U. 2023 poz. 845 (art. 5, 6, 7).
- Ustawa o informowaniu o cenach towarów i usług, Dz.U. 2023 poz. 168 (art. 4 to 6).
- Ustawa o pozasądowym rozwiązywaniu sporów konsumenckich, Dz.U. 2016 poz. 1823 (art. 31 to 32).
- Ustawa o języku polskim, Dz.U. 2026 poz. 81 (art. 7, 7a).
- Ustawa o nadzorze nad ogólnym bezpieczeństwem produktów, Dz.U. 2025 poz. 1826 (art. 4, 76).
- Ustawa o zużytym sprzęcie elektrycznym i elektronicznym, Dz.U. 2024 poz. 573 (art. 4 pkt 20, art. 37, 39).
- Ustawa o zapewnianiu spełniania wymagań dostępności, Dz.U. 2024 poz. 731 (art. 3, 4).
- Ustawa o VAT, Dz.U. 2026 poz. 1263 (art. 106a, 106b, 112); ustawa o rachunkowości, Dz.U. 2026 poz. 522 (art. 74); rozporządzenie MF z 17.12.2024, Dz.U. 2024 poz. 1902 (annex item 41).
- Referenced but not read in detail: ustawa o bateriach i akumulatorach (Dz.U. 2025 poz. 809); ustawa o gospodarce opakowaniami (Dz.U. 2026 poz. 619); ustawa o systemach oceny zgodności i nadzoru rynku (Dz.U. 2025 poz. 568).
- EUR-Lex / Publications Office: Reg. (EU) 2016/679; Reg. (EU) 2023/988 (art. 2, 4, 9, 12, 13, 16, 19, 20, 52); Directive 2009/48/EC (art. 7, 8, 11, annex V); Reg. (EU) 2025/2509 (application 1 Aug 2030); Directives 2014/35/EU (art. 9), 2014/30/EU (art. 10), 2014/53/EU (art. 13); Delegated Reg. (EU) 2019/2015 (art. 4), Reg. (EU) 2017/1369 (art. 5); Reg. (EU) 2022/2065 (DSA, art. 11, 12, 14, 16, 17, 19); Reg. (EU) 2018/302 (art. 3 to 5); Reg. (EU) 2024/3228 (end of ODR); Reg. (EU) 2025/40 (application 12 Aug 2026); Reg. (EU) 2023/1542 (batteries).
- Registers: Biała lista (wl-api.mf.gov.pl) and KRS API (api-krs.ms.gov.pl), 20 Nov 2026.
- CJEU press release on C-330/23 (curia.europa.eu, 2024-09). Stripe DPA page (stripe.com/legal/dpa). Press and practitioner articles on the status of the withdrawal button and WEEE duties in dropshipping were used only as pointers, not as authority.
- Live checks (no login, no order placed, one anonymous add-to-cart for the checkout test): HTML of the home, shop, category, product, cart, checkout, withdrawal and account pages; Store API cart and product lists; headless Chrome load of the home, product and checkout pages to list requests and cookies before and after clicking "Odrzuć wszystkie"; public DNS records.
