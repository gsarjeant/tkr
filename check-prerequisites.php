#!/usr/bin/env php
<?php
/**
 * tkr Prerequisites Checker - CLI Diagnostic Tool
 *
 * This script provides comprehensive diagnostic information for tkr.
 * It can be run from the command line or uploaded separately for troubleshooting.
 *
 * Usage: php check-prerequisites.php
 */

// Minimal bootstrap just for prerequisites
include_once __DIR__ . '/config/bootstrap.php';

$prerequisites = new Prerequisites();
$results = $prerequisites->validate();

// Exit with appropriate code for shell scripts
if (php_sapi_name() === 'cli') {
    exit(count($prerequisites->getErrors()) > 0 ? 1 : 0);
}