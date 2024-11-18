<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Helper/XbmusicHelper.php
 * @version 0.0.18.9 16th November 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Helper;

defined('_JEXEC') or die;

//require_once(JPATH_COMPONENT_ADMINISTRATOR.'/src/Helper/getid3/getid3.php');
require_once (JPATH_COMPONENT_ADMINISTRATOR. '/vendor/getID3/j5getID3.php');

use Joomla\CMS\Factory;
use Joomla\CMS\Access\Access;
//use Joomla\CMS\Application\ApplicationHelper;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Component\ComponentHelper;
//use Joomla\CMS\Filter\OutputFilter;
//use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Language\Text;
//use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
//use Joomla\Database;
//use Joomla\Database\DatabaseInterface;
use Joomla\Database\DatabaseQuery;
use Joomla\Filter\OutputFilter;
//use DOMDocument;
use DateTime;
use Exception;
//use Symfony\Component\Validator\Constraints\Existence;
//use Crosborne\Component\Xbmusic\Administrator\Helper\getid3\Getid3;
//use Joomla\CMS\Filter\InputFilter;

class XbmusicHelper extends ComponentHelper
{
	public static $extension = 'com_xbmusic';
	
	public static $musicBase = JPATH_ROOT.'/xbmusic/';
	
	public static $linktypes = array('artisttrack', 'artistalbum', 'songtrack', 'songalbum', 'artistsong');

	public static function getActions($categoryid = 0) {
	    $user 	=Factory::getApplication()->getIdentity();
	    $result = new \stdClass();
	    if (empty($categoryid)) {
	        $assetName = 'com_xbmusic';
	        $level = 'component';
	    } else {
	        $assetName = 'com_xbmusic.category.'.(int) $categoryid;
	        $level = 'category';
	    }
	    $actions = Access::getActions('com_xbmusic', $level);
	    foreach ($actions as $action) {
	        $result->set($action->name, $user->authorise($action->name, $assetName));
	    }
	    return $result;
	}

