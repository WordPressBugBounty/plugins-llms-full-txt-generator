<?php
$js = __DIR__ . '/build/index.js';
$version = file_exists($js) ? filemtime($js) : '1.0';
$deps = ['react', 'react-dom', 'wp-element', 'wp-i18n'];

file_put_contents(
    __DIR__ . '/build/assets.asset.php',
    "<?php\nreturn " . var_export(['dependencies' => $deps, 'version' => $version], true) . ";\n"
);