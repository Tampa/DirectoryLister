<?php

    // Include the DirectoryLister class
    require_once('resources/DirectoryLister.php');

    // Initialize the DirectoryLister object
    $lister = new DirectoryLister();

    // Restrict access to current directory
    ini_set('open_basedir', getcwd());
	
	// Appearantly using readfile(); can cause problems. Large files, which exceeds PHP's memory_limit, are most likely to fail.
	// Chunking the readfile solves this problem.
	// Credits to Rob Funk - http://www.php.net/manual/en/function.readfile.php#48683
	
	function readfile_chunked ($fname) 
	{ 
		$chunksize = 1*(1024*1024); // how many bytes per chunk 
		$buffer = ''; 
		$handle = fopen($fname, 'rb'); 
		if ($handle === false) { 
			return false; 
		} 
		while (!feof($handle)) { 
			$buffer = fread($handle, $chunksize); 
			print $buffer; 
		} 
		return fclose($handle); 
	}

	
	function getFileExt($fname)
	{
		return explode('.', $fname)[1];
	}

    // Return file hash
    if (isset($_GET['hash']))
	{

        // Get file hash array and JSON encode it
        $hashes = $lister->getFileHash($_GET['hash']);
        $data   = json_encode($hashes);

        // Return the data
        die($data);

    }

    if (isset($_GET['zip']))
	{

        $dirArray = $lister->zipDirectory($_GET['zip']);

    }
	else if(isset($_GET['file']))
	{
	
		$path = __DIR__;
		// Get name of file to be downloaded	
		$fname = $_GET['file'];
		//Check for various invalid files, and loop holes like ../ and ./
		if($fname == '.' || $fname == './' || !file_exists($fname) || empty($fname) || preg_match('/\..\/|\.\/\.|resources/',$fname))
		{
			echo "Invalid File or File Not Specified";
			exit(0);
		}
		else
		{
			
			// Init temporary array to handle data
			$downloads = array();
			
			// Now it should exist regardless
			if (file_exists("$path/resources/log") !== false)
			{
				$file = fopen("$path/resources/log","r");
				
				// Get file contents into array
				$downloads = unserialize(fread($file,filesize("$path/resources/log")));
				
				fclose($file);
				
				if ($downloads != null && $downloads != "" && $downloads != " " && count($downloads) != 0)
				{
					// Check if the key or filename already is in the array else append it
					if (array_key_exists($fname, $downloads))
					{
						$downloads[$fname] += 1;
					}
					else
					{
						$downloads[$fname] = 1;
					}
					
					// Massive debug code to figure out why this constantly overwrites with bad data
					
					$debugfile = "$path/resources/log".time();
					
					//touch($debugfile);
					//$file = fopen($debugfile,"w");
					//fwrite($file,serialize($downloads));
					//fclose($file);
					
					
					try
					{
						$file = fopen("$path/resources/log","w");
						
					}
					catch (Exception $e)
					{
						$file = fopen("$path/resources/logerror","w");
						fwrite($file,$e->getMessage()."\n");
						fclose($file);
					}
					
					try
					{
						fwrite($file,serialize($downloads));
					}
					catch (Exception $e)
					{
						$file = fopen("$path/resources/logerror","w");
						fwrite($file,$e->getMessage()."\n");
						fclose($file);
					}
					
					fclose($file);
				
				}
			}

		
			// Initiate force file download
			// fix for IE catching or PHP bug issue
			@header("Pragma: public");
			@header("Expires: 0"); // set expiration time
			@header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			// browser must download file from server instead of cache
		
			// force download dialog
			@header("Content-Type: application/force-download");
			@header("Content-Type: application/octet-stream");
			@header("Content-Type: application/download");
		
			// use the Content-Disposition header to supply a recommended filename and
			// force the browser to display the save dialog.
			
			@header("Content-Disposition: attachment; filename=\"".basename($fname)."\";" );
		
			/*
			The Content-transfer-encoding header should be binary, since the file will be read
			directly from the disk and the raw bytes passed to the downloading computer.
			The Content-length header is useful to set for downloads. The browser will be able to
			show a progress meter as a file downloads. The content-lenght can be determines by
			filesize function returns the size of a file.
			*/
			@header("Content-Transfer-Encoding: binary");
			@header("Content-Length: ".filesize($fname));
			@readfile_chunked($fname);
		}

	} 
	else
	{

        // Initialize the directory array
        if (isset($_GET['dir'])) {
            if(isset($_GET['by'])){
                if(isset($_GET['order'])){
                    $dirArray = $lister->listDirectory($_GET['dir'],$_GET['by'],$_GET['order']);
                } else {
                    $dirArray = $lister->listDirectory($_GET['dir'],$_GET['by'],'asc');
                }
            } else {
                $dirArray = $lister->listDirectory($_GET['dir'],'name', 'asc');
            }
        } else {
            if(isset($_GET['by'])){
                if(isset($_GET['order'])){
                    $dirArray = $lister->listDirectory('.',$_GET['by'],$_GET['order']);
                } else {
                    $dirArray = $lister->listDirectory('.',$_GET['by'],'asc');
                }
            } else {
                $dirArray = $lister->listDirectory('.','name', 'asc');
            }
        }

        // Define theme path
        if (!defined('THEMEPATH')) {
            define('THEMEPATH', $lister->getThemePath());
        }

        // Set path to theme index
        $themeIndex = $lister->getThemePath(true) . '/index.php';

        // Initialize the theme
        if (file_exists($themeIndex)) {
            include($themeIndex);
        } else {
            die('ERROR: Failed to initialize theme');
        }

    }
	
?>