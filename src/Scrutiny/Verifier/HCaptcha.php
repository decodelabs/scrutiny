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
    protected const string VerifyUrl = 'https://api.hcaptcha.com/siteverify';
    protected const string ApiUrl = 'https://hcaptcha.com/1/api.js';
    protected const string ClientKeyName = 'h-captcha';
    protected const string ResponseFieldName = 'h-captcha-response';

    public string $name { get => 'HCaptcha'; }

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
