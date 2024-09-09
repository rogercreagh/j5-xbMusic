<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Field/XbalbumlistField.php
 * @version 0.0.13.5 8th September 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 * @desc creates a form field type to select an album from list
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Field;

defined('JPATH_BASE') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\Database\DatabaseInterface;
use \stdClass;

class XbalbumlistField extends ListField {
    
    /**
     * element recent if >0 will add the most recent N at top of list
     * {@inheritDoc}
     * @see \Joomla\CMS\Form\Field\ListField::getOptions()
     */
    public function getOptions() { 
//        $db = Factory::getDbo();
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query  = $db->getQuery(true);
        $query->select('id as value, CONCAT(title, " (",sortartist,")") AS text')->from('#__xbmusic_albums');
        $query->order('title, sortartist ASC');
        $db->setQuery($query);
        $result = $db->loadObjectList();
        //now get most recent N for top of list
        $recent = [];
        $cnt = (int) $this->element['recent'];
        if ($cnt > 0) {
            $query->clear('order');
            $query->order('created DESC')->setLimit($cnt);
            $db->setQuery($query);
            $recent = $db->loadObjectList();
            //add a separator between recent and alpha
            $blank = new stdClass();
            $blank->value = 0;
            $blank->text = '------------';
            $recent[] = $blank;        
        }
        // Merge any additional options in the XML definition.
        $options = array_merge(parent::getOptions(), $recent, $result);
        return $options;
        
    }
}
