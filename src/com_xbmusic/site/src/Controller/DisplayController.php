<?php 
 /*******
 * @package xbMusic
 * @filesource site/src/Controller/DisplayController.php
 * @version 0.0.60.0 24th March 2026
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2026
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;

class DisplayController extends BaseController {
    
    public function display($cachable = false, $urlparams = array())
    {
        Factory::getApplication()->getLanguage()->load('xbcommon', JPATH_ADMINISTRATOR.'/components/com_xbmusic');
        return parent::display();
    }
    
}