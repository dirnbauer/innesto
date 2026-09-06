<?php

declare(strict_types=1);

namespace Webconsulting\Innesto\Tests\Fixtures;

use TYPO3\CMS\Core\DataHandling\DataHandler;

/** Simulates an extension rejecting a record after DataHandler has written it. */
final class RejectDemoWrite
{
    public function processDatamap_afterDatabaseOperations(string $status, string $table, int|string $id, array $fields, DataHandler $dataHandler): void
    {
        if ($table === 'tt_content') {
            $dataHandler->errorLog[] = 'Demo write rejected by the test fixture.';
        }
    }
}
