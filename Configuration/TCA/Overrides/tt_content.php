<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

// New Content Element wizard groups for the grafted registry families. A graft
// carries the registry item's category as its Content Blocks group, so each
// family lands in its own wizard tab. Only the groups shipped elements use are
// registered; `components` is the scaffolder's fallback for registry items
// without a category.
$position = 'before:default';

foreach ([
    'components' => 'LLL:EXT:innesto/Resources/Private/Language/labels.xlf:contentElementGroup.components',
    'magic-ui' => 'LLL:EXT:innesto/Resources/Private/Language/labels.xlf:contentElementGroup.magicUi',
    'case-studies' => 'LLL:EXT:innesto/Resources/Private/Language/labels.xlf:contentElementGroup.caseStudies',
    'stats' => 'LLL:EXT:innesto/Resources/Private/Language/labels.xlf:contentElementGroup.stats',
] as $group => $label) {
    ExtensionManagementUtility::addTcaSelectItemGroup(
        'tt_content',
        'CType',
        $group,
        $label,
        $position,
    );
    $position = 'after:' . $group;
}
