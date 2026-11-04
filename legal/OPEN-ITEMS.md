# Open items for the shop owner: Regulamin and Polityka prywatności

Prepared 3 November 2026 for 2ACE sp. z o.o. (2acetech.pl). The two documents (`regulamin-sklepu.md`, `polityka-prywatnosci.md`) are in Polish for consumers. This file is in English for you.

**These are drafts based on public sources and on the store's code. They are not legal advice. A Polish lawyer must review them before the shop goes live.**

Contents: 1. Company data check (important correction) · 2. Corrections to the original brief · 3. The four additional requests · 4. Placeholders you must fill · 5. Decisions you must take · 6. What the store really does (code findings) and actions in WooCommerce / theme · 7. Grey-list check (art. 385³ KC) · 8. What a Polish lawyer should confirm · 9. Sources and versions used

---

## 1. Company data check (white-list API and KRS API, 3 November 2026)

| Item | In your brief | Verified value | Source |
|---|---|---|---|
| Name | 2ACE SPÓŁKA Z OGRANICZONĄ ODPOWIEDZIALNOŚCIĄ | same | Biała lista (wl-api.mf.gov.pl) and KRS |
| NIP | 1133212948 | **1133212948** (VAT status "Czynny", VAT registration date shown by the API: 2026-10-01) | Biała lista, KRS |
| KRS | 545746743 | **0001267111** | KRS API (`OdpisAktualny/0001267111`, register P) and Biała lista |
| REGON | not given | **545746743** (9 digits; the KRS extract shows the 14-digit form 54574674300000) | Biała lista, KRS |
| Address | Ostrobramska 101A, lok.301, 04-041 Warszawa | **ul. Ostrobramska 101A, lok. 301, 04-041 Warszawa** (the white list writes it "101A/301") | KRS, Biała lista |
| Share capital | not given | 5 000,00 PLN | KRS |
| Registered in KRS | not given | 17.09.2026; last entry 18.09.2026 (extract state as of that date) | KRS |
| Registry court | not given | Sąd Rejonowy dla m.st. Warszawy w Warszawie, XIV Wydział Gospodarczy KRS. I inferred this from the case file signature "WA.XIV NS-REJ.KRS/…" in the extract. **Check it on the PDF extract (odpis aktualny) from ekrs.ms.gov.pl before publishing.** | KRS (inferred) |

**You were right: the "KRS" in your brief is not a KRS number.** 545746743 has 9 digits and is the company's **REGON**. A KRS number has 10 digits; yours is 0001267111. The documents use the verified values.

Hazard: if someone pads your 9-digit number with zeros or truncates it to 0000545746, the KRS API returns a **different, unrelated company**. Never paste the wrong number into the Customizer, invoices or the footer.

Also noted:

- The white list shows two bank accounts for the company. The account you show to customers for any bank-transfer payment must be one of them (otherwise B2B customers and some payment providers will flag it). I did not put any account number in the documents; you did not give one.
- Phone and e-mail are not in any register. `info@2acetech.pl` is only the theme's default; I treated it as unconfirmed.
- Footer line: set Appearance > Customize > Store details: Seller = `2ACE sp. z o.o.`, Seller ID = `KRS 0001267111 · NIP 1133212948 · REGON 545746743`, address = `ul. Ostrobramska 101A lok. 301, 04-041 Warszawa`.

---

## 2. Corrections to the original brief (with sources)

