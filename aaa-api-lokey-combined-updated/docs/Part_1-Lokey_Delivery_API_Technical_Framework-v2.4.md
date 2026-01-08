# 🧭 Part 1 – Lokey Delivery API Technical Framework (v2.4)
**Sequence Position:** Part 1 of the Lokey Delivery AI Documentation Suite  
**Preceded by:** *README_Index.md*  
**Followed by:** *Part 2 – Lokey AI Product Creation Protocol (v2.4)*  

---

## 1️⃣ Purpose  
This document defines the **technical core, schema behavior, and operational safety principles** of the Lokey Delivery system.  
It is the authoritative baseline for all API interactions used by AI or human operators to create and update WooCommerce products through the **Lokey Inventory API**.

---

## 2️⃣ System Overview
The endpoint `/lokey-inventory/v1/products/extended` is a **non-destructive REST API** used for safe product creation and updates.  

| Key Characteristics | Description |
|----------------------|-------------|
| **Mode** | Non-destructive — never overwrites existing data. |
| **Scope** | Product creation, updates, image imports, brand and supplier linkage. |
| **Behavior** | Automatically merges attributes, computes sale price, and links brand taxonomy. |
| **Compatibility** | Works with WooCommerce, ATUM, and BeRocket Brand integration. |

---

## 3️⃣ Core Operating Principles

1. **Determinism Over Creativity**  
   Every output must be predictable, verifiable, and reproducible.  

2. **Non-Destructive Updates Only**  
   The API merges or appends data; it never deletes or nullifies existing records.  

3. **Schema Enforcement**  
   All payloads must conform to the `ProductExtended` schema.  

4. **Attribute Integrity**  
   Use only global (`pa_`) attributes defined in `attributes.json`.  

5. **Taxonomy Discipline**  
   Never create or rename taxonomies or attribute terms (except `pa_lineage`).  

6. **Child Category Enforcement**  
   Always assign the **lowest-level category**; never use parent or mid-tier categories.  

7. **Image URL Immutability**  
   The `image_url` string must remain **identical** to the source provided. No rewriting, truncation, or replacements.  

8. **Inventory Default Behavior**  
   Products are always trackable (`manage_stock = true`) with `stock_quantity = 0`. WooCommerce automatically sets them as `out_of_stock`.  

---

## 4️⃣ Endpoint Logic Summary

| Method | Route | Function |
|--------|--------|----------|
| **POST** | `/lokey-inventory/v1/products/extended` | Create products safely |
| **PUT** | `/lokey-inventory/v1/products/extended/{id}` | Update existing products safely |
| **GET** | `/lokey-inventory/v1/products/{id}` | Retrieve created product (validation) |

---

## 5️⃣ Automatic Behaviors

### 🧮 5.1 Sale Price Computation
The plugin automatically calculates `sale_price` from `discount_percent` and `regular_price`.

**AI Rule:**  
Include `regular_price` and `discount_percent` only — never manually calculate sale price.

---

### 🖼️ 5.2 Image Import & SEO Renaming
The plugin automatically:
- Downloads the image from `image_url`.  
- Renames it using SEO-safe lowercase dash-separated format.  
- Prevents duplicate downloads via `_source_image_url`.  

**AI Rule:**  
- Always include a valid `image_url`.  
- Never modify or shorten the URL.  
- Do not attach gallery images unless explicitly provided.  

---

### 🏷️ 5.3 Brand Integration (BeRocket)
Automatically links product to `berocket_brand` taxonomy using provided brand ID.  
**AI Rule:**  
Include brand ID and name from `brands.json`.  
Do not duplicate or create new terms.

---

### 🧩 5.4 Supplier Linkage (ATUM)
- `supplier_id` may be provided **only post-creation.**  
- ATUM links supplier automatically to `_supplier_id` postmeta.  

**AI Rule:**  
Do not include supplier in creation payload; attach via update call.

---

## 6️⃣ ProductExtended Schema Overview

| Field | Type | Description |
|--------|------|-------------|
| **name** | string | Product name (canonical). |
| **sku** | string | Unique SKU code. |
| **type** | string | Always “simple.” |
| **regular_price** | string | Price (two decimals). |
| **discount_percent** | number | Auto-calculates sale price. |
| **manage_stock** | boolean | Always `true`. |
| **stock_quantity** | integer | Always `0`. |
| **status** | string | `publish` or `draft`. |
| **categories** | array | Child category only. |
| **brands** | array | Existing brand IDs only. |
| **attributes** | array | Global attributes only (`pa_` prefix). |
| **image_url** | string | Required field; unchanged from input. |
| **description** | string | Full HTML description (800–1000 words). |
| **short_description** | string | Short intro (80–120 words + bullet list). |

---

## 7️⃣ Attribute Governance

| Rule | Behavior |
|------|-----------|
| **Existing Attributes Only** | All attributes must pre-exist in WooCommerce. |
| **Term Creation** | Allowed only for `pa_lineage`. |
| **Attribute Mapping** | AI must apply full attribute group per category (not just top attributes). |
| **Validation** | AI must confirm term and ID match `attributes.json` before creation. |
| **Visibility Defaults** | `visible = true`, `variation = false`. |

---

## 8️⃣ Category Enforcement
- Always assign **child category only** (lowest node from `categories.json`).  
- Never attach parent or intermediate categories.  
- Validate category ID exists before submission.  

---

## 9️⃣ Inventory Logic
| Setting | Value | Notes |
|----------|--------|------|
| **manage_stock** | `true` | Always enabled |
| **stock_quantity** | `0` | Always zero |
| **stock_status** | `out_of_stock` | WooCommerce sets automatically |
| **purchase_price** | — | Not used during creation |
| **inventory meta** | — | ATUM handles post-linking |

---

## 🔟 Data Safety & Normalization Rules

| Element | Normalization | Example |
|----------|----------------|---------|
| Names | Lowercase, dash-separated | `green-crack-live-sauce-0-5g` |
| Prices | Two decimals | `"45.00"` |
| URLs | Escaped and unmodified | `"https://weedmaps.com/images/.../avatar/image.png"` |
| HTML | Sanitized via `wp_kses_post()` | `<p>...</p>` |
| Attributes | Use verified term strings only | `"Hybrid"`, `"Sativa"` |

---

## 1️⃣1️⃣ Verification Checklist

| Check | Description |
|--------|-------------|
| ✅ API Response `status=success` | Confirm product creation |
| ✅ Brand & Category linked | Verified via taxonomy |
| ✅ Image imported correctly | SEO name verified |
| ✅ Attributes complete | Matches category mapping |
| ✅ Description HTML valid | Safe formatting |
| ✅ Stock = 0 | Out of stock automatically |

---

## 1️⃣2️⃣ Compliance Summary

| Rule | Enforcement |
|------|--------------|
| Non-destructive updates only | ✅ |
| Canonical taxonomy enforcement | ✅ |
| Supplier post-link only | ✅ |
| Image URL integrity | ✅ |
| Stock logic (trackable, 0 qty) | ✅ |
| Attribute completeness | ✅ |
| Category = child only | ✅ |

---

**End of Part 1 – Lokey Delivery API Technical Framework (v2.4)**  
*Next: [Part 2 – Lokey AI Product Creation Protocol (v2.4)](Part_2-Lokey_AI_Product_Creation_Protocol-v2.4.md)*  
