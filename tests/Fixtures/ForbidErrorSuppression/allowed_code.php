<?php

declare(strict_types = 1);

$contents = file_get_contents($path);
$contents = \file_get_contents($path);
$metadata = stat($path);
$example = '@file_get_contents($path)';
$email = "reader@example.com";
$template = <<<TEXT
@file_get_contents($path)
TEXT;
$literal = <<<'TEXT'
@file_get_contents($path)
TEXT;

// @file_get_contents($path)
/* @file_get_contents($path) */
/** @var string $contents */
