<?php
/*******
 * @package xbMusic
 * @filesource admin/src/extension/defines.php
 * @version 0.0.19.4 7th January 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html

 ******/

defined('_JEXEC') or die;

/** constants used by class Xbtext first parameter to flag text modifiers
 * second paramter to Xbtext is an optional class naem string
 **/
//bitwise flags to modify or wrap text, default  is 0 = transform
define('XBSP1', 1); //add a space before the text
define('XBSP2', 2); //add a space after text
define('XBSP3', 3); //add spaces before and after text
define('XBSQ', 4); //wrap in single quotes
define('XBDQ', 8); //wrap in double quotes
define('XBP', 16); //wrap in <p>...</p> with optional class in second param
// if css class(es) specified in second parameter a <span class="xyz">..</span> will wrap the text
define('XBBR', 32); //add <br /> after text
define('XBHR', 64); //add <hr /> after text 
define('XBNL', 128); //add \n newline after text

define('XBTRL',512); //translate text by passing through Text::_() before processing

// used to adjust case of text.
//most useful if translate is true (param & 256)
define('XBLC1', 1024); // lower case first letter
define('XBUC1', 2048); //capitalise first letter
define('XBLCALL', 4096); // all lower case
define('XBUCALL', 8192); // all upper case

/** log messages 
 * 
 * 
 */
define('XBINFO','[INFO] ');
define('XBWARN','[WARNING] ');
define('XBERR','[ERROR] ');
define('XBENDITEM'," -------------------------- \n");
define('XBENDLOG',"\n ============================== \n\n");
