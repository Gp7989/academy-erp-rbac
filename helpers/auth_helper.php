<?php
// helpers/auth_helper.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function currentUserId(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header("Location: ../auth/login.php");
        exit();
    }
}

/**
 * Tenant owner id for current user (owner users resolve to themselves).
 */
function tenantOwnerId(): ?int
{
    if (isset($_SESSION['tenant_owner_id'])) {
        return (int) $_SESSION['tenant_owner_id'];
    }

    return null;
}
