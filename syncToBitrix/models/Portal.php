<?php
class Portal
{
    private $api;

    public function __construct()
    {
        $this->api = Api::getInstance();
    }

    public function create($data) {}

    public function getByDomain($domain) {}

    public function updateSettings($domain, $settings) {}

    public function getAllActive() {}
}
