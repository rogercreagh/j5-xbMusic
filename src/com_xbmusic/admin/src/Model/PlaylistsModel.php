<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Model/PlaylistsModel.php
 * @version 0.0.50.2 4th April 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Model;

defined('_JEXEC') or die;


use Joomla\CMS\Factory;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Utilities\ArrayHelper;
//use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Table\Table;
//use Crosborne\Component\Xbmusic\Administrator\Helper\XbmusicHelper;

class PlaylistsModel extends ListModel {
    
    public function __construct($config = array())
    {
        if (empty($config['filter_fields']))
        {
            $config['filter_fields'] = array(
                'id', 'a.id',
                'title', 'a.title',
                'alias', 'a.alias',
                'checked_out', 'a.checked_out',
                'checked_out_time', 'a.checked_out_time',
                'catid', 'a.catid', 'category_title',
                'status', 'a.status',
                'created', 'a.created',
                'modified', 'a.modified',
                'created_by', 'a.created_by',
                'created_by_alias', 'a.created_by_alias',
                'ordering', 'a.ordering',
                'category_id', 'level'
            );
            
        }
        
        parent::__construct($config);
    }
    
    protected function populateState($ordering = 'a.id', $direction = 'desc')
    {
        $app = Factory::getApplication();
        
        // Adjust the context to support modal layouts.
        if ($layout = $app->input->get('layout'))
        {
            $this->context .= '.' . $layout;
        }
        
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);
        
        $published = $this->getUserStateFromRequest($this->context . '.filter.status', 'filter_status', '');
        $this->setState('filter.published', $published);
        
        $level = $this->getUserStateFromRequest($this->context . '.filter.level', 'filter_level');
        $this->setState('filter.level', $level);
        
        
        $categoryId = $this->getUserStateFromRequest($this->context . '.filter.category_id', 'filter_category_id');
//        $artlist        = $this->getUserStateFromRequest($this->context . '.filter.artlist', 'filter_artlist', '1');
//        $scfilt        = $this->getUserStateFromRequest($this->context . '.filter.scfilt', 'filter_scfilt', '');
        
        $formSubmited = $app->input->post->get('form_submited');
        if ($formSubmited)
        {
//            $artlist = $app->input->post->get('artlist');
//            $this->setState('filter.artlist', $artlist);
            
            $categoryId = $app->input->post->get('category_id');
            $this->setState('filter.category_id', $categoryId);
            
//            $scfilt = $app->input->post->get('scfilt');
//            $this->setState('filter.artlist', $scfilt);
        }
        
