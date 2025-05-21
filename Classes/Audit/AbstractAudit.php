<?php

namespace GeorgRinger\Audit\Audit;
use GeorgRinger\Audit\Dto\SubResponse;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

abstract class AbstractAudit implements ReportInterface
{

    protected SubResponse $subReponseData;
    protected string $report = '';
    protected ?ContextualFeedbackSeverity $severity = null;

    public function setSubResponseResult(SubResponse $content): void
    {
        $this->subReponseData = $content;
    }

    public function getSeverity(): ?ContextualFeedbackSeverity
    {
        return $this->severity;
    }

    public function getReport(): string
    {
        return $this->report;
    }




}
