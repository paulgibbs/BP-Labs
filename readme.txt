=== BP Labs ===
Contributors: DJPaul
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=P3K7Z7NHWZ5CL&lc=GB&item_name=B%2eY%2eO%2eT%2eO%2eS%20%2d%20BuddyPress%20plugins&currency_code=GBP&bn=PP%2dDon
Tags: buddypress,experimental,akismet
Requires at least: WordPress 3.1, BuddyPress 1.2.8
Tested up to: WordPress 3.2, BuddyPress 1.3
Stable tag: 1.2

BP Labs contains unofficial and experimental BuddyPress features for testing and feedback. Works best with BuddyPress 1.3.

== Description ==

BP Labs contains three unofficial BuddyPress experiments; *@mentions autosuggest*, *Quick Admin*, and *Activity Stream Spam*. All experiments are in beta, and come with no guarantees.

*@mentions autosuggest* requires the Activity Stream component, and extends its @messaging feature to help you find the short name of a user. It is integrated into comments, the "What's New" activity status box, Private Messaging (body) and bbPress forums. To trigger the autosuggest, type an `@` followed by at least one other letter.
For example, to mention to Paul Gibbs, you could type `@Paul G`, and it will show a list of users who match. You can then select one of these users, and their short name will be added to the text area (even if Paul's short name is `paulgibbs`).

*Quick Admin* requires Groups, and affects the group directory. Designed to help speed up accessing admin screens for each group, hovering over each group in the directory will reveal links to the admin screens for that group (e.g. edit details, group settings, group avatar).

*Activity Stream Spam* keeps your Activity Stream minty-fresh by integrating it with Automattic's Akismet spam filtering service, It requires the Akismet WordPress plugin to work.

Remember, these are my own unofficial experiments for BuddyPress which I am making available for testing, feedback, and to give people new shiny things for their websites.

== Installation ==

1. Place this plugin in the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= I need help, or something's not working =

For help, or to report bugs, visit the [support forum](http://buddypress.org/community/groups/bp-labs/ "support forum").

= I am using BuddyPress 1.2.9 with the BP-Default theme, and @mentions autosuggest looks strange =

This is a known issue because of how parts of BuddyPress 1.2.9's BP-Default theme are written. To solution is to upgrade to BuddyPress 1.3.

= I've created a group, and Quick Admin links aren't appearing in the directory =

At the moment, only super admins are able to view the Quick Admin links. This will be addressed in a future version.


== Screenshots ==

1. @mentions autosuggest
2. Quick Admin
3. Activity Stream Spam

== Changelog ==

= 1.2 =
* Added caching to @mentions autosuggest; it speeds up multiple requests for the same query.
* Added Activity Stream Spam experiment.

= 1.1 =
* Added options panel underneath the BuddyPress menu in WordPress admin.

= 1.0 =
* First version; with *@mentions autosuggest* and *Quick Admin*.