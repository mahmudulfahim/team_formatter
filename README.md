# Assessment

## Description

A symfony api that allows a csv team hierarchy then returns it in a json format, and it is filterable by team name. 

## Requirements

- PHP version (e.g., PHP 8.2 or higher)
- Symfony version 7.2.*
- Composer for package management

## Installation

   ```bash 
   git clone git@github.com:mahmudulfahim/team_formatter.git

 ```bash
   cd team_formatter
   composer install
  ```

## Configuration

**Setup API token key:**
   Update API_TOKEN in .env file or use the current one.

**Start the sever:**

 ```bash
    symfony server:start
  ```

## API
Endpoint is http://127.0.0.1:8000/api/format-team.

Payload params are file and _q.

Use BearerToken as authorization mechanism

```bash
curl --location '127.0.0.1:8000/api/format-team' \
--header 'Authorization: Bearer DeeperSignalTest' \
--form 'file=@"team.csv"' \
--form '_q="Sales"'
```
```
### Also you can access swagger documentation http://127.0.0.1:8000/api/doc
```
## Implementation

### TeamController - `src\Controller`

It contains the method formatTeam and annotated for the route `/api/format-team`

### Security - `src\Security`

Implementation of a custom authenticator to validate the Authorization using API_TOKEN variable in .env

### Service - `src\Service`

There are 3 services implemented for different purposes.

- CsvReaderService: It accepts the uploaded csv file and then converts it into a php array.
- TeamDataValidatorService: Contains validation rules for data
  - Parent_team will always occur  in the team column,
  - Manager name will always be populated
  - Every team except the root node will have a parent team
  - business_unit is not required
- TeamHierarchyService: Functionality to build a tree based on provided array. As well as traversing the tree nodes to filter hierarchy.



