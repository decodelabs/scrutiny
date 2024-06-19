<?php

/**
 * @package Scrutiny
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Scrutiny\Verifier;

use DecodeLabs\Glitch\Attribute\SensitiveProperty;
use DecodeLabs\Hydro;
use DecodeLabs\Scrutiny\Error;
use DecodeLabs\Scrutiny\Payload;
use DecodeLabs\Scrutiny\Response;
use DecodeLabs\Scrutiny\Result;
use DecodeLabs\Scrutiny\Verifier;
use SensitiveParameter;

abstract class SiteVerify implements Verifier
{
    public const VERIFY_URL = 'https://example.com/api/siteverify';
    public const API_URL = 'https://example.com/api.js';
    public const CLIENT_FIELD_NAME = 'captcha';
    public const RESPONSE_FIELD_NAME = 'captcha-response';

    protected string $siteKey;

    #[SensitiveProperty]
    protected string $secret;

    /**
     * Init with config
     */
    public function __construct(
        string $siteKey,
        #[SensitiveParameter]
        string $secret
    ) {
        $this->siteKey = $siteKey;
        $this->secret = $secret;
    }

    /**
     * Verify payload
     */
    public function verify(
        Payload $payload
    ): Result {
        $ip = $payload->getIp();
        $key = static::RESPONSE_FIELD_NAME;
        $value = $payload->getValue($key);

        if ($value === null) {
            return new Result(
                payload: $payload,
                errors: [
                    Error::InvalidPayload
                ]
            );
        }

        $httpResponse = Hydro::request('POST', [
            'url' => static::VERIFY_URL,
            'form_params' => [
                'secret' => $this->secret,
                'response' => $value,
                'remoteIp' => (string)$ip
            ]
        ]);

        if ($httpResponse->getStatusCode() !== 200) {
            return new Result(
                payload: $payload,
                errors: [
                    match ($httpResponse->getStatusCode()) {
                        404,
                        500 => Error::VerifierFailed,
                        default => Error::InvalidInput
                    }
                ]
            );
        }

        $data = (array)json_decode((string)$httpResponse->getBody(), true);

        if (!($data['success'] ?? false)) {
            $errors = [];

            foreach ($data['error-codes'] ?? [] as $code) {
                $errors[] = match ($code) {
                    'missing-input-response' => Error::InvalidPayload,
                    'invalid-input-response' => Error::InvalidInput,
                    'invalid-input-secret',
                    'missing-input-secret' => Error::InvalidSecret,
                    'timeout-or-duplicate' => Error::Timeout,
                    default => Error::VerifierFailed
                };
            }

            return new Result(
                payload: $payload,
                errors: $errors
            );
        }

        return new Result(
            $payload,
            $this->createResponse($data)
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    abstract protected function createResponse(
        array $data
    ): Response;
}
