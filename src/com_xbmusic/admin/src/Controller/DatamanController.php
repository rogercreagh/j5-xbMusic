<?php 
 /*******
 * @package xbMusic
 * @filesource admin/src/Controller/DatamanController.php
 * @version 0.0.41.4 3rd March 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
//use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\MVC\Controller\FormController;

class DatamanController extends FormController
{
    function importmp3() {
        $jip = Factory::getApplication()->getInput();
        $post   = $jip->get('jform', 'array()', 'ARRAY');
        $model = $this->getModel('dataman');
         if ($post['selectedfiles']) {
             $files = explode("\n", trim($post['selectedfiles']));
             foreach ($files as &$file) {
                 $file = trim($post['foldername']).trim($file);
             }
         } else {
             $files = trim($post['foldername']);
         }
        $wynik = $model->parseFilesMp3($files, $post['impcat']); 
        $redirectTo =('index.php?option=com_xbmusic&view=dataman');
        $this->setRedirect($redirectTo );
        return $wynik;
        
    }
    
    public function loadLogfile() {
        $jip = Factory::getApplication()->getInput();
        $post   = $jip->get('jform', 'array()', 'ARRAY');
        $model = $this->getModel('dataman');
        $wynick = $model->readLog($post['logfile'], $post['logfilter']);
        $redirectTo =('index.php?option=com_xbmusic&view=dataman');
        $this->setRedirect($redirectTo );
    }
    
    public function saveErrors() {
        $jinput = Factory::getApplication()->input;
        $post   = $jinput->get('jform', 'array()', 'ARRAY');
        $model = $this->getModel();
        $wynick = $model->saveErrors($post['errlist'],true);
        $redirectTo =('index.php?option=com_xbmusic&view=dataman');
        $this->setRedirect($redirectTo );
    }
    
    function importazstations() {
//        $jip = Factory::getApplication()->getInput();
//        $post   = $jip->get('jform', 'array()', 'ARRAY');
//        if ($post['loadazid']>0) {
            $model = $this->getModel('dataman');
            $wynick = $model->importAzStations();
            $redirectTo =('index.php?option=com_xbmusic&view=dataman');
            $this->setRedirect($redirectTo );
//        }
    }
    
//     function importazst() {
//         $jip = Factory::getApplication()->getInput();
//         $post   = $jip->get('jform', 'array()', 'ARRAY');
//         if ($post['loadazid']>0) {
//             $model = $this->getModel('dataman');
//             $wynick = $model->loadAzSt($post['loadazid'],true);
//             $redirectTo =('index.php?option=com_xbmusic&view=dataman');
//             $this->setRedirect($redirectTo );
//         }
//     }
    
//     }
    
    function makesymlink() {
        $jip =  Factory::getApplication()->input;
        $post   = $jip->get('jform', 'array()', 'ARRAY');
        $targ =  $post['link_target'];
        $name = $post['link_name'];
        $model = $this->getModel('dataman');
        $wynik = $model->newsymlink($targ, $name);            
        $redirectTo =('index.php?option=com_xbmusic&task=display&view=dataman');
        $this->setRedirect($redirectTo );       
    }
    
    function remsymlink() {
        $jip =  Factory::getApplication()->input;
        $link = $jip->get('rem_name','','string');
        $model = $this->getModel('dataman');
        if (($link!='')) $wynik = $model->remsymlink($link);
        $redirectTo =('index.php?option=com_xbmusic&task=display&view=dataman');
        $this->setRedirect($redirectTo );
        
    }
    
}