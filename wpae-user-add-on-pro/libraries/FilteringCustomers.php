<?php

/**
 * Class FilteringCustomers
 * @package Wpae\Pro\Filtering
 */
class FilteringCustomers extends FilteringUsers
{
    /**
     * @return bool
     */
    public function parse() {
        if (!$this->isFilteringAllowed()) {
            return false;
        }
        
        $this->checkNewStuff();

        // No Filtering Rules defined
        if (empty($this->filterRules)) {
            if ($this->isExportOnlyCustomersWithPurchases()) {
                // Use subquery for better performance when finding customers with purchases
                $this->queryWhere .= " AND {$this->wpdb->users}.ID IN (
                    SELECT DISTINCT meta_value
                    FROM {$this->wpdb->postmeta}
                    WHERE meta_key = '_customer_user' AND meta_value IS NOT NULL AND meta_value != ''
                )";
            } else {
				// Get both customers with role 'customer' and those who have made purchases
	            $this->queryWhere .= " AND (
				    EXISTS (
				        SELECT 1
				        FROM {$this->wpdb->usermeta} um
				        WHERE um.user_id = {$this->wpdb->users}.ID
				        AND um.meta_key = '{$this->wpdb->prefix}capabilities'
				        AND um.meta_value LIKE '%\"customer\"%'
				    )
				    OR
				    EXISTS (
				        SELECT 1
				        FROM {$this->wpdb->postmeta} pm
				        WHERE pm.meta_value = {$this->wpdb->users}.ID
				        AND pm.meta_key = '_customer_user'
				        AND pm.meta_value != ''
				    )
				)";
            }



            return false;
        }

        // Apply Filtering Rules
        $this->queryWhere = $this->isExportNewStuff() ? $this->queryWhere . " AND (" : " AND (";

        // Apply Filtering Rules
        foreach ($this->filterRules as $rule) {
            if (is_null($rule->parent_id)) {
                $this->parse_single_rule($rule);
            }
        }

        if ($this->isExportOnlyCustomersWithPurchases()) {
            $this->meta_query = true;
            // Use subquery instead of JOIN for better performance
            $this->queryWhere .= " AND {$this->wpdb->users}.ID IN (
                SELECT DISTINCT meta_value
                FROM {$this->wpdb->postmeta}
                WHERE meta_key = '_customer_user' AND meta_value IS NOT NULL AND meta_value != ''
            )";
        } else {
            $this->meta_query = true;
	        // Get both customers with role 'customer' and those who have made purchases
	        $this->queryWhere .= " AND (
			    EXISTS (
			        SELECT 1
			        FROM {$this->wpdb->usermeta} um
			        WHERE um.user_id = {$this->wpdb->users}.ID
			        AND um.meta_key = '{$this->wpdb->prefix}capabilities'
			        AND um.meta_value LIKE '%\"customer\"%'
			    )
			    OR
			    EXISTS (
			        SELECT 1
			        FROM {$this->wpdb->postmeta} pm
			        WHERE pm.meta_value = {$this->wpdb->users}.ID
			        AND pm.meta_key = '_customer_user'
			        AND pm.meta_value != ''
			    )
			)";
        }



        $this->queryWhere .= ")";

        // Only add GROUP BY if absolutely necessary and only on the primary key
        if ($this->meta_query || $this->tax_query) {
            $this->queryWhere .= " GROUP BY {$this->wpdb->users}.ID";
        }
        
        return true;
    }

    /**
     * @return bool
     */
    protected function isExportOnlyCustomersWithPurchases() {
        return (!empty(\XmlExportEngine::$exportOptions['export_only_customers_that_made_purchases']));
    }









}