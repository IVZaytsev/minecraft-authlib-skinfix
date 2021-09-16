<?php
/*
 * Скрипт обработки запросов авторизации клиента. Формат JSON-запроса:
 * {
 *	"accessToken": "Токен для доступа",
 *	"selectedProfile": "UUID пользователя (без симолов тире)",
 *	"serverId": "ID сервера"
 * }
 * Подробнее можно почитать тут: https://wiki.vg/Protocol_Encryption#Client
 */

// Подключаем файл настроек
include_once('config.php');
debug('Вызов скрипта. Метод: ' . $_SERVER['REQUEST_METHOD'] . ' | Тип содержимого: ' . $_SERVER["CONTENT_TYPE"]);

// Проверяем, что мы получили POST-запрос с JSON-содержимым
if ($_SERVER['REQUEST_METHOD'] != 'POST') { debug('Ошибка! Метод запроса должен быть POST'); jsonError('Method Not Allowed', 'Something other than a POST request was received'); }
if (stripos($_SERVER["CONTENT_TYPE"], 'application/json') !== 0) { debug('Ошибка! Content-Type должен быть application/json'); jsonError('Unsupported Media Type', 'Data was not submitted as application/json'); }

//Получаем и декодируем JSON-данные
debug('Получены JSON-данные: ' . file_get_contents('php://input'));
$data = json_decode(file_get_contents('php://input'), TRUE);

// Проверяем, что все 3 параметра получены
if (!isset($data['accessToken'])) { debug('Ошибка! Параметр accessToken не получен'); jsonError('IllegalArgumentException', 'Bad arguments'); }
if (!isset($data['selectedProfile'])) { debug('Ошибка! Параметр selectedProfile не получен'); jsonError('IllegalArgumentException', 'Bad arguments'); }
if (!isset($data['serverId'])) { debug('Ошибка! Параметр serverId не получен'); jsonError('IllegalArgumentException', 'Bad arguments'); }

// Проверяем, что все 3 параметра необходимой длины
if (strlen($data['accessToken']) != 32) { debug('Ошибка! Длина accessToken не равна 32'); jsonError('IllegalArgumentException', 'Bad arguments'); }
if (strlen($data['selectedProfile']) != 32) { debug('Ошибка! Длина selectedProfile не равна 32'); jsonError('IllegalArgumentException', 'Bad arguments'); }
if (strlen($data['serverId']) < 39) { debug('Ошибка! Длина serverId меньше 39'); jsonError('IllegalArgumentException', 'Bad arguments'); }
if (strlen($data['serverId']) > 41) { debug('Ошибка! Длина serverId больше 41'); jsonError('IllegalArgumentException', 'Bad arguments'); }

// Проверяем, что все 3 параметра содержат только разрешенные символы
if (!preg_match('/^[a-zA-Z0-9]+$/', $data['accessToken'])) { debug('Ошибка! Параметр accessToken содержит недопустимые символы'); jsonError('IllegalArgumentException', 'Bad arguments'); }
if (!preg_match('/^[a-zA-Z0-9]+$/', $data['selectedProfile'])) { debug('Ошибка! Параметр selectedProfile содержит недопустимые символы'); jsonError('IllegalArgumentException', 'Bad arguments'); }
if (!preg_match('/^[a-zA-Z0-9_-]+$/', $data['serverId'])) { debug('Ошибка! Параметр serverId содержит недопустимые символы'); jsonError('IllegalArgumentException', 'Bad arguments'); }

// Подключаемся к MySQL
$mysql = new mysqli($MYSQL_HOSTNAME, $MYSQL_USERNAME, $MYSQL_PASSWORD, $MYSQL_DATABASE, $MYSQL_PORT);
if ($mysql->connect_error) { debug('Ошибка подключения MySQL: ' . $mysql->connect_error); jsonError('InternalOperationException', 'MySQL connection error'); }

// Формируем запрос на поиск связки [selectedProfile:accessToken]
$query = 'SELECT id FROM players WHERE uuid="'.$data['selectedProfile'].'" AND accessToken="'.$data['accessToken'].'";';
debug('Запрос MySQL: ' . $query);

// Выполняем запрос и проверяем, есть ли результат
$result = $mysql->query($query);
if (!$result) { debug('Ошибка запроса MySQL: ' . $mysql->error); $mysql->close(); jsonError('InternalOperationException', 'MySQL query error'); }
debug('Найдено результатов: ' . $result->num_rows);
if ($result->num_rows != 1) { debug('Ошибка авторизации! Связка ' . $data['accessToken'] . ':' . $data['selectedProfile'] . ' не найдена'); $mysql->close(); jsonError('ForbiddenOperationException', 'Invalid username or password'); }

// Связка найдена. Формируем запрос на обновление serverId
$query = 'UPDATE players SET serverID="'.$data['serverId'].'" WHERE uuid="'.$data['selectedProfile'].'" AND accessToken="'.$data['accessToken'].'";';
debug('Запрос MySQL: ' . $query);

// Выполняем запрос и проверяем, не возникло ли ошибки
$result = $mysql->query($query);
if (!$result) { debug('Ошибка запроса MySQL: ' . $mysql->error); $mysql->close(); jsonError('InternalOperationException', 'MySQL query error'); }

// Всё отлично! Закрываем соединение и выходим
debug('Успешное выполнение скрипта! Выходим...' . PHP_EOL);
$mysql->close();
die();

/*
 * Функция вывода сообщения клиенту об ошибке. Формат JSON-сообщения:
 * {
 *     "error": "Короткое сообщение об ошибке",
 *     "errorMessage": "Полное описание, отображаемое пользователю в клиенте игры",
 *     "cause": "Причина возникновения ошибки" // Опционально
 * }
 * Подробнее можно почитать тут: https://wiki.vg/Authentication#Request_format
 */
function jsonError($error, $errorMessage)
{
	echo json_encode(array('error' => $error, 'errorMessage' => $errorMessage, 'cause' => ''));
	die();
}
?>