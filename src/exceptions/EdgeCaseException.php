<?php
class EdgeCaseException extends Exception {
  public function errorMessage() {
    return "<p>EdgeCaseException:" . $this->getMessage() . "</p>";
  }
}
