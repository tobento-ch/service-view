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
 * Views interface
 */
interface ViewInterface
{
    /**
     * Add or get data collection
     *
     * @param null|array $data
     * @return static|DataInterface
     */
    public function data(?array $data = null): static|DataInterface;

    /**
     * Add data to the view.
     *
     * @param string $name The name
     * @param mixed $value The value
     * @return static $this
     */
    public function with(string $name, mixed $value): static;
    
    /**
     * Get data by name.
     *
     * @param string $name The name
     * @param mixed $default A default value
     * @return mixed The value
     */
    public function get(string $name, mixed $default = null): mixed;    
            
    /**
     * Renders a view.
     *
     * @param string $view The view name.
     * @param array $data The view data.
     * @return string The view rendered.
     */
    public function render(string $view, array $data = []): string;
        
    /**
     * On render view.
     *
     * @param string The view name.
     * @param callable function(array $data, ViewInterface $view) { return []; // the data }
     * @return static $this
     */
    public function on(string $view, callable $callable): static;
    
    /**
     * Add a view by key.
     *
     * @param string $key The view key.
     * @param string $view The view name.
     * @return static $this
     */        
    public function add(string $key, string $view): static;

    /**
     * If the view exists.
     *
     * @param string $view The view name.
     * @return bool True if the view exists, otherwise false.
     */        
    public function exists(string $view): bool;
    
    /**
     * Render once only.
     *
     * @param string $key Any identifier key.
     * @return bool True if not rendered yet, otherwise false.
     */        
    public function once(string $key): bool;
    
    /**
     * Get the assets.
     *
     * @return AssetsInterface
     */        
    public function assets(): AssetsInterface;

    /**
     * Add and get an asset.
     *
     * @param string $file The file
     * @return AssetInterface
     */        
    public function asset(string $file): AssetInterface;

    /**
     * Escapes html with htmlspecialchars.
     * 
     * @param mixed $string See php doc http://php.net/manual/en/function.htmlspecialchars.php
     * @param int $flags See php doc.
     * @param string $encoding See php doc
     * @param bool $double_encode See php doc
     * @return string
     */
    public function esc(
        mixed $string,
        int $flags = ENT_QUOTES,
        string $encoding = 'UTF-8',
        bool $double_encode = true
    ): string;   
}