<?php
require_once 'URLGen.php';

trait CiscoIPPhoneOutput {
  public function escapeSpecialChars($string) {
    $string = str_replace("&", "&amp;", $string);
    $string = str_replace("\"", "&quot;", $string);
    $string = str_replace("'", "&apos;", $string);
    $string = str_replace("<", "&lt;", $string);
    $string = str_replace(">", "&gt;", $string);
    $string = str_replace("!", "", $string);

    return $string;
  }

  public function escapeSpecialCharsURL($string) {
    $string = str_replace(" ", "%20", $string);
    return $this->escapeSpecialChars($string);
  }
}

abstract class CiscoIPPhone {
  use CiscoIPPhoneOutput;

  private $title;
  private $prompt;

  public function setTitle($newTitle) {
    $this->title = $newTitle;
  }

  public function setPrompt($newPrompt) {
     $this->prompt = $newPrompt;
  }

  abstract function buildOutput();

  public function __toString() {
    return "<".get_class($this).">\n"
          ."<Title>".$this->escapeSpecialChars($this->title)."</Title>\n"
          ."<Prompt>".$this->escapeSpecialChars($this->prompt)."</Prompt>\n"
          .$this->buildOutput()."</".get_class($this).">";
  }
}

class Menu {
  use CiscoIPPhoneOutput;

  private $menuItems, $menuType;

  function __construct() {
    $this->menuItems = array();
    $this->menuType = "Menu";
  }

  public function setMenuContents($menuArray) {
    if ($this->menuType == "Menu") {
      foreach ($menuArray as $menuItem) {
        $this->menuItems[$menuItem[0]] = ['URL'=>$menuItem[1]];
      }
    } elseif ($this->menuType == "GraphicFileMenu") {
      foreach ($menuArray as $menuItem) {
        $this->menuItems[$menuItem[0]] = ['URL'=>$menuItem[1], 'TouchArea'=>$menuItem[2]];
      }      
    }
  }

  public function setMenuType($menuType) {
    $this->menuType = $menuType;
  }

  public function setMenuItem($title, $url) {
    $this->menuItems[$title]=['URL'=>$url];
  }
  
  public function setFileMenuItem($title, $url, $touchArea) {
    $this->menuItems[$title] = ['URL'=>$url, 'TouchArea'=>$touchArea];
  }

  public function delMenuItem($title) {
    unset($this->menuItems[$title]);
  }

  public function getMenuItems() {
    return $this->menuItems;
  }

  public function getMenuItem($item) {
    return $this->menuItems[$item];
  }

  public function paginate($pageSize, $page, $urlGen) {
    $urlBase = $urlGen;
    if (isset($this->menuItems["Back"])) {
      $oldBackMenuItem = $this->menuItems["Back"]["URL"];
      unset($this->menuItems["Back"]);
    } else {
      $oldBackMenuItem = $urlBase;
    }

    $this->menuItems = array_slice($this->menuItems, $pageSize*$page, $pageSize);

    if (sizeof($this->menuItems) < $pageSize) {
      for ($i=sizeof($this->menuItems); $i < $pageSize; $i++) { 
        $this->setMenuItem(str_repeat(" ", $i), "");
      }
    }

    $this->setMenuItem("Prev", $urlBase->decParam("page"));
    $this->setMenuItem("Back", $oldBackMenuItem);
    $this->setMenuItem("Next", $urlBase->incParam("page"));
  }

  public function __toString() {
    $returnString = "";
    foreach($this->menuItems as $name => $array) {
      $returnString .= "<MenuItem>\n"
                      ."  <Name>".$this->escapeSpecialChars($name)."</Name>\n"
                      ."  <URL>".$this->escapeSpecialCharsURL($array['URL'])."</URL>\n";
      if ($this->menuType == "GraphicFileMenu") {
        $returnString .= '  <TouchArea X1="'.$array['TouchArea'][0].'" Y1="'.$array['TouchArea'][1].'"'
                                    .' X2="'.$array['TouchArea'][2].'" Y2="'.$array['TouchArea'][3]."\"/>\n";
      }
      $returnString .= "</MenuItem>\n";
    }
    return $returnString;
  }
}

class Image {
  use CiscoIPPhoneOutput;

  protected $locationX, $locationY;
  private $width, $height;
  private $depth;
  private $imageArray;

