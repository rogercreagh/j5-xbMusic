<?php 
 /*******
 * @package xbMusic
 * @filesource site/tmpl/Artists/default.php
 * @version 0.0.61.0 31st March 2026
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2026
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Crosborne\Component\Xbmusic\Site\Helper\RouteHelper as XbmusicHelperRoute;
//use Crosborne\Component\Xbmusic\Administrator\Helper\XbcommonHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\Xbtext;

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('bootstrap.popover', '.hasPopover', ['trigger'=>'hover']);

$wa = $this->document->getWebAssetManager();
$wa->useScript('table.columns')
->useScript('multiselect')
->useScript('xbmusic.xbgeneral');


$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));

$arttypes = array("unknown","XB_PERSON","XBMUSIC_DUO","XBMUSIC_TRIO","XB_GROUP");
$iconarr = array('facebook'=>'<i class="fab fa-facebook xbinfo"></i>',
                'twitter'=>'<i class="fab fa-twitter xbblue"></i>',
                'instagram'=>'<i class="fab fa-instagram xbgold"></i>',
                'bandcamp'=>'<i class="fab fa-bandcamp xbblue"></i>',
                'spotify'=>'<i class="fab fa-spotify xbgreen"></i>',
                'youtube'=>'<i class="fab fa-youtube-square"></i>',
                'website'=>'<i class="fas fa-globe xbblue"></i>'
            )

?>
<div id="xbcomponent" >

<h3><i class='fas fa-users-line' ></i> <?php echo Text::_('XBMUSIC_ARTIST_ROSTER'); ?></h3>

<form action="<?php echo Route::_('index.php?option=com_xbmusic&view=artists'); ?>" method="post" name="adminForm" id="adminForm">

<?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
        <div class="clearfix"></div>      
        <div class="pull-right pagination xbm0">
    		<?php  echo $this->pagination->getPagesLinks(); ?>
    	</div>
   		<div class="pull-right pagination" style="margin:25px 10px 0 0;">
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
			<?php echo HTMLHelper::_('searchtools.sort', 'XB_NAME', 'a.name', $listDirn, $listOrder); ?>
		</th>
		<th> </th>
		<th><?php echo Text::_('XBMUSIC_ALBUMS'); ?></th>
		<th class="hidden-phone"><?php echo Text::_('XBMUSIC_TRACKS'); ?></th>
		<th class="hidden-tab-phone"><?php echo Text::_('XB_CATEGORY').' &amp; '.Text::_('XB_TAGS'); ?></th>
	</tr>
	</thead>
	<tbody>
	<?php foreach ($this->items as $id => $item) :
	?>
	<tr>
		<td>
			<span class="xbbold"><?php echo $item->name; ?></span>
			<?php $pvuri = "'".(Uri::root().'index.php?option=com_xbmusc&view=artist&tmpl=component&id='.$item->id)."'"; ?>
			<?php $pvtit = "'".$item->name."'"; ?>
            <span  data-bs-toggle="modal" data-bs-target="#pvModal" data-bs-source="<?php echo $pvuri; ?>" 
            	data-bs-itemtitle="<?php echo $item->name; ?>" title="<?php echo Text::_('XB_MODAL_PREVIEW'); ?>" 
				onclick="var pv=document.getElementById('pvModal');pv.querySelector('.modal-body .iframe').setAttribute('src',<?php echo $pvuri; ?>);pv.querySelector('.modal-title').textContent=<?php echo $pvtit; ?>;"
            	><span class="icon-eye xbpl10"></span></span>
			<br />
			<?php if(!empty($item->type)) : ?>
				<span class="xbnit xbpl20 xbr085">
			    	(<?php echo Text::_($arttypes[$item->type]); ?>)
				</span><br />
			<?php endif; ?>
		
			<?php if($item->trkcnt > 0): ?>
				<span class="xbr09 xbnit"><?php  
				if ($item->trkcnt == 1) {
				    echo '1'.Xbtext::_('XBMUSIC_TRACK',XBSP1 + XBTRL);
				} else {
				    echo $item->trkcnt.Xbtext::_('XBMUSIC_TRACKS',XBSP1 + XBTRL); 
				} 
				?>
				</span>
			<?php endif; ?> 
			<?php if(count($item->albums) > 0) : ?>    								    
				<span class="xbr09 xbnit"><?php echo Xbtext::_('XB_ON', XBSP2 + XBTRL); ?>
					<?php if (count($item->albums) == 1) {
					    echo '1'.Xbtext::_('XBMUSIC_ALBUM',XBSP1 + XBTRL);
					} else {
					   echo count($item->albums).Xbtext::_('XBMUSIC_ALBUMS',XBSP1 + XBTRL);
					} ?>
				</span><br />
			<?php endif; ?>
			<?php if (count($item->singles) > 0) : ?>
				<span class="xbr09 xbnit"> <?php echo count($item->singles) . Xbtext::_('XBMUSIC_SINGLES',XBSP1 + XBTRL);?>
				</span><br />
			<?php endif; ?>
		</td>
		<td>
    		<?php if (!empty($item->ext_links)) : ?>
    			<?php foreach ($item->ext_links as $linkobj) : ?>
    			    <span class="xbr12"><a href="<?php echo $linkobj->link_url; ?>" target="_blank" 
    			    class="hasPopover" title="" 
						data-bs-content="<?php echo $linkobj->link_desc; ?>">
    			    <?php $key = trim(strtolower($linkobj->link_text));
    			    if (key_exists($key, $iconarr)) { 
    			        echo $iconarr[$key];
    			    } else {
    			        echo '<span class="xbr10">'.$linkobj->link_text.'</span>';
    			    } ?>
    			    </a> </span>
    			<?php endforeach; ?>
    		<?php endif; ?>
		</td>
		<td>
			<?php if (count($item->albums) > 1) : ?>
				<details>
					<summary><?php echo Text::sprintf('XBMUSIC_N_ALBUMS',count($item->albums)); ?></summary>
					<div class="xbyscroll" style="max-height:180px;">
					<ul style="margin:5px;list-style: none;">
					<?php foreach ($item->albums as $album) : ?>
						<li>							
							<?php if($album['imgurl']!='') :?>
								<?php  $src = trim($album['imgurl']);
        						  if ((!$src=='') && (file_exists(JPATH_ROOT.'/'.str_replace(Uri::root(),'',$src)))) :
       								$tip = '<img src=\''.$src.'\' style=\'width:500px;\' />';
        						?>
								<img src="<?php echo $src; ?>" alt="<?php echo $album['albumtitle']; ?>" 
									style="height:50px;margin: 2px 5px 8px 0;"
									class="hasPopover" title="" 
									data-bs-content="<?php echo $tip; ?>" />
								<?php endif; ?>
							<?php endif; ?>
							<a href="index.php?option=com_xbmusic&task=album.edit&retview=artists&id=<?php echo $album['albumid']; ?>">
			                <?php echo $album['albumtitle']; ?></a> 
			                <?php if($album['rel_date']) echo ' ('.$album['rel_date'].') '; ?>
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
				<?php if($album['imgurl']!='') :?>
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
				<a href="index.php?option=com_xbmusic&task=album.edit&retview=artists&id=<?php echo $album['albumid']; ?>">
	                <?php echo $album['albumtitle']; ?></a> 
	                <?php if($album['rel_date']) echo ' ('.$album['rel_date'].') '; ?>
	            </p>
	        	</details>
			<?php else : ?>
				<p class="xbnit"><?php echo Text::_('XBMUSIC_NO_ALBUMS_LISTED'); ?>
			<?php endif; ?>
			<?php if (count($item->singles) > 1) : ?>
				<details>
					<summary><?php echo Text::sprintf('XBMUSIC_N_SINGLES',count($item->singles)); ?></summary>
					<div class="xbyscroll" style="max-height:180px;">
					<ul style="margin:5px;">
					<?php foreach ($item->singles as $single) : ?>
						<li>
							<?php if($single['imgurl']!='') :?>
					<?php  $src = trim($single['imgurl']);
					  if ((!$src=='') && (file_exists(JPATH_ROOT.'/'.str_replace(Uri::root(),'',$src)))) :
						$tip = '<img src=\''.$src.'\' style=\'width:400px;\' />';
					?>
					<img src="<?php echo $src; ?>" alt="<?php echo $album['albumtitle']; ?>" 
						style="height:50px;margin: 2px 5px 8px 0;"
						class="hasPopover" title="" 
						data-bs-content="<?php echo $tip; ?>" />
					<?php endif; ?>
				<?php endif; ?>
							<a href="index.php?option=com_xbmusic&task=track.edit&retview=artists&id=<?php echo $single['trackid']; ?>">
			                <?php echo $single['albumtitle']; ?></a> 
			                <?php if($single['rel_date']) echo ' ('.$single['rel_date'].') '; ?>
			            </li>
					<?php endforeach; ?>
					</ul>
					</div>
				</details>
			<?php elseif (count($item->singles)==1) : ?>
				<?php $single = $item->singles[0]; ?>
				<p><span class="xbit"><?php echo Text::_('XBMUSIC_SINGLE'); ?></span><br />
				<?php if($single['imgurl']!='') :?>
					<?php  $src = trim($single['imgurl']);
					  if ((!$src=='') && (file_exists(JPATH_ROOT.'/'.str_replace(Uri::root(),'',$src)))) :
						$tip = '<img src=\''.$src.'\' style=\'width:400px;\' />';
					?>
					<img src="<?php echo $src; ?>" alt="<?php echo $single['tracktitle']; ?>" 
						style="height:50px;margin: 2px 5px 8px 0;"
						class="hasPopover" title="" 
						data-bs-content="<?php echo $tip; ?>" />
					<?php endif; ?>
				<?php endif; ?>
				<a href="index.php?option=com_xbmusic&task=track.edit&retview=artists&id=<?php echo $single['trackid']; ?>">
	                <?php echo $single['tracktitle']; ?></a> 
	                <?php if($single['rel_date']) echo ' ('.$single['rel_date'].') '; ?>
	            </p>
			<?php endif; ?>
		
		</td>
		<td class="hidden-phone">
    		<?php if (count($item->songs) > 1) : ?>
    			<details>
    				<summary><?php echo Text::sprintf('XBMUSIC_N_SONGS',count($item->songs)); ?></summary>
    				<div class="xbyscroll" style="max-height:180px;">
    				<ul style="margin:5px;">
    				<?php foreach ($item->songs as $song) : ?>
    					<li>
    						<a href="index.php?option=com_xbmusic&task=song.edit&retview=artists&id=<?php echo $song['songid']; ?>">
    		                <?php echo $song['songtitle']; ?></a> 
    		                <?php if($song['composer']) echo ' ('.$song['composer'].') '; ?>
    		            </li>
    				<?php endforeach; ?>
    				</ul>
    				</div>
    			</details>
    		<?php elseif (count($item->songs)==1) : ?>
    			<?php $song = $item->songs[0]; ?>
    			<p><span class="xbit"><?php echo Text::_('XBMUSIC_SONGS'); ?></span><br />
    				<a href="index.php?option=com_xbmusic&task=song.edit&retview=artists&id=<?php echo $song['songid']; ?>">
                    <?php echo $song['songtitle']; ?></a> 
                    <?php if($song['composer']) echo ' ('.$song['composer'].') '; ?>
                </p>
    		<?php else : ?>
    			<p class="xbnit"><?php echo Text::_('XBMUSIC_NO_SONGS_LISTED'); ?>
    		<?php endif; ?>
		<td class="hidden-tab-phone">
			<?php if ($item->catid > 0) : ?>
				<p>
					<span class="xblabel label-cat" >
						<?php echo $item->category_title; ?>
					</span>							
				</p>						
			<?php endif; ?>
			<ul class="inline">
			<?php foreach ($item->tags as $t) : ?>
				<li><span class="xblabel label-tag">
					<?php echo $t->title; ?></span>
				</li>												
			<?php endforeach; ?>
			</ul>						    											
			
		</td>
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
