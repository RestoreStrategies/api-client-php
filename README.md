# For the City API PHP client

This is a PHP client for the Restore Strategies's API. The API allows clients to view, filter, search & sign up for volunteer opportunities.

## Initializing

To use the API you need valid credentials. An instance of the API client requires a user token & secret.

```PHP
require 'client.php';

$apiClient = new RestoreStrategiesClient('<a_user_token>', '<a_user_secet>');
```

## Returned values

This client returns an objectification of the the server's JSON response.

## Viewing Opportunities

It is possible to view opportunities individually or all at once.

```PHP
// Gets the opportunity that has an id of 10.
$opportunity = $apiClient->getOpportunity(10);

// Gets all of the opportunities
$opportunities = $apiClient->listOpportunities();
```

## Search

The search function takes an array with keys & values. The following are possible keys.

* q: Free-form search term for fulltext search
* issues: An array of issues
* region: An array of geographical regions
* time: An array of times of day
* day: An array of days of the week
* type: An array of opportunity types
* group_type: An array of volunteer group types

```PHP
$searchParams = [
    'q' => 'kids and sports',
    'issues' => ['Children/Youth', 'Education']
];

$results = $apiClient->search($searchParams);
```

## Signup

The client can submit signups for opportunities. In the below example, each of the keys are required

```PHP
 $template = array(
     "givenName" => "Jon",
     "familyName" => "Doe",
     "telephone" => "5128675309",
     "email" => "jon.doe@example.com",
     "comment" => "I'm excited!",
     "numOfItemsCommitted" => 1,
     "lead" => "other"
);

$signup = $this->client->submitSignup(1, $template);

if ($signup->status == 202) {
    print 'The signup was accepted!';
}
```
