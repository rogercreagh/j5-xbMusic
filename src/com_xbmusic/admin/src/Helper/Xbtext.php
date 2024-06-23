<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Helper/Xbtext.php
 * @version 0.0.9.0 21st June 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;

class Xbtext extends ComponentHelper {
    
    /**
     * @name _()
     * @desc prefixes and/or appends spaces to langauge string and optionally makes first char lower case
     *  - in en-GB.xbcommon.ini single words are (almost always) with upper case first letter
     * @param string $text - language string
     * @param int $spaces - 1 to prefix, 2 to append, 3 for both
     * @return string
     */
    public static function _(string $text, int $spaces = 0, $lcfirst = false) {
        $result = Text::_($text);
        if ($spaces & 2) $result .=' ';
        if ($spaces & 1) $result = ' '.$result;
        if ($lcfirst) $result = lcfirst($result);
        return $result;
    }
}