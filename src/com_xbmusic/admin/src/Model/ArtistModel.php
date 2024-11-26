<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Model/ArtistModel.php
 * @version 0.0.19.1 25th November 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
//use Joomla\CMS\Plugin\PluginHelper;
//use Joomla\CMS\String\PunycodeHelper;
//use Joomla\CMS\Table\Table;
//use Joomla\CMS\Table\TableInterface;
//use Joomla\CMS\UCM\UCMType;
//use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
//use Joomla\Database\ParameterType;
use Joomla\Filter\OutputFilter;
use Joomla\Registry\Registry;
//use Joomla\Component\Categories\Administrator\Helper\CategoriesHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbmusicHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbcommonHelper;
use \SimpleXMLElement;
//use Symfony\Component\Validator\Constraints\IsNull;

class ArtistModel extends AdminModel {
  
    public $typeAlias = 'com_xbmusic.artist';
    
    protected $xbmusic_batch_commands = array(
        'untag' => 'batchUntag',
    );
    
    public function batch($commands, $pks, $contexts) {
        $this->batch_commands = array_merge($this->batch_commands, $this->xbmusic_batch_commands);
        return parent::batch($commands, $pks, $contexts);
    } 
    
    protected function batchUntag($value, $pks, $contexts) {
        $taghelper = new TagsHelper();
        $message = 'tag:'.$value.' removed from artists :';
        foreach ($pks as $pk) {
            if ($this->user->authorise('core.edit', $contexts[$pk])) {
                $existing = $taghelper->getItemTags('com_xbmusic.artist', $pk, false);
                $oldtags = array_column($existing,'tag_id');
                $newtags = array();
                for ($i = 0; $i<count($oldtags); $i++) {
                    if ($oldtags[$i] != $value) {
                        $newtags[] = $oldtags[$i];
                    }
                }
                $params = array( 'id' => $pk, 'tags' => $newtags );
                
                if($this->save($params)){
                    $message .= ' '.$pk;
                }
            } else {
                $this->setError(Text::_('JLIB_APPLICATION_ERROR_BATCH_CANNOT_EDIT'));
                return false;
            }
            Factory::getApplication()->enqueueMessage($message);
        }
        return true;
    }

    public function delete(&$pks) {
        //first need to delete links to albums, artists, tracks
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        foreach ($pks as $pk) {
            $query->delete($db->qn('#__xbmusic_artistalbum'));
            $query->where($db->qn('artist_id').' = '.$db->q($pk));
            $db->setQuery($query);
            $db->execute();
            $query->clear('delete');
            $query->delete($db->qn('#__xbmusic_artisttrack'));
            $db->setQuery($query);
            $db->execute();
            $query->clear('delete');
            $query->delete($db->qn('#__xbmusic_artistsong'));
            $db->setQuery($query);
            $db->execute();
            $query->clear();
        }
        
        return parent::delete($pks);
    }
    
    protected function canDelete($record) {
        if (empty($record->id) || ($record->status != -2)) {
            return false;
        }
        
        return $this->getCurrentUser()->authorise('core.delete', 'com_xbmusic.artist.' . (int) $record->id);
    }
    
    protected function canEditState($record) {
        $user = $this->getCurrentUser();
        
        // Check for existing artist.
        if (!empty($record->id)) {
            return $user->authorise('core.edit.state', 'com_xbmusic.artist.' . (int) $record->id);
        }
        
        // New artist, so check against the category.
        if (!empty($record->catid)) {
            return $user->authorise('core.edit.state', 'com_xbmusic.category.' . (int) $record->catid);
        }
        
        // Default to component settings if neither artist nor category known.
        return parent::canEditState($record);
    }
    
    protected function prepareTable($table) {
        
        // Reorder the artists within the category so the new artist is first
        if (empty($table->id)) {
            $table->reorder('catid = ' . (int) $table->catid . ' AND status >= 0');
        }
    }
    
    public function getItem($pk = null) {
        if ($item = parent::getItem($pk)) {
            if (!empty($item->id)) {
                $tagsHelper = new TagsHelper();
                $item->tags = $tagsHelper->getTagIds($item->id, 'com_xbmusic.artist'); 
                $item->tracks = $this->getArtistTrackList($item->id);
                $item->albums = $this->getArtistAlbumList($item->id);
                $item->songs = $this->getArtistSongList($item->id);
                
                
                if ($item->type == 2) {
                    $item->groupmembers = XbmusicHelper::getGroupMembers($item->id);
                }
//                $item->albums = XbmusicHelper::getArtistAlbums($item->id);
                $item->singles = XbmusicHelper::getArtistSingles($item->id);
            }
        }        
        return $item;
    }
    
