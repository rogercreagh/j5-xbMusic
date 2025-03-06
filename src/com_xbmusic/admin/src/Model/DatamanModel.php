<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Model/DatamanModel.php
 * @version 0.0.41.4 4th March 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
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
use Crosborne\Component\Xbmusic\Administrator\Helper\XbmusicHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbcommonHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\Xbtext;
use Crosborne\Component\Xbmusic\Administrator\Helper\AzApi;
use \SimpleXMLElement;


const XBINFO = '[INFO] ';
const XBWARN = '[WARNING] ';
const XBERR = '[ERROR] ';

class DatamanModel extends AdminModel {

    protected $trackcatid = 0;
    protected $albumcatid = 0;
    protected $artistcatid = 0;
    protected $songcatid = 0;
    protected $usedaycat = 0;
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
     * prepends the logging infomation to a log file with current date in /xbmusic-logs/
     * @param string|array $files - if a string it is assumed to be a folder 
     * @param int $cattype
     * @return boolean
     */
    public function parseFilesMp3($files, $usedaycat) {
        //if ($usedaycat == '') $usedaycat = $params->get('usedaycat','0');
        $params = ComponentHelper::getParams('com_xbmusic');
        $this->usedaycat = ($usedaycat === '') ? $params->get('impcat','0') : $usedaycat;
        //start log
        $loghead = '[IMPORT] Import ID3 Started '.date('H:i:s D jS M Y')."\n";
        $logmsg = '';
        
        //are we doing a whole folder, or selected files?
        if (is_string($files)) {
            $folder = trim($files);
            //get files in folder to array
            $files = [];
            $dirit = new \DirectoryIterator(JPATH_ROOT.'/xbmusic/'.$folder);
            foreach ($dirit as $fileinfo) {
                if (strtolower($fileinfo->getExtension()) == 'mp3') {
                    $files[] = trim($folder.$fileinfo->getFilename());
                }
            }            
        } elseif (!is_array($files)) {
            $errmsg .= Text::_('Invalid files list');
            Factory::getApplication()->enqueueMessage($errmsg,'Error');
            $logmsg .= $loghead.XBERR.$errmsg."\n ============================== \n\n";
            XbmusicHelper::writelog($logmsg);
            return false;
        }
        if (count($files)==0){
            $errmsg .= Xbtext::_('No mp3 files found in',2).$folder;
            Factory::getApplication()->enqueueMessage($errmsg,'Warning');
            $logmsg .= $loghead.XBWARN.$errmsg.XBENDLOG;
            XbmusicHelper::writelog($logmsg);
            return false;
        }
        // set up counts for logging and start time
        $cnts = array('newtrk'=>0,'duptrk'=>0,'newalb'=>0,'newart'=>0,'newsng'=>0,'errtrk'=>0);
        $starttime = time();
        //default categories for albums, artists and songs
        $uncatid = XbcommonHelper::getCatByAlias('uncategorised');
        $this->albumcatid = $params->get('defcat_album',$uncatid);
        $this->artistcatid = $params->get('defcat_artist',$uncatid);
        $this->songcatid = $params->get('defcat_song',$uncatid);
        $this->trackcatid = $params->get('defcat_track',$uncatid);
        //track category may be overriden by genre (tracks-genres-genre) on per item basis
        if ($this->usedaycat == 1) {
            //we are going to change the defaults to a day category under \imports 
            $daycatid = 0;
            $daycattitle = date('Y-m-d');
            $dcparent = XbcommonHelper::checkValueExists('imports', '#__categories', 'alias', "`extension` = 'com_xbmusic'");
            if ($dcparent === false) {
                $catdata = array('title'=>'Imports', 'alias'=>'imports', 'description'=>'parent for import date categories used when importing items from MP3');
                $dcparent = XbcommonHelper::createCategory($catdata, true);
            }
            $daycatid = XbcommonHelper::checkValueExists($daycattitle, '#__categories', 'alias', "`extension` = 'com_xbmusic'");
            if ($daycatid === false) {
                $parentcat = XbcommonHelper::getCatByAlias('imports');
                $parentid = ($parentcat>0) ? $parentcat->id : 1;
                $catdata = array('title'=>$daycattitle, 'alias'=>$daycattitle, 'parent_id'=>$parentid,'description'=>'items inported on '.date('D jS M Y'));
                $daycatid = XbcommonHelper::createCategory($catdata, true)->id;
            }
            if ($daycatid > 0) {
                $this->albumcatid = $daycatid;
                $this->artistcatid = $daycatid;
                $this->songcatid = $daycatid;
                $this->trackcatid = $daycatid;
            }
        } //endif cattype=1
        
        // ok we're going to iterate through the files
        $basemusicfolder = JPATH_ROOT.'/xbmusic/'; //XbmusicHelper::$musicBase;
        foreach ($files as $file) {
            $logmsg .= $this->parseID3($basemusicfolder.$file, $cnts);            
        }
        //update the log file with counts at the top
        $loghead .= '[SUM] '.$cnts['newtrk'].' new tracks, '.$cnts['duptrk'].' duplicates'."\n";
        $loghead .= '[SUM] '.$cnts['newalb'].' new albums, '.$cnts['newart'].' new artists, '.$cnts['newsng'].' new songs, '."\n";
        $loghead .= '[SUM] Elapsed time '.date('s', time()-$starttime).' seconds'."\n";
        $loghead .= " -------------------------- \n";
        $logmsg = $loghead.$logmsg;
        $logmsg .= '======================================'."\n\n";
        XbmusicHelper::writelog($logmsg);
        return true;
    } //end parseFilesMp3()    
    
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
        $loglevel = $params->get('loglevel',3);
        $app = Factory::getApplication();
        //start the logging for this file
        $ilogmsg = '[IMPORT BULK] '.str_replace(JPATH_ROOT.'/xbmusic/','',$filepathname)."\n";
        $enditem = " -------------------------- \n";
        $newmsg = Text::_('New items created:').'<br />';
        $trackdata = []; //only one track per file
        

// 1. check if filepathname already in database, if it exists already then exit
        if ( $tid = XbcommonHelper::checkValueExists($filepathname, '#__xbmusic_tracks', 'filepathname')) {
            $msg = Text::_('Track already in database with track_id').Xbtext::_($tid,XBSP1 + XBDQ);
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
            $msg = Text::_('Filename already in database at different location').Xbtext::_($fpath,7).Text::_('with track_id').Xbtext::_($fid,XBDQ + XBNL);
            $ilogmsg .= XBWARN.$msg;
            $msg2 = Xbtext::_('Importing this one anyway, but check and delete one or other if necessary',XBNL);
            $app->enqueueMessage(trim($msg).'<br />'.trim($msg2),'Warning');
            $ilogmsg .= XBWARN.$msg2;
        }
        
//3. okay, now get the id3 data
        $filedata = XbmusicHelper::getFileId3(($filepathname));
        
