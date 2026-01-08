# 🧩 Part 2 – Lokey AI Product Creation Protocol (v2.4)
**Sequence Position:** Part 2 of the Lokey Delivery AI Documentation Suite  
**Preceded by:** *Part 1 – Lokey Delivery API Technical Framework (v2.4)*  
**Followed by:** *Part 3 – Lokey SEO & Content Generation Standard (v2.4)*  

---

## 1️⃣ Purpose  
This document defines the **complete and deterministic operational workflow** the Lokey AI system must follow when creating products through the Lokey Delivery API.  
It governs every step of data validation, description generation, attribute assembly, and payload submission.  

All actions are bound by **non-destructive principles**, ensuring data integrity and reproducibility across all automation cycles.

---

## 2️⃣ Mission Statement  
To produce **accurate, compliant, and SEO-ready** WooCommerce product records using the Lokey Inventory API while adhering to strict safety, taxonomy, and content standards.  

---

## 3️⃣ Operational Overview  

| Phase | Name | Description |
|-------|------|-------------|
| 1️⃣ | Input Parsing | Validate provided product data from CSV or JSON. |
| 2️⃣ | Data Enrichment | Supplement verified data with external sources (Weedmaps, Leafly, brand sites). |
| 3️⃣ | Image Validation | Verify full URL integrity, no modification. |
| 4️⃣ | Content Generation | Generate short and long descriptions per SEO and compliance rules. |
| 5️⃣ | Attribute Assembly | Build attribute arrays using category-based mappings. |
| 6️⃣ | Payload Assembly | Construct and validate the JSON schema. |
| 7️⃣ | API Execution | Submit payload safely via `/lokey-inventory/v1/products/extended`. |
| 8️⃣ | Verification & Logging | Confirm creation, audit, and store log. |

---

## 4️⃣ Phase 1 – Input Parsing

**Objective:** Validate all base product fields before processing.  

**Actions:**
1. Read and map product CSV or JSON file.  
2. Match SKU, name, and strain fields exactly.  
3. Validate brand, classification, and category presence.  
4. Confirm brand and category IDs exist in `brands.json` and `categories.json`.  
5. Verify image_url is present and fully intact.  
6. Reject or flag any product with missing required fields.

**Required Fields**
- name  
- sku  
- category_id (child category only)  
- brand_id  
- image_url  
- classification  
- strain  

**Enforcement**
- Never auto-correct or guess missing data.  
- If any required field is invalid, **stop the process** and log the error.

---

## 5️⃣ Phase 2 – Data Enrichment

**Objective:** Enrich verified data without overwriting canonical JSON values.  

**Source Priority:**
1️⃣ Official Brand Website  
2️⃣ Weedmaps Listing / Metadata  
3️⃣ Leafly / Allbud (for strain lineage or effects)  
4️⃣ Public Review Data (for “Public Sentiment Snapshot” section)

**Enrichment Rules:**
- Use verified facts only; never guess or estimate.  
- Summarize lineage, aroma, flavor, and effects if available.  
- Record verified public sentiment neutrally (e.g., “widely praised for...”).  
- Attribute enrichment sources in the audit log, not in the description.

---

## 6️⃣ Phase 3 – Image Validation

**Objective:** Ensure full image URL integrity and readiness for import.  

**Rules:**
- Use exactly the `image_url` provided — **no modification or truncation**.  
- Never replace with “similar” or “brand” image URLs.  
- Validate that URL is publicly accessible (HTTP 200).  
- If missing or invalid → stop process, flag for review.  

---

## 7️⃣ Phase 4 – Content Generation  

**Objective:** Generate short and long descriptions following Lokey SEO and compliance rules.  

---

### 🩵 Short Description (Global Standard)  
**Length:** 80–120 words total  
**Format:** One short paragraph (1–2 sentences) + 3–5 bullet points  

```html
<p><strong>{Product Name}</strong> is a {classification} {product type} designed for reliable performance and balanced quality. Known for {verified flavor/effect summary}, it reflects the craftsmanship and precision expected from Lokey’s curated collection.</p>
<ul>
  <li>Strain: {Strain}</li>
  <li>Flavor & Aroma: {Verified Flavors}</li>
  <li>Effects: {Verified Effects}</li>
  <li>Classification: {Classification}</li>
</ul>
```

**Rules:**
- Do not include brand name (handled by plugin).  
- Use verified data only; no assumptions or placeholder text.  
- No hyperlinks or marketing slogans.  

---

### 💡 Long Description (Global Standard)  
**Length:** 800–1000 words (mandatory range)  
**Structure:**
1. `<h2>` Product Name – Classification  
2. `<h3>Strain Information</h3>`  
3. `<h3>Features and Benefits</h3>`  
4. `<h3>Detailed Specifications</h3>`  
5. `<h3>Suggested Usage</h3>`  
6. `<h3>Public Sentiment Snapshot</h3>`  
7. `<h3>Frequently Asked Questions</h3>`  

