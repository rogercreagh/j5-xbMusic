<?php 
 /*******
 * @package xbMusic
 * @filesource admin/src/Controller/TaglistController.php
 * @version 0.0.16.0 14th September 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\AdminController;

class TaglistController extends AdminController {

    protected $edtaglink = 'option=com_tags&task=tag.edit&id=';
    
    public function getModel($name = 'Taglist', $prefix = 'Administrator', $config = array('ignore_request' => true)) {
        return parent::getModel($name, $prefix, $config);
    }
       
    public function tagEdit() {
        $pks   = (array) $this->input->post->get('cid', [], 'int');
        $this->setRedirect($this->edtaglink.$pks[0]);
    }

    public function tagNew() {
        $this->setRedirect($this->edtaglink.'0');
    }

}