<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/content-writer/class-aipkit-google-sheets-parser.php
// Status: MODIFIED
// I have updated the Google API scope to allow writing, modified `get_rows_from_sheet` to read and filter by a new 'Status' column, and added a new `update_row_status` method.

namespace WPAICG\Lib\ContentWriter;

use WP_Error;
use Google_Client;
use Google_Service_Sheets;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Parses Google Sheets to extract topics for content generation.
 */
class AIPKit_Google_Sheets_Parser
{
    private $client;
    private $service_account_credentials;

    public function __construct(array $service_account_credentials)
    {
        if (!class_exists('Google_Client')) {
            throw new \Exception('Google API Client library not available.');
        }

        $this->service_account_credentials = $service_account_credentials;
        if (empty($this->service_account_credentials['private_key']) || empty($this->service_account_credentials['client_email'])) {
            throw new \Exception('Invalid Service Account credentials array: missing private_key or client_email.');
        }

        $this->client = new Google_Client();
        $this->client->setApplicationName('AI Power Google Sheets Integration');
        $this->client->setScopes([Google_Service_Sheets::SPREADSHEETS]);
        $this->client->setAuthConfig($this->service_account_credentials);
        $this->client->setAccessType('offline');
    }

    /**
     * Gets structured data from the first 7 columns of the specified Google Sheet.
     * A: Topic, B: Keywords, C: Category ID, D: Author Login, E: Post Type, F: Schedule Date, G: Status.
     * It filters out rows that have any value in the Status column (Column G).
     *
     * @param string $spreadsheet_id The ID of the Google Sheet.
     * @return array|WP_Error An array of structured row data or a WP_Error on failure.
     */
    public function get_rows_from_sheet(string $spreadsheet_id): array|WP_Error
    {
        if (empty($spreadsheet_id)) {
            return new WP_Error('missing_sheet_id', __('Google Sheet ID is required.', 'gpt3-ai-content-generator'));
        }

        try {
            $service = new Google_Service_Sheets($this->client);
            $range = 'A:G'; // Read up to column G for status
            $response = $service->spreadsheets_values->get($spreadsheet_id, $range);
            $values = $response->getValues();

            $rows = [];
            if (!empty($values)) {
                foreach ($values as $index => $row) {
                    $status = trim($row[6] ?? ''); // Column G is the 7th column (index 6)
                    // Skip empty rows, rows without a topic, and rows that already have a status
                    if (!empty(array_filter($row)) && !empty(trim($row[0] ?? '')) && empty($status)) {
                        $rows[] = [
                            'row_index'     => $index + 1, // Store the 1-based row index for updating later
                            'topic'         => trim($row[0] ?? ''),
                            'keywords'      => trim($row[1] ?? ''),
                            'category'      => trim($row[2] ?? ''),
                            'author'        => trim($row[3] ?? ''),
                            'post_type'     => trim($row[4] ?? ''),
                            'schedule_date' => trim($row[5] ?? ''), // Column F is schedule date
                        ];
                    }
                }
            }

            return $rows;
        } catch (\Exception $e) {
            return new WP_Error('google_api_error', 'Error connecting to Google Sheets: ' . $e->getMessage());
        }
    }

    /**
     * Updates the Status column (G) for a specific row in a Google Sheet.
     *
     * @param string $spreadsheet_id The ID of the Google Sheet.
     * @param int    $row_index      The 1-based index of the row to update.
     * @param string $status         The text to write into the status column.
     * @return bool|WP_Error True on success, or a WP_Error on failure.
     */
    public function update_row_status(string $spreadsheet_id, int $row_index, string $status): bool|WP_Error
    {
        if (empty($spreadsheet_id) || $row_index <= 0) {
            return new WP_Error('invalid_params_update_status', __('Spreadsheet ID and a valid row index are required.', 'gpt3-ai-content-generator'));
        }
        try {
            $service = new Google_Service_Sheets($this->client);
            $range = "G{$row_index}"; // Target cell in column G
            $value_range = new \Google_Service_Sheets_ValueRange([
                'values' => [[$status]]
            ]);
            $params = ['valueInputOption' => 'USER_ENTERED'];
            $service->spreadsheets_values->update($spreadsheet_id, $range, $value_range, $params);
            return true;
        } catch (\Exception $e) {
            return new WP_Error('google_api_update_error', __('Error updating Google Sheet status: ', 'gpt3-ai-content-generator') . $e->getMessage());
        }
    }


    /**
     * Verifies that the service account has access to the given Google Sheet.
     *
     * @param string $spreadsheet_id The ID of the Google Sheet.
     * @return true|WP_Error True on success, or a WP_Error on failure.
     */
    public function verify_access(string $spreadsheet_id): bool|WP_Error
    {
        if (empty($spreadsheet_id)) {
            return new WP_Error('missing_sheet_id_verify', __('Google Sheet ID is required for verification.', 'gpt3-ai-content-generator'));
        }

        try {
            $service = new Google_Service_Sheets($this->client);
            // Fetching a small, non-data property like the sheet's basic metadata is efficient for verification.
            $service->spreadsheets->get($spreadsheet_id, ['fields' => 'properties.title']);
            return true;
        } catch (\Exception $e) {
            $message = $e->getMessage();
            // Try to provide a more user-friendly message for common errors
            if (strpos($message, 'Requested entity was not found') !== false) {
                $message = __('Sheet not found or not shared with the service account email.', 'gpt3-ai-content-generator');
            } elseif (strpos($message, 'PERMISSION_DENIED') !== false) {
                $message = __('Permission denied. Ensure the sheet is shared with the service account email.', 'gpt3-ai-content-generator');
            }
            return new WP_Error('google_api_error_verify', 'Error connecting to Google Sheets: ' . $message);
        }
    }
}
