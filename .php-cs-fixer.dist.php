<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use PhpCsFixer\Fixer\ClassNotation\VisibilityRequiredFixer;
use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\Import\OrderedImportsFixer;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;
use PhpCsFixer\Fixer\Whitespace\BlankLineBeforeStatementFixer;
use PhpCsFixer\Fixer\Whitespace\NoExtraBlankLinesFixer;
use PhpCsFixer\Fixer\ControlStructure\TrailingCommaInMultilineFixer;
use PhpCsFixer\Fixer\FunctionNotation\ReturnTypeDeclarationFixer;
use PhpCsFixer\Fixer\ClassNotation\SingleClassElementPerStatementFixer;
use PhpCsFixer\Fixer\Import\GlobalNamespaceImportFixer;

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/app',
        __DIR__ . '/config',
        __DIR__ . '/database/migrations',
        __DIR__ . '/database/seeders',
        __DIR__ . '/routes',
        __DIR__ . '/tests',
    ])
    ->notPath('bootstrap')
    ->notPath('vendor')
    ->notPath('storage')
    ->name('*.php');

$config = new PhpCsFixer\Config();
$config->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        '@PSR12:risky' => true,
        '@PhpCsFixer' => true,
        'declare_strict_types' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'blank_line_before_statement' => [
            'statements' => ['return', 'throw', 'try', 'if', 'declare'],
        ],
        'trailing_comma_in_multiline' => [
            'elements' => ['arrays', 'arguments', 'parameters'],
        ],
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => null,
            'import_functions' => null,
        ],
        'concat_space' => ['spacing' => 'one'],
        'cast_spaces' => ['space' => 'single'],
        'class_attributes_separation' => [
            'elements' => [
                'method' => 'one',
                'property' => 'one',
                'trait_import' => 'none',
            ],
        ],
        'single_line_comment_style' => false,
        'phpdoc_align' => ['align' => 'vertical'],
        'phpdoc_summary' => false,
        'phpdoc_separation' => false,
        'yoda_style' => false,
        'native_function_invocation' => false,
    ])
    ->setFinder($finder);

return $config;
