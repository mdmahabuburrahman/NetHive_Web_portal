<?php
require_once 'auth_check.php';

// Require admin role for NAS management
checkAuth(['admin']);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$dataFile = '../data/nas_details.json';

function loadNasData()
{
    global $dataFile;
    if (!file_exists($dataFile)) {
        file_put_contents($dataFile, '[]');
    }
    return json_decode(file_get_contents($dataFile), true);
}

function saveNasData($data)
{
    global $dataFile;
    return file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT));
}

function saveLogoFile($logoData, $nasId)
{
    if (empty($logoData)) return '';

    // Create directory if it doesn't exist
    $logoDir = '../assets/images/views/nas';
    if (!is_dir($logoDir)) {
        mkdir($logoDir, 0755, true);
    }

    // Extract file extension from base64 data
    $extension = 'png'; // default
    if (preg_match('/data:image\/(\w+);base64,/', $logoData, $matches)) {
        $extension = $matches[1];
        $logoData = substr($logoData, strpos($logoData, ',') + 1);
    }

    $filename = $nasId . '.' . $extension;
    $filepath = $logoDir . '/' . $filename;

    if (file_put_contents($filepath, base64_decode($logoData))) {
        return 'assets/images/views/nas/' . $filename;
    }

    return '';
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get':
        echo json_encode(loadNasData());
        break;

    case 'add':
        try {
            // Validate required fields
            $required_fields = ['nas_name', 'nas_ip_port', 'username', 'password', 'hotspot_name'];
            foreach ($required_fields as $field) {
                if (empty($_POST[$field])) {
                    echo json_encode(['success' => false, 'error' => ucfirst(str_replace('_', ' ', $field)) . ' is required']);
                    exit;
                }
            }

            $data = loadNasData();
            $nasId = uniqid();
            $logoPath = saveLogoFile($_POST['logo'] ?? '', $nasId);

            $newNas = [
                'id' => $nasId,
                'nas_name' => trim($_POST['nas_name']),
                'nas_ip_port' => trim($_POST['nas_ip_port']),
                'username' => trim($_POST['username']),
                'password' => $_POST['password'],
                'hotspot_name' => trim($_POST['hotspot_name']),
                'dns_name' => trim($_POST['dns_name'] ?? ''),
                'currency' => trim($_POST['currency'] ?? 'BDT'),
                'session_timeout' => trim($_POST['session_timeout'] ?? ''),
                'live_report' => $_POST['live_report'] ?? 'off',
                'logo' => $logoPath
            ];
            $data[] = $newNas;
            
            if (saveNasData($data)) {
                echo json_encode(['success' => true, 'message' => 'NAS added successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to save NAS data']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'An error occurred: ' . $e->getMessage()]);
        }
        break;

    case 'update':
        try {
            $id = $_POST['id'] ?? '';
            if (empty($id)) {
                echo json_encode(['success' => false, 'error' => 'NAS ID is required']);
                exit;
            }

            // Validate required fields
            $required_fields = ['nas_name', 'nas_ip_port', 'username', 'password', 'hotspot_name'];
            foreach ($required_fields as $field) {
                if (empty($_POST[$field])) {
                    echo json_encode(['success' => false, 'error' => ucfirst(str_replace('_', ' ', $field)) . ' is required']);
                    exit;
                }
            }

            $data = loadNasData();
            $found = false;
            
            foreach ($data as &$nas) {
                if ($nas['id'] === $id) {
                    $nas['nas_name'] = trim($_POST['nas_name']);
                    $nas['nas_ip_port'] = trim($_POST['nas_ip_port']);
                    $nas['username'] = trim($_POST['username']);
                    $nas['password'] = $_POST['password'];
                    $nas['hotspot_name'] = trim($_POST['hotspot_name']);
                    $nas['dns_name'] = trim($_POST['dns_name'] ?? '');
                    $nas['currency'] = trim($_POST['currency'] ?? 'BDT');
                    $nas['session_timeout'] = trim($_POST['session_timeout'] ?? '');
                    $nas['live_report'] = $_POST['live_report'] ?? 'off';
                    
                    if (!empty($_POST['logo'])) {
                        $logoPath = saveLogoFile($_POST['logo'], $id);
                        if ($logoPath) {
                            $nas['logo'] = $logoPath;
                        }
                    }
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                echo json_encode(['success' => false, 'error' => 'NAS not found']);
                exit;
            }
            
            if (saveNasData($data)) {
                echo json_encode(['success' => true, 'message' => 'NAS updated successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to update NAS data']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'An error occurred: ' . $e->getMessage()]);
        }
        break;

    case 'delete':
        try {
            $id = $_POST['id'] ?? '';
            if (empty($id)) {
                echo json_encode(['success' => false, 'error' => 'NAS ID is required']);
                exit;
            }

            $data = loadNasData();
            $originalCount = count($data);
            
            $data = array_filter($data, function ($nas) use ($id) {
                return $nas['id'] !== $id;
            });
            
            if (count($data) === $originalCount) {
                echo json_encode(['success' => false, 'error' => 'NAS not found']);
                exit;
            }
            
            if (saveNasData(array_values($data))) {
                echo json_encode(['success' => true, 'message' => 'NAS deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to delete NAS']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'An error occurred: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
}
