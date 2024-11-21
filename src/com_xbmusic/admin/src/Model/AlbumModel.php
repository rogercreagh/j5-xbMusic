<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Model/AlbumModel.php
 * @version 0.0.18.9 21st November 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
//use Joomla\CMS\Form\Form;
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
use Crosborne\Component\Xbmusic\Administrator\Helper\XbcommonHelper;
use \SimpleXMLElement;
//use Symfony\Component\Validator\Constraints\IsNull;

class AlbumModel extends AdminModel {
  
    public $typeAlias = 'com_xbmusic.album';
    
    protected $xbmusic_batch_commands = array(
        'untag' => 'batchUntag',
    );
    
    public function batch($commands, $pks, $contexts) {
        $this->batch_commands = array_merge($this->batch_commands, $this->xbmusic_batch_commands);
        return parent::batch($commands, $pks, $contexts);
    } 
    
    protected function batchUntag($value, $pks, $contexts) {
        $taghelper = new TagsHelper();
        $message = 'tag:'.$value.' removed from albums :';
        foreach ($pks as $pk) {
            if ($this->user->authorise('core.edit', $contexts[$pk])) {
                $existing = $taghelper->getItemTags('com_xbmusic.album', $pk, false);
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
    
    protected function canDelete($record) {
        if (empty($record->id) || ($record->status != -2)) {
            return false;
        }
        
        return $this->getCurrentUser()->authorise('core.delete', 'com_xbmusic.album.' . (int) $record->id);
    }
    
    protected function canEditState($record) {
        $user = $this->getCurrentUser();
        
        // Check for existing album.
        if (!empty($record->id)) {
            return $user->authorise('core.edit.state', 'com_xbmusic.album.' . (int) $record->id);
        }
        
        // New album, so check against the category.
        if (!empty($record->catid)) {
            return $user->authorise('core.edit.state', 'com_xbmusic.category.' . (int) $record->catid);
        }
        
        // Default to component settings if neither album nor category known.
        return parent::canEditState($record);
    }
    
    protected function prepareTable($table) {
        
        // Reorder the albums within the category so the new album is first
        if (empty($table->id)) {
            $table->reorder('catid = ' . (int) $table->catid . ' AND status >= 0');
        }
    }
    
    public function getItem($pk = null) {
        if ($item = parent::getItem($pk)) {
            if (!empty($item->id)) {
                $tagsHelper = new TagsHelper();
                $item->tags = $tagsHelper->getTagIds($item->id, 'com_xbmusic.album');
                if (isset($item->imageinfo)) {
                    $item->imageinfo = json_decode($item->imageinfo);
                    if((isset($item->imageinfo->picturetype)) && (!isset($item->imageinfo->imagetitle))) 
                        $item->imageinfo->imagetitle = $item->imageinfo->picturetype;
                    if((isset($item->imageinfo->description)) && (!isset($item->imageinfo->imagedesc)))
                        $item->imageinfo->imagedesc = $item->imageinfo->description;
                    if (isset($item->imageinfo->imagetitle)) $item->newimagetitle = $item->imageinfo->imagetitle;
                    if (isset($item->imageinfo->imagedesc)) $item->newimagetitle = $item->imageinfo->imagedesc;
                }
                //                 $item->imginfo = [];
//                 if ($item->imgfile != '') {
//                     $file = trim(str_replace(Uri::root(),JPATH_ROOT.'/',$item->imgfile));
//                     if (file_exists($file)){
//                         $item->imginfo['folder'] = dirname(str_replace(Uri::root(),'',$item->imgfile)); 
//                         $item->imginfo['basename'] = basename($file);
//                         $bytes = filesize($file);
//                         $lbl = Array('bytes','kB','MB','GB');
//                         $factor = floor((strlen($bytes) - 1) / 3);
//                         $item->imginfo['filesize'] = sprintf("%.2f", $bytes / pow(1024, $factor)) . @$lbl[$factor];
//                         $item->imginfo['date'] = date("d M Y at H:i",filemtime($file));
//                         $imagesize = getimagesize($file);
//                         $item->imginfo['mime'] = $imagesize['mime'];
//                         $item->imginfo['width'] = $imagesize[0];
//                         $item->imginfo['ht'] = $imagesize[1];
//                     }
//                 }
            }
        }        
        return $item;
    }
    
    public function getForm($data = [], $loadData = true) {
        $app  = Factory::getApplication();
        $params = ComponentHelper::getParams('com_xbmusic');
        
        // Get the form.
        $form = $this->loadForm('com_xbmusic.album', 'album', ['control' => 'jform', 'load_data' => $loadData]);
        
        if (empty($form)) {
            return false;
        }
        
        //dynamically add fields for any taggroups defined in options and add the tags for them
        $tagsarr = explode(',',$form->getValue('tags',null,''));
        $parentids = $params->get('albumtagparents',[]);
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
        $data = $app->getUserState('com_xbmusic.edit.album.data', []);
        
        if (empty($data)) {
            $data = $this->getItem();
            $data->tracklist = $this->getAlbumTrackList($data->id);
            $data->songlist = $this->getAlbumSongList();
            
            $retview = $app->input->get('retview','');
            // Pre-select some filters (Status, Category, Language, Access) in edit form if those have been selected in Article Manager: Articles
            if (($this->getState('album.id') == 0) && ($retview != '')) {
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
        if (($data['newimage'])) {
            $imgurl = Uri::root().substr($data['newimage'],0,strpos($data['newimage'], "#"));
            if ($imgurl != $data['imgurl']) {
                $data['imgurl'] = $imgurl;
                unset($data['imageinfo']['datalength']);
                unset($data['imageinfo']['image_height']);
                unset($data['imageinfo']['image_width']);
                unset($data['imageinfo']['picturetype']);
                unset($data['imageinfo']['description']);
                unset($data['imageinfo']['image_mime']);
                $file = str_replace(Uri::root(),'',$imgurl);
                $data['imageinfo']['folder'] = dirname($file);
                $file = JPATH_ROOT.'/'.$file;
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
                $data['imageinfo']['imagetitle']='';
                $data['imageinfo']['imagedesc']='';
            }
        }
        if (isset($data['newimagetitle'])) {
            $data['imageinfo']['imagetitle'] = $data['newimagetitle'];
        }
        if (isset($data['newimagedesc'])) {
            $data['imageinfo']['imagedesc'] = $data['newimagedesc'];
        }
        
        $data['imageinfo'] = json_encode($data['imageinfo']);

        //alias is the title so we'll set and check it every time
        $albumalias = $data['title'];
        if (isset($data['sortartist'])) $albumalias.= '-'.$data['sortartist'];
        $data['alias'] = XbcommonHelper::makeAlias($albumalias);
//         $aid = XbcommonHelper::checkValueExists($data['alias'], '#__xbmusic_albums', 'alias');
//         if ($aid != false) {
// //            $id = XbcommonHelper::checkValueExists($data['alias'], '#__xbmusic_albums', 'alias');
//             $data['id'] = $aid;
//             $this->setState('album.id',$aid);
//             return true;
//         }
//        $data['alias'] = $newalias;        
        
        if (isset($data['created_by_alias'])) {
            $data['created_by_alias'] = $filter->clean($data['created_by_alias'], 'STRING');
        }
        
        //merge any tag groups back into tags
        $parentids = $params->get('albumtagparents',[]);
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
        
        // ok ready to save the album data
        if (parent::save($data)) {
            $aid = $this->getState('album.id');
            $this->storeAlbumTracks($aid, $data['tracklist']);
            $this->storeAlbumSongs($aid, $data['songlist']);
            // Check possible workflow
            if ($infomsg != '') $app->enqueueMessage($infomsg, 'Information');            
            return true;
        }
        if ($infomsg != '') $app->enqueueMessage($infomsg, 'Information');
        if ($warnmsg != '') $app->enqueueMessage($warnmsg, 'Warning');        
        return false;
    }
    
//     protected function preprocessForm(Form $form, $data, $group = 'content') {
//         Factory::getApplication()->getSession()->set('albumtitle', $data->title);
//         if ($this->canCreateCategory()) {
//             $form->setFieldAttribute('catid', 'allowAdd', 'true');
            
//             // Add a prefix for categories created on the fly.
//             $form->setFieldAttribute('catid', 'customPrefix', '#new#');
//         }
        
//         parent::preprocessForm($form, $data, $group);
//     }
    
//     public function saveorder($idArray = null, $lft_array = null)
//     {
//         // Get an instance of the table object.
//         $table = $this->getTable();
        
//         if (!$table->saveorder($idArray, $lft_array))
//         {
//             $this->setError($table->getError());
            
//             return false;
//         }
        
//         return true;
//     }
    
//     private function canCreateCategory() {
//         return $this->getCurrentUser()->authorise('core.create', 'com_content');
//     }
    
    private function getAlbumTrackList($album_id) {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);
        $query->select('t.id AS track_id, t.discno AS disc_no, t.trackno AS track_no');
        $query->from('#__xbmusic_tracks AS t');
        $query->where('t.album_id = '.$db->q($album_id));
        $query->order('t.discno, t.trackno, t.title ASC');
        $db->setQuery($query);
        return $db->loadAssocList();
    }
 
    private function getAlbumSongList() {
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->select('song_id, role, a.note AS note');
        $query->from('#__xbmusic_songalbum AS a');
        $query->innerjoin('#__xbmusic_songs AS b ON song_id = b.id');
        $query->where('album_id = '.(int) $this->getItem()->id);
        $query->order('listorder ASC');
        $db->setQuery($query);
        return $db->loadAssocList();
    }
    
    
    private function storeAlbumTracks($album_id, $trackList) {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);
        //restore the new list
        foreach ($trackList as $trk) {
            $query->clear();
            $query->update('#__xbmusic_tracks')
                ->set('album_id = '.$db->q($album_id))
                ->set('trackno = '.$db->q($trk['track_no']))
                ->set('discno = '.$db->q($trk['disc_no']));
            $query->where('id = '.$db->q($trk['track_id']));
            //try
            $db->setQuery($query);
            $db->execute();
        }
    }
    
    private function storeAlbumSongs($album_id, $songlist) {
        //delete existing role list
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->delete($db->quoteName('#__xbmusic_songalbum'));
        $query->where('album_id = '.$album_id);
        $db->setQuery($query);
        $db->execute();
        //restore the new list
        $listorder=0;
        foreach ($songlist as $song) {
            if ($song['song_id'] > 0) {
                $listorder ++;
                $query = $db->getQuery(true);
                $query->insert($db->quoteName('#__xbmusic_songalbum'));
                $query->columns('album_id,song_id,role,note,listorder');
                $query->values('"'.$album_id.'","'.$song['song_id'].'","'.$song['role'].'","'.$song['note'].'","'.$listorder.'"');
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
}

