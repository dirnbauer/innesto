<?php

declare(strict_types=1);

namespace Dirnbauer\Innesto\Registry;

use TYPO3\CMS\Core\Http\RequestFactory;

/**
 * Fetches shadcn registry-item JSON from any registry following the
 * https://ui.shadcn.com/schema/registry-item.json schema — i.e. every
 * registry cataloged on https://registry.directory/.
 */
final class RegistryClient
{
    /**
     * Shorthand → item URL template. "magicui/marquee" resolves against this
     * map; anything starting with http(s) is used verbatim.
     */
    private const REGISTRIES = [
        'shadcn' => 'https://ui.shadcn.com/r/%s.json',
        'magicui' => 'https://magicui.design/r/%s.json',
    ];

    public function __construct(private readonly RequestFactory $requestFactory)
    {
    }

    /**
     * @return array<string, mixed> the decoded registry item
     */
    public function fetchItem(string $reference): array
    {
        $url = $this->resolveUrl($reference);
        $response = $this->requestFactory->request($url, 'GET', [
            'headers' => ['Accept' => 'application/json'],
        ]);
        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException(
                sprintf('Registry returned HTTP %d for %s', $response->getStatusCode(), $url),
                1765432101
            );
        }
        $item = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($item) || !isset($item['name'])) {
            throw new \RuntimeException('Response is not a registry item (missing "name"): ' . $url, 1765432102);
        }
        return $item;
    }

    public function resolveUrl(string $reference): string
    {
        if (str_starts_with($reference, 'http://') || str_starts_with($reference, 'https://')) {
            return $reference;
        }
        if (str_contains($reference, '/')) {
            [$registry, $item] = explode('/', $reference, 2);
            $template = self::REGISTRIES[$registry] ?? null;
            if ($template !== null) {
                return sprintf($template, $item);
            }
        }
        throw new \InvalidArgumentException(
            sprintf(
                'Cannot resolve "%s". Pass a full item URL or one of: %s',
                $reference,
                implode(', ', array_map(static fn(string $k): string => $k . '/<item>', array_keys(self::REGISTRIES)))
            ),
            1765432103
        );
    }
}
