<?php
/*
 * Скрипт обработки запросов сервера. Формат GET-запроса:
 * hasJoined?username=<имя_пользователя>&serverId=<ID-сервера>&ip=<IP-адрес_клиента>
 * Подробнее можно почитать тут: https://wiki.vg/Protocol_Encryption#Server
 */

// Подключаем файл настроек
include_once('config.php');
debug('Вызов скрипта. Метод: ' . $_SERVER['REQUEST_METHOD']);

// Проверяем, что мы получили GET-запрос
if ($_SERVER['REQUEST_METHOD'] != 'GET') { debug('Ошибка! Метод запроса должен быть GET'); die(); }

// Проверяем, что параметры получены
if (!isset($_GET['username'])) { debug('Ошибка! Параметр username не получен'); die(); }
if (!isset($_GET['serverId'])) { debug('Ошибка! Параметр serverId не получен'); die(); }

// Проверяем, что параметры необходимой длины
if (strlen($_GET['username']) > 16) { debug('Ошибка! Длина username больше 16'); die(); }
if (strlen($_GET['serverId']) < 39) { debug('Ошибка! Длина serverId меньше 39'); die(); }
if (strlen($_GET['serverId']) > 41) { debug('Ошибка! Длина serverId больше 41'); die(); }

// Проверяем, что все 3 параметра содержат только разрешенные символы
if (!preg_match('/^[a-zA-Z0-9]+$/', $_GET['username'])) { debug('Ошибка! Параметр username содержит недопустимые символы'); die(); }
if (!preg_match('/^[a-zA-Z0-9_-]+$/', $_GET['serverId'])) { debug('Ошибка! Параметр serverId содержит недопустимые символы'); die(); }

// Подключаемся к MySQL
$mysql = new mysqli($MYSQL_HOSTNAME, $MYSQL_USERNAME, $MYSQL_PASSWORD, $MYSQL_DATABASE, $MYSQL_PORT);
if ($mysql->connect_error) { debug('Ошибка подключения MySQL: ' . $mysql->connect_error); die(); }

// Формируем запрос на поиск
$query = 'SELECT username,uuid FROM players WHERE username="' . $_GET['username'] . '" AND serverId="' . $_GET['serverId'] . '"';
debug('Запрос MySQL: ' . $query);

// Выполняем запрос и проверяем, есть ли результат
$result = $mysql->query($query);
if (!$result) { debug('Ошибка запроса MySQL: ' . $mysql->error); $mysql->close(); die(); }
debug('Найдено результатов: ' . $result->num_rows);
if ($result->num_rows != 1) { debug('Ошибка! Профиль пользователя не найден'); $mysql->close(); die(); }

// Профиль найден. Возвращаем профиль игрока серверу
$userdata = $result->fetch_assoc();
$profile = getProfile($userdata['uuid'], $userdata['username']);
$mysql->close();
echo json_encode($profile);
debug('Успешное выполнение скрипта! Выходим...' . PHP_EOL);
die();
?>