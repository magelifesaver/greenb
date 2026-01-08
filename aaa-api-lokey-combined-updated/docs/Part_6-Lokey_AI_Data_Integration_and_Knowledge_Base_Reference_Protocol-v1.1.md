# 📘 Part 6 – Lokey AI Data Integration & Knowledge Base Reference Protocol (v1.1)
**Sequence Position:** Part 6 of the Lokey Delivery AI Documentation Suite  
**Preceded by:** *Part 5 – Lokey AI Category Attribute Mapping & Enforcement Rules (v1.1)*  
**Followed by:** *Part 7 – Lokey Product Creator Agent Operating Instructions (v1.0)*  

---

## 1️⃣ Purpose  
This document defines how the Lokey AI system accesses, validates, and applies structured reference data from the Lokey Knowledge Base to ensure consistent, complete, and verifiable product creation.  
It guarantees **data integrity**, **attribute completeness**, and **zero numeric inference** across all automated processes.

---

## 2️⃣ Mission  
The Lokey AI must use **only verified, structured data** stored within the internal knowledge base.  
All product creation actions must reference these files and apply their rules without assumptions, abbreviations, or omissions.

---

## 3️⃣ Core Knowledge Base Files  

| File | Function | Data Type |
|------|-----------|-----------|
| `attributes.json` | Defines all global attributes and valid term options. | JSON |
| `attribute_groups_per_category.csv` | Links categories to their applicable attributes. | CSV |
| `brands.json` | Lists brand IDs and names for BeRocket taxonomy mapping. | JSON |
| `suppliers.json` | Contains supplier IDs and contact data for ATUM linkage. | JSON |
| `categories.json` | Defines all categories and hierarchical structure (child-parent). | JSON |
| `locations.json` | Maps physical inventory storage for ATUM integration. | JSON |

All files are part of the **Lokey Knowledge Base (LKB)** and are reloaded before each new AI execution cycle.

---

## 4️⃣ Data Access Priority Order  

The AI reads files in the following strict sequence to ensure completeness and mapping accuracy:

1️⃣ **Category Mapping (attribute_groups_per_category.csv)** – Determines required attributes for product’s category.  
2️⃣ **Attribute Definitions (attributes.json)** – Confirms attribute IDs, slugs, and valid terms.  
3️⃣ **Brand & Supplier References (brands.json, suppliers.json)** – Links brand taxonomy and supplier IDs.  
4️⃣ **Product Input Data (CSV or JSON)** – Includes product-specific values such as name, SKU, and strain.  
5️⃣ **Online Enrichment Data (Weedmaps, Leafly, Brand Sites)** – Optional enrichment used for strain and sentiment data.

All loaded data must be confirmed valid before proceeding to payload generation (see Part 2).

---

## 5️⃣ Data Validation Rules  

| Rule | Description |
|------|-------------|
| **Full File Reads Only** | The AI must load and read every line and record in each file—no truncation or partial loads. |
| **No Caching** | Each execution run reloads all source files; cached data is forbidden. |
| **Cross-File Consistency** | Attributes and terms appearing in multiple files must match exactly by name and ID. |
| **No Guesswork** | Numeric or descriptive fields (THC %, CBD %, mg, lineage) must not be inferred. |
| **Schema Validation** | File structure must match its expected schema (e.g., valid JSON array or CSV columns). |

If validation fails, the AI must halt and record an audit entry (see Part 4).

---

## 6️⃣ Numeric & Data Integrity Policy  

To preserve accuracy and prevent false metadata generation:

| Policy | Enforcement |
|---------|-------------|
| **Never Infer Numeric Data** | THC/CBD %, cannabinoid ratios, and dosages must only appear if verified in structured data. |
| **Verified Only** | Numeric values must originate from product JSON or trusted enrichment sources (Weedmaps, brand sites). |
| **Omit if Unverified** | If no numeric data is found, omit fields entirely—do not write “unknown” or placeholders. |
| **Contextual Mentions Allowed** | General qualitative statements like “high THC” are permitted only when source text confirms trend-level data. |

---

## 7️⃣ File Version Control & Hash Validation  

Each file must carry a version hash (SHA-256) for integrity verification.  
At runtime, the AI must:

1. Compute file hash for each JSON/CSV source.  
2. Compare to stored reference hash in the current version registry.  
3. Reload updated file automatically if mismatch is detected.  
4. Record hash values in the audit log (Part 4).  

**Example Audit Entry:**
```json
"file_versions": {
  "attributes_json_hash": "e65f92d4...",
  "categories_json_hash": "9bf8a2e3...",
  "brands_json_hash": "f74a19b1..."
}
```

---

## 8️⃣ Attribute Enforcement Integration  

The AI must automatically align its payload attributes with the mappings defined in `attribute_groups_per_category.csv`.  
This ensures every product includes **all applicable attributes** defined for its category.  

| Enforcement | Description |
|--------------|-------------|
| **Complete Inclusion** | All attributes listed in the category mapping must be present in the product payload. |
| **Term Verification** | Each term must match the pre-existing value in `attributes.json`. |
| **Non-Taxonomy Rule** | Only `pa_lineage` may receive new terms; all others are locked. |
| **Audit Recording** | Each run records `attribute_completeness` and `missing_attributes` fields in the audit JSON. |

---

## 9️⃣ AI Data Access Lifecycle  

| Stage | Description |
|--------|-------------|
| **1. File Load** | Load all required JSON and CSV data from the Lokey Knowledge Base. |
| **2. Validation** | Confirm file integrity (hash + schema) and enforce cross-reference accuracy. |
| **3. Mapping Application** | Filter applicable attributes by category and load valid term options. |
| **4. Payload Assembly** | Merge verified attributes, prices, and content into final API payload. |
| **5. Audit Logging** | Write file version, content counts, and validation metrics into audit record. |

---

## 🔟 Compliance with Part 4 Audit Framework  

Each audit log entry must include the following integration metrics:  

- `file_version_hash` for each loaded reference file.  
- `data_integrity` (pass/fail).  
- `attribute_completeness` percentage.  
- `numeric_fields_verified` boolean.  
- `source_file_conflicts` (if any mismatched data between files).  

This guarantees data provenance for every created product.

---

## 1️⃣1️⃣ Compliance Checklist  

| Check | Requirement | Status |
|--------|-------------|---------|
| Load all knowledge base files in full | No partial reads allowed | ✅ |
| Verify schema and hash integrity | Must match registry | ✅ |
| Use only verified attribute terms | No unlisted or created terms | ✅ |
| Include all mapped category attributes | Enforced from mapping CSV | ✅ |
| Do not infer numeric data | Omit or state qualitative context only | ✅ |
| Audit file versions | Record hashes for every file | ✅ |
| Log validation failures | Stop operation and alert operator | ✅ |

---

## ✅ Summary  
This protocol ensures Lokey AI operates exclusively on verified, structured, and current reference data.  
It guarantees **data consistency**, **traceability**, and **zero inference** while supporting the category-based attribute logic outlined in *Part 5* and the audit framework in *Part 4.*  

---

**End of Part 6 – Lokey AI Data Integration & Knowledge Base Reference Protocol (v1.1)**  
*Next: [Part 7 – Lokey Product Creator Agent Operating Instructions (v1.0)](Part_7-Lokey_Product_Creator_Agent_Operating_Instructions-v1.0.md)*  
