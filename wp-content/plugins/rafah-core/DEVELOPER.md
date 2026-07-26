# Rafah Core — Developer Guide

Enterprise conventions for maintaining and extending the Rafah platform. Read before changing anything.

Rafah is split into two packages with a strict boundary:

- **Rafah Core** (`plugins/rafah-core`) — all **data & business logic**: content types, fields, units engine, AJAX, schema, Polylang wiring, and the Elementor widgets that expose that data. Ships the shared Elementor Style controls.
- **Rafah theme** (`themes/rafah`, Astra child) — all **presentation**: templates, section rendering, the project-card renderer, the Customizer, the site hero/header/footer. Never owns data.

The golden rule: **data flows Core → theme; the theme never defines data, Core never hard-codes presentation.** Either side degrades gracefully if the other is inactive.

## Principles

1. **Never modify** WordPress core, Elementor, Astra, or third-party plugin files. Interact only through official WordPress APIs and Elementor's public widget API (`\Elementor\Widget_Base`, `elementor/widgets/register`).
2. **Additive-only data.** Updates must never delete or rename existing `_rafah_*` meta, posts, or terms. Data transformations go through versioned migrations.
3. **Extend via hooks, not edits.** Site customizations belong in a companion plugin or the child theme, using the filters below. This keeps Core and the theme updatable.
4. **Single source of truth.** Each concept is rendered/resolved in exactly one place: cards → `template-parts/project-card.php` (+ `rafah_theme_card_config()`); project sections → `rafah_theme_render_project_section()`; unit data → `Rafah_Units_DB`; style controls → `Rafah_Style_Controls`. Do not duplicate.
5. **Coding standard:** WordPress Coding Standards (WPCS). WPCS file/class naming (`class-rafah-*.php`, `Rafah_*`) intentionally takes precedence over PSR-4. Strictly modular: one class per concern, static `init()` bootstrapping, no globals.

## Module map (Core)

| Module | File | Responsibility |
|---|---|---|
| Bootstrap | `rafah-core.php` | Constants, includes, `rafah_core_modules` registry |
| Upgrades | `includes/class-rafah-upgrades.php` | Version-gated one-shot upgrade routine |
| Migrations | `includes/class-rafah-migrations.php` | File-based, ordered, idempotent migrations in `includes/migrations/` |
| Settings | `includes/class-rafah-settings.php` | Settings → Rafah (e.g. unit status highlight) |
| Post Types | `includes/class-rafah-post-types.php` | `project`, `agent`, `testimonial`, `news` CPTs |
| Sections | `includes/class-rafah-project-sections.php` | Generic `Rafah_Sections` registry (type-aware) |
| Taxonomies | `includes/class-rafah-taxonomies.php` | city, district, project_status, project_type, feature, amenity, news_category |
| Fields | `includes/fields-config.php` | Declarative field definitions (tabs → fields) |
| Meta Boxes | `includes/class-rafah-meta-boxes.php` | Renders/saves tabbed meta boxes, repeaters, media/gallery fields |
| Admin | `includes/class-rafah-admin.php` | List columns, filters, sorting, duplicate action |
| Schema | `includes/class-rafah-schema.php` | JSON-LD; defers Organization/Breadcrumb to Rank Math/Yoast |
| AJAX | `includes/class-rafah-ajax.php` | Public filter endpoint + shared `build_query_args()` |
| Polylang | `includes/class-rafah-polylang.php` | Translatable types + `_rafah_*` meta copy list |
| Assets | `includes/class-rafah-assets.php` | Front-end CSS/JS (deferred, no jQuery) |
| Covers | `includes/class-rafah-covers.php` | Hero/Card cover resolution + placeholder |
| Gallery | `includes/class-rafah-gallery.php` | `Rafah_Gallery::ids()/grid()/position()` — gallery source of truth |
| Editor | `includes/class-rafah-editor.php` | Classic editor for News/Posts |
| Units | `includes/units/*` | DB, dynamic columns, admin UI, import/export, front-end table |
| Elementor | `includes/elementor/*` | Category, widget registrar, shared `Rafah_Style_Controls` |
| Helpers | `includes/helpers.php` | `rafah_text()`, `rafah_meta()`, `rafah_price()`, `rafah_project_card()` |

Modules boot through `rafah_core_modules` in `rafah_core_init()`. A module is any class with a static `init()`.

## Theme map

| File | Responsibility |
|---|---|
| `functions.php` | Enqueues, `rafah_opt()`, widget registrar via `rafah_core_widgets`, share buttons, language switcher |
| `inc/customizer.php` | Rafah Theme panel: Colors, Project Cards, Section Manager (per type), Header/Footer/Hero/Contact |
| `inc/project-card.php` | `rafah_theme_card_config()` — resolves card look (defaults → Customizer → per-instance) |
| `inc/single-project-sections.php` | `rafah_theme_section_order()`, `rafah_theme_render_section()`, per-section renderer, `[rafah_project_section]` |
| `inc/class-rafah-hero-widget.php` | Hero Elementor widget (presentation; registered via `rafah_core_widgets`) |
| `inc/class-rafah-project-section-widget.php` | Renders any registered section as an Elementor widget/shortcode |
| `template-parts/project-card.php` | THE project card renderer (all grids/archives/AJAX) |
| `template-parts/blog-card.php`, `hero.php`, `home-fallback.php`, `site-header.php`, `site-footer.php` | Presentation partials |
| `single-project.php`, `single-agent.php`, `archive-project.php`, `archive-agent.php`, `taxonomy.php` | Templates |

