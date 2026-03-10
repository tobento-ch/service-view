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

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Throwable;
use Tobento\Service\Dir\DirInterface;
use Tobento\Service\Filesystem\Dir as FilesystemDir;
use Tobento\Service\Minify\Factory;
use Tobento\Service\Minify\CssMinifierInterface;
use Tobento\Service\Minify\JavaScriptMinifierInterface;
use Tobento\Service\Minify\Minifier\NullMinifier;
use Tobento\Service\Minify\MinifierFactoryInterface;
use Tobento\Service\Minify\MinifierInterface;

/**
 * AssetsHandler
 */
class AssetsHandler implements AssetsHandlerInterface
{
    private const IMPORT_REGEX = '/import\s*(?:[\w*\s{},]*\s*from\s*)?[\'"]([^\'"]+)[\'"]/';

    /**
     * @var bool
     */
    protected bool $versioning = true;
    
    /**
     * @var array<string, MinifierInterface|MinifierFactoryInterface|null>
     */
    protected array $minifiers = [];
    
    /**
     * @var array<int, string>
     */
    protected array $skip = [];
    
    /**
     * @var array<string, array<string|int, string>>
     */
    protected array $combine = [];
    
    /**
     * @var array<string, string>
     */
    protected array $replace = [];
    
    /**
     * Create a new instance.
     *
     * @param DirInterface $storeDir Directory where processed assets are stored
     * @param string $assetUri Public URI for generated assets
     * @param array<int, string> $skipAlways Glob patterns always excluded from processing
     * @param LoggerInterface|null $logger Optional logger
     */
    final public function __construct(
        protected DirInterface $storeDir,
        protected string $assetUri,
        protected array $skipAlways = ['*.min.css', '*.min.js'],
        private null|LoggerInterface $logger = null,
    ) {}
    
    /**
     * Returns a new instance with the given versioning.
     *
     * @param bool $versioning
     * @return static
     */
    public function versioning(bool $versioning = true): static
    {
        $new = clone $this;
        $new->versioning = $versioning;
        return $new;
    }

    /**
     * Returns a new instance with the given minifiers.
     *
     * @param null|MinifierInterface|MinifierFactoryInterface ...$minifiers
     * @return static
     */
    public function withMinifiers(null|MinifierInterface|MinifierFactoryInterface ...$minifiers): static
    {
        $new = clone $this;
        $new->minifiers = array_merge($new->minifiers, $minifiers);
        return $new;
    }

    /**
     * Returns a new instance with the given parameters.
     *
     * @param string ...$file
     * @return static
     */
    public function skipMinify(string ...$file): static
    {
        $new = clone $this;
        $new->skip = $file;
        return $new;
    }
    
    /**
     * Returns a new instance with the given parameters.
     * This method should merge previous replacements.
     *
     * @param string $file
     * @param string $with
     * @return static
     */
    public function replace(string $file, string $with): static
    {
        $new = clone $this;
        $new->replace = array_merge($new->replace, [$file => $with]);
        return $new;
    }
    
    /**
     * Returns a new instance with the given parameters.
     * This method should merge previous replacements.
     *
     * @param string $filename
     * @param array<int, string> $files
     * @return static
     */
    public function combine(string $filename, array $files): static
    {
        $new = clone $this;
        $new->combine = array_merge($new->combine, [$filename => $files]);
        return $new;
    }
    
    /**
     * Handle the assets.
     *
     * @param array<string, AssetInterface> $assets The assets
     * @return array<string, AssetInterface> The assets
     */
    public function handle(array $assets): array
    {
        if (!empty($this->replace)) {
            $assets = $this->replaceAssets($assets, $this->replace);
        }
        
        if (!empty($this->combine)) {
            $assets = $this->combineAssets($assets, $this->combine);
        }

        if ($this->versioning) {
            $assets = $this->versioningAssets($assets);
        }
        
        $skip = array_unique(array_merge($this->skipAlways, $this->skip));
        
        $assets = $this->minifyAssets($assets, $skip);
        
        $assets = $this->modifyAssetsUri($assets);
        
        return $assets;
    }

