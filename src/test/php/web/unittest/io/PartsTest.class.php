<?php namespace web\unittest\io;

use io\OperationNotSupportedException;
use io\streams\{InputStream, MemoryInputStream, Streams};
use lang\FormatException;
use unittest\{Expect, Test, TestCase, Values};
use web\io\{Part, Parts};

class PartsTest extends TestCase {
  const BOUNDARY = '------------------------899f0c287170dd63';

  /**
   * Creates a new fixture from a given payload
   *
   * @param  string... $payload
   * @return web.io.Parts
   */
  private function parts(... $payload) {
    $body= sprintf(implode("\r\n", $payload), self::BOUNDARY);
    return new Parts(new MemoryInputStream($body), self::BOUNDARY);
  }

  /**
   * Assertion helper
   *
   * @param  var[][] $expected
   * @param  web.io.Parts $parts
   * @return void
   * @throws unittest.AssertionFailedError
   */
  private function assertParts($expected, $parts) {
    $actual= [];
    foreach ($parts as $name => $part) {
      if (Part::FILE === $part->kind()) {
        $actual[]= ['file', $name.':'.$part->name().':'.$part->type(), $part->bytes()];
      } else if (Part::PARAM === $part->kind()) {
        $actual[]= ['param', $name, $part->value()];
      } else {
        $actual[]= ['incomplete', $name, $part->error()];
      }
    }
    $this->assertEquals($expected, $actual);
  }

  #[Test]
  public function single_parameter() {
    $this->assertParts([['param', 'submit', 'Upload']], $this->parts(
      '--%1$s',
      'Content-Disposition: form-data; name="submit"',
      '',
      'Upload',
      '--%1$s--',
      ''
    ));
  }

  #[Test]
  public function multiple_parameters() {
    $this->assertParts([['param', 'tc', 'Checked'], ['param', 'submit', 'Upload']], $this->parts(
      '--%1$s',
      'Content-Disposition: form-data; name="tc"',
      '',
      'Checked',
      '--%1$s',
      'Content-Disposition: form-data; name="submit"',
      '',
      'Upload',
      '--%1$s--',
      ''
    ));
  }

  #[Test]
  public function single_file() {
    $parts= [['file', 'upload:test.txt:text/plain', 'Test']];
    $this->assertParts($parts, $this->parts(
      '--%1$s',
      'Content-Disposition: form-data; name="upload"; filename="test.txt"',
      'Content-Type: text/plain',
      '',
      'Test',
      '--%1$s--',
      ''
    ));
  }

  #[Test]
  public function missing_file() {
    $parts= [['incomplete', 'upload', 'ERR_NO_FILE']];
    $this->assertParts($parts, $this->parts(
      '--%1$s',
      'Content-Disposition: form-data; name="upload"; filename=""',
      'Content-Type: application/octet-stream',
      '',
      '',
      '--%1$s--',
      ''
    ));
  }

  #[Test]
  public function multiple_files() {
    $parts= [['file', 'upload:test.txt:text/plain', 'Test'], ['file', 'upload:avatar.png:image/png', '...']];
    $this->assertParts($parts, $this->parts(
      '--%1$s',
      'Content-Disposition: form-data; name="upload"; filename="test.txt"',
      'Content-Type: text/plain',
      '',
      'Test',
      '--%1$s',
      'Content-Disposition: form-data; name="upload"; filename="avatar.png"',
      'Content-Type: image/png',
      '',
      '...',
      '--%1$s--',
      ''
    ));
  }

  #[Test]
  public function file_and_parameter() {
    $parts= [['file', 'upload:test.txt:text/plain', 'Test'], ['param', 'submit', 'Upload']];
    $this->assertParts($parts, $this->parts(
      '--%1$s',
      'Content-Disposition: form-data; name="upload"; filename="test.txt"',
      'Content-Type: text/plain',
      '',
      'Test',
      '--%1$s',
      'Content-Disposition: form-data; name="submit"',
      '',
      'Upload',
      '--%1$s--',
      ''
    ));
  }

  #[Test, Values([[''], ['Test'], ["\r"], ["\n"], ["\r\n"], ["\r\n--"], ["Un*x\nNo newline at end of file"], ["Mac\rNo newline at end of file"], ["Windows\r\nNo newline at end of file"], ["Un*x: Line 1\nLine 2\n"], ["Mac: Line 1\rLine 2\r"], ["Windows: Line 1\r\nLine 2\r\n"], ["GIF89a\1\0\1\0\200\0\0\0\0\0\377\377\377\!\371\4\1\0\0\0\0,\0\0\0\0\1\0\1\0@\2\1D\0;"],])]
  public function file_contents($bytes) {
    $this->assertParts([['file', 'upload:test.data:application/octet-stream', $bytes]], $this->parts(
      '--%1$s',
      'Content-Disposition: form-data; name="upload"; filename="test.data"',
      'Content-Type: application/octet-stream',
      '',
      $bytes,
      '--%1$s--',
      ''
    ));
  }

  #[Test]
  public function crlf_inside_chunk_of_file_contents() {
    $input= new class() implements InputStream {
      private $chunk= 0;
      private $buffer= [
        '--%1$s',
        'Content-Disposition: form-data; name="upload"; filename="test.data"',
        'Content-Type: application/octet-stream',
        '',
        'Hey',
        'Not the end',
        '--%1$s--',
        ''
      ];

      public function read($limit= 8192) {
        return sprintf($this->buffer[$this->chunk++], PartsTest::BOUNDARY)."\r\n";
      }

      public function available() {
        return $this->chunk < sizeof($this->buffer);
      }

      public function close() { }
    };

    $it= (new Parts($input, self::BOUNDARY))->getIterator();
    $this->assertEquals("Hey\r\nNot the end", $it->current()->bytes());
  }

