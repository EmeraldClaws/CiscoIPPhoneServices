<?php
abstract class MediaController {
  protected $title;
  protected $currentVolume;
  protected $timeCurrent, $timeRemaining;

  abstract public function updateCurrentData();

  public function getTitle() {
    return $this->title;
  }

  public function getVolume() {
    return $this->currentVolume;
  }

  public function getPosition() {
    //TODO: calculate position percentage from timeCurrent and timeTotal
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

  public function isPlaying() {
    return $this->isPlaying;
  }
  
  // abstract public function startNew($filePath);

  abstract public function pressPlayPause();

  abstract public function pressNext();

  abstract public function pressPrev();

  abstract public function pressVolumeUp();

  abstract public function pressVolumeDown();

  // abstract public function pressStop();
}