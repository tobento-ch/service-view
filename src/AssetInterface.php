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

use SplFileInfo;

/**
 * AssetInterface
 */
interface AssetInterface
{
    /**
     * Get the file
     *
     * @return string
     */
    public function getFile(): string;
    
    /**
     * Get the file info.
     *
     * @return SplFileInfo
     */
    public function getFileInfo(): SplFileInfo;
    
    /**
     * Set the dir.
     *
     * @param string $dir The directory.
     * @return static $this
     */
    public function dir(string $dir): static;

    /**
     * Get the dir
     *
     * @return string
     */
    public function getDir(): string;
        
    /**
     * Set the uri.
     *
     * @param string $uri The uri.
     * @return static $this
     */
    public function uri(string $uri): static;

    /**
     * Get the uri
     *
     * @return string
     */
    public function getUri(): string;

    /**
     * Add an attribute.
     *
     * @param string $name The attribute name
     * @param mixed $value The attribute value
     * @return static $this
     */
    public function attr(string $name, mixed $value = null): static;

    /**
     * Get the attributes
     *
     * @return array
     */
    public function getAttributes(): array;
                
    /**
     * Set the group.
     *
     * @param string $group The group.
     * @return static $this
     */
    public function group(string $group): static;

    /**
     * Get the group.
     *
     * @return string
     */
    public function getGroup(): string;
    
    /**
     * Set the order.
     *
     * @param int $order The order.
     * @return static $this
     */
    public function order(int $order): static;    

    /**
     * Get the order.
     *
     * @return int
     */
    public function getOrder(): int;    
    
    /**
     * Get the evaluated contents of the asset
     *
     * @return string
     */    
    public function render(): string;
}