## Elementor widgets

Category **Rafah**. Every widget follows Elementor's native **Content / Style / Advanced** structure; the Style tab is built from `Rafah_Style_Controls` using native Group Controls (Typography, Background, Border, Box Shadow), scoped per-instance with `{{WRAPPER}}`.

| Widget | `get_name()` | Package |
|---|---|---|
| Projects Grid | `rafah_projects_grid` | Core |
| Project Filter | `rafah_project_filter` | Core |
| Stats | `rafah_stats` | Core |
| Agents Grid | `rafah_agents_grid` | Core |
| Testimonials | `rafah_testimonials` | Core |
| FAQ | `rafah_faq` | Core |
| CTA | `rafah_cta` | Core |
| News | `rafah_news` | Core |
| Blog | `rafah_blog` | Core |
| Project Gallery | `rafah_project_gallery` | Core |
| Projects Map | `rafah_projects_map` | Core |
| Hero | `rafah_hero` | Theme |
| Project Section | `rafah_project_section` | Theme |
| Footer | `rafah_footer` | Theme |

Widgets are presentation of Core data; disabling Elementor never loses data (the theme renders defaults). Enabling "Edit with Elementor" on every public editor-enabled CPT is automatic (`Rafah_Elementor::ensure_cpt_support()`, filter `rafah_elementor_post_types`).

### Adding a Style tab to a new widget

At the end of `register_controls()`, call the relevant `Rafah_Style_Controls::*` methods targeting your widget's BEM classes:

```php
Rafah_Style_Controls::heading( $this );
Rafah_Style_Controls::box( $this, 'card', __( 'Card', 'rafah' ), '.my-card', array( 'hover' => true ) );
Rafah_Style_Controls::text( $this, 'title', __( 'Title', 'rafah' ), '.my-card__title' );
Rafah_Style_Controls::button( $this, '.rafah-btn' );
Rafah_Style_Controls::grid( $this, '.rafah-grid' );
```

Methods: `heading`, `text($id,$label,$sel,$align=false)`, `box($id,$label,$sel,$opts)`, `button($sel,$id)`, `badges`, `grid($sel,$id)`, `image($sel,$id)`, `overlay($sel,$id)`. Theme widgets must guard with `class_exists( 'Rafah_Style_Controls' )`.

### Repeatable UI — every list is a native repeater

Principle: **if it's visible on the frontend, the editor can add / remove / reorder / duplicate / collapse / hide / style it, and an empty repeater leaves no markup and no empty space.**

- Register a button group with one call: `Rafah_Repeaters::buttons( $widget, 'buttons', __( 'Buttons', 'rafah' ) )` (fields: text, link, variant primary/secondary/light/ghost/whatsapp). Add your own repeater with `new \Elementor\Repeater()` for other item types.
- Render buttons with `rafah_buttons_html( $items, 'wrap-class' )` — it filters empty rows and returns `''` (no wrapper) when none remain.
- **Always render conditionally.** In `render()`, filter blank rows and bail/omit the container when the collection is empty. Never emit a wrapper for an empty list. Example: `$rows = array_filter( $s['items'], fn( $r ) => '' !== trim( (string) ( $r['label'] ?? '' ) ) ); if ( ! $rows ) { return; }`.
- **Backward compatibility:** when replacing fixed fields with a repeater, keep reading the legacy setting keys as a fallback when the repeater is empty (Elementor preserves saved values of removed controls). See the Hero and CTA widgets.
- Data-driven lists (project status/featured badges, agent social links, agent contact actions) stay sourced from Rafah Core fields — those are already meta-box repeaters. The single-source-of-data rule is unchanged.

## Extension hooks

**Data / structure filters**

- `rafah_core_modules( array $modules )` — add/remove/replace modules.
- `rafah_project_fields`, `rafah_agent_fields`, `rafah_testimonial_fields( array $tabs )` — add tabs/fields. Types: `text, textarea, number, select, checkbox, date, url, tel, email, media, gallery, file, post_select, repeater`.
- `rafah_project_cpt_args`, `rafah_agent_cpt_args`, `rafah_testimonial_cpt_args`, `rafah_news_cpt_args`, `rafah_news_category_args` — registration args.
- `rafah_taxonomies( array $taxonomies )` — taxonomy definitions.
- `rafah_unit_columns` — default unit column plan.
- `rafah_classic_editor_post_types`, `rafah_elementor_post_types` — editor targeting.
- `rafah_admin_i18n_map( array $map )` — extend/override the AR dashboard translation map.

