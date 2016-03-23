<?php
//regenerates the privateKey
$newKey=md5(openssl_random_pseudo_bytes(rand()));//overkill probably.
file_put_contents("privateKey.txt",$newKey);
echo "Done";