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
verified by reading W3TC's source code. See the function
Minify_Plugin::get_template() in the file "Minify_Plugin.php" which just
duplicates the code in WordPress's wp-includes/template-loader.php except
it doesn't include the filter.

# My Analysis of W3TC 0.9.7.5 JavaScript Minification

## manual mode

In my opinion WT3C 0.9.7.5 in "Manual Minify" mode also has a significant design flaw.
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

## auto mode

WT3C 0.9.7.5 in "Auto Minify" mode also does not batch the "localize",
"translation", "before" and "after" inline <script> elements. Rather it stops
batching when it encounters a "localize", "translation", "before" or "after"
inline <script> element" and flushes the current batch file, emits the inline
script element and starts a new batch file. This results in multiple batch
files instead of one.
 
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
implementation of W3TC 0.9.7.5 JavaScript minification in "auto mode" in the
class Minify_AutoJs in the file "Minify_AutoJs.php".

# Simplified Description of W3TC's JavaScript Minification Algorithms

## manual mode

Using PHP's output buffering - ob_start() - W3TC edits the output buffer before it is
sent to browser. W3TC removes <script> elements with a "src" attribute set to a JavaScript
file in a "include", "include-body" or "include-footer" minified file. W3TC inserts
immediately after the <head> tag a <script> element with "src" attribute set to the "include"
minified file, inserts immediately after the <body> tag a <script> element with "src" attribute
set to the "include-body" minified file and inserts just before the </body> tag a <script>
element with "src" attribute set to the "include-footer" minified file. <script> elements
without a "src" attribute are not modified. This algorithm is implemented by W3TC 0.9.7.5
in the function Minify_Plugin::ob_callback() in the file "Minify_Plugin.php".

## auto mode

Using PHP's output buffering - ob_start() - W3TC edits the output buffer before it is
sent to browser. W3TC searches for the next <script> element. If this <script> element
has a "src" attribute the element is removed and the JavaScript file of the "src"
attribute is appended to the current vector of files to be minified. If this <script>
element does not have a "src" attribute a <script> element is inserted before this element.
This inserted <script> element has a "src" attribute set to a minified file whose contents
is the concatenation of the contents of the files in current vector of files to be minified.
The current vector of files to be minified is reset to empty and the search for the next <script>
element is repeated. N.B. the <script> element without a "src" attribute is not modified.
This algorithm is implemented by W3TC 0.9.7.5 in the class Minify_AutoJs in the file
"Minify_AutoJs.php".
 
