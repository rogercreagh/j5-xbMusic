<?php 
 /*******
 * @package xbMusic
 * @filesource admin/src/Controller/AzuracastController.php
 * @version 0.0.59.0 22nd October 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2025
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbmusicHelper;

class AzuracastController extends FormController {
    
    public function __construct($config = array(), MVCFactoryInterface $factory = null, $app = null, $input = null) {
        
        parent::__construct($config, $factory, $app, $input);
        
    }

    function importazstation() {
        $jip = Factory::getApplication()->getInput();
        $post   = $jip->get('jform', 'array()', 'ARRAY');
        if ($post['loadazid']>0) {
            $model = $this->getModel('azuracast');
            $wynick = $model->importAzStation($post['loadazid']);
            if (isset($wynick->code)) {
                Factory::getApplication()->enqueueMessage('Azuracast API Error: code '.$wynick->code.' - '.$wynick->type.
                    '<br />'.$wynick->formatted_message,'Warning');
            }
        }
        $redirectTo =('index.php?option=com_xbmusic&view=azuracast');
        $this->setRedirect($redirectTo );
    }
    
    function deletestation() {
        $jip = Factory::getApplication()->getInput();
        $post   = $jip->get('jform', 'array()', 'ARRAY');
        if ($post['dbstid']>0) {
            $model = $this->getModel('azuracast');
            $wynick = $model->deleteDbStation($post['dbstid']);
            if ($wynick === false) {
                Factory::getApplication()->enqueueMessage('Delete failed');
            }
        }
        $redirectTo =('index.php?option=com_xbmusic&view=azuracast');
        $this->setRedirect($redirectTo );        
    }
    
    function editstation() {
        $redirectTo =('index.php?option=com_xbmusic&view=azuracast');
        $jip = Factory::getApplication()->getInput();
        $post   = $jip->get('jform', 'array()', 'ARRAY');
        if ($post['dbstid']>0) {
            $redirectTo =('index.php?option=com_xbmusic&view=station&layout=edit&id='.$post['dbstid']);
            $this->setRedirect($redirectTo );
        }        
    }
    
    function saveapi() {
        $jip = Factory::getApplication()->getInput();
        $post   = $jip->get('jform', 'array()', 'ARRAY');
        $fullkey = str_replace(' ','',$post['newapikey']);
        XbmusicHelper::saveApiUserkey($fullkey);
        
        $redirectTo =('index.php?option=com_xbmusic&view=azuracast');
        $this->setRedirect($redirectTo );
        
    }
    
}