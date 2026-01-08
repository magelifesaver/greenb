# 🗂️ Part 5 – Lokey AI Category Attribute Mapping & Enforcement Rules (v1.1)
**Sequence Position:** Part 5 of the Lokey Delivery AI Documentation Suite  
**Preceded by:** *Part 4 – Lokey AI Audit Logging & Version Governance Framework (v1.1)*  
**Followed by:** *Part 6 – Lokey AI Data Integration & Knowledge Base Reference Protocol (v1.0)*  

---

## 1️⃣ Purpose  
This document defines how the Lokey AI system enforces **attribute mapping and validation** when creating or updating products.  
It ensures that all attributes are correctly applied per category, maintaining data consistency, SEO alignment, and WooCommerce taxonomy compliance.

---

## 2️⃣ Mission  
The AI must always include **all applicable attributes** for a given category, using verified data from Lokey’s reference files.  
It may never omit mapped attributes or introduce unmapped ones. This prevents taxonomy drift and guarantees consistent display and filter functionality.

---

## 3️⃣ Source Files  

| File | Description |
|------|--------------|
| `attribute_groups_per_category.csv` | Defines which attributes apply to which child category. |
| `attributes.json` | Lists all global attribute definitions and valid term options. |
| `categories.json` | Lists all category IDs, names, and parent-child relationships. |

These three data sources form the **canonical attribute enforcement structure**.

---

## 4️⃣ Category-to-Attribute Mapping Logic  

When creating or updating a product, the AI performs the following steps:

1. **Identify the Child Category**  
   - Only the **lowest-level (child)** category is used.  
   - Parent or mid-level categories are never assigned.  

2. **Load the Attribute Group**  
   - From `attribute_groups_per_category.csv`, identify the matching attribute group for that category.  
   - Example: *“Vape” → pa_classification, pa_strain, pa_thc_percentage, pa_flavor, pa_effects, pa_disposable, etc.*  

3. **Cross-Reference With Global Attributes**  
   - Pull matching definitions from `attributes.json` using the `slug` field.  
   - Confirm each attribute ID and term list exists in the WooCommerce taxonomy.  

4. **Assemble Valid Attribute Array**  
   - Include all mapped attributes, whether populated or empty.  
   - Fill verified term options where applicable.  
   - Mark all with `visible = true` and `variation = false`.  

5. **Validate Completeness**  
   - Compare the payload’s attribute set to the category group list.  
   - Log percentage of completeness (must be ≥95%) in the audit JSON (Part 4).  

6. **Reject Missing or Invalid Terms**  
   - Stop creation if required attributes are missing.  
   - Pause and request human approval if attribute term does not exist in WooCommerce.  

---

## 5️⃣ Attribute Creation Rules  

| Attribute Type | AI Behavior | Term Creation Policy |
|----------------|--------------|----------------------|
| **Global Taxonomy Attributes (`pa_`)** | Use only existing global attributes defined in `attributes.json`. | Never create new attributes. |
| **Attribute Terms** | Use only existing terms within those attributes. | Never create new terms except for `pa_lineage`. |
| **Lineage (`pa_lineage`)** | May generate and add verified new term if strain lineage is missing. | Permitted with audit record. |
| **Local (Non-taxonomy) Attributes** | Not allowed. All attributes must be global. | N/A |

---

## 6️⃣ Enforcement Workflow  

| Step | Enforcement Action |
|------|---------------------|
| 1️⃣ | Identify the product’s **child category ID**. |
| 2️⃣ | Load all mapped attribute slugs from `attribute_groups_per_category.csv`. |
| 3️⃣ | Verify each slug exists in `attributes.json`. |
| 4️⃣ | Match each mapped attribute to its valid terms. |
| 5️⃣ | Include all applicable attributes in the product payload. |
| 6️⃣ | Reject payloads missing mapped attributes. |
| 7️⃣ | Write completeness and mapping metrics to audit log (see Part 4). |

**Result:**  
All attributes required for the selected product category are represented in the payload — no omissions, no duplicates, no unverified fields.

---

## 7️⃣ Example Enforcement for Vape Products  

**Category:** Vape (ID: 47)  
**Mapped Attributes (from CSV):**  
```
classification, strain, thc_percentage, cbd_percentage, cartridge-size, cartridge-type, disposable, effects, aroma, flavor
```

