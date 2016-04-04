# nAuth by theWebDevNick
A super-simple PHP library for creating and using JSON Web Tokens (JWT).

## Installation
Copy the nAuthJWT.php file into your project, wherever you happen to store library files probably.
## Usage
### Create a new JWT, such as during login
Simply create a new instance of the JWT class:
>$myToken = new nAuthJWT(null, $timeToExpiration, $encryption, $privateKey);

Where the first argument is null (it is used later but not here) $timeToExpiration is a time-span string (such as "+30 Minutes") representing the time from now until the token expires, $encryption is a string representing the hashing algorithm to use ('HS256', 'HS384', or 'HS512'), and $privateKey is a secret string that you specify. $privateKey is used for hashing and security protection.

To add data to your token, use the pushPayload() method:
>$myToken->pushPayload($key, $value);

Where $key is an identifier for your data and $value is the actual data. $value can be anything you want (even null) and $key should be a 3-character string (for brevity) but it can be a string of any length/value except for 'iat' or 'exp'.
You can call the pushPayload() method as many times as necessary, but try not to store too much data here.

To use the token, you must sign it and then get the returned JWT string:
>$myToken->sign();//signs the token
>$myJWT = $myToken->getTokenString();//gets the string representing the token

You can now use the token string however you please, typically it would be sent client side at this point.

### To use an existing JWT, to extract data or verify authentication
Once you've created a JWT and have a string, you need some way to verify it.
To do this, create an instance of nAuthJWT using a token string in place of the earlier arguments
>$myToken = new nAuthJWT($myJWT, null,null,$privateKey);

Where $myJWT is a string (representing the token you created) and $privateKey is the private key you used to create the token.

If the token is valid, the instance will be initialized using the data from the token's payload. If it is invalid, all values will be set to null except for isValid which will be set to false and can be used for checking if the token is valid.
>if ($myToken->isValid){_statements_}

Assuming the token is valid, you can use the payload array to access your keyed items:
>$myData = $mytoken->$payload[$key];

$myToken->payload is just a regular array, so you can treat this variable as you would any other array in PHP.

From here, you can use any of the following methods:

Push more data onto the JWT payload
>$myToken->pushPayload($key, $value);

If the $key already exists, then it will be replaced with the new $value. $value can be anything you want (even null) and $key should be a 3-character string (for brevity) but it can be a string of any length/value except for 'iat' or 'exp'.You can call the pushPayload() method as many times as necessary, but try not to store too much data here.

Refresh the expiration of your token:
>$myToken->refreshToken($expiration);

Where $expiration is your new time until expiration (such as '+30 Minutes').

You can even re-sign and get the token string, same as above. You will always have to sign the token before you get the token string.
>$myToken->sign();//signs the token
>$myJWT = $myToken->getTokenString();//gets the string representing the token

You can now use the token string however you please, typically it would be sent client side at this point, same as above.


Happy Coding!