<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Model/TrackModel.php
 * @version 0.0.19.1 25th November 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Filter\InputFilter;
use Joomla\Filter\OutputFilter;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Uri\Uri;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbmusicHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbcommonHelper;
use \SimpleXMLElement;
//use Joomla\Registry\Registry;
//use Joomla\Component\Categories\Administrator\Helper\CategoriesHelper;
//use Symfony\Component\Validator\Constraints\IsNull;
//use Crosborne\Component\Xbmusic\Administrator\Extension\XbmusicComponent;
//use Joomla\CMS\Form\Form;
//use Joomla\CMS\Plugin\PluginHelper;
//use Joomla\CMS\String\PunycodeHelper;
//use Joomla\CMS\Table\Table;
//use Joomla\CMS\Table\TableInterface;
//use Joomla\CMS\UCM\UCMType;
//use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
//use Joomla\Database\ParameterType;

class TrackModel extends AdminModel {
  
    public $typeAlias = 'com_xbmusic.track';
    
    public $genreParentId = false;
    
    protected $xbmusic_batch_commands = array(
        'untag' => 'batchUntag',
    );
    
    public function __construct($config = [], $factory = null, $form = null) {
        $this->genreParentId = XbcommonHelper::getCreateTag(array('title'=>'Genres',
            'description'=>Text::_('XBMUSIC_ID3GENRES_TAG_DESC')),true);
        parent::__construct($config, $factory, $form);    
    }
    
    public function batch($commands, $pks, $contexts) {
        $this->batch_commands = array_merge($this->batch_commands, $this->xbmusic_batch_commands);
        return parent::batch($commands, $pks, $contexts);
    } 
    
