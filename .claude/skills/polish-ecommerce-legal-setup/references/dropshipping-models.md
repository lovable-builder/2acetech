# Dropshipping models: legal analysis and per-model setup

Purpose: decide which model a new store really runs, then switch the right template features and collect the right
documents. The consumer contract is always with **the shop** (art. 43a-43g UPK, art. 22 UPK, art. 548 § 3 KC): the supplier
is invisible to the consumer, but never to the law. Law statuses: see `legal-rules-register.md` (dates).
Reminder: drafts based on public sources, not legal advice; the supplier contract and the tax treatment need a Polish
lawyer and the accountant.

## 0. Common rules for every model

| Rule | Consequence in the documents / setup |
|---|---|
| The shop is the seller (UPK "przedsiębiorca") and answers for non-conformity for 2 years from delivery (art. 43c ust. 1 UPK [A 2026-11-20]), whoever shipped | Regulamin § 1 ust. 8 ("Kto jest Sprzedawcą"), § 7 ust. 10, Zwroty 7, Dostawa 3 (`dropshipping=true`) |
| Delivery promises become contract terms (art. 22 UPK [A]); a binding dispatch time promised to consumers needs a written supplier guarantee | `DISPATCH_DAYS_MAX` only with the supplier contract or written confirmation; transit time stays a forecast |
| Risk of loss until delivery to the consumer is the seller's (art. 548 § 3 KC [A]) | Regulamin § 5 ust. 10; supplier contract § 2 ust. 3 |
| Delay: consumer sets an additional term and may withdraw (art. 491, 492 KC [A]); 30-day outer limit is the seller's own promise | Regulamin § 5 ust. 9 |
| GPSR: the shop is at least a **distributor** (Reg. (EU) 2023/988 art. 12 [A]); online offers need manufacturer, EU responsible person, identifier, warnings in Polish before purchase (art. 19 [A]); selling under its own name/brand makes the shop a manufacturer (art. 13(1) [A]); no product without an EU economic operator (art. 16 [A]) | product-safety block (`pages-spec.md` 9); supplier must deliver documents **before** publication |
| Sector rules: toys (Dir. 2009/48/EC art. 7(2) distributor checks, art. 11(2) warnings visible online) [A]; LVD 2014/35/EU art. 9, EMC 2014/30/EU art. 10, RED 2014/53/EU art. 13 distributors check CE, documents, Polish instructions [A]; RoHS 2011/65/EU; lighting energy label Del. Reg. 2019/2015 art. 4 lit. b-c [A]; batteries Reg. (EU) 2023/1542 | no CE/documents = no sale |
| WEEE (ZSEE Act Dz.U. 2024 poz. 573 [A]): the introducer is the entity that first places equipment on the Polish market (art. 4 pkt 20); a distributor may sell only equipment placed on the market by an entity entered in the register (art. 39 pkt 1 lit. a); free take-back when delivering household equipment (art. 37 ust. 2); information on the website (art. 37 ust. 4). Batteries (Dz.U. 2025 poz. 809), packaging (Dz.U. 2026 poz. 619; Reg. (EU) 2025/40 applies from 12 Aug 2026) **not read in detail**: environmental adviser | BDO numbers in writing from every supplier; take-back arranged before the text promises it |
| Personal data: supplier receives name, address, phone (+ e-mail only if needed) | privacy § 3 recipients; art. 28 agreement if the supplier acts only on your instruction; couriers = separate controllers |
| Marketing claims about delivery | "free delivery" only when the shop bears the cost (supplier contract § 4 ust. 4); "fast"/"express" never; tracking promised only if every shipment has a tracking number; "from Poland" only if every variation ships from Poland |
| Product liability: Directive (EU) 2024/2853 applies to products placed on the market after **9 Dec 2026** (software included); transposition deadline 9 Dec 2026; the Polish status was not verified: **re-check** (rules register R-16) | insurance question for the owner; importer liability in model D |
| Accounting: input VAT needs a valid invoice to the shop's NIP (art. 88 VAT Act [K]); KSeF e-invoicing: mandatory for most from 1 Apr 2026 (large taxpayers 1 Feb 2026); everyone from 1 Jan 2027 (official MF timeline; **[verify date 2026-11-21 via web: ksef.podatki.gov.pl]**) | supplier invoices arrive through KSeF: the accountant sets it up |

---

## A. Domestic B2B supplier with a Polish warehouse (preferred)

