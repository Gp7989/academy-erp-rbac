<?php
require_once __DIR__ . '/auth_helper.php';
require_once __DIR__ . '/../database/db.php';

function resolveTenantOwnerId(int $userId): ?int
{
    global $conn;

    $stmt = $conn->prepare("SELECT id, owner_id FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row) {
        return null;
    }

    return $row['owner_id'] === null ? (int) $row['id'] : (int) $row['owner_id'];
}

function getUserPermissions(int $userId): array
{
    global $conn;

    $ownerId = resolveTenantOwnerId($userId);
    if (!$ownerId) {
        return [];
    }

    $permissions = [];

    $stmt = $conn->prepare("SELECT owner_id FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && $user['owner_id'] === null) {
        $all = $conn->query("SELECT permission_name FROM permissions");
        while ($row = $all->fetch_assoc()) {
            $permissions[] = $row['permission_name'];
        }

        return array_values(array_unique($permissions));
    }

    $stmt = $conn->prepare(" 
        SELECT DISTINCT p.permission_name
        FROM permissions p
        JOIN role_permissions rp ON rp.permission_id = p.id
        JOIN user_roles ur ON ur.role_id = rp.role_id
        JOIN roles r ON r.id = ur.role_id
        WHERE ur.user_id = ?
          AND r.owner_id = ?
    ");
    $stmt->bind_param("ii", $userId, $ownerId);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $permissions[] = $row['permission_name'];
    }

    $stmt = $conn->prepare(" 
        SELECT p.permission_name
        FROM permissions p
        JOIN user_permissions up ON up.permission_id = p.id
        WHERE up.user_id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $permissions[] = $row['permission_name'];
    }

    return array_values(array_unique($permissions));
}

function hasPermission(string $permission): bool
{
    $userId = currentUserId();
    if (!$userId) {
        return false;
    }

    $aliases = [
        'create' => 'create_form',
        'edit' => 'edit_form',
        'view' => 'view_form',
        'delete' => 'delete_form',
    ];

    $effective = $aliases[$permission] ?? $permission;
    $permissions = getUserPermissions($userId);

    return in_array($effective, $permissions, true);
}

function isAdmin(): bool
{
    global $conn;

    $userId = currentUserId();
    if (!$userId) {
        return false;
    }

    $stmt = $conn->prepare("SELECT owner_id FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    return $row && $row['owner_id'] === null;
}
