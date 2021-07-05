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
 * Assets
 */
class Assets implements AssetsInterface
{
    /**
     * @var array
     */    
    protected array $assets = [];
    
    /**
     * @var array The groups to render.
     */    
    protected array $groupsToRender = [];    
    
    /**
     * Create a new Assets.
     *
     * @param string $assetDir The asset directory.
     * @param string $assetUri The asset uri.
     * @param null|AssetsHandlerInterface $assetsHandler
     */    
    public function __construct(
        protected string $assetDir,
        protected string $assetUri,
        protected ?AssetsHandlerInterface $assetsHandler = null
    ) {}

    /**
     * Set the assets handler
     *
     * @param AssetsHandlerInterface $assetsHandler
     * @return static $this
     */
    public function setAssetsHandler(AssetsHandlerInterface $assetsHandler): static
    {        
        $this->assetsHandler = $assetsHandler;
        return $this;
    }
    
    /**
     * Adds an asset.
     *
     * @param AssetInterface $asset
     * @return static $this
     */
    public function add(AssetInterface $asset): static
    {        
        $this->assets[$asset->getFile()] = $asset;
        return $this;
    }
    
    /**
     * Create and adds an asset.
     *
     * @param string $file The file such as 'src/styles.css'.
     * @return AssetInterface
     */
    public function asset(string $file): AssetInterface
    {
        if (isset($this->assets[$file])) {
            return $this->assets[$file];
        }
        
        $asset = new Asset($file, $this->assetDir, $this->assetUri);
        
        $this->add($asset);
        
        return $asset;
    }

    /**
     * Render the assets.
     *
     * @param string $group The group.
     * @return string
     */
    public function render(string $group = 'default'): string
    {
        $this->groupsToRender[] = $group;
        
        return '<!-- assets="'.$group.'" -->';
    }
    
    /**
     * Flushing
     *
     * @param string $content The content.
     * @return string The content.
     */
    public function flushing(string $content): string
    {
        uasort(
            $this->assets,
            fn (AssetInterface $a, AssetInterface $b): int => $b->getOrder() <=> $a->getOrder()
        );
        
        if ($this->assetsHandler) {
            $this->assets = $this->assetsHandler->handle($this->assets);
        }
            
        foreach($this->groupsToRender as $group)
        {
            $content = str_replace('<!-- assets="'.$group.'" -->', $this->renderGroup($group), $content);
        }
        
        $this->groupsToRender = [];
        
        return $content;
    }

    /**
     * Get all assets.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->assets;
    }
    
    /**
     * Render the asset group
     *
     * @param string $group The group.
     * @return string The rendered asset group
     */
    protected function renderGroup(string $group): string
    {                
        $assets = array_filter($this->assets, fn ($a) => $a->getGroup() === $group);
        
        $content = '';
        
        foreach($assets as $asset)
        {
            $content .= $asset->render();
        }
        
        return $content;
    }    
}