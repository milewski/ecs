<?php

declare(strict_types = 1);

$contents = @file_get_contents($path);
$contents = @\file_get_contents($path);
$contents = @ file_get_contents($path);
$contents = @(file_get_contents($path));
$contents = @$read($path);
$contents = @$reader->read($path);
$contents = @$reader?->read($path);
$contents = @Reader::read($path);
$metadata = @stat($path);
$contents = @file_get_contents(@resolve_path($path));
