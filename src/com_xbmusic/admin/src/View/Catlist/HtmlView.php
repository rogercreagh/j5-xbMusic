<?php 
/*******
 * @package xbMusic
 * @filesource admin/src/View/Catlist/HtmlView.php
 * @version 0.0.14.0 10th September 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\View\Catlist;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbmusicHelper;
use Joomla\CMS\Helper\TagsHelper;
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
        
        ToolbarHelper::title(Text::_('XBMUSIC_ADMIN_CATEGORIES_TITLE'), 'fas fa-compact-disc');
        
        $canDo = ContentHelper::getActions('com_xbmusic');
        
        if ($canDo->get('core.create') || count($user->getAuthorisedCategories('com_xbmusic', 'core.create')) > 0) {
            ToolbarHelper::custom('catlist.categoryNew','new','','XB_CATEGORY_NEW',false);
        }
        
        if ($canDo->get('core.admin')) {
            ToolbarHelper::editList('catlist.categoryEdit', 'XB_CATEGORY_EDIT');
        }
/*         
        if ($canDo->get('core.edit.state') ) {
            $dropdown = $toolbar->dropdownButton('status-group')
            ->text('JTOOLBAR_CHANGE_STATUS')
            ->toggleSplit(false)
            ->icon('icon-ellipsis-h')
            ->buttonClass('btn btn-action')
            ->listCheck(true);
            
            $childBar = $dropdown->getChildToolbar();
            
            $childBar->publish('categories.publish')->listCheck(true);
            
            $childBar->unpublish('categories.unpublish')->listCheck(true);
            
            $childBar->archive('categories.archive')->listCheck(true);
            
            if ($this->state->get('filter.status') != -2) {
                $childBar->trash('categories.trash');
            }
            $childBar->checkin('categories.checkin');
                
        }
        
        if ($this->state->get('filter.status') == -2 && $canDo->get('core.delete')) {
            $toolbar->delete('categories.delete', 'JTOOLBAR_EMPTY_TRASH')
            ->message('JGLOBAL_CONFIRM_DELETE')
            ->listCheck(true);
        }
                
        if ($canDo->get('core.edit.state')) {
            // Add a batch button
            $toolbar->popupButton('batch', 'JTOOLBAR_BATCH')
            ->selector('collapseModal')
            ->listCheck(true);                        
        }
        
 */        
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