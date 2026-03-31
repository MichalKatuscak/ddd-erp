# Sales Module Design — Poptávky a Nabídky

**Datum:** 2026-04-01
**Status:** Schváleno

---

## Přehled

Nový Bounded Context `packages/sales` pokrývá obchodní pipeline před vznikem zakázky:

```
Poptávka (Inquiry) → Nabídka (Quote) → Zakázka (Order) [v Planning BC]
```

Zákazník může být existující z CRM nebo nový — při zadání poptávky se volitelně vytvoří.
Jeden obchodník řídí celý proces bez schvalovacího workflow.

---

## 1. Doménový model

### Inquiry (Poptávka) — Aggregate Root

- `InquiryId` — UUID
- `customerId: ?CustomerId` — reference na CRM zákazníka (nullable)
- `customerName: string` — denormalizováno (platné i pro nového zákazníka)
- `contactEmail: string`
- `description: string`
- `requestedDeadline: ?DateTimeImmutable`
- `requiredRoles: RequiredRole[]` — value objecty (role + skills[])
- `attachments: Attachment[]` — value objecty (path, mimeType, originalName)
- `status: InquiryStatus` — enum: `new | in_progress | quoted | won | lost | cancelled`
- `createdAt: DateTimeImmutable`

### Quote (Nabídka) — Aggregate Root

- `QuoteId` — UUID
- `inquiryId: InquiryId`
- `phases: QuotePhase[]`
- `totalPrice: Money` — value object (amount + currency)
- `validUntil: DateTimeImmutable`
- `status: QuoteStatus` — enum: `draft | sent | accepted | rejected`
- `pdfPath: ?string`
- `notes: string`

### QuotePhase (entita, vlastněná Quote)

- `QuotePhaseId` — UUID
- `name: string`
- `requiredRole: WorkerRole` — reuse enumu z Planning BC
- `durationDays: int`
- `dailyRate: Money`
- `subtotal: Money` — vypočteno: `durationDays × dailyRate`

### Stavové přechody

```
InquiryStatus:  new → in_progress → quoted → won / lost / cancelled
QuoteStatus:    draft → sent → accepted / rejected
```

Kdy `Inquiry` přejde do `won`: `AcceptQuote` handler nastaví Quote na `accepted`, emituje `QuoteAccepted` domain event, a zároveň odešle `AdvanceInquiryStatus` command → Inquiry přejde do `won`. Planning BC poslouchá `QuoteAccepted` a vytvoří `Order`.

---

## 2. Backend architektura

### Package struktura

```
packages/sales/
  composer.json
  src/
    Inquiry/
      Domain/
        Inquiry.php
        InquiryId.php
        InquiryStatus.php          — enum
        RequiredRole.php           — value object (role + skills[])
        Attachment.php             — value object (path, mimeType, originalName)
        InquiryRepository.php      — interface
        InquiryCreated.php         — domain event
      Application/
        CreateInquiry/             — command + handler
        UpdateInquiry/             — command + handler
        AttachFile/                — command + handler
        AdvanceInquiryStatus/      — command + handler
        GetInquiryList/            — query + handler + InquiryListItemDTO
        GetInquiryDetail/          — query + handler + InquiryDetailDTO
      Infrastructure/
        Persistence/
          DoctrineInquiryRepository.php
          Doctrine/xml/
        Http/
          CreateInquiryController.php
          UpdateInquiryController.php
          AttachFileController.php
          AdvanceInquiryStatusController.php
          GetInquiryListController.php
          GetInquiryDetailController.php
        Storage/
          FileStorage.php           — interface
          LocalFileStorage.php      — implementace (var/uploads/sales/)
    Quote/
      Domain/
        Quote.php
        QuotePhase.php
        QuoteId.php
        QuotePhaseId.php
        QuoteStatus.php            — enum
        Money.php                  — value object
        QuoteRepository.php        — interface
        QuoteAccepted.php          — domain event
      Application/
        CreateQuote/               — command + handler
        AddQuotePhase/             — command + handler
        UpdateQuotePhase/          — command + handler
        SendQuote/                 — command + handler (draft → sent)
        AcceptQuote/               — command + handler (sent → accepted, emituje QuoteAccepted)
        RejectQuote/               — command + handler (sent → rejected)
        ExportQuotePdf/            — command + handler (generuje PDF)
        GetQuoteDetail/            — query + handler + QuoteDetailDTO
      Infrastructure/
        Persistence/
          DoctrineQuoteRepository.php
          Doctrine/xml/
        Http/
          CreateQuoteController.php
          AddQuotePhaseController.php
          UpdateQuotePhaseController.php
          SendQuoteController.php
          AcceptQuoteController.php
          RejectQuoteController.php
          ExportQuotePdfController.php
          GetQuoteDetailController.php
        Pdf/
          QuotePdfGenerator.php    — dompdf nebo wkhtmltopdf
  tests/
    Inquiry/
      Domain/
      Application/
    Quote/
      Domain/
      Application/
```

### HTTP API