    /**
     * Clear
     *
     * @return void
     */
    public function clear(): void
    {
        // All modified or created assets should be cleared.
        new FilesystemDir()->delete($this->storeDir->dir());
    }

    /**
     * Replace assets based on a mapping of old file → new file.
     *
     * @param array<string, AssetInterface> $assets
     * @param array<string, string> $replace
     * @return array<string, AssetInterface>
     */
    protected function replaceAssets(array $assets, array $replace): array
    {
        foreach ($replace as $from => $to) {

            if (!isset($assets[$from])) {
                continue;
            }

            $asset = $assets[$from];

            $this->log(
                level: LogLevel::INFO,
                message: sprintf('Replaced asset "%s" with "%s"', $from, $to),
                context: ['from' => $from, 'to' => $to],
            );
            
            // Replace file path
            $newAsset = $asset->withFile($to);

            // Remove old key, insert new key
            unset($assets[$from]);
            $assets[$to] = $newAsset;
        }

        return $assets;
    }
    
    /**
     * Combine multiple assets into a single target asset.
     *
     * @param array<string, AssetInterface> $assets The assets
     * @param array<string, array<int|string, string>> $combine
     * @return array<string, AssetInterface> The assets
     */
    protected function combineAssets(array $assets, array $combine): array
    {
        $dir = new FilesystemDir();

        foreach ($combine as $target => $files) {

            $firstAsset = null;
            $combineAssets = [];

            // Collect source assets
            foreach ($files as $file) {

                if (!isset($assets[$file])) {
                    continue;
                }

                if ($firstAsset === null) {
                    $firstAsset = $assets[$file];
                }

                $combineAssets[$file] = $assets[$file];
                unset($assets[$file]);
            }

            if ($firstAsset === null) {
                continue;
            }

            // Create CombinedAsset object first
            $combinedAsset = new CombinedAsset(
                assets: $combineAssets,
                file: $target,
                dir: $this->storeDir->dir(),
                uri: $firstAsset->getUri(),
                attributes: $firstAsset->getAttributes(),
                order: $firstAsset->getOrder(),
                group: $firstAsset->getGroup(),
            );

            // Only rebuild if modified
            if ($combinedAsset->isModified()) {

                $combined = '';

                foreach ($combineAssets as $asset) {
                    $path = $asset->getDir() . $asset->getFile();
                    if (is_file($path)) {
                        $content = file_get_contents($path);
                        if (is_string($content)) {
                            $combined .= $content . "\n";
                        }
                    }
                }

                // Ensure directory exists
                $targetDir = dirname($this->storeDir->dir() . $target);
                if (!$dir->has($targetDir)) {
                    $dir->create(directory: $targetDir, mode: 0755, recursive: true);
                }

                // Write combined file
                file_put_contents($this->storeDir->dir() . $target, $combined);
                
                $combinedAsset->markRebuilt();
                
                $this->log(
                    level: LogLevel::INFO,
                    message: sprintf(
                        'Rebuilt combined asset "%s" from %d source files',
                        $target,
                        count($combineAssets)
                    ),
                    context: [
                        'target' => $target,
                        'sources' => array_keys($combineAssets),
                    ],
                );
            }

            // Register combined asset
            $assets[$target] = $combinedAsset;
        }
        
        return $assets;
    }
    