	/**
	 * @name getFileId3()
	 * @desc this uses j5getID3.php to interface with the getID3 component.
	 * It does some preliminary processing of the raw id3 data to produce an array of 4 arrays
	 *  - audioinfo contains the data relating to the audio file itself (sample and bitrates etc)
	 *  - fileinfo contains details about the file (mim type, size etc)
	 *  - imageinfo contains info about embedded images including the image data blobs
	 *  - id3tags contains the actual id3 descriptors
	 * @param string $filename
	 * @return array(audioinfo, fileinfo, id3tags, imageinfo)
	 */
	public static function getFileId3(string $filename) {
//	    require_once (JPATH_COMPONENT_ADMINISTRATOR. '/vendor/getID3/j5getID3.php');
	    $ThisFileInfo = getIdData($filename);
	    $result = array();	 
	    $result['audioinfo'] = array();
	    $result['imageinfo'] = array();
	    $result['id3tags'] = array();
	    $result['fileinfo'] = array();
	    $result['fileinfo']['playtime_string'] = (isset($ThisFileInfo['playtime_string'])) ? $ThisFileInfo['playtime_string'] : '';
	    $result['fileinfo']['mime_type'] = (isset($ThisFileInfo['mime_type'])) ? $ThisFileInfo['mime_type'] : '';
	    $result['fileinfo']['filesize'] = (isset($ThisFileInfo['filesize'])) ? $ThisFileInfo['filesize'] : '';
	    $result['fileinfo']['fileformat'] = (isset($ThisFileInfo['fileformat'])) ? $ThisFileInfo['fileformat'] : '';
	    $result['audioinfo']['bitrate'] = (isset($ThisFileInfo['bitrate'])) ? $ThisFileInfo['bitrate'] : '';
	    $result['audioinfo']['channels'] = (isset($ThisFileInfo['audio']['channels'])) ? $ThisFileInfo['audio']['channels'] : '';
	    $result['audioinfo']['channelmode'] = (isset($ThisFileInfo['audio']['channelmode'])) ? $ThisFileInfo['audio']['channelmode'] : '';
	    $result['audioinfo']['sample_rate'] = (isset($ThisFileInfo['audio']['sample_rate'])) ? $ThisFileInfo['audio']['sample_rate'] : '';
	    $result['audioinfo']['bitrate_mode'] = (isset($ThisFileInfo['audio']['bitrate_mode'])) ? $ThisFileInfo['audio']['bitrate_mode'] : '';
	    $result['audioinfo']['compression_ratio'] = (isset($ThisFileInfo['audio']['compression_ratio'])) ? $ThisFileInfo['audio']['compression_ratio'] : '';
	    $result['audioinfo']['encoder_options'] = (isset($ThisFileInfo['audio']['encoder_options'])) ? $ThisFileInfo['audio']['encoder_options'] : '';
	    $result['audioinfo']['encoder'] = (isset($ThisFileInfo['audio']['encoder'])) ? $ThisFileInfo['audio']['encoder'] : '';
	    $result['audioinfo']['playtime_seconds'] = (isset($ThisFileInfo['playtime_seconds'])) ? $ThisFileInfo['playtime_seconds'] : '';
	    if(isset($ThisFileInfo['comments']['picture'][0])){
//            $image='data:'.$ThisFileInfo['comments']['picture'][0]['image_mime'].';charset=utf-8;base64,'.base64_encode($OldThisFileInfo['comments']['picture'][0]['data']);
//            $image = $ThisFileInfo['comments']['picture'][0]['data'];
//    	    unset($ThisFileInfo['comments']['picture'][0]['data']);
    	    $result['imageinfo'] = $ThisFileInfo['comments']['picture'][0]; //we're only getting the first image
    	    unset($ThisFileInfo['comments']['picture']);
	    }
	    if (isset($ThisFileInfo['comments']['music_cd_identifier'])) { //this can contain binary chars and screws things up
	        unset($ThisFileInfo['comments']['music_cd_identifier']);
	    }
	    if (isset($result['imageinfo']['description'])) { //fix for an album with odd encoding on picture description
	        $desc = $result['imageinfo']['description'];
	        $res = htmlentities($desc, ENT_QUOTES | ENT_IGNORE, 'UTF-8');
	        $res =  preg_replace('/[\x00-\x1F\x7F-\x9F]/u', '', $res);
	        $result['imageinfo']['description'] = $res;
	    }
        $id3tags = array();
	    foreach ($ThisFileInfo['comments'] as $key => $valuearr) {
	        // artist, album, genre and maybe others have been seen with mulitple entries
	        // concat them with ' || ' which will look ok if printed as string but allows to explode to array to handle values separately
            $id3tags[$key] = implode(' || ', $valuearr);
	    }
	    $result['id3tags'] = $id3tags;
	    return $result;
	} // end getFileID3()
	    
