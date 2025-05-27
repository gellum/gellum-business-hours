=== Gellum Business Hours for WooCommerce ===
Contributors: gellum, venywest
Tags: woocommerce, business hours, opening hours, ecommerce
Donate link: https://gellum.com/opensource
Requires at least: 6.2
Tested up to: 6.8
Stable tag: 1.3.6
Requires PHP: 7.2
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
WC requires at least: 7.8
WC tested up to: 9.8

Manage your WooCommerce store's business hours. Disable checkout and display notices when the store is closed, indicating the next opening time.

== Description ==

Gellum Business Hours allows WooCommerce store owners to easily define their operating schedule for each day of the week. When the store is outside of these hours, the plugin can:

* Disable checkout functionality, preventing orders from being placed.
* Display a customizable notice (using WooCommerce's native system) informing customers that the store is currently closed.
* Inform customers of the next available date and time the store will be open.
* Shortcode that allows you to easily add a store status notice to any page. [gellum_business_hours]

Features:
* Set opening and closing times for each day of the week.
* Time selection in 15-minute intervals using a 24-hour format.
* Enable or disable specific days entirely.
* User-friendly settings page integrated into the WooCommerce menu.
* Automatic detection of store status based on current WordPress timezone settings.
* Clear notifications for customers regarding store status and next opening time.
* HPOS (High-Performance Order Storage) compatible.
* Customizable admin interface with "Readex Pro" Google Font for a modern look.
* Shortcode `[gellum_business_hours]` to display current store status on your website's frontend.

== Installation ==

1.  Upload the `gellum-business-hours` folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Navigate to **WooCommerce > Business Hours** in your WordPress admin panel to configure the settings.
    * For each day, set the status (Open/Closed).
    * If "Open", select the opening and closing times.
4.  Save changes. The plugin will now manage your store's availability based on your schedule.

== Frequently Asked Questions ==

= Does this plugin support timezones? =

Yes, Gellum Business Hours uses the timezone configured in your WordPress settings (Settings > General) to accurately determine if your store is open or closed.

= Can I set different hours for different days? =

Yes, you can configure unique opening and closing times for each day of the week (Monday through Sunday), or mark specific days as closed.

= What happens when the store is closed? =

When the store is determined to be closed based on your configured hours:
* WooCommerce notices will inform customers on cart and checkout pages that the store is closed and when it will reopen.
* Checkout buttons will be disabled or hidden, preventing new orders.

= How are overnight hours handled? =

The plugin supports overnight schedules (e.g., opening Monday 10:00 PM and closing Tuesday 2:00 AM). The logic correctly identifies these跨天 schedules.

= How can I display the store status on my website? =

You can use the shortcode `[gellum_business_hours]` in any page, post, or widget to display whether your store is currently "Open" or "Closed", along with relevant timing information (like "Closing in XX minutes" or next opening time). The output is styled with CSS classes `.gellum-business-hours-open` and `.gellum-business-hours-closed`.

== Screenshots ==

1.  **Settings Page:** The main configuration screen showing the weekly schedule in a table format with status, opening, and closing time dropdowns.

== Changelog ==

= 1.3.6 (May 22, 2025) =
* Minor fixes

= 1.3.5 (May 21, 2025) =
* The plugin checks if WordPress has a compatible, installed, and active version of WooCommerce.
* Minor changes in CSS
* Github post https://github.com/gellum/Gellum-Business-Hours

= 1.3.4 (May 20, 2025) =
* New shortcode `[gellum_business_hours]`. The previous shortcode is no longer available.
* CSS update, removing `!important;`. For styling the shortcode output with classes `.gellum-business-hours-open` and `.gellum-business-hours-closed`.


= 1.3.3 (May 20, 2025) =
* Feature: Added new shortcode `[gellum_store_status]` to display the current store status (Open/Closed) on the frontend.
* Feature: Shortcode displays "Open", "Closing in XX minutes" (for same-day closing within 60 minutes), or "Closed. Next opening: [Time/Day]".
* Feature: Implemented new public CSS file (`assets/css/public-styles.css`) for styling the shortcode output with classes `.gellum-business-hours-open` and `.gellum-business-hours-closed`.
* Improvement: Enqueued public CSS styles on the frontend via `wp_enqueue_scripts`.

= 1.3.2 (May 18, 2025) =
* Fix: domain path languages

= 1.3.1 (May 18, 2025) =
* Fix: Resolved a potential critical error reported with version 1.3.0 by ensuring clean removal of old code and reviewing recent logic changes.
* Improvement: Added `if (!defined(...))` checks for constants for better robustness.
* Improvement: Further clarified and simplified the logic in `is_store_open()` for handling overnight schedules to enhance accuracy and prevent edge-case issues.
* Tweak: Added a specific CSS class `gellum-time-select` to time dropdowns for more granular styling potential.

= 1.3.0 =
* Feature: Implemented a separate external CSS file (`assets/css/admin-styles.css`) for the admin settings page.
* Feature: Enqueued "Readex Pro" Google Font to style the admin settings page for a more modern look, matching user-provided design.
* Improvement: Refined HTML structure in the settings page with more specific CSS classes for precise styling via the external stylesheet.
* Internal: Replaced inline CSS output via `admin_head` with the standard `admin_enqueue_scripts` method for styles and fonts.
* Internal: Added `GELLUM_BUSINESS_HOURS_PLUGIN_URI` constant.

= 1.2.0 =
* Feature: Redesigned the admin settings page to use a table layout (Day, Status, Opening, Closing) for a more compact and user-friendly interface, based on user-provided design.
* Feature: Changed the "Status" control from a checkbox to an "Open/Closed" dropdown menu for each day.
* Improvement: Added basic inline CSS for the new table layout and visual status indicators (colored dots) for immediate feedback on a day's status.
* Internal: Removed the old `render_checkbox_field` function and adapted `render_time_select_field` to return HTML (renamed to `render_time_select_field_html`).
* Internal: Simplified `register_settings` function as fields are now manually rendered.

= 1.1.0 =
* Branding: Plugin name changed from "Gellum Horarios" to "Gellum Business Hours".
* Branding: Admin menu title within WooCommerce changed to "Business Hours" for brevity, while the main settings page panel title remains "Gellum Business Hours Settings".
* Localization: All user-facing strings in the admin area and frontend notices fully translated to English.
* Internal: Text domain updated to `gellum-business-hours`.
* Internal: Plugin slug and option names updated to reflect the new plugin name (e.g., `gellum-business-hours_settings`). Settings from previous versions (if any) would require reconfiguration.

= 1.0.1 (Conceptual - HPOS Compatibility and Initial Fixes) =
* Compatibility: Added explicit compatibility declaration for WooCommerce High-Performance Order Storage (HPOS / Custom Order Tables), resolving a warning message.
* Fix: Improved robustness of the `is_store_open()` logic, especially for overnight schedules.
* Fix: Enhanced clarity of the "next opening time" message.
* Improvement: Refined how checkout buttons are disabled/hidden when the store is closed.

= 1.0.0 (Initial Release - as "Gellum Horarios") =
* Feature: Core functionality to set store opening and closing times per day (Monday-Sunday).
* Feature: Time selection in 15-minute intervals (24-hour format).
* Feature: Ability to disable checkout functionality when the store is closed.
* Feature: Display WooCommerce notices to customers when the store is closed, indicating the next opening date and time.
* Feature: Admin settings page under the WooCommerce menu for schedule configuration.
* Language: Spanish (primary language for UI and messages).

== Upgrade Notice ==

= 1.3.4 =
This version includes a change to the shortcode if you're using an older version. (From [gellum_store_status] to [gellum_business_hours]).

= 1.1.0 =
This version includes a name change for the plugin (from Gellum Horarios to Gellum Business Hours) and updates to internal option names. If you were using a previous version, your settings will need to be reconfigured under WooCommerce > Business Hours.
