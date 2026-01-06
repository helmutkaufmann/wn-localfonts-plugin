# LocalFonts for WinterCMS (v5.0)

**LocalFonts** is a professional CLI utility for WinterCMS that localizes fonts from **Google Fonts**, **Bunny Fonts**, and **Fontshare**. It surgically extracts font assets, generates a `manifest.json` for version tracking, and builds a `fonts.less` file for immediate integration with your theme.



## Key Features
* **Multi-Source Discovery**: Automatically falls back from Google to Bunny to Fontshare.
* **Regex Isolation**: Critical logic is protected within the `BaseCommand` to prevent syntax errors.
* **Smart Filtering**: Automatically bypasses subset filters if the provider labels a block with the font name (common for Fontshare/Satoshi).
* **Variable Font Support**: Downloads both individual "Static" weights and modern "Variable" font files.
* **Surgical Removal**: Intelligent file cleanup that only deletes assets when no other variants reference them.

---

## Installation

### Via Composer
Add the repository to your root `composer.json` and install:
```bash
composer require mercator/wn-localfonts-plugin

```

### Manual Installation

1. Create: `plugins/mercator/localfonts`
2. Place the plugin files in their respective folders.
3. Register the commands:

```bash
php artisan plugin:refresh Mercator.LocalFonts

```

---

## Configuration

Control font character sets via your root `.env` file.

| Setting | Environment Variable | Default | Description |
| --- | --- | --- | --- |
| **Subsets** | `LOCALFONTS_SUBSETS` | `latin` | Comma-separated list (e.g., `latin,latin-ext`). Set to empty to fetch all. |

---

## Usage

### 1. Add a Font (`localfonts:add`)

Search and localize a font family.

```bash
# Add standard weight
php artisan localfonts:add Satoshi

# Add full family (All weights + Italics + Variable blocks)
php artisan localfonts:add Satoshi --full

```

### 2. List Fonts (`localfonts:list`)

View managed fonts. You can filter by family to see specific variants.

```bash
# List all
php artisan localfonts:list

# Filter by family
php artisan localfonts:list Satoshi

```

### 3. Remove Fonts (`localfonts:remove`)

Surgically clean up your font directory.

```bash
# Remove only the large Variable files to save space
php artisan localfonts:remove Satoshi --variable

# Remove only Static weights (keeping the Variable file)
php artisan localfonts:remove Satoshi --static

# Remove everything for a family without confirmation
php artisan localfonts:remove Satoshi --force

```

---

## Theme Integration

Include the generated `fonts.less` (default: `assets/src/fonts/fonts.less`) into your main theme file.

### UIkit Variable Mapping

We recommend using a mixin to map the specific localized IDs:

```less
@import "../src/fonts/fonts.less";

// Mixin for surgical weight control
.font-variant(@family; @weight; @style: normal) {
    font-family: @family;
    font-weight: @weight;
    font-style: @style;
}

h1 { .font-variant('Satoshi', 700); }

```
---
### Pro-Tip: Browser Auditing
If you aren't sure whether to keep the **Static** or **Variable** files, you can use the **Chrome DevTools Coverage Tab** to see exactly which font files are being utilized by your page.

1.  Open **DevTools** (`Cmd+Shift+P` or `Ctrl+Shift+P`).
2.  Type **"Coverage"** and select **Show Coverage**.
3.  Click the **Reload** icon.
4.  If a 200kb Variable font is 90% "red" (unused), you should switch to the 20kb Static files using `localfonts:remove Satoshi --variable`.
