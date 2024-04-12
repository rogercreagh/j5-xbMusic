<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Model/DatamanModel.php
 * @version 0.0.3.0 3rd April 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Changelog\Changelog;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use DOMDocument;
use ReflectionClass;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbmusicHelper;
use CBOR\OtherObject\TrueObject;

class DatamanModel extends ListModel {
    
//    public function getForm($data = [], $loadData = true) {
//        
//    }

}
