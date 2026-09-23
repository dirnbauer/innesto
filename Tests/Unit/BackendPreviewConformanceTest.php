<?php

declare(strict_types=1);

namespace Webconsulting\Innesto\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The page module previews speak the editor's language and share Desiderio's
 * preview vocabulary: translated UID and page chips, no hard-coded English
 * labels, and every label key the previews use exists in English and German.
 */
final class BackendPreviewConformanceTest extends TestCase
{
    private const string ROOT = __DIR__ . '/../..';
    private const string PREVIEW_LABELS = 'LLL:EXT:innesto/Resources/Private/Language/preview.xlf:';

    /**
     * @return iterable<string, array{string}>
     */
    public static function previews(): iterable
    {
        foreach (glob(self::ROOT . '/ContentBlocks/ContentElements/*/templates/backend-preview.fluid.html') ?: [] as $file) {
            yield basename(dirname($file, 2)) => [$file];
        }
    }

    #[Test]
    #[DataProvider('previews')]
    public function previewsUseTranslatedLabelsOnly(string $file): void
    {
        $template = (string)file_get_contents($file);

        self::assertStringContainsString('<f:layout name="Preview"/>', $template);
        self::assertStringContainsString('EXT:desiderio/Resources/Public/Css/content-preview.css', $template);
        self::assertStringContainsString('labels.xlf:preview.uid', $template, 'The UID chip is translated');
        self::assertDoesNotMatchRegularExpression('/d-ce-preview__label">\s*[A-Za-z]/', $template, 'Field labels come from XLIFF');
        self::assertDoesNotMatchRegularExpression('/>\s*UID:/', $template);
    }

    #[Test]
    public function everyPreviewLabelExistsInEnglishAndGerman(): void
    {
        $used = [];
        foreach (self::previews() as [$file]) {
            preg_match_all('/' . preg_quote(self::PREVIEW_LABELS, '/') . '([A-Za-z]+)/', (string)file_get_contents($file), $matches);
            $used = [...$used, ...$matches[1]];
        }
        $used = array_values(array_unique($used));
        sort($used);

        self::assertNotSame([], $used);
        foreach (['preview.xlf', 'de.preview.xlf'] as $name) {
            preg_match_all('/<unit id="([^"]+)">/', (string)file_get_contents(self::ROOT . '/Resources/Private/Language/' . $name), $matches);
            self::assertSame([], array_values(array_diff($used, $matches[1])), $name . ' defines every label the previews use');
        }
        self::assertStringContainsString('<target>', (string)file_get_contents(self::ROOT . '/Resources/Private/Language/de.preview.xlf'));
    }
}
