# 🛡️ Part 9 – Lokey API Product Safety Reference (v2.0)
**Sequence Position:** Part 9 of the Lokey Delivery AI Documentation Suite  
**Preceded by:** *Part 8 – Lokey Delivery API + Product Management Blueprint (v1.0)*  
**Followed by:** *Part 10 – Lokey AI Data Governance & Versioned Knowledge Protocol (v1.0)*  

---

## 1️⃣ Introduction  
The **Lokey API Product Safety Reference** defines the foundational safeguards, taxonomy rules, and operational policies that govern every product creation and update action within the Lokey Delivery ecosystem.  
It represents the unifying framework for all safe data handling, attribute preservation, and non-destructive behavior across the WooCommerce + ATUM + Lokey Inventory API integration.

Lokey’s guiding principle is **Safety by Design** — ensuring that every API call, AI action, and data write operation maintains catalog integrity, prevents taxonomy corruption, and guarantees reversibility.

---

## 2️⃣ Purpose  
To protect all existing product data, taxonomy relationships, and brand attributes through a strict **non-destructive update model**.  
This document sets the baseline for operational safety, and all other Lokey documentation (Parts 1–8) inherit its rules.

---

## 3️⃣ Core Safety Rules  

| Rule | Description |
|------|--------------|
| **No Destructive Updates** | The system only merges or appends new data; it never deletes or overwrites. |
| **No Empty or Null Values** | Empty arrays or null fields are never sent to the API. |
| **Always Reference Existing Taxonomies** | All brands, suppliers, categories, and attributes must exist in WooCommerce before use. |
| **Supplier Linking Post-Creation Only** | Supplier associations occur only after successful product creation. |
| **Valid Authentication Required** | Every operation requires a valid JWT token. |
| **Data Provenance** | Every field, image, and term must have a verifiable source. |

---

## 4️⃣ Attribute and Taxonomy Safety Framework  

### 4.1 Global Attribute Enforcement  
All attributes must exist as **global taxonomy attributes** (prefixed `pa_`).  
Local attributes (non-taxonomy) are disallowed to ensure product-wide consistency.

### 4.2 No Attribute Deletion  
- Attributes are only merged, never replaced.  
- If an attribute already exists, new terms are appended to it (never removed).  
- The AI never clears an attribute array.

### 4.3 Term Creation Restriction  
- The AI cannot create new attribute terms without authorization.  
- The only exception: `pa_lineage`, which allows addition of new verified lineage terms when not already present.

### 4.4 Category Hierarchy Enforcement  
- Products are assigned only to **child categories**, never parent-level.  
- Each category ID must be validated against `categories.json`.  
- If a category is not found, creation stops and logs the error.

### 4.5 Brand Integrity  
- Brands must correspond to existing `berocket_brand` taxonomy terms.  
- Brand creation is disabled; new brands require admin import.

---

## 5️⃣ Non-Destructive Update Workflow  

| Step | Behavior | Enforcement |
|------|-----------|-------------|
| 1️⃣ | Validate all fields before update | Rejects null or missing data. |
| 2️⃣ | Merge existing attribute sets | Preserves all prior terms. |
| 3️⃣ | Update only specified fields | Ignores any non-included fields. |
| 4️⃣ | Preserve all taxonomy relationships | Never removes existing brand/category terms. |
| 5️⃣ | Log version and timestamp | All API updates are recorded with checksum validation. |

The system guarantees that updates do not alter or remove prior metadata, ensuring rollback compatibility and full traceability.

---

## 6️⃣ Data Validation & Sanitization Rules  

| Element | Validation Method | Enforcement |
|----------|-------------------|-------------|
| **HTML Fields** | Sanitized via `wp_kses_post()` | ✅ |
| **Numeric Values** | Converted to fixed 2-decimal format | ✅ |
| **Strings** | Escaped via `sanitize_text_field()` | ✅ |
| **URLs** | Cleaned using `esc_url_raw()` | ✅ |
| **Empty Inputs** | Automatically removed from payload | ✅ |
| **Unsafe Tags** | Stripped from descriptions | ✅ |