	/**
	 * @name id3dataToItems()
	 * @desc processes the id3tags only from getFileId3() to produce separate arrays of data available for items
	 * track, album, songs, artists, genres
	 * @param array $id3data - array returned by getFileID3() or from json id3data in track item
	 * @param string $flogdata
	 * @return array | false
	 */
	public static function id3dataToItems(array $id3data, string &$ilogmsg) {
	    $items = array();
	    $trackdata = array(); //only one track
	    $albumdata = array(); //only one album title allowed, if alternates present reported in log
	    $songdata = array(); //could be more than one song imploded with ' || '
	    $artistdata = array(); //could be more than one artist imploded with ' || '
	    $genres = array(); //will will create any genres we find and return them as array of id=>title
	    $images = array(); //we will create any images found and return as an array of data
	    if (isset($id3data['title'])) { 
	        $trackdata['title'] = $id3data['title'];
	    } else { //no title found
	        $ilogmsg .= '[ERROR] No track title found in ID3 data. Cannot import'."\n";
	        return false;
	    }
	    // trackdata['alias'] is title with suffix to make it unique in case of two tracks with same title
	    $trackdata['alias'] = XbcommonHelper::makeUniqueAlias($trackdata['title'],'#__xbmusic_tracks');
	    if (isset($id3data['track_number'])) $trackdata['track_number'] = $id3data['track_number'];
	    if (isset($id3data['part_of_a_set'])) $trackdata['part_of_a_set'] = $id3data['part_of_a_set'];
//	    if (isset($id3data['audioinfo']['playtime_seconds'])) $trackdata['duration'] = (int)$id3data['audioinfo']['playtime_seconds'];
	    // dates in id3 can be any format and may include y m and D or not
	    $datematch = '/(^(\d{4})$)|(^(\d{4})-{1}[0-1][1-9]$)|(^(\d{4})-{1}[0-1][1-9]-{1}[0-3][1-9]$)/';
	    if (isset($id3data['recording_time'])) {
	        if (preg_match($datematch,$id3data['recording_time'])==1) {
	            $trackdata['rec_date'] = ($id3data['recording_time']);
	        } else {
	            $ilogmsg .= '[WARNING] Recording date '.$id3data['recording_time'].' wrong format. Enter manually for track'."\n";
	        }
	    }
	    if (isset($id3data['year'])) {
	        $year = trim(explode('||', $id3data['year'])[0]);
	        if (preg_match($datematch,$year)==1) {
	            $trackdata['rel_date'] = $year;
	        } else {
	            $ilogmsg .= '[WARNING] Release date '.$id3data['year'].' wrong format. Enter manually for track and album'."\n";
	        }
	    } else {
	        $ilogmsg .= '[WARNING] No release date found. Enter manually for track and album'."\n";
	    }
	    
	    if (isset($id3data['track_number'])) $trackdata['trackno'] = $id3data['track_number'];
	    if (isset($id3data['totaltracks'])) $trackdata['disctracks'] = $id3data['totaltracks'];
	    
	    //get artist info
	    if (isset($id3data['artist'])) {
	        $artiststr = $id3data['artist'];
	        $artcnt = substr_count($artiststr,' || ') + 1;
	        if ($artcnt > 1) {
	            $ilogmsg .= '[INFO] '.$artiststr.' '.$artcnt.' artist entries found in ID3, only first will be used'."\n";
	        }
	        //the first artist in the list will become the track sortartist and album artist 
	        $origartist = substr($artiststr, 0, strpos($artiststr.' ||', ' ||')+1);
	        $trackdata['sortartist'] = XbcommonHelper::stripThe($origartist);
	        //now break any listed artists into separate if the have & or and or with or feat.
            $splits = array(" & "," and "," with "," featuring","feat.");
            $splitcnt = 0;
            $artiststr = str_replace($splits," || ",$artiststr,$splitcnt);
            if ($splitcnt > 0) {
                $ilogmsg .= '[INFO] artists have been split into separate names - please check results are correct'."\n";
            }           
            $artistarr = explode(' || ', $artiststr);
            count($artistarr);
	        foreach ($artistarr as $artistname) {
	            $artistname = trim($artistname);	        
	            $artist = array('name'=>$artistname, 'alias'=>XbcommonHelper::makeAlias($artistname));
	            $aid=XbcommonHelper::checkValueExists($artist['alias'], '#__xbmusic_artists', 'alias');
	            if ($aid>0) {
	                $artist['id'] = $aid;
	            } else {
	                $artist['id'] = 0;
	                $artist['sortname'] = XbcommonHelper::stripThe($artistname);
	            }
	            //we could end up with duplicate artists - need to make artistdata unique by alias
	            if (!key_exists($artist['alias'], $artistdata)) $artistdata[$artist['alias']] = $artist;
	        }
	    } //endif set artist
	    
	    //get album info
	    if (isset($id3data['album'])) {
	        $albumstr = $id3data['album'];
	        $albcnt = substr_count($albumstr,' || ') + 1;
	        if ($albcnt > 1) {
	            $ilogmsg .= '[INFO] more than one album name in ID3, only first will be used'."\n";
	            $ilogmsg .= '[INFO] '.$albumstr."\n";
	            $albumstr = trim(explode('||', $albumstr)[0]);
	        }
	        
	        $albumdata['title'] = $albumstr;
	        $albumalias = $albumstr; 
	        if (isset($id3data['band'])) {
	            $albumdata['albumartist'] = $id3data['band'];
	            $albumdata['sortartist'] = XbcommonHelper::stripThe($id3data['band']);
	        } else {
	            if (isset($id3data['artist'])) {
	                $albumdata['albumartist'] = $origartist;
	                $albumdata['sortartist']= XbcommonHelper::stripThe($origartist);
	            }
	        }
	        if (isset($albumdata['sortartist'])) $albumalias.= '-'.$albumdata['sortartist'];
	        $albumdata['alias'] = XbcommonHelper::makeAlias($albumalias);
	        $aid = XbcommonHelper::checkValueExists($albumdata['alias'], '#__xbmusic_albums', 'alias');
	        if ($aid>0) {
	            $albumdata['id'] = $aid;
	        } else {
	            $albumdata['id'] = 0;
	        }
            $albumdata['rel_date'] = $trackdata['rel_date'];
            if (isset($id3data['part_of_a_set'])) {
                $trackdata['discno'] = $id3data['part_of_a_set'];
                $setstr = explode('/',$id3data['part_of_a_set']);
                if (count($setstr)==2) {
                    $albumdata['num_discs'] = (int)$setstr[1];
                } else {
                    $ilogmsg .= '[WARNING] failed to parse `num_discs` from "'.$id3data['part_of_a_set'].'"\n';
                }
            }
            $albumdata['compilation'] = (isset($trackdata['part_of_a_compilation'])) ? '1' : '0';   
	    } //end albuminfo
	    
	    
	    //get song info
	    if (substr_count($trackdata['title'],'/')) {
	        // we have a medley of songs
	        $songtitles = explode('/',$trackdata['title']); 
	        $ilogmsg .= '[INFO] Slashes in "'.$trackdata['title'].'" implies '.count($songtitles).' songs. Created as separate songs'."\n"; 
	    } else {
	        if (strpos(strtolower($trackdata['title']),'medley') !== false)  {
	            $ilogmsg .= '[WARNING] "'.$trackdata['title'].'" contains "medley" but can\'t separate titles. Created as single song'."\n";	            
	        }
	        $songtitles = array($trackdata['title']);
	    }
	    foreach ($songtitles as $title) {
	        $title = trim($title);
	        $song = array('title' => $title, 'alias'=>XbcommonHelper::makeAlias($title));
	        $song['id'] = XbcommonHelper::checkValueExists($song['alias'], '#__xbmusic_songs', 'alias');
	        $songdata[] = $song;
	    } //end songinfo
	    
	    //get genres
	    if (isset($id3data['genre'])) {
	        $genres = self::createGenres($id3data['genre'], $ilogmsg);
	    }
	    
	    //images are handled separately
	    
	    if (!empty($trackdata)) $items['trackdata'] = $trackdata;
	    if (!empty($albumdata)) $items['albumdata'] = $albumdata;
	    if (!empty($songdata)) $items['songdata'] = $songdata;
	    if (!empty($artistdata)) $items['artistdata'] = $artistdata;
	    if (!empty($genres)) $items['genres'] = $genres;
	    return $items;
	    
	} // end id3dataToItems()
	
/**
 * @name addItemLinks()
 * @desc adds cross links between item types for a NEW item used without invoking the model. 
 * @param int $item_id - the id of a single item having links
 * @param string $itemtype - the item type - album|artist|playlist|song|track
 * @param array $linklist (id, role, note, optional:listorder)
 * @param string $listtype - the last word in the table name (artistalbum|artistsong|artisttrack|playlisttrack|songalbum|songtrack) 
 * @param string $action replace|merge (default) - if merging 
 * @return boolean
 */
	public static function addItemLinks(int $item_id,  array $itemlist, string $itemtype, string $linktype, string $action = 'merge') {
	       
	    $table = '#__xbmusic_'.$linktype;
	    $errcnt = 0;
	    $listtype = str_replace($itemtype, '', $linktype);  
	    $msg = 'adding '.count($itemlist).' '.$listtype.'s to '.$itemtype.'.'.$item_id;
	    $db = Factory::getDBO();
	    //$db = Factory::getContainer()->get(DatabaseInterface::class);
	    $query = $db->getQuery(true);
        //delete existing role list - this is a new item so there really should be any!
        $query->delete($db->qn($table));
	    $query->where($itemtype.'_id = '.$db->q($item_id));
	    $db->setQuery($query);
	    $db->execute();
	    //restore the new list
	    $listorder=0;
	    foreach ($itemlist as $linkitem) {
	        if (isset($linkitem['listorder'])) {
	            $listorder = $linkitem['listorder'];
	        } else {
	            $listorder ++;
	        }
//	        if (!isset($link['role'])) $link['role'] = '';
//	        if (!isset($link['note'])) $link['note'] = '';
	        
	        $query->clear();   
            $query->insert($db->quoteName($table));
            $query->columns($itemtype.'_id, '.$listtype.'_id, role, note, listorder');
            $query->values('"'.$item_id.'","'.$linkitem['id'].'","'.$linkitem['role'].'","'.$linkitem['note'].'","'.$listorder.'"');
            try {
	            $db->setQuery($query);
	            $db->execute();
            } catch (\Exception $e) {
     	        $dberr = $e->getMessage();
     	        Factory::getApplication()->enqueueMessage('addItemLinks() '.$dberr.'<br />Query: '.$query->dump(), 'error');
     	        $errcnt ++;               
            }
	    }
	    if ($errcnt>0) $msg .= ' '.$errcnt.' failed, rest ok';
	    return $msg;
	}
	
	
	public static function getArtistAlbums($aid) {
	    //$db = Factory::getContainer()->get(DatabaseInterface::cl    
	    $db = Factory::getDbo();
	    $query = $db->getQuery(true);
	    $query->select('DISTINCT a.id AS albumid, a.title AS albumtitle, a.rel_date, a.imgfile');
	    $query->from('#__xbmusic_albums AS a');
	    $query->join('LEFT','#__xbmusic_tracks AS t ON t.album_id = a.id');
	    $query->join('LEFT','#__xbmusic_artisttrack AS at ON at.track_id = t.id');
	    $query->where('at.artist_id = '.$aid.' AND t.album_id > 0');
	    $query->order('a.title ASC');
	    $db->setQuery($query);
	    return $db->loadAssocList();
	}
	
