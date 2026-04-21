<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Model/ArtistsModel.php
 * @version 0.0.63.0 21st April 2026
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2026
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
use Crosborne\Component\Xbmusic\Administrator\Helper\XbmusicHelper;

class ArtistsModel extends ListModel {
    
    public function __construct($config = array())
    {
        if (empty($config['filter_fields']))
        {
            $config['filter_fields'] = array(
                'id', 'a.id',
                'name', 'a.name',
                'sortname','a.sortname',
                'alias', 'a.alias','type','a.type',
                'checked_out', 'a.checked_out',
                'checked_out_time', 'a.checked_out_time',
                'catid', 'a.catid', 'category_title',
                'status', 'a.status',
                'created', 'a.created',
                'modified', 'a.modified',
                'created_by', 'a.created_by',
                'created_by_alias', 'a.created_by_alias',
                'ordering', 'a.ordering',
                'category_id','tagfilt'
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
        
        $filt = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search','');
        $this->setState('filter.search', $filt);
        
        $filt = $this->getUserStateFromRequest($this->context . '.filter.status', 'filter_status', '');
        $this->setState('filter.published', $filt);
        
        $filt = $this->getUserStateFromRequest($this->context . '.filter.typefilter', 'filter_filter_typefilter', '');
        $this->setState('filter.typefilter', $filt);
        
        $filt = $this->getUserStateFromRequest($this->context . '.filter.tagfilt', 'filter_filter_tagfilt', '');
        $this->setState('filter.tagfilt', $filt);
        
        $filt = $this->getUserStateFromRequest($this->context . '.filter.taglogic', 'filter_filter_taglogic', '');
        $this->setState('filter.taglogic', $filt);
        
        $categoryId = $this->getUserStateFromRequest($this->context . '.filter.category_id', 'filter_category_id','');
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
                'DISTINCT a.id, a.name, a.alias, a.description, a.imgurl, '
                    .'a.type, a.sortname,'
                    .'a.ext_links, a.checked_out, a.checked_out_time, a.catid, '
                    .'a.status, a.access, a.created, a.created_by, a.created_by_alias, '
                    .'a.modified, a.modified_by, a.ordering, '
                    .'a.note'
                )
            );
        $query->select('(SELECT COUNT(DISTINCT(tk.id)) FROM #__xbmusic_trackartist AS tk WHERE tk.artist_id = a.id) AS trkcnt');
        $query->from('#__xbmusic_artists AS a');
                        
        // Join over the users for the checked out user.
        $query->select('uc.name AS editor')
        ->join('LEFT', '#__users AS uc ON uc.id=a.checked_out');
        
        // Join over the categories.
        $query->select('c.title AS category_title, c.created_user_id AS category_uid'.
            ',c.path AS category_path')
            ->join('LEFT', '#__categories AS c ON c.id = a.catid');
            
        // Join over the parent categories.
        $query->select('parent.title AS parent_category_title, parent.id AS parent_category_id,'.
						'parent.created_user_id AS parent_category_uid, parent.level AS parent_category_level')
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
		
		// filter by type
		$typefilter = $this->getState('filter.typefilter');
		if ($typefilter == 0) {
		    $query->where($db->qn('a.type').' IS NULL');
		} elseif ($typefilter > 0) {
		    $query->where($db->qn('a.type').' = '.$typefilter);
		}
		
		// Filter by categories
		$categoryId = $this->getState('filter.category_id', array());
		
		if (is_array($categoryId) && (!empty($categoryId))) $categoryId = $categoryId[0];
		if (is_numeric($categoryId)) $query->where($db->quoteName('a.catid') . ' = ' . (int) $categoryId);
		
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
		        $query->where('(a.name LIKE ' . $search . ' OR a.alias LIKE ' . $search . ')');
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
                    WHERE tmap.type_alias = '.$db->quote('com_xbmusic.artist').'
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
                                    WHERE tag_id IN ('.$tagIds.') AND type_alias = '.$db->quote('com_xbmusic.artist').')';
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
                if($item->ext_links) $item->ext_links = json_decode($item->ext_links);
                $item->ext_links_list ='';
                $item->ext_links_cnt = 0;
                if(is_object($item->ext_links)) {
                    $item->ext_links_cnt = count((array)$item->ext_links);
                    if ($item->ext_links_cnt > 0) {
                        $item->ext_links_list ='<ul>';
                        foreach($item->ext_links as $lnk) {
                            $item->ext_links_list .= '<li><a href="'.$lnk->link_url.'" target="_blank">'.$lnk->link_text.'</a></li>';
                        }
                        $item->ext_links_list = $item->ext_links_list.'</ul>';                        
                    }
                } //end if is_object
                $item->singles = $this->getArtistSingles($item->id);
                $item->albums = XbmusicHelper::getArtistAlbums($item->id);
                $item->songs = XbmusicHelper::getArtistSongs($item->id);
                
                $item->tags = $tagsHelper->getItemTags('com_xbmusic.artist' , $item->id);     
                
            } //end foreach
        } //endif items
        return $items;
        
    } // end getItems
    
    public function getArtistSingles($aid) {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);
        $query->select('t.id AS trackid, t.title AS tracktitle, t.imgurl, t.rel_date');
        $query->join('LEFT','#__xbmusic_trackartist AS at ON at.track_id = t.id');
        $query->from('#__xbmusic_tracks AS t');
        $query->where('t.album_id = 0 AND at.artist_id = '.$aid);
        $query->order('t.rel_date, t.title ASC');
        $db->setQuery($query);
        return $db->loadAssocList();
    }


}
