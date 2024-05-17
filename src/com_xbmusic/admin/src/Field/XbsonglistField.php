<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Field/XbsonglistField.php
 * @version 0.0.6.3 17th May 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 * @desc creates a form field type to select a track with list ordered by text similarity to element ['songalias']
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Field;

defined('JPATH_BASE') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Log\Log;
use Joomla\Utilities\ArrayHelper;
use \stdClass;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbmusicHelper;

class XbsonglistField extends ListField {
    
    /**
     * @desc gets an alphabetical list of all available tracks (title and performer)
     * if $songtitle element is defined 
     * {@inheritDoc}
     * @see \Joomla\CMS\Form\Field\ListField::getOptions()
     */
    public function getOptions() {       
        $sess= Factory::getApplication()->getSession();
        $tracktitle = $sess->get('tracktitle');
//        Factory::getApplication()->enqueueMessage('tracktitle '.$tracktitle);
        $sess->clear('tracktitle');
        $db = Factory::getDbo();
        $query  = $db->getQuery(true);
        $query->select('id AS value, title AS text')->from('#__xbmusic_songs');
        $query->order('title ASC');
        $db->setQuery($query);
        $result = $db->loadObjectList();
        //now get most recent 3 for top of list
        $query->clear('order');
        $query->order('created DESC')->setLimit('3');
        $recent = $db->loadObjectList();
        //add a separator between recent and alpha
        $blank = new stdClass();
        $blank->value = 0;
        $blank->text = '------------';
        $recent[] = $blank;
        // now get any title matches for top of list
        $like = array();
        /***/
        if ($tracktitle != '') {
            $query->clear();
            $query->select('id AS value, title AS text')->from('#__xbmusic_songs');
            $query->where('SOUNDEX('.$db->q($tracktitle).') =  SOUNDEX( title)');
            $db->setQuery($query);
            //Factory::getApplication()->enqueueMessage('query '.$query->dump());
            $like = $db->loadObjectList();
            if (!empty($like)) $like[] = $blank;
        }
        /* */
        
        // Merge any additional options in the XML definition.
        $options = array_merge(parent::getOptions(), $like, $recent, $result);
        return $options;
        
    }
}