	public static function getGroupMembers($gid) {
	    //$db = Factory::getContainer()->get(DatabaseInterface::class);
	    $db = Factory::getDbo();
	    $query = $db->getQuery(true);
	    $query->select('a.id AS artistid, a.name AS artistname, gm.role, gm.from, gm.until, gm.note');
	    $query->join('LEFT','#__xbmusic_artists AS a ON a.id = gm.artist_id');
	    $query->from('#__xbmusic_groupmember AS gm');
	    $query->where('gm.group_id = '.$db->q($gid));
	    $query->order('gm.listorder ASC');
	    $db->setQuery($query);
	    return $db->loadAssocList();
	}
	
	public static function getArtistSingles($aid) {
//	    $db = Factory::getContainer()->get(DatabaseInterface::class);
	    $db = Factory::getDbo();
	    $query = $db->getQuery(true);
	    $query->select('t.id AS trackid, t.title AS tracktitle, t.imgfile, t.rel_date');
	    $query->join('LEFT','#__xbmusic_artisttrack AS at ON at.track_id = t.id');
	    $query->from('#__xbmusic_tracks AS t');
	    $query->where('t.album_id = 0 AND at.artist_id = '.$aid);
	    $query->order('t.rel_date, t.title ASC');
	    $db->setQuery($query);
	    return $db->loadAssocList();
	}
	
