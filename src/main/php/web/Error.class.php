<?php namespace web;

class Error extends \lang\XPException {

  public function __construct($status, $message= null, $cause= null) {
    parent::__construct($message ?: Status::message($status), $cause);
    $this->status= $status;
  }

  /** @return int */
  public function status() { return $this->status; }

  /** @return string */
  public function compoundMessage() { return nameof($this).'(#'.$this->status.': '.$this->message.')'; }
}