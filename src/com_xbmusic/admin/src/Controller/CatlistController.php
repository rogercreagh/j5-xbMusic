<?php 
 /*******
 * @package xbMusic
 * @filesource admin/src/Controller/CatlistController.php
 * @version 0.0.14.1 10th September 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\AdminController;

class CatlistController extends AdminController {

    protected $edcatlink = 'index.php?option=com_categories&task=category.edit&extension=com_xbmusic&id=';
    
    public function getModel($name = 'Catlist', $prefix = 'Administrator', $config = array('ignore_request' => true)) {
        return parent::getModel($name, $prefix, $config);
    }
       
    public function catEdit() {
        $pks   = (array) $this->input->post->get('cid', [], 'int');
        $this->setRedirect($this->edcatlink.$pks[0]);
    }

    public function catNew() {
        $this->setRedirect($this->edcatlink.'0');
    }

}