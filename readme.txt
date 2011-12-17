=== BP Labs ===
Contributors: DJPaul
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=P3K7Z7NHWZ5CL&lc=GB&item_name=B%2eY%2eO%2eT%2eO%2eS%20%2d%20BuddyPress%20plugins&currency_code=GBP&bn=PP%2dDon
Tags: buddypress,experimental
Requires at least: WordPress 3.3, BuddyPress 1.6
Tested up to: WordPress 3.3, BuddyPress 1.6
Stable tag: 1.3

BP Labs contains unofficial and experimental BuddyPress features for testing and feedback. Requires BuddyPress 1.5+.

== Description ==

BP Labs contains three unofficial BuddyPress experiments; *@mentions autosuggest* and *Quick Admin*. All experiments are in beta, and come with no guarantees.

*@mentions autosuggest* requires the Activity Stream component, and extends its @messaging feature to help you find the short name of a user. It is integrated into comments, the "What's New" activity status box, Private Messaging (body) and bbPress forums. To trigger the autosuggest, type an `@` followed by at least one other letter.
For example, to mention to Paul Gibbs, you could type `@Paul G`, and it will show a list of users who match. You can then select one of these users, and their short name will be added to the text area (even if Paul's short name is `paulgibbs`).

*Quick Admin* requires Groups, and affects the group directory. Designed to help speed up accessing admin screens for each group, hovering over each group in the directory will reveal links to the admin screens for that group (e.g. edit details, group settings, group avatar).

Remember, these are my own unofficial experiments for BuddyPress which I am making available for testing, feedback, and to give people new shiny things for their websites.

== Installation ==

1. Place this plugin in the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= I need help, or something's not working =

For help, or to report bugs, visit the [support forum](http://buddypress.org/community/groups/bp-labs/ "support forum").

= I've created a group, and Quick Admin links aren't appearing in the directory =

At the moment, only super admins are able to view the Quick Admin links. This will be addressed in a future version.

= What happened to the Akismet anti-spam experiment? =

It got merged into BuddyPress 1.6! Woohoo!

== Screenshots ==

1. @mentions autosuggest
2. Quick Admin

== Changelog ==

= 1.3 =
* Akismet support removed as that got merged into BuddyPress 1.6! Woohoo!
* Add Like Button experiments (requires BuddyPress 1.6+ & WordPress 3.3+).

= 1.2.2 =
* BuddyPress 1.5 compatibility.

= 1.2-beta-1 =
* Added caching to @mentions autosuggest; it speeds up multiple requests for the same query.
* Added Activity Stream Spam experiment (requires BuddyPress 1.5).

= 1.1 =
* Added options panel underneath the BuddyPress menu in WordPress admin.

= 1.0 =
* First version; with *@mentions autosuggest* and *Quick Admin*.