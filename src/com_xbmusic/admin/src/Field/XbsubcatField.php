<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Field/XbsubcatField.php
 * @version 0.0.4.1 28th April 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 * @desc create a form field type to select a category allowing both a parent and a number of levels to be specified.
 * based on code from joomla3|4-/libraires/legacy|src/Form/Field/category|CategoryField.php
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Field;

defined('JPATH_BASE') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Log\Log;
use Joomla\Utilities\ArrayHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbmusicHelper;

class XbsubcatField extends ListField {
    
    public function getOptions() {
        
        $params = ComponentHelper::getParams('com_xbmusic');
        $options = array();
        $extension = $this->element['extension'] ? (string) $this->element['extension'] : 'com_xbmusic';
        $published = (string) $this->element['published'];
        $language  = (string) $this->element['language'];
        if (!empty($this->element['itemtype'])) {
            $itemtype = (string) $this->element['itemtype'];
            $rootid = $params->get('rootcat_'.$itemtype);
            $incroot= true;
        } else {
            $rootid = 0;
            $incroot = false;
        }
        
        $db = Factory::getDbo();
        $query  = $db->getQuery(true);
        if ($rootid>0) {
            $query->select('*')->from('#__categories')->where('id='.$rootid);
            $db->setQuery($query);
            $rootcat=$db->loadObject();
        }
        $start = $incroot ? '>=' : '>';
        
        $query->clear();
        $query->select('id AS value, title AS text, level')->from('#__categories')
        ->where('extension = '.$db->quote($extension));
        if ($rootid>0) {
            $query->where(' lft'.$start.$rootcat->lft.' AND rgt <='.$rootcat->rgt);
        }
        if ($published) {
            $query->where('published = 1');
        }
        $query->order('lft');
        $db->setQuery($query);
        $options = $db->loadObjectList();
        foreach ($options as &$item) {
            $adj = $incroot ? 0 : 1;
            $startlevel = $rootid>0 ? $rootcat->level + $adj :1;
            if ($item->level>0) {
                $item->text = str_repeat('- ', $item->level - $startlevel).$item->text;
            }
        }
        // Merge any additional options in the XML definition.
        $options = array_merge(parent::getOptions(), $options);
        return $options;
    }
    
}