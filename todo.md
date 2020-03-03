#TODO


## Upgrade to LTI v1.3

Use [https://github.com/IMSGlobal/lti-1-3-php-library] 

Add the following to your composer.json file
```
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/IMSGlobal/lti-1-3-php-library"
    }
],
"require": {
    "imsglobal/lti-1p3-tool": "dev-master"
}
```
Run composer install or composer update In your code, you will now be able to use classes in the \IMSGlobal\LTI namespace to access the library.






Moodle Test #1:
```
    Platform ID: https://mifsudm.unimelb.edu.au/Test/moodle
    Client ID: v23kN0NgpIoRgEW
    Deployment ID: 4
    Public keyset URL: https://mifsudm.unimelb.edu.au/Test/moodle/mod/lti/certs.php
    Access token URL: https://mifsudm.unimelb.edu.au/Test/moodle/mod/lti/token.php
    Authentication request URL: https://mifsudm.unimelb.edu.au/Test/moodle/mod/lti/auth.php
```

```json
{
    "https://mifsudm.unimelb.edu.au/Test/moodle" : { // This will usually look something like 'http://example.com'
        "client_id" : "v23kN0NgpIoRgEW", // This is the id received in the 'aud' during a launch
        "auth_login_url" : "https://mifsudm.unimelb.edu.au/Test/moodle/mod/lti/auth.php", // The platform's OIDC login endpoint
        "auth_token_url" : "https://mifsudm.unimelb.edu.au/Test/moodle/mod/lti/token.php", // The platform's service authorization endpoint
        "key_set_url" : "https://mifsudm.unimelb.edu.au/Test/moodle/mod/lti/certs.php", // The platform's JWKS endpoint
        "private_key_file" : "/key/tkuni.key", // Relative path to the tool's private key
        "deployment" : [
            "4" // The deployment_id passed by the platform during launch
        ]
    }
}
```




How to generate JWT RS256 key and JWKS:

    ssh-keygen -t rsa -b 4096 -m PEM -f tkuni.key
    # Don't add passphrase
    openssl rsa -in tkuni.key -pubout -outform PEM -out tkuni.key.pub
    cat tkuni.key
    cat tkuni.key.pub

```php
    $config = array(
        "digest_alg" => "sha512",
        "private_key_bits" => 4096,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
    );
    // Create the private and public key
    $res = openssl_pkey_new($config);
    // Extract the private key from $res to $privKey
    openssl_pkey_export($res, $privKey);
    // Extract the public key from $res to $pubKey
    $pubKey = openssl_pkey_get_details($res);
    $pubKey = $pubKey["key"];

//  ...
    $data = 'plaintext data goes here';
    // Encrypt the data to $encrypted using the public key
    openssl_public_encrypt($data, $encrypted, $pubKey);
    // Decrypt the data using the private key and store the results in $decrypted
    openssl_private_decrypt($encrypted, $decrypted, $privKey);
    
    echo $decrypted;
```






## Links

  - https://docs.moodle.org/dev/LTI_1.3_support
  - https://github.com/IMSGlobal/lti-1-3-php-library
  - https://github.com/IMSGlobal/lti-1-3-php-example-tool
  - http://www.imsglobal.org/spec/lti/v1p3/impl
  - https://github.com/dmitry-viskov/pylti1.3/wiki/Configure-Canvas-as-LTI-1.3-Platform
  - https://github.com/dmitry-viskov/pylti1.3/wiki/How-to-generate-JWT-RS256-key-and-JWKS





