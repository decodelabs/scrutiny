<?php

/**
 * @package Scrutiny
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Scrutiny;

enum Error: string
{
    case VerifierNotFound = 'Verifier not found';
    case InvalidSecret = 'Invalid secret';
    case InvalidPayload = 'Invalid payload';
    case ConnectionFailed = 'Connection failed';
    case VerifierFailed = 'Verifier failed';
    case InvalidInput = 'Invalid input';
    case HostNameMismatch = 'Host name mismatch';
    case ActionMismatch = 'Action mismatch';
    case RiskThresholdExceeded = 'Risk threshold exceeded';
    case Timeout = 'Timeout';

    public function isReportable(
        Error $error
    ): bool {
        return match ($error) {
            self::VerifierNotFound,
            self::InvalidPayload,
            self::ConnectionFailed,
            self::InvalidInput,
            self::HostNameMismatch,
            self::ActionMismatch => true,
            default => false,
        };
    }
}
