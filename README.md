NFK Map Viewer
==============

Генерирует изображение карты из файлов `.mapa` и `.ndm` игры [Need For Kill](http://needforkill.ru)

Пример работы скрипта: [http://harpywar.com/test/nfkmap/](http://harpywar.com/test/nfkmap/)


### Требования

PHP >= 5.3 с включенными расширениями `php_gd2` и `php_bz2`


### Примеры использования (более подробно в example.php):

Сохранение полноразмерной картинки из существующей карты:

    require_once("nfkmap.class.php");
	
    $nmap = new NFKMap("tourney4.mapa");
	$nmap->LoadMap();
    $nmap->DrawMap();
    $nmap->SaveMapImage();

![](http://habrastorage.org/storage2/9da/b58/0f1/9dab580f1202e3049eec694522530da2.png)
	
Можно создать свою карту, или изменить существующую:
    
    // хелпер для удобного создания объектов и более читаемого кода
    require_once("mapobj.class.php");
    
    $nmap = new NFKMap("test.mapa");
    
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
    
    // удалить из названия карты неразрешенные символы в имени файла
    $bad = array_merge( array_map('chr', range(0,31)), array("<", ">", ":", '"', "/", "\\", "|", "?", "*"));
    $filename = str_replace($bad, '', $this->Header->MapName);
    
    // хеш содержимого карты
    #$hash = md5( $nmap->GetMapStream()() );
    
    $nmap->SaveMap($filename);


<br>
### Разбор формата карты

* [Специальные объекты на карте](https://github.com/HarpyWar/nfkmap-viewer/wiki/Специальные-объекты-на-карте)

* [Структура карты NFK MAP](https://github.com/HarpyWar/nfkmap-viewer/wiki/Структура карты NFK MAP)
* [Файл карты в демке NFK DEMO](https://github.com/HarpyWar/nfkmap-viewer/wiki/Структура демки NFK DEMO)
* [BMP картинка палитры](https://github.com/HarpyWar/nfkmap-viewer/wiki/BMP-картинка-палитры)


