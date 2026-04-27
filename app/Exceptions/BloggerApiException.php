<?php

namespace App\Exceptions;

use Google\Service\Exception as Google_Service_Exception;
use RuntimeException;

class BloggerApiException extends RuntimeException
{
    public static function fromGoogleException(Google_Service_Exception $e): self
    {
        $errors = $e->getErrors();
        $message = $errors[0]['message'] ?? $e->getMessage();

        return new self($message, $e->getCode(), $e);
    }
}
