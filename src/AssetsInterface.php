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
 * AssetsInterface
 */
interface AssetsInterface
{
    /**
     * Set the assets handler
     *
     * @param AssetsHandlerInterface $assetsHandler
     * @return static $this
     */
    public function setAssetsHandler(AssetsHandlerInterface $assetsHandler): static;
    
    /**
     * Adds an asset.
     *
     * @param AssetInterface $asset
     * @return static $this
     */
    public function add(AssetInterface $asset): static;
    
    /**
     * Create and adds an asset.
     *
     * @param string $file The file such as 'src/styles.css'.
     * @return AssetInterface
     */
    public function asset(string $file): AssetInterface;

    /**
     * Render the assets.
     *
     * @param string $group The group.
     * @return string
     */
    public function render(string $group = 'default'): string;
    
    /**
     * Flushing
     *
     * @param string $content The content.
     * @return string The content.
     */
    public function flushing(string $content): string;

    /**
     * Get all assets.
     *
     * @return array
     */
    public function all(): array;
}