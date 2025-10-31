<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Field/XbapikeyslistField.php
 * @version 0.0.59.0 21st October 2025
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

class XbapikeyslistField extends ListField
{
    public function getOptions()
    {
        $userid = Factory::getApplication()->getIdentity()->id;
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query  = $db->getQuery(true);
        $query->select('id AS value, CONCAT(az_apicomment, " (",az_apikeyid,")") AS text')->from('#__xbmusic_userapikeys');
        $query->where($db->qn('user_id').' = '.$db->q($userid));
        $db->setQuery($query);
        $options = $db->loadObjectList();
        $options = array_merge(parent::getOptions(), $options);
        return $options;
    }
}