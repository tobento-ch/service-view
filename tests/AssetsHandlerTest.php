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
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Tobento\Service\Dir\Dir;
use Tobento\Service\Filesystem\Dir as FilesystemDir;
use Tobento\Service\Minify\Factory;
use Tobento\Service\View\Asset;
use Tobento\Service\View\AssetInterface;
use Tobento\Service\View\AssetsHandler;
use Tobento\Service\View\AssetsHandlerInterface;

class AssetsHandlerTest extends TestCase
{
    protected string $tmpDir = __DIR__.'/tmp-assets/';
    
    // make sure they are within tmpDir!
    protected string $assetDir = __DIR__.'/tmp-assets/';
    protected string $storeDir = __DIR__.'/tmp-assets/build/';
    
    protected function setUp(): void
    {
        new FilesystemDir()->create($this->tmpDir);
    }
    
    protected function tearDown(): void
    {
        new FilesystemDir()->delete($this->tmpDir);
    }

    protected function createAssets(AssetInterface ...$assets): array
    {
        $items = [];

        foreach ($assets as $asset) {
            $items[$asset->getFile()] = $asset;
        }

        return $items;
    }    

    protected function createAsset(
        string $file,
        string $content,
        bool $withDir = true,
        bool $withUri = true
    ): AssetInterface {
        $path = $this->assetDir.$file;
        $dir = new FilesystemDir();
        
        if (! $dir->has(dirname($path))) {
            $dir->create(directory: dirname($path), mode: 0755, recursive: true);
        }

        file_put_contents($path, $content);

        return new Asset(
            file: $file,
            dir: $withDir ? $this->assetDir : null,
            uri: $withUri ? 'https://example.com/assets/' : null,
        );
    }
    
    protected function normalizePath(string $path): string
    {
        return rtrim(str_replace('\\', '/', $path), '/');
    }
    
    protected function createAssetsHandler(null|LoggerInterface $logger = null): AssetsHandlerInterface
    {
        return new AssetsHandler(
            storeDir: new Dir($this->storeDir),
            assetUri: 'https://example.com/assets/build/',
            logger: $logger,
        );
    }

    public function testJsIsMinifiedAndVersioned()
    {
        $handler = $this->createAssetsHandler();

        $assets = $this->createAssets(
            $this->createAsset(file: 'js/app.js', content: "console.log('hi');   ")
        );

        $result = $handler->handle($assets);

        // We expect exactly one asset
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('js/app.js', $result);

        $asset = $result['js/app.js'];

        // File should have version query
        $this->assertStringStartsWith('js/app.js?v=', $asset->getFile());

        // Dir should be the store dir
        $this->assertSame(
            $this->normalizePath($this->storeDir),
            $this->normalizePath($asset->getDir())
        );

        // Uri should be the configured assetUri
        $this->assertSame('https://example.com/assets/build/', $asset->getUri());

        // And the minified file should exist in the store dir (without query)
        $this->assertFileExists($this->normalizePath($this->storeDir.'js/app.js'));
        
        // The minifier removes trailing whitespace, so this should be minified
        $minified = file_get_contents($this->storeDir.'js/app.js');

        $this->assertSame("console.log('hi');", $minified);
    }
    
    public function testCssIsMinifiedAndVersioned()
    {
        $handler = $this->createAssetsHandler();

        $assets = $this->createAssets(
            $this->createAsset(file: 'css/app.css', content: "body { color: red; }    ")
        );

        $result = $handler->handle($assets);

        // We expect exactly one asset
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('css/app.css', $result);

        $asset = $result['css/app.css'];

        // File should have version query
        $this->assertStringStartsWith('css/app.css?v=', $asset->getFile());

        // Dir should be the store dir
        $this->assertSame(
            $this->normalizePath($this->storeDir),
            $this->normalizePath($asset->getDir())
        );

        // Uri should be the configured assetUri
        $this->assertSame('https://example.com/assets/build/', $asset->getUri());

        // And the minified file should exist in the store dir (without query)
        $this->assertFileExists($this->normalizePath($this->storeDir.'css/app.css'));

        // The minifier removes trailing whitespace, so this should be minified
        $minified = file_get_contents($this->storeDir.'css/app.css');

        // Expected minified CSS (CSS minifier removes unnecessary whitespace)
        $this->assertSame('@charset "utf-8";body{color:red;}', $minified);
    }
    
