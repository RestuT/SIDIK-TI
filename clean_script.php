<?php
$directory = new RecursiveDirectoryIterator(__DIR__);
$iterator = new RecursiveIteratorIterator($directory);
$files = new RegexIterator($iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

$duplicate_pattern = '/(\[ \t\]*\/\/ Inject base tag to preserve relative link resolution\s*if \(!document\.querySelector\(\'base\'\)\) \{\s*var base = document\.createElement\(\'base\'\);\s*base\.href = window\.location\.href\.split\(\'\?\'\)\[0\];\s*document\.head\.appendChild\(base\);\s*\})\s*(\/\/ Inject base tag to preserve relative link resolution\s*if \(!document\.querySelector\(\'base\'\)\) \{\s*var base = document\.createElement\(\'base\'\);\s*base\.href = window\.location\.href\.split\(\'\?\'\)\[0\];\s*document\.head\.appendChild\(base\);\s*\})/s';

$count = 0;
foreach ($files as $fileInfo) {
    $filePath = $fileInfo[0];
    if (basename($filePath) === 'replace_script.php' || basename($filePath) === 'clean_script.php') continue;
    $content = file_get_contents($filePath);
    
    // Look for duplicates without regex just to be safe
    $block = <<<EOT
        // Inject base tag to preserve relative link resolution
        if (!document.querySelector('base')) {
            var base = document.createElement('base');
            base.href = window.location.href.split('?')[0];
            document.head.appendChild(base);
        }
EOT;
    
    $double_block = $block . "\n" . $block;
    
    if (strpos($content, $double_block) !== false) {
        $content = str_replace($double_block, $block, $content);
        file_put_contents($filePath, $content);
        echo "Cleaned: $filePath\n";
        $count++;
    }
}
echo "Total files cleaned: $count\n";
?>
