<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Model/DatamanModel.php
 * @version 0.0.55.4 6th July 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2025
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Filter\OutputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Uri\Uri;
use DirectoryIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbmusicHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbcommonHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\Xbtext;
use Crosborne\Component\Xbmusic\Administrator\Helper\AzApi;
use \SimpleXMLElement;
use Joomla\CMS\Filesystem\Folder;

class DatamanModel extends AdminModel {

    protected $trackcatid = 0;
    protected $albumcatid = 0;
    protected $artistcatid = 0;
    protected $songcatid = 0;
    protected $usedaycat = 0;
    protected $defcats = [];
    //when creating alias with strURLsafe() punction, apostrophes and quotes get converted into hyphens - we don't want that
    protected $unwantedaliaschars = array("?","!",",",";",".","'","\"");
    
    public function getForm($data = array(), $loadData = true) {
        $form = $this->loadForm('com_xbmusic.dataman', 'dataman',
            array('control' => 'jform', 'load_data' => $loadData));
        if (empty($form)) {
            return false;
        }
        return $form;
    }
    
/************ IMPORT MUSIC FUNCTIONS ************/
    
    /**
     * @name parseFilesMp3()
     * @desc takes either a folder path or a set of file pathnames and parses the files ID3 data
     * prepends the logging infomation to a log file with current date in /xbmusic-data/logs/
     * @param string|array $files - if a string it is assumed to be a folder 
     * @param int $cattype
     * @return boolean
     */
    public function parseFilesMp3($files, $usedaycat) {
        $basemusicfolder = JPATH_ROOT.'/xbmusic/'; //XbmusicHelper::$musicBase;
        $dosubfolders = true;
        $maxitems = 50;
        $maxlevels = 6;
        $itemcnt = 0;
        $app = Factory::getApplication();
        $params = ComponentHelper::getParams('com_xbmusic');
        $this->usedaycat = ($usedaycat === '') ? $params->get('impcat','0') : $usedaycat;
        $this->defcats = XbmusicHelper::getDefaultItemCats($this->usedaycat);
        //start log
        $loghead = '[IMPORT] Import ID3 Started '.date('H:i:s D jS M Y')."\n";
        $logmsg = '';            //        }
        // set up counts for logging and start time
        $cnts = array('newtrk'=>0,'duptrk'=>0,'newalb'=>0,'newart'=>0,'newsng'=>0,'errtrk'=>0);
        $starttime = time();
        //are we doing a whole folder, or selected files?
        if (is_string($files)) {
            //we have a folder name with trailing slash
            $folder = $files;
            $files = [];
            if (trim($folder,'/') != '') {
                //start by getting any mp3s in the base folder
                $mp3list = $this->getMp3FileList($basemusicfolder.$folder);
                $itemcnt = count($mp3list);
                if ($itemcnt > $maxitems) {
                    $errmsg = Text::sprintf('%s mp3 files found which exceeds the limit (%s). Aborting scan.',$itemcnt,$maxitems);
                    $app->enqueueMessage($errmsg,'Error');
                    $logmsg .= $loghead.XBERR.$errmsg.XBENDLOG;
                    XbmusicHelper::writelog($logmsg);
                    return false;
                } else {
                    $files = $mp3list;
                    $msg = Text::sprintf('Found %s mp3s in %s',$itemcnt,$folder);
                    //$app->enqueueMessage($msg);
                    $logmsg .= $msg."\n";
                }
            } //endif folder ok
            if ($dosubfolders) {
                $subfolders = $this->getSubfolderList($basemusicfolder.$folder,0,$maxlevels,$maxitems, $itemcnt);
 //               $foldercnt = count($subfolders);
                if ($itemcnt > $maxitems) {
                    $errmsg = Text::sprintf('%s mp3 files found which exceeds the limit (%s). Aborting scan.',$itemcnt,$maxitems);
                    $app->enqueueMessage($errmsg,'Error');
                    $logmsg .= $loghead.XBERR.$errmsg.XBENDLOG;
                    XbmusicHelper::writelog($logmsg);
                    return false;
                } else {
                    foreach ($subfolders as $folder) {
                        $mp3list = $this->getMp3FileList($folder);
                        $foundcnt = count($mp3list);
                        $files = array_merge($files,$mp3list);
                        $msg = Text::sprintf('Found %s mp3 files in %s and subfolders',$foundcnt,$folder);
                        $app->enqueueMessage($msg);
                        $logmsg .= $msg."\n";
                    }
                }
                
            }
        } elseif (is_array($files)) {
            //we've got a list of files, make the paths complete
            foreach ($files as $key=>$file) {
                $files[$key] = $basemusicfolder.$file;
            }
        } else {
            // not a string and not an array - invalid
            $errmsg .= Text::_('XBMUSIC_INVALID_FILES_LIST');
            Factory::getApplication()->enqueueMessage($errmsg,'Error');
            $logmsg .= $loghead.XBERR.$errmsg.XBENDLOG;
            XbmusicHelper::writelog($logmsg);
            return false;
        } //endif
        if (count($files)==0){
            $errmsg .= Xbtext::_('XBMUSIC_NO_MP3_FOUND',XBSP2 + XBTRL).$folder;
            Factory::getApplication()->enqueueMessage($errmsg,'Warning');
            $logmsg .= $loghead.XBWARN.$errmsg.XBENDLOG;
            XbmusicHelper::writelog($logmsg);
            return false;
        }
        // ok we're going to iterate through the files
        // $logmsg .= $file."\n";
        $app->enqueueMessage('parsing '.count($files));
        foreach ($files as $file) {
            $logmsg .= $this->parseID3($file, $cnts);
        }
        //update the log file with counts at the top
        $loghead .= '[SUM] '.$cnts['newtrk'].' new tracks, '.$cnts['duptrk'].' duplicates'."\n";
        $loghead .= '[SUM] '.$cnts['newalb'].' new albums, '.$cnts['newart'].' new artists, '.$cnts['newsng'].' new songs, '."\n";
        $loghead .= '[SUM] Elapsed time '.date('s', time()-$starttime).' seconds'."\n";
        $loghead .= XBENDITEM;
        $logmsg = $loghead.$logmsg.XBENDLOG;
        XbmusicHelper::writelog($logmsg);
        return true;
    } //end parseFilesMp3()
    
