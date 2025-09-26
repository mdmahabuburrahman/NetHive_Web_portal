<?php
require_once 'auth_check.php';
require_once 'routeros_api.class.php';

// Require admin/operator for user logs
checkAuth(['admin', 'operator']);

class UserLogsOperations {
    private $api;
    
    public function __construct() {
        $this->api = new RouterosAPI();
    }
    
    private function getNasDetails($nasId) {
        $nasDetailsFile = '../data/nas_details.json';
        if (!file_exists($nasDetailsFile)) {
            return null;
        }
        
        $nasDetails = json_decode(file_get_contents($nasDetailsFile), true);
        foreach ($nasDetails as $nas) {
            if ($nas['id'] === $nasId) {
                return $nas;
            }
        }
        return null;
    }
    
    public function checkLoggingStatus($nasId) {
        try {
            $nas = $this->getNasDetails($nasId);
            if (!$nas) {
                return ['success' => false, 'message' => 'NAS not found'];
            }
            
            list($nasIP, $port) = explode(':', $nas['nas_ip_port']);
            $this->api->port = (int)$port;
            
            if (!$this->api->connect($nasIP, $nas['username'], $nas['password'])) {
                return ['success' => false, 'message' => 'Failed to connect to NAS: ' . $this->api->error_str];
            }
            
            // Get all logging actions
            $allActions = $this->api->comm('/system/logging/action/print');
            
            $this->api->disconnect();
            
            // Search for syslog186 in all actions
            $configured = false;
            if (is_array($allActions)) {
                foreach ($allActions as $action) {
                    if (is_array($action) && isset($action['name']) && $action['name'] === 'syslog186') {
                        $configured = true;
                        break;
                    }
                }
            }
            
            return [
                'success' => true, 
                'configured' => $configured, 
                'debug' => 'Total actions: ' . count($allActions) . ', syslog186 found: ' . ($configured ? 'yes' : 'no'),
                'allActions' => $allActions
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function configureLogging($nasId) {
        try {
            $nas = $this->getNasDetails($nasId);
            if (!$nas) {
                return ['success' => false, 'message' => 'NAS not found'];
            }
            
            list($nasIP, $port) = explode(':', $nas['nas_ip_port']);
            $this->api->port = (int)$port;
            
            if (!$this->api->connect($nasIP, $nas['username'], $nas['password'])) {
                return ['success' => false, 'message' => 'Failed to connect to NAS'];
            }
            
            // Check if syslog186 action already exists
            $allActions = $this->api->comm('/system/logging/action/print');
            
            $actionExists = false;
            if (is_array($allActions)) {
                foreach ($allActions as $action) {
                    if (is_array($action) && isset($action['name']) && $action['name'] === 'syslog186') {
                        $actionExists = true;
                        break;
                    }
                }
            }
            
            if (!$actionExists) {
                // Get RouterOS version
                $versionInfo = $this->api->comm('/system/resource/print');
                $version = '';
                if (is_array($versionInfo) && isset($versionInfo[0]['version'])) {
                    $version = $versionInfo[0]['version'];
                }
                
                // Use v7 config only for versions above 7.15.2
                $useV7Config = false;
                if (preg_match('/^7\.(\d+)\.(\d+)/', $version, $matches)) {
                    $major = (int)$matches[1];
                    $minor = (int)$matches[2];
                    $useV7Config = ($major > 15) || ($major == 15 && $minor > 2);
                }
                
                // Add logging action based on version
                if ($useV7Config) {
                    // RouterOS V7 configuration
                    $addResult = $this->api->comm('/system/logging/action/add', [
                        'name' => 'syslog186',
                        'target' => 'remote',
                        'remote' => '103.117.192.186',
                        'src-address' => $nasIP,
                        'syslog-facility' => 'local5',
                        'remote-log-format' => 'syslog'
                    ]);
                } else {
                    // RouterOS V6 configuration
                    $addResult = $this->api->comm('/system/logging/action/add', [
                        'name' => 'syslog186',
                        'target' => 'remote',
                        'remote' => '103.117.192.186',
                        'src-address' => $nasIP,
                        'syslog-facility' => 'local5',
                        'bsd-syslog' => 'yes'
                    ]);
                }
                
                // Update default logging (get ID first)
                $defaultLogs = $this->api->comm('/system/logging/print');
                $defaultId = null;
                if (is_array($defaultLogs)) {
                    foreach ($defaultLogs as $log) {
                        if (isset($log['.id']) && isset($log['topics']) && strpos($log['topics'], 'info') !== false) {
                            $defaultId = $log['.id'];
                            break;
                        }
                    }
                }
                
                $setResult = null;
                if ($defaultId) {
                    $setResult = $this->api->comm('/system/logging/set', [
                        '.id' => $defaultId,
                        'topics' => 'info,!firewall'
                    ]);
                }
                
                // Add firewall logging
                $logResult = $this->api->comm('/system/logging/add', [
                    'action' => 'syslog186',
                    'topics' => 'firewall'
                ]);
                
                // Verify configuration was applied
                $verifyActions = $this->api->comm('/system/logging/action/print');
                $actionConfigured = false;
                if (is_array($verifyActions)) {
                    foreach ($verifyActions as $action) {
                        if (is_array($action) && isset($action['name']) && $action['name'] === 'syslog186') {
                            $actionConfigured = true;
                            break;
                        }
                    }
                }
                
                $this->api->disconnect();
                
                if (!$actionConfigured) {
                    return ['success' => false, 'message' => 'Failed to create syslog186 action'];
                }
                
                return ['success' => true, 'message' => 'Logging configured successfully'];
            } else {
                $this->api->disconnect();
                return ['success' => true, 'message' => 'Logging already configured'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

// Handle API requests
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_logs') {
    $log_base_path = '/var/log/remotelogs/hotspotRT_parsed';
    $nas = $_GET['nas'] ?? '';
    $date = $_GET['date'] ?? '';
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(1000, max(10, (int)($_GET['limit'] ?? 100)));
    $search = $_GET['search'] ?? '';

    if (empty($nas) || empty($date)) {
        echo json_encode(['success' => false, 'error' => 'NAS and date required']);
        exit;
    }

    $log_file = "$log_base_path/$nas/$date.log";
    if (!file_exists($log_file)) {
        echo json_encode(['success' => false, 'error' => 'Log file not found']);
        exit;
    }

    $file_size = filesize($log_file);
    if ($file_size === 0) {
        echo json_encode(['success' => true, 'data' => [], 'total' => 0, 'page' => 1, 'limit' => $limit, 'total_pages' => 0]);
        exit;
    }

    // Fast line counting
    $file = new SplFileObject($log_file, 'r');
    $file->seek(PHP_INT_MAX);
    $total_lines = $file->key() + 1;

    // Calculate pagination
    $offset = ($page - 1) * $limit;
    $total_pages = ceil($total_lines / $limit);

    // Read specific chunk
    $logs = [];
    $file->rewind();
    $collected = 0;

    // Skip to offset
    if ($offset > 0) {
        $file->seek($offset);
    }

    // Collect data
    while (!$file->eof() && $collected < $limit) {
        $line = $file->fgets();
        if ($line === false) break;
        
        $data = str_getcsv(trim($line));
        if (count($data) >= 9) {
            $log_entry = [
                'time' => $data[0],
                'nas_name' => $data[1],
                'user' => $data[2],
                'mac' => $data[3],
                'src_ip' => $data[4],
                'src_port' => $data[5],
                'dst_ip' => $data[6],
                'dst_port' => $data[7],
                'protocol' => $data[8]
            ];
            
            // Apply search filter
            if (empty($search) || 
                stripos($log_entry['user'], $search) !== false ||
                stripos($log_entry['src_ip'], $search) !== false ||
                stripos($log_entry['dst_ip'], $search) !== false ||
                stripos($log_entry['mac'], $search) !== false) {
                $logs[] = $log_entry;
                $collected++;
            }
        }
    }

    // Reverse to show latest first
    $logs = array_reverse($logs);

    echo json_encode([
        'success' => true,
        'data' => $logs,
        'total' => $total_lines,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => $total_pages,
        'file_size' => $file_size
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userLogs = new UserLogsOperations();
    
    switch ($_POST['action']) {
        case 'checkLoggingStatus':
            $nasId = $_POST['nasId'] ?? '';
            $result = $userLogs->checkLoggingStatus($nasId);
            break;
            
        case 'configureLogging':
            $nasId = $_POST['nasId'] ?? '';
            $result = $userLogs->configureLogging($nasId);
            break;
            
        default:
            $result = ['success' => false, 'message' => 'Invalid action'];
    }
    
    echo json_encode($result);
}
?>