<?php

require './lib/signup.php';

class ClientTest extends PHPUnit_Framework_TestCase {

    private $client;

    public function __construct() {
        $this->client = new RestoreStrategiesClient($_ENV['TOKEN'],
                                                $_ENV['SECRET'],
                                                $_ENV['HOST'],
                                                $_ENV['PORT']);
    }

    public function testGetOpportunity() {

        $opp = $this->client->getOpportunity(1);

        $href = $opp->collection->href;
        $this->assertEquals($href, "/api/opportunities/1");
    }

    public function testNonexistingOpportunity() {

        $opp = $this->client->getOpportunity(1000000)->collection;

        $error = $opp->error;
        $this->assertEquals($error->code, 404);
        $this->assertEquals($error->title, "Not found");
        $this->assertEquals($error->message, "Opportunity not found");
    }

    public function testListOpportunities() {

        $opps = $this->client->listOpportunities()->collection;

        $this->assertEquals($opps->href, "/api/opportunities");
        $this->assertEquals($opps->version, "1.0");
        $this->assertEquals(is_array($opps->items), true);
    }

    public function testSearchFullText() {

        $params = [ 'q' => 'foster care' ];

        $opps = $this->client->search($params)->collection;

        $this->assertEquals($opps->href, "/api/search?q=foster+care");
        $this->assertEquals($opps->version, "1.0");
        $this->assertEquals(is_array($opps->items), true);
    }


    public function testSearchParams() {

        $params = [
            'issues' => ['Education', 'Children/Youth'],
            'region' => ['South', 'Central']
        ];
        $path = "/api/search?issues[]=Education&issues[]=Children%2FYouth&region[]=South&region[]=Central";

        $opps = $this->client->search($params)->collection;

        $this->assertEquals($opps->href, $path);
        $this->assertEquals($opps->version, "1.0");
        $this->assertEquals(is_array($opps->items), true);
    }

    public function testSearchParamsAndFullText() {

        $params = [
            'q' => 'foster care',
            'issues' => ['Education', 'Children/Youth'],
            'region' => ['South', 'Central']
        ];
        $path = "/api/search?q=foster+care&issues[]=Education&issues[]=Children%2FYouth&region[]=South&region[]=Central";

        $opps = $this->client->search($params)->collection;

        $this->assertEquals($opps->href, $path);
        $this->assertEquals($opps->version, "1.0");
        $this->assertEquals(is_array($opps->items), true);
    }

    public function testGetSignup() {

        $signup = $this->client->getSignup(1);
        $this->assertEquals($signup->collection->version, "1.0");
        $this->assertEquals(is_array($signup->collection->template->data), true);
    }

    public function testSubmitSignup() {

        $template = array(
            "givenName" => "Jon",
            "familyName" => "Doe",
            "telephone" => "5124567890",
            "email" => "jon.doe@example.com",
            "comment" => "I'm excited!",
            "numOfItemsCommitted" => 1,
            "lead" => "other"
       );

        $signup = $this->client->submitSignup(1, $template);
        $this->assertEquals($signup->status, 202);
    }
}