    public function testCustomMinifiersExceptComments()
    {
        $handler = $this->createAssetsHandler()->withMinifiers(
            css: new Factory\CssMinifierFactory()->except('comments'),
            js: new Factory\JavaScriptMinifierFactory()->except('comments'),
        );

        $assets = $this->createAssets(
            $this->createAsset('css/app.css', "@charset \"utf-8\"; /* comment */ body { color: red; } "),
            $this->createAsset('js/app.js', "/* comment */ console.log('hi'); ")
        );

        $result = $handler->handle($assets);

        // CSS
        $this->assertArrayHasKey('css/app.css', $result);
        $css = $result['css/app.css'];

        $this->assertStringStartsWith('css/app.css?v=', $css->getFile());
        $this->assertFileExists($this->storeDir.'css/app.css');

        $cssMinified = file_get_contents($this->storeDir.'css/app.css');

        // Comments preserved, whitespace removed
        $this->assertSame('@charset "utf-8";/* comment */ body{color:red;}', $cssMinified);

        // JS
        $this->assertArrayHasKey('js/app.js', $result);
        $js = $result['js/app.js'];

        $this->assertStringStartsWith('js/app.js?v=', $js->getFile());
        $this->assertFileExists($this->storeDir.'js/app.js');

        $jsMinified = file_get_contents($this->storeDir.'js/app.js');

        // Comments preserved, whitespace removed
        $this->assertSame("/* comment */ console.log('hi');", $jsMinified);
    }
    
    public function testWithoutMinifiers()
    {
        $handler = $this->createAssetsHandler()->withMinifiers(
            css: null,
            js: null,
        );

        $assets = $this->createAssets(
            $this->createAsset('css/app.css', "@charset \"utf-8\"; /* comment */ body { color: red; } "),
            $this->createAsset('js/app.js', "/* comment */ console.log('hi'); ")
        );

        $result = $handler->handle($assets);

        // CSS
        $this->assertArrayHasKey('css/app.css', $result);
        $css = $result['css/app.css'];

        // Versioned file name
        $this->assertStringStartsWith('css/app.css?v=', $css->getFile());

        // File exists in store dir
        $this->assertFileExists($this->storeDir.'css/app.css');

        // Content must be EXACTLY as provided (no minification)
        $cssContent = file_get_contents($this->storeDir.'css/app.css');
        $this->assertSame("@charset \"utf-8\"; /* comment */ body { color: red; } ", $cssContent);

        // JS
        $this->assertArrayHasKey('js/app.js', $result);
        $js = $result['js/app.js'];

        // Versioned file name
        $this->assertStringStartsWith('js/app.js?v=', $js->getFile());

        // File exists in store dir
        $this->assertFileExists($this->storeDir.'js/app.js');

        // Content must be EXACTLY as provided (no minification)
        $jsContent = file_get_contents($this->storeDir.'js/app.js');
        $this->assertSame("/* comment */ console.log('hi'); ", $jsContent);
    }
    
    public function testSkipMinify()
    {
        $handler = $this->createAssetsHandler()->skipMinify(
            'css/foo.css',          // exact match
            'js/vendor/*.js',       // glob match
            '*.bundle.js',          // glob match
        );

        $assets = $this->createAssets(
            // Should NOT be minified (exact match)
            $this->createAsset('css/foo.css', "/* comment */ body { color: red; } "),

            // Should NOT be minified (glob match)
            $this->createAsset('js/vendor/lib.js', "/* comment */ console.log('hi'); "),

            // Should NOT be minified (glob match)
            $this->createAsset('js/app.bundle.js', "/* comment */ console.log('bundle'); "),

            // Should BE minified (no match)
            $this->createAsset('css/app.css', "/* comment */ body { color: blue; } "),
        );

        $result = $handler->handle($assets);

        // css/foo.css — skipped
        $this->assertFileDoesNotExist($this->storeDir.'css/foo.css');

        // js/vendor/lib.js — skipped
        $this->assertFileDoesNotExist($this->storeDir.'js/vendor/lib.js');

        // js/app.bundle.js — skipped
        $this->assertFileDoesNotExist($this->storeDir.'js/app.bundle.js');

        // css/app.css — NOT skipped → minified
        $this->assertFileExists($this->storeDir.'css/app.css');
    }
    