	/**
	 * @name createMusicItem()
	 * @desc Creates an xbMusic item with supplied data. Returns id or -id if alias exists. 
	 * Status, access, created & modified dates will be default values if missing. 
	 * Created_by will be set to user, alias will be created from title if missing (not if no title)
	 * Uses item model::save() to create item, so all valid data elements will be created, others will default.
	 * @param array $data
	 * @param string $table
	 * @return int|false - new item positive id, existing item negative id, or false on failure
	 */
	public static function createMusicItem(array $data, string $itemtype) {
	    $app = Factory::getApplication();
	    if (strpos(' track song playlist artist album ', $itemtype) == false) {
	        $app->enqueueMessage('Invalid itemtype to create','Error');
	        return false;
	    }
	    $id = XbcommonHelper::checkValueExists($data['alias'], '#__xbmusic_'.$itemtype.'s', 'alias');
	    if ($id !== false) return $id * -1;
	    if ($data['id'] == 0) unset($data['id']);
	    $itemid = false;
	    $sqldate = Factory::getDate()->toSql();
	    $user 	= $app->getIdentity();
	    if (!key_exists('status', $data))  $data['status'] = 1;
	    if (!key_exists('access', $data))  $data['access'] = 1;
	    if (!key_exists('created', $data))  $data['created'] = $sqldate;
	    if (!key_exists('modified', $data))  $data['modified'] = $sqldate;
	    if (!key_exists('created_by', $data))  $data['created_by'] = $user->id;
	    if ((!key_exists('alias', $data)) && (key_exists('title', $data))) 
	        $data['alias'] = XbcommonHelper::makeUniqueAlias($data['title'],'#__xbmusic_'.$itemtype.'s');
	    
//	    $itemmodel = $this->getMVCFactory()->createModel(ucfirst($itemtype), 'Administrator', ['ignore_request' => true]);
	    $itemmodel = $app->bootComponent('com_xbmusic')->getMVCFactory()
	       ->createModel(ucfirst($itemtype), 'Administrator', ['ignore_request' => true]);
	    if ($itemmodel->save($data) == false) {
	        $app->enqueueMessage('createMusicItem().'.$itemtype.' '.$itemmodel->getError(), 'Error');
	        return false;
	    }
        $itemid = $itemmodel->getState($itemtype.'.id');
	    return $itemid;
//	    }
//	    return false;
	}  // end createMusicItem()
	
