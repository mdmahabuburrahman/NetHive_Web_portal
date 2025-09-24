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

$input = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? $_POST['action'] ?? $input['action'] ?? '';

try {
    switch ($action) {
        case 'get_users':
            echo json_encode(getHotspotUsers());
            break;
        case 'get_profiles':
            echo json_encode(getHotspotProfiles());
            break;
        case 'get_active_users':
            echo json_encode(getActiveUsers());
            break;
        case 'get_hosts':
            echo json_encode(getHosts());
            break;
        case 'get_address_pools':
            echo json_encode(getAddressPools());
            break;
        case 'get_parent_queues':
            echo json_encode(getParentQueues());
            break;
        case 'get_servers':
            echo json_encode(getServers());
            break;
        case 'get_user_profile':
            echo json_encode(getUserProfile());
            break;
        case 'add_user_profile':
            echo json_encode(addUserProfile());
            break;
        case 'update_user_profile':
            echo json_encode(updateUserProfile());
            break;
        case 'delete_user':
            echo json_encode(deleteUser());
            break;
        case 'delete_profile':
            echo json_encode(deleteProfile());
            break;
        case 'disconnect_user':
            echo json_encode(disconnectUser());
            break;
        case 'delete_host':
            echo json_encode(deleteHost());
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function getHotspotUsers()
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

    $users = $api->comm('/ip/hotspot/user/print');
    $api->disconnect();

    return ['success' => true, 'data' => $users];
}

function getHotspotProfiles()
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

    $profiles = $api->comm('/ip/hotspot/user/profile/print');
    $api->disconnect();

    return ['success' => true, 'data' => $profiles];
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

    return ['success' => true, 'data' => $activeUsers];
}

function getHosts()
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

    $hosts = $api->comm('/ip/hotspot/host/print');
    $api->disconnect();

    return ['success' => true, 'data' => $hosts];
}

function getAddressPools()
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
    $api->disconnect();

    return ['success' => true, 'data' => $pools];
}

function getParentQueues()
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

    $queues = $api->comm('/queue/simple/print');
    $api->disconnect();

    return ['success' => true, 'data' => $queues];
}

function getServers()
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

    $servers = $api->comm('/ip/hotspot/print');
    $api->disconnect();

    return ['success' => true, 'data' => $servers];
}

