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
 * DirsInterface
 */
interface DirsInterface
{
    /**
     * Set the default group.
     *
     * @param string $group The default group name.
     * @return static
     */
    public function defaultGroup(string $group): static;
    
    /**
     * Adds a directory.
     *
     * @param DirInterface $dir
     * @return static $this
     */
    public function add(DirInterface $dir): static;
        
    /**
     * Adds a directory.
     *
     * @param string $dir The directory to the views. Must end with a slash /.
     * @param int $priority The priority. Highest first.
     * @param string $group A dir group name.
     * @return static $this
     */
    public function dir(string $dir, int $priority = 0, string $group = 'default'): static;

    /**
     * Filters the dirs by group.
     *
     * @param string $group The group name or null for the default group.
     * @return static
     */
    public function group(?string $group = null): static;
    
    /**
     * Get all dirs.
     *
     * @return array
     */
    public function all(): array;
}