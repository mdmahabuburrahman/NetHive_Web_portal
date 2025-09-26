<?php
require_once 'auth_check.php';
require_once 'routeros_api.class.php';

// Require admin/operator for web blocking
checkAuth(['admin', 'operator']);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

function logDebug($message)
{
    $logFile = '../data/webblocking_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[{$timestamp}] {$message}\n", FILE_APPEND | LOCK_EX);
}

function getNasDetails($nas_id)
{
    $nasFile = '../data/nas_details.json';
    if (!file_exists($nasFile)) return null;

    $nasData = json_decode(file_get_contents($nasFile), true);
    if (!$nasData) return null;

    foreach ($nasData as $nas) {
        if ($nas['id'] === $nas_id) return $nas;
    }
    return null;
}

function getCategoryUrls()
{
    return [
        'Adware_Malware' => 'https://raw.githubusercontent.com/StevenBlack/hosts/master/hosts',
        'Adware_Malware_Fakenews' => 'https://raw.githubusercontent.com/StevenBlack/hosts/master/alternates/fakenews/hosts',
        'Fakenews' => 'https://raw.githubusercontent.com/StevenBlack/hosts/master/alternates/fakenews-only/hosts',
        'Adware_Malware_Gambling' => 'https://raw.githubusercontent.com/StevenBlack/hosts/master/alternates/gambling/hosts',
        'Gambling' => 'https://raw.githubusercontent.com/StevenBlack/hosts/master/alternates/gambling-only/hosts',
        'Adware_Malware_Porn' => 'https://raw.githubusercontent.com/StevenBlack/hosts/master/alternates/porn/hosts',
        'Porn' => 'https://raw.githubusercontent.com/StevenBlack/hosts/master/alternates/porn-only/hosts',
        'Adware_Malware_Social' => 'https://raw.githubusercontent.com/StevenBlack/hosts/master/alternates/social/hosts',
        'Social' => 'https://raw.githubusercontent.com/StevenBlack/hosts/master/alternates/social-only/hosts',
        'Adware_Malware_Fakenews_Gambling' => 'https://raw.githubusercontent.com/StevenBlack/hosts/master/alternates/fakenews-gambling/hosts',
        'Fakenews_Gambling' => 'https://raw.githubusercontent.com/StevenBlack/hosts/master/alternates/fakenews-gambling-only/hosts',
        'Adware_Malware_Fakenews_Porn' => 'https://raw.githubusercontent.com/StevenBlack/hosts/master/alternates/fakenews-porn/hosts',
        'Fakenews_Porn' => 'https://raw.githubusercontent.com/StevenBlack/hosts/master/alternates/fakenews-porn-only/hosts',
        'Adware_Malware_Fakenews_Social' => 'https://raw.githubusercontent.com/StevenBlack/hosts/master/alternates/fakenews-social/hosts',
        'Fakenews_Social' => 'https://raw.githubusercontent.com/StevenBlack/hosts/master/alternates/fakenews-social-only/hosts',
        'Adware_Malware_Gambling_Porn' => 'https://raw.githubusercontent.com/StevenBlack/hosts/master/alternates/gambling-porn/hosts',
        'Gambling_Porn' => 'https://raw.githubusercontent.com/StevenBlack/hosts/master/alternates/gambling-porn-only/hosts',
        'Adware_Malware_Gambling_Social' => 'https://raw.githubusercontent.com/StevenBlack/hosts/master/alternates/gambling-social/hosts',
        'Gambling_Social' => 'https://raw.githubusercontent.com/StevenBlack/hosts/master/alternates/gambling-social-only/hosts',
        'Adware_Malware_Porn_Social' => 'https://raw.githubusercontent.com/StevenBlack/hosts/master/alternates/porn-social/hosts',
        'Porn_Social' => 'https://raw.githubusercontent.com/StevenBlack/hosts/master/alternates/porn-social-only/hosts',
        'Adware_Malware_Fakenews_Gambling_Porn' => 'https://raw.githubusercontent.com/StevenBlack/hosts/master/alternates/fakenews-gambling-porn/hosts',
        'Fakenews_Gambling_Porn' => 'https://raw.githubusercontent.com/StevenBlack/hosts/master/alternates/fakenews-gambling-porn-only/hosts',
        'Adware_Malware_Fakenews_Gambling_Social' => 'https://raw.githubusercontent.com/StevenBlack/hosts/master/alternates/fakenews-gambling-social/hosts',
        'Fakenews_Gambling_Social' => 'https://raw.githubusercontent.com/StevenBlack/hosts/master/alternates/fakenews-gambling-social-only/hosts',
        'Adware_Malware_Fakenews_Porn_Social' => 'https://raw.githubusercontent.com/StevenBlack/hosts/master/alternates/fakenews-porn-social/hosts',
        'Fakenews_Porn_Social' => 'https://raw.githubusercontent.com/StevenBlack/hosts/master/alternates/fakenews-porn-social-only/hosts',
        'Adware_Malware_Gambling_Porn_Social' => 'https://raw.githubusercontent.com/StevenBlack/hosts/master/alternates/gambling-porn-social/hosts',
        'Gambling_Porn_Social' => 'https://raw.githubusercontent.com/StevenBlack/hosts/master/alternates/gambling-porn-social-only/hosts',
        'Adware_Malware_Fakenews_Gambling_Porn_Social' => 'https://raw.githubusercontent.com/StevenBlack/hosts/master/alternates/fakenews-gambling-porn-social/hosts',
        'Fakenews_Gambling_Porn_Social' => 'https://raw.githubusercontent.com/StevenBlack/hosts/master/alternates/fakenews-gambling-porn-social-only/hosts'
    ];
}



