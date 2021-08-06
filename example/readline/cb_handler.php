<?php
/**
 * https://www.php.net/manual/zh/function.readline-callback-handler-install.php
 */

require dirname(__DIR__) . '/../test/bootstrap.php';

/**
 * @param int         $count
 * @param string|null $prompt
 *
 * @return string
 * @link https://www.php.net/manual/zh/function.readline-callback-handler-install.php#123075
 */
function handler_read_demo(int $count, string $prompt = null): string
{
    $prev = '';

    // 初始化一个 readline 回调接口，然后终端输出提示信息并立即返回
    // TIP: 第二次调用这个函数不需要移除上一个回调接口，这个函数将自动覆盖旧的接口
    readline_callback_handler_install($prompt ?? " \e[D", function ($input) use (&$prev) {
        echo "Input is: $input\n";
        $prev .= $input . '|';
    });

    $str = '';
    do {
        $r = [STDIN];
        // 配合 stream_select() 时回调的特性非常有用，它允许在 IO 与用户输入 间交叉进行
        $n = stream_select($r, $w, $e, null);

        // TIP: 每输入一个字符都会触发这里
        if ($n && in_array(STDIN, $r, true)) {
            // 当一个行被接收时读取一个字符并且通知 readline 调用回调函数
            readline_callback_read_char();
            // vdump(readline_info());
            // $str = $prev . readline_info('line_buffer');
            $str .= readline_info('line_buffer') . '|';
        }
    } while (mb_strlen($str) < $count); // use strlen if you need the exact byte count

    // 移除上一个安装的回调函数句柄并且恢复终端设置
    readline_callback_handler_remove();

    echo "prev: $prev\n";
    return $str;
}

$str = handler_read_demo(20, 'TEST > ');

echo "str: $str";