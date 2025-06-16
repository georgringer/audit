<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace GeorgRinger\Audit\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final readonly class DataCollectResponseHeader implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $imagesOnPage = [];

        foreach (GeneralUtility::makeInstance(AssetCollector::class)->getMedia() as $file => $information) {
            $filePath = Environment::getPublicPath() . '/' . ltrim(parse_url($file, PHP_URL_PATH), '/');
            $fileSize = is_file($filePath) ? filesize($filePath) : 0;
            $imagesOnPage[] = [
                'name' => $file,
                'size' => $fileSize,
            ];
        }

        $response = $response->withHeader('X-Used-Media', json_encode($imagesOnPage));

        return $response;
    }
}
