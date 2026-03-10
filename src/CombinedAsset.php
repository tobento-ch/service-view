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
 * Represents an asset created by combining multiple source assets.
 *
 * A CombinedAsset tracks its original source assets and can determine
 * whether it needs to be regenerated based on their modification times.
 */
class CombinedAsset extends Asset
{
    protected bool $rebuilt = false;
    
    /**
     * Create a new CombinedAsset.
     *
     * @param array<string, AssetInterface> $assets
     * @param string $file The file such as 'src/styles.css'.
     * @param string $dir The file directory.
     * @param string $uri The file uri.
     * @param array $attributes The attributes
     * @param int $order The priority
     * @param string $group The group
     */
    public function __construct(
        protected array $assets,
        protected string $file,
        protected string $dir = '',
        protected string $uri = '',
        protected array $attributes = [],
        protected int $order = 0,
        protected string $group = 'default',
    ) {}
    
    /**
     * Marks the combined asset as rebuilt in the current run.
     *
     * @return void
     */
    public function markRebuilt(): void
    {
        $this->rebuilt = true;
    }

    /**
     * Indicates whether the combined asset was rebuilt in the current run.
     *
     * @return bool
     */
    public function wasRebuilt(): bool
    {
        return $this->rebuilt;
    }

    /**
     * Determines whether the combined asset needs to be rebuilt.
     *
     * Checks the modification time of the generated combined file
     * against each of its source assets. If the combined file does not
     * exist or any source file is newer, the asset is considered modified.
     *
     * @return bool True if the combined asset must be regenerated, false otherwise.
     */
    public function isModified(): bool
    {
        $combinedPath = $this->getDir() . $this->getFile();

        if (!file_exists($combinedPath)) {
            return true;
        }

        $combinedMtime = filemtime($combinedPath);

        foreach ($this->assets as $asset) {
            $sourcePath = $asset->getDir() . $asset->getFile();

            if (!file_exists($sourcePath)) {
                return true;
            }

            if (filemtime($sourcePath) > $combinedMtime) {
                return true;
            }
        }

        return false;
    }
}