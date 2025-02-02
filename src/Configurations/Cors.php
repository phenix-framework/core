<?php

declare(strict_types=1);

namespace Phenix\Configurations;

use Phenix\Facades\Config;

class Cors extends Configuration
{
    protected array|string $origins;
    protected array $allowedMethods;
    protected int $maxAge;
    protected array $allowedHeaders;
    protected array $exposableHeaders;
    protected bool $allowCredentials;

    public function __construct(array $config)
    {
        $this->origins = $config['origins'];
        $this->allowedMethods = $config['allowed_methods'];
        $this->maxAge = $config['max_age'];
        $this->allowedHeaders = $config['allowed_headers'];
        $this->exposableHeaders = $config['exposable_headers'];
        $this->allowCredentials = $config['allow_credentials'];
    }

    public static function build(): self
    {
        return new self(Config::get('cors'));
    }

    public function toArray(): array
    {
        return [
            'origins' => (array) $this->origins,
            'allowed_methods' => $this->allowedMethods,
            'max_age' => $this->maxAge,
            'allowed_headers' => $this->allowedHeaders,
            'exposable_headers' => $this->exposableHeaders,
            'allow_credentials' => $this->allowCredentials,
        ];
    }
}
