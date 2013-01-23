NFK Map Viewer
==============

Генерирует изображение карты из файла `.mapa` игры [Need For Kill](http://needforkill.ru)

Пример работы скрипта: [http://harpywar.com/test/nfkmap/](http://harpywar.com/test/nfkmap/)


### Требования

PHP >= 5.3 с включенными расширениями `php_gd2` и `php_bz2`


### Примеры использования (более подробно в examples):

Сохранение полноразмерного изображения из существующей карты:

    require_once("nfkmap.class.php");
	use NFK\MapViewer;
	
    $nmap = new MapViewer("tourney4.mapa");
	$nmap->LoadMap();
    $im = $nmap->DrawMap();
    imagepng($im, $nmap->GetFileName() . '.png');

![](http://habrastorage.org/storage2/9da/b58/0f1/9dab580f1202e3049eec694522530da2.png)
	
Можно создать свою карту, или изменить существующую:
    
    // хелпер для удобного создания объектов и более понятного кода
    require_once("mapobj.class.php");

    $nmap = new MapViewer("test.mapa");
    
    // следующий код заполнит бриками границу карты
    for ($x = 0; $x < $nmap->Header->MapSizeX; $x++)
    	for ($y = 0; $y < $nmap->Header->MapSizeY; $y++)
    		if ($x == 0 || $x == $nmap->Header->MapSizeX - 1 || $y == 0 || $y == $nmap->Header->MapSizeY - 1)
    			$nmap->Bricks[$x][$y] = 228;
    
    // респавн в левом нижнем углу
    $nmap->Bricks[1][$nmap->Header->MapSizeY - 2] = NFK\MapViewer\SimpleObject::Respawn();
    
    // установим в правом нижнем углу портал, с телепортом в левый нижний угол
    $obj = NFK\MapViewer\SpecialObject::Teleport
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


### Использования памяти

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