	/**
	 * @name createImageFile()
	 * @desc takes image data from ID3 and a desired pathfilename from Joomla root and creates if not exists
	 * @param array $imgdata
	 * @param string $imgfilename
	 * @param string $flogmsg
	 * @return string|boolean - img url if exists or created or false on failure
	 */
	public static function createImageFile(array $imgdata, string $imgfilename, string &$flogmsg) {
	    $imgpath = pathinfo($imgfilename, PATHINFO_DIRNAME);
	    $folder = str_replace('/images/xbmusic/artwork/','',$imgpath);
	    $imgpath = JPATH_ROOT.$imgpath;
	    //create the folder if it doesn't exist (eg new initial)
	    if (file_exists($imgpath)==false) {
	        if (mkdir($imgpath,0775,true)) {
	            $flogmsg .= '[INFO] '.Text::_('artwork folder created',2).Xbtext::_($folder,13);
	        } else  {
	            $flogmsg .= '[ERROR] '.Text::_('failed to create artwork folder').Xbtext::_($imgpath,13);
	           return false;
	        }
	    }
	    $imgext = XbcommonHelper::imageMimeToExt($imgdata['image_mime']);
	    $imgfilename = $imgfilename.'.'.$imgext;
	    $xbfilename = $folder.'/'.pathinfo($imgfilename, PATHINFO_BASENAME);
	    $imgpathfile = JPATH_ROOT.$imgfilename;
	    $imgurl = Uri::root().$imgfilename;
	    $imgok = false;
	    if (file_exists($imgpathfile)) {
	        $imgok = true;
	        $flogmsg .= '[INFO] '.Text::sprintf('Artwork file "%s" already exists',$xbfilename)."\n";
	    } else {
	        $params = ComponentHelper::getParams('com_xbmusic');
	        $maxpx = $params->get('imagesize',500);
	        if ($imgdata['image_height'] > $maxpx) {
	            //need to resize image
	            $image = imagecreatefromstring($imgdata['data']);
	            $newimage = imagescale($image, $maxpx);
	            switch ($imgext) {
	                case 'jpg':
	                    $imgok = imagejpeg($newimage, $imgpathfile);
	                    break;
	                case 'png':
	                    $imgok = imagepng($newimage, $imgpathfile);
	                    break;
	                case 'gif':
	                    $imgok = imagegif($newimage, $imgpathfile);
	                    break;
	                default:
	                    $imgok = false;
	                    break;
	            }
	        } else {
	            $imgok = file_put_contents($imgpathfile, $imgdata['data']);
	        }
	    } //endif artfile !exists
	    if ($imgok) {
	        $flogmsg .= '[INFO] '.Text::_('image created').Xbtext::_($xbfilename,13);
	        return $imgurl;
	    }
	    $flogmsg .= '[ERROR] '.Text::_('failed to create image').Xbtext::_($imgpathfile,13);
	    
	    return false;
	} //end createImageFile()
	
