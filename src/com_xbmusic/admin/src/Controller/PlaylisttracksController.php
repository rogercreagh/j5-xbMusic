<?php 
 /*******
 * @package xbMusic
 * @filesource admin/src/Controller/PlaylisttracksController.php
 * @version 0.0.13.3 8th September 2024
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

class PlaylisttracksController extends AdminController {
    
    public function getModel($name = 'playlisttracks', $prefix = 'Administrator', $config = array('ignore_request' => true)) {
        return parent::getModel($name, $prefix, $config);
    }
        
    public function SaveOrderAjax() {
        // Check for request forgeries.
        $this->checkToken();
        
        // Get the input
        $pks   = (array) $this->input->post->get('cid', [], 'int');
        $order = (array) $this->input->post->get('order', [], 'int');
        
        // Remove zero PKs and corresponding order values resulting from input filter for PK
        foreach ($pks as $i => $pk) {
            if ($pk === 0) {
                unset($pks[$i]);
                unset($order[$i]);
            }
        }
        
        // Get the model
        $model = $this->getModel();
        
        // Save the ordering
        $return = $model->saveorder($pks, $order);
        
        $this->cleanCache();
        
        if ($return) {
            echo '1';
        }
        
        // Close the application
        $this->app->close();
        return true;
        
    }
}