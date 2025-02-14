<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Model/ArtistModel.php
 * @version 0.0.30.5 14th February 2025
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
use Joomla\CMS\Uri\Uri;

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
        'indgrp' => 'batchIndgrp'
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
    
    protected function batchIndgrp($value, $pks, $contexts) {
        if ($value > 0) {
            $cnt = 0;
            $db = Factory::getDbo();
            $query = $db->getQuery(true);
            $query->update($db->qn('#__xbmusic_artists'));
            $query->set($db->qn('type').' = '. $db->q($value));
            foreach ($pks as $pk) {
                if ($this->user->authorise('core.edit', $contexts[$pk])) {
                    $query->where($db->qn('id').' = '.$db->q($pk));
                    $db->setQuery($query);
                    $db->execute();
                    $cnt ++;
                    $query->clear('where');
                } else {
                    $this->setError(Text::_('JLIB_APPLICATION_ERROR_BATCH_CANNOT_EDIT'));
                    return false;
                }
                Factory::getApplication()->enqueueMessage($cnt.' '.'item types set to '.$value);
            }
            return true;            
        }
        return false;
    }

    public function delete(&$pks) {
        //first need to delete links to tracks, artistgroups
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        foreach ($pks as $pk) {
            $query->delete($db->qn('#__xbmusic_trackartist'));
            $query->where($db->qn('artist_id').' = '.$db->q($pk));
            $db->setQuery($query);
            $db->execute();
            $query->clear();
            $query->delete($db->qn('#__xbmusic_artistgroup'));
            $query->where($db->qn('artist_id').' = '.$db->q($pk));
            $query->orWhere($db->qn('group_id').' = '.$db->q($pk));
            $db->setQuery($query);
            $db->execute();
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
                $item->albums = XbmusicHelper::getArtistAlbums($item->id);
                $item->songs = XbmusicHelper::getArtistSongs($item->id);
                $item->members = []; $item->groups = [];                
                if ($item->type == 2) {
                    $item->members = XbmusicHelper::getGroupMembers($item->id);
                } elseif ($item->type == 1) {
                    $item->groups = XbmusicHelper::getMemberGroups($item->id);
                }
                $item->singles = XbmusicHelper::getArtistSingles($item->id);
                if (!empty(($item->imageinfo))) {
                    $item->imageinfo = json_decode($item->imageinfo);
                    if((isset($item->imageinfo->picturetype)) && (!isset($item->imageinfo->imagetitle)))
                        $item->imageinfo->imagetitle = $item->imageinfo->picturetype;
                    if((isset($item->imageinfo->description)) && (!isset($item->imageinfo->imagedesc)))
                        $item->imageinfo->imagedesc = $item->imageinfo->description;
                }
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
        $tags = $form->getValue('tags',null,'');
        $tagsarr = (is_array($tags)) ? $tags : explode(',',$tags);
        $parentids = $params->get('artisttagparents',[]);
        if (!empty($parentids)) {
            $taghelp = new TagsHelper;
            $parr = $taghelp->getTags($parentids);
            foreach ($parr as $pid=>$parent) {
                $groupname = $parent.'_tags';
                $element = new SimpleXMLElement('<field name="'.$groupname.'" type="xbtags" label="'.ucfirst($parent).' Group" mode="nested" multiple="true" custom="allow" parent="'.$pid.'" class="xbtags" />');
                $form->setField($element, null, true, 'taggroups');
                if (!empty($tagsarr)){
                    $groupnametags = $taghelp->getTagTreeArray($pid);
                    //set tags that are in this group
                    $grouptags = array_intersect($groupnametags, $tagsarr);
                    $form->setValue($groupname,null,$grouptags);
                    //remove group tags from the main tags field
                    $tagsarr = array_diff($tagsarr, $groupnametags);
                }
            }
        } // endforeach parenttag
        $form->setValue('tags', null, $tagsarr);
        
        return $form;
    }
    
    protected function loadFormData() {
        // Check the session for previously entered form data.
        $app  = Factory::getApplication();
        $data = $app->getUserState('com_xbmusic.edit.artist.data', []);
        
        if (empty($data)) {
            $data = $this->getItem();
//            $data->imageinfo = json_decode($data->imageinfo);
            $data->tracklist = $this->getArtistTrackList($data->id);
            $data->albumlist = XbmusicHelper::getArtistAlbums($data->id);
            $data->songlist = XbmusicHelper::getArtistSongs($data->id);
            $data->grouplist = XbmusicHelper::getMemberGroups($data->id);
            $data->memberlist = XbmusicHelper::getGroupMembers($data->id);
            
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
//            if (isset($data->imageinfo->imagetitle)) $data->newimagetitle = $data->imageinfo->imagetitle;
//            if (isset($data->imageinfo->imagedesc)) $data->newimagedesc = $data->imageinfo->imagedesc;
            
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

        if (!empty($data['newimage'])) {
            $imgurl = Uri::root().substr($data['newimage'],0,strpos($data['newimage'], "#"));
            if ($imgurl != $data['imgurl']) {
                $data['imgurl'] = $imgurl;
                $data['imageinfo']['datalength']='';
                $data['imageinfo']['image_height']='';
                $data['imageinfo']['image_width']='';
                $data['imageinfo']['picturetype']='';
                $data['imageinfo']['description']='';
                $data['imageinfo']['image_mime']='';
                $data['imageinfo']['imagetitle']='';
                $data['imageinfo']['imagedesc']='';
            }
 //       } elseif (empty($data['imgurl'])) {
 //           $data['imageinfo'] = [];
//            $data['imgurl'] = '';            
        } if ($data['imgurl'] != '') {
            $file = str_replace(Uri::root(),'',$data['imgurl']);
            $data['imageinfo']['folder'] = dirname($file);
            $file = JPATH_ROOT.'/'.$file;
            if (file_exists($file)) {
                $data['imageinfo']['basename'] = basename($file);
                $data['imageinfo']['filesize'] = filesize($file);
                $data['imageinfo']['basename'] = basename($file);
                $bytes = filesize($file);
                $lbl = Array('bytes','kB','MB','GB');
                $factor = floor((strlen($bytes) - 1) / 3);
                $data['imageinfo']['filesize'] = sprintf("%.2f", $bytes / pow(1024, $factor)) . @$lbl[$factor];
                $data['imageinfo']['filedate'] = date("d M Y at H:i",filemtime($file));
                $imagesize = getimagesize($file);
                $data['imageinfo']['filemime'] = $imagesize['mime'];
                $data['imageinfo']['filewidth'] = $imagesize[0];
                $data['imageinfo']['fileht'] = $imagesize[1];
                if (isset($data['newimagetitle'])) {
                    $data['imageinfo']['imagetitle'] = $data['newimagetitle'];
                }
                if (trim($data['imageinfo']['imagetitle'])=='') $data['imageinfo']['imagetitle'] = $data['name'];
                if (isset($data['newimagedesc'])) {
                    $data['imageinfo']['imagedesc'] = $data['newimagedesc'];
                }      
                       
            } else {
                $data['imageinfo'] = [];
                $data['imgurl'] = '';
            }
        } else {
            $data['imageinfo'] = [];
        }            
        
        $data['imageinfo'] = json_encode($data['imageinfo']);
        
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
        $parentids = $params->get('tracktagparents',[]);
        if (!empty($parentids)) {
            $thelp = new TagsHelper;
            $parr = $thelp->getTags($parentids);
            
            foreach ($parr as $pid=>$parent) {
                $groupname = $parent.'_tags';
                //$newpid = $pid;
                if (!empty($data[$groupname])) {
                    //need to test for #new# in 'id' column and if found create a new tag and add its id to group
                    foreach ($data[$groupname] as &$value) {
                        if (strpos($value,'#new#') !== false) {
                            $newtag = XbcommonHelper::getCreateTagPath($value, $pid);
                            $value = $newtag['id'];
                        }
                    }
                    $data['tags'] = ($data['tags']) ?
                    array_unique(array_merge($data['tags'],$data[$groupname])) : $data[$groupname];
                }
            } //endforeach parenttag
        } // endif !empty parentids
        
        // ok ready to save the artist data
        if (parent::save($data)) {
            $sid = $this->getState('artist.id');
            $this->storeArtistTracks($sid, $data['tracklist']);
            if ($data['type'] > 0) {
                $this->storeArtistGroup($sid, $data['memberlist'], $data['grouplist'], $data['type']);                
            }
//            $this->storeArtistAlbums($sid, $data['albumlist']);
//            $this->storeArtistSongs($sid, $data['songlist']);
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
        $query->from('#__xbmusic_trackartist AS a');
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
        $query->delete($db->quoteName('#__xbmusic_trackartist'));
        $query->where('artist_id = '.$artistid);
        $db->setQuery($query);
        $db->execute();
        //restore the new list
        foreach ($trackList as $trk) {
            if ($trk['track_id'] > 0) {
                if (!key_exists('listorder', $trk)) $trk['listorder'] = 0;
                $query->clear();
                $query->insert($db->quoteName('#__xbmusic_trackartist'));
                $query->columns('artist_id,track_id,role,note,listorder');
                $query->values('"'.$artistid.'","'.$trk['track_id'].'","'.$trk['role'].'","'.$trk['note'].'","'.$trk['listorder'].'"');
                //try
                $db->setQuery($query);
                $db->execute();
            }
        }
    }
    
    private function storeArtistGroup($itemid, $members, $groups, $type) {
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        //start by clearing all references to this artist
        $query->delete($db->quoteName('#__xbmusic_artistgroup'));
        $query->where('member_id = '.$itemid);
        $query->orWhere('group_id = '.$itemid);
        $db->setQuery($query);
        $db->execute();       
        if ($type == 1) {
            foreach ($groups AS $group) {
                $query->clear();
                $query->insert($db->quoteName('#__xbmusic_artistgroup'));
                $query->columns('member_id,group_id,role,since,until,note');
                $query->values('"'.$itemid.'","'.$group['group_id'].'","'.$group['role'].'","'.$group['since'].'","'.$group['until'].'","'.$group['note'].'"');
                //try
                $db->setQuery($query);
                $db->execute();
                
            }
        } elseif ($type == 2 ) {
            // we have a group so members are valid and clear groups
            foreach ($members AS $member) {
                $query->clear();
                $query->insert($db->quoteName('#__xbmusic_artistgroup'));
                $query->columns('group_id,member_id,role,since,until,note');
                $query->values('"'.$itemid.'","'.$member['member_id'].'","'.$member['role'].'","'.$member['since'].'","'.$member['until'].'","'.$member['note'].'"');
                //try
                $db->setQuery($query);
                $db->execute();
                
            }
        }
    }
    
}

