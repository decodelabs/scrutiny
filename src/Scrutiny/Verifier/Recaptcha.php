<?php

/**
 * @package Scrutiny
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Scrutiny\Verifier;

use DecodeLabs\Coercion;
use DecodeLabs\Scrutiny\Response;

class Recaptcha extends SiteVerify
{
    public const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';
    public const API_URL = 'https://www.google.com/recaptcha/api.js';
    public const CLIENT_KEY_NAME = 'g-recaptcha';
    public const RESPONSE_FIELD_NAME = 'g-recaptcha-response';

    public function getName(): string
    {
        return 'Recaptcha';
    }

    protected function createResponse(
        array $data
    ): Response {
        $score = null;

        if (null !== ($rawScore = Coercion::toFloatOrNull($data['score'] ?? null))) {
            $score = 1 - min(1, max(0, $rawScore));
        }

        return new Response(
            hostName: Coercion::toStringOrNull($data['hostname'] ?? null),
            action: Coercion::toStringOrNull($data['action'] ?? null),
            timestamp: Coercion::toDateTimeOrNull(
                Coercion::toStringOrNull($data['challenge_ts'] ?? null)
            ),
            score: $score,
            rawScore: $rawScore,
        );
    }
}
