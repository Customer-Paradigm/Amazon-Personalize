<?php
namespace CustomerParadigm\AmazonPersonalize\Model\Calc;

define("APL_NOTIFICATION_NO_CONNECTION", "Can't connect to licensing server.");
define("APL_NOTIFICATION_INVALID_RESPONSE", "Invalid server response.");
define("APL_NOTIFICATION_DATABASE_WRITE_ERROR", "Can't write to database.");
define("APL_NOTIFICATION_LICENSE_FILE_WRITE_ERROR", "Can't write to license file.");
define("APL_NOTIFICATION_SCRIPT_ALREADY_INSTALLED", "Script is already installed (or database not empty).");
define("APL_NOTIFICATION_LICENSE_CORRUPTED", "License is not installed yet or corrupted.");
define("APL_NOTIFICATION_BYPASS_VERIFICATION", "No need to verify");
define("APL_INCLUDE_KEY_CONFIG", "some_random_text");
define("APL_ROOT_IP", "");

define("APL_USER_INPUT_NOTIFICATION_INVALID_ROOT_URL", "User input error: Invalid installation URL (it should have a valid scheme and no / symbol at the end)");
define("APL_USER_INPUT_NOTIFICATION_EMPTY_LICENSE_DATA", "User input error: empty license data (licensed email or license code should be provided)");
define("APL_USER_INPUT_NOTIFICATION_INVALID_EMAIL", "User input error: invalid licensed email (it should be a valid email address)");
define("APL_USER_INPUT_NOTIFICATION_INVALID_LICENSE_CODE", "User input error: invalid license code (it should be a code in plain text)");

define("APL_CORE_NOTIFICATION_INVALID_SALT", "Configuration error: invalid or default encryption salt");
define("APL_CORE_NOTIFICATION_INVALID_ROOT_URL", "Configuration error: invalid root URL of Auto PHP Licenser installation");
define("APL_CORE_NOTIFICATION_INVALID_PRODUCT_ID", "Configuration error: invalid product ID");
define("APL_CORE_NOTIFICATION_INVALID_VERIFICATION_PERIOD", "Configuration error: invalid license verification period");
define("APL_CORE_NOTIFICATION_INVALID_STORAGE", "Configuration error: invalid license storage option");
define("APL_CORE_NOTIFICATION_INVALID_TABLE", "Configuration error: invalid MySQL table name to store license signature");
define("APL_CORE_NOTIFICATION_INVALID_LICENSE_FILE", "Configuration error: invalid license file location (or file not writable)");
define("APL_CORE_NOTIFICATION_INVALID_ROOT_IP", "Configuration error: invalid IP address of your Auto PHP Licenser installation");
define("APL_CORE_NOTIFICATION_INVALID_ROOT_NAMESERVERS", "Configuration error: invalid nameservers of your Auto PHP Licenser installation");
define("APL_CORE_NOTIFICATION_INVALID_DNS", "License error: actual IP address and/or nameservers of your Auto PHP Licenser installation don't match specified IP address and/or nameservers");
define("APL_DELETE_CANCELLED", "NO");
define("APL_DELETE_CRACKED", "YES");

use \Magento\Framework\Stdlib\DateTime\DateTime;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;

class Calculate 
{
	protected $resource;
	protected $connection;
	protected $timezone;
	protected $configWriter;
	protected $moddir;
	protected $scopeConfig;
    protected $scope;
    protected $cssServer;
    protected $cssVersion;
    protected $cssVersionTtl;
    protected $ruleKey;
    protected $ruleTable;
    protected $logger;

	public function __construct(
		\Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
	) {
		$this->resource = $resource;
		$this->connection = $this->resource->getConnection();
		$this->timezone = $timezone;
        $this->configWriter = $configWriter;
        $this->moddir = __DIR__;
        $this->scopeConfig = $scopeConfig;
        $this->scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        $this->logger = $logger;

        $this->cssServer = $this->scopeConfig->getValue('awsp_settings/awsp_general/css_server', $this->scope);
        $this->cssVersion = $this->scopeConfig->getValue('awsp_settings/awsp_general/css_version', $this->scope);
        $this->cssVersionTtl = $this->scopeConfig->getValue('awsp_settings/awsp_general/css_version_ttl', $this->scope);
        //$this->ruleKey = $this->scopeConfig->getValue('awsp_settings/awsp_general/rule_key', $this->scope);
        $this->ruleKey = $this->setRule();
       // $this->ruleKey = 'awequei909aezn';
        $this->ruleTable = $this->scopeConfig->getValue('awsp_settings/awsp_general/rule_table', $this->scope);
	}

    public function setRule() {
        $key = $this->scopeConfig->getValue('awsp_settings/awsp_general/rule_key', $this->scope);
        if( empty($key) ) {
            $this->configWriter->save('awsp_settings/awsp_general/rule_key', bin2hex(random_bytes(16)));
        }
		return $this->scopeConfig->getValue('awsp_settings/awsp_general/rule_key', $this->scope);
    }

	public function aplCustomEncrypt($string, $key)
	{
		$encrypted_string="";

		if (!empty($string) && !empty($key))
		{
			$iv=openssl_random_pseudo_bytes(openssl_cipher_iv_length("aes-256-cbc")); //generate an initialization vector

			$encrypted_string=openssl_encrypt($string, "aes-256-cbc", $key, 0, $iv); //encrypt the string using AES 256 encryption in CBC mode using encryption key and initialization vector
			$encrypted_string=base64_encode($encrypted_string."::".$iv); //the $iv is just as important as the key for decrypting, so save it with encrypted string using a unique separator "::"
		}

		return $encrypted_string;
	}


