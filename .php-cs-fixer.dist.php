<?php

/*
 * This document has been generated with
 * https://mlocati.github.io/php-cs-fixer-configurator/#version:2.16.4|configurator
 * you can change this configuration by importing this file.
 */
$config = new PhpCsFixer\Config();
return $config
    ->setRiskyAllowed(true)
    ->setRules([
        '@DoctrineAnnotation' => true,
        '@PSR1' => true,
        '@PSR2' => true,
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,
        '@Symfony' => true,
        '@Symfony:risky' => true,
        '@PHP73Migration' => true,
        '@PHP71Migration:risky' => true,
        'backtick_to_shell_exec' => true,
        'date_time_immutable' => false,
        'global_namespace_import' => true,
        'linebreak_after_opening_tag' => true,
        'list_syntax' => ['syntax' => 'short'],
        'mb_str_functions' => true,
        'no_php4_constructor' => true,
        'phpdoc_line_span' => ['const' => 'single'],
        'self_static_accessor' => true,
        'static_lambda' => true,
    ])
    ->setFinder(PhpCsFixer\Finder::create()
        ->in(__DIR__.'/src')
    )
;
