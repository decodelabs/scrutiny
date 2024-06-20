<?php

/**
 * @package Scrutiny
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Scrutiny;

use Closure;
use DecodeLabs\Dovetail;
use DecodeLabs\Dovetail\Config\Scrutiny as ScrutinyConfig;
use DecodeLabs\Exceptional;
use DecodeLabs\Scrutiny;
use DecodeLabs\Scrutiny\Renderer\Custom as CustomRenderer;
use DecodeLabs\Scrutiny\Renderer\Generic as GenericRenderer;
use DecodeLabs\Slingshot;
use DecodeLabs\Tagged\Element;
use DecodeLabs\Tagged\Markup;
use DecodeLabs\Veneer;
use ReflectionClass;

class Context
{
    /**
     * @var array<string>
     */
    protected array $hostNames = [];

    protected ?Config $config = null;

    /**
     * @var array<string, Renderer>
     */
    protected array $renderers = [];

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
     * @param array<string, mixed> $settings
     */
    public function loadVerifier(
        ?string $name = null,
        ?array $settings = null
    ): Verifier {
        if (!$verifier = $this->tryLoadVerifier($name, $settings)) {
            throw Exceptional::NotFound(
                'Verifier ' . $name . ' is not enabled'
            );
        }

        return $verifier;
    }

    /**
     * Try load verifier
     *
     * @param array<string, mixed> $settings
     */
    public function tryLoadVerifier(
        ?string $name = null,
        ?array $settings = null
    ): ?Verifier {
        $config = $this->getConfig();

        if ($name === null) {
            $name = $config?->getFirstEnabledVerifier();

            if ($name === null) {
                return null;
            }
        }

        if ($settings === null) {
            $settings = $config?->getSettingsFor($name) ?? [];
        }

        if (
            isset($settings['enabled']) &&
            $settings['enabled'] === false
        ) {
            return null;
        }

        return (new Slingshot())
            ->addType($this)
            ->resolveNamedInstance(Verifier::class, $name, $settings);
    }


    /**
     * Render inline
     */
    public function renderInline(
        ?string $verifierName = null,
        ?string $nonce = null
    ): ?Markup {
        if (!$verifier = $this->tryLoadVerifier($verifierName)) {
            return null;
        }

        $assets = $verifier->getInlineViewAssets($nonce);
        return $assets->renderInline();
    }

    /**
     * Render component
     */
    public function render(
        ?string $verifierName = null
    ): ?Element {
        if (!$verifier = $this->tryLoadVerifier($verifierName)) {
            return null;
        }

        $verifierName = (new ReflectionClass($verifier))->getShortName();
        return $this->getRenderer($verifierName)->render($verifier);
    }

    /**
     * Register renderer
     */
    public function registerRenderer(
        string $verifierName,
        Renderer $renderer
    ): void {
        $this->renderers[$verifierName] = $renderer;
    }

    /**
     * Register custom renderer
     */
    public function registerCustomRenderer(
        string $verifierName,
        Closure $renderer,
    ): void {
        $this->registerRenderer($verifierName, new CustomRenderer($renderer));
    }

    /**
     * Register default renderer
     */
    public function registerDefaultRenderer(
        Renderer $renderer
    ): void {
        $this->registerRenderer('Default', $renderer);
    }

    /**
     * Get renderer
     */
    public function getRenderer(
        string $verifierName
    ): Renderer {
        return
            $this->renderers[$verifierName] ??
            $this->renderers['Default'] ??
            new GenericRenderer();
    }

    /**
     * Remove renderer
     */
    public function removeRenderer(
        string $verifierName
    ): void {
        unset($this->renderers[$verifierName]);
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

        if (!$verifier = $this->tryLoadVerifier($name)) {
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
