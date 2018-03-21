# Gateway di autenticazione Federa Spid - Comune di Rimini

Il gateway di autenticazione permette di implementare un livello di astrazione fra le applicazioni (SP) e l'implementazione dell'interfacciamento con il provider di identità digitali (IdP).

Il gateway si occuperà di gestire tutte le interazioni SAML con l'Idp e di restituire all' SP solo i dati dell'eventuale utente autenticato.

La sicurezza tra il SP ed il Gateway è garantita da una comunicazione criptata basata sullo standard PKCS (https://en.wikipedia.org/wiki/PKCS)

Il gateway è stato sviluppato in linguaggio PHP.


![Grafico](https://g.gravizo.com/svg?@startuml;participant%20%22App01%22%20as%20A;participant%20%22Gateway%22%20as%20B;participant%20%22FEDERA/SPID%22%20as%20C;A%20-%3E%20B:%20Request%20Encrypted;activate%20B;B%20-%3E%20C:%20SAML%20Request;activate%20C;C%20--%3E%20B:%20SAML%20Response;deactivate%20C;B%20--%3E%20A:%20Response%20Encrypted;deactivate%20B;@enduml)

### Librerie utilizzate

- [OneLogin's SAML PHP Toolkit](https://github.com/onelogin/php-saml)
- [Base64UrlSafe](https://github.com/Spomky-Labs/base64url)
- [Monolog](https://github.com/Seldaek/monolog)

### Installazione e configurazione Gateway

Da ora in avanti indicherò come *wwwroot* la cartella di installazione del gateway

#### Verificare la versione PHP. La versione deve essere > 5.5.x

```
php -v
```

#### Installazione del gateway tramite git

```
git clone https://github.com/paulodiff/GatewayFederaSpid.git
```

#### Installare le librerie con [composer](https://getcomposer.org/)

```
php composer-setup.php install
```

#### Generare i certificati per il gateway

Creare una cartella non accessibile dal server web e generare i certificati con il seguente comando
	
```
openssl req -new -x509 -days 3652 -nodes -out gw_public.crt -keyout gw_private.pem
```

#### Configurazione SAML

Nella cartella *wwwroot/vendor/onelogin/php-saml/* copiare settings_example.php in settings.php

> Configurare la parte sp (service provider)

```
'entityId' : url gateway in fase di registrazione
'assertionConsumerService'->'url' : url della pagina di risposta del gateway

...

'singleLogoutService' -> 'url' : url della pagina di logout
```

copiare in forma di stringa i certificati generati per il gateway

```
'x509cert' => 'MII...hDM=', // gw_public.crt
'privateKey' => 'MII ...qug==', // gw_private.pem
```

> Sezione di configurazione sp completa:

```
// Service Provider Data that we are deploying
    'sp' => array (
        // Identifier of the SP entity  (must be a URI)
        'entityId' => 'https://GATEWAY_URL',
        // Specifies info about where and how the <AuthnResponse> message MUST be
        // returned to the requester, in this case our SP.
        'assertionConsumerService' => array (
            // URL Location where the <Response> from the IdP will be returned
            'url' => 'https://GATEWAY_URL/response.php',
            // SAML protocol binding to be used when returning the <Response>
            // message.  Onelogin Toolkit supports for this endpoint the
            // HTTP-Redirect binding only
            'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
        ),
        // If you need to specify requested attributes, set a
        // attributeConsumingService. nameFormat, attributeValue and
        // friendlyName can be omitted. Otherwise remove this section.

        //"attributeConsumingService"=> array(
        //        "ServiceName" => "SP test",
        //        "serviceDescription" => "Test Service",
        //        "requestedAttributes" => array(
        //            array(
        //                "name" => "",
        //                "isRequired" => false,
        //                "nameFormat" => "",
        //                "friendlyName" => "",
        //                "attributeValue" => ""
        //            )
        //        )
        //),

        // Specifies info about where and how the <Logout Response> message MUST be
        // returned to the requester, in this case our SP.
        'singleLogoutService' => array (
            // URL Location where the <Response> from the IdP will be returned
            'url' => 'https://GATEWAY_URL/logout.php',
            // SAML protocol binding to be used when returning the <Response>
            // message.  Onelogin Toolkit supports for this endpoint the
            // HTTP-Redirect binding only
            'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
        ),
        // Specifies constraints on the name identifier to be used to
        // represent the requested subject.
        // Take a look on lib/Saml2/Constants.php to see the NameIdFormat supported
        'NameIDFormat' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified',

        // Usually x509cert and privateKey of the SP are provided by files placed at
        // the certs folder. But we can also provide them with the following parameters
        'x509cert' => 'MII...hDM=', // gw_public.crt
        'privateKey' => 'MII ...qug==', // gw_private.pem

        /*
         * Key rollover
         * If you plan to update the SP x509cert and privateKey
         * you can define here the new x509cert and it will be 
         * published on the SP metadata so Identity Providers can
         * read them and get ready for rollover.
         */
        // 'x509certNew' => '',
    ),

```

> Configurare la parte Idp

I dati sono già pronti per FEDERA TEST

```
// Identity Provider Data that we want connect with our SP
    'idp' => array (
        // Identifier of the IdP entity  (must be a URI)
        'entityId' => 'https://federatest.lepida.it/gw/metadata',
        // SSO endpoint info of the IdP. (Authentication Request protocol)
        'singleSignOnService' => array (
            // URL Target of the IdP where the SP will send the Authentication Request Message
            'url' => 'https://federatest.lepida.it/gw/SSOProxy/SAML2',
            // SAML protocol binding to be used when returning the <Response>
            // message.  Onelogin Toolkit supports for this endpoint the
            // HTTP-POST binding only ???
            'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
        ),
        // SLO endpoint info of the IdP.
        'singleLogoutService' => array (
            // URL Location of the IdP where the SP will send the SLO Request
            'url' => '',
            // SAML protocol binding to be used when returning the <Response>
            // message.  Onelogin Toolkit supports for this endpoint the
            // HTTP-Redirect binding only
            'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
        ),
        // Public x509 certificate of the IdP
        'x509cert' => 'MIIDJDCCAgygAwIBAgIVAIq/MUgxPKO0cuX/GtD7YUvk87GtMA0GCSqGSIb3DQEBBQUAMBkxFzAVBgNVBAMTDmlkcC5tYWNoaW5lLml0MB4XDTA5MDMyNTEwNTM1OFoXDTI5MDMyNTA5NTM1OFowGTEXMBUGA1UEAxMOaWRwLm1hY2hpbmUuaXQwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQClXV18x0/yhZ+D3pHlmhrK4paA+xdJKAT7U7R9DeaTQygwtCjKmCrJbzdohckLz5pax7eaGeA53pPCY+JdiU0Uq4ES8nG2DCZgCtl4QGLUcTuUtJdPq+DbYD1cWBwEeeffsiClVyuhgLRPO1OQLl/TJp4slfoYTi0aONgQp03uG+ixL48myL7GrINHYXtDUDqo2BimyU0yrOe6ZmvxJchZ8nBuWKy0J8wsO/Mnasbvo79/c8gcn0HTst0QDlHXQlzwZ4Suq2os9qKjXAYOzA1VqmTyzJIge/ynHiJ0Fkw0HNxBaVFTJRNL8RvwJsMuBT7YZKRoNK7gjT5/6bGagYM/AgMBAAGjYzBhMEAGA1UdEQQ5MDeCDmlkcC5tYWNoaW5lLml0hiVodHRwczovL2lkcC5tYWNoaW5lLml0L2lkcC9zaGliYm9sZXRoMB0GA1UdDgQWBBSBOsPZiWZRXFqNINIguHfv7jnidDANBgkqhkiG9w0BAQUFAAOCAQEAeVLN9jczRINuPUvpXbgibL2c99dUReMcl47nSVtYeYEBkPPZrSz0h3AyVZyar2Vo+/fC3fRNmaOJvfiVSm+bo1069iROI1+dGGq2gAwWuQI1q0F7PNPX4zooY+LbZI0oUhuoyH81xed0WtMlpJ1aRSBMpR6oV3rguAkH6pdr725yv6m5WxKcOM/LzdD5Xt9fQRL7ino4HfiPPJNDG3UOKhoAWkVn/Y/CuMLcBPWh/3LxIv4A1bQbnkpdty+Qtwfp4QUKkisv7gufQP91aLqUvvRE6Uz8r51VH13e4mEJjJGxLKXWzlP50gp7b27AXCTKSS6fW6iBpfA14PGcWvDiPQ==',
        /*
         *  Instead of use the whole x509cert you can use a fingerprint in
         *  order to validate the SAMLResponse, but we don't recommend to use
         *  that method on production since is exploitable by a collision
         *  attack.
         *  (openssl x509 -noout -fingerprint -in "idp.crt" to generate it,
         *   or add for example the -sha256 , -sha384 or -sha512 parameter)
         *
         *  If a fingerprint is provided, then the certFingerprintAlgorithm is required in order to
         *  let the toolkit know which Algorithm was used. Possible values: sha1, sha256, sha384 or sha512
         *  'sha1' is the default value.
         */
        // 'certFingerprint' => '',
        // 'certFingerprintAlgorithm' => 'sha1',

        /* In some scenarios the IdP uses different certificates for
         * signing/encryption, or is under key rollover phase and more 
         * than one certificate is published on IdP metadata.
         * In order to handle that the toolkit offers that parameter.
         * (when used, 'x509cert' and 'certFingerprint' values are
         * ignored).
         */
        // 'x509certMulti' => array(
        //      'signing' => array(
        //          0 => '<cert1-string>',
        //      ),
        //      'encryption' => array(
        //          0 => '<cert2-string>',
        //      )
        // ),
    ),

```
Nella cartella *wwwroot/vendor/onelogin/php-saml/* copiare il file *advanced_settings_example.php* in *advanced_settings.php*

la voce *signatureAlgorithm* va impostata come segue anche se non è consigliato:

```
'signatureAlgorithm' => 'http://www.w3.org/2000/09/xmldsig#rsa-sha1',
```

#### PATCH saml:Advice per libreria SAML
Le asserzioni multiple in risposta non sono gestite dalla librerie e per questo è necessario applicare una patch qui descritta :

https://github.com/onelogin/php-saml/pull/113/commits/d99a2bcfb7866a3bca2d9b9eaab130ac0fd0abda

Bisogna modificare il file *vendor/onelogin/php-saml/lib/Saml2/Response.php* nella *function validateNumAssertions*  

in questo modo:

```
/**
     * Verifies that the document only contains a single Assertion (encrypted or not).
     *
     * @return bool TRUE if the document passes.
     */
    public function validateNumAssertions()
    {


	// PATCH ADVICE
	// https://github.com/onelogin/php-saml/pull/113/commits/d99a2bcfb7866a3bca2d9b9eaab130ac0fd0abda

	/********
        $encryptedAssertionNodes = $this->document->getElementsByTagName('EncryptedAssertion');
        $assertionNodes = $this->document->getElementsByTagName('Assertion');

        $valid = $assertionNodes->length + $encryptedAssertionNodes->length == 1;

        if ($this->encrypted) {
            $assertionNodes = $this->decryptedDocument->getElementsByTagName('Assertion');
            $valid = $valid && $assertionNodes->length == 1;
        }

        return $valid;
	*/

	$encryptedAssertionNodesLength = 0;
	$assertionNodesLength = 0;

        foreach ($this->document->getElementsByTagName('EncryptedAssertion') as $node) {
            if($node->parentNode->localName !== 'Advice') {
                $encryptedAssertionNodesLength++;
            }
        }

        foreach ($this->document->getElementsByTagName('Assertion') as $node) {
            if($node->parentNode->localName !== 'Advice') {
                $assertionNodesLength++;
            }
        }

        return ($assertionNodesLength + $encryptedAssertionNodesLength == 1);

    }

```

#### File di configurazione e LOG

Creare la cartella nella *wwroot/config* e creare il file config.php in questo modo:

```
<?php
	$DEBUG_GATEWAY = true; // abilita il debug
	$LOG_FILE = 'PATH_TO/gw.log'; // cartella dove sono presenti i certificati di integrazione dei client
	$CERT_PATH = 'PATH_TO/certs/';
?>
```

E' necessario inpostare il parametro in settings.php per la configurazione dei LOG

```
$LOG_FILE = 'PATH_TO/gw.log';
```

#### Verifica del file METADATA

A questo punto è possibile verificare il file metadati generato accedendo alla seguente url :
*https://GATEWAY_URL/metadata.php*


#### Richiedere l'integrazione a FEDERA

Il gateway è configurato è possibile richiedere l'integrazione a FEDERA.

#### Configurazione per la produzione e personalizzazione

- Disabilitare il DEBUG
- Impostare eventuale dati in *index.php* (home page gateway)

### Configurazione del Client per l'integrazione con il gateway

Per accedere alle funzionalità del gateway le richieste del client devono essere autenticate. Il metodo di sicurezza utilizzato è quello a chiave pubblica/privata (PKI). Viene generata sul gateway una coppia di chiavi; la chiave privata viene consegnata al client per cifrare le richieste di autenticazione e la chiave pubblica viene consegnata al server per decifrare le richieste e cifrare le risposte di autenticazione.

#### Modalità di integrazione fra client e gateway

Ipotizziamo di dover integrare un servizio applicativo che denomineremo app01.

- Generiamo una nuova coppia di chiavi 

> ATTENZIONE AL NOME DEL FILE DELLE CHIAVI, DEVE ESSERE IDENTICO AL NOME DEL SERVIZIO CHE VIENE INTEGRATO

```
openssl req -new -x509 -days 3652 -nodes -out app01.crt -keyout app01.pem
```

La chiave *app01.pem* viene consegnata la client

La chiave *app01.crt* viene memorizzata nella cartella indicata da:

```	
$CERT_PATH = 'PATH_TO/certs/';
```

del file *wwwroot/config.php*

#### Modalità di richiesta di autenticazione client

Per poter effettuare una richiesta di autenticazione al gateway il client deve inviare una richiesta GET alla pagina *auth.php* del gateway con i seguenti parametri:

```
nomeServizioIntegrazione=PARAMETRI_CIFRATI
```

Ad esempio per il servizio precedentemente integrato la url di richiesta sarà:

```
https://GATEWAY_URL?auth.php?app01=CgN....Yq
```

I parametro che è necessario inviare è una stringa di testo cifrata così composta:

```
timestamp;url_di_risposta_autenticazione
```

Il codice PHP per la generazione del parametro:

```
$landingPage = "http://app01/auth-landing.php";
$ts = date("YmdHis", time() - date("Z"));

$fp=fopen("app01.pem","r");
$private_key_string=fread($fp,8192);
fclose($fp);

openssl_private_encrypt($ts . ";" . $landingPage,$ts_crypted,$private_key_string);

$b64_ts_crypted =  Base64Url::encode($ts_crypted);

$gwURL = "https://GATEWAY_URL?auth.php?app01=" . $b64_ts_crypted;
```					


#### Client modalità di gestione della risposta di autenticazione

Una volta richiamato il gateway, l'autenticazione procederà verso FEDERA o SPID a seconda della scelta e terminerà verso la url del client valorizzata nei parametri di richiesta.

Seguendo l'esempio di app01, ad autenticazione termina il gateway invierà una risposta al service provider come qui di seguito:

```
http://app01/auth-landing.php?authenticatedUser=r5nb4.....W0CppJ
```

Il parametro authenticatedUser è cifrato con il certificato pubblico del client e contiene una stringa con le informazioni dell'utente che si è autenticato.
Il formato della stringa con i dati è in questo formato:

```
authenticationMethod;authenticatingAuthority;policyLevel;trustLevel;userid;CodiceFiscale;nome;cognome;dataNascita;luogoNascita;statoNascita
```

Il codice PHP per decifrare il parametro *authenticatedUser*:

```
$authenticatedUser = $_GET['authenticatedUser'];
$authenticatedUser_decoded =  Base64Url::decode($authenticatedUser);

$fp=fopen("app01.pem","r");
$private_key_string=fread($fp,8192);
fclose($fp);

openssl_private_decrypt($authenticatedUser_decoded, $authenticatedUser_decrypted, $private_key_string)

$authenticatedDataArray = explode(";", $authenticatedUser_decrypted);
```

### Link alla normativa

http://www.agid.gov.it/sites/default/files/documentazione/spid-avviso-n6-note-sul-dispiegamento-di-spid-presso-i-gestori-di-servizi-v1.pdf


### Alcune note sulla sicurezza

- verifica del ts
- firewall che permette connessioni solo da origini autorizzate

## Informazioni

Ruggero Ruggeri - Comune di Rimini - ruggero.ruggeri@comune.rimini.it - 0541 704607