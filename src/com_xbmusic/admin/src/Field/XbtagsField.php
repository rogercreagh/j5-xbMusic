<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Field/XbtagsField.php
 * @version 0.0.11.6 19th July 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\Database\ParameterType;
use Joomla\Utilities\ArrayHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbmusicHelper;

class XbtagsField extends ListField
{
    /**
     * A variation of the built in TagField to allow limiting selection to children of a specified parent and only a specified number of levels
     */
    public $type = 'Xbtags';
    public $isNested = null;
    protected $comParams = null;
    protected $layout = 'xbmusic.form.field.xbtags';
    
    /**
     * {@inheritDoc}
     * @see \Joomla\CMS\Form\Field\TagField::getOptions()
     * Modified Roger C-O Nov 2022 to allow options to limit values to children of a specified tag.
     * Add a new property 'parent (int) the id of the parent of the tags to be listed as options
     * Also forces nested mode to prevent ajax going outside the specified branch
     */
    
    public function __construct()
    {
        parent::__construct();
        
        // Load com_tags config
        $this->comParams = ComponentHelper::getParams('com_tags');
    }
    
    protected function getInput()
    {
        $data = $this->collectLayoutData();
        
        if (!\is_array($this->value) && !empty($this->value)) {
            if ($this->value instanceof TagsHelper) {
                if (empty($this->value->tags)) {
                    $this->value = [];
                } else {
                    $this->value = $this->value->tags;
                }
            }
            
            // String in format 2,5,4
            if (\is_string($this->value)) {
                $this->value = explode(',', $this->value);
            }
            
            // Integer is given
            if (\is_int($this->value)) {
                $this->value = [$this->value];
            }
            
            $data['value'] = $this->value;
        }
        
        $data['remoteSearch']  = $this->isRemoteSearch();
        $data['options']       = $this->getOptions();
        $data['isNested']      = $this->isNested();
        $data['allowCustom']   = $this->allowCustom();
        $data['minTermLength'] = (int) $this->comParams->get('min_term_length', 3);
        
        return $this->getRenderer($this->layout)->render($data);
    }
    
