<?php 
/*******
 * @package xbMusic
 * @filesource admin/src/View/Playlisttracks/HtmlView.php
 * @version 0.0.18.8 8th November 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\View\Playlisttracks;

defined('_JEXEC') or die;

// use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Helper\ContentHelper;
// use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Language\Text;
// use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbcommonHelper;
// use Joomla\CMS\Helper\TagsHelper;
//use Joomla\CMS\Layout\FileLayout;
//use Joomla\CMS\Toolbar\ToolbarFactoryInterface;

class HtmlView extends BaseHtmlView {
    
    protected $items;
    protected $pagination;
    protected $state;
    protected $categories;
    
    public $filterForm;
    
    public $activeFilters;
    
    public function display($tpl = null) {

        $this->items         = $this->get('Items');
        $this->pagination    = $this->get('Pagination');
        $this->state         = $this->get('State');
        $this->filterForm    = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');
               
        $this->params      = ComponentHelper::getParams('com_xbmusic');;

        $this->id = $this->state->get('id',0);
        $this->title = ($this->id>0) ? XbcommonHelper::getItemValue('#__xbmusic_playlists', 'title', $this->id) : 'xxx';
                
        $this->addToolbar();
        
        return parent::display($tpl);
    
    }

    protected function addToolbar()
    {
        // Get the toolbar object instance
        $toolbar = Toolbar::getInstance('toolbar');
        //$toolbar = Factory::getContainer()->get(ToolbarFactoryInterface::class)->createToolbar($name);
        
        ToolbarHelper::title(Text::_('XBMUSIC_ADMIN_PLAYLISTTRACKS_TITLE'), 'far fa-rectangle-list');
        
        $canDo = ContentHelper::getActions('com_xbmusic');
        
        $toolbar->cancel('dashboard.toPlaylists','XBMUSIC_BACK_PLAYLISTS')->icon('fas fa-headphones');
        //ToolbarHelper::editList('playlisttracks.remove','XBMUSIC_BATCH_REMOVE');

        $dropdown = $toolbar->dropdownButton('batch')
        ->text('XB_BATCH_ACTIONS')
        ->toggleSplit(false)
        ->icon('fas fa-square')
        ->buttonClass('btn btn-action')
        ->listCheck(true);
        $batchchildBar = $dropdown->getChildToolbar();
        $batchchildBar->standardButton('batchtop','XBMUSIC_MOVE_TOP', 'playlisttracks.toTop')->listCheck(true)->icon('fas fa-arrows-up-to-line');
        $batchchildBar->standardButton('batchend','XBMUSIC_MOVE_END', 'playlisttracks.toEnd')->listCheck(true)->icon('fas fa-arrows-down-to-line');
        $batchchildBar->standardButton('batchremove','XBMUSIC_LIST_REMOVE', 'playlisttracks.remove')->listCheck(true)->icon('fas fa-trash-can');
        
        $dropdown = $toolbar->dropdownButton('views')
        ->text('XBMUSIC_OTHER_VIEWS')
        ->toggleSplit(false)
        ->icon('icon-ellipsis-h')
        ->buttonClass('btn btn-action')
        ->listCheck(false);
        $childBar = $dropdown->getChildToolbar();
        $childBar->standardButton('dashboardview', 'XB_DASHBOARD', 'dashboard.toDashboard')->listCheck(false)->icon('fas fa-info-circle') ;
        $childBar->standardButton('albumsview', 'XBMUSIC_ALBUMS', 'dashboard.toAlbums')->listCheck(false)->icon('fas fa-users-line') ;
        $childBar->standardButton('artistsview', 'XBMUSIC_ARTISTS', 'dashboard.toArtists')->listCheck(false)->icon('fas fa-users-line') ;
        $childBar->standardButton('playlistsview', 'XBMUSIC_PLAYLISTS', 'dashboard.toPlaylists')->listCheck(false)->icon('fas fa-headphones') ;
        $childBar->standardButton('songsview', 'XBMUSIC_SONGS', 'dashboard.toSongs')->listCheck(false)->icon('fas fa-music') ;
        $childBar->standardButton('tracksview', 'XBMUSIC_TRACKS', 'dashboard.toTracks')->listCheck(false)->icon('fas fa-guitar') ;
        $childBar->standardButton('catsview', 'XB_CATEGORIES', 'dashboard.toCats')->listCheck(false)->icon('far fa-folder-tree') ;
        $childBar->standardButton('tagsview', 'XB_TAGLIST', 'dashboard.toTags')->listCheck(false)->icon('fas fa-tags') ;
        $childBar->standardButton('datamanview', 'XB_DATAMAN', 'dashboard.toDataman')->listCheck(false)->icon('database') ;
        
        if ($canDo->get('core.admin')) {
            //$toolbar->preferences('com_xbmusic');
            ToolbarHelper::preferences('com_xbmusic');
        }
        
        $toolbar->help('xbMusic:Playlisttracks',false,'https://crosborne.uk/xbmusic/doc#playlisttracks');
        
    }
    
    
}