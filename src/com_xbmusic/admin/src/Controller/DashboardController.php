<?php 
 /*******
 * @package xbMusic
 * @filesource admin/src/Controller/DashboardController.php
 * @version 0.0.13.0 20th August 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\AdminController;

class DashboardController extends AdminController
{
    public function getModel($name = 'Dashboard', $prefix = 'Administrator', $config = array('ignore_request' => true))
    {
        return parent::getModel($name, $prefix, $config);
    }

    //these redirect functions called from all list views 'other views' menu
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
    public function toPlaylisttracks() {
        $jip =  Factory::getApplication()->input;
        $pid =  $jip->get('cid');
        $this->setRedirect('index.php?option=com_xbmusic&view=playlisttracks&id='.$pid[0]);
    }
    public function toSongs() {
        $this->setRedirect('index.php?option=com_xbmusic&view=songs');
    }
    public function toTracks() {
        $this->setRedirect('index.php?option=com_xbmusic&view=tracks');
    }
    public function toCats() {
        $this->setRedirect('index.php?option=com_xbmusic&view=catlist');
    }
    public function toTags() {
        $this->setRedirect('index.php?option=com_xbmusic&view=taglist');
    }
    public function toDataman() {
        $this->setRedirect('index.php?option=com_xbmusic&view=dataman');
    }
}

