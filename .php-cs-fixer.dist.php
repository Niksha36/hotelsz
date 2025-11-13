<?php

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->name('*.php')
    ->exclude('vendor')
;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true) // нужны для некоторых правил (declare_strict_types, fully_qualified_strict_types)
    ->setRules([
        '@PSR12' => true,

        // импорты
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],

        // строки и кавычки
        'single_quote' => ['strings_containing_single_quote_chars' => false],
        'line_ending' => true,

        // убрать лишние пробелы / пустые строки
        'trim_array_spaces' => true,
        'no_extra_blank_lines' => ['tokens' => ['extra', 'throw', 'use', 'break', 'continue', 'return']],
        'no_whitespace_in_blank_line' => true,
        'single_blank_line_at_eof' => true,

        // строгие типы (все файлы) — ВНИМАНИЕ: risky, может изменить совместимость
        'declare_strict_types' => true,

        // импортирование глобальных классов/функций/констант
        'global_namespace_import' => [
            'import_classes'   => true,
            'import_functions' => true,
            'import_constants' => true,
        ],

        // опционально: приводит FQCN к импорту (риск — включи, если хочешь)
        'fully_qualified_strict_types' => [
            'import_symbols' => true,
        ],

        // phpdoc: не конвертировать некоторые docblocks в обычные комментарии
        // это помогает не поломать @psalm-* заметки
        'phpdoc_to_comment' => false,

        // другие полезные мелочи
        'no_trailing_whitespace' => true,
        'no_trailing_whitespace_in_comment' => true,
    ])
    ->setFinder($finder);
