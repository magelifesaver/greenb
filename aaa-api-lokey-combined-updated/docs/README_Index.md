# 🧭 Lokey Delivery AI Documentation Suite – Master README Index
**Version:** 1.0  
**Maintained by:** Lokey AI Systems Team  
**Last Updated:** January 2026  

---

## 📘 Overview
The **Lokey AI Documentation Suite** defines the full end-to-end workflow for creating, updating, and maintaining product records in the **Lokey Delivery API ecosystem**.  
Each document is version-controlled, sequenced, and directly linked to a defined function in the automation chain.

---

## 📚 Table of Contents (Sequential Parts)

| Part | Document Title | Version | Purpose Summary |
|------|----------------|----------|-----------------|
| **[Part 1](#part-1-lokey-delivery-api-technical-framework)** | **Lokey Delivery API Technical Framework** | v2.0 | Defines API schema, system safeguards, and operational mechanics for `/lokey-inventory/v1/products/extended`. Establishes the foundation for all product data operations. |
| **[Part 2](#part-2-lokey-ai-product-creation-protocol)** | **Lokey AI Product Creation Protocol** | v2.3 | Describes the exact operational workflow for AI-based product creation — from data parsing to API submission. Introduces new disclaimer, public sentiment, and attribute grouping logic. |
| **[Part 3](#part-3-lokey-seo--content-generation-standard)** | **Lokey SEO & Content Generation Standard** | v2.3 | Defines how short and long product descriptions are generated, formatted, and SEO-optimized. Enforces public sentiment and compliance disclaimers. |
| **[Part 4](#part-4-lokey-ai-audit-logging--version-governance-framework)** | **Lokey AI Audit Logging & Version Governance Framework** | v1.0 | Outlines logging standards, audit schema, version tracking, and traceability requirements for every AI-driven product. |
| **[Part 5](#part-5-lokey-ai-category-attribute-mapping--enforcement-rules)** | **Lokey AI Category Attribute Mapping & Enforcement Rules** | v1.0 | Links child categories to attribute groups, ensuring correct attributes are included for each product type (Flower, Vape, Edible, etc.). |
| **[Part 6](#part-6-lokey-ai-data-integration--knowledge-base-reference-protocol)** | **Lokey AI Data Integration & Knowledge Base Reference Protocol** | v1.0 | Governs how the AI reads and validates JSON/CSV reference files like `attributes.json` and `brands.json`. Prohibits truncation and numeric assumptions. |
| **[Part 7](#part-7-lokey-product-creator-agent-operating-instructions)** | **Lokey Product Creator Agent – Operating Instructions** | v1.0 | Defines AI behavioral rules — “Zero-Guess Policy,” non-destructive update behavior, and stop conditions. Provides field-by-field execution rules. |
| **[Part 8](#part-8-lokey-delivery-api--product-management-blueprint)** | **Lokey Delivery API + Product Management Blueprint** | v1.0 | Implementation reference outlining core WooCommerce + ATUM integration, safe field handling, and schema structure. |
| **[Part 9](#part-9-intro-to-lokey-api-product-safety-reference)** | **Intro to Lokey API Product Safety Reference** | v1.0 | Foundational overview describing Lokey’s product safety rules, global taxonomy use, and attribute behavior philosophy. |
| **[Part 10](#part-10-lokey-ai-data-governance-and-versioned-knowledge-protocol)** | **Lokey AI Data Governance & Versioned Knowledge Protocol** | v1.0 | Defines AI file version control, checksum validation, and reloading behavior for all JSON/CSV reference data. |

---

## ⚙️ System Hierarchy
```
├── Part_1-Lokey_Delivery_API_Technical_Framework-v2.0.md
├── Part_2-Lokey_AI_Product_Creation_Protocol-v2.3.md
├── Part_3-Lokey_SEO_and_Content_Generation_Standard-v2.3.md
├── Part_4-Lokey_AI_Audit_Logging_and_Version_Governance_Framework-v1.0.md
├── Part_5-Lokey_AI_Category_Attribute_Mapping_and_Enforcement_Rules-v1.0.md
├── Part_6-Lokey_AI_Data_Integration_and_Knowledge_Base_Reference_Protocol-v1.0.md
├── Part_7-Lokey_Product_Creator_Agent_Operating_Instructions-v1.0.md
├── Part_8-Lokey_Delivery_API_and_Product_Management_Blueprint-v1.0.md
├── Part_9-Intro_to_Lokey_API_Product_Safety_Reference-v1.0.md
└── Part_10-Lokey_AI_Data_Governance_and_Versioned_Knowledge_Protocol-v1.0.md
```

---

## 🧩 Cross-Document Dependencies

| Dependency | Description |
|-------------|--------------|
| Part 1 → Part 2 | The Product Creation Protocol depends on the API schema and safety rules defined in Part 1. |
| Part 2 → Part 3 | SEO & Content generation rules extend Part 2’s content generation phase. |
| Part 2 + Part 5 + Part 6 | Attribute assembly relies on category mappings and data integration logic. |
| Part 4 | Applies to all other parts for audit and version logging. |
| Part 7 | Acts as the behavioral rule set for AI execution across all other parts. |
| Part 9 | Provides foundational safety rules referenced in all schema-related documents. |

---

## 🧱 Compliance Overview
All documents conform to these global Lokey principles:
- **Non-Destructive Operations:** Never overwrite or delete data not explicitly updated.  
- **Canonical Taxonomy:** Always use existing `pa_` prefixed attributes and brand/category IDs.  
- **Auditability:** Every product action must be logged and versioned.  
- **Transparency:** All numeric or attribute data must be verifiable via structured sources.  
- **Public Clarity:** All descriptions must include disclaimers indicating informational use only.

---

## ✅ Version Control and Update Policy

| Rule | Description |
|------|--------------|
| **Major Update (+1.0)** | Core schema or logic change affecting downstream automation. |
| **Minor Update (+0.1)** | Structural, formatting, or rule clarification improvements. |
| **Patch Update (+0.01)** | Typographical or small content corrections. |

All version updates must be logged in the `CHANGELOG.md` file.

---

## 🔗 Quick Reference
- **Start Here:** [Part 1 – Technical Framework](Part_1-Lokey_Delivery_API_Technical_Framework-v2.0.md)  
- **Then:** [Part 2 – Product Creation Protocol](Part_2-Lokey_AI_Product_Creation_Protocol-v2.3.md)  
- **For Writing:** [Part 3 – SEO & Content Standard](Part_3-Lokey_SEO_and_Content_Generation_Standard-v2.3.md)  
- **For Auditing:** [Part 4 – Audit Logging Framework](Part_4-Lokey_AI_Audit_Logging_and_Version_Governance_Framework-v1.0.md)

---

## 🧩 End of Master README Index  
*Next: [Part 1 – Lokey Delivery API Technical Framework (v2.0)](Part_1-Lokey_Delivery_API_Technical_Framework-v2.0.md)*
