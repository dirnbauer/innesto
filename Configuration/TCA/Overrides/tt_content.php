<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

// New Content Element wizard groups for grafted registry families. Grafted
// elements carry the registry item's category as their Content Blocks group, so
// each family lands in its own wizard tab.
// Group keys equal registry category keys where the source provides them.
$position = 'before:default';

foreach ([
    'components' => 'LLL:EXT:innesto/Resources/Private/Language/labels.xlf:contentElementGroup.components',
    'magic-ui' => 'LLL:EXT:innesto/Resources/Private/Language/labels.xlf:contentElementGroup.magicUi',
    'case-studies' => 'LLL:EXT:innesto/Resources/Private/Language/labels.xlf:contentElementGroup.caseStudies',
    'ai' => 'LLL:EXT:innesto/Resources/Private/Language/labels.xlf:contentElementGroup.ai',
    'command-menu' => 'LLL:EXT:innesto/Resources/Private/Language/labels.xlf:contentElementGroup.commandMenu',
    'dialogs' => 'LLL:EXT:innesto/Resources/Private/Language/labels.xlf:contentElementGroup.dialogs',
    'file-upload' => 'LLL:EXT:innesto/Resources/Private/Language/labels.xlf:contentElementGroup.fileUpload',
    'form-layout' => 'LLL:EXT:innesto/Resources/Private/Language/labels.xlf:contentElementGroup.formLayout',
    'grid-list' => 'LLL:EXT:innesto/Resources/Private/Language/labels.xlf:contentElementGroup.gridList',
    'login' => 'LLL:EXT:innesto/Resources/Private/Language/labels.xlf:contentElementGroup.login',
    'onboarding' => 'LLL:EXT:innesto/Resources/Private/Language/labels.xlf:contentElementGroup.onboarding',
    'sidebar' => 'LLL:EXT:innesto/Resources/Private/Language/labels.xlf:contentElementGroup.sidebar',
    'stats' => 'LLL:EXT:innesto/Resources/Private/Language/labels.xlf:contentElementGroup.stats',
    'tables' => 'LLL:EXT:innesto/Resources/Private/Language/labels.xlf:contentElementGroup.tables',
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
