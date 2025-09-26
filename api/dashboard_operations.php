<?php
require_once 'auth_check.php';
require_once 'routeros_api.class.php';

// Require authentication for all dashboard operations
checkAuth(['admin', 'operator', 'viewer']);

class DashboardController
{
    private $api;
    private $nasData;
    private $selectedNasId;

    public function __construct()
    {
        $this->loadNasData();
        $this->api = new RouterosAPI();
        $this->selectedNasId = $_GET['nas_id'] ?? null;
    }

    private function loadNasData()
    {
        $jsonFile = '../data/nas_details.json';
        if (file_exists($jsonFile)) {
            $jsonContent = file_get_contents($jsonFile);
            $this->nasData = json_decode($jsonContent, true);
        }
    }

    private function getSelectedNas()
    {
        if (!$this->nasData || empty($this->nasData)) {
            return null;
        }

        if ($this->selectedNasId) {
            foreach ($this->nasData as $nas) {
                if ($nas['id'] === $this->selectedNasId) {
                    return $nas;
                }
            }
        }

        return $this->nasData[0]; // Default to first NAS
    }

    private function connectToNas()
    {
        $nas = $this->getSelectedNas();
        if (!$nas) {
            return ['success' => false, 'error' => 'No NAS device found'];
        }

        $ipPort = explode(':', $nas['nas_ip_port']);
        $ip = $ipPort[0];
        $port = isset($ipPort[1]) ? intval($ipPort[1]) : 8728;

        $this->api->port = $port;
        $connected = $this->api->connect($ip, $nas['username'], $nas['password']);

        if (!$connected) {
            $error = 'Connection failed';
            if ($this->api->error_str) {
                $error .= ': ' . $this->api->error_str;
            }
            return ['success' => false, 'error' => $error, 'nas_name' => $nas['nas_name']];
        }

        return ['success' => true, 'nas_name' => $nas['nas_name']];
    }

    public function getConnectionStatus()
    {
        $result = $this->connectToNas();
        if ($result['success']) {
            $this->api->disconnect();
        }
        return json_encode($result);
    }

    public function getNasDevices()
    {
        $devices = [];
        if ($this->nasData) {
            foreach ($this->nasData as $nas) {
                $devices[] = [
                    'id' => $nas['id'],
                    'name' => $nas['nas_name'],
                    'ip' => $nas['nas_ip_port']
                ];
            }
        }
        return json_encode($devices);
    }

    public function getSystemResources()
    {
        $connection = $this->connectToNas();
        if (!$connection['success']) {
            return json_encode($connection);
        }

        $resources = $this->api->comm('/system/resource/print');
        $this->api->disconnect();

        if (!empty($resources)) {
            $resource = $resources[0];
            return json_encode([
                'cpu' => isset($resource['cpu-load']) ? intval($resource['cpu-load']) : 0,
                'memory' => isset($resource['free-memory'], $resource['total-memory']) ?
                    round((($resource['total-memory'] - $resource['free-memory']) / $resource['total-memory']) * 100) : 0,
                'hdd' => isset($resource['free-hdd-space'], $resource['total-hdd-space']) ?
                    round((($resource['total-hdd-space'] - $resource['free-hdd-space']) / $resource['total-hdd-space']) * 100) : 0
            ]);
        }

        return json_encode(['cpu' => 0, 'memory' => 0, 'hdd' => 0]);
    }

    public function getSystemInfo()
    {
        $connection = $this->connectToNas();
        if (!$connection['success']) {
            return json_encode($connection);
        }

        $resource = $this->api->comm('/system/resource/print');
        $routerboard = $this->api->comm('/system/routerboard/print');
        $this->api->disconnect();

        $info = [];
        if (!empty($resource)) {
            $info['uptime'] = isset($resource[0]['uptime']) ? $resource[0]['uptime'] : 'Unknown';
            $info['version'] = isset($resource[0]['version']) ? $resource[0]['version'] : 'Unknown';
        }
        if (!empty($routerboard)) {
            $info['board_name'] = isset($routerboard[0]['board-name']) ? $routerboard[0]['board-name'] : 'Unknown';
            $info['model'] = isset($routerboard[0]['model']) ? $routerboard[0]['model'] : 'Unknown';
        }

        return json_encode($info);
    }

    public function getHotspotUsers()
    {
        $connection = $this->connectToNas();
        if (!$connection['success']) {
            return json_encode($connection);
        }

        $activeUsers = $this->api->comm('/ip/hotspot/active/print');
        $allUsers = $this->api->comm('/ip/hotspot/user/print');
        $this->api->disconnect();

        return json_encode([
            'active' => count($activeUsers),
            'total' => count($allUsers)
        ]);
    }

