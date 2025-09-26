<?php
require_once 'auth_check.php';

// Require admin/operator for voucher printing
checkAuth(['admin', 'operator']);

error_reporting(0);

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

function formatBytes($bytes, $precision = 2)
{
    if ($bytes == 0) return '';
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    for ($i = 0; $bytes > 1024; $i++) {
        $bytes /= 1024;
    }
    return round($bytes, $precision) . ' ' . $units[$i];
}

// Get parameters - following helper files logic
$comment = $_GET['c'] ?? '';
$profile = $_GET['profile'] ?? '';
$all = $_GET['all'] ?? '';
$nasId = $_GET['nas_id'] ?? '';
$d = $_GET['d'] ?? '';
$s = $_GET['s'] ?? '';
$t = $_GET['t'] ?? '';

// Template selection - following helper files logic
if (isset($d)) {
    $template = 'default';
} elseif (isset($s)) {
    $template = 'small';
} elseif (isset($t)) {
    $template = 'thermal';
} else {
    $template = 'default';
}

// Get NAS connection
if (empty($nasId)) {
    $nasDevices = loadNasData();
    if (!empty($nasDevices)) {
        $nasId = $nasDevices[0]['id'];
    }
}

$nas = getNasById($nasId);
if (!$nas) {
    die('NAS device not found');
}

require_once 'routeros_api.class.php';

$ipPort = explode(':', $nas['nas_ip_port']);
$ip = $ipPort[0];
$port = isset($ipPort[1]) ? (int)$ipPort[1] : 8728;

$api = new RouterosAPI();
$api->port = $port;
if (!$api->connect($ip, $nas['username'], $nas['password'])) {
    die('Failed to connect to MikroTik');
}

// Get users - following helper files logic
if (!empty($comment)) {
    $get_users = $api->comm('/ip/hotspot/user/print', ['?comment' => $comment]);
} elseif (!empty($profile)) {
    $get_users = $api->comm('/ip/hotspot/user/print', ['?profile' => $profile]);
} else {
    $get_users = $api->comm('/ip/hotspot/user/print');
}

if (empty($get_users)) {
    die('No users found');
}

// Filter out users with 'default' in username or profile name
$get_users = array_filter($get_users, function($user) {
    return stripos($user['name'], 'default') === false && stripos($user['profile'], 'default') === false;
});
$get_users = array_values($get_users); // Re-index array

if (empty($get_users)) {
    die('No users found after filtering');
}

// Get profile info - following helper files logic
$getuprofile = $get_users[0]['profile'];
$getprofile = $api->comm("/ip/hotspot/user/profile/print", array("?name" => "$getuprofile"));
$ponlogin = $getprofile[0]['on-login'] ?? '';
$validity = explode(",", $ponlogin)[3] ?? '1d';
$getprice = explode(",", $ponlogin)[2] ?? '0';
$getsprice = explode(",", $ponlogin)[4] ?? '0';

$api->disconnect();

// Settings from NAS configuration
$hotspotname = $nas['hotspot_name'] ?: "NetHive Hotspot";
$dnsname = $nas['dns_name'] ?: $ip; // If dns_name is empty, use the IP address
$currency = $nas['currency'] ?: "$";
$timestamp = date("Y-m-d H:i:s");

