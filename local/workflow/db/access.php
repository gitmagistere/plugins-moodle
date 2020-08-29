<?php

/**
 * Plugin Capabilities
 *
 * @author TCS
 * @package local_workflow
 */
$capabilities = array(
    'local/workflow:globalaccess' => array(
        'riskbitmask' => RISK_XSS,

        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'teacher' => CAP_INHERIT,
            'editingteacher' => CAP_INHERIT,
            'coursecreator' => CAP_INHERIT,
            'manager' => CAP_INHERIT
        ),
    ),

    'local/workflow:index' => array(
        'riskbitmask' => RISK_XSS,

        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'teacher' => CAP_INHERIT,
            'editingteacher' => CAP_INHERIT,
            'coursecreator' => CAP_INHERIT,
            'manager' => CAP_INHERIT
        ),
    ),

    'local/workflow:duplicate' => array(
        'riskbitmask' => RISK_XSS,

        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'teacher' => CAP_INHERIT,
            'editingteacher' => CAP_INHERIT,
            'coursecreator' => CAP_INHERIT,
            'manager' => CAP_INHERIT
        ),
    ),

    'local/workflow:trash' => array(
        'riskbitmask' => RISK_XSS,

        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'teacher' => CAP_INHERIT,
            'editingteacher' => CAP_INHERIT,
            'coursecreator' => CAP_INHERIT,
            'manager' => CAP_INHERIT
        ),
    ),

    'local/workflow:createsession' => array(
        'riskbitmask' => RISK_XSS,

        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'teacher' => CAP_INHERIT,
            'editingteacher' => CAP_INHERIT,
            'coursecreator' => CAP_INHERIT,
            'manager' => CAP_INHERIT
        ),
    ),

    'local/workflow:recreatesession' => array(
        'riskbitmask' => RISK_XSS,

        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'teacher' => CAP_INHERIT,
            'editingteacher' => CAP_INHERIT,
            'coursecreator' => CAP_INHERIT,
            'manager' => CAP_INHERIT
        ),
    ),

    'local/workflow:addformateur' => array(
        'riskbitmask' => RISK_XSS,

        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'teacher' => CAP_INHERIT,
            'editingteacher' => CAP_INHERIT,
            'coursecreator' => CAP_INHERIT,
            'manager' => CAP_INHERIT
        ),
    ),

    'local/workflow:addtuteur' => array(
        'riskbitmask' => RISK_XSS,

        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'teacher' => CAP_INHERIT,
            'editingteacher' => CAP_INHERIT,
            'coursecreator' => CAP_INHERIT,
            'manager' => CAP_INHERIT
        ),
    ),

    'local/workflow:addparticipant' => array(
        'riskbitmask' => RISK_XSS,

        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'teacher' => CAP_INHERIT,
            'editingteacher' => CAP_INHERIT,
            'coursecreator' => CAP_INHERIT,
            'manager' => CAP_INHERIT
        ),
    ),

    'local/workflow:addparticipantmanual' => array(
        'riskbitmask' => RISK_XSS,

        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'teacher' => CAP_INHERIT,
            'editingteacher' => CAP_INHERIT,
            'coursecreator' => CAP_INHERIT,
            'manager' => CAP_INHERIT
        ),
    ),

    'local/workflow:addparticipantcsv' => array(
        'riskbitmask' => RISK_XSS,

        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'teacher' => CAP_INHERIT,
            'editingteacher' => CAP_INHERIT,
            'coursecreator' => CAP_INHERIT,
            'manager' => CAP_INHERIT
        ),
    ),

    'local/workflow:addformateurmanual' => array(
        'riskbitmask' => RISK_XSS,

        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'teacher' => CAP_INHERIT,
            'editingteacher' => CAP_INHERIT,
            'coursecreator' => CAP_INHERIT,
            'manager' => CAP_INHERIT
        ),
    ),

    'local/workflow:addformateurcsv' => array(
        'riskbitmask' => RISK_XSS,

        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'teacher' => CAP_INHERIT,
            'editingteacher' => CAP_INHERIT,
            'coursecreator' => CAP_INHERIT,
            'manager' => CAP_INHERIT
        ),
    ),

    'local/workflow:addtuteurmanual' => array(
        'riskbitmask' => RISK_XSS,

        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'teacher' => CAP_INHERIT,
            'editingteacher' => CAP_INHERIT,
            'coursecreator' => CAP_INHERIT,
            'manager' => CAP_INHERIT
        ),
    ),

    'local/workflow:addtuteurcsv' => array(
        'riskbitmask' => RISK_XSS,

        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'teacher' => CAP_INHERIT,
            'editingteacher' => CAP_INHERIT,
            'coursecreator' => CAP_INHERIT,
            'manager' => CAP_INHERIT
        ),
    ),

    'local/workflow:opensession' => array(
        'riskbitmask' => RISK_XSS,

        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'teacher' => CAP_INHERIT,
            'editingteacher' => CAP_INHERIT,
            'coursecreator' => CAP_INHERIT,
            'manager' => CAP_INHERIT
        ),
    ),

    'local/workflow:setcoursedates' => array(
        'riskbitmask' => RISK_XSS,

        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'teacher' => CAP_INHERIT,
            'editingteacher' => CAP_INHERIT,
            'coursecreator' => CAP_INHERIT,
            'manager' => CAP_INHERIT
        ),
    ),

    'local/workflow:setcourseduration' => array(
        'riskbitmask' => RISK_XSS,

        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'teacher' => CAP_INHERIT,
            'editingteacher' => CAP_INHERIT,
            'coursecreator' => CAP_INHERIT,
            'manager' => CAP_INHERIT
        ),
    ),

    'local/workflow:setcoursecollection' => array(
        'riskbitmask' => RISK_XSS,

        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'teacher' => CAP_INHERIT,
            'editingteacher' => CAP_INHERIT,
            'coursecreator' => CAP_INHERIT,
            'manager' => CAP_INHERIT
        ),
    ),

    'local/workflow:setgaiasession' => array(
        'riskbitmask' => RISK_XSS,

        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'teacher' => CAP_INHERIT,
            'editingteacher' => CAP_INHERIT,
            'coursecreator' => CAP_INHERIT,
            'manager' => CAP_INHERIT
        ),
    ),

    'local/workflow:confirmparticipation' => array(
        'riskbitmask' => RISK_XSS,

        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'teacher' => CAP_INHERIT,
            'editingteacher' => CAP_INHERIT,
            'coursecreator' => CAP_INHERIT,
            'manager' => CAP_INHERIT
        ),
    ),

    'local/workflow:closecourse' => array(
        'riskbitmask' => RISK_XSS,

        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'teacher' => CAP_INHERIT,
            'editingteacher' => CAP_INHERIT,
            'coursecreator' => CAP_INHERIT,
            'manager' => CAP_INHERIT
        ),
    ),

    'local/workflow:reopencourse' => array(
        'riskbitmask' => RISK_XSS,

        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'teacher' => CAP_INHERIT,
            'editingteacher' => CAP_INHERIT,
            'coursecreator' => CAP_INHERIT,
            'manager' => CAP_INHERIT
        ),
    ),

    'local/workflow:courseopening' => array(
        'riskbitmask' => RISK_XSS,

        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'teacher' => CAP_INHERIT,
            'editingteacher' => CAP_INHERIT,
            'coursecreator' => CAP_INHERIT,
            'manager' => CAP_INHERIT
        ),
    ),

    'local/workflow:openselfenrolement' => array(
        'riskbitmask' => RISK_XSS,

        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'teacher' => CAP_INHERIT,
            'editingteacher' => CAP_INHERIT,
            'coursecreator' => CAP_INHERIT,
            'manager' => CAP_INHERIT
        ),
    ),
    
    'local/workflow:createparcours' => array(
        'riskbitmask' => RISK_XSS,
        
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'teacher' => CAP_INHERIT,
            'editingteacher' => CAP_INHERIT,
            'coursecreator' => CAP_INHERIT,
            'manager' => CAP_INHERIT
        ),
    ),
    
    'local/workflow:optimize' => array(
        'riskbitmask' => RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'teacher' => CAP_PREVENT,
            'editingteacher' => CAP_PREVENT,
            'coursecreator' => CAP_PREVENT,
            'manager' => CAP_PREVENT
        ),
    ),
    
    'local/workflow:optimizeconfig' => array(
        'riskbitmask' => RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'teacher' => CAP_PREVENT,
            'editingteacher' => CAP_PREVENT,
            'coursecreator' => CAP_PREVENT,
            'manager' => CAP_PREVENT
        ),
    ),
    
    'local/workflow:config' => array(
        'riskbitmask' => RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'teacher' => CAP_PREVENT,
            'editingteacher' => CAP_PREVENT,
            'coursecreator' => CAP_PREVENT,
            'manager' => CAP_PREVENT
        ),
    ),
    
);