**Flow.** Consumer pays the shop (card/wallet) -> shop forwards the order to the supplier (API, e-mail, supplier portal) -> supplier ships from its Polish
warehouse in neutral packaging with the shop as sender -> supplier invoices the shop (VAT invoice to the shop's NIP, KSeF) -> returns go to the address stated in the Regulamin
(shop's own address or supplier as authorised receiver, art. 34 ust. 1 UPK) -> refunds by the shop to the original payment method.

**Roles.** Supplier = seller to the shop (B2B), possibly manufacturer or importer of the goods (then it carries the GPSR/WEEE duties); shop = distributor; consumer contract = shop.

**Contract (template `wzor-umowy-dropshipping`)** - clause map:

| § | Subject | Why |
|---|---|---|
| 1-2 | definitions, subject; shop sells in own name and account; no contract between supplier and customer; ownership passes on confirmation; **risk with the supplier until delivery to the customer** | art. 548 § 3 KC |
| 3 | orders, confirmation time, stock feed, no substitutions, price changes with notice | availability claims, art. 4 Regulamin |
| 4 | **dispatch within N working days (binding)**, Polish warehouse only, courier, tracking number, transport cost, claims with carrier, re-ship lost/damaged at supplier's cost, take-back of old equipment (optional) | art. 22 UPK; Regulamin § 5 |
| 5 | **neutral packaging**, sender label, only allowed documents in the parcel, no supplier marketing to customers, no use of customer data | brand and data protection |
| 6 | **conformity and safety**: warranty of compliance with GPSR/sector law; **documents before first sale** (declaration of conformity, manufacturer/EU responsible person, identifier, label photos, warnings and manual in Polish); CE marking; **BDO number and who is the introducer**; 24-hour notice of incidents/recalls; right to stop selling; cooperation with authorities | GPSR art. 12, 19; ZSEE art. 39 |
| 7 | **returns** accepted at the warehouse as the shop's authorised person; report within N days; credit note; who bears return shipping | art. 34 ust. 1 UPK |
| 8 | **non-conformity, claims and recourse**: supplier liable to the shop (KC 556 ff. between businesses; art. 558 § 1 KC adaptations: period at least as long as the shop's liability to the consumer; no inspection duty) | art. 43c UPK vs KC |
| 9 | data protection: minimal data, art. 28 agreement as annex 5, no transfers outside EEA | RODO art. 28, 44 |
| 10 | settlements: VAT invoices to the shop's NIP (KSeF), payment to an account on the white list | VAT |
| 11-14 | confidentiality, content licence (photos/descriptions), contractual penalties **[lawyer]**, term and termination, Polish law | |
| Annexes | 1 goods and net prices; 2 warehouse and returns address, safety contact (24/7); 3 compliance documents per product; 4 take-back procedure; 5 art. 28 agreement | |

**Privacy policy**: `dropshipping=true`; supplier recipient bullet; optionally `name_suppliers_in_privacy=true` + `SUPPLIER_*`; role text per the contract (processor if art. 28 agreement signed).
**Regulamin wording**: § 1 ust. 8 ("Towary wysyłamy z magazynów Dostawców położonych w Polsce"), § 3 ust. 7, § 5 ust. 6, § 7 ust. 10.
**What the shop may claim**: Polish warehouse, dispatch within the guaranteed time, free delivery if it bears the cost. **May not**: "own stock", "fast", tracking unless guaranteed, manufacturer guarantee unless documented.

## B. Store-to-store (Shelfbridge-style): the real 2acetech flow

**Facts (read-only audit of 2026-11-20; all 4 live products came from this connection; zero orders at that date).**

| Step | What happens |
|---|---|
| Connection | the shop's WooCommerce is linked by the **Shelfbridge** plugin to the supplier's WooCommerce store (supplier brand Rimfya = SEVEN AM sp. z o.o., KRS 0001236640, NIP 1133200336 as read from the registers on 2026-11-20); the AliDropship plugin was still active with 24 legacy imported records but nothing live used it |
| Prices | shop price = supplier wholesale + 30% markup, rounded to end .99; hourly sync (e.g. supplier retail 109.99 -> shop 100.99); wholesale = supplier retail minus 30%, no tax or shipping added |
| Order | customer pays the shop (Stripe) -> order "processing" -> plugin sends **items, customer shipping address, phone and note** directly to the supplier's site -> supplier creates an **on-hold order billed to the shop at wholesale** |
| Settlement | supplier invoices the shop; sets the order Processing when paid |
| Fulfilment | supplier ships (InPost; "up to 3 working days" preparation, 2-5 days total, per the supplier's website) -> tracking is polled back -> customer e-mailed -> order Completed |
| Hub | a directory site (shelfbridge-landing.vercel.app) only lists stores |

**Legal analysis.**

1. **It is model A with an API.** Same duties: guaranteed dispatch time (the "up to 3 working days" on a website is not a contract: get it in writing), GPSR/CE documents per product (the shop imported product texts from the supplier's store: the plugin does **not** bring the manufacturer/EU responsible person data), BDO number, returns acceptance, neutral packaging (the plugin cannot change the supplier's labels or package inserts).
2. **What the plugin sends** = personal data to the supplier's server: name, address, phone, note (and possibly e-mail): privacy § 3, art. 28 agreement or separate-controller statement; check in the plugin and vendor documentation what else the **plugin vendor's** servers receive (unverified: ask the vendor, record the answer).
3. **Related-party point (do not assert as fact).** The supplier's public pages show the **same address (ul. Ostrobramska 101A/301) and the same phone number (+48 608 180 946)** as the shop's company (2ACE sp. z o.o., KRS 0001267111). That suggests the two companies are connected (owners, directors or the same office); public data do not prove it. **Ask the owner.** If they are related parties:
   - **Transfer pricing / arm's length**: prices between related companies must be market prices; the shop's markup and the supplier's wholesale discount must be documented (CIT Act chapter on related-party transactions, documentation thresholds): **accountant and tax adviser** **[K: provisions and thresholds not verified]**.
   - **VAT**: the supplier invoices the shop and the shop sells to consumers at 23% (check categories): input VAT deduction needs a valid invoice; no sham transactions (the goods must really move).
   - **Disclosure**: the privacy policy names the supplier as a recipient (`name_suppliers_in_privacy=true`) and states the role; **never** present the supplier as "independent" or the shop as "own production".
   - **Consumer law**: no extra consequence, but the supplier's address must not be on the parcel as the **return** address unless agreed: Regulamin § 6 ust. 14 uses a separate return address.
   - Keep a **supplier agreement** even between related companies (the template) and an art. 28 agreement.
4. **Markup and "regular price"**: the supplier's retail price is not a price the shop ever charged; never show it as a struck-through "before" price (Omnibus, UPNPR art. 5 ust. 3 pkt 5 [A]).
5. **Delivery claims**: "Wysyłka do 3 dni roboczych, dostawa 2-5 dni roboczych" only because the supplier states it **and** confirms it in writing; no tracking promise unless the plugin delivers a number for every order.
6. **Stock sync**: the plugin syncs stock hourly; between syncs the shop can sell the last unit twice: Regulamin § 3 ust. 7 and § 4 ust. 5 (refuse/cancel with refund within 14 days when the supplier cannot ship) cover it.

**Setup checklist**: import status Draft; map manufacturer fields (GPSR) before publishing; set the sync interval; disable e-mail sending to the supplier unless needed; set order notes carefully (customer "order notes" go to the supplier); test an order end to end in Stripe test mode and read the supplier's order screen (what data arrived); store the supplier's terms and privacy policy; sign the agreements; decide the return address.

## C. AliExpress / marketplace sellers with a Polish warehouse

**Flow.** The shop imports products with a plugin (AliDropship **free**: import, price markup, shipping filter; **Pro**: "Ali Order" automatic ordering and fulfilment statuses; the free version leaves ordering to the owner by hand). A customer order -> the owner (or Pro automation) buys the same item on AliExpress and enters the customer's address -> the seller ships from a "Poland" warehouse.

**Why it is the riskiest model that is still lawful:**

| Problem | Detail | Mitigation |
|---|---|---|
| **No B2B VAT invoice** | marketplace sellers (often non-EU entities) rarely issue a VAT invoice to the shop's NIP; no valid invoice = no input VAT deduction (art. 88 VAT Act, **[K]**), no proof of cost, while the shop owes 23% output VAT on the consumer sale | accountant decides **before** the first sale; if no invoice: do not sell |
| Ship-from truth | a listing shows "Ships from Poland" per variation; plugin option "ignore ship-from" imports all variations; a variation shipped from China breaks Regulamin § 5, Dostawa 3 and the 2-5 day promise | import only Poland variations; verify tracking origin on a test order; disable other variations; re-check monthly |
| GPSR / CE documents | sellers seldom provide a declaration of conformity, manufacturer and EU responsible person data; marketplace listing data may be incomplete | no documents, no sale (supplier contract cannot be signed: use the same requirements as a **purchase condition**) |
| **Toys, electrical goods, batteries, hoverboards** | highest non-compliance rates (no CE, counterfeits, unsafe batteries); distributor duties (Dir. 2009/48/EC, LVD, EMC, RED) | avoid these categories on this model |
| Data transfers | the customer's name, address, phone go to the marketplace and a non-EEA seller: art. 44-49 RODO (art. 49 ust. 1 lit. b only for occasional transfers) | `supplier_outside_eea=true` + `SUPPLIER_TRANSFER_TEXT`; name the platform entity as recipient (verify the contracting entity for EU buyers in the marketplace terms **[K, unverified]**); lawyer decides the mechanism |
| Returns | consumer returns go to the shop's address; the seller may refuse returns; the shop bears the loss; marketplace buyer protection is for the shop as buyer | the Regulamin's return address must be the shop's; do not use the sender label address |
| Non-conformity | the shop must repair/replace/refund within a reasonable time (art. 43d-43e UPK): a Chinese seller rarely cooperates | keep a small stock or refuse such products |
| Neutral packaging, inserts | the seller's flyers, invoices or prices in the parcel | not controllable: do not promise "neutral packaging" in public texts |
| Delivery times | 2-5 days from a Polish warehouse is plausible; from China 15-45 days: not allowed under the template | never mix |
| Plugin vendor | the AliDropship installation sends product data to the vendor's servers, licence checks; verify what | privacy only if customer data are involved |

AliDropship settings that matter legally (`wordpress-woocommerce-setup.md` 11): import status Draft; do not ignore ship-from; default phone country PL; PHP `max_input_vars`; fulfilment automation is Pro only.

**Privacy rows**: supplier/marketplace recipient, third-country transfer text, retention of order notes. **Regulamin**: as model A plus honest delivery times; no "from our warehouse".

## D. Direct import from outside the EU: DO NOT DO WITHOUT A LAWYER

Becoming the importer changes the legal position fundamentally. Minimum list of what changes (each item needs a professional):

| Area | Duty |
|---|---|
| GPSR importer (Reg. (EU) 2023/988 art. 11, 4 [A]) | check conformity assessment, technical documentation, labelling with the importer's name/address on the product, keep the EU declaration and documentation for 10 years, instructions in Polish, corrective action, cooperation with authorities |
| Sector CE rules | the importer must ensure CE marking and the declaration (toys, LVD, EMC, RED, RoHS, ecodesign) |
| Customs | EORI number, customs declarations (or a customs agent/postal operator), classification, origin, duty; **EU low-value consignments: removal of the old EUR 150 duty exemption, temporary EUR 3 flat duty per item category on consignments up to EUR 150 from 1 July 2026 to 1 July 2028 (Council Regulation (EU) 2026/382), product identifiers required from 1 Nov 2026 [verified by web search 2026-11-21; details to be confirmed on taxation-customs.ec.europa.eu]** |
| VAT | import VAT or **IOSS** (consignments <= EUR 150 sold to consumers); invoices; OSS if selling cross-border |
| WEEE/batteries/packaging | the shop becomes the **introducer ("wprowadzający")**: BDO registration, reporting, product fees, collection organisation, labelling (ZSEE art. 4 pkt 20 [A]) |
| Product liability | importer liability for defective products from outside the EU (Directive (EU) 2024/2853 from 9 Dec 2026) |
| Language and consumer information | all labels, manuals, warnings in Polish (ustawa o języku polskim art. 7a [A]) |
| Special goods | cosmetics (responsible person, CPNP), food and supplements, medical devices, PPE: do not sell |

The templates do not cover model D. If the owner wants it: stop, document the facts, and send the questionnaire to a Polish lawyer and a customs/tax adviser.

## Choosing the template features

| Model | `dropshipping` | `name_suppliers_in_privacy` | `supplier_outside_eea` | notes |
|---|---|---|---|---|
| A domestic supplier | true | optional | false | contract + art. 28 |
| B store-to-store (related party) | true | **true** | false | related-party note, contract even if related |
| C marketplace | true | true (platform) | **true** | lawyer on transfers; avoid toys/electricals |
| D import | not supported | | | lawyer |
| Own stock | false | n/a | false | own-warehouse wording variants (authored 2026-11-21, not in the 2ACE master): lawyer review |
