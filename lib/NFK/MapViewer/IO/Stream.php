<?php

/*
 * This file is part of NFK Map Viewer.
 *
 * (c) 2013 HarpyWar
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NFK\MapViewer\IO;

/**
 * Stream in memory
 *
 * @package mapviewer
 * @author  HarpyWar <harpywar@gmail.com>
 */
class Stream
{
	private $handle;
	private $stream;
	private $is_stream_modified = false;
	
	public function __construct()
    {
        $a = func_get_args();
        $i = func_num_args();
        if (method_exists($this, $f='__construct' . $i)) {
            call_user_func_array(array($this, $f), $a);
        }
    }

    private function __construct0()
    {
		// create inmemory stream
		$this->handle = fopen("php://memory", 'r+');
		$this->stream = '';
    }

    private function __construct1($data)
    {
		$this->__construct0();
		
		// write data to stream and rewind position to start
		fputs($this->handle, $data);
		$this->rewind();
		
		$this->stream = $data;
    }
	
	
	// return stream content
	public function stream()
	{
		if ($this->is_stream_modified)
		{
			$this->rewind();
			$this->stream = stream_get_contents($this->handle);

			$this->is_stream_modified = false;
		}
		return $this->stream;
	}
	
	// return current position
	public function pos()
	{
		return ftell($this->handle);
	}
	
	// return current position
	public function rewind()
	{
		rewind($this->handle);
	}

	// read bytes from current position to specified length
	public function read($length)
	{
		// decrease length if end it > end of stream
		if ( $this->pos() >= strlen($this->stream()) )
			return false;
			
		fseek( $this->handle, $this->pos() );
		
		$data = fread($this->handle, $length);

		return $data;
	}

	// write bytes to stream
	public function write($data)
	{
		if ($data)
			$this->is_stream_modified = true;

		fseek( $this->handle, $this->pos() );

		// add readed bytes to stream
		fwrite($this->handle, $data);
	}
	
	public function __destruct()
	{
		if ($this->handle)
			fclose($this->handle);
	}
}