    public function testSkipAlways()
    {
        // skipAlways defaults to ['*.min.css', '*.min.js']
        $handler = $this->createAssetsHandler();

        $assets = $this->createAssets(
            // Should ALWAYS be skipped (matches *.min.css)
            $this->createAsset('css/style.min.css', "body { color: red; }"),

            // Should ALWAYS be skipped (matches *.min.js)
            $this->createAsset('js/app.min.js', "console.log('hi');"),

            // Should be processed normally
            $this->createAsset('css/app.css', "body { color: blue; }"),
        );

        $handler->handle($assets);

        // These must NOT be written to build directory
        $this->assertFileDoesNotExist($this->storeDir.'css/style.min.css');
        $this->assertFileDoesNotExist($this->storeDir.'js/app.min.js');

        // This one SHOULD be written (normal processing)
        $this->assertFileExists($this->storeDir.'css/app.css');
    }
    
    public function testJavaScriptMinifyRewritesComplexRelativeImports()
    {
        $handler = $this->createAssetsHandler();

        $assets = $this->createAssets(
            $this->createAsset(
                'js/app/app.js',
                <<<JS
                    import a from './foo.js';
                    import b from '../bar.js';
                    import c from './../baz.js';
                    import d from '../../utils/qux.js';
                    import e from './nested/../weird/./path.js';

                    console.log(a, b, c, d, e);
                JS
            )
        );
        
        $this->createAsset('js/app/foo.js', "export default 'foo';");
        $this->createAsset('js/bar.js', "export default 'bar';");
        $this->createAsset('js/baz.js', "export default 'baz';");
        $this->createAsset('utils/qux.js', "export default 'qux';");
        $this->createAsset('js/app/weird/path.js', "export default 'path';");
        
        $result = $handler->handle($assets);

        // app.js must be written to build directory
        $this->assertFileExists($this->storeDir.'js/app/app.js');

        $content = file_get_contents($this->storeDir.'js/app/app.js');

        // Extract the single version used for all assets
        $version = explode('?', $result['js/app/app.js']->getFile())[1];

        // Assert rewritten imports (paths unchanged, version appended)
        $this->assertStringContainsString(
            sprintf("import a from './foo.js?%s';", $version),
            $content
        );

        $this->assertStringContainsString(
            sprintf("import b from '../bar.js?%s';", $version),
            $content
        );

        $this->assertStringContainsString(
            sprintf("import c from './../baz.js?%s';", $version),
            $content
        );

        $this->assertStringContainsString(
            sprintf("import d from '../../utils/qux.js?%s';", $version),
            $content
        );

        $this->assertStringContainsString(
            sprintf("import e from './nested/../weird/./path.js?%s';", $version),
            $content
        );

        // Minified code must remain
        $this->assertStringContainsString("console.log(a, b, c, d, e);", $content);
        
        $this->assertFileExists($this->storeDir.'js/app/foo.js');
        $this->assertFileExists($this->storeDir.'js/bar.js');
        $this->assertFileExists($this->storeDir.'js/baz.js');
        $this->assertFileExists($this->storeDir.'utils/qux.js');
        $this->assertFileExists($this->storeDir.'js/app/weird/path.js');
        
        // Assert imported files are minified
        $foo = file_get_contents($this->storeDir.'js/app/foo.js');
        $bar = file_get_contents($this->storeDir.'js/bar.js');
        $baz = file_get_contents($this->storeDir.'js/baz.js');
        $qux = file_get_contents($this->storeDir.'utils/qux.js');
        $path = file_get_contents($this->storeDir.'js/app/weird/path.js');

        // No newlines (minified)
        $this->assertStringNotContainsString("\n", $foo);
        $this->assertStringNotContainsString("\n", $bar);
        $this->assertStringNotContainsString("\n", $baz);
        $this->assertStringNotContainsString("\n", $qux);
        $this->assertStringNotContainsString("\n", $path);

        // No comments
        $this->assertStringNotContainsString("//", $foo);
        $this->assertStringNotContainsString("//", $bar);
        $this->assertStringNotContainsString("//", $baz);
        $this->assertStringNotContainsString("//", $qux);
        $this->assertStringNotContainsString("//", $path);

        // Still contains expected export
        $this->assertStringContainsString("export default", $foo);
    }
    
