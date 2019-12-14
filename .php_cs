<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude([])
    ->notPath([])
    ->in(['_lib/']);

return PhpCsFixer\Config::create()
    ->setRules([
        '@PSR2' => true,
        // 'psr4' => true,
        'no_leading_import_slash' => true,
        'fully_qualified_strict_types' => true,
        'no_unused_imports' => true,
        'ordered_imports'=> [
            'imports_order' =>  null,
            'sort_algorithm' => 'alpha'
        ],
        'array_syntax' => ['syntax' => 'short'],
        'phpdoc_add_missing_param_annotation' => true,
        'phpdoc_indent' => true,
        'phpdoc_types' => true,
        'phpdoc_order' => true,
        'phpdoc_trim' => true,
        'single_line_after_imports' => true,
        'trailing_comma_in_multiline_array' => true,
        'no_blank_lines_after_phpdoc' => true,
        'return_type_declaration' => true,
        // 'logical_operators' => true,
        'concat_space' => ['spacing' => 'one'],
        'cast_spaces' => true,
        'no_blank_lines_after_class_opening' => true,
        'no_empty_statement' => true,
        'no_useless_else' => true,
        'single_quote' => true,
        'yoda_style' => true,
        'unary_operator_spaces' => true,
        'lowercase_cast' => true,
        'binary_operator_spaces' => [
            'operators' => [
                '=' => 'single_space',
            ]
        ],
    ])
    ->setFinder($finder);