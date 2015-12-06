<?php
//------------------------------------------------------
//----------------- L3 MAIN CONTROLLER -----------------
//------------------------------------------------------

include 'includes/magicquotes.inc.php'; //Disable magic quotes
include 'includes/helpers.inc.php';


function recurseGetNodes($id, &$generalResult, $pdo) {

	//Write query
	$sql = 'SELECT source FROM node_links WHERE target = "' . $id . '"';
	$result = $pdo->query($sql);	//Execute query

	foreach ($result as $row) {
		//Unset all numeric or undesired indexes
		foreach ($row as $key => $value) {
			if (is_int($key))
        		unset($row[$key]);
		}

		$generalResult[] = $row;

		recurseGetNodes($row['source'], $generalResult, $pdo);
	}
}


//Node path request
if (isset($_GET['node_path'])) {

	if (isset($_GET['node_id'])) {
		include 'includes/db.inc.php'; //Connect to the database

		$nodesInvolved = array(array('source'=>$_GET['node_id']));

		//Populate the nodes involved array
		recurseGetNodes($_GET['node_id'], $nodesInvolved, $pdo);

		//Get the nodes references
		$nodes = array();

		//Write the query
		$sql = 'SELECT id,name FROM nodes WHERE';

		foreach($nodesInvolved as $node) {
			$sql .= ' id = "' . $node['source'] . '" OR';
		}

		$sql .= ' 0;';

		//echo $sql;
		//exit();

		$result = $pdo->query($sql);	//Execute query


		foreach ($result as $row) {
			//Unset all numeric or undesired indexes
			foreach ($row as $key => $value) {
				if (is_int($key))
	        		unset($row[$key]);
			}

			$nodes[] = $row;
		}


		//Get links
		//Write the query
		$sql = 'SELECT target,source FROM node_links WHERE';

		foreach($nodes as $node) {
			$sql .= ' target = "' . $node['id'] . '" OR';
		}

		$sql .= ' 0;';

		$result = $pdo->query($sql);	//Execute query

		$links = array();

		foreach ($result as $row) {
			//Unset all numeric or undesired indexes
			foreach ($row as $key => $value) {
				if (is_int($key))
	        		unset($row[$key]);
			}

			$links[] = $row;
		}

		$finalObj = array('nodes'=> $nodes, 'links'=>$links);

		echo json_encode($finalObj);
		exit();


		//Write query
		/*$sql = 'SELECT id,name FROM nodes WHERE id = "' . $_GET['node_id'] . '"';
		$result = $pdo->query($sql);	//Execute query

		$resultArr = array();

		foreach ($result as $row) {
			//Unset all numeric or undesired indexes
			foreach ($row as $key => $value) {
				if (is_int($key))
	        		unset($row[$key]);
			}

			$resultArr[] = $row;
		}

		print_r($resultArr);
		exit();*/
	} 
}

//Search request
if (isset($_GET['search_query']) && $_GET['search_query']) {

	include 'includes/db.inc.php'; //Connect to the database		

	//Write query
	$sql = 'SELECT id,name FROM nodes WHERE name = "' . $_GET['search_query'] . '"';
	$result = $pdo->query($sql);	//Execute query

	$resultArray = array();

	foreach ($result as $row) {
		
		//Unset all numeric or undesired indexes
		foreach ($row as $key => $value) {
			if (is_int($key))
        		unset($row[$key]);
		}

		$resultArray[] = $row;
	}

	//Return the results
	echo json_encode($resultArray);

	exit();
}

//User profile request
if (isset($_GET['user']) && $_GET['user']) {

	include 'includes/db.inc.php'; //Connect to the database

	//Get User Data

	//Write query
	$sql = 'SELECT * FROM users WHERE user_name = "' . $_GET['user'] . '"';

	$result = $pdo->query($sql);	//Execute query

	$row = $result->fetch();	//Get the first result

	//If there is no result, return user no found
	if(!$row) {
		htmlout("USER NOT FOUND");
		exit();
	}

	//Get user variables
	$userid = $row['id'];
	$username = $row['user_name'];
	$fullname = $row['full_name'];
	$userdesc = $row['description'];
	$userpic = $row['profile_pic_file'];

	//Get nodes of the current user

	//Write query
	$sql = 'SELECT * FROM nodes WHERE owner_id = ' . $userid ;
	$result = $pdo->query($sql);	//Execute query

	//Create array to store nodes
	$nodesArr = array();

	//Iterate thru the fetched rows and get the properties values
	foreach($result as $row) {
		
		//Unset the owner_id
		unset($row["owner_id"]);

		//Unset all numeric indexes
		foreach ($row as $key => $value) {
			if (is_int($key))
        		unset($row[$key]);
		}

		$nodesArr[] = $row;
	}

	//Parse json object from php object
	$nodesJson = json_encode($nodesArr);


	//Get node links of the current user

	//Write query
	$sql = 'SELECT * FROM node_links WHERE owner_id = ' . $userid ;
	$result = $pdo->query($sql);	//Execute query

		//Create array to store nodes
	$linksArr = array();

	//Iterate thru the fetched rows and get the properties values
	foreach($result as $row) {
		
		//Unset the owner_id
		unset($row["owner_id"]);

		//Unset all numeric indexes
		foreach ($row as $key => $value) {
			if (is_int($key))
        		unset($row[$key]);
		}

		$linksArr[] = $row;
	}

	//Parse json object from php object
	$linksJson = json_encode($linksArr);


	//Disconnect DB
	$pdo = null;

	include 'profile_page.html.php';

	exit();
}


