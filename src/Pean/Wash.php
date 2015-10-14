<?php

namespace Pean;

class Wash {

  protected $arr;
  protected $dbh;
  protected $config;

  public function __construct($config) {

    $this->config = $config;

    if(empty($this->config['test'])) {

      $input = file_get_contents("php://input");
      $this->arr = json_decode($input, true);

      // Nothing submitted: Goto URL
      $this->gotoURL($_SERVER['REQUEST_URI']);

      // JSON submitted: Create url
      $this->createURL();

    }

  }

  public function hello() {
    return "Hello Wash!";
  }

  protected function ga($hash,$url_id,$url) {

    $item = '/Wash/'.$hash.'/'.$url_id;

    $tracker = new \UnitedPrototype\GoogleAnalytics\Tracker($this->config['ga']['id'], $this->config['ga']['site']);
    $visitor = new \UnitedPrototype\GoogleAnalytics\Visitor();
    $visitor->setIpAddress($_SERVER['REMOTE_ADDR']);
    $visitor->setUserAgent($_SERVER['HTTP_USER_AGENT']);
    $visitor->setScreenResolution('1x1');

    $session = new \UnitedPrototype\GoogleAnalytics\Session();

    $page = new \UnitedPrototype\GoogleAnalytics\Page($item);
    $page->setTitle($url);

    $tracker->trackPageview($page, $session, $visitor);

  }

  protected function db() {
    try {
      $this->dbh = new \PDO(
        'mysql:host='.$this->config['db']['host'].';port=3306;dbname='.$this->config['db']['db'],
        $this->config['db']['user'],
        $this->config['db']['passw']
      );
    } catch (PDOException $e) {
      $this->response(0,"errorMsg","Could not connect to database: ".$e->getMessage());
    }
  } 

  protected function hashids($type,$str) {
    $hashids = new \Hashids\Hashids(md5($this->config['salt']));
    if($type == 'decode') {
      return end($hashids->decode($str));
    } elseif($type == "encode") {
      return $hashids->encode($str);
    }
  }

  protected function identifyURL($url) {

    $hash = end(explode('/',$url));

    // Nothng there. 
    if(!preg_match('/^[a-zA-Z0-9]+$/',$hash)) {
      return FALSE;
    }

    // Check alias first
    $url_id = $this->getAlias($hash);

    // If not an alias, go to hash
    if($url_id === FALSE) {
      $url_id = $this->hashids('decode',$hash);
    }

    if(preg_match('/^[\d]+$/',$url_id)) {
      // Something there
      return $url_id;
    } else {
      // Something not valid there
      return FALSE;
    }
  }

  protected function gotoURL($url, $output = 'redirect') {
    if(empty($this->arr)) {

      $url_id = $this->identifyURL($url);

      if($url_id === FALSE) {
        $this->response(0,"errorMsg","Invalid url");
      }

      $this->db(); 

      $sql = "select url from wash_urls where id=?";
      $stmt = $this->dbh->prepare($sql);
      try {
        $stmt->execute(array($url_id));
        $url = $stmt->fetchColumn();
      } catch (PDOException $e) {
        $this->response(0,"errorMsg","Could not fetch url: ".$e->getMessage());
      }

      if(!empty($url)) {
        $sql = "update wash_urls set clicks=clicks+1 where id=?";
        $stmt = $this->dbh->prepare($sql);
        try {
          $stmt->execute(array($url_id));
        } catch (PDOException $e) {
          $this->response(0,"errorMsg","Could count click: ".$e->getMessage());
        }

        // Track GA
        $this->ga($hash,$url_id,$url);

        if($output == 'JSON') {
          $this->response(1,"url",$url);
        } else {
          header('Location: '.$url);
          exit;
        }
      }
    }
  }

  protected function getAlias($alias) {
    $this->db();
    $sql = "select url_id from wash_aliases where alias=?";
    $stmt = $this->dbh->prepare($sql);
    try {
      $stmt->execute(array($alias));
      $url_id = $stmt->fetchColumn();
    } catch (PDOException $e) {
      $this->response(0,"errorMsg","Could not fetch alias: ".$e->getMessage());
    }
    if(!empty($url_id)) {
      return $url_id;
    }
    return FALSE;
  }

  protected function getUser() {

    if(empty($this->arr['token'])) {
      $this->response(0,"errorMsg","Missing token");
    }

    $this->db(); 

    $sql = "select id from wash_users where token=?";
    $stmt = $this->dbh->prepare($sql);
    try {
      $stmt->execute(array($this->arr['token']));
      $user_id = $stmt->fetchColumn();
    } catch (PDOException $e) {
      $this->response(0,"errorMsg", "Coud not fetch user".$e->getMessage());
    }
    if(empty($user_id)) {
      $this->response(0,"errorMsg","User not found");
    }
    return $user_id;
  }

  protected function createURL() {
    if(!empty($this->arr['url'])) {
     
      // Get user
      $user_id = $this->getUser();

      $sql = "select id from wash_urls where url=? && user_id=?";
      $stmt = $this->dbh->prepare($sql);
      try {
        $stmt->execute(array($this->arr['url'],$user_id));
        $url_id = $stmt->fetchColumn();
      } catch (PDOException $e) {
        $this->response(0,"errorMsg","Could not check if url existed: ".$e->getMessage());
      }

      if(empty($url_id)) {
        $sql = "insert into wash_urls (user_id,url,created) values (?,?,now())";
        $stmt = $this->dbh->prepare($sql);
        try {
          $stmt->execute(array($user_id,$this->arr['url']));
        } catch (PDOException $e) {
          $this->response(0,"errorMsg","Could not create link: ".$e->getMessage());
        }
        // Get last insert id
        $url_id = $this->dbh->lastInsertId();
      }
      // Generate the url
      if(!empty($url_id)) {
        $hash = $this->hashids('encode',$url_id);
        $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].$hash;
        // response(1,'url',$url);
        echo $url;
        exit;
      }
    }
  }

  protected function response($status,$key,$str) {
    header('Content-type: application/json');
    echo json_encode(array("status" => $status, $key => $str ));
    exit;
  }

}