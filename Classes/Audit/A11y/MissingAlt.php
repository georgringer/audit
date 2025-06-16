<?php

namespace GeorgRinger\Audit\Audit\A11y;

use GeorgRinger\Audit\Audit\AbstractAudit;
use Symfony\Component\DomCrawler\Crawler;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

class MissingAlt extends AbstractAudit
{

    public function init(): void
    {
        $list = [];
        $crawler = new Crawler($this->subReponseData->content);
        $crawler = $crawler->filter('img[alt=""],img:not([alt])');
        foreach ($crawler as $node) {

            $list[] = $node->getAttribute('src');
        }

        $this->report = implode('<br>', $list);
        if (!empty($list)) {
            $this->severity = ContextualFeedbackSeverity::WARNING;;
        }
    }

    public function getIdentifier(): string
    {
        return 'a11y-alt';
        // TODO: Implement getIdentifier() method.
    }

    public function getTitle(): string
    {
        return 'Missing alt';
        // TODO: Implement getTitle() method.
    }

    public function getDescription(): string
    {
        return 'missing alt';
        // TODO: Implement getDescription() method.
    }

    public function getIconIdentifier(): string
    {
        return '';
        // TODO: Implement getIconIdentifier() method.
    }

}
