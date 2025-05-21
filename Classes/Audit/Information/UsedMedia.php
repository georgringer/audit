<?php

namespace GeorgRinger\Audit\Audit\Information;

use GeorgRinger\Audit\Audit\AbstractAudit;
use GeorgRinger\Audit\Audit\ReportInterface;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class UsedMedia extends AbstractAudit implements ReportInterface
{

    public function init(): void
    {
        $media = $this->subReponseData->headers['X-Used-Media'][0] ?? '';
        if (empty($media)) {
            $this->report = '';
            return;
        }
        $data = json_decode($media, true);
        $out = [];
        foreach ($data as $img) {
            $out[] = $img['name'];
        }

        $this->report = implode('<br> ', $out);
        // TODO: Implement getReport() method.
    }

    public function getIdentifier(): string
    {
        return 'info-used-media';
    }

    public function getTitle(): string
    {
        return 'used media';
        // TODO: Implement getTitle() method.
    }

    public function getDescription(): string
    {
        return 'media';
        // TODO: Implement getDescription() method.
    }

    public function getIconIdentifier(): string
    {
        return 'audit-type-image';
        // TODO: Implement getIconIdentifier() method.
    }

}