    public function getForm($data = [], $loadData = true) {
        $app  = Factory::getApplication();
        $params = ComponentHelper::getParams('com_xbmusic');
        
        // Get the form.
        $form = $this->loadForm('com_xbmusic.artist', 'artist', ['control' => 'jform', 'load_data' => $loadData]);
        
        if (empty($form)) {
            return false;
        }
        
        //dynamically add fields for any taggroups defined in options and add the tags for them
        $tagsarr = explode(',',$form->getValue('tags',null,''));
        $parentids = $params->get('artisttagparents',[]);
        if (!empty($parentids)) {
            $taghelp = new TagsHelper;
            $parr = $taghelp->getTags($parentids);
            foreach ($parr as $pid=>$parent) {
                $groupname = $parent.'_tags';
                $element = new SimpleXMLElement('<field name="'.$groupname.'" type="xbtags" label="'.ucfirst($parent).' Group" mode="nested" multiple="true" custom="deny" parent="'.$pid.'" class="xbtags" />');
                $form->setField($element, null, true, 'taggroups');
                if (!empty($tagsarr)){
                    $groupnametags = $taghelp->getTagTreeArray($pid);
                    $grouptags = array_intersect($groupnametags, $tagsarr);
                    $form->setValue($groupname,null,$grouptags);
                }
            }
        } // endforeach parenttag
        
        return $form;
    }
    
    protected function loadFormData() {
        // Check the session for previously entered form data.
        $app  = Factory::getApplication();
        $data = $app->getUserState('com_xbmusic.edit.artist.data', []);
        
        if (empty($data)) {
            $data = $this->getItem();
            $data->tracklist = $this->getArtistTrackList($data->id);
            $data->albumlist = $this->getArtistAlbumList($data->id);
            $data->songlist = $this->getArtistSongList($data->id);
            
            $retview = $app->input->get('retview','');
            // Pre-select some filters (Status, Category, Language, Access) in edit form if those have been selected in Article Manager: Articles
            if (($this->getState('artist.id') == 0) && ($retview != '')) {
                $filters = (array) $app->getUserState('com_xbmusic.'.$retview.'.filter');
                $data->set(
                    'status',
                    $app->getInput()->getInt(
                            'status',
                        ((isset($filters['status']) && $filters['status'] !== '') ? $filters['status'] : null)
                        )
                    );
                    $data->set('catid', $app->getInput()->getInt('catid', (!empty($filters['category_id']) ? $filters['category_id'] : null)));

                     if ($app->isClient('administrator')) {
                         $data->set('language', $app->getInput()->getString('language', (!empty($filters['language']) ? $filters['language'] : null)));
                     }
    
                    $data->set(
                        'access',
                        $app->getInput()->getInt('access', (!empty($filters['access']) ? $filters['access'] : $app->get('access')))
                        );
                }
            }
            
            // If there are params fieldsets in the form it will fail with a registry object
            if (isset($data->params) && $data->params instanceof Registry) {
                $data->params = $data->params->toArray();
            }
            
            return $data;
        }
        
    public function save($data) {
        $app    = Factory::getApplication();
        $input  = $app->getInput();
        $params = ComponentHelper::getParams('com_xbmusic');
        $filter = InputFilter::getInstance();
        $infomsg = '';
        $warnmsg = '';

        if ($input->get('task') == 'save2copy') {
            $origTable = clone $this->getTable();
            $origTable->load($input->getInt('id'));
            
            if ($data['name'] == $origTable->name) {
                list($name, $alias) = $this->generateNewTitle($data['catid'], $data['alias'], $data['name']);
                $data['name'] = $name;
                $data['alias'] = $alias;
            } else {
                if ($data['alias'] == $origTable->alias) {
                    $data['alias'] = '';
                }
            }
            //need to add tracklinks
            $this->storeArtistTracks($origTable->id, $data['tracklist']);
            // standard Joomla practice is to set the new copy record as unpublished
            $data['status'] = 0;
        }
        
       
        //alias is the name so we'll set and check it every time
        $newalias = OutputFilter::stringURLSafe($data['name']);
        if (($data['id'] == 0) && XbcommonHelper::checkValueExists($newalias, '#__xbmusic_artists', 'alias')) {
            $warnmsg .= 'Duplicate alias - this artist name is already in the database';
            $app->enqueueMessage($warnmsg,'Error');
            return false;
        }
        $data['alias'] = $newalias;        
        
        if (isset($data['created_by_alias'])) {
            $data['created_by_alias'] = $filter->clean($data['created_by_alias'], 'TRIM');
        }
        
        //merge any tag groups back into tags
        $parentids = $params->get('artisttagparents',[]);
        if (!empty($parentids)) {
            $thelp = new TagsHelper;
            $parr = $thelp->getTags($parentids);
            foreach ($parr as $pid=>$parent) {
                $groupname = $parent.'_tags';
                if (!empty($data[$groupname])) {
                    $data['tags'] = ($data['tags']) ? array_unique(array_merge($data['tags'],$data[$groupname])) : $data[$groupname];
                }
            }
        } // endforeach parenttag
        
        // ok ready to save the artist data
        if (parent::save($data)) {
            $sid = $this->getState('artist.id');
            $this->storeArtistTracks($sid, $data['tracklist']);
            $this->storeArtistAlbums($sid, $data['albumlist']);
            $this->storeArtistSongs($sid, $data['songlist']);
            // Check possible workflow
            if ($infomsg != '') $app->enqueueMessage($infomsg, 'Information');            
            return true;
        }
        if ($infomsg != '') $app->enqueueMessage($infomsg, 'Information');
        if ($warnmsg != '') $app->enqueueMessage($warnmsg, 'Warning');        
        return false;
    }
    
