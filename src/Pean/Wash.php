<?php

namespace Pean;

class Wash {

  public $hashids;
  protected $arr;
  protected $dbh;
  protected $config;

  public function hello() {
    echo "Hello Wash!";
  }

  function __construct($config) {

    $this->config = $config;

    // TODO: Only do this if there is there is something to do 
    $this->db(); 
    $this->hashids();

    $input = file_get_contents("php://input");
    $this->arr = json_decode($input, true);

    // Nothing submitted: Goto URL
    if(empty($this->arr)) {
      $this->gotoURL();
    }

    // JSON submitted: Create url
    elseif(!empty($this->arr['url'])) {
      $this->createURL();
    }

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

  protected function hashids() {
    // Init hasids
    $this->hashids = new \Hashids\Hashids(md5($this->config['salt']));
  }

  protected function gotoURL() {
    $url = $_SERVER['REQUEST_URI'];
    $hash = end(explode('/',$url));

    // Nothng there. 
    if(empty($hash)) {
      return false;
    }

    $url_id = end($this->hashids->decode($hash));
    if(empty($url_id)) {
      $this->response(0,"errorMsg","Invalid url");
    }

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

      $this->response(1,"url",$url);
      // header('Location: '.$url);
      exit;
    }

  }

  protected function getUser() {
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

    // Get user
    if(!empty($this->arr['token'])) {
      $user_id = $this->getUser();
    }

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
      $hash = $this->hashids->encode($url_id);
      $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].$hash;
      // response(1,'url',$url);
      echo $url;
      exit;
    }
  }

  protected function response($status,$key,$str) {
    echo json_encode(array("status" => $status, $key => $str ));
    exit;
  }

}