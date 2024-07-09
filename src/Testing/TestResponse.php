<?php

declare(strict_types=1);

namespace Phenix\Testing;

use Amp\Http\Client\Response;
use Amp\Http\HttpStatus;

class TestResponse
{
    public readonly string $body;

    public function __construct(public Response $response)
    {
        $this->body = $response->getBody()->buffer();
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function assertOk(): self
    {
        expect($this->response->getStatus())->toBe(HttpStatus::OK);

        return $this;
    }

    public function assertNotFound(): self
    {
        expect($this->response->getStatus())->toBe(HttpStatus::NOT_FOUND);

        return $this;
    }

    public function assertNotAcceptable(): self
    {
        expect($this->response->getStatus())->toBe(HttpStatus::NOT_ACCEPTABLE);

        return $this;
    }

    public function assertUnprocessableEntity(): self
    {
        expect($this->response->getStatus())->toBe(HttpStatus::UNPROCESSABLE_ENTITY);

        return $this;
    }

    /**
     * @param array<int, string>|string $needles
     * @return self
     */
    public function assertBodyContains(array|string $needles): self
    {
        $needles = (array) $needles;

        expect($this->body)->toContain(...$needles);

        return $this;
    }

    public function assertHeaderContains(array $needles): self
    {
        $needles = (array) $needles;

        foreach ($needles as $header => $value) {
            expect($this->response->getHeader($header))->not->toBeNull();
            expect($this->response->getHeader($header))->toBe($value);
        }

        return $this;
    }
}
