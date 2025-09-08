<?php
/**
 * Debug Helper for Ample Connect Plugin
 * 
 * This file provides debugging utilities for database queries and API calls
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ample_Debug_Helper {
    
    /**
     * Log database queries with timing and caller info
     */
    public static function log_query($query, $context = '') {
        global $wpdb;
        
        $start_time = microtime(true);
        $result = $wpdb->query($query);
        $end_time = microtime(true);
        $execution_time = ($end_time - $start_time) * 1000; // Convert to milliseconds
        
        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];
        $caller_info = isset($caller['file']) ? basename($caller['file']) . ':' . $caller['line'] : 'unknown';
        
        $log_entry = sprintf(
            "[AMPLE DB] %s | Query: %s | Time: %.2fms | Called from: %s | Rows affected: %d",
            $context,
            $query,
            $execution_time,
            $caller_info,
            $result !== false ? $result : 0
        );
        
        ample_connect_log($log_entry);
        return $result;
    }
    
    /**
     * Log API requests and responses
     */
    public static function log_api_request($url, $method = 'GET', $data = null, $response = null) {
        $log_entry = sprintf(
            "[AMPLE API] %s %s | Data: %s | Response: %s",
            strtoupper($method),
            $url,
            $data ? json_encode($data, JSON_UNESCAPED_SLASHES) : 'none',
            is_array($response) || is_object($response) ? json_encode($response, JSON_UNESCAPED_SLASHES) : (string)$response
        );
        
        ample_connect_log($log_entry, true); // Use API log
    }
    
    /**
     * Get current database query count and time
     */
    public static function get_query_stats() {
        global $wpdb;
        
        if (!defined('SAVEQUERIES') || !SAVEQUERIES) {
            return 'SAVEQUERIES not enabled';
        }
        
        $total_queries = count($wpdb->queries);
        $total_time = 0;
        
        foreach ($wpdb->queries as $query) {
            $total_time += $query[1];
        }
        
        return sprintf(
            "Total queries: %d | Total time: %.4f seconds",
            $total_queries,
            $total_time
        );
    }
    
    /**
     * Debug WooCommerce order data
     */
    public static function debug_order($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            ample_connect_log("[AMPLE DEBUG] Order $order_id not found");
            return;
        }
        
        $debug_data = [
            'order_id' => $order_id,
            'status' => $order->get_status(),
            'total' => $order->get_total(),
            'customer_id' => $order->get_customer_id(),
            'items' => []
        ];
        
        foreach ($order->get_items() as $item_id => $item) {
            $debug_data['items'][] = [
                'product_id' => $item->get_product_id(),
                'name' => $item->get_name(),
                'quantity' => $item->get_quantity(),
                'total' => $item->get_total()
            ];
        }
        
        ample_connect_log("[AMPLE DEBUG] Order data: " . json_encode($debug_data, JSON_PRETTY_PRINT));
    }
    
    /**
     * Show slow queries (over threshold)
     */
    public static function log_slow_queries($threshold = 0.05) {
        global $wpdb;
        
        if (!defined('SAVEQUERIES') || !SAVEQUERIES || empty($wpdb->queries)) {
            return;
        }
        
        $slow_queries = [];
        foreach ($wpdb->queries as $query) {
            if ($query[1] > $threshold) {
                $slow_queries[] = [
                    'sql' => $query[0],
                    'time' => $query[1],
                    'caller' => $query[2]
                ];
            }
        }
        
        if (!empty($slow_queries)) {
            ample_connect_log("[AMPLE SLOW QUERIES] Found " . count($slow_queries) . " queries over {$threshold}s:");
            foreach ($slow_queries as $query) {
                ample_connect_log(sprintf(
                    "  %.4fs: %s (called from %s)",
                    $query['time'],
                    substr($query['sql'], 0, 200) . (strlen($query['sql']) > 200 ? '...' : ''),
                    $query['caller']
                ));
            }
        }
    }
}

// Hook to log slow queries on page load
add_action('wp_footer', function() {
    if (WP_DEBUG && function_exists('ample_connect_log')) {
        Ample_Debug_Helper::log_slow_queries(0.01); // Log queries over 10ms
        ample_connect_log("[AMPLE DEBUG] " . Ample_Debug_Helper::get_query_stats());
    }
});

// Example usage functions that you can call from your plugin
function ample_debug_query($query, $context = '') {
    return Ample_Debug_Helper::log_query($query, $context);
}

function ample_debug_api($url, $method, $data = null, $response = null) {
    return Ample_Debug_Helper::log_api_request($url, $method, $data, $response);
}

function ample_debug_order($order_id) {
    return Ample_Debug_Helper::debug_order($order_id);
}