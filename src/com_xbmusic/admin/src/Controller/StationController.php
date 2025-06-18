<?php 
 /*******
 * @package xbMusic
 * @filesource admin/src/Controller/StationController.php
 * @version 0.0.54.1 13th June 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2025
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

class StationController extends FormController {
    
    public function __construct($config = array(), MVCFactoryInterface $factory = null, $app = null, $input = null) {
        
        parent::__construct($config, $factory, $app, $input);
        
        //article edit view can be called from articles, artlinks, or artimgs.
        //override default by calling with retview set to the desired view name
        $ret = $this->input->get('retview');
        if ($ret) {
            $this->view_list = $ret;
            $this->view_item = 'station&retview='.$ret;
        }
    }

    public function save($key = null, $urlVar = null) {
        $return = parent::save($key, $urlVar);
        $this->setRedirect('index.php?option=com_xbmusic&view=dataman#azuracast');
        return $return;
    }
    
    public function cancel($key = null) {
        $return = parent::cancel($key);
        $this->setRedirect('index.php?option=com_xbmusic&view=dataman#azuracast');
        return $return;
    }
    
    protected function allowEdit($data = [], $key = 'id') {
        
        $recordId = (int) isset($data[$key]) ? $data[$key] : 0;
        $user     = $this->app->getIdentity();
        
        // Zero record (id:0), return component edit permission by calling parent controller method
        if (!$recordId) {
            return parent::allowEdit($data, $key);
        }
        
        // Check edit on the record asset (explicit or inherited)
        if ($user->authorise('core.edit', 'com_xbmusic.station.' . $recordId)) {
            return true;
        }
        
        // Check edit own on the record asset (explicit or inherited)
        if ($user->authorise('core.edit.own', 'com_xbmusic.station.' . $recordId)) {
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
 
    public function checkin() {
        $jip =  Factory::getApplication()->input;
        $pid =  $jip->get('cid');
        $model = $this->getModel('station');
        $wynik = $model->checkin($pid);
        $redirectTo =('index.php?option=com_xbmusic&task=display&view=dataman');
        $this->setRedirect($redirectTo );
    }
    
    public function batch($model = null) {
        
        $this->checkToken();
        
        // Set the model
        /** @var \Joomla\Component\Content\Administrator\Model\ArticleModel $model */
        $model = $this->getModel('station');
        
        // Preset the redirect
        $this->setRedirect((string)Uri::getInstance());
        
        return parent::batch($model);
    }
 
    public function publish() {
        $jip =  Factory::getApplication()->input;
        $pid =  $jip->get('cid');
        $model = $this->getModel('station');
        $wynik = $model->publish($pid);
        $redirectTo =('index.php?option=com_xbmusic&task=display&view=dataman');
        $this->setRedirect($redirectTo );
    }
    
    public function unpublish() {
        $jip =  Factory::getApplication()->input;
        $pid =  $jip->get('cid');
        $model = $this->getModel('station');
        $wynik = $model->publish($pid,0);
        $redirectTo =('index.php?option=com_xbmusic&task=display&view=dataman');
        $this->setRedirect($redirectTo );
    }
    
    public function archive() {
        $jip =  Factory::getApplication()->input;
        $pid =  $jip->get('cid');
        $model = $this->getModel('station');
        $wynik = $model->publish($pid,2);
        $redirectTo =('index.php?option=com_xbmusic&task=display&view=dataman');
        $this->setRedirect($redirectTo);
    }
    
    public function delete() {
        $jip =  Factory::getApplication()->input;
        $pid =  $jip->get('cid');
        $model = $this->getModel('station');
        $wynik = $model->delete($pid);
        $redirectTo =('index.php?option=com_xbmusic&task=display&view=dataman');
        $this->setRedirect($redirectTo );
    }
    
    public function trash() {
        $jip =  Factory::getApplication()->input;
        $pid =  $jip->get('cid');
        $model = $this->getModel('station');
        $wynik = $model->publish($pid,-2);
        $redirectTo =('index.php?option=com_xbmusic&task=display&view=dataman');
        $this->setRedirect($redirectTo );
    }
    
    
}
