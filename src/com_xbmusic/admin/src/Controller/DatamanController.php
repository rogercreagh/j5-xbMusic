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
    function importmp3() {
        $jip = Factory::getApplication()->getInput();
        $post   = $jip->get('jform', 'array()', 'ARRAY');
        $model = $this->getModel('dataman');
        if ($post['filepathname']) {
            $files = explode("\n", trim($post['filepathname'],"\n"));
            foreach ($files as &$file) {
                $file = $post['foldername'].$file;
            }
        } else {
            $files = $post['foldername'];
        }
        $wynik = $model->parseFilesMp3($files, $post['impcat']); 
        $redirectTo =('index.php?option=com_xbmusic&view=dataman');
        $this->setRedirect($redirectTo );
        return $wynik;
        
    }
    
    public function saveErrors() {
        $jinput = Factory::getApplication()->input;
        $post   = $jinput->get('jform', 'array()', 'ARRAY');
        $model = $this->getModel();
        $wynick = $model->saveErrors($post['errlist'],true);
        $redirectTo =('index.php?option=com_xbmusic&view=dataman');
        $this->setRedirect($redirectTo );
    }
    
}