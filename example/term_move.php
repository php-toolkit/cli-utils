<?php

class TermMove {

    const KEYS = [
        10  => 'enter',
        127 => 'backspace',
        65  => 'up',
        66  => 'down',
        67  => 'right',
        68  => 'left',
        9   => 'tab'
    ];

    public function readChar() : string
    {
        $c = $this->char();

        var_dump($c);
        $last = '';
        foreach (str_split($c) as $one) {
            var_dump($one . ' => num: ' . ord($one));

            $last = $one;
        }

        // var_dump($c);
        if (ctype_print($last)) {
            return $last;
        }

        $n = ord($last);
        if (
            array_key_exists($n, static::KEYS)
            && in_array(static::KEYS[$n], ['enter', 'backspace'])
        ) {
            return static::KEYS[$n];
        }

        return '';
    }

    public function char() : string
    {
        // return fread(STDIN, 4);

        if (DIRECTORY_SEPARATOR === "\\") {
            $c = stream_get_contents(STDIN, 1);

            return $c;
        }

        readline_callback_handler_install('', function() {});
        // $c = $this->read(1);
        // $c = fread(STDIN, 4);
        $c = stream_get_contents(STDIN, 4);
        // $c = readline();
        readline_callback_handler_remove();
        return $c;
    }
}

require dirname(__DIR__) . '/test/bootstrap.php';

$tm = new TermMove();

echo "please input:";
$c = $tm->readChar();
echo "CHAR: $c\n";
