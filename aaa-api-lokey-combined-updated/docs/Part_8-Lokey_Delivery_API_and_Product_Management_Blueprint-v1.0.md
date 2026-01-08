# 🏗️ Part 8 – Lokey Delivery API + Product Management Blueprint (v1.0)
**Sequence Position:** Part 8 of the Lokey Delivery AI Documentation Suite  
**Preceded by:** *Part 7 – Lokey Product Creator Agent Operating Instructions (v1.1)*  
**Followed by:** *Part 9 – Intro to Lokey API Product Safety Reference (v1.0)*  

---

## 1️⃣ Purpose  
This document defines the **Lokey Delivery API system architecture, endpoints, and operational lifecycle** for safe, consistent product management within WooCommerce and ATUM environments.  
It acts as the technical bridge between the Lokey AI automation layer and the backend API system.

---

## 2️⃣ Mission  
The mission of this blueprint is to establish **non-destructive, deterministic API behavior** that allows seamless product creation, updates, and synchronization without risking taxonomy or inventory corruption.  
Every endpoint interaction must be logged, authenticated, and verified for compliance.

---

## 3️⃣ System Overview  

| Component | Description |
|------------|-------------|
| **WooCommerce** | Hosts product catalog, taxonomy, attributes, and pricing data. |
| **ATUM Inventory Management** | Manages supplier linkage, purchase orders, and stock data. |
| **Lokey Inventory API** | Provides unified interface for AI automation to safely interact with WooCommerce + ATUM. |
| **Lokey Product Creator AI** | Generates, validates, and posts structured payloads to API endpoints. |
| **Audit Framework (Part 4)** | Logs every operation, file version, and validation event. |

---

## 4️⃣ Core Design Principles  

| Principle | Description |
|------------|-------------|
| **Non-Destructive Operations** | All updates merge or append; never delete or overwrite existing data. |
| **Schema Compliance** | Every payload follows the `ProductExtended` JSON schema. |
| **Authentication Required** | JWT tokens are mandatory for all API interactions. |
| **Supplier Linking Post-Create** | Supplier assignment always occurs after product creation. |
| **Traceability** | Every request and response logged for version tracking. |
| **Deterministic Responses** | Identical inputs yield identical results—no randomization. |

---

## 5️⃣ Authentication & Security  

**Endpoint:** `/jwt-auth/v1/token`  
Used to generate valid JWT for all Lokey operations.  

**Workflow:**
1. AI authenticates using dedicated system credentials.  
2. Token stored securely for session duration.  
3. Token validated via `/jwt-auth/v1/token/validate`.  

**Headers for all requests:**  
```http
Authorization: Bearer <JWT_TOKEN>
Content-Type: application/json
```

Tokens expire automatically; AI must renew as required.  

---

## 6️⃣ Core API Endpoints  

| Method | Endpoint | Purpose |
|--------|-----------|----------|
| **POST** | `/lokey-inventory/v1/products/extended` | Create new product safely. |
| **PUT** | `/lokey-inventory/v1/products/extended/{id}` | Update existing product. |
| **GET** | `/lokey-inventory/v1/products/{id}` | Retrieve product details. |
| **GET** | `/lokey-inventory/v1/diagnostics` | Health check for endpoint readiness. |
| **PUT** | `/lokeyInventoryUpdateProductExtended` | Supplier post-link and safe field merge. |
| **POST** | `/lokey-inventory/v1/inventory/{product_id}` | ATUM sync (purchase price, quantity). |

---

## 7️⃣ Product Creation Workflow  

### Step-by-Step Sequence  
1️⃣ **Authenticate** via JWT.  
2️⃣ **Validate category, brand, and attributes** using reference files.  
3️⃣ **Generate short and long descriptions** (per Parts 2–3).  
4️⃣ **Assemble payload** including attributes, categories, and verified fields.  
5️⃣ **POST product** to `/lokey-inventory/v1/products/extended`.  
6️⃣ **Verify response** (ensure status: “success”).  
7️⃣ **Attach supplier** via update endpoint.  
8️⃣ **Log audit record** including file hashes and word counts (Part 4).  

