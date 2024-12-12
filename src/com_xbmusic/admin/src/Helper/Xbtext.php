<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Helper/Xbtext.php
 * @version 1.0.0.0 10th November 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Button\ActionButton;
use Joomla\Plugin\Fields\SQL\Extension\SQL;

class Xbtext extends ComponentHelper {
    
    /**
    * @name _()
    * @desc processes text with optional translation, case conversion, sprintf,
    * single or double quotes, spaces before/after, css classes, paragraph wrapper
    * and line break, horizontal ruler, unix newline appended
    *  - in en-GB.xbcommon.ini single words are (almost always) with upper case first letter
    * @desc 
XBT_SP_FIRST', 1); //add a space before the text
XBT_SP_LAST add a space after text
XBT_SP_BOTH add spaces before and after text
XBT_SQ wrap in single quotes
XBT_DQ wrap in double quotes
XBT_P wrap in <p>...</p> with optional class in second param
// if css class(es) specified in second parameter a <span class="xyz">..</span> will wrap the text
XBT_BR add <br /> after text
XBT_HR add <hr /> after text 
XBT_NL add \n newline after text

XBT_TRANS translate text by passing through Text::_() before processing

// used to adjust case of text.
//most useful if translate is true (param & 256)
XBT_LC1  lower case first letter
XBT_UC1 capitalise first letter
XBT_LCALL all lower case
XBT_UCALL all upper case

    * @param string $text - language string
    * @param int $opts - see defines require by component in comment below
    * @param string $class -  css classes to be applied to p or span around the text
    * @param boolean|array $sprintf use Text::sprintf() with options in from array
    * @return string
     */
    public static function _(string $text, int $opts = 0, $class = '', $sprint = []) {
        //first do translation and sprintf if required
        if ($opts & XBT_TRANS) {
            $result = (!empty($sprint)) ? Text::sprintf($text,$translate) : Text::_($text);                      
        } else {
            $result = $text;
        }
        //second do any case conversion
        if ($opts & XBT_LCALL) $result = strtolower($result);
        if ($opts & XBT_UCALL) $result = strtoupper($result);
        if ($opts & XBT_LC1) $result = lcfirst($result);
        if ($opts & XBT_UC1) $result = ucfirst($result);
        //third add any wrappers
        if ($opts & XBT_SQ) $result = '\''.$result.'\'';
        if ($opts & XBT_DQ) $result = '"'.$result.'"';
        if ($opts & XBT_SP_FIRST) $result = ' '.$result;
        if ($opts & XBT_SP_LAST) $result .=' ';
        //now wrap in p or span with any class specified
        if ($class != '')  {
            $class = ' class="'.$class.'"';
            if ($opts & XBT_P) {
                $result = '<p'.$class.'>'.$result.'</p>';
            } else {
                $result = '<span class="'.$class.'">'.$result.'</span>';                
            }            
        } else {
            //no class wrap in paragraph
            if ($opts & XBT_P) $result = '<p>'.$result.'</p>';           
        }
        //finally append any br hr and newlie required
        if ($opts & XBT_BR) $result = $result."<br />";
        if ($opts & XBT_HR) $result = $result."<hr />";
        if ($opts & XBT_NL) $result = $result."\n";
        
        return $result;
    }
    
    /**
     * @name q()
     * @desc wraps double quotes around given text, optionally translating it first
     * @param string $text
     * @param boolean $translate
     * @return string
     */
    public static function q(string $text, $translate = false) {
        if ($translate) $text = Text::_($text);
        return '"'.$text.'"';
    }
}