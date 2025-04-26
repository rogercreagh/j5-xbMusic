<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Field/XbtimeField.php
 * @version 0.0.51.5 26th April 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2025
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\TimeField;

class XbtimeField extends TimeField
{
    public function __set($name, $value)
    {
        switch ($name) {
            case 'max':
            case 'min':
            case 'step':
                $this->$name = (int) $value;
                break;
            case 'default':
                if ($this->value == 'now') {
//                    if ($value == 'now') {
                        $this->value = date('H:i');
//                    } else {
//                        parent::__set($name, $value);
//                    }
                }
                break;
            default:
                parent::__set($name, $value);
        }
    }
       
}