---

## 7️⃣ Safe Product Lifecycle  

| Stage | Action | Safety Enforcement |
|--------|---------|--------------------|
| **Creation** | POST `/lokey-inventory/v1/products/extended` | Validates all fields before insert. |
| **Update** | PUT `/lokey-inventory/v1/products/extended/{id}` | Partial merge updates; no destructive overwrites. |
| **Supplier Linking** | POST `/lokey-inventory/v1/inventory/{product_id}` | Supplier ID only applied after creation confirmation. |
| **Audit Logging** | JSON snapshot created for every operation | Stored under `/logs/ai-products/YYYY/MM/`. |

---

## 8️⃣ Stock and Inventory Handling Safety  

| Parameter | Rule | Behavior |
|------------|------|-----------|
| **manage_stock** | Always `true` | Enables tracking automatically. |
| **stock_quantity** | Always `0` | Default value for new products. |
| **stock_status** | Auto-managed by WooCommerce | Set automatically to “out of stock.” |
| **purchase_price** | Excluded from product creation | Added later via ATUM sync. |
| **inventory metadata** | Never directly written by AI | ATUM handles post-creation linkage. |

---

## 9️⃣ API Safety and Field Behavior  

| Field | Enforcement | Notes |
|--------|--------------|-------|
| `description` | Sanitized HTML only | No unsafe or external code. |
| `short_description` | Clean and limited | 80–120 words max with bullet list. |
| `attributes` | Global only (`pa_` prefixed) | Cross-validated against `attributes.json`. |
| `categories` | Child only | Verified ID required. |
| `brands` | Valid existing term | Must match `brands.json`. |
| `supplier_id` | Optional, post-link only | Managed separately. |
| `image_url` | Must remain unchanged | No substitutions or rewrites. |

---

## 🔟 Audit & Compliance Linkage  

Every safety rule integrates directly with the audit framework (see Part 4).  

**Audit Log Fields Include:**  
- Product ID & SKU  
- Applied safety checks (pass/fail)  
- Attribute completeness %  
- Taxonomy integrity flag  
- Image URL match validation  
- `ai_ruleset_version`  
- File version hashes from Parts 5 & 6  

Logs are stored for 24 months minimum and reviewed weekly for compliance.

---

## 1️⃣1️⃣ AI Execution Safeguards  

| Safeguard | Description |
|------------|-------------|
| **Stop-on-Failure Logic** | AI halts execution if validation fails. |
| **Rollback Capability** | Logs allow reconstruction of any prior payload. |
| **Live Diagnostics** | `/lokey-inventory/v1/diagnostics` confirms route health before execution. |
| **Auto Version Tagging** | Every creation tagged with current AI version and timestamp. |

---

## 1️⃣2️⃣ Compliance Checklist  

| Check | Enforcement | Status |
|--------|--------------|---------|
| Attribute data validated | ✅ | Enforced by Part 5 |
| Brand and supplier verified | ✅ | Mapped via reference JSON |
| Image URL unmodified | ✅ | Strict immutability rule |
| Category = child only | ✅ | Verified via categories.json |
| Null values omitted | ✅ | Payload sanitization rule |
| Supplier linked post-creation | ✅ | Required workflow |
| Attributes merged, not replaced | ✅ | Enforced API-level |
| Audit log created | ✅ | Required for all operations |

---

## ✅ Summary  
The **Lokey API Product Safety Reference** ensures every API operation follows deterministic, non-destructive, and reversible logic.  
It integrates seamlessly with content generation (Part 3), creation protocol (Part 2), attribute enforcement (Part 5), and audit governance (Part 4).  

All operations conducted through the Lokey system inherit these safety principles, guaranteeing long-term data integrity, compliance, and recoverability across the entire Lokey Delivery infrastructure.

---

**End of Part 9 – Lokey API Product Safety Reference (v2.0)**  
*Next: [Part 10 – Lokey AI Data Governance & Versioned Knowledge Protocol (v1.0)](Part_10-Lokey_AI_Data_Governance_and_Versioned_Knowledge_Protocol-v1.0.md)*  
