<?php

namespace GeorgRinger\Audit\Audit\Seo;

use GeorgRinger\Audit\Audit\AbstractAudit;
use GeorgRinger\Audit\Audit\ReportInterface;
use Symfony\Component\DomCrawler\Crawler;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

class TitleStructure extends AbstractAudit implements ReportInterface
{
    public function init(): void
    {
        $crawler = new Crawler($this->subReponseData->content);
        $crawler = $crawler->filter('h1,h2,h3,h4,h5,h6');
        $elements = [];
        foreach ($crawler as $node) {

            $elements[] = ['level' => (int) (substr($node->nodeName, 1, 1)), 'label' => $this->trim($node->nodeValue)];
        }

        if (empty($elements)) {
            $this->report = '--';
            return;
        }

        $headings = $this->validate($elements);

        $output = [];
        $severity = ContextualFeedbackSeverity::OK;
        foreach ($headings as $heading) {
            $errorContent = $heading['error'] ? (' [' . $heading['error'] . ']') : '';
            if ($errorContent) {
                $severity = ContextualFeedbackSeverity::WARNING;
            }
            $output[] = sprintf('<li><span class="%s"><strong>h%s</strong>. %s%s</span>', $heading['error'] ? 'text-danger' : '', $heading['level'], htmlspecialchars($heading['label']), $errorContent);
        }
        $this->severity = $severity;

        $this->report = implode('', $output);
    }




    private function trim(string $value)
    {
        $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $stripped = strip_tags($decoded);
        return trim($stripped, " \t\n\r\0\x0B\xC2\xA0");
    }

    private function validate(array $elements): array
    {
        $currentLevel = 1;

        $result = [];

        foreach ($elements as $element) {
            $levelOfHeading = $element['level'];

            $error = '';
            if (empty($element['label'])) {
                $error = 'MISSING_LABEL';
            } elseif (!($levelOfHeading <= $currentLevel + 1)) {
                $error = 'INVALID_STRUCTURE';
            }

            $currentLevel = $levelOfHeading;
            $element['error'] = $error;
            $result[] = $element;
        }

        return $result;
    }

    public function getIdentifier(): string
    {
        return 'seo-title-structure';
        // TODO: Implement getIdentifier() method.
    }

    public function getTitle(): string
    {
        return 'Title Structure';
        // TODO: Implement getTitle() method.
    }

    public function getDescription(): string
    {
        return 'Check if the meta fields are set correctly';
        // TODO: Implement getDescription() method.
    }

    public function getIconIdentifier(): string
    {
        return 'audit-type-headline';
    }
}
