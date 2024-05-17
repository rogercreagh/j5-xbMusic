<?php 
 /*******
 * @package xbMusic
 * @filesource admin/src/Controller/SongsController.php
 * @version 0.0.5.0 15th May 2024
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

class SongsController extends AdminController {
    
    public function getModel($name = 'Songs', $prefix = 'Administrator', $config = array('ignore_request' => true)) {
        return parent::getModel($name, $prefix, $config);
    }
    
    public function toDashboard() {
        $this->setRedirect('index.php?option=com_xbmusic&view=dashboard');
    }
    public function toAlbums() {
        $this->setRedirect('index.php?option=com_xbmusic&view=albums');
    }
    public function toArtists() {
        $this->setRedirect('index.php?option=com_xbmusic&view=artists');
    }
    public function toPlaylists() {
        $this->setRedirect('index.php?option=com_xbmusic&view=playlists');
    }
    public function toSongs() {
        $this->setRedirect('index.php?option=com_xbmusic&view=songs');
    }
    public function toTracks() {
        $this->setRedirect('index.php?option=com_xbmusic&view=tracks');
    }
    public function toCats() {
        $this->setRedirect('index.php?option=com_categories&view=categories&extension=com_xbmusic');
    }
    public function toTags() {
        $this->setRedirect('index.php?option=com_xbmusic&view=tags');
    }
    
}