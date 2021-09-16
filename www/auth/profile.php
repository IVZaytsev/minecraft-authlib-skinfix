<?php
/*
 * Скрипт возвращает информация о профиле игрока. Формат GET-запроса:
 * profile.php?uuid=<uuid>
 * Формат JSON-ответа:
 * {
 *     "id": "UUID_игрока",
 *     "name": "ник_игрока",
 *     "properties": [ 
 *         {
 *             "name": "textures",
 *             "value": "base64-строка_профиля",
 *             "signature": "ненужная_строка_=)"
 *         }
 *     ]
 * }
 * Подробнее можно почитать тут: https://wiki.vg/Mojang_API#UUID_to_Profile_and_Skin.2FCape
 */

// Подключаем файл настроек
include('config.php');
debug('Вызов скрипта. Метод: ' . $_SERVER['REQUEST_METHOD']);

// Проверяем, что мы получили GET-запрос
if ($_SERVER['REQUEST_METHOD'] != 'GET') { debug('Ошибка! Метод запроса должен быть GET'); die(); }

// Проверяем, что параметры получены
if (!isset($_GET['uuid'])) { debug('Ошибка! Параметр uuid не получен'); die(); }

// Проверяем, что uuid необходимой длины
if (strlen($_GET['uuid']) != 32) { debug('Ошибка! Длина uuid не равна 32'); die(); }

// Подключаемся к MySQL
$mysql = new mysqli($MYSQL_HOSTNAME, $MYSQL_USERNAME, $MYSQL_PASSWORD, $MYSQL_DATABASE, $MYSQL_PORT);
if ($mysql->connect_error) { debug('Ошибка подключения MySQL: ' . $mysql->connect_error); die(); }

// Формируем запрос на поиск
$query = 'SELECT username FROM players WHERE uuid="' . $_GET['uuid'] . '"';
debug('Запрос MySQL: ' . $query);

// Выполняем запрос и проверяем, есть ли результат
$result = $mysql->query($query);
if (!$result) { debug('Ошибка запроса MySQL: ' . $mysql->error); $mysql->close(); die(); }
debug('Найдено результатов: ' . $result->num_rows);
if ($result->num_rows != 1) { debug('Ошибка! Профиль с указанным UUID не найден'); $mysql->close(); die(); }

// Профиль найден. Возвращаем профиль игрока серверу
$userdata = $result->fetch_assoc();
$profile = getProfile($_GET['uuid'], $userdata['username']);
$mysql->close();
echo json_encode($profile);
debug('Успешное выполнение скрипта! Выходим...' . PHP_EOL);
die();
?>