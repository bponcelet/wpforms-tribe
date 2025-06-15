<?php

namespace WPFormsTribe\Vendor;

// Don't redefine the functions if included multiple times.
if (!\function_exists('WPFormsTribe\\Vendor\\GuzzleHttp\\Psr7\\str')) {
    require __DIR__ . '/functions.php';
}
