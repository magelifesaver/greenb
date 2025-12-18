<?php
// File: classes/autogpt/cron/event-processor/trigger/module/parse-schedule-utils.php
// Purpose: Robust parsing of user-provided schedule date strings for "Use Dates from Input" mode.

namespace WPAICG\AutoGPT\Cron\EventProcessor\Trigger\Modules;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Attempts to parse a schedule datetime string provided by the user / Google Sheet.
 * Accepts multiple common formats and normalizes to a GMT datetime string (Y-m-d H:i:s) on success.
 *
 * Supported examples:
 *  - 2025-08-22 10:00
 *  - 2025-08-22 10:00:30
 *  - 2025/08/22 10:00
 *  - 08/22/2025 10:00  (US)
 *  - 22/08/2025 10:00  (EU – heuristic: if first part > 12 treat as D/M/Y)
 *  - 2025-08-22T10:00:00Z (ISO UTC)
 *  - 2025-08-22T10:00:00+02:00 (ISO with offset)
 *
 * Heuristic for ambiguous numeric dates with slashes:
 *  If first component > 12 => D/M/Y, else M/D/Y.
 *
 * @param string $raw Raw user-entered or sheet-extracted string.
 * @return array{gmt:string|null, error:string|null} gmt is normalized GMT datetime or null if parse failed; error is reason when failed.
 */
function parse_schedule_datetime_logic(string $raw): array
{
    $raw_original = trim($raw);
    if ($raw_original === '') {
        return ['gmt' => null, 'error' => 'empty'];
    }

    $raw_clean = preg_replace('/\s+/', ' ', $raw_original); // collapse whitespace

    $candidates = [];

    // Normalize ISO 'T'
    if (preg_match('/T/', $raw_clean)) {
        $iso = $raw_clean;
        // If Z or offset present, we can rely on strtotime (UTC or offset aware)
        $candidates[] = function() use ($iso) {
            $ts = strtotime($iso);
            return $ts ? ['ts' => $ts, 'is_utc' => true] : null;
        };
    }

    // Add direct known dash formats
    $dash_no_sec = '/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2})$/';
    if (preg_match($dash_no_sec, $raw_clean)) {
        $candidates[] = function() use ($raw_clean) {
            $dt = date_create_from_format('Y-m-d H:i', $raw_clean, wp_timezone());
            return $dt ? ['ts' => $dt->getTimestamp(), 'is_utc' => false] : null;
        };
    }
    $dash_with_sec = '/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})$/';
    if (preg_match($dash_with_sec, $raw_clean)) {
        $candidates[] = function() use ($raw_clean) {
            $dt = date_create_from_format('Y-m-d H:i:s', $raw_clean, wp_timezone());
            return $dt ? ['ts' => $dt->getTimestamp(), 'is_utc' => false] : null;
        };
    }

    // Slash formats (ambiguous)
    $slash_pattern = '/^(\d{1,2})\/(\d{1,2})\/(\d{4}) (\d{2}):(\d{2})(?::(\d{2}))?$/';
    if (preg_match($slash_pattern, $raw_clean, $m)) {
        $a = (int)$m[1]; $b = (int)$m[2]; $Y = (int)$m[3];
        $H = (int)$m[4]; $i = (int)$m[5]; $s = isset($m[6]) ? (int)$m[6] : 0;
        $is_day_first = $a > 12; // heuristic
        $day = $is_day_first ? $a : $b;
        $mon = $is_day_first ? $b : $a;
        if (checkdate($mon, $day, $Y)) {
            $candidates[] = function() use ($Y,$mon,$day,$H,$i,$s) {
                $dt = new \DateTime(sprintf('%04d-%02d-%02d %02d:%02d:%02d', $Y,$mon,$day,$H,$i,$s), wp_timezone());
                return ['ts' => $dt->getTimestamp(), 'is_utc' => false];
            };
        }
    }

    // Fallback strtotime (interprets as site timezone if no TZ info)
    $candidates[] = function() use ($raw_clean) {
        $ts = strtotime($raw_clean);
        return $ts ? ['ts' => $ts, 'is_utc' => false] : null;
    };

    foreach ($candidates as $resolver) {
        $res = $resolver();
        if ($res && !empty($res['ts'])) {
            // If resolver flagged as UTC we already have UTC ts; else interpret as site-local and convert to UTC.
            $utc_ts = $res['is_utc'] ? $res['ts'] : $res['ts'] - (int) get_option('gmt_offset') * HOUR_IN_SECONDS;
            return ['gmt' => gmdate('Y-m-d H:i:s', $utc_ts), 'error' => null];
        }
    }

    return ['gmt' => null, 'error' => 'unparsed'];
}

