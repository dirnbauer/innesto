<?php

declare(strict_types=1);

namespace Webconsulting\Innesto\Registry;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

/**
 * Registers a grafted content block in the Innesto site set. Content Blocks
 * exposes every content block as a virtual site set named after the block
 * (e.g. "innesto/marquee"). As soon as ANY content block in an installation
 * is referenced that way — as Desiderio does for its elements —
 * the New Content Element wizard switches to allow-list mode per site and
 * hides every block that is not listed. Appending the block name to the set's
 * optionalDependencies is therefore a mandatory part of every graft.
 */
final readonly class SetRegistrar
{
    /**
     * @return bool true if the block is (now) registered in the set config
     */
    public function register(string $setConfigPath, string $blockName): bool
    {
        if (!is_file($setConfigPath)) {
            return false;
        }
        $parsed = Yaml::parseFile($setConfigPath);
        if (!is_array($parsed)) {
            return false;
        }
        $dependencies = $parsed['optionalDependencies'] ?? [];
        if (!is_array($dependencies)) {
            return false;
        }
        if (in_array($blockName, $dependencies, true)) {
            return true;
        }
        $parsed['optionalDependencies'] = [...$dependencies, $blockName];
        new Filesystem()->dumpFile($setConfigPath, Yaml::dump($parsed, 4, 2));
        return true;
    }
}