    private function getMp3FileList($folder, $ext = 'mp3') {
        //$folder must be full path
        $mp3list =[];
        $dirit = new \DirectoryIterator($folder);
        foreach ($dirit as $fileinfo) {
            if (strtolower($fileinfo->getExtension()) == $ext) {
                $mp3list[] = $folder.$fileinfo->getFilename();
            }
        }
        return $mp3list;
    }
    
    private function getSubfolderList($folder, $level, $maxlevels, $maxitems, &$itemcnt) {
        //            Factory::getApplication()->enqueueMessage('in getSubfolderList level: '.$level.' maxfolders: '.$maxitems.' itemcnt: '.$itemcnt.'- folder: '.$folder);
        $level ++;
        if ($level == $maxlevels) {
            Factory::getApplication()->enqueueMessage('Maximum depth reached, will not check subfolders');
        }
        $subfolderlist = [];
        
        $items = scandir($folder);
        $subcnt = 0;
        
        foreach ($items as $item) {
            if ($item == '..' || $item == '.') continue;
//            if (is_dir($folder . $item)) $subcnt ++;
            if (pathinfo($item,PATHINFO_EXTENSION) == 'mp3') $itemcnt++;
        }
        if ($itemcnt > $maxitems) {
            //            Factory::getApplication()->enqueueMessage('Item limit exceeded ('.$itemcnt.' &gt; '.$maxitems.'). Ending scan','Error');
            return [];
        }
        foreach ($items as $item) {
            if ($item == '..' || $item == '.') continue;
            if (is_dir($folder . $item)) {
                $subfolderlist[] = $folder.$item.'/';
                if ($level < $maxlevels) {
                    $subsublist = $this->getSubFolderList($folder.$item.'/',$level, $maxlevels, $maxitems, $itemcnt);
                    $subfolderlist = array_merge($subfolderlist, $subsublist);
                }
            }
        }
        //            Factory::getApplication()->enqueueMessage('done getSubfolderList - subfolders: '.count($subfolderlist).' in '.basename($folder).' items: '.$itemcnt);
        return $subfolderlist;
        
    }
    