        if (!isset($filedata['id3tags']['title'])) { //could add any other required elements to the isset() function
            $msg = Xbtext::_('No title found in ID3 data. Cannot import',XBNL);
            $ilogmsg .= XBERR.$msg.$enditem;
            $cnts['errtrk'] ++;
            $app->enqueueMessage(trim($msg),'Error');
            return $ilogmsg;          
        }

//4. get the basic trackdata from id3
        $id3data = XbmusicHelper::id3dataToItems($filedata['id3tags'],$ilogmsg);
        if (isset($id3data['trackdata'])) {
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
                    $trackdata['catid'] = $this->trackcatid;
                }
            } else {
                $trackdata['catid'] = $this->trackcatid;
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
                    $song['catid'] = $this->songcatid;
                    if ($optalbsong & 1) $song['tags'] = $genreids;
                    $song['id'] = XbmusicHelper::createMusicItem($song, 'song');  
                    if ($song['id']=== false) {
                        $msg = Text::_('failed to save song').Xbtext::_($song['title'],XBSP1 + XBDQ + XBNL);
                        $ilogmsg .= XBERR.$msg;
                        $app->enqueueMessage(trim($msg),'Error');                         
                    } elseif ($song['id'] >0) {
                        $cnts['newsng'] ++;
                        $msg = Xbtext::_('new song saved. Id:',XBSP2).$song['id'].Xbtext::_($song['title'],XBSP1 + XBDQ + XBNL);
                        if ($loglevel==4) $ilogmsg .= XBINFO.$msg;
                        $newmsg .= (trim($msg).'<br />');
                    } else {
                        $msg = Text::_('Song already exists in database').Xbtext::_($song['title'],XBSP3 + XBDQ).Xbtext::_('Please check it is not a different song with the same title',XBNL);
                        $ilogmsg = XBWARN.$msg;
                        $app->enqueueMessage(trim($msg),'Warning');
                        $song['id'] = $song['id'] * -1;
                        if ($optalbsong & 1) $gadd = XbcommonHelper::addTagsToItem('com_xbmusic.song', $song['id'], $genreids);
                        if ($loglevel==4) $ilogmsg .= XBINFO.$gadd.Xbtext::_('genres added to song',XBSP3).$song['id'].': '.Xbtext::_($song['title'],XBDQ + XBNL);
                    }
                    if ($song['id']>0) {
                        $link = array('song_id'=>$song['id']);
                        $link['role'] = '1. Full Track';
                        $link['note'] = '';
                        $songlinks[] = $link; 
                    }
                }
                $trackdata['songlist'] = $songlinks;
            }
            