| # | Your brief | Finding | Source |
|---|---|---|---|
| 1 | KRS 545746743 | Wrong: that is the REGON. KRS is 0001267111. | KRS API, white list (see section 1) |
| 2 | "Art. 17 Consumer Rights Act" for the button | The article is right; the exact provision is **art. 17 ust. 3 UPK** (button or similar function must be labelled "zamówienie z obowiązkiem zapłaty" or another equivalent unambiguous wording). **Art. 17 ust. 2** requires the consumer to expressly confirm that the order carries a payment obligation. **Art. 17 ust. 4**: if ust. 2 or 3 is not met, **the contract is not concluded**. Art. 17 ust. 1 requires the key information (main features, total price, duration, minimum duration) to be shown clearly **directly before** the order. **It is not art. 8 UPK.** Art. 8 covers pre-contract information for contracts concluded *other than* at a distance or off-premises. The source is art. 8(2) of Directive 2011/83/EU. | UPK consolidated text Dz.U. 2026 poz. 1244 |
| 3 | "2-year presumption of non-conformity (art. 43a–43g)" | **Correct in substance.** Since 1 Jan 2023 art. 43c ust. 1 UPK sets **one** period of 2 years from delivery for both things: (a) the seller is liable for non-conformity that existed at delivery and **appears within 2 years**; (b) non-conformity that appears within those 2 years is **presumed** to have existed at delivery (rebuttable; the presumption does not apply if it cannot be reconciled with the nature of the goods or the defect). The period is longer if the seller/manufacturer set a longer shelf life. The seller cannot rely on the period if it fraudulently hid the defect (art. 43c ust. 2). Before 2023 the presumption was 1 year, so older guides are outdated. Chapter 5a is art. 43a–43g (art. 43g covers guarantee statements in advertising and durability guarantees). **For consumers the old "rękojmia" (KC art. 556 ff.) no longer applies** (art. 43a ust. 1), so the Regulamin does not use that word. I found no special limitation period in art. 43a–43g, so the general rules apply (see section 8). | UPK Dz.U. 2026 poz. 1244; KC Dz.U. 2026 poz. 795 |
| 4 | "Check whether the EU ODR platform is still operating" | **Closed.** Regulation (EU) 2024/3228: complaints through the platform were possible until 20 March 2025; Regulation 524/2013 was repealed with effect from 20 July 2025. **Do not link to the ODR platform and do not state the ODR e-mail/link requirement.** What remains: art. 12 ust. 1 pkt 21 UPK (tell consumers about out-of-court complaint and redress options) and the ADR Act (see decision D8). The Regulamin § 8 ust. 6–7 does this. | EUR-Lex (Polish text) |
| 5 | Withdrawal button (Directive 2023/2673) | See section 3, item 1. | EUR-Lex, ISAP |
| 6 | Right of withdrawal "Art. 27" and "Annex 2" | Correct: art. 27 ust. 1 UPK (14 days) and załącznik nr 2 UPK (model form). I also added załącznik nr 1 (the statutory information notice), because art. 13 UPK says a notice that follows the model satisfies art. 12 ust. 1 pkt 9–11. | UPK |
| 7 | "Cookie rules" | The old Telecommunications Law art. 173 was replaced: the rule is now **art. 399 ustawy z 12 lipca 2024 r. Prawo komunikacji elektronicznej** (prior information + consent; exemption in ust. 3 pkt 2 for storage that is strictly necessary for a service the user requested). E-mail/electronic marketing needs prior consent under **art. 398 PKE**. Art. 10 of the old e-services act (anti-spam) is repealed. | PKE, Dz.U. 2024 poz. 1221 (original text; the later amending acts I checked, including Dz.U. 2026 poz. 1296 on the DSA, do not appear to change art. 398–399; ask your lawyer to confirm on the current consolidated text) |
| 8 | Returns address, delivery costs | A returns address is not named in art. 12 UPK as such, but the consumer must be told how and where to exercise withdrawal (art. 12 ust. 1 pkt 9) and the model notice tells them where to send the goods. So a returns address is needed in practice. A separate complaints address is required only if it differs from the business address (art. 12 ust. 1 pkt 4). Delivery costs must be in the total price information (art. 12 ust. 1 pkt 5). | UPK |
| 9 | "Grey list: Art. 385(3) Civil Code" | Correct: art. 385³ KC, 23 points. Table in section 7. | KC |
| 10 | Brief to me: art. 22 UPK = maximum 30 days to deliver | **Not so.** Art. 22 UPK says the pre-contract information in art. 12 ust. 1 forms part of the contract and can be changed only by express agreement of the parties. I found **no** express "30 days" delivery rule anywhere in the UPK (the 30 days in the text relate to off-premises contracts and insurance, not delivery). Directive 2011/83 art. 18 has that rule, but the Polish act does not copy it. What applies: art. 455 KC (performance immediately after a call if no date), art. 491–492 KC (additional deadline and withdrawal for delay). Art. 22 UPK matters for you in another way: the delivery time you show before the order becomes a **term of the contract**, so "delivery times are estimates" cannot be a free right to change them (and would look like grey-list points 8, 10 and 19). The draft therefore treats the shipping preparation time as binding, only the carrier transit as an estimate, and adds a 30-day outer limit as your own promise (section 3, item 2). | UPK; KC |

---

## 3. The four additional requests

### 3.1 Withdrawal function (Directive 2023/2673)

**What the directive says.** Directive (EU) 2023/2673 adds art. 11a to Directive 2011/83/EU (Polish official text checked on EUR-Lex): for contracts concluded through an online interface the trader must offer a withdrawal function; it is labelled "odstąp od umowy tutaj" (or an equivalent unambiguous wording); it must be continuously available for the whole withdrawal period and prominent and easily accessible; it lets the consumer send an online withdrawal statement with name, details identifying the contract and the electronic means for the acknowledgement; a confirmation function labelled "potwierdź odstąpienie od umowy" (or equivalent); and the trader must send an acknowledgement of receipt on a durable medium **without undue delay**, showing the content and the date and time of the statement. A statement made online before the deadline counts as timely. Art. 6(1)(h) is also changed: before the contract the trader must tell the consumer about the **existence and location** of the function. Member States had to adopt the rules by **19 December 2025** and apply them from **19 June 2026**.

**Polish status as of 3 November 2026 (from ISAP):**

- The consolidated Consumer Rights Act (Dz.U. 2026 poz. 1244, legal state 2 September 2026) has **no** equivalent of art. 11a and no mandatory withdrawal function.
- The act of 4 Sept 2026 (Dz.U. 2026 poz. 1353, published 19 Oct 2026) amends the UPK but only implements Directive 2024/825 (green claims, durability, repair; it enters into force **27 September 2027** as published). It does not contain the withdrawal function.
- Trade press (not primary sources) reports a Ministry of Finance draft **UC169** (amending the UPK and the pawn-loan act) that would introduce the "funkcja odstąpienia od umowy", with no adoption date; one article expects it to reach the Council of Ministers in Q1 2027. Earlier, the rules were meant to travel with the consumer credit bill UC82; press reports say the government withdrew that mandate. I could not open legislacja.gov.pl, so treat UC169 details as unverified.
- Consequence: Poland is late. A directive that is not transposed does not by itself create a duty for a private shop, so **as of today the button is not yet a Polish legal duty**. But (a) art. 30 ust. 3–4 UPK **already** regulates what happens when a shop *offers* electronic withdrawal: the consumer may withdraw on the shop's website, and the shop must **promptly** send confirmation of receipt on a durable medium; (b) the Polish law will almost certainly follow the directive wording; (c) it is cheap to comply now. So the Regulamin describes the function exactly as built.

