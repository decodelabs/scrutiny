<?php

/**
 * @package Scrutiny
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Scrutiny;

use DateTimeInterface;

class Response
{
    protected(set) ?int $timestamp;
    protected(set) ?string $hostName;
    protected(set) ?string $action;
    protected(set) ?float $score;
    protected(set) int|float|string|null $rawScore;

    public function __construct(
        ?string $hostName = null,
        ?string $action = null,
        int|DateTimeInterface|null $timestamp = null,
        ?float $score = null,
        int|float|string|null $rawScore = null
    ) {
        if ($timestamp instanceof DateTimeInterface) {
            $timestamp = $timestamp->getTimestamp();
        }

        $this->hostName = $hostName;
        $this->action = $action;
        $this->timestamp = $timestamp;
        $this->score = $score;
        $this->rawScore = $rawScore;
    }
}
