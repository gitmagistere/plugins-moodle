<?php

/**
 * Moodle Magistere_offer local plugin
 * This file describe the service used by the frontend to load the modal data
 *
 * @package    local_magistere_offers
 * @copyright  2020 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$services = array(
    'magistere_offers' => array(
        'functions' => array ('local_magistere_offers_get_detail_offer'),
        'requiredcapability' => '',
        'restrictedusers' =>0,
        'enabled'=>1,
    )
);

$functions = array(
    'local_magistere_offers_get_detail_offer' => array(
        'classname'     => 'local_magistere_offers_external',
        'methodname'    => 'get_detail_offer',
        'classpath'     => 'local/magistere_offers/externallib.php',
        'description'   => 'get the detail of a course offer in the hub.',
        'type'          => 'read',
        'ajax'          => true,
        'enabled'       => 1,
        'services'      => array('magistere_offers'),
        'loginrequired' => false
    )
);