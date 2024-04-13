#! /usr/bin/perl
use Crypt::CBC;
$cipher = Crypt::CBC->new( {'key' => 'my secret key',
'cipher' => 'Blowfish',
'iv' => '12kak234',
'regenerate_key' => 0, # default true
'padding' => 'space',
'prepend_iv' => 0
});

if($ARGV[1] eq 'encode'){
        $passwd = $cipher->encrypt_hex($ARGV[0]);
}
elsif($ARGV[1] eq 'decode'){
        $passwd = $cipher->decrypt_hex($ARGV[0]);
}
else{
        print "invalid\n";
}
print $passwd;
