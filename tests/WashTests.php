<?php 

// require_once __DIR__ . '/../vendor/autoload.php'; // Autoload files using Composer autoload

class WashTests extends \PHPUnit_Framework_TestCase {

  private $wash = null;
  private $config = null;

  public function __construct() {

    $this->config = array(  
      'db' => array (
        'host' => 'localhost',
        'user' => 'root',
        'passw' => '',
        'db' => 'wa_test',
      ),
      'salt' => 'f82bb174a4e911fb541bb80f922f1f66',
      'ga' => array (
        'id' => 'UA-123456-7',
       'site' => 'wa.se'
      ),
      'test' => 1
    );

    $this->wash = new Pean\Wash($this->config);

  }

  public function testHello() {
    $this->assertEquals('Hello Wash!', $this->wash->hello());
  }

  public function testHashidsEncode() {

    $id = 42;
    $hashids = new \Hashids\Hashids(md5($this->config['salt']));
    $hash = $hashids->encode($id);

    $method = new ReflectionMethod($this->wash,'hashids');
    $method->setAccessible(TRUE);

    $whash = $method->invokeArgs($this->wash,array('encode',$id));

    $this->assertEquals($hash,$whash);

  }

  public function testHashidsDecode() {

    $hash = 'Jn';
    $hashids = new \Hashids\Hashids(md5($this->config['salt']));
    $id = end($hashids->decode($hash));

    $method = new ReflectionMethod($this->wash,'hashids');
    $method->setAccessible(TRUE);

    $wid = $method->invokeArgs($this->wash,array('decode',$hash));

    $this->assertEquals($id,$wid);

  }

  public function testIdentifyURL() {

    $method = new ReflectionMethod($this->wash,'identifyURL');
    $method->setAccessible(TRUE);

    $n = 100;

    $urls = $this->generateURLs('valid',$n);

    foreach($urls as $url) {
      $id = $method->invokeArgs($this->wash,array($url));
      $this->assertInternalType('int',$id);
    }

    // Generate some invalid urls
    $urls = $this->generateURLs('invalid',$n);
    foreach($urls as $url) {
      $id = $method->invokeArgs($this->wash,array($url));
      $this->assertEquals(FALSE,$id);
    }

  }

  public function testGotoURL() {
    $method = new ReflectionMethod($this->wash,'gotoURL');
    $method->setAccessible(TRUE);

    $urls = $this->generateURLs('valid',10);
    foreach($urls as $url) {
      $json = $method->invokeArgs($this->wash,array($url,'JSON'));
      // echo $url.' => '.$json."\n";
    }
  }

  public function generateURLs($type,$n) {
    $baseurl = "http://wa.se/";
    if($type == 'invalid') {
      $validchars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
      $invalidchars = '/+;:_!"#€%&/()=?';

      $validcharslen = strlen($validchars);
      $invalidcharslen = strlen($invalidchars);

      $urls = array();
      for($i=0;$i<$n;$i++) {
        $hash = '';
        $length = rand(1,10);
        for($j=0;$j<$length;$j++) {
          $hash .= $validchars[rand(0, $validcharslen - 1)];
          $hash .= $invalidchars[rand(0, $invalidcharslen - 1)];
        }
        $urls[] = $baseurl.$hash;
      }
    } else {
      // Generate som valid urls
      $hashids = new \Hashids\Hashids(md5($this->config['salt']));
      $urls = array();
      for($i=0;$i<$n;$i++) {
        $int = rand(0,1000000);
        $hash = $hashids->encode($int);
        $urls[] = $baseurl.$hash;
      }
    }
    return $urls;
  }


}