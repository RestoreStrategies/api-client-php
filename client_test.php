<?php

//require 'client.php';

class ClientTest extends PHPUnit_Framework_TestCase {

    private $client;

    public function __construct() {
        $this->client = new ForTheCityClient($_ENV['TOKEN'],
                                                $_ENV['SECRET'],
                                                $_ENV['HOST'],
                                                $_ENV['PORT']);
    }
    
    public function testGetOpportunity() {

        $opp = $this->client->getOpportunity(1);

        print $opp;
    }
}
