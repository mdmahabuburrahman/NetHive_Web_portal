<?php

/**
 * Queue Operations API Controller
 * Handles all queue-related operations for MikroTik RouterOS
 */

require_once 'routeros_api.class.php';

class QueueOperations
{
    private $api;
    private $nasData;
    private $selectedNas;

    public function __construct()
    {
        $this->loadNasData();
        $this->api = new RouterosAPI();
    }

    private function loadNasData()
    {
        $jsonFile = '../data/nas_details.json';
        if (file_exists($jsonFile)) {
            $jsonContent = file_get_contents($jsonFile);
            $this->nasData = json_decode($jsonContent, true);
        }
    }

    private function getSelectedNas($nasId)
    {
        if (!$this->nasData || empty($this->nasData)) {
            return null;
        }

        foreach ($this->nasData as $nas) {
            if ($nas['id'] === $nasId) {
                return $nas;
            }
        }

        return null;
    }

    private function connectToNas($nasId)
    {
        $this->selectedNas = $this->getSelectedNas($nasId);
        if (!$this->selectedNas) {
            return ['success' => false, 'error' => 'NAS device not found'];
        }

        $ipPort = explode(':', $this->selectedNas['nas_ip_port']);
        $ip = $ipPort[0];
        $port = isset($ipPort[1]) ? intval($ipPort[1]) : 8728;

        $this->api->port = $port;
        $connected = $this->api->connect($ip, $this->selectedNas['username'], $this->selectedNas['password']);

        if (!$connected) {
            $error = 'Connection failed';
            if ($this->api->error_str) {
                $error .= ': ' . $this->api->error_str;
            }
            return ['success' => false, 'error' => $error];
        }

        return ['success' => true];
    }

    public function handleRequest()
    {
        $method = $_SERVER['REQUEST_METHOD'];

        if ($method === 'GET') {
            $this->handleGet();
        } elseif ($method === 'POST') {
            $this->handlePost();
        } else {
            $this->sendResponse(['success' => false, 'error' => 'Method not allowed'], 405);
        }
    }

    private function handleGet()
    {
        $action = $_GET['action'] ?? '';

        switch ($action) {
            case 'get_nas_devices':
                $this->getNasDevices();
                break;

            case 'connect':
                $nasId = $_GET['nas_id'] ?? '';
                $this->testConnection($nasId);
                break;

            case 'get_queue_types':
                $nasId = $_GET['nas_id'] ?? '';
                $this->getQueueTypes($nasId);
                break;

            case 'get_simple_queues':
                $nasId = $_GET['nas_id'] ?? '';
                $this->getSimpleQueues($nasId);
                break;

            case 'get_queue_tree':
                $nasId = $_GET['nas_id'] ?? '';
                $this->getQueueTree($nasId);
                break;

            case 'get_queue_type_details':
                $nasId = $_GET['nas_id'] ?? '';
                $queueId = $_GET['queue_id'] ?? '';
                $this->getQueueTypeDetails($nasId, $queueId);
                break;

            case 'get_simple_queue_details':
                $nasId = $_GET['nas_id'] ?? '';
                $queueId = $_GET['queue_id'] ?? '';
                $this->getSimpleQueueDetails($nasId, $queueId);
                break;

            case 'get_queue_tree_details':
                $nasId = $_GET['nas_id'] ?? '';
                $queueId = $_GET['queue_id'] ?? '';
                $this->getQueueTreeDetails($nasId, $queueId);
                break;

            case 'get_interfaces':
                $nasId = $_GET['nas_id'] ?? '';
                $this->getInterfaces($nasId);
                break;

            default:
                $this->sendResponse(['success' => false, 'error' => 'Invalid action'], 400);
        }
    }