    /**
     * Minify all assets except those explicitly skipped.
     *
     * @param array<string, AssetInterface> $assets The assets
     * @param array<int, string> $skip
     * @return array<string, AssetInterface> The assets
     */
    protected function minifyAssets(array $assets, array $skip): array
    {
        // Determine which files to minify
        $toMinify = [];

        foreach ($assets as $asset) {
            $file = $this->stripVersion($asset->getFile());
            
            if (!in_array($file, $skip, true)) {
                $toMinify[$file] = $asset;
            }
        }
        
        $minified = [];
        $unminified = [];

        foreach ($assets as $key => $asset) {

            $file = $this->stripVersion($asset->getFile());

            // Check all skip patterns (supports *, ?, ranges)
            foreach ($skip as $pattern) {
                if (fnmatch($pattern, $file)) {
                    $this->log(
                        level: LogLevel::DEBUG,
                        message: sprintf('Skipped minify for asset "%s" (pattern: "%s")', $file, $pattern),
                        context: ['file' => $file, 'pattern' => $pattern],
                    );
                    
                    $unminified[$key] = $asset;
                    continue 2; // skip minification for this asset
                }
            }

            // Not skipped, minify
            $minified[$key] = $this->minifyAsset($asset, $toMinify);
        }

        return array_merge($minified, $unminified);
    }
    
    /**
     * Minifies the given asset.
     *
     * @param AssetInterface $asset
     * @param array<string, AssetInterface> $toMinify
     * @param array &$processing
     * @return AssetInterface
     */
    protected function minifyAsset(AssetInterface $asset, array $toMinify, array &$processing = []): AssetInterface
    {
        $key = $asset->getDir() . $asset->getFile();
        
        if (isset($processing[$key])) {
            $this->log(
                level: LogLevel::NOTICE,
                message: sprintf('Circular import detected for asset file: "%s"', $asset->getFile()),
            );
            return $asset; // circular import
        }

        $processing[$key] = true;

        // Check if has a directory, otherwise it may by an external file.
        if ($asset->getDir() === '') {
            unset($processing[$key]);
            return $asset;
        }
        
        if (! $this->isAssetModified($asset)) {
            unset($processing[$key]);
            return $asset;
        }

        if (empty($content = $this->loadAssetContent($asset))) {
            return $asset;
        }

        // Handle JS imports
        $content = $this->processJsImports($asset, $content, $toMinify, $processing);

        // Minify
        $extension = $asset->getFileInfo()->getExtension();
        $minified = $this->getMinifierFor($extension)->minify($content);

        unset($processing[$key]);

        return $this->writeAsset($minified, $asset);
    }

    /**
     * Process JS import statements and update content accordingly.
     *
     * @param AssetInterface $asset
     * @param string $content
     * @param array $toMinify
     * @param array $processing
     * @return string Updated content with resolved imports.
     */
    protected function processJsImports(
        AssetInterface $asset,
        string $content,
        array $toMinify,
        array &$processing
    ): string {
        // Only JS files have imports
        if ($asset->getFileInfo()->getExtension() !== 'js') {
            return $content;
        }

        preg_match_all(static::IMPORT_REGEX, $content, $matches);
        
        foreach ($matches[1] as $importPath) {

            // Resolve import path
            $resolved = $this->resolveImportPath($asset, $importPath);
            
            if (! $resolved || ! file_exists($resolved)) {
                $this->log(
                    level: LogLevel::ERROR,
                    message: sprintf(
                        'Missing imported JS file "%s" referenced in "%s"',
                        $importPath,
                        $asset->getFile()
                    ),
                    context: ['asset' => $asset, 'import' => $importPath],
                );
                continue;
            }
            
            // Normalize slashes
            $resolved = str_replace('\\', '/', $resolved);
            $assetDir = str_replace('\\', '/', $asset->getDir());

            // Convert absolute path to relative asset file
            $assetFile = ltrim(str_replace($assetDir, '', $resolved), '/');

            // Reuse existing version if available
            $versionAsset = $toMinify[$assetFile] ?? $asset;
            $existingVersion = $this->extractVersionFromFile($versionAsset->getFile());
            
            if ($existingVersion !== null) {
                $content = str_replace(
                    $importPath,
                    $importPath . '?v=' . $existingVersion,
                    $content
                );
            }

            // Skip if already scheduled for minification
            if (isset($toMinify[$assetFile])) {
                continue;
            }

            // Recursively minify imported asset
            $importAsset = $asset
                ->withFile($assetFile)
                ->withDir($asset->getDir());

            $this->minifyAsset($importAsset, $toMinify, $processing);
        }
        
        return $content;
    }
    
