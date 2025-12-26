# Database Schema Documentatie - Duidelijkheid.com

> Laatst bijgewerkt: 2025-12-22

## Overzicht

Dit document beschrijft het database schema van Duidelijkheid.com. Het schema is geconsolideerd om consistentie te garanderen en toekomstige "add column" migraties te minimaliseren.

---

## 📚 Tabellen Groepen

### 1. Content / Blog Tabellen

| Tabel | Beschrijving | Model |
|-------|-------------|-------|
| `blogs` | Hoofdblog artikelen | `Blog` |
| `over_ons_blogs` | Over Ons sectie artikelen | `OverOnsBlog` |
| `categories` | Categorieën voor alle blogs | `Category` |

**Gemeenschappelijke velden (blogs & over_ons_blogs):**
```
- title, slug, excerpt, content, cover_image_path
- media_type (enum: image, youtube, upload)
- video_path, download_file_path, extra_files_paths (JSON), external_url
- category_id (FK naar categories)
- is_published, published_at
- meta_title, meta_description, canonical_url, robots_index, robots_follow, og_image_path
- deleted_at (soft deletes)
```

### 2. Page Hero Content Tabellen

| Tabel | Beschrijving | Model |
|-------|-------------|-------|
| `blog_pages` | Hero content voor blog overzichtspagina | `BlogPage` |
| `innovatie_pages` | Hero content voor innovatie sectie | `InnovatiePage` |
| `over_ons_pages` | Hero content voor over ons sectie | `OverOnsPage` |
| `contact_pages` | Hero content + formulier settings voor contact | `ContactPage` |

**Gemeenschappelijke velden (alle page tabellen):**
```
- title, hero_title, hero_subtitle, intro, content
- image_path, hero_image_path
- meta_title, meta_description, seo_image_path, canonical_url
- robots_index, robots_follow, published
- updated_by
```

### 3. Gebruikers & Admin Tabellen

| Tabel | Beschrijving | Model |
|-------|-------------|-------|
| `users` | Reguliere gebruikers | `User` |
| `admins` | Beheerders | `Admin` |

### 4. Contact & Communicatie Tabellen

| Tabel | Beschrijving | Model |
|-------|-------------|-------|
| `contact_messages` | Ingediende contactformulieren | `ContactMessage` |
| `feedback` | Gebruikersfeedback | `Feedback` |

### 5. CMS Content Tabellen

| Tabel | Beschrijving | Model |
|-------|-------------|-------|
| `welcomes` | Homepage content | `Welcome` |
| `terms` | Algemene voorwaarden | `Terms` |
| `privacy_policies` | Privacybeleid | `PrivacyPolicy` |

---

## 🔗 Relaties

```
Category (1) ─────┬───── (*) Blog
                  └───── (*) OverOnsBlog

Admin (1) ────────────── (*) ContactMessage (handled_by)
```

---

## 📝 Categories Type Systeem

De `categories` tabel heeft een `type` veld voor onderscheid:

| Type | Gebruikt voor |
|------|--------------|
| `blog` | Hoofdblog artikelen |
| `over_ons` | Over Ons blog artikelen |
| `innovatie` | Innovatie blog artikelen |

---

## 🚀 Migratie Best Practices

1. **Nieuwe velden:** Als je nieuwe velden toevoegt, voeg ze toe aan ALLE gerelateerde tabellen voor consistentie.
2. **Schema checks:** Gebruik `Schema::hasColumn()` om bestaande kolommen te checken.
3. **Geen losse add-migraties:** Consolideer schema wijzigingen waar mogelijk.

---

## 📁 Bestandslocaties

- **Models:** `app/Models/`
- **Migrations:** `database/migrations/`
- **Seeders:** `database/seeders/`
