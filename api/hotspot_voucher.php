<?php
require_once 'routeros_api.class.php';

header('Content-Type: application/json');

function loadNasData()
{
    $dataFile = '../data/nas_details.json';
    // Try different paths
    if (!file_exists($dataFile)) {
        $dataFile = 'data/nas_details.json';
    }
    if (!file_exists($dataFile)) {
        $dataFile = './data/nas_details.json';
    }
    
    if (!file_exists($dataFile)) {
        return [];
    }
    
    return json_decode(file_get_contents($dataFile), true);
}

function getNasById($nasId)
{
    $nasDevices = loadNasData();
    foreach ($nasDevices as $nas) {
        if ($nas['id'] === $nasId) {
            return $nas;
        }
    }
    return null;
}

// Random string generators
function randLC($length) {
    return substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, $length);
}

function randUC($length) {
    return substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length);
}

function randULC($length) {
    return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length);
}

function randN($length) {
    return substr(str_shuffle('0123456789'), 0, $length);
}

function randNLC($length) {
    return substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, $length);
}

function randNUC($length) {
    return substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length);
}

function randNULC($length) {
    return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'generate':
            echo json_encode(generateVouchers());
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function generateVouchers()
{
    $nasId = $_POST['nas_id'] ?? null;
    if (!$nasId) {
        return ['success' => false, 'error' => 'NAS ID required'];
    }

    $nas = getNasById($nasId);
    if (!$nas) {
        return ['success' => false, 'error' => 'NAS device not found'];
    }
    


    $qty = (int)($_POST['qty'] ?? 10);
    $server = $_POST['server'] ?? '';
    $user = $_POST['user'] ?? 'up';
    $userl = (int)($_POST['userl'] ?? 6);
    $prefix = $_POST['prefix'] ?? '';
    $char = $_POST['char'] ?? 'mix';
    $profile = $_POST['profile'] ?? '';
    $timelimit = $_POST['timelimit'] ?? '';
    $datalimit = $_POST['datalimit'] ?? '';
    $gcomment = $_POST['gcomment'] ?? '';
    $gencode = $_POST['gencode'] ?? '';

    if (empty($profile)) {
        return ['success' => false, 'error' => 'Profile is required'];
    }

    // Process data limit
    $dlimit = 0;
    $mbgb = 0;
    if (!empty($datalimit)) {
        $dlimit = (float)substr($datalimit, 0, -1);
        $limitbyte = strtolower(substr($datalimit, -1));
        if ($limitbyte == 'm') {
            $mbgb = 1048576;
        } elseif ($limitbyte == 'g') {
            $mbgb = 1073741824;
        }
        $datalimit = $dlimit * $mbgb;
    } else {
        $datalimit = 0;
    }

    $timelimit = empty($timelimit) ? '0' : $timelimit;
    $comment = $user . '-' . $gencode . '-' . date('m.d.y') . '-' . $gcomment;

    $ipPort = explode(':', $nas['nas_ip_port']);
    $ip = $ipPort[0];
    $port = isset($ipPort[1]) ? (int)$ipPort[1] : 8728;

    $api = new RouterosAPI();
    $api->port = $port;
    
    if (!$api->connect($ip, $nas['username'], $nas['password'])) {
        return ['success' => false, 'error' => "Failed to connect to MikroTik at {$ip}:{$port}"];
    }

    $users = [];
    $passwords = [];

    // Generate usernames and passwords
    if ($user == 'up') {
        for ($i = 1; $i <= $qty; $i++) {
            switch ($char) {
                case 'lower':
                    $users[$i] = randLC($userl);
                    break;
                case 'upper':
                    $users[$i] = randUC($userl);
                    break;
                case 'upplow':
                    $users[$i] = randULC($userl);
                    break;
                case 'num':
                    $users[$i] = randN($userl);
                    break;
                case 'mix':
                    $users[$i] = randNLC($userl);
                    break;
                case 'mix1':
                    $users[$i] = randNUC($userl);
                    break;
                case 'mix2':
                    $users[$i] = randNULC($userl);
                    break;
            }
            $passwords[$i] = randN($userl);
            $users[$i] = $prefix . $users[$i];
        }
    } else { // voucher code
        for ($i = 1; $i <= $qty; $i++) {
            switch ($char) {
                case 'num':
                    $users[$i] = randN($userl);
                    break;
                case 'mix':
                    $users[$i] = randNLC($userl);
                    break;
                case 'mix1':
                    $users[$i] = randNUC($userl);
                    break;
                case 'mix2':
                    $users[$i] = randNULC($userl);
                    break;
                default:
                    $users[$i] = randNLC($userl);
            }
            $users[$i] = $prefix . $users[$i];
            $passwords[$i] = $users[$i]; // Same as username for voucher codes
        }
    }

    // Add users to MikroTik
    $addedCount = 0;
    $errors = [];
    for ($i = 1; $i <= $qty; $i++) {
        $params = [
            'name' => $users[$i],
            'password' => $passwords[$i],
            'profile' => $profile,
            'comment' => $comment
        ];
        
        // Only add server if not empty
        if (!empty($server)) {
            $params['server'] = $server;
        }
        
        // Only add time limit if not empty and not '0'
        if (!empty($timelimit) && $timelimit !== '0') {
            $params['limit-uptime'] = $timelimit;
        }
        
        // Only add data limit if greater than 0
        if ($datalimit > 0) {
            $params['limit-bytes-total'] = (string)$datalimit;
        }
        
        $result = $api->comm('/ip/hotspot/user/add', $params);

        if (!isset($result['!trap'])) {
            $addedCount++;
        } else {
            $errors[] = "User {$users[$i]}: " . (isset($result['!trap'][0]['message']) ? $result['!trap'][0]['message'] : 'Unknown error');
        }
    }

    $api->disconnect();

    if ($addedCount == 0) {
        $errorMsg = 'Failed to generate any vouchers';
        if (!empty($errors)) {
            $errorMsg .= ': ' . implode(', ', array_slice($errors, 0, 3));
        }
        return ['success' => false, 'error' => $errorMsg];
    }

    $result = [
        'success' => true,
        'data' => [
            'count' => $addedCount,
            'comment' => $comment,
            'profile' => $profile
        ]
    ];
    
    if (!empty($errors)) {
        $result['warnings'] = $errors;
    }
    
    return $result;
}
?>