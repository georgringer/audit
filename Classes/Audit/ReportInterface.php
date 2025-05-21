<?php

namespace GeorgRinger\Audit\Audit;

use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

interface ReportInterface
{
    public function init(): void;
    /**
     * Returns the content for a report
     */
    public function getReport(): string;

    /**
     * Returns unique identifier of the report
     */
    public function getIdentifier(): string;

    /**
     * Returns title of the report
     */
    public function getTitle(): string;

    /**
     * Returns description of the report
     */
    public function getDescription(): string;


}
