<?php 
 /*******
 * @package xbMusic
 * @filesource admin/src/Controller/PlaylisttrackController.php
 * @version 0.0.13.1 22nd August 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

class PlaylisttrackController extends FormController {
    
    public function __construct($config = array(), MVCFactoryInterface $factory = null, $app = null, $input = null) {
        
        parent::__construct($config, $factory, $app, $input);
        
        //article edit view can be called from articles, artlinks, or artimgs.
        //override default by calling with retview set to the desired view name
        $ret = $this->input->get('retview');
        if ($ret) {
            $this->view_list = $ret;
            $this->view_item = 'playlisttrack&retview='.$ret;
        }
    }

//     protected function postSaveHook(BaseDatabaseModel $model, $validData = array()) {
        
//         $task = $this->getTask();
//         $item = $model->getItem();
        
//         if (($task=='setfolder')) {
//             $tid = $validData['id'];
//             if ($tid>0) {
//                 $this->setRedirect('index.php?option=com_xbmusic&view=playlist&layout=edit&id='.$tid);
//             }
//         }
// }
    
    public function publish() {
        $jip =  Factory::getApplication()->input;
        $pid =  $jip->get('cid');
        $model = $this->getModel('playlist');
        $wynik = $model->publish($pid);
        $redirectTo =('index.php?option=com_xbmusic&task=display&view=playlists');
        $this->setRedirect($redirectTo );
    }
    
    public function unpublish() {
        $jip =  Factory::getApplication()->input;
        $pid =  $jip->get('cid');
        $model = $this->getModel('playlist');
        $wynik = $model->publish($pid,0);
        $redirectTo =('index.php?option=com_xbmusic&task=display&view=playlists');
        $this->setRedirect($redirectTo );
    }
    
    public function archive() {
        $jip =  Factory::getApplication()->input;
        $pid =  $jip->get('cid');
        $model = $this->getModel('playlist');
        $wynik = $model->publish($pid,2);
        $redirectTo =('index.php?option=com_xbmusic&task=display&view=playlists');
        $this->setRedirect($redirectTo);
    }
    
    public function delete() {
        $jip =  Factory::getApplication()->input;
        $pid =  $jip->get('cid');
        $model = $this->getModel('playlist');
        $wynik = $model->delete($pid);
        $redirectTo =('index.php?option=com_xbmusic&task=display&view=playlists');
        $this->setRedirect($redirectTo );
    }
    
    public function trash() {
        $jip =  Factory::getApplication()->input;
        $pid =  $jip->get('cid');
        $model = $this->getModel('playlist');
        $wynik = $model->publish($pid,-2);
        $redirectTo =('index.php?option=com_xbmusic&task=display&view=playlists');
        $this->setRedirect($redirectTo );
    }
    
    public function checkin() {
        $jip =  Factory::getApplication()->input;
        $pid =  $jip->get('cid');
        $model = $this->getModel('playlist');
        $wynik = $model->checkin($pid);
        $redirectTo =('index.php?option=com_xbmusic&task=display&view=playlists');
        $this->setRedirect($redirectTo );
    }
    
    
    protected function allowEdit($data = [], $key = 'id') {
        
        $recordId = (int) isset($data[$key]) ? $data[$key] : 0;
        $user     = $this->app->getIdentity();
        
        // Zero record (id:0), return component edit permission by calling parent controller method
        if (!$recordId) {
            return parent::allowEdit($data, $key);
        }
        
        // Check edit on the record asset (explicit or inherited)
        if ($user->authorise('core.edit', 'com_xbmusic.playlist.' . $recordId)) {
            return true;
        }
        
        // Check edit own on the record asset (explicit or inherited)
        if ($user->authorise('core.edit.own', 'com_xbmusic.playlist.' . $recordId)) {
            // Existing record already has an owner, get it
            $record = $this->getModel()->getItem($recordId);
            
            if (empty($record)) {
                return false;
            }
            
            // Grant if current user is owner of the record
            return $user->id == $record->created_by;
        }
        
        return false;
    }
    
    public function batch($model = null) {
        
        $this->checkToken();
        
        // Set the model
        /** @var \Joomla\Component\Content\Administrator\Model\ArticleModel $model */
        $model = $this->getModel('playlist');
        
        // Preset the redirect
        //        $this->setRedirect(Route::_('index.php?option=com_content&view=articles' . $this->getRedirectToListAppend(), false));
        $this->setRedirect((string)Uri::getInstance());
        
        return parent::batch($model);
    }
 
    
}