    protected function batchUntag($value, $pks, $contexts) {
        $taghelper = new TagsHelper();
        $message = 'tag:'.$value.' removed from tracks :';
        foreach ($pks as $pk) {
            if ($this->user->authorise('core.edit', $contexts[$pk])) {
                $existing = $taghelper->getItemTags('com_xbmusic.track', $pk, false);
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
        //first need to delete links artists, songs
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        foreach ($pks as $pk) {
            $query->delete($db->qn('#__xbmusic_artisttrack'));
            $query->where($db->qn('track_id').' = '.$db->q($pk));
            $db->setQuery($query);
            $db->execute();
            $query->clear('delete');
            $query->delete($db->qn('#__xbmusic_songtrack'));
            $db->setQuery($query);
            $db->execute();
            $query->clear();
        }
        
        return parent::delete($pks);
    }
    
    public function loadId3() {
//         $app  = Factory::getApplication();
//         $data = $app->getInput()->get('jform',[],'array');
//         $filename = $data['pathname'].'/'.$data['filename'];
//         if (file_exists($filename)) {
//             if ($this->importID3data($data)) {
//                 if ($data['id']>0) {
//                     $this->postSaveID3($data, $data['id']);
//                 }
//                 $app->setUserState('com_xbmusic.edit.track.data', $data);
//                 $this->loadFormData();
//                 return true;
//             } else {
//                 $app->enqueueMessage('Problem loading ID3 data','Warning');
//                 return false;
//             }
//         } else {
//             $app->enqueueMessage('File not found','Warning');
//             return false;
//         }
        return true;
    }
    
/*     
    public function saveId3(int $tkid) {
        $app  = Factory::getApplication();
        $data = $app->getInput()->get('jform',[],'array');
        $filename = rtrim($data['pathname'],'/').'/'.$data['filename'];
        if (file_exists($filename)) {
            $app->enqueueMessage($filename);
            //do stuff
        } else {
            $this->setError(Text::_('File not found on saveId3'));
            return false;
        }
        return true;
    }    
 */
 
    protected function canDelete($record) {
        if (empty($record->id) || ($record->status != -2)) {
            return false;
        }
        
        return $this->getCurrentUser()->authorise('core.delete', 'com_xbmusic.track.' . (int) $record->id);
    }
    
    protected function canEditState($record) {
        $user = $this->getCurrentUser();
        
        // Check for existing track.
        if (!empty($record->id)) {
            return $user->authorise('core.edit.state', 'com_xbmusic.track.' . (int) $record->id);
        }
        
        // New track, so check against the category.
        if (!empty($record->catid)) {
            return $user->authorise('core.edit.state', 'com_xbmusic.category.' . (int) $record->catid);
        }
        
        // Default to component settings if neither track nor category known.
        return parent::canEditState($record);
    }
    
    protected function prepareTable($table) {
        
        // Reorder the tracks within the category so the new track is first
        if (empty($table->id)) {
            $table->reorder('catid = ' . (int) $table->catid . ' AND status >= 0');
        }
    }
    
    public function getItem($pk = null) {
        if ($item = parent::getItem($pk)) {
            if (!empty($item->id)) {
                $tagsHelper = new TagsHelper();
                $item->tags = $tagsHelper->getTagIds($item->id, 'com_xbmusic.track');
                $item->album = $this->getAlbum($item->album_id);               
                $item->artists = $this->getTrackArtistList($item->id);
                $item->songs = $this->getTrackSongList($item->id);
                
                if ($item->id3tags) $item->id3_tags = json_decode($item->id3tags);
                if ($item->audioinfo) $item->audioinfo = json_decode($item->audioinfo);
                if ($item->fileinfo) $item->fileinfo = json_decode($item->fileinfo);
                if ($item->imageinfo) $item->imageinfo = json_decode($item->imageinfo);
                $item->image_type = ($item->imageinfo) ? $item->imageinfo->picturetype : '';
                $item->image_desc = ($item->imageinfo) ? $item->imageinfo->description : '';                
            }
        }        
        return $item;
    }
    
    public function getForm($data = [], $loadData = true) {
        $app  = Factory::getApplication();
        $params = ComponentHelper::getParams('com_xbmusic');
        
        // Get the form.
        $form = $this->loadForm('com_xbmusic.track', 'track', ['control' => 'jform', 'load_data' => $loadData]);
        if (empty($form)) {
            return false;
        }
        
        //dynamically add fields for any taggroups defined in options and add the tags for them
        $tagsarr = explode(',',$form->getValue('tags',null,''));
        $parentids = $params->get('tracktagparents',[]);
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
        $data = $app->getUserState('com_xbmusic.edit.track.data', []);
        
        if (empty($data)) {
            $data = $this->getItem();
            $data->songlist = $this->getTrackSongList($data->id);
            $data->artistlist = $this->getTrackArtistList($data->id);
//            $data->playlistlist = $this->getTrackPlayLists();
        }
        
        return $data;
    }
       
    public function save($trackdata) {     
        
        $params = ComponentHelper::getParams('com_xbmusic');
        $filter = InputFilter::getInstance();
        if (isset($trackdata['created_by_alias'])) {
            $trackdata['created_by_alias'] = $filter->clean($trackdata['created_by_alias'], 'TRIM');
        }
        $userid = $this->getCurrentUser()->id;
        if (!isset($trackdata['created_by'])) $trackdata['created_by'] = $userid;
        if ((!isset($trackdata['modified_by'])) && ($trackdata['id'] > 0)) $trackdata['modified_by'] = $userid;
        
        //merge any tag groups back into tags
        $parentids = $params->get('tracktagparents',[]);
        if (!empty($parentids)) {
            $thelp = new TagsHelper;
            $parr = $thelp->getTags($parentids);
           
            foreach ($parr as $pid=>$parent) {
                $groupname = $parent.'_tags';
                if (!empty($trackdata[$groupname])) {
                    $trackdata['tags'] = ($trackdata['tags']) ? 
                        array_unique(array_merge($trackdata['tags'],$trackdata[$groupname])) : $trackdata[$groupname];
                }
            }
        } // endforeach parenttag
        
        if (parent::save($trackdata)) {
            $tid = $this->getState('track.id');
            // if a new track we need to create and link any songs and artists and add genre to song and artist per options,
            $trackdata['songlist'] = XbcommonHelper::uniqueNestedArray($trackdata['songlist'], 'song_id');
            $this->storeTrackSongs($tid, $trackdata['songlist']);
            
            $trackdata['artistlist'] = XbcommonHelper::uniqueNestedArray($trackdata['artistlist'], 'artist_id');
            $this->storeTrackArtists($tid, $trackdata['artistlist']);
            //$this->storeTrackPlaylists($tid, $trackdata['playlists']);
            
            return true;
        }
        return false;
    }

/***************    
    public function XOLDsave($trackdata) {
        $app    = Factory::getApplication();
        //$input  = $app->getInput();
        $params = ComponentHelper::getParams('com_xbmusic');
        $filter = InputFilter::getInstance();
        $infomsg = '';
        $warnmsg = '';
        //break file into pathname and filename
        if ($trackdata['id']== 0){
            $trackdata['filepathname'] = JPATH_ROOT.'/xbmusic/'.trim($trackdata['foldername'],'/').'/'.$trackdata['selectedfiles'];
//            $trackdata['pathname'] = rtrim(dirname($trackdata['filepathname']),'/').'/';
            $trackdata['filename'] = $trackdata['selectedfiles'];
        }
        
        //set alias for track to path from basemusicfolder plus filename
        if ($trackdata['alias'] == '') {
            $trackdata['alias'] = XbcommonHelper::makeAliasUnique(OutputFilter::stringURLSafe($trackdata['title']),'#__xbmusic_tracks');
            if ( XbcommonHelper::checkValueExists($trackdata['alias'], '#__xbmusic_tracks', 'alias')) {
                $warnmsg .= Text::_('Duplicate alias - this track appears to be already in the database. Data not saved');
                $app->enqueueMessage($warnmsg,'Warning');
                return false;
            }
            
        }
        //if new track we will automatically load the ID3 data if available
//        $getid3 = (($trackdata['id']==0)) ? true : false;
        $getid3 = ($trackdata['id']==0) ? true : false;
        if ($getid3) {  
            //this will create the album and category and tag from id3 genre if required
            if (!$this->importID3data($trackdata)) {
                $warnmsg .= Text::_('Failed to import ID3 data. Data not saved');
                $app->enqueueMessage($warnmsg,'Error');
                return false; //?????
            }
        } else {
            //check if any id3 elements we can manually change have changed
            $id3changed = '';
            //get the previously saved data and check picturetype, picture decription, 
            $olditem = parent::getItem($trackdata['id']);
            $oldid3data = json_decode($olditem->id3_data);
            if (isset($oldid3data->imageinfo->picturetype) && $oldid3data->imageinfo->picturetype != $trackdata['image_type']) {
                $id3changed = 'ID3 data changed: image_type';
                $oldid3data->imageinfo->picturetype = $trackdata['image_type'];
            }
            if (isset($oldid3data->imageinfo->picturetype) && $oldid3data->imageinfo->description != $trackdata['image_desc']) {
                $id3changed .= ($id3changed =='') ? 'ID3 data changed: image_desc' : ', image_desc';
                $oldid3data->imageinfo->description = $trackdata['image_desc'];
            }
            if (isset($oldid3data->id3data->title) && $oldid3data->id3data->title != $trackdata['title']) {
                $id3changed .= ($id3changed =='') ? 'ID3 data changed: track title' : ', track title';
                $oldid3data->id3data->title = $trackdata['title'];
            }
            //  potentially other elements to add here
            
            if ($id3changed!='') {
                $trackdata['id3_data'] = json_encode($oldid3data);
                //TODO save changes back to file after saving (what about ?image data)
                $warnmsg .= Text::_('Some ID3 tag data has been changed manually, new data will be saved locally with track but music file will not be updated.'); 
                $warnmsg .= '<br />'.$id3changed;
            }           
        } //endif newtrack
        
        
        if (isset($trackdata['created_by_alias'])) {
            $trackdata['created_by_alias'] = $filter->clean($trackdata['created_by_alias'], 'TRIM');
        }
                        
        //merge any tag groups back into tags
        $parentids = $params->get('tracktagparents',[]);
        if (!empty($parentids)) {
            $thelp = new TagsHelper;
            $parr = $thelp->getTags($parentids);
            foreach ($parr as $pid=>$parent) {
                $groupname = $parent.'_tags';
                if (!empty($trackdata[$groupname])) {
                    $trackdata['tags'] = ($trackdata['tags']) ? array_unique(array_merge($trackdata['tags'],$trackdata[$groupname])) : $trackdata[$groupname];
                }
            }
        } // endforeach parenttag
        
        // ok ready to save the track data
        if (parent::save($trackdata)) {
            $tid = $this->getState('track.id');
            // if a new track we need to create and link any songs and artists and add genre to song and artist per options, 
            if ($getid3) {
               $res = $this->postSaveID3($trackdata,$tid);                
            } //endif postsave $getid3
            
            $trackdata['songlist'] = XbcommonHelper::uniqueNestedArray($trackdata['songlist'], 'song_id');
            $this->storeTrackSongs($tid, $trackdata['songlist']);
            
            $trackdata['artistlist'] = XbcommonHelper::uniqueNestedArray($trackdata['artistlist'], 'artist_id');
            $this->storeTrackArtists($tid, $trackdata['artistlist']);
            //$this->storeTrackPlaylists($tid, $trackdata['playlists']);
            
            //output any messages
            if ($infomsg != '') $app->enqueueMessage($infomsg, 'Info');            
            if ($warnmsg != '') $app->enqueueMessage($warnmsg, 'Warning');
            return true;
        }
        $app->enqueueMessage('Error saving track data','Error');
        if ($infomsg != '') $app->enqueueMessage($infomsg, 'Info');
        if ($warnmsg != '') $app->enqueueMessage($warnmsg, 'Warning');        
        return false;
    }
************/

    public function readId3Save() {
        $app    = Factory::getApplication();
        $trackdata = $app->getInput()->get('jform',[],'array');
        $params = ComponentHelper::getParams('com_xbmusic');
        $filter = InputFilter::getInstance();
        $infomsg = '';
        $warnmsg = '';
        //break file into pathname and filename
        if ($trackdata['id']== 0){
            $trackdata['filepathname'] = JPATH_ROOT.'/xbmusic/'.trim($trackdata['foldername'],'/').'/'.$trackdata['selectedfiles'];
            //            $trackdata['pathname'] = rtrim(dirname($trackdata['filepathname']),'/').'/';
            $trackdata['filename'] = $trackdata['selectedfiles'];
        }
        
        //set alias for track to path from basemusicfolder plus filename
        if ($trackdata['alias'] == '') {
            $trackdata['alias'] = XbcommonHelper::makeUniqueAlias($trackdata['title'],'#__xbmusic_tracks');
            if ( XbcommonHelper::checkValueExists($trackdata['alias'], '#__xbmusic_tracks', 'alias')) {
                $warnmsg .= Text::_('Duplicate alias - this track appears to be already in the database. Data not saved');
                $app->enqueueMessage($warnmsg,'Warning');
                return false;
            }
            
        }
        //if new track we will automatically load the ID3 data if available
        //        $getid3 = (($trackdata['id']==0) || (/** detect task ==getid3? **/)) ? true : false;
        $getid3 = ($trackdata['id']==0) ? true : false;
        if ($getid3) {
            //this will create the album and category and tag from id3 genre if required
            if (!$this->importID3data($trackdata)) {
                $warnmsg .= Text::_('Failed to import ID3 data. Data not saved');
                $app->enqueueMessage($warnmsg,'Error');
                return false; //?????
            }
        } else {
            //check if any id3 elements we can manually change have changed
            $id3changed = '';
//             //get the previously saved data and check picturetype, picture decription,
//             $olditem = parent::getItem($trackdata['id']);
//             $oldid3data = json_decode($olditem->id3_data);
//             if (isset($oldid3data->imageinfo->picturetype) && $oldid3data->imageinfo->picturetype != $trackdata['image_type']) {
//                 $id3changed = 'ID3 data changed: image_type';
//                 $oldid3data->imageinfo->picturetype = $trackdata['image_type'];
//             }
//             if (isset($oldid3data->imageinfo->picturetype) && $oldid3data->imageinfo->description != $trackdata['image_desc']) {
//                 $id3changed .= ($id3changed =='') ? 'ID3 data changed: image_desc' : ', image_desc';
//                 $oldid3data->imageinfo->description = $trackdata['image_desc'];
//             }
//             if (isset($oldid3data->id3data->title) && $oldid3data->id3data->title != $trackdata['title']) {
//                 $id3changed .= ($id3changed =='') ? 'ID3 data changed: track title' : ', track title';
//                 $oldid3data->id3data->title = $trackdata['title'];
//             }
//             //  potentially other elements to add here
            
//             if ($id3changed!='') {
//                 $trackdata['id3_data'] = json_encode($oldid3data);
//                 //TODO save changes back to file after saving (what about ?image data)
//                 $warnmsg .= Text::_('Some ID3 tag data has been changed manually, new data will be saved locally with track but music file will not be updated.');
//                 $warnmsg .= '<br />'.$id3changed;
//             }
        } //endif newtrack
        
        
        if (isset($trackdata['created_by_alias'])) {
            $trackdata['created_by_alias'] = $filter->clean($trackdata['created_by_alias'], 'TRIM');
        }
        
        //merge any tag groups back into tags
        $parentids = $params->get('tracktagparents',[]);
        if (!empty($parentids)) {
            $thelp = new TagsHelper;
            $parr = $thelp->getTags($parentids);
            foreach ($parr as $pid=>$parent) {
                $groupname = $parent.'_tags';
                if (!empty($trackdata[$groupname])) {
                    $trackdata['tags'] = ($trackdata['tags']) ? array_unique(array_merge($trackdata['tags'],$trackdata[$groupname])) : $trackdata[$groupname];
                }
            }
        } // endforeach parenttag
        
        // ok ready to save the track data
        if (parent::save($trackdata)) {
            $tid = $this->getState('track.id');
            // if a new track we need to create and link any songs and artists and add genre to song and artist per options,
            if ($getid3) {
                $res = $this->postSaveID3($trackdata,$tid);
            } //endif postsave $getid3
            
            $trackdata['songlist'] = XbcommonHelper::uniqueNestedArray($trackdata['songlist'], 'song_id');
            $this->storeTrackSongs($tid, $trackdata['songlist']);
            
            $trackdata['artistlist'] = XbcommonHelper::uniqueNestedArray($trackdata['artistlist'], 'artist_id');
            $this->storeTrackArtists($tid, $trackdata['artistlist']);
            //$this->storeTrackPlaylists($tid, $trackdata['playlists']);
            
            //output any messages
            if ($infomsg != '') $app->enqueueMessage($infomsg, 'Info');
            if ($warnmsg != '') $app->enqueueMessage($warnmsg, 'Warning');
            return true;
        }
        $app->enqueueMessage('Error saving track data','Error');
        if ($infomsg != '') $app->enqueueMessage($infomsg, 'Info');
        if ($warnmsg != '') $app->enqueueMessage($warnmsg, 'Warning');
        return false;
    } // end function readId3Save()
    
    
    public function importID3data(&$trackdata) {
        $app = Factory::getApplication();
        $params = ComponentHelper::getParams('com_xbmusic');
        $warnmsg = '';
        $infomsg = '';
        $ilogmsg = '';
        $filepathname = JPATH_ROOT.'/xbmusic/'.rtrim($trackdata['foldername'],'/').'/'.$trackdata['filename'];
        if (file_exists($filepathname)) {
// 1. check if filepathname already in database, if it exists already then exit
            if ($tid = XbcommonHelper::checkValueExists($filepathname, '#__xbmusic_tracks', 'filepathname')) {
//                $ilogmsg .= '[ERROR] Track already in database with track_id='.$tid."\n";
//                $ilogmsg .= $enditem;
//                $cnts['duptrk'] ++;
//                return $ilogmsg;
                $app->enqueueMessage(Text::sprintf('File %s already in database with track_id=%s',$filepathname,$tid), 'Error');
                return false;
            }
// 2. set track->pathname and track->filename, if filename exists then warning
            $fpathinfo = pathinfo($filepathname);
            $trackdata['filepathname'] = $filepathname;
//            $trackdata['pathname'] = str_replace(JPATH_ROOT.'/xbmusic/','',$fpathinfo['dirname']);;
            // check if same filename exists in a different folder - import it anyway and warn
            if ( $fid = XbcommonHelper::checkValueExists($fpathinfo['basename'], '#__xbmusic_tracks', 'filename')) {
                $fpath = XbcommonHelper::getItemValue('#__xbmusic_tracks', 'filepathname', $fid);
                $warnmsg = Text::sprintf('Filename already in database at %s with track_id=%s',$fpath,$fid).".<br />";
                $warnmsg .= Text::_('Importing this one anyway, but check and delete one or other if necessary')."<br />";
            }
//3. okay, now get the id3 data
            $filedata = XbmusicHelper::getFileId3($filepathname);
            // get the artist name without "The " to use for sorting and in artwork filename
            if ($trackdata['sortartist'] == '') {
                if (isset($filedata['id3tags']['artist'])) {
                    $artistarr = explode(' || ', $filedata['id3tags']['artist']);                    
                    $trackdata['sortartist'] = XbcommonHelper::stripThe($artistarr[0]);
                    if (count($artistarr) > 1) {
                        $warnmsg .= Text::_('More than one artist listed - only first used as Main Performer (sortname). Check and adjust sortname manually if required').'<br />';
                    }
                }
            }
            //get album title for use in creating and linking album
            if (isset($filedata['id3tags']['album'])) {
                $albumarr = explode(' || ', $filedata['id3tags']['album']);
                $albumtitle = $albumarr[0];
                $titlelist = '<br />';
                if (count($albumarr)>1) {
                    $titlelist = '<ul>';
                    foreach ($albumarr as $album) {
                        $titlelist .= '<li>'.$album.'</li>';
                    }
                    $titlelist .= '</ul>';
                    $warnmsg .= Text::_('More than one album title listed - only the first is used. Check alternate title and if necessary change album title. Track can only belong to one album').$titlelist;
                }
                $albumalias = $albumtitle;
            }
            //get album artist for use in image filename and creating album
            if (isset($filedata['id3tags']['band'])) {
                $albumartist = $filedata['id3tags']['band'];
            } else {
                $albumartist = (isset($filedata['id3tags']['artist'])) ? $trackdata['sortartist'] : '';
            }
            // get artwork if not set and if available in ID3
            if (empty($trackdata['imgurl'])) {
                if (isset($filedata['imageinfo']['data'])){
                    // 5. extract image data for later use and unset it in id3data to save clean id3_data with track
                    if (isset($filedata['imageinfo']['data'])){
                        $imgdata = $filedata['imageinfo'];
                        unset($filedata['imageinfo']['data']);
                    }
                }
                
                // 10. Create image file
                if (isset($filedata['imageinfo']['data'])){
                    
                    // if album is set image filename will be "album-title_sortartist.ext"
                    // saved in "images/xbmusic/artwork/albums/[initial letter of album title]/"
                    // if track has no album then filename "track-title_sortartist.ext" and save in artwork/singles/
                    $imgfilename = '/images/xbmusic/artwork/';
                    if (isset($albumdata['alias'])) {
                        $imgfilename .= 'albums/'.strtolower($albumdata['alias'][0]).'/'.$albumdata['alias'];
                    } else {
                        $imgfilename .= 'singles/'.$trackdata['alias'];
                    }
                    if (isset($trackdata['sortartist'])) $imgfilename .= '_'.$trackdata['sortartist'];
                    $imgurl = XbmusicHelper::createImageFile($imgdata, $imgfilename, $ilogmsg);
                    if ($imgurl != false) {
                        $trackdata['imgurl'] = $imgurl;
                        $ilogmsg .= Text::_('[INFO] image file created').' '.str_replace(Uri::root(),'',$imgurl)."\n";
                    } else {
                        $ilogmsg .= Text::_('[WARN] failed to create image file').' '.$imgurl."\n";
                    }
                } //end ifset image data
                
//                     // filename for image will be "album-title-albumartist-name.ext"
//                     // if track has no album listed but has image then "artist-name.ext" for all tracks by the artist
//                     // path will finish with initial letter of title or "unknown"
//                     $folder = ($albumtitle == '') ? 'singles/' : 'albums/'.strtolower($albumtitle[0]);
//                     $artpath = '/images/xbmusic/artwork/'.$folder.'/';
//                     if (file_exists($artpath)==false) {
//                         mkdir(JPATH_ROOT.$artpath,0775,true);
//                     }
//                     $artfilename = OutputFilter::stringURLSafe(str_replace(' & ',' and ', $albumtitle.' '.$trackdata['sortartist'])).'.'.XbcommonHelper::imageMimeToExt($filedata['imageinfo']['image_mime']);
//                     $artpathfile = JPATH_ROOT.$artpath.$artfilename;
//                     $arturl = Uri::root().$artpath.$artfilename;
//                     if (file_exists($artpathfile)) {
//                         $trackdata['imgurl'] = $arturl;
//                     } else {
//                         if (file_put_contents($artpathfile, $filedata['imageinfo']['data'])) {
//                             $trackdata['imgurl'] = $arturl;
//                         }
//                     }
//                     unset($filedata['imageinfo']['data']);
//                 }
            } //endif empty artwork, if no artwork no action needed
            
            // get record and release dates
            $datematch = '/(^(\d{4})$)|(^(\d{4})-{1}[0-1][1-9]$)|(^(\d{4})-{1}[0-1][1-9]-{1}[0-3][1-9]$)/';
            if ($trackdata['rec_date'] == '') {
                if (isset($filedata['id3tags']['recording_time'])) {
                    if (preg_match($datematch,$filedata['id3tags']['recording_time'])==1) {
                        $trackdata['rec_date'] = ($filedata['id3tags']['recording_time']);
                    } else {
                        $warnmsg .= 'Recording date '.$filedata['id3tags']['recording_time'].' doesn\'t match Y(-M(-D) format. Enter manually.<br />';
                    }
                }
            }
            if ($trackdata['rel_date'] == '') {
                if (isset($filedata['id3tags']['year'])) {
                    if (preg_match($datematch,$filedata['id3tags']['year'])==1) {
                        $trackdata['rel_date'] = $filedata['id3tags']['year'];
                    } else {
                        $warnmsg .= 'Release date '.$filedata['id3tags']['year'].' doesn\'t match Y(-M(-D)) format. Enter manually.<br />';
                    }
                }
            }
            // create album
            if ($albumtitle != '') {
                $numdiscs = (isset($filedata['id3tags']['part_of_a_set'])) ? (int) explode('/',$filedata['id3tags']['part_of_a_set'])[1] :1;
                $albumid = $this->getCreateAlbum($albumtitle, $albumartist, $trackdata['rel_date'], $trackdata['imgurl'],$numdiscs );
                if ($albumid < 0) {
                    $albumid *= -1;
                    $infomsg .= Text::sprintf('Existing album "%s" added to track',$albumtitle).'<br />';
                } else {
                    $infomsg .= Text::sprintf('New album "%s" added to track',$albumtitle).'<br />';                    
                }
                $trackdata['album_id'] = $albumid;
                $trackdata['trackno'] = (isset($filedata['id3tags']['track_number'])) ? $filedata['id3tags']['track_number'] : 0;
                $trackdata['discno'] = (isset($filedata['id3tags']['part_of_a_set'])) ? (int) $filedata['id3tags']['part_of_a_set'] : '';
            } else {
                $warnmsg .= Text::_('No ID3 album info available for track').'<br />';
            }
            // track title
            if ($trackdata['title'] == '') {
                if ($filedata['id3tags']['title'] != '') {
                    $trackdata['title'] = $filedata['id3tags']['title'];
                }
            } elseif ($trackdata['title'] != $filedata['id3tags']['title']) {
                $warnmsg .= Text::_('Track title does not match ID3 title').'<br />';
            }
            // genre
            if (isset($filedata['id3tags']['genre'])) {
//                 $pid = XbcommonHelper::checkValueExists('id3genres', '#__tags', 'alias');
//                 if ($pid == 0) {
//                     $pid = XbcommonHelper::getCreateTag(array('title'=>'Id3Genres',
//                         'description'=>Text::_('XBMUSIC_ID3GENRES_TAG_DESC'),
//                         'note'=>Text::_('XBMUSIC_ID3GENRES_TAG_NOTE')
//                         ));
//                 }
                XbcommonHelper::addTagToGroup('Genres', 'com_xbmusic.tracktagparents');
                $opt = $params->get('genrecattag',0);
                $genrenames = explode(' || ', $filedata['id3tags']['genre']);
                $genretags = [];
                //CATEGORY
                $find = array('.',',','/'); //replace these with hyphens in title
                if (($opt == 1) || ($opt == 3)) {
                    $genre = $genrenames[0];
                    $genre = str_replace($find,'-',$genre);
                    $cid = XbcommonHelper::getCatByAlias(ApplicationHelper::stringURLSafe($genre))->id;
                    if ($cid>0) {
                        $trackdata['catid'] = $cid;
                        $infomsg .= Text::sprintf('Category "%s" assigned to track', $genre).'<br />';
                    } else{
                        //get tracks category as parent
                        //$par = XbcommonHelper::getCatByAlias('tracks');
                        $tkcatid = XbcommonHelper::checkValueExists('tracks', '#__categories', 'alias', '`extension` = \'com_xbmusic\'');
                        if ($tkcatid == false) {
                            $tkcat = XbcommonHelper::createCategory(array('title'=>'tracks', 'id'=>1));
                            $tkcatid = $tkcat->id;
 //                           $pid = 1;
 //                           $warnmsg .= Text::sprintf('Category "Tracks" does not exist so will create "%s" category at top level',$genre).'<br />';
                        }
                        $newcat = XbcommonHelper::createCategory(array('title'=>$genre, 'parent_id'=>$tkcatid, 'note'=>Text::_('auto-created from id3 genre')));
                        if ($newcat->id) {
                            $trackdata['catid'] = $newcat->id;
                            $infomsg .= Text::sprintf('Category "%s" assigned to track', $genre).'<br />';
                        }                            
                    } //endif cat already exists
                } //end opt=1|3
                //TAG
                if(($opt == 2) || ($opt == 3)) {
                    $pid = XbcommonHelper::checkValueExists('Genres', '#__tags', 'title');
                  foreach ($genrenames as $genre) {
                        //if genre tag already exists
                      $genre = str_replace($find,'-',$genre);
                      $tid = XbcommonHelper::checkValueExists($genre, '#__tags', 'title');
                        if ($tid == 0) {                               
                            $newtag = XbcommonHelper::getCreateTag(array('title'=>$genre, 'parent_id'=>$pid, 'note'=>Text::_('XBMUSIC_ID3GENRES_TAG_NOTE')));
                            if ($newtag->id) {
                                $tid = $newtag->id;
                                $infomsg .= Text::sprintf('Tag %s  created and assigned to track', $genre);
                            }
                        } else{
                            $infomsg .= Text::sprintf('Tag "%s" assigned to track', $genre).'<br />';
                        } //endif tag already exists                        
                        //add tag to item
                        $trackdata['tags'][] = $tid;
                        $infomsg .= '<br />';
                        //need to also assign to album and song as per settings once they have been created after track has been saved
                        //do we have song and album ids? $trackdata['album_id']
                        $genretags[$tid] = $genre;
                    } //endif opt=2|3
                } //end foreach genre
                $trackdata['genres'] = $genretags;
            } // endif id3 genre is set
            if (isset($filedata['audioinfo']['playtime_seconds'])) $trackdata['duration'] = (int)$filedata['audioinfo']['playtime_seconds'];  
            $trackdata['imageinfo'] = json_encode($filedata['imageinfo']);
            $trackdata['id3tags'] = json_encode($filedata['id3tags']);
            $trackdata['fileinfo'] = json_encode($filedata['fileinfo']);
            $trackdata['audioinfo'] = json_encode($filedata['audioinfo']);
//            $trackdata['id3_data'] = json_encode($filedata);
            $trackdata['created_by_alias'] = 'Created from ID3 Import';           
            $app->enqueueMessage($infomsg,'Info');
            $app->enqueueMessage($warnmsg,'Warning');
        } else {
            $error = 'Impossible Error : file '.rtrim($trackdata['foldername'],'/').'/'.$trackdata['filename'].' does not exist';
            $app->enqueueMessage($error,'Error');
            return false;
        } //endif file exists
        return true;
    }
    
    /**
     * @name postSaveID3()
     * @desc after saving track this creates & links songs, artists, 
     * @param array $data the track data - song and artists lists will be updated
     * @param integer $tid - the id of the saved track
     */
    public function postSaveID3(&$data, int $tid) {
        $params = ComponentHelper::getParams('com_xbmusic');
        $infomsg = '';
        $warnmsg = '';
        $data['newsongs'] = [];
        $data['newartists'] = [];
        $data['songlist'] = $this->getTrackSongList();
        $data['artistlist'] = $this->getTrackArtistList();
        $app = Factory::getApplication();
        $db = $this->getDatabase();
        //$db = $this->getDbo();
        $id3tags = json_decode($data['id3tags'],true);
        // we've already created album if it exists so get it's title for reporting
        $albumtitle = ($data['album_id']>0) ? XbcommonHelper::getItemValue('#__xbmusic_albums', 'title', $data['album_id']) : '';
        
        //check id3 song title against song table and add if missing (alias created from title)
        if ($id3tags['title'] != '') {
            $songtitle = $id3tags['title'];
            if ((strpos($songtitle, ',')>1) || (strpos($songtitle, '/')>1)) {
                $warnmsg .= Text::_('Possible multiple titles detected in SongTitle - is this a medley? Use Save As Copy on Song edit to split into separate songs.').'<br />';
            }
            $songcomp = (isset($id3tags['composer'])) ? $id3tags['composer'] : '';
            $songid = $this->getCreateSong($songtitle, $songcomp);
            if ($songid<0) {
                $infomsg .= Text::sprintf('Existing song "%s" found in database', $songtitle).'<br />';
                $songid *= -1; //(faster than abs()
            } else {
                if ($songid > 0) $data['newsongs'][] = [$songid => $songtitle];
            }
            if (empty(XbcommonHelper::getItems('#__xbmusic_songtrack', 'song_id', $songid, 'track_id = '.$db->q($tid)))) {
                $n = (isset($data['songlist'])) ? count($data['songlist']) : 0;
                $data['songlist']['songlist'.$n] = array('song_id' => $songid, 'note' =>Text::_('XBMUSIC_CREATED_IMPORT'));
                $infomsg .= Text::sprintf('Song "%s" added to track',$songtitle ).'<br />';
            } else {
                $warnmsg .= Text::sprintf('Song "%s" already linked to track',$songtitle ).'<br />';
            }
            
        } else {
            $warnmsg .= Text::_('No ID3 song title available for track, new song will not be created or added to list').'<br />';
        }
        // check if artist exists if not create and warn if name includes , or & or and or feat.
        if ($id3tags['artist'] != '') {
            // multiple artists possible in id3
            $artistarr = explode(' || ',$id3tags['artist']);
            if ((count($artistarr)==1) && (preg_match('/(,)|( & )|( and )|( with )|( feat[\.| ])( featuring)/', $artistarr[0])>0)) {
                $warnmsg .= Text::sprintf('Artist name "%s" might include more than one artist - check and split manually if necessary',$artistarr[0]).'<br />';
            }
            $n = count($data['artistlist']);
            foreach ($artistarr as $name) {
                $artistid = $this->getCreateArtist($name);
                if ($artistid<0) {
                    $infomsg .= Text::sprintf('Existing artist "%s" found in database', $name).'<br />';
                    $artistid *= -1; //(faster than abs()
                }
                // add artist to artist list
                if (empty(XbcommonHelper::getItems('#__xbmusic_artisttrack', 'artist_id', $artistid, 'track_id = '.$db->q($tid)))) {
                    $data['artistlist']['artistlist'.$n] = array('artist_id' => $artistid, 'role'=>'', 'note' =>Text::_('auto created from ID3'));
                    $infomsg .= Text::sprintf('Artist %s added to %s %s','"'.$name.'"', 'track','' ).'<br />';
                    $n ++;
                } else {
                    $warnmsg .= Text::sprintf('Artist "%s" already linked to track',$name ).'<br />';
                }
                /***
                // link artist to song
                if ($this->createArtistSong($artistid, $songid, '', Text::_('auto created from ID3'))) {
                    $infomsg .= Text::sprintf('%s added to %s %s',$name, 'song', '"'.$songtitle.'"').'<br />';
                } else {
                    $warnmsg .= Text::_('Problem linking artist to song - link may already exist').'<br />';
                }
                ***/
                // link artist to track
                if ( ($data['album_id'] >0) 
                    && ($this->createArtistAlbum($artistid, $data['album_id'], '', Text::_('auto created from ID3')))) {
                        $infomsg .= Text::sprintf('Artist %s added to %s %s', '"'.$name.'"', 'album', ' "'.$albumtitle).'"<br />';
                } else {
                    $warnmsg .= Text::_('Problem linking artist to album - link may already exist').'<br />';
                }
                
            }
        } else {
            $infomsg .= Text::_('No ID3 artist available for track').'<br />';
        }
        //check album-artist against artist table and add if missing
        
        // add genres to album and song
        $addgenre = $params->get('addgenre',0);
        if ((isset($data['genres'])) && (count($data['genres']) > 0 ) && ($addgenre > 0)) {
            foreach ($data['genres'] as $tagid=>$tagname) {
                if (($addgenre == 1) || ($addgenre == 3)) {
                    //check id3genres is in taggroups for songs
                    XbcommonHelper::addTagToGroup('Genres', 'com_xbmusic.songtagparents');
                    XbcommonHelper::addTagToItems('com_xbmusic.song', $songid, $tagid);
                    $infomsg .= Text::sprintf('Tag "%s" added to song "%s"',$tagname,$songtitle).'<br />';
                }
                if ($addgenre > 1) {
                    //check if id3genres is in taggroups for albums
                    XbcommonHelper::addTagToGroup('Genres', 'com_xbmusic.albumtagparents');
                    XbcommonHelper::addTagToItems('com_xbmusic.album', $data['album_id'], $tagid);
                    $infomsg .= Text::sprintf('Tag "%s" added to album %s', $tagname, $albumtitle).'<br />';
                }
            }
        }
        if ($infomsg!='') $app->enqueueMessage($infomsg, 'Info');
        if ($warnmsg!='') $app->enqueueMessage($warnmsg,'Warning');
        return true;   
        
    } //end function postSaveID3()
    
    private function getAlbum($albumid) {
        if ($albumid >0) {
            $db = $this->getDatabase();
            $query = $db->getQuery(true);
            $query->select('id, title, sortartist, rel_date');
            $query->from('#__xbmusic_albums');
            $query->where($db->qn('id').' = '.$db->q($albumid));
            $query->order('title ASC');
            $db->setQuery($query);
            $album = $db->loadAssoc();
        }
        return $album;
    }

    private function getTrackArtistList($track_id) {
        $db = $this->getDatabase();
        //$db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->select('a.artist_id AS artist_id, b.name AS name, a.role AS role, a.note AS note, a.listorder AS listorder');
        $query->from('#__xbmusic_artisttrack AS a');
        $query->innerjoin('#__xbmusic_artists AS b ON a.artist_id = b.id');
        $query->where('a.track_id = '.$db->q($track_id));
        $query->order('a.listorder ASC');
        $db->setQuery($query);
        return $db->loadAssocList();
    }
    
    private function storeTrackArtists($track_id, $artistlist) {
        //delete existing role list
        $db = $this->getDatabase();
        //$db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->delete($db->quoteName('#__xbmusic_artisttrack'));
        $query->where('track_id = '.$db->q($track_id));
        $db->setQuery($query);
        $db->execute();
        //restore the new list
        $listorder=0;
        $query->clear();
        $query->insert($db->quoteName('#__xbmusic_artisttrack'));
        $query->columns('artist_id,track_id,role, note,listorder');
        foreach ($artistlist as $artist) {
            if ($artist['artist_id'] > 0) {
                $listorder ++;
                $query->values('"'.$artist['artist_id'].'","'.$track_id.'","'.$artist['role'].'","'.$artist['note'].'","'.$listorder.'"');
                //try
                $db->setQuery($query);
                $db->execute();
                $query->clear('values');                
            }
        }
    }
    
    private function getTrackSongList($track_id) {
        $db = $this->getDatabase();
        //$db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->select('a.title AS title, b.song_id as song_id, b.role AS role, b.note AS note, b.listorder AS listorder');
        $query->from('#__xbmusic_songtrack AS b');
        $query->innerjoin('#__xbmusic_songs AS a ON b.song_id = a.id');
        $query->where('b.track_id = '.$db->q($track_id));
        $query->order('b.listorder ASC');
        $db->setQuery($query);
        return $db->loadAssocList();
    }
      
    private function storeTrackSongs($track_id, $songlist) {
        //delete existing role list
        $db = $this->getDatabase();
        //$db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->delete($db->quoteName('#__xbmusic_songtrack'));
        $query->where('track_id = '.$db->q($track_id));
        $db->setQuery($query);
        $db->execute();
        //restore the new list
        $listorder=0;
        $query->clear();
        $query->insert($db->quoteName('#__xbmusic_songtrack'));
        $query->columns('song_id,track_id,role,note,listorder');
        foreach ($songlist as $song) {
            if ($song['song_id'] > 0) {
                $listorder ++;
                $query->values('"'.$song['song_id'].'","'.$track_id.'","'.$song['role'].'","'.$song['note'].'","'.$listorder.'"');
                //try
                $db->setQuery($query);
                $db->execute();
                $query->clear('values');
            }
        }
    }
    
    public function getCreateArtist($name) {
        $newalias = OutputFilter::stringURLSafe(str_replace(' & ',' and ', $name));
        //$db = $this->getDbo();
        $db = $this->getDatabase();
        $query = $db->getQuery(true);
        $query->select('id')->from('#__xbmusic_artists')->where('alias = '.$db->q($newalias));
        $db->setQuery($query);
        $id = -($db->loadResult());
        if (empty($id)) {
            $params = ComponentHelper::getParams('com_xbmusic');
            //get artist default category
            $catid = $params->get('defcat_artist',XbcommonHelper::getCatByAlias('uncategorised'));
            $createmod = Factory::getDate()->toSql();
            $createbyalias = 'created from ID3';
            $query->clear();
            $query->insert('#__xbmusic_artists');
            $query->columns('name, alias, catid, status, access, created, modified, created_by_alias');
            $query->values('"'.$name.'","'.$newalias.'","'.$catid.'","1","1","'.$createmod.'","'.$createmod.'","'.$createbyalias.'"');
            //            $query->values($db->quote($title, $newalias, $catid, $createmod, $createmod, $createbyalias));
            $db->setQuery($query);
            try {
                $db->execute();
            } catch (\Exception $e) {
                $dberr = $e->getMessage();
                Factory::getApplication()->enqueueMessage($dberr.'<br />Query: '.$query->dump(), '');
            }
            $id = $db->insertid();
            //           Factory::getApplication()->enqueueMessage(count(idarr).' '.Text::_('Songs with this title exist - please link manually','Warning'));
            //           return false;
        }
        return $id;       
    }
    
    /**
     * @desc returns id as negative number if song already exists or positive if song is new
     * @param string $title
     * @param integer $tid
     * @param string $composer
     * @return number|mixed
     */
    public function getCreateSong($title, $tid, $composer = '') {
        //check if two songs have same title but different alias?
        $newalias = OutputFilter::stringURLSafe(str_replace(' & ',' and ', $title));
        $db = $this->getDatabase();
        $query = $db->getQuery(true);
        $query->select('id')->from('#__xbmusic_songs')->where('alias = '.$db->q($newalias));
        $db->setQuery($query);
        $id = -($db->loadResult());
        if (empty($id)) {
            //create new song
            $params = ComponentHelper::getParams('com_xbmusic');
            //get song default category
            $catid = $params->get('defcat_song',XbcommonHelper::getCatByAlias('uncategorised'));
            $createmoddate = Factory::getDate()->toSql();
            $createbyalias = 'created from ID3';
            $query->clear();
            $query->insert('#__xbmusic_songs');
            $query->columns('title, alias, composer, catid, status, access, created, modified, created_by_alias');
            $query->values('"'.$title.'","'.$newalias.'","'.$composer.'","'.$catid.'","1","1","'.$createmoddate.'","'.$createmoddate.'","'.$createbyalias.'"');
//            $query->values($db->quote($title, $newalias, $catid, $createmod, $createmod, $createbyalias));
            $db->setQuery($query);
            try {
                $db->execute();
            } catch (\Exception $e) {
                $dberr = $e->getMessage();
                Factory::getApplication()->enqueueMessage($dberr.'<br />Query: '.$query->dump(), 'error');
            }
            $id = $db->insertid();
 //           Factory::getApplication()->enqueueMessage(count(idarr).' '.Text::_('Songs with this title exist - please link manually','Warning'));
 //           return false;
        }
        return $id;
    }
    
    public function getCreateAlbum($title, $artist, $reldate, $imgurl, $numdiscs) {
        //what if artist releases two albums with same title?
        $sortartist = XbcommonHelper::stripThe($artist);
        $newalias = OutputFilter::stringURLSafe(str_replace(' & ',' and ', $title).' '.$sortartist);
        $db = $this->getDatabase();
        //$db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->select('id')->from('#__xbmusic_albums')->where('alias = '.$db->q($newalias));
        $db->setQuery($query);
        $id = -($db->loadResult());
        if (empty($id)) {
            //create new album
            $params = ComponentHelper::getParams('com_xbmusic');
            //get song default category
            $catid = $params->get('defcat_album');
            if ($catid == '') $catid = XbcommonHelper::getCatByAlias('uncategorised');
            $createmod = Factory::getDate()->toSql();
            $createbyalias = 'created from ID3';
            $query->clear();
            $query->insert('#__xbmusic_albums');
            $query->columns('title, alias, albumartist, sortartist, rel_date, imgurl, num_discs, catid, status, access, created, modified, created_by_alias');
            $query->values('"'.$title.'","'.$newalias.'","'.$artist.'","'.$sortartist.'","'.$reldate.'","'.$imgurl.'","'.$numdiscs.'","'.$catid.'","1","1","'.$createmod.'","'.$createmod.'","'.$createbyalias.'"');
            //            $query->values($db->quote($title, $newalias, $catid, $createmod, $createmod, $createbyalias));
            $db->setQuery($query);
            try {
                $db->execute();
            } catch (\Exception $e) {
                $dberr = $e->getMessage();
                Factory::getApplication()->enqueueMessage($dberr.'<br />Query: '.$query->dump(), 'error');
            }
            $id = $db->insertid();
            //           Factory::getApplication()->enqueueMessage(count(idarr).' '.Text::_('Songs with this title exist - please link manually','Warning'));
            //           return false;
        }
        return $id;
    }
        
    private function canCreateCategory() {
        return $this->getCurrentUser()->authorise('core.create', 'com_content');
    }

    /****
    public function createArtistSong(int $artist_id, int $song_id, $role = '', $note = '') {
        $db = $this->getDatabase();
        //$db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->select('id')->from('#__xbmusic_artistsong')
        ->where($db->qn('artist_id').' = '.$db->q($artist_id))
        ->where($db->qn('song_id').' = '.$db->q($song_id));
        $db->setQuery($query);
        $res = $db->loadResult();
        if ($res>0) return false;
        $query->clear();
        $query->insert('#__xbmusic_artistsong')
        ->columns('artist_id','song_id','role','note')
        ->values($db->q($artist_id), $db->q($song_id), $db->q($role), $db->q($note));
        $db->setQuery($query);
        try {
            $res = $db->execute();
        } catch (\Exception $e) {
            $dberr = $e->getMessage();
            Factory::getApplication()->enqueueMessage($dberr.'<br />Query: '.$query->dump(), 'error');
        }
        return $res;
    }
    ***/
    
    public function createArtistAlbum(int $artist_id, int $album_id, $role = '', $note = '') {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);
        $query->select('id')->from('#__xbmusic_artistalbum')
            ->where($db->qn('artist_id').' = '.$db->q($artist_id))
            ->where($db->qn('album_id').' = '.$db->q($album_id));
        $db->setQuery($query);
        $res = $db->loadResult();
        if ($res>0) return false;
        $query->clear();
        $query->insert('#__xbmusic_artistalbum')
            ->columns('artist_id','album_id','role','note')
            ->values($db->q($artist_id), $db->q($album_id), $db->q($role), $db->q($note));
        $db->setQuery($query);
        try {
            $res = $db->execute();
        } catch (\Exception $e) {
            $dberr = $e->getMessage();
            Factory::getApplication()->enqueueMessage($dberr.'<br />Query: '.$query->dump(), 'error');
        }
        return $res;
    }
    
}

