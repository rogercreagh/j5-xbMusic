<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Field/XbplaylististField.php
 * @version 0.0.19.0 19th November 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 * @desc creates a form field type to select a track with list ordered by text similarity to element ['songalias']
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Field;

defined('JPATH_BASE') or die;

//use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
//use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Form\Field\ListField;
//use Joomla\CMS\Log\Log;
//use Joomla\Utilities\ArrayHelper;
use \stdClass;

class XbplaylistlistField extends ListField {
    
    /**
     * @desc gets an alphabetical list of all available playlists (alphabetical by title)
     * @desc if more than 5 available most recent 3 will be added at top of list
     * {@inheritDoc}
     * @see \Joomla\CMS\Form\Field\ListField::getOptions()
     */
    public function getOptions() { 
        $options = parent::getOptions();
        $db = Factory::getDbo();
        $query  = $db->getQuery(true);
        //SELECT id, CONCAT(title, ' (',(SELECT COuNT(*) FROM j512_xbmusic_songalbum AS a WHERE a.song_id = s.id),')') AS title FROM j512_xbmusic_songs AS s
        
        $query->select('id AS value, title AS text')->from('#__xbmusic_azplaylists');
        $query->order('title ASC');
        $db->setQuery($query);
        $result = $db->loadObjectList();
        if (!is_null($result)) {
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
}
