<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);

namespace Tobento\Service\View;

/**
 * AssetsHandlerInterface
 */
interface AssetsHandlerInterface
{
    /**
     * Handle the assets.
     *
     * @param array<string, AssetInterface> $assets The assets
     * @return array<string, AssetInterface> The assets
     */
    public function handle(array $assets): array;

    /**
     * Clear
     *
     * @return void
     */
    public function clear(): void;
}