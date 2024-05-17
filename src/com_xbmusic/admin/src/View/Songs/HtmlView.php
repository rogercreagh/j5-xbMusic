<?php 
/*******
 * @package xbMusic
 * @filesource admin/src/View/Songs/HtmlView.php
 * @version 0.0.5.0 15th May 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\View\Songs;

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
        
        $this->addToolbar();
        
        return parent::display($tpl);
    
    }

    protected function addToolbar()
    {
        // Get the toolbar object instance
        $toolbar = Toolbar::getInstance('toolbar');
        //$toolbar = Factory::getContainer()->get(ToolbarFactoryInterface::class)->createToolbar($name);
        
        ToolbarHelper::title(Text::_('XBMUSIC_ADMIN_SONGS_TITLE'), 'fas fa-music');
        
        $canDo = ContentHelper::getActions('com_xbmusic');
        
        if ($canDo->get('core.create') || count($user->getAuthorisedCategories('com_xbmusic', 'core.create')) > 0)
        {
            ToolbarHelper::addNew('song.add');
        }
        
        if ($canDo->get('core.edit.state') ) {
            $dropdown = $toolbar->dropdownButton('status-group')
            ->text('JTOOLBAR_CHANGE_STATUS')
            ->toggleSplit(false)
            ->icon('icon-ellipsis-h')
            ->buttonClass('btn btn-action')
            ->listCheck(true);
            
            $childBar = $dropdown->getChildToolbar();
            
            $childBar->publish('songs.publish')->listCheck(true);
            
            $childBar->unpublish('songs.unpublish')->listCheck(true);
            
            $childBar->archive('songs.archive')->listCheck(true);
            
            if ($this->state->get('filter.status') != -2) {
                $childBar->trash('songs.trash');
            }
            $childBar->checkin('songs.checkin');
                
        }
        
        $dropdown = $toolbar->dropdownButton('views')
        ->text('XBMUSIC_OTHER_VIEWS')
        ->toggleSplit(false)
        ->icon('icon-ellipsis-h')
        ->buttonClass('btn btn-action')
        ->listCheck(false);
        $childBar = $dropdown->getChildToolbar();
        $childBar->standardButton('dashboardview', 'Dashboard', 'songs.toDashboard')->listCheck(false)->icon('fas fa-info-circle') ;
        $childBar->standardButton('albumsview', 'Albums', 'songs.toAlbums')->listCheck(false)->icon('fas fa-compact-disc') ;
        $childBar->standardButton('artistsview', 'Artists', 'songs.toArtists')->listCheck(false)->icon('fas fa-users-line') ;
        $childBar->standardButton('playlistview', 'Playlists', 'songs.toPlaylists')->listCheck(false)->icon('fas fa-headphones') ;
        $childBar->standardButton('tracksview', 'Tracks', 'songs.toTracks')->listCheck(false)->icon('fas fa-music') ;
        $childBar->standardButton('catsview', 'Categories', 'songs.toCats')->listCheck(false)->icon('far fa-folder-open') ;
        $childBar->standardButton('tagsview', 'Tags', 'songs.toTags')->listCheck(false)->icon('fas fa-tags') ;
        
        if ($this->state->get('filter.status') == -2 && $canDo->get('core.delete')) {
            $toolbar->delete('songs.delete', 'JTOOLBAR_EMPTY_TRASH')
            ->message('JGLOBAL_CONFIRM_DELETE')
            ->listCheck(true);
        }
                
        if ($canDo->get('core.edit.state')) {
            // Add a batch button
            $toolbar->popupButton('batch', 'JTOOLBAR_BATCH')
            ->selector('collapseModal')
            ->listCheck(true);                        
        }
        
        if ($canDo->get('core.admin')) {
            //$toolbar->preferences('com_xbmusic');
            ToolbarHelper::preferences('com_xbmusic');
        }
        
        $toolbar->help('xbMusic:Songs',false,'https://crosborne.uk/xbmusic/doc#songs');
        
    }
    
    
}