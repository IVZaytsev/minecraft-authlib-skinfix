<?php
// Настройки для подключения к БД MySQL
$MYSQL_HOSTNAME		= 'localhost';	// Адрес сервера
$MYSQL_PORT			= 3306;			// Номер порта
$MYSQL_USERNAME		= 'root';		// Имя пользователя
$MYSQL_PASSWORD		= 'toor';		// Пароль
$MYSQL_DATABASE		= 'auth';		// Имя базы данных

/*
 * Переменная $DEBUG (включает / отключает) режим отладки скриптов
 * Если режим отладки включен - будет сформирован файл $DEBUG_LOG
 * $DEBUG_LOG содержит в себе все события по взаимодейтвию со скриптами
 * По окончанию настройки скриптов - переведите значение $DEBUG в FALSE
 */
$DEBUG				= FALSE;
$DEBUG_LOG			= 'debug.log';

/*
 * Локальный путь к папке с текстурами скинов и плащей
 * Текстуры скинов: /skin/имя_игрока.png
 * Текстуры плащей: /cape/имя_игрока.png
 * Эта папка нужна для формирования MD5-хешей скинов (см. ниже)
 */
$PATH_TEXTURES		= './textures';

/*
 * Локальный путь к папке со скинами и плащами
 * Каждый файл здесь назван собственным MD5-хешем
 * Пример: 3f1506ebc589e2a742a101a393e01deb.png
 * Именно эти файлы будут отданы клиенту на загрузку,
 * а не те, что находятся в папке 'textures'
 */
$PATH_SKINS			= './skins';

/*
 * URL-ссылка к папке с MD5-хешами скинов и плащей
 * Эта ссылка будет отправлена клиенту игры для загрузки скина
 */
$URL_SKINS			= 'http://localhost/auth/skins';


// ==========  СТРОКИ НИЖЕ ЛУЧШЕ ОСТАВИТЬ В ПОКОЕ ========== //
header('Content-Type: text/html; charset=utf-8');
date_default_timezone_set('Europe/Moscow');
if ($DEBUG) { error_reporting(E_ALL); } else { error_reporting(0); unlink($DEBUG_LOG); }

/*
 * Вывод профиля игрока в JSON-формате
 * Функция необходима для скриптов hasJoined и profile
 * Эти необходимо для организации обновления скинов (см. папку assets в клиенте)
 * Подробнее можно почитать тут: https://wiki.vg/Protocol_Encryption#Server
 */
function getProfile($uuid, $username)
{
	$textures = array();
	if (getSkinURL($username, 'skin')) $textures['SKIN'] = array('url' => getSkinURL($username, 'skin'));
	if (getSkinURL($username, 'cape')) $textures['CAPE'] = array('url' => getSkinURL($username, 'cape'));
	
	$property = array
	(
		'timestamp' => time(),
		'profileId' => $uuid,
		'profileName' => $username,
		'textures' => $textures
	);
	
	$profile = array
	(
		'id' => $uuid,
		'name' => $username,
		'properties' => array
		(
			0 => array
			(
				'name' => 'textures',
				'value' => base64_encode(json_encode($property, JSON_UNESCAPED_SLASHES)),
				'signature' => ''
			)
		)
	);
	
	return $profile;
}

/*
 * Возврат URL-ссылки на скин и плащ игрока, либо FALSE, если их нет
 * Функция переносит файлы скинов из папки $PATH_TEXTURES в $PATH_SKINS, меняя имя файла на md5-хеш
 * Эти необходимо для организации обновления скинов (см. папку assets в клиенте)
 */
function getSkinURL($username, $type)
{
	$texture = $GLOBALS['PATH_TEXTURES'] . '/' . $type . '/' . strtolower($username) . '.png';
	if (file_exists($texture))
	{
		$skin = $GLOBALS['PATH_SKINS'] . '/' . md5_file($texture) . '.png';
		if (!file_exists($skin)) copy($texture, $skin);
		return $GLOBALS['URL_SKINS'] . '/' . md5_file($texture) . '.png';
	}
	else return FALSE;
}

/*
 * Запись сообщения в файл отладки $DEBUG_LOG. Формат сообщения:
 * [ДАТА][ВРЕМЯ][ИМЯ_СКРИПТА] ТЕКСТ_СООБЩЕНИЯ
 * Если $DEBUG отключен (FALSE), то ничего не делает
 */
function debug($message)
{
	if ($GLOBALS['DEBUG']) file_put_contents($GLOBALS['DEBUG_LOG'], date('[d.m.y][H:i:s]') . chr(9) . '[' . strtoupper(basename($_SERVER['SCRIPT_NAME'], '.php')) . '] ' . $message . PHP_EOL, FILE_APPEND);
}
?>