    public function getInterfaceTraffic($interface = 'ether1')
    {
        $connection = $this->connectToNas();
        if (!$connection['success']) {
            return json_encode($connection);
        }

        // Get interface statistics with monitoring
        $stats = $this->api->comm('/interface/monitor-traffic', [
            'interface' => $interface,
            'duration' => '1'
        ]);

        $this->api->disconnect();

        if (!empty($stats) && isset($stats[0])) {
            $stat = $stats[0];
            return json_encode([
                'rx_bytes' => isset($stat['rx-bits-per-second']) ? intval($stat['rx-bits-per-second']) : 0,
                'tx_bytes' => isset($stat['tx-bits-per-second']) ? intval($stat['tx-bits-per-second']) : 0,
                'timestamp' => time()
            ]);
        }

        return json_encode(['rx_bytes' => 0, 'tx_bytes' => 0, 'timestamp' => time()]);
    }

    public function getHotspotLogs()
    {
        $connection = $this->connectToNas();
        if (!$connection['success']) {
            return json_encode($connection);
        }

        // Get hotspot logs - try different approaches
        $logs = $this->api->comm('/log/print', ['?topics' => 'hotspot']);
        
        // If no hotspot logs, try broader search
        if (empty($logs)) {
            $logs = $this->api->comm('/log/print', ['?topics' => 'hotspot,info']);
        }
        
        // If still empty, get all recent logs
        if (empty($logs)) {
            $logs = $this->api->comm('/log/print');
        }
        
        // Get active hotspot users for IP mapping
        $activeUsers = $this->api->comm('/ip/hotspot/active/print');
        $this->api->disconnect();

        // Create IP to user mapping
        $ipUserMap = [];
        if (!empty($activeUsers)) {
            foreach ($activeUsers as $user) {
                if (isset($user['address']) && isset($user['user'])) {
                    $ipUserMap[$user['address']] = $user['user'];
                }
            }
        }

        $formattedLogs = [];
        if (!empty($logs)) {
            // Filter logs that contain hotspot-related content
            $hotspotLogs = [];
            foreach ($logs as $log) {
                $topics = isset($log['topics']) ? $log['topics'] : '';
                $message = isset($log['message']) ? $log['message'] : '';
                
                // Include if topics contain hotspot or message contains hotspot-related keywords
                if (strpos($topics, 'hotspot') !== false || 
                    strpos(strtolower($message), 'hotspot') !== false ||
                    strpos(strtolower($message), 'login') !== false ||
                    strpos(strtolower($message), 'logout') !== false) {
                    $hotspotLogs[] = $log;
                }
            }
            
            // Get last 10 logs and reverse to show latest first
            $recentLogs = array_reverse(array_slice($hotspotLogs, -10));
            
            foreach ($recentLogs as $log) {
                $message = isset($log['message']) ? $log['message'] : '';
                $userInfo = '';
                
                // Extract IP from log message
                if (preg_match('/\b(?:[0-9]{1,3}\.){3}[0-9]{1,3}\b/', $message, $matches)) {
                    $ip = $matches[0];
                    $user = isset($ipUserMap[$ip]) ? $ipUserMap[$ip] : '';
                    $userInfo = $user ? "$user ($ip)" : $ip;
                }
                
                $formattedLogs[] = [
                    'time' => isset($log['time']) ? $log['time'] : '',
                    'user_ip' => $userInfo,
                    'message' => $message,
                    'topics' => isset($log['topics']) ? $log['topics'] : ''
                ];
            }
        }

        return json_encode($formattedLogs);
    }

    public function getInterfaces()
    {
        $connection = $this->connectToNas();
        if (!$connection['success']) {
            return json_encode($connection);
        }

        $interfaces = $this->api->comm('/interface/print');
        $this->api->disconnect();

        $interfaceList = [];
        if (!empty($interfaces)) {
            foreach ($interfaces as $iface) {
                if (isset($iface['name'])) {
                    $interfaceList[] = $iface['name'];
                }
            }
        }

        return json_encode($interfaceList);
    }

    public function getAppLogs()
    {
        $connection = $this->connectToNas();
        if (!$connection['success']) {
            return json_encode($connection);
        }

        $logs = $this->api->comm('/log/print', ['?topics' => 'system,info']);
        $this->api->disconnect();

        $formattedLogs = [];
        if (!empty($logs)) {
            foreach (array_slice($logs, -10) as $log) {
                $formattedLogs[] = [
                    'time' => isset($log['time']) ? $log['time'] : '',
                    'message' => isset($log['message']) ? $log['message'] : '',
                    'topics' => isset($log['topics']) ? $log['topics'] : ''
                ];
            }
        }

        return json_encode(array_reverse($formattedLogs));
    }
}

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $controller = new DashboardController();
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'connection_status':
            echo $controller->getConnectionStatus();
            break;
        case 'system_resources':
            echo $controller->getSystemResources();
            break;
        case 'system_info':
            echo $controller->getSystemInfo();
            break;
        case 'hotspot_users':
            echo $controller->getHotspotUsers();
            break;
        case 'interface_traffic':
            $interface = $_GET['interface'] ?? 'ether1';
            echo $controller->getInterfaceTraffic($interface);
            break;
        case 'hotspot_logs':
            echo $controller->getHotspotLogs();
            break;
        case 'interfaces':
            echo $controller->getInterfaces();
            break;
        case 'nas_devices':
            echo $controller->getNasDevices();
            break;
        case 'app_logs':
            echo $controller->getAppLogs();
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}
