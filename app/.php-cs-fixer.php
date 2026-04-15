<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfig;

$finder = (new Finder())
    ->in(__DIR__ . '/config')
    ->in(__DIR__ . '/migrations')
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
    ->exclude('var')
    ->exclude('bin')
    ->exclude('docker')
    ->exclude('DependencyInjection')
    ->exclude(__DIR__ . '/vendor')
    ->notPath('reference.php')
;

$rules = [
    '@PHP8x4Migration' => true,
    '@PhpCsFixer' => true,
    '@Symfony:risky' => true,
    'declare_strict_types' => true,
    'global_namespace_import' => true,
    'concat_space' => [
        'spacing' => 'one'
    ],
    'method_argument_space' => [
        'on_multiline' => 'ensure_fully_multiline',
    ],
    'single_line_throw' => false,
    'types_spaces' => ['space_multiple_catch' => 'single'],
    'phpdoc_order' => ['order' => [
        'deprecated',
        'internal',
        'param',
        'return',
        'throws',
    ]],
    'phpdoc_separation' => ['groups' => [['ORM\\*'], ['Assert\\*'], ['Serializer\\*'], ['Constraints\\*']]],
];

return (new Config())
    ->setCacheFile(__DIR__ . '/var/cache/.php-cs-fixer.cache')
    ->setParallelConfig(new ParallelConfig(4))
    ->setRiskyAllowed(true)
    ->setLineEnding("\n")
    ->setRules($rules)
    ->setFinder($finder);
