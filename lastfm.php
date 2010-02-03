<?php
/*
Plugin Name: last.fm Playlists
Plugin URI: http://www.robmcghee.com/lastfm/
Description: Display the tracklist of a playlist from a last.fm account
Author: Rob McGhee
Version: 1.1.1
Author URI: http://www.llygoden.com/
*/


function lastfm($content) {
	
	$opentag = "[lastfmplaylist:"; // used to find the playlist name
	$closetag = "]"; // used to find the playlist name
	$splitter = ":"; // used to split the fields up
	$playlistid = ""; // used to store the playlist id
	$tracklist = ""; // used to display the tracklist
	$i = 1; // used to display track numbers

	$lastfm = explode($opentag, $content); // find the open tag in the content
	$lastfm = explode($closetag, $lastfm[1]); // find the close tag in the content
	$lastfm = explode($splitter, $lastfm[0]); // split the three variables up
	
	$user = $lastfm[0]; // get the username
	$api = $lastfm[1]; // get the api key
	$playlist = $lastfm[2]; // get the playlist name
	
	$replaced = $opentag . $user . $splitter . $api . $splitter . $playlist . $closetag; // the string that will be replaced
	
	if ($user == "" || $api == "" || $playlist == ""){ // check that info was added
		$tracklist = "You must enter a valid last.fm Username, API Key and Playlist Name"; // error message for not having details
	}else{
	
		if(!$feed = file_get_contents("http://ws.audioscrobbler.com/2.0/?method=user.getplaylists&user=" . $user . "&api_key=" . $api)){ // get playlists of the last.fm user
			$tracklist = "Unable to open the users playlist list at the moment"; // if we can't get the file display an error message
		}else{	
			$pxml = simplexml_load_string($feed); // load into simplexml
			
			foreach ($pxml->playlists[0]->playlist as $play){ //loop through each playlist
				if (strcasecmp($play->title, $playlist) == 0){ // compare the playlists to the playlist given
					$playlistid = $play->id; // set tracklist to the playlists ID
				}
			}
			
			if ($playlistid == ""){ // if the plugin wasn't able to find the playlist supplied
				$tracklist = "You must enter a valid last.fm Playlist Name created by the Username supplied"; // error message for unable to open tracklist
			}else{
				if (!$tracks = file_get_contents("http://ws.audioscrobbler.com/2.0/?method=playlist.fetch&playlistURL=lastfm://playlist/" . $playlistid  . "&api_key=" . $api)){ // get the tracklist from the playlist
					$tracklist = "Unable to open the playlists tracklist list at the moment"; // if we can't get the file display an error message
				}else{	
					$txml = simplexml_load_string($tracks); // load into simplexml
					
					$tracklist .= "<link rel='stylesheet' href='lastfm.css' type='text/css' /><table width='100%' id='thePlaylist' class='tracklist'><thead><tr><th class='reorderButtons'></th>
					<th></th><th></th><th>Track</th><th></th><th></th><th class='length'>Time</th><th></th></tr></thead><tbody>"; // table header
					
					foreach ( $txml->playlist[0]->trackList[0]->track as $track){
						$artist = substr($track->identifier,25);
						$arr = explode("/", $artist, 2);
						$artist = $arr[0];
						
						$tracklist .= "<tr><td></td><td class=\"position\">" . $i . "</td><td></td>"; // give the track it's line number
						$tracklist .= "<td class=\"track\"><a href=http://www.last.fm/music/". $artist .">" . $track->creator ."</a> - "; // display artist detail
						$tracklist .= "<a href=". $track->identifier .">" . $track->title ."</a></td><td></td><td></td><span></span> "; // display track title
						$tracklist .= "<td class=\"length\"><abbr class=\"duration\">" . date('i:s', $track->duration/1000)."</abbr></td>"; // display duration 
						$tracklist .= "<td></td></tr>"; // close row record
						
						$i++; // increase line number
					}
					
					$tracklist .= "</tbody></table><br />"; // table footer
				}
			}
		}
	}
	
	$content = str_replace($replaced, $tracklist,$content); // replace the string in the original text
	
	return $content; // return the text 
}

add_filter('the_content','lastfm');
?>