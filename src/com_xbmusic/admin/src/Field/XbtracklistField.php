<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Field/XbtracklistField.php
 * @version 0.0.19.0 19th November 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 * @desc creates a form field type to select a track with list ordered by text similarity to element ['songalias']
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Field;

defined('JPATH_BASE') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use \stdClass;

class XbtracklistField extends ListField {
    
    /**
     * @desc gets an alphabetical list of all available tracks (title and performer)
     * if $songtitle element is defined 
     * {@inheritDoc}
     * @see \Joomla\CMS\Form\Field\ListField::getOptions()
     */
    public function getOptions() {       
//        $sess= Factory::getApplication()->getSession();
//        $songtitle = $sess->get('songtitle');
//        $sess->clear('songtitle');
        $options = parent::getOptions();
        $db = Factory::getDbo();
        $query  = $db->getQuery(true);
        $query->select('id AS value, title AS text, rel_date')->from('#__xbmusic_tracks');
        $query->order('title ASC');
        $db->setQuery($query);
        $result = $db->loadObjectList();
        if (!is_null($result)) {
            foreach ($result as &$value) {
                if (!is_null($value->rel_date)) {
                    $value->text .= ' ('.substr($value->rel_date,0,4).')';
                }
                unset($value->rel_date);
            }
            if (count($result) > 5) {
                //now get most recent 3 for top of list
                $query->clear('order');
                $query->order('modified DESC')->setLimit('3');
                $db->setQuery($query);
                $recent = $db->loadObjectList();
                //add a separator between recent and alpha
                $blank = new stdClass();
                $blank->value = 0;
                $blank->text = '------------';
                $recent[] = $blank;
                $options = array_merge($options, $recent, $result);
            } else {
                $options = array_merge($options, $result);
            }
        }
        return $options;
        
    }
        //CONCAT(title," - ",sortartist," (",SUBSTRING(rel_date,1,4),")") AS text
//         $like = array();
//         /***/
//         if ($songtitle != '') {
//             $query->clear();
//             $query->select('id AS value, title AS text, rel_date')->from('#__xbmusic_tracks');
//             $query->where('SOUNDEX('.$db->q($songtitle).') =  SOUNDEX(title)');
//             $db->setQuery($query);
//             //Factory::getApplication()->enqueueMessage('query '.$query->dump());
//             $like = $db->loadObjectList();
//             if (!empty($like)) $like[] = $blank;
//         }
    
}
