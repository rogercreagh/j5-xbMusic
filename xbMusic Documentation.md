# xbMusic Documentation



[TOC]

*NB Functions and facilities that are not yet implemented are flagged with ~~strikeout~~ in the text*

### Intro

xbMusic started as a means to record details of a personal music collection of mp3 files in a more flexible and extendable way than most Music Player apps allow. It was designed to be used as a Joomla website component which could either be running on a localhost webserver for standalone use or on an online website to allow multiple devices and users to access the data remotely across a network.

The website, whether local or internet, needs to be running on the same machine as the music is stored - it is assumed that all the music is available as mp3 files.

The server needs to fulfil the requirements for a Joomla website (Apache or nginx with PHP8.3+ and MySql database). It has only been tested on a Ubuntu Linux server, although it should work with a Windows server.

On Linux the music files can be stored outside the webserver space by creating symlinks to the source (this can be done from within the App) - this may not work on Windows.

### Basic System

The basic system creates a database to hold details of Tracks (files), Artists, Albums, and Songs with links between them. Initially the artist, album and album data is taken from the ID3 tags within the MP3 files, if this is missing or incomplete it can be edited after the ID3 is read, ~~and optionally the changes written back to the ID3 tags in the file~~.

In summary the relationship between the items are as follows

- Track - the basic element equates to a single file.
  - Track may optionally belong to an album
  - Track may have one or more artists
  - Track may have one or more songs (eg a medley), or be a part of a larger work (still called a song in the system) with other tracks
- Album
  - An Album may have multiple tracks which usually have a specific order
  - If an album is spread across multiple physical discs this should be listed as a single album with the individual tracks having a disc number as well as a track number. 
  - If a physical disc contains two or more albums then these should be listed as separate albums
- Artists
  - An artist will appear on multiple tracks, songs and albums
- Songs 
  - A song is an individual work (may be instrumental or )

### Azuracast Extension



