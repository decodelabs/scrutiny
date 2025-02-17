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
    public string $name { get => 'Compound'; }

    public array $dataKeys {
        get {
            $output = [];

            foreach ($this->verifiers as $verifier) {
                $output = array_merge($output, $verifier->dataKeys);
            }

            return $output;
        }
    }

    public array $componentData {
        get {
            $output = [];

            foreach ($this->verifiers as $verifier) {
                $slug = Dictum::slug($verifier->name);

                foreach ($verifier->componentData as $key => $value) {
                    $attr = str_replace(ltrim($key, ':@'), $slug . '-' . $key, $key);
                    $output[$attr] = $value;
                }
            }

            return $output;
        }
    }

    /**
     * @var list<Verifier>
     */
    protected array $verifiers;

    /**
     * @param list<string|Verifier> $verifiers
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

    public function prepareInlineViewAssets(
        ?string $nonce = null
    ): ViewAssetContainer {
        if (!isset($this->verifiers[0])) {
            return new ViewAssetContainer();
        }

        return $this->verifiers[0]->prepareInlineViewAssets($nonce);
    }

    public function verify(
        Payload $payload
    ): Result {
        foreach ($this->verifiers as $verifier) {
            $keys = $verifier->dataKeys;

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