function getUserProfile()
{
    $nasId = $_GET['nas_id'] ?? null;
    $profileId = $_GET['profile_id'] ?? null;

    if (!$nasId || !$profileId) {
        return ['success' => false, 'error' => 'NAS ID and Profile ID required'];
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

    $profile = $api->comm('/ip/hotspot/user/profile/print', ['.id' => $profileId]);
    $api->disconnect();

    if (empty($profile)) {
        return ['success' => false, 'error' => 'Profile not found'];
    }

    return ['success' => true, 'data' => $profile[0]];
}

function addUserProfile()
{
    $nasId = $_POST['nas_id'] ?? null;
    if (!$nasId) {
        return ['success' => false, 'error' => 'NAS ID required'];
    }

    $nas = getNasById($nasId);
    if (!$nas) {
        return ['success' => false, 'error' => 'NAS device not found'];
    }

    $name = preg_replace('/\s+/', '-', $_POST['name'] ?? '');
    if (empty($name)) {
        return ['success' => false, 'error' => 'Profile name is required'];
    }

    $ipPort = explode(':', $nas['nas_ip_port']);
    $ip = $ipPort[0];
    $port = isset($ipPort[1]) ? (int)$ipPort[1] : 8728;

    $api = new RouterosAPI();
    $api->port = $port;
    if (!$api->connect($ip, $nas['username'], $nas['password'])) {
        return ['success' => false, 'error' => 'Failed to connect to MikroTik'];
    }

    $sharedusers = $_POST['shared_users'] ?? '';
    $ratelimit   = $_POST['rate_limit'] ?? '';
    $expmode     = $_POST['expired_mode'] ?? '0';
    $validity    = strtolower($_POST['validity'] ?? '');
    $price       = '0';
    $sprice      = '0';
    $addrpool    = $_POST['address_pool'] ?? '';
    $parent      = $_POST['parent_queue'] ?? '';

    $getlock = $_POST['lock_user'] ?? 'Disable';
    if ($getlock == 'Enable') {
        $lock = '; [:local mac $"mac-address"; /ip hotspot user set mac-address=$mac [find where name=$user]]';
    } else {
        $lock = '';
    }

    $srvlock = $_POST['lock_server'] ?? 'Disable';
    if ($srvlock == 'Disable') {
        $slock = '';
    } else {
        $slock = '; [:local mac $"mac-address"; :local srv [/ip hotspot host get [find where mac-address="$mac"] server]; /ip hotspot user set server=$srv [find where name=$user]]';
    }

    if ($expmode == 'ntf' || $expmode == 'ntfc') {
        $mode = 'N';
    } elseif ($expmode == 'rem' || $expmode == 'remc') {
        $mode = 'X';
    }

    $record = '; :local mac $"mac-address"; :local time [/system clock get time ]; /system script add name="$date-|-$time-|-$user-|-' . $price . '-|-$address-|-$mac-|-' . $validity . '-|-' . $name . '-|-$comment" owner="$month$year" source=$date comment=nethive';

    $onlogin = ':put (",' . $expmode . ',' . $price . ',' . $validity . ',' . $sprice . ',,' . $getlock . ',' . $srvlock . ',"); :local mode "' . $mode . '"; {:local date [ /system clock get date ];:local year [ :pick $date 7 11 ];:local month [ :pick $date 0 3 ];:local comment [ /ip hotspot user get [/ip hotspot user find where name="$user"] comment]; :local ucode [:pic $comment 0 2]; :if ($ucode = "vc" or $ucode = "up" or $comment = "") do={ /sys sch add name="$user" disable=no start-date=$date interval="' . $validity . '"; :delay 2s; :local exp [ /sys sch get [ /sys sch find where name="$user" ] next-run]; :local getxp [len $exp]; :if ($getxp = 15) do={ :local d [:pic $exp 0 6]; :local t [:pic $exp 7 16]; :local s ("/"); :local exp ("$d$s$year $t"); /ip hotspot user set comment="$exp $mode" [find where name="$user"];}; :if ($getxp = 8) do={ /ip hotspot user set comment="$date $exp $mode" [find where name="$user"];}; :if ($getxp > 15) do={ /ip hotspot user set comment="$exp $mode" [find where name="$user"];}; /sys sch remove [find where name="$user"]';

    if ($expmode == 'rem' || $expmode == 'ntf') {
        $onlogin .= $lock . $slock . '}}';
    } elseif ($expmode == 'remc' || $expmode == 'ntfc') {
        $onlogin .= $record . $lock . $slock . '}}';
    } elseif ($expmode == '0' && $price != '') {
        $onlogin = ':put (",,' . $price . ',,' . $sprice . ',noexp,' . $getlock . ',' . $srvlock . ',")' . $lock . $slock;
    } else {
        $onlogin = '';
    }

    // ✅ Fixed firewall add (no \$ escaping, safe locals)
    $firewallLogin = ':local ip $"address"; :local usr $"user"; /ip firewall mangle add chain=WiFi-NetHive src-address=$ip connection-state=established action=return log=yes log-prefix=$usr comment=$usr';

    if (!empty($onlogin)) {
        $onlogin .= '; ' . $firewallLogin;
    } else {
        $onlogin = $firewallLogin;
    }

    // ✅ Fixed firewall cleanup
    $onlogout = ':local usr $"user"; /ip firewall mangle remove [find comment=$usr]';

    $params = [
        'name'             => $name,
        'shared-users'     => $sharedusers,
        'status-autorefresh' => '1m',
        'on-login'         => $onlogin,
        'on-logout'        => $onlogout
    ];

    if (!empty($addrpool)) {
        $params['address-pool'] = $addrpool;
    }
    if (!empty($ratelimit)) {
        $params['rate-limit'] = $ratelimit;
    }
    if (!empty($parent)) {
        $params['parent-queue'] = $parent;
    }

    $result = $api->comm('/ip/hotspot/user/profile/add', $params);
    $api->disconnect();

    if (isset($result['!trap'])) {
        return ['success' => false, 'error' => $result['!trap'][0]['message'] ?? 'Failed to add user profile'];
    }

    return ['success' => true, 'message' => 'User profile added successfully'];
}


function updateUserProfile()
{
    $nasId = $_POST['nas_id'] ?? null;
    $profileId = $_POST['profile_id'] ?? null;

    if (!$nasId || !$profileId) {
        return ['success' => false, 'error' => 'NAS ID and Profile ID required'];
    }

    $nas = getNasById($nasId);
    if (!$nas) {
        return ['success' => false, 'error' => 'NAS device not found'];
    }

    $name = preg_replace('/\s+/', '-', $_POST['name'] ?? '');
    if (empty($name)) {
        return ['success' => false, 'error' => 'Profile name is required'];
    }

    $ipPort = explode(':', $nas['nas_ip_port']);
    $ip = $ipPort[0];
    $port = isset($ipPort[1]) ? (int)$ipPort[1] : 8728;

    $api = new RouterosAPI();
    $api->port = $port;
    if (!$api->connect($ip, $nas['username'], $nas['password'])) {
        return ['success' => false, 'error' => 'Failed to connect to MikroTik'];
    }

    $sharedusers = $_POST['shared_users'] ?? '';
    $ratelimit = $_POST['rate_limit'] ?? '';
    $expmode = $_POST['expired_mode'] ?? '0';
    $validity = $_POST['validity'] ?? '';
    $price = '0';
    $sprice = '0';
    $addrpool = $_POST['address_pool'] ?? '';
    $parent = $_POST['parent_queue'] ?? '';

    $getlock = $_POST['lock_user'] ?? 'Disable';
    if ($getlock == 'Enable') {
        $lock = '; [:local mac $"mac-address"; /ip hotspot user set mac-address=$mac [find where name=$user]]';
    } else {
        $lock = '';
    }

    $srvlock = $_POST['lock_server'] ?? 'Disable';
    if ($srvlock == 'Disable') {
        $slock = '';
    } else if ($srvlock !== 'Disable') {
        $slock = '; [:local mac $"mac-address"; :local srv [/ip hotspot host get [find where mac-address="$mac"] server]; /ip hotspot user set server=$srv [find where name=$user]]';
    }

    if ($expmode == 'ntf' || $expmode == 'ntfc') {
        $mode = 'N';
    } else if ($expmode == 'rem' || $expmode == 'remc') {
        $mode = 'X';
    }

    $record = '; :local mac $"mac-address"; :local time [/system clock get time ]; /system script add name="$date-|-$time-|-$user-|-' . $price . '-|-$address-|-$mac-|-' . $validity . '-|-' . $name . '-|-$comment" owner="$month$year" source=$date comment=nethive';

    $onlogin = ':put (",' . $expmode . ',' . $price . ',' . $validity . ',' . $sprice . ',,' . $getlock . ',' . $srvlock . ',"); :local mode "' . $mode . '"; {:local date [ /system clock get date ];:local year [ :pick $date 7 11 ];:local month [ :pick $date 0 3 ];:local comment [ /ip hotspot user get [/ip hotspot user find where name="$user"] comment]; :local ucode [:pic $comment 0 2]; :if ($ucode = "vc" or $ucode = "up" or $comment = "") do={ /sys sch add name="$user" disable=no start-date=$date interval="' . $validity . '"; :delay 2s; :local exp [ /sys sch get [ /sys sch find where name="$user" ] next-run]; :local getxp [len $exp]; :if ($getxp = 15) do={ :local d [:pic $exp 0 6]; :local t [:pic $exp 7 16]; :local s ("/"); :local exp ("$d$s$year $t"); /ip hotspot user set comment="$exp $mode" [find where name="$user"];}; :if ($getxp = 8) do={ /ip hotspot user set comment="$date $exp $mode" [find where name="$user"];}; :if ($getxp > 15) do={ /ip hotspot user set comment="$exp $mode" [find where name="$user"];}; /sys sch remove [find where name="$user"]';

    if ($expmode == 'rem') {
        $onlogin = $onlogin . $lock . $slock . '}}';
    } elseif ($expmode == 'ntf') {
        $onlogin = $onlogin . $lock . $slock . '}}';
    } elseif ($expmode == 'remc') {
        $onlogin = $onlogin . $record . $lock . $slock . '}}';
    } elseif ($expmode == 'ntfc') {
        $onlogin = $onlogin . $record . $lock . $slock . '}}';
    } elseif ($expmode == '0' && $price != '') {
        $onlogin = ':put (",,' . $price . ',,' . $sprice . ',noexp,' . $getlock . ',' . $srvlock . ',")' . $lock . $slock;
    } else {
        $onlogin = '';
    }

    // Add firewall mangle rule for login
    $firewallLogin = ':local ip \$"address"; :local usr \$"user"; /ip firewall mangle add chain=WiFi-NetHive src-address=\$ip connection-state=established action=return log=yes log-prefix=\$usr comment=\$usr';

    if (!empty($onlogin)) {
        $onlogin .= '; ' . $firewallLogin;
    } else {
        $onlogin = $firewallLogin;
    }

    // Add on-logout script for firewall mangle cleanup
    $onlogout = ':local usr \$"user"; /ip firewall mangle remove [find comment=\$usr]';

    $params = [
        '.id' => $profileId,
        'name' => $name,
        'shared-users' => $sharedusers,
        'status-autorefresh' => '1m',
        'on-login' => $onlogin,
        'on-logout' => $onlogout
    ];

    // Only add optional parameters if they have values
    if (!empty($addrpool)) {
        $params['address-pool'] = $addrpool;
    }
    if (!empty($ratelimit)) {
        $params['rate-limit'] = $ratelimit;
    }
    if (!empty($parent)) {
        $params['parent-queue'] = $parent;
    }

    $result = $api->comm('/ip/hotspot/user/profile/set', $params);
    $api->disconnect();

    if (isset($result['!trap'])) {
        return ['success' => false, 'error' => $result['!trap'][0]['message'] ?? 'Failed to update user profile'];
    }

    return ['success' => true, 'message' => 'User profile updated successfully'];
}

function deleteUser()
{
    global $input;
    $nasId = $input['nas_id'] ?? null;
    $userId = $input['user_id'] ?? null;

    if (!$nasId || !$userId) {
        return ['success' => false, 'error' => 'NAS ID and User ID required'];
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

    $result = $api->comm('/ip/hotspot/user/remove', ['.id' => $userId]);
    $api->disconnect();

    if (isset($result['!trap'])) {
        return ['success' => false, 'error' => $result['!trap'][0]['message'] ?? 'Failed to delete user'];
    }

    return ['success' => true, 'message' => 'User deleted successfully'];
}

function deleteProfile()
{
    global $input;
    $nasId = $input['nas_id'] ?? null;
    $profileId = $input['profile_id'] ?? null;

    if (!$nasId || !$profileId) {
        return ['success' => false, 'error' => 'NAS ID and Profile ID required'];
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

    $result = $api->comm('/ip/hotspot/user/profile/remove', ['.id' => $profileId]);
    $api->disconnect();

    if (isset($result['!trap'])) {
        return ['success' => false, 'error' => $result['!trap'][0]['message'] ?? 'Failed to delete profile'];
    }

    return ['success' => true, 'message' => 'Profile deleted successfully'];
}

function disconnectUser()
{
    global $input;
    $nasId = $input['nas_id'] ?? null;
    $userId = $input['user_id'] ?? null;

    if (!$nasId || !$userId) {
        return ['success' => false, 'error' => 'NAS ID and User ID required'];
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

    $result = $api->comm('/ip/hotspot/active/remove', ['.id' => $userId]);
    $api->disconnect();

    if (isset($result['!trap'])) {
        return ['success' => false, 'error' => $result['!trap'][0]['message'] ?? 'Failed to disconnect user'];
    }

    return ['success' => true, 'message' => 'User disconnected successfully'];
}

function deleteHost()
{
    global $input;
    $nasId = $input['nas_id'] ?? null;
    $hostId = $input['host_id'] ?? null;

    if (!$nasId || !$hostId) {
        return ['success' => false, 'error' => 'NAS ID and Host ID required'];
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

    $result = $api->comm('/ip/hotspot/host/remove', ['.id' => $hostId]);
    $api->disconnect();

    if (isset($result['!trap'])) {
        return ['success' => false, 'error' => $result['!trap'][0]['message'] ?? 'Failed to delete host'];
    }

    return ['success' => true, 'message' => 'Host deleted successfully'];
}
