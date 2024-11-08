<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Model/CatinfoModel.php
 * @version 0.0.18.8 8th November 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Model;

defined('_JEXEC') or die;


use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ItemModel;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbcommonHelper;

class CatinfoModel extends ItemModel {
    
    protected function populateState() {
        $app = Factory::getApplication();        
        // Load state from the request.
        $id = $app->input->getInt('id');
        $this->setState('catinfo.id', $id);
        
    }
    
    public function getItem($id = null) {
        if (!isset($this->item) || !is_null($id)) {
            
            $id    = is_null($id) ? $this->getState('catinfo.id') : $id;
            $db = $this->getDatabase();
            $query = $db->getQuery(true);
            $query->select('c.id AS id, c.path AS path, c.title AS title, c.description AS description, c.alias AS alias, c.level, c.parent_id, c.note As note, c.metadata AS metadata' );
            $query->select('(SELECT COUNT(*) FROM #__xbmusic_albums AS al WHERE al.catid = c.id) AS albumcnt');
            $query->select('(SELECT COUNT(*) FROM #__xbmusic_artists AS ar WHERE ar.catid = c.id) AS artistcnt');
            $query->select('(SELECT COUNT(*) FROM #__xbmusic_playlists AS pl WHERE pl.catid = c.id) AS playlistcnt');
            $query->select('(SELECT COUNT(*) FROM #__xbmusic_songs AS sg WHERE sg.catid = c.id) AS songcnt');
            $query->select('(SELECT COUNT(*) FROM #__xbmusic_tracks AS tk WHERE tk.catid = c.id) AS trackcnt');
            $query->from('#__categories AS c');
            $query->where('c.id = '.$id);
            
            try {
                $db->setQuery($query);
                $this->item = $db->loadObject();
            } catch (\Exception $e) {
                $dberr = $e->getMessage();
                Factory::getApplication()->enqueueMessage($dberr.'<br />Query: '.$query, 'error');
            }
            if ($this->item) {
                $item = &$this->item;
                //get titles and ids of maps, markers and track in this category
                if ($item->albumcnt > 0) {
                    $query->clear();
                    $query->select('b.id AS id, b.title AS title')
                        ->from('#__categories AS c');
                    $query->join('LEFT','#__xbmusic_albums AS b ON b.catid = c.id');
                    $query->where('c.id='.$item->id);
                    $query->order('b.title');
                    $db->setQuery($query);
                    $item->albums = $db->loadAssocList();
                } else {
                    $item->albums = '';
                }
                if ($item->artistcnt > 0) {
                    $query->clear();
                    $query->select('b.id AS id, b.name AS title')
                        ->from('#__categories AS c');
                    $query->join('LEFT','#__xbmusic_artists AS b ON b.catid = c.id');
                    $query->where('c.id='.$item->id);
                    $query->order('b.name');
                    $db->setQuery($query);
                    $item->artists = $db->loadAssocList();
                } else {
                    $item->artists = '';
                }
                if ($item->playlistcnt > 0) {
                    $query->clear();
                    $query->select('b.id AS id, b.title AS title')
                    ->from('#__categories AS c');
                    $query->join('LEFT','#__xbmusic_playlists AS b ON b.catid = c.id');
                    $query->where('c.id='.$item->id);
                    $query->order('b.title');
                    $db->setQuery($query);
                    $item->playlists = $db->loadAssocList();
                } else {
                    $item->playlists = '';
                }
                if ($item->songcnt > 0) {
                    $query->clear();
                    $query->select('b.id AS id, b.title AS title')
                    ->from('#__categories AS c');
                    $query->join('LEFT','#__xbmusic_songs AS b ON b.catid = c.id');
                    $query->where('c.id='.$item->id);
                    $query->order('b.title');
                    $db->setQuery($query);
                    $item->songs = $db->loadAssocList();
                } else {
                    $item->songs = '';
                }
                if ($item->trackcnt > 0) {
                    $query->clear();
                    $query->select('b.id AS id, b.title AS title')
                    ->from('#__categories AS c');
                    $query->join('LEFT','#__xbmusic_tracks AS b ON b.catid = c.id');
                    $query->where('c.id='.$item->id);
                    $query->order('b.title');
                    $db->setQuery($query);
                    $item->tracks = $db->loadAssocList();
                } else {
                    $item->tracks = '';
                }
                $item->children = XbcommonHelper::getCatChildren($item->path);
                $item->parent_title = ($item->parent_id > 1) ? XbcommonHelper::getCat($item->parent_id)->title : '';
            }
            
            return $this->item;
        } //endif item set
    } //end getItem()
    
}
