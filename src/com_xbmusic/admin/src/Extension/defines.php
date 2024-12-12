<?php
/*******
 * @package xbMusic
 * @filesource admin/src/extension/defines.php
 * @version 0.0.19.3 12th December 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/
/** constants used by class Xbtext first parameter to flag text modifiers
 * second paramter is an optional class
 **/
//bitwise flags to modify or wrap text, default  is 0 = transform
define('XBT_SP_FIRST', 1); //add a space before the text
define('XBT_SP_LAST', 2); //add a space after text
define('XBT_SP_BOTH', 3); //add spaces before and after text
define('XBT_SQ', 4); //wrap in single quotes
define('XBT_DQ', 8); //wrap in double quotes
define('XBT_P', 16); //wrap in <p>...</p> with optional class in second param
// if css class(es) specified in second parameter a <span class="xyz">..</span> will wrap the text
define('XBT_BR', 32); //add <br /> after text
define('XBT_HR', 64); //add <hr /> after text 
define('XBT_NL', 128); //add \n newline after text

define('XBT_TRANS',512); //translate text by passing through Text::_() before processing

// used to adjust case of text.
//most useful if translate is true (param & 256)
define('XBT_LC1', 1024); // lower case first letter
define('XBT_UC1', 2048); //capitalise first letter
define('XBT_LCALL', 4096); // all lower case
define('XBT_UCALL', 8192); // all upper case

/** log messages 
 * 
 * 
 */
define('XBINFO','[INFO] ');
define('XBWARN','[WARNING] ');
define('XBERR','[ERROR] ');
define('XBENDITEM'," -------------------------- \n");
define('XBENDLOG',"\n ============================== \n\n");
