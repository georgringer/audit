<?php

namespace GeorgRinger\Audit\Audit\Seo;

use GeorgRinger\Audit\Audit\AbstractAudit;
use GeorgRinger\Audit\Audit\ReportInterface;
use Symfony\Component\DomCrawler\Crawler;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

class MetaFields extends AbstractAudit implements ReportInterface
{

    public function init(): void
    {
        $list = [];
        $crawler = new Crawler($this->subReponseData->content);
        $crawler = $crawler->filter('title');
        foreach ($crawler as $node) {
            $tagName = $node->nodeName;
            $this->report = $node->textContent;
//            $list[] = sprintf('%s: %s', $textContent);
        }

//        return implode('<br>', $list);

    }

    public function getIdentifier(): string
    {
        return 'seo-metafields';
        // TODO: Implement getIdentifier() method.
    }

    public function getTitle(): string
    {
        return 'Title';
        // TODO: Implement getTitle() method.
    }

    public function getDescription(): string
    {
        return 'Check if the meta fields are set correctly';
        // TODO: Implement getDescription() method.
    }

    public function getIconIdentifier(): string
    {
        return '';
        // TODO: Implement getIconIdentifier() method.
    }


}
