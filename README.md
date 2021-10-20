# Gateway API

## OVERVIEW
This README will provide details on getting your machine setup

## Requirements
***PHP 7.1***
***Composer***
***MySQL 5.7+***

## Setup 
You will need to create/update the .env file that will configure the database connections

```
composer install
cp .env.example .env
```

## Database Migrations
Setup your database structure and migrate

```
php artian migrate
```
