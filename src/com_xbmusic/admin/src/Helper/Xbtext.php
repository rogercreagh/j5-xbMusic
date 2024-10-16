<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Helper/Xbtext.php
 * @version 0.0.18.4 11th October 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Button\ActionButton;

class Xbtext extends ComponentHelper {
    
    /**
     * @name _()
     * @desc prefixes and/or appends spaces to langauge string and optionally changes case and uses sprintf
     *  - in en-GB.xbcommon.ini single words are (almost always) with upper case first letter
     * @param string $text - language string
     * @param int $spaces - 1 to prefix, 2 to append, 3 for both
     * @param int $case - 1 lcfirst, 2 ucfirst, 3 to lower, 4 to upper, false no Action
     * @param array $sprintf - true use sprintf
     * @return string
     */
    public static function _(string $text, int $spaces = 0, $case = false, $sparams = '') {
        $result = ($sparams =='') ? Text::_($text) : Text::sprintf($text,$params);
        if ($spaces & 2) $result .=' ';
        if ($spaces & 1) $result = ' '.$result;
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
        return $result;
    }
}