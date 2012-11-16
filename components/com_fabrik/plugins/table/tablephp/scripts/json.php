<?php
/*
 * Simple illustration of using the "Additional data" feature of table PHP plugin.
 * 
 * Usually when a table plugin is run, all the plugin is given is the $ids[] array of rowid's
 * for the selected rows.  Usually you can then use those rowid's to load the required table
 * data yourself.  But in certain circumstances this may not be possible, for instance if
 * you have a repeated table join, and need to be able to select individual 'sub rows'.
 * 
 * So, you can provide the Table PHP plugin with a comma separated list of full element names
 * that you want to be given a JSON encoded data structure for.  For instance, if you ask for:
 * 
 * fab_main_test___refnum,fab_main_test_from2___test_text
 * 
 * ... and the user selects 5 rows, the JSON might look like this:
 * 
{
    "1": {
        "rowid": "1",
        "fab_main_test___refnum": "11",
        "fab_main_test_from2___test_text": "dasfsd" 
    },
    "2": {
        "rowid": "1",
        "fab_main_test___refnum": "11",
        "fab_main_test_from2___test_text": "lskdjflsakj" 
    },
    "5": {
        "rowid": "1",
        "fab_main_test___refnum": "11",
        "fab_main_test_from2___test_text": "number 11" 
    },
    "6": {
        "rowid": "2",
        "fab_main_test___refnum": "12",
        "fab_main_test_from2___test_text": "bluber" 
    },
    "8": {
        "rowid": "4",
        "fab_main_test___refnum": "14",
        "fab_main_test_from2___test_text": "" 
    } 
}
 * 
 * .... which, in this case, show that the user selected three 'sub rows' of the main tables rowid 1,
 * and one row for rowid's 2 and 4.  The 1, 2, 5, 6 and 8 are the row indexes, i.e. position within the
 * visible table, and don't really serve any purposes.
 * 
 * So, to be able to use this data in PHP, you need to JSON decode it into a PHP structure.
 * The easiest way to do this is with json_decode(), as follows.  Note that FastJSON
 * is automatically available as Fabrik includes it as a 'helper'.
 * 
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

// First get the JSON string from the submitted data.  As usual, we use JReqiest.
// Note that we also have to urldecode() the string.
$json = urldecode(JRequest::getVar('fabrik_tableplugin_options', ''));

// Now run it through the JSON decoder
$mystuff = json_decode($json);

// Now we have a PHP associative array that looks like this:
/*
array(5) {
  [1]=>
  array(3) {
    ["rowid"]=>
    string(1) "1"
    ["fab_main_test___refnum"]=>
    string(2) "11"
    ["fab_main_test_from2___test_text"]=>
    string(6) "dasfsd"
  }
  [2]=>
  array(3) {
    ["rowid"]=>
    string(1) "1"
    ["fab_main_test___refnum"]=>
    string(2) "11"
    ["fab_main_test_from2___test_text"]=>
    string(11) "lskdjflsakj"
  }
  [5]=>
  array(3) {
    ["rowid"]=>
    string(1) "1"
    ["fab_main_test___refnum"]=>
    string(2) "11"
    ["fab_main_test_from2___test_text"]=>
    string(9) "number 11"
  }
  [6]=>
  array(3) {
    ["rowid"]=>
    string(1) "2"
    ["fab_main_test___refnum"]=>
    string(2) "12"
    ["fab_main_test_from2___test_text"]=>
    string(6) "bluber"
  }
  [8]=>
  array(3) {
    ["rowid"]=>
    string(1) "4"
    ["fab_main_test___refnum"]=>
    string(2) "14"
    ["fab_main_test_from2___test_text"]=>
    string(0) ""
  }
}
 */

/**
 * At this point you would then do whatever you needed with $mystuff
 * 
 * NOTE - because the tablephp javascript gets the data by just grabbing the innerHTML for
 * the required table cells, this may not be 'raw' data.  It will be whatever the element
 * you asked is formatted as within the table, which may include HTML formatting, A links, etc.
 */

?>