	public function aplCustomDecrypt($string, $key)
	{
		$decrypted_string="";

		if (!empty($string) && !empty($key))
		{
			$string=base64_decode($string); //remove the base64 encoding from string (it's always encoded using base64_encode)
			if (stristr($string, "::")) //unique separator "::" found, most likely it's valid encrypted string
			{
				$string_iv_array=explode("::", $string, 2); //to decrypt, split the encrypted string from $iv - unique separator used was "::"
				if (!empty($string_iv_array) && count($string_iv_array)==2) //proper $string_iv_array should contain exactly two values - $encrypted_string and $iv
				{
					list($encrypted_string, $iv)=$string_iv_array;

					$decrypted_string=openssl_decrypt($encrypted_string, "aes-256-cbc", $key, 0, $iv);
				}
			}
		}

		return $decrypted_string;
	}


	//validate integer and check if it's between min and max values
	public function aplValidateIntegerValue($number, $min_value=1, $max_value=999999999)
	{
		$result=false;

		if (!is_float($number) && filter_var($number, FILTER_VALIDATE_INT, array("options"=>array("min_range"=>$min_value, "max_range"=>$max_value)))!==false) //don't allow numbers like 1.0 to bypass validation
		{
			$result=true;
		}

		return $result;
	}


	//validate raw domain (only URL like (sub.)domain.com will validate)
	public function aplValidateRawDomain($url)
	{
		$result=false;

		if (!empty($url))
		{
			if (preg_match('/^[a-z0-9-.]+\.[a-z\.]{2,7}$/', strtolower($url))) //check if this is valid tld
			{
				$result=true;
			}
		}

		return $result;
	}


	//get current page url and remove last slash if needed
	public function aplGetCurrentUrl($remove_last_slash=0)
	{
		$protocol="http";
		$host="";
		$script="";
		$params="";
		$current_url="";

		if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=="off") || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO']=="https"))
		{
			$protocol="https";
		}

		if (isset($_SERVER['HTTP_HOST']))
		{
			$host=$_SERVER['HTTP_HOST'];
		}

		if (isset($_SERVER['SCRIPT_NAME']))
		{
			$script=$_SERVER['SCRIPT_NAME'];
		}

		if (isset($_SERVER['QUERY_STRING']))
		{
			$params=$_SERVER['QUERY_STRING'];
		}

		if (!empty($protocol) && !empty($host) && !empty($script)) //basic checks ok
		{
			$current_url=$protocol.'://'.$host.$script;

			if (!empty($params))
			{
				$current_url.='?'.$params;
			}

			if ($remove_last_slash==1) //remove / from the end of URL if it exists
			{
				while (substr($current_url, -1)=="/") //use cycle in case URL already contained multiple // at the end
				{
					$current_url=substr($current_url, 0, -1);
				}
			}
		}

