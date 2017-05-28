# CouchDb - a library for accessing couchDB easily

## Description
a little wrapper for curl calls to manage data by NoSql CouchDb.<br>

## Usage
<code>
$cdb = new CouchDb();

// set server and port<br>
$cdb->setServer($server = 'http://127.0.0.1', $port = 5984)

// set db<br>
$cdb->setDb($db)

// set user and pass (optional)<br>
$cdb->setUser($user, $pass)

// set response as object or json string (optional)
$cdb->setResponseAsObject($option = false)

// send data<br>
// method = PUT|GET|DELETE<br>
// id = document id<br>
// data = string containing data (optional)<br>
$cdb->send($method, $id, $data = null)

// add attachment to existing document<br>
$cdb->addAttachment($id, $filename)

// creates view<br>
// designName = string containing name<br>
// viewName = string containing name of View<br>
// script = string containting the javascript 
$cdb->createView($designName, $viewName, $script)

// deletes the view Part document<br>
// for deleting design document use send() with delete option<br>
$cdb->deleteView($designName, $viewName = null)

// gets data from a view
// filtering by Keyvalue at the url (?key=bla)<br>
$cdb->getView($design, $view, $keyValue = null)
</code>

## Test Coverage
![test-coverage](testcoverage.png)

## Requirements for PHPUnit testing
- curl installed for php
- CouchDb installed locally
- port: 5984
- user: phpunit
- pass: unittests
- db: unittests