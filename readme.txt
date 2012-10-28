=== About ===
name: Download Reports
website: https://github.com/rjmackay/Ushahidi-plugin-downloadreports
description: Allows all users to download reports by category in CSV/KML. Based on work by Marco Gnazzo, George Chamales and David Kobia.
version: 0.4
requires: 2.2
tested up to: 2.6
author:  Robbie Mackay.

== Description ==
It adds the link "Download Reports" in the home page top bar, allowing all users to download reports by category in CSV/KML. 

== Installation ==
1. Copy the entire /downloadreports/ directory into your /plugins/ directory.
2. Activate the plugin.

== Changelog ==
* 0.4
 * Fixes to make plugin work with Ushahidi 2.4, 2.5, 2.6
 * Fix KML output to escape user data properly
 * Add more fields to KML
 * UTF8 fixes

* 0.3
 * Show categories with heirarchy
 * Query subcategories when parent selected
 * Remove duplicates when reports in multiple categories
