<?php
class URLGen {
  public $host, $path, $param;

  public function __construct($host, $path, $param) {
    $this->host = $host;    //$_SERVER["host"]
    $this->path = $path;    //$_SERVER["path"]
    $this->param = $param;  //S_GET
  }
  
  public function setParam($key, $value) {
    $newParam = $this->param;
    $newParam[$key]=$value;
    return new URLGen($this->host, $this->path, $newParam);
  }
  
  public function unsetParam($key) {
    $newParam = $this->param;
    if (isset($newParam[$key])) {
      unset($newParam[$key]);
    }
    return new URLGen($this->host, $this->path, $newParam);
  }

  public function clearParams() {
    return new URLGen($this->host, $this->path, []);
  }

  public function incParam($param) {
    $newParam = $this->param;
    if (isset($newParam[$param])) {
      $newParam[$param]++;
    } else {
      $newParam[$param] = 1;
    }
    return new URLGen($this->host, $this->path, $newParam);
  }

  public function decParam($param) {
    $newParam = $this->param;
    if (isset($newParam[$param])) {
      $newParam[$param]--;
    } else {
      $newParam[$param] = -1;
    }
    return new URLGen($this->host, $this->path, $newParam);
  }

  public function upDir() {
    $dirStructure = explode("/", $this->path);
    // if (substr(end($dirStructure), -4) == ".php") {
    //   array_pop($dirStructure); //Gets rid of current page
    // }

    array_pop($dirStructure); //Clear empty element
    array_pop($dirStructure); //Gets rid of next dir up

    return new URLGen($this->host, implode("/", $dirStructure).'/', $this->param);
  }

  public function clearBasename() {
    $dirStructure = explode("/", $this->path);
    array_pop($dirStructure); //Gets rid of current page
    
    return new URLGen($this->host, implode("/", $dirStructure)."/", $this->param);
  }

  public function append($appendString) {
    return new URLGen($this->host, $this->path.$appendString, $this->param);
  }


  public function __toString() {
    $returnString = "http://".$this->host.$this->path;
    if (sizeof($this->param) > 0) {
      $returnString .= "?"
        .implode("&", array_map(function($key, $value) {return $key."=".$value;}
        ,array_keys($this->param), $this->param));
    }
    return $returnString;
  }
}

class URLGenWeb extends URLGen{
  public function __construct() {
    $this->host = $_SERVER["HTTP_HOST"];
    $this->path = $_SERVER["PHP_SELF"];
    $this->param = $_GET;
  }
}
?>
