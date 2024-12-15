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
XBSP1', 1); //add a space before the text
XBSP2 add a space after text
XBSP3 add spaces before and after text
XBSQ wrap in single quotes
XBDQ wrap in double quotes
XBP wrap in <p>...</p> with optional class in second param
// if css class(es) specified in second parameter a <span class="xyz">..</span> will wrap the text
XBBR add <br /> after text
XBHR add <hr /> after text 
XBNL add \n newline after text

XBTRL translate text by passing through Text::_() before processing

// used to adjust case of text.
//most useful if translate is true (param & 256)
XBLC1  lower case first letter
XBUC1 capitalise first letter
XBLCALL all lower case
XBUCALL all upper case

    * @param string $text - language string
    * @param int $opts - see defines require by component in comment below
    * @param string $class -  css classes to be applied to p or span around the text
    * @param boolean|array $sprintf use Text::sprintf() with options in from array
    * @return string
     */
    public static function _(string $text, int $opts = 0, $class = '', $sprint = []) {
        //first do translation and sprintf if required
        if ($opts & XBTRL) {
            $result = (!empty($sprint)) ? Text::sprintf($text,$translate) : Text::_($text);                      
        } else {
            $result = $text;
        }
        //second do any case conversion
        if ($opts & XBLCALL) $result = strtolower($result);
        if ($opts & XBUCALL) $result = strtoupper($result);
        if ($opts & XBLC1) $result = lcfirst($result);
        if ($opts & XBUC1) $result = ucfirst($result);
        //third add any wrappers
        if ($opts & XBSQ) $result = '\''.$result.'\'';
        if ($opts & XBDQ) $result = '"'.$result.'"';
        if ($opts & XBSP1) $result = ' '.$result;
        if ($opts & XBSP2) $result .=' ';
        //now wrap in p or span with any class specified
        if ($class != '')  {
            $class = ' class="'.$class.'"';
            if ($opts & XBP) {
                $result = '<p'.$class.'>'.$result.'</p>';
            } else {
                $result = '<span class="'.$class.'">'.$result.'</span>';                
            }            
        } else {
            //no class wrap in paragraph
            if ($opts & XBP) $result = '<p>'.$result.'</p>';           
        }
        //finally append any br hr and newlie required
        if ($opts & XBBR) $result = $result."<br />";
        if ($opts & XBHR) $result = $result."<hr />";
        if ($opts & XBNL) $result = $result."\n";
        
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