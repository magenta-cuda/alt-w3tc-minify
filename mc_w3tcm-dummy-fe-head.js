// If the page contains only inline <script> elements then W3TC will not run its JavaScript minifier because it batches
// only non inline <script> elements and since there are none of these it sees an empty batch. However, the monitor's
// minifier has batched the inline <script> elements and it needs W3TC to run its minifier as the monitor's minifier
// runs on a filter in W3TC's minifier code. To solve this we will emit dummy <head> and <body> <script> elements.
