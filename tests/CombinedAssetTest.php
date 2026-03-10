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

namespace Tobento\Service\View\Test;

use PHPUnit\Framework\TestCase;
use Tobento\Service\Filesystem\Dir as FilesystemDir;
use Tobento\Service\View\Asset;
use Tobento\Service\View\AssetInterface;
use Tobento\Service\View\CombinedAsset;

class CombinedAssetTest extends TestCase
{
    protected string $tmpDir = __DIR__.'/tmp-combined-asset/';
    
    protected function setUp(): void
    {
        new FilesystemDir()->create($this->tmpDir);
    }
    
    protected function tearDown(): void
    {
        new FilesystemDir()->delete($this->tmpDir);
    }
    
    protected function createAsset(
        string $file,
        string $content,
        bool $withDir = true,
        bool $withUri = true
    ): AssetInterface {
        $path = $this->tmpDir.$file;
        $dir = new FilesystemDir();
        
        if (! $dir->has(dirname($path))) {
            $dir->create(directory: dirname($path), mode: 0755, recursive: true);
        }

        file_put_contents($path, $content);

        return new Asset(
            file: $file,
            dir: $withDir ? $this->tmpDir : null,
            uri: $withUri ? 'https://example.com/assets/' : null,
        );
    }
    
    public function testCreateCombinedAsset()
    {
        $combined = new CombinedAsset(
            assets: [
                'a.js' => $this->createAsset('a.js', 'console.log(1);'),
            ],
            file: 'combined.js',
            dir: $this->tmpDir,
        );
        
        $this->assertInstanceOf(Asset::class, $combined);
        $this->assertInstanceOf(AssetInterface::class, $combined);
    }
    
    public function testIsModifiedWhenCombinedFileDoesNotExist()
    {
        $source = $this->createAsset('a.js', 'console.log(1);');

        $combined = new CombinedAsset(
            assets: ['a.js' => $source],
            file: 'combined.js',
            dir: $this->tmpDir,
        );

        $this->assertTrue($combined->isModified());
    }

    public function testIsModifiedWhenSourceFileMissing()
    {
        // Create combined file
        file_put_contents($this->tmpDir.'combined.js', '/* combined */');

        // Create asset pointing to a missing file
        $source = new Asset('missing.js', $this->tmpDir);

        $combined = new CombinedAsset(
            assets: ['missing.js' => $source],
            file: 'combined.js',
            dir: $this->tmpDir,
        );

        $this->assertTrue($combined->isModified());
    }
    
    public function testIsModifiedWhenSourceNewerThanCombined()
    {
        // Combined file older
        file_put_contents($this->tmpDir.'combined.js', '/* combined */');
        touch($this->tmpDir.'combined.js', time() - 10);

        // Source file newer
        $source = $this->createAsset('a.js', 'console.log(1);');
        touch($this->tmpDir.'a.js', time());

        $combined = new CombinedAsset(
            assets: ['a.js' => $source],
            file: 'combined.js',
            dir: $this->tmpDir,
        );

        $this->assertTrue($combined->isModified());
    }
    
    public function testIsNotModifiedWhenAllSourcesOlder()
    {
        // Combined file newer
        file_put_contents($this->tmpDir.'combined.js', '/* combined */');
        touch($this->tmpDir.'combined.js', time());

        // Source file older
        $source = $this->createAsset('a.js', 'console.log(1);');
        touch($this->tmpDir.'a.js', time() - 10);

        $combined = new CombinedAsset(
            assets: ['a.js' => $source],
            file: 'combined.js',
            dir: $this->tmpDir,
        );

        $this->assertFalse($combined->isModified());
    }

    public function testMarkRebuilt()
    {
        $combined = new CombinedAsset(
            assets: [],
            file: 'combined.js',
            dir: $this->tmpDir,
        );

        $this->assertFalse($combined->wasRebuilt());

        $combined->markRebuilt();

        $this->assertTrue($combined->wasRebuilt());
    }
}