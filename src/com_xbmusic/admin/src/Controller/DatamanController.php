<?php 
 /*******
 * @package xbMusic
 * @filesource admin/src/Controller/DatamanController.php
 * @version 0.0.18.0 17th September 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\MVC\Controller\FormController;

class DatamanController extends FormController
{
    function importFolder() {
        $jinput = Factory::getApplication()->input;
        $post   = $jinput->get('jform', 'array()', 'ARRAY');
        $model = $this->getModel('dataman');
        $wynik = $model->parseFiles($post['impcat']); 
        $redirectTo =('index.php?option=com_xbmusic&view=dataman');
        $this->setRedirect($redirectTo );
        
    }
    
    public function saveerrors() {
        $jinput = Factory::getApplication()->input;
        $post   = $jinput->get('jform', 'array()', 'ARRAY');
        $model = $this->getModel();
        $wynick = $model->saveErrors($post['errlist'],true);
        $redirectTo =('index.php?option=com_xbmusic&view=dataman');
        $this->setRedirect($redirectTo );
    }
    
}