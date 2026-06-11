<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

// New Content Element wizard groups mirroring the blocks.so overview
// (https://blocks.so/). Grafted elements carry the registry item's category
// as their Content Blocks group, so each family lands in its own wizard tab.
// Group keys equal the registry category keys; innesto:add reuses them as-is.
$position = 'before:default';

foreach ([
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
