<?php namespace web\unittest\server;

use io\streams\{MemoryInputStream, MemoryOutputStream, Streams};
use io\{File, Files, Folder, IOException, Path, TempFile};
use lang\{Environment, IllegalArgumentException};
use test\{Assert, Expect, Test, Values};
use web\io\Part;
use xp\web\Upload;

class UploadTest {
  const NAME= 'test.txt';

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
  private function assertTransmission($expected, $target) {
    $t= new Folder(Environment::tempDir(), 'xp-web-uploadtests');
    $t->create();

    $s= new TempFile('xp-web-upload');
    Files::write($s, 'Test');

    try {
      $written= yield from $this->newFixture(self::NAME, $s->getURI())->transmit($target($t));

      $contents= [];
      foreach ($t->entries() as $name => $entry) {
        $contents[$name]= Files::read($entry->asFile());
      }
      Assert::equals(4, $written);
      Assert::equals($expected, $contents);
    } finally {
      $t->unlink();
    }
  }

  /** @return iterable */
  private function files() {
    yield function($t) { return new File($t, 'target.txt'); };
    yield function($t) { return new Path($t, 'target.txt'); };
    yield function($t) { return $t->getURI().'target.txt'; };
  }

  /** @return iterable */
  private function folders() {
    yield function($t) { return $t; };
    yield function($t) { return new Path($t); };
    yield function($t) { return $t->getURI(); };
  }

  #[Test]
  public function can_create() {
    $this->newFixture(self::NAME);
  }

  #[Test]
  public function kind() {
    Assert::equals(Part::FILE, $this->newFixture(self::NAME)->kind());
  }

  #[Test]
  public function name() {
    Assert::equals(self::NAME, $this->newFixture(self::NAME)->name());
  }

  #[Test]
  public function type() {
    Assert::equals('text/plain', $this->newFixture(self::NAME)->type());
  }

  #[Test]
  public function string_representation() {
    Assert::equals(
      'xp.web.Upload("test.txt", type= text/plain, source= /tmp/upload)',
      $this->newFixture(self::NAME, '/tmp/upload')->toString()
    );
  }

  #[Test]
  public function bytes() {
    $source= Streams::readableUri(new MemoryInputStream('Test'));
    Assert::equals('Test', $this->newFixture(self::NAME, $source)->bytes());
  }

  #[Test]
  public function read_all() {
    $source= Streams::readableUri(new MemoryInputStream('Test'));
    Assert::equals('Test', Streams::readAll($this->newFixture(self::NAME, $source)));
  }

  #[Test]
  public function transmit_to_outputstream() {
    $in= new MemoryInputStream('Test');
    $out= new MemoryOutputStream();

    $it= $this->newFixture(self::NAME, Streams::readableUri($in))->transmit($out);
    while ($it->valid()) {
      $it->next();
    }

    Assert::equals(4, $it->getReturn());
    Assert::equals('Test', $out->bytes());
  }

  #[Test, Expect(IOException::class)]
  public function exceptions_raised_while_storing() {
    $in= new MemoryInputStream('Test');
    $out= new class() extends MemoryOutputStream {
      public function write($bytes) { throw new IOException('Disk full'); }
    };

    $it= $this->newFixture(self::NAME, Streams::readableUri($in))->transmit($out);
    while ($it->valid()) {
      $it->next();
    }
  }

  #[Test, Values(['', null, "\0abc", "/etc/\0passwd"]), Expect(IllegalArgumentException::class)]
  public function transmit_to_invalid_filename($name) {
    $it= $this->newFixture(self::NAME)->transmit($name);
    while ($it->valid()) {
      $it->next();
    }
  }

  #[Test, Values(from: 'folders')]
  public function transmit_to_folder($target) {
    $this->assertTransmission(['test.txt' => 'Test'], $target);
  }

  #[Test, Values(from: 'files')]
  public function transmit_to_file($target) {
    $this->assertTransmission(['target.txt' => 'Test'], $target);
  }
}