//5. get the artist(s) and create as necessary. make list of artists to link to track, album and song
            //we may have multiple artists
            if (isset($id3data['artistdata'])) {
                $artistlinks =[];
                foreach ($id3data['artistdata'] as &$artist) {
                    $artist['catid'] = $this->artistcatid;
                    $artist['songlist'] = $songlinks;
                    if (isset($trackdata['url_artist'])) {
                        $artist['ext_links']['ext_links0']= array('link_text'=>'internet', 
                            'link_url'=>$trackdata['url_artist'],
                            'link_desc'=>Text::sprintf('link found in track "%s" ID3 data', $trackdata['title'])
                            
                        );
                    }
                    $artist['id'] = XbmusicHelper::createMusicItem($artist, 'artist');
                    if ($artist['id'] === false) {
                        $msg = Text::_('failed to save artist').Xbtext::_($artist['title'],13);
                        $ilogmsg .= XBERR.$msg;
                        $app->enqueueMessage(trim($msg),'Error');
                    } elseif ($artist['id'] > 0) {
                        $cnts['newart'] ++;
                        $msg = Xbtext::_('new artist saved. Id:',XBSP2).$artist['id'].Xbtext::_($artist['name'],XBSP1 + XBDQ + XBNL);
                        if ($loglevel==4) $ilogmsg .= XBINFO.$msg;
                        $newmsg .= (trim($msg).'<br />');
                    } else { 
                        if ($loglevel==4) $ilogmsg .= XBINFO.Text::_('Artist already exists').Xbtext::_($artist['name'],XBSP1 + XBDQ + XBNL);
                        $artist['id'] = $artist['id'] * -1;
                    }
                    if ($artist['id']>0) { 
                        $artistlinks[] = array('artist_id'=>$artist['id'], 'role'=>'', 'note'=>''); 
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
                $imgurl = XbmusicHelper::createImageFile($imgdata, $imgfilename, $ilogmsg);
                if ($imgurl !== false) {
                    unset($imgdata['data']);
                    $imgdata['imagetitle'] = $imgdata['picturetype'];
                    $imgdata['imagedesc'] = $imgdata['description'];
                    $trackdata['imgurl'] = $imgurl;
                    $trackdata['imageinfo'] = json_encode($imgdata);
                } else {
                    $msg = Xbtext::_('failed to create image file',XBNL);
                    $ilogmsg .= XBWARN.$msg;
                    $app->enqueueMessage(trim($msg),'Warning');
                }
            } //end ifset image data

//7. get album data and create album if necessary
            if (isset($id3data['albumdata'])) {
                $albumdata = $id3data['albumdata'];
                $albumdata['catid'] = $this->albumcatid;                
                if (isset($imgdata)) $albumdata['imageinfo'] = $imgdata;                   
                if ($optalbsong > 1) $albumdata['tags'] = $genreids;
                if ($imgurl != false) $albumdata['imgurl'] = $imgurl;
                $albumdata['id'] = XbmusicHelper::createMusicItem($albumdata, 'album');
                if ($albumdata['id']===false) {
                    $msg = Text::_('failed to save album').Xbtext::_($albumdata['title'],XBSP1 + XBDQ + XBNL);
                        $ilogmsg .= XBERR.$msg;
                        $app->enqueueMessage($msg,'Error');                       
                } elseif ($albumdata['id']>0) { 
                    $cnts['newalb'] ++;                       
                    $msg = Text::_('new album saved').Xbtext::_($albumdata['title'],XBSP1 + XBDQ + XBNL);
                    if ($loglevel==4) $ilogmsg .= XBINFO.$msg;
                    $newmsg .= trim($msg).'<br />';
                } else { //this implies the album alias already exists
                    $albumdata['id'] = $albumdata['id'] * -1;
                }

                if ($optalbsong > 1) {
                    $gadd = XbcommonHelper::addTagsToItem('com_xbmusic.album', $albumdata['id'], $genreids);
                    if ($loglevel==4) $ilogmsg .= XBINFO.$gadd.Xbtext::_('genres added to album',XBSP3).$albumdata['id'].': '.Xbtext::_($albumdata['title'],XBDQ + XBNL);
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
                $msg = Text::_('failed to save track').Xbtext::_($trackdata['title'],XBSP1 + XBDQ + XBNL);
                $ilogmsg .= XBERR.$msg;
                $app->enqueueMessage(trim($msg),'Error');
            } elseif ($trackdata['id'] >0) {
                $cnts['newtrk'] ++;
                $msg = Xbtext::_('new track saved. Id:',XBSP2).$trackdata['id'].Xbtext::_($trackdata['title'],XBSP1 + XBDQ + XBNL);
                if ($loglevel==4) $ilogmsg .= XBINFO.$msg;
                $newmsg .= trim($msg).'<br />';
                $app->enqueueMessage($newmsg,'Success');
             //   $ilogmsg .= XBINFO.XbmusicHelper::addItemLinks($trackdata['id'],$songlinks, 'track','tracksong')."\n";
            } else {
                $msg .= ' : '.Text::_('FAILED to save track').Xbtext::_($trackdata['title'], XBSP1 + XBDQ + XBNL);
                $ilogmsg .= XBERR.$msg;
                $newmsg .= trim($msg).'<br />';
                $app->enqueueMessage(trim($msg),'Error');
                
             //   $ilogmsg .= XBINFO.XbmusicHelper::addItemLinks($trackdata['id'],$songlinks, 'track','tracksong')."\n";
            }
             
        } //end if iset id3data[trackdata]
        return $ilogmsg;
    } //end parseID3()
        
    public function getLastImportLog() {
        $file = null;
        foreach(new DirectoryIterator(JPATH_ROOT.'/xbmusic-logs') as $item) {
            if ($item->isFile() && (empty($file) || $item->getMTime() > $file->getMTime())) {
                $file = clone $item;
 //               $pathname = ;
            }
        }
        if (!is_null($file)) {
//            if (file_exists($file->getPathname)) { //do we need this? surely it exists
                $logtxt = file_get_contents($file->getPathname());
                return $logtxt;
//            }
        }
        return '';
        
//        return file_get_contents($file->getPathname);
    }
    
    /************ AZURACAST FUNCTIONS ************/
    
    /**
     * @name importAzStations()
     * @desc this will retrieve all stations available using the config api key
     * @desc this may not be the same as the key saved with the station in the database
     * @desc if the az_url and az_id for a returned station is not already in the database
     * @desc then a new station will be created
     * @desc existing stations in the database will not be updated
     */
    public function importAzStations() {
        $api = new AzApi();
        $apikey = $api->getApikey();
        $azurl = $api->getAzurl();
        $apiname = $api->getApiname();
        $azstations = $api->azStations();
        $uncatid = XbcommonHelper::getCatByAlias('uncategorised')->id;
        $uid = $this->getCurrentUser()->id;
        
        if ($azstations) {
            //$db = Factory::getContainer()->get(DatabaseInterface::cl
            $db = Factory::getDbo();
            $query = $db->getQuery(true);
            $query->select('az_id');
            $query->from('#__xbmusic_azstations');
            $query->where($db->qn('az_url').' = '.$db->q($azurl));
            $db->setQuery($query);
            $dbstations = $db->loadColumn();
            $missing = [];
            if ($dbstations) {
                foreach ($azstations as $station) {
                    if (!in_array($station->id, $dbstations)) {
                        $missing[] = $station;
                    }
                }
                $colarr = array('id', 'az_id', 'title', 'alias', 
                    'az_apikey', 'az_apiname', 'az_url',
                    'az_stream','website', 'az_player', 
                    'catid', 'description', 'az_info',
                    'created','created_by', 'created_by_alias'
                );
                if (count($missing)>0) {
                    foreach ($missing as $station) {
                        $values=array($db->q(0),$db->q($station->id),$db->q($station->name),$db->q($station->shortcode),
                            $db->q($apikey), $db->q($apiname), $db->q($azurl),
                            $db->q($station->listen_url), $db->q($station->url),$db->q($station->public_player_url),
                            $db->q($uncatid),$db->q($station->description),$db->q(json_encode($station)),
                            $db->q(Factory::getDate()->toSql()),$db->q($uid), $db->q('import from Azuracast')
                        );
                        $query->clear();
                        $query->insert($db->qn('#__xbmusic_azstations'));
                        $query->columns(implode(',',$db->qn($colarr)));
                        $query->values(implode(',',$values));
                        $db->setQuery($query);
                        $res = $db->execute();
                        if ($res == false) Factory::getApplication()->enqueueMessage('sql error '. $query>dump());       
                    }
                }
                
            }
        }
    }
    
    public function loadAzSt($azid) {
        
        return false;
    }
    
    public function getAzStations() {
        $api = new AzApi();
        return $api->azStations();
    }
    
    public function azPlaylists(int $stid) {
        $api = new AzApi();
        return $api->azPlaylists($stid);
    }
    
    public function azPlaylistPls(int $stid, int $plid) {
        $api = new AzApi();
        return $api->azPlaylistPls($stid, $plid);
    }
    
    public function getAzuracast() {
        
    }
    
    /************ FILES FUNCTIONS ************/
       
    public function newsymlink($targ, $name) {
        $res = false;
        $mtype = 'Warning';
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
                        $msg .= Text::_('XBMUSIC_ERROR_LINKING');                   
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

