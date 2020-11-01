=== Recomendo ===
Contributors: recomendo
Tags: artificial intelligence, machine learning, related posts, recommendations, personalization
Requires at least: 4.7
Tested up to: 4.9
Requires PHP: 5.2.4
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Make your website smart with Artificial Intelligence and engage your users with personalized recommendations.

== Description ==

[Recomendo](https://www.recomendo.ai "Recomendo") helps you increase the engagement of your website visitors by showing them the content they like. We do this by using State-of-the-art machine learning algorithms that run in *our* secure cloud infrastructure, so Recomendo will never slow down your site. Plus, Recomendo works with any post, page or custom post type, and is also WooCommerce and WPML multilingual compatible.

Recomendo is a very easy to use plugin, and requires almost no setup. You simply activate the plugin with the authorization code you receive via email. To request a free trial, go to the [Recomendo](https://www.recomendo.ai "Recomendo") website.

After the plugin has been activated, drag a widget into your theme to start recommending content. Recomendo does the rest! You can also write a shortcode in a any page where you want to show the recommendations.

And if you have WooCommerce activated, Recomendo can also show its recommendations in the related products area of Product pages, and items "usually bought together" in the Cart page.

Recomendo makes uses of standard WordPress template partials to fully integrate into your themes appearance, or call the PHP API directly to unleash ultimate control.

Recomendo offers 1 month free trial with all its subscription plans. You can learn more about our features and plans at the [Recomendo](https://www.recomendo.ai "Recomendo") website.

= Features =

* Easy to use - Recomendo is very easy to use just drag the widget to a sidebar and start getting personalized recommendations in your website.

* Uses your theme's template partials to fully integrate into your site with the same appearance.

* Personalized Recommendations - Show the right content to your visitors using State-of-the-art Machine Learning algorithms to significantly boost sales and average order value.

* Similar Item Recommendations - Recomendo can also show “people who liked this also like these” recommendations looking at the pages your visitors have been browsing and recommending similar ones to the ones they have already shown an interest in.

* Complimentary Purchase Recommendations - Recomendo helps you increase average order value by displaying “items frequently bought together” in your WooCommerce cart. Recomendo makes buying multiple items at once easy by automatically recommending a product together with its accessories or products that are used for a similar purpose.

* Trending Items -  Trending products are calculated from the different interactions of your users with your products and can be shown on any page. This powerful recommendation algorithm works great for all sites and can have an enormous impact on your bottom line, as Pareto’s rule in marketing states that on average 80% of your sales will come from 20% of your products.

* Custom Post Types - If you use custom post types, you can choose which one to show recommendations on.

* Sidebar widget - Drag the recomendo widget into your theme start showing personalized content

* Shortcodes - Easy integrates into any page with shortcodes.

* Developer friendly - Customize the looks via template partials and for ultimate control directly call the PHP api to get the relevant post ids to show to the users.

== Installation ==

1. Upload 'recomendo' to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to Settings > Recomendo and authorize the plugin with authorization code you received via email.
4. Select the Post type you want to recommend.
5. (Optional) If WooCommerce is activated - Select where to show Recomendo.
6. Navigate to Appearance > Widgets - and drag the Recomendo widget into your theme to start getting recommendations.


== Frequently Asked Questions ==

= Does it work with any Custom Post Type? =

Yes, it does. From the Recomendo settings page, you can select the type of post you want to recommend.

= Does it work with WooCommerce? =

Yes, Recomendo is compatible with WooCommerce. Recomendo displays its up-sale recommendations on Product pages and cross-sale recommendations on the cart page.

= Does it work with WPML? =

Yes, Recomendo is compatible with WMPL and works great!

= Can I use shortcodes? =

Yes, you can include recomendo into any page by using the included shortcode:

[recomendo] - Will display display the recommendations.

For example:

[recomendo number=5 type="personalized" template="content-product"]

Displays 5 personalized recommendations using the template part "content-product.php" from your theme.

[recomendo number=16 type="similar" template="content-product"]

Displays 16 similar item recommendations (on single pages) using the template part "content-product.php"

[recomendo number=4 type="complementary" template="content-product"]

Displays 4 complementary purchase recommendations to the items in the WooCommerce cart using the template part "content-product.php"

[recomendo number=12 type="trending" template="content-product"] using the template part "content-product.php"

Displays 12 trending products recommendations.


= How can I further customize the look? =

Recomendo uses template partials to directly get the look of your theme and integrate seamlessly into your site. If you need to further customize the appearance, you can create your own template partial in your child-theme.

= Is Recomendo Free? =

Recomendo offers a one month free trial with all its subscription plans so you can evaluate us without any risks.

= Why does Recomendo Charge? =

Recomendo is a premium service that uses state-of-the-art Artificial Intelligence technology and high performance computing servers to deliver the best recommendations.  Unlike other plugins, we don’t sell any of your information or show ads.

= What data does Recomendo collect? =

Recomendo needs to know how your visitors browse your site. We collect the user ID for registered users, and identify unregistered users via Cookies. We also collect the user's user-agent and IP address for geo-location and to detect robots that crawl your site. No other personal information is gathered from your visitors. Recomendo also needs to know the IDs of the posts to recommend and other metadata like categories, tags, date published, description and title. For more information please read our privacy policy [here](https://www.recomendo.ai/privacy-policy "here")



== Screenshots ==

1. Here's how the Recomendo sidebar widget looks.
2. Here's how Recomendo integrates into WooCommerce related products area.
3. Recomendo showing complementary purchase recommendations in the Cart.
4. Here's what the interface for configuring Recomendo looks like.
5. Here's how you drop the  Recomendo widget to the sidebar.



== Changelog ==
= 1.0.4 =
* WooCommerce 3.5 tested

* Better robots detection

* Bug fixes

= 1.0.3 =
* Bug fixes

= 1.0.2 =
* Bug fixes

= 1.0.1 =
* Bug fixes

= 1.0.0 =
* Option for excluding older items

* Control the relevance of on-sale and featured products

* Control the relevance of tags and categories in similar items recommendations

* Exclude out-of-stock products

* Bug fixes and optimizations

= 0.9.8 =
* JWT API authentication

* Display recommendations using template parts

* Data copied on the background after activating the Recomendo subscription

* New branding and logo.

* Bug fixes and optimizations

= 0.9.7 =
* GDPR features. Data Explorer with search, delete and export

* Users can omit to being tracked via the user settings.

* Customizable widget. Now you can choose post metas and the titles to display

* Dashboard shows Top 10 viewed items in the last 7 days

= 0.9.6.2 =
* Bug fixes and optimizations

= 0.9.6.1 =
* bug fixes

= 0.9.6 =
* Fixed bug while copying all data. Now optimized for servers with low memory_limit

* Avoids recording events for robots and crawlers

= 0.9.5 =
* Added a progress bar to the copy data process

* Recomendo Widget now can display personalized recommendations together with trending items, similar items and complementary items

* Added the Recomendo Dashboard to the WordPress welcome page

* Bug fixes

= 0.9.4 =
* New admin menu and screen

* System status now alerts of possible problems and server status

* Improved error handling

* Bug fixes

= 0.9.3 =
* Error messages to log file

* API calls through Recomendo_Client via WP_http

* Removed third-party libraries and dependencies (GuzzleHttp)

= 0.9.2 =
* Rerouted communications to Recomendo servers so that required tcp ports are present in all WP hosting providers

* Added screen output to the Copy data process

* Removed the Delete data tab to prevent silly mistakes

= 0.9.1 =
* Added WPML multilingual support.

* Fixed bug when re-activating the plugin with WooCommerce.

* Shortcodes now display user-based or item-based recommendations depending on what page it's being shown.

* Widget also displays user-based or item-based recommendations automatically depending where it is shown.

= 0.9 =
* Initial Version.