    /**
     * Reads the asset file and returns its contents.
     *
     * @param AssetInterface $asset
     * @return string Empty string if the file cannot be read.
     */
    protected function loadAssetContent(AssetInterface $asset): string
    {
        $file = $this->stripVersion($asset->getFile());
        $path = $asset->getDir() . $file;

        try {
            $content = file_get_contents($path);
            return is_string($content) ? $content : '';
        } catch (Throwable $t) {
            $this->log(
                level: LogLevel::ERROR,
                message: sprintf('Failed to load asset "%s": %s', $path, $t->getMessage()),
                context: ['exception' => $t],
            );
            return '';
        }
    }
    
    /**
     * Checks whether the asset's source file is newer than its minified version.
     *
     * @param AssetInterface $asset
     * @return bool True if the asset must be re‑minified, false if up to date.
     */
    protected function isAssetModified(AssetInterface $asset): bool
    {
        if ($asset instanceof CombinedAsset) {
            return $asset->wasRebuilt();
        }
        
        // Strip ?v=12345
        $cleanFile = $this->stripVersion($asset->getFile());

        // Original source file (unversioned)
        $sourcePath = $asset->getDir() . $cleanFile;
        
        // Minified file (versioned filename, stored in build/store dir)
        $minifiedPath = $this->storeDir->dir() . $cleanFile;
        
        $sourceMtime = file_exists($sourcePath) ? filemtime($sourcePath) : 0;
        $minifiedMtime = file_exists($minifiedPath) ? filemtime($minifiedPath) : 0;

        // If minified file is missing OR older → needs rebuild
        return $minifiedMtime < $sourceMtime;
    }
    
    /**
     * Returns the resolved absolute path for an import or null if not found.
     *
     * @param AssetInterface $asset
     * @param string $importPath
     * @return null|string
     */
    protected function resolveImportPath(AssetInterface $asset, string $importPath): null|string
    {
        // Strip quotes and whitespace
        $importPath = trim($importPath, " \t\n\r\0\x0B'\"");

        // Ignore URLs like http://, https://, //cdn...
        if (preg_match('#^(https?:)?//#i', $importPath)) {
            return null;
        }
                
        // Only handle relative imports
        if (!str_starts_with($importPath, './') && !str_starts_with($importPath, '../')) {
            return null;
        }

        // Build absolute path of the current asset file
        $assetPath = $asset->getDir() . $asset->getFile();

        // True base directory
        $baseDir = rtrim(dirname($assetPath), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        // Remove leading "./"
        if (str_starts_with($importPath, './')) {
            $importPath = substr($importPath, 2);
        }

        // Build full path
        $fullPath = $baseDir . $importPath;

        // Normalize path
        $fullPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fullPath);

        // Resolve
        $resolved = realpath($fullPath);
        
        return $resolved !== false ? $resolved : null;
    }
    
    /**
     * Returns the minifier for the given file extension.
     *
     * @param string $extension
     * @return MinifierInterface
     */
    protected function getMinifierFor(string $extension): MinifierInterface
    {
        // User explicitly disabled minifier: js: null, css: null
        if (array_key_exists($extension, $this->minifiers) && $this->minifiers[$extension] === null) {
            return new Factory\NullMinifierFactory()->createMinifier();
        }

        // User provided a minifier or factory
        $minifier = $this->minifiers[$extension] ?? null;

        $minifier = match (true) {
            $minifier instanceof MinifierInterface => $minifier,
            $minifier instanceof MinifierFactoryInterface => $minifier->createMinifier(),
            default => null, // important: do NOT create NullMinifier here
        };

        // If the minifier matches the extension-specific interface, return it
        if ($extension === 'css' && $minifier instanceof CssMinifierInterface) {
            return $minifier;
        }

        if ($extension === 'js' && $minifier instanceof JavaScriptMinifierInterface) {
            return $minifier;
        }

        // If user provided a minifier, always use it
        if ($minifier instanceof MinifierInterface) {
            return $minifier;
        }

        // No minifier provided, use default
        return $this->createDefaultMinifierFor($extension);
    }