**What the Regulamin promises (§ 6 ust. 4–12), matched to the code in `inc/withdrawal.php` and `template-withdrawal.php`:** page "Odstąp od umowy tutaj" at `/odstap-od-umowy/`; link in the footer of every page, in My Account > Orders next to each order, and on the order confirmation page; fields: first and last name, order number, e-mail for the acknowledgement, optional note which goods (default whole order); confirm button "Potwierdź odstąpienie od umowy tutaj"; confirmation screen with date and time; acknowledgement e-mail with the declaration and the date and time; a copy to the store; no refund promised automatically; no login needed.

Remarks:

- The directive's wording for the second step is "potwierdź odstąpienie od umowy". The theme adds "tutaj". That is fine ("or an equivalent unambiguous wording").
- The theme also puts the link in customer order e-mails. The Regulamin does not mention that, so it promises nothing extra.
- Pre-contract information: art. 6(1)(h) (as amended) wants the consumer told where the function is. The Regulamin and the confirmation e-mail attachment do that; consider also a one-line note near the checkout.
- Operational duty: someone must watch the "Withdrawals" admin list and the store's copy e-mail, and handle the return and refund within 14 days. The page does not do that automatically.

### 3.2 Prepayment only, international shipping

- **No COD:** Regulamin § 5 ust. 1 says the shop accepts only prepayment. **You must switch off "Cash on delivery" (and any "pay on pickup") in WooCommerce > Settings > Payments** so the store matches the text. Also remove any COD fee setting.
- **Customs and import taxes outside the EU:** allowed, **if disclosed before the order**. Art. 12 ust. 1 pkt 5 UPK: where additional costs cannot be calculated in advance, the trader must say that the consumer must pay them. Art. 23 UPK: if the trader did not meet the duty to inform about additional costs, the consumer does not bear them. So the clause is in § 5 ust. 10–11, and you should also show a short note in the cart/checkout for non-EU destinations (art. 18 UPK also requires delivery restrictions and accepted payment methods to be shown clearly at the start of the ordering process). Whether customs charges paid to authorities are refundable by you after withdrawal is a lawyer question (section 8).
- **"Delivery times are estimates":** handled as explained in item 10 of section 2: preparation time binding, transit time estimated, 30-day outer limit, KC art. 491–492 rights kept.
- **Which countries you ship to is a placeholder** (`[LISTA KRAJÓW DOSTAWY W UE]`, `[LISTA KRAJÓW SPOZA UE]`). The WooCommerce shipping zones and the "Selling location(s)" setting must match the list exactly. Shipping outside the EU also needs a tax adviser for VAT (export, 0% VAT with proof of export) and for the customs documents. Selling to consumers in other EU countries brings VAT questions (OSS scheme); ask your accountant before you enable them.
- Applicable law for foreign consumers: the Regulamin chooses Polish law but, as art. 6(2) Rome I requires, cannot take away mandatory protection of the consumer's home country. This is stated in § 12 ust. 1.

### 3.3 Right of withdrawal and the cost of return (art. 27–38 UPK)

Checked against the consolidated text. Summary of what the Regulamin (§ 6) says and why:

