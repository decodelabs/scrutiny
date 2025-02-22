<?php
/**
 * This is a stub file for IDE compatibility only.
 * It should not be included in your projects.
 */
namespace DecodeLabs;

use DecodeLabs\Veneer\Proxy as Proxy;
use DecodeLabs\Veneer\ProxyTrait as ProxyTrait;
use DecodeLabs\Scrutiny\Context as Inst;
use DecodeLabs\Scrutiny\Config as Ref0;
use DecodeLabs\Scrutiny\Verifier as Ref1;
use DecodeLabs\Tagged\Markup as Ref2;
use DecodeLabs\Tagged\Element as Ref3;
use DecodeLabs\Scrutiny\Renderer as Ref4;
use Closure as Ref5;
use DecodeLabs\Scrutiny\Result as Ref6;
use DecodeLabs\Scrutiny\Payload as Ref7;

class Scrutiny implements Proxy
{
    use ProxyTrait;

    public const Veneer = 'DecodeLabs\\Scrutiny';
    public const VeneerTarget = Inst::class;

    protected static Inst $_veneerInstance;

    public static function setConfig(?Ref0 $config): void {}
    public static function getConfig(): ?Ref0 {
        return static::$_veneerInstance->getConfig();
    }
    public static function loadVerifier(?string $name = NULL, ?array $settings = NULL): Ref1 {
        return static::$_veneerInstance->loadVerifier(...func_get_args());
    }
    public static function tryLoadVerifier(?string $name = NULL, ?array $settings = NULL): ?Ref1 {
        return static::$_veneerInstance->tryLoadVerifier(...func_get_args());
    }
    public static function renderInline(?string $verifierName = NULL, ?string $nonce = NULL): ?Ref2 {
        return static::$_veneerInstance->renderInline(...func_get_args());
    }
    public static function render(?string $verifierName = NULL): ?Ref3 {
        return static::$_veneerInstance->render(...func_get_args());
    }
    public static function registerRenderer(string $verifierName, Ref4 $renderer): void {}
    public static function registerCustomRenderer(string $verifierName, Ref5 $renderer): void {}
    public static function registerDefaultRenderer(Ref4 $renderer): void {}
    public static function getRenderer(string $verifierName): Ref4 {
        return static::$_veneerInstance->getRenderer(...func_get_args());
    }
    public static function removeRenderer(string $verifierName): void {}
    public static function verify(?string $verifierName = NULL, array $values = [], ?string $action = NULL, ?float $scoreThreshold = NULL, ?int $timeout = NULL): Ref6 {
        return static::$_veneerInstance->verify(...func_get_args());
    }
    public static function verifyPayload(Ref7 $payload): Ref6 {
        return static::$_veneerInstance->verifyPayload(...func_get_args());
    }
    public static function addHostNames(string ...$hostNames): void {}
    public static function removeHostNames(string ...$hostNames): void {}
    public static function getHostNames(): array {
        return static::$_veneerInstance->getHostNames();
    }
    public static function prepareHostName(string $hostName): string {
        return static::$_veneerInstance->prepareHostName(...func_get_args());
    }
    public static function createPayload(?string $verifierName = NULL, array $values = [], ?string $action = NULL, ?float $scoreThreshold = NULL, ?int $timeout = NULL): Ref7 {
        return static::$_veneerInstance->createPayload(...func_get_args());
    }
};
