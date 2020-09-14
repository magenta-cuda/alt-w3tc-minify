# Update of August 2020

In August 2020 W3TC released a new version 0.14.4. With respect to JavaScript
minification my analysis is still valid. The problems found in the analysis have not
been fixed.

## The YUI Compressor does not always work.

After unsuccessfully trying to get JavaScript minification using the "YUI Compressor" to work
I read the source code and found a very serious coding problem.  Errors from the "YUI Compressor"
are not handled correctly. This prevents the server from sending a valid response for a request
for a minified JavaScript file. This means that the "YUI Compressor" feature of W3TC (version
0.11.0) was never rigorously tested as it would have been very obvious that it does not work
on some very common JavaScript files. There is something seriously wrong with quality assurance
at W3TC. I don't think they are doing any automated testing. For an application of this size that
has to be an absolute necessity.

# Update of July 2019

I original wrote this plugin to set the vector of JavaScript files in W3TC's
"manual minify" mode. However, I think the design of W3TC 0.9.7.5 “manual minify”
mode prevents W3TC 0.9.7.5 JavaScript minification in “manual minify” mode from
being successful except under some quite restrictive conditions which will not be
true for some modern WordPress web pages. After analyzing W3TC 0.9.7.5's JavaScript
minification in "auto minify" mode I have found a workaround for the problem of
W3TC emitting multiple minified batch files instead of just one. The workaround just
also batches the inline scripts which W3TC does not do - forcing W3TC to stop
batching and emit the minified file of the current batched file everytime it 
encounters a inline JavaScript element in order to preserve the script order.

To replace W3TC's "auto mode" JavaScript minifier with this plugin's JavaScript minifier
just click on the "Auto Minify:Xxx" link in this plugin's entry in the "Installed Plugins"
admin page. This link toggles the plugin's minifier "Off" and "On".

