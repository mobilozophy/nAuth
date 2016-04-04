<?php
/*~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 * class nAuthJWT
 * Represents an instance of a JSON Web Token (JWT)
 * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~*/
class nAuthJWT
{
    public $payload = array("exp"=>null,"iat"=>null);
    public $isValid=true;
    private $eHeader=null;
    private $ePayload=null;
    private $eSignature=null;
    private $header =array("typ"=>"JWT","alg"=>"HS256");//default encryption
    private $eAlg=null;
    private $securityKey=null;
    /*~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     * __construct
     * nAuthJWT class constructor--creates a new instance of the nAuthJWT class
     *
     * PRE:$tokenString must be a JWT token or null. If $tokenString is null, then $expiration and $encryption must be
     * provided. $expiration must be a string representing a duration from now in minutes (for example, "+30 Minutes".
     * Encryption can be null or a string of 'HS256', 'HS384', or 'HS512'. $privateKey is user-specified and can't be null
     * POST:Creates an instance of a the JSonWebToken class. If $tokenString was passed and failed to validate, then
     * all content will be null, except for $isValid which will be set to false.
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~*/
    public function __construct($tokenString,$expiration,$encryption, $privateKey)
    {
        if ($privateKey!=null) {
            if ($tokenString == null) {
                $this->payload["iat"] = gmdate("Y/m/d h:i:s");
                if (strtotime($expiration)) {
                    $this->payload["exp"] = gmdate("Y/m/d h:i:s", strtotime($expiration));
                } else {
                    throw new Exception("nAuth Exception: Invalid value provided for expiration.");
                }
                $this->header["alg"] = $encryption;
                if (($eAlg = $this->getAlgorithm($encryption)) != false) {
                    $this->eAlg = $eAlg;
                }//if
                else {
                    throw new Exception("nAuth Exception: The specified encryption method of " . $encryption . "is not valid. Please specify null, 'HS256', 'HS384', or 'HS512' ");
                }//else
            }//if tokenString==null
            else {//create an instance of this class from an existing JWT token string
                if ($components = $this->validateToken($tokenString)) {//if this is a valid token, create an instance of this class
                    $this->header = (array)json_decode(base64_decode($components[0]));
                    $this->payload = (array)json_decode(base64_decode($components[1]));
                } else {
                    //don't create a token, instead invalidate it
                    $this->isValid = false;
                }//else
            }//else
        }//privateKey
        else
        {
            throw new Exception("nAuth Exception: Private Key was not specified.");
        }
    }//constructor
    /*~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *(public) function pushPayload
     * Adds and element to the JWT payload
     * PRE: $key must be a string that isn't equal to 'iat' or 'exp', $value can be any datatype, including null
     * POST:Creates a new element in the payload array with a key of $key and a value of $value. If $key already
     * exists as a key in the payload array, then its value is overwritten with $value.
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~*/
    public function pushPayload($key,$value)
    {
        if ($key=='iat' || $key =='exp')
        {
            throw new Exception("nAuth Exception: You cannot use 'iat' ir 'exp' in the pushPayload() method as they have been set by the constructor.");
        }//if
        else
        {
            $this->payload[$key]=$value;
        }//else

    }//function pushPayload
    /*~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *public Function sign()
     *Creates a signature element and encodes the JWT header, payload, and signature blocks
     * PRE: isValid must be true
     * POST: Creates and sets a base64 encoded signature saved as $eSignature. The header and payload are JSON and base64
     * encoded and saved in $eHeader and $ePayload respectively.
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~*/
   public function sign()
    {
        if(($this->isValid))
        {
            $this->eHeader = base64_encode(json_encode($this->header));
            $this->ePayload = base64_encode(json_encode($this->payload));
            $signature = $this->eHeader . "." . $this->ePayload;
            $signature = hash_hmac($this->eAlg, $signature, $this->securityKey, true);
            $this->eSignature = base64_encode($signature);
        }//if
        else
        {
            throw new Exception("nAuth Exception: Cannot sign an invalid JWT. This JWT was created from a token string that did not pass the validation test.");
        }//else
    }//function sign()
    /*~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *public function getTokenString
     *returns a JWT string from this instance of the nAuthJWT class
     *
     * PRE:eHeader, ePayload, and eSignature must be not null
     * POST:A single, properly formatted, JWT string is returned.
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~*/
   public function getTokenString()
    {
        if ($this->eHeader!=null && $this->ePayload!=null && $this->eSignature!=null) {
            $jwt = $this->eHeader . "." . $this->ePayload . "." . $this->eSignature;
            return $jwt;
        }//if
        else
        {
            throw new Exception("nAuth Exception: Cannot return a token string for an unsigned token. Please call the sign() method before calling the getTokenString() method");
        }//else
    }//getTokenString()
    /*~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *public function refreshToken($expiration)
     *changes the expiration time for the token
     *
     * PRE:$expiration must be a non-null string convertible via strtotime;
     * POST:payload["exp"] is updated.
    * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~*/
    public function refreshToken($expiration)
    {
        if (strtotime($expiration))
        {
            $this->payload["exp"] = gmdate("Y/m/d h:i:s", strtotime($expiration));
        }
        else
        {
            throw new Exception("nAuth Exception: Invalid value provided for expiration.");
        }
    }
    /*~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *private function getAlgorithm($encryption)
     *Returns a string representing the hashing algorithm to use in the php hash_hmac function
     *
     * PRE: $encryption is a string or null
     * POST: Returns a string representation of the hashing algorithm to use, or false if $encryption is invalid. A null
     * value will return a default of "sha256"
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~*/
    private function getAlgorithm($encryption)
    {
        switch ($encryption) {
            case null://use HS256 as default
            case "HS256":
                return "sha256";
                break;
            case "HS384":
                return "sha384";
                break;
            case "HS512":
               return "sha512";
                break;
            default:
                return false;
        }
    }
    /*~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *private function validateToken($token)
     *checks the validity of an JWT token
     *
     * PRE: $token must be a string
     * POST: Returns the components array of an exploded JWT string if $token is valid, else returns false
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~*/
    private function validateToken($token)
    {
        $components = explode(".",$token);
        if (count($components)==3)
        {
            $signature = $components[0] . "." . $components[1];
            $head =json_decode(base64_decode($components[0]));
            $eAlg = $this->getAlgorithm($head->alg);
            $signature =base64_encode(hash_hmac($eAlg, $signature, $this->securityKey, true));
            if ($signature == $components[2])
            {
                return $components;
            }
            else
            {
                return false;
            }
        }//if
        else
        {
            return false;
        }
    }//function validateToken
}//class nAuthJWT