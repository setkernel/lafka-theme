=== Lafka ===

Contributors: setkernel
Requires at least: 6.6
Tested up to: 7.0
Requires PHP: 8.1
Version: 7.0.0
License: GNU General Public License v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Tags: e-commerce, woocommerce, restaurant, food, full-width-template, custom-menu, custom-logo, featured-images, threaded-comments, translation-ready, accessibility-ready, block-styles, wide-blocks

Mobile-first restaurant-ordering theme for WordPress and WooCommerce, built on a token-driven design system.

== Description ==

Lafka is a mobile-first WordPress theme for restaurants, cafes, and food
businesses selling with WooCommerce. Its appearance is driven end-to-end by a
single token system (over 200 `--lafka-*` CSS custom properties), so colour,
type, spacing, radius, and motion all trace back to one source of truth — no
scattered hex literals. Every layout is verified at the 375 / 768 / 1280
breakpoints.

The theme owns 100% of appearance. Online-ordering functionality (food-menu
post type, addons/toppings engine, kitchen display, delivery zones, order
hours, local-SEO schema) lives in the separate, free companion plugin — see the
FAQ. The theme works on its own and degrades gracefully when the plugin is not
installed.

WooCommerce support is deep: WooCommerce 9.5 or newer is recommended (tested up
to 10.9), with custom shop and archive layouts, an AJAX cart drawer, and a
rebuilt single-product page.

**Highlights**

* Mobile-first, responsive layouts verified at 375 / 768 / 1280.
* Token-driven design system — one visual source of truth (see `DESIGN_SYSTEM.md`).
* Deep WooCommerce integration: custom shop/archive layouts, AJAX cart drawer, inline quick-add.
* Redesigned single-product page with topping/size pickers and a sticky add-to-cart bar.
* List-card menu archive: image-left / body-right product rows.
* Editorial / long-form page system on the same token system.
* Mega menu, multiple header styles, and a purpose-built mobile navigation.
* Opt-in dark-mode scaffold plus an operator accent override in the Customizer.
* Self-hosted web fonts — no third-party font-CDN requests.
* Accessibility-minded: visible focus rings, ARIA labelling, and reduced-motion support.
* Structured, SEO-friendly markup and styling (JSON-LD schema is emitted by the companion plugin).
* Translation-ready (text domain `lafka`), RTL support, and a bundled WPML config.
* Block editor styles with wide and full-width alignment support.

== Installation ==

1. In your WordPress admin, go to **Appearance -> Themes -> Add New -> Upload Theme**.
2. Upload the theme ZIP, click **Install Now**, then **Activate**.
3. Configure appearance under **Appearance -> Customize** (colours, logo, header style, accent override, and the dark-mode option).
4. (Recommended) Install the free companion Lafka plugin — https://github.com/setkernel/lafka-plugin — to add the online-ordering feature set (food menu, addons, delivery zones, order hours, kitchen display, local-SEO schema).
5. (Recommended) For any code-level customisation, create a child theme so your changes survive theme updates.

== Frequently Asked Questions ==

= Do I have to install any plugin to use this theme? =

