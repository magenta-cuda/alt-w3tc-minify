The W3 Total Cache auto minify mode does not work on my web site. The problem
is the order of the JavaScript files using the auto minify mode is different
from the order without minification. This results in undefined JavaScript
function errors. W3TC has a help tool for manually setting the order of
JavaScript files. I think this tool is tiresome to use. I just want to have
the same order as without minification. So, I wrote this plugin to do this.

**There is a significant restriction when using manual minify mode. W3TC in
manual minify mode cannot handle templates loaded using the 'template_include'
filter.** I have a [topic](https://wordpress.org/support/topic/problem-in-manual-minify-mode-when-a-plugin-uses-the-filter-template_include/) in the WordPress support forum on this problem.
