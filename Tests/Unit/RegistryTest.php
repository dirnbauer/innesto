<?php

declare(strict_types=1);

namespace Webconsulting\Innesto\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\RequestFactory;
use Webconsulting\Innesto\Registry\CssConverter;
use Webconsulting\Innesto\Registry\ElementScaffolder;
use Webconsulting\Innesto\Registry\RegistryClient;
use Webconsulting\Innesto\Registry\SetRegistrar;

final class RegistryTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/innesto-test-' . bin2hex(random_bytes(8));
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->directory);
    }

    public function testScaffoldPreservesQuotedAndMultilineRegistryMetadata(): void
    {
        $item = ['name' => 'demo', 'title' => "Editor's \"card\"", 'description' => "First line\nNext: 'quoted' & <safe>"];
        (new ElementScaffolder(new CssConverter()))->scaffold($item, 'demo', $this->directory);
        $config = Yaml::parseFile($this->directory . '/demo/config.yaml');
        self::assertSame($item['title'], $config['title']);
        self::assertSame($item['description'], $config['description']);
        $labels = new \DOMDocument();
        self::assertTrue($labels->load($this->directory . '/demo/language/labels.xlf'));
    }

    #[DataProvider('invalidKeys')]
    public function testScaffoldRejectsInvalidKeysBeforeWriting(string $key): void
    {
        try {
            (new ElementScaffolder(new CssConverter()))->scaffold(['name' => 'demo'], $key, $this->directory . '/target');
            self::fail('Invalid element key was accepted.');
        } catch (\InvalidArgumentException) {
            self::assertSame([], glob($this->directory . '/*'));
        }
    }

    /**
     * @return list<array{string}>
     */
    public static function invalidKeys(): array
    {
        return [[''], ['../escape'], ['-'], ['Demo'], ['two--dashes']];
    }

    public function testScaffoldDoesNotOverwriteExistingElement(): void
    {
        $scaffolder = new ElementScaffolder(new CssConverter());
        $scaffolder->scaffold(['name' => 'demo'], 'demo', $this->directory);
        $before = file_get_contents($this->directory . '/demo/config.yaml');
        try {
            $scaffolder->scaffold(['name' => 'changed'], 'demo', $this->directory);
            self::fail('Existing element was overwritten.');
        } catch (\RuntimeException) {
            self::assertSame($before, file_get_contents($this->directory . '/demo/config.yaml'));
        }
    }

    public function testScaffoldDoesNotSilentlyOverwriteSourcesWithTheSameBasename(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new ElementScaffolder(new CssConverter()))->scaffold([
            'name' => 'demo',
            'files' => [
                ['path' => 'one/card.tsx', 'content' => 'first'],
                ['path' => 'two/card.tsx', 'content' => 'second'],
            ],
        ], 'demo', $this->directory);
    }

    public function testMalformedRegistryCssLeavesNoPartialScaffold(): void
    {
        try {
            (new ElementScaffolder(new CssConverter()))->scaffold([
                'name' => 'demo',
                'files' => [['path' => 'demo.tsx', 'content' => 'source']],
                'cssVars' => ['theme' => 'invalid'],
            ], 'demo', $this->directory);
            self::fail('Malformed CSS was accepted.');
        } catch (\TypeError) {
            self::assertDirectoryDoesNotExist($this->directory . '/demo');
        }
    }

    public function testDifferentFolderNamesCannotCreateTheSameCType(): void
    {
        $scaffolder = new ElementScaffolder(new CssConverter());
        $scaffolder->scaffold(['name' => 'demo-card'], 'demo-card', $this->directory);
        try {
            $scaffolder->scaffold(['name' => 'democard'], 'democard', $this->directory);
            self::fail('Duplicate CType was accepted.');
        } catch (\InvalidArgumentException) {
            self::assertDirectoryDoesNotExist($this->directory . '/democard');
            self::assertFileExists($this->directory . '/demo-card/config.yaml');
        }
    }

    #[DataProvider('siteSetFormats')]
    public function testSetRegistrationSupportsValidYamlAndIsIdempotent(string $yaml): void
    {
        $path = $this->directory . '/config.yaml';
        file_put_contents($path, $yaml);
        $registrar = new SetRegistrar();
        self::assertTrue($registrar->register($path, 'innesto/demo'));
        $config = Yaml::parseFile($path);
        self::assertContains('innesto/demo', $config['optionalDependencies']);
        self::assertSame(['webconsulting/desiderio'], $config['dependencies']);
        self::assertTrue($registrar->register($path, 'innesto/demo'));
        self::assertSame($config, Yaml::parseFile($path));
    }

    /**
     * @return list<array{string}>
     */
    public static function siteSetFormats(): array
    {
        $base = "name: webconsulting/innesto\ndependencies: [webconsulting/desiderio]\n";
        return [
            [$base],
            [$base . 'optionalDependencies: []'],
            [$base . "optionalDependencies:\n  - innesto/marquee"],
            [$base . 'optionalDependencies: [innesto/marquee]'],
            [$base . "optionalDependencies:\n  # Existing block\n  - innesto/marquee\n"],
        ];
    }

    public function testKnownRegistryReferencesAndExplicitUrlResolve(): void
    {
        $client = new RegistryClient(self::createStub(RequestFactory::class));
        self::assertSame('https://magicui.design/r/marquee.json', $client->resolveUrl('@magicui/marquee'));
        self::assertSame('https://blocks.so/r/stats-09.json', $client->resolveUrl('blocks/stats-09'));
        self::assertSame('https://example.com/demo.json', $client->resolveUrl('https://example.com/demo.json'));
        $this->expectException(\InvalidArgumentException::class);
        $client->resolveUrl('unknown/demo');
    }

    public function testCssKeepsTokensDarkModeAndNestedAnimations(): void
    {
        $css = (new CssConverter())->convert([
            'cssVars' => ['theme' => ['animate-spin' => 'spin 1s linear infinite'], 'dark' => ['primary' => 'var(--foreground)']],
            'css' => ['@keyframes spin' => ['to' => ['transform' => 'rotate(360deg)']]],
        ]);
        self::assertStringContainsString('animation: var(--animate-spin)', $css);
        self::assertStringContainsString('.dark {', $css);
        self::assertStringContainsString('--primary: var(--foreground)', $css);
        self::assertStringContainsString('@keyframes spin {', $css);
        self::assertStringContainsString('transform: rotate(360deg)', $css);
    }

    /**
     * @param array<string, mixed> $item
     */
    #[DataProvider('invalidRegistryItems')]
    public function testMalformedRegistryItemsAreRejected(array $item): void
    {
        $factory = self::createStub(RequestFactory::class);
        $factory->method('request')->willReturn(new JsonResponse($item));
        $this->expectException(\RuntimeException::class);
        (new RegistryClient($factory))->fetchItem('magicui/demo');
    }

    /**
     * @return list<array{array<string, mixed>}>
     */
    public static function invalidRegistryItems(): array
    {
        return [[[]], [['name' => 42]], [['name' => []]], [['name' => '  ']]];
    }
}
