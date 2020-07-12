<?php namespace web\unittest\io;

use io\streams\{Streams, MemoryOutputStream};
use io\{Folder, File, Files, Path, IOException};
use lang\{Environment, IllegalArgumentException};
use unittest\TestCase;
use web\io\{Part, Stream};

class StreamTest extends TestCase {
  const NAME = 'test.txt';

  /**
   * Creates an iterable from given chunks
   *
   * @param  string... $chunks
   * @return iterable
   */
  private function asIterable(... $chunks) {
    foreach ($chunks as $chunk) {
      yield $chunk;
    }
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
  private function assertTransfer($expected, $target) {
    $t= new Folder(Environment::tempDir(), 'xp-web-streamtests');
    $t->create();

    try {
      $written= $this->newFixture(self::NAME, 'Test')->transfer($target($t));

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

  /** @return iterable */
  private function chunks() {
    yield [[], ''];
    yield [['Test'], 'Test'];
    yield [['Test', 'ed'], 'Tested'];
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

  #[@test]
  public function can_create() {
    $this->newFixture(self::NAME);
  }

  #[@test]
  public function kind() {
    $this->assertEquals(Part::FILE, $this->newFixture(self::NAME)->kind());
  }

  #[@test, @values('names')]
  public function name($name, $base) {
    $this->assertEquals($base, $this->newFixture($name)->name());
  }

  #[@test, @values('names')]
  public function raw_name($name, $base) {
    $this->assertEquals($name, $this->newFixture($name)->name(true));
  }

  #[@test]
  public function type() {
    $this->assertEquals('text/plain', $this->newFixture(self::NAME)->type());
  }

  #[@test]
  public function string_representation() {
    $this->assertEquals('web.io.Stream("test", type= text/plain)', $this->newFixture('test')->toString());
  }

  #[@test, @values('chunks')]
  public function bytes($chunks, $expected) {
    $this->assertEquals($expected, $this->newFixture(self::NAME, ...$chunks)->bytes());
  }

  #[@test, @values('chunks')]
  public function read_all($chunks, $expected) {
    $this->assertEquals($expected, Streams::readAll($this->newFixture(self::NAME, ...$chunks)));
  }

  #[@test, @values('chunks')]
  public function transfer_to_outputstream($chunks, $expected) {
    $out= new MemoryOutputStream();
    $written= $this->newFixture(self::NAME, ...$chunks)->transfer($out);

    $this->assertEquals(strlen($expected), $written);
    $this->assertEquals($expected, $out->bytes());
  }

  #[@test, @expect(IOException::class)]
  public function exceptions_raised_while_storing() {
    $out= new class() extends MemoryOutputStream {
      public function write($bytes) { throw new IOException('Disk full'); }
    };
    $this->newFixture(self::NAME, 'Test')->transfer($out);
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
    $this->assertTransfer(['test.txt' => 'Test'], $target);
  }

  #[@test, @values([
  #  [fn($t) => new File($t, 'target.txt')],
  #  [fn($t) => new Path($t, 'target.txt')],
  #  [fn($t) => $t->getURI().'target.txt'],
  #])]
  public function transfer_to_file($target) {
    $this->assertTransfer(['target.txt' => 'Test'], $target);
  }
}