![Screenshot](https://raw.githubusercontent.com/magenta-cuda/alt-w3tc-minify/master/assets/plugin_entry_screenshot.png)

This plugin in "auto minify" mode assumes that only the WordPress API, i.e., only the following functions are used to embed scripts into the HTML document.

    wp_enqueue_script()
    wp_add_inline_script()
    wp_script_add_data()
    wp_localize_script()
    wp_set_script_translations()

**Using ad hoc methods to embed scripts into the HTML document may invalidate assumptions made by this plugin and cause this plugin to malfunction.**

# The original README

The W3 Total Cache auto minify mode does not work on my web site. The problem
is the order of the JavaScript files using the auto minify mode is different
from the order without minification. This results in undefined JavaScript
function errors. W3TC has a help tool for manually setting the order of
JavaScript files. I think this tool is tiresome to use. I just want to have
the same order as without minification. So, I wrote this plugin to do this.

There is a significant restriction when using manual minify mode. W3TC in
manual minify mode cannot handle templates loaded using the 'template_include'
filter. I have a [topic](https://wordpress.org/support/topic/problem-in-manual-minify-mode-when-a-plugin-uses-the-filter-template_include/) in the WordPress support forum on this problem.
I find the response very confusing. This problem really does exists and can be
verified by reading W3TC's source code. W3TC needs to know the template that
WordPress will use. The W3TC function Minify_Plugin::get_template() in the file
"Minify_Plugin.php" is used to do this and is implemented by duplicating
the code in WordPress's wp-includes/template-loader.php. Unfortunately it doesn't
apply the filter 'template_include' causing it to return the wrong theme if this
filter overrides the theme selected by the code in wp-includes/template-loader.php.

In May 2020 I again checked the code of Minify_Plugin::get_template() for version 0.13.3
of W3TC against the code of template-loader.php for version 5.4.1 of WordPress.
Minify_Plugin::get_template() is now even more out of sync. The correct implementation of
Minify_Plugin::get_template() is dependent on it being a faithful duplicate of the
code in WordPress's wp-includes/template-loader.php. This requires W3TC to manual
update it everytime WordPress's wp-includes/template-loader.php is updated and this
is not being done.

# My Analysis of W3TC JavaScript Minification

## auto mode (W3TC 0.9.7.5 to W3TC 0.14.4)

WT3C 0.14.4 in "Auto Minify" mode does not batch the "localize",
"translation", "before" and "after" inline <script> elements. Rather it stops
batching when it encounters a "localize", "translation", "before" or "after"
inline <script> element" and flushes the current batch file, emits the inline
script element and starts a new batch file. This results in multiple batch
files instead of one.

Also WT3C 0.14.4 in "Auto Minify" mode ignores "conditional" scripts, i.e.,
scripts embedded in HTML comments, e.g., `"<!--[if lt IE 9]>\n<script>...</script><![endif]-->\n"`.
These are emitted in their original location but the minified combined scripts may be relocated to
the location of the first `<script>` element. This may change the relative order of executions of these scripts.

    <script>/* some "localize", "translation" or "before" script */</script>
    <script src="http://localhost/wp-content/cache/minify/0ae95.js"></script>
    <script>/* some "localize", "translation" or "before" script */</script>
    <script src="http://localhost/wp-content/cache/minify/dc06c.js"></script>
    <script>/* some "localize", "translation" or "before" script */</script>
    <script src="http://localhost/wp-content/cache/minify/63a69.js"></script>
    <script>/* some "localize", "translation" or "before" script */</script>
    <script src="http://localhost/wp-content/cache/minify/b4041.js"></script>
    <script>/* some "localize", "translation" or "before" script */</script>
    <script src="http://localhost/wp-content/cache/minify/ab379.js"></script>
    <!--[if lt IE 9]>"
    <script>/* some "localize" script */</script>
    <![endif]-->
    <!--[if lt IE 9]>
    <script>/* some "translation" or "before" script */</script>
    <script src="http://localhost/wp-content/plugins/.../some-javascript-file.js"></script>
    <script>/* some "after" script */</script>
    <![endif]-->

If you are interested in verifying the above for yourself you can find the
implementation of W3TC 0.14.4 JavaScript minification in "auto mode" in the
class Minify_AutoJs in the file "Minify_AutoJs.php".

## manual mode (W3TC 0.9.7.5)

In my opinion WT3C 0.9.7.5 in "Manual Minify" mode has a significant design flaw.
WordPress may decorate the <script> tag of the JavaScript file by prepending 
"localize", "translation", "before" inline <script> elements and appending a
"after" inline <script> element. It may also bracket the script tag with a 
conditional HTML comment.

    <!--[if lt IE 9]>
    <script type='text/javascript'>/* localize script */</script>
    <![endif]-->
    <!--[if lt IE 9]>
    <script type='text/javascript'>/* translation script */</script>
    <script type='text/javascript'>/* before script */</script>
    <script type='text/javascript' src='http://url/of/the/file.js'></script><!-- THIS IS THE TAG OF THE ACTUAL JAVASCRIPT FILE -->
    <script type='text/javascript'>/* after script */</script>
    <![endif]-->

In "Manual Minify" mode the "localize", "translation", "before" and "after"
scripts are emitted in their normal location. But the <script> tag of the
JavaScript file is not emitted but instead the code of the JavaScript file
is appended to a batch file which is emitted in another location. This means
that the order of the execution of the code in the "localize", "translation",
"before", and "after" inline <script> elements and the code of the JavaScript
file included in the batch file may not be correct. The obvious solution is to
also append the code in the "localize", "translation", "before" and "after"
inline <script> elements into the batch file along with the code of the
JavaScript file. However, W3TC does not do this.

Another problem is W3TC in "Manual Minify" mode selects the minified file to
use based on the template used to generate the web page. This template can be
dynamically changed using the filter "template_include". Unfortunately, W3TC
continues to use the initial template that WordPress selected and not the
dynamically selected template of the filter "template_include".

Further, since the minified file is selected based on the template used to generate
the webpage, W3TC is assuming that the JavaScript files used by all web pages
generated from the same template are the same. On many modern WordPress web
pages this isn't true. A JavaScript file can be dynamically included in a web
page. E.g., "admin-bar.js" is included on a web page only if the administrator
is logged in.

If you are interested in verifying the above for yourself you can find the
implementation of W3TC 0.9.7.5 JavaScript minification in "manual mode" in the
function Minify_Plugin::ob_callback() in the file "Minify_Plugin.php". The
problem with the filter 'template_include' is caused by the function
Minify_Plugin::get_template() which just duplicates the code in WordPress's 
wp-includes/template-loader.php except it doesn't include the filter.

# Simplified Description of W3TC's JavaScript Minification Algorithms

## auto mode (W3TC 0.9.7.5 to W3TC 0.14.4)

Using PHP's output buffering - ob_start() - W3TC edits the output buffer before it is
sent to browser. W3TC searches for the next <script> element. Unfortunately, it ignores <script>
elements embedded in HTML comments (e.g., `<!--[if lte IE 8]> <script>...</script> <![endif]-->`).
If this <script> element
has a "src" attribute the element is removed from the output buffer and the JavaScript file of the "src"
attribute is appended to the current vector of files to be minified. If this <script>
element does not have a "src" attribute (i.e., it is an inline script) a new <script> element is
inserted before this element in the output buffer.
This inserted <script> element has a "src" attribute set to a minified file whose contents
is the concatenation of the contents of the files in current vector of files to be minified.
The current vector of files to be minified is reset to empty and the search for the next <script>
element is repeated. N.B. the <script> element without a "src" attribute is not modified. Hence, if the
HTML document has many external scripts interleaved with inline scripts (as would be the case when
using WordPress's wp_localize_script() or wp_add_inline_script()) many minified combined script files
would be emitted instead of just one. This algorithm is implemented by W3TC 0.14.4 in the class
Minify_AutoJs in the file "Minify_AutoJs.php".
 
## manual mode (W3TC 0.9.7.5)

Using PHP's output buffering - ob_start() - W3TC edits the output buffer before it is
sent to browser. W3TC removes <script> elements with a "src" attribute set to a JavaScript
file in a "include", "include-body" or "include-footer" minified file. W3TC inserts
immediately after the <head> tag a <script> element with "src" attribute set to the "include"
minified file, inserts immediately after the <body> tag a <script> element with "src" attribute
set to the "include-body" minified file and inserts just before the </body> tag a <script>
element with "src" attribute set to the "include-footer" minified file. <script> elements
without a "src" attribute are not modified. This algorithm is implemented by W3TC 0.9.7.5
in the function Minify_Plugin::ob_callback() in the file "Minify_Plugin.php".

# Persistent W3TC 0.9.7.5 JavaScript Minification Data

The parameters for minification can be found in the "minify.*" properties of W3TC's JSON configuration
file. This file can be downloaded using "Export configuration:" on W3TC's "General Settings" page.

The minified files are found in the folder .../wp-content/cache/minify.

The list of files contained in a minified file is saved in the WordPress option "w3tc_minify".

    php wp-cli.phar eval 'print_r(json_decode(get_option("w3tc_minify"),true));'  
