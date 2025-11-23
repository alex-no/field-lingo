<?php
/**
 * PHPUnit Bootstrap File
 *
 * This file is loaded before running tests.
 * It sets up the autoloader and any necessary test environment configuration.
 */

// Composer autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Define test constants if needed
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');
