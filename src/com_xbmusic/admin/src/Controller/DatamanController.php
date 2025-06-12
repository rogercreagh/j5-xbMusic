<?php 
 /*******
 * @package xbMusic
 * @filesource admin/src/Controller/DatamanController.php
 * @version 0.0.53.0 9th June 2025
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
        $folder = trim($post['foldername']);
        $model = $this->getModel('dataman');
        if ($post['selectedfiles']) {
             $files = explode("\n", trim($post['selectedfiles']));
             foreach ($files as &$file) {
                 $file = $folder.trim($file);
             }
         } else {
             $files = $folder;
         }
        $wynik = $model->parseFilesMp3($files, $post['impcat']); 
        Factory::getApplication()->enqueueMessage($wynik);
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
    
    function importazstation() {
        $jip = Factory::getApplication()->getInput();
        $post   = $jip->get('jform', 'array()', 'ARRAY');
        if ($post['loadazid']>0) {
            $model = $this->getModel('dataman');
            $wynick = $model->importAzStation($post['loadazid']);
            if (isset($wynick->code)) {
                Factory::getApplication()->enqueueMessage('Azuracast API Error: code '.$wynick->code.' - '.$wynick->type.
                    '<br />'.$wynick->formatted_message,'Warning');              
            }
        }
        $redirectTo =('index.php?option=com_xbmusic&view=dataman');
        $this->setRedirect($redirectTo );
    }
    
    function deletestation() {
        $jip = Factory::getApplication()->getInput();
        $post   = $jip->get('jform', 'array()', 'ARRAY');
        if ($post['dbstid']>0) {
            $model = $this->getModel('dataman');
            $wynick = $model->deleteDbStation($post['dbstid']);
            if ($wynick === false) {
                Factory::getApplication()->enqueueMessage('Delete failed');
            }        
        }
        $redirectTo =('index.php?option=com_xbmusic&view=dataman');
        $this->setRedirect($redirectTo );
        
    }
    
//     function importazstations() {
//         $model = $this->getModel('dataman');
//         $wynick = $model->importAzStations();
//         if (isset($wynick->code)) {
//             Factory::getApplication()->enqueueMessage('Azuracast API Error: code '.$wynick->code.' - '.$wynick->type.
//                 '<br />'.$wynick->formatted_messagel,'Warning');
            
//         }
//         $redirectTo =('index.php?option=com_xbmusic&view=dataman');
//         $this->setRedirect($redirectTo );
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