**Sections & cards**

- `rafah_sections( array $registry )`, `rafah_sections_{type}( array $sections )` — register/reorder sections for any content type.
- `rafah_card_config( array $config, array $overrides )` — final project-card presentation config.

**Widgets & schema**

- `rafah_core_widgets( array $widgets )` — Elementor widget list (`file-slug => Class_Name`). Already-loaded external classes are used as-is (theme widgets use this).
- `rafah_schema_enabled( bool )` — master switch for Rafah JSON-LD.
- `rafah_schema_seo_owns_org_breadcrumb( bool )` — defaults true when Rank Math/Yoast is active; skips Rafah's Organization + Breadcrumb to avoid duplicates.
- `rafah_schema_graph( array $graph )` — mutate the final schema graph.

**Actions**

- `rafah_core_loaded` — after all modules init.
- `rafah_core_upgraded( string $version )` — after upgrade routine.
- `rafah_render_section( string $type, string $id, int $post_id )` — render a registered section for a non-project type.
- `rafah_before_units_table`, `rafah_after_units_table( int $project_id )` — inject around the front-end units table.
- `rafah_units_changed( int $project_id )` — after any unit write (cache busting).

**Polylang:** when you add a *structural* `_rafah_*` field that should copy across translations, add its key to `Rafah_Polylang::copy_metas()`. Leave per-language text (subtitle, card note) out so each translation edits independently.

**Example — add a field from the child theme:**

```php
add_filter( 'rafah_testimonial_fields', function ( $tabs ) {
    $tabs['details']['fields'][] = array(
        'key'   => 'video_url',
        'label' => __( 'Video Testimonial URL', 'rafah' ),
        'type'  => 'url',
    );
    return $tabs;
} );
```

The meta box UI, sanitization, and saving are automatic — driven by the declarative config.

## Template overrides

The theme renders Core data through overridable parts. To restyle without forking Core:

- **Project card:** copy `template-parts/project-card.php` logic is driven by `rafah_theme_card_config()`; prefer the Customizer (Project Cards) or the Projects Grid → Card controls over editing the file. For a bespoke layout, use the **Project Section** widget/shortcode to compose sections in Elementor.
- **Project sections:** control order/visibility/headings in Customizer → *{Type} Sections*. Add a section via `rafah_sections`/`rafah_sections_{type}` and render it through `rafah_theme_render_section()` (projects) or the `rafah_render_section` action (other types).
- **Cover images:** two optional fields per project (Hero Cover, Card Cover) with fallbacks resolved in `rafah_project_cover_id()`.

## Units engine

Fully dynamic. All unit values live in a `specs` JSON column keyed by permanent column IDs (`wp_rafah_units`). Column config is per-project post meta `_rafah_unit_columns`; reusable templates in option `rafah_unit_column_templates`. DB access is centralized in `Rafah_Units_DB` (prepared statements; integer-cast `IN()` lists; whitelisted `orderby`/status). All AJAX handlers pass through `guard()` (nonce `rafah_units` + `edit_post` capability).

## Upgrades & versioning

- Version lives in `rafah-core.php` (`RAFAH_CORE_VERSION`) and header, mirrored in option `rafah_core_version`.
- **All new data changes** ship as a dated file in `includes/migrations/` returning `array( 'id', 'description', 'run' => callable )`. `Rafah_Migrations` runs each once (recorded in option `rafah_migrations_done`), on `admin_init` for `manage_options` users, retrying on failure. This is the **single canonical migration path.** Migrations must be **idempotent and additive-only**.
- `Rafah_Upgrades` is **frozen/historical** — a version→method system retained only to run the 1.1.0–1.3.0 migrations on very old sites and to advance the `rafah_core_version` checkpoint. **Do not add entries to it.** (Two options exist by design: `rafah_core_version` = semver checkpoint; `rafah_migrations_done` = discrete migration ids.)
- Uninstalling **preserves all data** unless `RAFAH_REMOVE_ALL_DATA` is defined in `wp-config.php` (see `uninstall.php`).

## Compatibility posture

- **WordPress:** only stable public APIs; `show_in_rest => true` keeps CPTs Gutenberg/REST-ready.
- **PHP 8.1–8.3:** no dynamic properties (all static-method modules), no removed functions, all null-risk string ops `(string)`-cast.
- **Elementor Free & Pro:** widgets use only `\Elementor\Widget_Base` + `elementor/widgets/register` + public Group Controls. No Theme Builder / Pro dependency. If Elementor is absent the widgets are simply unavailable; the theme still renders.
- **RTL / Polylang:** AR-default; logical CSS properties (`inset-inline-*`); dashboard AR via `Rafah_Admin_I18n`; front strings via `rafah_text()`.
- **Rank Math / Yoast:** Rafah cedes Organization + Breadcrumb schema when either is active; keeps Project/Agent listing schema.
- **Meta keys are the public data contract:** `_rafah_{field}` on `project`, `agent`, `testimonial`, `news`. Treat as API — never rename.
