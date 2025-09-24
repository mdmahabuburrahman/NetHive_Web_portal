<?php
header('Content-Type: application/json');

$dataFile = '../data/users.json';

function loadUsersData()
{
    global $dataFile;
    if (!file_exists($dataFile)) {
        file_put_contents($dataFile, json_encode(['users' => []]));
    }
    $data = json_decode(file_get_contents($dataFile), true);
    return $data['users'] ?? [];
}

function saveUsersData($users)
{
    global $dataFile;
    return file_put_contents($dataFile, json_encode(['users' => $users], JSON_PRETTY_PRINT));
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get':
        echo json_encode(loadUsersData());
        break;

    case 'add':
        $users = loadUsersData();

        // Check if username already exists
        foreach ($users as $user) {
            if ($user['username'] === $_POST['username']) {
                echo json_encode(['success' => false, 'error' => 'Username already exists']);
                exit;
            }
        }

        $newUser = [
            'fullName' => $_POST['fullName'],
            'username' => $_POST['username'],
            'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
            'role' => $_POST['role']
        ];

        $users[] = $newUser;
        saveUsersData($users);
        echo json_encode(['success' => true, 'message' => 'User added successfully']);
        break;

    case 'update':
        $users = loadUsersData();
        $username = $_POST['id'];

        foreach ($users as &$user) {
            if ($user['username'] === $username) {
                $user['fullName'] = $_POST['fullName'];
                $user['role'] = $_POST['role'];

                // Only update password if provided
                if (!empty($_POST['password'])) {
                    $user['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                }
                break;
            }
        }

        saveUsersData($users);
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
        break;

    case 'delete':
        $users = loadUsersData();
        $username = $_POST['username'];

        // Prevent deleting the last admin user
        $adminCount = 0;
        $userToDelete = null;
        foreach ($users as $user) {
            if ($user['role'] === 'admin') {
                $adminCount++;
            }
            if ($user['username'] === $username) {
                $userToDelete = $user;
            }
        }

        if ($userToDelete && $userToDelete['role'] === 'admin' && $adminCount <= 1) {
            echo json_encode(['success' => false, 'error' => 'Cannot delete the last admin user']);
            exit;
        }

        $users = array_filter($users, function ($user) use ($username) {
            return $user['username'] !== $username;
        });

        saveUsersData(array_values($users));
        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
}