    /**
     * @name parseID3()
     * @desc reads ID3 info from an mp3 file and if found creates track, album, artists and songs and saves the image file
     * @param string $filepathname - the filepathname from the JPATH_ROOT/xbmusic/ folder. It must exist if from file selector
     * @param array $cnts - updated with the counts of items porcessed
     * @return string - the loging info to be appended to the log file
     */
    public function parseID3(string $filepathname, array &$cnts) {
        //we'll need the params for category allocation and handling genres and image files
        // The track is new so can use model with song and artist lists
        // album, song and artist may not be new in which case need to append links not in model
        $params = ComponentHelper::getParams('com_xbmusic');
        $urlhandling = $params->get('urlhandling',[]);
        $dotrackurl = (key_exists(0, $urlhandling)) ? true : false;
        $dosongurl = (key_exists(1, $urlhandling)) ? true : false;
        $doalbumurl = (key_exists(2, $urlhandling)) ? true : false;
        $doartisturl = (key_exists(3, $urlhandling)) ? true : false;
        $loglevel = $params->get('loglevel',3);
        $app = Factory::getApplication();
        //start the logging for this file
        $ilogmsg = '[IMPORT BULK] '.str_replace(JPATH_ROOT.'/xbmusic/','',$filepathname)."\n";
        $enditem = " -------------------------- \n";
        $newmsg = Text::_('XBMUSIC_NEW_ITEMS_CREATED').':<br />';
        $trackdata = []; //only one track per file
        

// 1. check if filepathname already in database, if it exists already then exit
        if ( $tid = XbcommonHelper::checkValueExists($filepathname, '#__xbmusic_tracks', 'filepathname')) {
            $msg = Text::_('XBMUSIC_TRACK_IN_DB').Xbtext::_($tid,XBSP1 + XBDQ);
            $ilogmsg .= XBERR.$msg.$enditem;
            $cnts['duptrk'] ++;
            $app->enqueueMessage(trim($msg),'Error');
            return $ilogmsg;
        }
// 2. set track->pathname and track->filename, if filename exists then warning
        // check if same filename exists in a different folder - import it anyway and warn 
        $fpathinfo = pathinfo($filepathname);
        if ( $fid = XbcommonHelper::checkValueExists($fpathinfo['basename'], '#__xbmusic_tracks', 'filename')) {  
            $fpath = XbcommonHelper::getItemValue('#__xbmusic_tracks', 'filepathname', $fid);
            $msg = Text::_('XBMUSIC_FILENAME_IN_DB').Xbtext::_($fpath,7).Text::_('XBMUSIC_WITH_TKID').Xbtext::_($fid,XBDQ + XBNL);
            $ilogmsg .= XBWARN.$msg;
            $msg2 = Xbtext::_('XBMUSIC_IMPORTING_ANYWAY',XBNL + XBTRL);
            $app->enqueueMessage(trim($msg).'<br />'.trim($msg2),'Warning');
            $ilogmsg .= XBWARN.$msg2;
        }
        
//3. okay, now get the id3 data
        $filedata = XbmusicHelper::getFileId3(($filepathname));
        
        if (!isset($filedata['id3tags']['title'])) { //could add any other required elements to the isset() function
            $msg = Xbtext::_('XBMUSIC_NO_TRACK_TITLE_IN_ID3',XBNL + XBTRL);
            $ilogmsg .= XBERR.$msg.$enditem;
            $cnts['errtrk'] ++;
            $app->enqueueMessage(trim($msg),'Error');
            return $ilogmsg;          
        }

//4. get the basic trackdata from id3
        $id3data = XbmusicHelper::id3dataToItems($filedata['id3tags'],$ilogmsg);
        if (isset($id3data['trackdata'])) {
            
 //           $trackdata = $this->parseTrackdata($id3data['trackdata']);
            $genreids = [];
            $trackdata = $id3data['trackdata'];
            $trackdata['filepathname'] = $filepathname;
            $trackdata['filename'] = $fpathinfo['basename']; // TODO this is potentislly redundasnt
            // get genres list, catids are defined above in parseFilesMp3()
            $optalbsong = $params->get('genrealbsong',0);
            $optcattag = $params->get('genrecattag',2);
            if (isset($id3data['genres'])) {
                $genreids = array_column($id3data['genres'],'id');
                //usedaycat will take priority
                if (($this->usedaycat == 0) && ($optcattag & 1)) {
                    //first check if we already have a cat for the genre
                    $thisgid = XbcommonHelper::getCatByAlias($id3data['genres'][0]['alias'])->id;
                    if (is_null($thisgid)) {
                        //get Genre in Tracks category
                        $gcat = XbcommonHelper::getCatByAlias('genres');
                        if ($gcat->id > 0) {
                            $gid = $gcat->id;
                        } else {
                            //we need to create the Tracks/Genres category
                            $tcat = XbcommonHelper::getCatByAlias('tracks');
                            $gpid = ($tcat->id > 0) ? $tcat->id : 1; //if the tracks category has been deleted fallback to root
                            $gid = XbcommonHelper::getCreateCat(array('title'=>'MusicGenres', 'alias'=>'musicgenres', 'parent_id'=>$gpid),true)->id;
                        }
                        $thisgid = XbcommonHelper::getCreateCat(array('title'=>$id3data['genres'][0]['title'], 'alias'=>$id3data['genres'][0]['alias'], 'parent_id'=>$gid),true)->id;
                    }
                    $trackdata['catid'] = $thisgid;
                    
                } else {
                    $trackdata['catid'] = $this->defcats['track'];
                }
            } else {
                $trackdata['catid'] = $this->defcats['track'];
            }
            
            if (isset($filedata['audioinfo']['playtime_seconds'])) $trackdata['duration'] = (int)$filedata['audioinfo']['playtime_seconds'];
            
            if (isset($id3data['genres'])) $trackdata['tags'] = $genreids;
            
            if (isset($filedata['imageinfo']['data'])){
                $imgdata = $filedata['imageinfo'];
                unset($filedata['imageinfo']['data']);
            }
            //save json encoded filedata with track for reference
            $trackdata['id3tags'] = json_encode($filedata['id3tags']);
            $trackdata['fileinfo'] = json_encode($filedata['fileinfo']);
            $trackdata['audioinfo'] = json_encode($filedata['audioinfo']);
            //imageinfo is added to track after the image has been parsed

//4. get the song creating as necessary and make array song id to link to track, artist and album
            if (isset($id3data['songdata'])) {
                //create songs
                $songlinks = array(); //will be linked to album and artist once we have ids
                foreach ($id3data['songdata'] as $song) {                   
                    $song['catid'] = $this->defcats['song'];
                    if ($optalbsong & 1) $song['tags'] = $genreids;
                    $song['id'] = XbmusicHelper::createMusicItem($song, 'song');  
                    if ($song['id']=== false) {
                        $msg = Text::_('XBMUSIC_FAILED_SAVE_SONG').Xbtext::_($song['title'],XBSP1 + XBDQ + XBNL);
                        $ilogmsg .= XBERR.$msg;
                        $app->enqueueMessage(trim($msg),'Error');                         
                    } elseif ($song['id'] >0) {
                        $cnts['newsng'] ++;
                        $msg = Xbtext::_('XBMUSIC_NEW_SONG_SAVED',XBSP2 + XBTRL).'. Id:'.$song['id'].Xbtext::_($song['title'],XBSP1 + XBDQ + XBNL);
                        if ($loglevel==4) $ilogmsg .= XBINFO.$msg;
                        $newmsg .= (trim($msg).'<br />');
                    } else {
                        $msg = Text::_('XBMUSIC_SONG_ALREADY_DB').Xbtext::_($song['title'],XBSP3 + XBDQ).Xbtext::_('XBMUSIC_SONG_ALREADY_DB2',XBNL + XBTRL);
                        $ilogmsg = XBWARN.$msg;
                        $app->enqueueMessage(trim($msg),'Warning');
                        $song['id'] = $song['id'] * -1;
 //                       if ($optalbsong & 1) $gadd = XbcommonHelper::addTagsToItem('com_xbmusic.song', $song['id'], $genreids);
 //                       if ($loglevel==4) $ilogmsg .= XBINFO.$gadd.Xbtext::_('XBMUSIC_GENRES_TO_SONG',XBSP3 + XBTRL).$song['id'].': '.Xbtext::_($song['title'],XBDQ + XBNL);
                    }
                    if ($song['id']>0) {
                        $link = array('song_id'=>$song['id']);
                        $link['role'] = '1. Full Track';
                        $link['note'] = '';
                        $songlinks[] = $link; 
                        if ($dosongurl) {
                            foreach ($id3data['urls'] as $url) {
                                XbmusicHelper::addExtLink($url, 'song', $song['id']);
                            }
                        }
                    }
                }
                $trackdata['songlist'] = $songlinks;
            }
            
//5. get the artist(s) and create as necessary. make list of artists to link to track, album and song
            //we may have multiple artists
            if (isset($id3data['artistdata'])) {
                $artistlinks =[];
                foreach ($id3data['artistdata'] as &$artist) {
                    $artist['catid'] = $this->defcats['artist'];
                    $artist['songlist'] = $songlinks;
//                     if (isset($id3data['url'])) {
//                         $artist['url'] = $id3data['url'];
// //                         if (array_search($trackdata['url'], array_column($artist['ext_links'], 'link_url'))===false) {
// //                             $artist['ext_links']['ext_links0']= array('link_text'=>'internet', 
// //                                 'link_url'=>$trackdata['url'],
// //                                 'link_desc'=>Text::sprintf('XBMUSIC_LINK_FOUND_IN_ID3', $trackdata['title'])
// //                             );
                           
// //                         }
//                     }
                    $artist['id'] = XbmusicHelper::createMusicItem($artist, 'artist');
                    if ($artist['id'] === false) {
                        $msg = Text::_('XBMUSIC_ARTIST_SAVE_FAILED').Xbtext::_($artist['title'],13);
                        $ilogmsg .= XBERR.$msg;
                        $app->enqueueMessage(trim($msg),'Error');
                    } elseif ($artist['id'] > 0) {
                        $cnts['newart'] ++;
                        $msg = Xbtext::_('XBMUSIC_ARTIST_NEW_SAVED',XBSP2 + XBTRL).'id: '.$artist['id'].Xbtext::_($artist['name'],XBSP1 + XBDQ + XBNL);
                        if ($loglevel==4) $ilogmsg .= XBINFO.$msg;
                        $newmsg .= (trim($msg).'<br />');
                    } else { 
                        if ($loglevel==4) $ilogmsg .= XBINFO.Text::_('XBMUSIC_ARTIST_EXISTS').Xbtext::_($artist['name'],XBSP1 + XBDQ + XBNL);
                        $artist['id'] = $artist['id'] * -1;
                    }
                    if ($artist['id']>0) { 
                        $artistlinks[] = array('artist_id'=>$artist['id'], 'role'=>'', 'note'=>''); 
                        if ($doartisturl) {
                            foreach ($id3data['urls'] as $url) {
                                XbmusicHelper::addExtLink($url, 'artist', $artist['id']);
                            }
                        }
                    }                   
                }
                //only one new track so ok to use artist list in model 
                $trackdata['artistlist'] = $artistlinks;
            }
            
//6. Create image file if available
            if (($imgdata['data'])){
                // filename will be "albums/X/album-alias_sortartist.ext" or "singles/track-alias_sortartist.ext"
                // saved in "images/xbmusic/artwork/albums/[initial letter of album title]/"
                // if track has no album then filename "track-title_sortartist.ext" and save in artwork/singles/
                $imgfilename = '/images/xbmusic/artwork/';
                if (isset($id3data['albumdata']['alias'])) {
                    $imgfilename .= 'albums/'.strtolower($id3data['albumdata']['alias'][0]).'/'.$id3data['albumdata']['alias'];
                } else {
                    $imgfilename .= 'singles/'.$trackdata['alias'];
                    if (isset($trackdata['sortartist'])) $imgfilename .= '_'.$trackdata['sortartist'];
                }
                $imgext = XbcommonHelper::imageMimeToExt($imgdata['image_mime']);
                $imgfilename = $imgfilename.'.'.$imgext;
                $test = JPATH_ROOT.$imgfilename;
//                if (file_exists($test)) {
//                    $imgurl = Uri::root().ltrim($imgfilename,'/');
//                } else {
                    $imgurl = XbmusicHelper::createImageFile($imgdata, $imgfilename, $ilogmsg);                  
//                }
//                $app->enqueueMessage('img: '.$test.'<br />'.$imgurl);
                unset($imgdata['data']);
                if ($imgurl !== false) {
                    $imgdata['imagetitle'] = $imgdata['picturetype'];
                    $imgdata['imagedesc'] = $imgdata['description'];
                    $trackdata['imgurl'] = $imgurl;
                    $trackdata['imageinfo'] = json_encode($imgdata);
                } else {
                    $msg = Xbtext::_('XBMUSIC_ARTWORK_CREATE_FAILED',XBNL + XBTRL);
                    $ilogmsg .= XBWARN.$msg;
                    $app->enqueueMessage(trim($msg),'Warning');
                }
            } //end ifset image data

//7. get album data and create album if necessary
            if (isset($id3data['albumdata'])) {
                $albumdata = $id3data['albumdata'];
                $albumdata['catid'] = $this->defcats['album'];                
                if ($imgurl != false) {
                    $albumdata['imgurl'] = $imgurl;
                    if (isset($imgdata)) $albumdata['imageinfo'] = $imgdata;                   
                }
                if ($optalbsong > 1) $albumdata['tags'] = $genreids;
                $albumdata['id'] = XbmusicHelper::createMusicItem($albumdata, 'album');
                if ($albumdata['id']===false) {
                    $msg = Text::_('XBMUSIC_ALBUM_SAVE_FAILED').Xbtext::_($albumdata['title'],XBSP1 + XBDQ + XBNL);
                        $ilogmsg .= XBERR.$msg;
                        $app->enqueueMessage($msg,'Error');                       
                } elseif ($albumdata['id']>0) { 
                    $cnts['newalb'] ++;                       
                    $msg = Text::_('XBMUSIC_ALBUM_SAVED').Xbtext::_($albumdata['title'],XBSP1 + XBDQ + XBNL);
                    if ($loglevel==4) $ilogmsg .= XBINFO.$msg;
                    $newmsg .= trim($msg).'<br />';
                } else { //this implies the album alias already exists
                    $albumdata['id'] = $albumdata['id'] * -1;
                }
                if ($albumdata['id']!==false) {
                    if ($doalbumurl) {
                        foreach ($id3data['urls'] as $url) {
                            XbmusicHelper::addExtLink($url, 'album', $albumdata['id']);
                        }
                    }
                }
                if ($optalbsong > 1) {
//                    $gadd = XbcommonHelper::addTagsToItem('com_xbmusic.album', $albumdata['id'], $genreids);
//                   if ($loglevel==4) $ilogmsg .= XBINFO.$gadd.Xbtext::_('XBMUSIC_GENRES_TO_ALBUM',XBSP3 + XBTRL).$albumdata['id'].': '.Xbtext::_($albumdata['title'],XBDQ + XBNL);
                }
             }
           
//8. finally we can add links to track and save it
            if ($albumdata['id']>0) {
                $trackdata['album_id'] = $albumdata['id'];
            }
            $trackdata['songlist'] = $songlinks;
            $trackdata['artistlist'] = $artistlinks;
            if (XbcommonHelper::checkValueExists($trackdata['alias'], '#__xbmusic_tracks', 'alias')) {
                $append = '';
                if (key_exists('sortartist',$trackdata)) $append = $trackdata['sortartist'];
                if ($trackdata['album_id'] > 0) $append .= ' '.$albumdata['alias'];
                if ($append != '') $append = ' ['.$append.']';
                $trackdata['alias'] = XbcommonHelper::makeUniqueAlias($trackdata['alias'].$append, '#__xbmusic_tracks');
                //$msg .= ' - '.Text::sprintf('Trying save with alias %s',$trackdata['alias']);               
            }
            $trackdata['id'] = XbmusicHelper::createMusicItem($trackdata, 'track');
            if ($trackdata['id']=== false) {
                $msg = Text::_('XBMUSIC_TRACK_SAVE_FAILED').Xbtext::_($trackdata['title'],XBSP1 + XBDQ + XBNL);
                $ilogmsg .= XBERR.$msg;
                $app->enqueueMessage(trim($msg),'Error');
            } elseif ($trackdata['id'] >0) {
                $cnts['newtrk'] ++;
                $msg = Xbtext::_('XBMUSIC_TRACK_SAVED',XBSP2 + XBTRL).'. Id:'.$trackdata['id'].Xbtext::_($trackdata['title'],XBSP1 + XBDQ + XBNL);
                if ($loglevel==4) $ilogmsg .= XBINFO.$msg;
                $newmsg .= trim($msg).'<br />';
                $app->enqueueMessage($newmsg,'Success');
             //   $ilogmsg .= XBINFO.XbmusicHelper::addItemLinks($trackdata['id'],$songlinks, 'track','tracksong')."\n";
                if ($dotrackurl) {
                    foreach ($id3data['urls'] as $url) {
                        XbmusicHelper::addExtLink($url, 'track', $trackdata['id']);
                    }
                }
            } else {
                //hang on can this happen - track already exist?
                $msg .= ' : '.Text::_('XBMUSIC_TRACK_SAVE_FAILED').Xbtext::_($trackdata['title'], XBSP1 + XBDQ + XBNL);
                $ilogmsg .= XBERR.$msg;
                $newmsg .= trim($msg).'<br />';
                $app->enqueueMessage(trim($msg),'Error');
                
                //   $ilogmsg .= XBINFO.XbmusicHelper::addItemLinks($trackdata['id'],$songlinks, 'track','tracksong')."\n";
            }
             
        } //end if iset id3data[trackdata]
        return $ilogmsg;
    } //end parseID3()
        
