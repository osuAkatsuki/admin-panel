<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('vendor')
    ->exclude('node_modules')
    ->exclude('chart')
    ->exclude('fonts')
    ->exclude('images')
    ->exclude('audio')
    ->exclude('palloncini')
    ->exclude('css')
    ->exclude('js')
    ->name('*.php');

$config = new PhpCsFixer\Config();
return $config
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'not_operator_with_successor_space' => true,
        'trailing_comma_in_multiline' => true,
        'phpdoc_scalar' => true,
        'unary_operator_spaces' => true,
        'binary_operator_spaces' => true,
        'blank_line_before_statement' => [
            'statements' => ['break', 'continue', 'declare', 'return', 'throw', 'try'],
        ],
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_var_without_name' => true,
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
            'keep_multiline_spaces_after_colon' => true,
        ],
        'single_trait_insert_per_statement' => true,
        'declare_strict_types' => false, // Keep false for compatibility
        'strict_comparison' => false, // Keep false for gradual migration
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setIndent("\t") // Match existing code style
    ->setLineEnding("\n");
