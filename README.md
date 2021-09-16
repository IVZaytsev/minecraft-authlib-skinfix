# Minecraft Authlib SkinFix
**Authlib SkinFix** - пропатченная библиотека **authlib** от Mojang, которая отвечает за авторизацию между клиентом игры и сервером. Благодаря небольшим изменениям, можно использовать для своих нужд официальную систему авторизации клиентов Minecraft с работающими скинами игроков и плащей. Подробнее о системе авторизации Yggdrasil можно почитать в [**здесь**](https://forum.mcmodding.ru/threads/gajd-php-sql-avtorizacija-yggdrasil-na-domashnem-servere.6174/) либо [**на вики**](https://wiki.vg/Authentication).

## Описание

Уже давно Mojang ввела цифровую подпись каждого скина, который находится на их серверах. Если отдать клиенту игры текстуру игрока без цифровой подписи, он применит стандартную текстуру Стива, поэтому у многих начиная с версии 1.8 скины просто так работать не захотели. Тем более, в новых версиях библиотеки authlib существует проверка на домен, с которого эта информация поступает на клиент. Обычно эту проблему решал аддон Skin Restorer. В пропатченной библиотеке удалены все проверки, позволяя грузить ваши скины откуда угодно. Начинают работать скины игрока, плащей, а также отображаются лица в списке игроков.

## Какие изменения сделаны в оригинальной библиотеке:
- [Patch] Убрана проверка цифровой подписи скинов и плащей;
- [Patch] Убрана проверка домена, с которого клиент получает скины;
- [Patch] Ссылки на скрипты авторизации серверов Mojang заменены ссылками на localhost (127.0.0.1);
- [Patch] Подавлен вывод ошибки подключения к серверу социального взаимодействия (для 1.16.5 и выше);
- [Misc] Добавлен вывод в лог сообщения *SkinFix by TaoGunner* (по сообщению можно определить, загрузилась ли библиотека).

## Для успешной работы требуется:
- Minecraft версии 1.7.10 и выше;
- Базовое понимание системы авторизации Yggdrasil (ссылки выше);
- Базовое знание PHP и MySQL;
- Умение работать с JSON.

## Как пользоваться:
1. Найти в своём клиенте игры библиотеку authlib . В зависимости от версии клиента это могут быть разные версии библиотек:
   - <details>
    <summary>Таблица версий Minecraft и их библиотек authlib</summary>
        | Minecraft | authlib            |
        |-----------|--------------------|
        | 1.17.1    | authlib-2.3.31.jar |
        | 1.16.5    | authlib-2.1.28.jar |
        | 1.12.2    | authlib-1.5.25.jar |
        | 1.10.2    | authlib-1.5.22.jar |
        | 1.8.9     | authlib-1.5.21.jar |
        | 1.7.10    | authlib-1.5.21.jar |
  </details>
2. Определившись, запоминаем её и ищем подходящую исправленную библиотеку **authlib-x.x.xx_skinfix.jar**. Открываем в этой библиотеке **YggdrasilMinecraftSessionService.class** в [InClassTranslator](https://series40.kiev.ua/f_pc/9933-in-class-translator-v.1.1.html)'е (или другом редакторе) видим вот такие строки:
```
	http://localhost/auth/join.php
	http://localhost/auth/hasJoined.php
	http://localhost/auth/profile.php?uuid=
```
3. Меняем пути до скриптов на те, которые нужны вам, например:
```
	http://192.168.100.10/www/join.php
				либо
	http://moy-server-minecraft.net/script/j.php
```
4. Сохраняем и закачиваем измененный класс **YggdrasilMinecraftSessionService.class** обратно в библиотеку. Теперь необходимо поместить её **и в клиент, и на сервер**:
    - КЛИЕНТ: удаляем оригинальную библиотеку и заменяем её на нашу (обычно она лежит по пути libraries/com/mojang/authlib);
    - СЕРВЕР: классы библиотеки находятся внутри **minecraft_server.1.xx.x.jar** (внутри jar ищите com/mojang/authlib). Открываем jar-файл пропатченной библиотеки и заменяем файлы сервера. <ins>Внимание! Некоторые сервера (например Forge) не используют minecraft_server.1.xx.x.jar, ищите где лежит authlib внимательнее)</ins>
5. Теперь остается лишь настроить PHP-скрипты для обработки запросов клиента и сервера. Примеры скриптов достаточно подробно снабжены комментариями.
   - <details>
    <summary>Краткое описание PHP-скриптов</summary>
      - [config.php] Основной файл с настройками, всегда начинайте с него! Тут указываем параметры подключения к MySQL, а также пути до скинов. Один из важных параметров на время настройки это $DEBUG. Поменяйте его значение на TRUE и у вас будет создан файл debug.log, в котором можно узнать, как отрабатывает тот или иной скрипт.
      - [join.php] Обрабатывает POST-запросы клиента игры (в формате JSON) при подключении к серверу.
      - [hasJoined.php] Обрабатывает GET-запросы сервера игры, когда к нему пытаются подключиться.
      - [profile.php] Отвечает за выдачу скинов и плащей.
      - [launcher.php] Этот скрипт не для официальной системы авторизации Yggdrasil и нужен лишь для примера обвязки с каким-нибудь "абстрактным" лаунчером. Выдает параметры запуска для клиента игры: UUID и accessToken.
  </details>
