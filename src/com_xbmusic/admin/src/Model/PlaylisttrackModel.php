<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Model/PlaylisttrackModel.php
 * @version 0.0.13.3 6th September 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Form\Form;
// use Joomla\CMS\Helper\TagsHelper;
// use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
// use Joomla\CMS\Plugin\PluginHelper;
// use Joomla\CMS\String\PunycodeHelper;
// use Joomla\CMS\Table\Table;
// use Joomla\CMS\Table\TableInterface;
// use Joomla\CMS\UCM\UCMType;
// use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
// use Joomla\Database\ParameterType;
// use Joomla\Filter\OutputFilter;
// use Joomla\Registry\Registry;
// use Joomla\Component\Categories\Administrator\Helper\CategoriesHelper;
// use Crosborne\Component\Xbmusic\Administrator\Helper\XbmusicHelper;
// use \SimpleXMLElement;
// use Symfony\Component\Validator\Constraints\IsNull;

class PlaylisttrackModel extends AdminModel {
  
    public $typeAlias = 'com_xbmusic.playlisttrack';
    
    public function getItem($pk = null) {
        if ($item = parent::getItem($pk)) {
        }        
        return $item;
    }
    
    public function getForm($data = [], $loadData = true) {
//        $app  = Factory::getApplication();
//        $params = ComponentHelper::getParams('com_xbmusic');
        
        // Get the form.
        $form = $this->loadForm('com_xbmusic.playlisttrack', 'playlisttrack', ['control' => 'jform', 'load_data' => $loadData]);
        
        if (empty($form)) {
            return false;
        }
        
        return $form;
    }
    
    protected function loadFormData() {
        // Check the session for previously entered form data.
        $app  = Factory::getApplication();
        $data = $app->getUserState('com_xbmusic.edit.playlist.data', []);
        
        if (empty($data)) {
            $data = $this->getItem();
            
        }
                        
        return $data;
    }
        
    public function save($data) {
        $app    = Factory::getApplication();
        $input  = $app->getInput();
        $params = ComponentHelper::getParams('com_xbmusic');
        $filter = InputFilter::getInstance();
        $infomsg = '';
        $warnmsg = '';
       
        
        // ok ready to save the playlist data
        if (parent::save($data)) {
            // Check possible workflow
            if ($infomsg != '') $app->enqueueMessage($infomsg, 'Information');            
            return true;
        }
        if ($infomsg != '') $app->enqueueMessage($infomsg, 'Information');
        if ($warnmsg != '') $app->enqueueMessage($warnmsg, 'Warning');        
        return false;
    }
    
    protected function preprocessForm(Form $form, $data, $group = 'content') {
        
        parent::preprocessForm($form, $data, $group);
    }
    
    public function saveorder($idArray = null, $lft_array = null)
    {
        // Get an instance of the table object.
        $table = $this->getTable();
        
        if (!$table->saveorder($idArray, $lft_array))
        {
            $this->setError($table->getError());
            
            return false;
        }
        
        return true;
    }
    
 
    
}

