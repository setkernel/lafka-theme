# Lafka - WordPress / WooCommerce Theme

A modern, feature-rich WordPress theme for restaurants, cafes, food businesses, and WooCommerce stores.

Originally developed by [theAlThemist](https://www.althemist.com) and sold on ThemeForest. The theme has since been delisted and is no longer supported by the original author. This repository continues development as an open-source project under the GPL v2+ license.

## Requirements

- WordPress 5.6+
- WooCommerce 7.0+
- PHP 7.4+
- [Lafka Plugin](https://github.com/setkernel/lafka-plugin) (companion plugin, required)

## Optional Commercial Plugins

The theme has built-in support for these commercial plugins. They are **not required** but enable additional functionality:

- **WPBakery Page Builder** — Visual page building, custom element templates
- **Revolution Slider** — Advanced hero sliders in header areas

These must be purchased separately from their respective vendors.

## Installation

1. Download or clone this repository into `wp-content/themes/lafka`
2. Activate the theme in WordPress Admin → Appearance → Themes
3. Install the [Lafka Plugin](https://github.com/setkernel/lafka-plugin) when prompted (or manually)
4. Optionally install WPBakery Page Builder and/or Revolution Slider

## Features

- **Custom Food Menu System** — Restaurant menu items with categories, prices, ingredients, allergens, and nutrition facts
- **Deep WooCommerce Integration** — Custom shop layouts, ajax cart, quick view, wishlist, product comparison
- **Mega Menu** — Multi-column menus with icons, images, and custom labels
- **Multiple Header Styles** — Sticky, transparent, with search, cart, and account dropdowns
- **Blog Layouts** — Standard, masonry, and mosaic styles
- **bbPress Forum Support**
- **The Events Calendar Support**
- **WPML Multilingual Support** with RTL
- **7 Demo Content Packages** — One-click import
- **Responsive Design** — Mobile-optimized with custom mobile menu
- **YouTube Video Backgrounds** — Per-page or global
- **Custom Options Framework** — Extensive theme customization panel

## Structure

```
lafka/
├── incl/                   # Core includes
│   ├── system/             # Core functions and config
│   ├── lafka-options-framework/  # Theme options panel
│   ├── tgm-plugin-activation/   # Plugin installer
│   └── ...
├── js/                     # JavaScript (custom + libraries)
├── styles/                 # CSS (dynamic, responsive, admin, RTL)
├── woocommerce/            # WooCommerce template overrides
├── partials/               # Reusable template parts
├── vc_templates/           # WPBakery element templates
├── page_templates/         # Custom page templates
├── store/demo/             # Demo content XML files
├── tribe-events/           # Events Calendar templates
├── plugins/                # Place commercial plugin zips here
├── functions.php           # Main theme functions
├── header.php / footer.php # Layout templates
└── style.css               # Main stylesheet (v4.5.7)
```

## License

GPL v2 or later. See [LICENSE](LICENSE).

## Contributing

Contributions are welcome. Please open an issue to discuss changes before submitting a pull request.
