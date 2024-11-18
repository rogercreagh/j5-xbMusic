
DROP TABLE IF EXISTS
  `#__xbmusic_albums`,
  `#__xbmusic_artists`,
  `#__xbmusic_playlists`,
  `#__xbmusic_songs`,
  `#__xbmusic_tracks`,
  `#__xbmusic_artisttrack`,
  `#__xbmusic_artistsong`,
  `#__xbmusic_medleytrack`,
  `#__xbmusic_playlisttrack`,
  `#__xbmusic_songtrack`,
  `#__xbmusic_groupmember`,
  `#__xbmusic_artistalbum`,
  `#__xbmusic_songalbum`
;

DELETE FROM `#__ucm_base` WHERE ucm_type_id in 
	(select type_id from `#__content_types` WHERE type_alias in ('com_xbmusic.album','com_xbmusic.artist','com_xbmusic.playlist','com_xbmusic.song','com_xbmusic.track'));
DELETE FROM `#__ucm_content` WHERE core_type_alias in ('com_xbmusic.album','com_xbmusic.artist','com_xbmusic.playlist','com_xbmusic.song','com_xbmusic.track');
DELETE FROM `#__contentitem_tag_map`WHERE type_alias in ('com_xbmusic.album','com_xbmusic.artist','com_xbmusic.playlist','com_xbmusic.song','com_xbmusic.track');
DELETE FROM `#__content_types` WHERE type_alias in ('com_xbmusic.album','com_xbmusic.artist','com_xbmusic.playlist','com_xbmusic.song','com_xbmusic.track');