    public function testJavaScriptMinifyRewritesImportsAndSharesVersionBetweenAssets()
    {
        $handler = $this->createAssetsHandler();

        $assets = $this->createAssets(
            $this->createAsset(
                'js/app.js',
                <<<JS
                    import a from './modal.js';
                    import b from './foo.js';

                    console.log(a);
                JS
            ),
            $this->createAsset(
                'js/modal.js',
                <<<JS
                    import b from './foo.js';

                    console.log(b);
                JS
            ) 
        );
        
        $this->createAsset('js/foo.js', "export default 'foo';");

        $result = $handler->handle($assets);

        $this->assertFileExists($this->storeDir.'js/app.js');

        $content = file_get_contents($this->storeDir.'js/app.js');

        // Extract the single version used for all assets
        $version = explode('?', $result['js/app.js']->getFile())[1];

        // Assert rewritten imports (paths unchanged, version appended)
        $this->assertStringContainsString(
            sprintf("import a from './modal.js?%s';", $version),
            $content
        );
        
        $this->assertStringContainsString(
            sprintf("import b from './foo.js?%s';", $version),
            $content
        );
        
        $modalVersion = explode('?', $result['js/modal.js']->getFile())[1];
        $this->assertSame($version, $modalVersion);
        
        $this->assertSame(['js/app.js', 'js/modal.js'], array_keys($result));
    }
    
    public function testJavaScriptMinifyHandlesCircularImportsWithSharedVersion()
    {
        $handler = $this->createAssetsHandler();

        $assets = $this->createAssets(
            $this->createAsset(
                'js/a.js',
                <<<JS
                    import b from './b.js';
                    console.log('a', b);
                JS
            ),
            $this->createAsset(
                'js/b.js',
                <<<JS
                    import a from './a.js';
                    console.log('b', a);
                JS
            )
        );

        $result = $handler->handle($assets);

        // Both files must exist in build directory
        $this->assertFileExists($this->storeDir.'js/a.js');
        $this->assertFileExists($this->storeDir.'js/b.js');

        $aContent = file_get_contents($this->storeDir.'js/a.js');
        $bContent = file_get_contents($this->storeDir.'js/b.js');

        // Extract version from one file
        $version = explode('?', $result['js/a.js']->getFile())[1];

        // Both must share the same version
        $bVersion = explode('?', $result['js/b.js']->getFile())[1];
        $this->assertSame($version, $bVersion);

        // Imports must be rewritten with the same version
        $this->assertStringContainsString(
            sprintf("import b from './b.js?%s';", $version),
            $aContent
        );

        $this->assertStringContainsString(
            sprintf("import a from './a.js?%s';", $version),
            $bContent
        );

        // Both assets must be present in result
        $this->assertSame(['js/a.js', 'js/b.js'], array_keys($result));
    }
    
    public function testJavaScriptMinifyRewritesImportsWithoutVersioningWhenDisabled()
    {
        $handler = $this->createAssetsHandler()
            ->versioning(false); // Disable versioning

        $assets = $this->createAssets(
            $this->createAsset(
                'js/app.js',
                <<<JS
                    import foo from './foo.js';
                    console.log(foo);
                JS
            )
        );

        // foo.js exists and should be processed, but NOT versioned
        $this->createAsset('js/foo.js', "export default 'foo';");

        $result = $handler->handle($assets);

        // app.js must be written to build directory
        $this->assertFileExists($this->storeDir.'js/app.js');

        $content = file_get_contents($this->storeDir.'js/app.js');

        // Assert import is rewritten WITHOUT versioning
        $this->assertStringContainsString(
            "import foo from './foo.js';",
            $content
        );

        // Ensure no ?v= appears anywhere
        $this->assertStringNotContainsString('?v=', $content);

        // Only app.js should be in the result
        $this->assertSame(['js/app.js'], array_keys($result));
    }

