<?php namespace web\io;

use IteratorAggregate, Traversable;
use io\streams\{InputStream, StringReader};

/** @see https://developer.mozilla.org/en-US/docs/Web/API/EventSource */
class EventSource implements IteratorAggregate {
  private $reader;

  /** Creates a new event source */
  public function __construct(InputStream $in) {
    $this->reader= new StringReader($in);
  }

  /** Yields events and associated data */
  public function getIterator(): Traversable {
    $event= null;
    while ($line= $this->reader->readLine()) {
      if (0 === strncmp($line, 'event: ', 7)) {
        $event= substr($line, 7);
      } else if (0 === strncmp($line, 'data: ', 6)) {
        yield $event => substr($line, 6);
        $event= null;
      }
    }
    $this->reader->close();
  }
}