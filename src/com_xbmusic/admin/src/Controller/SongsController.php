<?php 
 /*******
 * @package xbMusic
 * @filesource admin/src/Controller/SongsController.php
 * @version 0.0.5.0 15th May 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Router\Route;
use Joomla\Utilities\ArrayHelper;

class SongsController extends AdminController {
    
    public function getModel($name = 'Songs', $prefix = 'Administrator', $config = array('ignore_request' => true)) {
        return parent::getModel($name, $prefix, $config);
    }
        
}