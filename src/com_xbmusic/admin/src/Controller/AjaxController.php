<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Controller/AjaxController.php
 * @version 0.0.56.0 17th July 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use \stdClass;
use Crosborne\Component\Xbmusic\Administrator\Helper\AzApi;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbmusicHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbcommonHelper;

class AjaxController extends BaseController
{
    public function getplaylistfield()
    {
        // if you're using Joomla MVC then the Application instance is passed into the BaseController constructor
        // and stored as an instance variable $app which can be used your component Controllers
        $input = $this->app->getInput(); 

        $dbstid = $input->get("dbstid", 0);
        
        // Generate some enqueued messages which will be displayed using the js code
        //$this->app->enqueueMessage("Enqueued notice", "notice");
        //$this->app->enqueueMessage("Enqueued warning", "warning");
                
        $options = array();
        $opt= new stdClass;
        $opt->value = 0; $opt->text='Select Playlist';
        $options[] = $opt;
        
        if ($dbstid > 0) {
            $api = new AzApi($dbstid);
            $playlists = $api->azPlaylists();
            //$this->app->enqueueMessage('<pre>'.print_r($playlists,true).'</pre>');
            if (isset($playlists->code)) {
                $msg = 'Error from API: '.$playlists->code.' - '.$playlists->formatted_message;
                if ($playlists->type == 'NotLoggedInException') $msg .= '. Possibly caused by invalid APIkey';
                try {
                    
                $this->app->enqueuemessage($msg,'Warning');
                echo new JsonResponse('',$msg, true);
                }
                catch(\Exception $e)
                {
                    echo new JsonResponse($e);
                }
                return false;
            } else {
                //$options = [];
                foreach ($playlists as $plist) {
                    $item = new stdClass();
                    $item->value =  $plist->id;
                    $item->text = $plist->name;
                    //check if playlist is already imported
                    if (XbcommonHelper::getItem('#__xbmusic_playlists', $item->value, 'az_plid', 'db_stid = '.$dbstid)) 
                        $item->value = -1;
                    $options[] = $item;
                }
                //$options = array_merge($options, $result);
            }
        }
        
        $result='<div class="control-group">'.
            '<div class="control-label"><label id="jform_azplaylist-lbl" for="jform_azplaylist">'.
            Text::_('XBMUSIC_AZURACAST_PLAYLIST').
            '</label></div>'.
            '<div class="controls has-success">'.
            '<select id="jform_azplaylist" name="jform[azplaylist]" class="form-select valid form-control-success" '.
            'aria-describedby="jform_azplaylist-desc" aria-invalid="false" onChange="loadplaylist(this.value);">';
        foreach ($options as $option) {
            $result.='<option value="'.$option->value.'"';
            if ($option->value == -1) $result .= ' disabled="true"';
            $result .= '>'.$option->text.'</option>';
        }
        $result .= '</select><div id="jform_azplaylist-desc" class="hide-aware-inline-help d-none">'.
            '<small class="form-text">'.
            Text::_('XBMUSIC_AZURACAST_PLAYLIST_SELECT_LINK').'</small>'.
            '</div></div></div>';
        try
        {
            $this->app->enqueueMessage(Text::_('XBMUSIC_AZURACAST_PLAYLISTS_LOADED'),'success');
            echo new JsonResponse($result,"");
        }
        catch(\Exception $e)
        {
            echo new JsonResponse($e);
            return false;
        }
    }
    
}