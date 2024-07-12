=== Page Preview ===
Contributors:      handyplugins, m_uysl
Tags:              page preview, preview, screenshot, page screenshot
Requires at least: 6.0
Tested up to:      6.6
Requires PHP:      7.4
Stable tag:        1.0
License:           GPLv2 or later
License URI:       http://www.gnu.org/licenses/gpl-2.0.html
Donate link:       https://handyplugins.co/donate/

Quickly see how each page looks at a glance and manage your site more efficiently.

== Description ==

Page Preview, provides a visual enhancement to your WordPress dashboard by automatically adding screenshot of your published pages directly in the post listings. This feature allows you to see a visual representation of each page without the need to individually open and review them, saving you time and simplifying your workflow.

= Features 🖼️ =

- __Automatic Screenshots:__ Automatically captures and updates screenshots of your pages whenever they are published or updated.
- __Dashboard Integration:__ Integrates smoothly into your WordPress dashboard, adding screenshots to your post listings for easy visual management.
- __Responsive Previews:__ Displays responsive screenshots that adapt to the size of your screen, ensuring a consistent viewing experience.
- __Efficient Content Management:__ Helps you quickly identify the pages by their appearance, which is particularly useful for sites with a large number of pages or frequent updates.
- __Post Type Support:__ Supports custom post types, enabling you to display screenshots for any public type of content on your site.
- __Batch Processing:__ Supports batch processing from the post listing to generate or update screenshots in bulk, enhancing your productivity.
- __CLI Support:__ Offers a CLI command feature for advanced users to manage screenshots generation via command line.
- __Easy Customization:__ Provides a range of customization options to adjust the behavior of the screenshots according to your preferences.
- __Multisite Compatibility:__ Fully compatible with WordPress Multisite, allowing you to manage screenshots across multiple sites from a single network.

"Page Preview" is the perfect tool for content creators, website administrators, and anyone who manages a WordPress site and values efficiency, automation, and responsive design. Install it today to streamline your site management and enhance your productivity.

== Privacy Policy ==

This plugin makes HTTP requests to `https://screenshot.handyplugins.co` to generate screenshots of your pages.

Our screenshot capturing service requires the URL of the public page to generate screenshots. While we do not collect personal information directly from users, we do record IP addresses and domain names from the servers initiating the requests. This data is used exclusively for rate limiting and to prevent abuse of our service, ensuring fair usage and stability.

= Contributing & Bug Report =

Bug reports and pull requests are welcome on [GitHub](https://github.com/HandyPlugins/page-preview).


== Installation ==

= Manual Installation =

1. Upload the entire `/page-preview` directory to the `/wp-content/plugins/` directory.
2. Activate Accessibility Toolkit through the 'Plugins' menu in WordPress.

== Frequently Asked Questions ==

= How does the Page Preview plugin work? =

Page Preview plugin automatically generates screenshots of your published WordPress pages and displays them in the post listings on your dashboard. It captures screenshots in various dimensions to ensure they are optimized for viewing on different devices.

=  Can I generate screenshots for all my pages at once? =

Yes, Page Preview supports batch processing which allows you to generate or update screenshots for multiple pages at once directly from your post listings.

= Is there support for command line operations? =

Yes, the plugin includes CLI support. You can manage screenshot generation through command line, which is ideal for automated workflows or advanced WordPress management. Type `wp help page-preview` in your terminal to see the available commands.

= Are the screenshots responsive? =

Yes, the screenshots are responsive. The plugin generates screenshots in multiple dimensions and uses srcset attributes, ensuring that the previews are optimized for various screen sizes, from desktops to mobile devices.

= How does this plugin improve my workflow? =

By providing visual previews directly in your post listings, Page Preview saves you time and makes it easier to manage your content. You can quickly identify pages by their appearance, making editing and updates more efficient.

= Will using this plugin slow down my website? =

No, the Page Preview plugin is designed to perform its tasks without impacting the performance of your website. Screenshots are generated asynchronously and stored efficiently to minimize any load on your server.

= Is the Page Preview plugin free? =

Yes, the Page Preview plugin is available for free. You can download and install it from the WordPress plugin directory.

= Is there a rate limit for generating screenshots? =

Yes, to ensure optimal performance and server stability, our service supports the generation of screenshots for up to 100 pages every 10 minutes. If you have a larger number of pages, we recommend spacing out the screenshot generation to stay within this limit.


== Screenshots ==

1. Page Preview column in the post listings.
2. Page Preview settings page.
3. Bulk actions for generating/deleting screenshots.
4. CLI command for managing screenshots.

== Changelog ==

= 1.0 (July 12, 2024) =
* Initial release

== Upgrade Notice ==
