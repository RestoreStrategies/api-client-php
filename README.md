# Restore Strategies's API PHP client

This is a PHP client for the Restore Strategies's API. The API allows clients to view, filter, search & sign up for volunteer opportunities.

## Initializing

To use the API you need valid credentials. An instance of the API client requires a user token & secret.

```PHP
require 'client.php';

$apiClient = new RestoreStrategiesClient('<a_user_token>', '<a_user_secet>');
```

## Returned values

This client returns Response objects. A Response object has 3 functions:

* ```raw()```, an objectified version of the server's raw JSON response
* ```items()```, an array of opportunities, if any exist
* ```error()```, an error message, if it exists

## Viewing Opportunities

It is possible to view opportunities individually or all at once.

```PHP
// Gets the opportunity that has an id of 10 & print it's name.
$response = $apiClient->getOpportunity(10);
print 'This opportunity is called ' . $response->items()[0]->name;

// Gets all of the opportunities
$listResponse = $apiClient->listOpportunities();

// If your API user is in a different city than you're interested in, you can
// specify the city of interest
$response = $apiClient->getOpportunity(10, 'Austin');
$listResponse = $apiClient->listOpportunities('Austin');
```

## Search

The search function takes an array with keys & values. The following are possible keys.

* q: Free-form search term for fulltext search
* issues: An array of issues. Acceptable values: 'Children/Youth', 'Elderly', 'Family/Community', 'Foster Care/Adoption', 'Healthcare', 'Homelessness', 'Housing', 'Human Trafficking', 'International/Refugee', 'Job Training', 'Sanctity of Life', 'Sports', and 'Incarceration'
* regions: An array of geographical regions. Acceptable values: 'North', 'Central', 'East', 'West', and 'Other'
* municipalities: An array of municipalities (i.e. suburbs, neighborhoods, etc). Acceptable values vary by metropolitan area.
* times: An array of times of day. Acceptable values: 'Morning', 'Mid-Day', 'Afternoon', 'Evening'
* days: An array of days of the week. Acceptable values: 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', and 'Sunday'
* type: An array of opportunity types. Acceptable values: 'Gift', 'Service', 'Specific Gift', 'Training'
* group_types: An array of volunteer group types. Acceptable values: 'Individual', 'Group', 'Family'

```PHP
$searchParams = [
    'q' => 'kids and sports',
    'issues' => ['Children/Youth', 'Education']
];

$response = $apiClient->search($searchParams);

// If your API user is in a different city than you're interested in, you can
// specify the city of interest
$response = $apiClient->search($searchParams, 'Austin');
```

## Signup

The client can submit signups for opportunities. In the below example, each of the keys are required

```PHP
 $template = array(
     "givenName" => "Jon", // required
     "familyName" => "Doe", // required
     "telephone" => "5128675309", // required
     "email" => "jon.doe@example.com", // required
     "comment" => "I'm excited!",
     "numOfItemsCommitted" => 1, // only useful for gift opportunities
     "lead" => "other",
     "campus" => "Example Campus",
     "church" => "Example Church" // defaults to API user's church name
);

$response = $this->client->submitSignup(1, $template);

// or signup in a particular city
$response = $this->client->submitSignup(1, $template, 'Austin');


if ($response->raw()->status == 202) {
    print 'The signup was accepted!';
}
```
