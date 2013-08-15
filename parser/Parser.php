<?php
namespace parser;

class Parser {

	public $em; // entity manager
	public $uof; // unit of work

	public function __construct() {
		global $em;
		$this->em = $em;
		// $this->conn = $doctrine->conn;
		$this->uof = $this->em->getUnitOfWork();
	}

	function checkpoint($line = 0, $message = 'checkpoint stop') {
		$f = parse_ini_file('options'.DIRECTORY_SEPARATOR.'checkpoint.ini');
		if ( $f['die'] == 1 ) {
			$this->myLog($line, $message, true);
		}
	}

	function myLog($line = 0, $message = 'defalt message', $terminate=false) {
	    $file = fopen('logs'.DIRECTORY_SEPARATOR.'log.txt', 'a+');
	    $dtime = new \DateTime();
	    $dtime = $dtime->format("Y-m-d H:i:s");
	    $line = sprintf("%1$05d", $line);
	    fwrite($file, "$dtime | line: $line | message: $message\r\n");
	    fclose($file);
	    if ($terminate) {
	        exit("exit on line $line");
	    }
	}

    public function cp1251_utf8( $sInput ) {
        $sOutput = "";
        for ( $i = 0; $i < strlen( $sInput ); $i++ ) {
            $iAscii = ord( $sInput[$i] );
            if ( $iAscii >= 192 && $iAscii <= 255 )
                $sOutput .=  "&#".( 1040 + ( $iAscii - 192 ) ).";";
            else if ( $iAscii == 168 )
                $sOutput .= "&#".( 1025 ).";";
            else if ( $iAscii == 184 )
                $sOutput .= "&#".( 1105 ).";";
            else
                $sOutput .= $sInput[$i];
        }
        return $sOutput;
    }
    
    public function utf8_to_cp1251($s) {
    	$out = null;
		if ((mb_detect_encoding($s,'UTF-8,CP1251')) == "UTF-8") 
		{ 
		for ($c=0;$c<strlen($s);$c++) 
		  { 
		  $i=ord($s[$c]); 
		  if ($i<=127) $out.=$s[$c]; 
		  if (isset($byte2) && $byte2) 
		    { 
		    $new_c2=($c1&3)*64+($i&63); 
		    $new_c1=($c1>>2)&5; 
		    $new_i=$new_c1*256+$new_c2; 
		    if ($new_i==1025) 
		      { 
		      $out_i=168; 
		      } else { 
		      if ($new_i==1105) 
		        { 
		        $out_i=184; 
		        } else { 
		        $out_i=$new_i-848; 
		        } 
		      } 
		    $out.=chr($out_i); 
		    $byte2=false; 
		    } 
		    if (($i>>5)==6) 
		      { 
		      $c1=$i; 
		      $byte2=true; 
		      } 
		  } 
		return $out; 
		} 
		else 
		{ 
		return $s; 
		} 
	} 

	/**
	 * Copy remote file over HTTP one small chunk at a time.
	 *
	 * @param $infile The full URL to the remote file
	 * @param $outfile The path where to save the file
	 */
	function copyfile_chunked($infile, $outfile) {
	    $chunksize = 10 * (1024 * 1024); // 10 Megs

	    /**
	     * parse_url breaks a part a URL into it's parts, i.e. host, path,
	     * query string, etc.
	     */
	    $parts = parse_url($infile);
	    $i_handle = fsockopen($parts['host'], 80, $errstr, $errcode, 5);
	    $o_handle = fopen($outfile, 'wb');

	    if ($i_handle == false || $o_handle == false) {
	        return false;
	    }

	    if (!empty($parts['query'])) {
	        $parts['path'] .= '?' . $parts['query'];
	    }

	    /**
	     * Send the request to the server for the file
	     */
	    $request = "GET {$parts['path']} HTTP/1.1\r\n";
	    $request .= "Host: {$parts['host']}\r\n";
	    $request .= "User-Agent: Mozilla/5.0\r\n";
	    $request .= "Keep-Alive: 115\r\n";
	    $request .= "Connection: keep-alive\r\n\r\n";
	    fwrite($i_handle, $request);

	    /**
	     * Now read the headers from the remote server. We'll need
	     * to get the content length.
	     */
	    $headers = array();
	    while(!feof($i_handle)) {
	        $line = fgets($i_handle);
	        if ($line == "\r\n") break;
	        $headers[] = $line;
	    }

	    /**
	     * Look for the Content-Length header, and get the size
	     * of the remote file.
	     */
	    $length = 0;
	    foreach($headers as $header) {
	        if (stripos($header, 'Content-Length:') === 0) {
	            $length = (int)str_replace('Content-Length: ', '', $header);
	            break;
	        }
	    }

	    /**
	     * Start reading in the remote file, and writing it to the
	     * local file one chunk at a time.
	     */
	    $cnt = 0;
	    while(!feof($i_handle)) {
	        $buf = '';
	        $buf = fread($i_handle, $chunksize);
	        $bytes = fwrite($o_handle, $buf);
	        if ($bytes == false) {
	            return false;
	        }
	        $cnt += $bytes;

	        /**
	         * We're done reading when we've reached the conent length
	         */
	        if ($cnt >= $length) break;
	    }

	    fclose($i_handle);
	    fclose($o_handle);
	    return $cnt;
	}
}