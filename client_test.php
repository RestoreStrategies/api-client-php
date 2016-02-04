<?php

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
        $this->assertEquals($opp->href, "/api/opportunities/1");
    }

    public function testNonexistingOpportunity() {

        $opp = $this->client->getOpportunity(1000000);
        
        $this->assertEquals($opp->statusCode, 404);
        $this->assertEquals($opp->error, "Not found");
        $this->assertEquals($opp->message, "Opportunity not found");
    }

    public function testListOpportunities() {

        $opps = $this->client->listOpportunities();

        $this->assertEquals($opps->href, "/api/opportunities");
        $this->assertEquals($opps->version, "1.0");
        $this->assertEquals(is_array($opps->items), true);
    }
}