	/**
	 * @name normaliseGenrename()
	 * @desc applies any normalisation to names for genres specified in component options
	 * @param string $genrename
	 * @param string $ilogmsg
	 * @return string
	 */
	public static function normaliseGenrename(string $genrename, string &$ilogmsg) {
	    $params = ComponentHelper::getParams('com_xbmusic');
	    $opthyphen = $params->get('genrehyphen',1);
	    $optspaces = $params->get('genrespaces',1);
	    $optcase = $params->get('genrecase',1);
	    $cnt = 0;
	    if ($opthyphen == 1) {
	        $genrename = str_replace('/','-',$genrename, $cnt);
	    } elseif ($opthyphen==2) {
	        $genrename = str_replace('-','/',$genrename, $cnt);
	    }
	    if ($optspaces == 1)  {
	        $genrename = str_replace(' ','-',$genrename, $cnt);
	    } elseif ($optspaces == 2) {
	        $genrename = str_replace(' ','/',$genrename, $cnt);
	    }
	    if ($cnt > 0) $ilogmsg .= $genrename.' normalized'."\n";
	    if ($optcase > 0) $genrename = strtolower($genrename);
	    if ($optcase == 1) $genrename = ucfirst($genrename);
	    return $genrename;	    
	} // end normaliseGenrename()
	
	/**
	 * @name createGenres()
	 * @desc takes a string of genre names separated by || and returns them as array of assoc array of data for each
	 * @param string $genrenames - multiple names separated by ' || '
	 * @param string $ilogmsg
	 * @return array genres data including 'isnew' if created
	 */
	public static function createGenres(string $genrenames, string &$ilogmsg) {
	    $params = ComponentHelper::getParams('com_xbmusic');
	    $optspaces = $params->get('genrespaces',1);
	    // if required split names with spaces into two or more genres "Folk Rock" -> "Folk" and "Rock"
	    if ($optspaces == 3) $genrenames = str_replace(array(' || ', ' '), '||', $genrenames);	        
	    $genres = array();
	    $genrenames = explode('||', $genrenames);
        //get the parent tag for genre tags
        $parentgenre = XbcommonHelper::getCreateTag(array('title'=>'Genres'));
	    foreach ($genrenames as &$genre) {
	        $genre = self::normaliseGenrename(trim($genre), $ilogmsg);
	        //get or create the genre tag id and title
	        $newtag = XbcommonHelper::getCreateTag(array('title'=>$genre, 'parent_id'=>$parentgenre['id'],
	                       'created_by_alias'=>'xbMusicHelper::createGenres()'));
	        if ($newtag) $genres[] = $newtag;
	    } //end foreach genre
	    return $genres;
	} //end createGenres()
	
	
	static function getTagItemCnts($id) {
	    $res = array('albumcnt'=>0, 'artistcnt'=>0, 'playlistcnt'=>0, 'songcnt'=>0, 'trackcnt'=>0, 'total'=>0);
	    $db = Factory::getDbo();
	    $db->setQuery('SELECT COUNT(*) FROM #__contentitem_tag_map AS al WHERE al.type_alias='.$db->quote('com_xbmusic.album').' AND al.tag_id = '.$db->q($id));
	    $res['albumcnt'] = $db->loadResult();
	    $db->setQuery('SELECT COUNT(*) FROM #__contentitem_tag_map AS al WHERE al.type_alias='.$db->quote('com_xbmusic.album').' AND al.tag_id = '.$db->q($id));
	    $res['artistcnt'] = $db->loadResult();
	    $db->setQuery('SELECT COUNT(*) FROM #__contentitem_tag_map AS al WHERE al.type_alias='.$db->quote('com_xbmusic.album').' AND al.tag_id = '.$db->q($id));
	    $res['playlistcnt'] = $db->loadResult();
	    $db->setQuery('SELECT COUNT(*) FROM #__contentitem_tag_map AS al WHERE al.type_alias='.$db->quote('com_xbmusic.album').' AND al.tag_id = '.$db->q($id));
	    $res['songcnt'] = $db->loadResult();
	    $db->setQuery('SELECT COUNT(*) FROM #__contentitem_tag_map AS al WHERE al.type_alias='.$db->quote('com_xbmusic.album').' AND al.tag_id = '.$db->q($id));
	    $res['trackcnt'] = $db->loadResult();
	    $tot = array_sum($res);
	    $res['total'] = $tot;
	    return $res;
	    
	} // end getTagItemCnts()

} //end xbmusicHelper

