<?php
$directory = new RecursiveDirectoryIterator(__DIR__);
$iterator = new RecursiveIteratorIterator($directory);
$files = new RegexIterator($iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

$replace = <<<EOT
        // Inject base tag to preserve relative link resolution
        if (!document.querySelector('base')) {
            var base = document.createElement('base');
            base.href = window.location.href.split('?')[0];
            document.head.appendChild(base);
        }
        // Mask URL to root domain
        if (window.history.replaceState) {
            window.history.replaceState(null, null, '/');
        }
EOT;

$count = 0;
foreach ($files as $fileInfo) {
    $filePath = $fileInfo[0];
    if (basename($filePath) === 'replace_script.php') continue;
    $content = file_get_contents($filePath);
    
    // Using regex to catch any variation of quotes and whitespace
    $pattern = '/[ \t]*\/\/ Mask URL to root domain\s*if\s*\(window\.history\.replaceState\)\s*\{\s*window\.history\.replaceState\(null, null, ["\']\/["\']\);\s*\}/s';
    
    // Check if base tag injection is already present
    if (strpos($content, "document.createElement('base')") !== false) {
        continue;
    }

    if (preg_match($pattern, $content)) {
        $content = preg_replace($pattern, "\n" . $replace, $content);
        file_put_contents($filePath, $content);
        echo "Modified: $filePath\n";
        $count++;
    }
}
echo "Total files modified: $count\n";
?>
