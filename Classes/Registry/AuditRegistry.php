<?php

declare(strict_types=1);


namespace GeorgRinger\Audit\Registry;


use GeorgRinger\Audit\Audit\ReportInterface;

/**
 * Registry for status providers. The registry receives all services, tagged with "reports.status".
 * The tagging of status providers is automatically done based on the implemented StatusProviderInterface.
 *
 * @internal
 */
class AuditRegistry
{
    /**
     * @var ReportInterface[]
     */
    private array $providers = [];

    /**
     * @param iterable<ReportInterface> $providers
     */
    public function __construct(iterable $providers)
    {
        foreach ($providers as $item) {
            $this->providers[$item->getIdentifier()] = $item;
        }
    }

    /**
     * @return ReportInterface[]
     */
    public function getProviders(): array
    {
        return $this->providers;
    }

    public function getProvider(string $identifier): ?ReportInterface
    {
        return $this->providers[$identifier] ?? null;
    }
}