    public function testAssetsUriIsModified()
    {
        $handler = $this->createAssetsHandler();

        $assets = $this->createAssets(
            $this->createAsset('js/app.js', "console.log('hi');"),
            $this->createAsset('css/style.css', "body { color: red; }")
        );

        $result = $handler->handle($assets);

        // Ensure both assets exist
        $this->assertArrayHasKey('js/app.js', $result);
        $this->assertArrayHasKey('css/style.css', $result);

        // Both assets must have the modified URI
        $this->assertSame(
            'https://example.com/assets/build/',
            $result['js/app.js']->getUri()
        );

        $this->assertSame(
            'https://example.com/assets/build/',
            $result['css/style.css']->getUri()
        );
    }
    
    public function testAssetsAreVersionedByDefault()
    {
        $handler = $this->createAssetsHandler();

        $assets = $this->createAssets(
            $this->createAsset('js/app.js', "console.log('hi');"),
            $this->createAsset('css/style.css', "body { color: red; }")
        );

        $result = $handler->handle($assets);

        // Both assets must exist
        $this->assertArrayHasKey('js/app.js', $result);
        $this->assertArrayHasKey('css/style.css', $result);

        // Version must be appended to the file path
        $this->assertMatchesRegularExpression(
            '#^js/app\.js\?v=[a-f0-9]+$#',
            $result['js/app.js']->getFile()
        );

        $this->assertMatchesRegularExpression(
            '#^css/style\.css\?v=[a-f0-9]+$#',
            $result['css/style.css']->getFile()
        );
    }
    
    public function testAssetsAreNotVersionedWhenDisabled()
    {
        $handler = $this->createAssetsHandler()
            ->versioning(false);

        $assets = $this->createAssets(
            $this->createAsset('js/app.js', "console.log('hi');"),
            $this->createAsset('css/style.css', "body { color: red; }")
        );

        $result = $handler->handle($assets);

        // Both assets must exist
        $this->assertArrayHasKey('js/app.js', $result);
        $this->assertArrayHasKey('css/style.css', $result);

        // Version must NOT be appended
        $this->assertSame(
            'js/app.js',
            $result['js/app.js']->getFile()
        );

        $this->assertSame(
            'css/style.css',
            $result['css/style.css']->getFile()
        );
    }
    
    public function testAssetIsRebuiltWhenSourceFileIsNewer()
    {
        $handler = $this->createAssetsHandler();

        // First version of the asset
        $assetV1 = $this->createAsset('js/app.js', "console.log('v1');");
        $assetsV1 = $this->createAssets($assetV1);

        $resultV1 = $handler->handle($assetsV1);

        $fileV1 = $resultV1['js/app.js']->getFile();
        $this->assertMatchesRegularExpression('#\?v=[a-f0-9]+$#', $fileV1);
        
        sleep(1);
        
        // Second version of the asset (changed content)
        $assetV2 = $this->createAsset('js/app.js', "console.log('v2');");
        $assetsV2 = $this->createAssets($assetV2);

        $resultV2 = $handler->handle($assetsV2);

        $fileV2 = $resultV2['js/app.js']->getFile();
        $this->assertMatchesRegularExpression('#\?v=[a-f0-9]+$#', $fileV2);
        
        $content = file_get_contents($this->storeDir.'js/app.js');
        
        // Version must change because content changed
        $this->assertNotSame($fileV1, $fileV2);
    }
    
