<?php

/**
 * Scrutiny
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs;

use Closure;
use DecodeLabs\Dovetail\Config\Scrutiny as ScrutinyConfig;
use DecodeLabs\Kingdom\ContainerAdapter;
use DecodeLabs\Kingdom\Service;
use DecodeLabs\Kingdom\ServiceTrait;
use DecodeLabs\Scrutiny\Config;
use DecodeLabs\Scrutiny\Error;
use DecodeLabs\Scrutiny\Payload;
use DecodeLabs\Scrutiny\Renderer;
use DecodeLabs\Scrutiny\Renderer\Custom as CustomRenderer;
use DecodeLabs\Scrutiny\Renderer\Generic as GenericRenderer;
use DecodeLabs\Scrutiny\Result;
use DecodeLabs\Scrutiny\Verifier;

class Scrutiny implements Service
{
    use ServiceTrait;

    /**
     * @var array<string>
     */
    protected array $hostNames = [];

    protected ?Config $config = null;

    /**
     * @var array<string, Renderer>
     */
    protected array $renderers = [];

    public static function provideService(
        ContainerAdapter $container
    ): static {
        if (
            !$container->has(Config::class) &&
            class_exists(Dovetail::class)
        ) {
            $container->setType(Config::class, ScrutinyConfig::class);
        }

        return $container->getOrCreate(static::class);
    }

    public function __construct(
        ?Config $config = null
    ) {
        $this->config = $config;
    }

    /**
     * @param array<string,mixed> $settings
     */
    public function loadVerifier(
        ?string $name = null,
        ?array $settings = null
    ): Verifier {
        if (!$verifier = $this->tryLoadVerifier($name, $settings)) {
            throw Exceptional::{'./Scrutiny/NotFound'}(
                message: 'Verifier ' . $name . ' is not enabled'
            );
        }

        return $verifier;
    }

    /**
     * @param array<string,mixed> $settings
     */
    public function tryLoadVerifier(
        ?string $name = null,
        ?array $settings = null
    ): ?Verifier {
        if ($name === null) {
            $name = $this->config?->getFirstEnabledVerifier();

            if ($name === null) {
                return null;
            }
        }

        if ($settings === null) {
            $settings = $this->config?->getSettingsFor($name) ?? [];
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


    public function registerRenderer(
        string $verifierName,
        Renderer $renderer
    ): void {
        $this->renderers[$verifierName] = $renderer;
    }

    public function registerCustomRenderer(
        string $verifierName,
        Closure $renderer,
    ): void {
        $this->registerRenderer($verifierName, new CustomRenderer($renderer));
    }

    public function registerDefaultRenderer(
        Renderer $renderer
    ): void {
        $this->registerRenderer('Default', $renderer);
    }

    public function getRenderer(
        string $verifierName
    ): Renderer {
        return
            $this->renderers[$verifierName] ??
            $this->renderers['Default'] ??
            new GenericRenderer();
    }

    public function removeRenderer(
        string $verifierName
    ): void {
        unset($this->renderers[$verifierName]);
    }


    /**
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

    public function verifyPayload(
        Payload $payload
    ): Result {
        $name = $payload->verifierName;

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
     * @return array<string>
     */
    public function getHostNames(): array
    {
        return $this->hostNames;
    }

    public static function prepareHostName(
        string $hostName
    ): string {
        $hostName = (string)preg_replace('|^https?://|', '', $hostName);
        $hostName = trim($hostName, '/');
        return $hostName;
    }


    /**
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
