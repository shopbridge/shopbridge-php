<?php

declare(strict_types=1);

if (!function_exists('enum_exists')) {
    function enum_exists(string $enum, bool $autoload = true): bool
    {
        if ($autoload) {
            return class_exists($enum) || interface_exists($enum);
        }

        return false;
    }
}

require __DIR__ . '/../vendor/autoload.php';
