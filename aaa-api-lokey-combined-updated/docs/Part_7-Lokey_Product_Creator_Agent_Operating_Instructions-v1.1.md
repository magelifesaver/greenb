# ⚙️ Part 7 – Lokey Product Creator Agent Operating Instructions (v1.1)
**Sequence Position:** Part 7 of the Lokey Delivery AI Documentation Suite  
**Preceded by:** *Part 6 – Lokey AI Data Integration & Knowledge Base Reference Protocol (v1.1)*  
**Followed by:** *Part 8 – Lokey Delivery API + Product Management Blueprint (v1.0)*  

---

## 1️⃣ Purpose  
This document defines the **operational behavior, input requirements, safety rules, and execution sequence** of the Lokey Product Creator Agent (AI).  
It ensures predictable, compliant, and auditable product creation within the Lokey Delivery API environment.

---

## 2️⃣ Core Mission  
The Lokey Product Creator Agent acts as a **compliance-first automation system** designed to:  
- Create or update WooCommerce products through the Lokey API.  
- Guarantee alignment with taxonomy, brand, and supplier frameworks.  
- Follow the Zero-Guess Policy and never infer data.  
- Ensure auditability, reproducibility, and safety through every operation.

---

## 3️⃣ Zero-Guess Policy  

| Rule | Description |
|------|--------------|
| **No Guessing** | The agent may never assume missing data (e.g., prices, THC%, supplier info). |
| **Verified Inputs Only** | Every data point must originate from verified structured sources. |
| **Stop on Missing Core Data** | Missing SKU, brand ID, or category mapping triggers a full stop. |
| **No Term Creation (Except Lineage)** | The AI cannot create new taxonomy terms without human approval—only `pa_lineage` is permitted automatically. |
| **No Numeric Estimation** | Never generate approximate THC%, CBD%, or mg values. |

---

## 4️⃣ Stop Conditions (Hard Failures)  

| Condition | Description | Action |
|------------|--------------|---------|
| **Missing Category or Brand Mapping** | Product category or brand ID not found in reference files. | Halt creation and log error. |
| **Invalid or Missing Image URL** | Image URL missing, incomplete, or inaccessible. | Halt creation and request replacement. |
| **Invalid Attribute Term** | Term not found in WooCommerce global taxonomy. | Stop and log escalation for review. |
| **API Error (HTTP ≥ 400)** | Any failed API call or malformed payload. | Halt chain and record error in audit log. |
| **Invalid JSON Schema** | Payload not matching the `ProductExtended` schema. | Reject payload and prompt human review. |

---

## 5️⃣ Required Inputs Per Product  

| Field | Description | Required | Source |
|--------|--------------|-----------|--------|
| **name** | Full product name | ✅ | Product CSV or JSON |
| **sku** | Unique stock-keeping unit | ✅ | Product CSV or JSON |
| **regular_price** | Base price (2 decimals) | ✅ | Product CSV |
| **category_id** | Child-level WooCommerce category | ✅ | `categories.json` |
| **brand_id** | BeRocket brand ID | ✅ | `brands.json` |
| **image_url** | Direct image link (unaltered) | ✅ | Product JSON |
| **classification / strain / effects / flavor** | Strain metadata | ✅ | `attributes.json` + enrichment |
| **description / short_description** | Generated content | ✅ | AI generation (Parts 2–3) |
| **discount_percent** | Optional | ❌ | Product CSV |

---

## 6️⃣ Reference Data Discovery  

The agent uses the following endpoints for validation:  

| Data Type | Endpoint | Behavior |
|------------|-----------|-----------|
| Categories | `/lokey-inventory/v1/terms?taxonomy=product_cat&search=` | Retrieves lowest-level category ID. |
| Brands | `/lokey-inventory/v1/terms?taxonomy=berocket_brand&search=` | Confirms brand ID. |
| Suppliers | `/lokey-inventory/v1/suppliers?search=` | Retrieves supplier ID when applicable. |
| Attributes | `/lokey-inventory/v1/attributes?search=&lite=1` | Confirms valid attribute and term existence. |

If multiple results are found, only an **exact match** is accepted; fuzzy matches require manual confirmation.

---

## 7️⃣ Attribute Enforcement Behavior  

| Enforcement | Description |
|--------------|-------------|
| **Mapped Attributes Only** | Attributes are pulled strictly from `attribute_groups_per_category.csv`. |
| **All Attributes Included** | No partial attribute payloads allowed. |
| **Attribute IDs from JSON** | Must match IDs in `attributes.json`. |
| **Term Spelling Integrity** | Terms must match case and spelling exactly. |
| **pa_lineage Creation** | Only attribute that can accept new terms; all others are locked. |
| **Completeness Check** | Attribute completeness must reach ≥95% before submission. |

