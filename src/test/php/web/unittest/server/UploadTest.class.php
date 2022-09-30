<?php namespace web\unittest\server;

use io\streams\{MemoryInputStream, MemoryOutputStream, Streams};
use io\{File, Files, Folder, IOException, Path, TempFile};
use lang\{Environment, IllegalArgumentException};
use unittest\{Expect, Test, TestCase, Values};
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
      $this->assertEquals(4, $written);
      $this->assertEquals($expected, $contents);
    } finally {
      $t->unlink();
    }
  }

  #[Test]
  public function can_create() {
    $this->newFixture(self::NAME);
  }

  #[Test]
  public function kind() {
    $this->assertEquals(Part::FILE, $this->newFixture(self::NAME)->kind());
  }

  #[Test]
  public function name() {
    $this->assertEquals(self::NAME, $this->newFixture(self::NAME)->name());
  }

  #[Test]
  public function type() {
    $this->assertEquals('text/plain', $this->newFixture(self::NAME)->type());
  }

  #[Test]
  public function string_representation() {
    $this->assertEquals(
      'xp.web.Upload("test.txt", type= text/plain, source= /tmp/upload)',
      $this->newFixture(self::NAME, '/tmp/upload')->toString()
    );
  }

  #[Test]
  public function bytes() {
    $source= Streams::readableUri(new MemoryInputStream('Test'));
    $this->assertEquals('Test', $this->newFixture(self::NAME, $source)->bytes());
  }

  #[Test]
  public function read_all() {
    $source= Streams::readableUri(new MemoryInputStream('Test'));
    $this->assertEquals('Test', Streams::readAll($this->newFixture(self::NAME, $source)));
  }

  #[Test]
  public function transmit_to_outputstream() {
    $in= new MemoryInputStream('Test');
    $out= new MemoryOutputStream();

    $it= $this->newFixture(self::NAME, Streams::readableUri($in))->transmit($out);
    while ($it->valid()) {
      $it->next();
    }

    $this->assertEquals(4, $it->getReturn());
    $this->assertEquals('Test', $out->bytes());
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

  #[Test, Values(eval: '[[fn($t) => $t], [fn($t) => new Path($t)], [fn($t) => $t->getURI()]]')]
  public function transmit_to_folder($target) {
    $this->assertTransmission(['test.txt' => 'Test'], $target);
  }

  #[Test, Values(eval: '[[fn($t) => new File($t, "target.txt")], [fn($t) => new Path($t, "target.txt")], [fn($t) => $t->getURI()."target.txt"]]')]
  public function transmit_to_file($target) {
    $this->assertTransmission(['target.txt' => 'Test'], $target);
  }
}