    public function testAssetIsNotRebuiltWhenSourceFileIsNotNewer()
    {
        $handler = $this->createAssetsHandler();

        // First version of the asset
        $assetV1 = $this->createAsset('js/app.js', "console.log('same');");
        $assetsV1 = $this->createAssets($assetV1);

        $resultV1 = $handler->handle($assetsV1);
        $fileV1 = $resultV1['js/app.js']->getFile();

        // Second run with identical content and same mtime
        $assetV2 = $this->createAsset('js/app.js', "console.log('same');");
        $assetsV2 = $this->createAssets($assetV2);

        $resultV2 = $handler->handle($assetsV2);
        $fileV2 = $resultV2['js/app.js']->getFile();

        // Version must stay the same because source file is not newer
        $this->assertSame($fileV1, $fileV2);
    }
    
    public function testMultipleAssetsAreReplaced()
    {
        $handler = $this->createAssetsHandler()
            ->versioning(false)
            ->replace(
                file: 'css/foo.css',
                with: 'css/bar.css',
            )
            ->replace(
                file: 'js/alpha.js',
                with: 'js/beta.js',
            );

        // Create all assets
        $foo = $this->createAsset('css/foo.css',   "body { color: red; }");
        $bar = $this->createAsset('css/bar.css',   "body { color: blue; }");

        $alpha = $this->createAsset('js/alpha.js',   "console.log('alpha');");
        $beta = $this->createAsset('js/beta.js',    "console.log('beta');");

        $assets = $this->createAssets($foo, $bar, $alpha, $beta);

        $result = $handler->handle($assets);

        // foo.css must be removed
        $this->assertArrayNotHasKey('css/foo.css', $result);
        
        // bar.css must remain
        $this->assertArrayHasKey('css/bar.css', $result);
        $this->assertSame(
            '@charset "utf-8";body{color:blue;}',
            file_get_contents($this->storeDir.'css/bar.css')
        );
        
        $cssAsset = $result['css/bar.css'];
        $this->assertSame('css/bar.css', $cssAsset->getFile());

        // alpha.js must be removed
        $this->assertArrayNotHasKey('js/alpha.js', $result);

        // beta.js must remain
        $this->assertArrayHasKey('js/beta.js', $result);
        $this->assertSame(
            "console.log('beta');",
            file_get_contents($this->storeDir.'js/beta.js')
        );
        
        $this->assertSame(['css/bar.css', 'js/beta.js'], array_keys($result));
    }
    
    public function testAssetsAreCombinedIntoSingleFile()
    {
        $handler = $this->createAssetsHandler()
            ->combine(
                filename: 'app.css',
                files: [
                    'css/basis.css',
                    'css/app.css',
                ]
            );

        // Create the source files
        $basis = $this->createAsset('css/basis.css', "body { margin: 0; }");
        $app   = $this->createAsset('css/app.css',   "h1 { color: red; }");

        $assets = $this->createAssets($basis, $app);

        $result = $handler->handle($assets);

        // Only the combined file should remain
        $this->assertSame(['app.css'], array_keys($result));

        // Combined file must exist in the store directory
        $combinedPath = $this->storeDir . 'app.css';
        $this->assertFileExists($combinedPath);

        // Check combined + minified content
        $content = file_get_contents($combinedPath);

        $this->assertSame(
            '@charset "utf-8";body{margin:0;}h1{color:red;}',
            $content
        );
    }
    
    public function testCombinedAssetIsNotRebuiltWhenUnchanged()
    {
        $handler = $this->createAssetsHandler()
            ->combine(
                filename: 'app.css',
                files: [
                    'css/basis.css',
                    'css/app.css',
                ]
            );

        // Create source files
        $basis = $this->createAsset('css/basis.css', "body { margin: 0; }");
        $app   = $this->createAsset('css/app.css',   "h1 { color: red; }");

        $assets = $this->createAssets($basis, $app);

        // First run → creates combined file
        $handler->handle($assets);

        $combinedPath = $this->storeDir . 'app.css';
        $this->assertFileExists($combinedPath);

        // Capture timestamp
        $mtimeBefore = filemtime($combinedPath);

        // Sleep 1 second to ensure mtime difference is detectable
        sleep(1);

        // Second run → should NOT rebuild
        $handler->handle($assets);

        $mtimeAfter = filemtime($combinedPath);
        
        // Assert file was NOT rewritten
        $this->assertSame($mtimeBefore, $mtimeAfter);
    }
    
