<?php
require_once("src/CiscoIPPhone.php");
require_once("src/URLGen.php");

header("Content-type: text/xml");
header("Connection: close");
header("Expires: -1");

$urlBase = new URLGenWeb();

$menuItems = array(
  ["MPD Graphic File", $urlBase->clearBasename()->append("apps/mpd/index.php")->setParam("view", "graphicFile")->setparam("menu", "controller")],
  ["MPD Graphic Menu", $urlBase->clearBasename()->append("apps/mpd/index.php")->setParam("view", "graphicMenu")->setparam("menu", "controller")],
);

$mainMenu = new CiscoIPPhoneMenu();
$mainMenu->setTitle("Main Menu");
$mainMenu->setPrompt("Please select a demo");

$mainMenu->menu->setMenuContents($menuItems);

echo $mainMenu;
?>