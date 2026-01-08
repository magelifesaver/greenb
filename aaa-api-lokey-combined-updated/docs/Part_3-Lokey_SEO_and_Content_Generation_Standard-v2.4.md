# 🧠 Part 3 – Lokey SEO & Content Generation Standard (v2.4)
**Sequence Position:** Part 3 of the Lokey Delivery AI Documentation Suite  
**Preceded by:** *Part 2 – Lokey AI Product Creation Protocol (v2.4)*  
**Followed by:** *Part 4 – Lokey AI Audit Logging & Version Governance Framework (v1.0)*  

---

## 1️⃣ Purpose  
This document defines the **complete content generation and SEO framework** for Lokey AI-driven product creation.  
It ensures that every product description—both short and long—is accurate, compliant, structured for SEO, and aligned with attribute data from the Lokey knowledge base.  

---

## 2️⃣ Objectives  
- Guarantee uniform writing tone and structure.  
- Enforce clear HTML formatting rules.  
- Maintain SEO and accessibility best practices.  
- Integrate verified **public sentiment** into content naturally.  
- Support consistency with the `ProductExtended` schema defined in *Part 1*.

---

## 3️⃣ Content Standards Overview

| Content Type | Purpose | Word Count | Structure |
|---------------|----------|-------------|------------|
| **Short Description** | Overview paragraph for product cards and search | 80–120 words total | 1–2 sentences + bullet list (3–5 features) |
| **Long Description** | Detailed HTML content for product page | 800–1000 words | 7-section structure with mandatory disclaimer |

---

## 4️⃣ Short Description Guidelines  

**Purpose:** Present a concise and factual summary that introduces the product and key features at a glance.  

**Length:** 80–120 words total (paragraph + bullet list)  

**Structure:**  
```html
<p><strong>{Product Name}</strong> is a {classification} {product type} designed for consistent quality and reliable performance. With {verified flavor/effect reference}, this product stands out for its balanced craftsmanship and smooth delivery.</p>
<ul>
  <li>Strain: {Strain}</li>
  <li>Flavor & Aroma: {Verified Flavors}</li>
  <li>Effects: {Verified Effects}</li>
  <li>Classification: {Classification}</li>
</ul>
```

**Rules:**  
- ✅ Use **verified** information only (from JSON or enrichment sources).  
- 🚫 Do **not** include brand name (brand already shown via plugin).  
- ⚠️ Avoid superlatives (“best,” “premium”) or medical language.  
- 💡 No hyperlinks, buttons, or CTAs.

---

## 5️⃣ Long Description Guidelines  

**Purpose:** Create a thorough, SEO-optimized explanation of the product, its strain, and its verified qualities.  

**Length:** **800–1000 words** total  

**Structure:**
1. `<h2>` Product Name – Classification  
2. `<h3>Strain Information</h3>`  
3. `<h3>Features and Benefits</h3>`  
4. `<h3>Detailed Specifications</h3>`  
5. `<h3>Suggested Usage</h3>`  
6. `<h3>Public Sentiment Snapshot</h3>`  
7. `<h3>Frequently Asked Questions</h3>`  

**Formatting Rules:**  
- Use only: `<h2>`, `<h3>`, `<p>`, `<ul>`, `<li>`, `<strong>`, `<em>`, `<small>`.  
- Grade 8–10 readability; concise sentences.  
- Strain name ≤ 4 times total.  
- No numeric THC/CBD values unless verified.  
- Avoid repetitive phrasing (“experience,” “premium,” “high quality”).  

**Mandatory Disclaimer:**  
Add at the end of every long description:  
```html
<p><small>The information provided is based on publicly available sources and is not a medical recommendation in any way.</small></p>
```

---

## 6️⃣ Section-by-Section Breakdown  

| Section | Details | Word Range | Notes |
|----------|----------|-------------|-------|
| **H2: Product Name – Classification** | 1–2 sentences introducing the product. | 50–75 | Include classification + strain once. |
| **H3: Strain Information** | Describe lineage, flavor, and general strain background using verified info. | 150–200 | Pull from Leafly / Allbud data. |
| **H3: Features and Benefits** | Discuss manufacturing quality, terpene profile, or unique selling points. | 100–150 | Avoid sales tone; focus on verified traits. |
| **H3: Detailed Specifications** | Present bullet list of verified data. | 100–150 | THC/CBD only if confirmed; always end with note about accuracy. |
| **H3: Suggested Usage** | Suggest appropriate contexts for enjoyment (no medical intent). | 100 | Use neutral, lifestyle language. |
| **H3: Public Sentiment Snapshot** | Summarize review sentiment from Weedmaps, Leafly, Reddit. | 75–125 | Use neutral phrasing like “users report” or “commonly described as.” |
| **H3: Frequently Asked Questions** | 5 Q/A items (verified or neutral fallback). | 150–200 | Use concise, factual tone. |
| **Disclaimer (Mandatory)** | Public transparency note. | 25–40 | Must always be present verbatim. |