  function __construct(){
    include_once(dirname(__FILE__).'/../res/glyphs.php');
    $this->availableFonts = $fonts;
    $this->glyphs = $glyphs;
    $this->font = array_merge($this->glyphs, $this->availableFonts['cisco']);

    $this->data = "";
    $this->setLocationX(0);
    $this->setLocationY(0);
    $this->setWidth(isset($args['width']) ? $args['width'] : 132);

    $this->setHeight(isset($args['height']) ? $args['height'] : 56);
    $this->setDepth(2);

    $this->imageArray = array();
    $this->setBackground(0);
    $this->setBrushShade(3);    
  }

  public function setLocationX($newLocationX) {
     $this->locationX = $newLocationX;
  }

  public function getLocationX() {
     return $this->locationX;
  }

  public function setLocationY($newLocationY) {
     $this->locationY = $newLocationY;
  }

  public function getLocationY() {
     return $this->locationY;
  }

  public function setWidth($newWidth) {
     $this->width = $newWidth;
  }

  public function setHeight($newHeight) {
     $this->height = $newHeight;
  }

  public function setDepth($newDepth) {
     $this->depth = $newDepth;
  }

  public function setFont($newFont) {
    unset($this->font);
    $this->font = array_merge($this->glyphs, $this->availableFonts[$newFont]);
  }

  public function loadImage($location, $y, $x, $dx, $dy) {
    $fullSizeImage = imagecreatefrompng($location);
    $image = imagecreate($dx, $dy);
    imagecopyresized($image, $fullSizeImage, 0, 0, 0, 0, $dx, $dy, imagesx($fullSizeImage), imagesy($fullSizeImage));

    for ($imageX=0; $imageX < $dx; $imageX++) {
      for ($imageY=0; $imageY < $dy; $imageY++) {
        $rgb = imagecolorat($image, $imageX, $imageY);
        $colours = imagecolorsforindex($image, $rgb);


        unset($colours['alpha']);

        $avg = (max($colours)+ min($colours))/2;
        $avg = $avg / 64;
        $avg = round($avg);
        if ($avg > 3) {
          $avg = 3;
        }


        //Unused b/w algorithms 
        // $sum = 0.21*$colours['red'] + 0.72*$colours['green'] + 0.07*$colours['blue'];
        // $avg = $sum / 64;
        // $avg = round($avg);
        // if ($avg > 3) {
        //   $avg = 3;
        // }


        // $sum = $colours['red'] + $colours['green'] + $colours['blue'];
        // $avg = $sum / 228;
        // $avg = round($avg);
        // if ($avg > 3) {
        //   $avg = 3;
        // }

        $this->imageArray[$y + $imageY][$x + $imageX] = 3 - $avg;
      }
    }
  }


  public function setBackground($newShade) {
    for ($y=0; $y < $this->height; $y++) {
      for ($x=0; $x < $this->width; $x++) {
        $this->imageArray[$y][$x]=$newShade;
      }
    }
    $this->backgroundShade = $newShade;
  }

  public function setBrushShade($newShade) {
    $this->brushShade = $newShade;
  }

  public function printGlyph($glyph, $y, $x) {
    $maxWidth = 0;

    foreach ($this->font[$glyph] as $row => $rowString) {
      if (strlen($rowString) > $maxWidth) {
        $maxWidth = strlen($rowString);
      }
      foreach (str_split($rowString) as $column => $value) {
        if ($value == "-") {
        } else {
          $this->imageArray[$y+$row][$x+$column] = $this->brushShade;
        }
      }
    }

    return $maxWidth;
  }

  public function drawPixel($y, $x, $shade = null) {
    if ($shade == null)
      $this->imageArray[$y][$x] = $this->backgroundShade;
    else
      $this->imageArray[$y][$x] = $shade;
  }

  private function getGlyphWidth($glyph) {
    $maxWidth = 0;
    foreach ($this->font[$glyph] as $rowString) {
      if (strlen($rowString) > $maxWidth) {
        $maxWidth = strlen($rowString);
      }
    }
    return $maxWidth;
  }

