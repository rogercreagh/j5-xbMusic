<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Model/TrackModel.php
 * @version 0.0.19.3 16th December 2024
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
use Crosborne\Component\Xbmusic\Administrator\Helper\Xbtext;
use \SimpleXMLElement;

class TrackModel extends AdminModel {
  
    public $typeAlias = 'com_xbmusic.track';
    
//    public $genreParentId = false;
    
//    private $id3loaded = false;
    
    protected $xbmusic_batch_commands = array(
        'untag' => 'batchUntag',
        'playlist' => 'batchPlaylist',
    );
    
    public function __construct($config = [], $factory = null, $form = null) {
//        $this->genreParentId = XbcommonHelper::getCreateTag(array('title'=>'MusicGenres',
//            'description'=>Text::_('XBMUSIC_ID3GENRES_TAG_DESC')),true);
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
    
    protected function batchPlaylist($value, $pks, $contexts) {
        Factory::getApplication()->enqueueMessage('Playlist add '.$value.' to '.implode(',',$pks).' contexts '.implode(','.$contexts));
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
//        $this->id3loaded = 0;
        $app  = Factory::getApplication();
        $app->setUserState('com_xbmusic.edit.track.id3data', null);
        $app->setUserState('com_xbmusic.edit.track.id3loaded', 0);
        //        $app->enqueueMessage('loadId3()');
        $data = $app->getInput()->get('jform',[],'array');
//         $app->enqueueMessage(print_r($data,true));
        if ($data['id']>0) {
            //this is an existing track we are reloading
            
        } else {
            $data['filepathname'] = JPATH_ROOT.'/xbmusic/'.$data['foldername'].$data['selectedfiles'];
            $data['filename'] = $data['selectedfiles'];
        }
        //TODO check if file exists in case something solly has been entered manually
        
//        $warnmsg = '';
//        $infomsg = '';
//        $ilogmsg = '';
        //start log
        $ilogmsg = XBINFO.str_replace(JPATH_ROOT,'',$data['filepathname'])."\n";
        
        $filedata = XbmusicHelper::getFileId3($data['filepathname']);
        $id3data = XbmusicHelper::id3dataToItems($filedata['id3tags'],$ilogmsg);
        if (isset($id3data['trackdata'])) {
            if (isset($filedata['imageinfo']['data'])){
                $imgdata = $filedata['imageinfo'];
                unset($filedata['imageinfo']['data']);
            }
            $trackdata = $id3data['trackdata'];
            $trackdata['filepathname'] = $data['filepathname'];
            $trackdata['filename'] = $data['filename'];
            $trackdata['foldername'] = $data['foldername'];
            $trackdata['selectedfiles'] = $data['selectedfiles'];
            // first we'll get the genres and categories
            $params = ComponentHelper::getParams('com_xbmusic');
            $optalbsong = $params->get('genrealbsong',0);
            $optcattag = $params->get('genrecattag',2);
            $genreids = [];
            $defcats = XbmusicHelper::getItemDefcats();
            $trackdata['catid'] = $defcats->trackcatid;
            if (isset($id3data['genres'])) {
                $genreids = array_column($id3data['genres'],'id');
                $usedaycat = $params->get('impcat','0');
                if (!$usedaycat && ($optcattag & 1)) {
                    //need to create a genre category and assign it to defcat->albumcatid
                    $thisgid = XbcommonHelper::getCatByAlias($id3data['genrs'][0]['alias'])->id;
                    if (is_null($thisgid)) {
                        //get Genre in Tracks category
                        $gcat = XbcommonHelper::getCatByAlias('musicgenres');
                        if ($gcat->id > 0) {
                            $gid = $gcat->id;
                        } else {
                            //we need to create the Tracks/MusicGenres category
                            $tcat = XbcommonHelper::getCatByAlias('tracks');
                            $gpid = ($tcat->id > 0) ? $tcat->id : 1; //if the tracks category has been deleted fallback to root
                            $gid = XbcommonHelper::getCreateCat(array('title'=>'MusicGenres', 'alias'=>'musicgenres', 'parent_id'=>$gpid),true)->id;
                        }
                        $thisgid = XbcommonHelper::getCreateCat(array('title'=>$id3data['genres'][0]['title'], 'alias'=>$id3data['genres'][0]['alias'], 'parent_id'=>$gid),true)->id;
                    }
                    $trackdata['catid'] = $thisgid;
                } else {
                }
                $trackdata['tags'] = $genreids;
            } //endif genres set
            //
            if (isset($filedata['audioinfo']['playtime_seconds'])) $trackdata['duration'] = (int)$filedata['audioinfo']['playtime_seconds'];
            
            $trackdata['id3tags'] = json_encode($filedata['id3tags']);
            $trackdata['fileinfo'] = json_encode($filedata['fileinfo']);
            $trackdata['audioinfo'] = json_encode($filedata['audioinfo']);
            
// get the song data and save in trackdata
            if (isset($id3data['songdata'])) {
                $trackdata['song'] = $id3data['songdata'];
                $trackdata['song']['catid'] = $defcats->songcatid;
                $trackdata['song']['id'] = XbmusicHelper::getItemIdFromAlias('#__xbmusic_songs', $trackdata['song']['alias']);
                if ($optalbsong & 1) $trackdata['song']['tags'] = $genreids;
            } 
            //songlinks will bee generating on save and adding to artist album and track

// get artist details and save in trackdata            
            if (isset($id3data['artistdata'])) {
                foreach ($id3data['artistdata'] as &$artist) {
                    $artist['catid'] = $this->artistcatid;
                    if (isset($trackdata['url_artist'])) {
                        $artist['ext_links']['ext_links0']= array('link_text'=>'internet',
                            'link_url'=>$trackdata['url_artist'], 
                            'link_title'=>basename($trackdata['url_artist']), // TODO actually we want the domain name and maybe path
              'link_desc'=>Text::sprintf('link found in track "%s" ID3 data', $trackdata['title'])                                        
                        );
                    }
                }
                $trackdata['artists'] = $id3data['artistdata'];//
            } 
            //artistlinks will be created on save and added to album and song
            
//6. Create image file if available
            if (($imgdata['data'])){
                $imgfilename = '/images/xbmusic/artwork/';
                if (isset($id3data['albumdata']['alias'])) {
                    $imgfilename .= 'albums/'.strtolower($id3data['albumdata']['alias'][0]).'/'.$id3data['albumdata']['alias'];
                } else {
                    $imgfilename .= 'singles/'.$trackdata['alias'];
                }
                if (isset($trackdata['sortartist'])) $imgfilename .= '_'.XbcommonHelper::makeAlias($trackdata['sortartist']);
                $imgurl = XbmusicHelper::createImageFile($imgdata, $imgfilename, $ilogmsg);
                if ($imgurl !== false) {
                    $imgdata['imagetitle'] = $imgdata['picturetype'];
                    $imgdata['imagedesc'] = $imgdata['description'];
                    $trackdata['imgurl'] = $imgurl;
                    $trackdata['imageinfo'] = $imgdata; // json_encode on save
                } else {
                    unset ($imgdata);
                }
            } //end ifset image data
            // img url and info will need adding to album
            
            
// get album details
            if (isset($id3data['albumdata'])) {
                $id3data['albumdata'] = $id3data['albumdata'];
                $id3data['albumdata']['catid'] = $this->albumcatid;
                if (isset($imgdata)) $id3data['albumdata']['imageinfo'] = $imgdata;
                if ($optalbsong > 1) $id3data['albumdata']['tags'] = $genreids;
                if ($imgurl != false) $id3data['albumdata']['imgurl'] = $imgurl;
                $trackdata['albumdata'] = $id3data['albumdata'];
                $trackdata['albumdata']['id'] = XbmusicHelper::getItemIdFromAlias('#__xbmusic_albums', $trackdata['albumdata']['alias']);
            }
            $trackdata['logmsg'] = $ilogmsg;
            $app->setUserState('com_xbmusic.edit.track.id3data', $trackdata); 
            $app->setUserState('com_xbmusic.edit.track.id3loaded', 1);
            
//            $this->id3loaded = (empty ($app->getUserState('com_xbmusic.edit.track.id3data', []))) ? false : true;
        } else {
            //no trackdata found in id3 - we've already reported this in id3dataToItems()
        }
        
        //we will only write the log when item is saved update the log file with counts at the top
//         $loghead .= '[SUM] '.$cnts['newtrk'].' new tracks, '.$cnts['duptrk'].' duplicates'."\n";
//         $loghead .= '[SUM] '.$cnts['newalb'].' new albums, '.$cnts['newart'].' new artists, '.$cnts['newsng'].' new songs, '."\n";
//         $loghead .= '[SUM] Elapsed time '.date('s', time()-$starttime).' seconds'."\n";
//         $loghead .= " -------------------------- \n";
//         $logmsg = $loghead.$ilogmsg;
//         $logmsg .= '======================================'."\n\n";
//         XbmusicHelper::writelog($logmsg);
        
//        $app->setUserState('com_xbmusic.edit.track.data', $data);
        return true;
                            
    } //end loadId3()
    
 
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
        $app  = Factory::getApplication();
        if ($item = parent::getItem($pk)) {
            if (!empty($item->id)) {
                $tagsHelper = new TagsHelper();
                $item->tags = $tagsHelper->getTagIds($item->id, 'com_xbmusic.track');
                if ($item->album_id > 0) $item->album = $this->getAlbum($item->album_id);               
                $item->artists = $this->getTrackArtistList($item->id);
                $item->songs = $this->getTrackSongList($item->id);
            if ($item->id3tags) $item->id3_tags = json_decode($item->id3tags);
            if ($item->audioinfo) $item->audioinfo = json_decode($item->audioinfo);
            if ($item->fileinfo) $item->fileinfo = json_decode($item->fileinfo);
            if ($item->imageinfo) $item->imageinfo = json_decode($item->imageinfo);
            $item->image_type = ($item->imageinfo) ? $item->imageinfo->picturetype : '';
            $item->image_desc = ($item->imageinfo) ? $item->imageinfo->description : '';   
            $item->albumimage = ($item->album_id > 0) ? $item->album['imgurl'] :'';

            if ($app->getUserState('com_xbmusic.edit.track.id3loaded', 0) == 1) {
                $id3data = $app->getUserState('com_xbmusic.edit.track.id3data', []);
                if (!empty($id3data)) {                   
                    $app->enqueueMessage('New ID3 Data loaded but not yet saved','Warning');
                    //$item->fileinfo = json_decode($id3data['fileinfo']);
                    //$item->fileinfo = json_decode($id3data['fileinfo']);
                    $item->imageinfo = (object)$id3data['imageinfo'];
                    //$item->audioinfo = json_decode($id3data['audioinfo']);
                    //$item->id3tags = json_decode($id3data['id3tags']);
                    //$item->imgurl = $id3data['imgurl'];
                }
            }
            } else {
                //we have an item that hasn't been saved
            }
        } else {
            //we have no item
            
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
        $tags = $form->getValue('tags',null,'');
        $tagsarr = (is_array($tags)) ? $tags : explode(',',$tags);
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
        if ($app->getUserState('com_xbmusic.edit.track.id3loaded', 0) == 1) {
            $id3data = $app->getUserState('com_xbmusic.edit.track.id3data', []);
            if (!empty($id3data)) {
                // overwrite these fields even if set
                $fields = array('title','alias','filepathname','filename','foldername',
                  'selectedfiles','catid','sortartist','rec_date','rel_date','duration',
                    'imgurl','discno','trackno'
                );
                $replaced = $this->setFields($fields,$data,$id3data);
                if (($data->id > 0) && (!empty($replaced))) {
                    $msg = 'Some data has been overwritten by new values from ID3 import. ';
                    $msg .= 'If previous values existed they are shown below the fields in red';
                    $msg .= '<br />Check restore any old values by copy/pasting before saving if necessary';
                    $msg .= '<br />Any new artists, songs or albums have not yet been created, but will be added on Save';
                    $app->enqueueMessage($msg,'Warning');
                    $app->setUserState('com_xbmusic.edit.track.replaced', $replaced);
                }

                //any new tags get added
                if (!empty($data->tags)) {
                    $data->tags .= ','.implode(',',$id3data['tags']);
                } elseif (key_exists('tags',$id3data)) {
                    $data->tags = $id3data['tags'];
                }
             }               
        }            
        return $data;
    }
    
    private function setFields(array $names, &$olddata, $newdata) {
        $replaced = [];
        foreach ($names as $name) {
            if (!empty($newdata[$name])) {
                if (empty($olddata->$name)) { 
                    $replaced[$name] = 'blank';
                    $olddata->$name = $newdata[$name];
                } elseif ($olddata->$name != $newdata[$name]) {
                    $replaced[$name] = $olddata->$name;
                    $olddata->$name = $newdata[$name];
                }
            }
        }
        return $replaced;
    }
       
    public function save($trackdata) {     
        
        $app  = Factory::getApplication();
        $params = ComponentHelper::getParams('com_xbmusic');
        if ($app->getUserState('com_xbmusic.edit.track.id3loaded', 0) == 1) {
            $id3data = $app->getUserState('com_xbmusic.edit.track.id3data', []);
            if (!empty($id3data)) {
                $loglevel = $params->get('loglevel',3);
                if ($loglevel>0) {
                    $loghead = '[LOADID3] Load ID3 Started '.date('H:i:s D jS M Y')."\n";
                    $cnts = array('newtrk'=>0,'duptrk'=>0,'newalb'=>0,'newart'=>0,'newsng'=>0,'errtrk'=>0);
                    $ilogmsg = $id3data['logmsg'];
                }
                if (!empty($id3data['song'])) {
                    $id = XbmusicHelper::createMusicItem($id3data['song'], 'song');
                    if ($id < 0) {
                        $id = $id * -1;
                        if ($loglevel >3) $ilogmsg .= XBINFO.''; //********************
                    } elseif ($id > 0) {
                        $trackdata['songlist'][] = array('song_id'=>$id,'role'=>'','note'=>'');;
                    } else {
                        
                    }
                }
                if (!empty($id3data['artists'])) {
                    foreach ($id3data['artists'] as $artist) {
                        $id = XbmusicHelper::createMusicItem($artist, 'artist');
                        if ($id < 0) $id = $id * -1;
                        if ($id > 0) $trackdata['artistlist'][] = array('artist_id'=>$id,'role'=>'','note'=>'');
                    }
                }
                if (!empty($id3data['albumdata'])) {
                    $id = XbmusicHelper::createMusicItem($id3data['albumdata'], 'album');
                    if ($id < 0) $id = $id * -1;
                    if ($id > 0) $trackdata['album_id'] = $id;
                }
                $trackdata['fileinfo'] = $id3data['fileinfo'];
                $trackdata['imageinfo'] = json_encode($id3data['imageinfo']);
                $trackdata['audioinfo'] = $id3data['audioinfo'];
                $trackdata['id3tags'] = $id3data['id3tags'];
            }
        }
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
        // check if track alias already exists and if it does append [basename(imagename)]
        if (XbcommonHelper::checkValueExists($trackdata['alias'], '#__xbmusic_tracks', 'alias')) {
            $append = '';
            if (key_exists('sortartist',$trackdata)) $append = $trackdata['sortartist'];
            if ($trackdata['album_id'] > 0) $append .= ' '.$id3data['albumdata']['alias'];
            if ($append != '') $append = ' ['.$append.']';
            $trackdata['alias'] = XbcommonHelper::makeUniqueAlias($trackdata['alias'].$append, '#__xbmusic_tracks');
            //$msg .= ' - '.Text::sprintf('Trying save with alias %s',$trackdata['alias']);
        }
        if (parent::save($trackdata)) {
            $app->setUserState('com_xbmusic.edit.track.id3data', null);
            $app->setUserState('com_xbmusic.edit.track.id3loaded', 0);
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
    
    /**
     * @name postSaveID3()
     * @desc after saving track this creates & links songs, artists, 
     * @param array $data the track data - song and artists lists will be updated
     * @param integer $tid - the id of the saved track
     */
/**     public function postSaveID3(&$data, int $tid) {
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
                /***
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
                    XbcommonHelper::addTagToGroup('MusicGenres', 'com_xbmusic.songtagparents');
                    XbcommonHelper::addTagToItems('com_xbmusic.song', $songid, $tagid);
                    $infomsg .= Text::sprintf('Tag "%s" added to song "%s"',$tagname,$songtitle).'<br />';
                }
                if ($addgenre > 1) {
                    //check if id3genres is in taggroups for albums
                    XbcommonHelper::addTagToGroup('MusicGenres', 'com_xbmusic.albumtagparents');
                    XbcommonHelper::addTagToItems('com_xbmusic.album', $data['album_id'], $tagid);
                    $infomsg .= Text::sprintf('Tag "%s" added to album %s', $tagname, $albumtitle).'<br />';
                }
            }
        }
        if ($infomsg!='') $app->enqueueMessage($infomsg, 'Info');
        if ($warnmsg!='') $app->enqueueMessage($warnmsg,'Warning');
        return true;   
        
    } //end function postSaveID3()
    
 */    private function getAlbum($albumid) {
        $album = [];
        if ($albumid >0) {
            $db = $this->getDatabase();
            $query = $db->getQuery(true);
            $query->select('id, title, sortartist, rel_date, imgurl');
            $query->from('#__xbmusic_albums');
            $query->where($db->qn('id').' = '.$db->q($albumid));
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
    
/**   public function getCreateArtist($name) {
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
 */    
    /**
     * @desc returns id as negative number if song already exists or positive if song is new
     * @param string $title
     * @param integer $tid
     * @param string $composer
     * @return number|mixed
     */
/**     public function getCreateSong($title, $tid, $composer = '') {
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
 */    
/**     public function getCreateAlbum($title, $artist, $reldate, $imgurl, $numdiscs) {
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
 */        
/**     private function canCreateCategory() {
        return $this->getCurrentUser()->authorise('core.create', 'com_content');
    }
 */
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
    
/**     public function createArtistAlbum(int $artist_id, int $album_id, $role = '', $note = '') {
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

 */    /*
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
    
}
 