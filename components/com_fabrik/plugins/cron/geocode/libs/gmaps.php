<?php
/**
 * GMaps class ver 0.2
 *
 * Gets geo-informations from the Google Maps API
 * http://code.google.com/apis/maps/index.html
 *
 * Copyright 2008-2009 by Enrico Zimuel (enrico@zimuel.it)
 *
 */
class GMaps
{
    const MAPS_HOST = 'maps.google.com';
    /**
     * Latitude
     *
     * @var double
     */
    private $_latitude;
    /**
     * Longitude
     *
     * @var double
     */
    private $_longitude;
    /**
     * Address
     *
     * @var string
     */
    private $_address;
    /**
     * Country name
     *
     * @var string
     */
    private $_countryName;
    /**
     * Country name code
     *
     * @var string
     */
    private $_countryNameCode;
    /**
     * Administrative area name
     *
     * @var string
     */
    private $_administrativeAreaName;
    /**
     * Postal Code
     *
     * @var string
     */
    private $_postalCode;
    /**
     * Google Maps Key
     *
     * @var string
     */
    private $_key;
    /**
     * Base Url
     *
     * @var string
     */
    private $_baseUrl;

    /**
     *
     * error string
     * @var string
     */
    public $err;

    public $errcode;
    /**
     * Construct
     *
     * @param string $key
     */
    function __construct ($key='')
    {
        $this->_key= $key;
        $this->_baseUrl= "http://" . self::MAPS_HOST . "/maps/geo?output=xml&key=" . $this->_key;
    }
    /**
     * getInfoLocation
     *
     * @param string $address
     * @param string $city
     * @param string $state
     * @return boolean
     */
    public function getInfoLocation ($address) {
        if (!empty($address)) {
            return $this->_connect($address);
        }
        return false;
    }
    /**
     * connect to Google Maps
     *
     * @param string $param
     * @return boolean
     */
    private function _connect($param) {
    		$this->err = '';
    		$this->errcode= '';
        $request_url = $this->_baseUrl . "&oe=utf-8&q=" . urlencode($param);
        $xml = simplexml_load_file($request_url);
        if (!empty($xml->Response) && $xml->Response->Status->code == 200) {
            $point= $xml->Response->Placemark->Point;
            if (!empty($point)) {
                $coordinatesSplit = split(",", $point->coordinates);
                // Format: Longitude, Latitude, Altitude
                $this->_latitude = $coordinatesSplit[1];
                $this->_longitude = $coordinatesSplit[0];
            }
            $this->_address= $xml->Response->Placemark->address;
            $this->_countryName= $xml->Response->Placemark->AddressDetails->Country->CountryName;
            $this->_countryNameCode= $xml->Response->Placemark->AddressDetails->Country->CountryNameCode;
            $this->_administrativeAreaName= $xml->Response->Placemark->AddressDetails->Country->AdministrativeArea->AdministrativeAreaName;
            $administrativeArea= $xml->Response->Placemark->AddressDetails->Country->AdministrativeArea;
            if (!empty($administrativeArea->SubAdministrativeArea)) {
                $this->_postalCode= $administrativeArea->SubAdministrativeArea->Locality->PostalCode->PostalCodeNumber;
            } elseif (!empty($administrativeArea->Locality)) {
                $this->_postalCode= $administrativeArea->Locality->PostalCode->PostalCodeNumber;
            }
            return true;
        } else {
        	if (!empty($xml->Response)) {
        		$this->logError($xml->Response->Status->code);
        	}
            return false;
        }
    }

    /**
     * load error resonse codes
     * @param int $code
     */
    protected function logError($code)
    {
    	$this->errcode = $code;
    	switch($code) {
    		case '400':
    			$this->err = "A directions request could not be successfully parsed. For example, the request may have been rejected if it contained more than the maximum number of waypoints allowed. ";
    			break;
    		case '500':
    			$this->err = "A geocoding, directions or maximum zoom level request could not be successfully processed, yet the exact reason for the failure is not known.";
    			break;
    		case '601':
    			$this->err = "The HTTP q parameter was either missing or had no value. For geocoding requests, this means that an empty address was specified as input. For directions requests, this means that no query was specified in the input. ";
    			break;
    		case '602':
    			$this->err = "No corresponding geographic location could be found for the specified address. This may be due to the fact that the address is relatively new, or it may be incorrect.";
    			break;
    		case '603':
    			$this->err = "The geocode for the given address or the route for the given directions query cannot be returned due to legal or contractual reasons. ";
    			break;
    		case '604':
    			$this->err = "The GDirections object could not compute directions between the points mentioned in the query. This is usually because there is no route available between the two points, or because we do not have data for routing in that region.";
    			break;
    		case '610':
    			$this->err = "The given key is either invalid or does not match the domain for which it was given. ";
    			break;
    		case '620':
    			$this->err = "The given key has gone over the requests limit in the 24 hour period or has submitted too many requests in too short a period of time. If you're sending multiple requests in parallel or in a tight loop, use a timer or pause in your code to make sure you don't send the requests too quickly.";
    			break;
    		default:
    			$this->err = "unkown error code: $code";
    			break;
    	}
    }
    /**
     * get the Postal Code
     *
     * @return string
     */
    public function getPostalCode () {
        return $this->_postalCode;
    }
	/**
     * get the Address
     *
     * @return string
     */
    public function getAddress () {
        return $this->_address;
    }
	/**
     * get the Country name
     *
     * @return string
     */
    public function getCountryName () {
        return $this->_countryName;
    }
	/**
     * get the Country name code
     *
     * @return string
     */
    public function getCountryNameCode () {
        return $this->_countryNameCode;
    }
	/**
     * get the Administrative area name
     *
     * @return string
     */
    public function getAdministrativeAreaName () {
        return $this->_administrativeAreaName;
    }
    /**
     * get the Latitude coordinate
     *
     * @return double
     */
    public function getLatitude () {
        return $this->_latitude;
    }
    /**
     * get the Longitude coordinate
     *
     * @return double
     */
    public function getLongitude () {
        return $this->_longitude;
    }
}
?>
