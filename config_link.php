<?php

//global $dbhost, $dbuser, $dbpwd, $dbport, $dbname, $dbcnx;

// Адрес сервера MySQL
$dbhost = 'localhost';
// Имя пользователя БД
$dbuser = 'a';
// Пароль к БД
$dbpwd = 'pass';
// Порт
$dbport='3306';
// БД
$dbname = "a";

// Connect DB
$mysqli = new mysqli($dbhost,  $dbuser, $dbpwd, $dbname);
if ($mysqli->connect_errno) {
    echo "Не удалось подключиться к MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}
else{
    //echo "Удалось подключиться к MySQL";  
}

$mysqli->query("SET NAMES 'utf8'");

    //Для удобства, добавим здесь переменную, которая будет содержать название нашего сайта
    $address_site = "http://";

    //Почтовый адрес администратора сайта
    $email_admin = "eu.rychkov@yahoo.com";