// Output header
echo '<!DOCTYPE html>
<html>
<head>
    <title>Voucher</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <script src="../assets/js/jquery.min.js"></script>
    <script src="../assets/js/qrious.min.js"></script>
    <style>
        body {
            color: #333333;
            background-color: #f2edf3;
            font-size: 14px;
            font-family: "Ubuntu", sans-serif;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact;
            line-height: 1;
        }
        .voucher-container {
            display: inline-block;
            margin: 2px;
            width: 220px;
            background: #fff;
            border-radius: 0.3125rem;
            box-shadow: 0 0 0.875rem 0 rgba(33, 37, 41, 0.05);
            overflow: hidden;
            vertical-align: top;
        }
        .voucher-header {
            background: linear-gradient(to right, #da8cff, #9a55ff);
            color: #fff;
            padding: 12px 15px;
            text-align: center;
            font-weight: 500;
            font-size: 14px;
            letter-spacing: 0.5px;
        }
        .voucher-body {
            padding: 15px;
        }
        .voucher-qr {
            text-align: center;
            margin-bottom: 12px;
            padding: 5px;
            background: #fff;
            border-radius: 0.3125rem;
        }
        .qrcode {
            height: 110px;
            width: 110px;
            padding: 5px;
            background: #fff;
            border: 1px solid #ebedf2;
            border-radius: 0.3125rem;
        }
        .credentials-box {
            background: #f8f9fa;
            border: 1px solid #ebedf2;
            border-radius: 0.3125rem;
            padding: 12px;
            margin-bottom: 10px;
        }
        .credential-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        .credential-row:last-child {
            margin-bottom: 0;
        }
        .credential-label {
            color: #686868;
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 500;
            flex: 0 0 auto;
        }
        .credential-value {
            color: #000;
            font-weight: 500;
            font-size: 13px;
            text-align: right;
            letter-spacing: 0.3px;
            flex: 1;
            margin-left: 10px;
        }
        .info-box {
            font-size: 11px;
            color: #686868;
            text-align: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 0.3125rem;
            margin-top: 5px;
        }
        .info-detail {
            display: inline-block;
            margin: 2px;
            padding: 4px 8px;
            background: #fff;
            border-radius: 0.1875rem;
            border: 1px solid #ebedf2;
            font-weight: 500;
            color: #b66dff;
        }
        .voucher-footer {
            background: #f8f9fa;
            border-top: 1px solid #ebedf2;
            padding: 10px;
            text-align: center;
            font-size: 11px;
            color: #686868;
        }
        @page { 
            size: auto;
            margin: 5mm;
        }
        @media print { 
            body { 
                background-color: #fff;
                padding: 0;
                margin: 0;
            }
            .voucher-container { 
                page-break-inside: avoid; 
                box-shadow: none;
                margin: 2px;
            }
            .voucher-header { 
                background: #9a55ff !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .info-detail { 
                color: #9a55ff !important;
            }
        }
    </style>
</head>
<body>';

$TotalReg = count($get_users);

// Process each user - following helper files logic
for ($i = 0; $i < $TotalReg; $i++) {
    $regtable = $get_users[$i];
    $idqr = str_replace("=", "", base64_encode(($regtable['.id'] . "qr")));
    $username = $regtable['name'];
    $password = $regtable['password'];
    $profile = $regtable['profile'];
    $timelimit = $regtable['limit-uptime'];
    $getdatalimit = $regtable['limit-bytes-total'];
    $comment = $regtable['comment'];

    if ($getdatalimit == 0) {
        $datalimit = "";
    } else {
        $datalimit = formatBytes($getdatalimit, 2);
    }

    $urilogin = "http://$dnsname/login?username=" . urlencode($username) . "&password=" . urlencode($password);
    $num = $i + 1;
?>
    <div class="voucher-container">
        <div class="voucher-header">
            NetHive
        </div>
        <div class="voucher-body">
            <div class="voucher-qr">
                <canvas class="qrcode" id="<?php echo htmlspecialchars($idqr); ?>"></canvas>
                <script>
                    (function() {
                        new QRious({
                            element: document.getElementById("<?php echo htmlspecialchars($idqr); ?>"),
                            value: "<?php echo htmlspecialchars($urilogin); ?>",
                            size: 100,
                            padding: 0
                        });
                    })();
                </script>
            </div>
            <div class="credentials-box">
                <div class="credential-row">
                    <div class="credential-label">Username</div>
                    <div class="credential-value"><?php echo htmlspecialchars($username); ?></div>
                </div>
                <div class="credential-row">
                    <div class="credential-label">Password</div>
                    <div class="credential-value"><?php echo htmlspecialchars($password); ?></div>
                </div>
            </div>
            <div class="info-box">
                <div class="info-detail"><?php echo htmlspecialchars($profile); ?></div>
                <?php if ($timelimit): ?>
                    <div class="info-detail"><?php echo htmlspecialchars($timelimit); ?></div>
                <?php endif; ?>
                <?php if ($datalimit): ?>
                    <div class="info-detail"><?php echo htmlspecialchars($datalimit); ?></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="voucher-footer">
            Login at: http://<?php echo htmlspecialchars($dnsname); ?>
            <span style="color:#20a8d8">[<?php echo $num; ?>]</span>
        </div>
    </div>
<?php
}
?>
<script>
    window.onload = window.print();
</script>
</body>

</html>