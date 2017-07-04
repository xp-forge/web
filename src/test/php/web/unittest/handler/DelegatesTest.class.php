<?php namespace web\unittest\handler;

use web\Request;
use web\Response;
use web\Error;
use web\handler\Delegate;
use web\handler\Delegates;
use lang\XPClass;
use web\unittest\TestInput;
use web\unittest\TestOutput;
use lang\ElementNotFoundException;

class DelegatesTest extends \unittest\TestCase {
  private $fixture;

  /** @return void */
  public function setUp() {
    $this->fixture= new DelegatesFixture();
  }

  /**
   * Perform
   *
   * @param  web.Request $request
   * @param  web.Response $response
   * @param  var
   */
  private function perform($req, $res) {
    return (new Delegates($this->fixture))->from($req)->perform($req, $res);
  }

  #[@test]
  public function can_create() {
    new Delegates($this->fixture);
  }

  #[@test, @expect(ElementNotFoundException::class)]
  public function non_existant() {
    $req= new Request(new TestInput('GET', '/non-existant'));
    (new Delegates($this->fixture))->from($req);
  }

  #[@test]
  public function index_delegate() {
    $req= new Request(new TestInput('GET', '/'));
    $res= new Response(new TestOutput());

    $this->assertEquals('index', $this->perform($req, $res));
  }

  #[@test]
  public function options_delegate() {
    $req= new Request(new TestInput('OPTIONS', '/'));
    $res= new Response(new TestOutput());

    $this->assertEquals('options', $this->perform($req, $res));
  }

  #[@test]
  public function people_delegate() {
    $req= new Request(new TestInput('GET', '/people'));
    $res= new Response(new TestOutput());

    $this->assertEquals('people:max=10', $this->perform($req, $res));
  }

  #[@test]
  public function people_delegate_with_param() {
    $req= new Request(new TestInput('GET', '/people?max=20'));
    $res= new Response(new TestOutput());

    $this->assertEquals('people:max=20', $this->perform($req, $res));
  }

  #[@test]
  public function person_delegate() {
    $req= new Request(new TestInput('GET', '/people/1'));
    $res= new Response(new TestOutput());

    $this->assertEquals('person:1', $this->perform($req, $res));
  }

  #[@test]
  public function upload_key_delegate() {
    $key= 'ssh-rsa AAAAAAAAAAAAAAAAAAAAAA user@host';
    $req= new Request(new TestInput('PUT', '/people/1/keys/ssh', ['Content-Length' => strlen($key)], $key));
    $res= new Response(new TestOutput());
    $req->pass('user', 'test-user');

    $this->assertEquals('person:1,type:ssh='.$key.' via test-user', $this->perform($req, $res));
  }

  #[@test]
  public function upload_avatar_delegate() {
    $image= 'GIF89a';
    $req= new Request(new TestInput(
      'PUT',
      '/people/1/avatar',
      ['Content-Type' => 'image/gif', 'Content-Length' => strlen($image)],
      $image
    ));
    $res= new Response(new TestOutput());
    $req->pass('user', 'test-user');

    $this->assertEquals('person:1,type:image/gif='.$image.' via test-user', $this->perform($req, $res));
  }

  #[@test, @expect(Error::class)]
  public function admin_delegate() {
    $req= new Request(new TestInput('GET', '/admin'));
    $res= new Response(new TestOutput());

    $this->perform($req, $res);
  }

  #[@test, @expect(Error::class)]
  public function login_delegate() {
    $req= new Request(new TestInput('GET', '/login'));
    $res= new Response(new TestOutput());

    $this->perform($req, $res);
  }
}