---

## 8️⃣ Product Update Workflow  

### Safe Update Logic  
1️⃣ Retrieve product ID from WooCommerce via API.  
2️⃣ Apply **partial field update** (only specified fields).  
3️⃣ Never send null or empty values.  
4️⃣ Attributes and taxonomies are merged, not replaced.  
5️⃣ Confirm success response before proceeding.  

**Update Example:**
```json
{
  "id": 63514,
  "regular_price": "55.00",
  "discount_percent": 30,
  "stock_quantity": 0
}
```

---

## 9️⃣ Schema Compliance – ProductExtended Model  

| Field | Type | Required | Description |
|--------|------|----------|-------------|
| `name` | string | ✅ | Product title. |
| `sku` | string | ✅ | Unique SKU. |
| `type` | string | ✅ | “simple” (default). |
| `regular_price` | string | ✅ | Base price. |
| `discount_percent` | number | Optional | Auto-computes sale price. |
| `categories` | array | ✅ | Child category only. |
| `brands` | array | ✅ | BeRocket brand mapping. |
| `attributes` | array | ✅ | Category-mapped attributes (see Part 5). |
| `image_url` | string | ✅ | Must remain unaltered. |
| `description` | string | ✅ | Full HTML (800–1000 words). |
| `short_description` | string | ✅ | 80–120 words + bullet list. |
| `stock_quantity` | int | ✅ | Always 0 on creation. |
| `manage_stock` | boolean | ✅ | Always true. |

---

## 🔟 Inventory Management Integration  

| Integration | Description |
|--------------|-------------|
| **ATUM Link** | Supplier association occurs post-creation using `_supplier_id`. |
| **Stock Handling** | Products always created with `stock_quantity: 0` and `manage_stock: true`. |
| **WooCommerce Auto Logic** | Automatically flags out-of-stock status. |
| **Purchase Price Updates** | Managed separately through `/lokey-inventory/v1/inventory/{product_id}`. |

All ATUM data is synchronized safely via the AI-controlled post-update operation.

---

## 1️⃣1️⃣ Audit Integration (Part 4 Reference)  

Every API call writes a structured audit record containing:  
- `ai_ruleset_version`  
- `product_id` and SKU  
- Request payload hash (SHA-256)  
- Response status code  
- Attribute completeness percentage  
- Image URL hash check  
- File version hashes from the Knowledge Base  

Logs stored as JSON under `/logs/ai-products/YYYY/MM/` and retained for 24 months minimum.

---

## 1️⃣2️⃣ Error & Exception Management  

| Error Code | Meaning | AI Behavior |
|-------------|----------|-------------|
| 400 | Malformed payload | Stop chain; flag for review. |
| 401 | Invalid JWT | Refresh token and retry. |
| 403 | Permission denied | Halt and log security escalation. |
| 404 | Endpoint or ID not found | Skip record and log failure. |
| 409 | Duplicate SKU | Update instead of create. |
| 500 | Internal server error | Retry once, then stop. |

All error events trigger JSON audit entry (`status: "error"`).  

---

## 1️⃣3️⃣ Compliance Checklist  

| Check | Requirement | Status |
|--------|-------------|---------|
| JWT authentication required | ✅ |
| Payload adheres to `ProductExtended` schema | ✅ |
| Child category only | ✅ |
| Image URL unchanged | ✅ |
| Stock = 0; tracked | ✅ |
| Attribute completeness ≥ 95% | ✅ |
| Supplier linked post-creation | ✅ |
| Audit record generated | ✅ |
| Non-destructive updates only | ✅ |

---

## ✅ Summary  
This blueprint defines how the Lokey Delivery API and automation ecosystem maintain safe, compliant, and auditable product operations.  
It ensures every system interaction is validated, non-destructive, and fully traceable through the audit and data governance layers.  

---

**End of Part 8 – Lokey Delivery API + Product Management Blueprint (v1.0)**  
*Next: [Part 9 – Intro to Lokey API Product Safety Reference (v1.0)](Part_9-Intro_to_Lokey_API_Product_Safety_Reference-v1.0.md)*  
