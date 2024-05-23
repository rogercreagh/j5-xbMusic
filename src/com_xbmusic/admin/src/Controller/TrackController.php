<?php 
 /*******
 * @package xbMusic
 * @filesource admin/src/Controller/TrackController.php
 * @version 0.0.4.3 30th April 2024
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

class TrackController extends FormController {
    
    public function __construct($config = array(), MVCFactoryInterface $factory = null, $app = null, $input = null) {
        
        parent::__construct($config, $factory, $app, $input);
        
        //article edit view can be called from articles, artlinks, or artimgs.
        //override default by calling with retview set to the desired view name
        $ret = $this->input->get('retview');
        if ($ret) {
            $this->view_list = $ret;
            $this->view_item = 'track&retview='.$ret;
        }
    }

//     protected function postSaveHook(BaseDatabaseModel $model, $validData = array()) {
        
//         $task = $this->getTask();
//         $item = $model->getItem();
        
//         if (($task=='setfolder')) {
//             $tid = $validData['id'];
//             if ($tid>0) {
//                 $this->setRedirect('index.php?option=com_xbmusic&view=track&layout=edit&id='.$tid);
//             }
//         }
// }

    public function setfolder() {
        $app=Factory::getApplication();
        $nf = $this->input->get('jform','','string')['pathname'];
        $app->getSession()->set('musicfolder',$nf);
        $this->setRedirect((string)Uri::getInstance());       
    }
    
    protected function allowEdit($data = [], $key = 'id') {
        
        $recordId = (int) isset($data[$key]) ? $data[$key] : 0;
        $user     = $this->app->getIdentity();
        
        // Zero record (id:0), return component edit permission by calling parent controller method
        if (!$recordId) {
            return parent::allowEdit($data, $key);
        }
        
        // Check edit on the record asset (explicit or inherited)
        if ($user->authorise('core.edit', 'com_xbmusic.track.' . $recordId)) {
            return true;
        }
        
        // Check edit own on the record asset (explicit or inherited)
        if ($user->authorise('core.edit.own', 'com_xbmusic.track.' . $recordId)) {
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
        $model = $this->getModel('track');
        
        // Preset the redirect
        //        $this->setRedirect(Route::_('index.php?option=com_content&view=articles' . $this->getRedirectToListAppend(), false));
        $this->setRedirect((string)Uri::getInstance());
        
        return parent::batch($model);
    }
 
}