    protected function preprocessForm(Form $form, $data, $group = 'content') {
        Factory::getApplication()->getSession()->set('artistname', $data->name);
        if ($this->canCreateCategory()) {
            $form->setFieldAttribute('catid', 'allowAdd', 'true');
            
            // Add a prefix for categories created on the fly.
            $form->setFieldAttribute('catid', 'customPrefix', '#new#');
        }
        
        parent::preprocessForm($form, $data, $group);
    }
    
    public function saveorder($idArray = null, $lft_array = null)
    {
        // Get an instance of the table object.
        $table = $this->getTable();
        
        if (!$table->saveorder($idArray, $lft_array))
        {
            $this->setError($table->getError());
            
            return false;
        }
        
        return true;
    }
    
    private function canCreateCategory() {
        return $this->getCurrentUser()->authorise('core.create', 'com_content');
    }
    
    private function getArtistTrackList($artistid) {
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->select('b.title AS title, b.rel_date, a.track_id AS track_id, a.role AS role, a.note AS note, a.listorder AS listorder');
        $query->from('#__xbmusic_artisttrack AS a');
        $query->innerjoin('#__xbmusic_tracks AS b ON a.track_id = b.id');
        $query->where('a.artist_id = '.$db->q($artistid));
        $query->order('b.title ASC');
        $db->setQuery($query);
        return $db->loadAssocList();
    }
    
    function storeArtistTracks($artistid, $trackList) {
        //delete existing role list
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->delete($db->quoteName('#__xbmusic_artisttrack'));
        $query->where('artist_id = '.$artistid);
        $db->setQuery($query);
        $db->execute();
        //restore the new list
        foreach ($trackList as $trk) {
            if ($trk['track_id'] > 0) {
                if (!key_exists('listorder', $trk)) $trk['listorder'] = 0;
                $query->clear();
                $query->insert($db->quoteName('#__xbmusic_artisttrack'));
                $query->columns('artist_id,track_id,role,note,listorder');
                $query->values('"'.$artistid.'","'.$trk['track_id'].'","'.$trk['role'].'","'.$trk['note'].'","'.$trk['listorder'].'"');
                //try
                $db->setQuery($query);
                $db->execute();
            }
        }
    }

    private function getArtistAlbumList($artistid) {
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->select('a.album_id AS album_id, b.title AS title, b.rel_date AS rel_date, a.role AS role, a.note AS note, a.listorder AS listorder');
        $query->from('#__xbmusic_artistalbum AS a');
        $query->innerjoin('#__xbmusic_albums AS b ON a.album_id = b.id');
        $query->where('a.artist_id = '.$db->q($artistid));
        $query->order('b.title ASC');
        $db->setQuery($query);
        return $db->loadAssocList();
    }
    
    private function storeArtistAlbums($artistid, $albumList) {
        //delete existing list
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->delete($db->quoteName('#__xbmusic_artistalbum'));
        $query->where('artist_id = '.$artistid);
        $db->setQuery($query);
        $db->execute();
        //restore the new list
        foreach ($albumList as $album) {
            if ($album['album_id'] > 0) {
                if (!key_exists('listorder', $album)) $album['listorder'] = 0;
                $query->clear();
                $query->insert($db->quoteName('#__xbmusic_artistalbum'));
                $query->columns('artist_id,album_id,role,note,listorder');
                $query->values('"'.$artistid.'","'.$album['album_id'].'","'.$album['role'].'","'.$album['note'].'","'.$album['listorder'].'"');
                //try
                $db->setQuery($query);
                $db->execute();
            }
        }
    }
    
    private function getArtistSongList($artistid) {
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->select('a.id AS song_id, a.title AS title, b.role AS role, b.note AS note, b.listorder AS listorder');
        $query->from('#__xbmusic_artistsong AS b');
        $query->innerjoin('#__xbmusic_songs AS a ON b.song_id = a.id');
        $query->where('b.artist_id = '.$db->q($artistid));
        $query->order('a.title ASC');
        $db->setQuery($query);
        return $db->loadAssocList();
    }
    
    private function storeArtistSongs($artistid, $songList) {
        //delete existing list
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->delete($db->quoteName('#__xbmusic_artistsong'));
        $query->where('artist_id = '.$artistid);
        $db->setQuery($query);
        $db->execute();
        //restore the new list
        foreach ($songList as $song) {
            if ($song['song_id'] > 0) {
                if (!key_exists('listorder', $song)) $song['listorder'] = 0;
                $query->clear();
                $query->insert($db->quoteName('#__xbmusic_artistsong'));
                $query->columns('artist_id,song_id,role,note,listorder');
                $query->values('"'.$artistid.'","'.$song['song_id'].'","'.$song['role'].'","'.$song['note'].'","'.$song['listorder'].'"');
                //try
                $db->setQuery($query);
                $db->execute();
            }
        }
    }
    
}

