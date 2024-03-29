<?php namespace web\unittest\io;

use io\streams\{MemoryOutputStream, Streams};
use io\{File, Files, Folder, IOException, Path};
use lang\{Environment, IllegalArgumentException};
use test\{Assert, Expect, Test, Values};
use web\io\{Part, Stream};

class StreamTest {
  const NAME= 'test.txt';

  /**
   * Creates an iterable from given chunks
   *
   * @param  string... $chunks
   * @return iterable
   */
  private function asIterable(... $chunks) {
    yield from $chunks;
  }

  /**
   * Creates a new fixture with given chunks
   *
   * @param  string $name
   * @param  string... $chunks
   * @return web.io.Stream
   */
  private function newFixture($name, ... $chunks) {
    return new Stream($name, 'text/plain', $this->asIterable(...$chunks));
  }

  /**
   * Assertion helper
   *
   * @param  [:string] $expected
   * @param  function(io.Folder): var
   * @throws unittest.AssertionFailedErrror
   */
  private function assertTransmission($expected, $target) {
    $t= new Folder(Environment::tempDir(), 'xp-web-streamtests');
    $t->create();

    try {
      $written= yield from $this->newFixture(self::NAME, 'Test')->transmit($target($t));

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
  private function chunks() {
    yield [[], ''];
    yield [['Test'], 'Test'];
    yield [['Test', 'ed'], 'Tested'];
    yield [['Test', null, 'ed'], 'Tested'];
  }

  /** @return iterable */
  private function names() {
    yield ['.hidden', '.hidden'];
    yield ['test', 'test'];
    yield ['test.php', 'test.php'];
    yield ['C:\\autoexec.bat', 'autoexec.bat'];
    yield ['..\\test.txt', 'test.txt'];
    yield ['.\\test.txt', 'test.txt'];
    yield ['/etc/passwd', 'passwd'];
    yield ['../test.txt', 'test.txt'];
    yield ['./test.txt', 'test.txt'];
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

  #[Test, Values(from: 'names')]
  public function name($name, $base) {
    Assert::equals($base, $this->newFixture($name)->name());
  }

  #[Test, Values(from: 'names')]
  public function raw_name($name, $base) {
    Assert::equals($name, $this->newFixture($name)->name(true));
  }

  #[Test]
  public function type() {
    Assert::equals('text/plain', $this->newFixture(self::NAME)->type());
  }

  #[Test]
  public function string_representation() {
    Assert::equals('web.io.Stream("test", type= text/plain)', $this->newFixture('test')->toString());
  }

  #[Test, Values(from: 'chunks')]
  public function bytes($chunks, $expected) {
    Assert::equals($expected, $this->newFixture(self::NAME, ...$chunks)->bytes());
  }

  #[Test, Values(from: 'chunks')]
  public function read_all($chunks, $expected) {
    Assert::equals($expected, Streams::readAll($this->newFixture(self::NAME, ...$chunks)));
  }

  #[Test]
  public function read_empty() {
    $fixture= $this->newFixture(self::NAME);
    Assert::null($fixture->read());
  }

  #[Test]
  public function read_after_end() {
    $fixture= $this->newFixture(self::NAME, 'a');
    $fixture->read();
    Assert::null($fixture->read());
  }

  #[Test, Values(from: 'chunks')]
  public function transmit_to_outputstream($chunks, $expected) {
    $out= new MemoryOutputStream();
    $it= $this->newFixture(self::NAME, ...$chunks)->transmit($out);
    while ($it->valid()) {
      $it->next();
    }

    Assert::equals(strlen($expected), $it->getReturn());
    Assert::equals($expected, $out->bytes());
  }

  #[Test, Expect(IOException::class)]
  public function exceptions_raised_while_storing() {
    $out= new class() extends MemoryOutputStream {
      public function write($bytes) { throw new IOException('Disk full'); }
    };
    $it= $this->newFixture(self::NAME, 'Test')->transmit($out);
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

  #[Test]
  public function close_is_a_noop() {
    Assert::null($this->newFixture(self::NAME)->close());
  }
}