**Validated Attribute Array (JSON Example):**  
```json
[
  {"id":2,"name":"pa_classification","options":["Sativa"],"taxonomy":true,"visible":true,"variation":false},
  {"id":9,"name":"pa_strain","options":["Berry Bomb"],"taxonomy":true,"visible":true,"variation":false},
  {"id":54,"name":"pa_thc_percentage","options":["86"],"taxonomy":true,"visible":true,"variation":false},
  {"id":55,"name":"pa_cbd_percentage","options":["0.02"],"taxonomy":true,"visible":true,"variation":false},
  {"id":39,"name":"pa_cartridge-size","options":["1g"],"taxonomy":true,"visible":true,"variation":false},
  {"id":46,"name":"pa_cartridge-type","options":["510 Thread"],"taxonomy":true,"visible":true,"variation":false},
  {"id":13,"name":"pa_disposable","options":["Reusable"],"taxonomy":true,"visible":true,"variation":false},
  {"id":1,"name":"pa_effects","options":["Relaxed","Euphoric","Energetic"],"taxonomy":true,"visible":true,"variation":false},
  {"id":61,"name":"pa_aroma","options":["Sweet","Floral"],"taxonomy":true,"visible":true,"variation":false},
  {"id":14,"name":"pa_flavor","options":["Berry","Gas"],"taxonomy":true,"visible":true,"variation":false}
]
```

---

## 8️⃣ Enforcement Rules Summary  

| Rule | Description | Status |
|------|--------------|---------|
| Include all mapped attributes | Every mapped attribute from the CSV must appear in the payload. | ✅ |
| Use existing attribute IDs only | Must match `attributes.json` IDs. | ✅ |
| Match all term spellings | Term text must match exactly. | ✅ |
| Only `pa_lineage` allows term creation | All other attributes are read-only. | ✅ |
| Visibility defaults applied | `visible = true`, `variation = false`. | ✅ |
| Category = child only | Never assign parent or top-level category. | ✅ |
| Log completeness in audit record | Part 4 integration. | ✅ |

---

## 9️⃣ Integration with Audit Framework (Part 4)

Each product’s audit log (see Part 4) must include:  
- `attribute_completeness` percentage  
- `attributes_applied` dictionary (attribute name → applied terms)  
- `missing_attributes` (if any)  
- `file_version_hash` of `attributes.json` and `attribute_groups_per_category.csv`  

This enables traceability for every attribute included or omitted.

---

## 🔟 Category Attribute Maintenance Procedures  

1. When a new product category is added to WooCommerce:  
   - Append its corresponding row in `attribute_groups_per_category.csv`.  
   - Include all required attribute slugs for that category.  

2. When a new attribute is added globally:  
   - Add to `attributes.json` and link to relevant category rows in the CSV.  
   - Update AI rule version in `Part 4` (`AI_RULESET_VERSION +0.2`).  

3. When removing attributes:  
   - Remove from `attribute_groups_per_category.csv` and `attributes.json`.  
   - Invalidate old logs referencing removed attributes.  

---

## 1️⃣1️⃣ Enforcement Compliance Checklist  

| Check | Requirement | Status |
|--------|-------------|---------|
| Category identified as child | Always enforced | ✅ |
| Attribute group loaded fully | No omissions allowed | ✅ |
| Attribute IDs verified | Matches `attributes.json` | ✅ |
| New terms created only for `pa_lineage` | Logged and approved | ✅ |
| Completeness ≥ 95% | Logged in audit file | ✅ |
| Non-taxonomy attributes excluded | Not permitted | ✅ |
| Category mapping version tracked | Included in audit record | ✅ |

---

## ✅ Summary  
This enforcement framework ensures every AI-generated Lokey product is accurately and completely mapped to its corresponding category attributes.  
It eliminates attribute drift, guarantees taxonomy integrity, and provides a verifiable audit trail under the **Lokey Delivery API + WooCommerce environment**.  

---

**End of Part 5 – Lokey AI Category Attribute Mapping & Enforcement Rules (v1.1)**  
*Next: [Part 6 – Lokey AI Data Integration & Knowledge Base Reference Protocol (v1.0)](Part_6-Lokey_AI_Data_Integration_and_Knowledge_Base_Reference_Protocol-v1.0.md)*  
