# Propertyhive

Test zone for Property Hive.

## Property search card template

The `modules/property-search-card` directory contains a drop-in replacement
for the Property Hive search results card (`property-content.php`) together with
companion stylesheets (`property-content.css`, `content-property.css`, and
`energy-label.css`) that recreate the reference layout used throughout these
exercises.

### Usage

1. Copy `modules/property-search-card/property-content.php` into your
   theme at `propertyhive/content-property.php` (create the directory if it does
   not already exist).
2. Copy the CSS files you need from `modules/property-search-card/` into your
   theme, for example placing them at `propertyhive/property-content.css`,
   `propertyhive/content-property.css`, or `propertyhive/energy-label.css`
   depending on how you organise overrides in your theme. The template will
   automatically attempt to load `property-content.css` and `energy-label.css`
   from the same directory as the PHP override.
3. The template automatically enqueues the stylesheet, formats key property
   metadata, and renders the bespoke layout with the availability badge,
   postcode/town line, address heading, price ribbon, floor-area/bedroom icons,
   and energy label pill. Use the `propertyhive_search_card_price_suffix`
   filter to adjust the trailing price copy (for example switching between
   `p/m`, `Kosten koper`, or bespoke labels).

Once installed, your Property Hive search results cards will align with the
new layout, complete with badges, feature icons, and responsive
handling for varying content lengths.

## Energy label styling

Copy `modules/property-search-card/energy-label.css` into the same directory as
your `content-property.php` override so the coloured badge renders using the
provided palette. The template falls back to inline styles if the file is not
present, ensuring the energy ribbon always displays with the correct colours.
