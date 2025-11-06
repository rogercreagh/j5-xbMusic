<?php 
/*******
 * @package xbMusic
 * @filesource admin/src/View/Catlist/HtmlView.php
 * @version 0.0.59.4 6th November 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2025
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\View\Catlist;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Helper\ContentHelper;
// use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Language\Text;
// use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
// use Crosborne\Component\Xbmusic\Administrator\Helper\XbmusicHelper;
// use Joomla\CMS\Helper\TagsHelper;
//use Joomla\CMS\Layout\FileLayout;
//use Joomla\CMS\Toolbar\ToolbarFactoryInterface;

class HtmlView extends BaseHtmlView {
    
    protected $items;
    protected $pagination;
    protected $state;
    
    public $filterForm;
    
    public $activeFilters;
    
    public function display($tpl = null) {

        $this->items         = $this->get('Items');
        $this->pagination    = $this->get('Pagination');
        $this->state         = $this->get('State');
        $this->filterForm    = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');
               
        $this->params      = ComponentHelper::getParams('com_xbmusic');;
        $this->azuracast = $this->params->get('azuracast','0');
        $this->rootcat_album = $this->params->get('rootcat_album');
        $this->defcat_album = $this->params->get('defcat_album');
        $this->rootcat_artist = $this->params->get('rootcat_artist');
        $this->defcat_artist = $this->params->get('defcat_artist');
        $this->rootcat_playlist = $this->params->get('rootcat_playlist');
        $this->defcat_playlist = $this->params->get('defcat_playlist');
        $this->rootcat_song = $this->params->get('rootcat_song');
        $this->defcat_song = $this->params->get('defcat_song');
        $this->rootcat_track = $this->params->get('rootcat_track');
        $this->defcat_track = $this->params->get('defcat_track');
        
        $this->addToolbar();
        
        return parent::display($tpl);
    
    }

    protected function addToolbar()
    {
        $user  = Factory::getApplication()->getIdentity();
        // Get the toolbar object instance
        $toolbar = Toolbar::getInstance('toolbar');
        //$toolbar = Factory::getContainer()->get(ToolbarFactoryInterface::class)->createToolbar($name);
        
        ToolbarHelper::title(Text::_('XBMUSIC_ADMIN_CATEGORIES_TITLE'), 'fas fa-folder-tree');
        
        $canDo = ContentHelper::getActions('com_xbmusic');
        
        if ($canDo->get('core.create') || count($user->getAuthorisedCategories('com_xbmusic', 'core.create')) > 0) {
            ToolbarHelper::custom('catlist.catNew','new','','XB_CATEGORY_NEW',false);
        }
        
        if ($canDo->get('core.admin')) {
            ToolbarHelper::editList('catlist.catEdit', 'XB_CATEGORY_EDIT');
        }
   
        $dropdown = $toolbar->dropdownButton('views')
            ->text('XBMUSIC_OTHER_VIEWS')
            ->toggleSplit(false)
            ->icon('icon-ellipsis-h')
            ->buttonClass('btn btn-action')
            ->listCheck(false);
        $childBar = $dropdown->getChildToolbar();
        $childBar->standardButton('dashboardview', 'XB_DASHBOARD', 'dashboard.toDashboard')->listCheck(false)->icon('fas fa-info-circle') ;
        $childBar->standardButton('albumsview', 'XBMUSIC_ALBUMS', 'dashboard.toAlbums')->listCheck(false)->icon('fas fa-compact-disc') ;
        $childBar->standardButton('artistsview', 'XBMUSIC_ARTISTS', 'dashboard.toArtists')->listCheck(false)->icon('fas fa-users-line') ;
//        $childBar->standardButton('playlistview', 'XBMUSIC_PLAYLISTS', 'dashboard.toPlaylists')->listCheck(false)->icon('fas fa-headphones') ;
        $childBar->standardButton('songsview', 'XBMUSIC_SONGS', 'dashboard.toSongs')->listCheck(false)->icon('fas fa-music') ;
        $childBar->standardButton('tracksview', 'XBMUSIC_TRACKS', 'dashboard.toTracks')->listCheck(false)->icon('fas fa-guitar') ;
        $childBar->standardButton('tagsview', 'XB_TAGLIST', 'dashboard.toTags')->listCheck(false)->icon('fas fa-tags') ;
        if ( $this->azuracast) {
            $childBar->standardButton('azuracastview', 'XBMUSIC_AZURACAST', 'dashboard.toAzuracast')->listCheck(false)->icon('fas fa-broadcast-tower') ;
        }
        $childBar->standardButton('datamanview', 'XB_DATAMAN', 'dashboard.toDataman')->listCheck(false)->icon('icon-database') ;
        
        if ($canDo->get('core.admin')) {
            //$toolbar->link('Options','index.php?option=com_config&view=component&component=com_xbmusic');
            //  preferences('com_xbmusic')->buttonClass('');
            ToolbarHelper::preferences('com_xbmusic');
        }
        
        $toolbar->help('xbMusic:Categories',false,'https://crosborne.uk/xbmusic/doc#categories');
        
    }
    
    
}