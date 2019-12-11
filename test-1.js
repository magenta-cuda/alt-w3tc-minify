
// This is a test file for w3tc-minify-helper.php
// For testing MC_Alt_W3TC_Minify::sanitize_for_var_statment_processing().

function foo( a, b, c ) {
    var x = 'This is a string in single quotes.';
    var y = "This is another string in double quotes.";   // This is a trailing inline comment.
/*
    This is a multiline comment.
    This is another line.
 */
    var z = 1;
}
