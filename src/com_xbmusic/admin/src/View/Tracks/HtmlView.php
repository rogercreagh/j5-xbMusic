<?php 
/*******
 * @package xbMusic
 * @filesource admin/src/View/Tracks/HtmlView.php
 * @version 0.0.58.5 16th October 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2025
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\View\Tracks;

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
use Crosborne\Component\Xbmusic\Administrator\Helper\XbmusicHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbazuracastHelper;
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

        Text::script('XBMUSIC_WAITING_SERVER');
        
        $this->items         = $this->get('Items');
        $this->pagination    = $this->get('Pagination');
        $this->state         = $this->get('State');
        $this->filterForm    = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');
               
        $this->params      = ComponentHelper::getParams('com_xbmusic');;
        $this->azuracast = $this->params->get('azuracast',0);
        
//         if ($this->params->get('use_xbmusic', 1)) {
//             $this->basemusicfolder = JPATH_ROOT.'/xbmusic/'; //.$this->params->get('xbmusic_subfolder','');
//         } else {
//             if (is_dir(trim($this->params->get('music_path','')))) {
//                 $this->basemusicfolder = trim($this->params->get('music_path'));
//             } else {
//                 $this->basemusicfolder = JPATH_ROOT.'/xbmusic/'; //xbmusic/ added
//             }
//         }
        $this->basemusicfolder = XbmusicHelper::$musicBase;
        
        $this->addToolbar();
        
        return parent::display($tpl);
    
    }

    protected function addToolbar()
    {
        // Get the toolbar object instance
        $toolbar = Toolbar::getInstance('toolbar');
        //$toolbar = Factory::getContainer()->get(ToolbarFactoryInterface::class)->createToolbar($name);
        
        ToolbarHelper::title(Text::_('XBMUSIC_ADMIN_TRACKS_TITLE'), 'fas fa-guitar');
        
        $canDo = ContentHelper::getActions('com_xbmusic');
        
        if ($canDo->get('core.create') || count($user->getAuthorisedCategories('com_xbmusic', 'core.create')) > 0)
        {
            ToolbarHelper::addNew('track.add');
        }
        
        if ($canDo->get('core.edit.state') ) {
            $dropdown = $toolbar->dropdownButton('status-group')
            ->text('JTOOLBAR_CHANGE_STATUS')
            ->toggleSplit(false)
            ->icon('icon-ellipsis-h')
            ->buttonClass('btn btn-action')
            ->listCheck(true);
            
            $childBar = $dropdown->getChildToolbar();
            
            $childBar->publish('track.publish')->listCheck(true);
            
            $childBar->unpublish('track.unpublish')->listCheck(true);
            
            $childBar->archive('track.archive')->listCheck(true);
            
            if ($this->state->get('filter.status') != -2) {
                $childBar->trash('track.trash');
            }
            $childBar->checkin('track.checkin');
                
        }
        
        if ($this->state->get('filter.status') == -2 && $canDo->get('core.delete')) {
            $toolbar->delete('track.delete', 'JTOOLBAR_EMPTY_TRASH')
            ->message('JGLOBAL_CONFIRM_DELETE')
            ->listCheck(true);
        }
                
        if ($canDo->get('core.edit.state')) {
            // Add a batch button
            $toolbar->popupButton('batch', 'JTOOLBAR_BATCH')
            ->selector('collapseModal')
            ->listCheck(true);                        
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
        $childBar->standardButton('songsview', 'XBMUSIC_SONGS', 'dashboard.toSongs')->listCheck(false)->icon('fas fa-music') ;
        $childBar->standardButton('catsview', 'XB_CATEGORIES', 'dashboard.toCats')->listCheck(false)->icon('fas fa-folder-tree') ;
        $childBar->standardButton('tagsview', 'XB_TAGLIST', 'dashboard.toTags')->listCheck(false)->icon('fas fa-tags') ;
        if ( $this->azuracast) {
            $stations = XbazuracastHelper::getStations();
            $childBar->standardButton('azuracastview', 'XBMUSIC_AZURACAST_STATIONS', '')
            ->listCheck(false)->icon('fas fa-broadcast-tower')
            ->onclick("showEl('azwaiter',Joomla.JText._('XBMUSIC_WAITING_SERVER'));
                Joomla.submitbutton('dashboard.toAzuracast')") ;
            foreach ($stations AS $key=>$station) {
                $childBar->linkButton('stationview'.$station['id'],'<span class="xbpl20">'.$station['title'].'</span>', '')
                    ->url('index.php?option=com_xbmusic&task=station.edit&id='.$station['id'])
                    ->listCheck(false)->icon('fas fa-radio');
            }
        }
        
        $childBar->standardButton('datamanview', 'XB_DATAMAN', 'dashboard.toDataman')->listCheck(false)->icon('icon-database') ;
        
        if ($canDo->get('core.admin')) {
            //$toolbar->preferences('com_xbmusic');
            ToolbarHelper::preferences('com_xbmusic');
        }
        
        $toolbar->help('xbMusic:Tracks',false,'https://crosborne.uk/xbmusic/doc#tracks');
        
    }
    
    
}