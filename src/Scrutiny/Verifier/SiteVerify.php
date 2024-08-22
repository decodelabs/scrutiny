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
use DecodeLabs\Tagged\Asset\RemoteScript;
use DecodeLabs\Tagged\Element;
use DecodeLabs\Tagged\ViewAssetContainer;
use SensitiveParameter;

abstract class SiteVerify implements Verifier
{
    protected const VerifyUrl = 'https://example.com/api/siteverify';
    protected const ApiUrl = 'https://example.com/api.js';
    protected const ClientKeyName = 'captcha';
    protected const ResponseFieldName = 'captcha-response';

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
     * Get site key
     */
    public function getSiteKey(): string
    {
        return $this->siteKey;
    }

    /**
     * @return array<string>
     */
    public function getDataKeys(): array
    {
        return [static::ResponseFieldName];
    }


    /**
     * Get assets for inline render
     */
    public function getInlineViewAssets(
        ?string $nonce = null
    ): ViewAssetContainer {
        $output = new ViewAssetContainer();

        $output->addHeadJs(new RemoteScript(
            priority: 10,
            src: static::ApiUrl,
            attributes: [
                'nonce' => $nonce,
                'async' => true,
                'defer' => true
            ]
        ));

        $output->setContent(new Element('div', null, [
            'class' => static::ClientKeyName,
            'data-sitekey' => $this->siteKey
        ]));

        return $output;
    }

    /**
     * Get component data
     */
    public function getComponentData(): array
    {
        return [
            'siteKey' => $this->siteKey
        ];
    }

    /**
     * Verify payload
     */
    public function verify(
        Payload $payload
    ): Result {
        $ip = $payload->getIp();
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