  public function printString($string, $x, $y) {
    $yOffset = 0;
    $lines = explode("\n", $string);
    foreach ($lines as $line) {
      $words = explode(" ", $line);

      $xOffset = 0;
      foreach ($words as $key => $word) {
        $wordWidth = 0;
        foreach (str_split($word) as $char) {
          $wordWidth += $this->getGlyphWidth($char) + 1;
        }

        if (($x + $xOffset + $wordWidth) > $this->width) {
          $xOffset = 0;
          $yOffset += 9;
        }

        foreach (str_split($word) as $char) {
          if (isset($this->font[$char])) {
            $glyph = $this->font[$char];
          } elseif (isset($this->font[strtoupper($char)])) {
            $glyph = $this->font[strtoupper($char)];
            $char = strtoupper($char);

          } else {
            $glyph = $this->font['world'];
            $char = 'world';
          }
          $xOffset += $this->printGlyph($char, $y + $yOffset, $x + $xOffset) + 1;
        }
        $xOffset += $this->printGlyph(" ", $y + $yOffset, $x + $xOffset) + 1;
      }
      $yOffset += 9;
    }
    return $yOffset;
  }

  public function drawBox($startX, $startY, $boxWidth, $boxHeight, $shade, $thickness) {
    if ($thickness == 0)
      return;

    for ($x=$startX; $x < $startX+$boxWidth; $x++) {
      $this->imageArray[$startY][$x]=$shade;
      $this->imageArray[$startY+$boxHeight-1][$x]=$shade;
    }
    for ($y=$startY; $y < $startY+$boxHeight; $y++) {
      $this->imageArray[$y][$startX]=$shade;
      $this->imageArray[$y][$startX+$boxWidth-1]=$shade;
    }
    if ($thickness > 1) {
      $this->drawBox($startX+1, $startY+1, $boxWidth-2, $boxHeight-2, $shade, $thickness-1);
    }
  }

  public function drawLine($startX, $startY, $finishX, $finishY, $shade, $thickness) {
    if ($thickness == 0)
      return;

    $dX = $finishX - $startX;
    $dY = $finishY - $startY;

    for ($x=$startX; $x < $finishX; $x++) {
      $y = $startY + ($dY * ($x - $startX))/$dX;
      $this->drawPixel(round($y), $x, $shade);
    }
  }

  private function generatePackedPixelData() {
    $outputGraphicMenuStringUnconverted = "";
    foreach ($this->imageArray as $row) {
      $outputGraphicMenuStringUnconverted .= substr(implode("", $row), 0, $this->width);
    }
    $outputGraphicMenuUnconverted = str_split($outputGraphicMenuStringUnconverted, 4);

    $outputGraphicMenuString = "";
    foreach ($outputGraphicMenuUnconverted as $value) {
      $outputGraphicMenuString .= base_convert(strrev(substr($value, 2, 2)), 4, 16);
      $outputGraphicMenuString .= base_convert(strrev(substr($value, 0, 2)), 4, 16);
    }

    return strtoupper($outputGraphicMenuString);
  }

  public function __toString() {
    return "<LocationX>".$this->locationX."</LocationX>\n"
          ."<LocationY>".$this->locationY."</LocationY>\n"
          ."<Width>".$this->width."</Width>\n"
          ."<Height>".$this->height."</Height>\n"
          ."<Depth>".$this->depth."</Depth>\n"
          ."<Data>".$this->generatePackedPixelData()."</Data>\n";
  }
}

class GraphicFile extends Image {
  public $url;
  public $image;
  public $baseDir;
  public $colourPalette;
  // public $textLastX, $textLastY;


  function __construct($baseDir, $baseUrl) {
    $this->baseDir = $baseDir.'/';
    $this->image = imagecreatefrompng($this->baseDir.$baseUrl);

    $this->setLocationX(0);
    $this->setLocationY(0);

    $colourPalette = array();
    $colourPalette["black"] = imagecolorallocate($this->image, 0, 0, 0);
  }

  function saveImage($fileName) {
    imagepng($this->image, $this->baseDir.$fileName);
  }

  function includeSubImage($fileName, $xStart, $yStart, $xSrcStart, $ySrcStart, $width, $height) {
    $newSubImage = imagecreatefrompng($fileName);

    imagecopyresampled($this->image, $newSubImage, $xStart, $yStart, $xSrcStart, $ySrcStart, $width, $height, imagesx($newSubImage), imagesy($newSubImage));
  }

