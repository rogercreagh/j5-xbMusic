<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Model/TaginfoModel.php
 * @version 0.0.52.5 2nd June 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2025
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Model;

defined('_JEXEC') or die;


use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ItemModel;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbcommonHelper;

class TaginfoModel extends ItemModel {
    
    protected function populateState() {
        $app = Factory::getApplication();        
        // Load state from the request.
        $id = $app->input->getInt('id');
        $this->setState('taginfo.id', $id);
        
    }
    
    public function getItem($id = null) {
        if (!isset($this->item) || !is_null($id)) {
            
            $id    = is_null($id) ? $this->getState('taginfo.id') : $id;
            $db = $this->getDatabase();
            $query = $db->getQuery(true);
            $query->select('a.id AS id, a.path AS path, a.title AS title, a.description AS description, a.alias AS alias, a.level, a.parent_id, a.note As note, a.metadata AS metadata, a.published AS status' );
            //			$query->select('(SELECT COUNT(*) FROM #__contentitem_tag_map AS mb WHERE mb.type_alias='.$db->quote('com_xbfilms.film').' AND mb.tag_id = t.id) AS bcnt');
            $query->select('(SELECT COUNT(*) FROM #__contentitem_tag_map AS al WHERE al.type_alias='.$db->quote('com_xbmusic.album').' AND al.tag_id = a.id) AS albumcnt');
            $query->select('(SELECT COUNT(*) FROM #__contentitem_tag_map AS ar WHERE ar.type_alias='.$db->quote('com_xbmusic.artist').' AND ar.tag_id = a.id) AS artistcnt');
            $query->select('(SELECT COUNT(*) FROM #__contentitem_tag_map AS pl WHERE pl.type_alias='.$db->quote('com_xbmusic.playlist').' AND pl.tag_id = a.id) AS playlistcnt');
            $query->select('(SELECT COUNT(*) FROM #__contentitem_tag_map AS sg WHERE sg.type_alias='.$db->quote('com_xbmusic.song').' AND sg.tag_id = a.id) AS songcnt');
            $query->select('(SELECT COUNT(*) FROM #__contentitem_tag_map AS tk WHERE tk.type_alias='.$db->quote('com_xbmusic.track').' AND tk.tag_id = a.id) AS trackcnt');
            $query->select('(SELECT COUNT(*) FROM #__contentitem_tag_map AS ma WHERE ma.type_alias NOT LIKE '.$db->q('com_xbmusic%').' AND ma.tag_id = a.id) AS othercnt ');
            $query->from('#__tags AS a');
            $query->where('a.id = '.$id);
            
            try {
                $db->setQuery($query);
                $this->item = $db->loadObject();
            } catch (\Exception $e) {
                $dberr = $e->getMessage();
                Factory::getApplication()->enqueueMessage($dberr.'<br />Query: '.$query, 'error');
            }

            if ($this->item) {
                $item = &$this->item;
                //get titles and ids of tagged items
                
                $item->albums = ($item->albumcnt > 0) ? $this->getTagMusicItems($item->id, 'album') : '';
                $item->artists = ($item->artistcnt > 0) ? $this->getTagMusicItems($item->id, 'artist') : '';
                $item->playlists = ($item->playlistcnt > 0) ? $this->getTagMusicItems($item->id, 'playlist') : '';
                $item->songs = ($item->songcnt > 0) ? $this->getTagMusicItems($item->id, 'song') : '';
                $item->tracks = ($item->trackcnt > 0) ? $this->getTagMusicItems($item->id, 'track') : '';
                $item->others = $this->getTagOtherItems($item->id);
                $item->children = XbcommonHelper::getTagChildren($item->path);
                $item->parent_title = ($item->parent_id > 1) ? XbcommonHelper::getTag($item->parent_id)->title : '';
            }
            
            return $this->item;
        } //endif item set
    } //end getItem()
    
    protected function getTagMusicItems(int $tagid, string $item) {
        $table = '#__xbmusic_'.$item.'s';
        $type_alias = 'com_xbmusic.'.$item;
        $title = ($table == '#__xbmusic_artists') ? 'name' : 'title';
        $db = $this->getDatabase();
        $query = $db->getQuery(true);
        $query->select('a.id AS id, a.'.$title.' AS title');
        $query->from($db->qn($table).' AS a');
        $query->join('LEFT','#__contentitem_tag_map AS b ON b.content_item_id = a.id');
        $query->where('b.type_alias = '.$db->q($type_alias));
        $query->where('b.tag_id = '.$db->q($tagid));
        $query->order('a.title');
        $db->setQuery($query);
        return $db->loadAssocList();       
    }
    
    protected function getTagOtherItems(int $tagid) {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);
        $query->select('m.type_alias AS type_alias, m.core_content_id, c.core_title AS core_title');
        $query->from('#__contentitem_tag_map AS m');
        $query->join('LEFT','#__ucm_content AS c ON m.core_content_id = c.core_content_id');
        $query->where('m.tag_id = '.$db->q($tagid));
        $query->where('m.type_alias NOT LIKE ('.$db->q('com_xbmusic%').')');
        $query->order('m.type_alias, c.core_title');
        $db->setQuery($query);
        return $db->loadObjectList();       
    }

    
}
