= NFK Map Viewer =

Генерирует изображение карты из .mapa файла игры Need For Kill (http://needforkill.ru)

Пример работы:
http://harpywar.com/test/nfkmap/








= Структура карты NFK MAP =

1) header карты
	type THeader = record   
			  ID : Array[1..4] of Char;						// 4 байта
			  Version : byte;								// 1 байт
			  MapName      : string[70];					// 70 байт + 1 байт перед строкой 0x03(ignore)
			  Author : string[70];							// 70 байт + 1 байт перед строкой 0x03(ignore)
			  MapSizeX,MapSizeY,BG,GAMETYPE,numobj : byte;	// каждый по 1 байту
			  numlights : word;								// 2 байта
	end;

2) Массив "бриков". Поочередно, по одному байту считываются по колонкам слева направо:

	bbb : array [0..254,0..254] of TBrick; // массив бриков (карта)
	 
	for y := 0 to header.MapSizeY - 1 do begin
		f.read(buf,header.MapSizeX);
		for x := 0 to header.MapSizeX - 1 do
				bbb[y,x].image := buf[y];
	end;

	Каждый брик является индексом картинки из палитры. 
	Брики 0-53 заняты под предметы (оружие, флаги, и т.п).
	Брики 54-254 зарезервированы под палитру, встроенную в игру. 
	Если задана пользовательская палитра (в файл карты добавлена картинка), то она использует под себя брики с номерами 54-181

	Ширина палитры из карты имеет нефиксированную ширину. Например, в tourney7 её ширина 3 брика.
	Но ширина палитры не может быть больше 8 бриков. Каждый брик имеет размер 32x16 пикселей.
	


3) Специальные объекты (телепорт и т.п.)

	Считываются поочередно в массив типа TMAPOBJ:
	(байты выравнивания появляются из-за того, что тип record, а не packed record)

	ddd : array[0..255] of TMAPOBJV2;       // массив специальных объектов
	  
	type TMAPOBJ2 = record
        active : boolean; // 1 байт + 1 байт выравнивания(ignore)
        x,y,lenght,dir,wait : word; // каждый по 2 байта
        targetname,target,orient,nowanim,special : word; // каждый по 2 байта
		objtype : byte; // 1 байт + 1 байт выравнивания(ignore)
    end;


	for a := 0 to header.numobj-1 do
	   f.read(ddd[a],sizeof(ddd[a]));
	
4) Далее до конца файла идет палитра (она одна) и массив локаций, имеющие тип TMapEntry. Они могут присутствовать, а могут и нет (на dm2 вообще ничего нету).

	type TMapEntry = packed record
			EntryType : string[3]; // 3 байта + 1 байт перед строкой 0x03(ignore)
			DataSize : longint; // 4 байта
			Reserved1 : byte; // 1 байт
			Reserved2 : word; // 2 байта
			Reserved3 : integer; // 2 байта
			Reserved4 : longint; // 4 байта
			Reserved5 : cardinal; // 4 байта
			Reserved6 : boolean; // 1 байт (значение 0 или 1)
	end;
	   
	Наличие палитры определяется следующими тремя байтами (EntryType), которые должны быть равны "pal". Если палитра существует, то она считывается в структуру TMapEntry.
		Reserved5 - флаг, означающий прозрачность фона палитры (например, можно делать овальный брик)
		Reserved6 - цвет фона, который должен быть прозрачным
	
		DataSize - размер байтов, которые необходимо считать далее
		
	Сразу после палитры находится сама картинка, запакованния с помощью GZ - необходимо считать все байты размером DataSize.
	   
	
5)	После этого может находиться массив локаций. 

	Сперва считывается TMapEntry, и если  EntryType = loc, то далее считывается TLocationText.
	
	Type TLocationText = Packed Record
		Enabled : boolean; // 1 байт
		X, Y : byte; // по 1 байту
		Text : String [64]; // 64 байта + 1 байт перед строкой 0x0F(ignore)
	end;
	
	
	
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
	   
	   
	   
P.S. Вышеприведенный код взят из исходников NFK Radiant (редактор карт) https://bitbucket.org/pqr/nfk-r2/src/37dd3fe7e9f8ec819d68baa9d595f049ff82de57/EDITOR/radiant040/Unit1.pas


--
HarpyWar (harpywar@gmail.com)
http://harpywar.com


	   