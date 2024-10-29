<?php
const APPPAGO_SVIL = 'svil';
const APPPAGO_STAGING = 'staging';
const APPPAGO_PROD = 'prod';

//MODIFY ONLY THIS CONSTANT FOR CHANGE ENVIRONMENT
const APPPAGO_ENV = APPPAGO_PROD;

if (APPPAGO_ENV == APPPAGO_SVIL) {
    include_once($this->path . '/includes/configs/apppago_svil.php');
}

if (APPPAGO_ENV == APPPAGO_STAGING) {
    include_once($this->path . '/includes/configs/apppago_staging.php');
}

if (APPPAGO_ENV == APPPAGO_PROD) {
    include_once($this->path . '/includes/configs/apppago_prod.php');
}