No. The theme is fully functional on its own and does not depend on any plugin
to render. For the complete restaurant online-ordering experience we **recommend**
the free companion Lafka plugin (https://github.com/setkernel/lafka-plugin),
which contributes the food-menu post type, the addons/toppings engine, delivery
zones, order hours, the kitchen display, and the local-SEO schema graph. All of
that functionality lives in the plugin, never in the theme.

= Should I use a child theme? =

Yes, we recommend it for any customisation. Create a child theme that sets Lafka
as its template and place your overrides and `functions.php` snippets there so
they survive theme updates. Simple appearance tweaks can be made without a child
theme through **Appearance -> Customize**.

= Where did the "Theme Options" panel go? =

The legacy "Appearance -> Theme Options" admin panel has been retired. All theme
appearance settings now live in the WordPress **Customizer** (Appearance ->
Customize), which shows a live preview. When you update to this version, any
settings you had saved in the old panel are copied to their Customizer homes
automatically, once, on the first page load — your storefront looks identical
before and after. Feature toggles that belong to the companion plugin (product
addons, delivery zones, order hours, the kitchen display) now live on the
plugin's own Modules screen.

= Does it work with WooCommerce? =

Yes — the theme is built around WooCommerce. WooCommerce 9.5 or newer is
recommended (tested up to 10.9). Shop, archive, product, and cart surfaces all
ship theme templates and styling.

= Is the theme translation-ready? =

Yes. The text domain is `lafka` and a `.pot` template is bundled in
`/languages`. RTL layouts and a WPML configuration (`wpml-config.xml`) are
included.

= Where is the design documentation? =

`DESIGN_SYSTEM.md` in the theme root documents the token system and is the
single visual source of truth for the theme.

== Changelog ==

This theme follows semantic versioning. The full, per-release changelog is
maintained with the source and release notes on GitHub:

https://github.com/setkernel/lafka-theme/releases

The current release is recorded in the `Version:` header of `style.css`.

== Copyright ==

Lafka WordPress theme, Copyright the Lafka contributors.
Lafka is distributed under the terms of the GNU General Public License v2 or
later. See the bundled `LICENSE` file and
https://www.gnu.org/licenses/gpl-2.0.html.

Lafka was originally developed by theAlThemist and sold on ThemeForest; it has
since been delisted and is now community-maintained as an open-source project.

The theme bundles the following third-party assets. Licenses below are verified
from the vendored files; a complete GPL-compatibility and directory-compliance
audit of every bundled asset is tracked as NX5-01 / NX5-02, and items marked
TODO are pending that audit.

Fonts (self-hosted, `assets/fonts/`):

* Rubik — SIL Open Font License 1.1 — https://github.com/googlefonts/rubik
  (`assets/fonts/rubik/`; OFL license-copy to be added — TODO NX5-01).
* Fraunces — SIL Open Font License 1.1 — Copyright 2018 The Fraunces Project
  Authors — license: `assets/fonts/fraunces/OFL.txt`.

Scripts and styles (verified from the bundled file headers):

* Font Awesome Free 6.7.2 — Icons: CC BY 4.0, Fonts: SIL OFL 1.1, Code: MIT —
  Copyright 2024 Fonticons, Inc. — https://fontawesome.com/license/free
  (`styles/font-awesome/`).
* Owl Carousel 2 v2.3.4 — MIT — Copyright 2013-2018 David Deutsch
  (`js/owl-carousel2-dist/`, `styles/owl-carousel2-dist/`).
* Magnific Popup v1.1.0 — MIT — Copyright 2016 Dmitry Semenov (`js/magnific/`).
* Isotope v3.0.6 — GPLv3 (open-source use) or Isotope Commercial License —
  Copyright 2010-2018 Metafizzy (`js/isotope/`).
* FlexSlider v2.7.2 — GPLv2 — Copyright 2012 WooThemes (`js/flex/`).
* jQuery Countdown v2.1.0 — MIT — Copyright Keith Wood (`js/count/`).
* Simple JavaScript Inheritance (`js/count/jquery.plugin.js`) — MIT —
  by John Resig.
* Cloud Zoom v1.0.2 — MIT — Copyright 2010 R. Cecco (`js/cloud-zoom/`).
* jQuery Nice Select v1.0 — MIT — by Hernan Sartorio
  (`js/jquery.nice-select.min.js`).
* jQuery Nicescroll v3.7.6 — MIT — Copyright InuYaksa (`js/jquery.nicescroll/`).
* jQuery fontIconPicker v2.0.0 — MIT — by Alessandro Benoit and Swashata
  (`js/fonticonpicker/`).
* Modernizr (custom touch-detection build) — MIT (`js/modernizr.custom.js`).
* Typed.js — MIT — by Matt Boldt (`js/typed.min.js`; bundled minified build
  carries no version/license header — verify — TODO NX5-01).
* jquery.mb.YTPlayer — Copyright 2020 Matteo Bicocchi (`js/jquery.mb.YTPlayer/`;
  bundled header does not state license terms — verify — TODO NX5-01).

Other vendored libraries not enumerated above (for example the remaining helper
scripts under `js/`) are pending the same NX5-01 GPL audit.

Screenshot: `screenshot.png` is an original composition created for this theme.
