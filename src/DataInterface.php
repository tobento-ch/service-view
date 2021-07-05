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
 * DataInterface
 */
interface DataInterface
{
    /**
     * Set any data.
     *
     * @param string $key The key.
     * @param mixed $value The value.
     * @param null|string|array $views For specific view or null for all.
     * @return static $this
     */
    public function set(string $key, mixed $value, null|string|array $views = null): static;

    /**
     * Add data
     *
     * @param array $data The data to add.
     * @param null|string|array $views For specific view or null for all.
     * @return static
     */    
    public function add(array $data, null|string|array $views = null): static;

    /**
     * Get data by key.
     *
     * @param string $key The key
     * @param mixed $default A default value
     * @return mixed The value
     */
    public function get(string $key, mixed $default = null): mixed;
    
    /**
     * Get the data for a specific view or for all.
     *
     * @param null|string $view The view key
     * @return array The data.
     */
    public function all(?string $view = null): array;
    
    /**
     * Renames the data keys.
     *
     * @param array $renameKeys The keys to rename. ['old_key' => 'new_key']
     * @return static
     */
    public function rename(array $renameKeys): static;
}