**Formatting Rules:**
- Use only: `<h2>`, `<h3>`, `<p>`, `<ul>`, `<li>`, `<strong>`, `<em>`, `<small>`, `<a>`.  
- Maintain Grade 8–10 readability.  
- Mention strain ≤ 4 times total.  
- Avoid medical or unverified claims.  

**Mandatory Disclaimer (Always at End):**  
```html
<p><small>The information provided is based on publicly available sources and is not a medical recommendation in any way.</small></p>
```

---

## 8️⃣ Phase 5 – Attribute Assembly & Verification  

**Objective:** Include every applicable attribute from category mapping.  

**Process:**
1. Identify child category from input.  
2. Load `attribute_groups_per_category.csv`.  
3. Extract all mapped attribute slugs for that category.  
4. Cross-reference against `attributes.json`.  
5. Insert all valid attributes into the payload.  

**Rules:**
- Never omit mapped attributes (include them even if empty).  
- Never create new attributes.  
- Only `pa_lineage` can have new term additions.  
- Match capitalization and spelling exactly.  
- `visible = true`, `variation = false` for all.  

**Example Attribute Array:**
```json
[
  {"id":2,"name":"pa_classification","options":["Sativa"],"taxonomy":true,"visible":true,"variation":false},
  {"id":9,"name":"pa_strain","options":["Green Crack"],"taxonomy":true,"visible":true,"variation":false},
  {"id":14,"name":"pa_flavor","options":["Citrus","Mango"],"taxonomy":true,"visible":true,"variation":false},
  {"id":1,"name":"pa_effects","options":["Uplifted","Euphoric"],"taxonomy":true,"visible":true,"variation":false},
  {"id":55,"name":"pa_lineage","options":["Skunk #1 x Afghani Landrace"],"taxonomy":false,"visible":true,"variation":false}
]
```

---

## 9️⃣ Phase 6 – Payload Assembly & Schema Validation

**Objective:** Construct the `ProductExtended` payload accurately and validate it before submission.

| Field | Rule |
|-------|------|
| **manage_stock** | Always `true`. |
| **stock_quantity** | Always `0`. |
| **categories** | Must contain only the child category ID. |
| **brands** | Must reference verified brand ID from `brands.json`. |
| **attributes** | Must include all applicable mapped attributes. |
| **image_url** | Must remain identical to source. |
| **description** | 800–1000 words, compliant HTML. |
| **short_description** | 80–120 words total with bullet list. |

**No inventory fields (purchase_price, stock_location, etc.) are ever included in creation payloads.**

---

## 🔟 Phase 7 – API Execution  

**Endpoint:**  
`POST /lokey-inventory/v1/products/extended`

**AI Behavior:**
- Submit payload only after all validations pass.  
- If HTTP ≥ 400, halt and log the failure.  
- Never retry automatically; human review required.  

**On Success:**
- Capture `product_id`, `sku`, `timestamp`, and `permalink`.  
- Proceed to supplier linking via `lokeyInventoryUpdateProductExtended` if required.  

---

## 1️⃣1️⃣ Phase 8 – Verification & Audit Logging

**Objective:** Validate and document successful creation.  

**Verification Checks:**
- Product visible and published.  
- Brand taxonomy correctly linked.  
- Image imported and renamed SEO-safe.  
- Attributes display as expected on product page.  
- Stock = 0 and marked out of stock.  
- Description meets formatting and disclaimer rules.  

**Audit Record Schema:** (see Part 4 for full spec)
```json
{
  "product_id": 63514,
  "sku": "211281323",
  "ai_ruleset_version": "2.4",
  "content_summary": {
    "short_desc_words": 102,
    "long_desc_words": 912,
    "faq_count": 5
  },
  "status": "success"
}
```

---

## 1️⃣2️⃣ Compliance Checklist

| Check | Requirement | Status |
|--------|--------------|---------|
| Short description = 80–120 words + bullets | ✅ |
| Long description = 800–1000 words | ✅ |
| Disclaimer added | ✅ |
| Image URL unmodified | ✅ |
| Attributes complete per mapping | ✅ |
| Brand + category valid | ✅ |
| Stock set to 0 (trackable) | ✅ |
| Supplier added post-creation only | ✅ |

---

## ✅ Summary

This protocol ensures deterministic, compliant, and SEO-optimized product creation within the Lokey Delivery API environment.  
By combining validated structured data with automated audit logging, every product is guaranteed to meet Lokey’s technical and content standards.  

---

**End of Part 2 – Lokey AI Product Creation Protocol (v2.4)**  
*Next: [Part 3 – Lokey SEO & Content Generation Standard (v2.4)](Part_3-Lokey_SEO_and_Content_Generation_Standard-v2.4.md)*  
