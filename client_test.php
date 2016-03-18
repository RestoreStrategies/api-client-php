<?php

require './lib/signup.php';

class ClientTest extends PHPUnit_Framework_TestCase {

    private $client;

    public function __construct() {
        $this->client = new ForTheCityClient($_ENV['TOKEN'],
                                                $_ENV['SECRET'],
                                                $_ENV['HOST'],
                                                $_ENV['PORT']);
    }

    public function testGetOpportunity() {

        $opp = $this->client->getOpportunity(1)->collection;
        $this->assertEquals($opp->href, "/api/opportunities/1");
    }

    public function testNonexistingOpportunity() {

        $opp = $this->client->getOpportunity(1000000)->collection->error;

        $this->assertEquals($opp->code, 404);
        $this->assertEquals($opp->title, "Not found");
        $this->assertEquals($opp->message, "Opportunity not found");
    }

    public function testListOpportunities() {

        $opps = $this->client->listOpportunities();

        $this->assertEquals($opps->href, "/api/opportunities");
        $this->assertEquals($opps->version, "1.0");
        $this->assertEquals(is_array($opps->items), true);
    }

    public function testSearchFullText() {

        $params = [ 'q' => 'foster care' ];

        $opps = $this->client->search($params);

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

        $opps = $this->client->search($params);

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

        $opps = $this->client->search($params);

        $this->assertEquals($opps->href, $path);
        $this->assertEquals($opps->version, "1.0");
        $this->assertEquals(is_array($opps->items), true);
    }

    /*public function testPostSignup() {

        $template = "{
            \"template\": {
                \"data\": [
                    { \"name\": \"givenName\", \"value\": \"Timothy\" },
                    { \"name\": \"familyName\", \"value\": \"Johnson\" },
                    { \"name\": \"telephone\", \"value\": \"829-384-6743\" },
                    { \"name\": \"email\", \"value\": \"timothy.johnson@fakeemail.com\" },
                    { \"name\": \"church\", \"value\": \"Austin Stone Community Church\" },
                    { \"name\": \"churchOther\", \"value\": \"\" },
                    { \"name\": \"churchCampus\", \"value\": \"Downtown PM\" },
                    { \"name\": \"comment\", \"value\": \"\" },
                    { \"name\": \"numOfItemsCommitted\", \"value\": 2 },
                    { \"name\": \"lead\", \"object\": {\"1\": true, \"6\": true} }
                ]
            }
        }";

        $opps = $this->client->postSignup(1, $template);
        $this->assertEquals($opps->statusCode, 201);
    }*/

    public function testGetSignup() {
        $signup = $this->client->getSignup(1);
        $this->assertEquals($signup->collection->version, "1.0");
        $this->assertEquals(is_array($signup->collection->template->data), true);
    }

    /*public function loadTestSignup() {
        $templateFile = fopen("./lib/signupTemplate.json", "r");
        $templateJSON = "";

        while (!feof($templateFile)) {
            $templateJSON = $templateJSON . fread($templateFile, 100);
        }

        fclose($templateFile);
        $templateJSON = json_decode($templateJSON, false);
        $signup = new SignUp($templateJSON);
        return $signup;
    }

    public function testSignupGetHTML() {
        $signup = $this->loadTestSignup();

        $document = new DOMDocument();
        $inputDoc = $signup->getHTML($document, "churchCampus", "test", "austinStoneCC");
        $document->appendChild($inputDoc);
        $htmlString = $document->saveHTML();

        // Validate the html
        $doc = new DOMDocument();
        $loaded = $doc->loadHTML($htmlString);
        $this->assertTrue($loaded);
    }

    public function testSignupGetOptions() {
        $signup = $this->loadTestSignup();

        $optionsPossible = $signup->getOptions("church", "yes");
        $this->assertEquals(count($optionsPossible), 9);

        $optionsEmpty = $signup->getOptions("church", "no");
        $this->assertEquals(count($optionsEmpty), 0);
    }*/
}
