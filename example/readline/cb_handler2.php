<?php
/**
 * https://www.jb51.net/article/212496.htm
 */

use Toolkit\Cli\Util\Readline;

require dirname(__DIR__) . '/../test/bootstrap.php';

// 输出的内容进入这个回调函数中
function rl_callback($ret)
{
    global $c, $prompting;

    echo "您输入的内容是: $ret\n";
    $c++;

    readline_add_history($ret);

    // 限制了就调用10次，也可以通过命令行输入的内容来判断，比如上面的 exit 那种进行退出
    if ($c > 10) {
        $prompting = false;
        // 移除上一个安装的回调函数句柄并且恢复终端设置
        readline_callback_handler_remove();
    } else {
        // 继续进行递归回调
        readline_callback_handler_install("[$c] 输入点什么内容: ", 'rl_callback');
    }
}

$c = 1;
$prompting = true;

// 初始化一个 readline 回调接口，然后终端输出提示信息并立即返回，需要等待 readline_callback_read_char() 函数调用后才会进入到回调函数中
readline_callback_handler_install("[$c] 输入点什么内容: ", 'rl_callback');

// 当 $prompting 为 ture 时，一直等待输入信息
while ($prompting) {
    $w = null;
    $e = null;
    $r = [STDIN];
    $n = stream_select($r, $w, $e, null);
    if ($n && in_array(STDIN, $r, true)) {
        // 当一个行被接收时读取一个字符并且通知 readline 调用回调函数
        readline_callback_read_char();
    }
}

echo "结束，完成所有输入！\n";
// [1] 输入点什么内容: A
// 您输入的内容是: A
// [2] 输入点什么内容: B
// 您输入的内容是: B
// [3] 输入点什么内容: C
// 您输入的内容是: C
// [4] 输入点什么内容: D
// 您输入的内容是: D
// [5] 输入点什么内容: E
// 您输入的内容是: E
// [6] 输入点什么内容: F
// 您输入的内容是: F
// [7] 输入点什么内容: G
// 您输入的内容是: G
// [8] 输入点什么内容: H
// 您输入的内容是: H
// [9] 输入点什么内容: I
// 您输入的内容是: I
// [10] 输入点什么内容: J
// 您输入的内容是: J
// 结束，完成所有输入！

// print_r(readline_list_history());
Readline::setListTmpFile(__DIR__ . '/cb_handler2.txt');
print_r(Readline::listHistory());
// Array
// (
//     [0] => A
//     [1] => B
//     [2] => C
//     [3] => D
//     [4] => E
//     [5] => F
//     [6] => G
//     [7] => H
//     [8] => I
//     [9] => J
// )