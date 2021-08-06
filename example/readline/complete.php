<?php
/**
 * @link https://www.php.net/manual/zh/function.readline-completion-function.php
 */

require dirname(__DIR__) . '/../test/bootstrap.php';

function your_callback($input, $index): array
{
    // Get info about the current buffer
    /*
    string(3) "efg" // last words
    int(4)
    array(6) {
      ["line_buffer"]=> string(7) "adb efg" // full line
      ["point"]=> int(7)
      ["end"]=> int(7)
      ["library_version"]=> string(16) "EditLine wrapper"
      ["readline_name"]=> string(0) ""
      ["attempted_completion_over"]=> int(0)
    }

     */
    $rlInfo = readline_info();

    // Figure out what the entire input is
    $full_input = substr($rlInfo['line_buffer'], 0, $rlInfo['end']);

    // vdump($input, $index, $full_input, $rlInfo);
    // vdump($input, $index, $rlInfo);

    $samples = [
        'abc',
        'tom',
        'inhere',
        'quit',
        'exit',
    ];
    $matches = [];
    if (!$input) {
        return $samples;
    }

    // Get all matches based on the entire input buffer
    foreach ($samples as $phrase) {
        // Only add the end of the input (where this word begins)
        // to the matches array
        $matches[] = substr($phrase, $index);
    }

    return $matches;
}

$ok = readline_completion_function('your_callback');

// 使用 Tab 键测试一下吧
// $line = \Toolkit\Cli\Cli::readln('please input> ');
$line = trim(readline("please input> "));
if (!empty($line)) {
    readline_add_history($line);
}

echo $line, PHP_EOL; // 当前输入的命令信息
// 如果命令是 exit 或者 quit ，就退出程序执行
if($line === 'exit' || $line === 'quit'){
    exit('bye');
}
