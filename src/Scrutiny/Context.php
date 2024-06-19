<?php

/**
 * @package Scrutiny
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Scrutiny;

use DecodeLabs\Dovetail;
use DecodeLabs\Dovetail\Config\Scrutiny as ScrutinyConfig;
use DecodeLabs\Exceptional;
use DecodeLabs\Scrutiny;
use DecodeLabs\Slingshot;
use DecodeLabs\Veneer;

class Context
{
    /**
     * @var array<string>
     */
    protected array $hostNames = [];

    protected ?Config $config = null;

    /**
     * Set config
     */
    public function setConfig(
        ?Config $config
    ): void {
        $this->config = $config;
    }

    /**
     * Get config
     */
    public function getConfig(): ?Config
    {
        if (
            $this->config === null &&
            class_exists(Dovetail::class)
        ) {
            $this->config = ScrutinyConfig::load();
        }

        return $this->config;
    }

    /**
     * Load verifier
     *
     * @param array<string, mixed> $config
     */
    public function loadVerifier(
        string $name,
        ?array $config = null
    ): Verifier {
        if ($config === null) {
            $config = $this->getConfig()?->getSettingsFor($name) ?? [];
        }

        if (
            isset($config['enabled']) &&
            $config['enabled'] === false
        ) {
            throw Exceptional::NotFound(
                'Verifier ' . $name . ' is not enabled'
            );
        }

        return (new Slingshot())
            ->addType($this)
            ->resolveNamedInstance(Verifier::class, $name, $config);
    }

    /**
     * Load default verifier
     */
    public function loadDefaultVerifier(): Verifier
    {
        $config = $this->getConfig();
        $name = $config?->getFirstEnabledVerifier();

        if ($name === null) {
            throw Exceptional::NotFound(
                'No verifiers are enabled'
            );
        }

        return $this->loadVerifier(
            $name,
            $config?->getSettingsFor($name) ?? []
        );
    }

    /**
     * Create payload and verify
     *
     * @param array<string, mixed> $values
     */
    public function verify(
        ?string $verifierName = null,
        array $values = [],
        ?string $action = null,
        ?float $scoreThreshold = null,
        ?int $timeout = null,
    ): Result {
        return $this->verifyPayload(
            $this->createPayload(
                verifierName: $verifierName,
                values: $values,
                action: $action,
                scoreThreshold: $scoreThreshold,
                timeout: $timeout,
            )
        );
    }

    /**
     * Verify prepared payload
     */
    public function verifyPayload(
        Payload $payload
    ): Result {
        $name = $payload->getVerifierName();

        try {
            if ($name === null) {
                $verifier = $this->loadDefaultVerifier();
            } else {
                $verifier = $this->loadVerifier($name);
            }
        } catch (NotFoundException $e) {
            return new Result(
                payload: $payload,
                errors: [
                    Error::VerifierNotFound
                ]
            );
        }

        return $verifier->verify($payload);
    }


    /**
     * Add host name
     */
    public function addHostNames(
        string ...$hostNames
    ): void {
        foreach ($hostNames as $hostName) {
            $hostName = $this->prepareHostName($hostName);

            if (!in_array($hostName, $this->hostNames)) {
                $this->hostNames[] = $hostName;
            }
        }
    }

    /**
     * Remove host name
     */
    public function removeHostNames(
        string ...$hostNames
    ): void {
        foreach ($hostNames as $hostName) {
            $hostName = $this->prepareHostName($hostName);
            $key = array_search($hostName, $this->hostNames);

            if ($key !== false) {
                unset($this->hostNames[$key]);
            }
        }
    }

    /**
     * Get host names
     *
     * @return array<string>
     */
    public function getHostNames(): array
    {
        return $this->hostNames;
    }

    /**
     * Prepare host name
     */
    public static function prepareHostName(
        string $hostName
    ): string {
        $hostName = (string)preg_replace('|^https?://|', '', $hostName);
        $hostName = trim($hostName, '/');
        return $hostName;
    }


    /**
     * Create payload
     *
     * @param array<string, mixed> $values
     */
    public function createPayload(
        ?string $verifierName = null,
        array $values = [],
        ?string $action = null,
        ?float $scoreThreshold = null,
        ?int $timeout = null,
    ): Payload {
        return new Payload(
            verifierName: $verifierName,
            values: $values,
            hostNames: $this->hostNames,
            action: $action,
            scoreThreshold: $scoreThreshold,
            timeout: $timeout,
        );
    }
}

// Register the Veneer facade
Veneer::register(Context::class, Scrutiny::class);
