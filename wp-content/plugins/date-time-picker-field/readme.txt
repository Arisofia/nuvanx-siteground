=== Availability Datepicker – Booking Calendar for Contact Form 7 – Input WP ===
Contributors: inputwp
Donate link: https://www.inputwp.com
Tags: datepicker, booking, availability, calendar, contact form 7
Requires at least: 6.0
Tested up to: 6.9.1
Requires PHP: 7.4
Stable tag: 3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

**Availability datepicker** & **booking calendar** for any form. Configure **business hours**, time slots, date overrides and a **booking window**. Works with **Contact Form 7**.

== Description ==

**Availability Datepicker** by [InputWP](https://www.inputwp.com/) is a **booking calendar** and **date time picker** plugin that turns any text field into a smart **availability datepicker**. Define your business hours, booking type, and availability rules — the **calendar** enforces them automatically on the frontend. Works with **Contact Form 7** via a simple CSS selector, with no code required on your end.

= Perfect For =

- **Doctor / Medical clinic** — Fixed 30-minute appointment slots, Monday–Friday, 24-hour minimum notice, 60-day booking window.
- **Salon & Beauty** — Fixed 60-minute sessions, Monday–Saturday, 2-hour advance notice, 30-day booking window.
- **Hotel / Vacation Rental** — Day Based mode, check-in and check-out date selection, minimum 2-night / maximum 30-night stay, 365-day booking window.
- **Car Rental** — Flexible Range, pickup and return with date and time, 4-hour minimum / 7-day maximum, every day, 90-day booking window.
- **Equipment Rental** — Flexible Range, overnight to multi-day rentals, 12–72-hour duration, 60-day booking window.
- **Meeting Room** — Flexible 1–8-hour bookings, Monday–Friday, 15-minute buffers between slots, 30-day booking window.
- **And anyone** who runs appointments, reservations, or rentals and needs to show live **availability** on their booking form.

= Works with Contact Form 7 =

**Availability Datepicker** integrates with **Contact Form 7** (and any other form plugin) through a CSS selector. Add a text field to your form, copy the CSS class from the Integration panel, and paste it into the field's class setting. No shortcodes or custom code needed.

Follow the step-by-step guide for [Contact Form 7](https://www.inputwp.com/about/date-and-time-picker-field-on-contact-form-7/) or [Divi](https://www.inputwp.com/about/date-picker-in-divi-contact-form/) to connect your **date picker field** in minutes.

= Three Booking Types =

Choose the booking type that matches how your business operates:

- **Fixed Time Slots** — Guests pick a specific time slot (e.g. 9:00 AM – 10:00 AM). Ideal for appointments, consultations, and classes. Uses a single **date and time** field.
- **Day Based** — Guests pick a check-in date and a check-out date. Ideal for hotels, B&Bs, and vacation rentals. Uses two separate date fields.
- **Flexible Range** — Guests pick a start date+time and an end date+time. Ideal for car and equipment rentals, meeting rooms, and multi-hour bookings. Uses two separate date+time fields.

Six **Quick Setup Presets** let you pre-fill all availability settings for the most common scenarios in one click.

= Advanced Availability Settings =

The **availability calendar** is driven by a comprehensive set of rules you configure in the admin panel:

- **Business Hours** — Enable or disable each weekday independently. Add multiple open time ranges per day to model morning and afternoon shifts.
- **Availability Window** — Control how far ahead booking is open: dynamically (X days from today) or within a fixed predefined date range.
- **Slot Interval** — Set the gap between available time slots: 15, 30, or 60 minutes.
- **Minimum Notice** — Require a minimum lead time before a slot can be booked (e.g. 24 hours in advance).
- **Buffers** — Add preparation or cleanup time before and after each slot to prevent back-to-back bookings.
- **Min/Max Bookable Days** — Set the shortest and longest allowed stay lengths (Day Based mode).
- **Min/Max Duration** — Set the shortest and longest allowed rental or booking period (Flexible Range mode).
- **Blocked Dates** — Mark specific dates as unavailable: holidays, closures, one-off exceptions.
- **Allowed Date Exceptions** — Open a normally-closed date with custom hours (e.g. a special Saturday opening).
- **Live Admin Preview** — See how the calendar looks with your current settings before saving.

= Features =

- **Date picker** — Allow users to pick a date on the availability calendar.
- **Time picker** — Let users choose an available time alongside the date.
- **Three built-in themes** — Light, and Dark.
- **Multiple language support** — Display the datepicker interface in 40+ languages.
- **Date formats** — Choose from 15+ date format options (d/m/Y, Y-m-d, M j Y, and more).
- **Time format** — 12-hour (AM/PM) or 24-hour display.
- **Timezone** — Configure the timezone your availability rules are based on.
- **Inline display** — Keep the calendar always visible on the page instead of opening as a dropdown.
- **Quick Setup Presets** — Six pre-configured templates (Doctor, Salon, Hotel, Car Rental, Equipment Rental, Meeting Room) to get started in seconds.

= Upgrade to PRO =

PRO unlocks (as shown on the Support page in the admin):

- **Bookings** — Capture, manage, and block slots automatically so dates are disabled once booked.
- **Multiple Resources** — Custom availability rules per resource or form field.
- **Branding & dynamic styling** — Full control over calendar colors, fonts, and labels.
- **Import from .ics** — Sync Google Calendar, Outlook, and other calendar services to block busy dates.
- **Divi & WooCommerce integration** — Native integration with Divi and WooCommerce.

Try the [PRO version](https://www.inputwp.com) today. Have a feature request? [Let us know](https://www.inputwp.com/support/).

== Frequently Asked Questions ==

= Does the plugin record bookings or prevent the same slot from being selected twice? =

No. The plugin controls which dates and times appear as selectable in the datepicker. It does not store form submissions or automatically remove a time slot after someone books it — your form plugin (Contact Form 7, etc.) handles submissions independently. To block a date after it has been taken, add it manually to the Blocked Dates list. For automated booking management and double-booking prevention, a dedicated booking plugin is needed alongside this one.

= Do I need two separate form fields for check-in / check-out or start / end time? =

Yes, for Day Based and Flexible Range booking types. Each requires two text fields in your form (one for the start, one for the end), each configured with its own CSS selector in the Integration panel. Fixed Time Slots uses a single date+time field.

= Can different forms on the same site have different availability rules? =

The free version applies one set of availability rules to all forms site-wide. If you need different rules for different forms or fields — for example, two services with different business hours — independent rule sets per field are available in the PRO version.

= How do I connect the datepicker to my Contact Form 7 field? =

Add a plain text input to your CF7 form, then copy its CSS class or ID into the CSS Selector field in the Integration panel. The full step-by-step guide is available at [inputwp.com](https://www.inputwp.com/about/date-and-time-picker-field-on-contact-form-7/). For other form builders, the same approach applies — use the field's CSS class or ID as the selector.

= The datepicker is not appearing on my page — what should I check? =

Two things to check first: (1) the CSS selector in the Integration panel must exactly match the field's CSS class or ID — a single character difference will prevent the datepicker from attaching; (2) open the browser console for JavaScript errors that may indicate a conflict with another plugin or theme. Still stuck? Visit the [support forum](https://wordpress.org/support/plugin/date-time-picker-field/).

= Can I automatically sync Google Calendar or an external calendar to block booked dates? =

ICS calendar sync — the ability to connect a `.ics` URL from Google Calendar, Outlook, Airbnb, Booking.com, or any other calendar service to automatically disable dates that are already taken — is available in the [PRO version](https://www.inputwp.com).

= I was using v2.x — will my settings carry over? =

Settings are automatically migrated to the v3.0 format on first activation. As with any major upgrade, backing up your site beforehand is always recommended.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/date-time-picker-field` directory, or install the plugin directly through the WordPress plugins screen.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Go to **Availability → Availability** and configure your booking type, business hours, and availability rules.
4. Go to **Availability → Integration**, copy the CSS selector, and add it to the corresponding text field in your form.

== Screenshots ==

1. Availability — Configure booking type, business hours, and availability window.
2. Quick Setup Presets — Pre-fill all settings for common booking scenarios in one click.
3. Advanced time settings — Slot interval, minimum notice, buffers, and min/max duration.
4. Integration — Copy the CSS selector and paste it into your form field.
5. Settings — Configure date format, time format, timezone, and datepicker library options.

== Changelog ==

= v3.0 - 27 February 2026 =
- New: Three booking types — Fixed Time Slots, Day Based, Flexible Range
- New: Six Quick Setup Presets (Doctor/Medical, Salon & Beauty, Hotel/Vacation Rental, Car Rental, Equipment Rental, Meeting Room)
- New: Availability Window — dynamic or predefined date range
- New: Date Overrides — block specific dates or add exceptions with custom hours
- New: Advanced time settings — minimum notice, slot buffers, min/max stay or duration
- Enhancement: Rebuilt admin UI with live calendar preview
- Enhancement: Settings API architecture (no custom database tables)
- Enhancement: Automatic migration of settings from v2.x
- Requires: WordPress 6.0+, PHP 7.4+
- Tested: Compatibility with WordPress 6.9.1

= v2.3 - September 7, 2023 =
- Fix Freemius vulnerability by updating to v2.5.10
- Tested: Compatibility with WordPress v6.3.1
- Tested: Compatibility with Contact Form 7 v5.8
- Tested: Compatibility with Divi v4.22.1

= v2.2 - April 8, 2022 =
- Fix Freemius vulnerability by updating to 2.4.3

= v2.1 - August 13, 2021 =
- Fix: Divi Integration.
- Fix: Contact Form 7 Missing dependencies.
- Fix: Dropdown not showing on Modal.
- Tested: Compatibility with WordPress 5.8.

= v2.0 - July 16, 2021 =
- Enhancement: Option to select the date picker type.
- Enhancement: Backend UI Fixes.
- Enhancement: Separated the Form integration from the date picker definition.
- Enhancement: Migration engine that connects with PRO.
- Fix: WordPress version compatibility.
- Fix: Default time in datepicker is not coming correct as per the Hour Format selected in datepicker plugin.
- Fix: JS bugs on compatibility with Contact Form 7.

= v1.9.2 - March 3, 2021 =
* Fix: PHP version compatibility.

= v1.9.1 - March 3, 2021 =
* Fix: Turning off the Disable past dates.
* Fix: The path for the Settings link from the plugins list, redirected to the new location.
* Fix: Minimum Date and Maximum Date to work without "Disable Past Dates" activated.
* Fix: Wordpress version dependent jQuery library added.
* Fix: Bug that wouldn't allow selecting the year.

= v1.9 - February 24, 2021 =
* Enhancement: User experience and layout. Better grouping of the settings.
* Enhancement: Tested up to 5.6.2
* Enhancement: The location in WordPress dashboard was moved out of WordPress Settings into InputWP page.
* Enhancement: Branding, name, title.
* Fix: Fatal error showing on install.

= v.1.8 =
* Enhancement: New date formats added
* Fix: UTC issue fixed
* Enhancement: Offset and min_date improvements

= v.1.7.9.4 =
* Enhancement: Display inline option

= v.1.7.9.3 =
* Fix: Undefined index error fix

= v.1.7.9.2 =
* Fix: Dirname() error fix (min.req PHP7)

= v.1.7.9.1 =
* Fix: Time scroll fix
* Enhancement: Load custom version of jquery.datetimepicker plugin

= v.1.7.9 =
* Enhancement: Add minimum date option
* Enhancement: Set field type to text
* Fix: Mousewheel issue

= v.1.7.8.2 =
* Enhancement: Default settings improvement

= v.1.7.8.1 =
* Enhancement: Refractor code
* Enhancement: Language Improvements

= v.1.7.7 =
* Enhancement: Option to set maximum date
* Enhancement: Option to detect language automatically

= v.1.7.6 =
* Enhancement: Option to disable specific dates
* Enhancement: Improved time handling - it will now consider the site timezone

= v.1.7.5 =
* Enhancement: Improved default time value
* Enhancement: New option to set time offset for current day

= v.1.7.4.1 =
* Fix: Get_plugin_data() function

= v.1.7.4 =
* Enhancement: Language files
* Enhancement: Add version to loaded scripts and styles
* Enhancement: Remove unused files
* Enhancement: AM/PM hour format bug fix

= v.1.7.3 =
* Fix: Data format issue in some languages
* Enhancement: Removed moment library in favour of custom formatter

= v.1.7.2 =
* Fixed: IE11 issue

= v.1.7.1 =
* Enhancement: Added advanced options to better control time options for individual days

= v.1.6 =
* Enhancement: Start of the week now follows general settings option
* Enhancement: Added new Day.Month.Year format

= v.1.5 =
* Enhancement: Option to add minimum and maximum time entries
* Enhancement: Option to disable past dates

= v.1.4 =
* Enhancement: Option to add datetime field also in admin

= v.1.3 =
* Fix: Solved PHP missing file

= v.1.2.2 =
* Enhancement: Included option to prevent keyboard edit

= 1.2.1 =
* Enhancement: Added option to keep original placeholder

= 1.2 =
* Fix: Solved bug with date and hour format

= 1.1 =
* Enhancement: Added direct link to settings in plugins page
* Enhancement: Improved options handling

= 1.0 =
* Initial Release

== Credits ==
* [xdsoft.net datetimepicker jQuery plugin](https://xdsoft.net/jqplugins/datetimepicker/)
