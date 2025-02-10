<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Field/XbmemberlistField.php
 * @version 0.0.30.2 8th February 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 * @desc creates a form field type to select an artist by name
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Field;

defined('JPATH_BASE') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\Database\DatabaseInterface;
use \stdClass;

class XbmemberlistField extends ListField {
    
    /**
     * element recent if >0 will add the most recent N at top of list
     * {@inheritDoc}
     * @see \Joomla\CMS\Form\Field\ListField::getOptions()
     */
    public function getOptions() {       
        //create a separator entry
        $blank = new stdClass();
        $blank->value = 0;
        $blank->text = '------------';
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query  = $db->getQuery(true);
        $query->select('id AS value, name AS text')->from('#__xbmusic_artists');
        $query->where('type <> 2');
        $query->order('type DESC, sortname ASC');
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
            $recent[] = $blank;
        }
        // Merge any additional options in the XML definition.
        $options = array_merge(parent::getOptions(), $recent, $result);
        return $options;
        
    }
}
