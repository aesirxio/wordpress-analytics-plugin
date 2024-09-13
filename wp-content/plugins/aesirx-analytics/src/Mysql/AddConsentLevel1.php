<?php

use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

include plugin_dir_path(__FILE__) . 'GetVisitorConsentList.php';

Class AesirX_Analytics_Add_Consent_Level1 extends AesirxAnalyticsMysqlHelper
{
    /**
     * Executes analytics MySQL query and processes visitor consent.
     *
     * This function handles the execution of a MySQL query to retrieve visitor consent data,
     * checks the retrieved consents for specific conditions (e.g., level, same consent number,
     * expiration), and adds new visitor consent if applicable.
     *
     * @param array $params Parameters for the MySQL query and consent processing.
     * @return mixed|WP_Error Returns the result of adding visitor consent or a WP_Error if a previous consent was not expired.
     */
    function aesirx_analytics_mysql_execute($params = [])
    {
        // Validate required parameters
        if (!isset($params['uuid']) || empty($params['uuid'])) {
            return new WP_Error('missing_parameter', esc_html__('The uuid parameter is required.', 'aesirx-analytics'), ['status' => 400]);
        }

        if (!isset($params['consent']) || !is_numeric($params['consent'])) {
            return new WP_Error('invalid_parameter', esc_html__('The consent parameter is required and must be a number.', 'aesirx-analytics'), ['status' => 400]);
        }

        // Get the current date and time
        $now = gmdate('Y-m-d H:i:s');

        // Instantiate the class to get visitor consent list
        $class = new \AesirX_Analytics_Get_Visitor_Consent_List();

        // Execute the MySQL query to get consents based on provided parameters
        $consents = $class->aesirx_analytics_mysql_execute($params);

        // Iterate over each retrieved consent
        foreach ($consents['visitor_consents'] as $consent) {
            // Check if the consent is at level1 (i.e., consent_uuid is null)
            if (is_null($consent['consent_uuid'])) {
                // Check if the consent number is the same as the provided parameter
                if (!is_null($consent['consent']) && $consent['consent'] != (int) $params['consent']) {
                    continue; // Skip to the next consent if the numbers do not match
                }

                // Check if the consent is expired
                if (!is_null($consent['expiration']) && $consent['expiration'] > $now) {
                    // Return an error if the previous consent has not expired
                    return new WP_Error(
                        'not_expired',
                        esc_html__('Previous consent was not expired', 'aesirx-analytics'),
                        ['status' => 400]
                    );
                }
            }
        }

        // Add new visitor consent with the given parameters and calculated timestamps
        return parent::aesirx_analytics_add_visitor_consent(
            sanitize_text_field($params['uuid']),              // Visitor UUID
            null,                                              // Consent UUID (null for new consent)
            (int) $params['consent'],                          // Consent level
            $now,                                              // Current timestamp
            null,                                              // Consent expiration (null for no expiration)
            $params                                            // Additional parameters
        );
    }
}