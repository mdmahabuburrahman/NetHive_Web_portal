<?php
require_once 'routeros_api.class.php';

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

function connectToNas($nasId)
{
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
        return ['success' => false, 'error' => "Failed to connect to MikroTik at {$ip}:{$port}"];
    }

    return ['success' => true, 'api' => $api];
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_voucher_report':
            echo json_encode(getVoucherReport());
            break;
        case 'get_bandwidth_usage':
            echo json_encode(getBandwidthUsage());
            break;

        case 'get_traffic_report':
            echo json_encode(getTrafficReport());
            break;
        case 'get_system_logs':
            echo json_encode(getSystemLogs());
            break;
        case 'get_hotspot_logs':
            echo json_encode(getHotspotLogs());
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function getVoucherReport()
{
    $nasId = $_GET['nas_id'] ?? null;
    if (!$nasId) {
        return ['success' => false, 'error' => 'NAS ID required'];
    }

    $connection = connectToNas($nasId);
    if (!$connection['success']) {
        return $connection;
    }

    $api = $connection['api'];

    $users = $api->comm('/ip/hotspot/user/print');
    $filteredUsers = array_filter($users, function ($user) {
        return stripos($user['name'], 'default') === false && stripos($user['profile'], 'default') === false;
    });

    $voucherData = [];
    $profileStats = [];

    foreach ($filteredUsers as $user) {
        $profile = $user['profile'] ?? 'Unknown';
        $comment = $user['comment'] ?? '';
        $createdDate = date('Y-m-d', time()); // Fallback date

        // Parse comment for creation date if available
        if (preg_match('/(\d{2}\.\d{2}\.\d{2})/', $comment, $matches)) {
            $createdDate = '20' . str_replace('.', '-', $matches[1]);
        }

        $voucherData[] = [
            'username' => $user['name'],
            'profile' => $profile,
            'comment' => $comment,
            'created_date' => $createdDate,
            'time_limit' => $user['limit-uptime'] ?? 'Unlimited',
            'data_limit' => isset($user['limit-bytes-total']) ? formatBytes($user['limit-bytes-total']) : 'Unlimited',
            'disabled' => $user['disabled'] ?? 'false'
        ];

        if (!isset($profileStats[$profile])) {
            $profileStats[$profile] = ['total' => 0, 'active' => 0, 'disabled' => 0];
        }
        $profileStats[$profile]['total']++;
        if ($user['disabled'] === 'false') {
            $profileStats[$profile]['active']++;
        } else {
            $profileStats[$profile]['disabled']++;
        }
    }

    $api->disconnect();

    return [
        'success' => true,
        'data' => [
            'vouchers' => $voucherData,
            'profile_stats' => $profileStats,
            'total_vouchers' => count($voucherData)
        ]
    ];
}

