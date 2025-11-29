<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Field/XbazplaylistsField.php
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
use Joomla\CMS\Language\Text;
//use Joomla\CMS\Log\Log;
//use Joomla\Utilities\ArrayHelper;
use \stdClass;
use Crosborne\Component\Xbmusic\Administrator\Helper\AzApi;

class XbazplaylistsField extends ListField {
    
    /**
     * @desc gets a list of all available azuracast playlists given a station 
     * @desc grouped by server (alphabetical by name)
     * {@inheritDoc}
     * @see \Joomla\CMS\Form\Field\ListField::getOptions()
     */
    public function getOptions() { 
        $options = parent::getOptions();
        $azstid = $this->azstid;
        if ($dbstid > 0) {
            $api = new AzApi();
            $playlists = $api->azPlaylists($azstid);
            if (isset($playlists->code)) {
                Factory::getApplication()->enqueueMessage(Text::_('XBMUSIC_AZURACAST_ERROR').
                    ' '.$this->$playlists->code.'<br/>'.$playlists->formatted_message.
                    '<br />'.Text::_('XBMUSIC_CHECK_TRY_LATER'),'Error');
            } else {
                $result = [];
                $item = new stdClass();
                foreach ($result as $plist) {
                    $item->value =  $plist->id;
                    $item->text = $plist->name;
                    $result[] = $item;
                }
                $options = array_merge($options, $result);
           }
        }
        return $options;        
    }
}
