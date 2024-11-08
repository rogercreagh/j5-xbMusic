<?php 
/*******
 * @package xbMusic
 * @filesource admin/src/View/Catinfo/HtmlView.php
 * @version 0.0.15.0 11th September 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\View\Catinfo;

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

        $this->item = $this->get('Item');
        
        $this->params = ComponentHelper::getParams('com_xbmaps');
        
        $this->addToolBar();
         
        parent::display($tpl);
    } 

    protected function addToolbar()
    {
        $user  = Factory::getApplication()->getIdentity();
        // Get the toolbar object instance
        $toolbar = Toolbar::getInstance('toolbar');
        //$toolbar = Factory::getContainer()->get(ToolbarFactoryInterface::class)->createToolbar($name);
        
        ToolbarHelper::title(Text::_('XBMUSIC_ADMIN_CATEGORY_TITLE'), 'folder-open');
        
        $canDo = ContentHelper::getActions('com_xbmusic');
        
        ToolbarHelper::custom('catinfo.catList', 'fas fa-folder-tree', '', 'XB_CATEGORY_LIST', false) ;
        
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
        $childBar->standardButton('playlistview', 'XBMUSIC_PLAYLISTS', 'dashboard.toPlaylists')->listCheck(false)->icon('fas fa-headphones') ;
        $childBar->standardButton('songsview', 'XBMUSIC_SONGS', 'dashboard.toSongs')->listCheck(false)->icon('fas fa-music') ;
        $childBar->standardButton('tracksview', 'XBMUSIC_TRACKS', 'dashboard.toTracks')->listCheck(false)->icon('fas fa-guitar') ;
        $childBar->standardButton('tagsview', 'XB_TAGS', 'dashboard.toTags')->listCheck(false)->icon('fas fa-tags') ;
        
        if ($canDo->get('core.admin')) {
            //$toolbar->preferences('com_xbmusic');
            ToolbarHelper::preferences('com_xbmusic');
        }
        
        $toolbar->help('xbMusic:Categories',false,'https://crosborne.uk/xbmusic/doc#categories');
        
    }
    
    
}