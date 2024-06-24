<?php

/**
 * @package Scrutiny
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Scrutiny\Verifier;

use DecodeLabs\Dictum;
use DecodeLabs\Scrutiny\Context;
use DecodeLabs\Scrutiny\Error;
use DecodeLabs\Scrutiny\Payload;
use DecodeLabs\Scrutiny\Result;
use DecodeLabs\Scrutiny\Verifier;
use DecodeLabs\Tagged\ViewAssetContainer;

class Compound implements Verifier
{
    /**
     * @var array<Verifier>
     */
    protected array $verifiers;

    /**
     * @param array<string|Verifier> $verifiers
     */
    public function __construct(
        array $verifiers,
        Context $context
    ) {
        foreach ($verifiers as $verifier) {
            if (is_string($verifier)) {
                $verifier = $context->tryLoadVerifier($verifier);
            }

            if (!$verifier instanceof Verifier) {
                continue;
            }

            $this->verifiers[] = $verifier;
        }
    }

    public function getName(): string
    {
        return 'Compound';
    }

    public function getDataKeys(): array
    {
        $output = [];

        foreach ($this->verifiers as $verifier) {
            $output = array_merge($output, $verifier->getDataKeys());
        }

        return $output;
    }

    public function getComponentData(): array
    {
        $output = [];

        foreach ($this->verifiers as $verifier) {
            $slug = Dictum::slug($verifier->getName());

            foreach ($verifier->getComponentData() as $key => $value) {
                $attr = str_replace(ltrim($key, ':@'), $slug . '-' . $key, $key);
                $output[$attr] = $value;
            }
        }

        return $output;
    }

    public function getInlineViewAssets(
        ?string $nonce = null
    ): ViewAssetContainer {
        if (!isset($this->verifiers[0])) {
            return new ViewAssetContainer();
        }

        return $this->verifiers[0]->getInlineViewAssets($nonce);
    }

    public function verify(
        Payload $payload
    ): Result {
        foreach ($this->verifiers as $verifier) {
            $keys = $verifier->getDataKeys();

            foreach ($keys as $key) {
                if (null === ($value = $payload->getValue($key))) {
                    continue 2;
                }
            }

            return $verifier->verify($payload);
        }

        return new Result(
            payload: $payload,
            errors: [
                Error::VerifierFailed
            ]
        );
    }
}
