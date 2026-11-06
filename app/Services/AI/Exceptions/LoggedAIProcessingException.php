<?php

namespace App\Services\AI\Exceptions;

use RuntimeException;

/**
 * Internal marker: a provider failure has already been sanitized and logged.
 * It must not produce a second outer-boundary error log.
 */
class LoggedAIProcessingException extends RuntimeException
{
}
