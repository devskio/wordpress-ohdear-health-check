<?php

function oh_dear_health_check_rewrite_rules($rules)
{
    $new_rules = array(
        'healthcheck' => 'index.php?oh_dear_health_check=1'
    );
    return $new_rules + $rules;
}

function oh_dear_health_check_query_vars($vars)
{
    $vars[] = 'oh_dear_health_check';
    return $vars;
}

add_filter('rewrite_rules_array', 'oh_dear_health_check_rewrite_rules');
add_filter('query_vars', 'oh_dear_health_check_query_vars');
