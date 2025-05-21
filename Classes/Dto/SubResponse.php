<?php
declare(strict_types=1);

namespace GeorgRinger\Audit\Dto;

final readonly class SubResponse
{

    public function __construct(
        public string $content,
        public array $headers
    ) {}

}
