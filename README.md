NFK Map Viewer
==============

Генерирует изображение карты из .mapa файла игры [Need For Kill](http://needforkill.ru)

Пример работы скрипта: [http://harpywar.com/test/nfkmap/](http://harpywar.com/test/nfkmap/)


### Примеры использования (более подробно в example.php):

Сохранение полноразмерной картинки из существующей карты:

    require_once("nfkmap.class.php");
	
    $nmap = new NFKMap("tourney4.mapa");
	$nmap->LoadMap();
    $nmap->DrawMap();
    $nmap->SaveMapImage();

![](http://habrastorage.org/storage2/9da/b58/0f1/9dab580f1202e3049eec694522530da2.png)
	
Можно создать свою карту, или изменить существующую:

    $nmap = new NFKMap("test.mapa");
    
    // следующий код заполнит бриками границу карты
    for ($x = 0; $x < $nmap->Header->MapSizeX; $x++)
    	for ($y = 0; $y < $nmap->Header->MapSizeY; $y++)
    		if ($x == 0 || $x == $nmap->Header->MapSizeX - 1 || $y == 0 || $y == $nmap->Header->MapSizeY - 1)
    			$nmap->Bricks[$x][$y] = 228;
    
    // респавн в левом нижнем углу
    $nmap->Bricks[1][$nmap->Header->MapSizeY - 2] = 34;
    
    // установим в правом нижнем углу портал, с телепортом в левый нижний угол
    $obj = new TMapObj();
    $obj->active = 1; // всегда 1
    $obj->x = $nmap->Header->MapSizeX - 2; // x
    $obj->y = $nmap->Header->MapSizeY - 2; // y
    $obj->length = 2; // goto x
    $obj->dir = $nmap->Header->MapSizeY - 2; // goto y
    $obj->objtype = 1; // 1 = портал
    
    $nmap->Objects[] = $obj; // добавить портал в массив объектов
    
    $nmap->SaveMap();
	
![](http://habrastorage.org/storage2/158/372/863/158372863d1b504365c681a8d1db97ee.png)
	
<br>

Структура карты NFK MAP
----------

1. **Header карты**
 
 <pre>
 	type THeader = record   
 			  ID : Array[1..4] of Char;						// 4 байта
 			  Version : byte;								// 1 байт
 			  MapName      : string[70];					// 70 байт + 1 байт перед строкой 0x03(ignore)
 			  Author : string[70];							// 70 байт + 1 байт перед строкой 0x03(ignore)
 			  MapSizeX,MapSizeY,BG,GAMETYPE,numobj : byte;	// каждый по 1 байту
 			  numlights : word;								// 2 байта
 	end;
 </pre>
 
2. **Массив "бриков"**. Поочередно, по одному байту считываются по колонкам слева направо:

 <pre>
 	bbb : array [0..254,0..254] of TBrick; // массив бриков (карта)

 	for y := 0 to header.MapSizeY - 1 do begin
 		f.read(buf,header.MapSizeX);
 		for x := 0 to header.MapSizeX - 1 do
 				bbb[y,x].image := buf[y];
 	end;
 </pre>
 
 Каждый брик является индексом картинки из палитры. 
 Брики 0-53 заняты под предметы (оружие, флаги, и т.п).
 Брики 54-254 зарезервированы под палитру, встроенную в игру. 
 Если задана пользовательская палитра (в файл карты добавлена картинка), то она использует под себя брики с номерами 54-181
 
 Ширина палитры из карты имеет нефиксированную ширину. Например, в tourney7 её ширина 3 брика.
 Но ширина палитры не может быть больше 8 бриков. Каждый брик имеет размер 32x16 пикселей.
 
3. **Специальные объекты** (телепорт и т.п.)
 	 
 Считываются поочередно в массив типа TMAPOBJ:
 (байты выравнивания появляются из-за того, что тип record, а не packed record)
 	 
 <pre>
 	ddd : array[0..255] of TMAPOBJV2;       // массив специальных объектов

 	type TMAPOBJ2 = record
 		active : boolean; // 1 байт + 1 байт выравнивания(ignore)
 		x,y,lenght,dir,wait : word; // каждый по 2 байта
 		targetname,target,orient,nowanim,special : word; // каждый по 2 байта
 		objtype : byte; // 1 байт + 1 байт выравнивания(ignore)
 	end;
 
 
 	for a := 0 to header.numobj-1 do
 	   f.read(ddd[a],sizeof(ddd[a]));
 </pre>
 
4. **Далее до конца файла идет палитра (она одна) и массив локаций**, имеющие тип TMapEntry. Они могут присутствовать, а могут и нет (на dm2 вообще ничего нету)/
 
 <pre>
 	type TMapEntry = packed record
 		EntryType : string[3]; // 3 байта + 1 байт перед строкой 0x03(ignore)
 		DataSize : longint; // 4 байта
 		Reserved1 : byte; // 1 байт
 		Reserved2 : word; // 2 байта
 		Reserved3 : integer; // 4 байта
 		Reserved4 : longint; // 4 байта
 		Reserved5 : cardinal; // 4 байта
 		Reserved6 : boolean; // 1 байт (значение 0 или 1)
 	end;
 </pre>
 
 Наличие палитры определяется следующими тремя байтами (EntryType), которые должны быть равны "pal". Если палитра существует, то она считывается в структуру TMapEntry.
 
 * Reserved5 - флаг, означающий прозрачность фона палитры (например, можно делать овальный брик)
 * Reserved6 - цвет фона, который должен быть прозрачным
 * DataSize - размер байтов, которые необходимо считать далее
 
 Сразу после палитры находится сама картинка, запакованная BZip - необходимо считать все байты размером DataSize, затем распаковать данные.
    
 	
5. **После этого может находиться массив локаций**/
 
 Сперва считывается TMapEntry, и если  EntryType = loc, то далее считывается TLocationText.
 
 <pre>
 	Type TLocationText = Packed Record
 		Enabled : boolean; // 1 байт
 		X, Y : byte; // по 1 байту
 		Text : String [64]; // 64 байта + 1 байт перед строкой 0x0F(ignore)
 	end;
 </pre>
 	
<br>
-

Считывать палитру и локации удобно проверяя через while не настал ли конец файла. По ходу смотря чему равен EntryType (pal или loc).


    while F.Position < f.size do begin
    	f.read(entry,sizeof(entry));
    	if entry.EntryType = 'pal' then begin // reading pal
    			CUSTOMPALITRETRANSPARENTCOLOR := Entry.Reserved5;
    			CUSTOMPALITRETRANSPARENT := Entry.Reserved6;
    			CUSTOMPALITRE := TRUE;
    
    			decompstr := TMemoryStream.Create;
    			decompstr.clear;
    			PaletteStream.Clear;
    			decompstr.CopyFrom (F, Entry.Datasize);
    			decompstr.position := 0;
    			ProgressCallback := nil;
    			BZDecompress(decompstr,PaletteStream,ProgressCallback);
    			palettestream.Position := 0;
    			decompstr.free;
    
    			...
    	end
    	else if entry.EntryType = 'loc' then begin // reading location table.
    			For a := 1 to Entry.DataSize div Sizeof(TLocationText) do
    					f.Read (LocationsArray[a],sizeof(TLocationText));
    	end
    	else f.position := f.position + Entry.DataSize;
    end;


P.S. Вышеприведенный код взят из [исходников NFK Radiant](https://bitbucket.org/pqr/nfk-r2/src/37dd3fe7e9f8ec819d68baa9d595f049ff82de57/EDITOR/radiant040/Unit1.pas) (редактор карт)

Так же, Radiant поможет при проверке на соответствие всех значений и объектов на карте.

Извлечь брики и другие изображения можно из файлов игры `/basenfk/system/graph.d` и `graph2.d`, при помощи утилиты [VTDTool.exe](http://needforkill.ru/load/12-1-0-184)



BMP картинка палитры
----------
В некоторых картах встречается некорректная bmp палитра,. Она открывается через Radiant, но при экспорте картинка не откроется ни одним редактором изображений, либо будет показано смещенное изображение с неправильными цветами.

Изучая [формат BMP](http://www.xbdev.net/image_formats/bmp/index.php) были найдены следующие моменты (на примере карты tourney0):

* По смещению `0x02` неходится размер всего файла в байтах (`0x01D636`)
* По смещению `0x0A` неходится количество байтов от начала файла до начала самой картинки (`0x0836`), это заголовок (54 байта) + мусор (или это спец. данные)
* По смещению `0x0E` находится размер BitmapInfoHeader. Если он равен 12 байтам, то ничего делать не нужно - это файл формата [BMP Version 2](http://www.fileformat.info/format/bmp/egff.htm). Там pyfxtybz Width и Height имеют тип Short и занимают по 2 байта, а не по 4 (палитра в таком формате есть в карте castle-ctf)
* По смещению `0x22` находится количество байтов, которое занимает только сама картинка в этом файле (`0x01CE00`)

Фишка в том, что в этой картинке числа по смещению `0x02` и `0x0A` почему-то неверные! Первое должно быть = `0x01D236` (размер файла), а второе `0x01D236 - 0x01CE00 = 0x0436`. Delphi их игнорирует, поэтому и показывает картинку нормально.

Чтобы исправить эту картинку, необходимо поменять 8 на 4 по смещению `0X0B`, и она станет нормально открываться. По хорошему, лучше заменить ещё и неправильный размер файла вначале.

![](http://habrastorage.org/storage2/0cf/794/ddf/0cf794ddf4865641be86c9cb09c870f5.png)


