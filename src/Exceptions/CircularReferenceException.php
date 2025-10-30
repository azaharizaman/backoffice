<?php

declare(strict_types=1);

namespace AzahariZaman\BackOffice\Exceptions;

use Exception;

/**
 * Circular Reference Exception
 * 
 * Thrown when attempting to create a circular reference in hierarchical structures.
 */
class CircularReferenceException extends Exception
{
    public function __construct(string $message = 'Circular reference detected in hierarchy', int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}