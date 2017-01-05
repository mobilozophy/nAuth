# NAuthJWT by theWebDevNick
A super-simple PHP library for creating and using JSON Web Tokens (JWT).

## Installation
This class can be installed through composer or by cloning this repository.

## Usage
###To create a new JWT for sending a request
First, create a new instance of the JWT class:

```php
$myToken = new nAuthJWT(null, $timeToExpiration, $encryption, $privateKey);
```

Where the first argument is null (it is used for instantiating from an existing token string) $timeToExpiration is a time-span string (such as "+30 Minutes") representing the time from now until the token expires, $encryption is a string representing the hashing algorithm to use ('HS256', 'HS384', or 'HS512'), and $privateKey is a secret string that you specify. $privateKey is used for hashing and security protection.

To add data to your token, use the pushPayload() method:
```php
$myToken->pushPayload($key, $value);
```

Where $key is the identifier for your data and $value is the actual data. $value can be anything you want (even null) and $key should be a 3-character string (for brevity) but it can be a string of any length/value except for 'iat' or 'exp'.
You can call the pushPayload() method as many times as necessary, but try not to store too much data here.

To use the token, you must sign it and then get the returned JWT string:

```php
$myToken->sign();//signs the token
$myJWT = $myToken->getTokenString();//gets the string representing the token
```
It is important to sign the token before you send the string, but after adding your payload, as the payload is used to generate the signature.
The result, $myJWT, is complete JSON web token that can be used for authentication requests. this value is not base-64 encoded, so you may have to complete that step depending on the design of your system.

### To validate an existing token
To validate an existing token, you must create a new instance of the NAuthJWT class providing the tokenString parameter and the privateKey parameter.

```php
$myToken = new nAuthJWT($myJWT, null,null,$privateKey);
```

Where $myJWT is a string (representing the token you created) and $privateKey is the private key used to sign the token.

If the token is valid, the class will be initialized using the data from the token's payload. If it is invalid, all values will be set to null except for isValid, which will be set to false and can be used for checking if the token is valid.
```php
if ($myToken->isValid){
    ...
}
```
Assuming the token is valid, you can use the payload array to access your keyed items:

```php
$myData = $mytoken->$payload[$key];
//or you can loop through the payload
foreach ($mytoken->$payload as $key=>$value)
{
    ...
}

```
$myToken->payload is just a regular array, so you can treat this variable as you would any other array in PHP.

From here, you can use any of the following methods:

Push more data onto the JWT payload

```php
$myToken->pushPayload($key, $value);
```

If the $key already exists, then it will be replaced with the new $value. $value can be anything you want (even null) and $key should be a 3-character string (for brevity) but it can be a string of any length/value except for 'iat' or 'exp'.You can call the pushPayload() method as many times as necessary, but try not to store too much data here.

Refresh the expiration of your token:
```php
$myToken->refreshToken($expiration);
```

Where $expiration is your new time until expiration (such as '+30 Minutes').

You can even re-sign and get the token string, same as above. You will always have to sign the token before you get the token string.

```php
$myToken->sign();//signs the token
$myJWT = $myToken->getTokenString();//gets the string representing the token
```
You can now use the token string however you please, typically it would be sent client side at this point, same as above.


