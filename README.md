# QRAS-Avedsoft
Contributors: ss1919

Tags: QR Code, QRAS-Avedsoft, QR Code Generator

Requires at least: 5.5

Tested up to: 6.5.2

Requires PHP: 7.4

Stable tag: 1.1.0

License: GPLv3 or later

License URI: <a href="http://www.gnu.org/licenses/gpl-3.0.html"> http://www.gnu.org/licenses/gpl-3.0.html </a>

== Description ==
Plugin for generating QR codes for custom post types in WordPress, with access to posts only through a QR code.

== Terms of Use ==
This plugin uses a third-party service, [goqr.me](http://goqr.me/api/), to generate QR codes. It is important to understand how this service handles data and under what circumstances.

### Details:

- **Third-Party Service:** This plugin relies on the goqr.me API to generate QR codes.
- **Data Handling:** According to the [goqr.me terms of service](http://goqr.me/api/doc/create-qr-code/#general_tos), the service does not store any data at any point. This means that your data is not saved or recorded by goqr.me.
- **Legal Considerations:** By using this plugin, you agree to the terms and conditions set forth by goqr.me. Ensure you are familiar with their [terms of service](http://goqr.me/api/doc/create-qr-code/#general_tos).

### Code References:

The following code sections reference the goqr.me API:

- **File:** `qras-avedsoft/includes/class-qras-avedsoft.php`
    - **Line 274:** `$qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($qr_code_url);`
    - **Line 276:** `wp_remote_get($qr_url);`

By using this plugin, you acknowledge and accept these terms and conditions.

== Installation ==

= From your WordPress dashboard =

1. Visit 'Plugins > Add New'
2. Search for `QRAS-Avedsoft`
3. Activate `QRAS-Avedsoft` from your Plugins page.

= From WordPress.org =

1. Download QRAS-Avedsoft.
2. Upload the 'qras-avedsoft' directory to your '/wp-content/plugins/' directory, using your favorite method (ftp, sftp, scp, etc...)
3. Activate QRAS-Avedsoft from your Plugins page.


== Changelog ==

= 1.0 =

*Added plugin

= 1.1.0 =
*Update plugin README and License