    public function testCombinedAssetIsRebuiltWhenSourceChanges()
    {
        $handler = $this->createAssetsHandler()
            ->combine(
                filename: 'app.css',
                files: [
                    'css/basis.css',
                    'css/app.css',
                ]
            );

        // First run: original sources
        $basisV1 = $this->createAsset('css/basis.css', "body { margin: 0; }");
        $appV1 = $this->createAsset('css/app.css',   "h1 { color: red; }");

        $assetsV1 = $this->createAssets($basisV1, $appV1);

        $resultV1 = $handler->handle($assetsV1);
        /** @var CombinedAsset $combinedV1 */
        $combinedV1 = $resultV1['app.css'];
        $combinedPath = $this->storeDir . 'app.css';
        $mtimeBefore = filemtime($combinedPath);

        $this->assertSame(
            '@charset "utf-8";body{margin:0;}h1{color:red;}',
            file_get_contents($combinedPath)
        );
        
        sleep(1);

        // Second run: basis.css changed
        $basisV2 = $this->createAsset('css/basis.css', "body { margin: 10px; }");
        $appV2 = $this->createAsset('css/app.css',   "h1 { color: red; }");

        $assetsV2 = $this->createAssets($basisV2, $appV2);

        $resultV2 = $handler->handle($assetsV2);
        /** @var CombinedAsset $combinedV2 */
        $combinedV2 = $resultV2['app.css'];
        $combinedPath2 = $this->storeDir . 'app.css';
        $mtimeAfter = filemtime($combinedPath2);

        // Must have been rebuilt
        $this->assertGreaterThan($mtimeBefore, $mtimeAfter);

        $content = file_get_contents($combinedPath2);

        $this->assertSame(
            '@charset "utf-8";body{margin:10px;}h1{color:red;}',
            $content
        );
    }
    
    public function testAssetsHandlerClearRemovesStoreDirectory()
    {
        $handler = $this->createAssetsHandler();

        // Build an asset so the directory exists and contains files
        $asset = $this->createAsset('js/app.js', "console.log('clear-test');");
        $assets = $this->createAssets($asset);

        $handler->handle($assets);

        // Ensure the store directory exists before clearing
        $this->assertDirectoryExists($this->storeDir);

        // Clear everything
        $handler->clear();

        // The entire store directory should now be gone
        $this->assertDirectoryDoesNotExist($this->storeDir);
    }
    
    public function testLoggingIsCalledWhenAssetFailsToLoad()
    {
        $logger = new class implements LoggerInterface {
            public array $logs = [];

            public function log($level, $message, array $context = []): void
            {
                $this->logs[] = compact('level', 'message', 'context');
            }
            
            public function emergency($message, array $context = []): void { $this->log(LogLevel::EMERGENCY, $message, $context); }
            public function alert($message, array $context = []): void { $this->log(LogLevel::ALERT, $message, $context); }
            public function critical($message, array $context = []): void { $this->log(LogLevel::CRITICAL, $message, $context); }
            public function error($message, array $context = []): void { $this->log(LogLevel::ERROR, $message, $context); }
            public function warning($message, array $context = []): void { $this->log(LogLevel::WARNING, $message, $context); }
            public function notice($message, array $context = []): void { $this->log(LogLevel::NOTICE, $message, $context); }
            public function info($message, array $context = []): void { $this->log(LogLevel::INFO, $message, $context); }
            public function debug($message, array $context = []): void { $this->log(LogLevel::DEBUG, $message, $context); }
        };

        $handler = $this->createAssetsHandler(logger: $logger);

        // Asset points to a file that does NOT exist
        $assets = $this->createAssets(
            $this->createAsset(
                'js/app/app.js',
                <<<JS
                    import a from './foo.js';
                    console.log(a);
                JS
            )
        );

        // Trigger processing
        $handler->handle($assets);

        // Assert logging happened
        $this->assertNotEmpty($logger->logs);
        $this->assertSame(LogLevel::ERROR, $logger->logs[0]['level']);
    }
}