<?php 
/*******
 * @package xbMusic
 * @filesource admin/src/View/Dashboard/HtmlView.php
 * @version 0.0.6.12 7th June 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\View\Dashboard;

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
    
    public function display($tpl = null) {
        $app = Factory::getApplication();
        $taghelper = new TagsHelper();
        $params = ComponentHelper::getParams('com_xbmusic');
        $notres = '<span class="xbbadge badge-lt-green">not restricted</span>';
        $catbadge = '<span class="xbbadge badge-cat">';        
        
        $rootcat_album = $params->get('rootcat_album',0);
        if ($rootcat_album == 0) {
            $defcat_album = $params->get('defcat_album',0);           
        } else {
            $defcat_album = $params->get('defrescat_album',0);
        }
        $this->rootcat_album = ($rootcat_album == 0) ? $notres : $catbadge.XbmusicHelper::getCat($rootcat_album)->title.'</span>';
        $this->defcat_album = ($defcat_album == 0) ? 'Uncategorised' : XbmusicHelper::getCat($defcat_album)->title;

        $albumtagparents = $params->get('albumtagparents');
        if (is_array($albumtagparents)) {
            $albumtagparents = $taghelper->getTagNames($albumtagparents);
            $this->albumtagparents = ''; //'<i>'.Text::_('XBMUSIC_NO_GROUPS_DEFINED').'</i>: ';
            foreach ($albumtagparents as $name) {
                $this->albumtagparents .= '<span class="xbbadge badge-tag xbpl10">'.$name.'</span>';
            }
        } else {
            $this->albumtagparents = Text::_('XBMUSIC_ALL_TAGS_ALLOWED');
        }
        
        $genreparam = (int) $params->get('genrecattag',0);
        $artalb = (int) $params->get('addgenre',0);
        switch ($genreparam) {
            case 1:
                $this->id3genreuse = Text::_('as Category');
                break;  
            case 2:
                $this->id3genreuse = Text::_('as Tag');
                break;
            case 3:
                $this->id3genreuse = Text::_('Category &amp; Tag');
                break;
            default:
                $this->id3genreuse = Text::_('not used');
                break;
        }
        if ($genreparam > 1) {
            switch ($artalb) {
                case 1:
                    $this->id3genreuse .= ', '.Text::_('also tag Song');
                    break;
                case 2:
                    $this->id3genreuse .= ', '.Text::_('also tag Album');
                    break;
                case 3:
                    $this->id3genreuse .= ', '.Text::_('also tag Song &amp; Album');
                    break;                       
                default:
                break;
            }               
        }
        //==========================
        
        $rootcat_artist = $params->get('rootcat_artist',0);
        if ($rootcat_artist == 0) {
            $defcat_artist = $params->get('defcat_artist',0);
        } else {
            $defcat_artist = $params->get('defrescat_artist',0);
        }
        $this->rootcat_artist = ($rootcat_artist == 0) ? $notres : $catbadge.XbmusicHelper::getCat($rootcat_artist)->title.'</span>';
        $this->defcat_artist = ($defcat_artist == 0) ? 'Uncategorised' : XbmusicHelper::getCat($defcat_artist)->title;
        
        $artisttagparents = $params->get('artisttagparents');
        if (is_array($artisttagparents)) {
            $artisttagparents = $taghelper->getTagNames($artisttagparents);
            $this->artisttagparents =  ''; //'<i>'.Text::_('XBMUSIC_CHILDREN_OF').'</i>:';
            foreach ($artisttagparents as $name) {
                $this->artisttagparents .= '<span class="xbbadge badge-tag xbpl10">'.$name.'</span>';
            }
        } else {
            $this->artisttagparents = Text::_('XBMUSIC_NO_GROUPS_DEFINED');
        }
        //==========================
        
        $rootcat_plist = $params->get('rootcat_plist',0);
        if ($rootcat_plist == 0) {
            $defcat_plist = $params->get('defcat_plist',0);
        } else {
            $defcat_plist = $params->get('defrescat_plist',0);
        }
        $this->rootcat_plist = ($rootcat_plist == 0) ? $notres : $catbadge.XbmusicHelper::getCat($rootcat_plist)->title.'</span>';
        $this->defcat_plist = ($defcat_plist == 0) ? 'Uncategorised' : XbmusicHelper::getCat($defcat_plist)->title;
        
        $plisttagparents = $params->get('plisttagparents');
        if (is_array($plisttagparents)) {
            $plisttagparents = $taghelper->getTagNames($plisttagparents);
            $this->plisttagparents = ''; //'<i>'.Text::_('XBMUSIC_CHILDREN_OF').'</i>: ';
            foreach ($plisttagparents as $name) {
                $this->plisttagparents .= '<span class="xbbadge badge-tag xbpl10">'.$name.'</span>';
            }
        } else {
            $this->plisttagparents = Text::_('XBMUSIC_NO_GROUPS_DEFINED');
        }
        //==========================
        
        $rootcat_song = $params->get('rootcat_song',0);
        if ($rootcat_song == 0) {
            $defcat_song = $params->get('defcat_song',0);
        } else {
            $defcat_song = $params->get('defrescat_song',0);
        }
        $this->rootcat_song = ($rootcat_song == 0) ? $notres : $catbadge.XbmusicHelper::getCat($rootcat_song)->title.'</span>';
        $this->defcat_song = ($defcat_song == 0) ? 'Uncategorised' : XbmusicHelper::getCat($defcat_song)->title;
        
        $songtagparents = $params->get('songtagparents');
        if (is_array($songtagparents)) {
            $songtagparents = $taghelper->getTagNames($songtagparents);
            $this->songtagparents = ''; //'<i>'.Text::_('XBMUSIC_CHILDREN_OF').'</i>: ';
            foreach ($songtagparents as $name) {
                $this->songtagparents .= '<span class="xbbadge badge-tag xbpl10">'.$name.'</span>';
            }
        } else {
            $this->songtagparents = Text::_('XBMUSIC_NO_GROUPS_DEFINED');
        }
        //==========================
        
        $rootcat_track = $params->get('rootcat_track',0);
        if ($rootcat_track == 0) {
            $defcat_track = $params->get('defcat_track',0);
        } else {
            $defcat_track = $params->get('defrescat_track',0);
        }
        $this->rootcat_track = ($rootcat_track == 0) ? $notres : $catbadge.XbmusicHelper::getCat($rootcat_track)->title.'</span>';
        $this->defcat_track = ($defcat_track == 0) ? 'Uncategorised' : XbmusicHelper::getCat($defcat_track)->title;
        
        $tracktagparents = $params->get('tracktagparents');
        if (is_array($tracktagparents)) {
            $tracktagparents = $taghelper->getTagNames($tracktagparents);
            $this->tracktagparents = ''; //'<i>'.Text::_('XBMUSIC_CHILDREN_OF').'</i>: ';
            foreach ($tracktagparents as $name) {
                $this->tracktagparents .= '<span class="xbbadge badge-tag xbpl10">'.$name.'</span>';
            }
        } else {
            $this->tracktagparents = Text::_('XBMUSIC_NO_GROUPS_DEFINED');
        }
        //==========================
        
        $changelog = $this->get('Changelog');
        
        $this->xmldata = Installer::parseXMLInstallFile(JPATH_COMPONENT_ADMINISTRATOR . '/xbmusic.xml');
        $this->client = $this->get('Client');
        $this->albumcnts = $this->get('AlbumCnts');
        $this->artistcnts = $this->get('ArtistCnts');
        $this->playlistcnts = $this->get('PlaylistCnts');
        $this->songcnts = $this->get('SongCnts');
        $this->trackcnts = $this->get('TrackCnts');
        $this->catcnts = $this->get('CatCnts');
        $this->tagcnts = $this->get('TagCnts');
        
        
        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }    
        $this->updatable = false;
         // format the changelog
         $this->changelog = '<div style="margin:10px 0;">';
         if ((!is_array($changelog)) || (!$changelog)) {
             $this->changelog .= Text::_('XB_CHANGELOG_NOT_FOUND');
         } else {
             if (array_key_exists('element',$changelog['changelog'])) {
                 //we have only one entry, need to demote it a level
                 $changelog = array('changelog'=>array($changelog['changelog']));
             }
             foreach ($changelog['changelog'] as $i=>$log) {
                 $this->titleok = false;
                 $this->changelog .= '<div class="xbmt10 ';
                 $iscurrent = (version_compare($log['version'], $this->xmldata['version']));
                 $this->changelog .= ($iscurrent === 0) ? 'xbbgltgreen' : '';
                 if ($iscurrent === 1) {
                    $this->changelog .=  'xbbgltred';
                    $this->updatable = true;
                 }
                 $this->changelog .= ' " style="padding:5px 20px;">';
                 $this->changelog .= '<b>Version '.$log['version'].'</b> ';
                 if (key_exists('date',$log)) $this->changelog .= '&nbsp;&nbsp;<i>Updated</i>:&nbsp;'.$log['date'];
                 if (key_exists('title',$log)) {
                     $this->changelog .= '<h3>'.$log['title'].'</h3>';
                     $this->titleok = true;
                 }
                 $this->changelog .= '</div>';
                 $this->colours = array('security'=>'bg-danger', 'fix'=>'bg-dark','language'=>'bg-primary','addition'=>'bg-success',
                    'change'=>'bg-warning text-dark','remove'=>'bg-secondary','note'=>'bg-info'
                 );
                 foreach ($log as $key=>$items) {
                     if (is_array($items)) {
                         $this->changelog .= $this->itemstr($items, $key);
                     }
                 }
             }
         }
         $this->changelog .= '</div>';
        
        $this->addToolbar();
        
        return parent::display($tpl);
    }
    
    private function itemstr($items, $tag) {
        if (empty($items)) return '';
        $ans =  '<div class="changelog"><div class="changelog__item"><div class="changelog__tag">';
        $ans .=  '<span class="badge ';
        if (key_exists($tag, $this->colours)) {
            $ans .=  $this->colours[$tag];
            $ans .=  '">'. Text::_('XB_CHANGELOG_'.$tag) .'</span>';
        } else {
            $ans .= 'badge-ltblue">'.$tag.'</span>';
        }
        $ans .=  '</div>';
        $ans .=  '<div class="changelog__list"><ul>';
        if (is_array($items['item'])) {
            foreach ($items['item'] as $item) {
                if ((!$this->titleok) || !(str_starts_with($item, '<h3>'))) {
                    $ans .= '<li>'.$item.'</li>';
                }
            }
        } else {
            $ans .= $items['item'];
        }
        $ans .=  '</li></ul>';
        $ans .=  '</div></div></div>';
        return $ans;
    }
    
    protected function addToolbar()
    {
        // Get the toolbar object instance
        $toolbar = Toolbar::getInstance('toolbar');        
        //$toolbar = Factory::getContainer()->get(ToolbarFactoryInterface::class)->createToolbar($name);
        
        ToolbarHelper::title(Text::_('XBMUSIC_ADMIN_DASHBOARD_TITLE'), 'fas fa-info-circle');
        
        $dropdown = $toolbar->dropdownButton('views')
        ->text('Other Views')
        ->toggleSplit(false)
        ->icon('icon-ellipsis-h')
        ->buttonClass('btn btn-action')
        ->listCheck(false);       
        $childBar = $dropdown->getChildToolbar();
        $childBar->standardButton('albumsview', 'XBMUSIC_ALBUMS', 'dashboard.toAlbums')->listCheck(false)->icon('fas fa-compact-disc') ;
        $childBar->standardButton('artistsview', 'XBMUSIC_ARTISTS', 'dashboard.toArtists')->listCheck(false)->icon('fas fa-users-line') ;
        $childBar->standardButton('playlistsview', 'XBMUSIC_PLAYLISTS', 'dashboard.toPlaylists')->listCheck(false)->icon('fas fa-headphones') ;
        $childBar->standardButton('songsview', 'XBMUSIC_SONGS', 'dashboard.toSongs')->listCheck(false)->icon('fas fa-music') ;
        $childBar->standardButton('trackview', 'XBMUSIC_TRACKS', 'dashboard.toTracks')->listCheck(false)->icon('fas fa-guitar') ;
        $childBar->standardButton('catsview', 'XB_CATEGORIES', 'dashboard.toCats')->listCheck(false)->icon('far fa-folder-open') ;
        $childBar->standardButton('tagsview', 'XB_TAGS', 'dashboard.toTags')->listCheck(false)->icon('fas fa-tags') ;
        
        
        $canDo = ContentHelper::getActions('com_xbmusic');           
        if ($canDo->get('core.admin')) {
            //$toolbar->preferences('com_xbmusic');
            ToolbarHelper::preferences('com_xbmusic');
        }
        
        $toolbar->help('Dasboard',false,'https://crosborne.uk/xbmusic/doc#dashboard');
        
    }
        
}