    private function parseTrackdata($id3trackdata) {
        
    }
    
    private function parseAlbumdata($id3albumdata) {
        
    }
    
    private function parseArtistdata($id3artistdata) {
        
    }
    
    private function parseSongdata($id3songdata) {
        
    }
    
    private function createImageFile($id3imgdata) {
        
    }
    
    
    /************ AZURACAST FUNCTIONS ************/
    
    /**
     * @name importAzStation()
     * @desc creates or updates a single station with details from Azuracast 
     * @desc using the curent config credentials
     * @param int $azstid
     * @return int|object - id of new/updated dbstation is ok or error object if fails
     */
    public function importAzStation(int $azstid) {
//        $params = ComponentHelper::getParams('com_xbmusic');
//        $azurl = $params->get('az_url');
        $api = new AzApi();
        $azstation = $api->azStation($azstid);
        if (isset($azstation->code))
            return $azstation;            
        //now test if dbstastion exists ? update ELSE create
        $dbstid = $this->getDbStid(trim($azstation->url,'/'), $azstation->id);
        if ($dbstid > 0) {
            $res = $this->updateDbStation($dbstid,$azstation);
        } else {
            $res = $this->createDbStation($azstation);
        }
        return $res;
    }
    
    /**
     * @name getDbStid()
     * @desc returns the id of a station in the db which has the given azurl and azid
     * @param string $azurl
     * @param int $azstid
     * @return int|NULL null if the query fails
     */
    public function getDbStid(string $azurl, int $azstid) {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);
        $query->select('id');
        $query->from('#__xbmusic_azstations');
        $query->where($db->qn('az_url').' = '.$db->quote($azurl).' AND '.$db->qn('az_stid').' = '.$db->q($azstid));
        $db->setQuery($query);
        return $db->loadResult();       
    }
    
    /**
     * @name updateDbStation()
     * @replaces the azuracast data in a station with a fresh copy
     * @param int $dbstid
     * @param object $azstation
     * @return \Exception|boolean true if okay
     */
    public function updateDbStation(int $dbstid,object $azstation) {
        $api = new AzApi();
        $apikey = $api->getApikey();
        $azurl = $api->getAzurl();
        $apiname = $api->getApiname();
        // we need to update title alias apikey apiname azstream website azplayer description az info modified modified_by    
        $db = $this->getDatabase();
        $conditions = array('title='.$db->q($azstation->name), 'alias='.$db->q($azstation->shortcode),
            'az_stid='.$db->q($azstation->id), 'az_apikey='.$db->q($apikey), 'az_apiname='.$db->q($apiname),
            'az_stream='.$db->q($azstation->listen_url), 'website='.$db->q($azstation->url),
            'az_player='.$db->q($azstation->public_player_url), 'description='.$db->q($azstation->description), 
            'az_info='.$db->q(json_encode($azstation)), 
            'modified='.$db->q(Factory::getDate()->toSql()), 'modified_by='.$db->q($this->getCurrentUser()->id));
        
        $query = $db->getQuery(true);
        $query->update($db->qn('#__xbmusic_azstations'));
        $query->set($conditions);
        $query->where($db->qn('id').' = '.$db->q($dbstid));
        try {
            $db->setQuery($query);
            $res = $db->execute();
        } catch (\Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getCode().' '.$e->getMessage().'<br />'. $query>dump(),'Error');
            return $e;
        }
        Factory::getApplication()->enqueueMessage(Text::sprintf('XBMUSIC_STATION_UPDATED',$dbstid, $azstation->name));
        return $res;
    }
    
    /**
     * @name createDbStation()
     * @desc given details of an azuracast station creates a matching station in database
     * @param object $station
     * returns boolean|Exception true if ok
     */
    public function createDbStation($station) {
        $api = new AzApi();
        $apikey = $api->getApikey();
        $azurl = $api->getAzurl();
        $apiname = $api->getApiname();
        $uncatid = XbcommonHelper::getCatByAlias('uncategorised')->id; //TOD create stations catergory
//        XbcommonHelper::getCreateCat($catdata)
        $colarr = array('id', 'az_stid', 'title', 'alias',
            'az_apikey', 'az_apiname', 'az_url',
            'az_stream','website', 'az_player',
            'catid', 'description', 'az_info',
            'created','created_by', 'created_by_alias'
        );
        $db = $this->getDatabase();
        $values=array($db->q(0),$db->q($station->id),$db->q($station->name),$db->q($station->shortcode),
            $db->q($apikey), $db->q($apiname), $db->q($azurl),
            $db->q($station->listen_url), $db->q($station->url),$db->q($station->public_player_url),
            $db->q($uncatid),$db->q($station->description),$db->q(json_encode($station)),
            $db->q(Factory::getDate()->toSql()),$db->q($this->getCurrentUser()->id), $db->q('import from Azuracast')
        );
        //$db = Factory::getContainer()->get(DatabaseInterface::cl
        $query = $db->getQuery(true);
        $query->insert($db->qn('#__xbmusic_azstations'));
        $query->columns(implode(',',$db->qn($colarr)));
        $query->values(implode(',',$values));
        try {
            $db->setQuery($query);
            $res = $db->execute();            
        } catch (\Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getCode().' '.$e->getMessage().'<br />'. $query>dump(),'Error');
            return $e;
        }
        if ($res == true) { 
            $newid = $db->insertid();
            Factory::getApplication()->enqueueMessage(Text::sprintf('XBMUSIC_NEW_STATION_CREATED',$newid,$station->name));
        }
        return $res; 
    }
    
    public function deleteDbStation(int $stid) {
        //needs dbstid to delete
        $db = $this->getDatabase();
        $query = $db->getQuery(true);
        $query->delete($db->qn('#__xbmusic_azstations'));
        $query->where($db->qn('id').' = '.$db->q($stid));
        try {
            $db->setQuery($query);
            $res = $db->execute();
        } catch (\Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getCode().' '.$e->getMessage().'<br />'. $query>dump(),'Error');
            return $e;
        }
        return $res;
        //needs to also delete stid from playlists, tracks
    }
    
    /**
     * @name importAzStations()
     * @desc this will retrieve all stations available using the config api key
     * @desc this may not be the same as the key saved with the station in the database
     * @desc if the az_url and az_stid for a returned station is not already in the database
     * @desc then a new station will be created
     * @desc existing stations in the database will not be updated
     */
