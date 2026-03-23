<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidStateTransitionException extends RuntimeException
{
    public function __construct(
        public readonly string $currentState,
        public readonly string $targetState,
        public readonly ?string $sessionUuid = null,
    ) {
        parent::__construct(
            "Invalid state transition from '{$currentState}' to '{$targetState}'" .
            ($sessionUuid ? " for session {$sessionUuid}" : '')
        );
    }
}
