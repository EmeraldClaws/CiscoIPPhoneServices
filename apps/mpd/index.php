<?php
require_once('../../src/CiscoIPPhone.php');
require_once '../../src/URLGen.php';
require_once('MusicController.php');

header("Content-type: text/xml");
header("Connection: close");
header("Expires: -1");

$output;
$controller = new MPD();
$urlBase = new URLGenWeb();

if (!isset($_GET['menu']))
  $_GET['menu'] = "controller";

if (!isset($_GET['view']))
  $_GET['view'] = "default";

if ($_GET['menu'] == "controller") {
  
  //Process Actions
  if (isset($_GET['action'])) {
    switch ($_GET['action']) {
      case 'playpause':
        $controller->pressPlayPause();
        break;

      case 'volumeUp':
        $controller->pressVolumeUp();
        break;

      case 'volumeDown':
        $controller->pressVolumeDown();
        break;

      case 'next':
        $controller->pressNext();
        break;

      case 'prev':
        $controller->pressPrev();
        break;

      case 'setPlaylist':
        if (isset($_GET['playlist'])) {
          $controller->setPlaylist($_GET['playlist']);
          $urlBase->unsetParam("playlist");
        }
        break;

      case 'update':
        $controller->updateMPD();
        break;

      default:
        error_log("Unimplemented action");
        break;
    }
    $urlBase = $urlBase->unsetParam("action");
    $controller->updateCurrentData();
  }

  //Set to refresh when song finises
  header("Refresh: ".$controller->getTimeRemaining()."; url=".$urlBase);

  //Common menu used for all views
  $menuContents = array(
    array("Back", $urlBase->clearParams()->clearBasename()->upDir()->upDir()->append("index.php"), [257, 129, 280, 152]),
    array("Volume Up", $urlBase->setParam("action", "volumeUp"), [167, 99, 190, 122]),
    array("Volume Down", $urlBase->setParam("action", "volumeDown"), [167, 129, 190, 152]),
    array("Prev", $urlBase->setParam("action", "prev"), [197, 99, 220, 122]),
    array("Play/Pause", $urlBase->setParam("action", "playpause"), [227, 99, 250, 122]),
    array("Next", $urlBase->setParam("action", "next"), [257, 99, 280, 122]),
    array("Options", $urlBase->setParam("menu", "options"), [197, 129, 220, 152]),
    array("Refresh", $urlBase, [227, 129, 250, 152]),
  );
  
  $hasAlbumArt = false;
  $primaryArtist = explode("/", $controller->getArtist());
  $albumArtDir = __DIR__.'/albumArt/';
  if ($controller->hasAlbumArt($albumArtDir, $primaryArtist[0])) {
    $hasAlbumArt = true;
    $imageName = $primaryArtist[0]." - ".$controller->getAlbum().".png";
  }

  if ($_GET['view'] == "graphicFile") {
    $output = new CiscoIPPhoneGraphicFileMenu(__DIR__,'menu.png');
    $output->menu->setMenuContents($menuContents);    

    if ($hasAlbumArt) {
      $output->image->includeSubImage($albumArtDir.$imageName, 9, 9, 0, 0, 150, 150);
    }

    //Add text info
    $output->image->addTextArray(163, 11, 288, 99, array([5,$controller->getTitle()], [3, $controller->getArtist()], [2, $controller->getAlbum()]));

    //Volume display
    //168,100
    //189,151 
    $height = floor(($controller->getVolume()/100)*(150-99));
    $output->image->invertArea(168, 151-$height, 22, $height+1);

    // $output->image->invertArea(0, 0, 298, 168);

    //TODO: switch on get[action], only regen if image changing
    $output->image->saveImage('output.png');
    $output->setURL($urlBase->clearParams()->clearBasename()->append("output.png"));

  } else {
    if ($_GET['view'] == "graphicMenu") {
      $output = new CiscoIPPhoneGraphicMenu();
    } else {
      $output = new CiscoIPPhoneMenu();  
    }

    $output->setTitle("MPD for the Cisco 7940");
    $output->setPrompt($controller->getVolume()."/100 |".str_repeat("=", $controller->getVolume()/5).str_repeat("-", (100-$controller->getVolume())/5)."|");
    $output->menu->setMenuContents($menuContents);

    $output->image->setBrushShade(3);
    $output->image->drawBox(2, 2, 38, 38, 3, 2); //Box around album art

    $offset = $output->image->printString($controller->getTitle(), 42, 3);
    $output->image->setFont('ciscoNarrow');
    $offset += $output->image->printString($controller->getArtist(), 42, 3+$offset);
    $output->image->printString($controller->getAlbum(), 42, 3+$offset);
    $output->image->drawBox(42, 38, 90, 27, 0, 14);
    $output->image->drawLine(42, 39, 132, 39, 3, 1);

    //Progress Bar
    $output->image->drawLine(2, 55, 1+$controller->getPosition(), 55, 3, 1);
    $output->image->drawLine(2+$controller->getPosition(), 55, 132, 55, 2, 1);

    //Numbered control buttons
    $output->image->printGlyph("mpdVolumeUpDown", 41, 4);
    $output->image->printGlyph("mpdBack", 41, 43);
    $output->image->printGlyph("mpdNext", 41, 79);
    $output->image->printGlyph("mpdRefresh", 41, 115);
    $output->image->printGlyph("mpdOptions", 41, 97);
    if ($controller->isPlaying()) {
      $output->image->printGlyph("mpdPause", 41, 61);
    } else {
      $output->image->printGlyph("mpdPlay", 41, 61);
    }
    
    if ($hasAlbumArt) {
      $output->image->loadImage($albumArtDir.$imageName, 4, 4, 34, 34);
    } else {
      $output->image->drawBox(4, 4, 34, 34, 1, 17);
    }
  }
} elseif ($_GET['menu'] == "options") {
  if (isset($_GET['subMenu'])) {
    switch ($_GET['subMenu']) {
      case 'playlist':
        //Show playlists
        $menuContents = array(
          ["Back", $urlBase->unsetParam('subMenu')]
        );

        $playlistURL = $urlBase->unsetParam("subMenu")->setParam("menu","controller");
        foreach ($controller->getPlaylists() as $playlist) {
          array_push($menuContents,[$playlist, $playlistURL->setParam("action", "setPlaylist")->setParam("playlist", $playlist)]);
        }
        break;
      
      case 'playerSwitch':
        $playerSwitchURL = $urlBase->unsetParam("subMenu")->setParam("menu", "controller");

        $menuContents = array(
          array("Back", $urlBase->unsetParam("subMenu")),
          array("GraphicFileMenu", $playerSwitchURL->setParam("view", "graphicFile")),
          array("GraphicMenu", $playerSwitchURL->setParam("view", "graphicMenu")),
          array("Menu", $playerSwitchURL->setParam("view", "Menu"))
        );

        break;

      default:
        error_log("No subMenu set");
        break;
    }
    
  } else {
    $menuContents = array(
      array("Back", $urlBase->setParam("menu", "controller")),

      array("Playlists", $urlBase->setParam("subMenu", "playlist")),
      array("Switch Player", $urlBase->setParam("subMenu", "playerSwitch")),
      array("Update MPD", $urlBase->setParam("menu", "controller")->setParam("action", "update")),
    );
  }

  $output = new CiscoIPPhoneMenu();
  $output->setTitle("Options for MPD");
  $output->menu->setMenuItem("Back", $urlBase->setParam("menu", "controller"));
  $output->menu->setMenuContents($menuContents);




  // $library
  // echo isset($_GET['libraryIndex']);

}

echo $output;

?>