    /**
     * Returns the default minifier for the given file extension.
     *
     * @param string $extension
     * @return MinifierInterface
     */
    protected function createDefaultMinifierFor(string $extension): MinifierInterface
    {
        if ($extension === 'css') {
            return new Factory\CssMinifierFactory()->createMinifier();
        }
        
        if ($extension === 'js') {
            return new Factory\JavaScriptMinifierFactory()->createMinifier();
        }
        
        return new Factory\NullMinifierFactory()->createMinifier();
    }

    /**
     * Modify uri assets.
     *
     * @param array<string, AssetInterface> $assets The assets
     * @return array<string, AssetInterface> The assets
     */
    protected function modifyAssetsUri(array $assets): array
    {
        foreach ($assets as $key => $asset) {
            $assets[$key] = $asset->withUri(rtrim($this->assetUri, '/') . '/');
        }
        
        return $assets;
    }
    
    /**
     * Versioning assets.
     *
     * @param array<string, AssetInterface> $assets The assets
     * @return array<string, AssetInterface> The assets
     */
    protected function versioningAssets(array $assets): array
    {
        foreach ($assets as $key => $asset) {
            $version = $this->generateVersionHash($asset);
            $assets[$key] = $asset->withFile(sprintf('%s?v=%s', $asset->getFile(), $version));
        }
        
        return $assets;
    }
    
    /**
     * Generates a version identifier based on the asset's modification time.
     *
     * @param AssetInterface $asset
     * @return string
     */
    protected function generateVersionHash(AssetInterface $asset): string
    {
        $finfo = $asset->getFileInfo();

        if ($finfo->isFile()) {
            return (string) $finfo->getMTime();
        }

        // Fallback: no version until file exists
        return '';
    }
    
    /**
     * Removes any version query string (e.g. "?v=12345") from a file path.
     *
     * @param string $file
     * @return string The file path without a version parameter.
     */
    protected function stripVersion(string $file): string
    {
        $pos = strpos($file, '?');
        return $pos !== false ? substr($file, 0, $pos) : $file;
    }
    
    /**
     * Extracts the version value from a file name query string (e.g. "?v=12345").
     *
     * @param string $file
     * @return null|string The version string if present, otherwise null.
     */
    protected function extractVersionFromFile(string $file): null|string
    {
        if (strpos($file, '?v=') !== false) {
            return substr($file, strpos($file, '?v=') + 3);
        }
        return null;
    }

    /**
     * Write asset.
     *
     * @param string $content
     * @param AssetInterface $asset
     * @return AssetInterface
     */
    protected function writeAsset(string $content, AssetInterface $asset): AssetInterface
    {
        // Strip ?query from filename if present
        $cleanFile = explode('?', $asset->getFile())[0];
        
        $path = $this->storeDir->dir().$cleanFile;

        // Ensure directory exists
        $dir = new FilesystemDir();
        
        if (! $dir->has(dirname($path))) {
            $dir->create(directory: dirname($path), mode: 0755, recursive: true);
        }

        file_put_contents($path, $content);

        return $asset->withDir($this->storeDir->dir());
    }
    
    /**
     * Logs a message if a logger has been provided.
     *
     * This method is a lightweight wrapper around the PSR-3 logger,
     * allowing the AssetsHandler to perform optional logging without
     * requiring a logger implementation. If no logger is set, the call
     * is silently ignored.
     *
     * @param string $level The PSR-3 log level (use LogLevel::* constants).
     * @param string $message The log message.
     * @param array  $context Additional context passed to the logger.
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        $this->logger?->log($level, $message, $context);
    }
}