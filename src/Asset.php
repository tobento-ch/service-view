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
 * Asset
 */
class Asset implements AssetInterface
{
    /**
     * @var null|SplFileInfo
     */
    protected ?SplFileInfo $fileInfo = null;
    
    /**
     * Create a new Asset.
     *
     * @param string $file The file such as 'src/styles.css'.
     * @param string $dir The file directory.
     * @param string $uri The file uri.
     * @param array $attributes The attributes
     * @param int $order The priority
     * @param string $group The group
     */    
    public function __construct(
        protected string $file,
        protected string $dir = '',
        protected string $uri = '',
        protected array $attributes = [],
        protected int $order = 0,
        protected string $group = 'default',
    ) {}

    /**
     * Get the file
     *
     * @return string
     */
    public function getFile(): string
    {
        return $this->file;
    }
    
    /**
     * Get the file info.
     *
     * @return SplFileInfo
     */
    public function getFileInfo(): SplFileInfo
    {
        if (is_null($this->fileInfo)) {
            $this->fileInfo = new SplFileInfo($this->dir.$this->file);
        }
        
        return $this->fileInfo;
    }
    
    /**
     * Set the dir.
     *
     * @param string $dir The directory.
     * @return static $this
     */
    public function dir(string $dir): static
    {
        $this->dir = $dir;
        return $this;
    }

    /**
     * Get the dir
     *
     * @return string
     */
    public function getDir(): string
    {
        return $this->dir;
    }
        
    /**
     * Set the uri.
     *
     * @param string $uri The uri.
     * @return static $this
     */
    public function uri(string $uri): static
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * Get the uri
     *
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Add an attribute.
     *
     * @param string $name The attribute name
     * @param mixed $value The attribute value
     * @return static $this
     */
    public function attr(string $name, mixed $value = null): static
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    /**
     * Get the attributes
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }    
            
    /**
     * Set the group.
     *
     * @param string $group The group.
     * @return static $this
     */
    public function group(string $group): static
    {
        $this->group = $group;
        return $this;
    }

    /**
     * Get the group.
     *
     * @return string
     */
    public function getGroup(): string
    {
        return $this->group;
    }
    
    /**
     * Set the order.
     *
     * @param int $order The order.
     * @return static $this
     */
    public function order(int $order): static
    {
        $this->order = $order;
        return $this;
    }    

    /**
     * Get the order.
     *
     * @return int
     */
    public function getOrder(): int
    {
        return $this->order;
    }    
    
    /**
     * Get the evaluated contents of the asset
     *
     * @return string
     */    
    public function render(): string
    {
        $src = $this->uri.$this->file;
        
        $attributes = $this->attributesAsString();
        
        $attributes = empty($attributes) ? '' : ' '.$attributes;
        
        switch ($this->getFileInfo()->getExtension()) {
            case 'css':
                return '<link href="'.Str::esc($src).'" rel="stylesheet" type="text/css"'.$attributes.'>';
            case 'js':
                return '<script src="'.Str::esc($src).'"'.$attributes.'></script>';
        }

        return '';
    }

    /**
     * To string
     *
     * @return string
     */    
    public function __toString(): string
    {
        return $this->render();
    }    
    
    /**
     * Get the attributes as string.
     *
     * @return string
     */    
    protected function attributesAsString(): string
    {
        $attributes = [];

        foreach($this->attributes as $name => $value) {

            if (is_int($name)) {
                $attributes[] = Str::esc($value);
                continue;
            }
            
            if (is_null($value)) {
                $attributes[] = Str::esc($name);
                continue;
            }
            
            if (is_array($value)) {
                $attributes[] = Str::esc($name)."='".Str::esc(json_encode($value))."'";                
            } else {
                $attributes[] = Str::esc($name).'="'.Str::esc($value).'"';
            }            
        }
                
        return implode(' ', $attributes);    
    }                
}