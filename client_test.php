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

        $response = $this->client->getOpportunity(1);

        $href = $response->items()[0]->href;
        $this->assertEquals($href, "/api/opportunities/1");
    }

    public function testNonexistingOpportunity() {

        $response = $this->client->getOpportunity(1000000);
        $error = $response->error();

        $this->assertEquals($error->code, 404);
        $this->assertEquals($error->title, "Not found");
        $this->assertEquals($error->message, "Opportunity not found");
    }

    public function testListOpportunities() {

        $response = $this->client->listOpportunities();
        $collection = $response->raw()->collection;

        $this->assertEquals($collection->href, "/api/opportunities");
        $this->assertEquals($collection->version, "1.0");
        $this->assertEquals(is_array($response->items()), true);
    }

    public function testSearchFullText() {

        $params = [ 'q' => 'foster care' ];

        $response = $this->client->search($params);
        $collection = $response->raw()->collection;

        $this->assertEquals($collection->href, "/api/search?q=foster+care");
        $this->assertEquals($collection->version, "1.0");
        $this->assertEquals(is_array($response->items()), true);
    }


    public function testSearchParams() {

        $params = [
            'issues' => ['Education', 'Children/Youth'],
            'region' => ['South', 'Central']
        ];
        $path = "/api/search?issues[]=Education&issues[]=Children%2FYouth&region[]=South&region[]=Central";

        $response = $this->client->search($params);
        $collection = $response->raw()->collection;

        $this->assertEquals($collection->href, $path);
        $this->assertEquals($collection->version, "1.0");
        $this->assertEquals(is_array($response->items()), true);
    }

    public function testSearchParamsAndFullText() {

        $params = [
            'q' => 'foster care',
            'issues' => ['Education', 'Children/Youth'],
            'region' => ['South', 'Central']
        ];
        $path = "/api/search?q=foster+care&issues[]=Education&issues[]=Children%2FYouth&region[]=South&region[]=Central";

        $response = $this->client->search($params);
        $collection = $response->raw()->collection;

        $this->assertEquals($collection->href, $path);
        $this->assertEquals($collection->version, "1.0");
        $this->assertEquals(is_array($response->items()), true);
    }

    public function testGetSignup() {

        $response = $this->client->getSignup(1);
        $collection = $response->raw()->collection;

        $this->assertEquals($collection->version, "1.0");
        $this->assertEquals(is_array($collection->template->data), true);
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

        $response = $this->client->submitSignup(1, $template);

        $this->assertEquals($response->raw()->status, 202);
    }
}
