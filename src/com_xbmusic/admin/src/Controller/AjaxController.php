<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Controller/AjaxController.php
 * @version 0.0.42.3 17th March 2025
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

class AjaxController extends BaseController
{
    public function getplaylistfield()
    {
        // if you're using Joomla MVC then the Application instance is passed into the BaseController constructor
        // and stored as an instance variable $app which can be used your component Controllers
        $input = $this->app->input; 

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
                $this->app->enqueueMessage(Text::_('XBMUSIC_AZURACAST_ERROR').
                    ' '.$this->$playlists->code.'<br/>'.$playlists->formatted_message.
                    '<br />'.Text::_('XBMUSIC_CHECK_TRY_LATER'),'warning');
            } else {
                //$options = [];
                foreach ($playlists as $plist) {
                    $item = new stdClass();
                    $item->value =  $plist->id;
                    $item->text = $plist->name;
                    $options[] = $item;
                }
                //$options = array_merge($options, $result);
            }
        }
        
        $result='<div class="control-group">'.
            '<div class="control-label"><label id="jform_azplaylist-lbl" for="jform_azplaylist">'.
            'Azuracast Playlist</label></div>'.
            '<div class="controls has-success">'.
            '<select id="jform_azplaylist" name="jform[azplaylist]" class="form-select valid form-control-success" '.
            'aria-describedby="jform_azplaylist-desc" aria-invalid="false" onChange="loadplaylist(this.value);">';
        foreach ($options as $option) {
            $result.='<option value="'.$option->value.'">'.$option->text.'</option>';
        }
        $result .= '</select><div id="jform_azplaylist-desc" class="hide-aware-inline-help d-none">'.
            '<small class="form-text">Azuracast Playlist to be linked to this one</small>'.
            '</div></div></div>';
        try
        {
            $this->app->enqueueMessage('Azuracast Playlists Loaded Ok','success');
            echo new JsonResponse($result,"");
        }
        catch(\Exception $e)
        {
            echo new JsonResponse($e);
            return false;
        }
    }
    
}