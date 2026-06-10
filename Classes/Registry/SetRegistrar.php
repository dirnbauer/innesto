<?php

declare(strict_types=1);

namespace Webconsulting\Innesto\Registry;

use Symfony\Component\Yaml\Yaml;

/**
 * Registers a grafted content block in the Innesto site set. Content Blocks
 * exposes every content block as a virtual site set named after the block
 * (e.g. "innesto/marquee"). As soon as ANY content block in an installation
 * is referenced that way — Desiderio references all of its 255 elements —
 * the New Content Element wizard switches to allow-list mode per site and
 * hides every block that is not listed. Appending the block name to the set's
 * optionalDependencies is therefore a mandatory part of every graft.
 */
final class SetRegistrar
{
    /**
     * @return bool true if the block is (now) registered in the set config
     */
    public function register(string $setConfigPath, string $blockName): bool
    {
        if (!is_file($setConfigPath)) {
            return false;
        }
        $content = (string)file_get_contents($setConfigPath);
        $parsed = Yaml::parse($content);
        if (!is_array($parsed)) {
            return false;
        }
        if (in_array($blockName, (array)($parsed['optionalDependencies'] ?? []), true)) {
            return true;
        }
        $entry = '  - ' . $blockName . "\n";
        if (isset($parsed['optionalDependencies'])) {
            // append after the last existing "  - …" entry of the list
            $updated = preg_replace(
                '/^(optionalDependencies:\R(?:[ \t]+-[ \t][^\r\n]*\R)*)/m',
                '$1' . $entry,
                $content,
                1,
                $count
            );
            if ($updated === null || $count !== 1) {
                return false;
            }
            $content = $updated;
        } else {
            $content = rtrim($content, "\r\n") . "\noptionalDependencies:\n" . $entry;
        }
        return file_put_contents($setConfigPath, $content) !== false;
    }
}
