<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Helper/XbmusicHelper.php
 * @version 0.0.18.8 9th November 2024
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
	        if (preg_match($datematch,$id3data['year'])==1) {
	            $trackdata['rel_date'] = $id3data['year'];
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
	            $ilogmsg .= '[INFO] '.$artcnt.' artist entries found in ID3, only first will be used'."/n";
	        }
	        //the first artist in the list will become the track sortartist and album artist 
	        $origartist = substr($artiststr, 0, strpos($artiststr.' ||', ' ||')+1);
	        $trackdata['sortartist'] = XbcommonHelper::stripThe($origartist);
	        //now break any listed artists into separate if the have & or and or with or feat.
            $splits = array(" & "," and "," with "," feat");
            $splitcnt = 0;
            $artiststr = str_replace($splits," || ",$artiststr,$splitcnt);
            if ($splitcnt > 0) {
                $ilogmsg .= '[INFO] artists have been split into separate names - please check results are correct'."/n";
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
	            $ilogmsg .= '[INFO] more than one album name in ID3, only first will be used'."/n";
	            $ilogmsg .= '[INFO] '.$albumstr."/n";
	            $albumstr = explode(' || ', $albumstr)[0];
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
            $albumdata['compilation'] = (isset($trackdata['part_of_a_compilation']) ? true : false;   
	    } //end albuminfo
	    
	    
	    //get song info
	    if (substr_count($trackdata['title'],'/')) {
	        // we have a medley of songs
	        $songtitles = explode('/',$trackdata['title']); 
	        $ilogmsg .= '[INFO] Slashes in "'.$trackdata['title'].'" implies '.count($songtitles).' songs. Created as separate songs'."/n"; 
	    } else {
	        if (strpos(strtolower($trackdata['title']),'medley') !== false)  {
	            $ilogmsg .= '[WARNING] "'.$trackdata['title'].'" contains "medley" but can\'t separate titles. Created as single song'."/n";	            
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
	
// 	public static function getMusicBase() {
// 	    return self::$musicBase; 
// 	}
	    
	public static function getArtistAlbums($aid) {
	    //$db = Factory::getContainer()->get(DatabaseInterface::cl    $db = Factory::getDbo();
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
	 * @desc Creates an xbMusic item with supplied data. 
	 * status, access, created & modified dates will be default values if missing, 
	 * Created_by will be set to user, alias will be created from title if missing (not if no title)
	 * @param array $data
	 * @param string $table
	 * @return int|false - new item id or false on failure
	 */
	public static function createMusicItem(array $data, string $itemtype) {
	    $app = Factory::getApplication();
	    if (strpos(' track song playlist artist album ', $itemtype) == false) {
	        $app->enqueueMessage('Invalid itemtype to create','Error');
	        return false;
	    }
	    $itemid = false;
	    $sqldate = Factory::getDate()->toSql();
	    $user 	= $app->getIdentity();
	    if (!key_exists('status', $data))  $data['status'] = 1;
	    if (!key_exists('access', $data))  $data['access'] = 1;
	    if (!key_exists('created', $data))  $data['created'] = $sqldate;
	    if (!key_exists('modified', $data))  $data['modified'] = $sqldate;
	    if (!key_exists('created_by', $data))  $data['created_by'] = $user;
	    if ((!key_exists('alias', $data)) && (key_exists('title', $data))) 
	        $data['alias'] = XbcommonHelper::makeUniqueAlias($data['title'],'#__xbmusic_'.$itemtype);
	    
//	    $itemmodel = $this->getMVCFactory()->createModel(ucfirst($itemtype), 'Administrator', ['ignore_request' => true]);
	    $itemmodel = $app->bootComponent('com_xbmusic')->getMVCFactory()
	       ->createModel(ucfirst($itemtype), 'Administrator', ['ignore_request' => true]);
// 	    if ($itemtype == 'track') {
// 	        if (!$itemmodel->simpleSave($data)) {
// 	            $app->enqueueMessage('createMusicItem().'.$itemtype.' '.$itemmodel->getError(), 'Error');
// 	            return false;
// 	        }
// 	        $itemid = $itemmodel->getState($itemtype.'.id');
// 	        return $itemid;
	        
// 	    } else {
    	    if (!$itemmodel->save($data)) {
    	        $app->enqueueMessage('createMusicItem().'.$itemtype.' '.$itemmodel->getError(), 'Error');
    	        return false;
    	    }
            $itemid = $itemmodel->getState($itemtype.'.id');
    	    return $itemid;
//	    }
//	    return false;
	}
	
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
	    //create the folder if it doesn't exist (eg new initial)
	    if (file_exists($imgpath)==false) {
	        mkdir(JPATH_ROOT.$imgpath,0775,true);
	        $flogmsg .= '[INFO] '.Xbtext::_('artwork folder created',2).$imgpath."\n";
	    }
	    $imgext = XbcommonHelper::imageMimeToExt($imgdata['image_mime']);
	    $imgfilename = OutputFilter::stringURLSafe(str_replace(' & ',' and ', $imgfilename)).'.'.$imgext;
	    $imgpathfile = JPATH_ROOT.$imgfilename;
	    $imgurl = Uri::root().$imgfilename;
	    $imgok = false;
	    if (file_exists($imgpathfile)) {
	        $imgok = true;
	        $flogmsg .= '[INFO] '.Text::sprintf('Artwork file %s already exists',$imgfilename)."\n";
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
	    if ($imgok) return $imgurl;
	    
	    return false;
	}
	
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
	        $genre = self::normaliseGenrename(trim($genre));
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
	    
	}
	
	
	
// /****************** xbLibrary functions ***********/
// 	/** Sections
// 	 * 1. Categories
// 	 * 2. Tags
// 	 * 3. Database
// 	 * 3. Text
// 	 * 4. Files
// 	 */

// /**************** 1. Category Functions ********************/
	
// 	/**
// 	 * @name createCategories()
// 	 * @desc function to create several categories
// 	 *     doesn't check if categories already exist - do this first with checkValueExists() or you'll get an error message.
// 	 * @param array $catsarr which contains assoc array of data for each category containing at least 'title' element
// 	 * @param boolean $silent - enable messages if false. You'll still get a duplicate alias message if the tag already exists.
// 	 * @return array of objects containg id and title of new categories
// 	 */
// 	public static function createCategories(array $catsarr, $silent = true) {
// 	    $result = array();
// 	    foreach ($catsarr as $cat) {
// 	        $wynik = self::createCategory($cat, $silent);
// 	        if (!empty($wynik)) $result[] = $wynik;
// 	    }
// 	    return $result;
// 	}
	
// 	/**
// 	 * @name createCategory()
// 	 * @desc creates a category with the passed title and optional other fields including parent_id and returns an object containg the id and title
// 	 *     doesn't check if category already exists - do this first with checkValueExists() or you'll get an error message.
// 	 * @param assoc array $catdata
// 	 * @param boolean $silent - enable messages if false. You'll still get a duplicate alias message if the category already exists.
// 	 * @return \stdClass
// 	 */
// 	public static function createCategory(array $catdata, $silent = false) {
// 	    $app = Factory::getApplication();
// 	    $errmsg = '';
// 	    $infomsg = '';
// 	    $wynik = new \stdClass();
// 	    if (!key_exists('published', $catdata))  $catdata['published'] = 1;
// 	    if (!key_exists('parent_id', $catdata))  $catdata['parent_id'] = 1;
// 	    if (!key_exists('langauge', $catdata))  $catdata['language'] = '*';
// 	    if (!key_exists('description', $catdata))  $catdata['description'] = '';
// 	    if (!key_exists('extension', $catdata))  $catdata['extension'] = 'com_xbmusic';
// 	    $catModel = Factory::getApplication()->bootComponent('com_categories')
// 	    ->getMVCFactory()->createModel('Category', 'Administrator', ['ignore_request' => true]);
// 	    if (!$catModel->save($catdata)) {
// 	        $errmsg = $catModel->getError();
// 	    } else {
// 	        $catid = $catModel->getState('category.id');
// 	        $infomsg .= 'New category '.$catdata['title'].' created with id '.$catid;
// 	        $wynik->id = $catid;
// 	        $wynik->title = $catdata['title'];
// 	    }
// 	    if ($errmsg != '') $app->enqueueMessage('createCategory() '.$errmsg, 'Warning');
// 	    if ($infomsg != '') $app->enqueueMessage('createCategory() '.$infomsg,'Info');
// 	    return $wynik;
// 	}
	
// 	public static function getCreateCat(array $catdata, $silent = false ) {
// 	    $catid = self::checkValueExists($catdata['alias'], '#__categories', 'alias', "`extension` = 'com_xbmusic'");
// 	    if ($catid == false) $catid = self::createCategory($catdata, $silent)->id;
// 	    return $catid;
// 	}
// 	/**
// 	 * @name getCat()
// 	 * @desc given category id returns full row
// 	 * @param int $catid
// 	 * @return object|null
// 	 */
// 	public static function getCat(int $catid) {
// 	    $db = Factory::getDbo();
// 	    //$db = Factory::getContainer()->get(DatabaseInterface::class);
// 	    $query = $db->getQuery(true);
// 	    $query->select('*')
// 	    ->from('#__categories AS a ')
// 	    ->where('a.id = '.$db->q($catid));
// 	    $db->setQuery($query);
// 	    return $db->loadObject();
// 	}
	
// 	/**
// 	 * @name getCatByAlias()
// 	 * @desc given category alias returns full row
// 	 * @param string $catalias
// 	 * @param string $extension
// 	 * @return object|null
// 	 */
// 	public static function getCatByAlias(string $catalias, $extension = 'com_xbmusic') {
// 	    $db = Factory::getDbo();
// 	    //$db = Factory::getContainer()->get(DatabaseInterface::class);
// 	    $query = $db->getQuery(true);
// 	    $query->select('*')
// 	    ->from('#__categories AS a ')
// 	    ->where('a.alias = '.$db->q($catalias));
// 	    $db->setQuery($query);
// 	    return $db->loadObject();
// 	}
	
// 	/**
// 	 * @name getCatChildren()
// 	 * @desc retruns all descendents of given category
// 	 * @param int $id
// 	 */
// 	public static function getCatChildren($pathorid) {
// 	    if (is_int($pathorid)) {
// 	        $path = self::getCat($pathorid)->path;
// 	    } else if (is_string($pathorid)) {
// 	        $path = $pathorid;
// 	    } else {
// 	        return false;
// 	    }
// 	    //	    $db = Factory::getContainer()->get(DatabaseInterface::class);
// 	    $db = Factory::getDbo();
// 	    $query = $db->getQuery(true);
// 	    $query->select('*');
// 	    $query->select('(SELECT COUNT(*) FROM '.$db->qn('#__categories').' AS ccnt WHERE ccnt.parent_id = c.id) AS childcnt');
// 	    $query->from($db->qn('#__categories').' AS c');
// 	    $query->where($db->qn('path').' LIKE '.$db->q($path.'/%'));
// 	    $query->order($db->qn('path'));
// 	    $db->setQuery($query);
// 	    $result = $db->loadAssocList();
// 	    if (!is_null($result)) {
// 	        foreach($result as $i=>$item) {
// 	            $result[$i]['itemcnt'] = self::getCatItemCnts($result[$i]['id']);
// 	        }
// 	    }
// 	    return $result;
// 	}
	
// 	public static function getCatItemCnts($id) {
// 	    $res = array('albumcnt'=>0, 'artistcnt'=>0, 'playlistcnt'=>0, 'songcnt'=>0, 'trackcnt'=>0, 'total'=>0);
// 	    $db = Factory::getDbo();
// 	    $db->setQuery('SELECT COUNT(*) FROM #__xbmusic_albums AS a WHERE a.catid = '.$db->q($id));
// 	    $res['albumcnt'] = $db->loadResult();
// 	    $db->setQuery('SELECT COUNT(*) FROM #__xbmusic_artists AS a WHERE a.catid = '.$db->q($id));
// 	    $res['artistcnt'] = $db->loadResult();
// 	    $db->setQuery('SELECT COUNT(*) FROM #__xbmusic_playlists AS a WHERE a.catid = '.$db->q($id));
// 	    $res['playlistcnt'] = $db->loadResult();
// 	    $db->setQuery('SELECT COUNT(*) FROM #__xbmusic_songs AS a WHERE a.catid = '.$db->q($id));
// 	    $res['songcnt'] = $db->loadResult();
// 	    $db->setQuery('SELECT COUNT(*) FROM #__xbmusic_tracks AS a WHERE a.catid = '.$db->q($id));
// 	    $res['trackcnt'] = $db->loadResult();
// 	    $tot = array_sum($res);
// 	    $res['total'] = $tot;
// 	    return $res;
// 	}
	
// /**************** 2. Tag Functions ********************/
	
// 	/**
// 	 * @name getCreateTags()
// 	 * @desc function to create a tag with title, parent_tag_id, status, and optional description
// 	 * @param array $tagsarr which contains assoc array of data for each tag containing at least 'title' element
// 	 * @param boolean $silent - enable messages if false. You'll still get a duplicate alias message if the tag already exists.
// 	 * @return array of arrays containg id, title, & alias of all tags
// 	 */
// 	public static function getCreateTags(array $tagsarr, $silent = true) {
// 	    $result = array();
// 	    foreach ($tagsarr as $tag) {	        
// 	        $wynik = self::getCreateTag($tag, $silent);
// 	        if (!empty($wynik)) $result[] = $wynik;
// 	    }
// 	    return $result;
// 	}
		
// 	/**
// 	 * @name getCreateTag()
// 	 * @desc creates a tag with the passed title and optional other fields including parent_id and returns an object containg the id and title
// 	 * @param array $tagdata - ['title'=>$mynewtitle] is required as minimum
// 	 * @param boolean $silent - supress messages if true. 
// 	 * @return assoc array(id, title, alias). Will be empty if the function failed
// 	 */
// 	public static function getCreateTag(array $tagdata, $silent = false) {
// 	    $errmsg = '';
// 	    $infomsg = '';
//         if (!isset($tagdata['alias'])) $tagdata['alias'] = self::makeAlias($tagdata['title']);
//         $tagdata['id'] = self::checkValueExists($tagdata['alias'], '#__tags', 'alias');
//         if ($tagdata['id']) {
//             return $tagdata;
//         }
//         unset($tagdata['id']);
//         // doesnt already exist so set defaults for status & parent
//         if (!key_exists('published', $tagdata))  $tagdata['published'] = 1;
//         if (!key_exists('parent_id', $tagdata))  $tagdata['parent_id'] = 1;
//         if (!key_exists('langauge', $tagdata))  $tagdata['language'] = '*';
//         if (!key_exists('description', $tagdata))  $tagdata['description'] = '';
//         if (!key_exists('created_by_alias', $tagdata))  $tagdata['created_by_alias'] = 'xbMusicHelper::getCreateTag()';
        
//         // Create new tag.
//         $tagModel = Factory::getApplication()->bootComponent('com_tags')
//            ->getMVCFactory()->createModel('Tag', 'Administrator', ['ignore_request' => true]);	        
//         if (!$tagModel->save($tagdata)) {
//             $errmsg = $tagModel->getError();	            
//         } else {
// 	        $tagid = $tagModel->getState('tag.id');
// 	        $infomsg .= 'New tag '.$tagdata['title'].' created with id '.$tagid;
//             $tagdata['title'];
//             $tagdata['isnew'] = true;
//         }
//         if (!$silent) {
//     	    $app = Factory::getApplication();
//     	    if ($errmsg != '') $app->enqueueMessage('getCreateTag() '.$errmsg, 'Warning');
//     	    if ($infomsg != '') $app->enqueueMessage('getCreateTag() '.$infomsg, 'Info');           
//         }
//         return $tagdata;
// 	}
	
// 	/**
// 	 * @name addTagToItems()
// 	 * @desc adds an existing tag to one or more items of a given type
// 	 * @param string $compitem - the component item type in dotted lower case eg com_content.article
// 	 * @param int|array $itemId - the id(s) of the item(s) the tag is being added to
// 	 * @param int $tagId - the id of the tag being added
// 	 * @return boolean - true on suceess, false on failure
// 	 */
// 	public static function addTagToItems($compitem, $itemIds, $tagId) {
// 	    $arr=explode('.',$compitem);
// 	    $app = Factory::getApplication();
// 	    $factory = $app->bootComponent($arr[0])->getMVCFactory();
// 	    $model = $factory->createModel(ucfirst($arr[1]), 'Administrator');
// 	    $commands = array('tag'=>$tagId);
// 	    if (!is_array($itemIds)) {
// 	        $pks = [$itemIds];
// 	    } else {
// 	        $pks = $itemIds;
// 	    }
// 	    $contexts = array_combine($pks, $pks);
// 	    foreach($contexts as $key=>$itemId) {
// 	        $contexts[$key] = "$compitem.$itemId";
// 	    }
// 	    $res = $model->batch($commands, $pks, $contexts );
// 	    return $res;
// 	}
	
// 		/**
// 	 * @name addTagsToItem()
// 	 * @desc adds one or more existing tags to an item of a given type
// 	 * @param string $compitem - the component item type in dotted lower case eg com_content.article
// 	 * @param int $itemId - the id(s) of the item(s) the tag is being added to
// 	 * @param int $tagId - the id of the tag being added
// 	 * @return boolean - true on suceess, false on failure
// 	 */
// 	public static function addTagsToItem($compitem, int $itemId, $tagIds) {
// 	    $arr=explode('.',$compitem);
// 	    $app = Factory::getApplication();
// 	    $factory = $app->bootComponent($arr[0])->getMVCFactory();
// 	    $model = $factory->createModel(ucfirst($arr[1]), 'Administrator');
// 	    $commands = array('tag'=>$tagId);
//         $pks = [$itemId];
//         $contexts = array($itemId => $compitem.$itemId);
// 	    $res = $model->batch($commands, $pks, $contexts );
// 	    return $res;
// 	}
	
// /**
// 	 * @name addTagToGroup()
// 	 * @desc adds the given tag by name to component & taggroup specified
// 	 * NB This assumes you are requesting a valid tag list parameter in the config for a valid component
// 	 * @param string $tagtitle - the title of the tag to add, will be created if doesn't exist
// 	 * NB if tag it doesn't exist it will be created without a parent ie at top level
// 	 * @param string - $compgroup component and params taggroupname in dotted form eg com_xbmusic.tracktagparents
// 	 * @param string $tagdesc - optional tag description (eg 'Created as parent for Genre tags, do not delete')
// 	 * @return boolean
// 	 */
// 	public static function addTagToGroup(string $tagtitle, string $compgroup, $tagdesc = '') {
// 	    $cgarr = explode('.',$compgroup);
// 	    //get id of tag to add, create if it doesn't exist
// 	    $tid = self::getCreateTag(array('title'=>$tagtitle,
// 	        'description'=>$tagdesc,
// 	        'created_by_alias'=>'xbMusicHelper::addTagToGroup()'.' '.$compgroup
// 	        ))['id'];
// 	    //now add it to itemtype options
// 	    $params = ComponentHelper::getParams($cgarr[0]);
// 	    // Set new value of param(s)
// 	    $grouptags = $params->get($cgarr[1],[]);
// 	    if (!array_search($tid, $grouptags)){
// 	        //add tag to group
// 	        $grouptags[] = $tid;
// 	        $params->set($cgarr[1], $grouptags);
// 	        // Save the parameters
// 	        $componentid = ComponentHelper::getComponent($cgarr[0])->id;
// 	        $table = Table::getInstance('extension');
// 	        $table->load($componentid);
// 	        $table->bind(array('params' => $params->toString()));
	        
// 	        // check for error
// 	        if (!$table->check()) {
// 	            Factory::getApplication()->enqueueMessage('addTagToGroup()check '.$table->getError(),'Error');
// 	            return false;
// 	        }
// 	        // Save to database
// 	        if (!$table->store()) {
// 	            Factory::getApplication()->enqueueMessage('addTagToGroup()store '.$table->getError(),'Error');
// 	            return false;
// 	        }
// 	    }
// 	    return $tid;
// 	}

// 	/**
// 	 * @name getCatChildren()
// 	 * @desc retruns all descendents of given category
// 	 * @param int $id
// 	 */
// 	public static function getTagChildren($pathorid) {
// 	    if (is_int($pathorid)) {
// 	        $path = self::getTag($pathorid)->path;
// 	    } else if (is_string($pathorid)) {
// 	        $path = $pathorid;
// 	    } else {
// 	        return false;
// 	    }
// 	    //	    $db = Factory::getContainer()->get(DatabaseInterface::class);
// 	    $db = Factory::getDbo();
// 	    $query = $db->getQuery(true);
// 	    $query->select('*');
// 	    $query->select('(SELECT COUNT(*) FROM '.$db->qn('#__tags').' AS ccnt WHERE ccnt.path LIKE '.$db->q($path.'/%').') AS desccnt');
// 	    $query->from($db->qn('#__categories').' AS c');
// 	    $query->where($db->qn('path').' LIKE '.$db->q($path.'/%'));
// 	    $query->order($db->qn('path'));
// 	    $db->setQuery($query);
// 	    $result = $db->loadAssocList();
// 	    if (!is_null($result)) {
// 	        foreach($result as $i=>$item) {
// 	            $result[$i]['itemcnt'] = self::getTagItemCnts($result[$i]['id']);
// 	        }
// 	    }
// 	    return $result;
// 	}
	
	
// /**************** 3. Database Functions ********************/
	
// 	/**
// 	 * @name checkValueExists()
// 	 * @desc returns its id if given value (case insensitive) exists in given table column 
// 	 * @param string $value - text to check
// 	 * @param string $table - the table to check in
// 	 * @param string $col- the column to check
// 	 * @param string $where - optional additional where condition (AND). should be quoted
// 	 * @return int|boolean - id if value is found in column, otherwise false (first match only)
// 	 */
// 	public static function checkValueExists( $value,  $table, $col, $where = '') {
// 	    $db = Factory::getDbo();
// 	    //$db = Factory::getContainer()->get(DatabaseInterface::class);
// 	    $query = $db->getQuery(true);
// 	    $query->select('id')->from($db->quoteName($table))
// 	    ->where('LOWER('.$db->quoteName($col).')='.$db->quote(strtolower($value)));
// 	    if ($where != '') $query->where($where);
// 	    $db->setQuery($query);
// 	    $res = $db->loadResult();
// 	    if ($res > 0) {
// 	        return $res;
// 	    }
// 	    return false;
// 	}
	
// 	/**
// 	 * @name strDateReformat()
// 	 * @desc reformats date from YYYY[-MM[-DD]] to [DD-[MMM-]]YYYY
// 	 * @param string $ymd
// 	 * @return string
// 	 */
// 	public static function strDateReformat($ymd) {
// 	    $parts = explode('-',$ymd);
// 	    $res = '';
// 	    if (count($parts)>2) $res = $parts[2].' ';
// 	    if (count($parts)>1) $res .= DateTime::createFromFormat('!m', $parts[1])->format('M').' ';
// 	    if (count($parts)>0) $res .= $parts[0];
// 	    return $res;
// 	}
	
// 	/**
// 	 * @name getItemCnt
// 	 * @desc returns the number of items in a table
// 	 * @param string $table - table name, should include '#__' prefix
// 	 * @param string filter - optional where string to be used in query
// 	 * @return integer
// 	 */
// 	public static function getItemCnt(string $table, $filter = '') {
// 	    $db = Factory::getDbo();
// 	    //$db = Factory::getContainer()->get(DatabaseInterface::class);
// 	    $query = $db->getQuery(true);
// 	    $query->select('COUNT(*)')->from($db->quoteName($table));
// 	    if ($filter !='') {
// 	        $query->where($filter);
// 	    }
// 	    $db->setQuery($query);
// 	    $cnt=-1;
// 	    try {
// 	        $cnt = $db->loadResult();
// 	    } catch (\Exception $e) {
// 	        $dberr = $e->getMessage();
// 	        Factory::getApplication()->enqueueMessage('getItemCnt() '.$dberr.'<br />Query: '.$query->dump(), 'error');
// 	    }
// 	    return $cnt;
// 	}
	
//     /** 
//      * @name getItemValue
//      * @desc returns a single value from a table given the id or null if not found
//      * @param string $table
//      * @param string $column
//      * @param int $id
//      */
// 	public static function getItemValue(string $table, string $column, int $id) {
// 	    //$db = Factory::getContainer()->get(DatabaseInterface::class);
// 	    $db = Factory::getDbo();
// 	    $query = $db->getQuery(true);
// 	    $query->select($column)->from($db->qn($table)) 
// 	       ->where('id = '.$db->q($id));
// 	   $db->setQuery($query);
// 	   return $db->loadResult();	    
// 	}
	
// 	/**
// 	 * @name getItem()
// 	 * @desc returns a single item row as an object. If column values are not unique will return the first found
// 	 * @param string $table - the table name
// 	 * @param string $val - the value to match
// 	 * @param string $col - the column to look in, defaults to 'id'
// 	 * @return object|NULL
// 	 */
// 	public static function getItem(string $table, string $val, $col ='id', $where = '') {
// 	    $db = Factory::getDbo();
// 	    //$db = Factory::getContainer()->get(DatabaseInterface::class);
// 	    $query = $db->getQuery(true);
// 	    $query->select('*')->from($db->qn($table));
// 	    $query->where($db->qn($col).' = '.$db->q($val));
// 	    if ($where != '') $query->where($where);
// 	    $db->setQuery($query);
// 	    return $db->loadObject();
// 	}
	
// 	/**
// 	 * @name getItems
// 	 * @param string $table - table name containing item(s)
// 	 * @param string $column - column to search in
// 	 * @param string $search - value to search for, for partial string match use %str%
// 	 * @param string $filter - optional string to use as andwhere clause
// 	 * @return array of objects 
// 	 */
// 	public static function getItems(string $table, string $column, $search = '', $filter = '' ) {
// 	    //TODO make case insenstive?
// 	    $db = Factory::getDbo();
// 	    //$db = Factory::getContainer()->get(DatabaseInterface::class);
// 	    $query = $db->getQuery(true);
// 	    $query->select('*')->from($db->qn($table).' AS a');
// 	    if (($search !='') && (($search[0] == '%') || ($search[-1] == '%'))) {
// 	        $query->where($db->qn($column). 'LIKE ('.$db->q($search).')');
// 	    } else {
// 	        $query->where($db->qn($column).' = '.$db->q($search));
// 	    }
// 	    if ($filter !='') $query->where($filter);
// 	    $db->setQuery($query);
// 	    try {
// 	        $res = $db->loadObjectList();
// 	    } catch (\Exception $e) {
// 	        $dberr = $e->getMessage();
// 	        Factory::getApplication()->enqueueMessage('getItems() '.$dberr.'<br />Query: '.$query->dump(), 'error');
// 	    }
// 	    return $res;	    
// 	}

// 	public static function statusCnts(string $table = '#__content', string $colname = 'state', string $ext='com_content') {
// 	    $db = Factory::getDbo();
// 	    //$db = Factory::getContainer()->get(DatabaseInterface::class);
// 	    $query = $db->getQuery(true);
// 	    $query->select('DISTINCT a.'.$colname.', a.alias')
// 	    ->from($db->quoteName($table).' AS a');
// 	    if ($table == '#__categories') {
// 	        $query->where('extension = '.$db->quote($ext));
// 	    }
// 	    $db->setQuery($query);
// 	    $col = $db->loadColumn();
// 	    $vals = array_count_values($col);
// 	    $result['total'] = count($col);
// 	    $result['published'] = key_exists('1',$vals) ? $vals['1'] : 0;
// 	    $result['unpublished'] = key_exists('0',$vals) ? $vals['0'] : 0;
// 	    $result['archived'] = key_exists('2',$vals) ? $vals['2'] : 0;
// 	    $result['trashed'] = key_exists('-2',$vals) ? $vals['-2'] : 0;
// 	    return $result;
// 	}
	
// 	/**
// 	 * @name makeAlias()
// 	 * @desc takes a text string and removes puntuation before making urlsafe
// 	 * @param string $text
// 	 * @return string
// 	 */
// 	public static function makeAlias(string $text) {
// 	    //when creating alias with strURLsafe() odd chars, punction, apostrophes, quotes get converted into unwanted hyphens
// 	    $unwantedaliaschars = array("|","#","?","!",",",";",".","'","\"");
// 	    return OutputFilter::stringURLSafe(str_replace($unwantedaliaschars,"",$text));	    
// 	}
	
// 	/**
// 	 * @name makeUniqueAlias()
// 	 * @desc takes a text string and converts it into a unique alias for the given table
// 	 * calls makeAlias() to remove unwanted chars from text and make url safe 
// 	 * then checks against table and adds 2 digit suffix to make it unique if needed
// 	 * @param string $text
// 	 * @param string $table
// 	 * @return string
// 	 */
// 	public static function makeUniqueAlias(string $text, string $table) {
// 	    $alias = self::makeAlias($text);
// 	    $i = 0;
// 	    while (self::checkValueExists($alias, $table, 'alias') !== false) {
// 	        $i ++;
// 	        $alias = $text.'-'.sprintf("%02d",$i);
// 	    }
// 	    return $alias;
// 	}
	
// 	/**************** Text Functions ********************/

//     /**
//      * @name stripThe()
//      * @param string $name - string to strip the leading 'the ' from
//      * @return string  eg The Rolling Stones -> Rolling Stones
//      */
// 	public static function stripThe(string $name) {
// 	    if (substr(strtolower(ltrim($name)), 0, 4) == 'the ') {
// 	        $name = substr($name,4);
// 	    }
// 	    return $name;
// 	}
	
// 	public static function abridgeText(string $source, int $maxstart = 6, int $maxend = 4, $wordbrk = true) {
// 	    $source = trim($source);
// 	    if (strlen($source) < ($maxstart + $maxend + 5)) return $source;
// 	    $start = substr($source, 0, $maxstart);
// 	    $end = substr($source, strlen($source)-$maxend);
// 	    if ($wordbrk) {
//     	    $firstspace = strrpos($start, ' ');
//     	    if ($firstspace !== false) $start = substr($start,0,$firstspace);
//     	    $lastspace = strrpos($end,' ');
//     	    if ($lastspace !== false) $end = substr($end, strlen($end)-$lastspace);	        
// 	    }
// 	    return $start.' ... '.$end;	    
// 	}
	
// 	public static function truncateToText(string $source, int $maxlen=250, string $split = 'word', $ellipsis = true) { //null=exact|false=word|true=sentence
// 	    if ($maxlen < 5) return $source; //silly the elipsis '...' is 3 chars
// 	    $action = strpos(' firstsent lastsent word abridge exact',$split);
// 	    // firstsent = 1 lastsent = 11, word = 20, abridge = 25, exact = 33
// 	    $lastword = '';
// 	    //todo for php8.1+ we could use enum
// 	    if (!$action) return $source; //invalid $split value
// 	    $source = trim(html_entity_decode(strip_tags($source)));
// 	    if ((strlen($source)<$maxlen) && ($action > 19)) return $source; //not enough chars anyway
// 	    if ($ellipsis) $maxlen = $maxlen - 4; // allow space for ellipsis
// 	    // for abridge we'll save the last word to add back preceeded by ellipsis after truncating
// 	    if ($action == 25) {
// 	        $lastspace = strrpos($source, ' ');
// 	        $excess = strlen($source) - $maxlen;
// 	        if ($lastspace && ($lastspace > $maxlen)) {
// 	            $lastword = substr($source, $lastspace);
// 	        } else {
// 	            // no space to get lastword outside maxlen, so just take last 6 chars as lastword
// 	            $lastword = ($excess>6) ? substr($source, strlen($source)-6) : substr($source,strlen($source)-$excess);
// 	        }
// 	        $maxlen = $maxlen - strlen($lastword);
// 	    }
// 	    $source = substr($source, 0, $maxlen);
// 	    //for exact trim at maxlength
// 	    if ($action == 33) {
// 	        if ($ellipsis) return $source.'...';
// 	        return $source;
// 	    }
// 	    //for word or abridge simply find the last space and add the ellipsis plus lastword for abridge
// 	    $lastwordend = strrpos($source, ' ');
// 	    if ($action > 19) {
// 	        if ($lastwordend) {
// 	            $source = substr($source,$lastwordend);
// 	        }
// 	        return $source.'...'.$lastword;
// 	    }
// 	    //ok so we are doing first/last complete sentence
// 	    // get a temp version with '? ' and '! ' replaced by '. '
// 	    $dotsonly = str_replace(array('! ','? '),'. ',$source.' ');
// 	    if ($action == 1) {
// 	        // look for first ". " as end of sentence
// 	        $dot = strpos($dotsonly,'. ');
// 	    } else {
// 	        // look for last ". " as end of sentence
// 	        $dot = strrpos($dotsonly,'. ');
// 	    }
// 	    if ($dot !== false) {
// 	        if ($ellipsis) {
// 	            return substr($source, 0, $dot+1).'...';
// 	        }
// 	        return substr($source, 0, $dot+1);
// 	    }
// 	    return $source;
// 	}
	
// 	public static function truncateHtml(string $source, int $maxlen=250, bool $wordbreak = true) {
// 	    if ($maxlen < 10) return $source; //silly the elipsis '...' is 3 chars empire->emp...  workspace-> work... 'and so on' -> 'and so...'
// 	    $maxlen = $maxlen - 3; //to allow for 3 char ellipsis '...' rather thaan utf8
// 	    if (($wordbreak) && (strpos($source,' ') === false )) $wordbreak = false; //nowhere to wordbreak
// 	    $truncstr = substr($source, 0, $maxlen);
// 	    if (!self::isHtml($source)) {
// 	        //we can just truncate and find a wordbreak if needed
// 	        if (!$wordbreak || ($wordbreak) && (substr($source, $maxlen+1,1)== ' ')) {
// 	            //weve got a word at the end
// 	            return $truncstr.'...';
// 	        }
// 	        //ok we've got to look for a wordbreak (space or newline)
// 	        $lastspace = strrpos(str_replace("\n"," ",$truncstr),' ');
// 	        if ($lastspace) { // not if it is notfound or is first character (pos=0)
// 	            return substr($truncstr, 0, $lastspace).'...';
// 	        }
// 	        // still here - no spaces left in truncstr so return it all
// 	        return $truncstr.'...';
// 	    }
// 	    //ok so it is html
// 	    //get rid of any unclosed tag at the end of $truncstr
// 	    // Check if we are within a tag, if we are remove it
// 	    if (strrpos($truncstr, '<') > strrpos($truncstr, '>')) {
// 	        $lasttagstart = strrpos($truncstr, '<');
// 	        $truncstr = trim(substr($truncstr, 0, $lasttagstart));
// 	    }
// 	    $testlen = strlen(trim(html_entity_decode(strip_tags($truncstr))));
// 	    while ( $testlen > $maxlen ) {
// 	        $toloose = $testlen - $maxlen;
// 	        $trunclen = strlen($truncstr);
// 	        $endlasttag = strrpos($truncstr,'>');
// 	        if (($trunclen - $endlasttag) >= $toloose) {
// 	            $truncstr = substr($truncstr, $trunclen - $toloose);
// 	        } else {
// 	            //we need to remove another tag
// 	            $lasttagstart = strrpos($truncstr,'<');
// 	            if ($lasttagstart) {
// 	                $truncstr = substr($truncstr, 0, $lasttagstart);
// 	            } else {
// 	                $truncstr = substr($truncstr, 0, $maxlen);
// 	            }
// 	        }
// 	        $testlen = strlen(trim(html_entity_decode(strip_tags($truncstr))));
// 	    }
// 	    if (!$wordbreak) return $truncstr.'...';
// 	    $lastspace = strrpos(str_replace("\n",' ',$truncstr),' ');
// 	    if ($lastspace) {
// 	        $truncstr = substr($truncstr, 0, $lastspace);
// 	    }
// 	    return $truncstr.'...';
// 	}
	
	
// /**************** File Functions *******************/
	
// 	/**
// 	 * @name newestFile()
// 	 * @desc finds the most recently modified file in a folder
// 	 * use directory iterator methods like file->getPathname() to access info 
// 	 * @param string $path
// 	 * @return \DirectoryIterator instance
// 	 */
// 	public static function newestFile(string $path) {
// 	    foreach(new \DirectoryIterator($path) as $item) {
//     	    if ($item->isFile() && (empty($file) || $item->getMTime() > $file->getMTime())) {
//     	        $file = clone $item;
//     	    }
//     	}
// 	    return $file;
// 	}
	
// /**************** Unsorted *************************/	
// 	/**
// 	 * @name checkComponent()
// 	 * @desc test whether a component is installed and enabled.
// 	 * NB This sets the seesion variable if component installed to 1 if enabled or 0 if disabled.
// 	 * Test sess variable==1 if wanting to use component
// 	 * @param  $name - component name as stored in the extensions table (eg com_xbfilms)
// 	 * @param $usesess - true if result will also set or clear a session variable with the name of component
// 	 * @return boolean|number - true= installed and enabled, 0= installed not enabled, null = not installed
// 	 */
// 	public static function checkComponent($name, $usesess = true) {
// 	    $db = Factory::getDbo();
// 	    //$db = Factory::getContainer()->get(DatabaseInterface::class);
// 	    $db->setQuery('SELECT enabled FROM #__extensions WHERE element = '.$db->quote($name));
// 	    $res = $db->loadResult();
// 	    if ($usesess) {
//     	   $sname=substr($name,4).'_ok';
// 	       $sess= Factory::getApplication()->getSession();
// 	        if (is_null($res)) {
// 	            $sess->clear($sname);
// 	       } else {
// 	            $sess->set($sname,$res);
//     	    }
// 	    }
// 	    return $res;
// 	}
	
// 	/**
// 	 * @name checkTable()
// 	 * @desc checks if a given table exists in Joonla database
// 	 * @param string $table
// 	 * @return boolean - true if the table exists
// 	 */
// 	public static function checkTable(string $table) {
// 	    $db=Factory::getDbo();
// 	    //$db = Factory::getContainer()->get(DatabaseInterface::class);
// 	    $tablesarr = $db->setQuery('SHOW TABLES')->loadColumn();
// 	    $table = $db->getPrefix().$table;
// 	    return in_array($table, $tablesarr);
// 	}
	
//     /**
//      * @name checkTableColumn()
//      * @desc tests if a given table and column exist in database
//      * @param string $table - name of the table to check without joomla prefix
//      * @param string|array $column - name of the column(s) to check
//      * @return boolean|NULL - false if table doesn't exist, null if column doesn't exist, if ok then true
//      */
// 	public static function checkTableColumn($table, $column) {
// 	    $db=Factory::getDbo();
// 	    //$db = Factory::getContainer()->get(DatabaseInterface::class);
// 	    if (self::checkTable($table) != true) return false;
// 	    if (!is_array($column)) {
// 	        $column = (array) $column;
// 	    }
// 	    foreach ($column as $col) {
//     	    $db->setQuery('SHOW COLUMNS FROM '.$db->qn('#__'.$table).' LIKE '.$db->q($col));
//     	    $res = $db->loadResult();
//     	    if (is_null($res)) return null;	        
// 	    }
// 	    return true;
// 	}
	
// 	/**
// 	 * @name check_url()
// 	 * @desc gets headers for url and returns true if status 300,301,302 returned
// 	 * @param string $url
// 	 * @return boolean
// 	 */
// 	public static function check_url(string $url) {
// 	    $headers = @get_headers( $url);
// 	    $headers = (is_array($headers)) ? implode( "\n ", $headers) : $headers;
// 	    return (bool)preg_match('#^HTTP/.*\s+[(200|301|302)]+\s#i', $headers);
// 	}
			
// /**
// 	 * @name credit()
// 	 * @desc tests if reg code is installed and returns blank, or credit for site and PayPal button for admin
// 	 * @param string $ext - extension name to display, must match 'com_name' and xml filename and crosborne link page when converted to lower case
// 	 * @return string - empty is registered otherwise for display
// 	 */
// 	public static function credit(string $ext) {
// 	    if (self::penPont()) {
// 	        return '';
// 	    }
// 	    $lext = strtolower($ext);
// 	    $credit='<div class="xbcredit">';
// 	    if (Factory::getApplication()->isClient('administrator')==true) {
// 	        $xmldata = Installer::parseXMLInstallFile(JPATH_ADMINISTRATOR.'/components/com_'.$lext.'/'.$lext.'.xml');
// 	        $credit .= '<a href="http://crosborne.uk/'.$lext.'" target="_blank">'
// 	            .$ext.' Component '.$xmldata['version'].' '.$xmldata['creationDate'].'</a>';
// 	            $credit .= '<br />'.Text::_('XB_BEER_TAG');
// 	            $credit .= Text::_('XB_BEER_FORM');
// 	    } else {
// 	        $credit .= $ext.' by <a href="http://crosborne.uk/'.$lext.'" target="_blank">CrOsborne</a>';
// 	    }
// 	    $credit .= '</div>';
// 	    return $credit;
// 	}
	
// 	public static function penPont() {
// 	    $params = ComponentHelper::getParams('com_xbmusic');
// 	    $beer = trim($params->get('roger_beer',''));
// 	    if ($beer == '') return false;
// 	    //Factory::getApplication()->enqueueMessage(password_hash($beer));
// 	    //$hashbeer = $params->get('penpont');
// 	    if (password_verify($beer,'$2y$10$l8jx1ia8RJ3Kie2AyVgBlOBgm9sVL9dQsV8eBy8g5JOE30lw1HzhG')) { return true; }
// 	    return false;
// 	}
	
// 	/**
// 	 * @name getTag()
// 	 * @desc gets a tag's details given its id
// 	 * @param (int) $tagid
// 	 * @return mixed
// 	 */
// 	public static function getTag($tagid) {
// 	    $db = Factory::getDBO();
// 	    //$db = Factory::getContainer()->get(DatabaseInterface::class);
// 	    $query = $db->getQuery(true);
// 	    $query->select('*')
// 	    ->from('#__tags AS a ')
// 	    ->where('a.id = '.$tagid);
// 	    $db->setQuery($query);
// 	    return $db->loadObject();
// 	}
	
//     /**
//      * @name tagFilterQuery()
//      * @desc given tag filter ids and logic appends appropriate where statement to query
//      * @param DatabaseQuery $query - existing query object
//      * @param array $tagfilt - array of tag ids to filter by
//      * @param int $taglogic 1=all, 2=none, else: any
//      * @param string typealias - extension item type used in table #__contentitem_tag_map
//      * @return \Joomla\Database\DatabaseQuery object
//      */
// 	public static function tagFilterQuery(DatabaseQuery $query, array $tagfilt, int $taglogic, $typealias = 'com_xbmusic.track') {
	    
// 	    if (!empty($tagfilt)) {
// 	        $tagfilt = ArrayHelper::toInteger($tagfilt);
// 	        $subquery = '(SELECT tmap.tag_id AS tlist FROM #__contentitem_tag_map AS tmap
//                 WHERE tmap.type_alias = \''.$typealias.'\''.'
//                 AND tmap.content_item_id = a.id)';
// 	        switch ($taglogic) {
// 	            case 1: //all
// 	                for ($i = 0; $i < count($tagfilt); $i++) {
// 	                    $query->where($tagfilt[$i].' IN '.$subquery);
// 	                }
// 	                break;
// 	            case 2: //none
// 	                for ($i = 0; $i < count($tagfilt); $i++) {
// 	                    $query->where($tagfilt[$i].' NOT IN '.$subquery);
// 	                }
// 	                break;
// 	            default: //any
// 	                if (count($tagfilt)==1) {
// 	                    $query->where($tagfilt[0].' IN '.$subquery);
// 	                } else {
// 	                    $tagIds = implode(',', $tagfilt);
// 	                    if ($tagIds) {
// 	                        $subQueryAny = '(SELECT DISTINCT content_item_id FROM #__contentitem_tag_map
//                                 WHERE tag_id IN ('.$tagIds.') AND type_alias = '.$db->quote('com_xbmusic.track').')';
// 	                        $query->innerJoin('(' . (string) $subQueryAny . ') AS tagmap ON tagmap.content_item_id = a.id');
// 	                    }
// 	                }	                
// 	                break;
//             }	        
//         }
//         return $query;
// 	}

//     /**
//      * @name imageMimeToExt()
//      * @desc returns a three letter file extension (without the dot) for a given image mime type
//      * @param string $mime
//      * @return string
//      */
// 	public static function imageMimeToExt(string $mime) {
// 	    $mimemap = array(
// 	        'image/bmp'     => 'bmp',
// 	        'image/x-bmp'   => 'bmp',
// 	        'image/x-bitmap'    => 'bmp',
// 	        'image/x-xbitmap'   => 'bmp',
// 	        'image/x-win-bitmap'    => 'bmp',
// 	        'image/x-windows-bmp'   => 'bmp',
// 	        'image/ms-bmp'  => 'bmp',
// 	        'image/x-ms-bmp'    => 'bmp',
// 	        'image/cdr'     => 'cdr',
// 	        'image/x-cdr'   => 'cdr',
// 	        'image/gif'     => 'gif',
// 	        'image/x-icon'  => 'ico',
// 	        'image/x-ico'   => 'ico',
// 	        'image/vnd.microsoft.icon'  => 'ico',
// 	        'image/jp2'     => 'jp2',
// 	        'video/mj2'     => 'jp2',
// 	        'image/jpx'     => 'jp2',
// 	        'image/jpm'     => 'jp2',
// 	        'image/jpeg'    => 'jpg',
// 	        'image/pjpeg'   => 'jpg',
// 	        'image/png'     => 'png',
// 	        'image/x-png'   => 'png',
// 	        'image/vnd.adobe.photoshop' => 'psd',
// 	        'image/svg+xml' => 'svg',
// 	        'image/tiff'    => 'tiff',
// 	        'image/webp'    => 'webp'
// 	    );
// 	    return isset($mimemap[$mime]) ? $mimemap[$mime] : 'xyz';
// 	}
	
// 	/**
// 	 * @name uniqueNestedArray()
// 	 * @desc given an array of arrays returns the array with any duplicate values on the given key to the inner arrays removed
// 	 * @param array(array) $array - the nested array
// 	 * @param $key - the key on the inner arrays to check for duplicates
// 	 * @return array
// 	 */
// 	public static function uniqueNestedArray($array, $key) : array {
// 	    $uniq_array = array();
// 	    $key_array = array();	    
// 	    foreach($array as $key1=>$val1) {
// 	        if (!in_array($val1[$key], $key_array)) {
// 	            $key_array[] = $val1[$key];
// 	            $uniq_array[$key1] = $val1;
// 	        }
// 	    }
// 	    return $uniq_array;
// 	}
	
// 	/**
// 	 * @name setComponentOptions()
// 	 * @desc sets a component options from an array of keys=>values
// 	 * @param array $options
// 	 * @param string $component
// 	 * @return boolean
// 	 */
// 	public static function setComponentOptions(array $options, $component = 'com_xbmusic') {
// 	    // Load the current component params.
// 	    $params = ComponentHelper::getParams($component);
// 	    // Set new value of param(s)
// 	    foreach ($options as $key=>$value) {
// 	        $params->set($key, $value);
// 	    }	    
// 	    // Save the parameters
// 	    $componentid = ComponentHelper::getComponent($component)->id;
// 	    $table = Table::getInstance('extension');
// 	    $table->load($componentid);
// 	    $table->bind(array('params' => $params->toString()));
	    
// 	    // check for error
// 	    if (!$table->check()) {
// 	        echo $table->getError();
// 	        return false;
// 	    }
// 	    // Save to database
// 	    if (!$table->store()) {
// 	        echo $table->getError();
// 	        return false;
// 	    }
// 	}
 }

