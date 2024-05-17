<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Model/TrackModel.php
 * @version 0.0.6.2 17th May 2024
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
        //	    $basePath = JPATH_ADMINISTRATOR.'/components/com_content';
        //	    require_once $basePath.'/models/track.php';
        //	    $trackmodel = new ContentModelArticle(array('table_path' => $basePath . '/tables'));
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
            }
            $id3data = json_decode($item->id3_data);
            $item->id3_tags = $id3data->id3tags;
            $item->audioinfo = $id3data->audioinfo;
            $item->fileinfo = $id3data->fileinfo;
            $item->imageinfo = $id3data->imageinfo;
            $item->image_type = $id3data->imageinfo->picturetype;
            $item->image_desc = $id3data->imageinfo->description;
        }        
        return $item;
    }
    
    public function getForm($data = [], $loadData = true) {
        $app  = Factory::getApplication();
        
        // Get the form.
        $form = $this->loadForm('com_xbmusic.track', 'track', ['control' => 'jform', 'load_data' => $loadData]);
        
        if (empty($form)) {
            return false;
        }
        $params = ComponentHelper::getParams('com_xbmusic');
        if ($params->get('use_xbmusic', 1)) {
            $basemusicfolder = JPATH_ROOT.'/xbmusic/'.$params->get('xbmusic_subfolder','');
        } else {
            $basemusicfolder = ($params->get('music_path','') != '') ? $params->get('music_path') : JPATH_ROOT.'/xbmusic/';
        }
        $form->setFieldAttribute('pathname','directory',$basemusicfolder);
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
            
            $retview = $app->input->get('retview','');
            // Pre-select some filters (Status, Category, Language, Access) in edit form if those have been selected in Article Manager: Articles
            if (($this->getState('track.id') == 0) && ($retview != '')) {
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
        $filter = InputFilter::getInstance();
        $infomsg = '';
        $warnmsg = '';
        $filepathname = rtrim($data['pathname'],'/').'/'.$data['filename'];
        if (file_exists($filepathname)) {
            $filedata = XbmusicHelper::getFileId3($filepathname);            
            if (empty($data['id3_image'])) $data['id3_image'] = $filedata['imageinfo']['data'];
            unset($filedata['imageinfo']['data']);
            if ($data['id3_data'] == '') $data['id3_data'] = json_encode($filedata);
        }
        
            if ($data['title'] == '') {
                if ($filedata['id3tags']['title'] != '') {
                    $data['title'] = $filedata['id3tags']['title'];
                }
            } elseif ($data['title'] != $filedata['id3tags']['title']) {
                $infomsg .= 'Title does not match ID3 title<br />';
            }
            if ($data['rec_date'] == '') {
                if (isset($filedata['id3tags']['recording_time'])) {
                    $data['rec_date'] = ($filedata['id3tags']['recording_time']); 
                    //TODO check and format this
                    $infomsg .= 'Check format of recording date<br />';
                }
            }
            if ($data['rel_date'] == '') {
                if (isset($filedata['id3tags']['year'])) {
                    $data['rel_date'] = $filedata['id3tags']['year']; 
                    //TODO check and format this
                    $infomsg .= 'Check format of release date<br />';
                }
            }
            if ($data['perf_name'] == '') {
                if (isset($filedata['id3tags']['artist'])) {
                    $data['perf_name'] = $filedata['id3tags']['artist'];
                }
            }

        //alias is the filename so we'll set and check it every time
            $params = ComponentHelper::getParams('com_xbmusic');
            if ($params->get('use_xbmusic', 1)) {
                $basemusicfolder = JPATH_ROOT.'/xbmusic/'.$params->get('xbmusic_subfolder','');
            } else {
                $basemusicfolder = ($params->get('music_path','') != '') ? $params->get('music_path') : JPATH_ROOT.'/xbmusic/';
            }
            $newalias = OutputFilter::stringURLSafe(str_replace($basemusicfolder, '' , $data['pathname']).'-'. $data['filename']);
            if (($data['id'] == 0) && XbmusicHelper::checkValueExists($newalias, '#__xbmusic_tracks', 'alias')) {
                $warnmsg .= 'Duplicate alias - this filename is already in the database';
                $app->enqueueMessage($warnmsg,'Warning');
                if ($infomsg != '') $app->enqueueMessage($infomsg, 'Information');
                return false;
            }
            $data['alias'] = $newalias;
        
        //check title against song table and add if missing (alias created from title)
        if ($filedata['id3tags']['title'] != '') {
            $songtitle = $filedata['id3tags']['title'];
            $song = XbmusicHelper::getItems('#__xbmusic_songs', 'title', $songtitle);
            if (empty($song)) {
                //new song add it to db and get id to add to data
                $infomsg .= 'Song "'.$songtitle.'" added to xbmusic_songs table<br />';
            } else {
                $warnmsg .= 'Song title "'.$songtitle.'" found '.count($song).' times in database songs. Please correct duplicate entry<br />';
            }
        }
        //check album against album table and add if missing (alias created from artist-album)
        //check artist against artist table and add if missing (alias from name)
        //check album-artist against artist table and add if missing
        
        
        if (isset($data['created_by_alias'])) {
            $data['created_by_alias'] = $filter->clean($data['created_by_alias'], 'TRIM');
        }
        
        
//         if ((int) $data['catid'] > 0)
//         {
//             $data['catid'] = CategoriesHelper::validateCategoryId($data['catid'], 'com_xbmusic');
//         }
        
        //merge groups back into tags
        /*
         if ($data['taggroup1']) {
            $data['tags'] = ($data['tags']) ? array_unique(array_merge($data['tags'],$data['taggroup1'])) : $data['taggroup1'];
        }
        if ($data['taggroup2']) {
            $data['tags'] = ($data['tags']) ? array_unique(array_merge($data['tags'],$data['taggroup2'])) : $data['taggroup2'];
        }
        if ($data['taggroup3']) {
            $data['tags'] = ($data['tags']) ? array_unique(array_merge($data['tags'],$data['taggroup3'])) : $data['taggroup3'];
        }
        if ($data['taggroup4']) {
            $data['tags'] = ($data['tags']) ? array_unique(array_merge($data['tags'],$data['taggroup4'])) : $data['taggroup4'];
        }
         */            
        if (parent::save($data)) {
            // Check possible workflow
            if ($infomsg != '') $app->enqueueMessage($infomsg, 'Information');            
            return true;
        }
        if ($infomsg != '') $app->enqueueMessage($infomsg, 'Information');
        if ($warnmsg != '') $app->enqueueMessage($warnmsg, 'Warning');        
        return false;
    }
    
    protected function preprocessForm(Form $form, $data, $group = 'content') {
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
  
    public function getTrackSongList() {
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->select('a.id as song_id, ba.note AS note');
        $query->from('#__xbmusic_songtrack AS ba');
        $query->innerjoin('#__xbmusic_songs AS a ON ba.song_id = a.id');
        $query->where('ba.track_id = '.(int) $this->getItem()->id);
        $query->order('ba.listorder ASC');
        $db->setQuery($query);
        return $db->loadAssocList();
    }
    
    function storeTrackSongs($track_id, $songList) {
        //delete existing role list
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->delete($db->quoteName('#__xbmusic_songtrack'));
        $query->where('track_id = '.$track_id);
        $db->setQuery($query);
        $db->execute();
        //restore the new list
        $listorder=0;
        foreach ($songList as $song) {
            if ($song['song_id'] > 0) {
                $listorder ++;
                $query = $db->getQuery(true);
                $query->insert($db->quoteName('#__xbmusic_songtrack'));
                $query->columns('song_id,track_id,note,listorder');
                $query->values('"'.$song_id.'","'.$trk['track_id'].'","'.$trk['note'].'","'.$listorder.'"');
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
    
    
    private function canCreateCategory() {
        return $this->getCurrentUser()->authorise('core.create', 'com_content');
    }
    
    /*** not needed?
     public function validate($form, $data, $group = null) {
     if (!$this->getCurrentUser()->authorise('core.admin', 'com_xbmusic')) {
     if (isset($data['rules'])) {
     unset($data['rules']);
     }
     }
     
     return parent::validate($form, $data, $group);
     }
     ****/
    
    /*
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
    
}

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


