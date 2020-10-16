<?php

/**
 * Plugin Capabilities
 *
 * @author TCS
 * @package local_magistere_offers
 */

$capabilities = array(

    'local/magistere_offers:view_courseoffer' => array(
        'riskbitmask' => RISK_XSS,

        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'teacher' => CAP_PROHIBIT,
            'editingteacher' => CAP_PROHIBIT,
            'coursecreator' => CAP_PROHIBIT,
            'manager' => CAP_PROHIBIT
        ),
    ),
    'local/magistere_offers:view_formationoffer' => array(
        'riskbitmask' => RISK_XSS,

        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'teacher' => CAP_PROHIBIT,
            'editingteacher' => CAP_PROHIBIT,
            'coursecreator' => CAP_PROHIBIT,
            'manager' => CAP_PROHIBIT
        ),
    )
);