  #[Test]
  public function can_skip_reading_parts() {
    $parts= $this->parts(
      '--%1$s',
      'Content-Disposition: form-data; name="tc"',
      '',
      'Checked',
      '--%1$s',
      'Content-Disposition: form-data; name="upload"; filename="test.txt"',
      'Content-Type: text/plain',
      '',
      'Test',
      '--%1$s',
      'Content-Disposition: form-data; name="upload"; filename="avatar.png"',
      'Content-Type: image/png',
      '',
      '...',
      '--%1$s',
      'Content-Disposition: form-data; name="submit"',
      '',
      'Upload',
      '--%1$s--',
      ''
    );

    // Only read avatar image
    $bytes= null;
    foreach ($parts as $part) {
      if (Part::FILE === $part->kind() && 'avatar.png' === $part->name()) {
        $bytes= Streams::readAll($part);
      }
    }
    $this->assertEquals('...', $bytes);
  }

  #[Test, Expect(OperationNotSupportedException::class)]
  public function cannot_read_from_incomplete_file() {
    $parts= $this->parts(
      '--%1$s',
      'Content-Disposition: form-data; name="upload"; filename=""',
      'Content-Type: application/octet-stream',
      '',
      '',
      '--%1$s--',
      ''
    );

    Streams::readAll(iterator_to_array($parts)['upload']);
  }

  #[Test]
  public function missing_content_type_defaults_to_octet_stream() {
    $parts= [['file', 'upload:test.txt:application/octet-stream', 'Test']];
    $this->assertParts($parts, $this->parts(
      '--%1$s',
      'Content-Disposition: form-data; name="upload"; filename="test.txt"',
      '',
      'Test',
      '--%1$s--',
      ''
    ));
  }

  #[Test]
  public function parameter_string() {
    $parts= $this->parts(
      '--%1$s',
      'Content-Disposition: form-data; name="submit"',
      '',
      'Upload',
      '--%1$s--',
      ''
    );
    $this->assertEquals(
      'web.io.Param("submit", value= "Upload")',
      iterator_to_array($parts)['submit']->toString()
    );
  }

  #[Test]
  public function file_string() {
    $parts= $this->parts(
      '--%1$s',
      'Content-Disposition: form-data; name="upload"; filename="test.txt"',
      'Content-Type: text/plain',
      '',
      'Test',
      '--%1$s--',
      ''
    );
    $this->assertEquals(
      'web.io.Stream("test.txt", type= text/plain)',
      iterator_to_array($parts)['upload']->toString()
    );
  }

  #[Test]
  public function only_ending_delimiter() {
    $this->assertParts([], $this->parts('--%1$s--', ''));
  }

  #[Test]
  public function missing_trailing_crlf_ignored() {
    $this->assertParts([], $this->parts('--%1$s--'));
  }

  #[Test]
  public function malformed_empty_crlf_before_sole_ending_delimiter_ignored() {
    $this->assertParts([], $this->parts('', '--%1$s--', ''));
  }

  #[Test, Expect(['class' => FormatException::class, 'withMessage' => '/Malformed or truncated part/'])]
  public function missing_headers() {
    iterator_to_array($this->parts('--%1$s', ''));
  }

  #[Test, Expect(['class' => FormatException::class, 'withMessage' => '/Malformed or truncated part/'])]
  public function empty_headers() {
    iterator_to_array($this->parts('--%1$s', '', 'Upload'));
  }

  #[Test, Expect(['class' => FormatException::class, 'withMessage' => '/Malformed or truncated part/'])]
  public function malformed_part_without_content_disposition() {
    iterator_to_array($this->parts(
      '--%1$s',
      'Content-Type: text/plain',
      '',
      'Upload',
      '--%1$s--',
      ''
    ));
  }

  #[Test, Expect(['class' => FormatException::class, 'withMessage' => '/Expected boundary ".+", have ""/'])]
  public function missing_header_terminator() {
    iterator_to_array($this->parts(
      '--%1$s',
      'Content-Disposition: form-data; name="submit"'
    ));
  }

  #[Test, Expect(['class' => FormatException::class, 'withMessage' => '/Expected boundary ".+", have ""/'])]
  public function missing_data() {
    iterator_to_array($this->parts(
      '--%1$s',
      'Content-Disposition: form-data; name="submit"',
      ''
    ));
  }

  #[Test, Expect(['class' => FormatException::class, 'withMessage' => '/Expected boundary ".+", have ""/'])]
  public function missing_ending_delimiter() {
    iterator_to_array($this->parts(
      '--%1$s',
      'Content-Disposition: form-data; name="submit"',
      '',
      'Upload'
    ));
  }

  #[Test, Expect(['class' => FormatException::class, 'withMessage' => '/Expected boundary ".+", have ""/'])]
  public function mismatched_ending_delimiter() {
    iterator_to_array($this->parts(
      '--%1$s',
      'Content-Disposition: form-data; name="submit"',
      '',
      'Upload',
      '--END--',
      ''
    ));
  }

  #[Test, Expect(['class' => FormatException::class, 'withMessage' => '/Expected boundary ".+", have "--BEGIN"/'])]
  public function mismatched_starting_delimiter() {
    iterator_to_array($this->parts('--BEGIN', ''));
  }

  #[Test, Expect(['class' => FormatException::class, 'withMessage' => '/Expected boundary ".+", have ""/'])]
  public function from_empty_line() {
    iterator_to_array($this->parts());
  }

  #[Test, Expect(['class' => FormatException::class, 'withMessage' => '/Expected boundary ".+", have ""/'])]
  public function from_empty_payload() {
    iterator_to_array($this->parts());
  }
}