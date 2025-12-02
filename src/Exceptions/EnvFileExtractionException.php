<?php
namespace App\Exceptions;

use \Exception;

class EnvFileExtractionException extends Exception {
  public function errorMessage() {
    return "<p>EnvFileExtractionException:" . $this->getMessage() . "</p>";
  }
}