    private function handlePost()
    {
        $input = json_decode(file_get_contents('php://input'), true);

        // Log the incoming request for debugging
        error_log("Queue Operations API Request: " . json_encode($input));

        $action = $input['action'] ?? '';

        switch ($action) {
            case 'add_queue_type':
                $this->addQueueType($input);
                break;

            case 'add_simple_queue':
                $this->addSimpleQueue($input);
                break;

            case 'add_queue_tree':
                $this->addQueueTree($input);
                break;

            case 'edit_queue_type':
                $this->editQueueType($input);
                break;

            case 'edit_simple_queue':
                $this->editSimpleQueue($input);
                break;

            case 'edit_queue_tree':
                $this->editQueueTree($input);
                break;

            case 'delete_queue_type':
                $this->deleteQueueType($input);
                break;

            case 'delete_simple_queue':
                $this->deleteSimpleQueue($input);
                break;

            case 'delete_queue_tree':
                $this->deleteQueueTree($input);
                break;

            case 'toggle_queue':
                $this->toggleQueue($input);
                break;

            case 'reorder_simple_queue':
                $this->reorderSimpleQueue($input);
                break;

            case 'reorder_queue_tree':
                $this->reorderQueueTree($input);
                break;

            default:
                $this->sendResponse(['success' => false, 'error' => 'Invalid action'], 400);
        }
    }

