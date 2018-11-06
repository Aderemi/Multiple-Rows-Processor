# Laravel CSV Sheet Processor

Laravel CSV Sheet Processor is a package for Laravel 5 which is used to manage multiple record processing at database level through CSV files. 
This makes applications with multiple uploads through CSV much easier to maintain.

## Installation

Run the following command from you terminal:


 ```bash
 composer require "supermart_nigeria/library: @dev"
 ```

or add this to require section in your composer.json file:

 ```
 "supermart_nigeria/library": "@dev"
 ```

then run ```composer update```


## Usage

First, create your Sheet class. Note that your sheet class MUST extend ```MultipleRows\Contract\MultipleRows``` and implement four methods ```rule(string $method, array $data)```,
```getUniqueIDField()```, ```processor()``` and  ```model()```
And you must define 5 constants which are the headers of your sheets processes
```php
<?php
namespace MultipleRows\Tests;


use MultipleRows\Contract\MultipleRows;

class TestSheet extends MultipleRows
{
    const CREATE_HEADER = ['unique_test_id', 'name', 'status'];
    const UPDATE_HEADER = ['unique_test_id'];
    const DELETE_HEADER = ['unique_test_id', 'name'];
    const STATUS_CHANGE_HEADER = ['unique_test_id'];
    const UNIQUE_FIELDS = ['unique_test_id', 'name'];

    /**
     * @param string $method
     * @param array $data
     * @return array
     */
    protected function rules(string $method, array $data): array
    {
        switch($method){
            case "POST":
                return [
                    'unique_test_id' => "required|unique:" . TestModel::getTableName(),
                ];
            case "PUT" :
                return [
                    'unique_test_id' => "required|exists:" . TestModel::getTableName(),
                ];
        }
    }

    /**
     * @return string
     */
    public function getUniqueIDField(): string
    {
        return 'unique_test_id';
    }

    /**
     * @return string
     */
    protected function model()
    {
        return new TestModel();
    }

    /**
     * @return string
     */
    protected function processor()
    {
        return new TestProcessor();
    }
}
?>
php```
Create the process which will extend MultipleRows\Behaviour\Processor

```php
<?php
use MultipleRows\Behaviour\Processor;

class TestProcessor extends Processor
{

}
?>

php```

## Available Methods

The following methods are available:

##### MultipleRows\Behaviour

```php

```

The following methods are available:

##### MultipleRows\Behaviour\Processor

```php
public function beforeCreate(array $data) // If you over-ride this method it means you want to handle your create by yourself
public function beforeUpdate(array $data) // If you over-ride this method it means you want to handle your update by yourself
```


The JSON format currently supported are 
```json
// 1
{
  "header" : ["sku", "name", "price"],
  "body" : [
    ["394AG", "Tomatoes", 300],
    ["344AG", "Big Tomatoes", 500]
  ]
}
// 2
[
{
  "sku"   : "324AF",
  "name"  : "Tin Tomatoes",
  "price" : 100
},
{
  "sku"   : "334AF",
  "name"  : "Sachet Tomatoes",
  "price" : 70
},
{
  "sku"   : "324AF",
  "name"  : "Mashed Tomatoes",
  "price" : 400
}
]
// and 3
[
  ["sku", "name", "price"],
  ["321GE", "Pepper", 320],
  ["323GE", "Seasoning", 20]
]  

```

XML is under construction
Still editing....[STILL WORKING ON IT]
