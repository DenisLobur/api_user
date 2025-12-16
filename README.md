USER CRUD

How to run via docker:
- run **symfony server:start** - to run the server
- run **docker-compose -f docker-compose.yml up** - to run docker with mysql db and adminer

How to test:
- run **php bin/phpunit** - to run tests (currently tests cover Controller and Fixture)

How to add test data to db (if needed):
- run **symfony console doctrine:fixture:load** - to populate db with users and tokens

- test user:password that was used here:
**root:root**

- Database dump is located in the root:
**test-db.sql** 