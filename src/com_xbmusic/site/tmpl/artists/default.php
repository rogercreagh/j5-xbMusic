<?php 
 /*******
 * @package xbMusic
 * @filesource site/tmpl/Artists/default.php
 * @version 0.0.63.0 20th April 2026
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2026
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Crosborne\Component\Xbmusic\Site\Helper\RouteHelper as XbmusicHelperRoute;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbcommonHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\Xbtext;

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('bootstrap.popover', '.hasPopover', ['trigger'=>'hover']);

$wa = $this->document->getWebAssetManager();
$wa->useScript('joomla.dialog')
    ->useScript('multiselect')
    ->useScript('xbmusic.xbgeneral');


$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));

$arttypes = array("","XB_PERSON","XB_GROUP");
$iconarr = array('facebook'=>'<i class="fab fa-facebook"></i>',
                'twitter'=>'<i class="fab fa-twitter"></i>',
                'instagram'=>'<i class="fab fa-instagram"></i>',
                'bandcamp'=>'<i class="fab fa-bandcamp"></i>',
                'spotify'=>'<i class="fab fa-spotify"></i>',
                'youtube'=>'<i class="fab fa-youtube-square"></i>',
                'website'=>'<i class="fas fa-globe"></i>'
            );

$root = Uri::root();
$document = Factory::getApplication()->getDocument();
$document->addScriptOptions('com_xbmusic.uri', array("root" => $root));

?>
<script type="module" src="<?php echo Uri::root(); ?>/media/com_xbmusic/js/xbdialog.js"></script>

<div id="xbcomponent" >

<h3><i class='fas fa-users-line' ></i> <?php echo Text::_('XBMUSIC_ARTIST_ROSTER'); ?></h3>

<?php if ($this->showplay) : ?>
    <div class="pull-right">
    	<?php if ($this->playtime>0) : ?>
    		<p class="xbnote xbr09 xbmb5 xbtr"><?php echo Text::sprintf('play restricted to %s seconds', $this->playtime); ?></p>
    	<?php endif; ?>
    	<audio id="player" controls style="height:30px;border-radius:12px;"
    		src="<?php echo Uri::root(true).'/xbmusic/'; ?>">
    			<i>Your browser does not support the audio tag.</i>
    	</audio>        		
        <div id="playertitle" class="pull-left" style="margin:5px 20px 0 0;"></div>
    </div>
    <div class="clearfix"></div>      
<?php endif; ?>

<form action="<?php echo Route::_('index.php?option=com_xbmusic&view=artists'); ?>" method="post" name="adminForm" id="adminForm">

  	<?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
	<div class="pull-right pagination xbm0">
		<?php  echo $this->pagination->getPagesLinks(); ?>
	</div>        
	<div class="clearfix"></div>      
	<div class="pull-right pagination xbm0 xbpr20 xbpt5">
		<?php  echo $this->pagination->getResultsCounter(); ?>
	</div>
	<div class="clearfix"></div>      
              
	<?php if (empty($this->items)) : ?>
		<div class="alert alert-no-items">
			<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
		</div>
	<?php else : ?>
		<div class="pull-left">
    		<p class="xbmb5">              
                <?php echo Xbtext::_('XB_SORTED_BY',XBSP2 + XBTRL).$listOrder.' '.$listDirn ; ?>
    		</p>
		</div>
		<div class="pull-left" style="width:60%">
      		<p class="xbtr xbnote xbmb5"><?php echo Text::_('XB_AUTOCLOSE_DROPS'); ?><input  type="checkbox" id="autoclose" name="autoclose" value="yes" checked="true" style="margin:0 5px;" />
      		</p>
      	</div>
    	<div class="clearfix"></div>      

<div class="table-responsive">
  <table class="table table-striped">
  <thead>
    <tr>
 		<th scope="col">
			<?php echo HTMLHelper::_('searchtools.sort', 'XB_FULL_NAME', 'a.name', $listDirn, $listOrder); ?>
			<i><?php echo HTMLHelper::_('searchtools.sort', 'XBMUSIC_SORTNAME', 'a.sortname', $listDirn, $listOrder); ?></i>
		</th>
		<th> </th>
		<th class="hidden-phone"><?php echo Text::_('XBMUSIC_SONGS'); ?></th>
		<th><?php echo Text::_('XBMUSIC_ALBUMS'); ?></th>
		<?php if ($this->showcat || $this->showtags) : ?>
			<th class="hidden-tab-phone">
				<?php if ($this->showcat) echo Text::_('XB_CATEGORY');
    			      if ($this->showcat && $this->showtags) echo  ' &amp; ';
    			      if ($this->showtags) echo Text::_('XB_TAGS'); 
    			?>		
			</th>
		<?php endif; ?>
	</tr>
	</thead>
	<tbody>
	<?php foreach ($this->items as $id => $item) :
	?>
	<tr>
		<td>
			<span class="xbbold"><?php echo $item->name; ?></span>
			<?php if (($item->description != '') || ($item->imgurl != '')) : ?>
    			<?php $pvtit = Text::_('XBMUSIC_ARTIST_DETAILS'); ?>
                <span class="xbpveye" onclick="pvItem('<?php echo $pvtit; ?>','artist','<?php echo $item->id; ?>');"  >
                  <span class="icon-eye xbpl10"></span></span>
            <?php endif; ?>
			<br />
			<span class="xbnit xbpl20 xbr085">
		    	<?php if ($item->sortname != $item->name) echo $item->sortname; ?> 
    			<?php if(!empty($item->type)) : ?>
		    		(<?php echo Text::_($arttypes[$item->type]); ?>)
    			<?php endif; ?>		
			</span><br />
		</td>
		
		<td>
    		<?php if (($this->showlinks) && !empty($item->ext_links)) : ?>
    			<?php foreach ($item->ext_links as $linkobj) : ?>
    			    <span class="xbr12">
    			    	<a href="<?php echo $linkobj->link_url; ?>" target="_blank" 
        			    class="hasPopover" title="" 
    						data-bs-content="<?php echo ($linkobj->link_desc!='') ? $linkobj->link_desc : $linkobj->link_text; ?>">
    					<?php $key = trim(strtolower($linkobj->link_text));
        			    if (key_exists($key, $iconarr)) { 
        			        echo '<span class="xbicomag">'.$iconarr[$key].'</span>';
        			    } else {
        			        echo '<span class="xbr10">'.$linkobj->link_text.'</span>';
        			    } ?>
        			    </a>
					</span>
    			<?php endforeach; ?>
    		<?php endif; ?>
		</td>
		<td>
    		<?php if (count($item->songs) > 0) : ?>
    			<details>
    				<summary><?php echo Text::sprintf('XBMUSIC_N_SONGS',count($item->songs)); ?></summary>
    				<div class="xbyscroll" style="max-height:180px;">
    				<ul class="xbnobullets xbm5"><li>
    				<?php $prevalbum = 0; ?>
    				<?php foreach ($item->songs as $song) : ?>
    					<li>
    					<?php if ( ($prevalbum == $song['albumid'])) : ?>
    						<span class="xbpr20">&nbsp;</span>
	    				<?php endif ;?>
	    				<?php if ($this->showplay) : ?>
                            <span class="icon-play xbpl10 xbgreen xbpl10" 
                            	onclick='playAudio("<?php echo rawurlencode($item->sortname.' : '.$song["songtitle"]); ?>", 
                            	"<?php echo rawurlencode(str_replace(JPATH_ROOT,'',$song["filepathname"])); ?>",
                            	"<?php echo $this->playtime; ?>");'>
                            </span>
                        <?php endif; ?>
		                <b><?php echo $song['songtitle']; ?></b>
		                <?php if ($song['albumid'] > 0) : ?>
                            <?php if ( ($prevalbum != $song['albumid'])) : ?>
                            	(<?php echo $song['albumtitle'].' ['.$song['albumyear'].']'; ?>)
                            <?php endif; ?>
        		            <?php $prevalbum = $song['albumid']; ?> 
        		        <?php else : $prevalbum = -1; ?>   
		                <?php endif; ?>	
		                </li>	                    		                
    				<?php endforeach; ?>
    					</li>
    				</ul>
    				</div>
    			</details>
    		<?php else : ?>
    			<p class="xbnit"><?php echo Text::_('XBMUSIC_NO_SONGS_LISTED'); ?></p>
    		<?php endif; ?>    		
    	</td>
    	
		<td class="hidden-phone">
			<?php if (count($item->albums) > 1) : ?>
				<details>
					<summary><?php echo Text::sprintf('XBMUSIC_N_ALBUMS',count($item->albums)); ?></summary>
					<div class="xbyscroll" style="max-height:180px;">
					<p class="xbnote"><?php echo Text::_('XBMUSIC_ALBUMS_NOTALL_TRACKS'); ?></p>
					<ul class="xbnobullets xbm5">
					<?php foreach ($item->albums as $album) : ?>
						<li>							
							<?php if(($this->showimg) && ($album['imgurl']!='')) :?>
								<?php  $src = trim($album['imgurl']);
								if ((!$src=='') && (XbcommonHelper::check_url($src))) :
								    $tip = '<img src=\''.$src.'\' style=\'width:500px;\' />';
        						      ?>
									<img src="<?php echo $src; ?>" alt="<?php echo $album['albumtitle']; ?>" 
										style="height:50px;margin: 2px 5px 8px 0;"
										class="hasPopover" title="" 
										data-bs-content="<?php echo $tip; ?>" />
								<?php endif; ?>
							<?php endif; ?>
							
			                <?php echo $album['albumtitle']; ?> 
			                <?php if($album['rel_date']) echo ' ('.$album['rel_date'].') '; ?>
                			<?php $pvtit = "'".Text::_('XBMUSIC_ALBUM_DETAILS')."'"; ?>
                            <span class="xbpveye" 
                            	onclick="pvItem(<?php echo $pvtit; ?>,'album','<?php echo $album['albumid']; ?>');">
                            	<span class="icon-eye xbpl10"></span>
                            </span>
                            
                            
                            
			            </li>
					<?php endforeach; ?>
					</ul>
					</div>
				</details>
			<?php elseif (count($item->albums)==1) : ?>
				<details>
					<summary>1 <?php echo Text::_('XBMUSIC_ALBUM'); ?></summary>
				<?php $album = $item->albums[0]; ?>
				<p style="margin-left:40px;">
				<?php if(($this->showimg) && ($album['imgurl']!='')) :?>
					<?php  $src = trim($album['imgurl']);
					  if ((!$src=='') && (file_exists(JPATH_ROOT.'/'.str_replace(Uri::root(),'',$src)))) :
						$tip = '<img src=\''.$src.'\' style=\'width:400px;\' />';
					?>
					<img src="<?php echo $src; ?>" alt="<?php echo $album['albumtitle']; ?>" 
						style="height:50px;margin: 2px 5px 8px 0;"
						class="hasPopover" title="" 
						data-bs-content="<?php echo $tip; ?>" />
					<?php endif; ?>
				<?php endif; ?>

	                <?php echo $album['albumtitle']; ?> 
	                <?php if($album['rel_date']) echo ' ('.$album['rel_date'].') '; ?>
                			<?php $pvtit = "'".Text::_('XBMUSIC_ALBUM_DETAILS')."'"; ?>
                            <span class="xbpveye" 
                            	onclick="pvItem(<?php echo $pvtit; ?>,'album','<?php echo $album['albumid']; ?>');">
                            	<span class="icon-eye xbpl10"></span>
                            </span>
	            </p>
	        	</details>
			<?php else : ?>
				<p class="xbnit"><?php echo Text::_('XBMUSIC_NO_ALBUMS_LISTED'); ?></p>
			<?php endif; ?>
			<?php if (count($item->singles) > 0) : ?>
				<p><i><?php echo count($item->singles).Xbtext::_('XBMUSIC_ADDITIONAL_TRACKS',XBSP1 + XBTRL); ?></i></p>
			<?php endif; ?>		
		</td>
		
		<?php if (($this->showcat) && ($this->showtags)) : ?>
    		<td class="hidden-tab-phone">
    			<?php if (($this->showcat) && ($item->catid > 0)) : ?>
    				<p>
    					<span class="xblabel label-cat" >
    						<?php echo $item->category_title; ?>
    					</span>							
    				</p>						
    			<?php endif; ?>
    			<?php if ($this->showtags) : ?>
        			<ul class="inline">
        			<?php foreach ($item->tags as $t) : ?>
        				<li><span class="xblabel label-tag">
        					<?php echo $t->title; ?></span>
        				</li>												
        			<?php endforeach; ?>
    			</ul>						   
    			<?php endif; ?> 											    			
		</td>
		<?php endif; ?>
		
	</tr>
	<?php endforeach; ?>
	</tbody>
  </table>
</div>

		<?php echo $this->pagination->getListFooter(); ?>
	<?php endif; ?>
	<input type="hidden" name="task" value="">
	<input type="hidden" name="boxchecked" value="0">
	<?php echo HTMLHelper::_('form.token'); ?>

</form>
    <script language="JavaScript" type="text/javascript"
      src="<?php echo Uri::root(); ?>media/com_xbmusic/js/closedetails.js" >
    </script>

</div>
