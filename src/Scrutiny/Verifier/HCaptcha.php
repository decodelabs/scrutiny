<?php

/**
 * @package Scrutiny
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Scrutiny\Verifier;

use DecodeLabs\Coercion;
use DecodeLabs\Scrutiny\Response;

class HCaptcha extends SiteVerify
{
    public const VERIFY_URL = 'https://api.hcaptcha.com/siteverify';
    public const API_URL = 'https://hcaptcha.com/1/api.js';
    public const CLIENT_FIELD_NAME = 'h-captcha';
    public const RESPONSE_FIELD_NAME = 'h-captcha-response';

    protected function createResponse(
        array $data
    ): Response {
        $score = null;

        if (null !== ($rawScore = Coercion::toFloatOrNull($data['score'] ?? null))) {
            $score = min(1, max(0, $rawScore));
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
