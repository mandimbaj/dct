<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class MicrosoftEntraAuthenticationException extends RuntimeException
{
    public function __construct(
        public readonly string $translationKey,
        ?Throwable $previous = null,
    ) {
        parent::__construct($translationKey, 0, $previous);
    }
}