        // List state information.
        parent::populateState($ordering, $direction);
        
    }
    
    protected function getStoreId($id = '')
    {
        // Compile the store id.
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . serialize($this->getState('filter.access'));
        $id .= ':' . $this->getState('filter.status');
        $id .= ':' . serialize($this->getState('filter.category_id'));
        
        return parent::getStoreId($id);
    }
    
    protected function getListQuery() {
        
        // Create a new query object.
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);
        $app = Factory::getApplication();
        $user  = $app->getIdentity();
        
        // Select the required fields from the table.
        $query->select(
            $this->getState(
                'list.select',
                'DISTINCT a.id, a.title, a.alias, a.description, '
                    .'a.az_plid, a.az_name, a.az_type,'
                    .'a.scheduledcnt, publicschd,'
                    .'a.checked_out, a.checked_out_time, a.catid, '
                    .'a.status, a.access, a.created, a.created_by, a.created_by_alias, '
                    .'a.modified, a.modified_by, a.ordering, '
                    .'a.note, st.title AS st_name, st.az_apiname AS az_apiname, st.az_url AS st_url'
                )
            );
        $query->select('(SELECT COUNT(DISTINCT(tk.id)) FROM #__xbmusic_trackplaylist AS tk WHERE tk.playlist_id = a.id) AS trkcnt');
        $query->from('#__xbmusic_playlists AS a');
        
        // Join azstations
        $query->select('st.title AS azstation' )
        ->join('LEFT', '#__xbmusic_azstations AS st ON st.id = a.db_stid');
        
        // Join over the users for the checked out user.
        $query->select('uc.name AS editor')
        ->join('LEFT', '#__users AS uc ON uc.id=a.checked_out');
        
        // Join over the categories.
        $query->select('c.title AS category_title, c.created_user_id AS category_uid, c.level AS category_level'.
            ',c.path AS category_path')
            ->join('LEFT', '#__categories AS c ON c.id = a.catid');
            
        // Join over the parent categories.
        $query->select('parent.title AS parent_category_title, parent.id AS parent_category_id,
						parent.created_user_id AS parent_category_uid, parent.level AS parent_category_level')
						->join('LEFT', '#__categories AS parent ON parent.id = c.parent_id');
						
		// Filter by access level.
		$access = $this->getState('filter.access');
		
		if (is_numeric($access)) {
		    $query->where('a.access = ' . (int) $access);
		} elseif (is_array($access)) {
		    $access = ArrayHelper::toInteger($access);
		    $access = implode(',', $access);
		    $query->where('a.access IN (' . $access . ')');
		}
		
		// Filter by access level on categories.
		if (!$user->authorise('core.admin')) {
		    $groups = implode(',', $user->getAuthorisedViewLevels());
		    $query->where('a.access IN (' . $groups . ')');
		    $query->where('c.access IN (' . $groups . ')');
		}
		
		// Filter by published state
		$status = $this->getState('filter.status');
		
		if (is_numeric($status)) {
		    $query->where('a.status = ' . (int) $status);
		} elseif ($status === '') {
		    $query->where('(a.status = 0 OR a.status = 1)');
		}
		
		// Filter by categories and by level
		$categoryId = $this->getState('filter.category_id', array());
		$level = $this->getState('filter.level');
		
		if (!is_array($categoryId)) {
		    $categoryId = $categoryId ? array($categoryId) : array();
		}
		
		// Case: Using both categories filter and by level filter
		if (count($categoryId)) {
		    $categoryId = ArrayHelper::toInteger($categoryId);
		    $categoryTable = Table::getInstance('Category', 'JTable');
		    $subCatItemsWhere = array();
		    
		    foreach ($categoryId as $filter_catid) {
		        $categoryTable->load($filter_catid);
		        $subCatItemsWhere[] = '(' .
						        ($level ? 'c.level <= ' . ((int) $level + (int) $categoryTable->level - 1) . ' AND ' : '') .
						        'c.lft >= ' . (int) $categoryTable->lft . ' AND ' .
						        'c.rgt <= ' . (int) $categoryTable->rgt . ')';
		    }
		    
		    $query->where('(' . implode(' OR ', $subCatItemsWhere) . ')');
		} elseif ($level) {  // Case: Using only the by level filter
		    $query->where('c.level <= ' . (int) $level);
		} // endif $categoryid
		
		// Filter by search in title.
		$search = $this->getState('filter.search');
		
		if (!empty($search))
		{
		    if (stripos($search, 'id:') === 0)
		    {
		        $query->where('a.id = ' . (int) substr($search, 3));
		    }
		    elseif (stripos($search, 'desc:') === 0)
		    {
		        $search = $db->quote('%' . $db->escape(substr($search, 8), true) . '%');
		        $query->where('(a.desc LIKE ' . $search . ')');
		    }
		    elseif (stripos($search, 'note:') === 0)
		    {
		        $search = $db->quote('%' . $db->escape(substr($search, 8), true) . '%');
		        $query->where('(a.note LIKE ' . $search . ')');
		    }
		    else
		    {
		        $search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
		        $query->where('(a.title LIKE ' . $search . ' OR a.alias LIKE ' . $search . ')');
		    }
		} // endif $search 
		
		//filter by tags
		$tagfilt = '';
		//is tagid in query string. If so use it and ignore tag filters. Negative tagid to exclude tag
		$tagId = (int) $app->getUserStateFromRequest('tagid', 'tagid','');
		$app->setUserState('tagid', '');
		if (!empty($tagId)) {
		    $tagfilt = array(abs($tagId));
		    $taglogic = $tagId>0 ? 0 : 2;
		} else {
		    $tagfilt = $this->getState('filter.tagfilt');
		    $taglogic = $this->getState('filter.taglogic',0);  //0=ANY 1=ALL 2= None
		}
		if (!is_array($tagfilt)) {
		    $tagfilt = array($tagfilt);
    	}
    	if ($tagfilt[0] > 0) {
    	    $tagfilt = ArrayHelper::toInteger($tagfilt);
    	    $subquery = '(SELECT tmap.tag_id AS tlist FROM #__contentitem_tag_map AS tmap
                    WHERE tmap.type_alias = '.$db->quote('com_xbmusic.album').'
                    AND tmap.content_item_id = a.id)';
    	    switch ($taglogic) {
    	        case 1: //all tags must be matched
    	            for ($i = 0; $i < count($tagfilt); $i++) {
    	                $query->where($tagfilt[$i].' IN '.$subquery);
    	            }
    	            break;
    	        case 2: //none of the tags must be matched
    	            for ($i = 0; $i < count($tagfilt); $i++) {
    	                $query->where($tagfilt[$i].' NOT IN '.$subquery);
    	            }
    	            break;
    	        case 0: //any match will do
    	            if (count($tagfilt) == 1) {
    	                $query->where($tagfilt[0].' IN '.$subquery);
    	            } else {
    	                $tagIds = implode(',', $tagfilt);
    	                if ($tagIds) {
    	                    $subQueryAny = '(SELECT DISTINCT content_item_id AS cid FROM #__contentitem_tag_map
                                    WHERE tag_id IN ('.$tagIds.') AND type_alias = '.$db->quote('com_xbmusic.album').')';
    	                    $query->innerJoin('(' . (string) $subQueryAny . ') AS tm ON tm.cid = a.id');
    	                }
    	            } //end else
    	            break;
            } //endswitch
    	}
        // Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', 'a.id');
		$orderDirn = $this->state->get('list.direction', 'DESC');
		
		if ($orderCol=='a.ordering') {
		    $orderCol='category_title '.$orderDirn.', a.ordering';
		}
		
		$query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));
		
		return $query;
    } //end getlistquery
    
    public function getItems() {
        $items  = parent::getItems();
        if ($items) {
            $tagsHelper = new TagsHelper;
            foreach ($items as $item) {
                
                $item->tags = $tagsHelper->getItemTags('com_xbmusic.playlist' , $item->id);     
                
                $item->tracks = $this->getPlaylistTracks($item->id);
                $item->schedules = $this->getPlaylistSchedule($item->id);
            } //end foreach
        } //endif items
        return $items;
        
    } // end getItems
    
    public function getPlaylistTracks($pid) {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);
        $query->select('t.id AS trackid, t.title AS tracktitle, t.sortartist AS artistname, pt.listorder AS ordering');
        $query->join('LEFT','#__xbmusic_trackplaylist AS pt ON pt.track_id = t.id');
        $query->from('#__xbmusic_tracks AS t');
        $query->where('pt.playlist_id = '.$pid);
        $query->order('pt.listorder, t.title ASC');
        $db->setQuery($query);
        $res = $db->loadAssocList();
        return $res;
    }
    
    public function getPlaylistSchedule($pid) {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);
        $query->select('s.id AS schdbid, s.az_starttime, s.az_endtime, s.az_startdate, s.az_enddate, s.az_days, s.az_loop');
        $query->from('#__xbmusic_azschedules AS s');
        $query->where('s.dbplid = '.$pid);
        $query->order('s.az_startdate, s.az_starttime ASC');
        $db->setQuery($query);
        return $db->loadAssocList();
    }
    
}
