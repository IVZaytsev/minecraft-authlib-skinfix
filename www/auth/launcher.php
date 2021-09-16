<?php
/*
 * Скрипт обработки запросов лаунчера/мода клиента. Формат JSON-запроса:
 * {
 * 	"method": 	"что_делать", (например, auth - авторизация)
 * 	"username": "имя_пользователя",
 * 	"password": "пароль"
 * }
 */

// Подключаем файл настроек
include_once('config.php');
debug('Вызов скрипта. Метод: ' . $_SERVER['REQUEST_METHOD'] . ' | Тип содержимого: ' . $_SERVER["CONTENT_TYPE"]);

// Проверяем, что мы получили POST-запрос с JSON-содержимым
if ($_SERVER['REQUEST_METHOD'] != 'POST') { debug('Ошибка! Метод запроса должен быть POST'); jsonMessage('Method Not Allowed', 'Something other than a POST request was received'); }
if (stripos($_SERVER["CONTENT_TYPE"], 'application/json') !== 0) { debug('Ошибка! Content-Type должен быть application/json'); jsonMessage('Unsupported Media Type', 'Data was not submitted as application/json'); }

// Переводим JSON-содержимое запроса в массив (для удобства)
$data = json_decode(file_get_contents('php://input'), TRUE);
	
if (!isset($data['method'])) jsonMessage('ERROR', 'Не указан метод обработки данных');
	
// Тут решаем, что скрипт будет делать дальше: регистрировать нового пользователя, авторизоввывать и т.д.
switch ($data['method'])
{
	case 'auth':
		// Проходим проверки на достоверные данные и запускаем функцию авторизации
		if (!isset($data['username'])) jsonMessage('ERROR', 'Отсутствуют необходимые данные для авторизации');
		if (!isset($data['password'])) jsonMessage('ERROR', 'Отсутствуют необходимые данные для авторизации');
		
		if (!preg_match('/^[A-Za-z]{1}[A-Za-z0-9]{3,16}$/', $data['username'])) jsonMessage('ERROR', 'Некорректное имя пользователя');
		if (!preg_match('/^[a-zA-Z0-9_-]+$/', $data['password'])) jsonMessage('ERROR', 'Пароль содержит недопустимые символы');
		
		$mysql = new mysqli($MYSQL_HOSTNAME, $MYSQL_USERNAME, $MYSQL_PASSWORD, $MYSQL_DATABASE, $MYSQL_PORT);
		if ($mysql->connect_error) jsonMessage('ERROR', 'Ошибка БД MySQL #'.$mysql->connect_errno);
		
		$query = 'SELECT username,uuid FROM players WHERE LOWER(username)="' . strtolower($data['username']) . '" AND password="' . md5($data['password']) . '"';
		debug('Запрос MySQL: ' . $query);
		
		$result = $mysql->query($query);
		if ($result == FALSE) jsonMessage('ERROR', 'Ошибка БД MySQL #'.$mysql->errno);
		debug('Найдено результатов: ' . $result->num_rows);
		if ($result->num_rows != 1) { debug('Ошибка! Профиль пользователя не найден'); $mysql->close(); jsonMessage('ERROR', 'Wrong login / password'); }
		
		$player = $result->fetch_assoc();
		$accessToken = generateAccessToken();
		
		$query = 'UPDATE players SET accessToken="' . $accessToken . '" WHERE username="' . $data['username'] . '" AND password="' . md5($data['password']) . '"';
		debug('Запрос MySQL: ' . $query);
		
		$mysql->query($query);
		if ($result == FALSE) jsonMessage('ERROR', 'Ошибка БД MySQL #'.$mysql->errno);
		
		$answer = array(
			'status' => 'OK',
			'message' => 'Auth complete!',
			'username' => $player['username'],
			'UUID' => $player['uuid'],
			'accessToken' => $accessToken,
			'server' => '127.0.0.1'
			);
		echo json_encode($answer);
		$mysql->close();		
		break;
		
	default:
		jsonMessage('ERROR', 'Указанный метод обработки данных не найден');
		break;
}
die();

// Функция авторизации. Отдаёт лаунчеру данные в формате Username:UUID:accessToken
function doAuth($user, $password)
{
	$mysql = new mysqli($MYSQL_HOSTNAME, $MYSQL_USERNAME, $MYSQL_PASSWORD, $MYSQL_DATABASE, $MYSQL_PORT);
	if ($mysql->connect_error) jsonMessage('ERROR', 'Ошибка БД MySQL #'.$mysql->connect_errno);
	
	// Проверяем, есть ли в базе данных запись с пользователем и паролем
	$query = 'SELECT * FROM players WHERE LOWER(username)="' . strtolower($user) . '" AND password="' . md5($password) . '"';
	debug('Запрос MySQL: ' . $query);
	
	$result = $mysql->query($query);
	if ($result == FALSE) jsonMessage('ERROR', 'Ошибка БД MySQL #'.$mysql->errno);
	
	if ($result->num_rows == 1)
	{
		// Если есть - то генерируем новый accessToken и добавляем его в базу
		$player = $result->fetch_assoc();
		$accessToken = generateAccessToken();
		
		$query = 'UPDATE players SET accessToken="'.$accessToken.'" WHERE username="'.$user.'" AND password="'.md5($password).'";';
		file_put_contents('zaza.txt', $query.$player['uuid']);
		// Выводим лаунчеру результат успешной авторизации
		$answer = array(
			'status' => 'OK',
			'message' => array(
				'username' => $player['username'],
				'UUID' => $player['uuid'],
				'accessToken' => $accessToken
			)
		);
		//file_put_contents('zaza.txt', json_encode($answer, JSON_UNESCAPED_UNICODE));
		echo json_encode($answer);
		$mysql->query($query);
		$mysql->close();

	}
	else
	{
		$mysql->close();
		jsonMessage('ERROR', 'Ошибка авторизации. Неправильная пара логин-пароль');
	}
}

// Функция, создающая accessToken при успешной попытке авторизации
function generateAccessToken()
{
	srand(time());
	$randNum = rand(1000000000, 2147483647) . rand(1000000000, 2147483647) . rand(0, 9);
	return md5($randNum);
}

// Функция генерирует сообщения для лаунчера в формате JSON
function jsonMessage($status, $message)
{
	// Для PHP < 5.4.0 нужно убрать параметр JSON_UNESCAPED_UNICODE
    echo json_encode(array('status' => $status, 'message' => $message), JSON_UNESCAPED_UNICODE);
	die();
}
?>
