<?php

/**
 * Framework exception to indicate the developer when the framework
 * has detected an issue in the code.
 */
class FrameworkException extends Exception {

    /**
     * Possible solution suggested to solve the issue.
     * 
     * @var string @suggestion
     */
    public ?string $suggestion;

    /**
     * @param string $message
     * @param string $suggestion
     */
    public function __construct(string $message, string $suggestion = null) {
        parent::__construct($message, 0, null);
        $this->suggestion = $suggestion;
    }

    /**
     * Convert the exception to a string line.
     * 
     * @return string
     */
    public function __toString() : string {
        return __CLASS__ . ": {$this->message}\n";
    }


}