---

## 7️⃣ Public Sentiment Snapshot Rules  

| Source | Usage | Notes |
|---------|--------|-------|
| **Weedmaps / Leafly** | Extract verified strain perceptions and review summaries. | Use average tone (“users report uplifting effects”). |
| **Reddit / Community Reviews** | Secondary reference for flavor and mood notes. | Paraphrase neutrally. |
| **AI Writing Rules** | Synthesize across 3+ sources; never copy verbatim. | Write in plain, natural English. |

**Example Section:**  
```html
<h3>Public Sentiment Snapshot</h3>
<p>According to reviews on Weedmaps and Leafly, this strain is appreciated for its clear-headed sativa effects and sweet, fruity undertones. Consumers often highlight its smooth draw and reliable flavor consistency, giving it strong marks for daytime enjoyment.</p>
```

---

## 8️⃣ FAQ Construction Standard  

**Required 5 Questions:**
1. What makes this product unique?  
2. What are its main effects?  
3. Is it beginner-friendly?  
4. Where is it produced or sourced?  
5. Does it contain additives or fillers?  

**AI Rule:**  
- If verified info is unavailable, use fallback phrasing:  
  > “Not listed by the manufacturer.”  

**Example:**  
```html
<h3>Frequently Asked Questions</h3>
<ul>
  <li><strong>What makes this product unique?</strong> It combines verified strain genetics with consistent quality control for a balanced result.</li>
  <li><strong>What are its main effects?</strong> Users often describe a clear-headed and energizing sensation.</li>
  <li><strong>Is it beginner-friendly?</strong> Yes, it is considered manageable for moderate users.</li>
  <li><strong>Where is it produced or sourced?</strong> This item is sourced from verified California manufacturers.</li>
  <li><strong>Does it contain additives or fillers?</strong> None listed by the manufacturer.</li>
</ul>
```

---

## 9️⃣ Attribute and Data Alignment  

| Rule | Enforcement |
|------|--------------|
| Use only verified global attributes (`pa_` prefixed). | ✅ |
| Match all descriptive terms to existing taxonomy terms. | ✅ |
| Load all mapped attributes from `attribute_groups_per_category.csv`. | ✅ |
| Include every applicable mapped attribute (not just top few). | ✅ |
| Create terms only for `pa_lineage` when required. | ✅ |

**Effect on Descriptions:**  
Flavor, effects, and classification terms in text must match their attribute counterparts exactly to ensure SEO and filter alignment.  

---

## 🔟 SEO Keyword and Tone Guidelines  

| Type | Description |
|-------|-------------|
| **Primary Keywords** | Strain name, category, classification, product form (e.g., “Hybrid Pre-Roll”). |
| **Secondary Keywords** | Flavor and effects terms; product type (e.g., “Live Resin,” “510 Cartridge”). |
| **Keyword Density** | Natural — no more than 2 repetitions per term. |
| **Voice** | Third-person, confident, informational. |
| **Tone** | Professional and factual — never exaggerated. |
| **Readability** | Grade 8–10 (Flesch score 60–80 ideal). |

---

## 1️⃣1️⃣ Quality & Compliance Checklist  

| Check | Requirement |
|--------|-------------|
| Short Description = 80–120 words | ✅ |
| Long Description = 800–1000 words | ✅ |
| Disclaimer present | ✅ |
| Attribute terms verified | ✅ |
| Category attribute group applied | ✅ |
| Image URL unchanged (see Part 1) | ✅ |
| Public Sentiment Snapshot included | ✅ |
| Brand excluded from short description | ✅ |
| FAQ present (5 Q&A items) | ✅ |

---

## ✅ Conclusion  

The Lokey SEO & Content Generation Standard ensures every AI-generated description adheres to a consistent, fact-based framework aligned with the technical and attribute standards defined in *Parts 1 and 2.*  

This guarantees all content is compliant, verifiable, and formatted for both user clarity and search performance.  

---

**End of Part 3 – Lokey SEO & Content Generation Standard (v2.4)**  
*Next: [Part 4 – Lokey AI Audit Logging & Version Governance Framework (v1.0)](Part_4-Lokey_AI_Audit_Logging_and_Version_Governance_Framework-v1.0.md)*  