function formatBytes($bytes)
{
    if (!$bytes || $bytes == 0) return '0 B';
    $k = 1024;
    $sizes = ['B', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

function getBandwidthUsage()
{
    $nasId = $_GET['nas_id'] ?? null;
    if (!$nasId) {
        return ['success' => false, 'error' => 'NAS ID required'];
    }

    $connection = connectToNas($nasId);
    if (!$connection['success']) {
        return $connection;
    }

    $api = $connection['api'];

    $activeUsers = $api->comm('/ip/hotspot/active/print');
    $bandwidthData = [];
    $totalRx = 0;
    $totalTx = 0;

    foreach ($activeUsers as $user) {
        $rxBytes = isset($user['bytes-in']) ? intval($user['bytes-in']) : 0;
        $txBytes = isset($user['bytes-out']) ? intval($user['bytes-out']) : 0;

        $bandwidthData[] = [
            'user' => $user['user'] ?? 'Unknown',
            'address' => $user['address'] ?? '',
            'uptime' => $user['uptime'] ?? '0s',
            'rx_bytes' => $rxBytes,
            'tx_bytes' => $txBytes,
            'total_bytes' => $rxBytes + $txBytes,
            'rx_formatted' => formatBytes($rxBytes),
            'tx_formatted' => formatBytes($txBytes),
            'total_formatted' => formatBytes($rxBytes + $txBytes)
        ];

        $totalRx += $rxBytes;
        $totalTx += $txBytes;
    }

    // Sort by total usage
    usort($bandwidthData, function ($a, $b) {
        return $b['total_bytes'] - $a['total_bytes'];
    });

    $api->disconnect();

    return [
        'success' => true,
        'data' => [
            'users' => $bandwidthData,
            'summary' => [
                'total_rx' => formatBytes($totalRx),
                'total_tx' => formatBytes($totalTx),
                'total_usage' => formatBytes($totalRx + $totalTx),
                'active_users' => count($bandwidthData)
            ]
        ]
    ];
}

function getTrafficReport()
{
    $nasId = $_GET['nas_id'] ?? null;
    if (!$nasId) {
        return ['success' => false, 'error' => 'NAS ID required'];
    }

    $connection = connectToNas($nasId);
    if (!$connection['success']) {
        return $connection;
    }

    $api = $connection['api'];

    $interfaces = $api->comm('/interface/print');
    $trafficData = [];

    foreach ($interfaces as $interface) {
        if (isset($interface['name']) && $interface['name'] !== 'lo') {
            $stats = $api->comm('/interface/print', ['?name' => $interface['name']]);
            if (!empty($stats)) {
                $stat = $stats[0];
                $trafficData[] = [
                    'interface' => $interface['name'],
                    'rx_bytes' => isset($stat['rx-byte']) ? intval($stat['rx-byte']) : 0,
                    'tx_bytes' => isset($stat['tx-byte']) ? intval($stat['tx-byte']) : 0,
                    'rx_packets' => isset($stat['rx-packet']) ? intval($stat['rx-packet']) : 0,
                    'tx_packets' => isset($stat['tx-packet']) ? intval($stat['tx-packet']) : 0,
                    'status' => $interface['running'] ?? 'false'
                ];
            }
        }
    }

    $api->disconnect();

    return [
        'success' => true,
        'data' => $trafficData
    ];
}

function getSystemLogs()
{
    $nasId = $_GET['nas_id'] ?? null;
    if (!$nasId) {
        return ['success' => false, 'error' => 'NAS ID required'];
    }

    $connection = connectToNas($nasId);
    if (!$connection['success']) {
        return $connection;
    }

    $api = $connection['api'];

    //$logs = $api->comm('/log/print');
    $logs = $api->comm('/log/print', ['?topics' => 'system,info']);

    $formattedLogs = [];
    if (!empty($logs)) {
        foreach (array_slice($logs, -50) as $log) {
            $formattedLogs[] = [
                'time' => $log['time'] ?? '',
                'topics' => $log['topics'] ?? '',
                'message' => $log['message'] ?? ''
            ];
        }
    }

    $api->disconnect();

    return [
        'success' => true,
        'data' => array_reverse($formattedLogs)
    ];
}

function getHotspotLogs()
{
    $nasId = $_GET['nas_id'] ?? null;
    if (!$nasId) {
        return ['success' => false, 'error' => 'NAS ID required'];
    }

    $connection = connectToNas($nasId);
    if (!$connection['success']) {
        return $connection;
    }

    $api = $connection['api'];

    $logs = $api->comm('/log/print', ['?topics' => 'hotspot']);

    $formattedLogs = [];
    if (!empty($logs)) {
        foreach (array_slice($logs, -30) as $log) {
            $formattedLogs[] = [
                'time' => $log['time'] ?? '',
                'message' => $log['message'] ?? '',
                'topics' => $log['topics'] ?? ''
            ];
        }
    }

    $api->disconnect();

    return [
        'success' => true,
        'data' => array_reverse($formattedLogs)
    ];
}
