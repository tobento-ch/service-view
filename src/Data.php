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
 * Data collection
 */
class Data implements DataInterface
{                
    /**
     * Create a new Data collection.
     *
     * @param array $sharedData Any shared data.
     * @param array $viewData Any view specific data.
     */    
    public function __construct(
        protected array $sharedData = [],
        protected array $viewData = []
    ) {}
    
    /**
     * Set any data.
     *
     * @param string $key The key.
     * @param mixed $value The value.
     * @param null|string|array $views For specific view or null for all.
     * @return static $this
     */
    public function set(string $key, mixed $value, null|string|array $views = null): static
    {
        if ($views === null) {
            // Shared data.
            $this->sharedData[$key] = $value;
        }
    
        if (is_string($views)) {
            // Single view data.
            $this->viewData[$views][$key] = $value;
        }

        if (is_array($views)) {
            // Multiple views data.
            foreach($views as $view) {
                $this->viewData[$view][$key] = $value;
            }        
        }
        
        return $this;
    }

    /**
     * Add data
     *
     * @param array $data The data to add.
     * @param null|string|array $views For specific view or null for all.
     * @return static
     */    
    public function add(array $data, null|string|array $views = null): static
    {
        if ($views === null) {
            // Shared data.
             $this->sharedData = array_merge($this->sharedData, $data);
        }
    
        if (is_string($views)) {
            // Single view data.
            if (isset($this->dataViews[$views])) {
                $this->viewData[$views] = array_merge($this->viewData[$views], $data);
            } else {
                $this->viewData[$views] = $data;
            }
        }

        if (is_array($views)) {
            // Multiple views data.
            foreach($views as $view)
            {
                if (isset($this->viewData[$view])) {
                    $this->viewData[$view] = array_merge($this->viewData[$view], $data);
                } else {
                    $this->viewData[$view] = $data;
                }                
            }        
        }
        
        return $this;
    }

    /**
     * Get data by key.
     *
     * @param string $key The key
     * @param mixed $default A default value
     * @return mixed The value
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $data = $this->all();
        
        return array_key_exists($key, $data) ? $data[$key] : $default;
    }
    
    /**
     * Get the data for a specific view or for all.
     *
     * @param null|string $view The view key
     * @return array The data.
     */
    public function all(?string $view = null): array
    {
        if (isset($view, $this->viewData[$view])) {
            return array_merge($this->sharedData, $this->viewData[$view]);
        }

        return $this->sharedData;
    }
    
    /**
     * Renames the data keys.
     *
     * @param array $renameKeys The keys to rename. ['old_key' => 'new_key']
     * @return static
     */
    public function rename(array $renameKeys): static
    {
        $new = clone $this;
        
        $new->sharedData = $this->renameDataKeys($new->sharedData, $renameKeys);
        
        foreach($new->viewData as $view => $data)
        {
            $new->viewData[$view] = $this->renameDataKeys($data, $renameKeys);
        }
        
        return $new;    
    }
    
    /**
     * Renames the data keys.
     *
     * @param array $data The data
     * @param array $renameKeys The keys to rename. ['old_key' => 'new_key']
     * @return array The data renamed.
     */
    protected function renameDataKeys(array $data, array $renameKeys): array
    {        
        foreach($renameKeys as $oldKey => $newKey)
        {
            if (array_key_exists($oldKey, $data))
            {
                $data[$newKey] = $data[$oldKey];
                unset($data[$oldKey]);
            }
        }
        
        return $data;
    }                    
}