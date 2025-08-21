<?php

/**
 * @package Scrutiny
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Tagged\Component;

use DecodeLabs\Horizon\Property\BodyScriptCollection;
use DecodeLabs\Horizon\Property\BodyScriptCollectionTrait;
use DecodeLabs\Horizon\Property\LinkCollection;
use DecodeLabs\Horizon\Property\LinkCollectionTrait;
use DecodeLabs\Horizon\Property\ScriptCollection;
use DecodeLabs\Horizon\Property\ScriptCollectionTrait;
use DecodeLabs\Monarch;
use DecodeLabs\Scrutiny as ScrutinyLib;
use DecodeLabs\Scrutiny\Verifier;
use DecodeLabs\Tagged\Buffer;
use DecodeLabs\Tagged\Component;
use DecodeLabs\Tagged\ContentCollection;
use DecodeLabs\Tagged\Markup;
use DecodeLabs\Tagged\RenderableTrait;
use DecodeLabs\Tagged\Tag;
use ReflectionClass;

class Scrutiny extends Tag implements
    Component,
    BodyScriptCollection,
    LinkCollection,
    ScriptCollection
{
    use BodyScriptCollectionTrait;
    use LinkCollectionTrait;
    use ScriptCollectionTrait;
    use RenderableTrait;

    public ?Markup $content = null;

    public protected(set) ?string $nonce = null;
    public protected(set) Verifier $verifier;

    /**
     * @param array<string,mixed>|null $attributes
     * @param array<string,mixed>|null $settings
     */
    public function __construct(
        string|Verifier|null $verifier = null,
        ?array $settings = null,
        ?string $nonce = null,
        ?array $attributes = null
    ) {
        parent::__construct('div', $attributes);

        if (!$verifier instanceof Verifier) {
            $scrutiny = Monarch::getService(ScrutinyLib::class);

            $verifier = $scrutiny->loadVerifier(
                name: $verifier,
                settings: $settings
            );
        }

        $this->nonce = $nonce;
        $this->verifier = $verifier;
    }


    public function render(
        bool $pretty = false
    ): ?Buffer {
        $verifierName = (new ReflectionClass($this->verifier))->getShortName();
        $scrutiny = Monarch::getService(ScrutinyLib::class);
        $renderer = $scrutiny->getRenderer($verifierName);
        $output = $renderer->render($this->verifier);
        $output->setAttributes($this->getAttributes());
        return $output->render($pretty);
    }

    public function renderInline(
        bool $pretty = false
    ): ?Buffer {
        return ContentCollection::normalize(function () use ($pretty) {
            $inlineTag = $this->renderInlineTag($pretty);

            yield from $this->getLinks();
            yield from $this->getScripts();
            yield from $this->getBodyScripts();

            yield $inlineTag;
        }, $pretty);
    }

    public function renderInlineTag(
        bool $pretty = false
    ): ?Buffer {
        $this->verifier->prepareAssets($this);
        return $this->renderWith($this->content, $pretty);
    }
}
