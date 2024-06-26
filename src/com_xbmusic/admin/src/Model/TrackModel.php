<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Model/TrackModel.php
 * @version 0.0.10.1 24th June 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\String\PunycodeHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Table\TableInterface;
use Joomla\CMS\UCM\UCMType;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\Database\ParameterType;
use Joomla\Filter\OutputFilter;
use Joomla\Registry\Registry;
use Joomla\Component\Categories\Administrator\Helper\CategoriesHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbmusicHelper;
use Symfony\Component\Validator\Constraints\IsNull;
use \SimpleXMLElement;
use Crosborne\Component\Xbmusic\Administrator\Extension\XbmusicComponent;
use Joomla\CMS\Uri\Uri;

class TrackModel extends AdminModel {
  
    public $typeAlias = 'com_xbmusic.track';
    
    protected $xbmusic_batch_commands = array(
        'untag' => 'batchUntag',
    );
    
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
    
    public function saveId3() {
        $app  = Factory::getApplication();
        $data = $app->getInput()->get('jform',[],'array');
        $filename = $data['pathname'].'/'.$data['filename'];
        if (file_exists($filename)) {
            $app->enqueueMessage($filename);
        } else {
            $app->enqueueMessage('File not found','Warning');
            return false;
        }
        return true;
    }
    
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
                if (isset($item->id3_data)) {
                    $id3data = json_decode($item->id3_data);
                    $item->id3_tags = $id3data->id3tags;
                    $item->audioinfo = $id3data->audioinfo;
                    $item->fileinfo = $id3data->fileinfo;
                    $item->imageinfo = $id3data->imageinfo;
                    $item->image_type = $id3data->imageinfo->picturetype;
                    $item->image_desc = $id3data->imageinfo->description;
                    
                }
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
        
        //dynamically add fields for any taggroups defined in options
        $parentids = $params->get('tracktagparents',[]);
        if (!empty($parentids)) {
            $taghelp = new TagsHelper;
            $parr = $taghelp->getTags($parentids);
            foreach ($parr as $id=>$parent) {
                $element = new SimpleXMLElement('<field name="tags-'.$parent.'" type="tags" label="'.ucfirst($parent).'" mode="nested" multiple="true" custom="deny" />');
                $form->setField($element, null, true, 'taggroups');
            }
        } // endforeach parenttag
        
