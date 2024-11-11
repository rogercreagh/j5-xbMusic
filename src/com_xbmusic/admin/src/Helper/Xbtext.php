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
     * @desc prefixes and/or appends spaces to langauge string and optionally changes case and uses sprintf
     *  - in en-GB.xbcommon.ini single words are (almost always) with upper case first letter
     * @param string $text - language string
     * @param int $opts - 1 to prefix, 2 to append, 3 for both, 4 wrap double quotes, 8 append \n
     * @param int $case - 1 lcfirst, 2 ucfirst, 3 to lower, 
     * @param boolean|array $translate - false no translation, true translate, array use sprintf with array[optionss]
     * @return string
     */
    public static function _(string $text, int $opts = 0, $case = false, $translate = '') {
        if ($translate === false) {
            $result = $text;
        } else {
            $result = (is_array($translate)) ? Text::sprintf($text,$translate) : Text::_($text);                      
        }
        switch ($case) {
            case 1:
                $result = lcfirst($result);
                break;
            case 2:
                $result = ucfirst($result);
                break;
            case 3:
                $result = strtolower($result);
                break;
            case 4:
                $result = strtoupper($result);
                break;
            default:
                break;
        }
        if ($opts & 4) $result = '"'.$result.'"';
        if ($opts & 1) $result = ' '.$result;
        if ($opts & 2) $result .=' ';
        if ($opts & 8) $result = $result."\n";
        
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