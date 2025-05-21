<?php

declare(strict_types=1);

namespace GeorgRinger\Audit\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Frontend\Event\ShouldUseCachedPageDataIfAvailableEvent;

#[AsEventListener(
    identifier: 'ext-audit/should-use-cached-page-data-if-available-event-listener',
)]
class ShouldUseCachedPageDataIfAvailableEventListener {

    public function __invoke(ShouldUseCachedPageDataIfAvailableEvent $event)
    {
        $request = $event->getRequest();
        if ($request->getAttribute('subRequestForAudit')) {
            $event->setShouldUseCachedPageData(false);
        }
    }
}