		return $current_url;
	}


	//get raw domain (returns (sub.)domain.com from url like http://www.(sub.)domain.com/something.php?xx=yy)
	public function aplGetRawDomain($url)
	{
		$raw_domain="";

		if (!empty($url))
		{
			$scheme=parse_url($url, PHP_URL_SCHEME); //check if scheme exists because URL can't be parsed properly without a scheme
			if (empty($scheme)) //add a temporary http:// scheme before parsing if needed
			{
				$url="http://".$url;
			}

			$raw_domain=str_ireplace("www.", "", parse_url($url, PHP_URL_HOST));
		}

		return $raw_domain;
	}


	//return root url from long url (http://www.domain.com/path/file.php?aa=xx becomes http://www.domain.com/path/), remove scheme, www. and last slash if needed
	public function aplGetRootUrl($url, $remove_scheme, $remove_www, $remove_path, $remove_last_slash)
	{
		if (filter_var($url, FILTER_VALIDATE_URL))
		{
			$url_array=parse_url($url); //parse URL into arrays like $url_array['scheme'], $url_array['host'], etc

			$url=str_ireplace($url_array['scheme']."://", "", $url); //make URL without scheme, so no :// is included when searching for first or last /

			if ($remove_path==1) //remove everything after FIRST / in URL, so it becomes "real" root URL
			{
				$first_slash_position=stripos($url, "/"); //find FIRST slash - the end of root URL
				if ($first_slash_position>0) //cut URL up to FIRST slash
				{
					$url=substr($url, 0, $first_slash_position+1);
				}
			}
			else //remove everything after LAST / in URL, so it becomes "normal" root URL
			{
				$last_slash_position=strripos($url, "/"); //find LAST slash - the end of root URL
				if ($last_slash_position>0) //cut URL up to LAST slash
				{
					$url=substr($url, 0, $last_slash_position+1);
				}
			}

			if ($remove_scheme!=1) //scheme was already removed, add it again
			{
				$url=$url_array['scheme']."://".$url;
			}

			if ($remove_www==1) //remove www.
			{
				$url=str_ireplace("www.", "", $url);
			}

			if ($remove_last_slash==1) //remove / from the end of URL if it exists
			{
				while (substr($url, -1)=="/") //use cycle in case URL already contained multiple // at the end
				{
					$url=substr($url, 0, -1);
				}
			}
		}

		return trim($url);
	}


	//make post requests with cookies and referrers, return array with server headers, errors, and body content
	public function aplCustomPost($url, $post_info="", $refer="")
	{
		$user_agent="phpmillion cURL";
		$connect_timeout=10;
		$server_response_array=array();
		$formatted_headers_array=array();

		if (filter_var($url, FILTER_VALIDATE_URL) && !empty($post_info))
		{
			if (empty($refer) || !filter_var($refer, FILTER_VALIDATE_URL)) //use original URL as refer when no valid refer URL provided
			{
				$refer=$url;
			}

			$ch=curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connect_timeout);
			curl_setopt($ch, CURLOPT_TIMEOUT, $connect_timeout);
			curl_setopt($ch, CURLOPT_REFERER, $refer);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_info);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_MAXREDIRS, 10);

			curl_setopt($ch, CURLOPT_HEADERFUNCTION,
				function($curl, $header) use (&$formatted_headers_array)
				{
					$len=strlen($header);
					$header=explode(":", $header, 2);
					if (count($header)<2) //ignore invalid headers
						return $len;

					$name=strtolower(trim($header[0]));
					$formatted_headers_array[$name]=trim($header[1]);

					return $len;
				}
			);

			$result=curl_exec($ch);
            $curl_error=curl_error($ch); 
            if( $curl_error ) {
                $this->logger->error( "\nCalc curl post error" . $curl_error);
            }
            
            curl_close($ch);

			$server_response_array['headers']=$formatted_headers_array;
			$server_response_array['error']=$curl_error;
			$server_response_array['body']=$result;
		}

		return $server_response_array;
	}


	//verify date and/or time according to provided format (such as Y-m-d, Y-m-d H:i, H:i, and so on)
	public function aplVerifyDateTime($datetime, $format)
	{
		$result=false;

		if (!empty($datetime) && !empty($format))
		{
			$datetime=\DateTime::createFromFormat($format, $datetime);
			$errors=\DateTime::getLastErrors();

			if ($datetime && empty($errors['warning_count'])) //datetime OK
			{
				$result=true;
			}
		}

		return $result;
	}


	//calculate number of days between dates
	public function aplGetDaysBetweenDates($date_from, $date_to)
	{
		$number_of_days=0;

		if ($this->aplVerifyDateTime($date_from, "Y-m-d") && $this->aplVerifyDateTime($date_to, "Y-m-d"))
		{
			//$date_to= $this->timezone->date(new \DateTime($date_to));
			//$date_from=$this->timezone->date(new \DateTime($date_from));
			$date_to= new \DateTime($date_to);
			$date_from= new \DateTime($date_from);
			$number_of_days=$date_from->diff($date_to)->format("%a");
		}

		return $number_of_days;
	}


	//parse values between specified xml-like tags
	public function aplParseXmlTags($content, $tag_name)
	{
		$parsed_value="";

		if (!empty($content) && !empty($tag_name))
		{
			preg_match_all("/<".preg_quote($tag_name, "/").">(.*?)<\/".preg_quote($tag_name, "/").">/ims", $content, $output_array, PREG_SET_ORDER);

			if (!empty($output_array[0][1]))
			{
				$parsed_value=trim($output_array[0][1]);
			}
		}

		return $parsed_value;
	}


	//process response from Auto PHP Licenser server. if response received, validate it and parse notifications and data (if any). if response not received or is invalid, return a corresponding notification
	public function aplParseServerNotifications($content_array, $ROOT_URL, $CLIENT_EMAIL, $LICENSE_CODE)
	{
		$notifications_array=array();

		if (!empty($content_array)) //response received, validate it
		{
			if (!empty($content_array['headers']['notification_server_signature']) && $this->aplVerifyServerSignature($content_array['headers']['notification_server_signature'], $ROOT_URL, $CLIENT_EMAIL, $LICENSE_CODE)) //response valid
			{
				$notifications_array['notification_case']=$content_array['headers']['notification_case'];
				$notifications_array['notification_text']=$content_array['headers']['notification_text'];
				if (!empty($content_array['headers']['notification_data'])) //additional data returned
				{
					$notifications_array['notification_data']=json_decode($content_array['headers']['notification_data'], true);
				}
			}
			else //response invalid
			{
				$notifications_array['notification_case']="notification_invalid_response";
				$notifications_array['notification_text']=APL_NOTIFICATION_INVALID_RESPONSE;
			}
		}
		else //no response received
		{
			$notifications_array['notification_case']="notification_no_connection";
			$notifications_array['notification_text']=APL_NOTIFICATION_NO_CONNECTION;
		}

		return $notifications_array;
	}


	//generate signature to be submitted to Auto PHP Licenser server
	public function aplGenerateScriptSignature($ROOT_URL, $CLIENT_EMAIL, $LICENSE_CODE)
	{
		$script_signature="";
		$root_ips_array=gethostbynamel($this->aplGetRawDomain($this->cssServer));

		if (!empty($ROOT_URL) && isset($CLIENT_EMAIL) && isset($LICENSE_CODE) && !empty($root_ips_array))
		{
			$script_signature=hash("sha256", gmdate("Y-m-d").$ROOT_URL.$CLIENT_EMAIL.$LICENSE_CODE.$this->cssVersion.implode("", $root_ips_array));
		}

		return $script_signature;
	}


	//verify signature received from Auto PHP Licenser server
	public function aplVerifyServerSignature($notification_server_signature, $ROOT_URL, $CLIENT_EMAIL, $LICENSE_CODE)
	{
		$result=false;
		$root_ips_array=gethostbynamel($this->aplGetRawDomain($this->cssServer));

		if (!empty($notification_server_signature) && !empty($ROOT_URL) && isset($CLIENT_EMAIL) && isset($LICENSE_CODE) && !empty($root_ips_array))
		{
			if (hash("sha256", implode("", $root_ips_array).$this->cssVersion.$LICENSE_CODE.$CLIENT_EMAIL.$ROOT_URL.gmdate("Y-m-d"))==$notification_server_signature)
			{
				$result=true;
			}
		}

		return $result;
	}


	//check Auto PHP Licenser core configuration and return an array with error messages if something wrong
	public function aplCheckSettings()
    {
		$notifications_array=array();

		if (empty($this->ruleKey) || $this->ruleKey=="some_random_text") //invalid encryption salt
		{
			$notifications_array[]=APL_CORE_NOTIFICATION_INVALID_SALT;
		}

		if (!filter_var($this->cssServer, FILTER_VALIDATE_URL) || !ctype_alnum(substr($this->cssServer, -1))) //invalid Auto PHP Licenser server URL
		{
			$notifications_array[]=APL_CORE_NOTIFICATION_INVALID_ROOT_URL;
		}

		if (!$this->aplValidateIntegerValue($this->cssVersion)) //invalid product ID
		{
			$notifications_array[]=APL_CORE_NOTIFICATION_INVALID_PRODUCT_ID;
		}

		if (!$this->aplValidateIntegerValue($this->cssVersionTtl, 1, 365)) //invalid verification period
		{
			$notifications_array[]=APL_CORE_NOTIFICATION_INVALID_VERIFICATION_PERIOD;
		}

		if ( !ctype_alnum(str_ireplace(array("_"), "", $this->ruleTable))) //invalid license table name
		{
			$notifications_array[]=APL_CORE_NOTIFICATION_INVALID_TABLE;
		}

		if (!empty(APL_ROOT_IP) && !filter_var(APL_ROOT_IP, FILTER_VALIDATE_IP)) //invalid Auto PHP Licenser server IP
		{
			$notifications_array[]=APL_CORE_NOTIFICATION_INVALID_ROOT_IP;
		}

		if (!empty(APL_ROOT_IP) && !in_array(APL_ROOT_IP, gethostbynamel($this->aplGetRawDomain($this->cssServer)))) //actual IP address of Auto PHP Licenser server doesn't match specified IP address
		{
			$notifications_array[]=APL_CORE_NOTIFICATION_INVALID_DNS;
		}

		if (defined("APL_ROOT_NAMESERVERS") && !empty(APL_ROOT_NAMESERVERS)) //check if nameservers are valid (use "defined" to check if nameservers are set because APL_ROOT_NAMESERVERS is commented by default to prevent errors in PHP<7)
		{
			foreach (APL_ROOT_NAMESERVERS as $nameserver)
			{
				if (!aplValidateRawDomain($nameserver)) //invalid Auto PHP Licenser server nameservers
				{
					$notifications_array[]=APL_CORE_NOTIFICATION_INVALID_ROOT_NAMESERVERS;
					break;
				}
			}
		}

		if (defined("APL_ROOT_NAMESERVERS") && !empty(APL_ROOT_NAMESERVERS)) //check if actual nameservers of Auto PHP Licenser server domain match specified nameservers (use "defined" to check if nameservers are set because APL_ROOT_NAMESERVERS is commented by default to prevent errors in PHP<7)
		{
			$apl_root_nameservers_array=APL_ROOT_NAMESERVERS; //create a variable from constant in order to use sort and other array functions
			$fetched_nameservers_array=array();

			$dns_records_array=dns_get_record($this->aplGetRawDomain($this->cssServer), DNS_NS);
			foreach ($dns_records_array as $record)
			{
				$fetched_nameservers_array[]=$record['target'];
			}

			$apl_root_nameservers_array=array_map("strtolower", $apl_root_nameservers_array); //convert root nameservers to lowercase
			$fetched_nameservers_array=array_map("strtolower", $fetched_nameservers_array); //convert fetched nameservers to lowercase

			sort($apl_root_nameservers_array); //sort both arrays before comparison
			sort($fetched_nameservers_array);
			if ($apl_root_nameservers_array!=$fetched_nameservers_array)
			{
				$notifications_array[]=APL_CORE_NOTIFICATION_INVALID_DNS; //actual nameservers of Auto PHP Licenser server don't match specified nameservers
			}
        }
		return $notifications_array;
	}


	//check user input and return an array with error messages if something wrong
	public function aplCheckUserInput($ROOT_URL, $CLIENT_EMAIL, $LICENSE_CODE)
	{
		$notifications_array=array();

		if (empty($ROOT_URL) || !filter_var($ROOT_URL, FILTER_VALIDATE_URL) || !ctype_alnum(substr($ROOT_URL, -1))) //invalid installation url
		{
			$notifications_array[]=APL_USER_INPUT_NOTIFICATION_INVALID_ROOT_URL;
		}

		if (empty($CLIENT_EMAIL) && empty($LICENSE_CODE)) //both email and code empty
		{
			$notifications_array[]=APL_USER_INPUT_NOTIFICATION_EMPTY_LICENSE_DATA;
		}

		if (!empty($CLIENT_EMAIL) && !filter_var($CLIENT_EMAIL, FILTER_VALIDATE_EMAIL)) //invalid email
		{
			$notifications_array[]=APL_USER_INPUT_NOTIFICATION_INVALID_EMAIL;
		}

		if (!empty($LICENSE_CODE) && !is_string($LICENSE_CODE)) //invalid license code
		{
			$notifications_array[]=APL_USER_INPUT_NOTIFICATION_INVALID_LICENSE_CODE;
		}

		return $notifications_array;
	}


	//parse license file and make an array with license data
	public function aplParseLicenseFile()
	{
		$license_data_array=array();

		if (is_readable(APL_DIRECTORY."/".APL_LICENSE_FILE_LOCATION))
		{
			$file_content=file_get_contents(APL_DIRECTORY."/".APL_LICENSE_FILE_LOCATION);
			preg_match_all("/<([A-Z_]+)>(.*?)<\/([A-Z_]+)>/", $file_content, $matches, PREG_SET_ORDER);
			if (!empty($matches))
			{
				foreach ($matches as $value)
				{
					if (!empty($value[1]) && $value[1]==$value[3])
					{
						$license_data_array[$value[1]]=$value[2];
					}
				}
			}
		}

		return $license_data_array;
	}


	//return an array with license data,no matter where license is stored
    public function aplGetLicenseData($connection=null) {
		$settings_row=array();
		$rtn = array();

        if($this->calcTableExists()) {
            $settings_row= $this->connection->fetchAssoc("SELECT * FROM ".$this->ruleTable);
            $rtn = count($settings_row) > 0 ? $settings_row[1] : array();
        }

		return $rtn;
	}

	//check if connection to Auto PHP Licenser server can be established. if connection failed or response was invalid, return an array with errors
	public function aplCheckConnection()
	{
		$notifications_array=array();

		$content_array=$this->aplCustomPost($this->cssServer."/apl_callbacks/connection_test.php", "product_id=".rawurlencode($this->cssVersion)."&connection_hash=".rawurlencode(hash("sha256", "connection_test")));
		if (!empty($content_array)) //response received
		{
			if ($content_array['body']!="<connection_test>OK</connection_test>") //response invalid
			{
				$notifications_array['notification_case']="notification_invalid_response";
				$notifications_array['notification_text']=APL_NOTIFICATION_INVALID_RESPONSE;
			}
		}
		else //no response received
		{
			$notifications_array['notification_case']="notification_no_connection";
			$notifications_array['notification_text']=APL_NOTIFICATION_NO_CONNECTION;
		}

		return $notifications_array;
	}


	//check license data and return false if something wrong
	public function aplCheckData($connection=null)
    {
		$error_detected=0;
		$cracking_detected=0;
		$result=false;

        extract($this->aplGetLicenseData($this->connection));
		if (!empty($ROOT_URL) && !empty($INSTALLATION_HASH) && !empty($INSTALLATION_KEY) && !empty($LCD) && !empty($LRD)) //do further check only if essential variables are valid
        {
            $LCD=$this->aplCustomDecrypt($LCD, $this->ruleKey.$INSTALLATION_KEY); 
            $LRD=$this->aplCustomDecrypt($LRD, $this->ruleKey.$INSTALLATION_KEY); 

			if (!filter_var($ROOT_URL, FILTER_VALIDATE_URL) || !ctype_alnum(substr($ROOT_URL, -1))) //invalid installation url
            {
				$error_detected=1;
			}

			if (filter_var($this->aplGetCurrentUrl(), FILTER_VALIDATE_URL) && stristr($this->aplGetRootUrl($this->aplGetCurrentUrl(), 1, 1, 0, 1), $this->aplGetRootUrl("$ROOT_URL/", 1, 1, 0, 1))===false) //script is opened via browser (current_url set), but current_url is different from value in database
			{
				$error_detected=1;
			}

			if (empty($INSTALLATION_HASH) || $INSTALLATION_HASH!=hash("sha256", $ROOT_URL.$CLIENT_EMAIL.$LICENSE_CODE)) //invalid installation hash (value - $ROOT_URL, $CLIENT_EMAIL AND $LICENSE_CODE encrypted with sha256)
			{
				$error_detected=1;
			}
			if (empty($INSTALLATION_KEY) || !password_verify($LRD, $this->aplCustomDecrypt($INSTALLATION_KEY, $this->ruleKey.$ROOT_URL))) //invalid installation key (value - current date ("Y-m-d") encrypted with password_hash and then encrypted with custom public function (salt - $ROOT_URL). Put simply, it's LRD value, only encrypted different way)
            {
				$error_detected=1;
			}

			if (! $this->aplVerifyDateTime($LCD, "Y-m-d")) //last check date is invalid
            {
				$error_detected=1;
			}

			if (! $this->aplVerifyDateTime($LRD, "Y-m-d")) //last run date is invalid
			{
				$error_detected=1;
			}

			//check for possible cracking attempts - starts
			if ($this->aplVerifyDateTime($LCD, "Y-m-d") && $LCD>date("Y-m-d", strtotime("+1 day"))) //last check date is VALID, but higher than current date (someone manually decrypted and overwrote it or changed system time back). Allow 1 day difference in case user changed his timezone and current date went 1 day back
			{
				$error_detected=1;
				$cracking_detected=1;
			}

			if ($this->aplVerifyDateTime($LRD, "Y-m-d") && $LRD>date("Y-m-d", strtotime("+1 day"))) //last run date is VALID, but higher than current date (someone manually decrypted and overwrote it or changed system time back). Allow 1 day difference in case user changed his timezone and current date went 1 day back
			{
				$error_detected=1;
				$cracking_detected=1;
			}

			if ($this->aplVerifyDateTime($LCD, "Y-m-d") && $this->aplVerifyDateTime($LRD, "Y-m-d") && $LCD>$LRD) //last check date and last run date is VALID, but LCD is higher than LRD (someone manually decrypted and overwrote it or changed system time back)
			{
				$error_detected=1;
				$cracking_detected=1;
			}

			if ($cracking_detected==1 && APL_DELETE_CRACKED=="YES") //delete user data
			{
				$this->aplDeleteData($this->connection);
			}
            //check for possible cracking attempts - ends

			if ($error_detected!=1 && $cracking_detected!=1) //everything OK
			{
				$result=true;
			}
		}
		return $result;
	}


	//verify Envato purchase. if connection failed or response was invalid, return an array with errors
	public function aplVerifyEnvatoPurchase($LICENSE_CODE="")
	{
		$notifications_array=array();

		$content_array=$this->aplCustomPost($this->cssServer."/apl_callbacks/verify_envato_purchase.php", "product_id=".rawurlencode($this->cssVersion)."&license_code=".rawurlencode($LICENSE_CODE)."&connection_hash=".rawurlencode(hash("sha256", "verify_envato_purchase")));
		if (!empty($content_array)) //response received
		{
			if ($content_array['body']!="<verify_envato_purchase>OK</verify_envato_purchase>") //response invalid
			{
				$notifications_array['notification_case']="notification_invalid_response";
				$notifications_array['notification_text']=APL_NOTIFICATION_INVALID_RESPONSE;
			}
		}
		else //no response received
		{
			$notifications_array['notification_case']="notification_no_connection";
			$notifications_array['notification_text']=APL_NOTIFICATION_NO_CONNECTION;
		}

		return $notifications_array;
	}


	//install license

	public function calcCoupon($ROOT_URL, $CLIENT_EMAIL, $LICENSE_CODE, $connection=null)
    {
		$notifications_array=array();

		if (empty($apl_core_notifications=$this->aplCheckSettings())) //only continue if script is properly configured
        {
			if (!empty($this->aplGetLicenseData($this->connection)) && is_array($this->aplGetLicenseData($this->connection))) //license already installed
			{
				$notifications_array['notification_case']="notification_already_installed";
				$notifications_array['notification_text']=APL_NOTIFICATION_SCRIPT_ALREADY_INSTALLED;
			}
			else //license not yet installed, do it now
            {
				if (empty($apl_user_input_notifications=$this->aplCheckUserInput($ROOT_URL, $CLIENT_EMAIL, $LICENSE_CODE))) //data submitted by user is valid
				{
					$INSTALLATION_HASH=hash("sha256", $ROOT_URL.$CLIENT_EMAIL.$LICENSE_CODE); //generate hash
					$post_info="product_id=".rawurlencode($this->cssVersion)."&client_email=".rawurlencode($CLIENT_EMAIL)."&license_code=".rawurlencode($LICENSE_CODE)."&root_url=".rawurlencode($ROOT_URL)."&installation_hash=".rawurlencode($INSTALLATION_HASH)."&license_signature=".rawurlencode($this->aplGenerateScriptSignature($ROOT_URL, $CLIENT_EMAIL, $LICENSE_CODE));

					$content_array=$this->aplCustomPost($this->cssServer."/apl_callbacks/license_install.php", $post_info, $ROOT_URL);
					$notifications_array=$this->aplParseServerNotifications($content_array, $ROOT_URL, $CLIENT_EMAIL, $LICENSE_CODE); //process response from Auto PHP Licenser server
					if ($notifications_array['notification_case']=="notification_license_ok") //everything OK
					{
						$INSTALLATION_KEY=$this->aplCustomEncrypt(password_hash(date("Y-m-d"), PASSWORD_DEFAULT), $this->ruleKey.$ROOT_URL); //generate $INSTALLATION_KEY first because it will be used as salt to encrypt LCD and LRD!!!
						$LCD=$this->aplCustomEncrypt(date("Y-m-d", strtotime("-".$this->cssVersionTtl." days")), $this->ruleKey.$INSTALLATION_KEY); //license will need to be verified right after installation
						$LRD=$this->aplCustomEncrypt(date("Y-m-d"), $this->ruleKey.$INSTALLATION_KEY);

                        $content_array=$this->aplCustomPost($this->cssServer."/apl_callbacks/license_scheme.php", $post_info, $ROOT_URL); //get license scheme (use the same $post_info from license installation)
                        $notifications_array=$this->aplParseServerNotifications($content_array, $ROOT_URL, $CLIENT_EMAIL, $LICENSE_CODE); //process response from Auto PHP Licenser server
                        if (!empty($notifications_array['notification_data']) && !empty($notifications_array['notification_data']['scheme_query'])) //valid scheme received
                        {
                            $mysql_bad_array=array("%APL_DATABASE_TABLE%", "%ROOT_URL%", "%CLIENT_EMAIL%", "%LICENSE_CODE%", "%LCD%", "%LRD%", "%INSTALLATION_KEY%", "%INSTALLATION_HASH%");
                            
                            $mysql_good_array=array($this->ruleTable, $ROOT_URL, $CLIENT_EMAIL, $LICENSE_CODE, $LCD, $LRD, $INSTALLATION_KEY, $INSTALLATION_HASH);

                            $license_scheme=str_replace($mysql_bad_array, $mysql_good_array, $notifications_array['notification_data']['scheme_query']); 
                            $query_array = explode(';',$license_scheme);

                            // create table if it doesn't exist
			    if( ! $this->calcTableExists() ) {
				try {
					$this->connection->query($query_array[0]);
				} catch(Exception $e) {
            				$this->logger->error( "\nCalc config connection error:  " . $e->getMessage());
				}
                            }
                            // insert data
			    try {
				    $this->connection->query($query_array[1]); 
			    } catch(Exception $e) {
				$this->logger->error( "\nCalc config data insert error:  " . $e->getMessage());
			    }
                        }

					}
				}
				else //data submitted by user is invalid
				{
					$notifications_array['notification_case']="notification_user_input_invalid";
					$notifications_array['notification_text']=implode("; ", $apl_user_input_notifications);
				}
			}
		}
		else //script is not properly configured
		{
			$notifications_array['notification_case']="notification_script_corrupted";
			$notifications_array['notification_text']=implode("; ", $apl_core_notifications);
            		$this->logger->error( "\nCalc config error:  " . print_r($notifications_array,true));
		}

		return $notifications_array;
	}


	//verify license
	public function canCalc($connection=null, $FORCE_VERIFICATION=0)
	{
		$notifications_array=array();
		$update_lrd_value=0;
		$update_lcd_value=0;
		$updated_records=0;

		if (empty($apl_core_notifications=$this->aplCheckSettings())) //only continue if script is properly configured
		{
			if ($this->aplCheckData($this->connection)) //only continue if license is installed and properly configured
			{
				extract($this->aplGetLicenseData($this->connection)); //get license data

				if ($this->aplGetDaysBetweenDates($this->aplCustomDecrypt($LCD, $this->ruleKey.$INSTALLATION_KEY), date("Y-m-d"))<$this->cssVersionTtl && $this->aplCustomDecrypt($LCD, $this->ruleKey.$INSTALLATION_KEY)<=date("Y-m-d") && $this->aplCustomDecrypt($LRD, $this->ruleKey.$INSTALLATION_KEY)<=date("Y-m-d") && $FORCE_VERIFICATION===0) //the only case when no verification is needed, return notification_license_ok case, so script can continue working
				{
					$notifications_array['notification_case']="notification_license_ok";
					$notifications_array['notification_text']=APL_NOTIFICATION_BYPASS_VERIFICATION;
				}
				else //time to verify license (or use forced verification)
				{
					$post_info="product_id=".rawurlencode($this->cssVersion)."&client_email=".rawurlencode($CLIENT_EMAIL)."&license_code=".rawurlencode($LICENSE_CODE)."&root_url=".rawurlencode($ROOT_URL)."&installation_hash=".rawurlencode($INSTALLATION_HASH)."&license_signature=".rawurlencode($this->aplGenerateScriptSignature($ROOT_URL, $CLIENT_EMAIL, $LICENSE_CODE));

					$content_array=$this->aplCustomPost($this->cssServer."/apl_callbacks/license_verify.php", $post_info, $ROOT_URL);
					$notifications_array=$this->aplParseServerNotifications($content_array, $ROOT_URL, $CLIENT_EMAIL, $LICENSE_CODE); //process response from Auto PHP Licenser server
					if ($notifications_array['notification_case']=="notification_license_ok") //everything OK
					{
						$update_lcd_value=1;
					}

					if ($notifications_array['notification_case']=="notification_license_cancelled" && APL_DELETE_CANCELLED=="YES") //license cancelled, data deletion activated, so delete user data
					{
						$this->aplDeleteData($this->connection);
					}
				}

				if ($this->aplCustomDecrypt($LRD, $this->ruleKey.$INSTALLATION_KEY)<date("Y-m-d")) //used to make sure database gets updated only once a day, not every time script is executed. do it BEFORE new $INSTALLATION_KEY is generated
				{
					$update_lrd_value=1;
				}

				if ($update_lrd_value==1 || $update_lcd_value==1) //update database only if $LRD or $LCD were changed
				{
					if ($update_lcd_value==1) //generate new $LCD value ONLY if verification succeeded. Otherwise, old $LCD value should be used, so license will be verified again next time script is executed
					{
						$LCD=date("Y-m-d");
					}
					else //get existing DECRYPTED $LCD value because it will need to be re-encrypted using new $INSTALLATION_KEY in case license verification didn't succeed
					{
						$LCD=$this->aplCustomDecrypt($LCD, $this->ruleKey.$INSTALLATION_KEY);
					}

					$INSTALLATION_KEY=$this->aplCustomEncrypt(password_hash(date("Y-m-d"), PASSWORD_DEFAULT), $this->ruleKey.$ROOT_URL); //generate $INSTALLATION_KEY first because it will be used as salt to encrypt LCD and LRD!!!
					$LCD=$this->aplCustomEncrypt($LCD, $this->ruleKey.$INSTALLATION_KEY); //finally encrypt $LCD value (it will contain either DECRYPTED old date, either non-encrypted today's date)
					$LRD=$this->aplCustomEncrypt(date("Y-m-d"), $this->ruleKey.$INSTALLATION_KEY); //generate new $LRD value every time database needs to be updated (because if LCD is higher than LRD, cracking attempt will be detected).

                    $update = "UPDATE " .$this->ruleTable. " SET LCD='$LCD', LRD='$LRD', INSTALLATION_KEY='$INSTALLATION_KEY'";
                    $this->connection->query($update);
				}
			}
			else //license is not installed yet or corrupted
			{
				$notifications_array['notification_case']="notification_license_corrupted";
				$notifications_array['notification_text']=APL_NOTIFICATION_LICENSE_CORRUPTED;
			}
		}
		else //script is not properly configured
		{
			$notifications_array['notification_case']="notification_script_corrupted";
			$notifications_array['notification_text']=implode("; ", $apl_core_notifications);
		}

		return $notifications_array;
	}

	public function calcVerifySupport($connection=null)
	{
		$notifications_array=array();

		if (empty($apl_core_notifications=$this->aplCheckSettings())) //only continue if script is properly configured
		{
			if ($this->aplCheckData($this->connection)) //only continue if license is installed and properly configured
			{
				extract($this->aplGetLicenseData($this->connection)); //get license data

				$post_info="product_id=".rawurlencode($this->cssVersion)."&client_email=".rawurlencode($CLIENT_EMAIL)."&license_code=".rawurlencode($LICENSE_CODE)."&root_url=".rawurlencode($ROOT_URL)."&installation_hash=".rawurlencode($INSTALLATION_HASH)."&license_signature=".rawurlencode($this->aplGenerateScriptSignature($ROOT_URL, $CLIENT_EMAIL, $LICENSE_CODE));

				$content_array=$this->aplCustomPost($this->cssServer."/apl_callbacks/license_support.php", $post_info, $ROOT_URL);
				$notifications_array=$this->aplParseServerNotifications($content_array, $ROOT_URL, $CLIENT_EMAIL, $LICENSE_CODE); //process response from Auto PHP Licenser server
			}
			else //license is not installed yet or corrupted
			{
				$notifications_array['notification_case']="notification_license_corrupted";
				$notifications_array['notification_text']=APL_NOTIFICATION_LICENSE_CORRUPTED;
			}
		}
		else //script is not properly configured
		{
			$notifications_array['notification_case']="notification_script_corrupted";
			$notifications_array['notification_text']=implode("; ", $apl_core_notifications);
		}

		return $notifications_array;
	}

	public function calcVerifyUpdates($connection=null)
	{
		$notifications_array=array();

		if (empty($apl_core_notifications=$this->aplCheckSettings())) //only continue if script is properly configured
		{
			if ($this->aplCheckData($this->connection)) //only continue if license is installed and properly configured
			{
				extract($this->aplGetLicenseData($this->connection)); //get license data

				$post_info="product_id=".rawurlencode($this->cssVersion)."&client_email=".rawurlencode($CLIENT_EMAIL)."&license_code=".rawurlencode($LICENSE_CODE)."&root_url=".rawurlencode($ROOT_URL)."&installation_hash=".rawurlencode($INSTALLATION_HASH)."&license_signature=".rawurlencode($this->aplGenerateScriptSignature($ROOT_URL, $CLIENT_EMAIL, $LICENSE_CODE));

				$content_array=$this->aplCustomPost($this->cssServer."/apl_callbacks/license_updates.php", $post_info, $ROOT_URL);
				$notifications_array=$this->aplParseServerNotifications($content_array, $ROOT_URL, $CLIENT_EMAIL, $LICENSE_CODE); //process response from Auto PHP Licenser server
			}
			else //license is not installed yet or corrupted
			{
				$notifications_array['notification_case']="notification_license_corrupted";
				$notifications_array['notification_text']=APL_NOTIFICATION_LICENSE_CORRUPTED;
			}
		}
		else //script is not properly configured
		{
			$notifications_array['notification_case']="notification_script_corrupted";
			$notifications_array['notification_text']=implode("; ", $apl_core_notifications);
		}

		return $notifications_array;
	}

	public function calcUpdate($connection=null)
	{
		$notifications_array=array();

		if (empty($apl_core_notifications=$this->aplCheckSettings())) //only continue if script is properly configured
		{
			if ($this->aplCheckData($this->connection)) //only continue if license is installed and properly configured
			{
				extract($this->aplGetLicenseData($this->connection)); //get license data

				$post_info="product_id=".rawurlencode($this->cssVersion)."&client_email=".rawurlencode($CLIENT_EMAIL)."&license_code=".rawurlencode($LICENSE_CODE)."&root_url=".rawurlencode($ROOT_URL)."&installation_hash=".rawurlencode($INSTALLATION_HASH)."&license_signature=".rawurlencode($this->aplGenerateScriptSignature($ROOT_URL, $CLIENT_EMAIL, $LICENSE_CODE));

				$content_array=$this->aplCustomPost($this->cssServer."/apl_callbacks/license_update.php", $post_info, $ROOT_URL);
				$notifications_array=$this->aplParseServerNotifications($content_array, $ROOT_URL, $CLIENT_EMAIL, $LICENSE_CODE); //process response from Auto PHP Licenser server
			}
			else //license is not installed yet or corrupted
			{
				$notifications_array['notification_case']="notification_license_corrupted";
				$notifications_array['notification_text']=APL_NOTIFICATION_LICENSE_CORRUPTED;
			}
		}
		else //script is not properly configured
		{
			$notifications_array['notification_case']="notification_script_corrupted";
			$notifications_array['notification_text']=implode("; ", $apl_core_notifications);
		}

		return $notifications_array;
	}

	public function calcUninstall($connection=null)
    {
		$notifications_array=array();

		if (empty($apl_core_notifications=$this->aplCheckSettings())) //only continue if script is properly configured
        {
			if ($this->aplCheckData($this->connection)) //only continue if license is installed and properly configured
            {
                extract($this->aplGetLicenseData($this->connection)); //get license data;

				$post_info="product_id=".rawurlencode($this->cssVersion)."&client_email=".rawurlencode($CLIENT_EMAIL)."&license_code=".rawurlencode($LICENSE_CODE)."&root_url=".rawurlencode($ROOT_URL)."&installation_hash=".rawurlencode($INSTALLATION_HASH)."&license_signature=".rawurlencode($this->aplGenerateScriptSignature($ROOT_URL, $CLIENT_EMAIL, $LICENSE_CODE));

				$content_array=$this->aplCustomPost($this->cssServer."/apl_callbacks/license_uninstall.php", $post_info, $ROOT_URL);
				$notifications_array=$this->aplParseServerNotifications($content_array, $ROOT_URL, $CLIENT_EMAIL, $LICENSE_CODE); //process response from Auto PHP Licenser server
                if ($notifications_array['notification_case']=="notification_license_ok"
                    || $notifications_array['notification_case']=="notification_installation_not_found" ) 
				{
                    $delqry = "DELETE FROM ".$this->ruleTable;
                    $dropqry = "DROP TABLE ".$this->ruleTable;
                    $this->connection->query($delqry);
                    $this->connection->query($dropqry);
                    $this->configWriter->save('awsp_settings/awsp_general/calc_active',false, $this->scope);
				}
			}
			else //license is not installed yet or corrupted
			{
				$notifications_array['notification_case']="notification_license_corrupted";
				$notifications_array['notification_text']=APL_NOTIFICATION_LICENSE_CORRUPTED;
			}
		}
		else //script is not properly configured
		{
			$notifications_array['notification_case']="notification_script_corrupted";
			$notifications_array['notification_text']=implode("; ", $apl_core_notifications);
		}
		return $notifications_array;
    }

	public function calcTableExists() {
		$check_qry = "SHOW TABLES LIKE '%" . $this->ruleTable . "%'";
        $check = $this->connection->fetchOne($check_qry);
		return $check !== false;
	}
}