- 14 days (art. 27 ust. 1); starts from taking possession of the goods; for several goods delivered separately, the last one; for batches, the last batch (art. 28 pkt 1).
- Declaration by any unambiguous statement, optional model form, or on the website (art. 30 ust. 1 and 3); timely if sent before the end of the period (art. 30 ust. 2); acknowledgement promptly on a durable medium (art. 30 ust. 4).
- Deadline day falling on a Saturday or public holiday moves to the next working day (art. 115 KC). I included this; ask the lawyer if they want it stated.
- **Consumer bears the direct cost of return unless you agree to bear it** (art. 34 ust. 2). **The clause only works if you told the consumer about this cost before the order.** Art. 34 ust. 2 itself says the consumer does not bear the cost if the trader did not inform about it, and art. 23 UPK repeats it for return costs under art. 12 ust. 1 pkt 10. This is why the Regulamin states it (§ 6 ust. 15), repeats it in the Annex 1 notice, and why you must **send the Regulamin plus both annexes with the order confirmation** (section 6, action A4). For goods that cannot be returned by ordinary post, art. 12 ust. 1 pkt 10 requires an estimate of the return cost; there is a placeholder.
- Refund within 14 days of receiving the declaration, including the delivery cost, but not the extra cost of a more expensive delivery method than the cheapest standard one you offer (art. 32 ust. 1, art. 33). Same payment method unless the consumer expressly agrees otherwise at no cost (art. 32 ust. 2). You may hold the refund until you get the goods back or proof of dispatch, whichever is earlier, if you did not offer to collect them (art. 32 ust. 3).
- Consumer returns the goods within 14 days from withdrawal (art. 34 ust. 1); liable only for diminished value from handling beyond what is needed to establish the nature, characteristics and functioning (art. 34 ust. 4). This sentence is lost if you did not inform the consumer of the withdrawal right in line with art. 12 ust. 1 pkt 9, which is another reason to send the annexes.
- **Exceptions (art. 38 ust. 1)** that matter for an electronics shop: pkt 3 (made to the consumer's specification, e.g. engraving), pkt 5 (sealed packaging that cannot be returned for health or hygiene reasons, opened after delivery), pkt 6 (goods inseparably combined with other goods), pkt 9 (sealed audio/video recordings or software, opened after delivery). The exceptions must be named in advance (art. 12 ust. 1 pkt 12). **An opened electronic device is not excluded**; the consumer may test it, and you can only deduct diminished value. Using pkt 5 for in-ear headphones is common but contestable; decision D6.
- **Cross-border and non-EU returns:** same rules; the consumer chooses the carrier and pays the direct cost. Customs treatment of goods coming back into the EU is a placeholder (D7).
- **Who bears loss in transit:** to the consumer, the seller bears the risk until the goods are handed to the consumer (art. 548 § 3 KC; handing the parcel to a carrier counts as handing it to the consumer only if the seller had no influence on the choice of carrier). On returns, the UPK is silent on who bears the risk. Art. 32 ust. 3 lets you withhold the refund only until proof of dispatch, which pushes in the consumer's favour. The Regulamin deliberately does not shift the risk of return to the consumer (a clause doing so could be attacked under art. 385¹ KC) and only says what to do if a return parcel is lost. Lawyer to confirm (section 8).

### 3.4 WooCommerce integration note: the order button

- **Which provision:** art. 17 ust. 3 UPK (see section 2, item 2); the brief's "art. 17" is right at article level.
- **The theme already does it.** `inc/woocommerce.php` sets the classic checkout button text through `woocommerce_order_button_text`, and `assets/js/checkout.js` sets it for the block checkout through `registerCheckoutFilters(... placeOrderButtonLabel ...)`. The English string is "Order with obligation to pay". The Polish translation in `languages/pl_PL.po` is **"Zamówienie z obowiązkiem zapłaty"**. You do not need to configure anything in WooCommerce.
- **Three things you must do:**
  1. Keep the site language set to **Polish** (Settings > General > Site Language) so the Polish translation is loaded; otherwise customers see the English label on a Polish shop. If you ever change the wording, change it consistently in the `.po` file and recompile the `.mo`.
  2. Do **not** install a plugin that overrides the label (checkout-field editors, payment-gateway plugins with their own "Pay now" or "Place order" button, one-click or express-pay buttons such as Apple Pay, Google Pay, PayPal or Stripe Link buttons, if shown on the checkout). **Every button that completes an order must carry the wording**, because art. 17 ust. 4 means a non-compliant order is not a contract.
  3. Test with a real test order in Polish, on desktop and phone, with each payment method, and look at the actual button text.
- Note on the exact words: the theme now uses the literal statutory phrase **"Zamówienie z obowiązkiem zapłaty"** (changed after the first draft of this file, which suggested the equivalent "Zamawiam z obowiązkiem zapłaty"), and the Regulamin quotes the same words. If you ever change the wording, change it in both places.
- Art. 17 ust. 1 also requires that directly before the order the shop shows the main features of the goods and the total price with taxes and delivery. The default WooCommerce checkout summary does this, but check it on the test order.
- The terms checkbox: WooCommerce > Settings > Advanced > Page setup > "Terms and conditions" = the Regulamin page. Then the checkout shows the acceptance box that § 4 ust. 2 pkt 5 refers to.

---

## 4. Placeholders you must fill (all in square brackets in the documents)

| Placeholder | Where | What you must provide |
|---|---|---|
| `[DATA]`, `[DATA WEJŚCIA W ŻYCIE]` | both | publication date and effective date |
| `[ADRES E-MAIL SKLEPU]` | both | the real shop e-mail (also set in Customize > Store details) |
| `[NUMER TELEFONU SKLEPU]` | Regulamin § 1, Annex 1 | phone number (art. 12 ust. 1 pkt 3 UPK requires an e-mail and a phone number); the call must not cost more than an ordinary call (art. 11 UPK) |
| `[ADRES DO REKLAMACJI]` | § 1, § 8 | complaints address (or "same as registered office") |
| `[ADRES DO ZWROTÓW]` | § 1, § 6, Annex 1 | postal address where customers send returns; it may be a warehouse, not the registered office |
| `[LISTA METOD PŁATNOŚCI]`, `[NAZWA OPERATORA PŁATNOŚCI]` | § 5 ust. 2; privacy §3, §6 | the payment methods and provider you really enable; provider's privacy-policy URL and cookies |
| `[LICZBA]` (payment term) | § 5 ust. 3 | days to pay a bank transfer (only if you offer it) |
| `[LICZBA]` (preparation time) | § 5 ust. 7 | working days to prepare and dispatch an order; binding on you |
| Delivery table: `[SPOSÓB DOSTAWY]`, `[PRZEWOŹNIK]`, `[CENA]`, `[CZAS W DNIACH ROBOCZYCH]` | § 5 ust. 6 | one row per delivery method, with the same data as in WooCommerce shipping |
| `[LISTA KRAJÓW DOSTAWY W UE]`, `[LISTA KRAJÓW SPOZA UE]` | § 5 ust. 5 | countries you ship to (see 3.2) |
| `[DODAJ: darmowa dostawa…]` | § 5 ust. 6 | free-shipping threshold, if any |
| `[DECYZJA: … dostawa każdej części]` | § 5 ust. 9 | whether split shipments cost extra |
| `[RODZAJ DOKUMENTU…]`, `[ZASADY WYSTAWIANIA FAKTUR…]` | § 5 ust. 4 | receipt/invoice rules, agreed with your accountant (including prepayment and e-invoicing/KSeF questions) |
| `[WARIANT A / B]` return cost | § 6 ust. 15, Annex 1 | A = consumer pays (default); B = you pay (all returns or in defined cases) |
| `[KWOTA lub „nie dotyczy”]` | § 6 ust. 15 | estimated return cost for goods that cannot go by ordinary post |
| `[DECYZJA: … ponownym imporcie…]` | § 6 ust. 21 | customs/VAT on goods returned from outside the EU |
| `[WPISZ KONKRETNE TOWARY…]` | § 6 ust. 23 | which goods fall under art. 38 pkt 5 (sealed, hygiene); or delete |
| `[WARIANT A / B]` guarantee | § 7 ust. 9 | A = you give no own guarantee (manufacturer's guarantee may exist); B = your own guarantee with the content required by KC art. 577¹ |
| `[PROCEDURA…]` | § 8 ust. 3 | how a defective item is sent back at your cost (courier you order, or refund of the shipping cost) |
| ADR statement `[WARIANT A / B]` | § 8 ust. 7 | decision D8 |
| `[POTWIERDŹ: czy telefon jest wymagany]` | § 4 ust. 2, privacy | whether the checkout asks for a phone number (a delivery-courier need) |
| `[DECYZJA: wiadomość o przyjęciu Zamówienia…]` | § 4 ust. 4 | when the contract is concluded: acceptance e-mail immediately or after payment |
| `[DECYZJA WŁAŚCICIELA: czy Sklep sprzedaje także przedsiębiorcom?]` | § 1 ust. 3 | B2C only, or B2B too |
| Newsletter paragraph | § 10; privacy row 10 | keep only if you really start a newsletter (D3) |
| Privacy: `[PEŁNA NAZWA I KRAJ DOSTAWCY HOSTINGU]`, `[NAZWA DOSTAWCY POCZTY / SMTP]`, `[LISTA PRZEWOŹNIKÓW]`, `[NAZWA … biuro rachunkowe]` | privacy § 3 | the actual processors. Your hosting is Hostinger (per project notes); the exact legal entity and data location are in your hosting contract |
| `[OKRES PRZECHOWYWANIA KORESPONDENCJI]`, `[OKRES PRZECHOWYWANIA LOGÓW…]` | privacy table rows 7, 9 | your retention periods |
| `[PRAWNIK POTWIERDZA OKRES]` | privacy row 3 | the limitation period for claims |
| Privacy contact address, IOD note | privacy § 1 | a data-protection contact e-mail; confirm you do not need a DPO |
| Third-country transfers | privacy § 4 | name any supplier that processes data outside the EEA |
| `[POTWIERDŹ po uruchomieniu SSL]` | privacy § 7 | HTTPS active |
| Social media profile note | privacy § 6.2 | delete if you have no profiles |
| Reviews / comments / Gravatar blocks | privacy rows 11, §6.2 | keep only if product reviews or comments are on (D5) |

---

## 5. Decisions you must take

- **D1. B2C only?** The brief says consumers only. If you will also sell to companies, add a separate B2B regulamin; the withdrawal and non-conformity chapters of the UPK do not apply to companies.
- **D2. Who pays for returns.** Default in the text: the consumer. Offering free returns is a competitive choice, not a legal duty. Either way keep the wording consistent in § 6 ust. 15 and Annex 1.
- **D3. Newsletter.** The current built-in form only e-mails each address to the site admin; it stores no list, has no consent text, no consent record and no unsubscribe (see section 6). Do **not** send commercial e-mails until you add: a clear consent statement or checkbox with a link to the privacy policy, a record of consent (date/time), a sending tool with unsubscribe in every message, and, ideally, double opt-in. If you will not run a newsletter yet, remove the subscribe band or delete § 10 of the Regulamin and row 10 of the privacy policy.
- **D4. Order Attribution (WooCommerce).** Recent WooCommerce versions have "Order Attribution" which sets `sbjs_*` cookies and writes the visitor's source and device to each order. These cookies are **not** strictly necessary, so they would need prior consent under art. 399 PKE. The privacy policy assumes the feature is **off**: WooCommerce > Settings > Advanced > Features > Order Attribution = off. Alternatively keep it on, add a consent banner that blocks it, and rewrite § 6 of the policy.
- **D5. Product reviews and comments.** If you enable reviews: add them to the privacy policy (rows 11 and the `comment_author_*` cookie), decide whether to show Gravatar avatars (Settings > Discussion > Show avatars: switch off to avoid sending a hash of the reviewer's e-mail to a third party), and describe how you check that reviews come from real buyers (an EU consumer-protection rule for shops that publish reviews; I did not verify the Polish article, so ask your lawyer).
- **D6. art. 38 pkt 5 exception for in-ear headphones.** Common practice but contestable. Safer: allow returns of opened in-ear products if hygiene is not at stake, or only exclude when the factory seal is broken after delivery and you say so on the product page.
- **D7. Returns from outside the EU:** who pays customs/VAT on re-import.
- **D8. Out-of-court dispute resolution.** You are not obliged to take part. If you commit (or a special law obliges you), you must name the competent entity on your website and in your contract templates (art. 31 ADR Act). If a complaint ends without a solution you must send the consumer, on paper or a durable medium, a statement whether you will take part or refuse (art. 32). If you say nothing at all, art. 32 ust. 3 of the ADR Act treats it as agreement. Variant A in the text is the usual middle route: you do not commit in advance but will answer each time. (I read the ADR Act from the ISAP text of 2016; check the current wording.)
- **D9. Own guarantee.** None promised in the text unless you choose Variant B. If you or a manufacturer give a guarantee, the guarantee statement must be in Polish, clear, and include the items in KC art. 577¹ § 2 (including a statement that the guarantee does not affect the statutory remedies for non-conformity). You must also hand the guarantee document over with the goods (KC art. 577³) and tell the consumer about guarantees before the order (art. 12 ust. 1 pkt 14 UPK).
- **D10. Maximum delivery time.** The text commits you to deliver within 30 days of the contract if no other date is shown, and to a binding preparation time. If your suppliers cannot meet that, shorten the promise only by listing real, longer lead times on the product page (they become part of the contract under art. 22 UPK).
- **D11. When the contract is concluded** (acceptance e-mail before or after payment). Align your WooCommerce emails with § 4 ust. 4.

---

## 6. What the store really does, and actions to take in WooCommerce / the theme

**Findings from the code (`wp-content/themes/2acetech`):**

- Checkout button: "Order with obligation to pay" (EN) / "Zamówienie z obowiązkiem zapłaty" (PL), classic and block checkout.
- Contact form (`inc/contact.php`): name, e-mail, optional order number, message; e-mailed to the shop; **nothing is stored in the database**; a transient with a hash of the visitor's IP address lasts 1 hour to rate-limit (5 per hour). Privacy note on the form: "We use your details only to answer this message."
- Newsletter (`inc/newsletter.php`): e-mails the entered address to the site admin e-mail; no list is stored; no consent text, no consent log, no unsubscribe. Honeypot and nonce only. It can be replaced by a mailing-list shortcode in Customize.
- Withdrawal function (`inc/withdrawal.php`): stores each declaration as an admin-only "Withdrawals" entry (name, order number, e-mail, note, time); acknowledgement e-mail to the consumer; copy to the store; transient hash of IP for 1 hour (10 per hour).
- WooCommerce accounts and orders: standard WooCommerce data (name, address, e-mail, phone depending on settings, order history).
- No third-party scripts, no analytics, no embedded media; **fonts are self-hosted** (`assets/fonts/manrope-*`); theme JavaScript uses no cookies or local storage. Social "share" buttons on products are plain links.
- Cookies expected: WooCommerce cart/session cookies and WordPress login cookies (list in the privacy policy §6.2). Product comments/reviews, if open, set `comment_author_*` only when the visitor ticks the "save my details" box and may show Gravatar avatars.

**Actions for you (A1–A10):**

| # | Action |
|---|---|
| A1 | Switch off Cash on delivery and any pay-on-pickup (WooCommerce > Settings > Payments). |
| A2 | Settings > General > Site language = Polish. Confirm the order button reads "Zamówienie z obowiązkiem zapłaty" on a test order (desktop and phone, every payment method). |
| A3 | WooCommerce > Settings > Advanced > Features > Order Attribution = off (D4). Also Settings > Advanced > WooCommerce.com: do not allow usage tracking. |
| A4 | Send the Regulamin (PDF) with Annex 1 and Annex 2 to every customer **on a durable medium** with the order confirmation (art. 21 ust. 1 UPK: confirmation with the art. 12 information, at the latest at delivery; art. 12 ust. 1 pkt 9 requires the withdrawal information and the model form). Use an e-mail attachment on the "order received/processing" e-mail (WooCommerce > Settings > Emails, or a PDF-attachment plugin). Also make the Regulamin, Annexes and Privacy Policy pages available in the footer (Appearance > Menus). |
| A5 | Create pages: Regulamin (set as WooCommerce "Terms and conditions"), Polityka prywatności (Settings > Privacy), and put the model withdrawal form (Annex 2) on the Regulamin or a returns page. Remove all bracketed notes first. |
| A6 | Settings > Discussion: turn off avatars, or turn off comments and product reviews, unless you want them (D5). |
| A7 | Add a consent checkbox and a link to the privacy policy to the newsletter form, or remove the form until the tool is ready (D3). (Needs a theme change; the contact form already links the policy.) |
| A8 | Test the cookies on the live shop with the browser developer tools (guest, logged in, after adding to cart, on the payment page) and complete the table in the policy §6.2. Payment plugins (Stripe, Przelewy24, PayU, BLIK) often load their own scripts and cookies on the checkout page. If they do, add them to the policy; some may need a consent banner. The "no third-party scripts" fact is true for the theme, not for the payment plugin. |
| A9 | Check that "Order Attribution", "usage tracking", Jetpack, and any plugin that calls external services are off or listed in the privacy policy. |
| A10 | Prices and promotions: any price reduction you announce (including the deal countdown and strikethrough prices) must show the **lowest price from the previous 30 days** next to the reduced price (art. 4 ust. 2–3 ustawy o informowaniu o cenach towarów i usług, Dz.U. 2023 poz. 168). The theme shows the regular price exactly as you enter it, so you have to enter the correct reference price. Show unit prices where required. Prices must be gross, with the delivery cost visible before the order. |

---

## 6a. Cookie banner (added 4 November 2026)

The theme now shows a consent banner on the first visit (logo, text, three equal buttons: Reject all, Manage cookies, Accept) and a settings panel with three categories (necessary, analytics, marketing). The choice is stored in the cookie `ace_consent` for 180 days and can be changed with "Ustawienia cookies" in the footer.

- **Reject is as easy as Accept.** The reference site you showed has only "Manage cookies" and "Accept". I added "Reject all" on purpose: the EU and Polish regulators (UODO) treat a banner without an equally easy way to refuse as invalid consent. Keep the third button.
- **The banner currently controls nothing**, because the shop sets no analytics or marketing cookies. It does matter later: any analytics or ad script must be loaded only after consent. In HTML use `<script type="text/plain" data-ace-consent="analytics" src="...">`; in JavaScript `window.aceConsent.has('analytics')`; in PHP `ace_consent_allowed('analytics')`. Plugins that add their own tracking (Google Site Kit, Meta pixel, chat widgets) do not follow this automatically. Do not install them without checking.
- **Privacy policy §6** was rewritten to match (banner, `ace_consent` row, how to change the choice).
- A lawyer should confirm the wording of the banner and of §6, and the 180-day lifetime of the choice.

---

## 7. Grey-list check (art. 385³ KC) and related checks

I read the 23 points of art. 385³ KC in the current Civil Code (Dz.U. 2026 poz. 795) and checked every clause that could touch them.

| Regulamin clause | Grey-list point(s) checked | Result |
|---|---|---|
| § 1 ust. 6, § 4 ust. 2 pkt 5, § 4 ust. 7 (Regulamin available before the order, tick-box, sent with the confirmation) | pkt 4 (no chance to read before the contract) | OK. Regulamin is accessible on the site, a checkbox is required, and a copy is sent. |
| § 2 ust. 2–4 (account; termination) | pkt 14 (only consumer deprived of the right to terminate), pkt 15 (seller may end an indefinite contract without important reasons or reasonable notice) | OK. Consumer can end the account at any time at no cost; seller only for listed important reasons with 14 days' notice. |
| § 2 ust. 5, § 10 (account not required; newsletter voluntary) | pkt 7 (tying the contract to another unrelated contract), pkt 6 | OK. No purchase is conditional on an account or newsletter. (Depends on the guest-checkout setting.) |
| § 3 ust. 1 (offer data is part of the contract; changed only by agreement) | pkt 10, 19, 20 (unilateral change of the contract or price) | OK. Follows art. 22 UPK; no right for the shop to change price or features after the contract. |
| § 3 ust. 5 (no surcharge for payment method) | art. 383¹ KC (no surcharge above the trader's cost) | OK. |
| § 4 ust. 4–5 (acceptance; limited reasons to refuse or cancel) | pkt 8 (performance depends only on the trader's will), pkt 12 and 13 (no refund of payment for unperformed service), pkt 2 | OK. Closed list of objective reasons; full refund within 14 days. |
| § 4 ust. 6 (consumer can withdraw the offer before acceptance) | pkt 14 | OK. Follows art. 31 ust. 2 UPK. |
| § 5 ust. 3 (cancel unpaid orders) | pkt 8, 16–17 (penalties) | OK. No fee or penalty. |
| § 5 ust. 7–8 (delivery time: preparation binding, transit estimated, 30-day limit, art. 491–492 KC kept) | pkt 8, 10, 19 (time left to the trader's discretion), pkt 2 (limiting liability for non-performance), pkt 22 | OK as drafted. **Would NOT be OK** as a bare "all delivery times are estimates and may change". |
| § 5 ust. 9 (risk in transit) | pkt 2; art. 548 § 3 KC (less favourable clauses are void) | OK. Risk stays with the seller until delivery to the consumer. |
| § 5 ust. 10–11 (customs and import taxes outside the EU) | pkt 20 (price increase after the contract without a right to withdraw), pkt 12–13 | Lower risk. The charges are third-party taxes, disclosed before the order (art. 12 ust. 1 pkt 5, art. 23 UPK), and the right to withdraw is untouched. The sentence that charges paid to authorities are not refunded by the seller is flagged for the lawyer. |
| § 6 (withdrawal: 14 days, no reason, effects, exceptions) | pkt 14 (deprives consumer of withdrawal), pkt 12–13 (refund), pkt 16–17 (penalties), art. 7 UPK (waiver void) | OK. No restocking fee, no shorter period, no "opened packaging" ban, exceptions only as in art. 38. Cost of return and diminished value exactly as art. 34 ust. 2 and ust. 4. |
| § 6 ust. 19 (deduction for diminished value) | pkt 3 (excludes or limits consumer's set-off) | OK. The clause concerns the trader's deduction, and the consumer's set-off is untouched. |
| § 6 ust. 16 (partial withdrawal: proportional delivery refund) | pkt 12–13 | Flagged for the lawyer (section 8). |
| § 7 (non-conformity: art. 43a–43g, 2 years, presumption, remedies) | pkt 1–2 (excluding or limiting liability), art. 7 UPK | OK. Mirrors the statute, shortens nothing, and says guarantee is additional. No use of "rękojmia". |
| § 8 (complaints: 14 days, silence = accepted, any channel) | pkt 4, 9 and 11 (trader alone decides conformity or interprets), pkt 21 (excessive formalities) | OK. Answer in 14 days (art. 7a UPK), no formal requirements. |
| § 8 ust. 8 (courts) | pkt 23 (excluding Polish courts, arbitration, imposing a court) | OK. No jurisdiction or arbitration clause. |
| § 11 (changes to the Regulamin) | pkt 10 and 19 (unilateral changes without an important reason stated in the contract) | OK. Closed list of reasons, 14 days' notice, earlier orders keep the old version, free exit for account holders. |
| § 12 ust. 1 (Polish law) | pkt 23; Rome I art. 6(2) | OK. Mandatory home-country consumer rights preserved. |
| § 12 ust. 2–3 | art. 385¹ § 2 KC, art. 7 UPK | OK. |
| **Clauses deliberately not included** | pkt 2, 5, 16–17, 20, 23 | No liability cap, no force-majeure exclusion, no assignment of the contract without consent (pkt 5), no contractual penalties, no "price error – we may cancel" clause (pkt 8, 20), no jurisdiction clause, no "photos are for illustration" disclaimer, no restocking fee, no shorter withdrawal period. Do not add these later without a lawyer. |

---

## 8. What a Polish lawyer should confirm

1. The registry court and the full KRS extract data (section 1).
2. Whether the withdrawal-function clause (§ 6 ust. 6–12) matches the final Polish law once UC169 or its successor is adopted, and whether the "tutaj" suffix and the page design are acceptable.
3. Customs and import charges outside the EU: whether the sentence that the seller does not refund charges paid to authorities is acceptable (grey-list pkt 12–13, art. 32 UPK "all payments"), and who pays on re-import of returned goods.
4. Partial withdrawal: refund of delivery cost "in proportion" (§ 6 ust. 16).
5. Risk in return shipping: the UPK is silent; the draft is neutral. Confirm the safest wording.
6. The 30-day outer delivery limit and the "binding preparation time / estimated transit" split under art. 22 UPK and art. 385³ pkt 8, 10, 19.
7. Limitation period for non-conformity claims: I found no special period in art. 43a–43g, so I assumed the general rules of KC art. 118 (6 years; 3 years for claims linked to a business; the end falls on the last day of the calendar year). Retention in the privacy policy (3 years) depends on this.
8. Retention periods (privacy table) and the legal bases chosen, including contact-form data (art. 6 ust. 1 lit. f vs. b).
9. The ADR Act text: I read the 2016 text from ISAP. Check the current consolidated version of art. 31–32 and whether sector rules oblige you to join ADR.
10. Art. 398–399 PKE on the current consolidated text (Dz.U. 2024 poz. 1221 is the original text; later amendments exist). Likewise art. 6–8 of the e-services act (Dz.U. 2024 poz. 1513), which Dz.U. 2026 poz. 1296 amends from 6 November 2026 (DSA implementation; I saw no change to art. 8).
11. The conditional blocks (reviews, newsletter, guarantee variants, art. 38 exceptions).
12. **Not checked in primary sources and outside the brief, but relevant to an electronics shop (ask the lawyer or adviser):** product-safety information online (Regulation (EU) 2023/988: manufacturer name, address and product identifier on the offer); waste electrical and electronic equipment (take-back information and registration duties for sellers of electronics, and batteries rules); the European Accessibility Act for e-commerce (including the micro-enterprise exemption); the geo-blocking regulation (EU) 2018/302 if you sell across borders; consumer-review rules; VAT/OSS for EU consumers.
13. Upcoming change: Dz.U. 2026 poz. 1353 adds duties from **27 September 2027** (as published): a harmonised "legal guarantee" information notice (Implementing Regulation (EU) 2025/1960), information on a manufacturer's durability guarantee label, software-update period for goods with digital elements, repairability score and spare-parts information. Plan to update the Regulamin and the product pages before then.

---

## 9. Sources and versions used (all accessed 3 November 2026)

- Ustawa o prawach konsumenta, **tekst jednolity Dz.U. 2026 poz. 1244** (announcement of 4 Sept 2026; legal state 2 Sept 2026), via the Sejm ELI API (api.sejm.gov.pl). Articles used: 7, 7a, 7aa, 8, 12, 13, 17, 18, 21, 22, 23, 27–38, 43a–43g, załączniki nr 1 and 2. The Regulamin refers to this consolidated text; update the reference when a new one is published.
- Ustawa z 4 września 2026 r. o zmianie ustawy o przeciwdziałaniu nieuczciwym praktykom rynkowym oraz ustawy o prawach konsumenta, Dz.U. 2026 poz. 1353 (in force 27 Sept 2027 as published).
- Kodeks cywilny, Dz.U. 2026 poz. 795: art. 22¹, 115, 118, 383¹, 385–385⁵, 454¹, 455, 491–492, 548, 577–581.
- Ustawa o świadczeniu usług drogą elektroniczną, Dz.U. 2024 poz. 1513 (art. 6–10); Ustawa o pozasądowym rozwiązywaniu sporów konsumenckich, Dz.U. 2016 poz. 1823 (original text; art. 31–32); Ustawa o informowaniu o cenach towarów i usług, Dz.U. 2023 poz. 168 (art. 4); Prawo komunikacji elektronicznej, Dz.U. 2024 poz. 1221 (art. 398, 399); Ustawa o rachunkowości, Dz.U. 2026 poz. 522 (art. 74); Ustawa o VAT, Dz.U. 2026 poz. 1263 (art. 112).
- EUR-Lex / Publications Office (Polish text): Directive (EU) 2023/2673 (art. 11a, art. 2 dates); Regulation (EU) 2024/3228 (end of the ODR platform); Regulation (EU) 2016/679 (art. 6, 7, 12, 13, 15–18, 20–22, 49, 77); Regulation (EC) 593/2008 (art. 6).
- Registers: Biała lista podatników VAT (wl-api.mf.gov.pl, NIP 1133212948) and KRS API (api-krs.ms.gov.pl, 0001267111).
- Polish press and practitioner articles on the withdrawal button status (prawo.pl 9 June 2026; product-law.pl 10 Feb 2026; akademialtca.pl 11 Sept 2026): used only for the status of draft UC169; **not primary sources**.
- WooCommerce documentation (woocommerce.com/document/woocommerce-cookies and /order-attribution-tracking) for the cookie list. Please confirm on the live shop.
- Authority websites checked to exist: polubowne.uokik.gov.pl, uokik.gov.pl, prawakonsumenta.uokik.gov.pl, konsument.gov.pl, uodo.gov.pl.
