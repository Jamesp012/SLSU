<?php
// connection.php
require_once __DIR__ . '/credentials.php';

// Shared function to call Supabase API
function supabaseRequest($method, $endpoint, $data = null, $useServiceRole = false)
{
    global $baseUrl, $apiKey, $serviceRoleKey;
    $url = "$baseUrl/$endpoint";

    // Log the request details (disabled in production)
    // $logMessage = date('Y-m-d H:i:s') . " - Supabase Request: Method=$method, Endpoint=$endpoint\n";
    // file_put_contents(__DIR__ . '/../logs/debug.log', $logMessage, FILE_APPEND);

    // Use service role key if explicitly requested or for write operations
    $authKey = ($useServiceRole || $method === 'POST' || $method === 'PATCH' || $method === 'DELETE') ? $serviceRoleKey : $apiKey;

    $headers = [
        "apikey: $authKey",
        "Authorization: Bearer $authKey",
        "Content-Type: application/json"
    ];

    if ($method === 'GET' && $data) {
        $url .= '?' . http_build_query($data);
    }

    if ($method === 'POST' || $method === 'PATCH') {
        $headers[] = "Prefer: return=representation";
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($method === 'POST' || $method === 'PATCH') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    if ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // Debug logging (disabled in production)
    // file_put_contents('debug_curl.txt', "URL: $url\nHTTP Code: $httpCode\nCURL Error: $curlError\nResponse: $response\n\n", FILE_APPEND);

    // Ensure logs directory exists
    $logsDir = __DIR__ . '/../logs';
    if (!is_dir($logsDir)) {
        @mkdir($logsDir, 0755, true);
    }

    // Log the response details
    $responseLogMessage = date('Y-m-d H:i:s') . " - Supabase Response: HTTP Code=$httpCode, Error=$curlError, Response=$response\n";
    @file_put_contents($logsDir . '/debug.log', $responseLogMessage, FILE_APPEND);

    if ($curlError) {
        $errorMessage = 'cURL Error: ' . $curlError;
        @file_put_contents($logsDir . '/debug.log', date('Y-m-d H:i:s') . " - $errorMessage\n", FILE_APPEND);
        return ['error' => $errorMessage];
    }

    if ($httpCode >= 400) {
        $errorMessage = 'HTTP Error: ' . $httpCode;
        $debugMessage = date('Y-m-d H:i:s') . " - $errorMessage, Response: $response, Method: $method, Endpoint: $endpoint\n";
        @file_put_contents($logsDir . '/debug.log', $debugMessage, FILE_APPEND);
        error_log($debugMessage);
        return ['error' => $errorMessage, 'response' => $response, 'method' => $method, 'endpoint' => $endpoint];
    }

    // Parse the JSON response
    $decodedResponse = json_decode($response, true);

    // Check if JSON parsing failed
    if ($response && $decodedResponse === null && json_last_error() !== JSON_ERROR_NONE) {
        $errorMessage = 'JSON Parse Error: ' . json_last_error_msg();
        @file_put_contents($logsDir . '/debug.log', date('Y-m-d H:i:s') . " - $errorMessage, Response: $response\n", FILE_APPEND);
        return ['error' => $errorMessage, 'raw_response' => $response];
    }

    // Return empty array if response is null or empty
    if ($decodedResponse === null) {
        @file_put_contents($logsDir . '/debug.log', date('Y-m-d H:i:s') . " - Empty response converted to empty array\n", FILE_APPEND);
        return [];
    }

    return $decodedResponse;
}

// Shared function to call Supabase Auth API
function supabaseAuthRequest($method, $endpoint, $data = null)
{
    global $projectUrl, $serviceRoleKey;
    $url = "$projectUrl/auth/v1/$endpoint";

    $headers = [
        "apikey: $serviceRoleKey",
        "Authorization: Bearer $serviceRoleKey",
        "Content-Type: application/json"
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // Log Auth Request/Response
    $logsDir = __DIR__ . '/../logs';
    $authLogMessage = date('Y-m-d H:i:s') . " - Supabase Auth: $method $url, Code=$httpCode, Error=$curlError, Response=$response\n";
    @file_put_contents($logsDir . '/debug.log', $authLogMessage, FILE_APPEND);

    $decoded = json_decode($response, true);
    if ($httpCode >= 400) {
        return ['error' => "Auth Error $httpCode", 'details' => $decoded];
    }

    return $decoded;
}

// Raw SQL query execution via Supabase REST API
$php_raw_sql = function ($query) {
    global $baseUrl, $apiKey;

    $query = trim($query);

    if (preg_match('/^SELECT/i', $query)) {
        $selectMatch = [];
        if (preg_match('/SELECT\s+(.*?)\s+FROM\s+(\w+)/i', $query, $selectMatch)) {
            $selectFields = $selectMatch[1];
            $table = $selectMatch[2];

            $url = "$baseUrl/$table?select=" . urlencode($selectFields);

            if (preg_match('/WHERE\s+(.*?)(?:ORDER|GROUP|LIMIT|$)/i', $query, $whereMatch)) {
                $whereClause = $whereMatch[1];

                if (preg_match('/LOWER\((\w+)\)\s*=\s*[\'"]?(\w+)[\'"]?/i', $whereClause, $lowerMatch)) {
                    $field = $lowerMatch[1];
                    $value = $lowerMatch[2];
                    $url .= "&$field=ilike." . urlencode("%$value%");
                }
            }

            $headers = [
                "apikey: $apiKey",
                "Authorization: Bearer $apiKey",
                "Content-Type: application/json"
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                return ['error' => 'cURL Error: ' . $curlError];
            }

            if ($httpCode >= 400) {
                return ['error' => 'HTTP Error: ' . $httpCode];
            }

            $result = json_decode($response, true);
            return $result ?: [];
        }
    }

    return ['error' => 'Unsupported query format'];
};

// Fetch (GET)
$php_fetch = function ($table, $select = '*', $filters = [], $order = null, $useServiceRole = false, $limit = null) {
    // Special case: UPDATE
    if ($select === 'UPDATE') {
        $query = [];
        foreach ($filters as $key => $value) {
            $query[] = "$key=eq.$value";
        }
        $endpoint = "$table?" . implode('&', $query);
        return supabaseRequest('PATCH', $endpoint, $filters, $useServiceRole);
    }

    // Special case: COUNT
    if (is_string($select) && stripos($select, 'COUNT') !== false) {
        $query = [];

        foreach ($filters as $key => $value) {
            if (is_array($value)) {
                // IN query
                $query[$key] = 'in.(' . implode(',', $value) . ')';
            } elseif ($key !== null && is_string($key) && str_contains($key, '!=')) {
                $field = trim(str_replace('!=', '', $key));
                $query[$field] = "neq.$value";
            } elseif ($value !== null && is_string($value)) {
                // Check if value already has an operator prefix (eq., neq., ilike., in., gt., lt., etc.)
                if (preg_match('/^(eq|neq|like|ilike|in|gt|gte|lt|lte|is)\./i', $value)) {
                    // Value already has operator, use as-is
                    $query[$key] = $value;
                } else {
                    // No operator prefix, add eq. for exact match
                    $query[$key] = "eq.$value";
                }
            } elseif ($value !== null && !is_string($value)) {
                $query[$key] = "eq.$value";
            } else {
                $query[$key] = $value;
            }
        }

        $result = supabaseRequest('GET', $table, $query, $useServiceRole);
        if (isset($result['error'])) {
            return $result;
        }
        return [['count' => count($result)]];
    }

    // Normal GET + Join support
    $query = ['select' => $select];

    // Apply filters
    foreach ($filters as $key => $value) {
        if (is_array($value)) {
            $query[$key] = 'in.(' . implode(',', $value) . ')';
        } elseif ($key !== null && is_string($key) && str_contains($key, '!=')) {
            $field = trim(str_replace('!=', '', $key));
            $query[$field] = "neq.$value";
        } elseif ($value !== null && is_string($value)) {
            // Check if value already has an operator prefix (eq., neq., ilike., in., gt., lt., etc.)
            if (preg_match('/^(eq|neq|like|ilike|in|gt|gte|lt|lte|is)\./i', $value)) {
                // Value already has operator, use as-is
                $query[$key] = $value;
            } else {
                // No operator prefix, add eq. for exact match
                $query[$key] = "eq.$value";
            }
        } elseif ($value !== null && !is_string($value)) {
            $query[$key] = "eq.$value";
        } else {
            $query[$key] = $value;
        }
    }
    if (!empty($order)) {
        if (is_array($order)) {
            // Support both array of strings and associative array
            if (isset($order['column'])) {
                $query['order'] = "{$order['column']}.{$order['direction']}";
            } else {
                $query['order'] = implode(',', $order); // e.g. ['product_name.asc', 'price.desc']
            }
        } else {
            $query['order'] = $order; // e.g. 'product_name.asc'
        }
    }

    if ($limit !== null) {
        $query['limit'] = $limit;
    }

    return supabaseRequest('GET', $table, $query, $useServiceRole);
};



// Insert (POST)
$php_insert = function ($table, $data, $useServiceRole = true) {
    $result = supabaseRequest('POST', $table, $data, $useServiceRole);
    $debugMsg = "Table: $table\nData: " . json_encode($data) . "\nResult: " . json_encode($result) . "\n";
    @file_put_contents(__DIR__ . '/../logs/debug_insert.log', $debugMsg . "\n---\n", FILE_APPEND);
    error_log('[php_insert] ' . $debugMsg);
    return $result;
};

// Update (PATCH)
$php_update = function ($table, $data, $filters = [], $useServiceRole = true) {
    $query = [];
    foreach ($filters as $key => $value) {
        $query[] = "$key=eq.$value";
    }
    $endpoint = "$table?" . implode('&', $query);
    return supabaseRequest('PATCH', $endpoint, $data, $useServiceRole);
};

// Delete (DELETE)
$php_delete = function ($table, $filters = [], $useServiceRole = true) {
    // If filters is not an array (old format), convert it
    if (!is_array($filters)) {
        $filters = ['id' => $filters];
    }

    $query = [];
    foreach ($filters as $key => $value) {
        $query[] = "$key=eq.$value";
    }
    $endpoint = "$table?" . implode('&', $query);
    return supabaseRequest('DELETE', $endpoint, $useServiceRole);
};

// Generate booking detail ID by calling generate_booking_id() function
// This bypasses the DEFAULT expression issue with Supabase REST API
$generate_booking_detail_id = function () {
    // We use RPC to call the PostgreSQL function
    global $baseUrl, $serviceRoleKey;
    
    $endpoint = "rpc/generate_booking_id";
    $headers = [
        "apikey: $serviceRoleKey",
        "Authorization: Bearer $serviceRoleKey",
        "Content-Type: application/json"
    ];
    
    $url = "$baseUrl/$endpoint";
    error_log('[generate_booking_detail_id] Calling RPC: ' . $url);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, '{}'); // Empty JSON body for no-param function
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    error_log('[generate_booking_detail_id] HTTP Code: ' . $httpCode);
    error_log('[generate_booking_detail_id] Response: ' . $response);
    
    if ($curlError) {
        error_log('[generate_booking_detail_id] cURL Error: ' . $curlError);
        return null;
    }
    
    if ($httpCode >= 400) {
        error_log('[generate_booking_detail_id] HTTP Error: ' . $httpCode);
        return null;
    }
    
    // Try to decode as JSON first
    $result = json_decode($response, true);
    
    error_log('[generate_booking_detail_id] Decoded result: ' . json_encode($result) . ' | Type: ' . gettype($result));
    
    // Supabase RPC can return:
    // 1. Plain string (scalar): "SPA20251101XXXXXXX"
    // 2. JSON-wrapped: "\"SPA20251101XXXXXXX\""
    
    // If response is a plain string (not JSON-wrapped), use it directly
    if (!json_last_error() && is_string($result)) {
        error_log('[generate_booking_detail_id] Returning scalar string: ' . $result);
        return $result;
    }
    
    // If decode failed or it's empty, try the raw response
    if ($response && json_last_error() !== JSON_ERROR_NONE) {
        error_log('[generate_booking_detail_id] JSON decode failed, using raw response');
        // Remove quotes if it's a JSON string
        $cleaned = trim($response, '"');
        if (strpos($cleaned, 'SPA') === 0) {
            error_log('[generate_booking_detail_id] Returning cleaned response: ' . $cleaned);
            return $cleaned;
        }
    }
    
    // If it's an array, might be wrapped
    if (is_array($result) && count($result) === 1) {
        error_log('[generate_booking_detail_id] Returning first array element');
        return $result[0];
    }
    
    // Otherwise return as-is
    error_log('[generate_booking_detail_id] Returning result as-is: ' . json_encode($result));
    return $result;
};


function uploadProfileImage($base64Image, $uuid, $folder, $bucket = 'services-images')
{
    global $projectUrl, $serviceRoleKey;

    // Handle data URL format (data:image/png;base64,...)
    if (strpos($base64Image, 'data:') === 0) {
        // Split by comma to get just the base64 part
        $parts = explode(',', $base64Image, 2);
        if (count($parts) === 2) {
            $base64Image = $parts[1];
        }
    }

    // Decode base64 image
    $imageData = base64_decode($base64Image);
    if ($imageData === false) {
        return false;
    }

    // Detect MIME type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->buffer($imageData);

    // Determine file extension
    $extension = match ($mimeType) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        default => 'bin',
    };

    $filename = "$folder/$uuid.$extension";

    // Step 1: Delete existing image if it exists
    $deleteUrl = "$projectUrl/storage/v1/object/$bucket/$filename";
    $deleteHeaders = [
        "Authorization: Bearer $serviceRoleKey"
    ];

    $deleteCh = curl_init($deleteUrl);
    curl_setopt_array($deleteCh, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'DELETE',
        CURLOPT_HTTPHEADER => $deleteHeaders
    ]);

    curl_exec($deleteCh);
    curl_close($deleteCh);
    // (ignore delete errors; continue to upload)

    // Step 2: Upload new image
    $uploadUrl = "$projectUrl/storage/v1/object/$bucket/$filename";
    $uploadHeaders = [
        "Authorization: Bearer $serviceRoleKey",
        "Content-Type: $mimeType"
    ];

    $uploadCh = curl_init($uploadUrl);
    curl_setopt_array($uploadCh, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'POST', // POST: create new; PUT: overwrite
        CURLOPT_POSTFIELDS => $imageData,
        CURLOPT_HTTPHEADER => $uploadHeaders
    ]);

    $response = curl_exec($uploadCh);
    $httpCode = curl_getinfo($uploadCh, CURLINFO_HTTP_CODE);
    curl_close($uploadCh);

    if ($httpCode >= 200 && $httpCode < 300) {
        return "$projectUrl/storage/v1/object/public/$bucket/$filename";
    }

    return "../vendor/images/default_profile.png"; // Return default image on failure
}
