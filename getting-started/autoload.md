---
description: Phyrus automatically imports php, css and js files for you
---

# Autoload

One of the main features in Phyrus is that it automatically imports code and assets for you, sparing you this tedious task.

There are three things that Phyrus will automatically load:

* Assets in /assets (css, scss, js) or page specific /assets
* Any PHP file in /code and sub-directories
* Pages under /pages

For example, you can create a models folder under /code (/code/models) and all PHP files there will be automatically imported, so you **don't** need to use **include** or **require**.

This doesn't mean that you are obligated to work with this. You can always delete the /code folder and place your PHP files somewhere else, same with assets or pages.

