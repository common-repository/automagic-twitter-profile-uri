=== Automagic Twitter Profile URI ===
Contributors: benjaminwittorf
Tags: Twitter, automagic, social networks, remote content
Requires at least: 2.7
Tested up to: 2.7.1
Stable tag: 1.2.2

Automagically adds a Twitter profile link to your commenters.

== Description ==
<strong>As of now, <em>the plugin currently cannot work</em> anymore.
Please see <a href="http://code.google.com/p/twitter-api/issues/detail?id=353">Twitter Issue 353</a> and second my request to bring back the required functionality by either “starring” the topic or contributing to it. Thank you very much for your help.</strong>

== Installation ==
1. Upload the `automagic-twitter-profile-uri` directory to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. It will deactivate itself then.

== Frequently Asked Questions ==
= How does it work? =
It currently does not. Please see <a href="http://code.google.com/p/twitter-api/issues/detail?id=353">Twitter Issue 353</a> and second my request to bring back the required functionality. Thank you very much for your help.

== Screenshots ==
1. This is what the activated plugin usually would look like on your blog.
2. The options page.
4. The dashboard widget.

== Version history ==
The current version is 1.2.2 (2009.04.13).

= 1.2.2 (2009.04.13) =
* ATTENTION: The plugin auto deactivates itself on updating. Please see <a href="http://code.google.com/p/twitter-api/issues/detail?id=353">Twitter Issue 353</a> and second my request to bring back the required functionality.

= 1.2.1 (2009.02.24) =
* Changed: Fixed a typo that prevented this plugin from working. Sorry for any inconvenience.

= 1.2.0 (2009.02.24) =
* New: You can now define the commenter display option checkbox default status (checked/unchecked; thanks to <a href="http://www.iphoneclub.nl/">Jean-Paul Horn</a> for the idea).
* Changed: Some internal routines to respect “function users” more.

= 1.1.2 (2009.02.22) =
* Changed: Some minor code optimizations.

= 1.1.1 (2009.02.09) =
* Changed: The plugin has a much nicer options page now.

= 1.1.0 (2009.02.09) =
* New: Added another panic option, “Drop Database Table”, which will drop and recreate the plugin database table (while preserving settings). This is especially useful if you do not only want to flush the cache but old commenters not being associated with their Twitter username (no automatic opt-in, the next time they comment they can have the magic again).

= 1.0.2 (2009.02.09) =
* Fixed: That manual opt in for existing commenters caused some trouble (making the plugin behave unexpected). Everything is fine and works as advertised now.

= 1.0.1 (2009.02.09) =
* Changed: On new installations of this plugin, _existing_ commenters won't be automatically “enchanted” anymore. Returning commenters need to specifically agree to the magic if they want their old comments to be updated with the profile link. This will be overridden when the commenter isn't shown the option to decide (thanks to <a href="http://mit140zeichen.de/" title="Mit 140 Zeichen">Nicole</a> for the idea).

= 1.0.0 (2009.02.08) =
* There's nothing new or changed in this version (it is exactly the same as 0.5.2), only with the "gold status"/"stable release" tag. 

= 0.5.2 (2009.02.06) =
* New: You can now enable SSL connections to Twitter.
* Changed: Updated localization strings.
* Changed: Default settings: Comment form option instead of notice will be shown.

= 0.5.1 (2009.02.05) =
* New: Template tag support for comment form notice text and option (see <a href="http://immersion.io/publikationen/code/wordpress/automagic-twitter-profile-uri/">documentation</a>).
* Changed: Some code optimizations.
* Changed: Comment form notice will be shown using default settings.

= 0.5.0 (2009.02.05) =
* New: You can now let your commenters decide if they want to let the “magic” happen.
* New: You can now enable CSS "clear: both" on comment form notice/option.

= 0.4.3 (2009.02.03) =
* New: You can now have a notice about the “magic” automatically displayed under your comment form (and edit the text as well; thanks to <a href="http://www.heresytoday.org">Guy Vestal</a> for the idea).

= 0.4.2 (2009.02.03) =
* Fixed: Plays nice now with <a href="http://www.scratch99.com/wordpress-plugin-keywordluv/">KeywordLuv</a> (thanks to <a href="http://www.heresytoday.org">Guy Vestal</a> for the hint).

= 0.4.1 (2009.02.02) =
* New: Option to flush the cache on/from the options page.
* Fixed: Options page now also works with SSL hosts.
* Changed: Options page base name changed to plugin base name.
* Changed: Dashboard details (removed most of the line breaks).
* Changed: Even more error handlers when trying to get data from Twitter.

= 0.4.0 (2009.02.02) =
* New: Automatic integration, no more template editing.
* New: An options page.
* New: Better / easier customization.
* Changed: Improved localization features.

= 0.3.0 (2009.02.01) =
* New: Dashboard information widget.
* Changed: Deactivating the plugin no longer flushes the cache (what a mess on upgrading).

= 0.2.5 (2009.01.31) =
* New: Support for WordPress' own plugin deletion (removes the then unused database table and options).

= 0.2.4 (2009.01.26) =
* New: Now supports caching plugins (thanks to <a href="http://ottodestruct.com">Otto</a> for the tip).
* Changed: Updated database table structure for the things to come.
* Changed: Even more internal calls are now optimized (yup, again).

= 0.2.3 (2009.01.25) =
* Fixed: Commenting was not possible (whoops).

= 0.2.2 (2009.01.25) =
* Changed: Removed an unnecessary "if" loop.
* Changed: Improved code documentation.

= 0.2.1 (2009.01.25) =
* New: Added more Twitter exception handlers.

= 0.2.0 (2009.01.24) =
* New: Now “prefetches” all Twitter usernames on displaying an entry, avoids redundancy and lots of database calls (thanks to <a href="http://ottodestruct.com">Otto</a> for the tip).
* New: Handler for Twitter “over capacity” or ”maximum requests”.
* Changed: Even more code optimizations.

= 0.1.1 (2009.01.23) =
* Fixed: Entries without a Twitter username wouldn’t be updated.
* Fixed: Localization now fully works.
* Changed: The plugin doesn’t require PHP 5.2 anymore.
* Changed: Switched from json_decode() to good old preg_match() to extract Twitter usernames (thanks to <a href="http://ottodestruct.com">Otto</a> for the tip). Better compatibility to older PHP versions!
* Changed: Switched from get_file_contents() to wp_remote_get() to retrieve data from Twitter (thanks to <a href="http://ottodestruct.com">Otto</a> for the tip). Faster and more reliable!
* Changed: General code optimization.

= 0.1.0 (2009.01.23) =
* Initial release.

== To do ==
* A button to temporarily disable pulling from Twitter (to manually handle long “failwhale” times).
