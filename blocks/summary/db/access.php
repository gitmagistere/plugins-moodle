<?php
$capabilities = array(
	'block/summary:addinstance' => array(
			'riskbitmask' => RISK_XSS,
	
			'captype' => 'write',
			'contextlevel' => CONTEXT_SYSTEM,
			'archetypes' => array(
					'coursecreator' => CAP_ALLOW,
					'editingteacher' => CAP_ALLOW,
					'manager' => CAP_ALLOW
			),
	),
		'block/summary:managepages' => array(
				'riskbitmask' => RISK_XSS,
				
				'captype' => 'write',
				'contextlevel' => CONTEXT_BLOCK,
				'archetypes' => array(
						'coursecreator' => CAP_ALLOW,
						'editingteacher' => CAP_ALLOW,
						'manager' => CAP_ALLOW
				),
		),
		'block/summary:canseehiddensections' => array(
				'riskbitmask' => RISK_XSS,
				'captype' => 'read',
				'contextlevel' => CONTEXT_BLOCK,
				'archetypes' => array(
						'coursecreator' => CAP_ALLOW,
						'editingteacher' => CAP_ALLOW,
						'manager' => CAP_ALLOW
				),
		),
        'block/summary:canseesectionzero' => array(
            'riskbitmask' => RISK_XSS,
            'captype' => 'read',
            'contextlevel' => CONTEXT_BLOCK,
            'archetypes' => array(
                'coursecreator' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            ),
		),
		'block/summary:canseecompletion' => array(
            'riskbitmask' => RISK_XSS,
            'captype' => 'read',
            'contextlevel' => CONTEXT_BLOCK,
            'archetypes' => array(
				'student' => CAP_ALLOW,
				'coursecreator' => CAP_PREVENT,
                'editingteacher' => CAP_PREVENT,
                'manager' => CAP_PREVENT,
            ),
		),
	
);