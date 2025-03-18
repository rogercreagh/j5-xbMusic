<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Field/XbazstationsField.php
 * @version 0.0.41.2 13th March 2025
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

class XbazstationsField extends ListField {
    
    /**
     * @desc gets a list of all available azuracast stations 
     * @desc grouped by server (alphabetical by name)
     * {@inheritDoc}
     * @see \Joomla\CMS\Form\Field\ListField::getOptions()
     */
    public function getOptions() { 
        $options = parent::getOptions();
        $db = Factory::getDbo();
        $query  = $db->getQuery(true);
        $query->select('id AS value, CONCAT(title) AS text, az_url')->from('#__xbmusic_azstations');
        $query->order('az_url ASC')->order('title ASC');
        $db->setQuery($query);
        $result = $db->loadObjectList();
        if (!is_null($result)) {
            $options = array_merge($options, $result);
        }        
        return $options;        
    }
}
