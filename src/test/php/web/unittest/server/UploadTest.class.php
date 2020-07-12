<?php namespace web\unittest\server;

use io\streams\{Streams, MemoryInputStream, MemoryOutputStream};
use io\{File, Folder, Files, Path, TempFile, IOException};
use lang\{Environment, IllegalArgumentException};
use unittest\TestCase;
use web\io\Part;
use xp\web\Upload;

class UploadTest extends TestCase {
  const NAME = 'test.txt';

  /**
   * Creates a new fixture with given chunks
   *
   * @param  string $name
   * @param  ?string $file
   * @return web.io.Stream
   */
  private function newFixture($name, $file= null) {
    return new Upload($name, 'text/plain', $file);
  }

  /**
   * Assertion helper
   *
   * @param  [:string] $expected
   * @param  function(io.Folder): var
   * @throws unittest.AssertionFailedErrror
   */
  private function assertTransferred($expected, $target) {
    $t= new Folder(Environment::tempDir(), 'xp-web-uploadtests');
    $t->create();

    $s= new TempFile('xp-web-upload');
    Files::write($s, 'Test');

    try {
      $written= $this->newFixture(self::NAME, $s->getURI())->transfer($target($t));

      $contents= [];
      foreach ($t->entries() as $name => $entry) {
        $contents[$name]= Files::read($entry->asFile());
      }
      $this->assertEquals(4, $written);
      $this->assertEquals($expected, $contents);
    } finally {
      $t->unlink();
    }
  }

  #[@test]
  public function can_create() {
    $this->newFixture(self::NAME);
  }

  #[@test]
  public function kind() {
    $this->assertEquals(Part::FILE, $this->newFixture(self::NAME)->kind());
  }

  #[@test]
  public function name() {
    $this->assertEquals(self::NAME, $this->newFixture(self::NAME)->name());
  }

  #[@test]
  public function type() {
    $this->assertEquals('text/plain', $this->newFixture(self::NAME)->type());
  }

  #[@test]
  public function string_representation() {
    $this->assertEquals(
      'xp.web.Upload("test.txt", type= text/plain, source= /tmp/upload)',
      $this->newFixture(self::NAME, '/tmp/upload')->toString()
    );
  }

  #[@test]
  public function bytes() {
    $source= Streams::readableUri(new MemoryInputStream('Test'));
    $this->assertEquals('Test', $this->newFixture(self::NAME, $source)->bytes());
  }

  #[@test]
  public function read_all() {
    $source= Streams::readableUri(new MemoryInputStream('Test'));
    $this->assertEquals('Test', Streams::readAll($this->newFixture(self::NAME, $source)));
  }

  #[@test]
  public function transfer_to_outputstream() {
    $in= new MemoryInputStream('Test');
    $out= new MemoryOutputStream();
    $written= $this->newFixture(self::NAME, Streams::readableUri($in))->transfer($out);

    $this->assertEquals(4, $written);
    $this->assertEquals('Test', $out->bytes());
  }

  #[@test, @expect(IOException::class)]
  public function exceptions_raised_while_storing() {
    $in= new MemoryInputStream('Test');
    $out= new class() extends MemoryOutputStream {
      public function write($bytes) { throw new IOException('Disk full'); }
    };
    $this->newFixture(self::NAME, Streams::readableUri($in))->transfer($out);
  }

  #[@test, @values(['', null, "\0abc", "/etc/\0passwd"]), @expect(IllegalArgumentException::class)]
  public function transfer_to_invalid_filename($name) {
    $this->newFixture(self::NAME)->transfer($name);
  }

  #[@test, @values([
  #  [fn($t) => $t],
  #  [fn($t) => new Path($t)],
  #  [fn($t) => $t->getURI()],
  #])]
  public function transfer_to_folder($target) {
    $this->assertTransferred(['test.txt' => 'Test'], $target);
  }

  #[@test, @values([
  #  [fn($t) => new File($t, 'target.txt')],
  #  [fn($t) => new Path($t, 'target.txt')],
  #  [fn($t) => $t->getURI().'target.txt'],
  #])]
  public function transfer_to_file($target) {
    $this->assertTransferred(['target.txt' => 'Test'], $target);
  }
}