<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/core/Bootstrap.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/CSRF.php';

// Admin pages guard: only admin/editor can pass (enforced in Auth::login + requireLogin)
Auth::requireLogin();