| Method | Path | Popis |
|--------|------|-------|
| GET | `/api/sales/inquiries` | Seznam poptávek |
| POST | `/api/sales/inquiries` | Vytvořit poptávku |
| GET | `/api/sales/inquiries/{id}` | Detail poptávky |
| PUT | `/api/sales/inquiries/{id}` | Upravit poptávku |
| POST | `/api/sales/inquiries/{id}/attachments` | Nahrát přílohu (multipart) |
| POST | `/api/sales/inquiries/{id}/commands/advance-status` | Posunout stav |
| POST | `/api/sales/inquiries/{id}/quotes` | Vytvořit nabídku |
| GET | `/api/sales/inquiries/{id}/quotes/{quoteId}` | Detail nabídky |
| POST | `/api/sales/inquiries/{id}/quotes/{quoteId}/phases` | Přidat fázi nabídky |
| PUT | `/api/sales/inquiries/{id}/quotes/{quoteId}/phases/{phaseId}` | Upravit fázi |
| POST | `/api/sales/inquiries/{id}/quotes/{quoteId}/commands/send` | Odeslat nabídku |
| POST | `/api/sales/inquiries/{id}/quotes/{quoteId}/commands/accept` | Přijmout nabídku |
| POST | `/api/sales/inquiries/{id}/quotes/{quoteId}/commands/reject` | Zamítnout nabídku |
| GET | `/api/sales/inquiries/{id}/quotes/{quoteId}/pdf` | Stáhnout/zobrazit PDF |

### Cross-BC integrace

- `QuoteAccepted` domain event → Planning `CreateOrderFromQuoteHandler` vytvoří `Order` s fázemi z `QuotePhase[]`
- Nový zákazník při zadání poptávky → command do CRM `RegisterCustomer` → vrátí `CustomerId` → uloží se do `Inquiry.customerId`

### Oprávnění

Nový enum `Sales\Infrastructure\Security\SalesPermission`:
- `sales.inquiries.manage`
- `sales.quotes.manage`

`PermissionVoter` z Identity BC pokrývá libovolný dotted string — žádný nový voter není potřeba.

### Databázové tabulky

- `sales_inquiries` — id, customer_id (nullable), customer_name, contact_email, description, requested_deadline, status, created_at
- `sales_inquiry_required_roles` — inquiry_id, role, skills (json)
- `sales_inquiry_attachments` — inquiry_id, path, mime_type, original_name
- `sales_quotes` — id, inquiry_id, total_price_amount, total_price_currency, valid_until, status, pdf_path, notes
- `sales_quote_phases` — id, quote_id, name, required_role, duration_days, daily_rate_amount, daily_rate_currency, subtotal_amount, subtotal_currency

### File storage

Soubory do `var/uploads/sales/`. Servisování přes `/api/sales/attachments/{filename}` — controller ověří oprávnění a streamuje soubor. Podporované MIME typy: `application/pdf`, `image/png`, `image/jpeg`, `image/webp`.

---

## 3. Frontend architektura

### Struktura souborů

```
frontend/src/app/
  api/
    sales.ts                      — API typy + salesApi objekt
  modules/
    sales/
      InquiriesPage.tsx
      InquiriesPage.module.css
      InquiryDetailPage.tsx
      InquiryDetailPage.module.css
      QuoteDetailPage.tsx
      QuoteDetailPage.module.css
```

### Stránky

**InquiriesPage** (`/sales/inquiries`)
- Tabulka: zákazník, popis (zkrácený), stav badge, datum vytvoření
- Filtr podle stavu
- "Nová poptávka" button → modal (customerName, contactEmail, description, requestedDeadline, requiredRoles)
- Klik na řádek → InquiryDetailPage

**InquiryDetailPage** (`/sales/inquiries/:inquiryId`)
- Breadcrumb: Poptávky › {customerName}
- Hlavička: stav badge + tlačítko advance-status
- Sekce "Požadavky": popis, termín, role/skills tagy
- Sekce "Přílohy": seznam s náhledem (PDF viewer inline, obrázky), upload tlačítko
- Sekce "Nabídky": seznam nabídek (stav, celková cena, platnost) + "Vytvořit nabídku" button

**QuoteDetailPage** (`/sales/inquiries/:inquiryId/quotes/:quoteId`)
- Breadcrumb: Poptávky › {customerName} › Nabídka
- Stav badge + akční tlačítka (Odeslat / Přijmout / Zamítnout) dle stavu
- Sekce "Fáze nabídky": tabulka (název, role, dny, sazba/den, mezisoučet) + "Přidat fázi" inline form
- Celková cena (živě přepočítána z fází)
- "Exportovat PDF" button → GET pdf endpoint → otevře v nové záložce

### Navigace

Nová sekce v sidebaru "Obchod" (nad CRM):
- Poptávky → `/sales/inquiries`

### Oprávnění

Router guards: `sales.inquiries.manage`, `sales.quotes.manage` (stejný `requirePermission` pattern jako identity/planning).

---

## 4. Chybové stavy

| Domain exception | HTTP | Zpráva |
|-----------------|------|--------|
| `InvalidStatusTransitionException` | 422 | "Neplatný přechod stavu" |
| `QuoteNotInDraftException` | 422 | "Nabídku lze upravovat pouze ve stavu draft" |
| `InquiryAlreadyQuotedException` | 422 | "Poptávka již má aktivní nabídku" |
| `AttachmentNotFoundException` | 404 | "Příloha nenalezena" |

Všechny chyby zachytí existující `DomainExceptionListener` ze SharedKernel → `{"error": "..."}` JSON.

---

## 5. Testování

- **Domain unit testy:** stavové přechody Inquiry a Quote, výpočet `subtotal` a `totalPrice`, validace přechodů (nelze přijmout draft nabídku)
- **Application unit testy:** všechny command/query handlery s test doubles pro repository a FileStorage
- Bez integračních testů (stejný pattern jako ostatní packages)
