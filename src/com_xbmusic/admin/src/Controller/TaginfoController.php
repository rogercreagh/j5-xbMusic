<?php 
 /*******
 * @package xbMusic
 * @filesource admin/src/Controller/TaginfoController.php
 * @version 0.0.17.0 15th September 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\AdminController;

class TaginfoController extends AdminController {

    
    public function getModel($name = 'Taginfo', $prefix = 'Administrator', $config = array('ignore_request' => true)) {
        return parent::getModel($name, $prefix, $config);
    }
       
    public function tagList() {
        $this->setRedirect('index.php?option=com_xbmusic&view=taglist');
    }

}