//     public function importAzStations() {
//         $api = new AzApi();
//         $apikey = $api->getApikey();
//         $azurl = $api->getAzurl();
//         $apiname = $api->getApiname();
//         $azstations = $api->azStations();
//         if (isset($azstations->code)) 
//             return $azstations;
//         $uncatid = XbcommonHelper::getCatByAlias('uncategorised')->id;
//         $uid = $this->getCurrentUser()->id;
        
//         if ($azstations) {
//             //$db = Factory::getContainer()->get(DatabaseInterface::cl
//             $db = Factory::getDbo();
//             $query = $db->getQuery(true);
//             $query->select('az_stid');
//             $query->from('#__xbmusic_azstations');
//             $query->where($db->qn('az_url').' = '.$db->q($azurl));
//             $db->setQuery($query);
//             $dbstations = $db->loadColumn();
//             $missing = [];
//             if ($dbstations) {
//                 foreach ($azstations as $station) {
//                     if (!in_array($station->id, $dbstations)) {
//                         $missing[] = $station;
//                     }
//                 }
//             } else {
//                 $missing = $azstations;
//             }
//             $colarr = array('id', 'az_stid', 'title', 'alias', 
//                     'az_apikey', 'az_apiname', 'az_url',
//                     'az_stream','website', 'az_player', 
//                     'catid', 'description', 'az_info',
//                     'created','created_by', 'created_by_alias'
//                 );
//             if (count($missing)>0) {
//                 foreach ($missing as $station) {
//                     $values=array($db->q(0),$db->q($station->id),$db->q($station->name),$db->q($station->shortcode),
//                         $db->q($apikey), $db->q($apiname), $db->q($azurl),
//                         $db->q($station->listen_url), $db->q($station->url),$db->q($station->public_player_url),
//                         $db->q($uncatid),$db->q($station->description),$db->q(json_encode($station)),
//                         $db->q(Factory::getDate()->toSql()),$db->q($uid), $db->q('import from Azuracast')
//                     );
//                     $query->clear();
//                     $query->insert($db->qn('#__xbmusic_azstations'));
//                     $query->columns(implode(',',$db->qn($colarr)));
//                     $query->values(implode(',',$values));
//                     $db->setQuery($query);
//                     $res = $db->execute();
//                     if ($res == false) Factory::getApplication()->enqueueMessage('sql error '. $query>dump());       
//                 }
//             }                          
//         }
//         return true;
//     }
    
   
//     public function getAzStations() {
//         $api = new AzApi();
//         return $api->azStations();
//     }
    
