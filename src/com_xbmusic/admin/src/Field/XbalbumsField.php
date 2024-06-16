<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Field/XbalbumsField.php
 * @version 0.0.6.14 10th June 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 * @desc creates a form field type to select an album from list
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Field;

defined('JPATH_BASE') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use \stdClass;

class XbalbumsField extends ListField {
    
    public function getOptions() {
        $db = Factory::getDbo();
        $query  = $db->getQuery(true);
        $query->select('id as value, CONCAT(title, " (",sortartist,")") AS text')->from('#__xbmusic_albums');
        $query->order('title, sortartist ASC');
        $db->setQuery($query);
        $result = $db->loadObjectList();
        //now get most recent 3 for top of list
        $query->clear('order');
        $query->order('created DESC')->setLimit('3');
        $db->setQuery($query);
        $recent = $db->loadObjectList();
        //add a separator between recent and alpha
        $blank = new stdClass();
        $blank->value = 0;
        $blank->text = '------------';
        $recent[] = $blank;
        // Merge any additional options in the XML definition.
        $options = array_merge(parent::getOptions(), $recent, $result);
        return $options;
    }
}