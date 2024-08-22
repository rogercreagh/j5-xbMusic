<?php 
 /*******
 * @package xbMusic
 * @filesource admin/src/Controller/TracksController.php
 * @version 0.0.4.3 30th April 2024
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

class TracksController extends AdminController {
    
    public function getModel($name = 'Tracks', $prefix = 'Administrator', $config = array('ignore_request' => true)) {
        return parent::getModel($name, $prefix, $config);
    }
    
}