    /**
     *
     */
    public function getOptions()
    {
        $published = (string) $this->element['published'] ?: array(0, 1);
        
        $levels = 0;
        $maxlevel = 0;
        $parent_id = (int) $this->element['parent'];
        $levels = (string) $this->element['levels'];
        if ($levels > 0) {
            //if parent set get level
            $maxlevel = $levels;
            if ($parent_id>1) {
                //get parent level
                $ptag = XbmusicHelper::getTag($parent_id);
                $maxlevel += $ptag->level;
            }
        }
        $app       = Factory::getApplication();
        $language  = null;
        $options   = [];
        
        // This limit is only used with isRemoteSearch
        $prefillLimit   = 30;
        $isRemoteSearch = $this->isRemoteSearch();
        
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
        ->select('DISTINCT a.id AS value, a.path, a.title AS text, a.level, a.published, a.lft')
        ->from($db->quoteName('#__tags', 'a'))
        ->join('LEFT', $db->qn('#__tags','b').' ON '.
            $db->qn('a.lft').' > '.$db->qn('b.lft').' AND '.$db->qn('a.rgt').' < '.$db->qn('b.rgt'));
        
        // Limit options to only children of parent
        if ($parent_id > 1) {
            $query->where('b.id = '. $db->q($parent_id));
        }
        //limit how far down the tree to go
        if (($levels > 0) && ($maxlevel > 0)) {
            $query->where($db->qn('a.level').' <= '.$db->q($maxlevel));
        }
        
        //never show ROOT
        $query->where($db->qn('a.lft') . ' > 0');
        
        //add language stuff from TagField if required
        
        if (is_numeric($published))
        {
            $query->where('a.published = ' . (int) $published);
        }
        elseif (is_array($published))
        {
            $published = ArrayHelper::toInteger($published);
            $query->where('a.published IN (' . implode(',', $published) . ')');
        }
        
        if ($this->isNested())
        $query->order('a.path ASC');

        // Preload only active values and 30 most used tags or fill up
        if ($isRemoteSearch) {
            // Load the most $prefillLimit used tags
            $topQuery = $db->getQuery(true)
            ->select($db->quoteName('tag_id'))
            ->from($db->quoteName('#__contentitem_tag_map'))
            ->group($db->quoteName('tag_id'))
            ->order('count(*)')
            ->setLimit($prefillLimit);
            
            $db->setQuery($topQuery);
            $topIds = $db->loadColumn();
            
            // Merge the used values into the most used tags
            if (!empty($this->value) && \is_array($this->value)) {
                $topIds = array_unique(array_merge($topIds, $this->value));
            }
            
            // Set the default limit for the main query
            $query->setLimit($prefillLimit);
            
            if (!empty($topIds)) {
                // Filter the ids to the most used tags and the selected tags
                $preQuery = clone $query;
                $preQuery->clear('limit')
                ->whereIn($db->quoteName('a.id'), $topIds);
                
                $db->setQuery($preQuery);
                
                try {
                    $options = $db->loadObjectList();
                } catch (\RuntimeException $e) {
                    return [];
                }
                
                // Limit the main query to the missing amount of tags
                $count        = \count($options);
                $prefillLimit -= $count;
                $query->setLimit($prefillLimit);
                
                // Exclude the already loaded tags from the main query
                if ($count > 0) {
                    $query->whereNotIn($db->quoteName('a.id'), ArrayHelper::getColumn($options, 'value'));
                }
            }
        }
        
        // Only execute the query if we need more tags not already loaded by the $preQuery query
        if (!$isRemoteSearch || $prefillLimit > 0) {
            // Get the options.
            $db->setQuery($query);
            
            try {
                $options = $db->loadObjectList();
            } catch (\RuntimeException $e) {
                return [];
            }
        }
                
        // Prepare nested data
        if ($this->isNested()) {
            $this->prepareOptionsNested($options);
        } else {
            foreach ($options as &$option) {
                $option->text = substr($option->path,0, strpos($option->path,'/',-1))."\u{2003}".$option->text;
            }
        }
        // Block the possibility to set a tag as it own parent
        // REMOVED as this is only relevant to com_tags.tag
        
        // Merge any additional options in the XML definition.
 //       $options = array_merge(get_parent_class(get_parent_class(get_class($this)))::getOptions(), $options);
        $options = array_merge(parent::getOptions(), $options);
        
        return $options;
    }
    
    protected function prepareOptionsNested(&$options)
    {
        if ($options) {
            foreach ($options as &$option) {
                $repeat       = (isset($option->level) && $option->level - 1 >= 0) ? $option->level - 1 : 0;
 //               $option->text = str_repeat('- ', $repeat) . $option->text;
                $prefix = ($repeat>0) ? str_repeat("\u{2003}",$repeat)."\u{2514}\u{2500} " : '';
                $option->text = $prefix.$option->text;
            }
        }
        
        return $options;
    }
    
    
 /**
     * Override parent function to force always use nested mode
     * {@inheritDoc}
     * @see \Joomla\CMS\Form\Field\TagField::isNested()
     */
    public function isNested()
    {
        if ($this->isNested === null)
        {
            if (isset($this->element['parent'])) {
                //force nested if we have a parent
                $this->isNested = true;
            } else {
                // If mode="nested" || ( mode not set & config = nested )
                if (isset($this->element['mode']) && (string) $this->element['mode'] === 'nested'
                    || !isset($this->element['mode']) && $this->comParams->get('tag_field_ajax_mode', 1) == 0)
                {
                    $this->isNested = true;
                }
            }
        }
        
        return $this->isNested;
    }
    
    public function allowCustom()
    {
        if ($this->element['custom'] && \in_array((string) $this->element['custom'], ['0', 'false', 'deny'])) {
            return false;
        }
        
        return $this->getCurrentUser()->authorise('core.create', 'com_tags');
    }
    
    /**
     * Override parent function to correct bug in not respecting local isNested
     * {@inheritDoc}
     * @see \Joomla\CMS\Form\Field\TagField::isRemoteSearch()
     */
    public function isRemoteSearch()
    {
        if ($this->element['remote-search']) {
            return !\in_array((string) $this->element['remote-search'], ['0', 'false', '']);
        }
        if ((isset($this->element['mode']) && (string) $this->element['mode'] === 'nested')) return false;
        
        return $this->comParams->get('tag_field_ajax_mode', 1) == 1;
    }
    
}
