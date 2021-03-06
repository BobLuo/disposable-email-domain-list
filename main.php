<?php

// Retrieve full domain list from sources
$listA = file_get_contents('https://raw.githubusercontent.com/ivolo/disposable-email-domains/master/index.json');
$listB = file_get_contents('https://raw.githubusercontent.com/ivolo/disposable-email-domains/master/wildcard.json');
$listC = file_get_contents('https://gist.githubusercontent.com/michenriksen/8710649/raw/e09ee253960ec1ff0add4f92b62616ebbe24ab87/disposable-email-provider-domains');
$listD = file_get_contents('https://raw.githubusercontent.com/martenson/disposable-email-domains/master/disposable_email_blocklist.conf');
$listE = file_get_contents('https://github.com/GeroldSetz/emailondeck.com-domains/raw/master/emailondeck.com_domains_from_bdea.cc.txt');
$list = $listA . $listB . $listC . $listD . $listE;

// Clean format
$list = str_replace(array(',', '"', '[', ']', ' '), '' , $list);

// Explode
$list = explode("\n", $list);

// Remove duplicates
$list = array_unique($list);

// Sort
usort($list, "strcasecmp");

// Implode
$list = implode("\n",$list);

// Remove empty lines
$list = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $list);

// Overwrite all unvalidated domains to data/all-domains.txt
file_put_contents('domains-unvalidated.txt', $list);

// Empty domains.txt
file_put_contents('domains.txt', "");


// Validate domains

    function mxrecordValidate($domain) {
        $arr = dns_get_record($domain, DNS_MX);
        if ($arr[0]['host'] == $domain && !empty($arr[0]['target'])) {
            return $arr[0]['target'];
        }
    }

    $file = fopen("domains-unvalidated.txt", "r");
    $i=0;

    while(! feof($file))
    {
        $domain = fgets($file); // Read one line
        $domain = str_replace(array("\n", "\r"), '', $domain);

        if (mxrecordValidate($domain)) {
            // This MX records exists. Valid Email Domain.
            $outputline = $domain."\n";
        } else {
            // No MX record exists. Invalid Email Domain.
            $outputline ="";
        }
        echo $outputline;
        $outputfile="domains.txt";
        $handle=fopen($outputfile,"a+");

        $str=fwrite($handle, $outputline);
        fclose($handle);

        $i++;

    }

    fclose($file);

// Delete domains-unvalidated.txt

    unlink('domains-unvalidated.txt');


// Remove whitelisted domains

    function encode($file) {
        $lines = file($file);
        $handle = fopen($file, 'w+');
        foreach ($lines as $line)
        {
            if ($line!="") {
                fwrite($handle, base64_encode(trim($line)) . "\n");
            }
        }
        fclose($handle);
    }

    function decode($file) {
        $lines = file($file);
        $handle = fopen($file, 'w+');
        foreach ($lines as $line)
        {
            if ($line!="") {
                fwrite($handle, base64_decode(trim($line))."\n");
            }
        }
        fclose($handle);
    }

    encode('domains.txt');
    encode('whitelist.txt');

    $whitelist = file_get_contents("whitelist.txt") or die("Cannot read file");
    $whitelist=preg_replace("/[\n\r]/","|",$whitelist);
    $whitelist=preg_replace("/[=]/","",$whitelist);

    // Remove whitelist items
    $list = file_get_contents("domains.txt") or die("Cannot read file");
    $list = preg_replace("/\b($whitelist)\b/i", "", $list);

    // Remove empty lines in domains.txt
    $list = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $list);

    file_put_contents("domains.txt", $list);

    decode('domains.txt');
    decode('whitelist.txt');

// Convert domains.txt into domains.json

    header('Content-type: application/json');

    // get the contents of file in array
    $conents_arr   = file('domains.txt',FILE_IGNORE_NEW_LINES);
    foreach($conents_arr as $key=>$value)
    {
        $conents_arr[$key]  = rtrim($value, "\r");
    }
    var_dump($conents_arr);
    $json_contents = json_encode($conents_arr);

    // echo $json_contents;
    file_put_contents('domains.json', $json_contents);

?>