  function extractArea($startX, $startY, $width, $height) {
    $returnImage = imagecreate($width, $height);
    imagecopy($returnImage, $this->image, 0, 0, $startX, $startY, $width, $height); 
    return $returnImage;
  }

  function invertArea($startX, $startY, $width, $height) {
    $tempImage = $this->extractArea($startX, $startY, $width, $height);
    imagefilter($tempImage, IMG_FILTER_NEGATE);
    imagecopy($this->image, $tempImage, $startX, $startY, 0, 0, $width, $height);
  }

  public function addTextArray($startX, $startY, $maxX, $maxY, $stringArray) {
    //Text Sizes
    //[0] => Width of character
    //[1] => Total height of character
    //[2] => Pixels of char under line
    $textSizes = array(
      1 => [4,7,1],
      2 => [5,10,2],
      3 => [6,11,2],
      4 => [7,13,3],
      5 => [8,12,2],
    );

    $positionY = $startY;

    foreach ($stringArray as $textIndex => $textArray) {
      $size = $textArray[0];
      $textString = $textArray[1];

      $textStringArray = explode(" ", $textString);
      $printString = $textStringArray[0];
      
      for ($arrayPointer=1; $arrayPointer < sizeof($textStringArray); $arrayPointer++) { 
        if ($positionY+$textSizes[$size][1] >= $maxY) {
          break 2;
        }

        if ((strlen($printString)+strlen($textStringArray[$arrayPointer])+1)*($textSizes[$size][0]+1) > ($maxX - $startX)) {
          //String too long
          $this->addText($startX, $positionY, $size, $printString);
          $positionY += $textSizes[$size][1] + 2;
          $printString = $textStringArray[$arrayPointer];
        } else {
          //String short
          $printString .= " ".$textStringArray[$arrayPointer];
        }
      }
      if ($positionY+$textSizes[$size][1] >= $maxY) {
          break;
      }
      $this->addText($startX, $positionY, $size, $printString);
      $positionY += $textSizes[$size][1] + 3;
    }
  }


  public function addText($x, $y, $size, $string) {
    imagestring($this->image, $size, $x, $y, $string, $this->colourPalette["black"]);
  }
}

class CiscoIPPhoneMenu extends CiscoIPPhone{
  public $menu;

  function __construct(){
    $this->menu = new Menu();
  }

  public function setMenu($menu) {
    $this->menu = $menu;
  }

  public function buildOutput() {
    return $this->menu;
  }
}

class CiscoIPPhoneText {}

class CiscoIPPhoneInput {}

class CiscoIPPhoneDirectory {}

class CiscoIPPhoneImage extends CiscoIPPhone {
  public $image;

  function __construct(){
    $image = new Image();    
  }

  public function buildOutput() {
    return $image;    
  }
}

class CiscoIPPhoneImageFile {}

class CiscoIPPhoneGraphicMenu extends CiscoIPPhone {
  public $image;
  public $menu;

  function __construct(){
    $this->image = new Image();
    $this->menu = new Menu();
  }

  public function buildOutput() {
    return $this->image."\n".$this->menu;
  }
}

class CiscoIPPhoneGraphicFileMenu extends CiscoIPPhone {
  use CiscoIPPhoneOutput;

  public $image;
  public $menu;

  function __construct($baseDir, $baseUrl){
    $this->image = new GraphicFile($baseDir, $baseUrl);
    $this->menu = new Menu();
    $this->menu->setMenuType("GraphicFileMenu");
  }

  public function setMenu($menu) {
    $this->menu = $menu;
  }


  public function setURL($url) {
    $this->image->url = $url;
  }

  public function buildOutput() {
    $returnString = "<LocationX>".$this->image->getLocationX()."</LocationX>\n"
          ."<LocationY>".$this->image->getLocationY()."</LocationY>\n"
          ."<URL>".$this->escapeSpecialCharsURL($this->image->url)."</URL>\n";  //PNG background image

    $returnString .= $this->menu;


    return $returnString;
  }
}

class CiscoIPPhoneIconMenu {}

class CiscoIPPhoneIconFileMenu {}

class CiscoIPPhoneStatus {}

class CiscoIPPhoneStatusFile {}

class CiscoIPPhoneExecute {}


?>