//     public function azPlaylists(int $stid) {
//         $api = new AzApi();
//         return $api->azPlaylists($stid);
//     }
    
//     public function azPlaylistPls(int $stid, int $plid) {
//         $api = new AzApi();
//         return $api->azPlaylistPls($stid, $plid);
//     }
    
//     public function getAzuracast() {
        
//     }
    
    /************ FILES FUNCTIONS ************/
       
    public function newsymlink($targ, $name) {
        $res = false;
        $targ = rtrim($targ,"/ ");
        $name = trim($name,"/ ");
        $mtype = 'Warning'; 
        $linkpath = pathinfo(JPATH_ROOT.'/xbmusic/'.$name, PATHINFO_DIRNAME);
        if (!is_dir($linkpath)) {
            if (!mkdir($linkpath)) {
                Factory::getApplication()->enqueueMessage( 'Error creating '.$linkpath,'Error');
                return false;
            }
        } 
        $linkname = JPATH_ROOT.'/xbmusic/'.$name;
        $msg = '/xbmusic/<b>'.$name.'</b> -> '.$targ.'<br />';
        if (file_exists($targ)) {;
            if (is_readable($targ)) {
                if (!file_exists($linkname)) {
                    $res = symlink($targ,$linkname);
                    if ($res) {
                        $msg .= Text::_('XBMUSIC_LINK_CREATED');
                        $mtype = 'Success';
                    } else {
                        $msg .= Text::_('XBMUSIC_ERROR_LINKING').' '.$targ.' to '.$linkname;                   
                    }
                } else {
                    $msg .= Text::_('XBMUSIC_LINK_EXISTS');
                }
            } else {
                $msg .= Text::_('XBMUSIC_TARGET_NOT_READABLE');
            }
        } else {
            $msg .= Text::_('XBMUSIC_TARGET_NOT_EXIST');
        }
        Factory::getApplication()->enqueueMessage($msg,$mtype);
        return $res;        
    }
    
    public function remsymlink( $link) {
        $msg = Text::_('Link').' <b>'.str_replace(JPATH_ROOT,'',$link).'</b> ';
        $res = false;
        $mtype = 'Warning';
        $name = str_replace(JPATH_ROOT,'',$link);
        if (is_link($link)) {
            $res = unlink($link);
            if ($res) {
                $msg .= Text::_('XBMUSIC_REMOVED_OK');
                $mtype = 'Success';
            } else {
                $msg .= Text::_('XBMUSIC_ERROR_UNLINKING');
                $mtype = 'Error';
            }
        } else {
            $msg .= Text::_('XBMUSIC_NOT_LINK');
        }
        Factory::getApplication()->enqueueMessage($msg,$mtype);
        return $res;
        
    }
    
    public function getSymlinks($path = '') {
        if ($path=='') $path = JPATH_ROOT . '/xbmusic/*';
        $result = [];
        $folders = glob($path, GLOB_ONLYDIR);
        if (!empty($folders)) {
            foreach ($folders AS $folder) {
                if (is_link($folder)) {                    
                    $result[] = array('target' => readlink($folder), 'name'=>$folder) ;
                } else {
                    $result =  array_merge($result,$this->getSymlinks($folder.'/*'));
                }
            }
        }
        return $result;
    }
    
}

