<?php

$user='postgres.whznrsvlicbdgjkrpzvz'; 
$password='Dailyfix041517'; 
$host='aws-0-ap-south-1.pooler.supabase.com';
$database='postgres';              
$port='5432';
$dbname='postgres';    
$schemaName = 'dailyfix';              

// A constant for your project's base URL.
define('BASE_URL', '/dailyfix/');

// Create a PostgreSQL connection string (DSN)
$dsn = "pgsql:host=$host;port=$port;dbname=$database;options='--search_path=$schemaName'";

try {
    // Create a new PDO instance to establish the database connection
    $conn = new PDO($dsn, $user, $password);

    // Set the PDO error mode to exception for better error handling
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    // This will show you the exact error for debugging
    die("Connection failed: " . $e->getMessage());
}