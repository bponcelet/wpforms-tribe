<?php

namespace WPFormsTribe\Vendor;

// Don't redefine the functions if included multiple times.
if (!\function_exists('WPFormsTribe\\Vendor\\GuzzleHttp\\Promise\\promise_for')) {
    require __DIR__ . '/functions.php';
}
