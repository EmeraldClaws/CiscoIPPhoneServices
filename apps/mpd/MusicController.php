<?php
require_once '../MediaController.php';
// require_once 'config.php';

abstract class MusicController extends MediaController{
  protected $currentArtist, $currentAlbum;

  function __construct() {
    require 'config.php';
  }

  abstract public function getArtist();

  abstract public function getAlbum();

  abstract public function getPlaylists();

  abstract public function setPlaylist($playlistName);

  public function hasAlbumArt($albumArtDir, $primaryArtist) {
    require 'config.php';
    $filepath = $albumArtDir.$primaryArtist." - ".$this->getAlbum().".png";
    if (!file_exists($filepath)) {
      $albumJSON = file_get_contents('http://ws.audioscrobbler.com/2.0/?method=album.getinfo'
                                    .'&api_key='.$albumArtAPIKey
                                    .'&artist='.urlencode($primaryArtist)
                                    .'&album='.urlencode($this->getAlbum())
                                    .'&format=json');
      var_dump($albumJSON);
      $albumInfo = json_decode($albumJSON);

      if ($albumInfo != FALSE && (isset($albumInfo->message) && $albumInfo->message != "Album not found")) {
        if (strlen($albumInfo->album->image[0]->{"#text"}) < 1) {
          //No album art
          return false;
        } else {
          //Album art exists
          $imageURL = end($albumInfo->album->image)->{"#text"};
          file_put_contents($filepath, file_get_contents($imageURL));
          return true;
        }
      } else {
        //No JSON
        return false;
      }
    } else {
      //Album art already exists
      return true;
    }
  }
}

class MPD extends MusicController {

  function __construct() {
    parent::__construct();
    require 'config.php';
    $this->mpc = "mpc -h $mpdPassword@$mpdHost ";
    $this->updateCurrentData();
  }

  public function updateCurrentData() {
    $mpcStatus;
    exec($this->mpc.'-f "%title%\n%artist%\n%album%"', $mpcStatus);

    $this->currentTitle = $mpcStatus[0];
    $this->currentArtist = $mpcStatus[1];
    $this->currentAlbum = $mpcStatus[2];
    $this->currentVolume = substr(exec($this->mpc.'volume'), 7, -1);

    $time;
    preg_match('/(\d:\d\d)\/(\d:\d\d)/', $mpcStatus[3], $time);
    unset($time[0]);
    foreach ($time as $key => $timeCode) {
      $time[$key] = explode(":", $timeCode);
      $time[$key] = 60*$time[$key][0]+$time[$key][1];
    }

    $this->timeCurrent = $time[1];
    $this->timeTotal = $time[2];
    $this->timeRemaining = $this->timeTotal - $this->timeCurrent;

    $this->position = round(substr(explode(" ", $mpcStatus[3])[5], 1, -2)*(130/100));

    if (explode(" ",$mpcStatus[3])[0] == "[playing]") {
      $this->isPlaying = true;      
    } else {
      $this->isPlaying = false;
    }
  }

  public function updateMPD(){
    exec("$mpc update");
  }

  public function getArtist() {
    return $this->currentArtist;
  }

  public function getAlbum() {
    return $this->currentAlbum;
  }

  public function getTitle() {
    return $this->currentTitle;
  }

  public function getVolume() {
    return $this->currentVolume;
  }

  public function getPosition() {
    return $this->position;
  }

  public function pressPlayPause() {
    exec($this->mpc.'toggle');
  }

  public function pressNext() {
    exec($this->mpc.'next');
  }

  public function pressPrev() {
    exec($this->mpc.'prev');
  }

  public function pressVolumeUp() {
    exec($this->mpc.'volume +5');
  }

  public function pressVolumeDown() {
    exec($this->mpc.'volume -5');
  }

  public function getPlaylists() {
    exec($this->mpc.'lsplaylists', $availablePlaylists);
    return $availablePlaylists;
  }

  public function isPlaying() {
    return $this->isPlaying;
  }

  public function getTimeCurrent() {
    return $this->timeCurrent; 
  }

  public function getTimeTotal() {
    return $this->timeTotal;
  }

  public function getTimeRemaining() {
    return $this->timeRemaining;
  }

  public function setPlaylist($playlistName) {
    //Clear old playlist, load new, start playing
    //Assuming random is on, 'next' stops the first track from playing first
    exec($this->mpc.'-w clear');
    exec($this->mpc.'-w load '.escapeshellarg($playlistName));
    exec($this->mpc.'-w play');
    exec($this->mpc.'-w next');
  }
}

?>
