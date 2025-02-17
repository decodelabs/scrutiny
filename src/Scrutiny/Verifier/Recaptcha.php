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
    protected const string VerifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
    protected const string ApiUrl = 'https://www.google.com/recaptcha/api.js';
    protected const string ClientKeyName = 'g-recaptcha';
    protected const string ResponseFieldName = 'g-recaptcha-response';

    public string $name { get => 'Recaptcha'; }

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