function pushCategoryToMikrotik($nas_details, $category, $url)
{
    $ipPort = explode(':', $nas_details['nas_ip_port']);
    $ip = $ipPort[0];
    $port = isset($ipPort[1]) ? (int)$ipPort[1] : 8728;

    $api = new RouterosAPI();
    $api->port = $port;
    if (!$api->connect($ip, $nas_details['username'], $nas_details['password'])) {
        throw new Exception("Cannot connect to MikroTik router");
    }

    $api->comm('/ip/dns/adlist/add', [
        'url' => $url,
        'ssl-verify' => 'no'
    ]);

    $api->disconnect();
    return true;
}

function pushDomainToMikrotik($nas_details, $domain, $category)
{
    $ipPort = explode(':', $nas_details['nas_ip_port']);
    $ip = $ipPort[0];
    $port = isset($ipPort[1]) ? (int)$ipPort[1] : 8728;

    $api = new RouterosAPI();
    $api->port = $port;
    if (!$api->connect($ip, $nas_details['username'], $nas_details['password'])) {
        throw new Exception("Cannot connect to MikroTik router");
    }

    $api->comm('/ip/dns/static/add', [
        'cname' => '.',
        'name' => $domain,
        'type' => 'CNAME',
        'comment' => "WebBlocking-$category"
    ]);

    $api->disconnect();
    return true;
}

function loadBlockedDomains($nas_id)
{
    $dataFile = '../data/webBlocking.json';
    if (!file_exists($dataFile)) {
        return ['custom_domains' => [], 'active_category' => null];
    }
    $allData = json_decode(file_get_contents($dataFile), true);
    return $allData[$nas_id] ?? ['custom_domains' => [], 'active_category' => null];
}

