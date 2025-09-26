<?php
require_once 'auth_check.php';
require_once 'routeros_api.class.php';

// Require admin/operator for internet control
checkAuth(['admin', 'operator']);

header('Content-Type: application/json');

function loadNasData()
{
    $dataFile = '../data/nas_details.json';
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

$input = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? $_POST['action'] ?? $input['action'] ?? '';

try {
    switch ($action) {
        case 'get_pools':
            echo json_encode(getPools());
            break;
        case 'get_active_users':
            echo json_encode(getActiveUsers());
            break;
        case 'get_user_pool':
            echo json_encode(getUserPool());
            break;
        case 'control_internet':
            echo json_encode(controlInternet());
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function getPools()
{
    $nasId = $_GET['nas_id'] ?? null;
    if (!$nasId) {
        return ['success' => false, 'error' => 'NAS ID required'];
    }

    $nas = getNasById($nasId);
    if (!$nas) {
        return ['success' => false, 'error' => 'NAS device not found'];
    }

    $ipPort = explode(':', $nas['nas_ip_port']);
    $ip = $ipPort[0];
    $port = isset($ipPort[1]) ? (int)$ipPort[1] : 8728;

    $api = new RouterosAPI();
    $api->port = $port;
    if (!$api->connect($ip, $nas['username'], $nas['password'])) {
        return ['success' => false, 'error' => 'Failed to connect to MikroTik'];
    }

    $pools = $api->comm('/ip/pool/print');
    $profiles = $api->comm('/ip/hotspot/user/profile/print');
    $activeUsers = $api->comm('/ip/hotspot/active/print');
    $api->disconnect();
    
    $poolData = [];
    foreach ($pools as $pool) {
        $poolName = $pool['name'];
        $associatedProfile = null;
        
        // Find associated hotspot profile
        foreach ($profiles as $profile) {
            if (isset($profile['address-pool']) && $profile['address-pool'] === $poolName) {
                $associatedProfile = $profile['name'];
                break;
            }
        }
        
        // Only include pools that are assigned to hotspot profiles
        if ($associatedProfile === null) {
            continue;
        }
        
        $activeCount = 0;
        foreach ($activeUsers as $user) {
            if (isIpInRange($user['address'], $pool['ranges'])) {
                $activeCount++;
            }
        }
        
        $poolData[] = [
            'name' => $poolName,
            'ranges' => $pool['ranges'],
            'profile' => $associatedProfile,
            'active_users' => $activeCount,
            'bandwidth_usage' => formatBytes(0),
            'status' => 'Active'
        ];
    }
    
    return ['success' => true, 'data' => $poolData];
}

function getActiveUsers()
{
    $nasId = $_GET['nas_id'] ?? null;
    if (!$nasId) {
        return ['success' => false, 'error' => 'NAS ID required'];
    }

    $nas = getNasById($nasId);
    if (!$nas) {
        return ['success' => false, 'error' => 'NAS device not found'];
    }

    $ipPort = explode(':', $nas['nas_ip_port']);
    $ip = $ipPort[0];
    $port = isset($ipPort[1]) ? (int)$ipPort[1] : 8728;

    $api = new RouterosAPI();
    $api->port = $port;
    if (!$api->connect($ip, $nas['username'], $nas['password'])) {
        return ['success' => false, 'error' => 'Failed to connect to MikroTik'];
    }

    $activeUsers = $api->comm('/ip/hotspot/active/print');
    $api->disconnect();
    
    $userData = [];
    foreach ($activeUsers as $user) {
        $userData[] = [
            'username' => $user['user'],
            'ip' => $user['address'],
            'display' => $user['user'] . ' (' . $user['address'] . ')'
        ];
    }
    
    return ['success' => true, 'data' => $userData];
}

function getUserPool()
{
    global $input;
    $nasId = $_POST['nas_id'] ?? $input['nas_id'] ?? null;
    $userIp = $_POST['user_ip'] ?? $input['user_ip'] ?? '';
    
    if (!$nasId) {
        return ['success' => false, 'error' => 'NAS ID required'];
    }

    $nas = getNasById($nasId);
    if (!$nas) {
        return ['success' => false, 'error' => 'NAS device not found'];
    }

    $ipPort = explode(':', $nas['nas_ip_port']);
    $ip = $ipPort[0];
    $port = isset($ipPort[1]) ? (int)$ipPort[1] : 8728;

    $api = new RouterosAPI();
    $api->port = $port;
    if (!$api->connect($ip, $nas['username'], $nas['password'])) {
        return ['success' => false, 'error' => 'Failed to connect to MikroTik'];
    }

    $pools = $api->comm('/ip/pool/print');
    $api->disconnect();
    
    foreach ($pools as $pool) {
        if (isIpInRange($userIp, $pool['ranges'])) {
            return [
                'success' => true, 
                'data' => [
                    'pool_name' => $pool['name'],
                    'pool_range' => $pool['ranges']
                ]
            ];
        }
    }
    
    return ['success' => false, 'error' => 'Pool not found for this IP'];
}

function controlInternet()
{
    global $input;
    $nasId = $_POST['nas_id'] ?? $input['nas_id'] ?? null;
    $userIp = $_POST['user_ip'] ?? $input['user_ip'] ?? '';
    $poolRange = $_POST['pool_range'] ?? $input['pool_range'] ?? '';
    
    if (!$nasId) {
        return ['success' => false, 'error' => 'NAS ID required'];
    }

    $nas = getNasById($nasId);
    if (!$nas) {
        return ['success' => false, 'error' => 'NAS device not found'];
    }

    $ipPort = explode(':', $nas['nas_ip_port']);
    $ip = $ipPort[0];
    $port = isset($ipPort[1]) ? (int)$ipPort[1] : 8728;

    $api = new RouterosAPI();
    $api->port = $port;
    if (!$api->connect($ip, $nas['username'], $nas['password'])) {
        return ['success' => false, 'error' => 'Failed to connect to MikroTik'];
    }

    $blockRule = [
        'chain' => 'forward',
        'src-address' => $poolRange,
        'action' => 'drop',
        'comment' => 'NetHive-Pool-Block-' . time()
    ];
    
    $blockResult = $api->comm('/ip/firewall/filter/add', $blockRule);
    
    $allowRule = [
        'chain' => 'forward',
        'src-address' => $userIp,
        'action' => 'accept',
        'comment' => 'NetHive-User-Allow-' . time()
    ];
    
    $api->comm('/ip/firewall/filter/add', $allowRule);
    $api->disconnect();
    
    if (isset($blockResult['!trap'])) {
        return ['success' => false, 'error' => $blockResult['!trap'][0]['message'] ?? 'Failed to create firewall rules'];
    }
    
    return ['success' => true, 'message' => 'Internet access controlled successfully'];
}

function isIpInRange($ip, $range) {
    if (strpos($range, '-') !== false) {
        list($start, $end) = explode('-', $range);
        return ip2long($ip) >= ip2long(trim($start)) && ip2long($ip) <= ip2long(trim($end));
    } elseif (strpos($range, '/') !== false) {
        list($subnet, $mask) = explode('/', $range);
        $subnet = ip2long($subnet);
        $ip = ip2long($ip);
        $mask = -1 << (32 - $mask);
        return ($ip & $mask) == ($subnet & $mask);
    }
    return false;
}

function formatBytes($bytes) {
    if ($bytes == 0) return '0 B';
    $k = 1024;
    $sizes = ['B', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}
?>