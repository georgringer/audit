<?php
declare(strict_types=1);

namespace GeorgRinger\Audit\Event;

use Psr\Http\Message\ServerRequestInterface;

final class AuditProviderEvent
{

    protected string $title = '';
    protected string $description = '';
    protected string $content = '';
    protected int $severity = 0;

    public function __construct(
        protected string $table,
        protected int $recordId,
        protected bool $isOverview,
    ) {

    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getRecordId(): int
    {
        return $this->recordId;
    }

    public function isOverview(): bool
    {
        return $this->isOverview;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getSeverity(): int
    {
        return $this->severity;
    }

    public function setSeverity(int $severity): void
    {
        $this->severity = $severity;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }



}
