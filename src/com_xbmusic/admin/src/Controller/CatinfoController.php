<?php 
 /*******
 * @package xbMusic
 * @filesource admin/src/Controller/CatinfoController.php
 * @version 0.0.15.0 11th September 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\AdminController;

class CatinfoController extends AdminController {

    
    public function getModel($name = 'Catinfo', $prefix = 'Administrator', $config = array('ignore_request' => true)) {
        return parent::getModel($name, $prefix, $config);
    }
       
    public function catList() {
        $this->setRedirect('index.php?option=com_xbmusic&view=catlist');
    }

}