    private function getNasDevices()
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
        $this->sendResponse($devices);
    }

    private function testConnection($nasId)
    {
        $connectionResult = $this->connectToNas($nasId);
        if ($connectionResult['success']) {
            $this->api->disconnect();
            $this->sendResponse([
                'success' => true,
                'nas_name' => $this->selectedNas['nas_name']
            ]);
        } else {
            $this->sendResponse($connectionResult);
        }
    }

    private function getQueueTypes($nasId)
    {
        $connectionResult = $this->connectToNas($nasId);
        if (!$connectionResult['success']) {
            $this->sendResponse($connectionResult);
            return;
        }

        try {
            $queueTypes = $this->api->comm('/queue/type/print');
            $this->api->disconnect();

            $this->sendResponse([
                'success' => true,
                'data' => $queueTypes ?: []
            ]);
        } catch (Exception $e) {
            $this->api->disconnect();
            $this->sendResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function getSimpleQueues($nasId)
    {
        $connectionResult = $this->connectToNas($nasId);
        if (!$connectionResult['success']) {
            $this->sendResponse($connectionResult);
            return;
        }

        try {
            $simpleQueues = $this->api->comm('/queue/simple/print');
            $this->api->disconnect();

            $this->sendResponse([
                'success' => true,
                'data' => $simpleQueues ?: []
            ]);
        } catch (Exception $e) {
            $this->api->disconnect();
            $this->sendResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function getQueueTree($nasId)
    {
        $connectionResult = $this->connectToNas($nasId);
        if (!$connectionResult['success']) {
            $this->sendResponse($connectionResult);
            return;
        }

        try {
            $queueTree = $this->api->comm('/queue/tree/print');
            $this->api->disconnect();

            $this->sendResponse([
                'success' => true,
                'data' => $queueTree ?: []
            ]);
        } catch (Exception $e) {
            $this->api->disconnect();
            $this->sendResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function getQueueTypeDetails($nasId, $queueId)
    {
        $connectionResult = $this->connectToNas($nasId);
        if (!$connectionResult['success']) {
            $this->sendResponse($connectionResult);
            return;
        }

        try {
            $queueType = $this->api->comm('/queue/type/print', ['?.id' => $queueId]);
            $this->api->disconnect();

            if (empty($queueType)) {
                $this->sendResponse(['success' => false, 'error' => 'Queue type not found']);
                return;
            }

            $this->sendResponse([
                'success' => true,
                'data' => $queueType[0]
            ]);
        } catch (Exception $e) {
            $this->api->disconnect();
            $this->sendResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function getSimpleQueueDetails($nasId, $queueId)
    {
        $connectionResult = $this->connectToNas($nasId);
        if (!$connectionResult['success']) {
            $this->sendResponse($connectionResult);
            return;
        }

        try {
            $queue = $this->api->comm('/queue/simple/print', ['?.id' => $queueId]);
            $this->api->disconnect();

            if (empty($queue)) {
                $this->sendResponse(['success' => false, 'error' => 'Simple queue not found']);
                return;
            }

            $this->sendResponse([
                'success' => true,
                'data' => $queue[0]
            ]);
        } catch (Exception $e) {
            $this->api->disconnect();
            $this->sendResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function getQueueTreeDetails($nasId, $queueId)
    {
        $connectionResult = $this->connectToNas($nasId);
        if (!$connectionResult['success']) {
            $this->sendResponse($connectionResult);
            return;
        }

        try {
            $queue = $this->api->comm('/queue/tree/print', ['?.id' => $queueId]);
            $this->api->disconnect();

            if (empty($queue)) {
                $this->sendResponse(['success' => false, 'error' => 'Queue tree entry not found']);
                return;
            }

            $this->sendResponse([
                'success' => true,
                'data' => $queue[0]
            ]);
        } catch (Exception $e) {
            $this->api->disconnect();
            $this->sendResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function getInterfaces($nasId)
    {
        $connectionResult = $this->connectToNas($nasId);
        if (!$connectionResult['success']) {
            $this->sendResponse($connectionResult);
            return;
        }

        try {
            $interfaces = $this->api->comm('/interface/print');
            $this->api->disconnect();

            $this->sendResponse([
                'success' => true,
                'data' => $interfaces ?: []
            ]);
        } catch (Exception $e) {
            $this->api->disconnect();
            $this->sendResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function addQueueType($data)
    {
        $connectionResult = $this->connectToNas($data['nas_id']);
        if (!$connectionResult['success']) {
            $this->sendResponse($connectionResult);
            return;
        }

        try {
            $params = [
                'name' => $data['name'],
                'kind' => $data['kind']
            ];

            if (!empty($data['comment'])) {
                $params['comment'] = $data['comment'];
            }

            // Add PCQ-specific parameters if kind is pcq
            if ($data['kind'] === 'pcq') {
                if (!empty($data['pcq-rate'])) {
                    $params['pcq-rate'] = $data['pcq-rate'];
                }
                if (!empty($data['pcq-limit'])) {
                    $params['pcq-limit'] = $data['pcq-limit'];
                }
                if (!empty($data['pcq-classifier'])) {
                    $params['pcq-classifier'] = $data['pcq-classifier'];
                }
                if (!empty($data['pcq-total-limit'])) {
                    $params['pcq-total-limit'] = $data['pcq-total-limit'];
                }
            }

            $result = $this->api->comm('/queue/type/add', $params);
            $this->api->disconnect();

            $this->sendResponse(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            $this->api->disconnect();
            $this->sendResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function addSimpleQueue($data)
    {
        $connectionResult = $this->connectToNas($data['nas_id']);
        if (!$connectionResult['success']) {
            $this->sendResponse($connectionResult);
            return;
        }

        try {
            $params = [
                'name' => $data['name']
            ];

            // Add optional parameters
            $optionalParams = [
                'target',
                'max-limit',
                'burst-limit',
                'burst-threshold',
                'burst-time',
                'priority',
                'limit-at',
                'comment'
            ];

            foreach ($optionalParams as $param) {
                if (!empty($data[$param])) {
                    $params[$param] = $data[$param];
                }
            }

            if (isset($data['disabled']) && $data['disabled'] === 'on') {
                $params['disabled'] = 'yes';
            }

            $result = $this->api->comm('/queue/simple/add', $params);
            $this->api->disconnect();

            $this->sendResponse(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            $this->api->disconnect();
            $this->sendResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function addQueueTree($data)
    {
        $connectionResult = $this->connectToNas($data['nas_id']);
        if (!$connectionResult['success']) {
            $this->sendResponse($connectionResult);
            return;
        }

        try {
            $params = [
                'name' => $data['name']
            ];

            // Add optional parameters
            $optionalParams = [
                'parent',
                'packet-mark',
                'queue-type',
                'max-limit',
                'limit-at',
                'burst-limit',
                'burst-threshold',
                'burst-time',
                'priority',
                'comment'
            ];

            foreach ($optionalParams as $param) {
                if (!empty($data[$param])) {
                    $params[$param] = $data[$param];
                }
            }

            if (isset($data['disabled']) && $data['disabled'] === 'on') {
                $params['disabled'] = 'yes';
            }

            $result = $this->api->comm('/queue/tree/add', $params);
            $this->api->disconnect();

            $this->sendResponse(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            $this->api->disconnect();
            $this->sendResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function editQueueType($data)
    {
        $connectionResult = $this->connectToNas($data['nas_id']);
        if (!$connectionResult['success']) {
            $this->sendResponse($connectionResult);
            return;
        }

        try {
            $params = ['.id' => $data['queue_id']];

            // Update parameters
            $updateParams = ['name', 'kind', 'comment'];
            foreach ($updateParams as $param) {
                if (isset($data[$param])) {
                    $params[$param] = $data[$param];
                }
            }

            // Add PCQ-specific parameters if kind is pcq
            if (isset($data['kind']) && $data['kind'] === 'pcq') {
                $pcqParams = ['pcq-rate', 'pcq-limit', 'pcq-classifier', 'pcq-total-limit'];
                foreach ($pcqParams as $param) {
                    if (isset($data[$param])) {
                        $params[$param] = $data[$param];
                    }
                }
            }

            $result = $this->api->comm('/queue/type/set', $params);
            $this->api->disconnect();

            $this->sendResponse(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            $this->api->disconnect();
            $this->sendResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function editSimpleQueue($data)
    {
        $connectionResult = $this->connectToNas($data['nas_id']);
        if (!$connectionResult['success']) {
            $this->sendResponse($connectionResult);
            return;
        }

        try {
            $params = ['.id' => $data['queue_id']];

            // Update parameters
            $updateParams = [
                'name',
                'target',
                'max-limit',
                'burst-limit',
                'burst-threshold',
                'burst-time',
                'priority',
                'limit-at',
                'comment'
            ];

            foreach ($updateParams as $param) {
                if (isset($data[$param])) {
                    $params[$param] = $data[$param];
                }
            }

            if (isset($data['disabled'])) {
                $params['disabled'] = $data['disabled'] === 'on' ? 'yes' : 'no';
            }

            $result = $this->api->comm('/queue/simple/set', $params);
            $this->api->disconnect();

            $this->sendResponse(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            $this->api->disconnect();
            $this->sendResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function editQueueTree($data)
    {
        $connectionResult = $this->connectToNas($data['nas_id']);
        if (!$connectionResult['success']) {
            $this->sendResponse($connectionResult);
            return;
        }

        try {
            $params = ['.id' => $data['queue_id']];

            // Update parameters
            $updateParams = [
                'name',
                'parent',
                'packet-mark',
                'queue-type',
                'max-limit',
                'limit-at',
                'burst-limit',
                'burst-threshold',
                'burst-time',
                'priority',
                'comment'
            ];

            foreach ($updateParams as $param) {
                if (isset($data[$param])) {
                    $params[$param] = $data[$param];
                }
            }

            if (isset($data['disabled'])) {
                $params['disabled'] = $data['disabled'] === 'on' ? 'yes' : 'no';
            }

            $result = $this->api->comm('/queue/tree/set', $params);
            $this->api->disconnect();

            $this->sendResponse(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            $this->api->disconnect();
            $this->sendResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function deleteQueueType($data)
    {
        $connectionResult = $this->connectToNas($data['nas_id']);
        if (!$connectionResult['success']) {
            $this->sendResponse($connectionResult);
            return;
        }

        try {
            $result = $this->api->comm('/queue/type/remove', [
                '.id' => $data['queue_id']
            ]);
            $this->api->disconnect();

            $this->sendResponse(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            $this->api->disconnect();
            $this->sendResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function deleteSimpleQueue($data)
    {
        $connectionResult = $this->connectToNas($data['nas_id']);
        if (!$connectionResult['success']) {
            $this->sendResponse($connectionResult);
            return;
        }

        try {
            $result = $this->api->comm('/queue/simple/remove', [
                '.id' => $data['queue_id']
            ]);
            $this->api->disconnect();

            $this->sendResponse(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            $this->api->disconnect();
            $this->sendResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function deleteQueueTree($data)
    {
        $connectionResult = $this->connectToNas($data['nas_id']);
        if (!$connectionResult['success']) {
            $this->sendResponse($connectionResult);
            return;
        }

        try {
            $result = $this->api->comm('/queue/tree/remove', [
                '.id' => $data['queue_id']
            ]);
            $this->api->disconnect();

            $this->sendResponse(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            $this->api->disconnect();
            $this->sendResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function toggleQueue($data)
    {
        $connectionResult = $this->connectToNas($data['nas_id']);
        if (!$connectionResult['success']) {
            $this->sendResponse($connectionResult);
            return;
        }

        try {
            $queueType = $data['queue_type'];
            $queueId = $data['queue_id'];

            // First get current status
            if ($queueType === 'simple') {
                $current = $this->api->comm('/queue/simple/print', ['?.id' => $queueId]);
                $path = '/queue/simple/';
            } elseif ($queueType === 'queue-tree') {
                $current = $this->api->comm('/queue/tree/print', ['?.id' => $queueId]);
                $path = '/queue/tree/';
            } else {
                throw new Exception('Invalid queue type');
            }

            if (empty($current)) {
                throw new Exception('Queue not found');
            }

            $isDisabled = isset($current[0]['disabled']) && $current[0]['disabled'] === 'true';

            if ($isDisabled) {
                // Enable the queue
                $result = $this->api->comm($path . 'enable', ['.id' => $queueId]);
            } else {
                // Disable the queue
                $result = $this->api->comm($path . 'disable', ['.id' => $queueId]);
            }

            $this->api->disconnect();
            $this->sendResponse(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            $this->api->disconnect();
            $this->sendResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function reorderSimpleQueue($data)
    {
        $connectionResult = $this->connectToNas($data['nas_id']);
        if (!$connectionResult['success']) {
            $this->sendResponse($connectionResult);
            return;
        }

        try {
            $queueId = $data['queue_id'];
            $newPosition = intval($data['new_position']);
            $oldPosition = intval($data['old_position']);

            error_log("=== SIMPLE QUEUE REORDER ===");
            error_log("Queue ID: $queueId");
            error_log("Old Position: $oldPosition");
            error_log("New Position: $newPosition");

            // Get current queue list
            $queues = $this->api->comm('/queue/simple/print');

            if (empty($queues)) {
                throw new Exception('No queues found');
            }

            error_log("Total queues: " . count($queues));

            // Log current queue order
            foreach ($queues as $index => $queue) {
                error_log("Position $index: " . $queue['.id'] . " - " . ($queue['name'] ?? 'N/A'));
            }

            // Validate the queue exists
            $queueExists = false;
            foreach ($queues as $queue) {
                if ($queue['.id'] === $queueId) {
                    $queueExists = true;
                    break;
                }
            }

            if (!$queueExists) {
                throw new Exception("Queue with ID $queueId not found");
            }

            // Perform the move based on new position
            if ($newPosition === 0) {
                // Move to top
                error_log("Moving $queueId to top (position 0)");
                $result = $this->api->comm('/queue/simple/move', [
                    'numbers' => $queueId,
                    'destination' => '0'
                ]);
            } else if ($newPosition >= count($queues) - 1) {
                // Move to bottom
                error_log("Moving $queueId to bottom");
                $result = $this->api->comm('/queue/simple/move', [
                    'numbers' => $queueId
                    // No destination = move to end
                ]);
            } else {
                // Move to specific position
                // We need to find the queue that should be after our queue in the new position
                // In SortableJS, newPosition is the target index where we want to place the item

                if ($newPosition > $oldPosition) {
                    // Moving down - place before the item that's currently at newPosition + 1
                    $targetIndex = $newPosition + 1;
                } else {
                    // Moving up - place before the item that's currently at newPosition
                    $targetIndex = $newPosition;
                }

                if ($targetIndex < count($queues) && isset($queues[$targetIndex])) {
                    $destinationId = $queues[$targetIndex]['.id'];
                    error_log("Moving $queueId before $destinationId (position $targetIndex)");

                    if ($destinationId !== $queueId) {
                        $result = $this->api->comm('/queue/simple/move', [
                            'numbers' => $queueId,
                            'destination' => $destinationId
                        ]);
                    } else {
                        error_log("Destination is same as source queue, skipping move");
                        $result = [];
                    }
                } else {
                    error_log("Target position $targetIndex is out of bounds, moving to end");
                    $result = $this->api->comm('/queue/simple/move', [
                        'numbers' => $queueId
                    ]);
                }
            }

            $this->api->disconnect();

            error_log("Move operation completed");

            $this->sendResponse([
                'success' => true,
                'message' => 'Simple queue reordered successfully',
                'data' => [
                    'moved_queue' => $queueId,
                    'old_position' => $oldPosition,
                    'new_position' => $newPosition,
                    'result' => $result
                ]
            ]);
        } catch (Exception $e) {
            $this->api->disconnect();
            error_log("Simple queue reorder failed: " . $e->getMessage());
            $this->sendResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function reorderQueueTree($data)
    {
        $connectionResult = $this->connectToNas($data['nas_id']);
        if (!$connectionResult['success']) {
            $this->sendResponse($connectionResult);
            return;
        }

        try {
            $queueId = $data['queue_id'];
            $newPosition = intval($data['new_position']);
            $oldPosition = intval($data['old_position']);

            error_log("=== QUEUE TREE REORDER ===");
            error_log("Queue ID: $queueId");
            error_log("Old Position: $oldPosition");
            error_log("New Position: $newPosition");

            // Get current queue tree list
            $queues = $this->api->comm('/queue/tree/print');

            if (empty($queues)) {
                throw new Exception('No queue tree entries found');
            }

            error_log("Total queue tree entries: " . count($queues));

            // Log current queue order
            foreach ($queues as $index => $queue) {
                error_log("Position $index: " . $queue['.id'] . " - " . ($queue['name'] ?? 'N/A'));
            }

            // Validate the queue exists
            $queueExists = false;
            foreach ($queues as $queue) {
                if ($queue['.id'] === $queueId) {
                    $queueExists = true;
                    break;
                }
            }

            if (!$queueExists) {
                throw new Exception("Queue tree entry with ID $queueId not found");
            }

            // Perform the move based on new position
            if ($newPosition === 0) {
                // Move to top
                error_log("Moving $queueId to top (position 0)");
                $result = $this->api->comm('/queue/tree/move', [
                    'numbers' => $queueId,
                    'destination' => '0'
                ]);
            } else if ($newPosition >= count($queues) - 1) {
                // Move to bottom
                error_log("Moving $queueId to bottom");
                $result = $this->api->comm('/queue/tree/move', [
                    'numbers' => $queueId
                ]);
            } else {
                // Move to specific position
                if ($newPosition > $oldPosition) {
                    $targetIndex = $newPosition + 1;
                } else {
                    $targetIndex = $newPosition;
                }

                if ($targetIndex < count($queues) && isset($queues[$targetIndex])) {
                    $destinationId = $queues[$targetIndex]['.id'];
                    error_log("Moving $queueId before $destinationId (position $targetIndex)");

                    if ($destinationId !== $queueId) {
                        $result = $this->api->comm('/queue/tree/move', [
                            'numbers' => $queueId,
                            'destination' => $destinationId
                        ]);
                    } else {
                        error_log("Destination is same as source queue, skipping move");
                        $result = [];
                    }
                } else {
                    error_log("Target position $targetIndex is out of bounds, moving to end");
                    $result = $this->api->comm('/queue/tree/move', [
                        'numbers' => $queueId
                    ]);
                }
            }

            $this->api->disconnect();

            error_log("Queue tree move operation completed");

            $this->sendResponse([
                'success' => true,
                'message' => 'Queue tree reordered successfully',
                'data' => [
                    'moved_queue' => $queueId,
                    'old_position' => $oldPosition,
                    'new_position' => $newPosition,
                    'result' => $result
                ]
            ]);
        } catch (Exception $e) {
            $this->api->disconnect();
            error_log("Queue tree reorder failed: " . $e->getMessage());
            $this->sendResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function sendResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

// Initialize and handle the request
$queueOperations = new QueueOperations();
$queueOperations->handleRequest();
