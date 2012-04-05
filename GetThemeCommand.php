<?php
//
// This class will help you to download and install themes
// for your Yii powered app from the http://yiithemes.mehesz.net site
//

class GetThemeCommand extends CConsoleCommand
{
	// var to store the themes path
	public $themespath;

	public $noinstall = false;

	public $available_options = array(
				'--tp', '--themespath',
				'--ni', '--noinstall'
			);

	public $options = array();
	public $theme_ids = array();
	public $themes_url = 'http://yiithemes.mehesz.net/';

	public function __construct( $name, $runner )
	{
		// we are guessing the themes path (assuming classic Yii setup)
		$app_path = YiiBase::getPathOfAlias( 'application' );
		$this->themespath = realpath( $app_path . '/../themes/' );

		return parent::__construct( $name, $runner );
	}

	public function run( $args )
	{
		// no arguments? you don't know
		// what you are doing ... HELP!
		if( empty( $args ) )
		{
			echo $this->getHelp();
			return;
		}

		$this->parseOptions( $args );
		$this->parseThemeIds( $args );

		// at this point we should have "valid" IDs and useful options
		// check if themes folder is writable
		if( ! is_dir( $this->themespath ) || ! is_writable( $this->themespath  ) )
		{
			$this->stopExec( 'Themes folder does not exist or is not writable. Exiting.' );
		}

		// let's check if we can access the site ...
		echo "Accessing Yii Themes site ... ";
		if( ! $this->_checkUrl( $this->themes_url ) )
		{
			$this->stopExec( 'Failed!' );
		}
		else
		{
			echo "OK!\r\n";
		}

		// grab the IDs, here we go
		foreach( $this->theme_ids as $tid )
		{
			echo 'Attempting to download theme #' . $tid . ' ';
			// TODO can we do this with CURL?
			$success = $this->_downloadFile( $tid, !$this->noinstall );

			if( $success )
			{
				echo "OK!\r\n";
			}
			else
			{
				echo "Failed! Skipping!\r\n";
			}
		}
	}

	public function getHelp()
	{
		return <<<EOD
USAGE
  gettheme [--options] theme_id[ theme_id2 theme_id3 ... etc]

DESCRIPTION
  use this command line tool to download and install theme(s) 
  fron the Yii Themes site for your Yii powered application.

OPTIONS
  --tp, --themespath
    set the themes folder where the themes will be downloaded 
    and installed (default is {$this->themespath} - use full path! )

  --ni, --noinstall
    script will only download the theme(s) and will not install 
	(default is `false`, meaning it automatically installs the theme(s))

EXAMPLES
  To download and install themes just execute
  ./yiic gettheme 1 2 3

  To only download themes
  ./yiic gettheme --ni 1 2 3

EOD;
	}
	
	// parsing options given through arguments
	public function parseOptions( $args )
	{
		// at this point we should have args, but just in case ...
		if( ! empty( $args ) )
		{
			foreach( $args as $arg )
			{
				// if the argument starts with -- we have an option
				if( substr( $arg,0,2 ) == '--' )
				{
					// we only care about the first occurance of the =
					$arg_val = explode( '=', $arg, 2 );
					$option = $arg_val[0];
					$value = isset( $arg_val[1] ) ? $arg_val[1] : '';

					if( ! in_array( $option, $this->available_options ) )
					{
						$this->stopExec( "Oops! Unkown option: " . $option );
					}
					else
					{
						$this->options[$option] = $value;
					}
				}
			}

			// if we have options, we parse them accordingly
			if( ! empty( $this->options ) )
			{
				// o - option
				// v - value
				foreach( $this->options as $o => $v )
				{
					switch( $o )
					{
						case '--tp':
						case '--themespath':
									$this->themespath = $v;
								break;

						case '--ni':
						case '--noinstall':
									// it's true ... unless it's false
									$this->noinstall = $v != 'false' ? true : false;
								break;
					}
				}
			}
		}
	}

	public function parseThemeIds( $args )
	{
		if( ! empty( $args ) )
		{
			foreach( $args as $arg )
			{
				// every argument that does not start with a -- considered as an ID
				// and must be a number (integer)
				if( substr( $arg,0,2 ) != '--' )
				{
					$intarg = (int)$arg;
					if( $intarg )
					{
						$this->theme_ids[] = $intarg;
					}
				}
			}
		}

		if( empty( $this->theme_ids ) )
		{
			$this->stopExec( "No theme IDs found. Exiting." );
		}
	}

	public function stopExec( $msg = '' )
	{
		if( $msg )
		{
			echo $msg . "\r\n";
		}
		exit;
	}

	private function _checkUrl($url)
	{
		$agent = "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)";
		$ch=curl_init();

		curl_setopt ($ch, CURLOPT_URL,$url );
		curl_setopt($ch, CURLOPT_USERAGENT, $agent);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($ch,CURLOPT_VERBOSE,false);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);

		$page=curl_exec($ch);
		//echo curl_error($ch);

		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		if($httpcode>=200 && $httpcode<300) return true;
		else return false;
	}

	private function _downloadFile( $tid, $install = false )
	{
		$url = $this->themes_url . '/theme/download?id=' . $tid;

		$agent = "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)";
		$ch = curl_init();
		$timeout = 5;
		curl_setopt($ch, CURLOPT_USERAGENT, $agent);
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
		$data = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);
		if($httpcode>=200 && $httpcode<300 && $data )
		{
			$file_name = "yiitheme$tid.zip";
			$handle = fopen( $this->themespath . '/' . $file_name, 'w' ) or die( "Could not create file!\r\n" );
			if( fwrite( $handle, $data ) )
			{
				fclose( $handle );

				if( $install )
				{
					$this->_unzip( $file_name );
				}

				return true;
			}
			fclose( $handle );
		}

		return false;
	}

	private function _unzip( $file )
	{

		if( @class_exists( 'ZipArchive') )
		{
			echo "Installing ... ";
			$zip = new ZipArchive();
			$res = $zip->open( $this->themespath . '/' . $file );

			if( $res === true )
			{
				$zip->extractTo( $this->themespath );
				$zip->close();
				return true;
			}
		}

		echo 'Install FAILED!!! ';

		return false;
	}

}
