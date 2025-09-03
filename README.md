# Inventory PDF & Barcode Manager for WooCommerce

Generate a PDF (or printer-friendly HTML) listing all **physical** WooCommerce products (including variations), then bulk-update inventory, and import counts from a **barcode scan list** using the `_global_unique_id` meta.

## 1.0.2
- **UI:** Tight constraints for the Quantity input so it never overflows the table (specific classes + `!important`).
- **UI:** Fixed table layout with word wrapping to keep long titles from pushing the layout horizontally.

## 1.0.1
- **Fix:** Removed parent variable products from lists (only variations shown).
- **Fix:** Eliminated duplicate variation rows by not prefetching `variation` type and expanding from parents only.

## Features
- Filter by **categories** for both the PDF and the bulk update list.
- Includes **physical** products only (excludes Virtual products).
- Includes **variations** and appends attribute values to the title.
- PDF via **Dompdf** (optional) or use the printer-friendly HTML fallback and "Print → Save as PDF".
- Bulk update page with columns: **ID, Title (read-only), Manage stock, Quantity**.
- Barcode import page:
  - Paste/scan one barcode per line (from `_global_unique_id`).
  - Modes: **Set**, **Add**, **Subtract (no negatives)**.
  - Option to enable "**manage stock**" for all updated items.
  - Shows a summary of updates and **alerts to unknown barcodes**.

## Installation
1. Copy the folder `wc-inventory-pdf-and-barcode` into your WordPress `wp-content/plugins/`.
2. (Optional, recommended) Install **Dompdf** to enable true PDF files:
   ```bash
   cd wp-content/plugins/wc-inventory-pdf-and-barcode
   composer install
   ```
   If Dompdf is not installed, the **Generate PDF** page will display a printer-friendly HTML table.

3. Activate the plugin in **Plugins → Installed Plugins**.

## Usage
- **Inventory PDF** (menu): Choose categories and generate a PDF (or print-friendly HTML).
- **Bulk Update**: Filter categories, set *Manage stock* and *Quantity*, then **Save Changes**.
- **Barcode Import**: Paste scans (one per line) and choose a mode: *Set*, *Add*, or *Subtract*.
  - Unknown barcodes are listed in the summary.
  - Optionally enable *Manage stock* for all updated items.

## Notes
- Stock status updates automatically: `instock` if quantity > 0, else `outofstock`.
- Titles for variations show attributes, e.g. `Parent – Size: Large, Color: Red`.
- Sorting is alphabetical by title (default) or by ID.

## Safety
- CSRF protected with nonces, and restricted to users with `manage_woocommerce` capability.
- Sanitization applied to inputs.
