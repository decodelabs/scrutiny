<?php

/**
 * @package Scrutiny
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Scrutiny;

class Result
{
    protected(set) Payload $payload;
    protected(set) ?Response $response = null;

    /**
     * @var list<Error>
     */
    protected(set) array $errors = [];

    /**
     * Init with payload
     *
     * @param list<Error> $errors
     */
    public function __construct(
        Payload $payload,
        ?Response $response = null,
        array $errors = []
    ) {
        $this->payload = $payload;
        $this->response = $response;
        $this->errors = $errors;

        // Host name
        if (false === $this->payload->validateHostName(
            $this->response?->hostName
        )) {
            $this->errors[] = Error::HostNameMismatch;
        }

        // Action
        if (false === $this->payload->validateAction(
            $this->response?->action
        )) {
            $this->errors[] = Error::ActionMismatch;
        }

        // Threshold
        if (false === $this->payload->validateScoreThreshold(
            $this->response?->score
        )) {
            $this->errors[] = Error::RiskThresholdExceeded;
        }

        // Timeout
        if (false === $this->payload->validateTimeout(
            $this->response?->timestamp
        )) {
            $this->errors[] = Error::Timeout;
        }

        $this->errors = array_values(
            array_unique($this->errors, SORT_REGULAR)
        );
    }

    /**
     * Is valid
     */
    public function isValid(): bool
    {
        return empty($this->errors);
    }
}