include 'main_page.html.php';

exit();






include '/includes/magicquotes.inc.php';



if (!userIsLoggedIn())
{
	include '../login.html.php';
	exit();
}

if (!userHasRole('Account Administrator'))
{
	$error = 'Only Account Administrators may access this page.';
	include '../accessdenied.html.php';
	exit();
}

if (isset($_GET['add']))
{
	$pageTitle = 'New Author';
	$action = 'addform';
	$name = '';
	$email = '';
	$id = '';
	$button = 'Add author';
	include 'form.html.php';
	exit();
}

// Display author list
include $_SERVER['DOCUMENT_ROOT'] . '/projects/LearnSQL/includes/db.inc.php';

if (isset($_GET['editform']))
{

	try
	{
		$sql = 'UPDATE author SET
		name = :name,
		email = :email
		WHERE id = :id';
		$s = $pdo->prepare($sql);
		$s->bindValue(':id', $_POST['id']);
		$s->bindValue(':name', $_POST['name']);
		$s->bindValue(':email', $_POST['email']);
		$s->execute();
	}
	catch (PDOException $e)
	{
		$error = 'Error updating submitted author.';
		include 'error.html.php';
		exit();
	}
	header('Location: .');
	exit();
}



if (isset($_POST['action']) and $_POST['action'] == 'Edit')
{

	try
	{
		$sql = 'SELECT id, name, email FROM author WHERE id = :id';
		$s = $pdo->prepare($sql);
		$s->bindValue(':id', $_POST['id']);
		$s->execute();
	}

	catch (PDOException $e)
	{
		$error = 'Error fetching author details.';
		include 'error.html.php';
		exit();
	}

	$row = $s->fetch();
	$pageTitle = 'Edit Author';
	$action = 'editform';
	$name = $row['name'];
	$email = $row['email'];
	$id = $row['id'];
	$button = 'Update author';
	include 'form.html.php';
	exit();
}

if (isset($_GET['addform']))
{
	try
	{
		$sql = 'INSERT INTO author SET name = :name, email = :email';
		$s = $pdo->prepare($sql);
		$s->bindValue(':name', $_POST['name']);
		$s->bindValue(':email', $_POST['email']);
		$s->execute();
	}
	catch (PDOException $e)
	{
		$error = 'Error adding submitted author.';
		include 'error.html.php';
		exit();
	}
	header('Location: .');
	exit();
}

if (isset($_POST['action']) and $_POST['action'] == 'Delete')
{

	// Get jokes belonging to author
	try
	{
		$sql = 'SELECT id FROM joke WHERE authorid = :id';
		$s = $pdo->prepare($sql);
		$s->bindValue(':id', $_POST['id']);
		$s->execute();
	}

	catch (PDOException $e)
	{
		$error = 'Error getting list of jokes to delete.';
		include 'error.html.php';
		exit();
	}

	$result = $s->fetchAll();
	// Delete joke category entries
	try
	{
		$sql = 'DELETE FROM jokecategory WHERE jokeid = :id';
		$s = $pdo->prepare($sql);
		// For each joke
		foreach ($result as $row)
		{
			$jokeId = $row['id'];
			$s->bindValue(':id', $jokeId);
			$s->execute();
		}
	}
	catch (PDOException $e)
	{
		$error = 'Error deleting category entries for joke.';
		include 'error.html.php';
		exit();
	}
	// Delete jokes belonging to author
	try
	{
		$sql = 'DELETE FROM joke WHERE authorid = :id';
		$s = $pdo->prepare($sql);
		$s->bindValue(':id', $_POST['id']);
		$s->execute();
	}
	catch (PDOException $e)
	{
		$error = 'Error deleting jokes for author.';
		include 'error.html.php';
		exit();

	}
	

	// Delete the author
	try
	{
		$sql = 'DELETE FROM author WHERE id = :id';
		$s = $pdo->prepare($sql);
		$s->bindValue(':id', $_POST['id']);
		$s->execute();
	}
	catch (PDOException $e)
	{
		$error = 'Error deleting author.';
		include 'error.html.php';
		exit();
	}
	
	header('Location: .');
	exit();
}



try
{
	$result = $pdo->query('SELECT id, name FROM author');
}

catch (PDOException $e)
{
	$error = 'Error fetching authors from the database!';
	include 'error.html.php';
	exit();
}

foreach ($result as $row)
{
	$authors[] = array('id' => $row['id'], 'name' => $row['name']);
}

include 'authors.html.php';
