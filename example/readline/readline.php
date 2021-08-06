<?php

require dirname(__DIR__) . '/../test/bootstrap.php';

$line = readline("command："); // 读取命令行交互信息
if (!empty($line)) {
    readline_add_history($line); // 需要手动加入到命令历史记录中
}
echo $line, PHP_EOL; // aaa

$line = readline("command：");
if (!empty($line)) {
    readline_add_history($line);
}

readline_write_history($filename = __DIR__ . '/rl_history.txt');

// 命令历史记录列表
// TIP: on READLINE_LIB=libedit not support the function.
// var_dump(readline_list_history());
var_dump(file_get_contents($filename));
// Array
// (
//     [0] => aaa
//     [1] => bbb
// )

// 当前命令行内部的变量信息
var_dump(readline_info());
// Array
// (
//     [line_buffer] => bbb
//     [point] => 3
//     [end] => 3
//     [mark] => 0
//     [done] => 1
//     [pending_input] => 0
//     [prompt] => 请输入命令：
//     [terminal_name] => xterm-256color
//     [completion_append_character] =>
//     [completion_suppress_append] =>
//     [library_version] => 7.0
//     [readline_name] => other
//     [attempted_completion_over] => 0
// )