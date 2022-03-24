<?php

# Bibliothek über Composer eingebunden
# https://github.com/Spomky-Labs/otphp
# autoload.php wegen composer muss included sein

use OTPHP\TOTP;
include_once(__DIR__.'/vendor/autoload.php');

# echo text
# Beim registrieren, einen Benutzer angeben!
# Beim einloggen, Benutzer und den code angeben!
# es wird ein formular mittels echo erstellt

echo "<h1>Beim registrieren nur Benutzernamen angeben, beim einloggen bitte beides angeben!</h1><br>";
echo "<form action='index.php' method='POST'><input type='text' name='name' placeholder='Einen Namen eingeben...'><input type='text' name='code' placeholder='CODE..'><input type='submit' value='Action!'></form>";

# Wenn Benutzername und der Code existiert beim submitten, wird gechecked ob der User schon registriert ist, wenn ja dann printen wir ein echo mit "User gibts schon" aus. (Beim registrieren geben wir nämlich noch keinen Code an)
if (array_key_exists('name', $_POST) && empty($_POST['code'])){ // register
    if (checkUser($_POST['name'])){
        echo "User gibts schon, gib den Code an!";
    }else{
        # hier erstellen wir einen neuen otp secret
        $otp = TOTP::create();
        $secret = $otp->getSecret();
        # speichern den user in die secrets.txt
        saveUser($_POST['name'], $secret);
        # setzen das label für den code (wird z.B. im Authenticator angezeigt)
        $otp->setLabel('Mein Label!');
        # erstellen und printen einen QR Code aus, fürs einfache scannen
        $grCodeUri = $otp->getQrCodeUri(
            'https://api.qrserver.com/v1/create-qr-code/?data=[DATA]&size=300x300&ecc=M',
            '[DATA]'
        );
        echo "<p>$secret</p>";
        echo "<img src='{$grCodeUri}' alt='QRCode'>";
    }
}

# Wenn Benutzer und Code angegeben und der User existiert, verify'n wir diesen und sagen wir sind "authentifiziert"
# Wenn der Code falsch ist printen wir das auch aus, wenns den User nicht gibt gibt man eine Info aus.
if (array_key_exists('name', $_POST) && ($_POST['code'] != null)){
    if (checkUser($_POST['name'])){
        $code = $_POST['code'];
        # wichtig: wir nehmen den secret string und createn damit den otp und verify'n damit den übergebenen code
        $loaded_otp = TOTP::create(load_user_secret($_POST['name']));
        if ($loaded_otp->verify($code)){
            echo "Erfolgreich authentifiziert!";
        }else{
            echo "Falscher Code!";
        }
    }else{
        echo "Den user mit dem Code gibts nicht, lasse code leer zum registrieren!";
    }
}

# Funktion um einen user abzuspeichern, Filezugriff und schreiben user:code rein
function saveUser($user, $otp){
    $file = fopen("secrets.txt", "a");
    fwrite($file, PHP_EOL.$user.":".$otp);
    fclose($file);
}

# Function um den user zu checken, gibt es ihn? Wenn nicht returnen wir false, sonst true
# Gehen damit die file zeile für zeile durch und checken ob er existiert
function checkUser($user): bool{
    $login_data = file( "secrets.txt", FILE_IGNORE_NEW_LINES );
    foreach ($login_data as $userdata){
        $name = explode(":", $userdata)[0];
        if ($name === $user){
            return true;
        }
    }
    return false;
}

# load_user_secret gibt uns den code zurück (falls existiert) sonst null (im code wird vorher noch checkuser ausgeführt, daher kommt es eigentlich nicht zum null)
function load_user_secret($user){
    $login_data = file( "secrets.txt", FILE_IGNORE_NEW_LINES );
    foreach ($login_data as $userdata){
        $name = explode(":", $userdata)[0];
        if ($name === $user){
            return explode(":", $userdata)[1];
        }
    }
    return null;
}

?>