function saveBlockedDomains($nas_id, $data)
{
    $dataFile = '../data/webBlocking.json';
    $allData = [];
    if (file_exists($dataFile)) {
        $allData = json_decode(file_get_contents($dataFile), true) ?: [];
    }
    $allData[$nas_id] = $data;
    return file_put_contents($dataFile, json_encode($allData, JSON_PRETTY_PRINT));
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    $nas_id = $_GET['nas_id'] ?? '';

    switch ($action) {
        case 'get_blocked_domains':
            if (empty($nas_id)) {
                echo json_encode(['success' => false, 'error' => 'NAS ID is required']);
                exit;
            }
            $data = loadBlockedDomains($nas_id);
            echo json_encode([
                'success' => true,
                'custom_domains' => $data['custom_domains'],
                'active_category' => $data['active_category']
            ]);
            break;

        case 'get_categories':
            $categories = array_keys(getCategoryUrls());
            echo json_encode(['success' => true, 'categories' => $categories]);
            break;

        case 'get_server_ip':
            $defaultIP = $_SERVER['SERVER_ADDR'] ?? '192.168.1.100';
            echo json_encode(['success' => true, 'ip' => $defaultIP]);
            break;

        case 'check_dns_status':
            if (empty($nas_id)) {
                echo json_encode(['success' => false, 'error' => 'NAS ID is required']);
                exit;
            }
            $nas_details = getNasDetails($nas_id);
            if (!$nas_details) {
                echo json_encode(['success' => false, 'error' => 'NAS not found']);
                exit;
            }

            $ipPort = explode(':', $nas_details['nas_ip_port']);
            $ip = $ipPort[0];
            $port = isset($ipPort[1]) ? (int)$ipPort[1] : 8728;

            $api = new RouterosAPI();
            $api->port = $port;
            if (!$api->connect($ip, $nas_details['username'], $nas_details['password'])) {
                echo json_encode(['success' => false, 'error' => 'Cannot connect to router']);
                exit;
            }

            // Check if WebBlocking DNS rules exist
            $rules = $api->comm('/ip/firewall/filter/print', ['?comment' => 'WebBlocking-DNS-Block-TCP']);
            $configured = !empty($rules);

            $api->disconnect();
            echo json_encode(['success' => true, 'configured' => $configured]);
            break;



        case 'get_dns_rules':
            if (empty($nas_id)) {
                echo json_encode(['success' => false, 'error' => 'NAS ID is required']);
                exit;
            }
            $nas_details = getNasDetails($nas_id);
            if (!$nas_details) {
                echo json_encode(['success' => false, 'error' => 'NAS not found']);
                exit;
            }

            $ipPort = explode(':', $nas_details['nas_ip_port']);
            $ip = $ipPort[0];
            $port = isset($ipPort[1]) ? (int)$ipPort[1] : 8728;

            $api = new RouterosAPI();
            $api->port = $port;
            if (!$api->connect($ip, $nas_details['username'], $nas_details['password'])) {
                echo json_encode(['success' => false, 'error' => 'Cannot connect to router']);
                exit;
            }

            $rules = [];

            // Get custom domain CNAME entries
            $staticEntries = $api->comm('/ip/dns/static/print', ['?type' => 'CNAME', '?comment' => 'WebBlocking-Custom']);
            foreach ($staticEntries as $entry) {
                $rules[] = [
                    'name' => $entry['name'] ?? '',
                    'address' => 'CNAME: .',
                    'category' => 'Custom',
                    'status' => 'Blocked'
                ];
            }

            // Get adlist entries
            $adlists = $api->comm('/ip/dns/adlist/print');
            foreach ($adlists as $adlist) {
                $rules[] = [
                    'name' => 'Adlist URL',
                    'address' => $adlist['url'] ?? '',
                    'category' => 'Category List',
                    'status' => 'Active'
                ];
            }

            $api->disconnect();
            echo json_encode(['success' => true, 'rules' => $rules]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $nas_id = $input['nas_id'] ?? '';

    if (empty($nas_id)) {
        echo json_encode(['success' => false, 'error' => 'NAS ID is required']);
        exit;
    }

    $nas_details = getNasDetails($nas_id);
    if (!$nas_details) {
        echo json_encode(['success' => false, 'error' => 'NAS not found']);
        exit;
    }



    switch ($action) {
        case 'add_custom_domain':
            $domain = trim($input['domain'] ?? '');
            if (empty($domain)) {
                echo json_encode(['success' => false, 'error' => 'Domain is required']);
                exit;
            }

            $data = loadBlockedDomains($nas_id);
            if (in_array($domain, $data['custom_domains'])) {
                echo json_encode(['success' => false, 'error' => 'Domain already blocked']);
                exit;
            }

            // Add to MikroTik
            pushDomainToMikrotik($nas_details, $domain, 'Custom');

            $data['custom_domains'][] = $domain;
            saveBlockedDomains($nas_id, $data);

            echo json_encode(['success' => true, 'message' => 'Domain blocked successfully']);
            break;

        case 'remove_custom_domain':
            $domain = trim($input['domain'] ?? '');
            if (empty($domain)) {
                echo json_encode(['success' => false, 'error' => 'Domain is required']);
                exit;
            }

            $data = loadBlockedDomains($nas_id);
            // Remove from MikroTik
            $ipPort = explode(':', $nas_details['nas_ip_port']);
            $ip = $ipPort[0];
            $port = isset($ipPort[1]) ? (int)$ipPort[1] : 8728;

            $api = new RouterosAPI();
            $api->port = $port;
            if ($api->connect($ip, $nas_details['username'], $nas_details['password'])) {
                $staticEntries = $api->comm('/ip/dns/static/print', [
                    '?name' => $domain,
                    '?type' => 'CNAME',
                    '?comment' => 'WebBlocking-Custom'
                ]);
                foreach ($staticEntries as $entry) {
                    $api->comm('/ip/dns/static/remove', ['.id' => $entry['.id']]);
                }
                $api->disconnect();
            }

            $data['custom_domains'] = array_values(array_filter($data['custom_domains'], function ($d) use ($domain) {
                return $d !== $domain;
            }));
            saveBlockedDomains($nas_id, $data);

            echo json_encode(['success' => true, 'message' => 'Domain unblocked successfully']);
            break;

        case 'toggle_category':
            $category = $input['category'] ?? '';
            $enabled = $input['enabled'] ?? false;

            if (empty($category)) {
                echo json_encode(['success' => false, 'error' => 'Category is required']);
                exit;
            }

            $data = loadBlockedDomains($nas_id);

            try {
                if ($enabled) {
                    // Check if another category is already active
                    if ($data['active_category'] !== null) {
                        throw new Exception("Please disable the current category first before enabling another one");
                    }

                    $categoryUrls = getCategoryUrls();
                    if (!isset($categoryUrls[$category])) {
                        throw new Exception("Unknown category: $category");
                    }

                    logDebug("Setting adlist URL for category: $category");
                    pushCategoryToMikrotik($nas_details, $category, $categoryUrls[$category]);

                    $data['active_category'] = $category;
                } else {
                    // Remove from MikroTik
                    $ipPort = explode(':', $nas_details['nas_ip_port']);
                    $ip = $ipPort[0];
                    $port = isset($ipPort[1]) ? (int)$ipPort[1] : 8728;

                    $api = new RouterosAPI();
                    $api->port = $port;
                    if ($api->connect($ip, $nas_details['username'], $nas_details['password'])) {
                        $adlists = $api->comm('/ip/dns/adlist/print');
                        if (!empty($adlists)) {
                            foreach ($adlists as $adlist) {
                                $api->comm('/ip/dns/adlist/remove', ['.id' => $adlist['.id']]);
                            }
                        }
                        $api->disconnect();
                    }

                    $data['active_category'] = null;
                }

                saveBlockedDomains($nas_id, $data);

                $message = $enabled ? "Category '{$category}' enabled and pushed to router successfully" : "Category '{$category}' disabled and removed from router successfully";
                echo json_encode(['success' => true, 'message' => $message]);
            } catch (Exception $e) {
                logDebug("Error toggling category $category: " . $e->getMessage());
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;

        case 'clear_all_blocks':
            $data = ['custom_domains' => [], 'active_category' => null];
            saveBlockedDomains($nas_id, $data);

            // Clear all WebBlocking rules from MikroTik
            $ipPort = explode(':', $nas_details['nas_ip_port']);
            $ip = $ipPort[0];
            $port = isset($ipPort[1]) ? (int)$ipPort[1] : 8728;

            $api = new RouterosAPI();
            $api->port = $port;
            if ($api->connect($ip, $nas_details['username'], $nas_details['password'])) {
                // Remove custom domain CNAME entries
                $staticEntries = $api->comm('/ip/dns/static/print', ['?type' => 'CNAME', '?comment' => 'WebBlocking-Custom']);
                foreach ($staticEntries as $entry) {
                    $api->comm('/ip/dns/static/remove', ['.id' => $entry['.id']]);
                }

                // Remove all adlist entries
                $adlists = $api->comm('/ip/dns/adlist/print');
                foreach ($adlists as $adlist) {
                    $api->comm('/ip/dns/adlist/remove', ['.id' => $adlist['.id']]);
                }
                $api->disconnect();
            }

            echo json_encode(['success' => true, 'message' => 'All blocking rules cleared successfully']);
            break;

        case 'setup_dns_redirect':
            try {
                $ipPort = explode(':', $nas_details['nas_ip_port']);
                $ip = $ipPort[0];
                $port = isset($ipPort[1]) ? (int)$ipPort[1] : 8728;

                $api = new RouterosAPI();
                $api->port = $port;
                if (!$api->connect($ip, $nas_details['username'], $nas_details['password'])) {
                    throw new Exception("Cannot connect to MikroTik router");
                }

                // 1. Get active upstream interface from default route
                $routes = $api->comm('/ip/route/print', ['?dst-address' => '0.0.0.0/0', '?active' => 'true']);
                if (empty($routes)) {
                    throw new Exception("No active default route found");
                }

                $gatewayIP = $routes[0]['gateway'] ?? null;
                if (!$gatewayIP) {
                    throw new Exception("No gateway IP found in default route");
                }

                // Find interface by looking up gateway IP in IP addresses
                $addresses = $api->comm('/ip/address/print');
                $upstreamInterface = null;
                foreach ($addresses as $addr) {
                    $network = $addr['address'] ?? '';
                    if (strpos($network, '/') !== false) {
                        list($ip, $mask) = explode('/', $network);
                        $subnet = ip2long($ip) & ((-1 << (32 - (int)$mask)));
                        $gatewayLong = ip2long($gatewayIP);
                        if (($gatewayLong & ((-1 << (32 - (int)$mask)))) === $subnet) {
                            $upstreamInterface = $addr['interface'] ?? null;
                            break;
                        }
                    }
                }

                if (!$upstreamInterface) {
                    throw new Exception("Cannot determine upstream interface for gateway: $gatewayIP");
                }

                logDebug("Found upstream interface: $upstreamInterface for gateway: $gatewayIP");

                // 2. Add firewall rule to block DNS (TCP/UDP 53) on upstream interface
                $api->comm('/ip/firewall/filter/add', [
                    'chain' => 'input',
                    'in-interface' => $upstreamInterface,
                    'protocol' => 'tcp',
                    'dst-port' => '53',
                    'action' => 'drop',
                    'comment' => 'WebBlocking-DNS-Block-TCP',
                    'place-before' => '0'
                ]);

                $api->comm('/ip/firewall/filter/add', [
                    'chain' => 'input',
                    'in-interface' => $upstreamInterface,
                    'protocol' => 'udp',
                    'dst-port' => '53',
                    'action' => 'drop',
                    'comment' => 'WebBlocking-DNS-Block-UDP',
                    'place-before' => '0'
                ]);

                // 3. Enable allow remote requests in DNS
                $api->comm('/ip/dns/set', ['allow-remote-requests' => 'yes']);

                $api->disconnect();
                echo json_encode(['success' => true, 'message' => 'DNS setup completed successfully']);
            } catch (Exception $e) {
                logDebug("Error setting up DNS: " . $e->getMessage());
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;

        case 'remove_dns_setup':
            try {
                $ipPort = explode(':', $nas_details['nas_ip_port']);
                $ip = $ipPort[0];
                $port = isset($ipPort[1]) ? (int)$ipPort[1] : 8728;

                $api = new RouterosAPI();
                $api->port = $port;
                if (!$api->connect($ip, $nas_details['username'], $nas_details['password'])) {
                    throw new Exception("Cannot connect to MikroTik router");
                }

                // Remove WebBlocking firewall rules
                $tcpRules = $api->comm('/ip/firewall/filter/print', ['?comment' => 'WebBlocking-DNS-Block-TCP']);
                foreach ($tcpRules as $rule) {
                    $api->comm('/ip/firewall/filter/remove', ['.id' => $rule['.id']]);
                }

                $udpRules = $api->comm('/ip/firewall/filter/print', ['?comment' => 'WebBlocking-DNS-Block-UDP']);
                foreach ($udpRules as $rule) {
                    $api->comm('/ip/firewall/filter/remove', ['.id' => $rule['.id']]);
                }

                // Disable allow remote requests
                $api->comm('/ip/dns/set', ['allow-remote-requests' => 'no']);

                $api->disconnect();
                echo json_encode(['success' => true, 'message' => 'DNS setup removed successfully']);
            } catch (Exception $e) {
                logDebug("Error removing DNS setup: " . $e->getMessage());
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
}