        //set up the folders for the directory and file selectors
        //directory (music_path) uses basemusicfolder
        if ($params->get('use_xbmusic', 1)) {
            $basemusicfolder = JPATH_ROOT.'/xbmusic/'; //.$params->get('xbmusic_subfolder','');
        } else {
            $basemusicfolder = (trim($params->get('music_path','')) != '') ? trim($params->get('music_path')) : JPATH_ROOT.'/xbmusic/';
        }
        $form->setFieldAttribute('pathname','directory',$basemusicfolder);
        //filenae is dynamically set by a session variable when pathname changes
        $mf = $app->getSession()->get('musicfolder','');
        if ($mf !='') $form->setValue('pathname', null, $mf);
        $form->setFieldAttribute('filename','directory',$form->getValue('pathname'));
        return $form;
    }
    
    protected function loadFormData() {
        // Check the session for previously entered form data.
        $app  = Factory::getApplication();
        $data = $app->getUserState('com_xbmusic.edit.track.data', []);
        
        if (empty($data)) {
            $data = $this->getItem();
            $data->songlist = $this->getTrackSongList();
            $data->artistlist = $this->getTrackArtistList();
//            $data->playlistlist = $this->getTrackPlayList();
            
            //copy any tags in a taggroup folder back to their control
            $tagsHelper = new TagsHelper;
            $params = ComponentHelper::getParams('com_xbmusic');
            $parentids = $params->get('tracktagparents',[]);
            if ((!empty($data->tags)) && (!empty($parentids))) {
                $parr = $tagsHelper->getTags($parentids);
                foreach ($parr as $pid=>$parent) {
                    $grouptags = $tagsHelper->getTagTreeArray($pid);
                    $groupname = 'tags-'.$parent;
                    $data->$groupname = array_intersect($grouptags, explode(',', $data->tags));
                }
            }           
        }
        
        return $data;
    }
        
    public function save($data) {
        $app    = Factory::getApplication();
        //$input  = $app->getInput();
        $params = ComponentHelper::getParams('com_xbmusic');
        $filter = InputFilter::getInstance();
        $infomsg = '';
        $warnmsg = '';
        //set alias for track to path from basemusicfolder plus filename
        if ($data['alias'] == '') {
            $basemusicfolder = XbmusicHelper::getMusicBase();
            $data['alias'] = OutputFilter::stringURLSafe(str_replace($basemusicfolder, '' , $data['pathname']).'-'. $data['filename']);
            if ( XbmusicHelper::checkValueExists($data['alias'], '#__xbmusic_tracks', 'alias')) {
                $warnmsg .= 'Duplicate alias - this track appears to be already in the database. Data not saved';
                $app->enqueueMessage($warnmsg,'Error');
                return false;
            }
        }
        //if new track we will automatically load the ID3 data if available
        $getid3 = ($data['getid3onsave'] == 1);
        if ($getid3) {  
            //this will create the album and category and tag from id3 genre if required
            if (!$this->importID3data($data)) return false; //?????
        } else {
            //check if any id3 elements we can manually change have changed
            $id3changed = '';
            //get the previously saved data and check picturetype, picture decription, 
            $olditem = parent::getItem($data['id']);
            $oldid3data = json_decode($olditem->id3_data);
            if (isset($oldid3data->imageinfo->picturetype) && $oldid3data->imageinfo->picturetype != $data['image_type']) {
                $id3changed = 'ID3 data changed: image_type';
                $oldid3data->imageinfo->picturetype = $data['image_type'];
            }
            if (isset($oldid3data->imageinfo->picturetype) && $oldid3data->imageinfo->description != $data['image_desc']) {
                $id3changed .= ($id3changed =='') ? 'ID3 data changed: image_desc' : ', image_desc';
                $oldid3data->imageinfo->description = $data['image_desc'];
            }
            if (isset($oldid3data->id3data->title) && $oldid3data->id3data->title != $data['title']) {
                $id3changed .= ($id3changed =='') ? 'ID3 data changed: track title' : ', track title';
                $oldid3data->id3data->title = $data['title'];
            }
            //  potentially other elements to add here
            
            if ($id3changed!='') {
                $data['id3_data'] = json_encode($oldid3data);
                //TODO save changes back to file after saving (what about ?image data)
                $warnmsg .= Text::_('Some ID3 tag data has been changed manually, new data will be saved with track but music file will not be updated.'); 
                $warnmsg .= '<br />'.$id3changed;
            }           
        } //endif newtrack
        
        
        if (isset($data['created_by_alias'])) {
            $data['created_by_alias'] = $filter->clean($data['created_by_alias'], 'TRIM');
        }
                        
        //merge any tag groups back into tags
        $parentids = $params->get('tracktagparents',[]);
        if (!empty($parentids)) {
            $thelp = new TagsHelper;
            $parr = $thelp->getTags($parentids);
            foreach ($parr as $id=>$parent) {
                if (!empty($data['tags-'.$parent])) {
                    $data['tags'] = ($data['tags']) ? array_unique(array_merge($data['tags'],$data['tags-'.$parent])) : $data['tags-'.$parent];                    
                }
            }
        } // endforeach parenttag
        
        // ok ready to save the track data
        if (parent::save($data)) {
            $tid = $this->getState('track.id');
            // if a new track we need to create and link any songs and artists and add genre to song and artist per options, 
            if ($getid3) {
               $res = $this->postSaveID3($data,$tid);                
            } //endif postsave $getid3
            
            $data['songlist'] = XbmusicHelper::uniqueNestedArray($data['songlist'], 'song_id');
            $this->storeTrackSongs($tid, $data['songlist']);
            
            $data['artistlist'] = XbmusicHelper::uniqueNestedArray($data['artistlist'], 'artist_id');
            $this->storeTrackArtists($tid, $data['artistlist']);
            //$this->storeTrackPlaylists($tid, $data['playlists']);
            
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
    
    public function importID3data(&$data) {
        $app = Factory::getApplication();
        $params = ComponentHelper::getParams('com_xbmusic');
        $warnmsg = '';
        $infomsg = '';
        $filepathname = rtrim($data['pathname'],'/').'/'.$data['filename'];
        if (file_exists($filepathname)) {
            $filedata = XbmusicHelper::getFileId3($filepathname);
            // get the artist name without "The " to use for sorting and in artwork filename
            if ($data['sortartist'] == '') {
                if (isset($filedata['id3tags']['artist'])) {
                    $artistarr = explode(' || ', $filedata['id3tags']['artist']);                    
                    $data['sortartist'] = $this->stripThe($artistarr[0]);
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
                
            }
            //get album artist for use in image filename and creating album
            if (isset($filedata['id3tags']['band'])) {
                $albumartist = $filedata['id3tags']['band'];
            } else {
                $albumartist = (isset($filedata['id3tags']['artist'])) ? $data['sortartist'] : '';
            }
            // get artwork if not set and if available in ID3
            if (empty($data['artwork'])) {
                if (isset($filedata['imageinfo']['data'])){
                    // filename for image will be "album-title-albumartist-name.ext"
                    // if track has no album listed but has image then "artist-name.ext" for all tracks by the artist
                    // path will finish with initial letter of title or "unknown"
                    $folder = ($albumtitle == '') ? 'singles/' : 'albums/'.strtolower($albumtitle[0]);
                    $artpath = '/images/xbmusic/artwork/'.$folder.'/';
                    if (!file_exists($artpath)) {
                        mkdir(JPATH_ROOT.$artpath,0775,true);
                    }
                    $artfilename = OutputFilter::stringURLSafe(str_replace(' & ',' and ', $albumtitle.' '.$data['sortartist'])).'.'.XbmusicHelper::imageMimeToExt($filedata['imageinfo']['image_mime']);
                    $artpathfile = JPATH_ROOT.$artpath.$artfilename;
                    $arturl = Uri::root().$artpath.$artfilename;
                    if (file_exists($artpathfile)) {
                        $data['artwork'] = $arturl;
                    } else {
                        if (file_put_contents($artpathfile, $filedata['imageinfo']['data'])) {
                            $data['artwork'] = $arturl;
                        }
                    }
                    unset($filedata['imageinfo']['data']);
                }
            } //endif empty artwork, if no artwork no action needed
            // get record and release dates
            $datematch = '/(^(\d{4})$)|(^(\d{4})-{1}[0-1][1-9]$)|(^(\d{4})-{1}[0-1][1-9]-{1}[0-3][1-9]$)/';
            if ($data['rec_date'] == '') {
                if (isset($filedata['id3tags']['recording_time'])) {
                    if (preg_match($datematch,$filedata['id3tags']['recording_time'])==1) {
                        $data['rec_date'] = ($filedata['id3tags']['recording_time']);
                    } else {
                        $warnmsg .= 'Recording date '.$filedata['id3tags']['recording_time'].' doesn\'t match Y(-M(-D) format. Enter manually.<br />';
                    }
                }
            }
            if ($data['rel_date'] == '') {
                if (isset($filedata['id3tags']['year'])) {
                    if (preg_match($datematch,$filedata['id3tags']['year'])==1) {
                        $data['rel_date'] = $filedata['id3tags']['year'];
                    } else {
                        $warnmsg .= 'Release date '.$filedata['id3tags']['year'].' doesn\'t match Y(-M(-D)) format. Enter manually.<br />';
                    }
                }
            }
            // create album
            if ($albumtitle != '') {
                $numdiscs = (isset($filedata['id3tags']['part_of_a_set'])) ? (int) explode('/',$filedata['id3tags']['part_of_a_set'])[1] :1;
                $albumid = $this->getCreateAlbum($albumtitle, $albumartist, $data['rel_date'], $data['artwork'],$numdiscs );
                if ($albumid < 0) {
                    $albumid *= -1;
                    $infomsg .= Text::sprintf('Existing album "%s" added to track',$albumtitle).'<br />';
                } else {
                    $infomsg .= Text::sprintf('New album "%s" added to track',$albumtitle).'<br />';                    
                }
                $data['album_id'] = $albumid;
                $data['trackno'] = (isset($filedata['id3tags']['track_number'])) ? $filedata['id3tags']['track_number'] : 0;
                $data['discno'] = (isset($filedata['id3tags']['part_of_a_set'])) ? (int) $filedata['id3tags']['part_of_a_set'] : '';
            } else {
                $warnmsg .= Text::_('No ID3 album info available for track').'<br />';
            }
            // track title
            if ($data['title'] == '') {
                if ($filedata['id3tags']['title'] != '') {
                    $data['title'] = $filedata['id3tags']['title'];
                }
            } elseif ($data['title'] != $filedata['id3tags']['title']) {
                $warnmsg .= Text::_('Track title does not match ID3 title').'<br />';
            }
            // genre
            if (isset($filedata['id3tags']['genre'])) {
                $opt = $params->get('genrecattag',0);
                $genrenames = explode(' || ', $filedata['id3tags']['genre']);
                $genretags = [];
                //CATEGORY
                if (($opt == 1) || ($opt == 3)) {
                    $genre = $genrenames[0];
                    $cid = XbmusicHelper::getCatByAlias(ApplicationHelper::stringURLSafe($genre))->id;
                    if ($cid>0) {
                        $data['catid'] = $cid;
                        $infomsg .= Text::sprintf('Category "%s" assigned to track', $genre).'<br />';
                    } else{
                        //get tracks category as parent
                        //$par = XbmusicHelper::getCatByAlias('tracks');
                        $tkcatid = XbmusicHelper::checkValueExists('tracks', '#__categories', 'alias', '`extension` = \'com_xbmusic\'');
                        if ($tkcatid == false) {
                            $tkcat = XbmusicHelper::createCategory(array('title'=>'tracks', 'id'=>1));
                            $tkcatid = $tkcat->id;
 //                           $pid = 1;
 //                           $warnmsg .= Text::sprintf('Category "Tracks" does not exist so will create "%s" category at top level',$genre).'<br />';
                        }
                        $newcat = XbmusicHelper::createCategory(array('title'=>$genre, 'parent_id'=>$tkcatid, 'note'=>Text::_('auto-created from id3 genre')));
                        if ($newcat->id) {
                            $data['catid'] = $newcat->id;
                            $infomsg .= Text::sprintf('Category "%s" assigned to track', $genre).'<br />';
                        }                            
                    } //endif cat already exists
                } //end opt=1|3
                //TAG
                if(($opt == 2) || ($opt == 3)) {
                  foreach ($genrenames as $genre) {
                        //if genre tag already exists
                        $tid = XbmusicHelper::checkValueExists(ApplicationHelper::stringURLSafe($genre), '#__tags', 'alias');
                        if ($tid>0) {
                            $data['tags'][] = $tid;
                            $infomsg .= Text::sprintf('Tag "%s" assigned to track', $genre).'<br />';
                        } else{
                            $pid = XbmusicHelper::checkValueExists('id3genres', '#__tags', 'alias');
                            if (!$pid>0) {
                                $pid = XbmusicHelper::createTag(array('title'=>'Id3Genres',
                                    'description'=>Text::_('Parent tag for ID3 genres. Do not remove, genres will be added automatically from track files.'),
                                    'note'=>Text::_('auto-created from id3 genre import')
                                ));
                                //now add it to track options
                                //$params = ComponentHelper::getParams($component);
                                // Set new value of param(s)
                                $tagpars = $params->get('tracktagparents');
                                if (!array_search($pid, $tagpars)){
                                    $tagpars[] = $pid;
                                    $params->set('tracktagparents', $tagpars);
                                }
                                
                                // Save the parameters
                                $componentid = ComponentHelper::getComponent('com_xbmusic')->id;
                                $table = Table::getInstance('extension');
                                $table->load($componentid);
                                $table->bind(array('params' => $params->toString()));
                                
                                // check for error
                                if (!$table->check()) {
                                    echo $table->getError();
                                    return false;
                                }
                                // Save to database
                                if (!$table->store()) {
                                    echo $table->getError();
                                    return false;
                                }
                                
                            }
                            $newtag = XbmusicHelper::createTag(array('title'=>$genre, 'parent_id'=>$pid, 'note'=>Text::_('auto-created from id3 genre')));
                            if ($newtag->id) {
                                $tid = $newtag->id;
                                //add tag to item
                                $data['tags'][] = $tid;
                                $infomsg .= Text::sprintf('Tag %s assigned to track', $genre);
                            }
                        } //endif tag already exists                        
                        $infomsg .= '<br />';
                        //need to also assign to album and song as per settings once they have been created after track has been saved
                        //do we have song and album ids? $data['album_id']
                        $genretags[$tid] = $genre;
                    } //endif opt=2|3
                } //end foreach genre
                $data['genres'] = $genretags;
            } // endif id3 genre is set
            if (isset($filedata['audioinfo']['playtime_seconds'])) $data['duration'] = (int)$filedata['audioinfo']['playtime_seconds'];  
            $data['id3_data'] = json_encode($filedata);
            $data['created_by_alias'] = 'Created from ID3 Import';           
            $app->enqueueMessage($infomsg,'Info');
            $app->enqueueMessage($warnmsg,'Warning');
        } else {
            $app->enqueueMessage('Impossible Error : file '.rtrim($data['pathname'],'/').'/'.$data['filename'].' does not exist', 'Error');
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
        $app = Factory::getApplication();
        $db = $this->getDatabase();
        //$db = $this->getDbo();
        $id3data = json_decode($data['id3_data'],true);
        //check id3 song title against song table and add if missing (alias created from title)
        if ($id3data['id3tags']['title'] != '') {
            $songtitle = $id3data['id3tags']['title'];
            if (strpos($songtitle, ',')>1) {
                $warnmsg .= Text::_('Possible multiple titles detected in SongTitle - is this a medley? Use Save As Copy on Song edit to split into separate songs.').'<br />';
            }
            $songcomp = (isset($id3data['id3tags']['composer'])) ? $id3data['id3tags']['composer'] : '';
            $songid = $this->getCreateSong($songtitle, $songcomp);
            if ($songid<0) {
                $infomsg .= Text::sprintf('Existing song "%s" found in database', $songtitle).'<br />';
                $songid *= -1; //(faster than abs()
            } else {
                if ($songid > 0) $data['newsongs'][] = [$songid => $songtitle];
            }
            if (empty(XbmusicHelper::getItems('#__xbmusic_songtrack', 'song_id', $songid, 'track_id = '.$db->q($tid)))) {
                $n = (isset($data['songlist'])) ? count($data['songlist']) : 0;
                $data['songlist']['songlist'.$n] = array('song_id' => $songid, 'note' =>Text::_('auto created from ID3'));
                $infomsg .= Text::sprintf('Song "%s" added to track',$songtitle ).'<br />';
            } else {
                $warnmsg .= Text::sprintf('Song "%s" already linked to track',$songtitle ).'<br />';
            }
            
        } else {
            $warnmsg .= Text::_('No ID3 song title available for track, new song will not be created or added to list').'<br />';
        }
        // check if artist exists if not create and warn if name includes , or & or and or feat.
        if ($id3data['id3tags']['artist'] != '') {
            // multiple artists possible in id3
            $artistarr = explode(' || ',$id3data['id3tags']['artist']);
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
                if (empty(XbmusicHelper::getItems('#__xbmusic_artisttrack', 'artist_id', $artistid, 'track_id = '.$db->q($tid)))) {
                    $data['artistlist']['artistlist'.$n] = array('artist_id' => $artistid, 'role'=>'', 'note' =>Text::_('auto created from ID3'));
                    $infomsg .= Text::sprintf('%s added to %s %s','"'.$name.'"', 'track','' ).'<br />';
                    $n ++;
                } else {
                    $warnmsg .= Text::sprintf('Artist "%s" already linked to track',$name ).'<br />';
                }
                // link artist to song
                if ($this->createArtistSong($artistid, $songid, '', Text::_('auto created from ID3'))) {
                    $infomsg .= Text::sprintf('%s added to %s %s',$name, 'song', '"'.$songtitle.'"').'<br />';
                } else {
                    $warnmsg .= Text::_('Problem linking artist to song - link may already exist').'<br />';
                }
                // link artist to track
                if ($this->createArtistAlbum($artistid, $data['album_id'], '', Text::_('auto created from ID3'))) {
                    $albumname = XbmusicHelper::getItemValue('#__xbmusic_albums', 'title', $data['album_id']);
                    $infomsg .= Text::sprintf('%s added to %s %s', '"'.$name.'"', 'album', '#'.$albumname).'<br />';
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
                    $this->addTagToItem('com_xbmusic.song', $songid, $tagid);
                    $infomsg .= Text::sprintf('Tag "%s" added to song "%s"',$tagname,$songtitle).'<br />';
                }
                if ($addgenre > 1) {
                    $this->addTagToItem('com_xbmusic.album', $songid, $data['album_id']);
                    $infomsg .= Text::sprintf('Tag "%s" added to album #%s',$tagname,$data['album_id']).'<br />';
                }
            }
        }
        if ($infomsg!='') $app->enqueueMessage($infomsg, 'Information');
        if ($warnmsg!='') $app->enqueueMessage($warnmsg,'Warning');
        return true;   
        
    }
    
    public function getTrackArtistList() {
        $db = $this->getDatabase();
        //$db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->select('ba.artist_id as artist_id, ba.role AS role, ba.note AS note');
        $query->from('#__xbmusic_artisttrack AS ba');
//        $query->innerjoin('#__xbmusic_artists AS a ON ba.artist_id = a.id');
        $query->where('ba.track_id = '.(int) $this->getItem()->id);
        $query->order('ba.listorder ASC');
        $db->setQuery($query);
        return $db->loadAssocList();
    }
    
    public function getTrackSongList() {
        $db = $this->getDatabase();
        //$db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->select('ba.song_id as song_id, ba.note AS note');
        $query->from('#__xbmusic_songtrack AS ba');
//        $query->innerjoin('#__xbmusic_songs AS a ON ba.song_id = a.id');
        $query->where('ba.track_id = '.(int) $this->getItem()->id);
        $query->order('ba.listorder ASC');
        $db->setQuery($query);
        return $db->loadAssocList();
    }
      
    function storeTrackSongs($track_id, $songlist) {
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
        foreach ($songlist as $song) {
            if ($song['song_id'] > 0) {
                $listorder ++;
                $query = $db->getQuery(true);
                $query->insert($db->quoteName('#__xbmusic_songtrack'));
                $query->columns('song_id,track_id,note,listorder');
                $query->values('"'.$song['song_id'].'","'.$track_id.'","'.$song['note'].'","'.$listorder.'"');
                //try
                $db->setQuery($query);
                $db->execute();
            } else {
                // Factory::getApplication()->enqueueMessage('<pre>'.print_r($pers,true).'</pre>');
                //create person
                //add filmperson with new id
            }
        }
    }
    
    function storeTrackArtists($track_id, $artistlist) {
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
        foreach ($artistlist as $artist) {
            if ($artist['artist_id'] > 0) {
                $listorder ++;
                $query = $db->getQuery(true);
                $query->insert($db->quoteName('#__xbmusic_artisttrack'));
                $query->columns('artist_id,track_id,role, note,listorder');
                $query->values('"'.$artist['artist_id'].'","'.$track_id.'","'.$artist['role'].'","'.$artist['note'].'","'.$listorder.'"');
                //try
                $db->setQuery($query);
                $db->execute();
            } else {
                // Factory::getApplication()->enqueueMessage('<pre>'.print_r($pers,true).'</pre>');
                //create person
                //add filmperson with new id
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
            $catid = $params->get('defcat_artist',XbmusicHelper::getCatByAlias('uncategorised'));
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
                Factory::getApplication()->enqueueMessage($dberr.'<br />Query: '.$query->dump(), 'error');
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
            $catid = $params->get('defcat_song',XbmusicHelper::getCatByAlias('uncategorised'));
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
    
    public function getCreateAlbum($title, $artist, $reldate, $artwork, $numdiscs) {
        //what if artist releases two albums with same title?
        $sortartist = $this->stripThe($artist);
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
            if ($catid == '') $catid = XbmusicHelper::getCatByAlias('uncategorised');
            $createmod = Factory::getDate()->toSql();
            $createbyalias = 'created from ID3';
            $query->clear();
            $query->insert('#__xbmusic_albums');
            $query->columns('title, alias, albumartist, sortartist, rel_date, artwork, num_discs, catid, status, access, created, modified, created_by_alias');
            $query->values('"'.$title.'","'.$newalias.'","'.$artist.'","'.$sortartist.'","'.$reldate.'","'.$artwork.'","'.$numdiscs.'","'.$catid.'","1","1","'.$createmod.'","'.$createmod.'","'.$createbyalias.'"');
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
    
    public function stripThe($name) {
        if (substr(strtolower($name), 0, 4) == 'the ') {
            $name = substr($name,4);
        }
        return $name;
    }
    
    private function canCreateCategory() {
        return $this->getCurrentUser()->authorise('core.create', 'com_content');
    }
    
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
    
/**
     * @name addTagToItem()
     * @desc adds an existing tag to an item
     * @param string $compitem - the coponent ite type in dotted lower case eg com_content.article
     * @param int $itemId - the id of the ite the tag is being added to
     * @param int $tagId - the id of the tag being added
     * @return boolean - true on suceess, false on failure
     */
    public function addTagToItem($compitem, $itemId, $tagId) {
        $arr=explode('.',$compitem);
        $app = Factory::getApplication();
        $factory = $app->bootComponent($arr[0])->getMVCFactory();
        $model = $factory->createModel(ucfirst($arr[1]), 'Administrator');
        $commands = array('tag'=>$tagId);
        $pks = [$itemId];
        $contexts = [$itemId=>$compitem.'.'.$itemId];
        $res = $model->batch($commands, $pks, $contexts );
        return $res;
    }
    
}

    /*** not needed?
//    protected function preprocessForm(Form $form, $data, $group = 'content') {
//         if ($this->canCreateCategory()) {
//             $form->setFieldAttribute('catid', 'allowAdd', 'true');
            
//             // Add a prefix for categories created on the fly.
//             $form->setFieldAttribute('catid', 'customPrefix', '#new#');
//         }
        
//        parent::preprocessForm($form, $data, $group);
//    }
    
//     public function getTrackAlbumList() {
//         $db = $this->getDbo();
//         $query = $db->getQuery(true);
//         $query->select('a.id as album_id, ba.discno AS discno, ba.trackno AS trackno, ba.listorder AS oldorder');
//         $query->from('#__xbmusic_albumtrack AS ba');
//         $query->innerjoin('#__xbmusic_albums AS a ON ba.album_id = a.id');
//         $query->where('ba.track_id = '.(int) $this->getItem()->id);
//         $query->order('a.title ASC');
//         $db->setQuery($query);
//         return $db->loadAssocList();
//     }
    
//     function storeTrackAlbums($track_id, $albumlist) {
//         //delete existing role list
//         $db = $this->getDbo();
//         $query = $db->getQuery(true);
//         $query->delete($db->quoteName('#__xbmusic_albumtrack'));
//         $query->where('track_id = '.$db->q($track_id));
//         $db->setQuery($query);
//         $db->execute();
//         //restore the new list
//         $listorder=0;
//         foreach ($albumlist as $album) {
//             if ($album['album_id'] > 0) {
//                 $listorder = ($album['oldorder']>0) ? $album['oldorder'] : 0;
//                 $query = $db->getQuery(true);
//                 $query->insert($db->quoteName('#__xbmusic_albumtrack'));
//                 $query->columns('album_id,track_id,discno, trackno,listorder');
//                 $query->values('"'.$album['album_id'].'","'.$track_id.'","'.$album['discno'].'","'.$album['trackno'].'","'.$listorder.'"');
//                 //try
//                 $db->setQuery($query);
//                 $db->execute();
//             } else {
//                 // Factory::getApplication()->enqueueMessage('<pre>'.print_r($pers,true).'</pre>');
//                 //create person
//                 //add filmperson with new id
//             }
//         }
//     }
    
     public function validate($form, $data, $group = null) {
     if (!$this->getCurrentUser()->authorise('core.admin', 'com_xbmusic')) {
     if (isset($data['rules'])) {
     unset($data['rules']);
     }
     }
     
     return parent::validate($form, $data, $group);
     }
     ****/
    
    /***
     protected function batchMove($value, $pks, $contexts) {
     
     if (empty($this->batchSet))
     {
     // Set some needed variables.
     $this->user = $this->getCurrentUser();
     $this->table = $this->getTable();
     $this->tableClassName = \get_class($this->table);
     $this->contentType = new UcmType();
     $this->type = $this->contentType->getTypeByTable($this->tableClassName);
     }
     
     $categoryId = (int) $value;
     
     if (!$this->checkCategoryId($categoryId)) {
     return false;
     }
     
     PluginHelper::importPlugin('system');
     
     // Parent exists so we proceed
     foreach ($pks as $pk) {
     if (!$this->user->authorise('core.edit', $contexts[$pk])) {
     $this->setError(Text::_('JLIB_APPLICATION_ERROR_BATCH_CANNOT_EDIT'));
     
     return false;
     }
     
     // Check that the row actually exists
     if (!$this->table->load($pk)) {
     if ($error = $this->table->getError()) {
     // Fatal error
     $this->setError($error);
     
     return false;
     }
     // Not fatal error
     $this->setError(Text::sprintf('JLIB_APPLICATION_ERROR_BATCH_MOVE_ROW_NOT_FOUND', $pk));
     continue;
     }
     
     $fields = FieldsHelper::getFields('com_xbmusic.track', $this->table, true);
     $fieldsData = array();
     
     if (!empty($fields)) {
     $fieldsData['com_fields'] = array();
     
     foreach ($fields as $field) {
     $fieldsData['com_fields'][$field->name] = $field->rawvalue;
     }
     }
     
     // Set the new category ID
     $this->table->catid = $categoryId;
     
     //             // We don't want to modify tags - so remove the associated tags helper
     //             if ($this->table instanceof TaggableTableInterface) {
     //                 $this->table->clearTagsHelper();
     //             }
     
     // Check the row.
     if (!$this->table->check()) {
     $this->setError($this->table->getError());
     
     return false;
     }
     
     if (!empty($this->type))
     {
     $this->createTagsHelper($this->tagsObserver, $this->type, $pk, $this->typeAlias, $this->table);
     }
     
     // Store the row.
     if (!$this->table->store()) {
     $this->setError($this->table->getError());
     
     return false;
     }
     
     // Run event for moved track
     Factory::getApplication()->triggerEvent('onContentAfterSave', ['com_xbmusic.track', &$this->table, false, $fieldsData]);
     }
     
     // Clean the cache
     $this->cleanCache();
     
     return true;
     }
     
     */
    

/////////////from getForm()
//         $params = ComponentHelper::getParams('com_xbmusic');
//         if ($this->params->get('use_xbmusic', 1)) {
//             $this->basemusicfolder = JPATH_ROOT.'xbmusic/'.$this->params->get('xbmusic_subfolder','');
//         } else {
//             $this->basemusicfolder = ($this->params->get('music_path','') != '') ? $this->params->get('music_path') : JPATH_ROOT.'/xbmusic/';
//         }
//         $form->setFieldAttribute('pathname','directory',$this->basemusicfolder,'general');


/*
 // Object uses for checking edit state permission of track
 $record = new \stdClass();
 
 $trackIdFromInput = $app->getInput()->getInt('id', 0);
 
 $id = (int) $this->getState('track.id', $trackIdFromInput);
 
 $record->id = $id;
 
 // For new tracks we load the potential state + associations
 if ($id == 0 && $formField = $form->getField('catid')) {
 $assignedCatids = $data['catid'] ?? $form->getValue('catid');
 
 $assignedCatids = \is_array($assignedCatids)
 ? (int) reset($assignedCatids)
 : (int) $assignedCatids;
 
 // Try to get the category from the category field
 if (empty($assignedCatids)) {
 $assignedCatids = $formField->getAttribute('default', null);
 
 if (!$assignedCatids) {
 // Choose the first category available
 $catOptions = $formField->options;
 
 if ($catOptions && !empty($catOptions[0]->value)) {
 $assignedCatids = (int) $catOptions[0]->value;
 }
 }
 }
 
 // Activate the reload of the form when category is changed
 $form->setFieldAttribute('catid', 'refresh-enabled', true);
 $form->setFieldAttribute('catid', 'refresh-cat-id', $assignedCatids);
 $form->setFieldAttribute('catid', 'refresh-section', 'track');
 
 // Store ID of the category uses for edit state permission check
 $record->catid = $assignedCatids;
 } else {
 // Get the category which the track is being added to
 if (!empty($data['catid'])) {
 $catId = (int) $data['catid'];
 } else {
 $catIds  = $form->getValue('catid');
 
 $catId = \is_array($catIds)
 ? (int) reset($catIds)
 : (int) $catIds;
 
 if (!$catId) {
 $catId = (int) $form->getFieldAttribute('catid', 'default', 0);
 }
 }
 
 $record->catid = $catId;
 }
 
 // Modify the form based on Edit State access controls.
 if (!$this->canEditState($record)) {
 // Disable fields for display.
 $form->setFieldAttribute('featured', 'disabled', 'true');
 //            $form->setFieldAttribute('featured_up', 'disabled', 'true');
 //            $form->setFieldAttribute('featured_down', 'disabled', 'true');
 $form->setFieldAttribute('ordering', 'disabled', 'true');
 $form->setFieldAttribute('publish_up', 'disabled', 'true');
 $form->setFieldAttribute('publish_down', 'disabled', 'true');
 $form->setFieldAttribute('state', 'disabled', 'true');
 
 // Disable fields while saving.
 // The controller has already verified this is an track you can edit.
 $form->setFieldAttribute('featured', 'filter', 'unset');
 //            $form->setFieldAttribute('featured_up', 'filter', 'unset');
 //            $form->setFieldAttribute('featured_down', 'filter', 'unset');
 $form->setFieldAttribute('ordering', 'filter', 'unset');
 $form->setFieldAttribute('publish_up', 'filter', 'unset');
 $form->setFieldAttribute('publish_down', 'filter', 'unset');
 $form->setFieldAttribute('state', 'filter', 'unset');
 }
 
 // Don't allow to change the created_by user if not allowed to access com_users.
 if (!$this->getCurrentUser()->authorise('core.manage', 'com_users')) {
 $form->setFieldAttribute('created_by', 'filter', 'unset');
 }
 
 */

//////////////////////////////////////////// from loadFromData()
//need to extract any genre tags and poke them into $data->genre
//ie filter tag list (comma sep string) by parent
// get genre_parent and if set
//
/*             $tagsHelper = new TagsHelper;
 $params = ComponentHelper::getParams('com_xbarticleman');
 $taggroup1_parent = $params->get('taggroup1_parent','');
 if ($taggroup1_parent && !(empty($data->tags))) {
 $taggroup1_tags = $tagsHelper->getTagTreeArray($taggroup1_parent);
 $data->taggroup1 = array_intersect($taggroup1_tags, explode(',', $data->tags->tags));
 }
 $taggroup2_parent = $params->get('taggroup2_parent','');
 if ($taggroup2_parent && !(empty($data->tags))) {
 $taggroup2_tags = $tagsHelper->getTagTreeArray($taggroup2_parent);
 $data->taggroup2 = array_intersect($taggroup2_tags, explode(',', $data->tags->tags));
 }
 $taggroup3_parent = $params->get('taggroup3_parent','');
 if ($taggroup3_parent && !(empty($data->tags))) {
 $taggroup3_tags = $tagsHelper->getTagTreeArray($taggroup3_parent);
 $data->taggroup3 = array_intersect($taggroup3_tags, explode(',', $data->tags->tags));
 }
 $taggroup4_parent = $params->get('taggroup4_parent','');
 if ($taggroup4_parent && !(empty($data->tags))) {
 $taggroup4_tags = $tagsHelper->getTagTreeArray($taggroup4_parent);
 $data->taggroup4 = array_intersect($taggroup4_tags, explode(',', $data->tags->tags));
 }
 */
//allow content plugins to preprocess
//$this->preprocessData('com_xbmusic.track', $data);


