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
    protected const VerifyUrl = 'https://api.hcaptcha.com/siteverify';
    protected const ApiUrl = 'https://hcaptcha.com/1/api.js';
    protected const ClientKeyName = 'h-captcha';
    protected const ResponseFieldName = 'h-captcha-response';

    public function getName(): string
    {
        return 'HCaptcha';
    }

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
