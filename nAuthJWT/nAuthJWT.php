<?php

/**
 * Class nAuthJWT
 */
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

    /**
     * nAuthJWT constructor.
     * @param null $tokenString
     * @param null $expiration
     * @param null $encryption
     * @param $privateKey
     * @throws Exception
     */
    public function __construct($tokenString=null,$expiration=null,$encryption=null, $privateKey)
    {
        if ($privateKey!=null) {
            $this->securityKey=$privateKey;
            if ($tokenString == null) {
                $this->payload["iat"] = gmdate("Y/m/d h:i:s A");
                if (strtotime($expiration)) {
                    $this->payload["exp"] = gmdate("Y/m/d h:i:s A", strtotime($expiration));
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
                    $this->header = (array)json_decode($this->base64url_decode($components[0]));
                    $this->payload = (array)json_decode($this->base64url_decode($components[1]));
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

    /**
     * @param $key string representing t
     * @param $value
     * @throws Exception
     */
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

    /**
     * @throws Exception
     */
    public function sign()
    {
        if(($this->isValid))
        {
            $this->eHeader = $this->base64url_encode(json_encode($this->header));
            $this->ePayload = $this->base64url_encode(json_encode($this->payload));
            $signature = $this->eHeader . "." . $this->ePayload;
            $this->eSignature  = hash_hmac($this->eAlg, $signature, $this->securityKey, false);
        }//if
        else
        {
            throw new Exception("nAuth Exception: Cannot sign an invalid JWT. This JWT was created from a token string that did not pass the validation keys.");
        }//else
    }//function sign()

    /**
     * @return string
     * @throws Exception
     */
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

    /**
     * @param $expiration
     * @throws Exception
     */
    public function refreshToken($expiration)
    {
        if (strtotime($expiration))
        {
            $this->payload["exp"] = gmdate("Y/m/d h:i:s A", strtotime($expiration));
        }
        else
        {
            throw new Exception("nAuth Exception: Invalid value provided for expiration.");
        }
    }

    /**
     * @param $encryption
     * @return bool|string
     */
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

    /**
     * @param $token
     * @return array|bool
     */
    private function validateToken($token)
    {
        $components = explode(".",$token);
        if (count($components)==3)
        {
            $signature = $components[0] . "." . $components[1];
            $head =json_decode($this->base64url_decode($components[0]));
            $eAlg = $this->getAlgorithm($head->alg);
            $signature =hash_hmac($eAlg, $signature, $this->securityKey, false);
            if ($signature == $components[2])
            {
                //signature is good, now check expiration
                if ($this->notExpired((array)json_decode($this->base64url_decode($components[1]))))
                {
                    return $components;
                }
                else
                {
                    return false;
                }
            }
            else
            {
                //signature no bueno
                return false;
            }
        }//if
        else
        {
            return false;
        }
    }//function validateToken

    /**
     * @param $data
     * @return string
     */
    private function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * @param $data
     * @return string
     */
    private function base64url_decode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }

    /**
     * @param $payload
     * @return bool
     */
    private function notExpired($payload)
    {
        if (isset($payload["exp"])) {
            $expiration = strtotime($payload["exp"]);
            if (time()-$expiration < 0)
            {
                return true;
            }
            else
            {
                return false;
            }

        }
        else
        {
            //no expiration, so it is considered insecure
            return false;
        }
    }
}//class nAuthJWT