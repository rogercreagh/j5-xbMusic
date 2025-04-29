<?php 
 /*******
 * @package xbMusic
 * @filesource admin/src/Controller/ScheduleController.php
 * @version 0.0.51.7 28th April 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2025
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
//use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\MVC\Controller\AdminController;
//use Joomla\CMS\Router\Route;
//use Joomla\Utilities\ArrayHelper;

class ScheduleController extends AdminController {
    
    public function getModel($name = 'Schedule', $prefix = 'Administrator', $config = array('ignore_request' => true)) {
        return parent::getModel($name, $prefix, $config);
    }
      
    public function setStation() {
        $this->setRedirect('index.php?option=com_xbmusic&view=schedule');        
    }
    
    public function clearFilter() {
        $app = Factory::getApplication();
        $app->setUserState('com_xbmusic.schedule.section', null);
        $this->setRedirect('index.php?option=com_xbmusic&view=schedule');
    }
}