<?php

/**
 * @package Scrutiny
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Scrutiny\Verifier;

use DecodeLabs\Coercion;
use DecodeLabs\Glitch\Attribute\SensitiveProperty;
use DecodeLabs\Hydro;
use DecodeLabs\Scrutiny\Error;
use DecodeLabs\Scrutiny\Payload;
use DecodeLabs\Scrutiny\Response;
use DecodeLabs\Scrutiny\Result;
use DecodeLabs\Scrutiny\Verifier;
use DecodeLabs\Tagged\Component\Scrutiny as ScrutinyComponent;
use DecodeLabs\Tagged\Element;
use SensitiveParameter;

abstract class SiteVerify implements Verifier
{
    protected const string VerifyUrl = 'https://example.com/api/siteverify';
    protected const string ApiUrl = 'https://example.com/api.js';
    protected const string ClientKeyName = 'captcha';
    protected const string ResponseFieldName = 'captcha-response';

    public array $dataKeys { get => [static::ResponseFieldName]; }

    public array $componentData {
        get => [
            'siteKey' => $this->siteKey
        ];
    }

    protected(set) string $siteKey;

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
     * Prepare component
     */
    public function prepareAssets(
        ScrutinyComponent $component
    ): void {
        $component->addClass(static::ClientKeyName);
        $component->setDataAttribute('sitekey', $this->siteKey);

        $component->addScript(
            key: 'api',
            priority: 10,
            attributes: [
                'src' => static::ApiUrl,
                'nonce' => $component->nonce,
                'async' => true,
                'defer' => true
            ]
        );
    }

    /**
     * Verify payload
     */
    public function verify(
        Payload $payload
    ): Result {
        $ip = $payload->ip;
        $key = static::ResponseFieldName;
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
            'url' => static::VerifyUrl,
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

        /** @var array<string,mixed> */
        $data = (array)json_decode((string)$httpResponse->getBody(), true);

        if (!($data['success'] ?? false)) {
            $errors = [];

            foreach (Coercion::asArray($data['error-codes'] ?? []) as $code) {
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
