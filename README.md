NFK Map Viewer
==============

Генерирует изображение карты из файла `.mapa` игры [Need For Kill](http://needforkill.ru)

Пример работы скрипта: [http://harpywar.com/test/nfkmap/](http://harpywar.com/test/nfkmap/)


### Требования

PHP >= 5.3 с включенными расширениями `php_gd2` и `php_bz2`


### Установка через Composer

Менеджер пакетов [Composer](http://getcomposer.org) упрощает установку сторонних скриптов в вашем проекте. Он сам загрузит необходимые файлы и добавит их в `vendor/autoload.php`, который будет достаточно включить в начале вашего скрипта.

1. Откройте папку, где будет размещен NFK Map Viewer, и создайте в ней файл `composer.json` со следующим содержимым:
    
        {
            "minimum-stability": "dev",
            "require": {
                "nfk/mapviewer":"dev-master"
            }
        }
    
2. Положите в эту папку файл http://getcomposer.org/composer.phar и выполните команду:
    
        php composer.phar install
    
3. После установки пакета должна появиться папка `vendor`, внутри которой, помимо автозагрузчика composer'a, расположены исходники MapViewer.

4. Теперь в начале вашего скрипта достаточно включить загрузочный файл, после чего можно использовать MapViewer:
    
        include "vendor/autoload.php";

		
### Обычная установка

Если вы не используете Composer, то в начале своего скрипта добавьте встроенный автозагрузчик классов:

    include("lib/autoloader.php");
    Autoloader::register();
    

### Примеры использования (более подробно в examples):

Сохранение полноразмерного изображения из существующей карты:

	use NFK\MapViewer\MapViewer;
	
    $nmap = new MapViewer("tourney4.mapa");
	$nmap->LoadMap();
    $im = $nmap->DrawMap();
    imagepng($im, $nmap->GetFileName() . '.png');

![](http://habrastorage.org/storage2/8e8/8ee/88c/8e88ee88cacdc8530ab530b04439a3e5.png)
	
Можно создать свою карту, или изменить существующую:
    
    // хелперы для удобного создания объектов
    use NFK\MapViewer\MapObject\SimpleObject
    use NFK\MapViewer\MapObject\SpecialObject

    $nmap = new MapViewer("test.mapa");
    
    // следующий код заполнит бриками границу карты
    for ($x = 0; $x < $nmap->Header->MapSizeX; $x++)
    	for ($y = 0; $y < $nmap->Header->MapSizeY; $y++)
    		if ($x == 0 || $x == $nmap->Header->MapSizeX - 1 || $y == 0 || $y == $nmap->Header->MapSizeY - 1)
    			$nmap->Bricks[$x][$y] = 228;
    
    // респавн в левом нижнем углу
    $nmap->Bricks[1][$nmap->Header->MapSizeY - 2] = SimpleObject::Respawn();
    
    // установим в правом нижнем углу портал, с телепортом в левый нижний угол
    $obj = SpecialObject::Teleport
    (
    	$nmap->Header->MapSizeX - 2, // x
    	$nmap->Header->MapSizeY - 2, // y
    	2, // goto x
    	$nmap->Header->MapSizeY - 2 // goto y
    ); 
    
    $nmap->Objects[] = $obj; // добавить портал в массив объектов
    
    $nmap->SaveMap();
	
![](http://habrastorage.org/storage2/158/372/863/158372863d1b504365c681a8d1db97ee.png)

Можно извлечь и сохранить карту `.mapa` из демки:

    $nmap = new NFKMap("demo.ndm");
    $nmap->LoadMap();
    
    // хеш содержимого карты
    $filename = md5( $nmap->GetMapBytes() );
    
    $nmap->SaveMap($filename);

	
### Использование памяти

На очень больших картах может потребоваться большое количество памяти для создания изображения.
Поэтому, в скрипте желательно убрать ограничение памяти, или установить его до максимально возможного значения:

    ini_set('memory_limit', '-1');
    ini_set('memory_limit', '256M');

Расход памяти на примере большой карты http://ge.tt/5uyLLIW/v/0
* 0.7 мб до загрузки карты
* 5.7 мб после загрузки карты в память
* 6.7 мб после загрузки ресурсов (картинки палитры и объектов)
* 151 мб после создания слоя карты через `imagecreatetruecolor` размером 7776х3888 px
* 151 мб после рисования всех объектов
* 237 мб после сохранения картинки через `imagepng` 

Замер производился функцией `memory_get_peak_usage`

<br>

### Разбор формата карты

* [Специальные объекты на карте](https://github.com/HarpyWar/nfkmap-viewer/wiki/Специальные-объекты-на-карте)

* [Структура карты NFK MAP](https://github.com/HarpyWar/nfkmap-viewer/wiki/Структура карты NFK MAP)
* [Файл карты в демке NFK DEMO](https://github.com/HarpyWar/nfkmap-viewer/wiki/Структура демки NFK DEMO)
* [BMP картинка палитры](https://github.com/HarpyWar/nfkmap-viewer/wiki/BMP-картинка-палитры)