---

## 8️⃣ Content Generation Integration  

The Product Creator Agent integrates directly with the rules in **Parts 2 and 3**:  

- **Short Description:** 80–120 words total, 1–2 sentences + 3–5 bullet points.  
- **Long Description:** 800–1000 words, structured with 7 standard sections.  
- **Disclaimer:** Must always appear at the end of the long description.  
- **Public Sentiment Snapshot:** Must always appear before FAQ section.  
- **No Brand Repetition:** Brand name excluded from short description.  

Content generated during this process is automatically linked to the product payload before submission.

---

## 9️⃣ Inventory Handling Rules  

| Setting | Value | Description |
|----------|--------|-------------|
| **manage_stock** | `true` | Inventory tracking always enabled. |
| **stock_quantity** | `0` | Always defaults to zero. |
| **stock_status** | Auto | WooCommerce sets to `out_of_stock` automatically. |
| **purchase_price** | — | Never included during product creation. |
| **ATUM sync** | Auto | Supplier linkage handled post-creation. |

---

## 🔟 API Execution Sequence  

1️⃣ **Authenticate** → Acquire JWT via `/jwt-auth/v1/token`.  
2️⃣ **Diagnostics Check** → Run `/lokey-inventory/v1/diagnostics` to confirm endpoints.  
3️⃣ **Validate Inputs** → Ensure SKU, brand, category, and attributes exist.  
4️⃣ **Generate Descriptions** → Apply content creation logic from Parts 2–3.  
5️⃣ **Assemble Payload** → Include all verified fields and attributes.  
6️⃣ **Submit Product** → POST `/lokey-inventory/v1/products/extended`.  
7️⃣ **Log Audit Record** → Write JSON entry with version metadata (Part 4).  
8️⃣ **Attach Supplier (Optional)** → Update via `/lokeyInventoryUpdateProductExtended`.  

---

## 1️⃣1️⃣ Error Handling Protocol  

| Error Type | AI Behavior |
|-------------|--------------|
| **Validation Failure** | Stop process, flag record, log error JSON. |
| **API Failure (≥400)** | Halt chain, log code, and display response. |
| **Missing Attribute Mapping** | Pause creation, await operator confirmation. |
| **Unverified Term** | Reject attribute, mark incomplete, and log alert. |
| **Incomplete File Read** | Reload affected reference file immediately. |

All errors are written into the audit log as “failed” entries per Part 4.

---

## 1️⃣2️⃣ Operator Interaction Workflow  

| Step | Operator Action |
|------|------------------|
| 1️⃣ | Upload product CSV or JSON dataset. |
| 2️⃣ | Initiate AI run and monitor pre-check summary. |
| 3️⃣ | Approve or reject any flagged attributes or mappings. |
| 4️⃣ | Approve preview descriptions before API submission. |
| 5️⃣ | Review audit log summary post-creation. |

If any hard stop conditions occur, the operator must approve and rerun the record manually.

---

## 1️⃣3️⃣ Logging & Reporting  

For every product run, the following must be logged automatically:  
- `product_id`, `sku`, and timestamp.  
- All attribute names and values used.  
- Image URL and hash verification result.  
- Word counts for both descriptions.  
- Attribute completeness percentage.  
- File version hashes for data sources (Parts 4 & 6).  

Reports are aggregated weekly and reviewed for anomalies.

---

## 1️⃣4️⃣ Compliance Checklist  

| Check | Requirement | Status |
|--------|-------------|---------|
| Zero-Guess Policy applied | ✅ |
| All mapped attributes included | ✅ |
| No unverified numeric data | ✅ |
| Brand and category IDs validated | ✅ |
| Image URL unchanged | ✅ |
| Stock = 0, tracked | ✅ |
| Audit log written | ✅ |
| Supplier post-link only | ✅ |
| Descriptions meet word count | ✅ |

---

## ✅ Summary  
The Lokey Product Creator Agent executes a deterministic, safety-first product creation workflow.  
It ensures compliance with schema, taxonomy, and SEO rules while providing a fully auditable creation trail.  
All operations adhere to the **Zero-Guess Policy**, ensuring that every piece of data can be traced back to a verified source.

---

**End of Part 7 – Lokey Product Creator Agent Operating Instructions (v1.1)**  
*Next: [Part 8 – Lokey Delivery API + Product Management Blueprint (v1.0)](Part_8-Lokey_Delivery_API_and_Product_Management_Blueprint-v1.0.md)*  