/**
 * Convenience wrapper: returns GMT datetime string or null (ignores error detail).
 * @param string $raw
 * @return string|null
 */
function parse_schedule_datetime_simple_logic(string $raw): ?string
{
    $res = parse_schedule_datetime_logic($raw);
    return $res['gmt'];
}

/**
 * Computes the scheduled GMT time for a queue item based on task_config schedule settings.
 * Handles both schedule_mode=from_input (using provided schedule_date field or pipe/array tail) and schedule_mode=smart.
 * Ensures returned string is TRUE GMT (Y-m-d H:i:s) not local.
 *
 * @param array|string $item_data Structured array or raw line/string.
 * @param array $task_config Task configuration.
 * @param int $item_index Zero-based index of item in queue (used for smart schedule offset).
 * @param string $generation_mode Mode (bulk,csv,single,rss,gsheets,url...)
 * @return string|null GMT datetime string or null if not scheduled.
 */
function compute_item_schedule_gmt_logic($item_data, array $task_config, int $item_index, string $generation_mode): ?string
{
    $schedule_mode = $task_config['schedule_mode'] ?? 'immediate';
    $scheduled_gmt_time = null;

    if ($schedule_mode === 'from_input') {
        $date_str = '';
        if ($generation_mode === 'gsheets' && is_array($item_data) && !empty($item_data['schedule_date'])) {
            $date_str = $item_data['schedule_date'];
        } elseif (is_array($item_data) && !empty($item_data['schedule_date'])) {
            $date_str = $item_data['schedule_date'];
        } else {
            $raw = is_array($item_data) ? ($item_data['topic'] ?? '') : $item_data;
            if (is_string($raw) && strpos($raw, '|') !== false) {
                $parts = array_map('trim', explode('|', $raw));
                if (count($parts) > 1) {
                    $date_str_candidate = end($parts);
                    // Only treat it as date if it has digit
                    if (preg_match('/\d/', $date_str_candidate)) {
                        $date_str = $date_str_candidate;
                    }
                }
            }
        }
        if ($date_str !== '') {
            $parsed = parse_schedule_datetime_simple_logic($date_str);
            if ($parsed) {
                $scheduled_gmt_time = $parsed;
            }
        }
    } elseif ($schedule_mode === 'smart' && !empty($task_config['smart_schedule_start_datetime'])) {
        try {
            $start_local = new \DateTime($task_config['smart_schedule_start_datetime'], wp_timezone());
            $interval_value = absint($task_config['smart_schedule_interval_value'] ?? 1);
            $interval_unit = $task_config['smart_schedule_interval_unit'] ?? 'hours';
            $offset_value = $item_index * $interval_value;
            $start_local->modify("+{$offset_value} {$interval_unit}");
            // Convert the local scheduled time to GMT explicitly.
            $local_str = $start_local->format('Y-m-d H:i:s');
            $scheduled_gmt_time = get_gmt_from_date($local_str, 'Y-m-d H:i:s');
        } catch (\Exception $e) {
            $scheduled_gmt_time = null;
        }
    }
    return $scheduled_gmt_time;
}
